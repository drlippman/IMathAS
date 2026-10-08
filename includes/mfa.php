<?php

function mfa_showLoginEntryForm($redir, $error = '', $showtrust = true) {
    global $imasroot, $staticroot, $installname, $mathimgurl;
    $pagetitle = _('MFA Entry');
    require_once __DIR__.'/../header.php';
    if ($error !== '') {
        echo '<p class=noticetext>'._('Invalid code - try again').'</p>';
    }
    echo '<p>'._('Enter the 2-factor authentication code from your device').'. ';
    echo _('This code can be found in the Google Authenticator compatible app, like Authy, that you set up when you enabled 2-factor authentication.');
    echo '</p>';
    echo '<form method="POST" action="'.$redir.'">';
    echo '<input type=hidden name=action value="entermfa" />';
    echo '<p>'._('Code: ').'<input size=8 name=mfatoken /></p>';
    if ($showtrust) {
        echo '<p><label><input type=checkbox name=mfatrust /> '._('Do not ask again on this device').'</label></p>';
    }
    foreach ($_POST as $k=>$v) {
        if ($k == 'mfatoken') { continue; }
        echo '<input type=hidden name="'.Sanitize::encodeStringForDisplay($k).'" value="'.Sanitize::encodeStringForDisplay($v).'" />';
    }
    echo '<p><button type=submit>'._('Verify Code').'</button></p>';
    echo '</form>';
    require_once __DIR__.'/../footer.php';
}

/**
 * Returns true if too many recent failed attempts; also expires old failures
 * from $mfadata (in memory only).
 */
function mfa_isLockedOut(&$mfadata) {
    if (isset($mfadata['lastfail']) && time() - $mfadata['lastfail'] > 30) {
        unset($mfadata['failcnt']);
        unset($mfadata['lastfail']);
    }
    return (isset($mfadata['failcnt']) && $mfadata['failcnt'] > 3);
}

/**
 * Checks a code with rate limiting and replay protection. Returns true/false.
 * If $uid > 0, failures are recorded (and logged), and a success updates the
 * replay info. Call mfa_isLockedOut afterwards to tell lockout from a bad code.
 * $mfadata is updated by reference.  On success, it is saved to the DB only if
 * $save is true; pass false if the caller will modify $mfadata further and save it.
 */
function mfa_checkCode(&$mfadata, $code, $uid = 0, $save = true) {
    global $DBH, $CFG;
    if (mfa_isLockedOut($mfadata)) {
        return false;
    }
    require_once __DIR__.'/GoogleAuthenticator.php';
    $MFA = new GoogleAuthenticator();
    $code = (string) $code;
    //check that code is valid and not a replay
    if ($MFA->verifyCode($mfadata['secret'], $code) &&
        ($code != $mfadata['last'] || time() - $mfadata['laston'] > 600)) {
        if ($uid > 0) {
            $mfadata['last'] = $code;
            $mfadata['laston'] = time();
            unset($mfadata['failcnt']);
            unset($mfadata['lastfail']);
            if ($save) {
                $stm = $DBH->prepare("UPDATE imas_users SET mfa = :mfa WHERE id = :uid");
                $stm->execute(array(':uid'=>$uid, ':mfa'=>json_encode($mfadata)));
            }
        }
        return true;
    }
    if ($uid > 0) {
        $mfadata['lastfail'] = time();
        $mfadata['failcnt'] = ($mfadata['failcnt'] ?? 0) + 1;
        $stm = $DBH->prepare("UPDATE imas_users SET mfa = :mfa WHERE id = :uid");
        $stm->execute(array(':uid'=>$uid, ':mfa'=>json_encode($mfadata)));
        if (isset($CFG['cloudwatch_loginlog'])) {
            require_once __DIR__.'/CloudWatchLogger.php';
            addLoginLog('login_failure', $uid, [
                'reason' => 'bad_mfa',
                'mfafailcnt' => $mfadata['failcnt']
            ]);
        }
    }
    return false;
}

function mfa_verify($mfadata, $formaction, $uid = 0, $showtrust = true, $admin = false) {
    global $DBH, $imasroot, $CFG;
    $error = '';
    if (mfa_isLockedOut($mfadata)) {
        echo _("Too many failed attempts.  Wait a minute and try again");
        exit;
    }
    if (mfa_checkCode($mfadata, $_POST['mfatoken'], $uid, false)) {
        if ($uid > 0) {
            require_once __DIR__.'/GoogleAuthenticator.php';
            $MFA = new GoogleAuthenticator();
            if (isset($_POST['mfatrust'])) {
                $trusttoken = $MFA->createSecret();
                // admin trust (enabling admin features) is tracked separately from login trust
                $cookiename = $admin ? 'gat' : 'gatl';
                $trustkey = $admin ? 'trusted' : 'logintrusted';
                setsecurecookie($cookiename, $trusttoken, time()+60*60*24*365*10, true);
                if (!isset($mfadata[$trustkey])) {
                    $mfadata[$trustkey] = array();
                }
                $mfadata[$trustkey][] = $trusttoken;
            }
            $stm = $DBH->prepare("UPDATE imas_users SET mfa = :mfa WHERE id = :uid");
            $stm->execute(array(':uid'=>$uid, ':mfa'=>json_encode($mfadata)));
        }
        return true;
    } else {
        if ($uid > 0 && mfa_isLockedOut($mfadata)) {
            echo _("Too many failed attempts.  Wait a minute and try again");
            exit;
        }
        mfa_showLoginEntryForm($formaction, 'error', $showtrust);
        exit;
    }
    return false;
}
