<?php
//Create question libraries based on a course
//IMathAS (c) 2018 David Lippman for Lumen Learning

require("../init.php");
require("../includes/htmlutil.php");

if ($myrights<100) { exit; }
if (!isset($_GET['cid'])) {
	echo "No course identified";
	exit;
}

$stm = $DBH->prepare("SELECT itemorder,ownerid FROM imas_courses WHERE id=?");
$stm->execute(array($cid));
list($itemorder,$courseowner) = $stm->fetch(PDO::FETCH_NUM);
$itemorder = unserialize($itemorder);

if (isset($_POST['libs'])) {
	$blockbase = $itemorder;
	$blocktree = explode('-', $_POST['pullfrom']);
	for ($i=1;$i<count($blocktree);$i++) {
		$blockbase =& $blockbase[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
	$now = time();
	
	$DBH->beginTransaction();
	
	if ($_POST['aswho']==1) {
		$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=?");
		$stm->execute(array($courseowner));
		$thisowner = $courseowner;
		$thisgroup = $stm->fetchColumn(0);
	} else {
		$thisowner = $userid;
		$thisgroup = $groupid;
	}
	
	$librights = $_POST['librights'];
	$_POST['baselibname'] = trim($_POST['baselibname']);
	if ($_POST['baselibname']=='') {
		$_POST['baselibname'] = $coursename;
	}	
	echo '<pre>';
	$stm = $DBH->prepare("SELECT iq.id,iq.questionsetid FROM imas_questions AS iq JOIN imas_assessments AS ia ON iq.assessmentid=ia.id WHERE ia.courseid=?");
	$stm->execute(array($cid));
	$qmap = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$qmap[$row[0]] = $row[1];
	}

	$stm = $DBH->prepare("SELECT id,name,itemorder FROM imas_assessments WHERE courseid=?");
	$stm->execute(array($cid));
	$assessdata = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$row['itemorder'] = str_replace('~',',',$row['itemorder']);
		$row['itemorder'] = preg_replace('/\d+\|\d+,/','',$row['itemorder']);
		
		$qs = explode(',', $row['itemorder']);
		foreach ($qs as $k=>$v) {
			$qs[$k] = $qmap[$v]; //map imas_questions.id to imas_questionset.id
		}
		$row['itemorder'] = $qs;
		$assessdata[$row['id']] = $row;
	}
	
	$stm = $DBH->prepare("SELECT id,typeid FROM imas_items WHERE itemtype='Assessment' AND courseid=?");
	$stm->execute(array($cid));
	$itemdata = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$itemdata[$row['id']] = $row['typeid'];
	}
	
	function getuniqid() {
		$mt = explode(" ",microtime());
		return $mt[1].substr($mt[0],2,6);
	}
	function pareItemOrder($items) {
		global $itemdata;
		$hasAssess = false;
		$out = array();
		foreach ($items as $item) {
			if (is_array($item)) {
				list($sub, $subHasAssess) = pareItemOrder($item['items']);
				if ($subHasAssess) {
					$hasAssess = true;
					$item['items'] = $sub;
					$out[] = $item;
				}
			} else if (isset($itemdata[$item])) {
				$hasAssess = true;
				$out[] = $item;
			}
		}
		return array($out, $hasAssess);
	}
	list($assessblocks,$junk) = pareItemOrder($blockbase);

	function normalizelibs(&$items) {
		global $itemdata, $assessdata;
		$hasblock = false;
		$hasassess = false;
		
		foreach ($items as $item) { 
			if (is_array($item)) {
				$hasblock = true;
				normalizelibs($item['items']);
			} else {
				$hasassess = true;
			}
		}
		if ($hasblock && $hasassess) { //need to move assess into blocks
			foreach ($items as $k=>$item) { 
				if (!is_array($item)) {
					$newblock = array(
						'name'=>$assessdata[$itemdata[$item]]['name'],
						'items'=>array($item)
					);
					$items[$k] = $newblock;
				} 
			}
		}
	}
	if ($_POST['libtype'] == 'no') {
		normalizelibs($assessblocks);	
	}
	
	function createLibs($items, $curparent) {
		global $now, $thisowner, $thisgroup, $librights, $inslib, $inslibitem, $DBH, $nlibs, $nlibitems;
		global $assessdata, $itemdata;
		foreach ($items as $item) {
			if (is_array($item)) { //is a block
				$inslib->execute(array(getuniqid(), $now, $now, $item['name'], $thisowner, $librights, $curparent, $thisgroup));
				$libid = $DBH->lastInsertId();
				$nlibs++;
				createLibs($item['items'], $libid);
			} else {
				$aid = $itemdata[$item];
				
				if ($_POST['libtype']=='yes') {
					$inslib->execute(array(getuniqid(), $now, $now, $assessdata[$aid]['name'], $thisowner, $librights, $curparent, $thisgroup));
					$qlibid = $DBH->lastInsertId();
					$nlibs++;
				} else {
					$qlibid = $curparent;
				}
				foreach ($assessdata[$aid]['itemorder'] as $qsetid) {
					$inslibitem->execute(array($qlibid, $qsetid, $thisowner, $now));
					$nlibitems++;
				}
			}
		}
	}
	$inslib = $DBH->prepare("INSERT INTO imas_libraries (uniqueid, adddate, lastmoddate, name, ownerid, userights, parent, groupid) VALUES (?,?,?,?,?,?,?,?)");
	$inslibitem = $DBH->prepare("INSERT INTO imas_library_items (libid, qsetid, ownerid, lastmoddate) VALUES (?,?,?,?)");
	
	$inslib->execute(array(getuniqid(), $now, $now, $_POST['baselibname'], $thisowner, $librights, $_POST['libs'], $thisgroup));
	$libid = $DBH->lastInsertId();
	$nlibs = 1;
	$nlibitems = 0;
	createLibs($assessblocks, $libid);
	$DBH->commit();
	echo 'Done. Added '.$nlibs.' libraries, and '.$nlibitems.' library entries';
	
	exit;
}
function buildexistblocks($items,$parent,$pre='') {
	global $existblocks;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$existblocks[$parent.'-'.($k+1)] = $pre.$item['name'];
			if (count($item['items'])>0) {
				buildexistblocks($item['items'],$parent.'-'.($k+1),$pre.'&nbsp;&nbsp;');
			}
		}
	}
}

