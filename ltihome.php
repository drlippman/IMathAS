<?php
//IMathAS: LTI instructor home page
//(c) 2011 David Lippman

require("init.php");
if (!isset($_SESSION['ltirole']) || $_SESSION['ltirole']!='instructor') {
	echo _("Not authorized to view this page");
	exit;
}

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['ltihome'])) {
	require($CFG['hooks']['ltihome']);
}

//decide what we need to display
if ($_SESSION['ltiitemtype']==0) {
	$hascourse = true;
	$hasplacement = true;
	$placementtype = 'assess';
	$typeid = $_SESSION['ltiitemid'];
	$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$typeid));
	$cid = $stm->fetchColumn(0);
	$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
	$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
	if ($stm->rowCount()==0) {
		$role = 'tutor';
	} else {
		$role = 'teacher';
	}
} else {
    $shortorg = explode(':', $_SESSION['ltiorg'])[0];
	$stm = $DBH->prepare("SELECT courseid FROM imas_lti_courses WHERE contextid=:contextid AND org LIKE :org");
    $stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':org'=>"$shortorg:%"));
	if ($stm->rowCount()==0) {
		$hascourse = false;
		if (isset($_SESSION['lti_launch_get']) && isset($_SESSION['lti_launch_get']['cid'])) {
			$cid = intval($_SESSION['lti_launch_get']['cid']);
			if ($cid>0) {
				$stm = $DBH->prepare("INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES (:org, :contextid, :courseid)");
				$stm->execute(array(':org'=>$_SESSION['ltiorg'], ':contextid'=>$_SESSION['lti_context_id'], ':courseid'=>$cid));
				$hascourse = true;
			}
		}
	} else {
		$hascourse = true;
		$cid = $stm->fetchColumn(0);
	}
	if ($hascourse) {
        $shortorg = explode(':', $_SESSION['ltiorg'])[0];
		$query = "SELECT id,placementtype,typeid FROM imas_lti_placements WHERE contextid=:contextid ";
		$query .= "AND org LIKE :org AND linkid=:linkid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':org'=>"$shortorg:%", ':linkid'=>$_SESSION['lti_resource_link_id']));
		if ($stm->rowCount()==0) {
			$hasplacement = false;
			if (isset($_SESSION['lti_launch_get']) && isset($_SESSION['lti_launch_get']['aid'])) {
				$aid = intval($_SESSION['lti_launch_get']['aid']);
				if ($aid>0) {
					$placementtype = 'assess';
					$typeid = $aid;
					$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
					$query .= "(:org, :contextid, :linkid, :placementtype, :typeid)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':org'=>$_SESSION['ltiorg'], ':contextid'=>$_SESSION['lti_context_id'], ':linkid'=>$_SESSION['lti_resource_link_id'], ':placementtype'=>$placementtype, ':typeid'=>$typeid));
					$placementid = $DBH->lastInsertId();
					$hasplacement = true;
				}
			}
		} else {
			$hasplacement = true;
			list($placementid,$placementtype,$typeid) = $stm->fetch(PDO::FETCH_NUM);
		}
		$role = 'teacher';
	}
}

