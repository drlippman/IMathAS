<?php
	$dbsetup = true; //prevents connection to database
	$init_skip_csrfp = true;
	include("init_without_validate.php");
	if (!isset($_GET['bare'])) {
?>
<html>
<head>
<title><?php echo $installname;?> Help</title>
<style type="text/css">
table td {
	padding: 1px 10px;
}
table thead td {
	font-weight: bold;
}
ul li {
	margin-top: .3em;
}
span.icon {
	padding-left: 5px;
	padding-right: 5px;
	background-color: #ff0;
	color:#00d;	
	border: 1px solid #00f;
	font-weight: bolder;
}
h1,h2,h3 {
	margin-top: 1.5em;
	margin-bottom: .5em;
}
h1 {
	color: #00f;
}
h2,h3,h4,h5,h6 {
	color: #00c;
}
</style>
<link rel="stylesheet" href="<?php echo $imasroot;?>/iconfonts/style.css?v=081316" type="text/css" />
<link rel="stylesheet" href="<?php echo $imasroot;?>/tinymce4/skins/lightgray/skin.min.css?v=061416" type="text/css" />
<?php
	}
//IMathAS:  Reads sections of the help.html file
//(c) 2006 David Lippman
	if (!isset($_GET['section']) && !isset($_GET['bare'])) {
		echo "<style type=\"text/css\">\n";
		echo "div.h2 {margin-left: 10px;} \n div.h3 {margin-left: 20px;} \n div.h4 {margin-left: 30px;} \n div.h5 {margin-left: 40px;} \n";
		echo "</style>\n";
	}
	if (!isset($_GET['bare'])) {
		echo "</head><body>\n";
	}
	if (isset($_GET['section']) && !isset($_GET['bare'])) {
		echo '<div id="headerindex" class="pagetitle"><h2>'.$installname.' Help</h2></div>';
	}


	$indiv = false;
	$intoc = false;
	$ndiv = 0;
	$nul = 0;
	$inbody = false;
	$handle = fopen("help.html", "r");
	
	if ($handle) {
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			if (!$inbody) {
				if (strpos($buffer,"<body")!==false) {
					$inbody = true;
				}
				continue;
			}
			if (!isset($_GET['section'])) {
				echo $buffer;
				continue;
			} 
			if ($indiv) {
				$buffer = preg_replace('/<a\s+href\="#([^"]+)"[^>]*>/',"<a href=\"help.php?section=$1\">",$buffer);
				echo $buffer;
				$ndiv += substr_count($buffer,"<div");
				$ndiv -= substr_count($buffer,"</div");
				if ($ndiv==0) {
					break;
				}
			} else if ($intoc) {
				echo $buffer;
				$nul += substr_count($buffer,"<ul");
				$nul -= substr_count($buffer,"</ul");
				if ($nul==0) {
					echo "</ul>\n";
					$intoc = false;
				}
			} else if (strpos($buffer,"<li><a href=\"#{$_GET['section']}\"")!==false) {
				$next = fgets($handle, 4096);
				if (strpos($next, "<ul>")!==false) {
					$intoc = true;
					$nul = 1;
					echo "<ul>";
					echo $buffer;
					echo $next;
				}
			
			} else if (preg_match('/.*<h([1-6])>\s*<a\s+name\="([^"]+)".*/',$buffer,$matches) && $matches[2]==$_GET['section']) {
				if (!isset($_GET['bare'])) {
					echo "<style type=\"text/css\">\n";
					for ($i=$matches[1]+1;$i<5;$i++) {
						echo "div.h$i { margin-left: " . 10*Sanitize::encodeStringForCSS($i-$matches[1]) . "px;}\n";
					}
					echo "</style>\n";
				}
					
				echo "<div>\n$buffer";
				$indiv = true;
				$ndiv = 1;
			}	
		}
		fclose($handle);
	} else {
		echo "No handle";
	}
	if (!isset($_GET['bare'])) {
		echo "</body></html>\n";
	}
?>


