<?php
//IMathAS:  Displays a linked text item
//(c) 2006 David Lippman
	if (!isset($_GET['cid'])) {
		echo "Need course id";
		exit;
	}
	$cid = $_GET['cid'];
	
	if (isset($_GET['from'])) {
		$pubcid = $cid;  //swap out cid's before calling validate
		$cid = $_GET['from'];
		$_GET['cid'] = $_GET['from'];
		require("../validate.php");
		$fcid = $cid;
		$cid = $pubcid;
	} else if (preg_match('/cid=(\d+)/',$_SERVER['HTTP_REFERER'],$matches) && $matches[1]!=$cid) {
		$pubcid = $cid;  //swap out cid's before calling validate
		$cid = $matches[1];
		$_GET['cid'] = $matches[1];
		require("../validate.php");
		$fcid = $cid;
		$cid = $pubcid;
	} else {
		$fcid = 0;
		require("../config.php");
	}
			
	function findinpublic($items,$id) {
		foreach ($items as $k=>$item) {
			if (is_array($item)) {
				if ($item['public']==1) {
					if (finditeminblock($item['items'],$id)) {
						return true;
					}
				}
			} 
		}
		return false;
	}
	function finditeminblock($items,$id) {
		foreach ($items as $k=>$item) {
			if (is_array($item)) {
				if (finditeminblock($item['items'],$id)) {
					return true;
				}
			} else {
				if ($item==$id) {
					return true;
				}
			}
		}
		return false;
	}
	
	$query = "SELECT id FROM imas_items WHERE itemtype='LinkedText' AND typeid='{$_GET['id']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$itemid = mysql_result($result,0,0);
	
	$query = "SELECT itemorder,name,theme FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$items = unserialize(mysql_result($result,0,0));
	if ($fcid==0) {
		$coursename = mysql_result($result,0,1);
		$coursetheme = mysql_result($result,0,2);
		$breadcrumbbase = "<a href=\"public.php?cid=$cid\">$coursename</a> &gt; ";
	} else {
		$breadcrumbbase = "$breadcrumbbase <a href=\"course.php?cid=$fcid\">$coursename</a> &gt; ";
	}
		
	if (!findinpublic($items,$itemid)) {
		require("../header.php");
		echo "This page does not appear to be publically accessible.  Please return to the <a href=\"../index.php\">Home Page</a> and try logging in.\n";
		require("../footer.php");
		exit;
	}
	$ispublic = true;
	
	if (!isset($_GET['id'])) {
		echo "<html><body>No item specified.</body></html>\n";
		exit;
	}
	$query = "SELECT text,title FROM imas_linkedtext WHERE id='{$_GET['id']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$text = mysql_result($result, 0,0);
	$title = mysql_result($result,0,1);
	$titlesimp = strip_tags($title);

	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase $titlesimp</div>";
	
	echo '<div style="padding-left:10px; padding-right: 10px;">';
	echo filter($text);
	echo '</div>';
	
	if (isset($_GET['from'])) {
		echo "<div class=right><a href=\"course.php?cid={$_GET['cid']}\">Back</a></div>\n";
	} else if ($fcid>0) {
		echo "<div class=right><a href=\"{$_SERVER['HTTP_REFERER']}\">Back</a></div>\n";
	} else {
		echo "<div class=right><a href=\"public.php?cid={$_GET['cid']}\">Return to the Public Course Page</a></div>\n";
	}
	require("../footer.php");	

?>
