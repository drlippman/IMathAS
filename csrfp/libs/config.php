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
	"disabledJavascriptMessage" => "This site attempts to protect users against <a href=\"https://www.owasp.org/index.php/Cross-Site_Request_Forgery_%28CSRF%29\">
	Cross-Site Request Forgeries </a> attacks. In order to do so, you must have JavaScript enabled in your web browser otherwise this site will fail to work correctly for you.
	 See details of your web browser for how to enable JavaScript.",
	 "verifyGetFor" => array()
);
