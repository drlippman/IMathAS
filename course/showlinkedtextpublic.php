<?php
//IMathAS:  Displays a linked text item
//(c) 2006 David Lippman

	if (!isset($_GET['cid'])) {
		echo "Need course id";
		exit;
	}
    if (!isset($_GET['id'])) { exit; }
	$cid = intval($_GET['cid']);

	if (isset($_GET['from'])) {
		$pubcid = $cid;  //swap out cid's before calling validate
		$cid = intval($_GET['from']);
		$_GET['cid'] = intval($_GET['from']);
		require_once "../init.php";
		$fcid = $cid;
		$cid = $pubcid;
	} else if (isset($_SERVER['HTTP_REFERER']) && preg_match('/cid=(\d+)/',$_SERVER['HTTP_REFERER'],$matches) && $matches[1]!=$cid) {
		$pubcid = $cid;  //swap out cid's before calling validate
		$cid = intval($matches[1]);
		$_GET['cid'] = intval($matches[1]);
		require_once "../init.php";
		$fcid = $cid;
		$cid = $pubcid;
	} else {
		$fcid = 0;
		require_once "../init_without_validate.php";
	}

	function findinpublic($items,$id) {
        if (!is_array($items)) { return false; }
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
        if (!is_array($items)) { return false; }
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
	$stm = $DBH->prepare("SELECT id FROM imas_items WHERE itemtype='LinkedText' AND typeid=:typeid");
	$stm->execute(array(':typeid'=>intval($_GET['id'])));
	$itemid = $stm->fetchColumn(0);
	$stm = $DBH->prepare("SELECT itemorder,name,theme FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$row = $stm->fetch(PDO::FETCH_NUM);
    if ($row === false) { exit; }
    list($itemorder,$itemcoursename,$itemcoursetheme) = $row;
	$items = unserialize($itemorder);
	if ($fcid==0) {
		$coursename = $itemcoursename;
		$coursetheme = $itemcoursetheme;
		$breadcrumbbase = "<a href=\"public.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	} else {
		$breadcrumbbase = "$breadcrumbbase <a href=\"course.php?cid=$fcid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	}

	if (!findinpublic($items,$itemid)) {
		require_once "../header.php";
		echo "This page does not appear to be publically accessible.  Please return to the <a href=\"../index.php\">Home Page</a> and try logging in.\n";
		require_once "../footer.php";
		exit;
	}
	$ispublic = true;

	if (!isset($_GET['id'])) {
		echo "<html><body>No item specified.</body></html>\n";
		exit;
	}
	$stm = $DBH->prepare("SELECT text,title FROM imas_linkedtext WHERE id=:id AND courseid=:cid");
	$stm->execute(array(':id'=>intval($_GET['id']), ':cid'=>$cid));
	if ($stm->rowCount()==0) {
		echo "Invalid ID";
		exit;
	}
	list($text,$title) = $stm->fetch(PDO::FETCH_NUM);
	$titlesimp = strip_tags($title);

	require_once "../header.php";
	echo "<div class=breadcrumb>$breadcrumbbase ".Sanitize::encodeStringForDisplay($titlesimp)."</div>";

	echo '<div id="headershowlinkedtext" class="pagetitle"><h1>'.Sanitize::encodeStringForDisplay($titlesimp).'</h1></div>';

	echo '<div style="padding-left:10px; padding-right: 10px;">';
	echo Sanitize::outgoingHtml(filter($text));
	echo '</div>';
	if (!($_GET['from'])) {
		echo "<div class=right><a href=\"course.php?cid=$cid\">Back</a></div>\n";
	} else if ($fcid>0) {
		echo "<div class=right><a href=\"" . Sanitize::url($_SERVER['HTTP_REFERER']) . "\">Back</a></div>\n";
	} else {
		echo "<div class=right><a href=\"public.php?cid=$cid\">Return to the Public Course Page</a></div>\n";
	}
	require_once "../footer.php";

?>
