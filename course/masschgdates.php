<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require_once("../includes/TeacherAuditLog.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
//$pagetitle = "Manage Student Groups";
//$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" . $_GET['cid'] . "\">$coursename</a> ";


if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else {
	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($_POST['chgcnt'])) {
		$metadata = array();

		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		$cnt = $_POST['chgcnt'];
		$blockchg = 0;

		$assessbasictoupdate = array();
		$assessfulltoupdate = array();
		$inlinetoupdate = array();
		$wikitoupdate = array();
		$linktoupdate = array();
		$forumbasictoupdate = array();
		$forumfulltoupdate = array();
		$fullassess = false;
		$fullforum = false;
		$imas_assessments = [];
		$imas_forums = [];
		$imas_wikis = [];
		$imas_inlinetext = [];
		$imas_linkedtext = [];

		$select = "SELECT id, startdate, enddate, avail ";
		// imas_assessments
		$stm = $DBH->prepare($select . ", reviewdate, LPcutoff FROM imas_assessments WHERE courseid=:id");
		$stm->execute(array(':id'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$imas_assessments[$row['id']] = $row;
		}
		// imas_forums
		$stm = $DBH->prepare($select . ", postby, replyby FROM imas_forums WHERE courseid=:id");
		$stm->execute(array(':id'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$imas_forums[$row['id']] = $row;
		}
		// imas_wikis
		$stm = $DBH->prepare($select . " FROM imas_wikis WHERE courseid=:id");
		$stm->execute(array(':id'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$imas_wikis[$row['id']] = $row;
		}
		// imas_inlinetext
		$stm = $DBH->prepare($select . " FROM imas_inlinetext WHERE courseid=:id");
		$stm->execute(array(':id'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$imas_inlinetext[$row['id']] = $row;
		}
		// imas_linkedtext
		$stm = $DBH->prepare($select . " FROM imas_linkedtext WHERE courseid=:id");
		$stm->execute(array(':id'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$imas_linkedtext[$row['id']] = $row;
		}
		$existingIds = [
			'Assessment' => array_keys($imas_assessments),
			'Forum' => array_keys($imas_forums),
			'Wiki' => array_keys($imas_wikis),
			'InlineText' => array_keys($imas_inlinetext),
			'Link' => array_keys($imas_linkedtext)
		];

		for ($i=0; $i<$cnt; $i++) {
			require_once("../includes/parsedatetime.php");

			$data = explode(',',$_POST['data'.$i]);

			if ($data[0] == '0') {
				$startdate = 0;
			} else {
				$pts = explode('~',$data[0]);
				$startdate = parsedatetime($pts[0],$pts[1],0);
			}
			/*
			if ($_POST['sdatetype'.$i]=='0') {
				$startdate = 0;
			} else {
				$startdate = parsedatetime($_POST['sdate'.$i],$_POST['stime'.$i]);
			}
			*/

			if ($data[1] == '2000000000') {
				$enddate = 2000000000;
			} else {
				$pts = explode('~',$data[1]);
				$enddate = parsedatetime($pts[0],$pts[1],2000000000);
			}
			/*
			if ($_POST['edatetype'.$i]=='0') {
				$enddate = 2000000000;
			} else {
				$enddate = parsedatetime($_POST['edate'.$i],$_POST['etime'.$i]);
			}
			*/

			if ($data[2] != 'NA') {
				if ($data[2]=='A') {
					$reviewdate = 2000000000;
				} else if ($data[2] == 'N') {
					$reviewdate = 0;
				} else {
					$pts = explode('~',$data[2]);
					$reviewdate = parsedatetime($pts[0],$pts[1],2000000000);
				}
			}
			if ($data[3] != 'NA') {
				if ($data[3]=='A') {
					$fpdate = 2000000000;
				} else if ($data[3] == 'N') {
					$fpdate = 0;
				} else {
					$pts = explode('~',$data[3]);
					$fpdate = parsedatetime($pts[0],$pts[1],2000000000);
				}
			}
			if ($data[4] != 'NA') {
				if ($data[4]=='A') {
					$frdate = 2000000000;
				} else if ($data[4] == 'N') {
					$frdate = 0;
				} else {
					$pts = explode('~',$data[4]);
					$frdate = parsedatetime($pts[0],$pts[1],2000000000);
				}
			}
			/*
			if (isset($_POST['rdatetype'.$i])) {
				if ($_POST['rdatetype'.$i]=='0') {
					$reviewdate = $_POST['rdatean'.$i];
				} else {
					$reviewdate = parsedatetime($_POST['rdate'.$i],$_POST['rtime'.$i]);
				}
			}
			*/
			if ($data[5] != 'NA') {
				if ($data[5]=='N') {
					$lpdate = 0;
				} else {
					$pts = explode('~',$data[5]);
					$lpdate = parsedatetime($pts[0],$pts[1],0);
					if ($lpdate <= $enddate) {
						$lpdate = 0;
					}
				}
			}

			$type = $data[6]; // $_POST['type'.$i];
			$id = $data[7]; // $_POST['id'.$i];
			$avail = intval($data[8]);

			$new = [
				'id' => $id,
				'startdate' => $startdate,
				'enddate' => $enddate,
				'avail' =>$avail
			];

			// check it's ok
			if ($type != 'Block') {
				if (!in_array(intval($id), $existingIds[$type])) {
					continue;
				}
			}
			$logChange = false;
			if ($type=='Assessment') {
				if ($id>0) {
					$old = $imas_assessments[$id];
					if ($data[2] != 'NA' && $data[5] != 'NA') {
						$new['reviewdate'] = $reviewdate;
						$new['LPcutoff'] = $lpdate;
						if ($old != $new) {
							$logChange = true;
							array_push($assessfulltoupdate, $id, $startdate, $enddate, $reviewdate, $lpdate, $avail);
						}
					} else {
						unset($old['reviewdate'], $old['LPcutoff']);
						if ($old != $new) {
							$logChange = true;
							array_push($assessbasictoupdate, $id, $startdate, $enddate, $avail);
						}
					}
				}
			} else if ($type=='Forum') {
				if ($id>0) {
					$old = $imas_forums[$id];
					if ($data[3] != 'NA' && $data[4] != 'NA') {
						$new['postby'] = $fpdate;
						$new['replyby'] = $frdate;
						if ($old != $new) {
							$logChange = true;
							array_push($forumfulltoupdate, $id, $startdate, $enddate, $avail, $fpdate, $frdate);
						}
					} else {
						unset($old['postby'], $old['replyby']);
						if ($old != $new) {
							$logChange = true;
							array_push($forumbasictoupdate, $id, $startdate, $enddate, $avail);
						}
					}
				}
			} else if ($type=='Wiki') {
				if ($id>0) {
					$old = $imas_wikis[$id];
					if ($old != $new) {
						$logChange = true;
						array_push($wikitoupdate, $id, $startdate, $enddate, $avail);
					}
				}
			} else if ($type=='InlineText') {
				if ($id>0) {
					$old = $imas_inlinetext[$id];
					if ($old != $new) {
						$logChange = true;
						array_push($inlinetoupdate, $id, $startdate, $enddate, $avail);
					}
				}
			} else if ($type=='Link') {
				if ($id>0) {
					$old = $imas_linkedtext[$id];
					if ($old != $new) {
						$logChange = true;
						array_push($linktoupdate, $id, $startdate, $enddate, $avail);
					}
				}
			} else if ($type=='Block') {
				$blocktree = explode('-',$id);
				$sub =& $items;
				if (count($blocktree)>1) {
					for ($j=1;$j<count($blocktree)-1;$j++) {
						$sub =& $sub[$blocktree[$j]-1]['items']; //-1 to adjust for 1-indexing
					}
				}
				$sub =& $sub[$blocktree[$j]-1];
				$old = [
					'id' => $id,
					'startdate' => $sub['startdate'],
					'enddate' => $sub['enddate'],
					'avail' => $sub['avail']
				];
				if ($new != $old) {
					$logChange = true;
					$sub['startdate'] = $startdate;
					$sub['enddate'] = $enddate;
					$sub['avail'] = $avail;
					$blockchg++;
				}
			}
			if ($logChange) {
				foreach ($old as $column => $value) {
					if ($old[$column] != $new[$column]) {
						$metadata[$type][$id][$column]['old'] = $old[$column];
						$metadata[$type][$id][$column]['new'] = $new[$column];
					}
				}
			}

		}
		if (count($assessbasictoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($assessbasictoupdate, 4);
			$query = "INSERT INTO imas_assessments (id,startdate,enddate,avail) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),avail=VALUES(avail)";
			$stm = $DBH->prepare($query);
			$stm->execute($assessbasictoupdate);
		}
		if (count($assessfulltoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($assessfulltoupdate, 6);
			$query = "INSERT INTO imas_assessments (id,startdate,enddate,reviewdate,LPcutoff,avail) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),reviewdate=VALUES(reviewdate),LPcutoff=VALUES(LPcutoff),avail=VALUES(avail)";
			$stm = $DBH->prepare($query);
			$stm->execute($assessfulltoupdate);
		}
		if (count($inlinetoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($inlinetoupdate, 4);
			$query = "INSERT INTO imas_inlinetext (id,startdate,enddate,avail) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),avail=VALUES(avail)";
			$stm = $DBH->prepare($query);
			$stm->execute($inlinetoupdate);
		}
		if (count($linktoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($linktoupdate, 4);
			$query = "INSERT INTO imas_linkedtext (id,startdate,enddate,avail) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),avail=VALUES(avail)";
			$stm = $DBH->prepare($query);
			$stm->execute($linktoupdate);
		}
		if (count($wikitoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($wikitoupdate, 4);
			$query = "INSERT INTO imas_wikis (id,startdate,enddate,avail) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),avail=VALUES(avail)";
			$stm = $DBH->prepare($query);
			$stm->execute($wikitoupdate);
		}
		if (count($forumbasictoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($forumbasictoupdate, 4);
			$query = "INSERT INTO imas_forums (id,startdate,enddate,avail) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),avail=VALUES(avail)";
			$stm = $DBH->prepare($query);
			$stm->execute($forumbasictoupdate);
		}
		if (count($forumfulltoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($forumfulltoupdate, 6);
			$query = "INSERT INTO imas_forums (id,startdate,enddate,avail,postby,replyby) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),avail=VALUES(avail),postby=VALUES(postby),replyby=VALUES(replyby)";
			$stm = $DBH->prepare($query);
			$stm->execute($forumfulltoupdate);
		}
		if ($blockchg>0) {
			$itemorder = serialize($items);
			$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
		}
		if (!empty($metadata) === true) {
			$result = TeacherAuditLog::addTracking(
				$cid,
				"Mass Date Change",
				null,
				$metadata
			);
		}

		$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid$btf" . "&r=" . Sanitize::randomQueryStringParam());

		exit;
	} else { //DEFAULT DATA MANIPULATION
		$pagetitle = "Mass Change Dates";
		$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/masschgdates.js?v=100319\"></script>";
		$placeinhead .= "<style>.show {display:inline;} \n .hide {display:none;} td.dis {color:#ccc;opacity:0.5;}\n td.dis input {color: #ccc;}</style>";
	}
}


/******* begin html output ********/


if ($overwriteBody==1) {
	require("../header.php");
	echo $body;
} else {

	$shortdays = array("Su","M","Tu","W","Th","F","Sa");
	function getshortday($atime) {
		global $shortdays;
		return $shortdays[tzdate('w',$atime)];
	}

	$availnames = array(_("Hidden"),_("By Dates"),_("Always"));

	if (isset($_GET['orderby'])) {
		$orderby = Sanitize::onlyInt($_GET['orderby']);
		$_SESSION['mcdorderby'.$cid] = $orderby;
	} else if (isset($_SESSION['mcdorderby'.$cid])) {
		$orderby = $_SESSION['mcdorderby'.$cid];
	} else {
		$orderby = 3;
	}
	if (isset($_GET['filter'])) {
		$filter = Sanitize::simpleString($_GET['filter']);
		$_SESSION['mcdfilter'.$cid] = $filter;
	} else if (isset($_SESSION['mcdfilter'.$cid])) {
		$filter = $_SESSION['mcdfilter'.$cid];
	} else {
		$filter = "all";
	}

//	}
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js?v=100319\"></script>";
	//$placeinhead .= '<style type="text/css">.mcind1 {padding-left: .9em; text-indent:-.5em;} .mcind2 {padding-left: 1.4em; text-indent:-1em;}
	//		.mcind3 {padding-left: 1.9em; text-indent:-1.5em; .mcind4 {padding-left: 2.4em; text-indent:-2em; .mcind5, mcind6 {padding-left: 2.9em; text-indent:-2.5em;}
	//		td {padding: .1em .4em;}</style>';
	$placeinhead .= '<style type="text/css">
			td {padding: .1em 4px;}
			.mcind1 {padding-left:20px} .mcind2 {padding-left:36px} .mcind3 {padding-left:52px;}
			.mcind4 {padding-left:66px;} .mcind5 {padding-left:84px;} mcind6 {padding-left:100px;}
			.mcind0 img, .mcind1 img, .mcind2 img, .mcind3 img, .mcind4 img, .mcind5 img, .mcind6 img {float: left;}
			.mcind0 div, .mcind1 div, .mcind2 div, .mcind3 div, .mcind4 div, .mcind5 div, .mcind6 div {margin-left: 21px;}
			</style>';
	if ($filter!='assessments') {
		$placeinhead .= '<style type="text/css">.mca {display:none;}</style>';
		$placeinhead .= '<script type="text/javascript">var includeassess = false;</script>';
	} else {
		$placeinhead .= '<script type="text/javascript">var includeassess = true;</script>';
	}
	if ($filter!='forums') {
		$placeinhead .= '<style type="text/css">.mcf {display:none;}</style>';
		$placeinhead .= '<script type="text/javascript">var includeforums = false;</script>';
	} else {
		$placeinhead .= '<script type="text/javascript">var includeforums = true;</script>';
	}

	require("../header.php");

	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; Mass Change Dates</div>\n";
	echo '<div id="headermasschgdates" class="pagetitle"><h1>Mass Change Dates</h1></div>';

	echo "<script type=\"text/javascript\">var filteraddr = \"$imasroot/course/masschgdates.php?cid=$cid&orderby=" . Sanitize::encodeUrlParam($orderby) . "\";";

	echo "var orderaddr = \"$imasroot/course/masschgdates.php?cid=$cid&filter=" . Sanitize::encodeUrlParam($filter) . "\";</script>";

	echo '<p>Order by: <select id="orderby" onchange="chgorderby()">';
	echo '<option value="0" ';
	if ($orderby==0) {echo 'selected="selected"';}
	echo '>Start Date</option>';
	echo '<option value="1" ';
	if ($orderby==1) {echo 'selected="selected"';}
	echo '>End Date</option>';
	echo '<option value="2" ';
	if ($orderby==2) {echo 'selected="selected"';}
	echo '>Name</option>';
	echo '<option value="3" ';
	if ($orderby==3) {echo 'selected="selected"';}
	echo '>Course page</option>';
	echo '</select> ';

	echo 'Filter by type: <select id="filter" onchange="filteritems()">';
	echo '<option value="all" ';
	if ($filter=='all') {echo 'selected="selected"';}
	echo '>All</option>';
	echo '<option value="assessments" ';
	if ($filter=='assessments') {echo 'selected="selected"';}
	echo '>Assessments</option>';
	echo '<option value="inlinetext" ';
	if ($filter=='inlinetext') {echo 'selected="selected"';}
	echo '>Inline Text</option>';
	echo '<option value="linkedtext" ';
	if ($filter=='linkedtext') {echo 'selected="selected"';}
	echo '>Linked Text</option>';
	echo '<option value="forums" ';
	if ($filter=='forums') {echo 'selected="selected"';}
	echo '>Forums</option>';
	echo '<option value="wikis" ';
	if ($filter=='wikis') {echo 'selected="selected"';}
	echo '>Wikis</option>';
	echo '<option value="blocks" ';
	if ($filter=='blocks') {echo 'selected="selected"';}
	echo '>Blocks</option>';
	echo '</select> ';
	if ($filter=='all' || $filter=='forums') {
		echo '<button type="button" id="MCDforumtoggle" onclick="toggleMCDincforum()">';
		if ($filter!='forums') {
			echo _('Show Forum Dates');
		} else {
			echo _('Hide Forum Dates');
		}
		echo '</button>';
	}
	if ($filter=='all' || $filter=='assessments') {
		echo '<button type="button" id="MCDassesstoggle" onclick="toggleMCDincassess()">';
		if ($filter!='assessments') {
			echo _('Show Assessment Dates');
		} else {
			echo _('Hide Assessment Dates');
		}
		echo '</button>';
	}
	echo '</p>';

	echo "<p><input type=checkbox id=\"onlyweekdays\" checked=\"checked\"> Shift by weekdays only</p>";
	echo "<p>Once changing dates in one row, you select <i>Send down date and time change</i> from the Action pulldown to send the date change ";
	echo "difference to all rows below.  You can select <i>Copy down time</i> or <i>Copy down date &amp; time</i>to copy the same time/date to all rows below.  ";
	echo "If you click the checkboxes on the left, you can limit the update to those items. ";
	echo "Click the <img src=\"$staticroot/img/swap.gif\" alt=\"Swap\"> icon in each cell to swap from ";
	echo "Always/Never to Dates.  Swaps to/from Always/Never and Show changes cannot be sent down the list, but you can use the checkboxes and the pulldowns to change those settings for many items at once.</p>";
	echo "<form id=\"qform\">";

	echo '<p>Check: <a href="#" onclick="return chkAllNone(\'qform\',\'all\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'all\',false)">None</a>. ';

	//echo '<p>Check/Uncheck All: <input type="checkbox" name="ca" value="1" onClick="chkAll(this.form, this.checked)"/>. ';
	echo 'Change selected items <select id="swaptype" onchange="chgswaptype(this)">
		<option value="a" selected>Show</option>
		<option value="s">Start Date</option>
		<option value="e">End Date</option>
		<option value="r">Review Date</option>
		<option value="lp">LatePass Cutoff</option>
		<option value="fp">Forum Post By</option>
		<option value="fp">Forum Reply By</option>
		</select>';
	echo ' to <select id="swapselected">
		<option value="0">Hidden</option>
		<option value="1">By Dates</option>
		<option value="2">Always/By Dates</option>
		</select>';
	echo ' <input type="button" value="Go" onclick="MCDtoggleselected(this.form)" /> &nbsp;';
	echo ' <button type="button" onclick="submittheform()">'._("Save Changes").'</button></p>';

	echo '<table class=gb><thead><tr><th></th><th>Name</th><th>Show</th><th>Start Date</th><th>End Date</th><th class=mca>Review</th><th class=mca>LatePass Cutoff</th><th class="mcf">Post By Date</th><th class="mcf">Reply By Date</th><th>Send Changes</th></thead><tbody>';
	$prefix = array();
	if ($orderby==3) {  //course page order
		$itemsassoc = array();
		$stm = $DBH->prepare("SELECT id,typeid,itemtype FROM imas_items WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$itemsassoc[$row[0]] = $row[2].$row[1];
		}
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$itemorder = unserialize($stm->fetchColumn(0));
		$itemsimporder = array();

		function flattenitems($items,&$addto,$parent,$pre) {
			global $itemsimporder,$itemsassoc,$prefix,$imasroot;
			foreach ($items as $k=>$item) {
				if (is_array($item)) {
					$addto[] = 'Block'.$parent.'-'.($k+1);
					$prefix['Block'.$parent.'-'.($k+1)] = $pre;
					flattenitems($item['items'],$addto,$parent.'-'.($k+1),$pre.' ');
				} else {
					$addto[] = $itemsassoc[$item];
					$prefix[$itemsassoc[$item]] = $pre;
				}
			}
		}
		flattenitems($itemorder,$itemscourseorder,'0','');
		$itemscourseorder = array_flip($itemscourseorder);
	}


	$names = array();
	$startdates = array();
	$enddates = array();
	$reviewdates = array();
	$LPcutoffs = array();
	$fpdates = array();
	$frdates = array();
	$ids = array();
	$avails = array();
	$types = array();
	$courseorder = array();
	$pres = array();
	if ($filter=='all' || $filter=='assessments') {
		$stm = $DBH->prepare("SELECT name,startdate,enddate,reviewdate,id,avail,LPcutoff FROM imas_assessments WHERE courseid=:courseid ");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$types[] = "Assessment";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = $row[3];
			$fpdates[] = -1; $frdates[] = -1;
			$ids[] = $row[4];
			$avails[] = $row[5];
			$LPcutoffs[] = $row[6]*1;
			if (isset($prefix['Assessment'.$row[4]])) {$pres[] = $prefix['Assessment'.$row[4]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['Assessment'.$row[4]];}
		}
	}
	if ($filter=='all' || $filter=='inlinetext') {
		$stm = $DBH->prepare("SELECT title,startdate,enddate,id,avail FROM imas_inlinetext WHERE courseid=:courseid ");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$types[] = "InlineText";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = -1;
			$fpdates[] = -1; $frdates[] = -1;
			$ids[] = $row[3];
			$avails[] = $row[4];
			$LPcutoffs[] = -1;
			if (isset($prefix['InlineText'.$row[3]])) {$pres[] = $prefix['InlineText'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['InlineText'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='linkedtext') {
		$stm = $DBH->prepare("SELECT title,startdate,enddate,id,avail FROM imas_linkedtext WHERE courseid=:courseid ");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$types[] = "Link";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = -1;
			$fpdates[] = -1; $frdates[] = -1;
			$ids[] = $row[3];
			$avails[] = $row[4];
			$LPcutoffs[] = -1;
			if (isset($prefix['LinkedText'.$row[3]])) {$pres[] = $prefix['LinkedText'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['LinkedText'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='forums') {
		$stm = $DBH->prepare("SELECT name,startdate,enddate,id,avail,postby,replyby FROM imas_forums WHERE courseid=:courseid ");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$types[] = "Forum";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = -1;
			$fpdates[] = $row[5];
			$frdates[] = $row[6];
			$ids[] = $row[3];
			$avails[] = $row[4];
			$LPcutoffs[] = -1;
			if (isset($prefix['Forum'.$row[3]])) {$pres[] = $prefix['Forum'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['Forum'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='wikis') {
		$stm = $DBH->prepare("SELECT name,startdate,enddate,id,avail FROM imas_wikis WHERE courseid=:courseid ");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$types[] = "Wiki";
			$names[] = $row[0];
			$startdates[] = $row[1];
			$enddates[] = $row[2];
			$reviewdates[] = -1;
			$fpdates[] = -1; $frdates[] = -1;
			$ids[] = $row[3];
			$avails[] = $row[4];
			$LPcutoffs[] = -1;
			if (isset($prefix['Wiki'.$row[3]])) {$pres[] = $prefix['Wiki'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['Wiki'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='blocks') {
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		function getblockinfo($items,$parent) {
			global $ids,$types,$names,$startdates,$enddates,$LPcutoffs,$reviewdates,$frdates,$fpdates,$ids,$itemscourseorder,$courseorder,$orderby,$avails,$pres,$prefix;
			foreach($items as $k=>$item) {
				if (is_array($item)) {
					$ids[] = $parent.'-'.($k+1);
					$types[] = "Block";
					if ($orderby==3) {$courseorder[] = $itemscourseorder['Block'.$parent.'-'.($k+1)];}
					$names[] = $item['name'];
					$startdates[] = $item['startdate'];
					$enddates[] = $item['enddate'];
					$avails[] = $item['avail'];
					$reviewdates[] = -1;
					$LPcutoffs[] = -1;
					$fpdates[] = -1; $frdates[] = -1;
					if (isset($prefix['Block'.$parent.'-'.($k+1)])) {$pres[] = $prefix['Block'.$parent.'-'.($k+1)];} else {$pres[] = '';}
					if (count($item['items'])>0) {
						getblockinfo($item['items'],$parent.'-'.($k+1));
					}
				}
			}
		}
		getblockinfo($items,'0');
	}

	$cnt = 0;
	$now = time();
	$hr = floor($coursedeftime/60)%12;
	$min = $coursedeftime%60;
	$am = ($coursedeftime<12*60)?'am':'pm';
	$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
	$hr = floor($coursedefstime/60)%12;
	$min = $coursedefstime%60;
	$am = ($coursedefstime<12*60)?'am':'pm';
	$defstime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;

	//figure out a "now" based on deftime
	$nowpts = explode('-', date("G-i-s", $now));
	$defnow  = $now - $nowpts[2] - $nowpts[1]*60 - $nowpts[0]*60*60 + 60*$coursedeftime;
	$defsnow  = $now - $nowpts[2] - $nowpts[1]*60 - $nowpts[0]*60*60 + 60*$coursedefstime;

	if ($orderby==0) {
		asort($startdates);
		$keys = array_keys($startdates);
	} else if ($orderby==1) {
		asort($enddates);
		$keys = array_keys($enddates);
	} else if ($orderby==2) {
		natcasesort($names);
		$keys = array_keys($names);
	} else if ($orderby==3) {
		asort($courseorder);
		$keys = array_keys($courseorder);
	}

	foreach ($keys as $i) {
		echo '<tr class=grid>';
		echo '<td>';
		echo "<input type=\"checkbox\" id=\"cb$cnt\" value=\"".strlen($pres[$i])."\" ";
		if ($types[$i]=='Block') {echo 'onchange="MCDselectblockgrp(this,'.strlen($pres[$i]).')"';}
		echo "/></td>";
		if ($filter=='all') {
			echo '<td class="mcind'.strlen($pres[$i]).' togdishid'.($avails[$i]==0?' dis':'').'">';
		} else {
			echo '<td class="togdishid'.($avails[$i]==0?' dis':'').'">';
		}
		echo "<input type=hidden id=\"type$cnt\" value=\"{$types[$i]}\"/>";
		echo '<img alt="'.$types[$i].'" title="'.$types[$i].'" src="'.$staticroot.'/img/';
		switch ($types[$i]) {
			case 'Calendar': echo $CFG['CPS']['miniicons']['calendar']; break;
			case 'InlineText': echo $CFG['CPS']['miniicons']['inline']; break;
			case 'Link': echo $CFG['CPS']['miniicons']['linked']; break;
			case 'Forum': echo $CFG['CPS']['miniicons']['forum']; break;
			case 'Wiki': echo $CFG['CPS']['miniicons']['wiki']; break;
			case 'Block': echo $CFG['CPS']['miniicons']['folder']; break;
			case 'Assessment': echo $CFG['CPS']['miniicons']['assess']; break;
			case 'Drill': echo $CFG['CPS']['miniicons']['drill']; break;
		}
		echo '"/><div>';

		$sdatebase = ($startdates[$i]==0)?$defsnow:$startdates[$i];
		$edatebase = ($enddates[$i]==2000000000)?(($startdates[$i]==0?$defnow:$sdatebase)+7*24*60*60):$enddates[$i];
		$lpdatebase = ($LPcutoffs[$i]==0)?$edatebase+7*24*60*60:$LPcutoffs[$i];
		$fpdatebase = ($fpdates[$i]==0 || $fpdates[$i]==2000000000)?$defnow:$fpdates[$i];
		$frdatebase = ($frdates[$i]==0 || $frdates[$i]==2000000000)?$defnow+7*24*60*60:$frdates[$i];

		echo Sanitize::encodeStringForDisplay($names[$i])."<input type=hidden id=\"id" . Sanitize::encodeStringForDisplay($cnt) . "\" value=\"" . Sanitize::encodeStringForDisplay($ids[$i]) . "\"/></div>";
		echo "<script> basesdates[$cnt] = ". Sanitize::onlyInt($sdatebase) . ";";
		echo "baseedates[$cnt] = ". Sanitize::onlyInt($edatebase) . ";";
		echo "baselpdates[$cnt] = ". Sanitize::onlyInt($lpdatebase) . ";";
		echo "baserdates[$cnt] = ". (($reviewdates[$i]==-1)?'"NA"':Sanitize::onlyInt($reviewdates[$i])) . ";";
		echo "basefpdates[$cnt] = ". (($fpdates[$i]==-1)?'"NA"':Sanitize::onlyInt($fpdatebase)) . ";";
		echo "basefrdates[$cnt] = ". (($frdates[$i]==-1)?'"NA"':Sanitize::onlyInt($frdatebase)) . ";";
		echo "</script>";
		echo "</td>";

		echo '<td><span class="nowrap"><img src="'.$staticroot.'/img/swap.gif" alt="Swap" onclick="MCDtoggle(\'a\','.$cnt.')"/><span id="availname'.Sanitize::encodeStringForDisplay($cnt).'">'.Sanitize::encodeStringForDisplay($availnames[$avails[$i]]).'</span><input type="hidden" id="avail'.Sanitize::encodeStringForDisplay($cnt).'" value="'.Sanitize::encodeStringForDisplay($avails[$i]).'"/></span></td>';

		echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\"><img src=\"$staticroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('s',$cnt)\"/>";
		if ($startdates[$i]==0) {
			echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"0\"/>";
		} else {
			echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"1\"/>";
		}

		echo '<span id="sspan0'.$cnt.'" class="'.($startdates[$i]==0?'show':'hide').'" onclick="MCDtoggle(\'s\','.$cnt.')">';
		echo _('Always').'</span>';

		echo '<span id="sspan1'.$cnt.'" class="'.($startdates[$i]==0?'hide':'show').'">';

		if ($startdates[$i]==0) {
			$sdate = tzdate("m/d/Y", $sdatebase);
			$stime = $defstime;
		} else {
			$sdate = tzdate("m/d/Y",$startdates[$i]);
			$stime = tzdate("g:i a",$startdates[$i]);
		}

		echo "<input type=text size=10 id=\"sdate$cnt\" name=\"sdate$cnt\" value=\"$sdate\" onblur=\"ob(this)\"/>(";
		echo "<span id=\"sd$cnt\">".getshortday($sdatebase).'</span>';
		//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].sdate$cnt,'anchor$cnt','MM/dd/yyyy',document.forms[0].sdate$cnt.value); return false;\" NAME=\"anchor$cnt\" ID=\"anchor$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		echo ") <a href=\"#\" onClick=\"displayDatePicker('sdate$cnt', this); return false\"><img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></a>";

		echo " at <input type=text size=8 id=\"stime$cnt\" name=\"stime$cnt\" value=\"$stime\">";
		echo '</span></td>';

		echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\"><img src=\"$staticroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('e',$cnt)\"/>";
		if ($enddates[$i]==2000000000) {
			echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"0\"/>";
		} else {
			echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"1\"/>";
		}

		echo '<span id="espan0'.$cnt.'" class="'.($enddates[$i]==2000000000?'show':'hide').'" onclick="MCDtoggle(\'e\','.$cnt.')">';
		echo _('Always').'</span>';

		echo '<span id="espan1'.$cnt.'" class="'.($enddates[$i]==2000000000?'hide':'show').'">';

		if ($enddates[$i]==2000000000) {
			$edate = tzdate("m/d/Y", $edatebase);
			$etime = $deftime;
		} else {
			$edate = tzdate("m/d/Y",$enddates[$i]);
			$etime = tzdate("g:i a",$enddates[$i]);
		}

		echo "<input type=text size=10 id=\"edate$cnt\" name=\"edate$cnt\" value=\"$edate\" onblur=\"ob(this)\"/>(";
		echo "<span id=\"ed$cnt\">".getshortday($edatebase).'</span>';
		//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].edate$cnt,'anchor2$cnt','MM/dd/yyyy',document.forms[0].edate$cnt.value); return false;\" NAME=\"anchor2$cnt\" ID=\"anchor2$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		echo ") <a href=\"#\" onClick=\"displayDatePicker('edate$cnt', this); return false\"><img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></a>";

		echo " at <input type=text size=8 id=\"etime$cnt\" name=\"etime$cnt\" value=\"$etime\">";
		echo '</span></td>';

		echo "<td class=\"mca togdis".($avails[$i]!=1?' dis':'')."\" onclick=\"MCDtoggle('r',$cnt)\">";
		if ($types[$i]=='Assessment') {
			echo "<img src=\"$staticroot/img/swap.gif\" alt=\"Swap\"/>";
			if ($reviewdates[$i]==0) {
				echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"0\"/>";
			} else {
				echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"1\"/>";
			}
			echo '<span id="rspan0'.$cnt.'" class="'.($reviewdates[$i]==0?'show':'hide').'">Never</span>';
			echo '<span id="rspan1'.$cnt.'" class="'.($reviewdates[$i]>0?'show':'hide').'">After Due</span>';
		}
		echo '</td>';
		echo "<td class=\"mca togdishid".($avails[$i]==0?' dis':'')."\">";
		if ($types[$i]=='Assessment') {
			echo "<img src=\"$staticroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('lp',$cnt)\"/>";
			if ($LPcutoffs[$i]==0) {
				echo "<input type=hidden id=\"lpdatetype$cnt\" name=\"lpdatetype$cnt\" value=\"0\"/>";
			} else {
				echo "<input type=hidden id=\"lpdatetype$cnt\" name=\"lpdatetype$cnt\" value=\"1\"/>";
			}
			if ($LPcutoffs[$i]==0) {
				$lpdate = tzdate("m/d/Y", $lpdatebase);
				$lptime = $deftime;
			} else {
				$lpdate = tzdate("m/d/Y",$LPcutoffs[$i]);
				$lptime = tzdate("g:i a",$LPcutoffs[$i]);
			}
			echo '<span id="lpspan0'.$cnt.'" class="'.($LPcutoffs[$i]==0?'show':'hide').'" onclick="MCDtoggle(\'lp\','.$cnt.')">';
			echo _('No limit').'</span>';

			echo '<span id="lpspan1'.$cnt.'" class="'.($LPcutoffs[$i]==0?'hide':'show').'">';
			echo "<input type=text size=10 id=\"lpdate$cnt\" name=\"lpdate$cnt\" value=\"$lpdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"lpd$cnt\">".getshortday($lpdatebase).'</span>';
			//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].rdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].rdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo ") <a href=\"#\" onClick=\"displayDatePicker('lpdate$cnt', this); return false\"><img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></a>";
			echo " at <input type=text size=8 id=\"lptime$cnt\" name=\"lptime$cnt\" value=\"$lptime\"></span>";
		}
		echo '</td>';
		echo "<td class=\"mcf togdishid".($avails[$i]==0?' dis':'')."\">";
		if ($types[$i]=='Forum') {
			echo "<img src=\"$staticroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('fp',$cnt)\"/>";
			if ($fpdates[$i]==0 || $fpdates[$i]==2000000000) {
				echo "<input type=hidden id=\"fpdatetype$cnt\" name=\"fpdatetype$cnt\" value=\"0\"/>";
			} else {
				echo "<input type=hidden id=\"fpdatetype$cnt\" name=\"fpdatetype$cnt\" value=\"1\"/>";
			}
			if ($fpdates[$i]==0 || $fpdates[$i]==2000000000) {
				echo "<span id=\"fpspan0$cnt\" class=\"show\">";
			} else {
				echo "<span id=\"fpspan0$cnt\" class=\"hide\">";
			}
			echo "<input type=radio name=\"fpdatean$cnt\" value=\"0\" id=\"fpdateanN$cnt\" ";
			if ($fpdates[$i]==0) {
				echo 'checked=1';
			}
			echo " />Never <input type=radio name=\"fpdatean$cnt\" value=\"2000000000\"  id=\"fpdateanA$cnt\"  ";
			if ($fpdates[$i]!=0) {
				echo 'checked=1';
			}
			echo " />Always</span>";

			if ($fpdates[$i]==0 || $fpdates[$i]==2000000000) {
				echo "<span id=\"fpspan1$cnt\" class=\"hide\">";
			} else {
				echo "<span id=\"fpspan1$cnt\" class=\"show\">";
			}
			if ($fpdates[$i]==0 || $fpdates[$i]==2000000000) {
				$fpdate = tzdate("m/d/Y",$fpdatebase);
				$fptime = $deftime;
			} else {
				$fpdate = tzdate("m/d/Y",$fpdates[$i]);
				$fptime = tzdate("g:i a",$fpdates[$i]);
			}

			echo "<input type=text size=10 id=\"fpdate$cnt\" name=\"fpdate$cnt\" value=\"$fpdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"fpd$cnt\">".getshortday($fpdatebase).'</span>';
			//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].fpdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].fpdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo ") <a href=\"#\" onClick=\"displayDatePicker('fpdate$cnt', this); return false\"><img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></a>";

			echo " at <input type=text size=8 id=\"fptime$cnt\" name=\"fptime$cnt\" value=\"$fptime\"></span>";
		}
		echo '</td>';
		echo "<td class=\"mcf togdishid".($avails[$i]==0?' dis':'')."\">";
		if ($types[$i]=='Forum') {
			echo "<img src=\"$staticroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('fr',$cnt)\"/>";
			if ($frdates[$i]==0 || $frdates[$i]==2000000000) {
				echo "<input type=hidden id=\"frdatetype$cnt\" name=\"frdatetype$cnt\" value=\"0\"/>";
			} else {
				echo "<input type=hidden id=\"frdatetype$cnt\" name=\"frdatetype$cnt\" value=\"1\"/>";
			}
			if ($frdates[$i]==0 || $frdates[$i]==2000000000) {
				echo "<span id=\"frspan0$cnt\" class=\"show\">";
			} else {
				echo "<span id=\"frspan0$cnt\" class=\"hide\">";
			}
			echo "<input type=radio name=\"frdatean$cnt\" value=\"0\" id=\"frdateanN$cnt\" ";
			if ($frdates[$i]==0) {
				echo 'checked=1';
			}
			echo " />Never <input type=radio name=\"frdatean$cnt\" value=\"2000000000\"  id=\"frdateanA$cnt\"  ";
			if ($frdates[$i]!=0) {
				echo 'checked=1';
			}
			echo " />Always</span>";

			if ($frdates[$i]==0 || $frdates[$i]==2000000000) {
				echo "<span id=\"frspan1$cnt\" class=\"hide\">";
			} else {
				echo "<span id=\"frspan1$cnt\" class=\"show\">";
			}
			if ($frdates[$i]==0 || $frdates[$i]==2000000000) {
				$frdate = tzdate("m/d/Y",$frdatebase);
				$frtime = $deftime;
			} else {
				$frdate = tzdate("m/d/Y",$frdates[$i]);
				$frtime = tzdate("g:i a",$frdates[$i]);
			}

			echo "<input type=text size=10 id=\"frdate$cnt\" name=\"frdate$cnt\" value=\"$frdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"frd$cnt\">".getshortday($frdatebase).'</span>';
			//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].frdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].frdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo ") <a href=\"#\" onClick=\"displayDatePicker('frdate$cnt', this); return false\"><img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></a>";

			echo " at <input type=text size=8 id=\"frtime$cnt\" name=\"frtime$cnt\" value=\"$frtime\"></span>";
		}
		echo '</td>';

		//echo "<td>Send Down: <a href=\"#\" <input type=button value=\"Change\" onclick=\"senddown($cnt)\"/> <input type=button value=\"Copy\" onclick=\"copydown($cnt)\"/></td>";
		echo '<td class="c"><div class="dropdown">';
		echo '<a tabindex=0 class="dropdown-toggle" id="dropdownMenu'.$cnt.'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		echo ' Action <img src="'.$staticroot.'/img/collapse.gif" width="10" class="mida" alt="" />';
		echo '</a>';
		echo '<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dropdownMenu'.$cnt.'">';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',1)">Send down date &amp; time changes</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',2)">Copy down times only</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',3)">Copy down dates &amp; times</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',4)">Copy down start date &amp; time</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',5)">Copy down end date &amp; time</a></li>';
		//echo '<li><a href="#" onclick="return senddownaction('.$cnt.',6)">Copy down review date &amp; time</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',7)">Copy down LatePass cutoff date &amp; time</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',8)">Copy down forum post-by date &amp; time</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',9)">Copy down forum reply-by date &amp; time</a></li>';
		echo '</ul></div></td>';

		/*echo "<td><select id=\"sel$cnt\" onchange=\"senddownselect(this);\"><option value=\"0\" selected=\"selected\">Action...</option>";
		echo '<option value="1">Send down date &amp; time changes</option>';
		echo '<option value="2">Copy down times only</option>';
		echo '<option value="3">Copy down dates &amp; times</option>';
		echo '<option value="4">Copy down start date &amp; time</option>';
		echo '<option value="5">Copy down end date &amp; time</option>';
		echo '<option value="6">Copy down review date &amp; time</option>';
		echo '</select></td>';
		*/
		echo "</tr>";
		$cnt++;
	}
	echo '</tbody></table>';
	echo '</form>';
	echo "<form id=\"realform\" method=post action=\"masschgdates.php?cid=$cid\" onsubmit=\"prepforsubmit(this)\">";
	echo "<input type=hidden id=\"chgcnt\" name=\"chgcnt\" value=\"$cnt\" />";
	echo '<input type=submit value="Save Changes"/>';
	echo '</form>';
	//echo "<script>var acnt = $cnt;</script>";
}

require("../footer.php");

?>
