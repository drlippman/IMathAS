<?php
//IMathAS:  Main course page
//(c) 2006 David Lippman
   require("../init.php");
   require("courseshowitems.php");
   require("../includes/calendardisp.php");
   if (isset($instrPreviewId)) {
	   $tutorid = $instrPreviewId;
   }
   if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
   }
   $cid = Sanitize::courseId($_GET['cid']);
   require("../filter/filter.php");

   $stm = $DBH->prepare("SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,latepasshrs FROM imas_courses WHERE id=:id");
   $stm->execute(array(':id'=>$cid));
   $line = $stm->fetch(PDO::FETCH_ASSOC);
   if ($line == null) {
	   echo "Course does not exist.  <a href=\"../index.php\">Return to main page</a></body></html>\n";
	   exit;
   }
   $allowunenroll = $line['allowunenroll'];
   $pagetitle = $line['name'];
   $items = unserialize($line['itemorder']);
   $msgset = $line['msgset']%5;
   $latepasshrs = $line['latepasshrs'];

    //get exceptions
   $now = time();
   $exceptions = array();
   if (!isset($teacherid) && !isset($tutorid)) {
	$exceptions = loadExceptions($cid, $userid);
   }
    if (count($exceptions)>0) {
	   upsendexceptions($items);
    }

   //if ($_GET['folder']!='0') {
   if (strpos($_GET['folder'],'-')!==false) {
	   $now = time();
	   $blocktree = explode('-',$_GET['folder']);
	   $backtrack = array();
	   for ($i=1;$i<count($blocktree);$i++) {
		$backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
		if (!isset($teacherid) && !isset($tutorid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
			$_GET['folder'] = 0;
			$items = unserialize($line['itemorder']);
			unset($backtrack);
			unset($blocktree);
			break;
		}
		if (strlen($items[$blocktree[$i]-1]['SH'])>2) {
			$contentbehavior = $items[$blocktree[$i]-1]['SH'][2];
		} else {
			$contentbehavior = 0;
		}
		$items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	   }
   }

   $openblocks = Array(0);
   if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]);}
   if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);} else {$prevloadedblocks = array();}
   if (in_array($_GET['folder'],$prevloadedblocks)) { $firstload = false;} else {$firstload = true;}

   //$oblist = implode(',',$openblocks);
   //echo "<script>\n";
   //echo "  oblist += ',$oblist';\n";
   //echo "</script>\n";


   //get latepasses
   if (!isset($teacherid) && !isset($tutorid) && !$inInstrStuView && isset($studentinfo)) {
	   //$query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
	   //$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   //$latepasses = mysql_result($result,0,0);
	   $latepasses = $studentinfo['latepasses'];
   } else {
	   $latepasses = 0;
   }

   //get new forum posts info
   	$query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	  $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid=:courseid ";
	  $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	  $query .= "WHERE imas_forum_threads.lastposttime<:now AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
    if (!isset($teacherid)) {
		  $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid2)) ";
    }
	  $query .= "GROUP BY imas_forum_threads.forumid";
  	$stm = $DBH->prepare($query);
  	if (!isset($teacherid)) {
      $stm->execute(array(':now'=>$now, ':courseid'=>$cid, ':userid'=>$userid, ':userid2'=>$userid));
  	} else {
      $stm->execute(array(':now'=>$now, ':courseid'=>$cid, ':userid'=>$userid));
    }
  	$newpostcnts = array();
  	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
  		$newpostcnts[$row[0]] = $row[1];
  	}

   //get items with content views, for enabling stats link
	/*if (isset($teacherid) || isset($tutorid)) {
		$hasstats = array();
		$query = "SELECT DISTINCT(CONCAT(SUBSTRING(type,1,1),typeid)) FROM imas_content_track WHERE courseid='$cid' AND type IN ('inlinetext','linkedsum','linkedlink','linkedintext','linkedviacal','assessintro','assess','assesssum','wiki','wikiintext') ";
		//$query .= "UNION SELECT DISTINCT(CONCAT(SUBSTRING(type,1,1),info)) FROM imas_content_track WHERE courseid='$cid' AND type in ('forumpost','forumreply')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$hasstats[$row[0]] = true;
		}
	}
	*/

	//get read linked items
	$readlinkeditems = array();
	if ($coursetheme=='otbsreader.css' && isset($studentid)) {
		$stm = $DBH->prepare("SELECT DISTINCT typeid FROM imas_content_track WHERE userid=:userid AND type='linkedlink' AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$readlinkeditems[$row[0]] = true;
		}
	}

   if (isset($teacherid)) {
	   //echo generateadditem($_GET['folder'],'t');
   }
   if (count($items)>0) {
	   //update block start/end dates to show blocks containing items with exceptions

	   showitems($items,$_GET['folder'],false,$contentbehavior);
	   if (isset($teacherid)) {
		   //echo generateadditem($_GET['folder'],'b');
	   }
   } else if (isset($teacherid)) {
	 // $_GET['folder'] is sanitized in generateadditem().
	 echo generateadditem($_GET['folder'],'b');
   }


   if ($firstload) {
	   echo "<script>document.cookie = 'openblocks-$cid=' + oblist;</script>\n";
   }
   if (isset($tutorid) && isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3) {
	echo '<script type="text/javascript">$(".instrdates").hide();</script>';
   }



?>
