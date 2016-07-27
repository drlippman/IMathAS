<?php

//Exception Handling functions

//$exception should be from imas_exceptions, and be null, or 
//   array(startdate,enddate,islatepass,waivereqscore,itemtype)
//$line should be from imas_forums, and be assoc array including keys
//   replyby, postby, enddate, allowlate
//returns array($canundolatepassP, $canundolatepassR, $canundolatepass,
//                 $canuselatepassP, $canuselatepassR,
//		   new postby, new replyby, new enddate)
function getCanUseLatePassForums($exception, $line) {
	global $latepasshrs, $latepasses, $sessiondata;
	$now = time();
	$canundolatepassP = false;
	$canundolatepassR = false;
	$canundolatepass = false;
	$latepasscntP = 0;
	$latepasscntR = 0;
	if ($exception !== null) {
	   //for forums, exceptions[$items[$i]][0] is used for postby and [1] is used for replyby
	   if (($exception[4]=='P' || $exception[4]=='F') && $exception[0]>0) {
		   //if latepass and it's before original due date or exception is for more than a latepass past now
		   if ($exception[2]>0 && ($now < $line['postby'] || $exception[0] > $now + $latepasshrs*60*60)) {
			   $canundolatepassP = true;
		   }
		   if ($exception[2]>0) {
			   $latepasscntP = max(0,round(($exception[0] - $line['postby'])/($latepasshrs*3600)));
		   }
		   $line['postby'] = $exception[0];
		   if ($line['postby']>$line['enddate']) { //extend enddate if needed to accomodate postby exception
			   $line['enddate'] = $line['postby'];
		   }
	   }
	   if (($exception[4]=='R' || $exception[4]=='F') && $exception[1]>0) {
		   //if latepass and it's before original due date or exception is for more than a latepass past now
		   if ($exception[2]>0 && ($now < $line['replyby'] || $exception[1] > $now + $latepasshrs*60*60)) {
			   $canundolatepassR = true;
		   }
		   if ($exception[2]>0) {
			   $latepasscntR = max(0,round(($exception[1] - $line['replyby'])/($latepasshrs*3600)));
		   }
		   $line['replyby'] = $exception[1];
		   if ($line['replyby']>$line['enddate']) { //extend enddate if needed to accomodate postby exception
			   $line['enddate'] = $line['replyby'];
		   }
	   }
	   if ($exception[4]=='F' && $exception[0]>0 && $exception[1]>0) {
		  $canundolatepass = $canundolatepassP && $canundolatepassR;
	   } else {
		  $canundolatepass = $canundolatepassP || $canundolatepassR; 
	   
	   }
	}
	$canuselatepassP = false;
	$canuselatepassR = false;
	if ($line['allowlate']>0 && $latepasses>0 && !isset($sessiondata['stuview'])) {
	   $allowlaten = $line['allowlate']%10;
	   $allowlateon = floor($line['allowlate']/10)%10;
	   if ($allowlateon != 3 && $line['postby']<2000000000 && ($allowlaten==1 || $allowlaten-1>$latepasscntP)) { //it allows post LPs, and can use latepases
		   if ($line['allowlate']>=100 && ($now - $line['postby'])<$latepasshrs*3600) { //allow after due date
			   $canuselatepassP = true;
		   } else if ($line['allowlate']<100 && $now < $line['postby']) {
			   $canuselatepassP = true;
		   }
	   }
	   if ($allowlateon != 2 && $line['replyby']<2000000000&& ($allowlaten==1 || $allowlaten-1>$latepasscntR)) { //it allows replies LPs
		   if ($line['allowlate']>=100 && ($now - $line['replyby'])<$latepasshrs*3600) { //allow after due date
			   $canuselatepassR = true;
		   } else if ($line['allowlate']<100 && $now < $line['replyby']) {
			   $canuselatepassR = true;
		   }
	   }
	}
	return array($canundolatepassP && $canundolatepass, $canundolatepassR && $canundolatepass, $canundolatepass, $canuselatepassP, $canuselatepassR, $line['postby'], $line['replyby'], $line['enddate']); 
}
