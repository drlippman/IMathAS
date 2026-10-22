<?php

require_once __DIR__. '/JWT.php';

function make_pwreset_link($userid, $recoverylink = false) {
    global $DBH, $CFG;

    $stm = $DBH->prepare("SELECT jsondata,email FROM imas_users WHERE id=?");
    $stm->execute([$userid]);
    list($jsondata,$email) = $stm->fetch(PDO::FETCH_NUM);

    // Signing secrets are kept in jsondata until a reset completes (see actions.php).
    // Recovery links (sent to the old address after an email/password change) have their own
    // secret so a regular reset can't revoke them, and are also tracked in order in
    // jsondata['pwrecovery']; see revoke_recovery_links().
    $secretkey = $recoverylink ? 'pwrecoverysecret' : 'pwresetsecret';
    $jsondata = json_decode($jsondata ?: '', true);
    if (!is_array($jsondata)) {
        $jsondata = [];
    }
    $code = $jsondata[$secretkey] ?? '';
    $needsave = false;
    if ($code === '') {
        $code = bin2hex(random_bytes(16));
        $jsondata[$secretkey] = $code;
        $needsave = true;
    }

    if ($recoverylink) {
        // recovery links should last for 24 hours
        $expiretime = 60*60*24;
    } else {
        // otherwise expire in 15 minutes, or use value from $CFG['GEN']['pwresetexpiry']
        $expiretime = $CFG['GEN']['pwresetexpiry'] ?? 900;
    }
    $payload = [
        'uid' => $userid,
        'expires' => time() + $expiretime,
        'email' => $email
    ];
    if ($recoverylink) {
        $payload['rl'] = 1;
        $payload['jti'] = bin2hex(random_bytes(16));
        $now = time();
        $list = array_values(array_filter($jsondata['pwrecovery'] ?? [], function ($e) use ($now) {
            return is_array($e) && count($e) == 2 && $e[1] > $now;
        }));
        if (count($list) >= 25) {
            // keep the oldest links; drop the newest so spamming can't push out earlier ones
            array_pop($list);
        }
        $list[] = [$payload['jti'], $payload['expires']];
        $jsondata['pwrecovery'] = $list;
        $needsave = true;
    }
    if ($needsave) {
        $stm = $DBH->prepare("UPDATE imas_users SET jsondata=:jsondata WHERE id=:id");
        $stm->execute(array(':jsondata'=>json_encode($jsondata), ':id'=>$userid));
    }

    return JWT::encode($payload, $code);
}

function verify_pwreset_link($JWT) {
    global $DBH;
    try {
        $payload = JWT::decode($JWT, null, false);
    } catch (Exception $e) {
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
    } catch (Exception $e) {
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
