<?php
//IMathAS:  Manage Student Groups
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	
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
			$questions = explode(",",$adata['itemorder']);
			foreach($questions as $k=>$q) {
				if (strpos($q,'~')!==false) {
					$sub = explode('~',$q);
					$questions[$k] = $sub[array_rand($sub,1)];
				}
			}
			if ($adata['shuffle']&1) {shuffle($questions);}
			
			if ($adata['shuffle']&2) { //all questions same random seed
				if ($adata['shuffle']&4) { //all students same seed
					$seeds = array_fill(0,count($questions),$aid);
				} else {
					$seeds = array_fill(0,count($questions),rand(1,9999));
				}
			} else {
				if ($adata['shuffle']&4) { //all students same seed
					for ($i = 0; $i<count($questions);$i++) {
						$seeds[] = $aid + $i;
					}
				} else {
					for ($i = 0; $i<count($questions);$i++) {
						$seeds[] = rand(1,9999);
					}
				}
			}
	
			$scores = array_fill(0,count($questions),-1);
			$attempts = array_fill(0,count($questions),0);
			$lastanswers = array_fill(0,count($questions),'');
			
			$starttime = time();
			
			$qlist = implode(',',$questions);
			$seedlist = implode(',',$seeds);
			$scorelist = implode(',',$scores);
			$attemptslist = implode(',',$attempts);
			$lalist = implode('~',$lastanswers);
			
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
			$query = "SELECT assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
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
				$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
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
			$query = "SELECT userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
			$query .= "FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$row = mysql_fetch_row($result);
			$insrow = "'".implode("','",addslashes_deep($row))."'";
			$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
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
	
	
	if (isset($_GET['aid'])) {
		//Assessment selected - list groups in assessment
		$aid = $_GET['aid'];
		
		if (isset($_GET['cleargrps'])) {
			if ($_GET['cleargrps']=='true') {
				$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE assessmentid='$aid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			} else {
				require("../header.php");
				echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> &gt; ";
				echo "<a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&aid=$aid\">Assessment Groups</a>";
				echo "&gt; Clear Groups</div>";
				echo "<p>Are you sure you want to clear all groups?</p>";
				echo "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='managestugrps.php?cid=$cid&aid=$aid&cleargrps=true'\">\n";
				echo "<input type=button value=\"Nevermind\" onClick=\"window.location='managestugrps.php?cid=$cid&aid=$aid'\"></p>\n";
				require("../footer.php");
				exit;
			}
		}
		$pagetitle = "Manage Assessment Groups";
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> &gt; ";
		echo "<a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Assessment Groups</div>";
		echo '<h2>Manage Assessment Groups</h2>';
		echo "<p><a href=\"managestugrps.php?cid=$cid&aid=$aid&cleargrps=ask\">Clear All Groups</a></p>";
		$query = "SELECT name FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<h3>Managing groups for '.mysql_result($result,0,0).'</h3>';
		
		$query = "SELECT userid FROM imas_students WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stus = array();
		while ($row = mysql_fetch_row($result)) {
			$stus[] = $row[0];
		}
		
		$query = "SELECT ias.agroupid,ias.id,iu.LastName,iu.FirstName,ias.userid FROM imas_users AS iu, imas_assessment_sessions as ias WHERE ";
		$query .= "ias.userid=iu.id AND ias.assessmentid='$aid' AND ias.agroupid>0 ORDER BY agroupid,iu.LastName,iu.FirstName";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$lastgroup = -1; $grpcnt = 1; $grpstus = array(); $groupids = array();
		while ($row = mysql_fetch_row($result)) {
			if ($row[0]!=$lastgroup) {
				if ($lastgroup!=-1) {
					echo '</ul>';
				}
				echo "<h4>Group $grpcnt</h4>";
				echo '<ul>';
				$groupids[$grpcnt] = $row[0];
				$grpcnt++;
				$lastgroup = $row[0];
			}
			echo "<li>{$row[2]}, {$row[3]} ";
			echo "<a href=\"managestugrps.php?cid=$cid&aid=$aid&asid={$row[1]}&breakfromgrp=true\" onClick=\"return confirm('Are you sure you want to remove this student from this group?');\">Break from Group</a>";
			echo "</li>";
			$grpstus[] = $row[4];
		}
		if ($lastgroup!=-1) {
			echo '</ul>';
		}
		$ungrpstus = array_diff($stus,$grpstus);
		
		if (count($ungrpstus)>0) {
			echo '<h4>Students not in a group</h4>';
			echo "<form method=post action=\"managestugrps.php?cid=$cid&aid=$aid&addtogrp=true\">";
			echo "<p>With selected, add to group ";
			echo '<select name="grpid"><option value="new">New Group</option>';
			for ($i=1;$i<$grpcnt;$i++) {
				echo "<option value=\"{$groupids[$i]}\">$i</option>";
			}
			echo '</select> <input type="submit" value="Add"/></p>';
			$idlist = "'".implode("','",$ungrpstus)."'";
			$query = "SELECT id,LastName,FirstName FROM imas_users WHERE id IN ($idlist) ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			echo '<ul class=nomark>';
			while ($row = mysql_fetch_row($result)) {
				echo "<li><input type=checkbox name=\"stutoadd[]\" value=\"{$row[0]}\" />{$row[1]}, {$row[2]}</li>";
			}
			echo '</ul>';
			echo '</form>';
		}
		require("../footer.php");
		exit;
	}
	
	//Pick a group to manage
	$pagetitle = "Manage Student Groups";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> &gt; Manage Student Groups</div>";
	echo '<h2>Manage Student Groups</h2>';
	echo '<h4>Assessment Groups</h4>';
	echo '<p>Group assessments are listed below.  Select an assessment to edit the groups of.</p>';
	echo '<ul>';
	$query = "SELECT id,name FROM imas_assessments AS ia WHERE ia.courseid='$cid' AND ia.isgroup>0 ORDER BY ia.name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<li><a href=\"managestugrps.php?cid=$cid&aid={$row[0]}\">{$row[1]}</li>";
	}
	if (mysql_num_rows($result)==0) {
		echo '<li>No assessments with groups</li>';
	}
	echo '</ul>';
	require("../footer.php");
?>
	
