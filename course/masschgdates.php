<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


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

		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $items = unserialize(mysql_result($result,0,0));
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		$cnt = $_POST['chgcnt'];
		$blockchg = 0;

		$assesstoupdate = array();
		$inlinetoupdate = array();
		$wikitoupdate = array();
		$linktoupdate = array();
		$forumbasictoupdate = array();
		$forumfulltoupdate = array();
		for ($i=0; $i<$cnt; $i++) {
			require_once("../includes/parsedatetime.php");

			$data = explode(',',$_POST['data'.$i]);

			if ($data[0] == '0') {
				$startdate = 0;
			} else {
				$pts = explode('~',$data[0]);
				$startdate = parsedatetime($pts[0],$pts[1]);
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
				$enddate = parsedatetime($pts[0],$pts[1]);
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
					$reviewdate = parsedatetime($pts[0],$pts[1]);
				}
			}
			if ($data[3] != 'NA') {
				if ($data[3]=='A') {
					$fpdate = 2000000000;
				} else if ($data[3] == 'N') {
					$fpdate = 0;
				} else {
					$pts = explode('~',$data[3]);
					$fpdate = parsedatetime($pts[0],$pts[1]);
				}
			}
			if ($data[4] != 'NA') {
				if ($data[4]=='A') {
					$frdate = 2000000000;
				} else if ($data[4] == 'N') {
					$frdate = 0;
				} else {
					$pts = explode('~',$data[4]);
					$frdate = parsedatetime($pts[0],$pts[1]);
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

			$type = $data[5]; // $_POST['type'.$i];
			$id = $data[6]; // $_POST['id'.$i];
			$avail = intval($data[7]);
			if ($type=='Assessment') {
				if ($id>0) {
					//$stm = $DBH->prepare("UPDATE imas_assessments SET startdate=:startdate,enddate=:enddate,reviewdate=:reviewdate,avail=:avail WHERE id=:id");
					//$stm->execute(array(':startdate'=>$startdate, ':enddate'=>$enddate, ':reviewdate'=>$reviewdate, ':avail'=>$avail, ':id'=>$id));
					array_push($assesstoupdate, $id, $startdate, $enddate, $reviewdate, $avail);
				}
			} else if ($type=='Forum') {
				if ($id>0) {
					if ($data[3] != 'NA' && $data[4] != 'NA') {
						//$stm = $DBH->prepare("UPDATE imas_forums SET startdate=:startdate,enddate=:enddate,postby=:postby,replyby=:replyby,avail=:avail WHERE id=:id");
						//$stm->execute(array(':startdate'=>$startdate, ':enddate'=>$enddate, ':postby'=>$fpdate, ':replyby'=>$frdate, ':avail'=>$avail, ':id'=>$id));
						array_push($forumfulltoupdate, $id, $startdate, $enddate, $avail, $fpdate, $frdate);
					} else {
						//$stm = $DBH->prepare("UPDATE imas_forums SET startdate=:startdate,enddate=:enddate,avail=:avail WHERE id=:id");
						//$stm->execute(array(':startdate'=>$startdate, ':enddate'=>$enddate, ':avail'=>$avail, ':id'=>$id));
						array_push($forumbasictoupdate, $id, $startdate, $enddate, $avail);
					}
				}
			} else if ($type=='Wiki') {
				if ($id>0) {
					//$stm = $DBH->prepare("UPDATE imas_wikis SET startdate=:startdate,enddate=:enddate,avail=:avail WHERE id=:id");
					//$stm->execute(array(':startdate'=>$startdate, ':enddate'=>$enddate, ':avail'=>$avail, ':id'=>$id));
					array_push($wikitoupdate, $id, $startdate, $enddate, $avail);
				}
			} else if ($type=='InlineText') {
				if ($id>0) {
					//$stm = $DBH->prepare("UPDATE imas_inlinetext SET startdate=:startdate,enddate=:enddate,avail=:avail WHERE id=:id");
					//$stm->execute(array(':startdate'=>$startdate, ':enddate'=>$enddate, ':avail'=>$avail, ':id'=>$id));
					array_push($inlinetoupdate, $id, $startdate, $enddate, $avail);
				}
			} else if ($type=='Link') {
				if ($id>0) {
					//$stm = $DBH->prepare("UPDATE imas_linkedtext SET startdate=:startdate,enddate=:enddate,avail=:avail WHERE id=:id");
					//$stm->execute(array(':startdate'=>$startdate, ':enddate'=>$enddate, ':avail'=>$avail, ':id'=>$id));
					array_push($linktoupdate, $id, $startdate, $enddate, $avail);
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
				$sub['startdate'] = $startdate;
				$sub['enddate'] = $enddate;
				$sub['avail'] = $avail;
				$blockchg++;
			}

		}
		if (count($assesstoupdate)>0) {
			$placeholders = Sanitize::generateQueryPlaceholdersGrouped($assesstoupdate, 5);
			$query = "INSERT INTO imas_assessments (id,startdate,enddate,reviewdate,avail) VALUES $placeholders ";
			$query .= "ON DUPLICATE KEY UPDATE startdate=VALUES(startdate),enddate=VALUES(enddate),reviewdate=VALUES(reviewdate),avail=VALUES(avail)";
			$stm = $DBH->prepare($query);
			$stm->execute($assesstoupdate);
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
			//DB $itemorder = addslashes(serialize($items));
			//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = serialize($items);
			$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
		}

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

		exit;
	} else { //DEFAULT DATA MANIPULATION
		$pagetitle = "Mass Change Dates";
		$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/masschgdates.js?v=041316\"></script>";
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
		$sessiondata['mcdorderby'.$cid] = $orderby;
		writesessiondata();
	} else if (isset($sessiondata['mcdorderby'.$cid])) {
		$orderby = $sessiondata['mcdorderby'.$cid];
	} else {
		$orderby = 3;
	}
	if (isset($_GET['filter'])) {
		$filter = Sanitize::simpleString($_GET['filter']);
		$sessiondata['mcdfilter'.$cid] = $filter;
		writesessiondata();
	} else if (isset($sessiondata['mcdfilter'.$cid])) {
		$filter = $sessiondata['mcdfilter'.$cid];
	} else {
		$filter = "all";
	}
	/*if (isset($_GET['incforum'])) {
		$incforum = $_GET['incforum'];
		$sessiondata['mcdincforum'.$cid] = $incforum;
		writesessiondata();
	} else if (isset($sessiondata['mcdincforum'.$cid])) {
		$incforum = $sessiondata['mcdincforum'.$cid];
	} else {
	*/
	$incforum = false;
//	}
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
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
	if (!$incforum) {
		$placeinhead .= '<style type="text/css">.mcf {display:none;}</style>';
		$placeinhead .= '<script type="text/javascript">var includeforums = false;</script>';
	} else {
		$placeinhead .= '<script type="text/javascript">var includeforums = true;</script>';
	}
	require("../header.php");

	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; Mass Change Dates</div>\n";
	echo '<div id="headermasschgdates" class="pagetitle"><h2>Mass Change Dates</h2></div>';

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
	echo '<button type="button" id="MCDforumtoggle" onclick="toggleMCDincforum()">';
	if ($incforum) {
		echo _('Hide Forum Dates');
	} else {
		echo _('Show Forum Dates');
	}
	echo '</button>';
	echo '</p>';

	echo "<p><input type=checkbox id=\"onlyweekdays\" checked=\"checked\"> Shift by weekdays only</p>";
	echo "<p>Once changing dates in one row, you select <i>Send down date and time change</i> from the Action pulldown to send the date change ";
	echo "difference to all rows below.  You can select <i>Copy down time</i> or <i>Copy down date &amp; time</i>to copy the same time/date to all rows below.  ";
	echo "If you click the checkboxes on the left, you can limit the update to those items. ";
	echo "Click the <img src=\"$imasroot/img/swap.gif\" alt=\"Swap\"> icon in each cell to swap from ";
	echo "Always/Never to Dates.  Swaps to/from Always/Never and Show changes cannot be sent down the list, but you can use the checkboxes and the pulldowns to change those settings for many items at once.</p>";
	echo "<form id=\"qform\">";

	echo '<p>Check: <a href="#" onclick="return chkAllNone(\'qform\',\'all\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'all\',false)">None</a>. ';

	//echo '<p>Check/Uncheck All: <input type="checkbox" name="ca" value="1" onClick="chkAll(this.form, this.checked)"/>. ';
	echo 'Change selected items <select id="swaptype" onchange="chgswaptype(this)"><option value="s">Start Date</option><option value="e">End Date</option><option value="r">Review Date</option><option value="a">Show</option></select>';
	echo ' to <select id="swapselected"><option value="always">Always</option><option value="dates">Dates</option></select>';
	echo ' <input type="button" value="Go" onclick="MCDtoggleselected(this.form)" /> &nbsp;';
	echo ' <button type="button" onclick="submittheform()">'._("Save Changes").'</button></p>';

	if ($picicons) {
		echo '<table class=gb><thead><tr><th></th><th>Name</th><th>Show</th><th>Start Date</th><th>End Date</th><th>Review Date</th><th class="mcf">Post By Date</th><th class="mcf">Reply By Date</th><th>Send Changes</th></thead><tbody>';
	} else {
		echo '<table class=gb><thead><tr><th></th><th>Name</th><th>Type</th><th>Show</th><th>Start Date</th><th>End Date</th><th>Review Date</th><th class="mcf">Post By Date</th><th class="mcf">Reply By Date</th><th>Send Changes</th></thead><tbody>';
	}
	$prefix = array();
	if ($orderby==3) {  //course page order
		$itemsassoc = array();
		//DB $query = "SELECT id,typeid,itemtype FROM imas_items WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,typeid,itemtype FROM imas_items WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$itemsassoc[$row[0]] = $row[2].$row[1];
		}

		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $itemorder = unserialize(mysql_result($result,0,0));
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


	$names = Array();
	$startdates = Array();
	$enddates = Array();
	$reviewdates = Array();
	$fpdates = Array();
	$frdates = Array();
	$ids = Array();
	$avails = array();
	$types = Array();
	$courseorder = Array();
	$pres = array();
	if ($filter=='all' || $filter=='assessments') {
		//DB $query = "SELECT name,startdate,enddate,reviewdate,id,avail FROM imas_assessments WHERE courseid='$cid' ";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT name,startdate,enddate,reviewdate,id,avail FROM imas_assessments WHERE courseid=:courseid ");
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
			if (isset($prefix['Assessment'.$row[4]])) {$pres[] = $prefix['Assessment'.$row[4]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['Assessment'.$row[4]];}
		}
	}
	if ($filter=='all' || $filter=='inlinetext') {
		//DB $query = "SELECT title,startdate,enddate,id,avail FROM imas_inlinetext WHERE courseid='$cid' ";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
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
			if (isset($prefix['InlineText'.$row[3]])) {$pres[] = $prefix['InlineText'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['InlineText'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='linkedtext') {
		//DB $query = "SELECT title,startdate,enddate,id,avail FROM imas_linkedtext WHERE courseid='$cid' ";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
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
			if (isset($prefix['LinkedText'.$row[3]])) {$pres[] = $prefix['LinkedText'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['LinkedText'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='forums') {
		//DB $query = "SELECT name,startdate,enddate,id,avail,postby,replyby FROM imas_forums WHERE courseid='$cid' ";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
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
			if (isset($prefix['Forum'.$row[3]])) {$pres[] = $prefix['Forum'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['Forum'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='wikis') {
		//DB $query = "SELECT name,startdate,enddate,id,avail FROM imas_wikis WHERE courseid='$cid' ";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
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
			if (isset($prefix['Wiki'.$row[3]])) {$pres[] = $prefix['Wiki'.$row[3]];} else {$pres[] = '';}
			if ($orderby==3) {$courseorder[] = $itemscourseorder['Wiki'.$row[3]];}
		}
	}
	if ($filter=='all' || $filter=='blocks') {
		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $items = unserialize(mysql_result($result,0,0));
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		function getblockinfo($items,$parent) {
			global $ids,$types,$names,$startdates,$enddates,$reviewdates,$frdates,$fpdates,$ids,$itemscourseorder,$courseorder,$orderby,$avails,$pres,$prefix;
			foreach($items as $k=>$item) {
				if (is_array($item)) {
					$ids[] = $parent.'-'.($k+1);
					$types[] = "Block";
					if ($orderby==3) {$courseorder[] = $itemscourseorder['Block'.$parent.'-'.($k+1)];}
					//DB $names[] = stripslashes($item['name']);
					$names[] = $item['name'];
					$startdates[] = $item['startdate'];
					$enddates[] = $item['enddate'];
					$avails[] = $item['avail'];
					$reviewdates[] = -1;
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
		if ($picicons>0) {
			echo "<input type=hidden id=\"type$cnt\" value=\"{$types[$i]}\"/>";
			echo '<img alt="'.$types[$i].'" title="'.$types[$i].'" src="'.$imasroot.'/img/';
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
		}
		echo Sanitize::encodeStringForDisplay($names[$i])."<input type=hidden id=\"id" . Sanitize::encodeStringForDisplay($cnt) . "\" value=\"" . Sanitize::encodeStringForDisplay($ids[$i]) . "\"/></div>";
		echo "<script> basesdates[$cnt] = ";
		//if ($startdates[$i]==0) { echo '"NA"';} else {echo $startdates[$i];}
		echo Sanitize::encodeStringForJavascript($startdates[$i]);
		echo "; baseedates[$cnt] = ";
		//if ($enddates[$i]==0 || $enddates[$i]==2000000000) { echo '"NA"';} else {echo $enddates[$i];}
		echo Sanitize::encodeStringForJavascript($enddates[$i]);
		echo "; baserdates[$cnt] = ";
		//if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {echo '"NA"';} else { echo $reviewdates[$i];}
		if ($reviewdates[$i]==-1) {echo '"NA"';} else { echo Sanitize::encodeStringForJavascript($reviewdates[$i]);}
		echo "; basefpdates[$cnt] = ";
		if ($fpdates[$i]==-1) {echo '"NA"';} else { echo Sanitize::encodeStringForJavascript($fpdates[$i]);}
		echo "; basefrdates[$cnt] = ";
		if ($frdates[$i]==-1) {echo '"NA"';} else { echo Sanitize::encodeStringForJavascript($frdates[$i]);}
		echo ";</script>";
		echo "</td>";
		if ($picicons==0) {
			echo "<td>";
			echo "{$types[$i]}<input type=hidden id=\"type$cnt\" value=\"{$types[$i]}\"/>";
			echo "</td>";
		}

		echo '<td><span class="nowrap"><img src="'.$imasroot.'/img/swap.gif" alt="Swap" onclick="MCDtoggle(\'a\','.$cnt.')"/><span id="availname'.Sanitize::encodeStringForDisplay($cnt).'">'.Sanitize::encodeStringForDisplay($availnames[$avails[$i]]).'</span><input type="hidden" id="avail'.Sanitize::encodeStringForDisplay($cnt).'" value="'.Sanitize::encodeStringForDisplay($avails[$i]).'"/></span></td>';

		echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\"><img src=\"$imasroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('s',$cnt)\"/>";
		if ($startdates[$i]==0) {
			echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"0\"/>";
		} else {
			echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"1\"/>";
		}
		if ($startdates[$i]==0) {
			echo "<span id=\"sspan0$cnt\" class=\"show\">Always</span>";
		} else {
			echo "<span id=\"sspan0$cnt\" class=\"hide\">Always</span>";
		}
		if ($startdates[$i]==0) {
			echo "<span id=\"sspan1$cnt\" class=\"hide\">";
		} else {
			echo "<span id=\"sspan1$cnt\" class=\"show\">";
		}
		if ($startdates[$i]==0) {
			$startdates[$i] = time();
			$sdate = tzdate("m/d/Y",$startdates[$i]);
			$stime = $defstime;
		} else {
			$sdate = tzdate("m/d/Y",$startdates[$i]);
			$stime = tzdate("g:i a",$startdates[$i]);
		}

		echo "<input type=text size=10 id=\"sdate$cnt\" name=\"sdate$cnt\" value=\"$sdate\" onblur=\"ob(this)\"/>(";
		echo "<span id=\"sd$cnt\">".getshortday($startdates[$i]).'</span>';
		//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].sdate$cnt,'anchor$cnt','MM/dd/yyyy',document.forms[0].sdate$cnt.value); return false;\" NAME=\"anchor$cnt\" ID=\"anchor$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		echo ") <a href=\"#\" onClick=\"displayDatePicker('sdate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";

		echo " at <input type=text size=8 id=\"stime$cnt\" name=\"stime$cnt\" value=\"$stime\">";
		echo '</span></td>';

		echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\"><img src=\"$imasroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('e',$cnt)\"/>";
		if ($enddates[$i]==2000000000) {
			echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"0\"/>";
		} else {
			echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"1\"/>";
		}
		if ($enddates[$i]==2000000000) {
			echo "<span id=\"espan0$cnt\" class=\"show\">Always</span>";
		} else {
			echo "<span id=\"espan0$cnt\" class=\"hide\">Always</span>";
		}
		if ($enddates[$i]==2000000000) {
			echo "<span id=\"espan1$cnt\" class=\"hide\">";
		} else {
			echo "<span id=\"espan1$cnt\" class=\"show\">";
		}

		if ($enddates[$i]==2000000000) {
			$enddates[$i]  = $startdates[$i] + 7*24*60*60;
			$edate = tzdate("m/d/Y",$enddates[$i]);
			$etime = $deftime;
		} else {
			$edate = tzdate("m/d/Y",$enddates[$i]);
			$etime = tzdate("g:i a",$enddates[$i]);
		}

		echo "<input type=text size=10 id=\"edate$cnt\" name=\"edate$cnt\" value=\"$edate\" onblur=\"ob(this)\"/>(";
		echo "<span id=\"ed$cnt\">".getshortday($enddates[$i]).'</span>';
		//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].edate$cnt,'anchor2$cnt','MM/dd/yyyy',document.forms[0].edate$cnt.value); return false;\" NAME=\"anchor2$cnt\" ID=\"anchor2$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
		echo ") <a href=\"#\" onClick=\"displayDatePicker('edate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";

		echo " at <input type=text size=8 id=\"etime$cnt\" name=\"etime$cnt\" value=\"$etime\">";
		echo '</span></td>';

		echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\">";
		if ($types[$i]=='Assessment') {
			echo "<img src=\"$imasroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('r',$cnt)\"/>";
			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"0\"/>";
			} else {
				echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"1\"/>";
			}
			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				echo "<span id=\"rspan0$cnt\" class=\"show\">";
			} else {
				echo "<span id=\"rspan0$cnt\" class=\"hide\">";
			}
			echo "<input type=radio name=\"rdatean$cnt\" value=\"0\" id=\"rdateanN$cnt\" ";
			if ($reviewdates[$i]!=2000000000) {
				echo 'checked=1';
			}
			echo " />Never <input type=radio name=\"rdatean$cnt\" value=\"2000000000\"  id=\"rdateanA$cnt\"  ";
			if ($reviewdates[$i]==2000000000) {
				echo 'checked=1';
			}
			echo " />Always</span>";

			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				echo "<span id=\"rspan1$cnt\" class=\"hide\">";
			} else {
				echo "<span id=\"rspan1$cnt\" class=\"show\">";
			}
			if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
				$reviewdates[$i] = $enddates[$i] + 7*24*60*60;
				$rdate = tzdate("m/d/Y",$reviewdates[$i]);
				$rtime = $deftime;
			} else {
				$rdate = tzdate("m/d/Y",$reviewdates[$i]);
				$rtime = tzdate("g:i a",$reviewdates[$i]);
			}

			echo "<input type=text size=10 id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"$rdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"rd$cnt\">".getshortday($reviewdates[$i]).'</span>';
			//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].rdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].rdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo ") <a href=\"#\" onClick=\"displayDatePicker('rdate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";

			echo " at <input type=text size=8 id=\"rtime$cnt\" name=\"rtime$cnt\" value=\"$rtime\"></span>";
		}
		echo '</td>';
		echo "<td class=\"mcf togdishid".($avails[$i]==0?' dis':'')."\">";
		if ($types[$i]=='Forum') {
			echo "<img src=\"$imasroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('fp',$cnt)\"/>";
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
				$fpdates[$i] = $enddates[$i];
				$fpdate = tzdate("m/d/Y",$fpdates[$i]);
				$fptime = $deftime;
			} else {
				$fpdate = tzdate("m/d/Y",$fpdates[$i]);
				$fptime = tzdate("g:i a",$fpdates[$i]);
			}

			echo "<input type=text size=10 id=\"fpdate$cnt\" name=\"fpdate$cnt\" value=\"$fpdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"fpd$cnt\">".getshortday($fpdates[$i]).'</span>';
			//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].fpdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].fpdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo ") <a href=\"#\" onClick=\"displayDatePicker('fpdate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";

			echo " at <input type=text size=8 id=\"fptime$cnt\" name=\"fptime$cnt\" value=\"$fptime\"></span>";
		}
		echo '</td>';
		echo "<td class=\"mcf togdishid".($avails[$i]==0?' dis':'')."\">";
		if ($types[$i]=='Forum') {
			echo "<img src=\"$imasroot/img/swap.gif\" alt=\"Swap\" onclick=\"MCDtoggle('fr',$cnt)\"/>";
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
				$frdates[$i] = $enddates[$i];
				$frdate = tzdate("m/d/Y",$frdates[$i]);
				$frtime = $deftime;
			} else {
				$frdate = tzdate("m/d/Y",$frdates[$i]);
				$frtime = tzdate("g:i a",$frdates[$i]);
			}

			echo "<input type=text size=10 id=\"frdate$cnt\" name=\"frdate$cnt\" value=\"$frdate\" onblur=\"ob(this)\"/>(";
			echo "<span id=\"frd$cnt\">".getshortday($frdates[$i]).'</span>';
			//echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].frdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].frdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo ") <a href=\"#\" onClick=\"displayDatePicker('frdate$cnt', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";

			echo " at <input type=text size=8 id=\"frtime$cnt\" name=\"frtime$cnt\" value=\"$frtime\"></span>";
		}
		echo '</td>';

		//echo "<td>Send Down: <a href=\"#\" <input type=button value=\"Change\" onclick=\"senddown($cnt)\"/> <input type=button value=\"Copy\" onclick=\"copydown($cnt)\"/></td>";
		echo '<td class="c"><div class="dropdown">';
		echo '<a tabindex=0 class="dropdown-toggle" id="dropdownMenu'.$cnt.'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		echo ' Action <img src="../img/collapse.gif" width="10" class="mida" alt="" />';
		echo '</a>';
		echo '<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dropdownMenu'.$cnt.'">';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',1)">Send down date &amp; time changes</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',2)">Copy down times only</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',3)">Copy down dates &amp; times</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',4)">Copy down start date &amp; time</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',5)">Copy down end date &amp; time</a></li>';
		echo '<li><a href="#" onclick="return senddownaction('.$cnt.',6)">Copy down review date &amp; time</a></li>';
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
