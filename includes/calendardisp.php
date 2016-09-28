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

$havecalcedviewedassess = false;
$viewedassess = array();

function showcalendar($refpage) {
global $DBH;
global $imasroot,$cid,$userid,$teacherid,$previewshift,$latepasses,$urlmode, $latepasshrs, $myrights, $tzoffset, $tzname, $havecalcedviewedassess, $viewedassess, $editingon;

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
if (!isset($editingon)) {
	$editingon = false;
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

	list($thisyear,$thismo,$thisday,$thismonum,$datestr) = explode('|',tzdate('Y|M|j|n|l F j, Y',$midtoday - ($dayofweek - $i)*24*60*60));
	if ($thismo==$lastmo) {
		$hdrs[$row][$col] = $thisday;
	} else {
		$hdrs[$row][$col] = "$thismo $thisday";
		$lastmo = $thismo;
	}
	$ids[$row][$col] = "$thisyear-$thismonum-$thisday";

	$dates[$ids[$row][$col]] = $datestr;
}

?>


<?php
//echo '<div class="floatleft">Jump to <a href="'.$refpage.'.php?calpageshift=0&cid='.$cid.'">Now</a></div>';
$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$refpage.php?cid=$cid";

echo '<script type="text/javascript">var calcallback = "'.$address.'";</script>';
echo '<div class="floatright"><span class="calupdatenotice red"></span> Show <select id="callength" onchange="changecallength(this)">';
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
$forumexceptions = array();
if (!isset($teacherid)) {
	//DB $query = "SELECT assessmentid,startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE userid='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT assessmentid,startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE userid=:userid");
	$stm->execute(array(':userid'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[5]=='A') {
			$exceptions[$row[0]] = array($row[1],$row[2],$row[3],$row[4]);
		} else if ($row[5]=='F' || $row[5]=='P' || $row[5]=='R') {
			$forumexceptions[$row[0]] = array($row[1],$row[2],$row[3],$row[4],$row[5]);
		}
	}
}

$byid = array();
$k = 0;

$bestscores_stm = null;
//DB $query = "SELECT id,name,startdate,enddate,reviewdate,gbcategory,reqscore,reqscoreaid,timelimit,allowlate,caltag,calrtag FROM imas_assessments WHERE avail=1 AND courseid='$cid' AND enddate<2000000000 ORDER BY name";
//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id,name,startdate,enddate,reviewdate,gbcategory,reqscore,reqscoreaid,timelimit,allowlate,caltag,calrtag FROM imas_assessments WHERE avail=1 AND courseid=:courseid AND enddate<2000000000 ORDER BY name");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
	if (!$editingon && $row[3]>$uppertime && ($row[4]==0 || $row[4]>$uppertime || $row[4]<$row[3])) {
		continue;
	}
	//if enddate is past, and reviewdate is past end of calendar
	if (!$editingon && $row[3]<$now && $row[4]>$uppertime) {
		//continue;
	}
	//echo "{$row[1]}, {$row[3]}, $uppertime, {$row[4]}<br/>";
	//if startdate is past now
	if (!$editingon && ($row[2]>$now && !isset($teacherid))) {
		continue;
	}
	//if past reviewdate
	if (!$editingon && $row[4]>0 && $now>$row[4] && !isset($teacherid)) { //if has reviewdate and we're past it   //|| ($now>$row[3] && $row[4]==0)
		//continue;
	}

	$showgrayedout = false;
	if (!isset($teacherid) && abs($row[6])>0 && $row[7]>0 && (!isset($exceptions[$row[0]]) || $exceptions[$row[0]][3]==0)) {
			 //DB $query = "SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid='{$row[7]}' AND userid='$userid'";
		   //DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		   //DB if (mysql_num_rows($r2)==0) {
			 if ($bestscores_stm===null) { //only prepare once
			 	$bestscores_stm = $DBH->prepare("SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid=:assessmentid AND userid=:userid");
			 }
			 $bestscores_stm->execute(array(':assessmentid'=>$row[7], ':userid'=>$userid));
		   if ($bestscores_stm->rowCount()==0) {
		   	   if ($row[6]<0) {
		   	   	   $showgrayedout = true;
		   	   } else {
		   	   	   continue;
		   	   }
		   } else {
			   //DB $scores = explode(';',mysql_result($r2,0,0));
			   $scores = explode(';',$bestscores_stm->fetchColumn(0));
			   if (round(getpts($scores[0]),1)+.02<abs($row[6])) {
				   if ($row[6]<0) {
					   $showgrayedout = true;
				   } else {
					   continue;
				   }
			   }
		   }
	}
	if ($row[4]<$uppertime && $row[4]>$exlowertime && (($row[4]>0 && $now>$row[3]) || $editingon)) { //has review, and we're past enddate
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[4]));
		$row[1] = htmlentities($row[1], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
		$tag = htmlentities($row[11], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
		if ($editingon) {$colors='';} else {if ($now<$row[4]) { $colors = '#99f';} else {$colors = '#ccc';}}
		$json = "{type:\"AR\", typeref:\"$row[0]\", time:\"$time\", tag:\"$tag\", ";
		if ($now<$row[4] || isset($teacherid)) { $json .= "id:\"$row[0]\",";}
		$json .=  "color:\"".$colors."\",name:\"$row[1]\"".((isset($teacherid))?", editlink:true":"")."}";
		//if (($row[3]<$uppertime && $row[3]>$exlowertime) || $editingon) {  //if going to do a second tag, need to increment.
			$byid['AR'.$row[0]] = array($moday,$tag,$colors,$json,$row[1]);
		//}
	}
	$tag = htmlentities($row[10], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	if ($row[3]<$uppertime && $row[3]>$exlowertime) {// taking out "hide if past due" && ($now<$row[3] || isset($teacherid))) {
		/*if (isset($gbcats[$row[5]])) {
			$tag = $gbcats[$row[5]];
		} else {
			$tag = '?';
		}*/

		if (!$havecalcedviewedassess && $now>$row[3] && $row[9]>10) {
			$havecalcedviewedassess = true;
			$viewedassess = array();
			//DB $query = "SELECT typeid FROM imas_content_track WHERE courseid='$cid' AND userid='$userid' AND type='gbviewasid'";
			//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($r = mysql_fetch_row($r2)) {
			$stm2 = $DBH->prepare("SELECT typeid FROM imas_content_track WHERE courseid=:courseid AND userid=:userid AND type='gbviewasid'");
			$stm2->execute(array(':courseid'=>$cid, ':userid'=>$userid));
			while ($r = $stm2->fetch(PDO::FETCH_NUM)) {
				$viewedassess[] = $r[0];
			}
		}
		if (($row[9]%10==1 || $row[9]%10-1>$latepasscnt) && $latepasses>0 && !$showgrayedout &&
		   ($now < $row[3] || ($row[9]>10 && $now-$row[3]<$latepasshrs*3600 && !in_array($row[0],$viewedassess)))) {
			$lp = 1;
		} else {
			$lp = 0;
		}


		if ($canundolatepass && !$showgrayedout) {
			$ulp = 1;
		} else {
			$ulp = 0;
		}
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[3]));
		$row[1] = htmlentities($row[1], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
		if ($showgrayedout) {
			$colors = '#ccc';
		} else {
			$colors = makecolor2($row[2],$row[3],$now);
		}
		if ($editingon) {$colors='';}
		$json = "{type:\"AE\", typeref:\"$row[0]\", time:\"$time\", ";
		if ($now<$row[3] || $row[4]>$now || isset($teacherid) || $lp==1) { $json .= "id:\"$row[0]\",";}
		if ((($now>$row[3] && $now>$row[4]) || $showgrayedout) && !isset($teacherid)) { $json .= 'inactive:true,';}
		$json .= "name:\"$row[1]\", color:\"".$colors."\", allowlate:\"$lp\", undolate:\"$ulp\", tag:\"$tag\"".(($row[8]!=0)?", timelimit:true":"").((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";
		$byid['AE'.$row[0]] = array($moday,$tag,$colors,$json,$row[1]);
	}
	if ($editingon && $row[2]>$exlowertime && $row[2]<$uppertime) {
		$json = "{type:\"AS\", typeref:\"$row[0]\", name:\"$row[1]\", tag:\"$tag\"}";
		$byid['AS'.$row[0]] = array(tzdate('Y-n-j',$row[2]) ,$tag,'',$json,$row[1]);
	}
}
// 4/4/2011, changing tthis to code block below.  Not sure why change on 10/23 was made :/
//if (isset($teacherid)) {
	//$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND (avail=1 OR (avail=2 AND startdate>0)) AND courseid='$cid'";
//} else {
//	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$lowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";  //chg 10/23/09: replace $now with $uppertime
//}

if (isset($teacherid)) {
	//DB $query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND (avail=1 OR (avail=2 AND startdate>0)) AND courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND (avail=1 OR (avail=2 AND startdate>0)) AND courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
} else {
	//DB $query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ";
	//DB $query .= "((avail=1 AND ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime))) OR ";
	//DB $query .= "(avail=2 AND oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail FROM imas_inlinetext WHERE ";
	$query .= "((avail=1 AND ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime))) OR ";
	$query .= "(avail=2 AND oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND courseid=:courseid";
	$stm = $DBH->prepare($query); //times were calcualated in flow - safe
	$stm->execute(array(':courseid'=>$cid));
}

//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if ($row[1]=='##hidden##') {
		$row[1] = preg_replace('/\s+/',' ',strip_tags($row[3]));
		if (strlen($row[1])>25) {
			$row[1] = substr($row[1],0,25).'...';
		}
	}
	if ($row[5]==1) {
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[4]));
		if ($row[7]==2) {
			$datefield = 'O';
		} else {
			$datefield = 'S';
		}
	} else {
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[2]));
		$datefield = 'E';
	}
	$row[1] = htmlentities($row[1], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$colors = makecolor2($row[4],$row[2],$now);
	if ($row[7]==2) {
		$colors = "#0f0";
	}
	if ($editingon) {$colors='';}
	$tag = htmlentities($row[6], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$json = "{type:\"I$datefield\", typeref:\"$row[0]\", folder:\"@@@\", time:\"$time\", id:\"$row[0]\", name:\"$row[1]\", color:\"".$colors."\", tag:\"$tag\"".((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";

	$byid['I'.$datefield.$row[0]] = array($moday,$tag,$colors,$json,$row[1]);

	if ($editingon && $datefield != 'O' && $row[7]==1) {
		if ($datefield=='S' && $row[2]>$exlowertime && $row[2]<$uppertime) {
			$json = "{type:\"IE\", typeref:\"$row[0]\", name:\"$row[1]\", tag:\"$tag\"}";
			$byid['IE'.$row[0]] = array(tzdate('Y-n-j',$row[2]),$tag,$colors,$json,$row[1]);
		} else if ($datefield=='E' && $row[4]>$exlowertime && $row[4]<$uppertime) {
			$json = "{type:\"IS\", typeref:\"$row[0]\", name:\"$row[1]\", tag:\"$tag\"}";
			$byid['IS'.$row[0]] = array(tzdate('Y-n-j',$row[4]),$tag,$colors,$json,$row[1]);
		}
	}

}
//$query = "SELECT id,title,enddate,text,startdate,oncal,caltag FROM imas_linkedtext WHERE ((oncal=2 AND enddate>$lowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime)) AND avail=1 AND courseid='$cid'";
if (isset($teacherid)) {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail,target FROM imas_linkedtext WHERE ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime) OR (oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND (avail=1 OR (avail=2 AND startdate>0)) AND courseid=:courseid ORDER BY title";
} else {
	$query = "SELECT id,title,enddate,text,startdate,oncal,caltag,avail,target FROM imas_linkedtext WHERE ";
	$query .= "((avail=1 AND ((oncal=2 AND enddate>$exlowertime AND enddate<$uppertime AND startdate<$now) OR (oncal=1 AND startdate<$now AND startdate>$exlowertime))) OR ";
	$query .= "(avail=2 AND oncal=1 AND startdate<$uppertime AND startdate>$exlowertime)) AND courseid=:courseid ORDER BY title";
}
$stm = $DBH->prepare($query); //times were calcualated in flow - safe
$stm->execute(array(':courseid'=>$cid));
//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if ($row[5]==1) {
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[4]));
		if ($row[7]==2) {
			$datefield = 'O';
		} else {
			$datefield = 'S';
		}
	} else {
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[2]));
		$datefield = 'E';
	}
	$row[1] = htmlentities($row[1], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
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
	if ($editingon) {$colors='';}
	$json = "{type:\"L$datefield\", typeref:\"$row[0]\", time:\"$time\", ";
	if (isset($teacherid) || ($now<$row[2] && $now>$row[4]) || $row[7]==2) {
		$json .= "id:\"$row[0]\", ";
	}
	$tag = htmlentities($row[6], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$alink = htmlentities($alink, ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$json .= "name:\"$row[1]\", link:\"$alink\", target:{$row[8]}, color:\"".$colors."\", tag:\"$tag\"".((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";

	$byid['L'.$datefield.$row[0]] = array($moday,$tag,$colors,$json,$row[1]);
	if ($editingon && $datefield != 'O' && $row[7]==1) {
		if ($datefield=='S' && $row[2]>$exlowertime && $row[2]<$uppertime) {
			$json = "{type:\"LE\", typeref:\"$row[0]\", name:\"$row[1]\", tag:\"$tag\"}";
			$byid['LE'.$row[0]] = array(tzdate('Y-n-j',$row[2]),$tag,$colors,$json,$row[1]);
		} else if ($datefield=='E' && $row[4]>$exlowertime && $row[4]<$uppertime) {
			$json = "{type:\"LS\", typeref:\"$row[0]\", name:\"$row[1]\", tag:\"$tag\"}";
			$byid['LS'.$row[0]] = array(tzdate('Y-n-j',$row[4]),$tag,$colors,$json,$row[1]);
		}
	}
}

if (isset($teacherid)) {
	$query = "SELECT id,name,enddate,startdate,caltag,avail FROM imas_drillassess WHERE (enddate>$exlowertime AND enddate<$uppertime) AND avail=1 AND courseid=:courseid ORDER BY name";
} else {
	$query = "SELECT id,name,enddate,startdate,caltag,avail FROM imas_drillassess WHERE ";
	$query .= "avail=1 AND (enddate>$exlowertime AND enddate<$uppertime AND startdate<$now) ";
	$query .= "AND courseid=:courseid ORDER BY name";
}
$stm = $DBH->prepare($query); //times were calcualated in flow - safe
$stm->execute(array(':courseid'=>$cid));
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[2]));

	$row[1] = htmlentities($row[1], ENT_COMPAT | ENT_HTML401, "UTF-8", false);

	$colors = makecolor2($row[3],$row[2],$now);
	if ($row[7]==2) {
		$colors = "#0f0";
	}
	if ($editingon) {$colors='';}
	$json = "{type:\"DE\", typeref:\"$row[0]\", time:\"$time\", ";
	if (isset($teacherid) || ($now<$row[2] && $now>$row[3]) || $row[5]==2) {
		$json .= "id:\"$row[0]\", ";
	}
	$tag = htmlentities($row[4], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$json .= "name:\"$row[1]\", color:\"".$colors."\", tag:\"$tag\"".((isset($teacherid))?", editlink:true":"")."}";//"<span class=icon style=\"background-color:#f66\">?</span> <a href=\"../assessment/showtest.php?id={$row[0]}&cid=$cid\">{$row[1]}</a> Due $time<br/>";

	$byid['DE'.$row[0]] = array($moday,$tag,$colors,$json,$row[1]);
	if ($editingon && $row[3]>$exlowertime && $row[3]<$uppertime) {
		$json = "{type:\"DS\", typeref:\"$row[0]\", name:\"$row[1]\", tag:\"$tag\"}";
		$byid['DS'.$row[0]] = array(tzdate('Y-n-j',$row[3]),$tag,'',$json,$row[1]);
	}
}

$query = "SELECT id,name,postby,replyby,startdate,caltag,allowlate FROM imas_forums WHERE enddate>$exlowertime AND ((postby>$exlowertime AND postby<$uppertime) OR (replyby>$exlowertime AND replyby<$uppertime)) AND avail>0 AND courseid=:courseid ORDER BY name";
$stm = $DBH->prepare($query); //times were calcualated in flow - safe
$stm->execute(array(':courseid'=>$cid));
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	if (($row['startdate']>$now && !isset($teacherid))) {
		continue;
	}
	//check for exception
	require_once("exceptionfuncs.php");
	list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $row['postby'], $row['replyby'], $row['enddate']) = getCanUseLatePassForums(isset($forumexceptions[$row['id']])?$forumexceptions[$row['id']]:null, $row);

	list($posttag,$replytag) = explode('--',$row['caltag']);
	$posttag = htmlentities($posttag, ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$replytag = htmlentities($replytag, ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$row['name'] = htmlentities($row['name'], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	if ($row['postby']!=2000000000) { //($row['postby']>$now || isset($teacherid))
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row['postby']));
		$colors = makecolor2($row['startdate'],$row['postby'],$now);
		if ($editingon) {$colors='';}
		$json = "{type:\"FP\", typeref:\"{$row['id']}\", time:\"$time\", ";
		//if ($row['postby']>$now || isset($teacherid)) {
			$json .= "id:\"{$row['id']}\",";
		//}
		$json .= 'allowlate:"'.($canuselatepassP?1:0).'",undolate:"'.($canundolatepassP?1:0).'",';

		$json .= "name:\"{$row['name']}\", color:\"".$colors."\", tag:\"$posttag\"".((isset($teacherid))?", editlink:true":"")."}";
		$byid['FP'.$row['id']] = array($moday,$posttag,$colors,$json,$row['name']);
	}
	if ($row['replyby']!=2000000000) { //($row['replyby']>$now || isset($teacherid))
		list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row['replyby']));
		$colors = makecolor2($row['startdate'],$row['replyby'],$now);
		if ($editingon) {$colors='';}
		$json = "{type:\"FR\", typeref:\"{$row['id']}\", time:\"$time\",";
		//if ($row['replyby']>$now || isset($teacherid)) {
			$json .= "id:\"{$row['id']}\",";
		//}
		$json .= 'allowlate:"'.($canuselatepassR?1:0).'",undolate:"'.($canundolatepassR?1:0).'",';
		$json .= "name:\"{$row['name']}\", color:\"".$colors."\", tag:\"$replytag\"".((isset($teacherid))?", editlink:true":"")."}";
		$byid['FR'.$row['id']] = array($moday,$replytag,$colors,$json,$row['name']);
	}
	$tag = substr($row[1],0,8);
	if ($editingon && $row['startdate']>$exlowertime && $row['startdate']<$uppertime) {
		$json = "{type:\"FS\", typeref:\"{$row['id']}\", name:\"{$row['name']}\", tag:\"F\"}";
		$byid['FS'.$row['id']] = array(tzdate('Y-n-j',$row['startdate']),$tag,'',$json,$row['name']);
	}
	if ($editingon && $row['enddate']>$exlowertime && $row['enddate']<$uppertime) {
		$json = "{type:\"FE\", typeref:\"{$row['id']}\", name:\"{$row['name']}\", tag:\"F\"}";
		$byid['FE'.$row['id']] = array(tzdate('Y-n-j',$row['enddate']),$tag,'',$json,$row['name']);
	}
}
//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB $itemorder = unserialize(mysql_result($result,0,0));
$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$itemorder = unserialize($stm->fetchColumn(0));
$itemsimporder = array();
$itemfolder = array();
$hiddenitems = array();

