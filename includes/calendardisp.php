<?php

if (isset($_GET['calstart'])) {
	setcookie("calstart".$_GET['cid'], $_GET['calstart']);
	$_COOKIE["calstart".$_GET['cid']] = $_GET['calstart'];
}	
if (isset($_GET['callength'])) {
	setcookie("callength".$_GET['cid'], $_GET['callength']);
	$_COOKIE["callength".$_GET['cid']] = $_GET['callength'];
}

require_once("filehandler.php");

function showcalendar($refpage) {

global $imasroot,$cid,$userid,$teacherid,$previewshift,$latepasses,$urlmode, $latepasshrs, $myrights, $tzoffset, $tzname;

$now= time();
if ($previewshift!=-1) {
	$now = $now + $previewshift;
}
if (!isset($_COOKIE['calstart'.$cid]) || $_COOKIE['calstart'.$cid] == 0) {
	$today = $now;
} else {
	$today = $_COOKIE['calstart'.$cid];
}

if (isset($_GET['calpageshift'])) {
	$pageshift = $_GET['calpageshift'];
} else {
	$pageshift = 0;
}
if (!isset($_COOKIE['callength'.$cid])) {
	$callength = 4;
} else {
	$callength = $_COOKIE['callength'.$cid];
}

$today = $today + $pageshift*7*$callength*24*60*60;

$dayofweek = tzdate('w',$today);
$curmonum = tzdate('n',$today);
$dayofmo = tzdate('j',$today);
$curyr = tzdate('Y',$today);
if ($tzname=='') {
	$serveroffset = date('Z') + $tzoffset*60;
} else {
	$serveroffset = 0; //don't need this if user's timezone has been set
}
$midtoday = mktime(12,0,0,$curmonum,$dayofmo,$curyr)+$serveroffset;


$hdrs = array();
$ids = array();

$lastmo = '';
for ($i=0;$i<7*$callength;$i++) {
	$row = floor($i/7);
	$col = $i%7;
	
	list($thismo,$thisday,$thismonum,$datestr) = explode('|',tzdate('M|j|n|l F j, Y',$midtoday - ($dayofweek - $i)*24*60*60));
	if ($thismo==$lastmo) {
		$hdrs[$row][$col] = $thisday;
	} else {
		$hdrs[$row][$col] = "$thismo $thisday";
		$lastmo = $thismo;
	}
	$ids[$row][$col] = "$thismonum-$thisday";
	
	$dates[$ids[$row][$col]] = $datestr;
}

?>


<?php
//echo '<div class="floatleft">Jump to <a href="'.$refpage.'.php?calpageshift=0&cid='.$cid.'">Now</a></div>';
$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$refpage.php?cid=$cid";
	
echo '<script type="text/javascript">var calcallback = "'.$address.'";</script>';
echo '<div class="floatright">Show <select id="callength" onchange="changecallength(this)">';
for ($i=2;$i<26;$i++) {
	echo '<option value="'.$i.'" ';
	if ($i==$callength) {echo 'selected="selected"';}
	echo '>'.$i.'</option>';
}
echo '</select> weeks </div>';
echo '<div class=center><a href="'.$refpage.'.php?calpageshift='.($pageshift-1).'&cid='.$cid.'">&lt; &lt;</a> ';
//echo $longcurmo.' ';

if ($pageshift==0 && (!isset($_COOKIE['calstart'.$cid]) || $_COOKIE['calstart'.$cid]==0)) {
	echo "Now ";
} else {
	echo '<a href="'.$refpage.'.php?calpageshift=0&calstart=0&cid='.$cid.'">Now</a> ';
}
echo '<a href="'.$refpage.'.php?calpageshift='.($pageshift+1).'&cid='.$cid.'">&gt; &gt;</a> ';
echo '</div> ';
echo "<table class=\"cal\" >";  //onmouseout=\"makenorm()\"

$exlowertime = mktime(0,0,0,$curmonum,$dayofmo - $dayofweek,$curyr)+$serveroffset;
$lowertime = max($now,$exlowertime);
$uppertime = mktime(0,0,0,$curmonum,$dayofmo - $dayofweek + 7*$callength,$curyr)+$serveroffset;

$exceptions = array();
if (!isset($teacherid)) {
	$query = "SELECT assessmentid,startdate,enddate,islatepass FROM imas_exceptions WHERE userid='$userid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$exceptions[$row[0]] = array($row[1],$row[2],$row[3]);
	}
}


