<?php

require_once(__DIR__ . "/includes/sanitize.php");

// Load site config.
if (!file_exists(__DIR__ . "/config.php")) {
	// Can't use $basesiteurl here, as it's defined in config.php.
	$httpMode = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
		? 'https://' : 'http://';
	header('Location: ' . Sanitize::url($httpMode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/install.php"));
}

require_once(__DIR__ . "/config.php");

// Store PHP sessions in the database.
require_once(__DIR__ . "/includes/session.php");
if (!isset($use_local_sessions)) {
	session_set_save_handler(new SessionDBHandler(), true);
}

// OWASP CSRF Protector
if (!empty($CFG['use_csrfp']) && (!isset($init_skip_csrfp) || (isset($init_skip_csrfp) && false == $init_skip_csrfp))) {
	require_once(__DIR__ . "/csrfp/libs/csrf/csrfprotector.php");
	csrfProtector::init();
}

// Load validate.php?
if (!isset($init_skip_validate) || (isset($init_skip_validate) && false == $init_skip_validate)) {
	require_once(__DIR__ . "/validate.php");
}
