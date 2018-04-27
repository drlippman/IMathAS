<?php
//IMathAS:  New threads and messages list for a course using remoteaccess code
//for mobile access w/o logging in
//(c) 2009 David Lippman
	$init_skip_csrfp = true;
   	require("init_without_validate.php");
   	$keyString = Sanitize::simpleString($_GET['key']);
	if (!empty($_COOKIE['remoteaccess']) && strlen($_COOKIE['remoteaccess'])==10) {
        $keyString = Sanitize::simpleString($_COOKIE['remoteaccess']);
	} else if (empty($keyString) || strlen(trim($keyString))!=10) {
		echo "Key Error";
        setcookie('remoteaccess',$keyString, time()+60*60*24*30*365*10,'','',true,true);
		exit;
	} else {
		//setcookie('remoteaccess',$keyString, time()+60*60*24*30*365*10,'','',true,true);
	}
	//look up user
	//DB $query = "SELECT id FROM imas_users WHERE remoteaccess='{$_GET['key']}'";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {
	$stm = $DBH->prepare("SELECT id FROM imas_users WHERE remoteaccess=:remoteaccess");
	$stm->execute(array(':remoteaccess'=>$keyString));
	if ($stm->rowCount()==0) {
		echo "Access key invalid";
		exit;
	}
	//DB $userid = mysql_result($result,0,0);
	$userid = $stm->fetchColumn(0);
  $tzoffset = Sanitize::onlyInt($_GET['tzoffset']);
	$now = time();

	function tzdate($string,$time) {
		  global $tzoffset;
		  //$dstoffset = date('I',time()) - date('I',$time);
		  //return gmdate($string, $time-60*($tzoffset+60*$dstoffset));
		  $serveroffset = date('Z') + $tzoffset*60;
		  return date($string, $time-$serveroffset);
		  //return gmdate($string, $time-60*$tzoffset);
	  }

  function formatdate($date) {
	  return date("D n/j/y, g:i a",$date);
	//return tzdate("D n/j/y, g:i a",$date);
	//return tzdate("M j, Y, g:i a",$date);
  }
  header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?><!DOCTYPE html>
