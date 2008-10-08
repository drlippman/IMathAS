<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/
function updatesessdata($uid,$oldasid,$newasid) {
	$query = "SELECT sessionid,sessiondata FROM imas_sessions WHERE userid='$uid'";
	$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$tmpsessdata = unserialize(base64_decode($row[1]));
		if ($tmpsessdata['sessiontestid']==$oldasid) {
			$tmpsessdata['sessiontestid'] = $newasid;
			$tmpsessdata['groupid'] = 0;
			$tmpsessdata = base64_encode(serialize($tmpsessdata));
			$query = "UPDATE imas_sessions SET sessiondata='$tmpsessdata' WHERE sessionid='{$row[0]}'";
			mysql_query($query) or die("Query failed : $query:" . mysql_error());
		}
	}
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Manage Student Groups";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" . $_GET['cid'] . "\">$coursename</a> ";


if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else {

	$cid = $_GET['cid'];
	
	if (isset($_GET['addtogrp']) && isset($_POST['grpid']) && count($_POST['stutoadd'])>0 && isset($_GET['aid'])) {
		$aid = $_GET['aid'];
		if ($_POST['grpid']=="new") {
			//see if first student selected has an existing session (maybe broken from group)
			$query = "SELECT id,agroupid FROM imas_assessment_sessions WHERE assessmentid='$aid' AND userid='{$_POST['stutoadd'][0]}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$asid = mysql_result($result,0,0);
				$query = "UPDATE imas_assessment_sessions SET agroupid='$asid' WHERE id='$asid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$_POST['grpid'] = $asid;
				array_splice($_POST['stutoadd'],0,1);
			}
		}
		if ($_POST['grpid']=="new") {
			//if really new, create assessment session, then copy to all students
			$query = "SELECT * FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$adata = mysql_fetch_array($result, MYSQL_ASSOC);
			
			require("../assessment/asidutil.php");
			list($qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist) = generateAssessmentData($adata['itemorder'],$adata['shuffle'],$aid);
			$starttime = time();
			$agroupid = 0;
			foreach ($_POST['stutoadd'] as $uid) {
				//check for existing asid (perhaps started, but separated from group)
				$query = "SELECT id FROM imas_assessment_sessions WHERE userid='$uid' AND assessmentid='$aid'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
								if (mysql_num_rows($result)>0) {
					$oldasid = mysql_result($result,0,0);
				} else {
					$oldasid = 0;
				}
				$query = "INSERT INTO imas_assessment_sessions (userid,agroupid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,bestscores,bestattempts,bestseeds,bestlastanswers) ";
				$query .= "VALUES ('$uid','$agroupid','$aid','$qlist','$seedlist','$scorelist','$attemptslist','$lalist',$starttime,'$scorelist','$attemptslist','$seedlist','$lalist');";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$asid = mysql_insert_id();
				if ($agroupid==0) {
					$query = "UPDATE imas_assessment_sessions SET agroupid='$asid' WHERE id='$asid'";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$agroupid = $asid;
				}
				//if had existing asid, delete it and update sess info
				if ($oldasid>0) {
					$query = "DELETE FROM imas_assessment_sessions WHERE id='$oldasid'";
					mysql_query($query) or die("Query failed : $query:" . mysql_error());
					updatesessdata($uid,$oldasid,$newasid);
				}
			}
			
		} else {
			$query = "SELECT assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,reattempting,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
			$query .= "FROM imas_assessment_sessions WHERE agroupid='{$_POST['grpid']}' LIMIT 1";
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$row = mysql_fetch_row($result);
			$insrow = "'".implode("','",addslashes_deep($row))."'";
			foreach ($_POST['stutoadd'] as $uid) {
				//check for existing asid (perhaps started, but separated from group)
				$query = "SELECT id FROM imas_assessment_sessions WHERE userid='$uid' AND assessmentid='$aid'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				if (mysql_num_rows($result)>0) {
					$oldasid = mysql_result($result,0,0);
				} else {
					$oldasid = 0;
				}
				//add session info
				$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,reattempting,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
				$query .= "VALUES ('$uid',$insrow)";
				mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$newasid = mysql_insert_id();
				//if had existing asid, delete it and update sess info
				if ($oldasid>0) {
					$query = "DELETE FROM imas_assessment_sessions WHERE id='$oldasid'";
					mysql_query($query) or die("Query failed : $query:" . mysql_error());
					updatesessdata($uid,$oldasid,$newasid);
				}
			}
		}
		
	}
	
		
	if (isset($_GET['breakfromgrp']) && isset($_GET['asid'])) {
		$query = "SELECT count(id) FROM imas_assessment_sessions WHERE agroupid='{$_GET['asid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_result($result,0,0)>1) { //was group creator and others in group; need to move to new id
			$query = "SELECT id,agroupid,userid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$oldgroupid = $row[1];
			$thisuserid = $row[2];
			$query = "SELECT userid,assessmentid,questions,seeds,scores,attempts,lastanswers,reattempting,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
			$query .= "FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$row = mysql_fetch_row($result);
			$insrow = "'".implode("','",addslashes_deep($row))."'";
			$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,reattempting,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
			$query .= "VALUES ($insrow)";
			mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$newasid = mysql_insert_id();
			$query = "DELETE FROM imas_assessment_sessions WHERE id='{$_GET['asid']}' LIMIT 1";
			mysql_query($query) or die("Query failed : $query:" . mysql_error());
			updatesessdata($thisuserid,$_GET['asid'],$newasid);
		} else {
			$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE id='{$_GET['asid']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}

	if (isset($_GET['aid']) && isset($_GET['cleargrps'])) {
		//Assessment selected - list groups in assessment
		$aid = $_GET['aid'];
		
		if ($_GET['cleargrps']=='true') {
			$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
			exit();
		} else {
			$pagetitle = "Clear Groups";
			$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&aid=$aid\">Assessment Groups</a>";
			$curBreadcrumb .= "&gt; Clear Groups";
		}
	} elseif (isset($_GET['aid'])) {
		$aid = $_GET['aid'];
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Assessment Groups";
		
		$query = "SELECT name FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_curAssessmentName = mysql_result($result,0,0);
		
		$query = "SELECT userid FROM imas_students WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stus = array();
		while ($row = mysql_fetch_row($result)) {
			$stus[] = $row[0];
		}
		
		$query = "SELECT ias.agroupid,ias.id,iu.LastName,iu.FirstName,ias.userid,ias.bestscores,ias.userid FROM imas_users AS iu, imas_assessment_sessions as ias WHERE ";
		$query .= "ias.userid=iu.id AND ias.assessmentid='$aid' AND ias.agroupid>0 ORDER BY agroupid,iu.LastName,iu.FirstName";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$lastgroup = -1; $grpcnt = 1; $grpstus = array(); $groupids = array();
		$page_ulList = "";
		while ($row = mysql_fetch_row($result)) {
			if ($row[0]!=$lastgroup) {
				if ($lastgroup!=-1) {
					$page_ulList .= "		</ul>\n";
				}
				$scores = explode(",",$row[5]);
				$pts = 0;
				for ($j=0;$j<count($scores);$j++) {
					$pts += getpts($scores[$j]);
					//if ($scores[$i]>0) {$total += $scores[$i];}
				}
				$page_ulList .=  "	<b>Group $grpcnt</b> \n";
				$page_ulList .= "<a href=\"gb-viewasid.php?cid=$cid&aid=$aid&asid={$row[1]}&uid={$row[6]}&from=stugrp\">$pts pts</a>";
				if (in_array(-1,$scores)) {
					$page_ulList .= " (IP)";
				}
				$page_ulList .=  "		<ul>\n";
				$groupids[$grpcnt] = $row[0];
				$grpcnt++;
				$lastgroup = $row[0];
			}
			$page_ulList .= "			<li>{$row[2]}, {$row[3]} ";
			$page_ulList .= "<a href=\"managestugrps.php?cid=$cid&aid=$aid&asid={$row[1]}&breakfromgrp=true\" onClick=\"return confirm('Are you sure you want to remove this student from this group?');\">Break from Group</a>";
			$page_ulList .=  "			</li>\n";
			$grpstus[] = $row[4];
		}
		if ($lastgroup!=-1) {
			$page_ulList .=  "		</ul>\n";
		}
		$ungrpstus = array_diff($stus,$grpstus);
		
		if (count($ungrpstus)>0) {

			$page_ungrpSudents = array();
			for ($i=1;$i<$grpcnt;$i++) {
				$page_ungrpStudents['val'][$i-1] .= $groupids[$i];
				$page_ungrpStudents['label'][$i-1] .= $i;
			}
			
			$idlist = "'".implode("','",$ungrpstus)."'";
			$query = "SELECT id,LastName,FirstName FROM imas_users WHERE id IN ($idlist) ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());

			$page_liList = "";	
			while ($row = mysql_fetch_row($result)) {
				$page_liList .= "			<li><input type=checkbox name=\"stutoadd[]\" value=\"{$row[0]}\" />{$row[1]}, {$row[2]}</li>\n";
			}

		}

	} else { //DEFAULT DATA MANIPULATION
		$curBreadcrumb .= " &gt; Manage Student Groups";
		
		$query = "SELECT id,name FROM imas_assessments AS ia WHERE ia.courseid='$cid' AND ia.isgroup>0 ORDER BY ia.name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_liList2 = "";
		while ($row = mysql_fetch_row($result)) {
			$page_liList2 .= "<li><a href=\"managestugrps.php?cid=$cid&aid={$row[0]}\">{$row[1]}</li>\n";
		}
		$page_noGroupsMsg = (mysql_num_rows($result)==0) ? "<li>No assessments with groups</li>\n" : "";
		
	}
}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {	
?>
	<div class="breadcrumb"><?php echo $curBreadcrumb ?></div>
	<h3><?php echo $pagetitle ?></h3>

<?php
	if (isset($_GET['aid']) &&  isset($_GET['cleargrps']) && $_GET['cleargrps']!='true') {
		//Assessment selected - list groups in assessment
		$aid = $_GET['aid'];
?>		
		<p>Are you sure you want to clear all groups?</p>
		<p><input type=button value="Yes, Clear" onClick="window.location='managestugrps.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&cleargrps=true'">
		<input type=button value="Nevermind" onClick="window.location='managestugrps.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>'"></p>
<?php
	} elseif (isset($_GET['aid'])) {
		$aid = $_GET['aid'];
?>		
		<p><a href="managestugrps.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&cleargrps=ask">Clear All Groups</a></p>
		<h3>Managing groups for <?php echo $page_curAssessmentName ?></h3>
		<?php echo $page_ulList ?>
		
<?php		
		if (count($ungrpstus)>0) {
?>
		<h4>Students not in a group</h4>
		<form method=post action="managestugrps.php?cid=<?php echo $cid ?>&aid=<?php echo $aid ?>&addtogrp=true">
			<p>
				With selected, add to group
				<?php writeHtmlSelect("grpid",$page_ungrpStudents['val'],$page_ungrpStudents['label'],null,"New Group","new",null); ?>
				<input type="submit" value="Add"/>
			</p>

			<ul class=nomark>
			<?php echo $page_liList ?>
			</ul>
		</form>
<?php
		}

	} else {
?>	
		
		<h4>Assessment Groups</h4>
		<p>Group assessments are listed below.  Select an assessment to edit the groups of.</p>
		<ul>
		<?php echo $page_liList2 ?>
		<?php echo $page_noGroupsMsg ?>
		</ul>

<?php
	}	
}	

require("../footer.php");

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) { 
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) { 
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}
?>
	
