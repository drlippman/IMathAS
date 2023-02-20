<?php

function mfa_showLoginEntryForm($redir, $error = '', $showtrust = true) {
    global $imasroot, $staticroot, $installname;
    require(__DIR__.'/../header.php');
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
    require(__DIR__.'/../footer.php');
}

function mfa_verify($mfadata, $formaction, $uid = 0, $showtrust = true) {
    global $DBH, $imasroot;
    $error = '';
    require(__DIR__.'/GoogleAuthenticator.php');
    $MFA = new GoogleAuthenticator();
    if (isset($mfadata['lastfail']) && time() - $mfadata['lastfail'] > 30) {
        unset($mfadata['failcnt']);
        unset($mfadata['lastfail']);
    }
    if (isset($mfadata['failcnt']) && $mfadata['failcnt'] > 3) {
        echo _("Too many failed attempts.  Wait a minute and try again");
        exit;
    }
    //check that code is valid and not a replay
    if ($MFA->verifyCode($mfadata['secret'], $_POST['mfatoken']) &&
        ($_POST['mfatoken'] != $mfadata['last'] || time() - $mfadata['laston'] > 600)) {
        if ($uid > 0) {
            $mfadata['last'] = $_POST['mfatoken'];
            $mfadata['laston'] = time();
            unset($mfadata['failcnt']);
            unset($mfadata['lastfail']);
            if (isset($_POST['mfatrust'])) {
                $trusttoken = $MFA->createSecret();
                setcookie('gatl', $trusttoken, time()+60*60*24*365*10, $imasroot.'/', '', true, true);
                if (!isset($mfadata['logintrusted'])) {
                    $mfadata['logintrusted'] = array();
                }
                $mfadata['logintrusted'][] = $trusttoken;
            }
            $stm = $DBH->prepare("UPDATE imas_users SET mfa = :mfa WHERE id = :uid");
            $stm->execute(array(':uid'=>$uid, ':mfa'=>json_encode($mfadata)));
        }
        return true;
    } else {
        if ($uid > 0) {
            $mfadata['lastfail'] = time();
            if (isset($mfadata['failcnt'])) {
                $mfadata['failcnt']++;
            } else {
                $mfadata['failcnt'] = 1;
            }
            $stm = $DBH->prepare("UPDATE imas_users SET mfa = :mfa WHERE id = :uid");
            $stm->execute(array(':uid'=>$uid, ':mfa'=>json_encode($mfadata)));
            if ($mfadata['failcnt'] > 3) {
                echo _("Too many failed attempts.  Wait a minute and try again");
                exit;
            }
        }
        mfa_showLoginEntryForm($formaction, 'error', $showtrust);
        exit;
    }
    return false;
}
