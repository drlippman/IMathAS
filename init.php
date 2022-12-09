<?php

require_once(__DIR__ . "/includes/sanitize.php");

if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
    $urlmode = 'https://';
} else {
    $urlmode = 'http://';
}

// Load site config.
if (!file_exists(__DIR__ . "/config.php")) {
	header('Location: ' . Sanitize::url($urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/install.php?r=" . Sanitize::randomQueryStringParam()));
}

require_once(__DIR__ . "/config.php");

require_once(__DIR__ . "/i18n/i18n.php");

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['init'])) {
	require_once($CFG['hooks']['init']);
}

// setup session stuff
if (!function_exists('disallowsSameSiteNone')) {
function disallowsSameSiteNone () {
	// based on https://devblogs.microsoft.com/aspnet/upcoming-samesite-cookie-changes-in-asp-net-and-asp-net-core/
	if (!isset($_SERVER['HTTP_USER_AGENT'])) { return false; }
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
ini_set('session.gc_maxlifetime',432000);

$hostdomain = explode(':', Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']));
$hostparts = explode('.', $hostdomain[0]);
if ((!function_exists('isDevEnvironment') || !isDevEnvironment())
    && $hostdomain[0] != 'localhost'
    && !is_numeric($hostparts[count($hostparts)-1])
) {
	$sess_cookie_domain = '.'.implode('.',array_slice($hostparts,isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2));
	if (disallowsSameSiteNone()) {
		session_set_cookie_params(0, '/', $sess_cookie_domain, false, true);
	} else if (PHP_VERSION_ID < 70300) {
		// hack to add samesite
		session_set_cookie_params(0, '/; samesite=none', $sess_cookie_domain, true, true);
  } else {
		session_set_cookie_params(array(
			'lifetime' => 0,
			'path' => '/',
			'domain' => $sess_cookie_domain,
            'secure' => true,
            'httponly' => true,
			'samesite'=>'None'
		));
  }
}
if (!function_exists('setsecurecookie')) {
function setsecurecookie($name, $value, $expires=0) {
	global $imasroot;
	if ($_SERVER['HTTP_HOST'] == 'localhost' || disallowsSameSiteNone()) {
		setcookie($name, $value, $expires);
	} else if (PHP_VERSION_ID < 70300) {
		setcookie($name, $value, $expires, '/; samesite=none;', '', true);
	} else {
		setcookie($name, $value, array(
			'expires' => $expires,
			'secure' => true,
			'samesite'=>'None',
			'path' => $imasroot.'/'
		));
	}
	$_COOKIE[$name] = $value;
}
}

// prevent errors in PHP < 7.2
if (!defined('JSON_INVALID_UTF8_IGNORE')) {
	define('JSON_INVALID_UTF8_IGNORE', 0);
}

// Store PHP sessions in the database.
if (!isset($use_local_sessions)) {
  if (!empty($CFG['redis'])) {
		$redispath = $CFG['redis'] . ((strpos($CFG['redis'], '?')===false)?'?':'&')
			. 'prefix='.preg_replace('/\W/','',$installname);
  	ini_set('session.save_handler', 'redis');
  	ini_set('session.save_path', $redispath);
	} else if (!empty($CFG['dynamodb'])) {
  	require_once(__DIR__ . "/includes/dynamodb/DynamoDbSessionHandler.php");
  	(new Idealstack\DynamoDbSessionsDependencyFree\DynamoDbSessionHandler([
  		'region' => $CFG['dynamodb']['region'],
  		'table_name' => $CFG['dynamodb']['table'],
  		'credentials' => [
  			'key' => $CFG['dynamodb']['key'],
  			'secret' => $CFG['dynamodb']['secret']
  		],
  		'base64' => false
  	]))->register();
  } else {
	require_once(__DIR__ . "/includes/session.php");
	session_set_save_handler(new SessionDBHandler(), true);
  }
}

$staticroot = $imasroot;

// Load validate.php?
if (!isset($init_skip_validate) || (isset($init_skip_validate) && false == $init_skip_validate)) {
	require_once(__DIR__ . "/validate.php");
	// OWASP CSRF Protector
	if (!empty($CFG['use_csrfp']) && (!isset($init_skip_csrfp) || (isset($init_skip_csrfp) && false == $init_skip_csrfp))) {
		require_once(__DIR__ . "/csrfp/simplecsrfp.php");
		csrfProtector::init();
	}
} else if (!empty($init_session_start)) {
	session_start();
}
if (isset($CFG['static_server']) && !empty($_SESSION['static_ok'])) {
    $staticroot = $CFG['static_server'];
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
