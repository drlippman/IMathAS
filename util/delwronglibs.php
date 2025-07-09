<?php

require_once "../init.php";

if ($myrights<100) {
	exit;
}
$placeinhead = '<script type="text/javascript">
function previewq(qn) {
  previewpop = window.open(imasroot+"/course/testquestion.php?fixedseeds=1&cid="+cid+"&qsetid="+qn,"Testing","width="+(.4*screen.width)+",height="+(.8*screen.height)+",scrollbars=1,resizable=1,status=1,top=20,left="+(.6*screen.width-20));
  previewpop.focus();
}
function selgrp(n) {
	var state = $(".LG"+n+":first-child").prop("checked");
	$(".LG"+n).prop("checked",!state)
}
</script>';
$pagetitle = _('Remove Questions Marked with Wrong Library');
require_once "../header.php";
echo '<h1>Remove Questions Marked with Wrong Library</h1>';

if (isset($_POST['record'])) {
	$now = time();
	$todel = array_map('Sanitize::onlyInt', $_POST['todel']);
	$ph = Sanitize::generateQueryPlaceholders($todel);
	$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1 WHERE id IN ($ph)");
	$stm->execute($todel);
	
	$unwrong = array_map('Sanitize::onlyInt', $_POST['unwrong']);
	$ph = Sanitize::generateQueryPlaceholders($unwrong);
	$stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=0 WHERE id IN ($ph)");
	$stm->execute($unwrong);
	
	//now, resolve any unassigned issues
	//first, try to undelete the unassigned library item for any question with no undeleted library items
	$query = "UPDATE imas_library_items AS ili, (SELECT qsetid FROM imas_library_items GROUP BY qsetid HAVING min(deleted)=1) AS tofix ";
	$query .= "SET ili.deleted=0 WHERE ili.qsetid=tofix.qsetid AND ili.libid=0";
	$stm = $DBH->query($query);
	
	//if any still have no undeleted library items, then they must not have an unassigned entry to undelete, so add it
	$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid,junkflag,deleted,lastmoddate) ";
	$query .= "(SELECT 0,ili.qsetid,iq.ownerid,0,0,iq.lastmoddate FROM imas_library_items AS ili JOIN imas_questionset AS iq ON iq.id=ili.qsetid GROUP BY ili.qsetid HAVING min(ili.deleted)=1)";
	$stm = $DBH->query($query);
	
	//make unassigned deleted if there's also an undeleted other library
	$query = "UPDATE imas_library_items AS A JOIN imas_library_items AS B ON A.qsetid=B.qsetid AND A.deleted=0 AND B.deleted=0 ";
	$query .= "SET A.deleted=1 WHERE A.libid=0 AND B.libid>0";
	$stm = $DBH->query($query);
	echo "Done";
} else {

	echo '<p>Leave the checkbox checked to remove the question from the library</p>';
	echo '<form method="post">';
	
	$stm = $DBH->query("SELECT id,parent,name FROM imas_libraries WHERE userights>3");
	$libdata = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$libdata[$row['id']] = $row;
	}
	
	$query = "SELECT iq.id,iq.description,ili.id AS liid,ili.libid FROM imas_questionset AS iq JOIN imas_library_items AS ili ";
	$query .= "ON iq.id=ili.qsetid AND ili.deleted=0 JOIN imas_libraries AS il ON ili.libid=il.id AND il.deleted=0 AND il.userights>3 ";
	$query .= "WHERE ili.junkflag=1 ORDER BY ili.libid";
	$stm = $DBH->query($query);
	$lastlib = -1;
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($lastlib!=$row['libid']) {
			if ($lastlib!=-1) { echo '</ul>';}
			$lastlib = $row['libid'];	
			$libname = $libdata[$lastlib]['name'];
			$parent = $libdata[$lastlib]['parent'];
			while ($parent != 0) {
				$libname = $libdata[$parent]['name'] . ' &gt; '.$libname;
				$parent = $libdata[$parent]['parent'];
			}
			echo '<p><b>'.$libname.'</b> ';
			echo '<button type="button" onclick="selgrp('.Sanitize::onlyInt($lastlib).')">Remove all</button>';
			echo '</p><ul>';
		}
		echo '<li>';
		echo '<input class="LG'.Sanitize::onlyInt($lastlib).'" type="checkbox" name="todel[]" value="'.Sanitize::onlyInt($row['liid']).'" />';
		echo '<a href="#" onclick="previewq('.$row['id'].');return false;">';
		echo 'Question '.$row['id'].'</a>: '.Sanitize::encodeStringForDisplay($row['description']);
		echo '. <input type="checkbox" name="unwrong[]" value="'.Sanitize::onlyInt($row['liid']).'"/> Un-mark as wrong lib';
		echo '</li>';
	}
	echo '</ul>';
	echo '<input type="submit" name="record" value="Remove Library Assignments" />';
	echo '</form>';
}

require_once "../footer.php";
