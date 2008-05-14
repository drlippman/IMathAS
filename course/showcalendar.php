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
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/course.js\"></script>";
	
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; Calendar</div>";
	
	 if (isset($teacherid)) {
		echo "<a id=\"mcelink\" href=\"managecalitems.php?cid=$cid\">Manage Events</a>";
	 }
	 showcalendar("showcalendar");
	
	 require("../footer.php");	
	 
	 
	 
	 function makecolor2($stime,$etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   if ($etime==2000000000) {
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
