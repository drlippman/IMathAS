<?php

//FIX outcomemap on more than one outcome in an assessment

require("../validate.php");

require("outcometable.php");
$canviewall = true;
$catfilter = -1;
$secfilter = -1;
$t = outcometable();

//load outcomes
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

//the overview report
$type = 1; //0 past, 1 attempted

$outc = array();
function flattenout($arr) {
	global $outc;
	foreach ($arr as $oi) {
		if (is_array($oi)) {
			flattenout($oi['outcomes']);
		} else {
			$outc[] = $oi;
		}
	}
}
flattenout($outcomes);

require("../header.php");

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a> &gt; ";
$curBreadcrumb .= "<a href=\"addoutcomes.php?cid=$cid\">"._("Course Outcomes")."</a>\n";

echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; '._("Outcomes Report").'</div>';

echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>"._("Outcomes Report")."</h2></div>\n";

$report = 'overview';

if ($report=='overview') {

	echo '<table class="gb"><thead><tr><th>'._('Name').'</th>';
	foreach ($outc as $oc) {
		echo '<th>'.$outcomeinfo[$oc].$oc.'</th>';
	}
	echo '</tr></thead><tbody>';
	
	$ot = outcometable();
	
	for ($i=1;$i<count($ot);$i++) {
		echo '<tr class="'.($i%2==0?'even':'odd').'">';
		echo '<td>'.$ot[$i][0][0].'</td>';
		foreach ($outc as $oc) {
			echo '<td>'.round($ot[$i][3][$type][$oc]*100,1).'%</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table>';
	
	echo '<p>'._('Note:  The outcome performance in each gradebook category is weighted based on gradebook weights to produce these overview scores').'</p>';
} else if ($report=='oneoutcome') {
	$outcome = 14;
	$ot = outcometable();
	echo '<h3>'._('Report on outcome:').$outcomeinfo[$outcome].'<h3>';
	echo '<table class="gb"><thead><tr><th>'._('Name').'</th>';
	echo '<th>'._('Total').'</th>';
	$catstolist = array();
	foreach ($ot[0][2] as $i=>$catinf) {
		if (isset($catinf[1][$outcome])) {
			echo '<th>'.$catinf[0].'</th>';
			$catstolist[] = $i;
		}
	}
	$itemstolist = array();
	foreach($ot[0][1] as $i=>$iteminf) {
		if (isset($iteminf[6][$outcome])) {
			echo '<th>'.$iteminf[0].'</th>';
			$itemstolist[] = $i;
		}
	}
	
	
}

require("../footer.php");
?>
