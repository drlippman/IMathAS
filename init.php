<?php

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


//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['init'])) {
	require($CFG['hooks']['init']);
}

// setup session stuff
if (isset($sessionpath)) { session_save_path($sessionpath);}
ini_set('session.gc_maxlifetime',86400);
ini_set('auto_detect_line_endings',true);
$hostparts = explode('.',Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']));
if ($_SERVER['HTTP_HOST'] != 'localhost' && !is_numeric($hostparts[count($hostparts)-1])) {
	session_set_cookie_params(0, '/', '.'.implode('.',array_slice($hostparts,isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2)));
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
