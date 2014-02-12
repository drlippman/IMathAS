<?php
//IMathAS: LTI instructor home page
//(c) 2011 David Lippman

require("validate.php");
//decide what we need to display
if ($sessiondata['ltiitemtype']==0) {
	$hascourse = true;
	$hasplacement = true;
	$placementtype = 'assess';
	$typeid = $sessiondata['ltiitemid'];
	$query = "SELECT courseid FROM imas_assessments WHERE id='$typeid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$cid = mysql_result($result,0,0);
	$query = "SELECT id FROM imas_teachers WHERE courseid='$cid' AND userid='$userid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		$role = 'tutor';
	} else {
		$role = 'teacher';
	}
} else {
	$query = "SELECT courseid FROM imas_lti_courses WHERE contextid='{$sessiondata['lti_context_id']}' ";
	$query .= "AND org='{$sessiondata['ltiorg']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		$hascourse = false;
		if (isset($sessiondata['lti_launch_get']) && isset($sessiondata['lti_launch_get']['cid'])) {
			$cid = intval($sessiondata['lti_launch_get']['cid']);
			if ($cid>0) {
				$query = "INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES ";
				$query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}',$cid)";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$hascourse = true;
			} 
		}
	} else {
		$hascourse = true;
		$cid = mysql_result($result,0,0);
	}
	if ($hascourse) {
		$query = "SELECT id,placementtype,typeid FROM imas_lti_placements WHERE contextid='{$sessiondata['lti_context_id']}' ";
		$query .= "AND org='{$sessiondata['ltiorg']}' AND linkid='{$sessiondata['lti_resource_link_id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) {
			$hasplacement = false;
			if (isset($sessiondata['lti_launch_get']) && isset($sessiondata['lti_launch_get']['aid'])) {
				$aid = intval($sessiondata['lti_launch_get']['aid']);
				if ($aid>0) {
					$placementtype = 'assess';
					$typeid = $aid;
					$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
					$query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}','{$sessiondata['lti_resource_link_id']}','$placementtype','$typeid')";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$placementid = mysql_insert_id();
					$hasplacement = true;
				} 
			} 
		} else {
			$hasplacement = true;
			list($placementid,$placementtype,$typeid) = mysql_fetch_row($result);
		}
		$role = 'teacher';
	}
}

//handle form postbacks
if (isset($_POST['createcourse'])) {
	$query = "SELECT courseid FROM imas_teachers WHERE courseid='{$_POST['createcourse']}' AND userid='$userid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$cid = $_POST['createcourse'];
	} else {
		//creating a copy of a template course
		$blockcnt = 1;
		$itemorder = addslashes(serialize(array()));
		$randkey = uniqid();
		$hideicons = isset($CFG['CPS']['hideicons'])?$CFG['CPS']['hideicons'][0]:0;
		$picicons = isset($CFG['CPS']['picicons'])?$CFG['CPS']['picicons'][0]:0;
		$allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
		$copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
		$msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
		$msgmonitor = (floor($msgset/5))&1;
		$msgQtoInstr = (floor($msgset/5))&2;
		$msgset = $msgset%5;
		$cploc = isset($CFG['CPS']['cploc'])?$CFG['CPS']['cploc'][0]:1;
		$topbar = isset($CFG['CPS']['topbar'])?$CFG['CPS']['topbar'][0]:array(array(),array(),0);
		$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
		$chatset = isset($CFG['CPS']['chatset'])?$CFG['CPS']['chatset'][0]:0;
		$showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;
		
		$avail = 0;
		$lockaid = 0;
		$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,chatset,showlatepass,itemorder,topbar,cploc,available,theme,ltisecret,blockcnt) VALUES ";
		$query .= "('{$sessiondata['lti_context_label']}','$userid','$randkey','$hideicons','$picicons','$unenroll','$copyrights','$msgset',$chatset,$showlatepass,'$itemorder','$topbar','$cploc','$avail','$theme','$randkey','$blockcnt');";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$cid = mysql_insert_id();
		//if ($myrights==40) {
			$query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('$userid','$cid')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		//}
		$useweights = intval(isset($CFG['GBS']['useweights'])?$CFG['GBS']['useweights']:0);
		$orderby = intval(isset($CFG['GBS']['orderby'])?$CFG['GBS']['orderby']:0);
		$defgbmode = intval(isset($CFG['GBS']['defgbmode'])?$CFG['GBS']['defgbmode']:21);
		$usersort = intval(isset($CFG['GBS']['usersort'])?$CFG['GBS']['usersort']:0);
		
		$query = "INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES ('$cid',$useweights,$orderby,$defgbmode,$usersort)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		
		
		$query = "SELECT useweights,orderby,defaultcat,defgbmode,stugbmode FROM imas_gbscheme WHERE courseid='{$_POST['createcourse']}'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		$query = "UPDATE imas_gbscheme SET useweights='{$row[0]}',orderby='{$row[1]}',defaultcat='{$row[2]}',defgbmode='{$row[3]}',stugbmode='{$row[4]}' WHERE courseid='$cid'";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
		
		$gbcats = array();
		$query = "SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid='{$_POST['createcourse']}'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight) VALUES ";
			$frid = array_shift($row);
			$irow = "'".implode("','",addslashes_deep($row))."'";
			$query .= "('$cid',$irow)";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
			$gbcats[$frid] = mysql_insert_id();
		}
		$copystickyposts = true;
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_POST['createcourse']}'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		$newitems = array();
		require("includes/copyiteminc.php");
		copyallsub($items,'0',$newitems,$gbcats);
		$itemorder = addslashes(serialize($newitems));
		$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		copyrubrics();
	}
	
	$query = "INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES ";
	$query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}',$cid)";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$hascourse = true;
	
} else if (isset($_POST['setplacement'])) {
	if ($_POST['setplacement']=='course') {
		$placementtype = 'course';
		$typeid = $cid;
	} else {
		$placementtype = 'assess';
		$typeid = $_POST['setplacement'];
	}
	if (isset($sessiondata['lti_selection_return'])) {
		//Canvas custom LTI selection return
		if ($placementtype=='assess') {
			$query = "SELECT name FROM imas_assessments WHERE id='$typeid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$atitle = mysql_result($result,0,0);
			
			$url = $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/bltilaunch.php?custom_place_aid=$typeid";
			header('Location: '.$sessiondata['lti_selection_return'].'?embed_type=basic_lti&url='.urlencode($url).'&title='.urlencode($atitle).'&text='.urlencode($atitle));
			exit;
				
		} else {
			exit;
		}
	}
	if ($hasplacement) {
		$query = "UPDATE imas_lti_placements SET placementtype='$placementtype',typeid='$typeid' WHERE id='$placementid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	} else {
		$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
		$query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}','{$sessiondata['lti_resource_link_id']}','$placementtype','$typeid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$placementid = mysql_insert_id();
		$hasplacement = true;
	}
}

