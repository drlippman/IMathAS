<?php
//IMathAS: LTI instructor home page
//(c) 2011 David Lippman

require("init.php");
if (!isset($sessiondata['ltirole']) || $sessiondata['ltirole']!='instructor') {
	echo "Not authorized to view this page";
	exit;
}
//decide what we need to display
if ($sessiondata['ltiitemtype']==0) {
	$hascourse = true;
	$hasplacement = true;
	$placementtype = 'assess';
	$typeid = $sessiondata['ltiitemid'];
	//DB $query = "SELECT courseid FROM imas_assessments WHERE id='$typeid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $cid = mysql_result($result,0,0);
	$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$typeid));
	$cid = $stm->fetchColumn(0);
	//DB $query = "SELECT id FROM imas_teachers WHERE courseid='$cid' AND userid='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {
	$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
	$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
	if ($stm->rowCount()==0) {
		$role = 'tutor';
	} else {
		$role = 'teacher';
	}
} else {
	//DB $query = "SELECT courseid FROM imas_lti_courses WHERE contextid='{$sessiondata['lti_context_id']}' ";
	//DB $query .= "AND org='{$sessiondata['ltiorg']}'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {
	$stm = $DBH->prepare("SELECT courseid FROM imas_lti_courses WHERE contextid=:contextid AND org=:org");
	$stm->execute(array(':contextid'=>$sessiondata['lti_context_id'], ':org'=>$sessiondata['ltiorg']));
	if ($stm->rowCount()==0) {
		$hascourse = false;
		if (isset($sessiondata['lti_launch_get']) && isset($sessiondata['lti_launch_get']['cid'])) {
			$cid = intval($sessiondata['lti_launch_get']['cid']);
			if ($cid>0) {
				//DB $query = "INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES ";
				//DB $query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}',$cid)";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES (:org, :contextid, :courseid)");
				$stm->execute(array(':org'=>$sessiondata['ltiorg'], ':contextid'=>$sessiondata['lti_context_id'], ':courseid'=>$cid));
				$hascourse = true;
			}
		}
	} else {
		$hascourse = true;
		//DB $cid = mysql_result($result,0,0);
		$cid = $stm->fetchColumn(0);
	}
	if ($hascourse) {
		//DB $query = "SELECT id,placementtype,typeid FROM imas_lti_placements WHERE contextid='{$sessiondata['lti_context_id']}' ";
		//DB $query .= "AND org='{$sessiondata['ltiorg']}' AND linkid='{$sessiondata['lti_resource_link_id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)==0) {
		$query = "SELECT id,placementtype,typeid FROM imas_lti_placements WHERE contextid=:contextid ";
		$query .= "AND org=:org AND linkid=:linkid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':contextid'=>$sessiondata['lti_context_id'], ':org'=>$sessiondata['ltiorg'], ':linkid'=>$sessiondata['lti_resource_link_id']));
		if ($stm->rowCount()==0) {
			$hasplacement = false;
			if (isset($sessiondata['lti_launch_get']) && isset($sessiondata['lti_launch_get']['aid'])) {
				$aid = intval($sessiondata['lti_launch_get']['aid']);
				if ($aid>0) {
					$placementtype = 'assess';
					$typeid = $aid;
					//DB $query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
					//DB $query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}','{$sessiondata['lti_resource_link_id']}','$placementtype','$typeid')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					//DB $placementid = mysql_insert_id();
					$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
					$query .= "(:org, :contextid, :linkid, :placementtype, :typeid)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':org'=>$sessiondata['ltiorg'], ':contextid'=>$sessiondata['lti_context_id'], ':linkid'=>$sessiondata['lti_resource_link_id'], ':placementtype'=>$placementtype, ':typeid'=>$typeid));
					$placementid = $DBH->lastInsertId();
					$hasplacement = true;
				}
			}
		} else {
			$hasplacement = true;
			//DB list($placementid,$placementtype,$typeid) = mysql_fetch_row($result);
			list($placementid,$placementtype,$typeid) = $stm->fetch(PDO::FETCH_NUM);
		}
		$role = 'teacher';
	}
}

