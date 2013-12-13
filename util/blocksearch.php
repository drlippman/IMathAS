<?php
require("../validate.php");
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
require("../header.php");
echo '<form method="post"><p>Search: <input type="text" name="search" size="40" value="'.htmlentities(stripslashes($_POST['search'])).'"> <input type="submit" value="Search"/></p>';
if (isset($_POST['search'])) {
	echo '<p>';
	$srch = $_POST['search'];
	$query = "SELECT id,itemorder,name FROM imas_courses WHERE itemorder LIKE '%$srch%' LIMIT 40";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$items = unserialize($row[1]);	
		$det = getstr($items, $srch, '0');
		if (count($det)>0) {
			echo '<a target="_blank" href="'.$imasroot.'/course/course.php?cid='.$row[0].'&folder='.$det[0].'">'.$det[1].'</a> in'.$row[2].'<br/>';
		}
	}
	echo '</p>';
}
?>
