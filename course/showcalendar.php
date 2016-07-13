<?php
//IMathAS:  Display the calendar by itself
//(c) 2008 David Lippman
	require("../validate.php");
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
	}
	
	$cid = $_GET['cid'];
	
	require("../includes/calendardisp.php");
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/course.js?v=092815\"></script>";
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; Calendar</div>";
	echo '<div id="headercalendar" class="pagetitle"><h2>Calendar</h2></div>';
	
	 if (isset($teacherid)) {
		echo "<div class=\"cpmid\"><a id=\"mcelink\" href=\"managecalitems.php?from=cal&cid=$cid\">Manage Events</a></div>";
	 }
	 if (!isset($teacherid) && !isset($tutorid) && $previewshift==-1 && isset($studentinfo)) {
	   //$query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
	   //$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   //$latepasses = mysql_result($result,0,0);
	   $latepasses = $studentinfo['latepasses'];
	} else {
		$latepasses = 0;
	}
	 
	 $query = "SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,toolset,chatset,topbar,cploc,latepasshrs FROM imas_courses WHERE id='$cid'";
	 $result = mysql_query($query) or die("Query failed : " . mysql_error());
	 $line = mysql_fetch_array($result, MYSQL_ASSOC);
	 $latepasshrs = $line['latepasshrs']; 
	 $msgset = $line['msgset']%5;
	 
	 showcalendar("showcalendar");
	
	 require("../footer.php");	
	 
	 
	 
	 function makecolor2($stime,$etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   if ($etime==2000000000 && $now >= $stime) {
		   return '#0f0';
	   } else if ($stime==0) {
		   return makecolor($etime,$now);
	   }
	   if ($etime==$stime) {
		   return '#ccc';
	   }
	   $r = ($etime-$now)/($etime-$stime);  //0 = etime, 1=stime; 0:#f00, 1:#0f0, .5:#ff0
	   if ($etime<$now || $stime>$now) {
		   $color = '#ccc';
	   } else if ($r<.5) {
		   $color = '#f'.dechex(floor(32*$r)).'0';
	   } else if ($r<1) {
		   $color = '#'.dechex(floor(32*(1-$r))).'f0';
	   } else {
		   $color = '#0f0';
	   }
	    return $color;
	 }
	 
	 function makecolor($etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   //$now = time();
	   if ($etime<$now) {
		   $color = "#ccc";
	   } else if ($etime-$now < 605800) {  //due within a week
		   $color = "#f".dechex(floor(16*($etime-$now)/605801))."0";
	   } else if ($etime-$now < 1211600) { //due within two weeks
		   $color = "#". dechex(floor(16*(1-($etime-$now-605800)/605801))) . "f0";
	   } else {
		   $color = "#0f0";
	   }
	   return $color;
   }
	 function formatdate($date) {
	return tzdate("D n/j/y, g:i a",$date);   
		//return tzdate("M j, Y, g:i a",$date);   
	   }
	function getpts($scs) {
		$tot = 0;
		foreach(explode(',',$scs) as $sc) {
			if (strpos($sc,'~')===false) {
				if ($sc>0) { 
					$tot += $sc;
				} 
			} else {
				$sc = explode('~',$sc);
				foreach ($sc as $s) {
					if ($s>0) { 
						$tot+=$s;
					}
				}
			}
		}
		return $tot;
	   }

?>
