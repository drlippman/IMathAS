<?php

require_once __DIR__. '/JWT.php';

/**
 * Creates a password reset link token for $userid.
 *
 * Tokens are short opaque strings of the form <uid>_<32 hex chars>. Only a SHA-256 hash of
 * the random part is stored, in imas_users.jsondata:
 *  - Regular reset links: jsondata['pwreset'] = [hash, expires, sha256(email)]. Only one is
 *    active at a time; requesting a new one replaces any earlier unused link. Binding to the
 *    email hash means changing the account email invalidates the link.
 *  - Recovery links (sent to the old address after an email/password change):
 *    jsondata['pwrecovery'] = ordered list of [hash, expires]. These are tracked separately
 *    so a regular reset can't revoke them, and are not replaced by newer links;
 *    see revoke_recovery_links().
 */
function make_pwreset_link($userid, $recoverylink = false) {
    global $DBH, $CFG;

    $stm = $DBH->prepare("SELECT jsondata,email FROM imas_users WHERE id=?");
    $stm->execute([$userid]);
    list($jsondata,$email) = $stm->fetch(PDO::FETCH_NUM);

    $jsondata = json_decode($jsondata ?: '', true);
    if (!is_array($jsondata)) {
        $jsondata = [];
    }

    $random = bin2hex(random_bytes(16));
    $hash = hash('sha256', $random);

    if ($recoverylink) {
        // recovery links should last for 24 hours
        $expires = time() + 60*60*24;
        $now = time();
        $list = array_values(array_filter($jsondata['pwrecovery'] ?? [], function ($e) use ($now) {
            return is_array($e) && count($e) == 2 && $e[1] > $now;
        }));
        if (count($list) >= 25) {
            // keep the oldest links; drop the newest so spamming can't push out earlier ones
            array_pop($list);
        }
        $list[] = [$hash, $expires];
        $jsondata['pwrecovery'] = $list;
    } else {
        // otherwise expire in 15 minutes, or use value from $CFG['GEN']['pwresetexpiry']
        $expires = time() + ($CFG['GEN']['pwresetexpiry'] ?? 900);
        // replaces any earlier unused reset link
        $jsondata['pwreset'] = [$hash, $expires, hash('sha256', (string) $email)];
    }
    $stm = $DBH->prepare("UPDATE imas_users SET jsondata=:jsondata WHERE id=:id");
    $stm->execute(array(':jsondata'=>json_encode($jsondata), ':id'=>$userid));

    return $userid . '_' . $random;
}

/**
 * Verifies a reset code. Echoes an error and exits if invalid.
 * Returns ['uid', 'recoverylink', 'jti'] where jti is the stored hash identifying a
 * recovery link (null for regular links).
 */
function verify_pwreset_link($code) {
    global $DBH;

    if (!is_string($code) || !preg_match('/^(\d+)_([0-9a-f]{32})$/', $code, $m)) {
        // not a new-format token; may be an old JWT link still in someone's inbox
        return verify_legacy_pwreset_link($code);
    }
    $uid = (int) $m[1];
    $hash = hash('sha256', $m[2]);

    $stm = $DBH->prepare("SELECT jsondata,email FROM imas_users WHERE id=?");
    $stm->execute([$uid]);
    $row = $stm->fetch(PDO::FETCH_NUM);
    if ($row === false) {
        echo 'Invalid reset link';
        exit;
    }
    list($jsondata,$email) = $row;
    $jsondata = json_decode($jsondata ?: '', true);
    if (!is_array($jsondata)) {
        $jsondata = [];
    }

    // recovery links: must be in the list of unrevoked recovery links
    foreach ($jsondata['pwrecovery'] ?? [] as $e) {
        if (is_array($e) && count($e) == 2 && hash_equals((string) $e[0], $hash)) {
            if ($e[1] < time()) {
                echo 'Reset link has expired';
                exit;
            }
            return ['uid' => $uid, 'recoverylink' => true, 'jti' => $hash];
        }
    }

    // regular reset link: only the most recent one is valid
    $e = $jsondata['pwreset'] ?? null;
    if (is_array($e) && count($e) == 3 && hash_equals((string) $e[0], $hash)) {
        if ($e[1] < time()) {
            echo 'Reset link has expired';
            exit;
        }
        if (!hash_equals((string) $e[2], hash('sha256', (string) $email))) {
            echo 'Reset link no longer valid';
            exit;
        }
        return ['uid' => $uid, 'recoverylink' => false, 'jti' => null];
    }

    echo 'Reset link not valid';
    exit;
}

/**
 * Verifies the old JWT-style reset links. Only needed while links issued before the
 * switch to short tokens may still be unexpired (regular: 15 min, recovery: 24 hours).
 * TODO: remove this, along with JWT.php, after that window has passed.
 */
function verify_legacy_pwreset_link($JWT) {
    global $DBH;
    try {
        $payload = JWT::decode($JWT, null, false);
    } catch (Throwable $e) {
        echo 'Invalid reset link';
        exit;
    }

    if (!isset($payload->uid)) {
        echo 'Invalid reset link';
        exit;
    } else if ($payload->expires < time()) {
        echo 'Reset link has expired';
        exit;
    }

    $stm = $DBH->prepare("SELECT jsondata,email FROM imas_users WHERE id=?");
    $stm->execute([$payload->uid]);
    $row = $stm->fetch(PDO::FETCH_NUM);
    if ($row === false) {
        echo 'Invalid reset link';
        exit;
    }
    list($jsondata,$email) = $row;
    $jsondata = json_decode($jsondata ?: '', true);
    // the (unverified) rl flag only selects which secret to check the signature against
    $secretkey = isset($payload->rl) ? 'pwrecoverysecret' : 'pwresetsecret';
    $code = is_array($jsondata) ? ($jsondata[$secretkey] ?? '') : '';

    if ($code == '') {
        echo 'Reset link not valid';
        exit;
    }
    // now verify signature
    try {
        $payload = JWT::decode($JWT, $code);
    } catch (Throwable $e) {
        echo 'Invalid reset link';
        exit;
    }

    if (!isset($payload->rl) && $payload->email != $email) {
        echo 'Reset link no longer valid';
        exit;
    }
    if (isset($payload->rl)) {
        // must still be in the list of unrevoked recovery links
        $found = false;
        foreach ($jsondata['pwrecovery'] ?? [] as $e) {
            if (is_array($e) && isset($payload->jti) && $e[0] === $payload->jti && $e[1] > time()) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo 'Reset link no longer valid';
            exit;
        }
    }

    return ['uid' => $payload->uid, 'recoverylink' => isset($payload->rl) ? true : false,
        'jti' => $payload->jti ?? null];
}

/**
 * Revokes the recovery link with id $jti and every recovery link issued after it, but not
 * earlier ones. An attacker who makes further account changes gets links sent to the
 * address they control; using those must not kill the link sent to the real owner earlier.
 * Also drops expired entries. Takes and returns the decoded jsondata array.
 */
function revoke_recovery_links($jsondata, $jti) {
    $list = $jsondata['pwrecovery'] ?? [];
    foreach ($list as $i => $e) {
        if (is_array($e) && $e[0] === $jti) {
            $list = array_slice($list, 0, $i);
            break;
        }
    }
    $now = time();
    $list = array_values(array_filter($list, function ($e) use ($now) {
        return is_array($e) && $e[1] > $now;
    }));
    if (count($list) > 0) {
        $jsondata['pwrecovery'] = $list;
    } else {
        unset($jsondata['pwrecovery']);
    }
    return $jsondata;
}