$byid = array();
$k = 0;

$query = "SELECT id,name,startdate,enddate,reviewdate,gbcategory,reqscore,reqscoreaid,timelimit,allowlate,caltag,calrtag FROM imas_assessments WHERE avail=1 AND courseid='$cid' AND enddate<2000000000 ORDER BY name";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$canundolatepass = false;
	$latepasscnt = 0;
	if (isset($exceptions[$row[0]])) {
		if ($exceptions[$row[0]][2]>0 && ($now < $row[3] || $exceptions[$row[0]][1] > $now + $latepasshrs*60*60)) {
			$canundolatepass = true;
		}
		$latepasscnt = round(($exceptions[$row[0]][1] - $row[3])/($latepasshrs*3600));		   
		$row[2] = $exceptions[$row[0]][0];
		$row[3] = $exceptions[$row[0]][1];
	}
	//2: start, 3: end, 4: review
	//if enddate past end of calendar
	if ($row[3]>$uppertime && ($row[4]==0 || $row[4]>$uppertime || $row[4]<$row[3])) {
		continue;
	}
	//if enddate is past, and reviewdate is past end of calendar
	if ($row[3]<$now && $row[4]>$uppertime) { 
		//continue;
	}
	//echo "{$row[1]}, {$row[3]}, $uppertime, {$row[4]}<br/>";
	//if startdate is past now
	if (($row[2]>$now && !isset($teacherid))) {  
		continue;
	} 
	//if past reviewdate
	if ($row[4]>0 && $now>$row[4] && !isset($teacherid)) { //if has reviewdate and we're past it   //|| ($now>$row[3] && $row[4]==0)
		//continue;
	}
	
	if (!isset($teacherid) && $row[6]>0 && $row[7]>0) {
		$query = "SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid='{$row[7]}' AND userid='$userid'";
		   $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		   if (mysql_num_rows($r2)==0) {
			   continue;
		   } else {
			   $scores = explode(';',mysql_result($r2,0,0));
			   if (getpts($scores[0])<$row[6]) {
				   continue;
			   }
		   }
	}
	if ($row[4]<$uppertime && $row[4]>0 && $now>$row[3]) { //has review, and we're past enddate
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[4]));
		$row[1] = htmlentities($row[1]);
		$tag = htmlentities($row[11]);
		if ($now<$row[4]) { $colors = '#99f';} else {$colors = '#ccc';}
		$json = "{type:\"AR\", time:\"$time\", tag:\"$tag\", ";
		if ($now<$row[4] || isset($teacherid)) { $json .= "id:\"$row[0]\",";}
		$json .=  "color:\"".$colors."\",name:\"$row[1]\"".((isset($teacherid))?", editlink:true":"")."}";
		if ($row[3]<$uppertime && $row[3]>$exlowertime) {  //if going to do a second tag, need to increment.
			$byid['AR'.$row[0]] = array($moday,$tag,$colors,$json);
		}
	} 
	if ($row[3]<$uppertime && $row[3]>$exlowertime) {// taking out "hide if past due" && ($now<$row[3] || isset($teacherid))) {
		/*if (isset($gbcats[$row[5]])) {
			$tag = $gbcats[$row[5]];
		} else {
			$tag = '?';
		}*/
		$tag = htmlentities($row[10]);
		if (($row[9]==1 || $row[9]-1>$latepasscnt) && $latepasses>0 && $now < $row[3]) {
			$lp = 1;
		} else {
			$lp = 0;
		}
		if ($canundolatepass) {
			$ulp = 1;
		} else {
			$ulp = 0;
		}
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[3]));
		$row[1] = htmlentities($row[1]);
		$colors = makecolor2($row[2],$row[3],$now);
		$json = "{type:\"A\", time:\"$time\", ";
		if ($now<$row[3] || $row[4]>$now || isset($teacherid)) { $json .= "id:\"$row[0]\",";}
		$json .= "name:\"$row[1]\", color:\"".$colors."\", allowlate:\"$lp\", undolate:\"$ulp\", tag:\"$tag\"".(($row[8]!=0)?", timelimit:true":"").((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
		$byid['A'.$row[0]] = array($moday,$tag,$colors,$json);
	} 
}
// 4/4/2011, changing tthis to code block below.  Not sure why change on 10/23 was made :/
//if (isset($teacherid)) {
	//$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND (avail=1 OR (avail=2 AND startdate>0)) AND courseid='$cid'";
