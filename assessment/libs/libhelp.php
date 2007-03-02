<?php
	require("../../validate.php");
	require("../../header.php");
	
	echo "<h1>Installed Macro Libraries</h1>\n";
	echo "Load a macro library by entering the line <pre>loadlibrary(\"list of library names\")</pre> at the beginning of the Common Control section.<BR>\n";
	echo "Examples:  <pre>loadlibrary(\"stats\")</pre> or  <pre>loadlibrary(\"stats,misc\")</pre><BR>\n";
	echo "You do not need to load the Core libraries.\n";
	echo "<ul>";
	echo "<li><a href=\"$imasroot/help.php?section=randomizers\">Core Randomizers</a></li>\n";
	echo "<li><a href=\"$imasroot/help.php?section=displaymacros\">Core Display Macros</a></li>\n";
	echo "<li><a href=\"$imasroot/help.php?section=generalmacros\">Core General Macros</a></li>\n";
	$path = ".";

	$dir = opendir($path);
	while (($file = readdir($dir)) !== false) {
		if (($pos=strpos($file,".html")) !== false) {
			$filearray[] = $file;
			
		}
	}
	natsort($filearray);
	foreach ($filearray as $file) {
		echo "<li><a href=\"$file\">" . substr($file, 0, strpos($file,".html")) . "</a></li>\n";
	}
	echo "</ul>\n";
	
	require("../../footer.php");
?>
