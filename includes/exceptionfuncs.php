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
		if ($latepasshrs==0) {
			$latepasshrs = 1e-9;
		}
		$this->uid = $uid;
		$this->cid = $cid;
		$this->latepasses = $latepasses;
		$this->latepasshrs = $latepasshrs;
		$this->isstu = $isstu;  // !isset($_SESSION['stuview']) && !$actas
		$this->courseenddate = $GLOBALS['courseenddate'];
	}
	public function setLatepasses($lp) {
		$this->latepasses = $lp;
	}
	public function setLatepasshrs($lph) {
		if ($lph==0) {
			$lph = 1e-9;
		}
		$this->latepasshrs = $lph;
	}

	public function calcLPneeded($end) {
		$now = time();
		$latepassesNeededToExtend = ceil(($now - $end)/($this->latepasshrs*3600) - .0001);
		//adjust for possible off-by-one due to DST
		if ($now < strtotime("+".($this->latepasshrs*($latepassesNeededToExtend-1))." hours", $end)) { //are OK with one less
			$latepassesNeededToExtend--;
		} else if ($now < strtotime("+".($this->latepasshrs*$latepassesNeededToExtend)." hours", $end)) { //calculated # works

		} else { //really need 1 more
			$latepassesNeededToExtend++;
		}
		return $latepassesNeededToExtend;
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
		$stm = $DBH->prepare("SELECT typeid,type FROM imas_content_track WHERE courseid=:courseid AND userid=:userid AND (type='gbviewasid' OR type='gbviewassess' OR type='assessreview')");
		$stm->execute(array(':courseid'=>$this->cid, ':userid'=>$this->uid));
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
            $type = ($r[1] == 'assessreview') ? 'review' : 'gb';
            if (!isset($this->viewedassess[$r[0]]) || $type == 'gb') {
                $this->viewedassess[$r[0]] = $type;
            }
		}
	}

	//determine time limit status of an assessment:
	// returns: noissue, started, expired
	public function getTimelimitStatus($aid, $ver) {
		global $DBH;
		if ($ver > 1) {
			$query = 'SELECT iar.timelimitexp,iar.status,ia.timelimit FROM imas_assessment_records AS iar ';
			$query .= 'JOIN imas_assessments AS ia ON iar.assessmentid=ia.id ';
			$query .= 'WHERE iar.userid=? AND ia.id=?';
			$stm = $DBH->prepare($query);
			$stm->execute(array($this->uid, $aid));
			if ($stm->rowCount()==0) {
				return 'noissue';
			} else {
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				if (($row['status']&32)==32) {
					return 'outofattempts';
				} else if ($row['timelimit']==0 || ($row['status']&3)==0) {
					return 'noissue';
				} else if ($row['timelimitexp'] > 0 && $now > $row['timelimitexp']) {
					return 'expired';
				} else {
					return 'started';
				}
			}
		} else {
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
	}

	//$exception should be from imas_exceptions, and be null, or
	//   array(startdate,enddate,islatepass,is_lti)
	//$adata should be associative array from imas_assessments including
	//   startdate, enddate, allowlate, id, LPcutoff
	//returns normally array(useexception, canundolatepass, canuselatepass)
	//if getcanusereason is set, returns array(useexception, canusereason)
	//if limit is set, just returns useexception
	public function getCanUseAssessException($exception, $adata, $limit=false, $getcanusereason=false) {
		$now = time();
		$canuselatepass = false;
        $canundolatepass = false;
        
        if (isset($adata['original_enddate'])) {
            // exception already applied. For calculations here we want to use the original enddate
            $adata['enddate'] = $adata['original_enddate'];
        }

		$useexception = ($exception!==null && $exception!==false); //use by default
		if ($useexception && !empty($exception[3])) {
			//is LTI-set - use the exception

		} else if ($useexception && $exception[2]>0 && $exception[0]>=$adata['startdate'] && ($adata['enddate']>$exception[1] || $exception[1]>$this->courseenddate)) {
			//if latepass and assessment enddate is later than exception enddate, skip exception
			//or, if latepass and exception would put it past the course end date, skip exception
			$useexception = false;
		} else if ($useexception && $exception[2]==0 && $exception[0]>=$adata['startdate'] && $adata['enddate']>$exception[1]) {
			//if manual exception and start of exception is equal or after original startdate and asessment enddate is later than exception enddate, skip exception
			//presumption: exception was made to extend due date, not start assignment early
			$useexception = false;
		}
		if (!$limit) {
			if ($useexception && $exception[2]>0 && ($now < $adata['enddate'] || $exception[1] > strtotime("+".$this->latepasshrs." hours", $now))) {
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
			if ($getcanusereason) {
				$canuselatepass = $this->getCanUseAssessLatePass($adata, $latepasscnt, true);
				return array($useexception, $canuselatepass);
			} else {
                $canuselatepass = $this->getCanUseAssessLatePass($adata, $latepasscnt);
				return array($useexception, $canundolatepass, $canuselatepass);
			}
		} else {
			return $useexception;
		}

	}

	//get if latepass could be used if viewedassess was cleared
	// latepasscnt is number of latepasses already used
	public function getLatePassBlockedByView($adata, $latepasscnt = 0) {
		$now = time();
		//not blocked if before due date, no latepasses, or not allowed after
		if ($now < $adata['enddate'] || $this->latepasses == 0 || $adata['allowlate']<10) {
			return false;
		}
		if ($this->viewedassess===null) {
			$this->getViewedAssess();
		}
		$LPusereason = $this->getCanUseAssessLatePass($adata, $latepasscnt, true);
		if ($LPusereason == 1 || $LPusereason > 6) { // 7 is review block, 8 is gb block
			return true;
		} else {
			return false;
		}
	}

	//get if latepass can be used.
	// Typically used if exception doesn't already exist, called without second parameter
	// Also called internally from getCanUseAssessException using second param
	// latepasscnt is number of latepasses already used
	public function getCanUseAssessLatePass($adata, $latepasscnt = 0, $getreason=false) {
		if ($this->latepasses == 0) { // no latepasses to use; no need to check further
			return false;
		}
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
        //**FIX/check

		if ($adata['allowlate']%10==1) {
			$latepassesAllowed = 10000000;  //unlimited
		} else {
			$latepassesAllowed = $adata['allowlate']%10-1;
		}
		if (!isset($adata['LPcutoff']) || $adata['LPcutoff']<$adata['enddate']) { //ignore nonsensical LPcutoff
			$adata['LPcutoff'] = 0;
        }
        /* old version
		if (($skipViewedCheck || !isset($this->viewedassess[$adata['id']])) &&
			($adata['LPcutoff']==0 || $now<$adata['LPcutoff']) &&
			$this->isstu && $adata['enddate'] < $this->courseenddate) { //basic checks
			if ($now<$adata['enddate'] && $latepassesAllowed > $latepasscnt) { //before due date and use is allowed
				$canuselatepass = true;
			} else if ($now>$adata['enddate'] && $adata['allowlate']>10) { //after due date and allows use after due date
				$latepassesNeededToExtend = $this->calcLPneeded($adata['enddate']);
				if ($latepassesAllowed >= $latepasscnt + $latepassesNeededToExtend && $latepassesNeededToExtend<=$this->latepasses) {
					$canuselatepass = true;
				}
			}
        }
        */
        if ($now>$adata['enddate'] && $adata['allowlate']>10) { //after due date and allows use after due date
            $latepassesNeededToExtend = $this->calcLPneeded($adata['enddate']);
        } else {
            $latepassesNeededToExtend = 1;
        }
        // determine reason for blocking
        if ($adata['allowlate'] == 0) {
            $canuselatepass = 0; //not enabled
        } else if ($adata['enddate'] > $this->courseenddate) {
            $canuselatepass = 3; // past course enddate
        } else if ($now>$adata['enddate'] && $adata['allowlate']<10) {
            $canuselatepass = 4; // past due date, and LP only allowed before
        } else if ($adata['LPcutoff']>0 && $now>$adata['LPcutoff']) {
            $canuselatepass = 2; // past LP cutoff
        } else if ($latepassesAllowed < $latepasscnt + $latepassesNeededToExtend) {
            $canuselatepass = 5; // due date too far past based on allowed latepasses
        } else if ($latepassesNeededToExtend>$this->latepasses) {
            $canuselatepass = 6; // student doesn't have enough latepasses
        } else if (isset($this->viewedassess[$adata['id']]) && $this->viewedassess[$adata['id']]=='review') {
            $canuselatepass = 7; // practice mode block
        } else if (isset($this->viewedassess[$adata['id']]) && $this->viewedassess[$adata['id']]=='gb') {
            $canuselatepass = 8; // gb view block
        } else {
            $canuselatepass = 1; // can use
        }
        //print_r($this->viewedassess);
        //print_r($adata);
        if (!$getreason) {
            $canuselatepass = ($canuselatepass == 1);
        }
		/**old old version

		//replaced ($now - $adata['enddate']) < $this->latepasshrs*3600
		// $now < $adata['enddate'] + $this->latepasshrs*3600
		// $now < strtotime("+".$this->latepasshrs." hours", $adata['enddate'])

		if (($adata['allowlate']%10==1 || $adata['allowlate']%10-1>$latepasscnt) && !in_array($adata['id'],$this->viewedassess) && $this->latepasses>0 && $this->isstu) {
			if ($now>$adata['enddate'] && $adata['allowlate']>10 && $now < strtotime("+".$this->latepasshrs." hours", $adata['enddate']) && $adata['enddate'] < $this->courseenddate) {
				$canuselatepass = true;
			} else if ($now<$adata['enddate'] && $adata['enddate'] < $this->courseenddate) {
				$canuselatepass = true;
			}
		}
		*/
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
			   if ($exception[2]>0 && ($now < $line['postby'] || $exception[0] > strtotime("+".$this->latepasshrs." hours", $now))) {
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
			   if ($exception[2]>0 && ($now < $line['replyby'] || $exception[1] > strtotime("+".$this->latepasshrs." hours", $now))) {
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
		   //$allowlaten = $line['allowlate']%10;
		   if ($line['allowlate']%10 == 1) { //unlimited
		   	   $latepassesAllowed = 10000000;
		   } else {
		   	   $latepassesAllowed =  $line['allowlate']%10 - 1;
		   }
		   $allowlateon = floor($line['allowlate']/10)%10;  //0: both, 2: posts only, 3: replies only

		   if ($allowlateon != 3  && $line['postby']<2000000000) { //it allows post LPs
		   	if ($now < $line['postby'] && $latepassesAllowed > $latepasscntP) { //before due date and use is allowed
		   		$canuselatepassP = true;
		   	} else if ($now > $line['postby'] && $line['allowlate']>=100) { //after due date and allows use after due date
		   		$latepassesNeededToExtend = $this->calcLPneeded($line['postby']);
				if ($latepassesAllowed >= $latepasscntP + $latepassesNeededToExtend && $latepassesNeededToExtend<=$this->latepasses) {
					$canuselatepassP = true;
				}
		   	}
		   }
		   if ($allowlateon != 2  && $line['replyby']<2000000000) { //it allows reply LPs
		   	if ($now < $line['replyby'] && $latepassesAllowed > $latepasscntR) { //before due date and use is allowed
		   		$canuselatepassR = true;
		   	} else if ($now > $line['replyby'] && $line['allowlate']>=100) { //after due date and allows use after due date
		   		$latepassesNeededToExtend = $this->calcLPneeded($line['replyby']);
				if ($latepassesAllowed >= $latepasscntR + $latepassesNeededToExtend && $latepassesNeededToExtend<=$this->latepasses) {
					$canuselatepassR = true;
				}
		   	}
		   }
		   /*
		   old code
		   if ($allowlateon != 3 && $line['postby']<2000000000 && ($allowlaten==1 || $allowlaten-1>$latepasscntP)) { //it allows post LPs, and can use latepases
			   if ($line['allowlate']>=100 && $now < strtotime("+".$this->latepasshrs." hours", $line['postby'])) { //allow after due date
				   $canuselatepassP = true;
			   } else if ($line['allowlate']<100 && $now < $line['postby']) {
				   $canuselatepassP = true;
			   }
		   }
		   if ($allowlateon != 2 && $line['replyby']<2000000000&& ($allowlaten==1 || $allowlaten-1>$latepasscntR)) { //it allows replies LPs
			   if ($line['allowlate']>=100 && $now < strtotime("+".$this->latepasshrs." hours", $line['replyby'])) { //allow after due date
				   $canuselatepassR = true;
			   } else if ($line['allowlate']<100 && $now < $line['replyby']) {
				   $canuselatepassR = true;
			   }
		   }
		   */
		}
		return array($canundolatepassP && $canundolatepass, $canundolatepassR && $canundolatepass, $canundolatepass, $canuselatepassP, $canuselatepassR, $line['postby'], $line['replyby'], $line['enddate']);
	}
}
