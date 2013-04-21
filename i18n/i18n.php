<?php
//$CFG['locale'] = 'de_DE.UTF-8'
if (isset($CFG['locale'])) {
	setlocale(LC_MESSAGES, $CFG['locale']);
	putenv('LC_MESSAGES='.$CFG['locale']);
}
bindtextdomain('imathas', dirname(__FILE__).'/locale/');
bind_textdomain_codeset("imathas", 'UTF-8');
textdomain('imathas');

?>