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
$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/course/course.php?cid=$cid\">$coursename</a>";

if ($cid==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a course id";
} else if ($id==0) {
	$overwriteBody=1;
	$body = "You need to access this page with a wiki id";
} else if (isset($_GET['delall']) && isset($teacherid)) {
	if ($_GET['delall']=='true') {
		$query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id'";	
		mysql_query($query) or die("Query failed : " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewwiki.php?cid=$cid&id=$id");	
		exit;
	} else {
		$curBreadcrumb .= " &gt; Clear WikiPage Contents\n";	
		$pagetitle = "Confirm Page Contents Delete";
	}
} else if (isset($_GET['delrev']) && isset($teacherid)) {
	if ($_GET['delrev']=='true') {
		$query = "SELECT id FROM imas_wiki_revisions WHERE wikiid='$id' ORDER BY id DESC LIMIT 1";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$curid = mysql_result($result,0,0);
		$query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND id<$curid";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewwiki.php?cid=$cid&id=$id");	
		exit;
	} else {
		$curBreadcrumb .= " &gt; Clear WikiPage History\n";	
		$pagetitle = "Confirm History Delete";
	}
	
} else if (isset($_GET['revert'])) {
	if ($_GET['revert']=='true') {
		$revision = intval($_GET['torev']);
		$query = "SELECT revision FROM imas_wiki_revisions WHERE wikiid='$id' ";
		$query .= "AND id>=$revision ORDER BY id DESC";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>1 && $revision>0) {
			$row = mysql_fetch_row($result);
			$base = diffstringsplit($row[0]);
			while ($row = mysql_fetch_row($result)) { //apply diffs
				$diffs = explode('],[',substr($row[0],2,strlen($row[0])-4));
				for ($i = count($diffs)-1; $i>=0; $i--) {
					$diffs[$i] = explode(',',$diffs[$i]);
					if ($diffs[$i][0]==2) { //replace
						$diffs[$i][3] = str_replace(array('\\"','\\\\'),array('"','\\'),substr($diffs[$i][3],1,strlen($diffs[$i][3])-2));
						array_splice($base,$diffs[$i][1],$diffs[$i][2],$diffs[$i][3]);
					} else if ($diffs[$i][0]==0) { //insert
						$diffs[$i][2] = str_replace(array('\\"','\\\\'),array('"','\\'),substr($diffs[$i][2],1,strlen($diffs[$i][2])-2));
						array_splice($base,$diffs[$i][1],0,$diffs[$i][2]);
					} else if ($diffs[$i][0]==1) { //delete
						array_splice($base,$diffs[$i][1],$diffs[$i][2]);
					}
				}
			}
			$newbase = addslashes(implode(' ',$base));
			$query = "UPDATE imas_wiki_revisions SET revision='$newbase' WHERE id=$revision";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$id' AND id>$revision";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewwiki.php?cid=$cid&id=$id");	
		exit;
	} else {
		$curBreadcrumb .= " &gt; Revert Wiki\n";	
		$pagetitle = "Confirm Wiki Version Revert";
	}
		
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	
	
	$curBreadcrumb .= " &gt; View Wiki";
	
	$query = "SELECT name,startdate,enddate,editbydate,avail FROM imas_wikis WHERE id='$id'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	$wikiname = $row[0];
	$now = time();
	if (!isset($teacherid) && ($row[4]==0 || ($row[4]==1 && ($now<$row[1] || $now>$row[2])))) {
		$overwriteBody=1;
		$body = "This wiki is not currently available for viewing";
	} else {
		require_once("../filter/filter.php");

		if (isset($teacherid) || $now<$row[3]) {
			$canedit = true;
		} else {
			$canedit = false;
		}
		$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id FROM ";
		$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
		$query .= "WHERE i_w_r.wikiid='$id' ORDER BY i_w_r.id DESC";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$numrevisions = mysql_num_rows($result);
		
		if ($numrevisions == 0) {
			$text = '';
		} else {
			$row = mysql_fetch_row($result);
			$text = $row[1];
			$lastedittime = tzdate("F j, Y, g:i a",$row[2]);
			$lasteditedby = $row[3].', '.$row[4];
		}
	}
}


//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
 $pagetitle = "View Wiki: $wikiname";
 $placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/viewwiki.js"></script>';
 $addr = 'http://'.$_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/wikirev.php?cid='.$cid.'&id='.$id;
 $addr2 = 'http://'.$_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/viewwiki.php?revert=ask&cid='.$cid.'&id='.$id;
 
 $placeinhead .= '<script type="text/javascript">var AHAHrevurl = "'.$addr.'"; var reverturl = "'.$addr2.'";</script>';
 $placeinhead .= '<style type="text/css"> a.grayout {color: #ccc; cursor: default;} del {background-color: #f99; text-decoration:none;} ins {background-color: #9f9; text-decoration:none;} .wikicontent {padding: 10px;}</style>';
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {  // DISPLAY 	
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerviewwiki" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php
	if (isset($_GET['delall']) && isset($teacherid)) {
		echo '<p>Are you SURE you want to delete all contents and history for this Wiki page?</p>';
		
		echo "<p><a href=\"viewwiki.php?cid=$cid&id=$id&delall=true\">Yes, I'm Sure</a> | ";
		echo "<a href=\"viewwiki.php?cid=$cid&id=$id\">Nevermind</a></p>";
		
	} else if (isset($_GET['delrev']) && isset($teacherid)) {
		echo '<p>Are you SURE you want to delete all revision history for this Wiki page?  The current version will be retained.</p>';
		
		echo "<p><a href=\"viewwiki.php?cid=$cid&id=$id&delrev=true\">Yes, I'm Sure</a> | ";
		echo "<a href=\"viewwiki.php?cid=$cid&id=$id\">Nevermind</a></p>";
	} else if (isset($_GET['revert'])) {
		$torev = $_GET['torev'];
		$disprev = $_GET['disprev'];
		echo '<p>Are you SURE you want to revert to revision '.$disprev.' of this Wiki page?  All changes after that revision will be deleted.</p>';
		
		echo "<p><a href=\"viewwiki.php?cid=$cid&id=$id&torev=$torev&revert=true\">Yes, I'm Sure</a> | ";
		echo "<a href=\"viewwiki.php?cid=$cid&id=$id\">Nevermind</a></p>";
		
	} else { //default page display
?>
<?php
if (isset($teacherid)) {
	echo "<p><a href=\"viewwiki.php?cid=$cid&id=$id&delall=ask\">Clear Page Contents</a> | ";
	echo "<a href=\"viewwiki.php?cid=$cid&id=$id&delrev=ask\">Clear Page History</a></p>";
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
	echo '<a id="revrevert" style="display:none;" href="#">Revert to this revision</a>';
	echo '</span>';
}
?>
	</p>
	<div class="editor">
<?php
if ($canedit) {
	echo "<a href=\"editwiki.php?cid=$cid&id=$id\">Edit this page</a>";
}
?>	
	<div class="wikicontent" id="wikicontent"><?php echo filter($text); ?></div></div>
	
<?php
}
}

require("../footer.php");
?>
					
		
		
		
		
		
