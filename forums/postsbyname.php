<?php
	//Lists forum posts by Student name
	//(c) 2006 David Lippman

	require_once "../init.php";


	/*if (!isset($teacherid) && !isset($tutorid)) {
	   require_once "../header.php";
	   echo "You must be a teacher to access this page\n";
	   require_once "../footer.php";
	   exit;
	}*/
	if (isset($teacherid)) {
		$isteacher = true;
	} else {
		$isteacher = false;
	}

	$forumid = Sanitize::onlyInt($_GET['forum']);
    $cid = Sanitize::courseId($_GET['cid']);
    $page = Sanitize::onlyInt($_GET['page'] ?? 0);

	if (isset($_GET['markallread'])) {
		$stm = $DBH->prepare("SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid=:forumid");
		$stm->execute(array(':forumid'=>$forumid));
		$now = time();
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            /*
            $stm2 = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) 
                VALUES (:userid, :threadid, :lastview)
                ON DUPLICATE KEY UPDATE lastview=:lastview2");
		    $stm2->execute(array(':userid'=>$userid, ':threadid'=>$row[0], ':lastview'=>$now, ':lastview2'=>$now));
            */
            $stm2 = $DBH->prepare("SELECT lastview FROM imas_forum_views WHERE userid=:userid AND threadid=:threadid");
			$stm2->execute(array(':userid'=>$userid, ':threadid'=>$row[0]));
			if ($stm2->rowCount()>0) {
				$stm2 = $DBH->prepare("UPDATE imas_forum_views SET lastview=:lastview WHERE userid=:userid AND threadid=:threadid");
				$stm2->execute(array(':lastview'=>$now, ':userid'=>$userid, ':threadid'=>$row[0]));
			} else{
				$stm2 = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES (:userid, :threadid, :lastview)");
				$stm2->execute(array(':userid'=>$userid, ':threadid'=>$row[0], ':lastview'=>$now));
		    }
		}
	}
	$stm = $DBH->prepare("SELECT settings,replyby,defdisplay,name,points,rubric,tutoredit, groupsetid,autoscore FROM imas_forums WHERE id=:id");
	$stm->execute(array(':id'=>$forumid));
	list($forumsettings, $replyby, $defdisplay, $forumname, $pointspos, $rubric, $tutoredit, $groupsetid,$autoscore) = $stm->fetch(PDO::FETCH_NUM);
	$allowanon = (($forumsettings&1)==1);
	$allowmod = ($isteacher || (($forumsettings&2)==2));
	$allowdel = ($isteacher || (($forumsettings&4)==4));
	$postbeforeview = (($forumsettings&16)==16);
	$haspoints = ($pointspos>0);

	$canviewall = (isset($teacherid) || isset($tutorid));
	$caneditscore = (isset($teacherid) || (isset($tutorid) && ($tutoredit&1)==1));
	$canviewscore = (isset($teacherid) || (isset($tutorid) && $tutoredit!=2));
	$allowreply = ($canviewall || (time()<$replyby));

	$caller = "byname";
	require_once "posthandler.php";

	$placeinhead = '<link rel="stylesheet" href="'.$staticroot.'/forums/forums.css?ver=082911" type="text/css" />';
	if ($haspoints && $caneditscore && $rubric != 0) {
		$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/rubric.js?v=011823"></script>';
		require_once "../includes/rubric.php";
	}
	if ($caneditscore && $_SESSION['useed']!=0) {
		$useeditor = "noinit";
		$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",null,true);</script>';
	}
	$loadiconfont = true;
	require_once "../header.php";
    echo "<div class=breadcrumb>";
    if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
        echo "$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }    
    echo "<a href=\"thread.php?cid=$cid&forum=$forumid&page=".Sanitize::onlyInt($page)."\">Forum Topics</a> &gt; Posts by Name</div>\n";

	echo '<div id="headerpostsbyname" class="pagetitle">';
	echo "<h1>Posts by Name - ".Sanitize::encodeStringForDisplay($forumname)."</h1>\n";
	echo '</div>';
	if (!$canviewall && $postbeforeview) {
		$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE forumid=:forumid AND parent=0 AND userid=:userid LIMIT 1");
		$stm->execute(array(':forumid'=>$forumid, ':userid'=>$userid));
		if ($stm->rowCount()==0) {
			echo '<p>This page is blocked. In this forum, you must post your own thread before you can read those posted by others.</p>';
			require_once "../footer.php";
			exit;
		}
	}
