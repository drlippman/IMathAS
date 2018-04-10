<?php
//IMathAS:  View Wiki page
//(c) 2010 David Lippman


/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require("../includes/diff.php");



/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

$cid = intval($_GET['cid']);
$id = intval($_GET['id']);
$groupid = intval($_GET['grp']);
$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a>";

if (isset($_GET['framed'])) {
	$flexwidth = true;
	$shownav = false;
	$framed = "&framed=true";
} else {
	$shownav = true;
	$framed = '';
}

if ($cid==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a course id";
} else if ($id==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a wiki id";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

	//DB $query = "SELECT name,startdate,enddate,editbydate,avail,groupsetid FROM imas_wikis WHERE id='$id'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT name,startdate,enddate,editbydate,avail,groupsetid FROM imas_wikis WHERE id=:id");
	$stm->execute(array(':id'=>$id));
	$row = $stm->fetch(PDO::FETCH_ASSOC);
	$wikiname = $row['name'];
	$pagetitle = $wikiname;
	$now = time();
	if (!isset($teacherid) && ($row['avail']==0 || ($row['avail']==1 && ($now<$row['startdate'] || $now>$row['enddate'])))) {
		$overwriteBody=1;
		$body = "This wiki is not currently available for viewing";
	} else if (isset($_REQUEST['delall']) && isset($teacherid)) {
		if (isset($_POST['delall']) && $_POST['delall']=='true') {
			//DB $query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid AND stugroupid=:stugroupid");
			$stm->execute(array(':wikiid'=>$id, ':stugroupid'=>$groupid));
			header('Location: ' . $GLOBALS['basesiteurl'] . "/wikis/viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed&r=" .Sanitize::randomQueryStringParam());
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed\">View Wiki</a>";
			$curBreadcrumb .= " &gt; Clear WikiPage Contents\n";
			$pagetitle = "Confirm Page Contents Delete";
		}
	} else if (isset($_REQUEST['delrev']) && isset($teacherid)) {
		if (isset($_POST['delrev']) && $_POST['delrev']=='true') {
			//DB $query = "SELECT id FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' ORDER BY id DESC LIMIT 1";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
				//DB $curid = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT id FROM imas_wiki_revisions WHERE wikiid=:wikiid AND stugroupid=:stugroupid ORDER BY id DESC LIMIT 1");
			$stm->execute(array(':wikiid'=>$id, ':stugroupid'=>$groupid));
			if ($stm->rowCount()>0) {
				$curid = $stm->fetchColumn(0);
				//DB $query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' AND id<$curid";
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid AND stugroupid=:stugroupid AND id<:curid");
				$stm->execute(array(':wikiid'=>$id, ':stugroupid'=>$groupid, ':curid'=>$curid));
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/wikis/viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed\">View Wiki</a>";
			$curBreadcrumb .= " &gt; Clear WikiPage History\n";
			$pagetitle = "Confirm History Delete";
		}

	} else if (isset($_REQUEST['revert']) && isset($teacherid)) {
		if (isset($_POST['revert']) && $_POST['revert']=='true') {
			$revision = intval($_GET['torev']);
			//DB $query = "SELECT revision FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' ";
			//DB $query .= "AND id>=$revision ORDER BY id DESC";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>1 && $revision>0) {
				//DB $row = mysql_fetch_row($result);
			$query = "SELECT revision FROM imas_wiki_revisions WHERE wikiid=:wikiid AND stugroupid=:stugroupid ";
			$query .= "AND id>=:id ORDER BY id DESC";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':wikiid'=>$id, ':stugroupid'=>$groupid, ':id'=>$revision));
			if ($stm->rowCount()>1 && $revision>0) {
				$row = $stm->fetch(PDO::FETCH_NUM);
				$base = diffstringsplit($row[0]);
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$base = diffapplydiff($base,$row[0]);
				}
				//DB $newbase = addslashes(implode(' ',$base));
				$newbase = implode(' ',$base);
				//DB $query = "UPDATE imas_wiki_revisions SET revision='$newbase' WHERE id=$revision";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_wiki_revisions SET revision=:revision WHERE id=:id");
				$stm->execute(array(':revision'=>$newbase, ':id'=>$revision));
				//DB $query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' AND id>$revision";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid AND stugroupid=:stugroupid AND id>:id");
				$stm->execute(array(':wikiid'=>$id, ':stugroupid'=>$groupid, ':id'=>$revision));
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/wikis/viewwiki.php?cid=$cid&id=$id$framed&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed\">View Wiki</a>";
			$curBreadcrumb .= " &gt; Revert Wiki\n";
			$pagetitle = "Confirm Wiki Version Revert";
		}
	} else { //just viewing
		$curBreadcrumb .= " &gt; View Wiki";

		require_once("../filter/filter.php");

		if (isset($teacherid) || $now<$row['editbydate']) {
			$canedit = true;
		} else {
			$canedit = false;
		}

		//if is group wiki, get groupid or fail
		if ($row['groupsetid']>0 && !isset($teacherid)) {
			$isgroup = true;
			$groupsetid = $row['groupsetid'];
			//DB $query = 'SELECT i_sg.id,i_sg.name FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			//DB $query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='$groupsetid'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB if (mysql_num_rows($result)==0) {
			$query = 'SELECT i_sg.id,i_sg.name FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			$query .= "WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':userid'=>$userid, ':groupsetid'=>$groupsetid));
			if ($stm->rowCount()==0) {
				$overwriteBody=1;
				$body = "You need to be a member of a group before you can view or edit this wiki";
				$isgroup = false;
			} else {
				//DB $groupid = mysql_result($result,0,0);
				//DB $curgroupname = mysql_result($result,0,1);
				list($groupid, $curgroupname) = $stm->fetch(PDO::FETCH_NUM);
			}
		} else if ($row['groupsetid']>0 && isset($teacherid)) {
			$isgroup = true;
			$groupsetid = $row['groupsetid'];
			$stugroup_ids = array();
			$stugroup_names = array();
			$hasnew = array();
			$wikilastviews = array();
			//DB $query = "SELECT stugroupid,lastview FROM imas_wiki_views WHERE userid='$userid' AND wikiid='$id'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT stugroupid,lastview FROM imas_wiki_views WHERE userid=:userid AND wikiid=:wikiid");
			$stm->execute(array(':userid'=>$userid, ':wikiid'=>$id));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			 	$wikilastviews[$row[0]] = $row[1];
			}

			//DB $query = "SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid='$id' GROUP BY stugroupid";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid=:wikiid GROUP BY stugroupid");
			$stm->execute(array(':wikiid'=>$id));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (!isset($wikilastviews[$row[0]]) || $wikilastviews[$row[0]] < $row[1]) {
				   $hasnew[$row[0]] = 1;
				}
			}
			$i = 0;
			//DB $query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY name";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY name");
			$stm->execute(array(':groupsetid'=>$groupsetid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$stugroup_ids[$i] = $row[0];
				$stugroup_names[$i] = $row[1] . ((isset($hasnew[$row[0]]))?' (New Revisions)':'');
				if ($row[0]==$groupid) {
					$curgroupname = $row[1];
				}
				$i++;
			}
			if ($groupid==0) {
				if (count($stugroup_ids)==0) {
					$overwriteBody=1;
					$body = "No groups exist yet.  There have to be groups before you can view their wikis";
					$isgroup = false;
				} else {
					$groupid = $stugroup_ids[0];
					$curgroupname = $stugroup_names[0];
				}
			}
		} else {
			$groupid = 0;
		}

		if ($groupid>0) {
			$grpmem = '<p>Group Members: <ul class="nomark">';
			//DB $query = "SELECT i_u.LastName,i_u.FirstName FROM imas_stugroupmembers AS i_sg,imas_users AS i_u WHERE ";
			//DB $query .= "i_u.id=i_sg.userid AND i_sg.stugroupid='$groupid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$query = "SELECT i_u.LastName,i_u.FirstName FROM imas_stugroupmembers AS i_sg,imas_users AS i_u WHERE ";
			$query .= "i_u.id=i_sg.userid AND i_sg.stugroupid=:stugroupid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':stugroupid'=>$groupid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$grpmem .= "<li>".Sanitize::encodeStringForDisplay($row[0]).", ". Sanitize::encodeStringForDisplay($row[1])."</li>";
			}
			$grpmem .= '</ul></p>';
		}

		//DB $query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id FROM ";
		//DB $query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
		//DB $query .= "WHERE i_w_r.wikiid='$id' AND i_w_r.stugroupid='$groupid' ORDER BY i_w_r.id DESC";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $numrevisions = mysql_num_rows($result);
		$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id FROM ";
		$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
		$query .= "WHERE i_w_r.wikiid=:wikiid AND i_w_r.stugroupid=:stugroupid ORDER BY i_w_r.id DESC";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':wikiid'=>$id, ':stugroupid'=>$groupid));
		$numrevisions = $stm->rowCount();

		if ($numrevisions == 0) {
			$text = '';
		} else {
			//DB $row = mysql_fetch_row($result);
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			$text = $row['revision'];
			if (strlen($text)>6 && substr($text,0,6)=='**wver') {
				$wikiver = substr($text,6,strpos($text,'**',6)-6);
				$text = substr($text,strpos($text,'**',6)+2);
			} else {
				$wikiver = 1;
			}
			$lastedittime = tzdate("F j, Y, g:i a",$row['time']);
			$lasteditedby = $row['LastName'].', '.$row['FirstName'];
		}
		if (isset($studentid)) {
			$rec = "data-base=\"wikiintext-$id\" ";
			$text = str_replace('<a ','<a '.$rec, $text);
		}
		//DB $query = "UPDATE imas_wiki_views SET lastview=$now WHERE userid='$userid' AND wikiid='$id' AND stugroupid='$groupid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_affected_rows()==0) {
		$stm = $DBH->prepare("UPDATE imas_wiki_views SET lastview=:lastview WHERE userid=:userid AND wikiid=:wikiid AND stugroupid=:stugroupid");
		$stm->execute(array(':lastview'=>$now, ':userid'=>$userid, ':wikiid'=>$id, ':stugroupid'=>$groupid));
		if ($stm->rowCount()==0) {
			//DB $query = "INSERT INTO imas_wiki_views (userid,wikiid,stugroupid,lastview) VALUES ('$userid','$id',$groupid,$now)";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("INSERT INTO imas_wiki_views (userid,wikiid,stugroupid,lastview) VALUES (:userid, :wikiid, :stugroupid, :lastview)");
			$stm->execute(array(':userid'=>$userid, ':wikiid'=>$id, ':stugroupid'=>$groupid, ':lastview'=>$now));
		}

	}
}