//} else {
//	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$lowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";  //chg 10/23/09: replace $now with $uppertime
//}

if (isset($teacherid)) {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND (avail=1 OR (avail=2 AND startdate>0)) AND courseid='$cid'";
} else {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ";
	$query .= "((avail=1 AND ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime))) OR ";
	$query .= "(avail=2 AND oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND courseid='$cid'";
}

$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if ($row[1]=='##hidden##') {
		$row[1] = strip_tags( $row[3]);
	}
	if ($row[5]==1) {
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[4]));
	} else {
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[2]));
	}
	$row[1] = htmlentities($row[1]);
	$colors = makecolor2($row[4],$row[2],$now);
	if ($row[7]==2) {
		$colors = "#0f0";
	}
	$tag = htmlentities($row[6]);
	$json = "{type:\"I\", folder:\"@@@\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", color:\"".$colors."\", tag:\"$tag\"".((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	
	$byid['I'.$row[0]] = array($moday,$tag,$colors,$json);
	
}
//$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_linkedtext WHERE ((oncal=2 AND enddate>$lowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";
if (isset($teacherid)) {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_linkedtext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND (avail=1 OR (avail=2 AND startdate>0)) AND courseid='$cid' ORDER BY title";
} else {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_linkedtext WHERE ";
	$query .= "((avail=1 AND ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime))) OR ";
	$query .= "(avail=2 AND oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND courseid='$cid' ORDER BY title";
}
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if ($row[5]==1) {
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[4]));
	} else {
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[2]));
	}
	$row[1] = htmlentities($row[1]);
	 if ((substr($row[3],0,4)=="http") && (strpos($row[3]," ")===false)) { //is a web link
		   $alink = trim($row[3]);
	   } else if (substr($row[3],0,5)=="file:") {
		   $filename = substr(strip_tags($row[3]),5);
		   $alink = getcoursefileurl($filename);//$alink = $imasroot . "/course/files/".$filename;
	   } else {
		   $alink = '';
	   }
	$colors = makecolor2($row[4],$row[2],$now);
	if ($row[7]==2) {
		$colors = "#0f0";
	}
	$json = "{type:\"L\", time:\"$time\", ";
	if (isset($teacherid) || ($now<$row[2] && $now>$row[4]) || $row[7]==2) {
		$json .= "id:\"$row[0]\", ";
	}
	$tag = htmlentities($row[6]);
	$alink = htmlentities($alink);
	$json .= "name:\"$row[1]\", link:\"$alink\", color:\"".$colors."\", tag:\"$tag\"".((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
	
	$byid['L'.$row[0]] = array($moday,$tag,$colors,$json);
}
$query = "SELECT id,name,postby,replyby,startdate,caltag FROM imas_forums WHERE enddate>$exlowertime AND ((postby>$exlowertime AND postby<$uppertime) OR (replyby>$exlowertime AND replyby<$uppertime)) AND avail>0 AND courseid='$cid' ORDER BY name";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if (($row[4]>$now && !isset($teacherid))) {
		continue;
	}
	list($posttag,$replytag) = explode('--',$row[5]);
	$posttag = htmlentities($posttag);
	$replytag = htmlentities($replytag);
	if ($row[2]!=2000000000) { //($row[2]>$now || isset($teacherid))
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[2]));
		$row[1] = htmlentities($row[1]);
		$colors = makecolor2($row[4],$row[2],$now);
		$json = "{type:\"FP\", time:\"$time\", ";
		if ($row[2]>$now || isset($teacherid)) {
			$json .= "id:\"$row[0]\",";
		}
		$json .= "name:\"$row[1]\", color:\"".$colors."\", tag:\"$posttag\"".((isset($teacherid))?", editlink:true":"")."}";
		$byid['FP'.$row[0]] = array($moday,$posttag,$colors,$json);
	}
	if ($row[3]!=2000000000) { //($row[3]>$now || isset($teacherid)) 
		list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[3]));
		$colors = makecolor2($row[4],$row[3],$now);
		$json = "{type:\"FR\", time:\"$time\",";
		if ($row[3]>$now || isset($teacherid)) {
			$json .= "id:\"$row[0]\",";
		}
		$json .= "name:\"$row[1]\", color:\"".$colors."\", tag:\"$replytag\"".((isset($teacherid))?", editlink:true":"")."}";
		$byid['FR'.$row[0]] = array($moday,$replytag,$colors,$json);
	}
}
$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
$itemorder = unserialize(mysql_result($result,0,0));
$itemsimporder = array();
$itemfolder = array();