flattenitems($itemorder,$itemsimporder,$itemfolder,$hiddenitems,'0');

$itemsassoc = array();
//DB $query = "SELECT id,itemtype,typeid FROM imas_items WHERE courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id,itemtype,typeid FROM imas_items WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$itemsassoc[$row[0]] = array($row[1],$row[2]);
}

$assess = array();
$colors = array();
$tags = array();
$names = array();
$itemidref = array();
$k = 0;
foreach ($itemsimporder as $item) {
	if (isset($hiddenitems[$item]) && !isset($teacherid)) {
		//skip over any items in a hidden folder or future not-yet-open folder if it's a student
		continue;
	}
	if ($itemsassoc[$item][0]=='Assessment') {
		foreach (array('S','E','R') as $datetype) {
			if (isset($byid['A'.$datetype.$itemsassoc[$item][1]])) {
				$moday = $byid['A'.$datetype.$itemsassoc[$item][1]][0];
				$itemidref[$k] = 'A'.$datetype.$itemsassoc[$item][1];
				$tags[$k] = $byid['A'.$datetype.$itemsassoc[$item][1]][1];
				$colors[$k] = $byid['A'.$datetype.$itemsassoc[$item][1]][2];
				$assess[$moday][$k] = $byid['A'.$datetype.$itemsassoc[$item][1]][3];
				$names[$k] = $byid['A'.$datetype.$itemsassoc[$item][1]][4];
				$k++;
			}
		}
	} else if ($itemsassoc[$item][0]=='Forum') {
		foreach (array('S','E','P','R') as $datetype) {
			if (isset($byid['F'.$datetype.$itemsassoc[$item][1]])) {
				$moday = $byid['F'.$datetype.$itemsassoc[$item][1]][0];
				$itemidref[$k] = 'F'.$datetype.$itemsassoc[$item][1];
				$tags[$k] = $byid['F'.$datetype.$itemsassoc[$item][1]][1];
				$colors[$k] = $byid['F'.$datetype.$itemsassoc[$item][1]][2];
				$assess[$moday][$k] = $byid['F'.$datetype.$itemsassoc[$item][1]][3];
				$names[$k] = $byid['F'.$datetype.$itemsassoc[$item][1]][4];
				$k++;
			}
		}
	} else if ($itemsassoc[$item][0]=='InlineText') {
		foreach (array('S','E','O') as $datetype) {
			if (isset($byid['I'.$datetype.$itemsassoc[$item][1]])) {
				$moday = $byid['I'.$datetype.$itemsassoc[$item][1]][0];
				$itemidref[$k] = 'I'.$datetype.$itemsassoc[$item][1];
				$tags[$k] = $byid['I'.$datetype.$itemsassoc[$item][1]][1];
				$colors[$k] = $byid['I'.$datetype.$itemsassoc[$item][1]][2];
				if (isset($itemfolder[$item])) {
					$assess[$moday][$k] = str_replace('@@@',$itemfolder[$item],$byid['I'.$datetype.$itemsassoc[$item][1]][3]);
				} else {
					$assess[$moday][$k] = str_replace('"@@@"','null',$byid['I'.$datetype.$itemsassoc[$item][1]][3]);
				}
				$names[$k] = $byid['I'.$datetype.$itemsassoc[$item][1]][4];
				$k++;
			}
		}
	} else if ($itemsassoc[$item][0]=='LinkedText') {
		foreach (array('S','E','O') as $datetype) {
			if (isset($byid['L'.$datetype.$itemsassoc[$item][1]])) {
				$moday = $byid['L'.$datetype.$itemsassoc[$item][1]][0];
				$itemidref[$k] = 'L'.$datetype.$itemsassoc[$item][1];
				$tags[$k] = $byid['L'.$datetype.$itemsassoc[$item][1]][1];
				$colors[$k] = $byid['L'.$datetype.$itemsassoc[$item][1]][2];
				$assess[$moday][$k] = $byid['L'.$datetype.$itemsassoc[$item][1]][3];
				$names[$k] = $byid['L'.$datetype.$itemsassoc[$item][1]][4];
				$k++;
			}
		}
	} else if ($itemsassoc[$item][0]=='Drill') {
		foreach (array('S','E') as $datetype) {
			if (isset($byid['D'.$datetype.$itemsassoc[$item][1]])) {
				$moday = $byid['D'.$datetype.$itemsassoc[$item][1]][0];
				$itemidref[$k] = 'D'.$datetype.$itemsassoc[$item][1];
				$tags[$k] = $byid['D'.$datetype.$itemsassoc[$item][1]][1];
				$colors[$k] = $byid['D'.$datetype.$itemsassoc[$item][1]][2];
				$assess[$moday][$k] = $byid['D'.$datetype.$itemsassoc[$item][1]][3];
				$names[$k] = $byid['D'.$datetype.$itemsassoc[$item][1]][4];
				$k++;
			}
		}
	}

}

