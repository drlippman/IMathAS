<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Macro Library Help</title>
        <link rel="stylesheet" href="help.css?v=112325" type="text/css" />
    </head>
    <body>
<?php
	/*
	require_once "../../init.php";
	$nologo = true;
	$flexwidth = true;
	$pagetitle = _('Macro Library Help');
	require_once "../../header.php";
	*/
	echo "<h1>Installed Macro Libraries</h1>\n";
	echo "<p>Load a macro library by entering the line <pre>loadlibrary(\"list of library names\")</pre> at the beginning of the Common Control section.</p>\n";
	echo "<p>Examples:</p>  <pre>loadlibrary(\"stats\")\nloadlibrary(\"stats,misc\")</pre>\n";
	echo "<p>You do not need to load the Core libraries.</p>";
	echo "<ul>";
	echo "<li><a href=\"../../help.php?section=randomizers\">Core Randomizers</a></li>\n";
	echo "<li><a href=\"../../help.php?section=graphtablemacros\">Core Graph/Table Macros</a></li>\n";
	echo "<li><a href=\"../../help.php?section=formatmacros\">Core Format Macros</a></li>\n";
	echo "<li><a href=\"../../help.php?section=stringmacros\">Core String Macros</a></li>\n";
	echo "<li><a href=\"../../help.php?section=arraymacros\">Core Array Macros</a></li>\n";
	echo "<li><a href=\"../../help.php?section=generalmacros\">Core General Macros</a></li>\n";
	echo "<li><a href=\"../../help.php?section=mathmacros\">Core Math Macros</a></li>\n";
	$path = ".";

	$dir = opendir($path);
	$filearray = array();
	while (($file = readdir($dir)) !== false) {
		$parts = explode('.', $file);
		if (count($parts)>1 && $parts[1] == "html") {
			if (file_exists($parts[0].'.php')) {
				$filearray[] = $parts[0];
			}
			
		}
	}
	natsort($filearray);
	foreach ($filearray as $file) {
		echo "<li><a href=\"$file.html\">" . $file . "</a></li>\n";
	}
	echo "</ul>\n";

?>
</body></html>
