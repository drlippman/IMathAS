<?php
//IMathAS:  Displays a wiki, public view
//(c) 2010 David Lippman


	if (!isset($_GET['cid'])) {
		echo "Need course id";
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);
	if (isset($_GET['from'])) {
		$pubcid = $cid;  //swap out cid's before calling validate
	  $cid = Sanitize::courseId($_GET['from']);
		$_GET['cid'] = Sanitize::courseId($_GET['from']);
		require("../init.php");
		$fcid = $cid;
		$cid = $pubcid;
	} else {
		$fcid = 0;
		require("../init_without_validate.php");
	}
	if (!isset($_GET['id'])) {
		echo "<html><body>No item specified.</body></html>\n";
		exit;
	}
	$id = intval($_GET['id']);

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

	//DB $query = "SELECT id FROM imas_items WHERE itemtype='Wiki' AND typeid='$id'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $itemid = mysql_result($result,0,0);
	$stm = $DBH->prepare("SELECT id FROM imas_items WHERE itemtype='Wiki' AND typeid=:typeid");
	$stm->execute(array(':typeid'=>$id));
	$itemid = $stm->fetchColumn(0);

	//DB $query = "SELECT itemorder,name,theme FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $items = unserialize(mysql_result($result,0,0));
	$stm = $DBH->prepare("SELECT itemorder,name,theme FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$items = unserialize($stm->fetchColumn(0));
	if ($fcid==0) {
		//DB $coursename = mysql_result($result,0,1);
		//DB $coursetheme = mysql_result($result,0,2);
		list($coursename, $coursetheme) = $stm->fetch(PDO::FETCH_NUM);
		$breadcrumbbase = "<a href=\"$imasroot/course/public.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	} else {
		$breadcrumbbase = "$breadcrumbbase <a href=\"$imasroot/course/course.php?cid=$fcid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	}

	if (!findinpublic($items,$itemid)) {
		require("../header.php");
		echo "This page does not appear to be publically accessible.  Please return to the <a href=\"../index.php\">Home Page</a> and try logging in.\n";
		require("../footer.php");
		exit;
	}
	$ispublic = true;


	//DB $query = "SELECT name,startdate,enddate,editbydate,avail,groupsetid FROM imas_wikis WHERE id='$id'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT name,startdate,enddate,editbydate,avail,groupsetid FROM imas_wikis WHERE id=:id");
	$stm->execute(array(':id'=>$id));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$wikiname = $row[0];
	$now = time();
	if ($row[5]>0 || $row[4]==0 || ($row[4]==1 && ($now<$row[1] || $now>$row[2]))) {
		require("../header.php");
		echo "This wiki is not currently available for viewing";
		require("../footer.php");
		exit;
	}

	require("../header.php");
	echo "<div class=breadcrumb> $breadcrumbbase View Wiki</div>";
	echo '<div id="headerviewwiki" class="pagetitle"><h1>'.Sanitize::encodeStringForDisplay($wikiname).'</h1></div>';

	//DB $query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id FROM ";
	//DB $query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
	//DB $query .= "WHERE i_w_r.wikiid='$id' ORDER BY i_w_r.id DESC LIMIT 1";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id FROM ";
	$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
	$query .= "WHERE i_w_r.wikiid=:wikiid ORDER BY i_w_r.id DESC LIMIT 1";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':wikiid'=>$id));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$text = $row[1];
	if (strlen($text)>6 && substr($text,0,6)=='**wver') {
		$wikiver = Sanitize::onlyInt(substr($text,6,strpos($text,'**',6)-6));
		$text = substr($text,strpos($text,'**',6)+2);
	} else {
		$wikiver = 1;
	}

	echo '<div style="padding-left:10px; padding-right: 10px; border: 1px solid #000;">';
	echo Sanitize::outgoingHtml(filter($text));
	echo '</div>';

	echo "<div class=right><a href=\"../course/public.php?cid=$cid\">Return to Public Course Page</a></div>\n";
	require("../footer.php");

?>
