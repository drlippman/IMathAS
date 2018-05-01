<?php
	//Listing of all forums for a course - not being used
	//(c) 2006 David Lippman

	require("../init.php");
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
	}
	if (isset($teacherid)) {
		$isteacher = true;
	} else {
		$isteacher = false;
	}

	if (!isset($_GET['cid'])) {
		exit;
	}

	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($_POST['searchsubmit'])) {
		if (trim($_POST['search'])=='' && $_POST['tagfiltersel'] == '') {
			$_GET['clearsearch'] = true;
		}
	}

	if (isset($_GET['clearsearch'])) {
		unset($sessiondata['forumsearchstr'.$cid]);
		unset($sessiondata['forumsearchtype'.$cid]);
		unset($sessiondata['forumsearchtag'.$cid]);
		writesessiondata();
		$searchtype = "none";
	} else if(isset($_POST['searchsubmit'])) {
		$searchstr = trim($_POST['search']);
		$searchtype = $_POST['searchtype'];
		$searchtag = $_POST['tagfiltersel'];
		$sessiondata['forumsearchstr'.$cid] = $searchstr;
		$sessiondata['forumsearchtype'.$cid] = $searchtype;
		$sessiondata['forumsearchtag'.$cid] = $searchtag;
		writesessiondata();
	} else if (isset($sessiondata['forumsearchstr'.$cid])) {
		$searchstr = $sessiondata['forumsearchstr'.$cid];
		$searchtype = $sessiondata['forumsearchtype'.$cid];
		$searchtag = $sessiondata['forumsearchtag'.$cid];
	} else {
		$searchtype = "none";
	}


	$pagetitle = "Forums";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/thread.js"></script>';
	$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $GLOBALS['basesiteurl'] . "/forums/savetagged.php?cid=$cid';</script>";

	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	if ($searchtype != 'none') {
		echo "<a href=\"forums.php?cid=$cid&amp;clearsearch=true\">Forum List</a> &gt; ";
	}
	echo "Forums</div>\n";

	//get general forum info and page order
	$now = time();
	//DB $query = "SELECT * FROM imas_forums WHERE imas_forums.courseid='$cid'";
	$query = "SELECT * FROM imas_forums WHERE imas_forums.courseid=:courseid";
	if (!$teacherid) {
		//check for avail or past startdate; we'll do an enddate check later
		$query .= " AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now))";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	$lines = $stm->fetchALL(PDO::FETCH_ASSOC);
	$anyforumsgroup = false;
	$forumdata = array();
	$anyforumsgroup = false;
	foreach ($lines as $line) {
		$forumdata[$line['id']] = $line;
		if ($line['groupsetid']>0) {
			$anyforumsgroup = true;
		}
	}
	
	//pull exceptions, as they may extend the enddate
	$exceptions = array();
	if (isset($studentid) && count($forumdata)>0) {
		require_once("../includes/exceptionfuncs.php");
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
		$ph = Sanitize::generateQueryPlaceholders($forumdata);
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,waivereqscore,itemtype,assessmentid FROM imas_exceptions WHERE assessmentid in ($ph) AND userid=? AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
		$stm->execute(array_merge(array_keys($forumdata), array($userid)));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$exceptionresult = $exceptionfuncs->getCanUseLatePassForums($row, $forumdata[$row[5]]);
			$forumdata[$row[5]]['enddate'] = $exceptionresult[7];
		}
	}

	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$result = $stm->execute(array(':id'=>$cid));
	$itemorder =  unserialize($stm->fetchColumn(0));
	$itemsimporder = array();
	function flattenitems($items,&$addto) {
		global $itemsimporder;
		foreach ($items as $item) {
			if (is_array($item)) {
				flattenitems($item['items'],$addto);
			} else {
				$addto[] = $item;
			}
		}
	}
	flattenitems($itemorder,$itemsimporder);

	$itemsassoc = array();
	//DB $query = "SELECT id,typeid FROM imas_items WHERE courseid='$cid' AND itemtype='Forum'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("SELECT id,typeid FROM imas_items WHERE courseid=:courseid AND itemtype='Forum'");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$itemsassoc[$row[0]] = $row[1];
		if (!in_array($row[0],$itemsimporder)) {
			//capture any forums that are in imas_items but not imas_courses.itemorder
			$itemsimporder[] = $row[0];
		}
	}

	$maxitemnum = max($itemsimporder) + 1;
	//capture any forums that are not in imas_items
	foreach ($forumdata as $fid=>$line) {
		if (in_array($fid,$itemsassoc)) { continue; }
		$itemsassoc[$maxitemnum] = $fid;
		$itemsimporder[] = $maxitemnum;
		$maxitemnum++;
	}


	//construct tag list selector
	$taginfo = array();
	foreach ($itemsimporder as $item) {
		if (!isset($itemsassoc[$item])) { continue; }
		$taglist = $forumdata[$itemsassoc[$item]]['taglist'];
		if ($taglist=='') { continue;}
		$p = strpos($taglist,':');
		$catname = substr($taglist,0,$p);
		if (!isset($taginfo[$catname])) {
			$taginfo[$catname] = explode(',',substr($taglist,$p+1));
		} else {
			$newtags = array_diff(explode(',',substr($taglist,$p+1)), $taginfo[$catname]);
			foreach ($newtags as $tag) {
				$taginfo[$catname][] = $tag;
			}
		}
	}
	if (count($taginfo)==0) {
		$tagfilterselect = '';
	} else {
		if (count($taginfo)>1) {
			$tagfilterselect = 'Category: ';
		} else {
			$tagfilterselect = $catname .': ';
		}
		$tagfilterselect .= '<select name="tagfiltersel">';
		$tagfilterselect .= '<option value="">All</option>';
		foreach ($taginfo as $catname=>$tagarr) {
			if (count($taginfo)>1) {
				$tagfilterselect .= '<optgroup label="'.$catname.'">';
			}
			foreach ($tagarr as $tag) {
				$tagfilterselect .= '<option value="'.$tag.'"';
				if ($tag==$searchtag) { $tagfilterselect .= ' selected="selected"';}
				$tagfilterselect .= '>'.$tag.'</option>';
			}
			if (count($taginfo)>1) {
				$tagfilterselect .= '</optgroup>';
			}
		}
		$tagfilterselect .= '</select>';
	}
	echo '<div class="cpmid">';
	echo '<a href="newthreads.php?cid='.$cid.'">'._('New Forum Posts').'</a> | ';
	echo '<a href="flaggedthreads.php?cid='.$cid.'">'._('Flagged Forum Posts').'</a>';
	echo '</div>';

	if ($searchtype=='none') {
		echo '<div id="headerforums" class="pagetitle"><h2>Forums</h2></div>';
	} else {
		echo '<div id="headerforums" class="pagetitle"><h2>Forum Search Results</h2></div>';
	}
