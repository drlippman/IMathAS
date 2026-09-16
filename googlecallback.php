<?php
//Handles the redirect back from Google's OAuth consent screen for "Sign in with Google".
$init_session_start = true;
require_once __DIR__ . '/init_without_validate.php';
require_once __DIR__ . '/includes/googleoauth.php';

if (empty($CFG['allow_google_login'])) {
    header('Location: ' . $GLOBALS['basesiteurl'] . '/index.php');
    exit;
}

$returnTo = !empty($_SESSION['google_oauth_returnto']) ? $_SESSION['google_oauth_returnto'] : $GLOBALS['basesiteurl'] . '/index.php';

$expectedState = $_SESSION['google_oauth_state'] ?? null;
unset($_SESSION['google_oauth_state']);
unset($_SESSION['google_oauth_returnto']);

function googlecallback_error($message) {
    global $imasroot;
    require_once __DIR__ . '/header.php';
    echo '<p class="noticetext">' . Sanitize::encodeStringForDisplay($message) . '</p>';
    echo '<p><a href="' . $GLOBALS['basesiteurl'] . '/index.php">' . _('Return to login') . '</a></p>';
    require_once __DIR__ . '/footer.php';
    exit;
}

if (!empty($_GET['error'])) {
    header('Location: ' . $returnTo);
    exit;
}

if (empty($expectedState) || empty($_GET['state']) || !is_string($_GET['state']) || !hash_equals($expectedState, $_GET['state'])) {
    googlecallback_error(_('Your Google sign-in request could not be verified. Please try again.'));
}

if (empty($_GET['code']) || !is_string($_GET['code'])) {
    googlecallback_error(_('Google did not return an authorization code. Please try again.'));
}

$googleMgr = new GoogleOAuthManager($CFG['GOOGLE']['client_id'], $CFG['GOOGLE']['client_secret'], $GLOBALS['basesiteurl'] . '/googlecallback.php');

try {
    $tokenData = $googleMgr->exchangeCodeForToken($_GET['code']);
    $profile = $googleMgr->verifyIdToken($tokenData['id_token']);
} catch (Exception $e) {
    googlecallback_error(_('Google sign-in failed: ') . $e->getMessage());
}

$foundUserId = $googleMgr->findLinkedUserBySub($profile['sub']);

if ($foundUserId) {
    $token = bin2hex(random_bytes(16));
    $_SESSION['google_login_pending'][$token] = $foundUserId;
    ?>
<!DOCTYPE html>
<html><head><title><?php echo _('Signing in...'); ?></title>
<script type="text/javascript" src="<?php echo $staticroot; ?>/javascript/jstz_min.js"></script>
</head>
<body>
<form id="googleLoginBridge" method="post" action="<?php echo Sanitize::encodeStringForDisplay($returnTo); ?>">
<input type="hidden" name="googleLoginToken" value="<?php echo Sanitize::encodeStringForDisplay($token); ?>">
<input type="hidden" id="tzoffset" name="tzoffset" value="">
<input type="hidden" id="tzname" name="tzname" value="">
<noscript><button type="submit"><?php echo _('Continue'); ?></button></noscript>
</form>
<script type="text/javascript">
var thedate = new Date();
document.getElementById('tzoffset').value = thedate.getTimezoneOffset();
var tz = jstz.determine();
document.getElementById('tzname').value = tz.name();
document.getElementById('googleLoginBridge').submit();
</script>
</body></html>
    <?php
    exit;
} else {
    $_SESSION['google_pending_profile'] = array(
        'sub' => $profile['sub'],
        'email' => $profile['email'],
        'given_name' => $profile['given_name'],
        'family_name' => $profile['family_name'],
    );
    header('Location: ' . $imasroot . '/forms.php?action=googlelink');
    exit;
}