flattenitems($itemorder,$itemsimporder,$itemfolder,'0');

$itemsassoc = array();
$query = "SELECT id,itemtype,typeid FROM imas_items WHERE courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$itemsassoc[$row[0]] = array($row[1],$row[2]);
}

$assess = array();
$colors = array();
$tags = array();
$k = 0;
foreach ($itemsimporder as $item) {
	if ($itemsassoc[$item][0]=='Assessment') {
		if (isset($byid['A'.$itemsassoc[$item][1]])) {
			$moday = $byid['A'.$itemsassoc[$item][1]][0];
			$tags[$k] = $byid['A'.$itemsassoc[$item][1]][1];
			$colors[$k] = $byid['A'.$itemsassoc[$item][1]][2];
			$assess[$moday][$k] = $byid['A'.$itemsassoc[$item][1]][3];
			$k++;
		}
		if (isset($byid['AR'.$itemsassoc[$item][1]])) {
			$moday = $byid['AR'.$itemsassoc[$item][1]][0];
			$tags[$k] = $byid['AR'.$itemsassoc[$item][1]][1];
			$colors[$k] = $byid['AR'.$itemsassoc[$item][1]][2];
			$assess[$moday][$k] = $byid['AR'.$itemsassoc[$item][1]][3];
			$k++;
		}
	} else if ($itemsassoc[$item][0]=='Forum') {
		if (isset($byid['FP'.$itemsassoc[$item][1]])) {
			$moday = $byid['FP'.$itemsassoc[$item][1]][0];
			$tags[$k] = $byid['FP'.$itemsassoc[$item][1]][1];
			$colors[$k] = $byid['FP'.$itemsassoc[$item][1]][2];
			$assess[$moday][$k] = $byid['FP'.$itemsassoc[$item][1]][3];
			$k++;
		}
		if (isset($byid['FR'.$itemsassoc[$item][1]])) {
			$moday = $byid['FR'.$itemsassoc[$item][1]][0];
			$tags[$k] = $byid['FR'.$itemsassoc[$item][1]][1];
			$colors[$k] = $byid['FR'.$itemsassoc[$item][1]][2];
			$assess[$moday][$k] = $byid['FR'.$itemsassoc[$item][1]][3];
			$k++;
		}
	} else if ($itemsassoc[$item][0]=='InlineText') {
		if (isset($byid['I'.$itemsassoc[$item][1]])) {
			$moday = $byid['I'.$itemsassoc[$item][1]][0];
			$tags[$k] = $byid['I'.$itemsassoc[$item][1]][1];
			$colors[$k] = $byid['I'.$itemsassoc[$item][1]][2];
			if (isset($itemfolder[$item])) {
				$assess[$moday][$k] = str_replace('@@@',$itemfolder[$item],$byid['I'.$itemsassoc[$item][1]][3]);
			} else {
				$assess[$moday][$k] = str_replace('"@@@"','null',$byid['I'.$itemsassoc[$item][1]][3]);
			}
			$k++;
		}
	} else if ($itemsassoc[$item][0]=='LinkedText') {
		if (isset($byid['L'.$itemsassoc[$item][1]])) {
			$moday = $byid['L'.$itemsassoc[$item][1]][0];
			$tags[$k] = $byid['L'.$itemsassoc[$item][1]][1];
			$colors[$k] = $byid['L'.$itemsassoc[$item][1]][2];
			$assess[$moday][$k] = $byid['L'.$itemsassoc[$item][1]][3];
			$k++;
		}
	}
	
}

