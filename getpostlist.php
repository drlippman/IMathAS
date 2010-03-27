<?php
//IMathAS:  New threads and messages list for a course using remoteaccess code
//for mobile access w/o logging in 
//(c) 2009 David Lippman
   	require("config.php");
	if (!empty($_COOKIE['remoteaccess']) && strlen($_COOKIE['remoteaccess'])==10) {
		$_GET['key'] = $_COOKIE['remoteaccess'];
	} else if (empty($_GET['key']) || strlen(trim($_GET['key']))!=10) {
		echo "Key Error";
		exit;
	} else {
		setcookie('remoteaccess',$_GET['key'], time()+60*60*24*30*365*10);	
	}
	//look up user
	$query = "SELECT id FROM imas_users WHERE remoteaccess='{$_GET['key']}'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "Access key invalid";
		exit;
	}
	$userid = mysql_result($result,0,0);
	$tzoffset = $_GET['tzoffset'];
	
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
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
		$query = "SELECT imas_forums.courseid, imas_forums.name, imas_forums.id, imas_forum_threads.id as tid, imas_forum_threads.lastposttime FROM imas_forum_threads ";
		$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
		$query .= "JOIN imas_teachers ON imas_forums.courseid=imas_teachers.courseid AND imas_teachers.userid='$userid' ";
		$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
		$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
		//$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
		$query .= "ORDER BY imas_forum_threads.lastposttime DESC LIMIT 30";
	
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
			$forumname[$line['id']] = $line['name'];
			$lastpost[$line['tid']] = formatdate($line['lastposttime']);
		}
	}
	if ($_GET['limit'] != 'teach') {  //take or all
		$query = "SELECT imas_forums.courseid, imas_forums.name, imas_forums.id, imas_forum_threads.id as tid, imas_forum_threads.lastposttime FROM imas_forum_threads ";
		$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
		$query .= "JOIN imas_students ON imas_forums.courseid=imas_students.courseid AND imas_students.userid='$userid' ";
		$query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' ";
		$query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
		$query .= "ORDER BY imas_forum_threads.lastposttime DESC LIMIT 30";
	
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
			$forumname[$line['id']] = $line['name'];
			$lastpost[$line['tid']] = formatdate($line['lastposttime']);
		}
	}
	
	$coursenames = array();
	$cidlist = implode(',',array_keys($courseforums));
	$query = "SELECT id,name FROM imas_courses WHERE id IN ($cidlist)";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$coursenames[$row[0]] = $row[1];
	}
	asort($coursenames);
	if (count($lastpost)>0) {
		$threadids = implode(',',array_keys($lastpost));
		$query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
		$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.id IN ($threadids) ORDER BY imas_forum_posts.postdate DESC";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$lastforum = '';
		$lastcourse = '';
		$forumcontent = array();
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (!isset($forumcontent[$line['forumid']])) {
				$forumcontent[$line['forumid']] = '';
			}
			$course = $forumcourse[$line['forumid']];
			$url = $imasroot."/forums/posts.php?page=-2&amp;cid=$course&amp;forum={$line['forumid']}&amp;thread={$line['threadid']}";
			/*$forumcontent[$line['forumid']] .= "<div style='font-size:100%;'>";
			$forumcontent[$line['forumid']] .= "<a style='color: blue;' href='$url' target='_new'>{$line['subject']}</a>";
			$forumcontent[$line['forumid']] .= " <span style='color: black;'>".htmlspecialchars("{$line['LastName']}, {$line['FirstName']}")."</span>";
			$forumcontent[$line['forumid']] .= " <span style='color: gray;'>".htmlspecialchars($lastpost[$line['threadid']])."</span></div>";
			*/
			$forumcontent[$line['forumid']] .= "<tr><td><a style='color: blue;' href='$url' target='_new'>{$line['subject']}</a></td>";
			$forumcontent[$line['forumid']] .= "<td><span style='color: black;'>".htmlspecialchars("{$line['LastName']}, {$line['FirstName']}")."</span><br/>";
			$forumcontent[$line['forumid']] .= "<span style='color: gray;'>".htmlspecialchars($lastpost[$line['threadid']])."</span></td></tr>";
		}
		
		echo "<div style='font-size:100%; font-weight: 700; background-color: #ccf; margin-below:5px;'>New Posts</div>";
		foreach($coursenames as $id=>$name) {
			echo "<div style='font-size:100%; color: #606; font-weight: 700; '>$name</div>";
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
	
	
	$query = "SELECT imas_msgs.id,imas_msgs.courseid,imas_msgs.title,imas_msgs.senddate,imas_users.FirstName,imas_users.LastName ";
	$query .= "FROM imas_msgs,imas_users WHERE imas_msgs.msgto='$userid' AND imas_msgs.msgfrom=imas_users.id ";
	$query .= "AND (imas_msgs.isread=0 OR imas_msgs.isread=4) ORDER BY imas_msgs.senddate DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)>0) {
		echo "<div style='font-size:100%; font-weight: 700; background-color: #ccf; '>New Messages</div>";
		echo '<table border=0 cellspacing=2>';
	} else {
		echo "<div style='font-size:100%; font-weight: 700; background-color: #ccf; '>No New Messages</div>";	
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$n = 0;
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		$line['title'] = htmlspecialchars($line['title']);
		if ($n==1) {
			$line['title'] = 'Re: '.$line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
		}
		$url = "/msgs/viewmsg.php?cid=0&amp;type=msg&amp;msgid=".$line['id'];	
		echo "<tr><td>";
		echo "<a style='color: blue;' href='$url' target='_new'>".$line['title']."</a></td>";
		echo "<td><span style='color: black;'>".htmlspecialchars("{$line['LastName']}, {$line['FirstName']}")."</span><br/>";
		echo "<span style='color: gray;'>".htmlspecialchars(formatdate($line['senddate']))."</span></td></tr>";
	}
	if (mysql_num_rows($result)>0) {
		echo '</table>';
	}
	
?>
</body>
</html>
