<?php
//Edit course sort order
//(c) 2018 IMathAS

/*
  TODO


*/

require("../init.php");

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
	function additems($list, $status=0) {
		global $allcourses;
		 $outarr = array();
		 $list = substr($list,1,-1);
		 $i = 0; $nd = 0; $last = 0;
		 $listarr = array();
		 while ($i<strlen($list)) {
			 if ($list[$i]=='[') {
				 $nd++;
			 } else if($list[$i]==']') {
				 $nd--;
			 } else if ($list[$i]==',' && $nd==0) {
				$listarr[] = substr($list,$last,$i-$last);
				$last = $i+1;
			 }
			 $i++;
		 }
		 $listarr[] = substr($list,$last);
		 //new group:  newgrp###, name in newg###
		 //ext group:  grp### name in g###
		 //course: c###
		 foreach ($listarr as $it) {
			 if (strpos($it,'grp')!==false) { //is course group
				 $pos = strpos($it,':');
				 if ($pos===false) {
					 $block = array("courses"=>array());
					 $pts[0] = $it;
				 } else {
					 $pts[0] = substr($it,0,$pos);
					 $pts[1] = substr($it,$pos+1);
					 if ($pts[0]=='maingrp') {
						 $status = 0;
					 } else if ($pts[0]=='hiddengrp') {
						 $status = 1;
					 }
					 $subarr = additems($pts[1], $status);
					 $block = array("courses"=>$subarr);
				 }
				 if (substr($pts[0],0,3)=='new') {
				 	 $name = $_POST['newg'.substr($pts[0],6)];
				 } else {
				 	 $name = $_POST['g'.substr($pts[0],3)];
				 }
				 $block['name'] = Sanitize::stripHtmlTags($name);
				 $outarr[] = $block;
			 } else { //is a course
			 	 $cid = substr($it,1);
			 	 $outarr[] = $cid;
			 	 $allcourses[$status][] = $cid;
			 }
		 }
		 return $outarr;
	 }

	 //this call parses the item array, adds any new outcomes, and updates names of any existing ones
	 $itemarray = additems($_POST['order']);
	 
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
$placeinhead .=  "<script>var AHAHsaveurl = '$imasroot/admin/modcourseorder.php?type=$type'; var j=jQuery.noConflict();</script>";
$placeinhead .= "<script src=\"$imasroot/javascript/mootools.js\"></script>";
$placeinhead .= "<script src=\"$imasroot/javascript/nested1.js?v=042318\"></script>";
$placeinhead .= '<script type="text/javascript">
 	var noblockcookie=true;
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
		var html = \'<li class="blockli" id="newgrp\'+ocnt+\'"><span class=icon style="background-color:#66f">G</span> \';
		html += \'<input class="outcome" type="text" size="40" id="newg\'+ocnt+\'" onkeyup="txtchg()"> \';
		html += \'<a href="#" onclick="removecoursegrp(this);return false\">'._("Delete").'</a></li>\';
		j("#maingrp > ul").prepend(html);
		j("#newgrp"+ocnt).focus();
		ocnt++;
		txtchg();
	}
	function removecoursegrp(el) {
		if (confirm("'._("Are you sure you want to delete this course group?  This will not delete the included courses.").'")) {
			var curloc = j(el).parent();
			curloc.find("li").each(function() {
				curloc.before(j(this));
			});
			curloc.remove();
			txtchg();
		}
	}
	var itemorderhash="h";
	</script>';
require("../header.php");

echo '<div class=breadcrumb>'.$breadcrumbbase._("Course Order").'</div>';

echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>";
echo _("Display Order").': ';
if ($type=='take') {
	echo _('Courses you\'re taking');
} else if ($type=='tutor') {
	echo _('Courses you\'re tutoring');
} else if ($type=='teach') {
	echo _('Courses you\'re teaching');
}
echo "</h2></div>\n";

echo '<div class="breadcrumb">'._('Use colored boxes to drag-and-drop order and move courses inside groups.').' <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/><span id="submitnotice" class=noticetext></span></div>';

echo '<input type="button" onclick="addcoursegrp()" value="'._('Add Course Group').'"/> ';

function listCourse($course) {
	$now = time();
	echo '<li id="c'.Sanitize::onlyInt($course['id']).'">';
	echo '<span class=icon style="background-color:#0f0">C</span> ';
	echo Sanitize::encodeStringForDisplay($course['name']);
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
			echo '<input class="outcome" type="text" size="40" id="g'.$cnt.'" value="'.Sanitize::encodeStringForDisplay($item['name']).'" onkeyup="txtchg()"> ';
			echo '<a href="#" onclick="removecoursegrp(this);return false">'._("Delete").'</a>';
			$cnt++;
			if (count($item['courses'])>0) {
				echo '<ul class="qview">';
				showCourseList($item['courses']);
				echo '</ul>';
			}
			echo '</li>';
		} else if (isset($courses[$item])) { //individual course
			listCourse($courses[$item]);
			$shownCourses[] = $item;
		}
	}
}

echo '<ul id="qviewtree" class="qview nochildren">';
echo '<li class="blockli" id="maingrp"><b>'._('Displayed Courses').'</b>';
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
	echo '<li class="blockli" id="hiddengrp"><b>'._('Hidden Courses').'</b>';
	echo  '<ul class="qview">';
	//display hidden courses
	foreach ($hiddencourses as $course) {
		listCourse($course);
	}
	echo  '</ul></li>';
}
echo '</ul>';
require("../footer.php");

?>
