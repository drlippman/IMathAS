<?php

require_once __DIR__. '/JWT.php';

function make_pwreset_link($userid, $recoverylink = false) {
    global $DBH, $CFG;

    $stm = $DBH->prepare("SELECT remoteaccess,email FROM imas_users WHERE id=?");
    $stm->execute([$userid]);
    list($code,$email) = $stm->fetch(PDO::FETCH_NUM);

    if ($code === null || $code == '') {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $code = '';
        for ($i=0;$i<10;$i++) {
            $code .= substr($chars,rand(0,61),1);
        }
        $query = "UPDATE imas_users SET remoteaccess=:code WHERE id=:id";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':code'=>$code, ':id'=>$userid));
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
    }

    return JWT::encode($payload, $code);
}

function verify_pwreset_link($JWT) {
    global $DBH;
    
    $payload = JWT::decode($JWT, null, false);

    if (!isset($payload->uid)) {
        echo 'Invalid reset link';
        exit;
    } else if ($payload->expires < time()) {
        echo 'Reset link has expired';
        exit;
    }

    $stm = $DBH->prepare("SELECT remoteaccess,email FROM imas_users WHERE id=?");
    $stm->execute([$payload->uid]);
    list($code,$email) = $stm->fetch(PDO::FETCH_NUM);

    // now verify signature
    $payload = JWT::decode($JWT, $code);

    if (!isset($payload->rl) && $payload->email != $email) {
        echo 'Reset link no longer valid';
        exit;
    }

    return ['uid' => $payload->uid, 'recoverylink' => isset($payload->rl) ? true : false];
}