<html>
<head>
<meta name = "viewport" content = "width = device-width">
<title><?php echo $installname;?> Posts and Messages</title>
</head>
<body>
<div style="font-weight: bold"><?php echo $installname;?> Posts and Messages</div>
<div style="color: gray; font-size: 80%">Last updated <?php echo formatdate(time());?> <a href="javascript:location.reload(true)">Refresh</a></div>
<?php

	$courseforums = array();
	$forumthreads = array();
	$forumname = array();
	$lastpost = array();
	$forumcourse = array();
	if ($_GET['limit'] != 'take') {//teach or all
		//DB $query = "SELECT imas_forums.courseid, imas_forums.name, imas_forums.id, imas_forum_threads.id as tid, imas_forum_threads.lastposttime FROM imas_forum_threads ";
		//DB $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
		//DB $query .= "JOIN imas_teachers ON imas_forums.courseid=imas_teachers.courseid AND imas_teachers.userid='$userid' ";
		//DB $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
		//DB $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
    //DB // $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
		//DB $query .= "ORDER BY imas_forum_threads.lastposttime DESC LIMIT 30";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $query = "SELECT imas_forums.courseid, imas_forums.name, imas_forums.id, imas_forum_threads.id as tid, imas_forum_threads.lastposttime FROM imas_forum_threads ";
		$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
		$query .= "JOIN imas_teachers ON imas_forums.courseid=imas_teachers.courseid AND imas_teachers.userid=:userid ";
		$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:useridB ";
		$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
		$query .= "ORDER BY imas_forum_threads.lastposttime DESC LIMIT 30";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':useridB'=>$userid));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {

			if (!isset($courseforums[$line['courseid']])) {
				$courseforums[$line['courseid']] = array();
			}
			if (!isset( $forumthreads[$line['id']])) {
				 $forumthreads[$line['id']]= array();
			}
			//add forum to courseforums list if not already there
			if (!in_array($line['id'], $courseforums[$line['courseid']])) {
				$courseforums[$line['courseid']][] = $line['id'];
			}
			$forumcourse[$line['id']] = $line['courseid'];
			//add thread to forumthreads list if not already there
			if (!in_array($line['tid'], $forumthreads[$line['id']])) {
				$forumthreads[$line['id']][] = $line['tid'];
			}
			$forumname[$line['id']] = Sanitize::stripHtmlTags($line['name']);
			$lastpost[$line['tid']] = formatdate($line['lastposttime']);
		}
	}
	if ($_GET['limit'] != 'teach') {  //take or all
		//DB $query = "SELECT imas_forums.courseid, imas_forums.name, imas_forums.id, imas_forum_threads.id as tid, imas_forum_threads.lastposttime FROM imas_forum_threads ";
		//DB $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
		//DB $query .= "JOIN imas_students ON imas_forums.courseid=imas_students.courseid AND imas_students.userid='$userid' ";
		//DB $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
		//DB $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
		//DB $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
		//DB $query .= "ORDER BY imas_forum_threads.lastposttime DESC LIMIT 30";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$query = "SELECT imas_forums.courseid, imas_forums.name, imas_forums.id, imas_forum_threads.id as tid, imas_forum_threads.lastposttime FROM imas_forum_threads ";
		$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
		$query .= "JOIN imas_students ON imas_forums.courseid=imas_students.courseid AND imas_students.userid=:userid ";
		$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:useridB ";
		$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) AND imas_forum_threads.lastposttime<:now ";
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:useridC)) ";
		$query .= "ORDER BY imas_forum_threads.lastposttime DESC LIMIT 30";

		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':useridB'=>$userid, ':now'=>$now, ':useridC'=>$userid));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($courseforums[$line['courseid']])) {
				$courseforums[$line['courseid']] = array();
			}
			if (!isset( $forumthreads[$line['id']])) {
				 $forumthreads[$line['id']]= array();
			}
			//add forum to courseforums list if not already there
			if (!in_array($line['id'], $courseforums[$line['courseid']])) {
				$courseforums[$line['courseid']][] = $line['id'];
			}
			$forumcourse[$line['id']] = $line['courseid'];
			//add thread to forumthreads list if not already there
			if (!in_array($line['tid'], $forumthreads[$line['id']])) {
				$forumthreads[$line['id']][] = $line['tid'];
			}
			$forumname[$line['id']] = Sanitize::stripHtmlTags($line['name']);
			$lastpost[$line['tid']] = formatdate($line['lastposttime']);
		}
	}

	$coursenames = array();
	$cidlist = implode(',',array_keys($courseforums));
	//DB $query = "SELECT id,name FROM imas_courses WHERE id IN ($cidlist)";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
  //we know $cidlist is all integers from the database, so this is safe