?>


	<div id="forumsearch">
	<form method="post" action="forums.php?cid=<?php echo $cid;?>">
		<p>
		Search: <input type=text name="search" value="<?php echo Sanitize::encodeStringForDisplay($searchstr);?>" />
		<input type="radio" name="searchtype" value="thread" <?php if ($searchtype!='posts') {echo 'checked="checked"';}?>/>All thread subjects
		<input type="radio" name="searchtype" value="posts" <?php if ($searchtype=='posts') {echo 'checked="checked"';}?>/>All posts.
		<?php
		if ($tagfilterselect != '') {
			echo "Limit by $tagfilterselect";
		}
		?>
		<input name="searchsubmit" type="submit" value="Search"/>
		</p>
	</form>
	</div>
<?php
if ($searchtype == 'thread') {
	//doing a search of thread subjects
	$now = time();
	$searchstr = trim(str_replace(' and ', ' ',$searchstr));

	$query = "SELECT imas_forums.id AS forumid,imas_forum_posts.id,imas_forum_posts.subject,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.files,imas_forum_threads.views,imas_forum_posts.tag,imas_forum_posts.isanon,imas_forum_views.tagged ";
	$query .= "FROM imas_forum_posts JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id ";
	$query .= "JOIN imas_users ON imas_users.id=imas_forum_posts.userid ";
	$query .= "JOIN imas_forum_threads ON imas_forum_threads.id=imas_forum_posts.threadid AND imas_forum_threads.lastposttime<? ";
	$query .= "LEFT JOIN imas_forum_views ON imas_forum_threads.id=imas_forum_views.threadid AND imas_forum_views.userid=? ";
	$query .= "WHERE imas_forums.courseid=? AND imas_forum_posts.id=imas_forum_posts.threadid "; //these are indexed fields, but parent is not
	$arr = array($now, $userid, $cid );
	if ($searchstr != '') {
		//DB $searchterms = explode(" ",addslashes($searchstr));
		$searchterms = explode(" ", $searchstr);
		//DB $searchlikes = "(imas_forum_posts.subject LIKE '%".implode("%' AND imas_forum_posts.subject LIKE '%",$searchterms)."%')";
		$searchlikes = "(imas_forum_posts.subject LIKE ?".str_repeat(" AND imas_forum_posts.subject LIKE ?",count($searchterms)-1).") ";
		foreach ($searchterms as $t) {
			$arr[] = "%$t%";
		}
		$query .= "AND $searchlikes ";
	}
	if ($searchtag != '') {
		$query .= "AND imas_forum_posts.tag=?";
		$arr[] = $searchtag;
	}
	if (!$isteacher) {
		$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now AND imas_forums.enddate>$now)) ";
	}
	if ($anyforumsgroup && !$isteacher) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=?)) ";
		$arr[] = $userid;
	}

	$query .= " ORDER BY imas_forum_threads.lastposttime DESC";
	$stm = $DBH->prepare($query);
	$stm->execute($arr);
	$result=$stm->fetchALL(PDO::FETCH_ASSOC);

	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$threaddata = array();
	$threadids = array();
	foreach($result as $line) {
		$threaddata[$line['id']] = $line;
		$threadids[] = $line['id'];
	}
	if (count($threadids)==0) {
		echo 'No results';
	} else {
		$limthreads = implode(',', array_map('intval', $threadids));
		//DB $query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
		//DB $query .= "WHERE threadid IN ($limthreads) GROUP BY threadid";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
    $query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
		$query .= "WHERE threadid IN ($limthreads) GROUP BY threadid";
		$stm = $DBH->query($query);

		$postcount = array();
		$maxdate = array();
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$postcount[$row[0]] = $row[1] - 1;
			$maxdate[$row[0]] = $row[2];
		}
		echo '<table class=forum><thead>';
		echo '<tr><th>Topic</th><th>Forum</th><th>Replies</th><th>Views</th><th>Last Post Date</th></tr></thead><tbody>';
		foreach ($threaddata as $line) {
			if (isset($postcount[$line['id']])) {
				$posts = $postcount[$line['id']];
				$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
			} else {
				$posts = 0;
				$lastpost = '';
			}
			echo "<tr id=\"tr" . Sanitize::onlyInt($line['id']) . "\" ";
			if ($line['tagged']==1) {echo 'class="tagged"';}
			echo "><td>";
			echo "<span class=right>\n";
			if ($line['tag']!='') { //category tags
				echo '<span class="forumcattag">' . Sanitize::encodeStringForDisplay($line['tag']) . '</span> ';
			}

			if ($line['tagged']==1) {
				echo "<img class=\"pointer\" id=\"tag" . Sanitize::onlyInt($line['id']) . "\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged(" . Sanitize::onlyInt($line['id']) . ");return false;\" alt=\"Flagged\" />";
			} else {
				echo "<img class=\"pointer\" id=\"tag" . Sanitize::onlyInt($line['id']) . "\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged(" . Sanitize::onlyInt($line['id']) . ");return false;\" alt=\"Not flagged\"/>";
			}

			if ($isteacher) {
				echo "<a href=\"thread.php?page=" . Sanitize::encodeUrlParam($page) . "&cid=" . Sanitize::courseId($cid) . "&forum=" . Sanitize::onlyInt($line['forumid']) . "&move=" . Sanitize::onlyInt($line['id']) . "\">Move</a> ";
			}
			if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby)) {
				echo "<a href=\"thread.php?page=" . Sanitize::encodeUrlParam($page) . "&cid=" . Sanitize::courseId($cid) . "&forum=" . Sanitize::onlyInt($line['forumid']) . "&modify=" . Sanitize::onlyInt($line['id']) . "\">Modify</a> ";
			}
			if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) {
				echo "<a href=\"thread.php?page=" . Sanitize::encodeUrlParam($page) . "&cid=" . Sanitize::courseId($cid) . "&forum=" . Sanitize::onlyInt($line['forumid']) . "&remove=" . Sanitize::onlyInt($line['id']) . "\">Remove</a>";
			}
			echo "</span>\n";
			if ($line['isanon']==1) {
				$name = "Anonymous";
			} else {
				$name = "{$line['LastName']}, {$line['FirstName']}";
			}
			echo "<b><a href=\"posts.php?cid=$cid&forum=" . Sanitize::encodeUrlParam($line['forumid']) . "&thread=" . Sanitize::encodeUrlParam($line['id']) . "&page=-4\">" . Sanitize::encodeStringForDisplay($line['subject']) . "</a></b>: " . Sanitize::encodeStringForDisplay($name);
			echo "</td>\n";
			echo "<td class=\"c\"><a href=\"thread.php?cid=$cid&forum=" . Sanitize::encodeStringForDisplay($line['forumid']) . "\">" . Sanitize::encodeStringForDisplay($line['name']) . "</a></td>";
			echo "<td class=c>$posts</td><td class=c>" . Sanitize::encodeStringForDisplay($line['views']) . " </td><td class=c>$lastpost ";
			echo "</td></tr>\n";
		}
	}




} else if ($searchtype == 'posts') {
	//doing a search of all posts
	if (!isset($CFG['CPS']['itemicons'])) {
	   $itemicons = array('web'=>'web.png', 'doc'=>'doc.png', 'wiki'=>'wiki.png',
		'html'=>'html.png', 'forum'=>'forum.png', 'pdf'=>'pdf.png',
		'ppt'=>'ppt.png', 'zip'=>'zip.png', 'png'=>'image.png', 'xls'=>'xls.png',
		'gif'=>'image.png', 'jpg'=>'image.png', 'bmp'=>'image.png',
		'mp3'=>'sound.png', 'wav'=>'sound.png', 'wma'=>'sound.png',
		'swf'=>'video.png', 'avi'=>'video.png', 'mpg'=>'video.png',
		'nb'=>'mathnb.png', 'mws'=>'maple.png', 'mw'=>'maple.png');
	 } else {
	   $itemicons = $CFG['CPS']['itemicons'];
	 }
	require_once("../includes/filehandler.php");
	$now = time();
	if ($searchstr != '') {
		$searchstr = trim(str_replace(' and ', ' ',$searchstr));
		$searchterms = explode(" ", $searchstr);
		//DB $searchlikes = "(imas_forum_posts.message LIKE '%".implode("%' AND imas_forum_posts.message LIKE '%",$searchterms)."%')";
		//DB $searchlikes2 = "(imas_forum_posts.subject LIKE '%".implode("%' AND imas_forum_posts.subject LIKE '%",$searchterms)."%')";
		//DB $searchlikes3 = "(imas_users.LastName LIKE '%".implode("%' AND imas_users.LastName LIKE '%",$searchterms)."%')";
		$searchlikesarr = array();
		foreach ($searchterms as $t) {
			$searchlikesarr[] = '(imas_forum_posts.message LIKE ? OR imas_forum_posts.subject LIKE ? OR imas_users.LastName LIKE ?)';
		}
		$searchlikes = implode(' AND ', $searchlikesarr);
	}

	//DB $query = "SELECT imas_forums.id AS forumid,imas_forum_posts.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.files,imas_forum_posts.isanon ";
	//DB $query .= "FROM imas_forum_posts JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id ";
	//DB $query .= "JOIN imas_users ON imas_users.id=imas_forum_posts.userid ";
	$query = "SELECT imas_forums.id AS forumid,imas_forum_posts.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.files,imas_forum_posts.isanon ";
	$query .= "FROM imas_forum_posts JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id ";
	$query .= "JOIN imas_users ON imas_users.id=imas_forum_posts.userid ";
	$array = array();
	if ($anyforumsgroup && !$isteacher) {
		$query .= "JOIN imas_forum_threads ON imas_forum_threads.id=imas_forum_posts.threadid AND imas_forum_threads.lastposttime<?";
		$array[] = $now;
	}
	$query .= "WHERE imas_forums.courseid=? ";
	$array[]= $cid;
	if ($searchstr != '') {
		$query .= " AND ($searchlikes) ";
		foreach ($searchterms as $t) {
			$array[] = "%$t%";
			$array[] = "%$t%";
			$array[] = "%$t%";
		}
	}
	if ($searchtag != '') {
		$query .= "AND imas_forum_posts.tag=? ";
		$array[]= $searchtag;
	}
	if (!$isteacher) {
		$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now AND imas_forums.enddate>$now)) AND (imas_forums.settings&16)=0 ";
	}
	if ($anyforumsgroup && !$isteacher) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=?')) ";
		$array[]= $userid;
	}
	$query .= " ORDER BY imas_forum_posts.postdate DESC";

	$stm = $DBH->prepare($query);
	$stm->execute($array);
  $result=$stm->fetchALL(PDO::FETCH_ASSOC);
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if ($result==0) {
		echo '<p>No results</p>';
	}
	foreach ($result as $line) {
		echo "<div class=block>";
		echo "<b>" . Sanitize::encodeStringForDisplay($line['subject']) ."</b>";
		echo ' (in ' . Sanitize::encodeStringForDisplay($line['name']).')';
		if ($line['isanon']==1) {
			$name = "Anonymous";
		} else {
			$name = "{$line['LastName']}, {$line['FirstName']}";
		}
		echo "<br/>Posted by: " . Sanitize::encodeStringForDisplay($name) . ", ";
		echo tzdate("F j, Y, g:i a",$line['postdate']);

		echo "</div><div class=blockitems>";
		if($line['files']!='') {
			$fl = explode('@@',$line['files']);
			if (count($fl)>2) {
				echo '<p><b>Files:</b> ';//<ul class="nomark">';
			} else {
				echo '<p><b>File:</b> ';
			}
			for ($i=0;$i<count($fl)/2;$i++) {
				//if (count($fl)>2) {echo '<li>';}
				echo '<a href="'.getuserfileurl('ffiles/'.$line['id'].'/'.$fl[2*$i+1]).'" target="_blank">';
				$extension = ltrim(strtolower(strrchr($fl[2*$i+1],".")),'.');
				if (isset($itemicons[$extension])) {
					echo "<img alt=\"".Sanitize::encodeStringForDisplay($extension)."\" src=\"$imasroot/img/{$itemicons[$extension]}\" class=\"mida\"/> ";
				} else {
					echo "<img alt=\"doc\" src=\"$imasroot/img/doc.png\" class=\"mida\"/> ";
				}
				echo Sanitize::encodeStringForDisplay($fl[2*$i]) . '</a> ';
				//if (count($fl)>2) {echo '</li>';}
			}
			//if (count($fl)>2) {echo '</ul>';}
			echo '</p>';
		}
		echo Sanitize::outgoingHtml(filter($line['message']));
		echo "<p><a href=\"posts.php?cid=" . Sanitize::courseId($cid) . "&forum=" . Sanitize::onlyInt($line['forumid']) . "&thread=" . Sanitize::onlyInt($line['threadid']) . "&page=-4\">Show full thread</a></p>";
		echo "</div>\n";
	}

} else {
	if (count($forumdata)==0) {
		if ($isteacher) {
			echo '<p>There are no forums in this class yet.  You can add forums from the course page.</p>';
		} else {
			echo '<p>There are no active forums at this time.</p>';
		}
	} else {
	//default display

?>
	<table class=forum>
	<thead>
	<tr><th>Forum Name</th><th>Threads</th><th>Posts</th><th>Last Post Date</th></tr>
	</thead>
	<tbody>
<?php

	//DB $query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	//DB $query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forum_posts.parent=0 AND imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$query = "SELECT imas_forums.id,COUNT(imas_forum_threads.id) FROM imas_forums LEFT JOIN imas_forum_threads ON ";
	$query .= "imas_forums.id=imas_forum_threads.forumid AND imas_forum_threads.lastposttime<:now ";
	$query .= "WHERE imas_forums.courseid=:courseid ";
	$qarr = array(':now'=>$now, ':courseid'=>$cid);
	if (!isset($teacherid)) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid )) ";
		$qarr[':userid']=$userid;
	}
	$query .= "GROUP BY imas_forum_threads.forumid ORDER BY imas_forums.id";
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	$result=$stm->fetchALL(PDO::FETCH_NUM);
	foreach ($result as $row) {
		$threadcount[$row[0]] = $row[1];
	}

	// $query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) AS postcount,MAX(imas_forum_posts.postdate) AS maxdate FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	// $query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";
	//
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	// var_dump($result);
	// while ($row = mysql_fetch_row($result)) {
	// 	$postcount[$row[0]] = $row[1];
	// 	$maxdate[$row[0]] = $row[2];
	//NOT WORKING
	$query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) AS postcount,MAX(imas_forum_posts.postdate) AS maxdate FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	$query .= "imas_forums.id=imas_forum_posts.forumid ";
	if (!isset($teacherid)) {
		$query .= "JOIN imas_forum_threads ON imas_forum_posts.threadid=imas_forum_threads.id ";
	}
	$query .= "WHERE imas_forums.courseid=:courseid ";
	$qarr = array(':courseid'=> $cid);
	if (!isset($teacherid)) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid )) ";
		$qarr[':userid']=$userid;
	}
	$query .= "GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);

	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$result=$stm->fetchALL(PDO::FETCH_NUM);
	foreach ($result as $row){
		$postcount[$row[0]] = $row[1];
		$maxdate[$row[0]] = $row[2];
	}
