<?php
	//Displays forum threads
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
	
	$threadsperpage = $listperpage;
	
	$cid = $_GET['cid'];
	$forumid = $_GET['forum'];
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = $_GET['page'];
	}
	
	if (($isteacher || isset($tutorid)) && isset($_POST['score'])) {
		if (isset($tutorid)) {
			$query = "SELECT tutoredit FROM imas_forums WHERE id='$forumid'";
			$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$row = mysql_fetch_row($res);
			if ($row[0] != 1) {
				//no rights to edit score
				exit;
			}
		}
		$existingscores = array();
		$query = "SELECT refid,id FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid'";
		$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($res)) {
			$existingscores[$row[0]] = $row[1];
		}
		$postuserids = array();
		$refids = "'".implode("','",array_keys($_POST['score']))."'";
		$query = "SELECT id,userid FROM imas_forum_posts WHERE id IN ($refids)";
		$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($res)) {
			$postuserids[$row[0]] = $row[1];
		}
		foreach($_POST['score'] as $k=>$v) {
			if (isset($_POST['feedback'][$k])) {
				$feedback = $_POST['feedback'][$k];
			} else {
				$feedback = '';
			}
			if (is_numeric($v)) {
				if (isset($existingscores[$k])) {
					$query = "UPDATE imas_grades SET score='$v',feedback='$feedback' WHERE id='{$existingscores[$k]}'";
				} else {
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score,feedback) VALUES ";
					$query .= "('forum','$forumid','{$postuserids[$k]}','$k','$v','$feedback')";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} else {
				if (isset($existingscores[$k])) {
					$query = "DELETE FROM imas_grades WHERE id='{$existingscores[$k]}'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
		}
		if (isset($_POST['save']) && $_POST['save']=='Save Grades and View Previous') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?page=$page&cid=$cid&forum=$forumid&thread={$_POST['prevth']}");
		} else if (isset($_POST['save']) && $_POST['save']=='Save Grades and View Next') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?page=$page&cid=$cid&forum=$forumid&thread={$_POST['nextth']}");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
		}
			exit;	
	}
	$query = "SELECT name,postby,settings,groupsetid,sortby,taglist FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	list($forumname, $postby, $forumsettings, $groupsetid, $sortby, $taglist) = mysql_fetch_row($result);
	
	$allowmod = (($forumsettings&2)==2);
	$allowdel = ((($forumsettings&4)==4) || $isteacher);
	$dofilter = false;
	$now = time();
	$grpqs = '';
	if ($groupsetid>0) {
		if (isset($_GET['ffilter'])) {
			$sessiondata['ffilter'.$forumid] = $_GET['ffilter'];
			writesessiondata();
		}
		if (!$isteacher) {
			$query = 'SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			$query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='$groupsetid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$groupid = mysql_result($result,0,0);
			} else {
				$groupid=0;
			}
			$dofilter = true;
		} else {
			if (isset($sessiondata['ffilter'.$forumid]) && $sessiondata['ffilter'.$forumid]>-1) {
				$groupid = $sessiondata['ffilter'.$forumid];
				$dofilter = true;
				$grpqs = "&grp=$groupid";
			} else {
				$groupid = 0;
			}
		}
		if ($dofilter) {
			$limthreads = array();
			if ($isteacher || $groupid==0) {
				$query = "SELECT id FROM imas_forum_threads WHERE stugroupid='$groupid'";
			} else {
				$query = "SELECT id FROM imas_forum_threads WHERE stugroupid=0 OR stugroupid='$groupid'";
			}
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$limthreads[] = $row[0];
			}
			if (count($limthreads)==0) {
				$limthreads = '0';
			} else {
				$limthreads = implode(',',$limthreads);
			}
		}
	} else {
		$groupid = 0;
	}
	
	if (isset($_GET['tagfilter'])) {
		$sessiondata['tagfilter'.$forumid] = stripslashes($_GET['tagfilter']);
		writesessiondata();
		$tagfilter = stripslashes($_GET['tagfilter']);
	} else if (isset($sessiondata['tagfilter'.$forumid]) && $sessiondata['tagfilter'.$forumid]!='') {
		$tagfilter = $sessiondata['tagfilter'.$forumid];
	} else {
		$tagfilter = '';
	}
	if ($tagfilter != '') {
		$query = "SELECT threadid FROM imas_forum_posts WHERE tag='".addslashes($tagfilter)."'";
		if ($dofilter) {
			$query .= " AND threadid IN ($limthreads)";
		}
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$limthreads = array();
		while ($row = mysql_fetch_row($result)) {
			$limthreads[] = $row[0];
		}
		if (count($limthreads)==0) {
			$limthreads = '0';
		} else {
			$limthreads = implode(',',$limthreads);
		}
		$dofilter = true;
	}
	
	if (isset($_GET['search']) && trim($_GET['search'])!='') {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
		echo "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; Search Results</div>\n";
	
		echo "<h2>Forum Search Results</h2>";
		
		$safesearch = $_GET['search'];
		$safesearch = str_replace(' and ', ' ',$safesearch);
		$searchterms = explode(" ",$safesearch);
		$searchlikes = "(imas_forum_posts.message LIKE '%".implode("%' AND imas_forum_posts.message LIKE '%",$searchterms)."%')";
		$searchlikes2 = "(imas_forum_posts.subject LIKE '%".implode("%' AND imas_forum_posts.subject LIKE '%",$searchterms)."%')";
		$searchlikes3 = "(imas_users.LastName LIKE '%".implode("%' AND imas_users.LastName LIKE '%",$searchterms)."%')";
		if (isset($_GET['allforums'])) {
			$query = "SELECT imas_forums.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.isanon FROM imas_forum_posts,imas_forums,imas_users ";
			$query .= "WHERE imas_forum_posts.forumid=imas_forums.id ";
			if (!$isteacher) {
				$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now AND imas_forums.enddate>$now)) ";
			}
			$query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid='$cid' AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
		} else {
			$query = "SELECT imas_forum_posts.forumid,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate ";
			$query .= "FROM imas_forum_posts,imas_users WHERE imas_forum_posts.forumid='$forumid' AND imas_users.id=imas_forum_posts.userid AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
		}
		if ($dofilter) {
			$query .= " AND imas_forum_posts.threadid IN ($limthreads)";
		}
		
		$query .= " ORDER BY imas_forum_posts.postdate DESC";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<div class=block>";
			echo "<b>{$row[2]}</b>";
			if (isset($_GET['allforums'])) {
				echo ' (in '.$row[7].')';
			}
			if ($row[8]==1) {
				$name = "Anonymous";
			} else {
				$name = "{$row[4]} {$row[5]}";
			}
			echo "<br/>Posted by: $name, ";
			echo tzdate("F j, Y, g:i a",$row[6]);
			
			echo "</div><div class=blockitems>";
			echo filter($row[3]);
			echo "<p><a href=\"posts.php?cid=$cid&forum={$row[0]}&thread={$row[1]}\">Show full thread</a></p>";
			echo "</div>\n";
		}
		require("../footer.php");
		exit;
	}
	
	if (isset($_GET['markallread'])) {
		$query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid='$forumid'";
		if ($dofilter) {
			$query .= " AND threadid IN ($limthreads)";
		}
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$now = time();
		while ($row = mysql_fetch_row($result)) {
			$query = "SELECT id FROM imas_forum_views WHERE userid='$userid' AND threadid='{$row[0]}'";
			$r2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($r2)>0) {
				$r2id = mysql_result($r2,0,0);
				$query = "UPDATE imas_forum_views SET lastview=$now WHERE id='$r2id'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} else{
				$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','{$row[0]}',$now)";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
		}
	}
	$caller = 'thread';
	if (isset($_GET['modify']) || isset($_GET['remove']) || isset($_GET['move'])) {
		require("posthandler.php");
	}
	
	$pagetitle = "Threads";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\"); td.pointer:hover {text-decoration: underline;}\n</style>\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/thread.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/savetagged.php?cid=$cid';";
	$placeinhead .= '$(function() {$("img[src*=\'flag\']").attr("title","Flag Message");});';
	$placeinhead .= "var tagfilterurl = '" . $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$pages&cid=$cid&forum=$forumid';</script>";
	require("../header.php");
	
	
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Forum Topics</div>\n";
	echo '<div id="headerthread" class="pagetitle"><h2>Forum: '.$forumname.'</h2></div>';

	$query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
	$query .= "WHERE forumid='$forumid' ";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}

	$query .= "GROUP BY threadid";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$postcount = array();
	$maxdate = array();
	
	while ($row = mysql_fetch_row($result)) {
		$postcount[$row[0]] = $row[1] -1;
		$maxdate[$row[0]] = $row[2];
	}
	
	$query = "SELECT threadid,lastview,tagged FROM imas_forum_views WHERE userid='$userid'";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}

	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$lastview = array();
	$flags = array();
	while ($row = mysql_fetch_row($result)) {
		$lastview[$row[0]] = $row[1];
		if ($row[2]==1) {
			$flags[$row[0]] = 1;
		}
	}
	$flaggedlist = implode(',',array_keys($flags));
	//make new list
	$newpost = array();
	foreach (array_keys($maxdate) as $tid) {
		if (!isset($lastview[$tid]) || $lastview[$tid]<$maxdate[$tid]) {
			$newpost[] = $tid;
		}
	}
	$newpostlist = implode(',',$newpost);
	if ($page==-1 && count($newpost)==0) {
		$page = 1;
	} else if ($page==-2 && count($flags)==0) {
		$page = 1;
	}
	$prevnext = '';
	if ($page>0) {
		$query = "SELECT COUNT(id) FROM imas_forum_posts WHERE parent=0 AND forumid='$forumid'";
		if ($dofilter) {
			$query .= " AND threadid IN ($limthreads)";
		}
	
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
		
		if ($numpages > 1) {
			$prevnext .= "Page: ";
			if ($page < $numpages/2) {
				$min = max(2,$page-4);
				$max = min($numpages-1,$page+8+$min-$page);
			} else {
				$max = min($numpages-1,$page+4);
				$min = max(2,$page-8+$max-$page);
			}
			if ($page==1) {
				$prevnext .= "<b>1</b> ";
			} else {
				$prevnext .= "<a href=\"thread.php?page=1&cid=$cid&forum=$forumid\">1</a> ";
			}
			if ($min!=2) { $prevnext .= " ... ";}
			for ($i = $min; $i<=$max; $i++) {
				if ($page == $i) {
					$prevnext .= "<b>$i</b> ";
				} else {
					$prevnext .= "<a href=\"thread.php?page=$i&cid=$cid&forum=$forumid\">$i</a> ";
				}
			}
			if ($max!=$numpages-1) { $prevnext .= " ... ";}
			if ($page == $numpages) {
				$prevnext .= "<b>$numpages</b> ";
			} else {
				$prevnext .= "<a href=\"thread.php?page=$numpages&cid=$cid&forum=$forumid\">$numpages</a> ";
			}
			$prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			
			if ($page>1) {
				$prevnext .= "<a href=\"thread.php?page=".($page-1)."&cid=$cid&forum=$forumid\">Previous</a> ";
			} else {
				$prevnext .= "Previous ";
			}
			if ($page < $numpages) {
				$prevnext .= "| <a href=\"thread.php?page=".($page+1)."&cid=$cid&forum=$forumid\">Next</a> ";
			} else {
				$prevnext .= "| Next ";
			}
			
			echo "<div>$prevnext</div>";
		}
	}
	echo "<form method=get action=\"thread.php\">";
	echo "<input type=hidden name=page value=\"$page\"/>";
	echo "<input type=hidden name=cid value=\"$cid\"/>";
	echo "<input type=hidden name=forum value=\"$forumid\"/>";
	
