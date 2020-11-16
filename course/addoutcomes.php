<?php
//(c) 2013 David Lippman.  Part of IMathAS
//Define course outcomes

require("../init.php");
if (!isset($teacherid)) { echo "You are not validated to view this page"; exit;}

if (isset($_POST['order'])) {
	//store order and outcome groups as serialized array
	//array(name=>name, items=>array(outcome ids))
	//get list of existing outcomes
	$curoutcomes = array();
	$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$curoutcomes[$row[0]] = $row[1];
	}

	//read in from order
	$seenoutcomes = array();

	function additems2($list) {
 		 global $DBH,$curoutcomes, $seenoutcomes, $cid;
 		 $outarr = array();
 		 //new group:  newgrp###, name in newg###
 		 //ext group:  grp### name in g###
 		 //new outcome: new###, name in newo###
 		 //ext outcome: ###, name in o###
 		 foreach ($list as $it) {
			 $id = $it['id'];
 			 if (strpos($id,'grp')!==false) { //is outcome group
 				 if (count($it['children']) == 0) {
 					 $block = array("outcomes"=>array());
 				 } else {
 					 $block = array("outcomes"=>additems2($it['children']));
 				 }
 				 if (substr($id,0,3)=='new') {
 				 	 $name = $_POST['newg'.substr($id,6)];
 				 } else {
 				 	 $name = $_POST['g'.substr($id,3)];
 				 }
 				 $block['name'] = $name;
 				 $outarr[] = $block;
 			 } else { //is an outcome
 			 	 if (substr($id,0,3)=='new') {
 			 	 	$ocnt = substr($id,3);
 			 	 	$stm = $DBH->prepare("INSERT INTO imas_outcomes (courseid, name) VALUES (:cid,:name)");
 			 	 	$stm->execute(array(':cid'=>$cid, ':name'=>$_POST['newo'.$ocnt]));
 			 	 	$newid = $DBH->lastInsertId();
 			 	 	$seenoutcomes[] = $newid;
 			 	 	$outarr[] = $newid;
 			 	 } else if (isset($curoutcomes[$id])) {
 			 	 	if ($_POST['o'.$id]!=$curoutcomes[$id]) {
 						 $stm = $DBH->prepare("UPDATE imas_outcomes SET name=:name WHERE id=:id AND courseid=:courseid");
 						 $stm->execute(array(':name'=>$_POST['o'.$id], ':id'=>$id, ':courseid'=>$cid));
 			 	 	}
 			 	 	$outarr[] = $id;
 			 	 	$seenoutcomes[] = $id;
 			 	 }
 			 }

 		 }
 		 return $outarr;
 	 }

	 //this call parses the item array, adds any new outcomes, and updates names of any existing ones
	 //$itemarray = additems($_POST['order']);
	 $order = json_decode($_POST['order'], true);
	 if ($order === null) {
		 echo '0:'._('Error: Unable to process. Refresh the page to load changes and try again.');
	 	 exit;
	 }
	 $itemarray = additems2($order);
	 $outcomeorder = serialize($itemarray);
	 $stm = $DBH->prepare("UPDATE imas_courses SET outcomes=:outcomes WHERE id=:id");
	 $stm->execute(array(':outcomes'=>$outcomeorder, ':id'=>$cid));


	//remove unused outcomes
	$unused = array_diff(array_keys($curoutcomes), $seenoutcomes);

	if (count($unused)>0) {
		//these aren't horribly efficient, but shouldn't be called that often, so oh well.

		$unusedlist = implode(',',array_map('intval',$unused));
		$DBH->query("DELETE FROM imas_outcomes WHERE id IN ($unusedlist)");

		//detach unused outcomes from questions/content items
		$stm = $DBH->prepare("UPDATE imas_assessments SET defoutcome=0 WHERE courseid=:courseid AND defoutcome IN ($unusedlist)");
		$stm->execute(array(':courseid'=>$cid));

		/*$query = "UPDATE imas_linkedtext SET outcome=0 WHERE courseid='$cid' AND outcomes IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());

		$query = "UPDATE imas_forums SET outcome=0 WHERE courseid='$cid' AND outcomes IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());

		$query = "UPDATE imas_gbitems SET outcome=0 WHERE courseid='$cid' AND outcomes IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		*/

		//$DBH->query("UPDATE imas_questions SET category='' WHERE category IN ($unusedlist)");
		$query = "UPDATE imas_questions AS iq INNER JOIN imas_assessments AS ia ON iq.assessmentid=ia.id ";
		$query .= "SET iq.category='' WHERE ia.courseid=:courseid AND iq.category IN ($unusedlist)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid));
	}
	echo '1,h:';
}



//load existing outcomes
$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$row = $stm->fetch(PDO::FETCH_NUM);
if ($row[0]=='') {
	$outcomes = array();
} else {
	$outcomes = unserialize($row[0]);
	if (!is_array($outcomes)) {
		$outcomes = array();
	}
}

$outcomeinfo = array();
$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$outcomeinfo[$row[0]] = $row[1];
}