$query = "SELECT title,tag,date FROM imas_calitems WHERE date>$exlowertime AND date<$uppertime and courseid='$cid' ORDER BY title";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
while ($row = mysql_fetch_row($result)) {
	list($moday,$time) = explode('~',tzdate('n-j~g:i a',$row[2]));
	$row[0] = htmlentities($row[0]);
	$row[1] = htmlentities($row[1]);
	$assess[$moday][$k] = "{type:\"C\", time:\"$time\", tag:\"$row[1]\", name:\"$row[0]\"}";
	$tags[$k] = $row[1];
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
			$addr = $refpage.".php?cid=$cid&calstart=". ($midtoday + $i*7*24*60*60 + ($j - $dayofweek)*24*60*60);
			echo '<td id="'.$ids[$i][$j].'" onclick="showcalcontents(this)" ><div class="td"><span class=day><a href="'.$addr.'" class="caldl">'.$hdrs[$i][$j]."</a></span><div class=center>";
		}
		if (isset($assess[$ids[$i][$j]])) {
			foreach ($assess[$ids[$i][$j]] as $k=>$info) {
				//echo $assess[$ids[$i][$j]][$k];
				if (strpos($info,'type:"AR"')!==false) {
					echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">{$tags[$k]}</span> ";
				} else if (strpos($info,'type:"A"')!==false) {
					echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">{$tags[$k]}</span> ";
				} else if (strpos($info,'type:"F')!==false) { 
					echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">{$tags[$k]}</span> ";
				} else if (strpos($info,'type:"C')!==false) { 
					echo "<span class=\"calitem\" style=\"background-color: #0ff;\">{$tags[$k]}</span> ";
				} else { //textitems
					if (isset($tags[$k])) {
						echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">{$tags[$k]}</span> ";
					} else {
						echo "<span class=\"calitem\" style=\"background-color:".$colors[$k].";\">!</span> ";
					}
				}
			}
		}
		echo "</div></div></td>";
	}
	echo "</tr>";
}
echo "</tbody></table>";

echo "<div style=\"margin-top: 10px; padding:10px; border:1px solid #000;\">";
echo '<span class=right><a href="#" onclick="showcalcontents('.(1000*($midtoday - $dayofweek*24*60*60)).'); return false;"/>Show all</a></span>';
echo "<div id=\"caleventslist\"></div><div class=\"clear\"></div></div>";
if ($pageshift==0) {
	echo "<script>showcalcontents(document.getElementById('{$ids[0][$dayofweek]}'));</script>";
}

}
function flattenitems($items,&$addto,&$folderholder,$folder,$avail=true) {
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$now = time();
			$avail = ($avail && ($item['avail']==2 || ($item['avail']==1 && $item['startdate']<$now && $item['enddate']>$now)));
			flattenitems($item['items'],$addto,$folderholder,$folder.'-'.($k+1),$avail);
		} else {
			$addto[] = $item;
			if ($avail) {
				$folderholder[$item] = $folder;
			}
		}
	}
}
?>
