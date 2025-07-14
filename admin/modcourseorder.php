<?php
//Edit course sort order
//(c) 2018 IMathAS

/*
  TODO


*/

require_once "../init.php";

$type = Sanitize::simpleString($_GET['type']);

$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=?");
$stm->execute(array($userid));
$jsondata = json_decode($stm->fetchColumn(0), true);
if ($jsondata===null) {
	$jsondata = array();
}
$courseListOrder = isset($jsondata['courseListOrder'])?$jsondata['courseListOrder']:array();

if ($type == 'take') {
	$table = "imas_students";
} else if ($type == 'tutor') {
	$table = "imas_tutors";
} else if ($type == 'teach') {
	$table = "imas_teachers";
} else {
	echo "Invalid type";
	exit;
}

if (isset($_POST['order'])) {
	//read in from order
	$allcourses = array(array(), array());
	function additems2($list, $status=0) {
		global $allcourses;
		 $outarr = array();

		 //new group:  newgrp###, name in newg###
		 //ext group:  grp### name in g###
		 //course: c###
		 foreach ($list as $it) {
			 $id = $it['id'];
			 if (strpos($id,'grp')!==false) { //is course group
				 if ($id === 'maingrp') {
					 $status = 0;
				 } else if ($id === 'hiddengrp') {
					 $status = 1;
				 }
				 if (count($it['children']) == 0) {
					 $block = array("courses"=>array());
				 } else {
					 $block = array("courses"=>additems2($it['children'], $status));
				 }
				 if (substr($id,0,3)=='new') {
				 	 $name = $_POST['newg'.substr($id,6)];
				 } else if (substr($id,0,3)=='grp') {
				 	 $name = $_POST['g'.substr($id,3)];
				 } else {
                    $name = $id;
                 }
				 $block['name'] = Sanitize::stripHtmlTags($name);
				 $outarr[] = $block;
			 } else { //is a course
			 	 $cid = substr($id,1);
			 	 $outarr[] = $cid;
			 	 $allcourses[$status][] = $cid;
			 }
		 }
		 return $outarr;
	 }

	 //this call parses the item array, adds any new outcomes, and updates names of any existing ones
	 $order = json_decode($_POST['order'], true);
	 if ($order === null) {
		 echo '0:'._('Error: Unable to process. Refresh the page to load changes and try again.');
	 	 exit;
	 }
	 $itemarray = additems2($order);

	 if (!isset($jsondata['courseListOrder'])) {
	 	 $jsondata['courseListOrder'] = array();
	 }
	 //first entry is maingrp courses
	 $jsondata['courseListOrder'][$type] = $itemarray[0]['courses'];

	 $stm = $DBH->prepare("UPDATE imas_users SET jsondata=? where id=?");
	 $stm->execute(array(json_encode($jsondata), $userid));

	 if (count($allcourses[0])>0) {
	 	 $ph = Sanitize::generateQueryPlaceholders($allcourses[0]);
	 	 $stm = $DBH->prepare("UPDATE $table SET hidefromcourselist=0 WHERE courseid IN ($ph) AND userid=?");
	 	 $stm->execute(array_merge($allcourses[0], array($userid)));
	 }
	 if (count($allcourses[1])>0) {
	 	 $ph = Sanitize::generateQueryPlaceholders($allcourses[1]);
	 	 $stm = $DBH->prepare("UPDATE $table SET hidefromcourselist=1 WHERE courseid IN ($ph) AND userid=?");
	 	 $stm->execute(array_merge($allcourses[1], array($userid)));
	 }
	 echo "2";
	 exit;
}

$query = "SELECT ic.id,ic.name,ic.startdate,ic.enddate,B.hidefromcourselist,";
$query .= "IF(UNIX_TIMESTAMP()<ic.startdate OR UNIX_TIMESTAMP()>ic.enddate,0,1) as active ";
$query .= "FROM imas_courses AS ic JOIN $table as B ON B.courseid=ic.id WHERE B.userid=? ";
if ($type == 'take') {
	$query .= 'AND (ic.available=0 OR ic.available=2) ';
} else {
	$query .= 'AND (ic.available=0 OR ic.available=1) ';
}
$query .= 'ORDER BY active DESC,ic.name';
$stm = $DBH->prepare($query);
$stm->execute(array($userid));
$courses = array();
$defaultcourseorder = array();
$hiddencourses = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	if ($row['hidefromcourselist']==1) {
		$hiddencourses[] = $row;
	} else {
		$courses[$row['id']] = $row;
		$defaultcourseorder[] = $row['id'];
	}
}