$cnt = 0;
function printoutcome($arr) {
	global $outcomeinfo,$cnt;
	foreach ($arr as $item) {
		if (is_array($item)) { //is outcome group
			echo '<li class="blockli" id="grp'.$cnt.'"><span class=icon style="background-color:#66f">G</span> ';
			echo '<input class="outcome" type="text" size="60" id="g'.$cnt.'" value="'.htmlentities($item['name']).'" onkeyup="txtchg()"> ';
			echo '<a href="#" onclick="removeoutcomegrp(this);return false">'._("Delete").'</a>';
			$cnt++;
			if (count($item['outcomes'])>0) {
				echo '<ul class="qview">';
				printoutcome($item['outcomes']);
				echo '</ul>';
			}
			echo '</li>';
		} else { //individual outcome
			echo '<li id="' . Sanitize::encodeStringForDisplay($item) . '"><span class=icon style="background-color:#0f0">O</span> ';
			echo '<input class="outcome" type="text" size="60" id="o' . Sanitize::encodeStringForDisplay($item) . '" value="' . Sanitize::encodeStringForDisplay($outcomeinfo[$item]).'" onkeyup="txtchg()"> ';
			echo '<a href="#" onclick="removeoutcome(this);return false">'._("Delete").'</a></li>';
		}
	}
}

if (isset($_POST['order'])) {
	//if postback, send new layout
	printoutcome($outcomes);
	exit;
}


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a>\n";

$placeinhead = '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;} ul.qview li {padding: 3px}</style>';
$placeinhead .=  "<script>var AHAHsaveurl = '$imasroot/course/addoutcomes.php?cid=$cid&save=save'; </script>";
$placeinhead .= "<script src=\"$staticroot/javascript/nestedjq.js?v=050719\"></script>";
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
	function addoutcome() {
		var html = \'<li id="new\'+ocnt+\'"><span class=icon style="background-color:#0f0">O</span> \';
		html += \'<input class="outcome" type="text" size="60" id="newo\'+ocnt+\'" onkeyup="txtchg()"> \';
		html += \'<a href="#" onclick="removeoutcome(this);return false\">'._("Delete").'</a></li>\';
		$("#qviewtree").append(html);
		$("#new"+ocnt).focus();
		ocnt++;
		if (!sortIt.haschanged) {
			sortIt.haschanged = true;
			sortIt.fireEvent(\'onFirstChange\', null);
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}
	function addoutcomegrp() {
		var html = \'<li class="blockli" id="newgrp\'+ocnt+\'"><span class=icon style="background-color:#66f">G</span> \';
		html += \'<input class="outcome" type="text" size="60" id="newg\'+ocnt+\'" onkeyup="txtchg()"> \';
		html += \'<a href="#" onclick="removeoutcomegrp(this);return false\">'._("Delete").'</a></li>\';
		$("#qviewtree").append(html);
		$("#newgrp"+ocnt).focus();
		ocnt++;
		if (!sortIt.haschanged) {
			sortIt.haschanged = true;
			sortIt.fireEvent(\'onFirstChange\', null);
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}
	function removeoutcome(el) {
		if (confirm("'._("Are you sure you want to delete this outcome?").'")) {
			$(el).parent().remove();
			if (!sortIt.haschanged) {
				sortIt.haschanged = true;
				sortIt.fireEvent(\'onFirstChange\', null);
				window.onbeforeunload = function() {return unsavedmsg;}
			}
		}
	}
	function removeoutcomegrp(el) {
		if (confirm("'._("Are you sure you want to delete this outcome group?  This will not delete the included outcomes.").'")) {
			var curloc = $(el).parent();
			curloc.find("li").each(function() {
				curloc.before($(this));
			});
			curloc.remove();
			if (!sortIt.haschanged) {
				sortIt.haschanged = true;
				sortIt.fireEvent(\'onFirstChange\', null);
				window.onbeforeunload = function() {return unsavedmsg;}
			}
		}
	}
	var itemorderhash="h";
	</script>';
require("../header.php");

echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; '._("Course Outcomes").'</div>';

echo "<div id=\"headercourse\" class=\"pagetitle\"><h1>"._("Course Outcomes")."</h1></div>\n";

echo '<div class="cpmid"><a href="outcomemap.php?cid='.$cid.'">'._('View Outcomes Map').'</a> | <a href="outcomereport.php?cid='.$cid.'">'._('View Outcomes Report').'</a></div>';

echo '<div class="breadcrumb">'._('Use colored boxes to drag-and-drop order and move outcomes inside groups.').' <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges(\'json\')"/><span id="submitnotice" class=noticetext></span></div>';

echo '<ul id="qviewtree" class="qview">';
printoutcome($outcomes);
echo '</ul>';
echo '<input type="button" onclick="addoutcomegrp()" value="'._('Add Outcome Group').'"/> ';
echo '<input type="button" onclick="addoutcome()" value="'._('Add Outcome').'"/> ';
require("../footer.php");

?>
