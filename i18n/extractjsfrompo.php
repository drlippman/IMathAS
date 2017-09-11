<?php
require_once(__DIR__ . "/../includes/sanitize.php");

if (php_sapi_name() !== 'cli') {
	echo "this script can only be run on the command line";
	exit;
}
if (count($argv)!==2) {
	echo "incorrect inputs:  provide one input - locale";
	exit;
}

$locale = Sanitize::simpleString($argv[1]);
if (strlen($locale)!=2) {
	echo "invalid locale- should be two letters";
}
$locale = preg_replace('/\W/','',$locale);

$fp = fopen(__dir__.'/'.$locale.'.po', 'r');

$injs = false;
$msgs = array();
$cnt = 0;
while (($line = fgets($fp, 4096)) !== false) {
	$line = trim($line);
	$cnt++;
	if ($line=='') {
		$injs = false; $msgid='""';
	} else if ($line{0}=='#' && strpos($line,'.js')!==false) {
		$injs = true;
	} else if (substr($line,0,5)=='msgid') {
		$msgid = substr($line,6);
	} else if (substr($line,0,6)=='msgstr' && $injs) {
		$msgstr = substr($line,7);
		if ($msgid!='""') {
			$msgs[] = $msgid.':'.$msgstr;
		}
	}
}
fclose($fp);
if (!is_dir(__dir__.'/locale')) {
	mkdir(__dir__.'/locale');
}
if (!is_dir(__dir__.'/locale/'.$locale)) {
	mkdir(__dir__.'/locale/'.$locale);
}
$fpo = fopen(__dir__.'/locale/'.$locale.'/messages.js', 'w');
fwrite($fpo, 'var i18njs = {'.implode(",\n",$msgs).'};');
fclose($fpo);

echo count($msgs) . " messages found in $cnt lines";

