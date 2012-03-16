<?php
//IMathAS:  New threads and messages list for course; part of Google postreader
//(c) 2009 David Lippman
   	require("config.php");
	if (empty($_GET['key']) || strlen(trim($_GET['key']))!=10) {
		echo "Key Error";
		exit;
	}
	//look up user
	$query = "SELECT id FROM imas_users WHERE remoteaccess='{$_GET['key']}'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
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
	return tzdate("D n/j/y, g:i a",$date);   
	//return tzdate("M j, Y, g:i a",$date);   
  }
	
	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8" ?>';
	
	$courseforums = array();
	$forumthreads = array();
	$forumname = array();
	$lastpost = array();
	$forumcourse = array();
	if ($_GET['limit'] != 'take') {//teach or all
		$query = "SELECT imas_forums.courseid, imas_forums.name, imas_forums.id, imas_forum_threads.id as tid, imas_forum_threads.lastposttime FROM imas_forum_threads ";
		$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
		$query .= "JOIN imas_courses ON imas_forums.courseid=imas_courses.id AND (imas_courses.available=0 OR imas_courses.available=1) ";
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
		$query .= "JOIN imas_courses ON imas_forums.courseid=imas_courses.id AND (imas_courses.available=0 OR imas_courses.available=2) ";
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
			$forumcontent[$line['forumid']] .= '<post>';
			$forumcontent[$line['forumid']] .= '<id>'. $line['id'] .'</id>';
			$forumcontent[$line['forumid']] .= "<subject>".htmlspecialchars($line['subject'])."</subject>";
			$forumcontent[$line['forumid']] .= "<author>".htmlspecialchars("{$line['LastName']}, {$line['FirstName']}")."</author>";
			$forumcontent[$line['forumid']] .= "<date>".htmlspecialchars($lastpost[$line['id']])."</date>";
			$forumcontent[$line['forumid']] .= '</post>';
		}
	}
	
	//output forum posts XML
	echo '<imathasfeed>';
	echo '<baseurl>';
	echo htmlspecialchars("http://" . $_SERVER['HTTP_HOST'] . $imasroot);
	echo '</baseurl>';
	echo '<postslist>';
	foreach($coursenames as $id=>$name) {
		echo '<course name="'.htmlspecialchars($name).'" id="'.$id.'">';
		asort($courseforums[$id]);
		foreach($courseforums[$id] as $fid) {
			echo '<forum name="'.htmlspecialchars($forumname[$fid]).'" id="'.$fid.'">';
			echo $forumcontent[$fid];
			echo '</forum>';
		}
		echo '</course>';
	}
	echo '</postslist>';
	
	$query = "SELECT imas_msgs.id,imas_msgs.courseid,imas_msgs.title,imas_msgs.senddate,imas_users.FirstName,imas_users.LastName ";
	$query .= "FROM imas_msgs,imas_users WHERE imas_msgs.msgto='$userid' AND imas_msgs.msgfrom=imas_users.id ";
	$query .= "AND (imas_msgs.isread=0 OR imas_msgs.isread=4) ORDER BY imas_msgs.senddate DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	echo '<msglist>';
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$n = 0;
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		if ($n==1) {
			$line['title'] = 'Re: '.$line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
		}
		echo '<msg>';
		echo '<id>'.$line['id'].'</id>';
		echo '<subject>'.htmlspecialchars($line['title']).'</subject>';
		echo "<author>".htmlspecialchars("{$line['LastName']}, {$line['FirstName']}")."</author>";
		echo '<date>'.htmlspecialchars(formatdate($line['senddate'])).'</date>';
		echo '</msg>';
	}
	echo '</msglist>';
	echo '</imathasfeed>';
	
?>
