<?php
//IMathAS:  View Wiki page
//(c) 2010 David Lippman


/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/diff.php");
					

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

$cid = intval($_GET['cid']);
$id = intval($_GET['id']);
$groupid = intval($_GET['grp']);
$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/course/course.php?cid=$cid\">$coursename</a>";

if ($cid==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a course id";
} else if ($id==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a wiki id";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	
	
	$query = "SELECT name,startdate,enddate,editbydate,avail,groupsetid FROM imas_wikis WHERE id='$id'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	$wikiname = $row[0];
	$now = time();
	if (!isset($teacherid) && ($row[4]==0 || ($row[4]==1 && ($now<$row[1] || $now>$row[2])))) {
		$overwriteBody=1;
		$body = "This wiki is not currently available for viewing";
	} else if (isset($_GET['delall']) && isset($teacherid)) {
		if ($_GET['delall']=='true') {
			$query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid'";	
			mysql_query($query) or die("Query failed : " . mysql_error());
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewwiki.php?cid=$cid&id=$id&grp=$groupid");	
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid\">View Wiki</a>";
			$curBreadcrumb .= " &gt; Clear WikiPage Contents\n";	
			$pagetitle = "Confirm Page Contents Delete";
		}
	} else if (isset($_GET['delrev']) && isset($teacherid)) {
		if ($_GET['delrev']=='true') {
			$query = "SELECT id FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' ORDER BY id DESC LIMIT 1";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$curid = mysql_result($result,0,0);
				$query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' AND id<$curid";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewwiki.php?cid=$cid&id=$id&grp=$groupid");	
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid\">View Wiki</a>";
			$curBreadcrumb .= " &gt; Clear WikiPage History\n";	
			$pagetitle = "Confirm History Delete";
		}
		
	} else if (isset($_GET['revert']) && isset($teacherid)) {
		if ($_GET['revert']=='true') {
			$revision = intval($_GET['torev']);
			$query = "SELECT revision FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' ";
			$query .= "AND id>=$revision ORDER BY id DESC";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>1 && $revision>0) {
				$row = mysql_fetch_row($result);
				$base = diffstringsplit($row[0]);
				while ($row = mysql_fetch_row($result)) { //apply diffs
					$base = diffapplydiff($base,$row[0]);
				}
				$newbase = addslashes(implode(' ',$base));
				$query = "UPDATE imas_wiki_revisions SET revision='$newbase' WHERE id=$revision";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupid' AND id>$revision";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewwiki.php?cid=$cid&id=$id");	
			exit;
		} else {
			$curBreadcrumb .= " &gt; <a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid\">View Wiki</a>";
			$curBreadcrumb .= " &gt; Revert Wiki\n";	
			$pagetitle = "Confirm Wiki Version Revert";
		}
	} else { //just viewing
		$curBreadcrumb .= " &gt; View Wiki";
	
		require_once("../filter/filter.php");

		if (isset($teacherid) || $now<$row[3]) {
			$canedit = true;
		} else {
			$canedit = false;
		}
		
		//if is group wiki, get groupid or fail
		if ($row[5]>0 && !isset($teacherid)) {
			$isgroup = true;
			$groupsetid = $row[5];
			$query = 'SELECT i_sg.id,i_sg.name FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			$query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='$groupsetid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)==0) {
				$overwriteBody=1;
				$body = "You need to be a member of a group before you can view or edit this wiki";
				$isgroup = false;
			} else {
				$groupid = mysql_result($result,0,0);
				$curgroupname = mysql_result($result,0,1);
			}
		} else if ($row[5]>0 && isset($teacherid)) {
			$isgroup = true;
			$groupsetid = $row[5];
			$stugroup_ids = array();
			$stugroup_names = array();
			$hasnew = array();
			$wikilastviews = array();
			   $query = "SELECT stugroupid,lastview FROM imas_wiki_views WHERE userid='$userid' AND wikiid='$id'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   while ($row = mysql_fetch_row($result)) {
				   $wikilastviews[$row[0]] = $row[1];
			   }
			   
			   $query = "SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid='$id' GROUP BY stugroupid";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   while ($row = mysql_fetch_row($result)) {
				   if (!isset($wikilastviews[$row[0]]) || $wikilastviews[$row[0]] < $row[1]) {
					   $hasnew[$row[0]] = 1;
				   }
			   }
			$i = 0;
			$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY name";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());	
			while ($row = mysql_fetch_row($result)) {
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
			$query = "SELECT i_u.LastName,i_u.FirstName FROM imas_stugroupmembers AS i_sg,imas_users AS i_u WHERE ";
			$query .= "i_u.id=i_sg.userid AND i_sg.stugroupid='$groupid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$grpmem .= "<li>{$row[0]}, {$row[1]}</li>";
			} 
			$grpmem .= '</ul></p>';
		}
				
		$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id FROM ";
		$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
		$query .= "WHERE i_w_r.wikiid='$id' AND i_w_r.stugroupid='$groupid' ORDER BY i_w_r.id DESC";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$numrevisions = mysql_num_rows($result);
		
		if ($numrevisions == 0) {
			$text = '';
		} else {
			$row = mysql_fetch_row($result);
			$text = $row[1];
			if (strlen($text)>6 && substr($text,0,6)=='**wver') {
				$wikiver = substr($text,6,strpos($text,'**',6)-6);
				$text = substr($text,strpos($text,'**',6)+2);
			} else {
				$wikiver = 1;
			}
			$lastedittime = tzdate("F j, Y, g:i a",$row[2]);
			$lasteditedby = $row[3].', '.$row[4];
		}
		if (isset($studentid)) {
			$rec = "data-base=\"wikiintext-$id\" ";
			$text = str_replace('<a ','<a '.$rec, $text);
		}
		$query = "UPDATE imas_wiki_views SET lastview=$now WHERE userid='$userid' AND wikiid='$id' AND stugroupid='$groupid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_affected_rows()==0) {
			$query = "INSERT INTO imas_wiki_views (userid,wikiid,stugroupid,lastview) VALUES ('$userid','$id',$groupid,$now)";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
	}
}


