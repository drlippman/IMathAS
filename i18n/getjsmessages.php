<?php

echo "This script has been deprecated.  Using the extractjsfrompo.php instead.";
exit;
$dir = realpath('..');

$strs = array();
function scanRecursive($dir) {
    global $strs;
    $cnt = 0;
    if(!$dh = @opendir($dir))  {
       return;
    }
    while (false !== ($obj = readdir($dh)))  {
        if(substr($obj,0,1) == '.')  {  //skip all hidden files and . and .. dir
            continue;
        }
	if (is_file($dir . '/' . $obj)) {
		if (substr($obj,-2)!=='js') {continue;} //only js
		$contents = file_get_contents($dir.'/'.$obj);
		$contents = preg_replace('/\/\/.*/','',$contents); //kill comments
		$n = preg_match_all('/\b_\((\'|")(.*?[^\\\\])\1/',$contents,$matches);
		if ($n>0) {
			foreach ($matches[2] as $st) {
				$str = stripslashes($st);
				if (!in_array($str,$strs)) {
					$strs[] = $str;
				}
			}
		}
	} else if (is_dir($dir . '/' . $obj)) {
		scanRecursive($dir.'/'.$obj);
	}
    }
    closedir($dh);

}
scanRecursive($dir);
$fp = fopen("messages.js","w");
foreach ($strs as $k=>$v) {
	$strs[$k] = '"'.str_replace('"','\\"',$v).'":'."\n".'""';
}
fwrite($fp, 'var i18njs = {'.implode(",\n\n",$strs).'};');
fclose($fp);
echo count($strs);
?>
