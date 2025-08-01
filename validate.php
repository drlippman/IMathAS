<?php
//IMathAS:  Checks user's login - prompts if none.
//(c) 2006 David Lippman

header('P3P: CP="ALL CUR ADM OUR"');

$curdir = rtrim(dirname(__FILE__), '/\\');

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['validate'])) {
    require_once $CFG['hooks']['validate'];
}

session_start();
$sessionid = session_id();

$myrights = 0;
$myspecialrights = 0;
$ispublic = false;
if (isset($CFG['CPS']['theme'])) {
    $defaultcoursetheme = $CFG['CPS']['theme'][0];
} else if (!isset($defaultcoursetheme)) {
    $defaultcoursetheme = "modern.css";
}
$coursetheme = $defaultcoursetheme; //will be overwritten later if set
if (!isset($CFG['CPS']['miniicons'])) {
    $CFG['CPS']['miniicons'] = array(
        'assess' => 'assess_tiny.png',
        'drill' => 'drill_tiny.png',
        'inline' => 'inline_tiny.png',
        'linked' => 'html_tiny.png',
        'forum' => 'forum_tiny.png',
        'wiki' => 'wiki_tiny.png',
        'folder' => 'folder_tiny.png',
        'tree' => 'folder_tree_tiny.png',
        'calendar' => '1day.png');
}

//check for bad sessionids.
if (strlen($sessionid) < 10) {
    if (function_exists('session_regenerate_id')) {session_regenerate_id();}
    echo sprintf(_("Error.  Please %s try again%s"), "<a href=\"$imasroot/index.php\">", "<a href=\"$imasroot/index.php\">", "</a>");
    exit;
}

if (!empty($_SESSION['userid'])) { // logged in
    $userid = $_SESSION['userid'];
    $tzoffset = $_SESSION['tzoffset'] ?? 0;
    $tzname = '';
    if (isset($_SESSION['tzname']) && $_SESSION['tzname'] != '') {
        if (date_default_timezone_set($_SESSION['tzname'])) {
            $tzname = $_SESSION['tzname'];
        }
    }

    $lastSessionTime = isset($GLOBALS['sessionLastAccess']) ? $GLOBALS['sessionLastAccess'] : ($_SESSION['time'] ?? 0);
    if ((time() - $lastSessionTime) > 24 * 60 * 60 && (!isset($_POST) || count($_POST) == 0)) {
        $wasLTI = isset($_SESSION['ltiitemtype']);
        unset($userid);
        $_SESSION = array();
        if ($wasLTI) {
            require_once __DIR__."/header.php";
            echo _('Your session has expired. Please go back to your LMS and open this assignment again.');
            require_once 'footer.php';
            exit;
        }
    }
}

$hasusername = isset($userid);
$haslogin = isset($_POST['password']) && isset($_POST['username']);