?>
	Search: <input type=text name="search" /> <input type=checkbox name="allforums" />All forums in course? <input type="submit" value="Search"/>
	</form>
<?php
	if ($isteacher && $groupsetid>0) {
		if (isset( $sessiondata['ffilter'.$forumid])) {
			$curfilter = $sessiondata['ffilter'.$forumid];
		} else {
			$curfilter = -1;
		}
		
		$groupnames = array();
		$groupnames[0] = "Non-group-specific";
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$grpnums = 1;
		while ($row = mysql_fetch_row($result)) {
			if ($row[1] == 'Unnamed group') { 
				$row[1] .= " $grpnums";
				$grpnums++;
			}
			$groupnames[$row[0]] = $row[1];
		}
		natsort($groupnames);
		
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		/*echo "<script type=\"text/javascript\">";
		echo 'function chgfilter() {';
		echo '  var ffilter = document.getElementById("ffilter").value;';
		echo "  window.location = \"thread.php?page=$pages&cid=$cid&forum=$forumid&ffilter=\"+ffilter;";
		echo '}';
		echo '</script>';*/
		echo '<p>Show posts for group: <select id="ffilter" onChange="chgfilter()"><option value="-1" ';
		if ($curfilter==-1) { echo 'selected="1"';}
		echo '>All groups</option>';
		foreach ($groupnames as $gid=>$gname) {
			echo "<option value=\"$gid\" ";
			if ($curfilter==$gid) { echo 'selected="1"';}
			echo ">$gname</option>";
		}
		echo '</select></p>';
	}
	echo '<p>';
	$toshow = array();
	if (($myrights > 5 && time()<$postby) || $isteacher) {
		$toshow[] =  "<button type=\"button\" onclick=\"window.location.href='thread.php?page=$page&cid=$cid&forum=$forumid&modify=new'\">"._('Add New Thread')."</button>";
	}
	//if ($isteacher || isset($tutorid)) {
		$toshow[] =  "<a href=\"postsbyname.php?page=$page&cid=$cid&forum=$forumid\">List Posts by Name</a>";
	//}
	
	if ($page<0) {
		$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=1\">Show All</a>";
	} else {
		if (count($newpost)>0) {
			$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=-1\">Limit to New</a>";
		}
		$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=-2\">Limit to Flagged</a>";
		if ($taglist!='') {
			$p = strpos($taglist,':');
			
			$tagselect = 'Filter by '.substr($taglist,0,$p).': ';
			$tagselect .= '<select id="tagfilter" onChange="chgtagfilter()"><option value="" ';
			if ($tagfilter=='') {
				$tagselect .= 'selected="selected"';
			}
			$tagselect .= '>All</option>';
			$tags = explode(',',substr($taglist,$p+1));
			foreach ($tags as $tag) {
				$tag =  str_replace('"','&quot;',$tag);   
				$tagselect .= '<option value="'.$tag.'" ';
				if ($tag==$tagfilter) {$tagselect .= 'selected="selected"';}
				$tagselect .= '>'.$tag.'</option>';
			}
			$tagselect .= '</select>';
			$toshow[] = $tagselect;
		}
		
	} 
	if (count($newpost)>0) {
		$toshow[] =  "<button type=\"button\" onclick=\"window.location.href='thread.php?page=$page&cid=$cid&forum=$forumid&markallread=true'\">"._('Mark all Read')."</button>";
	}
	
	echo implode(' | ',$toshow);
	
	echo "</p>";
	
