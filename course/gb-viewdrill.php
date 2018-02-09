<?php
//IMathAS:  Drill Assess player (updated quickdrill)
//(c) 2011 David Lippman
require("../init.php");


function stddev($array){
  //Don Knuth is the $deity of algorithms
  //http://en.wikipedia.org/wiki/Algorithms_for_calculating_variance#III._On-line_algorithm
  $n = 0;
  $mean = 0;
  $M2 = 0;
  foreach($array as $x){
      $n++;
      $delta = $x - $mean;
      $mean = $mean + $delta/$n;
      $M2 = $M2 + $delta*($x - $mean);
  }
  $variance = $M2/($n - 1);
  return sqrt($variance);
}

$cid = intval($_GET['cid']);
$daid = intval($_GET['daid']);

if (!isset($teacherid)) {
	echo 'You are not authorized to view this page';
	exit;
}

//DB $query = "SELECT * FROM imas_drillassess WHERE id='$daid' AND courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->prepare("SELECT * FROM imas_drillassess WHERE id=:id AND courseid=:courseid");
$stm->execute(array(':id'=>$daid, ':courseid'=>$cid));
if ($stm->rowCount()==0) {
	echo 'Invalid drill id.';
	exit;
}
//DB $dadata = mysql_fetch_array($result, MYSQL_ASSOC);
$dadata = $stm->fetch(PDO::FETCH_ASSOC);
$n = $dadata['n'];
$showtype = $dadata['showtype'];
$scoretype = $dadata['scoretype'];
if ($scoretype{0}=='t') {
	$mode = 'cntdown';
	$torecord = 'cc';   //count  correct
} else {
	$mode = 'cntup';
	$stopattype = $scoretype{1};  //a: attempted, c: correct, s: streak
	$torecord = $scoretype{2}; //t: time, c: total count
}
$showtostu = $dadata['showtostu'];
if ($dadata['itemids']=='') {
	$itemids = array();
} else {
	$itemids = explode(',',$dadata['itemids']);
}
if ($dadata['itemdescr']=='') {
	$itemdescr = array();
} else {
	$itemdescr = explode(',',$dadata['itemdescr']);
}
$classbests = explode(',',$dadata['classbests']);

$studata = array();
//DB $query = "SELECT iu.LastName,iu.FirstName,ids.scorerec FROM imas_drillassess_sessions AS ids ";
//DB $query .= "JOIN imas_users AS iu ON iu.id=ids.userid WHERE ids.drillassessid=$daid ORDER BY iu.LastName, iu.FirstName";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$query = "SELECT iu.LastName,iu.FirstName,ids.scorerec FROM imas_drillassess_sessions AS ids ";
$query .= "JOIN imas_users AS iu ON iu.id=ids.userid WHERE ids.drillassessid=:drillassessid ORDER BY iu.LastName, iu.FirstName";
$stm = $DBH->prepare($query);
$stm->execute(array(':drillassessid'=>$daid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$scorerec = unserialize($row[2]);
	$rowdata = array($row[0].', '.$row[1]);
	foreach ($itemids as $qn=>$v) {
		if (isset($scorerec[$qn])) {
			if ($torecord=='cc') {
				$score =  dispscore(max($scorerec[$qn]));
			} else {
				$score =  dispscore(min($scorerec[$qn]));
			}

			if (isset($_GET['details'])) {
				$score .= ' ; '.count($scorerec[$qn]).' ; ';
			} else {
				$score .= '('.count($scorerec[$qn]).')';
				$score .= ' -br- '; //replaced later
			}
			$score .= dispscore($scorerec[$qn][count($scorerec[$qn])-1]);
			if (isset($_GET['details'])) {
				$score .= ' ; ' . dispscore(round(array_sum($scorerec[$qn])/count($scorerec[$qn]), 1));
				if (count($scorerec[$qn])>1) {
					$score .= ' ; ' . round(stddev($scorerec[$qn]),1);
				} else {
					$score .= ' ; 0';
				}
			}
		} else {
			$score = 'N/A';
		}
		$rowdata[] = $score;
	}
	$studata[] = $rowdata;
}
$placeinhead = '<script type="text/javascript">function highlightrow(el) { el.setAttribute("lastclass",el.className); el.className = "highlight";}';
$placeinhead .= 'function unhighlightrow(el) { el.className = el.getAttribute("lastclass");}</script>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js?v=012811\"></script>\n";

require("../header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=". Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Drill Assessment Results</div>";
echo "<h2>Drill Assessment Results</h2>";

echo '<table id="myTable" class="gb">';
echo '<thead><tr><th>Best (# tries)<br/>Last</th>';
$sarr = "'S'";
foreach ($itemdescr as $qn=>$v) {
	echo '<th>' . Sanitize::encodeStringForDisplay($v) . '</th>';
	$sarr .= ",'N'";
}
echo '</tr></thead><tbody>';
foreach ($studata as $i=>$sturow) {
	if ($i%2==0) {
		echo '<tr class="even" onMouseOver="highlightrow(this)" onMouseOut="unhighlightrow(this)">';
	} else {
		echo '<tr class="odd" onMouseOver="highlightrow(this)" onMouseOut="unhighlightrow(this)">';
	}
	foreach ($sturow as $stuval) {
		echo '<td>'.str_replace(' -br- ','<br/>',Sanitize::encodeStringForDisplay($stuval)).'</td>';
	}
	echo '</tr>';
}
echo '</tbody></table>';

echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";

require("../footer.php");

function dispscore($sc) {
	global $torecord;
	if ($torecord=='t') {
		return formattime($sc);
	} else if ($torecord=='cc') {
		return $sc . ' correct';
	} else {
		return $sc . ' attempts';
	}
}


function formattime($cur) {
	if ($cur > 3600) {
		$hours = floor($cur/3600);
		$cur = $cur - 3600*$hours;
	} else { $hours = 0;}
	if ($cur > 60) {
		$minutes = floor($cur/60);
		if ($minutes<10) { $minutes = '0'.$minutes;}
		$cur = $cur - 60*$minutes;
	} else {$minutes='00';}
	$seconds = $cur;
	if ($seconds<10) { $seconds = '0'.$seconds;}
	return "$hours:$minutes:$seconds";
}
?>