?>
	<script type="text/javascript">
	function toggleshow(bnum) {
	   var node = document.getElementById('m'+bnum);
	   var butn = document.getElementById('butn'+bnum);
	   if (node.className == 'blockitems') {
	       node.className = 'hidden';
	       butn.value = '+';
	   } else {
	       node.className = 'blockitems';
	       butn.value = '-';
	   }
	   butn.setAttribute("aria-expanded", butn.value == '-');
	}
	function toggleshowall() {
	  for (var i=0; i<bcnt; i++) {
	    var node = document.getElementById('m'+i);
	    var butn = document.getElementById('butn'+i);
	    node.className = 'blockitems';
	    butn.value = '-';
		butn.setAttribute("aria-expanded", true);
	  }
	  document.getElementById("toggleall").value = 'Collapse All';
	  document.getElementById("toggleall").onclick = togglecollapseall;
	}
	function onsubmittoggle() {
		for (var i=0; i<bcnt; i++) {
		    var node = document.getElementById('m'+i);
		    node.className = 'pseudohidden';
		  }
	}
	function togglecollapseall() {
	  for (var i=0; i<bcnt; i++) {
	    var node = document.getElementById('m'+i);
	    var butn = document.getElementById('butn'+i);
	    node.className = 'hidden';
	    butn.value = '+';
		butn.setAttribute("aria-expanded", false);
	  }
	  document.getElementById("toggleall").value = 'Expand All';
	  document.getElementById("toggleall").onclick = toggleshowall;
	}
	function toggleposts(el) {
		if ($(el).text()==_("Show Posts")) {
			$(el).text(_("Hide Posts"));
			$(".initialpost").removeClass("pseudohidden");
		} else {
			$(el).text(_("Show Posts"));
			$(".initialpost").addClass("pseudohidden");
		}
	}
	function togglereplies(el) {
		if ($(el).text()==_("Show Replies")) {
			$(el).text(_("Hide Replies"));
			$(".reply").removeClass("pseudohidden");
		} else {
			$(el).text(_("Show Replies"));
			$(".reply").addClass("pseudohidden");
		}
	}
    $(function () {
        $("input[type=text][id^=score]").on('keyup', function() {
            var visel = $("input[type=text][id^=score]:visible");
            var idx = visel.index(this);
            if (idx != -1) {
                if (event.which == 38 && idx > 0) { 
                    idx--; 
                } else if ((event.which == 40 || event.which == 13) && idx < visel.length-1) {
                    idx++;
                }
                visel[idx].focus();
            }
        }).on('keypress', function() {
            if (event.which == 13) { event.preventDefault(); }
        });
    })
	

	function GBviewThread(threadid) {
		var qsb = "embed=true&cid="+cid+"&thread="+threadid+"&forum=<?php echo $forumid?>";
		GB_show(_("Thread"),"posts.php?"+qsb,800,"auto");
		return false;
	}
	function GBdoReply(threadid,postid) {
		var qsb = "embed=true&cid="+cid+"&thread="+threadid+"&forum=<?php echo $forumid?>";
		GB_show(_("Reply"),"posts.php?"+qsb+"&modify=reply&replyto="+postid,600,"auto",true,'','',{'func':'submitpost','label':'<?php echo _('Post Reply');?>'});
		return false;
	}
	</script>