$placeinhead = '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;} ul.qview li {padding: 3px}</style>';
$placeinhead .=  "<script>var AHAHsaveurl = '$imasroot/admin/modcourseorder.php?type=$type';</script>";
$placeinhead .= "<script src=\"$staticroot/javascript/nestedjq.js?v=071425\"></script>";
$placeinhead .= '<script type="text/javascript">
 	var noblockcookie=true;
	var hidelinksonchange = false;
	var ocnt = 0;
	var unsavedmsg = "'._("You have unrecorded changes.  Are you sure you want to abandon your changes?").'";

	function txtchg() {
		if (!sortIt.haschanged) {
			sortIt.haschanged = true;
			sortIt.fireEvent(\'onFirstChange\', null);
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}

	function addcoursegrp() {
		var html = \'<li class="blockli" id="newgrp\'+ocnt+\'"><span class=icon style="background-color:#66f" tabindex=0>G</span> \';
		html += \'<span class=canedit id="newg\'+ocnt+\'"">Course Group</span> \';
		html += \'<a href="#" onclick="removecoursegrp(this);return false\">'._("Delete").'</a><ul class=qview></ul></li>\';
		$("#maingrp > ul").prepend(html);
		addsortmarkup("qviewtree");
		$("#qviewtree").attr("aria-activedescendant", "").find("li").attr("tabindex","-1");
		$("#newgrp"+ocnt).attr("tabindex","0").focus();
		ocnt++;
		txtchg();
	}
	function removecoursegrp(el) {
		if (confirm("'._("Are you sure you want to delete this course group?  This will not delete the included courses.").'")) {
			var curloc = $(el).closest("li");
			let newfocus;
			curloc.find("li").each(function() {
				newfocus = this;
				curloc.before($(this));
			});
			
			if (!newfocus) {
				if (curloc.next("li")) {
					newfocus = curloc.next("li")[0];
				} else if (curloc.prev("li")) {
					newfocus = curloc.prev("li")[0];
				}
			}
			curloc.remove();
			if (newfocus) { $(newfocus).attr("tabindex","0").focus();}
			txtchg();
		}
	}
	var itemorderhash="h";
	</script>';
require_once "../header.php";

echo '<div class=breadcrumb>'.$breadcrumbbase._("Course Order").'</div>';

echo "<div id=\"headercourse\" class=\"pagetitle\"><h1>";
echo _("Display Order").': ';
if ($type=='take') {
	echo _('Courses you\'re taking');
} else if ($type=='tutor') {
	echo _('Courses you\'re tutoring');
} else if ($type=='teach') {
	echo _('Courses you\'re teaching');
}
echo "</h1></div>\n";

echo '<div class="breadcrumb">'._('Use colored boxes to drag-and-drop order and move courses inside groups. Click a title to edit in place. Hover-over or click on an element to show links.').' <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges(\'json\',\'all\')"/><span id="submitnotice" class=noticetext></span></div>';

echo '<input type="button" onclick="addcoursegrp()" value="'._('Add Course Group').'"/> ';

echo '<div class="sr-only" tabindex=0 onfocus="this.className=\'\'">'._('Keyboard instructions: In the tree, use arrow keys to move within the tree. On an course group item, press Tab to edit the title and access links. To rearrange, press Space to select the item, then use the arrow keys to rearrange the item up or down, left to move out of a branch, right to move into a branch when positioned below it. Press Space again to deselect.').'</div>';

function listCourse($course) {
	$now = time();
	echo '<li id="c'.Sanitize::onlyInt($course['id']).'">';
	echo '<span class=icon style="background-color:#0f0">C</span> ';
	echo '<span id="cn'.Sanitize::onlyInt($course['id']).'">'.Sanitize::encodeStringForDisplay($course['name']).'</span>';
	if (isset($course['available']) && (($course['available']&1)==1)) {
		echo ' <em style="color:green;">', _('Unavailable'), '</em>';
	}
	if (isset($course['startdate']) && $now<$course['startdate']) {
		echo ' <em style="color:green;">';
		echo _('Starts ').tzdate('m/d/Y', $course['startdate']);
		echo '</em>';
	} else if (isset($course['enddate']) && $now>$course['enddate']) {
		echo ' <em style="color:green;">';
		echo _('Ended ').tzdate('m/d/Y', $course['enddate']);
		echo '</em>';
	}
	echo '</li>';
}
$cnt = 0;
$shownCourses = array();
function cleanCourseList(&$arr) {
	global $courses;
	foreach ($arr as $k=>$item) {
		if (is_array($item)) {
			cleanCourseList($arr[$k]['courses']);
		} else if (!isset($courses[$item])) {
			unset($arr[$k]);
		}
	}
}
function showCourseList($arr) {
	global $courses,$cnt,$shownCourses;
	foreach ($arr as $item) {
		if (is_array($item)) { //is course group
			echo '<li class="blockli" id="grp'.$cnt.'"><span class=icon style="background-color:#66f">G</span> ';
			echo '<span id="g'.$cnt.'" class="canedit">'.Sanitize::encodeStringForDisplay($item['name']).'</span> ';
			echo '<a class="links" href="#" onclick="removecoursegrp(this);return false">'._("Delete").'</a>';

			$cnt++;
			echo '<ul class="qview">';
			if (count($item['courses'])>0) {
				showCourseList($item['courses']);
			}
			echo '</ul>';
			echo '</li>';
		} else if (isset($courses[$item])) { //individual course
			listCourse($courses[$item]);
			$shownCourses[] = $item;
		}
	}
}

echo '<ul id="qviewtree" class="qview nochildren">';
echo '<li class="blockli locked" id="maingrp"><span class="icon" style="display:none;"></span><b>'._('Displayed Courses').'</b>';
echo  '<ul class="qview">';
//display main courses
if (isset($courseListOrder[$type])) {
	cleanCourseList($courseListOrder[$type]);
	showCourseList($courseListOrder[$type]);
	$unlisted = array_diff($defaultcourseorder, $shownCourses);
	foreach ($unlisted as $course) {
		listCourse($courses[$course]);
	}
} else {
	foreach ($defaultcourseorder as $course) {
		listCourse($courses[$course]);
	}
}
echo  '</ul></li>';
if (count($hiddencourses)>0) {
	echo '<li class="blockli locked" id="hiddengrp"><span class="icon" style="display:none;"></span><b>'._('Hidden Courses').'</b>';
	echo  '<ul class="qview">';
	//display hidden courses
	foreach ($hiddencourses as $course) {
		listCourse($course);
	}
	echo  '</ul></li>';
}
echo '</ul>';
require_once "../footer.php";

?>