if (!$hasusername && !$haslogin && isset($_GET['guestaccess']) && isset($CFG['GEN']['guesttempaccts'])) {
    if (empty($_SERVER['HTTP_REFERER'])) {
        if (isset($_GET['cid'])) {
            $cid = Sanitize::onlyInt($_GET['cid']);
            $stm = $DBH->prepare("SELECT istemplate,available FROM imas_courses WHERE id=?");
            $stm->execute(array($cid));
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if (($row['istemplate'] & 8) != 8 || $row['available'] >= 4) {
                echo '<p>' . _('This course does not allow guest access.') . '</p>';
                exit;
            }
        } else {
            $cid = 0;
        }

        $placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
        if (isset($_SERVER['QUERY_STRING'])) {
            $querys = '?' . Sanitize::fullQueryString($_SERVER['QUERY_STRING']) . '&guestaccess=true';
        } else {
            $querys = '?guestaccess=true';
        }
        $formAction = $GLOBALS['basesiteurl'] . substr($_SERVER['SCRIPT_NAME'], strlen($imasroot)) . Sanitize::encodeStringForDisplay($querys);
        
        require_once __DIR__."/header.php";
        echo '<form method=post action="' . $formAction . '">';
        echo '<p>' . _('You have requested guest access to a course.') . '</p>';
        echo '<p><button type=button onclick="location.href=\'' . $imasroot . '/index.php\'">', _('Nevermind'), '</button> ';
        echo '<button type=submit>' . _('Continue') . '</button>';
        echo '<input type=hidden id=tzname name=tzname />';
        echo '<script type="text/javascript">
     $(function() {
       var tz = jstz.determine();
       document.getElementById("tzname").value = tz.name();
     });
     </script>';
        echo '</form>';
        require_once __DIR__ . '/footer.php';
        exit;
    }
    $haslogin = true;
    $_POST['username'] = 'guest';
    $_POST['mathdisp'] = 0;
    $_POST['graphdisp'] = 2;
    if (!isset($_POST['tzname'])) { // set an arbitrary default for guests
        $_POST['tzname'] = 'America/Los_Angeles';
    }
}
if (isset($_GET['checksess']) && !$hasusername) {
    echo '<html><body>';
    echo _('Unable to establish a session. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again.');
    echo '</body></html>';
    exit;
}
$verified = false;
$err = '';
$now = time();
//Just put in username and password, trying to log in
if ($haslogin && !$hasusername) {

    if (isset($CFG['GEN']['guesttempaccts']) && isset($_POST['username']) && $_POST['username'] == 'guest') { // create a temp account when someone logs in w/ username: guest
        $stm = $DBH->query('SELECT ver FROM imas_dbschema WHERE id=2');
        $guestcnt = $stm->fetchColumn(0);
        $stm = $DBH->query('UPDATE imas_dbschema SET ver=ver+1 WHERE id=2');

        if (isset($CFG['GEN']['homelayout'])) {
            $homelayout = $CFG['GEN']['homelayout'];
        } else {
            $homelayout = '|0,1,2||0,1';
        }
        $query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,homelayout) ";
        $query .= "VALUES (:guestcnt,'',5,'Guest','Account','none@none.com',0,:homelayout)";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':guestcnt' => "guestacct$guestcnt", ':homelayout' => $homelayout));
        $userid = $DBH->lastInsertId();
        $query = "SELECT id FROM imas_courses WHERE istemplate > 0 AND (istemplate&8)=8 AND available<4";
        if (isset($_GET['cid'])) {$query .= ' AND id=:id';}
        $stm = $DBH->prepare($query);
        if (isset($_GET['cid'])) {
            $stm->execute(array(':id' => $_GET['cid']));
        } else {
            $stm->execute(array());
        }
        if ($stm->rowCount() > 0) {
            $query = "INSERT INTO imas_students (userid,courseid) VALUES ";
            $i = 0;
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                if ($i > 0) {$query .= ',';}
                $query .= "($userid,{$row[0]})"; //INT's from DB - safe
                $i++;
            }
            $DBH->query($query);
        }

        $line['id'] = $userid;
        $line['rights'] = 5;
        $line['groupid'] = 0;
        $line['jsondata'] = '';
        $_POST['password'] = 'temp';
        if (isset($CFG['GEN']['newpasswords'])) {
            require_once "includes/password.php";
            $line['password'] = password_hash('temp', PASSWORD_DEFAULT);
        } else {
            $line['password'] = md5('temp');
        }
        $_POST['usedetected'] = true;
    } else {
        $query = "SELECT id,password,rights,groupid";
        if (strpos(basename($_SERVER['PHP_SELF']), 'upgrade.php') === false) {
            $query .= ',jsondata,mfa';
        }
        $query .= " FROM imas_users WHERE SID=:SID";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':SID' => $_POST['username']));
        $line = $stm->fetch(PDO::FETCH_ASSOC);
        if ($line != false && isset($line['jsondata'])) {
            $json_data = json_decode($line['jsondata'], true);
            if (isset($json_data['login_blockuntil']) && time() < $json_data['login_blockuntil']) {
                echo _('Too many invalid logins - please wait a minute before trying again, or use the forgot password link to reset your password');
                exit;
            }
        }
    }
    if (isset($CFG['GEN']['newpasswords'])) {
        require_once "includes/password.php";
    }
    if (!empty($line['mfa'])) {
        require_once __DIR__.'/includes/mfa.php';
        $mfadata = json_decode($line['mfa'], true);
        if (isset($_SERVER['QUERY_STRING'])) {
            $querys = '?' . Sanitize::fullQueryString($_SERVER['QUERY_STRING']);
        } else {
            $querys = '';
        }
        $formAction = $GLOBALS['basesiteurl'] . substr($_SERVER['SCRIPT_NAME'], strlen($imasroot)) . Sanitize::encodeStringForDisplay($querys);    
    }

    if (($line != false) && (
        ((!isset($CFG['GEN']['newpasswords']) || $CFG['GEN']['newpasswords'] != 'only') && ((md5($line['password'] . $_SESSION['challenge']) == $_POST['password']) || ($line['password'] == md5($_POST['password']))))
        || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['password'], $line['password']))
    )) {
        if (empty($_POST['tzname']) && (!isset($_POST['tzoffset']) || $_POST['tzoffset'] == '') && strpos(basename($_SERVER['PHP_SELF']), 'upgrade.php') === false) {
            echo _('Uh oh, something went wrong.  Please go back and try again');
            exit;
        }
        $userid = $line['id'];
        $groupid = $line['groupid'];
        //for upgrades times:
        // if ($line['rights']<100) {
        //     echo "The system is currently down for maintenence.  Please try again later.";
        //     exit;
        // }
        //
        if ($line['rights'] == 0) {
            require_once __DIR__."/header.php";
            echo _("You have not yet confirmed your registration.  You must respond to the email that was sent to you by IMathAS.");
            require_once __DIR__ . '/footer.php';
            exit;
        }

        //$_SESSION['useragent'] = $_SERVER['HTTP_USER_AGENT'];
        //$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        if (!isset($_POST['mfatoken'])) {
            // only do this stuff on initial pass from login page
            $_SESSION['secsalt'] = generaterandstring();

            if (!isset($_POST['tzoffset'])) {
                $_POST['tzoffset'] = 0;
            }
            if (isset($_POST['tzname'])) {
                $_SESSION['logintzname'] = $_POST['tzname'];
            }
            if (isset($CFG['static_server']) && !empty($_POST['static_check'])) {
                $_SESSION['static_ok'] = 1;
            }
            require_once "$curdir/includes/userprefs.php";
            generateuserprefs($userid);

            $_SESSION['tzoffset'] = floatval($_POST['tzoffset']);
            if (!empty($_POST['tzname']) && strpos(basename($_SERVER['PHP_SELF']), 'upgrade.php') === false) {
                $_SESSION['tzname'] = $_POST['tzname'];
            }

            if (isset($json_data['login_errors'])) {
                unset($json_data['login_errors']);
                unset($json_data['login_blockuntil']);
                $line['jsondata'] = json_encode($json_data);
            }

            if (isset($CFG['GEN']['newpasswords']) && strlen($line['password']) == 32) { //old password - rehash it
                $hashpw = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess,password=:password,jsondata=:jsondata WHERE id=:id");
                $stm->execute(array(':lastaccess' => $now, ':password' => $hashpw, ':jsondata' => $line['jsondata'], ':id' => $userid));
            } else {
                $stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess,jsondata=:jsondata WHERE id=:id");
                $stm->execute(array(':lastaccess' => $now, ':jsondata' => $line['jsondata'], ':id' => $userid));
            }
        }

        // check for MFA before actual login
        if (!empty($line['mfa']) && 
            (empty($mfadata['mfatype']) || $mfadata['mfatype'] == 'all') 
        ) {
            $loginmfaverified = false;
            if (isset($_COOKIE['gatl']) && isset($mfadata['logintrusted'])) {
                foreach ($mfadata['logintrusted'] as $mfatoken) {
                    if ($mfatoken == $_COOKIE['gatl']) {
                        $loginmfaverified = true;
                        break;
                    }
                }
            }
            if (!$loginmfaverified && isset($_POST['mfatoken'])) {
                $loginmfaverified = mfa_verify($mfadata, $formAction, $line['id']);
            }
            if (!$loginmfaverified) {
                mfa_showLoginEntryForm($formAction);
                exit;
            }
        }
        // do actual login
        $_SESSION['userid'] = $userid;
        $_SESSION['time'] = $now;
        $_SESSION['started'] = $now;
        unset($loginmfaverified);
        unset($_SESSION['challenge']); //challenge is used up - forget it.

        //call hook, if defined
        if (function_exists('onLogin')) {
            onLogin();
        }

        if (!empty($_SERVER['QUERY_STRING'])) {
            $querys = '?' . Sanitize::fullQueryString($_SERVER['QUERY_STRING']) . (isset($addtoquerystring) ? '&' . Sanitize::fullQueryString($addtoquerystring) : '');
        } else {
            $querys = (!empty($addtoquerystring) ? '?' . Sanitize::fullQueryString($addtoquerystring) : '');
        }

        $needToForcePasswordReset = false;
        if ($_POST['username'] != 'guest') {
            if (isset($CFG['acct']['passwordMinlength']) && strlen($_POST['password']) < $CFG['acct']['passwordMinlength']) {
                $needToForcePasswordReset = true;
            } else if (isset($CFG['acct']['passwordFormat'])) {
                require_once "includes/newusercommon.php";
                if (!checkFormatAgainstRegex($_POST['password'], $CFG['acct']['passwordFormat'])) {
                    $needToForcePasswordReset = true;
                }
            }
        }
        // checks if the array $querys is empty
        if (!empty($querys)) {
            $rqp = "&r=" . Sanitize::randomQueryStringParam();
        } else {
            $rqp = "?r=" . Sanitize::randomQueryStringParam();
        }
        
        if ($line['rights'] > 74 && !empty($CFG['reqadminmfa']) && empty($line['mfa'])) {
            header('Location: ' . $GLOBALS['basesiteurl'] . '/forms.php?action=adminmfanotice&r=' . Sanitize::randomQueryStringParam());
        } else if ($needToForcePasswordReset) {
            header('Location: ' . $GLOBALS['basesiteurl'] . '/forms.php?action=forcechgpwd&r=' . Sanitize::randomQueryStringParam());
        } else {
            header('Location: ' . $GLOBALS['basesiteurl'] . substr($_SERVER['SCRIPT_NAME'], strlen($imasroot)) . $querys . $rqp);
        }
        exit;
    } else {
        if (empty($_SESSION['challenge'])) {
            $badsession = true;
        } else {
            $badsession = false;
        }

        if ($line != false) {
            if (!isset($json_data['login_errors'])) {
                $json_data['login_errors'] = 0;
            }
            $json_data['login_errors']++;
            if ($json_data['login_errors'] > 3) {
                $json_data['login_blockuntil'] = time() + 60;
            }
            $stm = $DBH->prepare("UPDATE imas_users SET jsondata=:jsondata WHERE id=:id");
            $stm->execute(array(':jsondata' => json_encode($json_data), ':id' => $line['id']));
        }
        /*  For login error tracking - requires add'l table
    if ($line==null) {
    $err = "Bad SID";
    } else if ($_SESSION['challenge']!=$_POST['challenge']) {
    $err = "Bad Challenge (post:{$_POST['challenge']}, sess: ".addslashes($_SESSION['challenge']).")";
    } else {
    $err = "Bad PW";
    }
    $err .= ','.addslashes($_SERVER['HTTP_USER_AGENT']);
    $query = "INSERT INTO imas_failures (SID,challenge,sent,type) VALUES ";
    $query .= "('{$_POST['username']}','{$_SESSION['challenge']}','{$_POST['password']}','$err')";
    mysql_query($query) or die("Query failed : " . mysql_error());
     */

    }

}

