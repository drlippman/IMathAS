<?php

if (isset($CFG['locale'])) {
	putenv('LC_ALL='.$CFG['locale']);
	setlocale(LC_ALL, $CFG['locale']);
}
bindtextdomain('imathas', dirname(__FILE__).'/locale/');
bind_textdomain_codeset("imathas", 'UTF-8');
textdomain('imathas');

?>