//handle form postbacks
$createcourse = Sanitize::onlyInt($_POST['createcourse']);
if (!empty($createcourse)) {
	$stm = $DBH->prepare("SELECT courseid FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
	$stm->execute(array(':courseid'=>$createcourse, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$cid = $createcourse;
	} else {
		//log terms agreement if needed
		$stm = $DBH->prepare("SELECT termsurl FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$createcourse));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row[0]!='') { //has terms of use url
			$now = time();
			$userid = intval($userid);
			$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
			$stm->execute(array(':time'=>$now, ':log'=>'User $userid agreed to terms of use on course '.$createcourse));
		}
		//creating a copy of a template course
		$blockcnt = 1;
		$itemorder = serialize(array());
		$randkey = uniqid();
		$allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
		$copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
		$msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
		$msgmonitor = (floor($msgset/5))&1;
		$msgset = $msgset%5;
		$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
		$showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;

		$avail = 0;
		$lockaid = 0;
		$DBH->beginTransaction();

		$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,allowunenroll,copyrights,msgset,showlatepass,itemorder,available,theme,ltisecret,blockcnt) VALUES ";
		$query .= "(:name, :ownerid, :enrollkey, :allowunenroll, :copyrights, :msgset, :showlatepass, :itemorder, :available, :theme, :ltisecret, :blockcnt);";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':name'=>$_SESSION['lti_context_label'], ':ownerid'=>$userid, ':enrollkey'=>$randkey,
			':allowunenroll'=>$allowunenroll, ':copyrights'=>$copyrights, ':msgset'=>$msgset, ':showlatepass'=>$showlatepass, ':itemorder'=>$itemorder,
			':available'=>$avail, ':theme'=>$theme, ':ltisecret'=>$randkey, ':blockcnt'=>$blockcnt));
		$cid = $DBH->lastInsertId();
		//if ($myrights==40) {
			$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		//}
		$useweights = intval(isset($CFG['GBS']['useweights'])?$CFG['GBS']['useweights']:0);
		$orderby = intval(isset($CFG['GBS']['orderby'])?$CFG['GBS']['orderby']:0);
		$defgbmode = intval(isset($CFG['GBS']['defgbmode'])?$CFG['GBS']['defgbmode']:21);
		$usersort = intval(isset($CFG['GBS']['usersort'])?$CFG['GBS']['usersort']:0);
		$stm = $DBH->prepare("INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES (:courseid, :useweights, :orderby, :defgbmode, :usersort)");
		$stm->execute(array(':courseid'=>$cid, ':useweights'=>$useweights, ':orderby'=>$orderby, ':defgbmode'=>$defgbmode, ':usersort'=>$usersort));
		//TODO: copy settings in one query?
		$stm = $DBH->prepare("SELECT useweights,orderby,defaultcat,defgbmode,stugbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$createcourse));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$stm = $DBH->prepare("UPDATE imas_gbscheme SET useweights=:useweights,orderby=:orderby,defaultcat=:defaultcat,defgbmode=:defgbmode,stugbmode=:stugbmode WHERE courseid=:courseid");
		$stm->execute(array(':useweights'=>$row[0], ':orderby'=>$row[1], ':defaultcat'=>$row[2], ':defgbmode'=>$row[3], ':stugbmode'=>$row[4], ':courseid'=>$cid));

		$gbcats = array();
		$stm = $DBH->prepare("SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$createcourse));

		$stm2 = $DBH->prepare("INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight) VALUES (:courseid, :name, :scale, :scaletype, :chop, :dropn, :weight)");

		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$frid = $row[0];
			$stm2->execute(array(':courseid'=>$cid, ':name'=>$row[1], ':scale'=>$row[2], ':scaletype'=>$row[3], ':chop'=>$row[4], ':dropn'=>$row[5], ':weight'=>$row[6]));
			$gbcats[$frid] = $DBH->lastInsertId();
		}
		$copystickyposts = true;
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$createcourse));
		$items = unserialize($stm->fetchColumn(0));
		$newitems = array();
		require("includes/copyiteminc.php");
		copyallsub($items,'0',$newitems,$gbcats);
		$itemorder = serialize($newitems);
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
		copyrubrics();
		$DBH->commit();

		//call hook, if defined
		if (function_exists('onAddCourse')) {
			onAddCourse($cid, $userid);
		}
    }
    
    $shortorg = explode(':', $_SESSION['ltiorg'])[0];
    $stm = $DBH->prepare("SELECT courseid FROM imas_lti_courses WHERE contextid=:contextid AND org LIKE :org");
    $stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':org'=>"$shortorg:%"));
    $oldcourseid = $stm->fetchColumn(0);
    if ($oldcourseid === false) { // no existing course connection
		$stm = $DBH->prepare("INSERT INTO imas_lti_courses (org,contextid,courseid) VALUES (:org, :contextid, :courseid)");
		$stm->execute(array(':org'=>$_SESSION['ltiorg'], ':contextid'=>$_SESSION['lti_context_id'], ':courseid'=>$cid));
    } else if (!empty($cid) && $oldcourseid != $cid) {
        $stm = $DBH->prepare("UPDATE imas_lti_courses SET courseid=:courseid WHERE org LIKE :org AND contextid=:contextid");
        $stm->execute(array(':courseid'=>$cid, ':org'=>"$shortorg:%", ':contextid'=>$_SESSION['lti_context_id']));
        // if we're changing courses, we should delete any existing placements.
        $stm = $DBH->prepare("DELETE FROM imas_lti_placements WHERE contextid=:contextid AND org LIKE :org");
        $stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':org'=>"$shortorg:%"));
	} // otherwise, same course; do nothing
	$hascourse = true;

} else if (isset($_POST['setplacement'])) {
	if ($_POST['setplacement']=='course') {
		$placementtype = 'course';
		$typeid = $cid;
	} else {
		$placementtype = 'assess';
		$typeid = $_POST['setplacement'];
	}
	if (isset($_SESSION['lti_selection_return']) && $_SESSION['lti_selection_return_format'] == "Canvas") {
		//Canvas custom LTI selection return or IMS deeplink LTI selection return
		if ($placementtype=='assess') {
			$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$typeid));
			$atitle = $stm->fetchColumn(0);
			$url = $GLOBALS['basesiteurl'] . "/bltilaunch.php?custom_place_aid=$typeid";

			header('Location: '.$_SESSION['lti_selection_return'].'?embed_type=basic_lti&url='.Sanitize::encodeUrlParam($url).'&title='.Sanitize::encodeUrlParam($atitle).'&text='.Sanitize::encodeUrlParam($atitle). '&r=' .Sanitize::randomQueryStringParam());
			exit;

		} else {
			$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$typeid));
			$cname = $stm->fetchColumn(0);
			$url = $GLOBALS['basesiteurl'] . "/bltilaunch.php?custom_open_folder=$typeid-0";
			header('Location: '.$_SESSION['lti_selection_return'].'?embed_type=basic_lti&url='.Sanitize::encodeUrlParam($url).'&title='.Sanitize::encodeUrlParam($cname).'&text='.Sanitize::encodeUrlParam($cname). '&r=' .Sanitize::randomQueryStringParam());
			exit;
		}
	} else if (isset($_SESSION['lti_selection_return']) && $_SESSION['lti_selection_return_format'] == "IMSdeeplink") {
		require_once 'includes/OAuth.php';
		require_once 'includes/ltioauthstore.php';
		if ($placementtype=='assess') {
			$stm = $DBH->prepare("SELECT name,summary,ptsposs FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$typeid));
			list($title,$text,$ptsposs) = $stm->fetch(PDO::FETCH_NUM);
			$url = $GLOBALS['basesiteurl'] . "/bltilaunch.php?custom_place_aid=$typeid";
		} else {
			$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$typeid));
			$title = $stm->fetchColumn(0);
			$text = '';
			$url = $GLOBALS['basesiteurl'] . "/bltilaunch.php?custom_open_folder=$typeid-0";
		}
		$target = 'iframe';
		if (!empty($_SESSION['lti_selection_targets'])) {
			$allowedtargets = explode(',',$_SESSION['lti_selection_targets']);
			$desiredtargets = array('iframe','frame','window');
			foreach ($desiredtargets as $t) {
				if (in_array($t, $allowedtargets)) {
					$target = $t;
					break;
				}
			}
		}
		/**
		BB doesn't seem to allow the array @context
		'@context' => array(
				'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
				array(
					"lineItem" => "http://purl.imsglobal.org/ctx/lis/v2/LineItem",
					"res" => "http://purl.imsglobal.org/ctx/lis/v2p1/Result#"
				)
			),
		**/
		$contentitems = array(
			'@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
			'@graph' => array(
				array(
					'@type' => 'LtiLinkItem',
					'mediaType' => 'application/vnd.ims.lti.v1.ltilink',
					'url' => $url,
					'title' => $title,
					'placementAdvice' => array(
						'presentationDocumentTarget' => $target
					)
				)
			)
		);
		if ($placementtype=='assess' && $ptsposs>0) {
			$contentitems['@graph'][0]['lineItem'] = array(
				'@type' => 'LineItem',
				'label' => $title,
				'reportingMethod' => 'res:totalScore',
				'maximumScore' => $ptsposs,
				'scoreConstraints' => array(
					'@type' => 'NumericLimits',
					'normalMaximum' => $ptsposs,
					'totalMaximum' => $ptsposs
				)
			);
		}
		echo '<html><head><script type="text/javascript">
			window.onload = function() {
				document.getElementById("theform").submit();
			}
			</script></head>';
		$params = array(
			'lti_message_type' => 'ContentItemSelection',
			'lti_version' => 'LTI-1p0',
			'content_items' => json_encode($contentitems, JSON_INVALID_UTF8_IGNORE)
		);
		if (!empty($_SESSION['lti_selection_data'])) {
			$params['data'] = $_SESSION['lti_selection_data'];
		}
		$store = new IMathASLTIOAuthDataStore();
		$consumer = $store->lookup_consumer($_SESSION['lti_origkey']);
		$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
		$acc_req = OAuthRequest::from_consumer_and_token($consumer, false, 'POST', $_SESSION['lti_selection_return'], $params);
		$acc_req->sign_request($hmac_method, $consumer, false);
		$newparms = $acc_req->get_parameters();

		echo '<body><form id="theform" method="post" action="'.Sanitize::encodeStringForDisplay($_SESSION['lti_selection_return']).'">';
		//output form fields
		foreach($newparms as $key => $value ) {
			$key = Sanitize::encodeStringForDisplay($key);
			$value = Sanitize::encodeStringForDisplay($value);
			echo '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}
		echo '<input type="submit" value="'._('Continue').'" />';
		echo '</form></body></html>';
		exit;
	}
	if ($hasplacement) {
		$stm = $DBH->prepare("UPDATE imas_lti_placements SET placementtype=:placementtype,typeid=:typeid WHERE id=:id");
		$stm->execute(array(':placementtype'=>$placementtype, ':typeid'=>$typeid, ':id'=>$placementid));
	} else {
		$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
		$query .= "(:org, :contextid, :linkid, :placementtype, :typeid)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':org'=>$_SESSION['ltiorg'], ':contextid'=>$_SESSION['lti_context_id'], ':linkid'=>$_SESSION['lti_resource_link_id'], ':placementtype'=>$placementtype, ':typeid'=>$typeid));
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
	echo '<h2>'._('Link courses').'</h2>';
	echo '<form method="post" action="ltihome.php">';
	echo "<p>".sprintf(_("This course on your LMS has not yet been linked to a course on %s. "),$installname);
	echo _('Select a course to link with.  If it is a template course, a copy will be created for you:').'<br/> <select name="createcourse" onchange="updateCourseSelector(this)"> ';
	$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid=:userid AND ic.available<4 ORDER BY ic.name");
	$stm->execute(array(':userid'=>$userid));
	if ($stm->rowCount()>0) {
		echo '<optgroup label="'._('Your Courses').'">';
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			printf('<option value="%d">%s</option>' ,Sanitize::onlyInt($row[0]), Sanitize::encodeStringForDisplay($row[1]));
		}
		echo '</optgroup>';
	}
	$stm = $DBH->query("SELECT id,name,copyrights,termsurl FROM imas_courses WHERE istemplate > 0 AND (istemplate&1)=1 AND copyrights=2 AND available<4 ORDER BY name");
	if ($stm->rowCount()>0) {
		echo '<optgroup label="'._('Template Courses').'">';
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
	$query .= "iu.groupid=:groupid AND ic.istemplate > 0 AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':groupid'=>$groupid));
	if ($stm->rowCount()>0) {
		echo '<optgroup label="'._('Group Template Courses').'">';
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.Sanitize::onlyInt($row[0]).'"';
			if ($row[3]!='') {
				echo ' data-termsurl="'.Sanitize::encodeStringForDisplay($row[3]).'"';
			}
			echo '>'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
		}
		echo '</optgroup>';
	}

	echo '</select>';
	echo '<p id="termsbox" style="display:none;">'.sprintf(_('This course has special %s Terms of Use %s.  By copying this course, you agree to these terms.'),'<a id="termsurl">','</a>').'</p>';
	echo '<input type="Submit" value="'._('Link Course').'"/>';
	echo "<p>".sprintf(_("If you want to create a new course, log directly into %s to create new courses"),$installname)."</p>";
	echo '</form>';
} else if (!$hasplacement || isset($_GET['chgplacement'])) {
	if (isset($_SESSION['lti_selection_type']) && $_SESSION['lti_selection_type']=='assn') {
		echo '<h2>'._('Link Assignment').'</h2>';
	} else {
		echo '<h2>'._('Link Resource').'</h2>';
	}
	echo '<form method="post" action="ltihome.php">';
	echo "<p>".sprintf(_("This placement on your LMS has not yet been linked to content on %s. "),$installname);
	if (isset($_SESSION['lti_selection_type']) && $_SESSION['lti_selection_type']=='assn') {
		echo _('Select the assessment you\'d like to use: ');
	} else if (isset($_SESSION['lti_selection_type']) && $_SESSION['lti_selection_type']=='link') {
		echo _('You can either do a full course placement, in which case all content of the course is available from this one placement, or you can place an individual assessment. In both cases, grades will not be returned if you set up the link in this way. ');
		echo _('For grade return, you need to create a new assignment link instead.').'</p>';
		echo '<p>'._('Select the placement you\'d like to make: ');
	} else {
		echo _('You can either do a full course placement, in which case all content of the course is available from this one placement (but no grades are returned), or you can place an individual assessment (and grades will be returned, if supported by your LMS).  Select the placement you\'d like to make: ');
	}

	echo '<br/> <select name="setplacement"> ';

	if (isset($_SESSION['lti_selection_type']) && $_SESSION['lti_selection_type']=='link') {
		echo '<option value="course">'._('Whole Course Placement').'</option>';
	}
	$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	if ($stm->rowCount()>0) {
		echo '<optgroup label="'._('Assessments').'">';
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			printf('<option value="%d">%s</option>', Sanitize::onlyInt($row[0]), Sanitize::encodeStringForDisplay($row[1]));
		}
		echo '</optgroup>';
	}
	if (!isset($_SESSION['lti_selection_type']) || $_SESSION['lti_selection_type']=='all') {
		echo '<optgroup label="'._('Course').'">';
		echo '<option value="course">'._('Whole Course Placement').'</option>';
		echo '</optgroup>';
	}
	echo '</select>';
	echo '<input type="Submit" value="'._('Make Placement').'"/>';
	echo "<p>".sprintf(_('If you want to create new assessments, log directly into %s'),$installname)."</p>";
    echo "<p>".sprintf(_('Your LMS course is currently linked with %s course ID %d. '),$installname, $cid);
    echo sprintf(_("If your LMS course is linked with the wrong course on %s, "),$installname);
	echo '<a href="ltihome.php?chgcourselink=true" onclick="return confirm(\''._('Are you SURE you want to do this? This may break existing placements.').'\');">'._('Change course link').'</a></p>';
	echo '</form>';
} else if ($placementtype=='course') {
	echo '<h2>'._('LTI Placement of whole course').'</h2>';
	echo "<p><a href=\"course/course.php?cid=" . Sanitize::courseId($cid) . "\">"._("Enter course")."</a></p>";
	echo '<p><a href="ltihome.php?chgplacement=true">'._('Change placement').'</a></p>';
} else if ($placementtype=='assess') {
	$stm = $DBH->prepare("SELECT name,avail,startdate,enddate,date_by_lti,ver FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$typeid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	echo "<h2>".sprintf(_("LTI Placement of %s"), Sanitize::encodeStringForDisplay($line['name'])) . "</h2>";
	if ($line['ver'] > 1) {
		echo "<p><a href=\"assess2/?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a> | ";
		echo "<a href=\"course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a> ";
		if ($role == 'teacher') {
			echo "| <a href=\"course/gb-itemanalysis2.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a>";
		}
	} else {
		echo "<p><a href=\"assessment/showtest.php?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Preview assessment")."</a> | ";
		echo "<a href=\"course/isolateassessgrade.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Grade list")."</a> ";
		if ($role == 'teacher') {
			echo "| <a href=\"course/gb-itemanalysis.php?cid=" . Sanitize::courseId($cid) . "&asid=average&aid=" . Sanitize::encodeUrlParam($typeid) . "\">"._("Item Analysis")."</a>";
		}
	}
	echo "</p>";

	$now = time();
	echo '<p>';
	if ($line['avail']==0) {
		echo _('Currently unavailable to students.');
	} else if ($line['date_by_lti']==1) {
		echo _('Waiting for the LMS to send a date');
	} else if ($line['date_by_lti']>1) {
		echo sprintf(_('Default due date set by LMS. Available until: %s.'),formatdate($line['enddate']));
		echo '</p><p>';
		if ($line['date_by_lti']==2) {
			echo _('This default due date was set by the date reported by the LMS in your instructor launch, and may change when the first student launches the assignment. ');
		} else {
			echo _('This default due date was set by the first student launch. ');
		}
		echo _('Be aware some LMSs will send unexpected dates on instructor launches, so don\'t worry if the date shown in the assessment preview is different than you expected or different than the default due date. ');
		echo '</p><p>';
		echo _('If the LMS reports a different due date for an individual student when they open this assignment, this system will handle that by setting a due date exception. ');
	} else if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) { //regular show
		echo _("Currently available to students.")."  ";
		echo sprintf(_("Available until %s"), formatdate($line['enddate']));
	} else {
		echo sprintf(_('Currently unavailable to students. Available %s until %s'),formatdate($line['startdate']),formatdate($line['enddate']));
	}
	echo '</p>';
	if ($role == 'teacher') {
		if ($line['ver']>1) {
			$addassess = 'addassessment2.php';
		} else {
			$addassess = 'addassessment.php';
		}
		echo "<p><a href=\"course/$addassess?cid=" . Sanitize::courseId($cid) . "&id=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Settings")."</a> | ";
		echo "<a href=\"course/addquestions.php?cid=" . Sanitize::courseId($cid) . "&aid=" . Sanitize::encodeUrlParam($typeid) . "&from=lti\">"._("Questions")."</a></p>";
		if ($_SESSION['ltiitemtype']==-1) {
			echo '<p><a href="ltihome.php?chgplacement=true">'._('Change placement').'</a></p>';
		}
		echo '<p>&nbsp;</p><p class=small>'.sprintf(_('This assessment is housed in course ID %s'),Sanitize::courseId($cid)).'</p>';
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