//has logged in already
if ($hasusername) {
    //check validity, if desired
    //if (($_SESSION['useragent'] != $_SERVER['HTTP_USER_AGENT']) || ($_SESSION['ip'] != $_SERVER['REMOTE_ADDR'])) {
    //suggests sidejacking.  Delete session and require relogin
    //   caused issues so removed
    //}
    //$username = $_COOKIE['username'];

    // load OWASP CSRF Protector
    if (!empty($CFG['use_csrfp']) && (!isset($init_skip_csrfp) || (isset($init_skip_csrfp) && false == $init_skip_csrfp))) {
        require_once __DIR__ . "/csrfp/simplecsrfp.php";
        csrfProtector::init();
    }

    $query = "SELECT SID,rights,groupid,LastName,FirstName,deflib";
    if (strpos(basename($_SERVER['PHP_SELF']), 'upgrade.php') === false) {
        $query .= ',listperpage,hasuserimg,theme,specialrights,FCMtoken,forcepwreset,mfa,lastemail';
    }
    $query .= " FROM imas_users WHERE id=:id";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':id' => $userid));
    $line = $stm->fetch(PDO::FETCH_ASSOC);
    if (!isset($_SESSION['started'])) {
        $_SESSION['started'] = time();
    }
    if ($line['lastemail'] > $_SESSION['started']) {
        // lastemail actually records last password reset
        // if password has been reset since session began, force relogin in
        user_logout();
        header('Location: ' . $GLOBALS['basesiteurl'] . '/index.php?r=' . Sanitize::randomQueryStringParam());
        exit;
    }
    $username = $line['SID'];
    $myrights = $line['rights'];
    if ($myrights == 12) {
        $myrights = 5; // treat pending instructors like guest users.
    }
    $myspecialrights = $line['specialrights'];
    $userHasAdminMFA = false;
    if (($myrights > 40 || $myspecialrights > 0) && !empty($line['mfa']) && empty($_SESSION['mfaadminverified'])) {
        $mfadata = json_decode($line['mfa'], true);
        if (isset($_COOKIE['gat']) && isset($mfadata['trusted'])) {
            foreach ($mfadata['trusted'] as $mfatoken) {
                if ($mfatoken == $_COOKIE['gat']) {
                    $_SESSION['mfaadminverified'] = true;
                    break;
                }
            }
        }
        if (empty($_SESSION['mfaadminverified'])) {
            $userHasAdminMFA = true;
            $myrights = 40;
            $myspecialrights = 0;
        }
    }
    $groupid = $line['groupid'];
    $userdeflib = $line['deflib'];
    $listperpage = intval($line['listperpage']);
    if ($listperpage < 1) {$listperpage = 20;}
    $selfhasuserimg = $line['hasuserimg'];
    /*$usertheme = $line['theme'];
    if (isset($usertheme) && $usertheme!='') {
    $coursetheme = $usertheme;
    }
     */
    $FCMtoken = $line['FCMtoken'];
    $userfullname = strip_tags($line['FirstName'] . ' ' . $line['LastName']);
    $inInstrStuView = false;
    if (!isset($_SESSION['userprefs']) && strpos(basename($_SERVER['PHP_SELF']), 'upgrade.php') === false) {
        //userprefs are missing!  They should be defined from initial session setup
        //we should never be here. But in case we are, reload prefs
        require_once "$curdir/includes/userprefs.php";
        generateuserprefs($userid);
    }
    if (isset($_SESSION['userprefs']['usertheme']) && strcmp($_SESSION['userprefs']['usertheme'], '0') != 0) {
        $coursetheme = $_SESSION['userprefs']['usertheme'];
    }

    if (!empty($line['forcepwreset']) && (empty($_GET['action']) || $_GET['action'] != 'forcechgpwd')
        && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltirole'] != 'learner')
        && !isset($_SESSION['emulateuseroriginaluser'])) {
        header('Location: ' . $GLOBALS['basesiteurl'] . '/forms.php?action=forcechgpwd&r=' . Sanitize::randomQueryStringParam());
        exit;
    }

    //call hook function in validate hook, if defined
    if (function_exists('alreadyLoggededIn')) {
        alreadyLoggededIn($userid);
    }

    $basephysicaldir = rtrim(dirname(__FILE__), '/\\');
    if ($myrights == 100 && (isset($_GET['debug']) || isset($_SESSION['debugmode']))) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL ^ E_NOTICE);
        if (isset($_GET['debug'])) {
            $_SESSION['debugmode'] = true;
        }
    }
    if (isset($_GET['fullwidth'])) {
        $_SESSION['usefullwidth'] = true;
        $usefullwidth = true;
    } else if (isset($_SESSION['usefullwidth'])) {
        $usefullwidth = true;
    }

    if (isset($_GET['mathdisp'])) {
        $_SESSION['mathdisp'] = intval($_GET['mathdisp']);
    }

    if (isset($_GET['readernavon'])) {
        $_SESSION['readernavon'] = true;
    }
    if (isset($_GET['useflash'])) {
        $_SESSION['useflash'] = true;
    }
    if (isset($_GET['graphdisp'])) {
        $_SESSION['graphdisp'] = $_GET['graphdisp'];
    }
    if (!isset($_SESSION['secsalt'])) { // if it was lost somehow
        $_SESSION['secsalt'] = generaterandstring();
    }
    if (!function_exists('isDiagnostic')) {
        function isDiagnostic() {
            return isset($_SESSION['isdiag']); // && strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false) {
        }
    }
    if (isDiagnostic()) {
        $urlparts = parse_url($_SERVER['PHP_SELF']);
        if ($_SESSION['diag_aver'][0] == 1 &&
            !in_array(basename($urlparts['path']), array('showtest.php', 'ltiuserprefs.php'))
        ) {
            header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?r=" . Sanitize::randomQueryStringParam());
            exit;
        } else if ($_SESSION['diag_aver'][0] > 1 &&
            strpos($_SERVER['PHP_SELF'], 'assess2/') === false &&
            !in_array(basename($urlparts['path']), array('ltiuserprefs.php'))
        ) {
            $querystr = 'cid=' . Sanitize::onlyInt($_SESSION['diag_aver'][1]);
            $querystr .= '&aid=' . Sanitize::onlyInt($_SESSION['diag_aver'][2]);
            header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?" . $querystr);
            exit;
        }
    }
    // update session time, if not handled by sessionLastAccess
    // this is used by local and redis sessions; db sessions handle via sessionLastAccess
    if (empty($GLOBALS['sessionLastAccess'])) {
        $_SESSION['time'] = time();
    }

    if (isset($_SESSION['ltiitemtype']) && $_SERVER['PHP_SELF'] == $imasroot . '/index.php') {
        if ($myrights > 18) {
            foreach ($_SESSION as $k => $v) {
                if (substr($k, 0, 3) == 'lti') {
                    unset($_SESSION[$k]);
                }
            }
            setcookie('fromltimenu', '', time() - 3600);
        } else if ($_SESSION['ltiitemtype'] == 0 && $_SESSION['ltirole'] == 'learner') {
            require_once __DIR__ . '/includes/userutils.php';
            user_logout();
            header('Location: ' . $GLOBALS['basesiteurl'] . '/index.php?r=' . Sanitize::randomQueryStringParam());
            exit;
        }
    }

    if (isset($_SESSION['ltiitemtype'])) {
        $hideAllHeaderNav = true;
        if ($_SESSION['ltiitemtype'] == 1) {
            if (strpos(basename($_SERVER['PHP_SELF']), 'showtest.php') === false && isset($_GET['cid']) && $_SESSION['ltiitemid'] != $_GET['cid']) {
                echo "You do not have access to this page";
                echo "<a href=\"$imasroot/course/course.php?cid={$_SESSION['ltiitemid']}\">Return to course page</a>";
                exit;
            }
            $breadcrumbbase = '';
        } else if ($_SESSION['ltiitemtype'] == 0 && $_SESSION['ltirole'] == 'learner') {
            if (isset($_GET['cid'])) {
                if (isset($_SESSION['ltiitemver']) && $_SESSION['ltiitemver'] > 1) {
                    $breadcrumbbase = "<a href=\"$imasroot/assess2/?cid=" . Sanitize::courseId($_GET['cid']) . "&aid={$_SESSION['ltiitemid']}\">Assignment</a> &gt; ";
                } else {
                    $breadcrumbbase = "<a href=\"$imasroot/assessment/showtest.php?cid=" . Sanitize::courseId($_GET['cid']) . "&id={$_SESSION['ltiitemid']}\">Assignment</a> &gt; ";
                }
            } else {
                $breadcrumbbase = '';
            }
            $urlparts = parse_url($_SERVER['PHP_SELF']);
            $allowedinLTI = array('showtest.php', 'printtest.php', 'msglist.php', 'sentlist.php', 'viewmsg.php', 'msghistory.php',
                'redeemlatepass.php', 'gb-viewasid.php', 'showsoln.php', 'ltiuserprefs.php', 'file_manager.php', 'upload_handler.php',
                'index.php', 'gbviewassess.php', 'autosave.php', 'endassess.php', 'getscores.php', 'livepollstatus.php', 'loadassess.php',
                'loadquestion.php', 'scorequestion.php', 'startassess.php', 'uselatepass.php', 'gbloadassess.php', 'gbloadassessver.php',
                'gbloadquestionver.php', 'getquestions.php', 'savework.php', 'posts.php', 'thread.php', 'postsbyname.php',
                'savetagged.php', 'recordlikes.php', 'listlikes.php', 'gbloadtexts.php', 'rectrack.php');
            //call hook, if defined
            if (function_exists('allowedInAssessment')) {
                $allowedinLTI = array_merge($allowedinLTI, allowedInAssessment());
            }
            if (!in_array(basename($urlparts['path']), $allowedinLTI)) {
                //if (strpos(basename($_SERVER['PHP_SELF']),'showtest.php')===false && strpos(basename($_SERVER['PHP_SELF']),'printtest.php')===false && strpos(basename($_SERVER['PHP_SELF']),'msglist.php')===false && strpos(basename($_SERVER['PHP_SELF']),'sentlist.php')===false && strpos(basename($_SERVER['PHP_SELF']),'viewmsg.php')===false ) {
                $stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
                $stm->execute(array(':id' => $_SESSION['ltiitemid']));
                $cid = Sanitize::courseId($stm->fetchColumn(0));
                if (isset($_SESSION['ltiitemver']) && $_SESSION['ltiitemver'] > 1) {
                    header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?cid=$cid&aid={$_SESSION['ltiitemid']}&r=" . Sanitize::randomQueryStringParam());
                } else {
                    header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id={$_SESSION['ltiitemid']}&r=" . Sanitize::randomQueryStringParam());
                }
                exit;
            }
        } else if ($_SESSION['ltirole'] == 'instructor') {
            if (!empty($_SESSION['ltiver']) && $_SESSION['ltiver'] == '1.3') {
                $breadcrumbbase = '<div class="dropdown inlinediv"><a href="#"
                  role="button"
                  id="ltimenubutton"
                  class="dropdown-toggle arrow-down"
                  data-toggle="dropdown"
                  aria-haspopup="true"
                  aria-expanded="false"
                  >' . _('LTI') . '</a>
                  <div id="ltimenudiv" role="menu" class="dropdown-menu ltimenu">'
                . _('Loading...') . '</div></div> ';
                //$breadcrumbbase = "<a id=ltihomelink href=\"$imasroot/lti/ltihome.php\">LTI Home</a> &gt; ";
            } else {
                $breadcrumbbase = "<a href=\"$imasroot/ltihome.php?showhome=true\">LTI Home</a> &gt; ";
            }
        } else {
            $breadcrumbbase = '';
        }
    } else {
        $breadcrumbbase = "<a href=\"$imasroot/index.php\">Home</a> &gt; ";
    }

    if ((isset($_GET['cid']) && $_GET['cid'] != "admin" && $_GET['cid'] > 0) || (isset($_SESSION['courseid']) && strpos(basename($_SERVER['PHP_SELF']), 'showtest.php') !== false)) {
        if (isset($_GET['cid'])) {
            $cid = Sanitize::courseId($_GET['cid']);
        } else {
            $cid = Sanitize::courseId($_SESSION['courseid']);
        }
        $stm = $DBH->prepare("SELECT id,locked,timelimitmult,section,latepass,lastaccess,lticourseid,lockaid FROM imas_students WHERE userid=:userid AND courseid=:courseid");
        $stm->execute(array(':userid' => $userid, ':courseid' => $cid));
        $line = $stm->fetch(PDO::FETCH_ASSOC);
        if ($line != false) {
            $studentid = $line['id'];
            $studentinfo['timelimitmult'] = $line['timelimitmult'];
            $studentinfo['section'] = $line['section'];
            $studentinfo['latepasses'] = $line['latepass'];
            $studentinfo['lockaid'] = $line['lockaid'];
            if ($line['lticourseid'] > 0) {
                $studentinfo['lticourseid'] = $line['lticourseid'];
            }
            if ($line['locked'] > 0) {
                require_once __DIR__."/header.php";
                echo "<p>", _("You have been locked out of this course by your instructor.  Please see your instructor for more information."), "</p>";
                echo "<p><a href=\"$imasroot/index.php\">Home</a></p>";
                require_once __DIR__ . '/footer.php';
                exit;
            } else {
                $now = time();
                if (!isset($_SESSION['lastaccess' . $cid]) || $now - $_SESSION['lastaccess' . $cid] > 24 * 3600) {
                    $stm = $DBH->prepare("UPDATE imas_students SET lastaccess=:lastaccess WHERE id=:id");
                    $stm->execute(array(':lastaccess' => $now, ':id' => $studentid));
                    $_SESSION['lastaccess' . $cid] = $now;
                    $stm = $DBH->prepare("INSERT INTO imas_login_log (userid,courseid,logintime) VALUES (:userid, :courseid, :logintime)");
                    $stm->execute(array(':userid' => $userid, ':courseid' => $cid, ':logintime' => $now));
                    $_SESSION['loginlog' . $cid] = $DBH->lastInsertId();
                } else if (isset($CFG['GEN']['keeplastactionlog'])) {
                    $stm = $DBH->prepare("UPDATE imas_login_log SET lastaction=:lastaction WHERE id=:id");
                    $stm->execute(array(':lastaction' => $now, ':id' => $_SESSION['loginlog' . $cid]));
                }
            }
        } else {
            $stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
            $stm->execute(array(':userid' => $userid, ':courseid' => $cid));
            $line = $stm->fetch(PDO::FETCH_ASSOC);
            if ($line != false) {
                if ($myrights > 19) {
                    $teacherid = $line['id'];
                    if (isset($_GET['stuview'])) {
                        $_SESSION['stuview'] = $_GET['stuview'];
                    }
                    if (isset($_GET['teachview'])) {
                        unset($_SESSION['stuview']);
                    }
                    if (isset($_SESSION['stuview'])) {
                        $inInstrStuView = true;
                        unset($teacherid);
                        $studentid = $line['id'];
                    }
                } else {
                    $tutorid = $line['id'];
                }
            } else if ($myrights == 100) {
                $teacherid = $userid;
                $adminasteacher = true;
            } else {
                $stm = $DBH->prepare("SELECT id,section FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
                $stm->execute(array(':userid' => $userid, ':courseid' => $cid));
                $line = $stm->fetch(PDO::FETCH_ASSOC);
                if ($line != false) {
                    $tutorid = $line['id'];
                    $tutorsection = trim($line['section']);
                } else if ($myrights == 5 && isset($_GET['guestaccess']) && isset($CFG['GEN']['guesttempaccts'])) {
                    //guest user not enrolled, but trying via guestaccess; enroll
                    $stm = $DBH->prepare("SELECT istemplate,available FROM imas_courses WHERE id=?");
                    $stm->execute(array($cid));
                    $row = $stm->fetch(PDO::FETCH_ASSOC);
                    if (($row['istemplate'] & 8) == 8 && $row['available'] < 4) {
                        $stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid) VALUES (?,?)");
                        $stm->execute(array($userid, $cid));
                        $studentid = $DBH->lastInsertId();
                        $studentinfo = array('latepasses' => 0, 'timelimitmult' => 1, 'section' => null);
                    } else {
                        echo '<p>' . _('This course does not allow guest access.') . '</p>';
                        exit;
                    }
                }
            }
        }
        $query = "SELECT imas_courses.name,imas_courses.available,imas_courses.lockaid,imas_courses.copyrights,imas_users.groupid,imas_courses.theme,imas_courses.newflag,imas_courses.msgset,imas_courses.toolset,imas_courses.deftime,imas_courses.latepasshrs,imas_courses.startdate,imas_courses.enddate,imas_courses.UIver ";
        $query .= "FROM imas_courses JOIN imas_users ON imas_users.id=imas_courses.ownerid WHERE imas_courses.id=:id";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':id' => $cid));
        if ($stm->rowCount() > 0) {
            $crow = $stm->fetch(PDO::FETCH_ASSOC);
            $coursename = $crow['name']; //mysql_result($result,0,0);
            $coursetheme = $crow['theme']; //mysql_result($result,0,5);
            /*if (isset($usertheme) && $usertheme!='') {
            $coursetheme = $usertheme;
            } else
             */
            if (isset($_SESSION['userprefs']['usertheme']) && strcmp($_SESSION['userprefs']['usertheme'], '0') != 0) {
                $coursetheme = $_SESSION['userprefs']['usertheme'];
            } else if (isset($CFG['CPS']['theme']) && $CFG['CPS']['theme'][1] == 0) {
                $coursetheme = $defaultcoursetheme;
            } else if (isset($CFG['CPS']['themelist']) && strpos($CFG['CPS']['themelist'], $coursetheme) === false) {
                $coursetheme = $defaultcoursetheme;
            }
            $coursenewflag = $crow['newflag']; //mysql_result($result,0,6);
            $coursemsgset = $crow['msgset'] % 5;
            $coursetoolset = $crow['toolset'];
            $coursedeftime = $crow['deftime'] % 10000;
            if ($crow['deftime'] > 10000) {
                $coursedefstime = floor($crow['deftime'] / 10000) % 10000;
            } else {
                $coursedefstime = $coursedeftime;
            }
            $courseenddate = $crow['enddate'];
            $latepasshrs = max(1,$crow['latepasshrs']);
            $courseUIver = $crow['UIver'];

            if (isset($studentid) && !$inInstrStuView && ((($crow['available']) & 1) == 1 || time() < $crow['startdate'])) {
                echo _("This course is not available at this time");
                exit;
            }
            $lockaid = $crow['lockaid']; 
            if (!empty($studentinfo['lockaid'])) {
                $lockaid = $studentinfo['lockaid'];
            }
            if (isset($studentid) && $lockaid > 0) {
                if ((($courseUIver == 1 && strpos(basename($_SERVER['PHP_SELF']), 'showtest.php') === false) ||
                    ($courseUIver > 1 && (strpos($_SERVER['PHP_SELF'], 'assess2/') === false ||
                        strpos($_SERVER['QUERY_STRING'], '&aid=' . $lockaid) === false))
                    ) && strpos(basename($_SERVER['PHP_SELF']), 'ltiuserprefs.php') === false
                    && strpos(basename($_SERVER['PHP_SELF']), 'rectrack.php') === false
                ) {
                    $stm = $DBH->prepare('SELECT name,msgtoinstr,posttoforum FROM imas_assessments WHERE id=?');
                    $stm->execute([$lockaid]);
                    $lockaiddata = $stm->fetch(PDO::FETCH_ASSOC);
                    $showlockmsg = true;
                    if ($lockaiddata === false) { 
                        // assessment deleted
                        $showlockmsg = false;
                    } else if ($lockaiddata['msgtoinstr'] > 0 && strpos($_SERVER['PHP_SELF'], '/msgs/') !== false) {
                        $showlockmsg = false;
                    } else if ($lockaiddata['posttoforum'] > 0 && strpos($_SERVER['PHP_SELF'], '/forums/') !== false) {
                        $showlockmsg = false;
                    } 
                    if ($showlockmsg) {
                        $lockaidname =  $lockaiddata['name'];
                        require_once __DIR__."/header.php";
                        echo '<p>', sprintf(_('This course is currently locked for an assessment, <b>%s</b>'),
                            Sanitize::encodeStringForDisplay($lockaidname)), '</p>';
                        
                        if (!empty($studentinfo['lockaid'])) {
                            echo '<p>' , _('That assessment must be submitted before you can open anything else in the course.') , '</p>';
                        }
                        if (isset($_SESSION['ltiitemtype']) && $_SESSION['ltiitemtype'] == 0) {
                            echo "<p>" . _('Go back to the LMS and open the correct assessment') . ".</p>";
                            if (!empty($studentinfo['lockaid'])) {
                                echo '<p>' . sprintf(_('Or, if you are done with <b>%s</b>, you can submit it now.'),
                                    Sanitize::encodeStringForDisplay($lockaidname)), '</p>';
                                echo '<p><form method=post action="'.$imasroot.'/assess2/endassess.php?cid='.$cid.'&aid='.Sanitize::encodeUrlParam($lockaid).'" ';
                                echo 'onsubmit="return confirm(\''._('Are you SURE you want to submit the assessment for scoring now? If you do, you will not be able to continue working on the assessment later.').'\')">';
                                echo '<input type=hidden name=redirect value="'.Sanitize::encodeStringForDisplay($_GET['aid']).'"/>';
                                echo '<button type=submit>';
                                echo sprintf(_('Submit <em>%s</em> Now'), Sanitize::encodeStringForDisplay($lockaidname)) . '</button>';
                                echo '</form>';
                            }
                        } else if ($courseUIver > 1) {
                            echo "<p><a href=\"$imasroot/assess2/?cid=$cid&aid=" . Sanitize::encodeUrlParam($lockaid) . "\">", _("Go to Assessment"), "</a> | <a href=\"$imasroot/index.php\">", _("Home"), "</a></p>";
                        } else {
                            echo "<p><a href=\"$imasroot/assessment/showtest.php?cid=$cid&id=" . Sanitize::encodeUrlParam($lockaid) . "\">Go to Assessment</a> | <a href=\"$imasroot/index.php\">", _("Home"), "</a></p>";
                        }
                        require_once __DIR__ . '/footer.php';
                        //header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id=$lockaid");
                        exit;
                    }
                }
            }
            unset($lockaid);
            if ($myrights == 75 && !isset($teacherid) && !isset($studentid) && $crow['groupid'] == $groupid) {
                //group admin access
                $teacherid = $userid;
                $adminasteacher = true;
            } else if ($myrights > 19 && !isset($teacherid) && !isset($studentid) && !isset($tutorid) && !$inInstrStuView) {
                if ($crow['copyrights'] == 2) {
                    $instrPreviewId = $userid;
                } else if ($crow['copyrights'] == 1 && $crow['groupid'] == $groupid) {
                    $instrPreviewId = $userid;
                }
            }
        }
    } else if (!empty($_GET['cid']) && $_GET['cid'] == "admin") {
        $courseUIver = 2;
    }
    $verified = true;

}
if (!empty($flexwidth) || !empty($hideAllHeaderNav)) {
    $nologo = true;
}

