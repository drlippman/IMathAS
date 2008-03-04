<?php

function showcalendar() {

global $imasroot,$cid,$userid,$teacherid;

$now= time();
$today = $now;
if (isset($_GET['calpageshift'])) {
	$pageshift = $_GET['calpageshift'];
} else {
	$pageshift = 0;
}
$today = $today + $pageshift*28*24*60*60;

$dayofweek = date('w',$today);
$dayofmo = date('j',$today);
$curmo = date('M',$today);
$curyr = date('Y',$today);
$curmonum = date('n',$today);
$daysinmo = date('t',$today);
$lastmo = date('M',$today - ($dayofmo+2)*24*60*60);
$lastmonum = date('n',$today - ($dayofmo+2)*24*60*60);
$daysinlastmo = date('t',$today - ($dayofmo+2)*24*60*60);
$nextmo = date('M',$today + ($daysinmo-$dayofmo+2)*24*60*60);
$nextmonum = date('n',$today + ($daysinmo-$dayofmo+2)*24*60*60);

$hdrs = array();
$ids = array();

for ($i=0;$i<$dayofweek;$i++) {
	$curday = $dayofmo - $dayofweek + $i;
	if ($curday<1) {
		if ($i==0) {
			$hdrs[0][$i] = $lastmo . " " . ($daysinlastmo+$curday);
			$ids[0][$i] = $lastmonum.'-'.($daysinlastmo + $curday);
		} else {
			$hdrs[0][$i] = ($daysinlastmo + $curday);
			$ids[0][$i] = $lastmonum.'-'.($daysinlastmo + $curday);
		}
	} else {
		if ($i==0) {
			$hdrs[0][$i] = $curmo . " " . $curday;
			$ids[0][$i] = $curmonum.'-'.$curday;
		} else {
			$hdrs[0][$i] = $curday;
			$ids[0][$i] = $curmonum.'-'.$curday;
		}
	}
	$dates[$ids[0][$i]] = date('l F j, Y',$today - ($dayofweek - $i)*24*60*60);
}

for ($i=$dayofweek;$i<28;$i++) {
	$row = floor($i/7);
	$col = $i%7;
	$curday = $dayofmo -$dayofweek+ $i;
	if ($curday > $daysinmo) {
		if ($curday == $daysinmo+1) {
			$hdrs[$row][$col] = $nextmo." 1";
		} else {
			$hdrs[$row][$col] = $curday - $daysinmo;
		}
		$ids[$row][$col] = $nextmonum.'-'.($curday - $daysinmo);
	} else {
		$hdrs[$row][$col] = $curday;
		$ids[$row][$col] = $curmonum.'-'.$curday;
	}
	$dates[$ids[$row][$col]] = date('l F j, Y',$today + ($i-$dayofweek)*24*60*60);
}

?>


<?php
echo '<div class=center><a href="course.php?calpageshift='.($pageshift-1).'&cid='.$cid.'">&lt; &lt;</a> ';
if ($pageshift==0) {
	echo "Now ";
} else {
	echo '<a href="course.php?calpageshift=0&cid='.$cid.'">Now</a> ';
}
echo '<a href="course.php?calpageshift='.($pageshift+1).'&cid='.$cid.'">&gt; &gt;</a> ';
echo '</div> ';
echo "<table class=\"cal\" >";  //onmouseout=\"makenorm()\"

$lowertime = max($now,mktime(0,0,0,$curmonum,$dayofmo - $dayofweek,$curyr));
$uppertime = mktime(0,0,0,$curmonum,$dayofmo - $dayofweek + 28,$curyr);

$exceptions = array();
if (!isset($teacherid)) {
	$query = "SELECT assessmentid,startdate,enddate FROM imas_exceptions WHERE userid='$userid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$exceptions[$row[0]] = array($row[1],$row[2]);
	}
}

