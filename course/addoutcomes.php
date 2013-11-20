<?php
//(c) 2013 David Lippman.  Part of IMathAS
//Define course outcomes

require("../validate.php");
if (!isset($teacherid)) { echo "You are not validated to view this page"; exit;}

if (isset($_POST['order'])) {
	//store order and outcome groups as serialized array 
	//array(name=>name, items=>array(outcome ids))
	//get list of existing outcomes
	$curoutcomes = array();
	$query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$curoutcomes[$row[0]] = $row[1];
	}
	
	//read in from order
	$seenoutcomes = array();
	function additems($list) {
		 global $curoutcomes, $seenoutcomes, $cid;
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
		 //new outcome: new###, name in newo###
		 //ext outcome: ###, name in o###
		 foreach ($listarr as $it) {
			 if (strpos($it,'grp')!==false) { //is outcome group
				 $pos = strpos($it,':');
				 if ($pos===false) {
					 $block = array("outcomes"=>array());
					 $pts[0] = $it;
				 } else {
					 $pts[0] = substr($it,0,$pos);
					 $pts[1] = substr($it,$pos+1);
					 $subarr = additems($pts[1]);
					 $block = array("outcomes"=>$subarr);
				 }
				 if (substr($pts[0],0,3)=='new') {
				 	 $name = stripslashes($_POST['newg'.substr($pts[0],6)]);
				 } else {
				 	 $name = stripslashes($_POST['g'.substr($pts[0],3)]);
				 }
				 $block['name'] = $name;
				 $outarr[] = $block;
			 } else { //is an outcome
			 	 if (substr($it,0,3)=='new') {
			 	 	$ocnt = substr($it,3);
			 	 	$query = "INSERT INTO imas_outcomes (courseid, name) VALUES ";
			 	 	$query .= "('$cid','{$_POST['newo'.$ocnt]}')";
			 	 	$result = mysql_query($query) or die("Query failed : " . mysql_error());
			 	 	$newid = mysql_insert_id();
			 	 	$seenoutcomes[] = $newid;
			 	 	$outarr[] = $newid;
			 	 } else if (isset($curoutcomes[$it])) {
			 	 	if (stripslashes($_POST['o'.$it])!=$curoutcomes[$it]) {
						 $query = "UPDATE imas_outcomes SET name='{$_POST['o'.$it]}' WHERE id='$it' AND courseid='$cid'";
						 $result = mysql_query($query) or die("Query failed : " . mysql_error());
			 	 	}
			 	 	$outarr[] = $it;
			 	 	$seenoutcomes[] = $it;
			 	 }
			 }
			 
		 }
		 return $outarr;
	 }
	
	 //this call parses the item array, adds any new outcomes, and updates names of any existing ones
	 $itemarray = additems($_POST['order']);
	 
	 $outcomeorder = addslashes(serialize($itemarray));
	 $query = "UPDATE imas_courses SET outcomes='$outcomeorder' WHERE id='$cid'";
	 mysql_query($query) or die("Query failed : " . mysql_error());
	 
	
	//remove unused outcomes
	$unused = array_diff(array_keys($curoutcomes), $seenoutcomes);
	
	if (count($unused)>0) {
		//these aren't horribly efficient, but shouldn't be called that often, so oh well.
		
		$unusedlist = implode(',',$unused);
		$query = "DELETE FROM imas_outcomes WHERE id IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		//detach unused outcomes from questions/content items
		$query = "UPDATE imas_assessments SET defoutcome=0 WHERE courseid='$cid' AND defoutcome IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		/*$query = "UPDATE imas_linkedtext SET outcome=0 WHERE courseid='$cid' AND outcomes IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "UPDATE imas_forums SET outcome=0 WHERE courseid='$cid' AND outcomes IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "UPDATE imas_gbitems SET outcome=0 WHERE courseid='$cid' AND outcomes IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		*/
		$query = "UPDATE imas_questions SET category='' WHERE category IN ($unusedlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	echo 'OK';
}



//load existing outcomes
$query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$row = mysql_fetch_row($result);
if ($row[0]=='') {
	$outcomes = array();
} else {
	$outcomes = unserialize($row[0]);
}

$outcomeinfo = array();
$query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
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
			echo '<li id="'.$item.'"><span class=icon style="background-color:#0f0">O</span> ';
			echo '<input class="outcome" type="text" size="60" id="o'.$item.'" value="'.htmlentities($outcomeinfo[$item]).'" onkeyup="txtchg()"> ';
			echo '<a href="#" onclick="removeoutcome(this);return false">'._("Delete").'</a></li>';
		}
	}
}

if (isset($_POST['order'])) {
	//if postback, send new layout
	printoutcome($outcomes);
	exit;
}


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";

$placeinhead = '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;} ul.qview li {padding: 3px}</style>';
$placeinhead .=  "<script>var AHAHsaveurl = '$imasroot/course/addoutcomes.php?cid=$cid&save=save'; var j=jQuery.noConflict();</script>";
$placeinhead .= "<script src=\"$imasroot/javascript/mootools.js\"></script>";
$placeinhead .= "<script src=\"$imasroot/javascript/nested1.js?v=0122102\"></script>";
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
		j("#qviewtree").append(html);
		j("#new"+ocnt).focus();
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
		j("#qviewtree").append(html);
		j("#newgrp"+ocnt).focus();
		ocnt++;
		if (!sortIt.haschanged) {
			sortIt.haschanged = true;
			sortIt.fireEvent(\'onFirstChange\', null);
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}
	function removeoutcome(el) {
		if (confirm("'._("Are you sure you want to delete this outcome?").'")) {
			j(el).parent().remove();
			if (!sortIt.haschanged) {
				sortIt.haschanged = true;
				sortIt.fireEvent(\'onFirstChange\', null);
				window.onbeforeunload = function() {return unsavedmsg;}
			}
		}
	}
	function removeoutcomegrp(el) {
		if (confirm("'._("Are you sure you want to delete this outcome group?  This will not delete the included outcomes.").'")) {
			var curloc = j(el).parent();
			curloc.find("li").each(function() {
				curloc.before(j(this));
			});
			curloc.remove();
			if (!sortIt.haschanged) {
				sortIt.haschanged = true;
				sortIt.fireEvent(\'onFirstChange\', null);
				window.onbeforeunload = function() {return unsavedmsg;}
			}
		}
	}
	
	</script>';
require("../header.php");

echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; '._("Course Outcomes").'</div>';

echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>"._("Course Outcomes")."</h2></div>\n";

echo '<div class="cpmid"><a href="outcomemap.php?cid='.$cid.'">'._('View Outcomes Map').'</a> | <a href="outcomereport.php?cid='.$cid.'">'._('View Outcomes Report').'</a></div>';

echo '<div class="breadcrumb">'._('Use colored boxes to drag-and-drop order.').' <input type="button" id="recchg" disabled="disabled" value="', _('Record Changes'), '" onclick="submitChanges()"/><span id="submitnotice" style="color:red;"></span></div>';

echo '<ul id="qviewtree" class="qview">';
printoutcome($outcomes);
echo '</ul>';
echo '<input type="button" onclick="addoutcomegrp()" value="'._('Add Outcome Group').'"/> ';
echo '<input type="button" onclick="addoutcome()" value="'._('Add Outcome').'"/> ';
require("../footer.php");

?>