//handle form postbacks
$createcourse = Sanitize::onlyInt($_POST['createcourse']);
if (!empty($createcourse)) {
	//DB $query = "SELECT courseid FROM imas_teachers WHERE courseid='{$_POST['createcourse']}' AND userid='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
	$stm = $DBH->prepare("SELECT courseid FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
	$stm->execute(array(':courseid'=>$createcourse, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$cid = $createcourse;
	} else {
		$_POST['createcourse'] = $createcourse;
		//log terms agreement if needed
		//DB $query = "SELECT termsurl FROM imas_courses WHERE id='{$_POST['createcourse']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT termsurl FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$createcourse));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row[0]!='') { //has terms of use url
			$now = time();
			$userid = intval($userid);
			//DB $query = "INSERT INTO imas_log (time,log) VALUES($now,'User $userid agreed to terms of use on course {$_POST['createcourse']}')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
			$stm->execute(array(':time'=>$now, ':log'=>'User $userid agreed to terms of use on course '.$_POST['createcourse']));
		}
		//creating a copy of a template course
		$blockcnt = 1;
		//DB $itemorder = addslashes(serialize(array()));
		$itemorder = serialize(array());
		$randkey = uniqid();
		$hideicons = isset($CFG['CPS']['hideicons'])?$CFG['CPS']['hideicons'][0]:0;
		$picicons = isset($CFG['CPS']['picicons'])?$CFG['CPS']['picicons'][0]:0;
		$allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
		$copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
		$msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
		$msgmonitor = (floor($msgset/5))&1;
		$msgset = $msgset%5;
		$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
		$showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;

		$avail = 0;
		$lockaid = 0;
		//DB mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
		$DBH->beginTransaction();

		$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,showlatepass,itemorder,available,theme,ltisecret,blockcnt) VALUES ";
		$query .= "(:name, :ownerid, :enrollkey, :hideicons, :picicons, :allowunenroll, :copyrights, :msgset, :showlatepass, :itemorder, :available, :theme, :ltisecret, :blockcnt);";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':name'=>$sessiondata['lti_context_label'], ':ownerid'=>$userid, ':enrollkey'=>$randkey, ':hideicons'=>$hideicons, ':picicons'=>$picicons,
			':allowunenroll'=>$allowunenroll, ':copyrights'=>$copyrights, ':msgset'=>$msgset, ':showlatepass'=>$showlatepass, ':itemorder'=>$itemorder,
			':available'=>$avail, ':theme'=>$theme, ':ltisecret'=>$randkey, ':blockcnt'=>$blockcnt));
		$cid = $DBH->lastInsertId();
		//if ($myrights==40) {
			//DB $query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('$userid','$cid')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		//}
		$useweights = intval(isset($CFG['GBS']['useweights'])?$CFG['GBS']['useweights']:0);
		$orderby = intval(isset($CFG['GBS']['orderby'])?$CFG['GBS']['orderby']:0);
		$defgbmode = intval(isset($CFG['GBS']['defgbmode'])?$CFG['GBS']['defgbmode']:21);
		$usersort = intval(isset($CFG['GBS']['usersort'])?$CFG['GBS']['usersort']:0);

		//DB $query = "INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES ('$cid',$useweights,$orderby,$defgbmode,$usersort)";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES (:courseid, :useweights, :orderby, :defgbmode, :usersort)");
		$stm->execute(array(':courseid'=>$cid, ':useweights'=>$useweights, ':orderby'=>$orderby, ':defgbmode'=>$defgbmode, ':usersort'=>$usersort));



		//DB $query = "SELECT useweights,orderby,defaultcat,defgbmode,stugbmode FROM imas_gbscheme WHERE courseid='{$_POST['createcourse']}'";
		//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		//TODO: copy settings in one query?
		$stm = $DBH->prepare("SELECT useweights,orderby,defaultcat,defgbmode,stugbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$createcourse));
		$row = $stm->fetch(PDO::FETCH_NUM);
		//DB $query = "UPDATE imas_gbscheme SET useweights='{$row[0]}',orderby='{$row[1]}',defaultcat='{$row[2]}',defgbmode='{$row[3]}',stugbmode='{$row[4]}' WHERE courseid='$cid'";
		//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_gbscheme SET useweights=:useweights,orderby=:orderby,defaultcat=:defaultcat,defgbmode=:defgbmode,stugbmode=:stugbmode WHERE courseid=:courseid");
		$stm->execute(array(':useweights'=>$row[0], ':orderby'=>$row[1], ':defaultcat'=>$row[2], ':defgbmode'=>$row[3], ':stugbmode'=>$row[4], ':courseid'=>$cid));

		$gbcats = array();
		//DB $query = "SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid='{$_POST['createcourse']}'";
		//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$createcourse));

		$stm2 = $DBH->prepare("INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight) VALUES (:courseid, :name, :scale, :scaletype, :chop, :dropn, :weight)");

		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			//DB $query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight) VALUES ";
			//DB $frid = array_shift($row);
			//DB $irow = "'".implode("','",addslashes_deep($row))."'";
			//DB $query .= "('$cid',$irow)";
			//DB $query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight) VALUES ($row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6])";
			//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
			//DB $gbcats[$frid] = mysql_insert_id();
			$frid = $row[0];
			$stm2->execute(array(':courseid'=>$cid, ':name'=>$row[1], ':scale'=>$row[2], ':scaletype'=>$row[3], ':chop'=>$row[4], ':dropn'=>$row[5], ':weight'=>$row[6]));
			$gbcats[$frid] = $DBH->lastInsertId();
		}
		$copystickyposts = true;
		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='{$_POST['createcourse']}'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $items = unserialize(mysql_result($result,0,0));
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$createcourse));
		$items = unserialize($stm->fetchColumn(0));
		$newitems = array();
		require("includes/copyiteminc.php");
		copyallsub($items,'0',$newitems,$gbcats);
		//DB $itemorder = addslashes(serialize($newitems));
		$itemorder = serialize($newitems);
		//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
		copyrubrics();
		//DB mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
		$DBH->commit();

	}
	//DB $query = "UPDATE imas_lti_courses SET courseid=$cid WHERE org='{$sessiondata['ltiorg']}' AND contextid='{$sessiondata['lti_context_id']}'";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_affected_rows()==0) {
	$stm = $DBH->prepare("UPDATE imas_lti_courses SET courseid=:courseid WHERE org=:org AND contextid=:contextid");
	$stm->execute(array(':courseid'=>$cid, ':org'=>$sessiondata['ltiorg'], ':contextid'=>$sessiondata['lti_context_id']));
	//DB if (mysql_affected_rows()==0) {
	if ($stm->rowCount()==0) {
		//DB $query = "INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES ";
		//DB $query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}',$cid)";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES (:org, :contextid, :courseid)");
		$stm->execute(array(':org'=>$sessiondata['ltiorg'], ':contextid'=>$sessiondata['lti_context_id'], ':courseid'=>$cid));
	}
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
			//DB $query = "SELECT name FROM imas_assessments WHERE id='$typeid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $atitle = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$typeid));
			$atitle = $stm->fetchColumn(0);
			$url = $GLOBALS['basesiteurl'] . "/bltilaunch.php?custom_place_aid=$typeid&r=" .Sanitize::randomQueryStringParam();
			
			header('Location: '.$sessiondata['lti_selection_return'].'?embed_type=basic_lti&url='.Sanitize::encodeUrlParam($url).'&title='.Sanitize::encodeUrlParam($atitle).'&text='.Sanitize::encodeUrlParam($atitle). '&r=' .Sanitize::randomQueryStringParam());
			exit;

		} else {
			//DB $query = "SELECT name FROM imas_courses WHERE id='$typeid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $cname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$typeid));
			$cname = $stm->fetchColumn(0);
			$url = $GLOBALS['basesiteurl'] . "/bltilaunch.php?custom_open_folder=$typeid-0&r" .Sanitize::randomQueryStringParam();
			header('Location: '.$sessiondata['lti_selection_return'].'?embed_type=basic_lti&url='.Sanitize::encodeUrlParam($url).'&title='.Sanitize::encodeUrlParam($cname).'&text='.Sanitize::encodeUrlParam($cname). '&r=' .Sanitize::randomQueryStringParam());
			exit;
		}
	}
	if ($hasplacement) {
		//DB $query = "UPDATE imas_lti_placements SET placementtype='$placementtype',typeid='$typeid' WHERE id='$placementid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_lti_placements SET placementtype=:placementtype,typeid=:typeid WHERE id=:id");
		$stm->execute(array(':placementtype'=>$placementtype, ':typeid'=>$typeid, ':id'=>$placementid));
	} else {
		//DB $query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
		//DB $query .= "('{$sessiondata['ltiorg']}','{$sessiondata['lti_context_id']}','{$sessiondata['lti_resource_link_id']}','$placementtype','$typeid')";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $placementid = mysql_insert_id();
		$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
		$query .= "(:org, :contextid, :linkid, :placementtype, :typeid)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':org'=>$sessiondata['ltiorg'], ':contextid'=>$sessiondata['lti_context_id'], ':linkid'=>$sessiondata['lti_resource_link_id'], ':placementtype'=>$placementtype, ':typeid'=>$typeid));
		$placementid = $DBH->lastInsertId();
		$hasplacement = true;
	}
}