$stm = $DBH->prepare("SELECT id,name FROM imas_courses WHERE id IN (:cidlist)");
	$stm->execute(array(':cidlist'=>$cidlist));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$coursenames[$row[0]] = $row[1];
	}
	asort($coursenames);
	if (count($lastpost)>0) {
		$threadids = implode(',',array_keys($lastpost));
		//DB $query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
		//DB $query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.id IN ($threadids) ORDER BY imas_forum_posts.postdate DESC";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
    //we know $threadids is all integers from the database, so this is safe
		$query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
		$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.id IN (:threadids) ORDER BY imas_forum_posts.postdate DESC";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':threadids'=>$threadids));
		$lastforum = '';
		$lastcourse = '';
		$forumcontent = array();
		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($forumcontent[$line['forumid']])) {
				$forumcontent[$line['forumid']] = '';
			}
			$course = $forumcourse[$line['forumid']];
			$url = $imasroot."/forums/posts.php?page=-2&amp;cid=$course&amp;forum={$line['forumid']}&amp;thread={$line['threadid']}";
			/*$forumcontent[$line['forumid']] .= "<div style='font-size:100%;'>";
			$forumcontent[$line['forumid']] .= "<a style='color: blue;' href='$url' target='_new'>{$line['subject']}</a>";
			$forumcontent[$line['forumid']] .= " <span style='color: black;'>".Sanitize::encodeStringForDisplay("{$line['LastName']}, {$line['FirstName']}")."</span>";
			$forumcontent[$line['forumid']] .= " <span style='color: gray;'>".Sanitize::encodeStringForDisplay($lastpost[$line['threadid']])."</span></div>";
			*/
			$forumcontent[$line['forumid']] .= "<tr><td><a style='color: blue;' href='$url' target='_new'>{$line['subject']}</a></td>";
			$forumcontent[$line['forumid']] .= "<td><span style='color: black;'>".Sanitize::encodeStringForDisplay("{$line['LastName']}, {$line['FirstName']}")."</span><br/>";
			$forumcontent[$line['forumid']] .= "<span style='color: gray;'>".Sanitize::encodeStringForDisplay($lastpost[$line['threadid']])."</span></td></tr>";
		}

		echo "<div style='font-size:100%; font-weight: 700; background-color: #ccf; margin-below:5px;'>New Posts</div>";
		foreach($coursenames as $id=>$name) {
			echo "<div style='font-size:100%; color: #606; font-weight: 700; '>" .Sanitize::encodeStringForDisplay($name)."</div>";
			echo "<div style='margin-left: 5px;'>";
			asort($courseforums[$id]);
			foreach($courseforums[$id] as $fid) {
				echo "<div style='font-size:100%; color: green;'>{$forumname[$fid]}</div>";
				echo "<table border=0>";
				echo $forumcontent[$fid];
				echo '</table></div>';
			}
			echo '</div>';
		}
	} else {
		echo "<div style='font-size:100%; font-weight: 700; background-color: #ccf; margin-below:5px;'>No New Posts</div>";
	}


	//DB $query = "SELECT imas_msgs.id,imas_msgs.courseid,imas_msgs.title,imas_msgs.senddate,imas_users.FirstName,imas_users.LastName ";
	//DB $query .= "FROM imas_msgs,imas_users WHERE imas_msgs.msgto='$userid' AND imas_msgs.msgfrom=imas_users.id ";
	//DB $query .= "AND (imas_msgs.isread=0 OR imas_msgs.isread=4) ORDER BY imas_msgs.senddate DESC";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
	$query = "SELECT imas_msgs.id,imas_msgs.courseid,imas_msgs.title,imas_msgs.senddate,imas_users.FirstName,imas_users.LastName ";
	$query .= "FROM imas_msgs,imas_users WHERE imas_msgs.msgto=:msgto AND imas_msgs.msgfrom=imas_users.id ";
	$query .= "AND (imas_msgs.isread=0 OR imas_msgs.isread=4) ORDER BY imas_msgs.senddate DESC";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':msgto'=>$userid));
	if ($stm->rowCount()>0) {
		echo "<div style='font-size:100%; font-weight: 700; background-color: #ccf; '>New Messages</div>";
		echo '<table border=0 cellspacing=2>';
    $intable = true;
	} else {
		echo "<div style='font-size:100%; font-weight: 700; background-color: #ccf; '>No New Messages</div>";
    $intable = false;
	}
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$n = 0;
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		$line['title'] = Sanitize::encodeStringForDisplay($line['title']);
		if ($n==1) {
			$line['title'] = 'Re: '.$line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
		}
		$url = "/msgs/viewmsg.php?cid=0&amp;type=msg&amp;msgid=".$line['id'];
		echo "<tr><td>";
		echo "<a style='color: blue;' href='$url' target='_new'>".$line['title']."</a></td>";
		echo "<td><span style='color: black;'>".Sanitize::encodeStringForDisplay("{$line['LastName']}, {$line['FirstName']}")."</span><br/>";
		echo "<span style='color: gray;'>".Sanitize::encodeStringForDisplay(formatdate($line['senddate']))."</span></td></tr>";
	}
	if ($intable) {
		echo '</table>';
	}

?>
</body>
</html>