$existblocks = array();
buildexistblocks($itemorder,'0');

require("../header.php");
?>
<script>
var curlibs = '';
function libselect() {
	window.open('../course/libtree2.php?cid='+cid+'&libtree=popup&select=parent&selectrights=1&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
</script>
<h2>Course to Libraries</h2>

<?php

echo '<form method="post">';
echo '<p>Library in which to create a new library? ';
echo "<span id=\"libnames\"></span><input type=hidden name=\"libs\" id=\"libs\"  value=\"-1\">\n";
echo "<input type=button value=\"Select Library\" onClick=\"libselect()\"></p>";

echo '<p>Name for base new library: <input name="baselibname" value="'.Sanitize::encodeStringForDisplay($coursename).'" size=50 /></p>';

echo '<p>Which block contains the base level of your desired library structure?';
writeHtmlSelect ("pullfrom",array_keys($existblocks),array_values($existblocks),$selectedVal=null,$defaultLabel="Main Course Page",$defaultVal="0",$actions=null);
echo '</p>';

echo '<p>Do you want a library for each assessment? <select name="libtype">';
echo '<option value="yes">Yes, create a library for each assessment</option>';
echo '<option value="no">No, put all assessment questions in the block library</option>';
echo '</select>';
echo '</p>';
echo '<p>Assign as owner to libraries and library items: ';
echo '<select name="aswho"><option value="1" selected">the course owner</option>';
echo '<option value="0">the person running this</option></select></p>';

echo '<p>Rights for new libraries: <select name="librights">
		<option value="0">Private</option>
		<option value="1">Closed to group, private to others</option>
		<option value="2" selected>Open to group, private to others</option>
		<option value="4">Closed to all</option>
		<option value="5">Open to group, closed to others</option>
		<option value="8">Open to all</option>
		</select></p>';

echo '<input type="submit" value="Go">';
echo '</form>';

require("../footer.php");

