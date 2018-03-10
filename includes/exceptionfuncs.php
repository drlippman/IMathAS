<?php

class ExceptionFuncs {
	//Exception Handling functions for determining whether to use exception
	// due dates, and whether latepasses can be used on an assessment
	private $viewedassess = null;
	private $timelimitup = null;
	private $uid = null;
	private $cid = null;
	private $latepasses = 0;
	private $latepasshrs = 24;
	private $isstu = true;
	private $courseenddate = 2000000000;

	function __construct($uid, $cid, $isstu, $latepasses=0, $latepasshrs=24) {
		$this->uid = $uid;
		$this->cid = $cid;
		$this->latepasses = $latepasses;
		$this->latepasshrs = $latepasshrs;
		$this->isstu = $isstu;  // !isset($sessiondata['stuview']) && !$actas
		$this->courseenddate = $GLOBALS['courseenddate'];
	}
	public function setLatepasses($lp) {
		$this->latepasses = $lp;
	}
	public function setLatepasshrs($lph) {
		$this->latepasshrs = $lph;
	}

	//get which assessments have expired timelimits
	private function getTimesUsed() {
		global $DBH;
		$this->timelimitup = array();
		if (!$this->isstu) {
			return;
		}
		$query = 'SELECT ia.id,ias.starttime,ia.timelimit FROM imas_assessment_sessions AS ias ';
		$query .= 'JOIN imas_assessments AS ia ON ias.assessmentid=ia.id AND ia.timelimit<>0 ';
		$query .= 'WHERE ias.userid=? AND ia.courseid=?';
		$stm = $DBH->prepare($query);
		$stm->execute(array($this->uid, $this->cid));
		$now = time();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($now - $row['starttime'] > abs($row['timelimit'])) {
				$this->timelimitup[] = $row['id'];
			}
		}
	}

	//get viewedassess
	private function getViewedAssess() {
		global $DBH;
		$this->viewedassess = array();
		if (!$this->isstu) {
			return;
		}
		$stm = $DBH->prepare("SELECT typeid FROM imas_content_track WHERE courseid=:courseid AND userid=:userid AND (type='gbviewasid' OR type='assessreview')");
		$stm->execute(array(':courseid'=>$this->cid, ':userid'=>$this->uid));
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
			$this->viewedassess[] = $r[0];
		}
	}

	//determine time limit status of an assessment:
	// returns: noissue, started, expired
	public function getTimelimitStatus($aid) {
		global $DBH;
		$query = 'SELECT ias.starttime,ia.timelimit FROM imas_assessment_sessions AS ias ';
		$query .= 'JOIN imas_assessments AS ia ON ias.assessmentid=ia.id ';
		$query .= 'WHERE ias.userid=? AND ia.id=?';
		$stm = $DBH->prepare($query);
		$stm->execute(array($this->uid, $aid));
		$now = time();
		if ($stm->rowCount()==0) {
			return 'noissue';
		} else {
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			if ($row['timelimit']==0) {
				return 'noissue';
			} else if ($now - $row['starttime'] > abs($row['timelimit'])) {
				return 'expired';
			} else {
				return 'started';
			}
		}
	}

	//$exception should be from imas_exceptions, and be null, or
	//   array(startdate,enddate,islatepass,is_lti)
	//$adata should be associative array from imas_assessments including
	//   startdate, enddate, allowlate, id
	//returns array(useexception, canundolatepass, canuselatepass)
	public function getCanUseAssessException($exception, $adata, $limit=false) {
		$now = time();
		$canuselatepass = false;
		$canundolatepass = false;

		$useexception = ($exception!==null && $exception!==false); //use by default
		if ($exception!==null && $exception!==false && !empty($exception[3])) {
			//is LTI-set - use the exception
			
		} else if ($exception!==null && $exception[2]>0 && ($adata['enddate']>$exception[1] || $exception[1]>$this->courseenddate)) {
			//if latepass and assessment enddate is later than exception enddate, skip exception
			//or, if latepass and exception would put it past the course end date, skip exception
			$useexception = false;
		} else if ($exception!==null && $exception!==false && $exception[2]==0 && $exception[0]>=$adata['startdate'] && $adata['enddate']>$exception[1]) {
			//if manual exception and start of exception is equal or after original startdate and asessment enddate is later than exception enddate, skip exception
			//presumption: exception was made to extend due date, not start assignment early
			$useexception = false;
		}
		if (!$limit) {
			if ($useexception && $exception[2]>0 && ($now < $adata['enddate'] || $exception[1] > $now + $this->latepasshrs*60*60)) {
				$canundolatepass = true;
			}
			if ($useexception) {
				//this logic counts "latepasses used" based on date of exception past original enddate
				//regardless of whether exception is manual or latepass
				//prevents using latepasses on top of a manual extension
				if (!empty($exception[3])) {
					//with LTI one, base latepasscnt only on the value in the exception
					$latepasscnt = $exception[2];
				} else {
					$latepasscnt = max(0,round(($exception[1] - $adata['enddate'])/($this->latepasshrs*3600)));
				}
				//use exception due date for determining canuselatepass
				$adata['enddate'] = $exception[1];
			} else {
				//if not using exception, use latepasscnt as actual number of latepasses used, or 0
				if ($exception!==null) {
					$latepasscnt = 0;//max(0,$exception[2]);
				} else {
					$latepasscnt = 0;
				}
			}

			$canuselatepass = $this->getCanUseAssessLatePass($adata, $latepasscnt);

			return array($useexception, $canundolatepass, $canuselatepass);
		} else {
			return $useexception;
		}

	}

	//get if latepass can be used.
	// Typically used if exception doesn't already exist, called without second parameter
	// Also called internally from getCanUseAssessException using second param
	public function getCanUseAssessLatePass($adata, $latepasscnt = 0) {
		$now = time();
		$canuselatepass = false;
		if ($this->viewedassess===null) {
			$this->getViewedAssess();
		}
		/*
		This code could be used to hide the "Use LatePass" link if the time limit
		is expired.  Removed in favor of giving an explanation on the redeem
		latepass page

		if ($this->timelimitup===null) {
			$this->getTimesUsed();
		}

		removed from below:
			 && !in_array($adata['id'],$this->timelimitup)
		*/

		if (($adata['allowlate']%10==1 || $adata['allowlate']%10-1>$latepasscnt) && !in_array($adata['id'],$this->viewedassess) && $this->latepasses>0 && $this->isstu) {
			if ($now>$adata['enddate'] && $adata['allowlate']>10 && ($now - $adata['enddate']) < $this->latepasshrs*3600 && $adata['enddate'] < $this->courseenddate) {
				$canuselatepass = true;
			} else if ($now<$adata['enddate'] && $adata['enddate'] < $this->courseenddate) {
				$canuselatepass = true;
			}
		}
		return $canuselatepass;
	}


	//$exception should be from imas_exceptions, and be null, or
	//   array(startdate,enddate,islatepass,waivereqscore,itemtype)
	//$line should be from imas_forums, and be assoc array including keys
	//   replyby, postby, enddate, allowlate
	//returns array($canundolatepassP, $canundolatepassR, $canundolatepass,
	//                 $canuselatepassP, $canuselatepassR,
	//		   new postby, new replyby, new enddate)
	public function getCanUseLatePassForums($exception, $line) {
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
			   if ($exception[2]>0 && ($now < $line['postby'] || $exception[0] > $now + $this->latepasshrs*60*60)) {
				   $canundolatepassP = true;
			   }
			   if ($exception[2]>0) {
				   $latepasscntP = max(0,round(($exception[0] - $line['postby'])/($this->latepasshrs*3600)));
			   }
			   $line['postby'] = $exception[0];
			   if ($line['postby']>$line['enddate']) { //extend enddate if needed to accomodate postby exception
				   $line['enddate'] = $line['postby'];
			   }
		   }
		   if (($exception[4]=='R' || $exception[4]=='F') && $exception[1]>0) {
			   //if latepass and it's before original due date or exception is for more than a latepass past now
			   if ($exception[2]>0 && ($now < $line['replyby'] || $exception[1] > $now + $this->latepasshrs*60*60)) {
				   $canundolatepassR = true;
			   }
			   if ($exception[2]>0) {
				   $latepasscntR = max(0,round(($exception[1] - $line['replyby'])/($this->latepasshrs*3600)));
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
		if ($line['allowlate']>0 && $this->latepasses>0 && $this->isstu) {
		   $allowlaten = $line['allowlate']%10;
		   $allowlateon = floor($line['allowlate']/10)%10;
		   if ($allowlateon != 3 && $line['postby']<2000000000 && ($allowlaten==1 || $allowlaten-1>$latepasscntP)) { //it allows post LPs, and can use latepases
			   if ($line['allowlate']>=100 && ($now - $line['postby'])<$this->latepasshrs*3600) { //allow after due date
				   $canuselatepassP = true;
			   } else if ($line['allowlate']<100 && $now < $line['postby']) {
				   $canuselatepassP = true;
			   }
		   }
		   if ($allowlateon != 2 && $line['replyby']<2000000000&& ($allowlaten==1 || $allowlaten-1>$latepasscntR)) { //it allows replies LPs
			   if ($line['allowlate']>=100 && ($now - $line['replyby'])<$this->latepasshrs*3600) { //allow after due date
				   $canuselatepassR = true;
			   } else if ($line['allowlate']<100 && $now < $line['replyby']) {
				   $canuselatepassR = true;
			   }
		   }
		}
		return array($canundolatepassP && $canundolatepass, $canundolatepassR && $canundolatepass, $canundolatepass, $canuselatepassP, $canuselatepassR, $line['postby'], $line['replyby'], $line['enddate']);
	}
}