if ($hasplacement && $placementtype=='course') {
	if (!isset($_GET['showhome']) && !isset($_GET['chgplacement'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=" . Sanitize::courseId($cid) . "&r=" .Sanitize::randomQueryStringParam());
		exit;
	}
}

//HTML Output
$pagetitle = "LTI Home";
require("header.php");
if (!$hascourse || isset($_GET['chgcourselink'])) {
	echo '<script type="text/javscript">
	function updateCourseSelector(el) {
		if ($(el).find(":selected").data("termsurl")) {
			$("#termsbox").show();
			$("#termsurl").attr("href",$(el).find(":selected").data("termsurl"));
		}
		else {
			$("#termsbox").hide();
		}
	}
	</script>';
	echo '<h3>Link courses</h3>';
	echo '<form method="post" action="ltihome.php">';
	echo "<p>This course on your LMS has not yet been linked to a course on $installname.";
	echo 'Select a course to link with.  If it is a template course, a copy will be created for you:<br/> <select name="createcourse" onchange="updateCourseSelector(this)"> ';
	//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$userid' AND ic.available<4 ORDER BY ic.name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
	$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid=:userid AND ic.available<4 ORDER BY ic.name");
	$stm->execute(array(':userid'=>$userid));
	if ($stm->rowCount()>0) {
		echo '<optgroup label="Your Courses">';
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			printf('<option value="%d">%s</option>' ,Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]));
		}
		echo '</optgroup>';
	}

	//DB $query = "SELECT id,name,copyrights,termsurl FROM imas_courses WHERE (istemplate&1)=1 AND copyrights=2 AND available<4 ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
	$stm = $DBH->query("SELECT id,name,copyrights,termsurl FROM imas_courses WHERE (istemplate&1)=1 AND copyrights=2 AND available<4 ORDER BY name");
	if ($stm->rowCount()>0) {
		echo '<optgroup label="Template Courses">';
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'"';
			if ($row[3]!='') {
				echo ' data-termsurl="'.Sanitize::encodeStringForDisplay($row[3]).'"';
			}
			echo '>'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
		}
		echo '</optgroup>';
	}
	
	$query = "SELECT ic.id,ic.name,ic.copyrights,ic.termsurl FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
	$query .= "iu.groupid=:groupid AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':groupid'=>$groupid));
	if ($stm->rowCount()>0) {
		echo '<optgroup label="Group Template Courses">';
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'"';
			if ($row[3]!='') {
				echo ' data-termsurl="'.Sanitize::encodeStringForDisplay($row[3]).'"';
			}
			echo '>'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
		}
		echo '</optgroup>';
	}
	
	echo '</select>';
	echo '<p id="termsbox" style="display:none;">This course has special <a id="termsurl">Terms of Use</a>.  By copying this course, you agree to these terms.</p>';
	echo '<input type="Submit" value="Create"/>';
	echo "<p>If you want to create a new course, log directly into $installname to create new courses</p>";
	echo '</form>';
} else if (!$hasplacement || isset($_GET['chgplacement'])) {
	echo '<h3>Link courses</h3>';
	echo '<form method="post" action="ltihome.php">';
	echo "<p>This placement on your LMS has not yet been linked to content on $installname. ";
	//if (!isset($sessiondata['lti_selection_return'])) {
		echo 'You can either do a full course placement, in which case all content of the course is available from this one placement (but no grades are returned), or ';
		echo 'you can place an individual assessment (and grades will be returned, if supported by your LMS).  Select the placement you\'d like to make: ';
	//} else {
	//	echo 'Select the assessment you\'d like to use: ';
	//}

	echo '<br/> <select name="setplacement"> ';
	//if (!isset($sessiondata['lti_selection_return'])) {
		echo '<option value="course">Whole course Placement</option>';
	//}
	//DB $query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
	$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	if ($stm->rowCount()>0) {
		echo '<optgroup label="Assessment">';
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			printf('<option value="%d">%s</option>', Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]));
		}
		echo '</optgroup>';
	}
	echo '</select>';
	echo '<input type="Submit" value="Make Placement"/>';
	echo "<p>If you want to create new assessments, log directly into $installname</p>";
	echo "<p>If your LMS course is linked with the wrong course on $installname, ";
	echo '<a href="ltihome.php?chgcourselink=true" onclick="return confirm(\'Are you SURE you want to do this? This may break existing placements.\');">Change course link</a></p>';
	echo '</form>';
} else if ($placementtype=='course') {
	echo '<h3>LTI Placement of whole course</h3>';
	echo "<p><a href=\"course/course.php?cid=" . Sanitize::courseId($cid) . "\">Enter course</a></p>";
	echo '<p><a href="ltihome.php?chgplacement=true">Change placement</a></p>';
} else if ($placementtype=='assess') {
	//DB $query = "SELECT name,avail,startdate,enddate FROM imas_assessments WHERE id='$typeid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT name,avail,startdate,enddate,date_by_lti FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$typeid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	echo "<h3>LTI Placement of " . Sanitize::encodeStringForDisplay($line['name']) . "</h3>";
	echo "<p><a href=\"assessment/showtest.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "\">Preview assessment</a> | ";
	echo "<a href=\"course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">Grade list</a> ";
	if ($role == 'teacher') {
		echo "| <a href=\"course/gb-itemanalysis.php?cid=" . Sanitize::courseId($cid) . "&asid=average&aid=" . Sanitize::encodeUrlParam($typeid) . "\">Item Analysis</a>";
	}
	echo "</p>";

	$now = time();
	echo '<p>';
	if ($line['avail']==0) {
		echo 'Currently unavailable to students.';
	} else if ($line['date_by_lti']==1) {
		echo 'Waiting for the LMS to send a date';	
	} else if ($line['date_by_lti']>1) {
		echo 'Default due date set by LMS. Available until: '.formatdate($line['enddate']).'.';
		echo '</p><p>';
		if ($line['date_by_lti']==2) {
			echo 'This default due date was set by the date reported by the LMS in your instructor launch, and may change when the first student launches the assignment. ';
		} else {
			echo 'This default due date was set by the first student launch. ';
		}
		echo 'Be aware some LMSs will send unexpected dates on instructor launches, so don\'t worry if the date shown in the assessment preview is different than you expected or different than the default due date. ';
		echo '</p><p>';
		echo 'If the LMS reports a different due date for an individual student when they open this assignment, ';
		echo 'this system will handle that by setting a due date exception. ';
	} else if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) { //regular show
		echo "Currently available to students.  ";
		echo "Available until " . formatdate($line['enddate']);
	} else {
		echo 'Currently unavailable to students. Available '.formatdate($line['startdate']).' until '.formatdate($line['enddate']);
	}
	echo '</p>';
	if ($role == 'teacher') {
		echo "<p><a href=\"course/addassessment.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">Settings</a> | ";
		echo "<a href=\"course/addquestions.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">Questions</a></p>";
		if ($sessiondata['ltiitemtype']==-1) {
			echo '<p><a href="ltihome.php?chgplacement=true">Change placement</a></p>';
		}
		echo '<p>&nbsp;</p><p class=small>This assessment is housed in course ID '.Sanitize::courseId($cid).'</p>';
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