<?php

	if ($haspoints && $caneditscore && $rubric != 0) {
		$stm = $DBH->prepare("SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
		$stm->execute(array(':id'=>$rubric));
		if ($stm->rowCount()>0) {
			$row = $stm->fetch(PDO::FETCH_NUM);
			// $row data is sanitized by printrubrics().
			echo printrubrics(array($row));
		}
	}

	$scores = array();
	$feedback = array();
	if ($haspoints) {
		$stm = $DBH->prepare("SELECT refid,score,feedback FROM imas_grades WHERE gradetype='forum' AND gradetypeid=:gradetypeid");
		$stm->execute(array(':gradetypeid'=>$forumid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$scores[$row[0]] = $row[1];
			$feedback[$row[0]] = $row[2];
		}
	}
	$dofilter = false;
	if (!$canviewall && $groupsetid>0) {
		$query = 'SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
		$query .= "WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':groupsetid'=>$groupsetid));
		if ($stm->rowCount()>0) {
			$groupid = $stm->fetchColumn(0);
		} else {
			$groupid=0;
		}
		$dofilter = true;
	}
	$blockreplythreads = array();
	if (!$canviewall) { //this should probably be refactored in a more elegant way
		$query = "SELECT threadid FROM imas_forum_posts WHERE forumid=:forumid AND parent=0 AND posttype=3 ";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':forumid'=>$forumid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$blockreplythreads[] = $row[0];
		}
	}
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,ifv.lastview,imas_students.id AS stuid FROM imas_forum_posts JOIN ";
	$query .= "imas_forum_threads AS ift ON ift.id=imas_forum_posts.threadid AND ift.lastposttime<:now JOIN imas_users ";
	$query .= "ON imas_forum_posts.userid=imas_users.id LEFT JOIN (SELECT DISTINCT threadid,lastview FROM imas_forum_views WHERE userid=:userid) AS ifv ON ";
	$query .= "ifv.threadid=imas_forum_posts.threadid ";
	$query .= "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid=:courseid ";
	$query .= "WHERE imas_forum_posts.forumid=:forumid AND imas_forum_posts.isanon=0 ";
	$arr = array(':userid'=>$userid, ':forumid'=>$forumid, ':now'=>$now, ":courseid"=>$cid);
	if ($dofilter) {
		//$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
		$query .= "AND (ift.stugroupid=0 OR ift.stugroupid=:stugroupid) ";
		$arr[':stugroupid']=$groupid;
	}
	$query .= "ORDER BY imas_users.LastName,imas_users.FirstName,imas_forum_posts.postdate DESC";
	// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());

	$stm = $DBH->prepare($query);
	$stm->execute($arr);

	$laststu = -1;
	$cnt = 0;
	echo "<input type=\"button\" value=\"Expand All\" onclick=\"toggleshowall()\" id=\"toggleall\"/> ";
	echo '<button type="button" onclick="toggleposts(this)">'._("Hide Posts").'</button> ';
	echo '<button type="button" onclick="togglereplies(this)">'._("Hide Replies").'</button> ';
	echo "<button type=\"button\" onclick=\"window.location.href='postsbyname.php?cid=$cid&forum=$forumid&markallread=true'\">"._('Mark all Read')."</button><br/>";

	if ($caneditscore && $haspoints) {
		echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=".Sanitize::onlyInt($page)."&score=true\" onsubmit=\"onsubmittoggle()\">";
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	function printuserposts($name, $uid, $content, $postcnt, $replycnt, $hasuserimg) {
        global $imasroot, $urlmode;
		printf("<b><pii class='pii-full-name'>%s</span></b> (", Sanitize::encodeStringForDisplay($name));
		echo $postcnt.($postcnt==1?' post':' posts').', '.$replycnt. ($replycnt==1?' reply':' replies').')';
		if ($hasuserimg==1) {
			echo '<button type=button class="plain nopad" onclick="togglepic(this)">';
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm".Sanitize::onlyInt($uid).".jpg\" alt=\"User picture\" />";
			} else {
				echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_sm".Sanitize::onlyInt($uid).".jpg\" alt=\"User picture\" />";
			}
			echo '</button>';
		}
		echo '<div class="forumgrp">'.$content.'</div>';
	}
	$content = ''; $postcnt = 0; $replycnt = 0; $lastname = ''; $lasthasuserimg = 0;
	while ($line =  $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($line['userid']!=$laststu) {
			if ($laststu!=-1) {
				printuserposts($lastname, $laststu, $content, $postcnt, $replycnt, $lasthasuserimg);
				$content = '';  $postcnt = 0; $replycnt = 0;
			}
            $laststu = $line['userid'];
            $lasthasuserimg = $line['hasuserimg'];
			$lastname = Sanitize::encodeStringForDisplay($line['LastName']).", " . Sanitize::encodeStringForDisplay($line['FirstName']);
		}

		if ($line['parent']!=0) {
			if ($line['userid']!=$userid && in_array($line['threadid'], $blockreplythreads)) { continue;}
			$content .= '<div class="reply"><div class="block flexgroup">';
			$replycnt++;
		} else {
			$content .= '<div class="initialpost"><div class="block flexgroup">';
			$postcnt++;
		}

		$content .= "<input type=\"button\" value=\"+\" onclick=\"toggleshow($cnt)\" id=\"butn$cnt\" aria-controls=\"m$cnt\" aria-expanded=\"false\" />";
		$content .= '<span style="flex-grow:1">';
		if ($line['parent']!=0) {
			$content .= '<span style="color:#060">';
		}
		if ($line['parent'] != 0) {
			$content .= '<span class="icon-forumreply" role="img" aria-label="reply" title="Reply"></span> ';
		} else {
			$content .= '<span class="icon-forumpost" role="img" aria-label="post" title="Post"></span> ';
		}
		$content .= '<b>'.Sanitize::encodeStringForDisplay($line['subject']).'</b>';
		if ($line['parent']!=0) {
			$content .= '</span>';
		}
		$dt = tzdate("D, M j, Y, g:i a",$line['postdate']);
		$content .= ', Posted: '.Sanitize::encodeStringForDisplay($dt);

		if ($line['lastview']==null || $line['postdate']>$line['lastview']) {
			$content .= " <span class=noticetext>New</span>\n";
		}
		$content .= '</span>';

		// right buttons
		$content .= '<span class="nowrap">';
		if ($haspoints) {
			if ($caneditscore && $line['stuid']!==null) {
				$content .= "<input type=text size=2 name=\"score[".Sanitize::onlyInt($line['id'])."]\" id=\"score".Sanitize::onlyInt($line['id'])."\" value=\"";
				if (isset($scores[$line['id']])) {
					$content .= Sanitize::encodeStringForDisplay($scores[$line['id']]);
				}

				$content .= '"/> <label for="score'.Sanitize::onlyInt($line['id']).'">'._('Points').'</label> ';
				if ($rubric != 0) {
					$content .= printrubriclink($rubric,$pointspos,"score".Sanitize::onlyInt($line['id']), "feedback".Sanitize::onlyInt($line['id'])).' ';
				}
			} else if (($line['userid']==$userid || $canviewscore) && isset($scores[$line['id']])) {
				$content .= "<span class=red> ".Sanitize::onlyFloat($scores[$line['id']])." points</span> ";
			}
		}
		//$content .= "<a href=\"posts.php?cid=$cid&forum=$forumid&thread=".Sanitize::onlyInt($line['threadid'])."\">Thread</a> ";
		$content .= "<a href=\"#\" onclick=\"return GBviewThread(".Sanitize::onlyInt($line['threadid']).")\">Thread</a> ";

		/* don't really need these links on this page
		if ($isteacher || ($line['userid']==$userid && $allowmod)) {
			$content .= "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread=".Sanitize::onlyInt($line['threadid'])."&modify=".Sanitize::onlyInt($line['id'])."\">Modify</a> \n";
		}
		if ($isteacher || ($allowdel && $line['userid']==$userid)) {
			$content .= "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread=".Sanitize::onlyInt($line['threadid'])."&remove=".Sanitize::onlyInt($line['id'])."\">Remove</a> \n";
		}
		*/
		if ($line['posttype']!=2 && $myrights > 5 && $allowreply) {
			$content .= "<a href=\"#\" onclick=\"return GBdoReply(".Sanitize::onlyInt($line['threadid']).",".Sanitize::onlyInt($line['id']).")\">Reply</a> ";
			//$content .= "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread=".Sanitize::onlyInt($line['threadid'])."&modify=reply&replyto=".Sanitize::onlyInt($line['id'])."\">Reply</a>";
		}
		$content .= '</span>';

		$content .= '</div>';
		$content .= "<div id=\"m$cnt\" class=\"hidden\">".Sanitize::outgoingHtml(filter($line['message']));
		if ($haspoints) {
			if ($caneditscore && $line['stuid']!==null) {
				$content .= '<hr/>';
				$content .= '<label for="feedback'.Sanitize::onlyInt($line['id']).'">'._('Private Feedback').'</label>: ';
				/*echo "<textarea cols=\"50\" rows=\"2\" name=\"feedback[". Sanitize::onlyInt($line['id'])."]\" id=\"feedback".Sanitize::onlyInt($line['id'])."\">";
				if ($feedback[$line['id']]!==null) {
					$content .= Sanitize::encodeStringForDisplay($feedback[$line['id']]);
				}
				$content .= "</textarea>";
				*/
				if ($_SESSION['useed']==0) {
					$content .= "<textarea class=scorebox cols=\"50\" rows=\"2\" name=\"feedback".Sanitize::onlyInt($line['id'])."\" id=\"feedback".Sanitize::onlyInt($line['id'])."\">";
					if (!empty($feedback[$line['id']])) {
						$content .= Sanitize::encodeStringForDisplay($feedback[$line['id']]);
					}
					$content .= "</textarea>";
				} else {
					$content .= '<div class="fbbox" id="feedback'.Sanitize::onlyInt($line['id']).'">';
					if (!empty($feedback[$line['id']])) {
						$content .= Sanitize::outgoingHtml($feedback[$line['id']]);
					}
					$content .= '</div>';
				}
			} else if (($line['userid']==$userid || $canviewscore) && !empty($feedback[$line['id']])) {
				$content .= '<div class="signup">Private Feedback: ';
				$content .= '<div>'.Sanitize::outgoingHtml($feedback[$line['id']]).'</div>';
				$content .= '</div>';
			}
		}
		$content .= '</div></div>';
		$cnt++;
	}
	printuserposts($lastname, $laststu, $content, $postcnt, $replycnt, $lasthasuserimg);
	echo "<script>var bcnt = $cnt;</script>";
	if ($caneditscore && $haspoints) {
		echo "<div><input type=submit value=\"Save Grades\" /></div>";
		echo "</form>";
	}

	echo '<p>Color code<br/>Black: New thread <span class="icon-forumpost" role="img" aria-label="post" title="Post"></span></br>';
	echo '<span style="color:#060;">Green: Reply <span class="icon-forumreply" role="img" aria-label="reply" title="Reply"></span></span></p>';

	echo "<p><a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Back to Thread List</a></p>";

	require_once "../footer.php";

?>