//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/viewwiki.js?v=051710"></script>';
 $addr = $GLOBALS['basesiteurl'] . '/wikis/wikirev.php?cid=' . $cid . '&id=' . $id . $framed;
 if ($isgroup) {
	 $addr .= '&grp='.$groupid;
 }
 $addr2 = $GLOBALS['basesiteurl'] . '/wikis/viewwiki.php?revert=ask&cid=' . $cid . '&id=' . $id . $framed;
 if ($isgroup) {
	 $addr2 .= '&grp='.$groupid;
 }
 $placeinhead .= '<script type="text/javascript">var AHAHrevurl = "'.$addr.'"; var reverturl = "'.$addr2.'";</script>';
 $placeinhead .= '<style type="text/css"> a.grayout {color: #ccc; cursor: default;} del {color: #f99; text-decoration:none;} ins {color: #6f6; text-decoration:none;} .wikicontent {padding: 10px;}</style>';
 if ($isgroup && isset($teacherid)) {
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function chgfilter() {';
	$placeinhead .= '  var gfilter = document.getElementById("gfilter").value;';
	$placeinhead .= "  window.location = \"viewwiki.php?cid=$cid&id=$id$framed&grp=\"+gfilter;";
	$placeinhead .= '}';
	$placeinhead .= '</script>';
 }

 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
	echo "<p><a href=\"$imasroot/course/course.php?cid=$cid$framed\">Back</a></p>";
} else {  // DISPLAY
	if ($shownav) {
		echo '<div class="breadcrumb">'.$curBreadcrumb.'</div>';
	}
?>
	<div id="headerviewwiki" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php

	if (isset($teacherid) && $groupid>0 && !isset($curgroupname)) {
		//DB $query = "SELECT name FROM imas_stugroups WHERE id='$groupid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $grpnote = 'group '.mysql_result($result,0,0)."'s";
		$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
		$stm->execute(array(':id'=>$groupid));
		$grpnote = 'group '.$stm->fetchColumn(0)."'s";
	} else {
		$grpnote = 'this';
	}
	if (isset($_GET['delall']) && isset($teacherid)) {
		echo '<p>Are you SURE you want to delete all contents and history for '.Sanitize::encodeStringForDisplay($grpnote).' Wiki page?</p>';

		$querystring = http_build_query(array('cid'=>$cid,'id'=>$id,'grp'=>$groupid));
		echo '<form method="post" action="viewwiki.php?'.$querystring.$framed.'">';
		echo '<p><button type=submit name=delall value=true>'._("Yes, I'm Sure").'</button> ';
		echo "<button type=\"button\" class=\"secondarybtn\" onclick=\"window.location.href='viewwiki.php?$querystring$framed'\">Nevermind</button></p>";
		echo '</p>';
		echo '</form>';

	} else if (isset($_GET['delrev']) && isset($teacherid)) {
		echo '<p>Are you SURE you want to delete all revision history for '.Sanitize::encodeStringForDisplay($grpnote).' Wiki page?  The current version will be retained.</p>';

		$querystring = http_build_query(array('cid'=>$cid,'id'=>$id,'grp'=>$groupid));
		echo '<form method="post" action="viewwiki.php?'.$querystring.$framed.'">';
		echo '<p><button type=submit name=delrev value=true>'._("Yes, I'm Sure").'</button> ';
		echo "<button type=\"button\" class=\"secondarybtn\" onclick=\"window.location.href='viewwiki.php?$querystring$framed'\">Nevermind</button></p>";
		echo '</p>';
		echo '</form>';

	} else if (isset($_GET['revert'])) {
		$torev = Sanitize::onlyInt($_GET['torev']);
		$disprev = Sanitize::onlyInt($_GET['disprev']);
		echo '<p>Are you SURE you want to revert to revision '.Sanitize::onlyInt($disprev).' of '.Sanitize::encodeStringForDisplay($grpnote).' Wiki page?  All changes after that revision will be deleted.</p>';

		$querystring = http_build_query(array('cid'=>$cid,'id'=>$id,'grp'=>$groupid,'torev'=>$torev));
		echo '<form method="post" action="viewwiki.php?'.$querystring.$framed.'">';
		echo '<p><button type=submit name=revert value=true>'._("Yes, I'm Sure").'</button> ';
		echo "<button type=\"button\" class=\"secondarybtn\" onclick=\"window.location.href='viewwiki.php?$querystring$framed'\">Nevermind</button></p>";
		echo '</p>';
		echo '</form>';

	} else if (isset($_GET['snapshot'])) {
		echo "<p>Current Version Code.  <a href=\"viewwiki.php?cid=".Sanitize::courseId($cid)."&id=". Sanitize::onlyInt($id)."&grp=".Sanitize::onlyInt($groupid).Sanitize::encodeStringForDisplay($framed)."\">Back</a></p>";
		echo '<div class="editor" style="font-family:courier; padding: 10px;">';
		$snapShotText = str_replace('&gt; &lt;',"&gt;<br/>&lt;",Sanitize::encodeStringForDisplay($text));
        echo $snapShotText;
		echo '</div>';
	} else { //default page display
		if ($isgroup && isset($teacherid)) {
			echo '<p>Viewing page for group: ';
			writeHtmlSelect('gfilter',$stugroup_ids,$stugroup_names,$groupid,null,null,'onchange="chgfilter()"');
			echo '</p>';
		} else if ($isgroup) {
			echo "<p>Group:". Sanitize::encodeStringForDisplay($curgroupname)."</p>";
		}
?>
<?php
if (isset($teacherid)) {
	echo '<div class="cpmid">';
	if ($isgroup) {
		$grpnote = "For this group's wiki: ";
	}
	echo "<button type=\"button\" onclick=\"window.location.href='viewwiki.php?cid=".Sanitize::courseId($cid)."&id=".Sanitize::onlyInt($id)."&grp=".Sanitize::onlyInt($groupid)."&delall=ask".Sanitize::encodeStringForJavascript($framed)."'\">Clear Page Contents</button> | ";
	echo "<button type=\"button\" onclick=\"window.location.href='viewwiki.php?cid=".Sanitize::courseId($cid)."&id=".Sanitize::onlyInt($id)."&grp=".Sanitize::onlyInt($groupid)."&delrev=ask".Sanitize::encodeStringForJavascript($framed)."'\">Clear Page History</button> | ";
	echo "<a href=\"viewwiki.php?cid=".Sanitize::courseId($cid)."&id=".Sanitize::onlyInt($id)."&grp=".Sanitize::onlyInt($groupid)."&snapshot=true".Sanitize::encodeUrlParam($framed)."\">Current Version Snapshot</a></div>";
}
echo '<p><span id="revisioninfo">Revision '.$numrevisions;
if ($numrevisions>0) {
	echo ".  Last edited by $lasteditedby on $lastedittime.";
}
echo '</span>';

if ($numrevisions>1) {
	$last = $numrevisions-1;
	echo '<span id="prevrev"><input type="button" value="Show Revision History" onclick="initrevisionview()" /></span>';
	echo '<span id="revcontrol" style="display:none;"><br/>Revision history: <a href="#" id="first" onclick="jumpto(1)">First</a> <a id="older" href="#" onclick="seehistory(1); return false;">Older</a> ';
	echo '<a id="newer" class="grayout" href="#" onclick="seehistory(-1); return false;">Newer</a> <a href="#" class="grayout" id="last" onclick="jumpto(0)">Last</a> <input type="button" id="showrev" value="Show Changes" onclick="showrevisions()" />';
	if (isset($teacherid)) {
		echo '<a id="revrevert" style="display:none;" href="#">Revert to this revision</a>';
	}
	echo '</span>';
}
?>
	</p>
	<div class="editor">
<?php
if ($canedit) {
	echo "<button type=\"button\" onclick=\"window.location.href='editwiki.php?cid=".Sanitize::courseId($cid)."&id=". Sanitize::onlyInt($id)."&grp=".Sanitize::onlyInt($groupid). Sanitize::encodeStringForJavascript($framed)."'\">Edit this page</button>";
}
?>
	<div class="wikicontent" id="wikicontent"><?php echo filter($text); ?></div></div>

<?php
if ($isgroup) {
	//encoded when set on line  223
	echo $grpmem;
}
}
}

require("../footer.php");
?>
