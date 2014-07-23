<?php
	//Displays forums posts
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
	
	$cid = $_GET['cid'];
	$forumid = $_GET['forum'];
	$threadid = $_GET['thread'];
	$page = $_GET['page'];
	//special "page"s
	//-1 new posts from forum page
	//-2 tagged posts from forum page
	//-3 new posts from newthreads page
	//-4 forum search
	
	if (isset($_GET['markunread'])) {
		$query = "DELETE FROM imas_forum_views WHERE userid='$userid' AND threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($page==-4) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forums.php?cid=$cid");
		} else if ($page==-3) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newthreads.php?cid=$cid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?cid=$cid&forum=$forumid&page=$page");
		}
		exit;
	}
	if (isset($_GET['marktagged'])) {
		$query = "UPDATE imas_forum_views SET tagged=1 WHERE userid='$userid' AND threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($page==-4) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forums.php?cid=$cid");
		} else if ($page==-3) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newthreads.php?cid=$cid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?cid=$cid&forum=$forumid&page=$page");
		}
		exit;
	} else if (isset($_GET['markuntagged'])) {
		$query = "UPDATE imas_forum_views SET tagged=0 WHERE userid='$userid' AND threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($page==-4) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forums.php?cid=$cid");
		} else if ($page==-3) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newthreads.php?cid=$cid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?cid=$cid&forum=$forumid&page=$page");
		}
		exit;
	}
	$query = "SELECT settings,replyby,defdisplay,name,points,groupsetid,postby,rubric,tutoredit FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	list($forumsettings, $replyby, $defdisplay, $forumname, $pointsposs, $groupset, $postby, $rubric, $tutoredit) = mysql_fetch_row($result);
	
	$allowreply = ($isteacher || (time()<$replyby));
	$allowanon = (($forumsettings&1)==1);
	$allowmod = ($isteacher || (($forumsettings&2)==2));
	$allowdel = ($isteacher || (($forumsettings&4)==4));
	$allowlikes = (($forumsettings&8)==8);
	$postbeforeview = (($forumsettings&16)==16);
	$haspoints =  ($pointsposs > 0);
	$groupid = 0;
	
	$canviewall = (isset($teacherid) || isset($tutorid));
	$caneditscore = (isset($teacherid) || (isset($tutorid) && $tutoredit==1));
	$canviewscore = (isset($teacherid) || (isset($tutorid) && $tutoredit<2));
	
	if ($groupset>0) {
		if (!isset($_GET['grp'])) {
			if (!$canviewall) {
				$query = 'SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
				$query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='$groupset'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$groupid = mysql_result($result,0,0);
				} else {
					$groupid=0;
				}
			} else {
				$groupid = -1;
			}
		} else {
			if (!$canviewall) {
				$groupid = intval($_GET['grp']);
				$query = "SELECT id FROM imas_stugroupmembers WHERE stugroupid='$groupid' AND userid='$userid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)==0) {
					echo 'Invalid group - try again';
					exit;
				}
			} else {
				$groupid = intval($_GET['grp']);
			}
		}
	}
	$placeinhead = '';
	if ($haspoints && $caneditscore && $rubric != 0) {
		$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=120311"></script>';
		require("../includes/rubric.php");
	}
		
		
	if (isset($_GET['view'])) {
		$view = $_GET['view'];
	} else {
		$view = $defdisplay;  //0: expanded, 1: collapsed, 2: condensed
	}
	
	$caller = "posts";
	include("posthandler.php");
	
	$pagetitle = "Posts";
	$placeinhead .= '<link rel="stylesheet" href="'.$imasroot.'/forums/forums.css?ver=022410" type="text/css" />';
	//$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	
	if ($haspoints && $caneditscore && $rubric != 0) {
		$query = "SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id=$rubric";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$row = mysql_fetch_row($result);
			echo printrubrics(array($row));
		}
	}
	
	$allowmsg = false;
	if (!$canviewall) {
		$query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ((mysql_result($result,0,0)%5)==0) {
			$allowmsg = true;
		} 
	}
	if ($postbeforeview && !$canviewall) {
		$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND parent=0 AND userid='$userid' LIMIT 1";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$oktoshow = (mysql_num_rows($result)>0);
		if (!$oktoshow) {
			$query = "SELECT posttype FROM imas_forum_posts WHERE id='$threadid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$oktoshow = (mysql_result($result,0,0)>0);
		}
	} else {
		$oktoshow = true;
	}
	
	if ($oktoshow) {
		if ($haspoints) {
			$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_grades.score,imas_grades.feedback,imas_students.section FROM ";
			$query .= "imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id ";
			$query .= "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid='$cid' ";
			$query .= "LEFT JOIN imas_grades ON imas_grades.gradetype='forum' AND imas_grades.refid=imas_forum_posts.id ";
			$query .= "WHERE (imas_forum_posts.id='$threadid' OR imas_forum_posts.threadid='$threadid') ORDER BY imas_forum_posts.id";
		} else {
			$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_students.section FROM ";
			$query .= "imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id ";
			$query .= "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid='$cid' ";
			$query .= "WHERE (imas_forum_posts.id='$threadid' OR imas_forum_posts.threadid='$threadid') ORDER BY imas_forum_posts.id";
			
			//$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg from imas_forum_posts,imas_users ";
			//$query .= "WHERE imas_forum_posts.userid=imas_users.id AND (imas_forum_posts.id='$threadid' OR imas_forum_posts.threadid='$threadid') ORDER BY imas_forum_posts.id";	
		}
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$children = array(); $date = array(); $subject = array(); $message = array(); $posttype = array(); $likes = array(); $mylikes = array();
		$ownerid = array(); $files = array(); $points= array(); $feedback= array(); $poster= array(); $email= array(); $hasuserimg = array(); $section = array();
		while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['parent']==0) {
				if ($line['replyby']!=null) {
					$allowreply = ($canviewall || (time()<$line['replyby']));
				}
			}
			
			if ($line['id']==$threadid) {
				$newviews = $line['views']+1;
			}
			$children[$line['parent']][] = $line['id'];
			$date[$line['id']] = $line['postdate'];
			$n = 0;
			while (strpos($line['subject'],'Re: ')===0) {
				$line['subject'] = substr($line['subject'],4);
				$n++;
			}
			if ($n==1) {
				$line['subject'] = 'Re: '.$line['subject'];
			} else if ($n>1) {
				$line['subject'] = "Re<sup>$n</sup>: ".$line['subject'];
			}
				
			$subject[$line['id']] = $line['subject'];
			if ($sessiondata['graphdisp']==0) {
				$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
			}
			$message[$line['id']] = $line['message'];
			$posttype[$line['id']] = $line['posttype'];
			$ownerid[$line['id']] = $line['userid'];
			$hasuserimg[$line['id']] = $line['hasuserimg'];
			
			if ($line['files']!='') {
				$files[$line['id']] = $line['files'];
			}
			if ($haspoints && $line['score']!==null) {
				$points[$line['id']] = 1*$line['score'];
				$feedback[$line['id']] = $line['feedback'];
			} else {
				$points[$line['id']] = $line['score'];
				$feedback[$line['id']] = null;
			}
			if ($line['isanon']==1) {
				$poster[$line['id']] = "Anonymous";
				$ownerid[$line['id']] = 0;
			} else {
				$poster[$line['id']] = $line['FirstName'] . ' ' . $line['LastName'];
				$section[$line['id']] = $line['section'];
				$email[$line['id']] = $line['email'];
			}
			$likes[$line['id']] = array(0,0,0);
			
		}
		
		if ($allowlikes) {
			//get likes
			$query = "SELECT postid,type,count(*) FROM imas_forum_likes WHERE threadid='$threadid'";
			$query .= "GROUP BY postid,type";	
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$likes[$row[0]][$row[1]] = $row[2];
			}
			
			$query = "SELECT postid FROM imas_forum_likes WHERE threadid='$threadid' AND userid='$userid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$mylikes[] = $row[0];
			}
		}
		
		if (count($files)>0) {
			require_once('../includes/filehandler.php');
		}
		//update view count
		$query = "UPDATE imas_forum_posts SET views='$newviews' WHERE id='$threadid'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		
		$query = "UPDATE imas_forum_threads SET views=views+1 WHERE id='$threadid'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		
		//mark as read
		$query = "SELECT lastview,tagged FROM imas_forum_views WHERE userid='$userid' AND threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$now = time();
		if (mysql_num_rows($result)>0) {
			$lastview = mysql_result($result,0,0);
			$tagged = mysql_result($result,0,1);
			$query = "UPDATE imas_forum_views SET lastview=$now WHERE userid='$userid' AND threadid='$threadid'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
		} else {
			$lastview = 0;
			$tagged = 0;
			$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','$threadid',$now)";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
		}
	}
	
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
	if ($page==-4) {
		echo "<a href=\"forums.php?cid=$cid\">Forum Search</a> ";	
	} else if ($page==-3) {
		echo "<a href=\"newthreads.php?cid=$cid\">New Threads</a> ";
	} else {
		echo "<a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">$forumname</a> ";
	}
	echo "&gt; Posts</div>\n";
	
	if (!$oktoshow) {
		echo '<p>This post is blocked. In this forum, you must post your own thread before you can read those posted by others.</p>';
	} else {
		echo '<div id="headerposts" class="pagetitle"><h2>Forum: '.$forumname.'</h2></div>';
		echo "<b style=\"font-size: 120%\">Post: {$subject[$threadid]}</b><br/>\n";
		
		$query = "SELECT id FROM imas_forum_threads WHERE forumid='$forumid' AND id<'$threadid' ";
		if ($groupset>0 && $groupid!=-1) {$query .= "AND (stugroupid='$groupid' OR stugroupid=0) ";}
		$query .= "ORDER BY id DESC LIMIT 1";
		//$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND threadid<'$threadid' AND parent=0 ORDER BY threadid DESC LIMIT 1";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$prevth = '';
		if (mysql_num_rows($result)>0) {
			$prevth = mysql_result($result,0,0);
			echo "<a href=\"posts.php?cid=$cid&forum=$forumid&thread=$prevth&grp=$groupid\">Prev</a> ";
		} else {
			echo "Prev ";
		}
		
		$query = "SELECT id FROM imas_forum_threads WHERE forumid='$forumid' AND id>'$threadid' ";
		if ($groupset>0 && $groupid!=-1) {$query .= "AND (stugroupid='$groupid' OR stugroupid=0) ";}
		$query .= "ORDER BY id LIMIT 1";
		//$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND threadid>'$threadid' AND parent=0 ORDER BY threadid LIMIT 1";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$nextth = '';
		if (mysql_num_rows($result)>0) {
			$nextth = mysql_result($result,0,0);
			echo "<a href=\"posts.php?cid=$cid&forum=$forumid&thread=$nextth&grp=$groupid\">Next</a>";
		} else {
			echo "Next";
		}
		echo " | <a href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page&markunread=true\">Mark Unread</a>";
		if ($tagged) {
			echo " | <a href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page&markuntagged=true\">Unflag</a>";
		} else {
			echo " | <a href=\"posts.php?cid=$cid&forum=$forumid&thread=$threadid&page=$page&marktagged=true\">Flag</a>";
		}
		//echo "<br/><b style=\"font-size: 120%\">Post: {$subject[$threadid]}</b><br/>\n";
		//echo "<b style=\"font-size: 100%\">Forum: $forumname</b></p>";
		echo " | <input type=button value=\"Expand All\" onclick=\"expandall()\"/>";
		echo "<input type=button value=\"Collapse All\" onclick=\"collapseall()\"/> | ";
		echo " <input type=button value=\"Show All\" onclick=\"showall()\"/>";
		echo "<input type=button value=\"Hide All\" onclick=\"hideall()\"/> ";
		/*if ($view==2) {
			echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=0\">View Expanded</a>";
		} else {
			echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=2\">View Condensed</a>";
		}*/
			
	?>
		<script type="text/javascript">
		function toggleshow(bnum) {
		   var node = document.getElementById('block'+bnum);
		   var butn = document.getElementById('butb'+bnum);
		   if (node.className == 'forumgrp') {
		       node.className = 'hidden';
		       //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
		//       butn.value = 'Expand';
			butn.src = imasroot+'/img/expand.gif';
		   } else { 
		       node.className = 'forumgrp';
		       //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
		//       butn.value = 'Collapse';
			butn.src = imasroot+'/img/collapse.gif';
		}
		}
		function toggleitem(inum) {
		   var node = document.getElementById('item'+inum);
		   var butn = document.getElementById('buti'+inum);
		   if (node.className == 'blockitems') {
		       node.className = 'hidden';
		       butn.value = 'Show';
		   } else { 
		       node.className = 'blockitems';
		       butn.value = 'Hide';
		   }
		}
		function expandall() {
		   for (var i=0;i<bcnt;i++) {
		     var node = document.getElementById('block'+i);
		     var butn = document.getElementById('butb'+i);
		     node.className = 'forumgrp';
		//     butn.value = 'Collapse';
		       //if (butn.value=='Expand' || butn.value=='Collapse') {butn.value = 'Collapse';} else {butn.value = '-';}
		       butn.src = imasroot+'/img/collapse.gif';
		   }
		}
		function collapseall() {
		   for (var i=0;i<bcnt;i++) {
		     var node = document.getElementById('block'+i);
		     var butn = document.getElementById('butb'+i);
		     node.className = 'hidden';
		//     butn.value = 'Expand';
		       //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
		       butn.src = imasroot+'/img/expand.gif';
		   }
		}
		
		function showall() {
		   for (var i=0;i<icnt;i++) {
		     var node = document.getElementById('item'+i);
		     var buti = document.getElementById('buti'+i);
		     node.className = "blockitems";
		     buti.value = "Hide";
		   }
		}
		function hideall() {
		   for (var i=0;i<icnt;i++) {
		     var node = document.getElementById('item'+i);
		     var buti = document.getElementById('buti'+i);
		     node.className = "hidden";
		     buti.value = "Show";
		   }
		}
		function savelike(el) {
			var like = (el.src.match(/gray/))?1:0;
			var postid = el.id.substring(8);
			$(el).parent().append('<img style="vertical-align: middle" src="../img/updating.gif" id="updating"/>');
			$.ajax({
				url: "recordlikes.php",
				data: {cid:<?php echo $cid;?>, postid: postid, like: like},
				dataType: "json"
			}).done(function(msg) {
				if (msg.aff==1) {
					el.title = msg.msg;
					$('#likecnt'+postid).text(msg.cnt>0?msg.cnt:'');
					el.className = "likeicon"+msg.classn;
					if (like==0) {
						el.src = el.src.replace("liked","likedgray");
					} else {
						el.src = el.src.replace("likedgray","liked");
					}
				}
				$('#updating').remove();
			});
		}
		</script>
	<?php
		$bcnt = 0;
		$icnt = 0;
		function printchildren($base,$restricttoowner=false) {
			$curdir = rtrim(dirname(__FILE__), '/\\');
			global $children,$date,$subject,$message,$poster,$email,$forumid,$threadid,$isteacher,$cid,$userid,$ownerid,$points;
			global $feedback,$posttype,$lastview,$bcnt,$icnt,$myrights,$allowreply,$allowmod,$allowdel,$allowlikes,$view,$page,$allowmsg;
			global $haspoints,$imasroot,$postby,$replyby,$files,$CFG,$rubric,$pointsposs,$hasuserimg,$urlmode,$likes,$mylikes,$section;
			global $canviewall, $caneditscore, $canviewscore;
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
			foreach($children[$base] as $child) {
				if ($restricttoowner && $ownerid[$child] != $userid) {
					continue;
				}
				echo "<div class=block> ";
				echo '<span class="leftbtns">';
				if (isset($children[$child])) {
					if ($view==1) { 
						$lbl = '+';
						$img = "expand";
					} else {
						$lbl = '-';
						$img = "collapse";
					}
					//echo "<input type=button id=\"butb$bcnt\" value=\"$lbl\" onClick=\"toggleshow($bcnt)\"> ";
					echo "<img class=\"pointer\" id=\"butb$bcnt\" src=\"$imasroot/img/$img.gif\" onClick=\"toggleshow($bcnt)\"/> ";
				}
				if ($hasuserimg[$child]==1) {
					if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
						echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$ownerid[$child]}.jpg\"  onclick=\"togglepic(this)\" />";
					} else {
						echo "<img src=\"$imasroot/course/files/userimg_sm{$ownerid[$child]}.jpg\"  onclick=\"togglepic(this)\" />";
					}
				}
				echo '</span>';
				echo "<span class=right>";
				
				if ($view==2) {
					echo "<input type=button id=\"buti$icnt\" value=\"Show\" onClick=\"toggleitem($icnt)\">\n";
				} else {
					echo "<input type=button id=\"buti$icnt\" value=\"Hide\" onClick=\"toggleitem($icnt)\">\n";
				}
				
				if ($isteacher) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&move=$child\">Move</a> \n";
				} 
				if ($isteacher || ($ownerid[$child]==$userid && $allowmod)) {
					if (($base==0 && time()<$postby) || ($base>0 && time()<$replyby) || $isteacher) {
						echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=$child\">Modify</a> \n";
					}
				}
				if ($isteacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&remove=$child\">Remove</a> \n";
				}
				if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=reply&replyto=$child\">Reply</a>";
				}
				
				echo "</span>\n";
				echo '<span style="float:left">';
				echo "<b>{$subject[$child]}</b><br/>Posted by: ";
				//if ($isteacher && $ownerid[$child]!=0) {
				//	echo "<a href=\"mailto:{$email[$child]}\">";
				//} else if ($allowmsg && $ownerid[$child]!=0) {
				if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
					echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to={$ownerid[$child]}\" ";
					if ($section[$child]!='') {
						echo 'title="Section: '.$section[$child].'"';
					}
					echo ">";
				}
				echo $poster[$child];
				if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
					echo "</a>";
				}
				if ($isteacher && $ownerid[$child]!=0 && $ownerid[$child]!=$userid) {
					 echo " <a class=\"small\" href=\"$imasroot/course/gradebook.php?cid=$cid&stu={$ownerid[$child]}\" target=\"_popoutgradebook\">[GB]</a>";
				}
				echo ', ';
				echo tzdate("D, M j, Y, g:i a",$date[$child]);
				
				if ($date[$child]>$lastview) {
					echo " <span style=\"color:red;\">New</span>\n";
				}
				echo '</span>';
				
				if ($allowlikes) {
					$icon = (in_array($child,$mylikes))?'liked':'likedgray';
					$likemsg = 'Liked by ';
					$likecnt = 0;
					$likeclass = '';
					if ($likes[$child][0]>0) {
						$likeclass = ' liked';
						$likemsg .= $likes[$child][0].' ' . ($likes[$child][0]==1?'student':'students');
						$likecnt += $likes[$child][0];
					}
					if ($likes[$child][1]>0 || $likes[$child][2]>0) {
						$likeclass = ' likedt';
						$n = $likes[$child][1] + $likes[$child][2];
						if ($likes[$child][0]>0) { $likemsg .= ' and ';}
						$likemsg .= $n.' ';
						if ($likes[$child][2]>0) {
							$likemsg .= ($n==1?'teacher':'teachers');
							if ($likes[$child][1]>0) {
								$likemsg .= '/tutors/TAs';
							}
						} else if ($likes[$child][1]>0) {
							$likemsg .= ($n==1?'tutor/TA':'tutors/TAs');
						}
						$likecnt += $n;
					}
					if ($likemsg=='Liked by ') {
						$likemsg = '';
					} else {
						$likemsg .= '.';
					}
					if ($icon=='liked') {
						$likemsg = 'You like this. '.$likemsg;
					} else {
						$likemsg = 'Click to like this post. '.$likemsg;;
					}
					
					echo '<div class="likewrap">';
					echo "<img id=\"likeicon$child\" class=\"likeicon$likeclass\" src=\"$imasroot/img/$icon.png\" title=\"$likemsg\" onclick=\"savelike(this)\">";
					echo " <span class=\"pointer\" id=\"likecnt$child\" onclick=\"GB_show('"._('Post Likes')."','listlikes.php?cid=$cid&amp;post=$child',500,500);\">".($likecnt>0?$likecnt:'').' </span> ';
					echo '</div>';
				}
				echo '<div class="clear"></div>';
				echo "</div>\n";
				if ($view==2) {
					echo "<div class=hidden id=\"item$icnt\">";
				} else {
					echo "<div class=blockitems id=\"item$icnt\" style=\"clear:all\">";
				}
				if(isset($files[$child]) && $files[$child]!='') {
					$fl = explode('@@',$files[$child]);
					if (count($fl)>2) {
						echo '<p><b>Files:</b> ';//<ul class="nomark">';
					} else {
						echo '<p><b>File:</b> ';
					}
					for ($i=0;$i<count($fl)/2;$i++) {
						//if (count($fl)>2) {echo '<li>';}
						echo '<a href="'.getuserfileurl('ffiles/'.$child.'/'.$fl[2*$i+1]).'" target="_blank">';
						$extension = ltrim(strtolower(strrchr($fl[2*$i+1],".")),'.');
						if (isset($itemicons[$extension])) {
							echo "<img alt=\"$extension\" src=\"$imasroot/img/{$itemicons[$extension]}\" class=\"mida\"/> ";
						} else {
							echo "<img alt=\"doc\" src=\"$imasroot/img/doc.png\" class=\"mida\"/> ";
						}
						echo $fl[2*$i].'</a> ';
						//if (count($fl)>2) {echo '</li>';}
					}
					//if (count($fl)>2) {echo '</ul>';}
					echo '</p>';
				}
				echo filter($message[$child]);
				if ($haspoints) {
					if ($caneditscore && $ownerid[$child]!=$userid) {
						echo '<hr/>';
						echo "Score: <input type=text size=2 name=\"score[$child]\" id=\"scorebox$child\" value=\"";
						if ($points[$child]!==null) {
							echo $points[$child];
						}
						echo "\"/> ";
						if ($rubric != 0) {
							echo printrubriclink($rubric,$pointsposs,"scorebox$child", "feedback$child");
						}
						echo " Private Feedback: <textarea cols=\"50\" rows=\"2\" name=\"feedback[$child]\" id=\"feedback$child\">";
						if ($feedback[$child]!==null) {
							echo $feedback[$child];
						}
						echo "</textarea>";
					} else if (($ownerid[$child]==$userid || $canviewscore) && $points[$child]!==null) {
						echo '<div class="signup">Score: ';
						echo "<span class=red>{$points[$child]} points</span><br/> ";
						if ($feedback[$child]!==null && $feedback[$child]!='') {
							echo 'Private Feedback: ';
							echo $feedback[$child];
						}
						echo '</div>';
					}
				}
				
				
				echo "<div class=\"clear\"></div></div>\n";
				$icnt++;
				if (isset($children[$child])) { //if has children
					echo "<div class=";
					if ($view==0 || $view==2) {
						echo '"forumgrp"';
					} else if ($view==1) {
						echo '"hidden"';
					}
					echo " id=\"block$bcnt\">\n";
					$bcnt++;
					printchildren($child, ($posttype[$child]==3 && !$isteacher));
					echo "</div>\n";
				}
			//}
			}
		}
		if ($caneditscore && $haspoints) {
			echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=$page&score=true\">";
		}
		printchildren(0);
		if ($caneditscore && $haspoints) {
			echo '<div><input type=submit name="save" value="Save Grades" /></div>';
			if ($prevth!='' && $page!=-3) {
				echo '<input type="hidden" name="prevth" value="'.$prevth.'"/>';
				echo '<input type="submit" name="save" value="Save Grades and View Previous"/>';
			}
			if ($nextth!='' && $page!=-3) {
				echo '<input type="hidden" name="nextth" value="'.$nextth.'"/>';
				echo '<input type="submit" name="save" value="Save Grades and View Next"/>';
			}
			echo "</form>";
		}
		echo "<img src=\"$imasroot/img/expand.gif\" style=\"visibility:hidden\" />";
		echo "<img src=\"$imasroot/img/collapse.gif\" style=\"visibility:hidden\" />";
		
		echo "<script type=\"text/javascript\">";
		echo "var bcnt =$bcnt; var icnt = $icnt;\n";
		echo "</script>";
	}
	echo "<div class=right><a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Back to Forum Topics</a></div>\n";
	require("../footer.php");
?>
