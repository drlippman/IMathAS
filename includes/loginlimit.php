<?php
// Rate limiting of password login attempts.  State is kept in the user's
// jsondata: login_errors (consecutive failures) and login_blockuntil.

/**
 * Is the account currently blocked from password attempts?
 * @param string|array|null $jsondata  imas_users.jsondata, raw or decoded
 */
function login_isBlocked($jsondata) {
    if (is_string($jsondata)) {
        $jsondata = json_decode($jsondata, true);
    }
    return (is_array($jsondata) && isset($jsondata['login_blockuntil']) &&
        time() < $jsondata['login_blockuntil']);
}

/**
 * Records a failed password attempt, blocking for a minute after 3 failures.
 * If $jsondata (raw imas_users.jsondata) is not given, it is read from the DB.
 */
function login_recordFailure($uid, $jsondata = null) {
    global $DBH;
    if ($jsondata === null) {
        $stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=:id");
        $stm->execute(array(':id' => $uid));
        $jsondata = $stm->fetchColumn();
    }
    $json_data = json_decode($jsondata ?: '', true);
    if (!is_array($json_data)) {
        $json_data = array();
    }
    $json_data['login_errors'] = ($json_data['login_errors'] ?? 0) + 1;
    if ($json_data['login_errors'] > 3) {
        $json_data['login_blockuntil'] = time() + 60;
    }
    $stm = $DBH->prepare("UPDATE imas_users SET jsondata=:jsondata WHERE id=:id");
    $stm->execute(array(':jsondata' => json_encode($json_data), ':id' => $uid));
}

/**
 * Returns the raw jsondata string with failure tracking removed.
 * Does not touch the DB; use when the caller will save jsondata itself.
 */
function login_clearFailures($jsondata) {
    $json_data = json_decode($jsondata ?: '', true);
    if (is_array($json_data) && (isset($json_data['login_errors']) || isset($json_data['login_blockuntil']))) {
        unset($json_data['login_errors']);
        unset($json_data['login_blockuntil']);
        return json_encode($json_data);
    }
    return $jsondata;
}

/**
 * Clears failure tracking in the DB after a successful password check,
 * writing only if there is something to clear.
 */
function login_resetFailures($uid, $jsondata) {
    global $DBH;
    $new = login_clearFailures($jsondata);
    if ($new !== $jsondata) {
        $stm = $DBH->prepare("UPDATE imas_users SET jsondata=:jsondata WHERE id=:id");
        $stm->execute(array(':jsondata' => $new, ':id' => $uid));
    }
}