//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $pagetitle = "View Wiki: $wikiname";
 $placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/viewwiki.js?v=051710"></script>';
 $addr = $urlmode.$_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/wikirev.php?cid='.$cid.'&id='.$id;
 if ($isgroup) {
	 $addr .= '&grp='.$groupid;
 }
 $addr2 = $urlmode.$_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/viewwiki.php?revert=ask&cid='.$cid.'&id='.$id;
 if ($isgroup) {
	 $addr2 .= '&grp='.$groupid;
 }
 $placeinhead .= '<script type="text/javascript">var AHAHrevurl = "'.$addr.'"; var reverturl = "'.$addr2.'";</script>';
 $placeinhead .= '<style type="text/css"> a.grayout {color: #ccc; cursor: default;} del {color: #f99; text-decoration:none;} ins {color: #6f6; text-decoration:none;} .wikicontent {padding: 10px;}</style>';
 if ($isgroup && isset($teacherid)) {
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function chgfilter() {';
	$placeinhead .= '  var gfilter = document.getElementById("gfilter").value;';
	$placeinhead .= "  window.location = \"viewwiki.php?cid=$cid&id=$id&grp=\"+gfilter;";
	$placeinhead .= '}';
	$placeinhead .= '</script>';
 }
 
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
	echo "<p><a href=\"$imasroot/course/course.php?cid=$cid\">Back</a></p>";
} else {  // DISPLAY 	
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerviewwiki" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php
	if (isset($teacherid) && $groupid>0 && !isset($curgroupname)) {
		$query = "SELECT name FROM imas_stugroups WHERE id='$groupid'";		
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$grpnote = 'group '.mysql_result($result,0,0)."'s";
	} else {
		$grpnote = 'this';
	}
	if (isset($_GET['delall']) && isset($teacherid)) {
		echo '<p>Are you SURE you want to delete all contents and history for '.$grpnote.' Wiki page?</p>';
		
		echo "<p><a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid&delall=true\">Yes, I'm Sure</a> | ";
		echo "<a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid\">Nevermind</a></p>";
		
	} else if (isset($_GET['delrev']) && isset($teacherid)) {
		echo '<p>Are you SURE you want to delete all revision history for '.$grpnote.' Wiki page?  The current version will be retained.</p>';
		
		echo "<p><a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid&delrev=true\">Yes, I'm Sure</a> | ";
		echo "<a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid\">Nevermind</a></p>";
	} else if (isset($_GET['revert'])) {
		$torev = $_GET['torev'];
		$disprev = $_GET['disprev'];
		echo '<p>Are you SURE you want to revert to revision '.$disprev.' of '.$grpnote.' Wiki page?  All changes after that revision will be deleted.</p>';
		
		echo "<p><a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid&torev=$torev&revert=true\">Yes, I'm Sure</a> | ";
		echo "<a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid\">Nevermind</a></p>";
		
	} else if (isset($_GET['snapshot'])) {
		echo "<p>Current Version Code.  <a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid\">Back</a></p>";
		echo '<div class="editor" style="font-family:courier; padding: 10px;">';
		echo str_replace('&gt; &lt;',"&gt;<br/>&lt;",htmlentities($text));
		echo '</div>';
	} else { //default page display
		if ($isgroup && isset($teacherid)) {
			echo '<p>Viewing page for group: ';
			writeHtmlSelect('gfilter',$stugroup_ids,$stugroup_names,$groupid,null,null,'onchange="chgfilter()"');
			echo '</p>';
		} else if ($isgroup) {
			echo "<p>Group: $curgroupname</p>";
		}
?>
<?php
if (isset($teacherid)) {
	echo '<p>';
	if ($isgroup) {
		$grpnote = "For this group's wiki: ";
	}
	echo "<a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid&delall=ask\">Clear Page Contents</a> | ";
	echo "<a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid&delrev=ask\">Clear Page History</a> | ";
	echo "<a href=\"viewwiki.php?cid=$cid&id=$id&grp=$groupid&snapshot=true\">Current Version Snapshot</a></p>";
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
	echo "<a href=\"editwiki.php?cid=$cid&id=$id&grp=$groupid\">Edit this page</a>";
}
?>	
	<div class="wikicontent" id="wikicontent"><?php echo filter($text); ?></div></div>
	
<?php
if ($isgroup) {
	echo $grpmem;
}
}
}

require("../footer.php");
?>
					
		
		
		
		
		
