<?php
	require("../validate.php");
	
	$cid = $_GET['cid'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<style type="text/css">
table.cal {
	border-collapse: collapse;
	width: 100%;
}
table.cal thead th {
	text-align: center;
	background-color: #ddf;
	border: 1px solid #000;
}
table.cal td {
	border: 1px solid #000;
	width: 14%;
	height: 2.5em;
}
.day {
	font-size: 80%;
	background-color: #ddf;
}
.today {
	background-color: #fdd;
}
.contents {
	text-align: center;
}
div.td {
	width: 100%;
	height: 100%;
	overflow: hidden;
}



</style>
</head>
<body>
<?php

$today = time();
if (isset($_GET['pageshift'])) {
	$pageshift = $_GET['pageshift'];
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
			$hdrs[0][$i] = $lastmo . " " . $daysinlastmo;
			$ids[0][$i] = $lastmonum.'-'.$daysinlastmo;
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
}
?>
<script> 
function showcontents(el) {
	html = '';
	if (caleventsarr[el.id]!=null) {
		for (var i=0; i<caleventsarr[el.id].length; i++) {
			html += '<span class=icon style="background-color: #f66">?</span> <a href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[el.id][i].id+'">';
			html += caleventsarr[el.id][i].name + '</a>';
			html += ' Due '+caleventsarr[el.id][i].time;
			html += '<br/>';
		}
	}
	document.getElementById('step').innerHTML = html;	
	var alltd = document.getElementsByTagName("td");
	for (var i=0;i<alltd.length;i++) {
		alltd[i].style.backgroundColor = '#fff';
	}
	el.style.backgroundColor = '#fdd';
}
</script>

<?php
echo '<p><a href="calendardisp.php?pageshift='.($pageshift-1).'">&lt; &lt;</a> ';
echo '<a href="calendardisp.php?pageshift=0">Now</a> ';
echo '<a href="calendardisp.php?pageshift='.($pageshift+1).'">&gt; &gt;</a> </p>';
echo "<table class=\"cal\" >";  //onmouseout=\"makenorm()\"

$lowertime = mktime(0,0,0,$curmonum,$dayofmo - $dayofweek,$curyr);
$uppertime = mktime(0,0,0,$curmonum,$dayofmo - $dayofweek + 28,$curyr);

//check for startdate
$query = "SELECT id,name,enddate FROM imas_assessments WHERE enddate>$lowertime AND enddate<$uppertime AND avail>0 AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	list($moday,$time) = explode('~',date('n-j~g:i a',$row[2]));
	$row[1] = str_replace('"','\"',$row[1]);
	$assess[$moday][] = "{time:\"$time\", id:\"$row[0]\", name:\"$row[1]\"}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
}


$jsarr = '{';
foreach ($assess as $moday=>$val) {
	if ($jsarr!='{') {
		$jsarr .= ',';
	}
	$jsarr .= '"'.$moday.'":['.implode(',',$assess[$moday]).']';
}
$jsarr .= '}';
		
echo '<script>';
echo "var cid = $cid;";
echo "var caleventsarr = $jsarr;";
echo '</script>';
echo "<thead><tr><th>Sunday</th> <th>Monday</th> <th>Tuesday</th> <th>Wednesday</th> <th>Thursday</th> <th>Friday</th> <th>Saturday</th></tr></thead>";
echo "<tbody>";
for ($i=0;$i<count($hdrs);$i++) {
	echo "<tr>";
	for ($j=0; $j<count($hdrs[$i]);$j++) {
		if ($i==0 && $j==$dayofweek && $pageshift==0) { //onmouseover="makebig(this)"
			echo '<td id="'.$ids[$i][$j].'" onclick="showcontents(this)" class="today"><div class="td"><span class=day>'.$hdrs[$i][$j]."</span><div class=contents>";
		
		} else {
			echo '<td id="'.$ids[$i][$j].'" onclick="showcontents(this)" ><div class="td"><span class=day>'.$hdrs[$i][$j]."</span><div class=contents>";
		}
		if (isset($assess[$ids[$i][$j]])) {
			for ($k=0;$k<count($assess[$ids[$i][$j]]);$k++) {
				//echo $assess[$ids[$i][$j]][$k];
				echo "<span class=icon style=\"background-color:#f66\">?</span> ";
			}
		}
		echo "</div></div></td>";
	}
	echo "</tr>";
}
echo "</tbody></table>";

echo "<div id=step style=\"margin: 10px; padding:10px; border:2px solid #000;\"></div>";
?>
	
</body>
</html>
