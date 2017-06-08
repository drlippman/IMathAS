<?php
/**
 * Configuration file for CSRF Protector
 * Necessary configurations are (library would throw exception otherwise)
 * ---- logDirectory
 * ---- failedAuthAction
 * ---- jsPath
 * ---- jsUrl
 * ---- tokenLength
 *
 * Filesystem paths are relative to: vendor/owasp/csrf-protector-php/libs/
 */
return array(
	"CSRFP_TOKEN" => "",
	"logDirectory" => "../log",
	"failedAuthAction" => array(
		"GET" => 3,
		"POST" => 3),
	"errorRedirectionPage" => "",
	"customErrorMessage" => "Invalid or missing CSRF token.",
	"jsPath" => "../js/csrfprotector.js",
	"jsUrl" => $GLOBALS['basesiteurl'] . "/csrfp/js/csrfprotector.js",
	"tokenLength" => 10,
	"secureCookie" => false,
	"disabledJavascriptMessage" => "This site requires JavaScript.",
	"verifyGetFor" => array()
);