if (!$verified) {
    if (!isset($skiploginredirect) && strpos(basename($_SERVER['SCRIPT_NAME']), 'directaccess.php') === false) {
        if (isset($no_session_handler)) {
            if ($no_session_handler === 'json_error') {
                header('Content-Type: application/json; charset=utf-8');
                echo '{"error": "no_session"}';
            } else {
                call_user_func($no_session_handler);
            }
            exit;
        }
        if (!isset($loginpage)) {
            $loginpage = "loginpage.php";
        }
        require_once $loginpage;
        exit;
    }
}

if (!isset($cid)) {
    $cid = 0;
}

function tzdate($string, $time)
{
    global $tzoffset, $tzname;
    //$dstoffset = date('I',time()) - date('I',$time);
    //return gmdate($string, $time-60*($tzoffset+60*$dstoffset));
    if ($tzname != '') {
        return date($string, $time);
    } else {
        $serveroffset = date('Z') + floor($tzoffset * 60);
        return date($string, $time - $serveroffset);
    }
    //return gmdate($string, $time-60*$tzoffset);
}

function checkeditorok()
{
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if ((strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) &&
        preg_match('/OS (\d+)_(\d+)/', $ua, $match)
    ) {
        if ($match[1] >= 5) {
            return 1;
        } else {
            return 0;
        }
    } else if (strpos($ua, 'Android') !== false &&
        preg_match('/Android\s+(\d+)((?:\.\d+)+)\b/', $ua, $match)
    ) {
        if ($match[1] >= 4) {
            return 1;
        } else {
            return 0;
        }
    } else {
        return 1;
    }
}
if (!isset($coursename)) {
    $coursename = "Course Page";
}
function generaterandstring()
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $pass = '';
    for ($i = 0; $i < 10; $i++) {
        $pass .= substr($chars, rand(0, 61), 1);
    }
    return $pass;
}

function user_logout() {
	$_SESSION = array();
	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time()-42000, '/', '', false, true);
	}
	session_destroy();
}