?>
	<table class="forum gb">
	<thead>
	<tr><th>Topic</th>
<?php
	if ($isteacher && $groupsetid>0 && !$dofilter) {
		echo '<th>Group</th>';
	}
?>
	<th>Replies</th><th>Views (Unique)</th><th>Last Post</th></tr>
	</thead>
	<tbody>
<?php
	
	
	$query = "SELECT imas_forum_posts.id,count(imas_forum_views.userid) FROM imas_forum_views,imas_forum_posts ";
	$query .= "WHERE imas_forum_views.threadid=imas_forum_posts.id AND imas_forum_posts.parent=0 AND ";
	$query .= "imas_forum_posts.forumid='$forumid' ";
	if ($dofilter) {
		$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
	}
	if ($page==-1) {
		$query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
	} else if ($page==-2) {
		$query .= "AND imas_forum_posts.threadid IN ($flaggedlist) ";
	} 
	$query .= "GROUP BY imas_forum_posts.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$uniqviews[$row[0]] = $row[1]-1;
	}
	
	$query = "SELECT imas_forum_posts.*,imas_forum_threads.views as tviews,imas_users.LastName,imas_users.FirstName,imas_forum_threads.stugroupid FROM imas_forum_posts,imas_users,imas_forum_threads WHERE ";
	$query .= "imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.parent=0 AND imas_forum_posts.forumid='$forumid' ";	
	
	if ($dofilter) {
		$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
	}
	if ($page==-1) {
		$query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
	} else if ($page==-2) {
		$query .= "AND imas_forum_posts.threadid IN ($flaggedlist) ";
	} 
	if ($sortby==0) {
		$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_posts.id DESC ";
	} else if ($sortby==1) {
		$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_threads.lastposttime DESC ";
	}
	$offset = ($page-1)*$threadsperpage;
	if ($page>0) {
		$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo '<tr><td colspan='.(($isteacher && $grpaid>0 && !$dofilter)?5:4).'>No posts have been made yet.  Click Add New Thread to start a new discussion</td></tr>';
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (isset($postcount[$line['id']])) {
			$posts = $postcount[$line['id']];
			$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
		} else {
			$posts = 0;
			$lastpost = '';
		}
		echo "<tr id=\"tr{$line['id']}\"";
		if ($line['posttype']>0) {
			echo "class=sticky";
		} else if (isset($flags[$line['id']])) {
			echo "class=tagged";
		}
		echo "><td>";
		echo "<span class=\"right\">\n";
		if ($line['tag']!='') { //category tags
			echo '<span class="forumcattag">'.$line['tag'].'</span> ';
		}
		if ($line['posttype']==0) {
			if (isset($flags[$line['id']])) {
				echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
			} else {
				echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
			}
		}
		if ($isteacher) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&move={$line['id']}\">Move</a> ";
		}
		if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby)) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&modify={$line['id']}\">Modify</a> ";
		} 
		if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&remove={$line['id']}\">Remove</a>";
		}
		echo "</span>\n";
		if ($line['isanon']==1) {
			$name = "Anonymous";
		} else {
			$name = "{$line['LastName']}, {$line['FirstName']}";
		}
		echo "<b><a href=\"posts.php?cid=$cid&forum=$forumid&thread={$line['id']}&page=$page$grpqs\">{$line['subject']}</a></b>: $name";
		
		echo "</td>\n";
		if ($isteacher && $groupsetid>0 && !$dofilter) {
			echo '<td class=c>'.$groupnames[$line['stugroupid']].'</td>';
		}
		
		echo "<td class=c>$posts</td>";
		
		if ($isteacher) {
			echo '<td class="pointer c" onclick="GB_show(\''._('Thread Views').'\',\'listviews.php?cid='.$cid.'&amp;thread='.$line['id'].'\',500,500);">';
		} else {
			echo '<td class="c">';
		}
		echo "{$line['tviews']} ({$uniqviews[$line['id']]})</td><td class=c>$lastpost ";
		if ($lastpost=='' || $maxdate[$line['id']]>$lastview[$line['id']]) {
			echo "<span style=\"color: red;\">New</span>";
		}
		echo "</td></tr>\n";
	}
?>
	</tbody>
	</table>
<?php
	if (($myrights > 5 && time()<$postby) || $isteacher) {
		echo "<p><button type=\"button\" onclick=\"window.location.href='thread.php?page=$page&cid=$cid&forum=$forumid&modify=new'\">"._('Add New Thread')."</button></p>\n";
	}
	if ($prevnext!='') {
		echo "<p>$prevnext</p>";
	}
	
	require("../footer.php");
?>
