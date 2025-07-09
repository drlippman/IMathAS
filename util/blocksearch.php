<?php
require_once "../init.php";
if ($myrights<100) {exit;}

function getstr($items,$str,$parent) {
	foreach ($items as $k=>$it) {
		if (is_array($it)) {
			if (stripos($it['name'],$str)!==false) {
				return array($parent.'-'.($k+1), $it['name']);
			} else {
				$val = getstr($it['items'], $str, $parent.'-'.($k+1));
				if (count($val)>0) {
					return $val;
				}
			}
		}
	}
	return array();
}
$pagetitle = _('Block Search');
require_once "../header.php";
echo '<form method="post"><p>Search: <input type="text" name="search" size="40" value="'.Sanitize::encodeStringForDisplay($_POST['search']).'"> <input type="submit" value="Search"/></p>';
echo '</form>';
if (isset($_POST['search'])) {
	echo '<p>';
	$srch = $_POST['search'];
	$stm = $DBH->prepare("SELECT id,itemorder,name FROM imas_courses WHERE itemorder LIKE :srch LIMIT 40");
	$stm->execute(array(':srch'=>"%:srch%"));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$items = unserialize($row[1]);
		$det = getstr($items, $srch, '0');
		if (count($det)>0) {
			echo '<a target="_blank" href="'.$imasroot.'/course/course.php?cid='.Sanitize::courseId($row[0]).'&folder='.Sanitize::encodeUrlParam($det[0]).'">'.Sanitize::encodeStringForDisplay($det[1]).'</a> in'.Sanitize::encodeStringForDisplay($row[2]).'<br/>';
		}
	}
	echo '</p>';
}
require_once "../footer.php";
?>
