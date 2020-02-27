<?php
//Error checking off
error_reporting(0);
ini_set('display_errors', 'Off');
//End error checking off
require_once(__DIR__ . "/includes/sanitize.php");

// Load site config.
if (!file_exists(__DIR__ . "/config.php")) {
	// Can't use $basesiteurl here, as it's defined in config.php.
	$httpMode = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
		? 'https://' : 'http://';
	header('Location: ' . Sanitize::url($httpMode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/install.php?r=" . Sanitize::randomQueryStringParam()));
}

require_once(__DIR__ . "/config.php");
// TESTING:
require_once(__DIR__ . "/i18n/i18n.php");

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['init'])) {
	require_once($CFG['hooks']['init']);
}

// setup session stuff
if (!function_exists('disallowsSameSiteNone')) {
function disallowsSameSiteNone () {
	// based on https://devblogs.microsoft.com/aspnet/upcoming-samesite-cookie-changes-in-asp-net-and-asp-net-core/
	$userAgent = $_SERVER['HTTP_USER_AGENT'];
	if (strpos($userAgent, "CPU iPhone OS 12") !== false ||
		strpos($userAgent, "iPad; CPU OS 12") !== false
	) {
		return true;
	}
	if (strpos($userAgent, "Macintosh; Intel Mac OS X 10_14") !== false &&
		strpos($userAgent, "Version/") !== false &&
		strpos($userAgent, "Safari") !== false
	) {
		return true;
	}
	if (strpos($userAgent, "Chrome/5") !== false ||
		strpos($userAgent, "Chrome/6") !== false
	) {
		return true;
	}

	return false;
}
}
if (isset($sessionpath)) { session_save_path($sessionpath);}
ini_set('session.gc_maxlifetime',86400);
ini_set('auto_detect_line_endings',true);
$hostparts = explode('.',Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']));
if ($_SERVER['HTTP_HOST'] != 'localhost' && !is_numeric($hostparts[count($hostparts)-1])) {
	$sess_cookie_domain = '.'.implode('.',array_slice($hostparts,isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2));
	if (disallowsSameSiteNone()) {
		session_set_cookie_params(0, '/', $sess_cookie_domain);
	} else if (PHP_VERSION_ID < 70300) {
		// hack to add samesite
		session_set_cookie_params(0, '/; samesite=none', $sess_cookie_domain, true);
  } else {
		session_set_cookie_params(array(
			'lifetime' => 0,
			'path' => '/',
			'domain' => $sess_cookie_domain,
			'secure' => true,
			'samesite'=>'None'
		));
  }
}

// Store PHP sessions in the database.
require_once(__DIR__ . "/includes/session.php");
if (!isset($use_local_sessions)) {
	session_set_save_handler(new SessionDBHandler(), true);
}

// Load validate.php?
if (!isset($init_skip_validate) || (isset($init_skip_validate) && false == $init_skip_validate)) {
	require_once(__DIR__ . "/validate.php");
	// OWASP CSRF Protector
	if (!empty($CFG['use_csrfp']) && (!isset($init_skip_csrfp) || (isset($init_skip_csrfp) && false == $init_skip_csrfp))) {
		require_once(__DIR__ . "/csrfp/simplecsrfp.php");
		csrfProtector::init();
	}
} else {
	session_start();
}
/*
if (isset($_SESSION['ratelimiter']) && isset($CFG['GEN']['ratelimit']) &&
	$_SERVER['REQUEST_METHOD'] === 'POST' &&
	microtime(true)-$_SESSION['ratelimiter'] < $CFG['GEN']['ratelimit']
) {
	echo "Slow down: ".(microtime(true)-$_SESSION['ratelimiter']);
	$_SESSION['ratelimiter'] = microtime(true);
	exit;
} else {
	$_SESSION['ratelimiter'] = microtime(true);
}
*/
