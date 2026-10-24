<?php

/**
 * Append an entry to the account change log stored in imas_users.jsondata['chglog'].
 * Re-reads jsondata so it is safe to call after other jsondata writes.
 *
 * @param int   $uid    user whose account was changed
 * @param array $entry  e.g. ['chgpw'=>1, 'oldemail'=>..., 'oldrights'=>..., 'by'=>$adminid]
 */
function addAccountChangeLog($uid, $entry) {
    global $DBH;

    if (empty($entry)) {
        return;
    }
    $stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=?");
    $stm->execute([$uid]);
    $jsondata = $stm->fetchColumn(0);
    if ($jsondata === false) {
        return;
    }
    $jsondata = json_decode($jsondata ?: '', true);
    if (!is_array($jsondata)) {
        $jsondata = [];
    }
    if (!isset($jsondata['chglog']) || !is_array($jsondata['chglog'])) {
        $jsondata['chglog'] = [];
    }
    $now = time();
    while (isset($jsondata['chglog'][$now])) {
        $now++;
    }
    $jsondata['chglog'][$now] = $entry;
    $stm = $DBH->prepare("UPDATE imas_users SET jsondata=? WHERE id=?");
    $stm->execute([json_encode($jsondata), $uid]);
}
