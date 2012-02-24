<?php
	//Listing of all forums for a course - not being used
	//(c) 2006 David Lippman
	
	require("../validate.php");
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
	
	$cid = $_GET['cid'];
	
	
	$pagetitle = "Forums";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Forums</div>\n";
	
	//get general forum info and page order
	$now = time();
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
				$tagfilterselect .= '<option value="'.$tag.'">'.$tag.'</option>';
			}
			if (count($taginfo)>1) {
				$tagfilterselect .= '</optgroup>';
			}
		}
		$tagfilterselect .= '</select>';
	}
	
?>
	
	<div id="headerforums" class="pagetitle"><h2>Forums</h2></div>
	<div id="forumsearch">
	<form method="post" action="forums.php?cid=<?php echo $cid;?>">
		Search: <input type=text name="search" /> 
		<input type="radio" name="allthreads" value="thread" checked="checked"/>All thread subjects
		<input type="radio" name="allthreads" value="posts" />All posts 
		Limit by 
		<input type="submit" value="Search"/>
	</form>
	</div>
	
	
	<table class=forum>
	<thead>
	<tr><th>Forum Name</th><th>Threads</th><th>Posts</th><th>Last Post Date</th></tr>
	</thead>
	<tbody>
<?php
	
	$query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	$query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forum_posts.parent=0 AND imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$threadcount[$row[0]] = $row[1];
	}

	$query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) AS postcount,MAX(imas_forum_posts.postdate) AS maxdate FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	$query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";

	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
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
	$query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid='$cid' ";
	$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
	$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	if (!isset($teacherid)) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
	}
	$query .= "GROUP BY imas_forum_threads.forumid";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
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
		echo "<b><a href=\"thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b> ";
		if ($newcnt[$line['id']]>0) {
			 echo "<a href=\"thread.php?cid=$cid&forum={$line['id']}&page=-1\" style=\"color:red\">New Posts ({$newcnt[$line['id']]})</a>";
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
		echo "<td class=c>$threads</td><td class=c>$posts</td><td class=c>$lastpost</td></tr>\n";
	}
?>
	</tbody>
	</table>
<?php

	
	require("../footer.php");
?>
	
	
	
	