$assess = array();
$colors = array();
$k = 0;
$query = "SELECT id,name,startdate,enddate,reviewdate FROM imas_assessments WHERE avail=1 AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if (isset($exceptions[$row[0]])) {
		$row[2] = $exceptions[$row[0]][0];
		$row[3] = $exceptions[$row[0]][1];
	}
	if ($row[2]>$now || ($now>$row[3] && $row[4]==0) || ($row[4]>0 && $now>$row[4])) {
		continue;
	}
	if ($now<$row[3]) {
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[3]));
		$row[1] = str_replace('"','\"',$row[1]);
		$colors[$k] = makecolor2($row[2],$row[3],$now);
		$assess[$moday][$k] = "{type:\"A\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", color:\"".$colors[$k]."\"}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	} else { //in review
		list($moday,$time) = explode('~',date('n-j~g:i a',$row[4]));
		$row[1] = str_replace('"','\"',$row[1]);
		$assess[$moday][$k] = "{type:\"AR\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\"}";
	}
	$k++;
}
$query = "SELECT id,title,enddate,text,startdate FROM imas_inlinetext WHERE enddate>$lowertime AND enddate<$uppertime AND startdate<$now AND avail=1 AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if ($row[1]=='##hidden##') {
		$row[1] = strip_tags( $row[3]);
	}
	list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
	$row[1] = str_replace('"','\"',$row[1]);
	$colors[$k] = makecolor2($row[4],$row[2],$now);
	$assess[$moday][$k] = "{type:\"I\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", color:\"".$colors[$k]."\"}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	$k++;
}
$query = "SELECT id,title,enddate,text,startdate FROM imas_linkedtext WHERE enddate>$lowertime AND enddate<$uppertime AND startdate<$now AND avail=1 AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
	$row[1] = str_replace('"','\"',$row[1]);
	 if ((substr($row[3],0,4)=="http") && (strpos($row[3]," ")===false)) { //is a web link
		   $alink = trim($row[3]);
	   } else if (substr($row[3],0,5)=="file:") {
		   $filename = substr($row[3],5);
		   $alink = $imasroot . "/course/files/".$filename;
	   } else {
		   $alink = '';
	   }
	$colors[$k] = makecolor2($row[4],$row[2],$now);
	$assess[$moday][$k] = "{type:\"L\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", link:\"$alink\", color:\"".$colors[$k]."\"}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	$k++;
}
$query = "SELECT id,name,postby,replyby,startdate FROM imas_forums WHERE enddate>$lowertime AND ((postby>$now AND postby<$uppertime) OR (replyby>$now AND replyby<$uppertime)) AND startdate<$now AND avail>0 AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
	$row[1] = str_replace('"','\"',$row[1]);
	$colors[$k] = makecolor2($row[4],$row[2],$now);
	$assess[$moday][$k] = "{type:\"FP\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", color:\"".$colors[$k]."\"}";
	$k++;
	list($moday,$time) = explode('~',date('n-j~g:i a',$row[3]));
	$colors[$k] = makecolor2($row[4],$row[3],$now);
	$assess[$moday][$k] = "{type:\"FR\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", color:\"".$colors[$k]."\"}";
	$k++;	
}

$jsarr = '{';
foreach ($dates as $moday=>$val) {
	if ($jsarr!='{') {
		$jsarr .= ',';
	}
	if (isset($assess[$moday])) {
		$jsarr .= '"'.$moday.'":{date:"'.$dates[$moday].'",data:['.implode(',',$assess[$moday]).']}';
	} else {
		$jsarr .= '"'.$moday.'":{date:"'.$dates[$moday].'"}';
	}
}
$jsarr .= '}';
		
echo '<script type="text/javascript">';
echo "cid = $cid;";
echo "caleventsarr = $jsarr;";
echo '</script>';
echo "<thead><tr><th>Sunday</th> <th>Monday</th> <th>Tuesday</th> <th>Wednesday</th> <th>Thursday</th> <th>Friday</th> <th>Saturday</th></tr></thead>";
echo "<tbody>";
for ($i=0;$i<count($hdrs);$i++) {
	echo "<tr>";
	for ($j=0; $j<count($hdrs[$i]);$j++) {
		if ($i==0 && $j==$dayofweek && $pageshift==0) { //onmouseover="makebig(this)"
			echo '<td id="'.$ids[$i][$j].'" onclick="showcalcontents(this)" class="today"><div class="td"><span class=day>'.$hdrs[$i][$j]."</span><div class=center>";
		
		} else {
			echo '<td id="'.$ids[$i][$j].'" onclick="showcalcontents(this)" ><div class="td"><span class=day>'.$hdrs[$i][$j]."</span><div class=center>";
		}
		if (isset($assess[$ids[$i][$j]])) {
			foreach ($assess[$ids[$i][$j]] as $k=>$info) {
				//echo $assess[$ids[$i][$j]][$k];
				if (strpos($info,'type:"AR"')!==false) {
					echo "<span style=\"background-color:#99f;padding: 0px 3px 0px 3px;\">R</span> ";
				} else if (strpos($info,'type:"A"')!==false) {
					echo "<span style=\"background-color:".$colors[$k].";padding: 0px 3px 0px 3px;\">?</span> ";
				} else if (strpos($info,'type:"F')!==false) { 
					echo "<span style=\"background-color:".$colors[$k].";padding: 0px 3px 0px 3px;\">F</span> ";
				} else { //textitems
					echo "<span style=\"background-color:".$colors[$k].";padding: 0px 3px 0px 3px;\">!</span> ";
				}
			}
		}
		echo "</div></div></td>";
	}
	echo "</tr>";
}
echo "</tbody></table>";

echo "<div style=\"margin-top: 10px; padding:10px; border:1px solid #000;\">";
echo '<span class=right><a href="#" onclick="showcalcontents('.(1000*($today - $dayofweek*24*60*60)).'); return false;"/>Show all</a></span>';

echo "<div id=\"caleventslist\"></div></div>";
if ($pageshift==0) {
	echo "<script>showcalcontents(document.getElementById('{$ids[0][$dayofweek]}'));</script>";
}

}
?>