if ($hasplacement && $placementtype=='course') {
	if (!isset($_GET['showhome']) && !isset($_GET['chgplacement'])) {
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
		exit;
	} 
}

//HTML Output
$pagetitle = "LTI Home";
require("header.php");
if (!$hascourse) {
	echo '<h3>Link courses</h3>';
	echo '<form method="post" action="ltihome.php">';
	echo "<p>This course on your LMS has not yet been linked to a course on $installname.";
	echo 'Select a course to link with.  If it is a template course, a copy will be created for you:<br/> <select name="createcourse"> ';
	$query = "SELECT ic.id,ic.name FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$userid' ORDER BY ic.name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		echo '<optgroup label="Your Courses">';
		while ($row = mysql_fetch_row($result)) {
			echo '<option value="'.$row[0].'">'.$row[1].'</option>';
		}
		echo '</optgroup>';
	}
	if (isset($templateuser)) {
		$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' ORDER BY ic.name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			echo '<optgroup label="Template Courses">';
			while ($row = mysql_fetch_row($result)) {
				echo '<option value="'.$row[0].'">'.$row[1].'</option>';
			}
			echo '</optgroup>';
		}
	}
	echo '</select>';
	echo '<input type="Submit" value="Create"/>';
	echo "<p>If you want to create a new course, log directly into $installname to create new courses</p>";
	echo '</form>';
} else if (!$hasplacement || isset($_GET['chgplacement'])) {
	echo '<h3>Link courses</h3>';
	echo '<form method="post" action="ltihome.php">';
	echo "<p>This placement on your LMS has not yet been linked to content on $installname. ";
	if (!isset($sessiondata['lti_selection_return'])) {
		echo 'You can either do a full course placement, in which case all content of the course is available from this one placement, or ';
		echo 'you can place an individual assessment.  Select the placement you\'d like to make: ';
	} else {
		echo 'Select the assessment you\'d like to use: ';
	}
	
	echo '<br/> <select name="setplacement"> ';
	if (!isset($sessiondata['lti_selection_return'])) {
		echo '<option value="course">Whole course Placement</option>';
	}
	$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		echo '<optgroup label="Assessment">';
		while ($row = mysql_fetch_row($result)) {
			echo '<option value="'.$row[0].'">'.$row[1].'</option>';
		}
		echo '</optgroup>';
	}
	echo '</select>';
	echo '<input type="Submit" value="Make Placement"/>';
	echo "<p>If you want to create new assessments, log directly into $installname</p>";
	echo '</form>';
} else if ($placementtype=='course') {
	echo '<h3>LTI Placement of whole course</h3>';
	echo "<p><a href=\"course/course.php?cid=$cid\">Enter course</a></p>";
	echo '<p><a href="ltihome.php?chgplacement=true">Change placement</a></p>';
} else if ($placementtype=='assess') {
	$query = "SELECT name,avail,startdate,enddate FROM imas_assessments WHERE id='$typeid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	echo "<h3>LTI Placement of {$line['name']}</h3>";
	echo "<p><a href=\"assessment/showtest.php?cid=$cid&id=$typeid\">Preview assessment</a> | ";
	echo "<a href=\"course/isolateassessgrade.php?cid=$cid&aid=$typeid\">Grade list</a> ";
	if ($role == 'teacher') {
		echo "| <a href=\"course/gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">Item Analysis</a>";
	}
	echo "</p>";
	
	$now = time();
	echo '<p>';
	if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) { //regular show
		echo "Currently available to students.  Available until " . formatdate($line['enddate']);
	} else if ($line['avail']==0) {
		echo 'Currently unavailable to students.';
	} else {
		echo 'Currently unavailable to students. Available '.formatdate($line['startdate']).' until '.formatdate($line['enddate']); 
	}
	echo '</p>';
	if ($role == 'teacher') {
		echo "<p><a href=\"course/addassessment.php?cid=$cid&id=$typeid&from=lti\">Settings</a> | ";
		echo "<a href=\"course/addquestions.php?cid=$cid&aid=$typeid&from=lti\">Questions</a></p>";
		if ($sessiondata['ltiitemtype']==-1) {
			echo '<p><a href="ltihome.php?chgplacement=true">Change placement</a></p>';
		}
	}
}
require("footer.php");

function formatdate($date) {
	if ($date==0 || $date==2000000000) {
		return 'Always';
	} else {
		return tzdate("D n/j/y, g:i a",$date);   
	}
}

?>