$stm = $DBH->prepare("SELECT title,tag,date,id FROM imas_calitems WHERE date>$exlowertime AND date<$uppertime and courseid=:courseid ORDER BY title");
$stm->execute(array(':courseid'=>$cid));
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	list($moday,$time) = explode('~',tzdate('Y-n-j~g:i a',$row[2]));
	$row[0] = htmlentities($row[0], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$row[1] = htmlentities($row[1], ENT_COMPAT | ENT_HTML401, "UTF-8", false);
	$assess[$moday][$k] = "{type:\"CD\", typeref:\"$row[3]\", time:\"$time\", tag:\"$row[1]\", name:\"$row[0]\"}";
	$tags[$k] = $row[1];
	$names[$k] = $row[0];
	$colors[$k]='';
  $itemidref[$k] = 'CD'.$row[3];
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
				if ($colors[$k]=='') {
					$style = '';
				} else {
					$style = ' style="background-color:'.$colors[$k].'"';
				}
				//echo $assess[$ids[$i][$j]][$k];
				echo "<span class=\"calitem\" id=\"".$itemidref[$k]."\" $style>";
				if ($editingon) {
					$type = $itemidref[$k]{1};
					if ($type=='S') {
						echo '<span class="icon-startdate"></span>';
					} else if ($type=='R' && $itemidref[$k]{0}=='A') {
						echo '<span class="icon-eye2"></span>';
					} else if ($type=='P') {
						echo '<span class="icon-forumpost"></span>';
					} else if ($type=='R' && $itemidref[$k]{0}=='F') {
						echo '<span class="icon-forumreply"></span>';
					}
				}
				echo '<span class="calitemtitle">';
				if ($editingon && isset($names[$k]) && trim($names[$k])!='') {
					echo $names[$k];
				} else if (isset($tags[$k])) {
					echo $tags[$k];
				} else {
					echo '!';
				}
				echo '</span>';
				if ($editingon && $type=='E') {
					echo '<span class="icon-enddate"></span>';
				}
				echo '</span> ';
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
function flattenitems($items,&$addto,&$folderholder,&$hiddenholder,$folder,$avail=true,$ishidden=false) {
	$now = time();
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$thisavail = ($avail && ($item['avail']==2 || ($item['avail']==1 && ($item['SH'][0]=='S' || ($item['startdate']<$now && $item['enddate']>$now)))));
			//set as hidden if explicitly hidden or opens in future.  We won't count past folders that aren't showing as hidden
			//  to allow students with latepasses to access old assignments even if the folder is gone.
			$thisishidden = ($ishidden || $item['avail']==0 || ($item['avail']==1 && $item['SH'][0]=='H' && $item['startdate']>$now));
			flattenitems($item['items'],$addto,$folderholder,$hiddenholder,$folder.'-'.($k+1),$thisavail,$thisishidden);
		} else {
			$addto[] = $item;
			if ($avail) {
				$folderholder[$item] = $folder;
			}
			if ($ishidden) {
				$hiddenholder[$item] = true;
			}
		}
	}
}
?>
