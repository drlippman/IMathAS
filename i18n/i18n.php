<?php

if (function_exists('gettext')) {
	if (isset($CFG['locale'])) {
		putenv('LC_MESSAGES='.$CFG['locale']);
		@setlocale(LC_MESSAGES, $CFG['locale']);
	}
	bindtextdomain('imathas', dirname(__FILE__).'/locale/');
	bind_textdomain_codeset("imathas", 'UTF-8');
	textdomain('imathas');
} else {
	if (isset($CFG['locale'])) {
		echo "<p>Warning: Locale is set in config, but gettext is not installed on this server</p>";
	}
	function _($t) {
		return $t;
	}
}