/*
	$query = "SELECT imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
	$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
*/
	/*$query = "SELECT imas_forums.id,count(imas_forum_threads.id) as pcount FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) GROUP BY imas_forums.id";
	*/
	//DB $query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	//DB $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid='$cid' ";
	//DB $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
	//DB $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	//DB NOT WORKING
	$query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid=:courseid ";
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	$query .= "WHERE imas_forum_threads.lastposttime<:now  AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	$array = array(':now'=>$now, ':courseid'=>$cid, ':userid'=>$userid);
	if (!isset($teacherid)) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid )) ";
		$array[':userid']=$userid;
	}
	$query .= "GROUP BY imas_forum_threads.forumid";
	$stm = $DBH->prepare($query);
	$stm->execute($array);
	$result=$stm->fetchALL(PDO::FETCH_NUM);
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	foreach($result as $row) {
		$newcnt[$row[0]] = $row[1];
	}

	/*$now = time();
	$query = "SELECT * FROM imas_forums WHERE imas_forums.courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumdata = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$forumdata[$line['id']] = $line;
	}

	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$itemorder = unserialize(mysql_result($result,0,0));
	$itemsimporder = array();
	function flattenitems($items,&$addto) {
		global $itemsimporder;
		foreach ($items as $item) {
			if (is_array($item)) {
				flattenitems($item['items'],$addto);
			} else {
				$addto[] = $item;
			}
		}
	}
	flattenitems($itemorder,$itemsimporder);

	$itemsassoc = array();
	$query = "SELECT id,typeid FROM imas_items WHERE courseid='$cid' AND itemtype='Forum'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$itemsassoc[$row[0]] = $row[1];
	}
	*/
	foreach ($itemsimporder as $item) {
		if (!isset($itemsassoc[$item])) { continue; }
		$line = $forumdata[$itemsassoc[$item]];

		if (!$isteacher && !($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now))) {
				continue;
		}
		echo "<tr><td>";
		if ($isteacher) {
			echo '<span class="right">';
			echo "<a href=\"../course/addforum.php?cid=$cid&id={$line['id']}\">Modify</a> ";
			echo '</span>';
		}
		echo "<b><a href=\"thread.php?cid=$cid&forum={$line['id']}\">".Sanitize::encodeStringForDisplay($line['name'])."</a></b> ";
		if ($newcnt[$line['id']]>0) {
			 echo "<a href=\"thread.php?cid=$cid&forum=" . Sanitize::onlyInt($line['id']) . "&page=-1\" class=noticetext >New Posts (" . Sanitize::encodeStringForDisplay($newcnt[$line['id']]) . ")</a>";
		}
		echo "</td>\n";
		if (isset($threadcount[$line['id']])) {
			$threads = $threadcount[$line['id']];
			$posts = $postcount[$line['id']];
			$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
		} else {
			$threads = 0;
			$posts = 0;
			$lastpost = '';
		}
		echo "<td class=c>" . Sanitize::onlyInt($threads) . "</td><td class=c>" . Sanitize::onlyInt($posts) . "</td><td class=c>" . Sanitize::encodeStringForDisplay($lastpost) . "</td></tr>\n";
	}
?>
	</tbody>
	</table>
<?php
	}
}
	require("../footer.php");
?>
