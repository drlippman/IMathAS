<?php
//IMathAS:  include with posts.php and postsbyname.php for handling deletes, replies, etc.
//(c) 2006 David Lippman

require_once(__DIR__ . "/../includes/sanitize.php");


ini_set("max_execution_time", "60");




if (!isset($caller)) {
	exit;
}
if ($caller=="posts") {
	$returnurl = "posts.php?view=$view&cid=$cid&page=$page&forum=$forumid&thread=$threadid";
	$returnname = "Posts";
} else if ($caller=="byname") {
	$threadid = Sanitize::onlyInt($_GET['thread']);
	$returnurl = "postsbyname.php?cid=$cid&forum=$forumid&thread=$threadid";
	$returnname = "Posts by Name";

} else if ($caller=='thread') {
	$returnurl = "thread.php?page=$page&cid=$cid&forum=$forumid";
	$returnname = "Forum Topics";
}
if (!empty($_GET['embed'])) {
	$returnurl .= '&embed=true';
}

$now = time();

if (isset($_GET['modify'])) { //adding or modifying post
	if ($caller=='thread') {
		$threadid = $_GET['modify'];
	}
	if ($_GET['modify']!='new' && $threadid==0) {
		echo "I don't know what thread you're replying to.  Please go back and try again.";
	}
	if (isset($_POST['subject'])) {  //form submitted
		if (isset($_POST['postanon']) && $_POST['postanon']==1) {
			$isanon = 1;
		} else {
			$isanon = 0;
		}
		if ($isteacher) {
			$type = $_POST['type'];
			if (!isset($_POST['replyby']) || $_POST['replyby']=="null") {
				$replyby = null;
			} else if ($_POST['replyby']=="Always") {
				$replyby = 2000000000;
			} else if ($_POST['replyby']=="Never") {
				$replyby = 0;
			} else {
				require_once("../includes/parsedatetime.php");
				$replyby = parsedatetime($_POST['replybydate'],$_POST['replybytime'],null);
			}
		} else {
			$type = 0;
			$replyby = null;
		}
		if (isset($_POST['tag'])) {
			$tag = $_POST['tag'];
		} else {
			$tag = '';
		}
		$_POST['subject'] = htmlentities($_POST['subject']);

        $_POST['message'] = Sanitize::trimEmptyPara(Sanitize::incomingHtml($_POST['message']));
		$_POST['subject'] = trim(strip_tags($_POST['subject']));
		if (trim($_POST['subject'])=='') {
			$_POST['subject']= '(none)';
		}
		$thisposttime = $now-1;
		if ($isteacher) {
			if ($_POST['releaseon']=='Date') {
				require_once("../includes/parsedatetime.php");
				$thisposttime = parsedatetime($_POST['releasedate'],$_POST['releasetime'],$now-1);
			}
		}
		if ($_GET['modify']=="new") { //new thread
			if ($groupsetid>0) {
				if ($isteacher) {
					if (isset($_POST['stugroup'])) {
						$groupid = $_POST['stugroup'];
					} else {
						$groupid = 0;
					}
				}
			}
			if (isset($studentid)) {
				if (time()>$postby) {
					echo 'Post rejected - it is after the New Threads due date.';
					exit;
				}
			}
			$query = "INSERT INTO imas_forum_posts (forumid,subject,message,userid,postdate,parent,posttype,isanon,replyby,tag) VALUES ";
			$query .= "(:forumid, :subject, :message, :userid, :postdate, :parent, :posttype, :isanon, :replyby, :tag)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':forumid'=>$forumid, ':subject'=>$_POST['subject'], ':message'=>$_POST['message'], ':userid'=>$userid, ':postdate'=>$thisposttime, ':parent'=>0, ':posttype'=>$type, ':isanon'=>$isanon, ':replyby'=>$replyby, ':tag'=>$tag));
			$threadid = $DBH->lastInsertId();
			$stm = $DBH->prepare("UPDATE imas_forum_posts SET threadid=:threadid WHERE id=:id");
			$stm->execute(array(':threadid'=>$threadid, ':id'=>$threadid));
	 		$stm = $DBH->prepare("INSERT INTO imas_forum_threads (id,forumid,lastposttime,lastpostuser,stugroupid) VALUES (:id, :forumid, :lastposttime, :lastpostuser, :stugroupid)");
	 		$stm->execute(array(':id'=>$threadid, ':forumid'=>$forumid, ':lastposttime'=>$thisposttime, ':lastpostuser'=>$userid, ':stugroupid'=>$groupid));
			$stm = $DBH->prepare("INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES (:userid, :threadid, :lastview)");
			$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid, ':lastview'=>$now));
			$sendemail = true;

			if (isset($studentid)) {
				$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
				$query .= "(:userid, :courseid, :type, :typeid, :viewtime, :info)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':type'=>'forumpost', ':typeid'=>$threadid, ':viewtime'=>$now, ':info'=>$forumid));
			}
			if (isset($studentid) && $autoscore != '' && strlen(Sanitize::stripBlankLines($_POST['message']))>1) {
				$autoscore = explode(',',$autoscore);
				if ($autoscore[0]>0) { //assigning points
					$stm = $DBH->prepare("SELECT count(id) FROM imas_forum_posts WHERE forumid=? AND userid=? AND parent=0");
					$stm->execute(array($forumid, $userid));
					if ($stm->fetchColumn(0) <= $autoscore[1]) {
						$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score) VALUES ";
						$query .= "(:gradetype, :gradetypeid, :userid, :refid, :score)";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':gradetype'=>'forum', ':gradetypeid'=>$forumid, ':userid'=>$userid, ':refid'=>$threadid, ':score'=>$autoscore[0]));
					}
				}
			}
			$_GET['modify'] = $threadid;
			$files = array();
		} else if ($_GET['modify']=="reply") { //new reply post
			$stm = $DBH->prepare("SELECT userid FROM imas_forum_posts WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['replyto']));
			if ($stm->rowCount()==0) {

				$sendemail = false;
				require("../header.php");
				echo '<h1>Error:</h1><p>It looks like the post you were replying to was deleted.  Your post is below in case you ';
				echo 'want to copy-and-paste it somewhere. <a href="'.Sanitize::url($returnurl).'">Continue</a></p>';
				echo '<hr>';
				// $_POST['message'] contains HTML.
				echo '<p>Message:</p><div class="editor">'.Sanitize::outgoingHtml(filter($_POST['message'])).'</div>';
				echo '<p>HTML format:</p>';
				echo '<div class="editor">'.Sanitize::encodeStringForDisplay($_POST['message']).'</div>';
				require("../footer.php");
				exit;
			} else {
				$uid = $stm->fetchColumn(0);
				$query = "INSERT INTO imas_forum_posts (forumid,threadid,subject,message,userid,postdate,parent,posttype,isanon) VALUES ";
				$query .= "(:forumid, :threadid, :subject, :message, :userid, :postdate, :parent, :posttype, :isanon)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':forumid'=>$forumid, ':threadid'=>$threadid, ':subject'=>$_POST['subject'], ':message'=>$_POST['message'], ':userid'=>$userid, ':postdate'=>$now, ':parent'=>$_GET['replyto'], ':posttype'=>0, ':isanon'=>$isanon));
				$_GET['modify'] = $DBH->lastInsertId();
				if ($page==-3) {
					$stm = $DBH->prepare("SELECT lastposttime FROM imas_forum_threads WHERE id=:id");
					$stm->execute(array(':id'=>$threadid));
					$returnurl .= '&olpt='.Sanitize::onlyInt($stm->fetchColumn(0));
				}
				$stm = $DBH->prepare("UPDATE imas_forum_threads SET lastposttime=:lastposttime,lastpostuser=:lastpostuser WHERE id=:id");
				$stm->execute(array(':lastposttime'=>$now, ':lastpostuser'=>$userid, ':id'=>$threadid));

				if (isset($studentid)) {
					$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
					$query .= "(:userid, :courseid, :type, :typeid, :viewtime, :info)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':type'=>'forumreply', ':typeid'=>$_GET['modify'], ':viewtime'=>$now, ':info'=>"$forumid;$threadid"));
				}
				if (isset($studentid) && $autoscore != '' && strlen(Sanitize::stripBlankLines($_POST['message']))>1) {
					$autoscore = explode(',',$autoscore);
					if ($autoscore[2]>0) { //assigning points
						$stm = $DBH->prepare("SELECT count(id) FROM imas_forum_posts WHERE forumid=? AND userid=? AND parent>0");
						$stm->execute(array($forumid, $userid));
						if ($stm->fetchColumn(0) <= $autoscore[3]) {
							$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score) VALUES ";
							$query .= "(:gradetype, :gradetypeid, :userid, :refid, :score)";
							$stm = $DBH->prepare($query);
							$stm->execute(array(':gradetype'=>'forum', ':gradetypeid'=>$forumid, ':userid'=>$userid, ':refid'=>$_GET['modify'], ':score'=>$autoscore[2]));
						}
					}
				}
				if ($isteacher && isset($_POST['points']) && trim($_POST['points'])!='') {
					$stm = $DBH->prepare("SELECT id FROM imas_grades WHERE gradetype='forum' AND refid=:refid");
					$stm->execute(array(':refid'=>$_GET['replyto']));
					if ($stm->rowCount()>0) {
						$gradeid = $stm->fetchColumn(0);
						$stm = $DBH->prepare("UPDATE imas_grades SET score=:score WHERE id=:id");
						$stm->execute(array(':score'=>$_POST['points'], ':id'=>$gradeid));

						//$query = "UPDATE imas_forum_posts SET points='{$_POST['points']}' WHERE id='{$_GET['replyto']}'";
						// mysql_query($query) or die("Query failed : $query " . mysql_error());
					} else {
						//moved up as a "did the post get deleted" check
						//$query = "SELECT userid FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
						//$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//$uid = mysql_result($result,0,0);
						$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score) VALUES ";
						$query .= "(:gradetype, :gradetypeid, :userid, :refid, :score)";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':gradetype'=>'forum', ':gradetypeid'=>$forumid, ':userid'=>$uid, ':refid'=>$_GET['replyto'], ':score'=>$_POST['points']));
					}
				}
				$sendemail = true;
				$files = array();
			}
		} else {
			$query = "UPDATE imas_forum_posts SET subject=:subject,message=:message,isanon=:isanon,tag=:tag,posttype=:posttype,replyby=:replyby";
			$arr = array(':subject'=>$_POST['subject'], ':message'=>$_POST['message'], ':isanon'=>$isanon, ':tag'=>$tag, ':posttype'=>$type, ':replyby'=>$replyby, ':id'=>$_GET['modify']);
			if ($isteacher && isset($_POST['releaseon']) && $_POST['releaseon'] != 'nochange') {
					$query .= ",postdate=:postdate";
					$arr[':postdate'] = $thisposttime;
			}
			$query .= " WHERE id=:id";
			if (!$isteacher) {
				$query .= " AND userid=:userid";
				$stm = $DBH->prepare($query);
				$arr[':userid'] = $userid;
				$stm->execute($arr);
			} else {
				$stm = $DBH->prepare($query);
				$stm->execute($arr);
			}
			// mysql_query($query) or die("Query failed : $query " . mysql_error());
			if ($caller=='thread' || $_GET['thread']==$_GET['modify']) {
				if ($groupsetid>0 && $isteacher && isset($_POST['stugroup'])) {
					$groupid = $_POST['stugroup'];
					$stm = $DBH->prepare("UPDATE imas_forum_threads SET stugroupid=:stugroupid WHERE id=:id");
					$stm->execute(array(':stugroupid'=>$groupid, ':id'=>$_GET['modify']));

				}
				if ($isteacher && isset($_POST['releaseon']) && $_POST['releaseon'] != 'nochange') {
					$stm = $DBH->prepare("UPDATE imas_forum_threads SET lastposttime=:newtime WHERE id=:id");
					$stm->execute(array(':newtime'=>$thisposttime, ':id'=>$_GET['modify']));
				}
			}

			if (isset($studentid)) {
				$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
				$query .= "(:userid, :courseid, :type, :typeid, :viewtime, :info)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':type'=>'forummod', ':typeid'=>$_GET['modify'], ':viewtime'=>$now, ':info'=>"$forumid;$threadid"));

			}

			$sendemail = false;
			$stm = $DBH->prepare("SELECT files FROM imas_forum_posts WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['modify']));
			$files = $stm->fetchColumn(0);

			if ($files=='') {
				$files = array();
			} else {
				$files = explode('@@',$files);
			}
		}
		if ($sendemail) {
			require_once("../includes/email.php");

			$query = "SELECT iu.email FROM imas_users AS iu,imas_forum_subscriptions AS ifs WHERE ";
			$query .= "iu.id=ifs.userid AND ifs.forumid=:forumid AND iu.id<>:userid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':forumid'=>$forumid, ':userid'=>$userid));
			if ($stm->rowCount()>0) {
				$message  = "<h3>This is an automated message.  Do not respond to this email</h3>\r\n";
				$message .= "<p>A new post has been made in forum $forumname in course ".Sanitize::encodeStringForDisplay($coursename)."</p>\r\n";
				$message .= "<p>Subject:".Sanitize::encodeStringForDisplay($_POST['subject'])."</p>";
				$message .= "<p>Poster: $userfullname</p>";
				$message .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/forums/$returnurl\">";
				$message .= "View Posting</a>\r\n";
			}
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$row[0] = trim($row[0]);
				if ($row[0]!='' && $row[0]!='none@none.com') {
					send_email($row[0], $sendfrom, _('New forum post notification'), $message, array(), array(), 1);
				}
			}
		}
		//now handle any files
		if (isset($_POST['filedesc'])) {
			foreach ($_POST['filedesc'] as $i=>$v) {
				$files[2*$i] = str_replace('@@','@',$v);
			}
			for ($i=count($files)/2-1;$i>=0;$i--) {
				if (isset($_POST['filedel'][$i])) {
					if (deleteforumfile($_GET['modify'],$files[2*$i+1])) {
						array_splice($files,2*$i,2);
					}
				}
			}
		}
		if (isset($_FILES['newfile-0'])) {
			require_once("../includes/filehandler.php");
			$i = 0;
			$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
			while (isset($_FILES['newfile-'.$i]) && is_uploaded_file($_FILES['newfile-'.$i]['tmp_name'])) {
				$userfilename = Sanitize::sanitizeFilenameAndCheckBlacklist(basename(str_replace('\\','/',$_FILES['newfile-'.$i]['name'])));
				if (trim($_POST['newfiledesc-'.$i])=='') {
					$_POST['newfiledesc-'.$i] = $userfilename;
				}
				$_POST['newfiledesc-'.$i] = str_replace('@@','@',$_POST['newfiledesc-'.$i]);
				$extension = strtolower(strrchr($userfilename,"."));
				if (!in_array($extension,$badextensions) && storeuploadedfile('newfile-'.$i,'ffiles/'
						.Sanitize::sanitizeFilenameAndCheckBlacklist($_GET['modify']).'/'.$userfilename,"public")) {
					$files[] = $_POST['newfiledesc-'.$i];
					$files[] = $userfilename;
				}
				$i++;
			}
		}
		$files = implode('@@',$files);
		$stm = $DBH->prepare("UPDATE imas_forum_posts SET files=:files WHERE id=:id");
		$stm->execute(array(':files'=>$files, ':id'=>$_GET['modify']));
		if (!empty($_GET['embed'])) {
			$returnurl = "embeddone.php?embed=true";
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/$returnurl&r=" . Sanitize::randomQueryStringParam());
		exit;
	} else { //display mod
		if ($caller=='thread') {
			$pagetitle = "Add/Modify Thread";
		} else {
			$pagetitle = "Add/Modify Post";
		}
		$useeditor = "message";
		$loadgraphfilter = true;
		$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";

		require("../header.php");
		if (empty($_GET['embed'])) {
            echo "<div class=breadcrumb>";
            if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
                echo "$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
            }   
            if ($caller != 'thread') {
				echo "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
			}
			echo "<a href=\"$returnurl\">$returnname</a> &gt; ";
			if ($_GET['modify']!="reply" && $_GET['modify']!='new') {
				echo "Modify Posting";
			} else if ($_GET['modify']=='reply') {
				echo "Post Reply";
			} else if ($_GET['modify']=='new') {
				echo "Add Thread";
			}
			echo '</div>';
		}
		$notice = '';
		if ($_GET['modify']!="reply" && $_GET['modify']!='new') {
			$stm = $DBH->prepare("SELECT * from imas_forum_posts WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['modify']));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			$replyby = $line['replyby'];
			if ($groupsetid>0 && $isteacher && $line['parent']==0) {
				$stm = $DBH->prepare("SELECT stugroupid FROM imas_forum_threads WHERE id=:id");
				$stm->execute(array(':id'=>$line['threadid']));
				$curstugroupid = $stm->fetchColumn(0);
			}
			echo '<div id="headerposthandler" class="pagetitle"><h1>Modify Post</h1></div>';
		} else {
			if ($_GET['modify']=='reply') {

					//$query = "SELECT subject,points FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
				$query = "SELECT ifp.subject,ig.score FROM imas_forum_posts AS ifp LEFT JOIN imas_grades AS ig ON ";
				$query .= "ig.gradetype='forum' AND ifp.id=ig.refid WHERE ifp.id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$_GET['replyto']));
				list($sub,$points) = $stm->fetch(PDO::FETCH_NUM);

				$sub = str_replace('"','&quot;',$sub);
				$line['subject'] = "Re: $sub";
				$line['message'] = "";
				$line['files'] = '';
				$replyby = $line['replyby'];
				if ($isteacher) {
					$stm = $DBH->prepare("SELECT points FROM imas_forums WHERE id=:id");
					$stm->execute(array(':id'=>$forumid));
					$haspoints = ($stm->fetchColumn(0)>0);
				}
				echo '<div id="headerposthandler" class="pagetitle"><h1>Post Reply</h1></div>';
			} else if ($_GET['modify']=='new') {
				if (isset($studentid)) {
					if (time()>$postby) {
						echo 'It is after the New Threads due date.';
						exit;
					}
				}
				$line['subject'] = "";
				$line['message'] = "";
				$line['posttype'] = 0;
				$line['files'] = '';
                $line['tag'] = '';
                if (isset($_SESSION['ffilter'.$forumid])) {
                    $curstugroupid = $_SESSION['ffilter'.$forumid];
                    if ($curstugroupid == -1) {
                        $curstugroupid = 0;
                    }
                } else {
                    $curstugroupid = 0;
                }
				$replyby = null;
				echo "<h1>Add Thread - \n";
				if (isset($_GET['quoteq'])) {
					$showa = false;
					$parts = explode('-',$_GET['quoteq']);
					$GLOBALS['assessver'] = $parts[4];
                    if ($courseUIver > 1) {
                        include('../assess2/AssessStandalone.php');
                        $a2 = new AssessStandalone($DBH);
                        $state = array(
                            'seeds' => array($parts[0] => $parts[2]),
                            'qsid' => array($parts[0] => $parts[1])
                        );
                        $a2->setState($state);
                        $a2->loadQuestionData();
                        $res = $a2->displayQuestion($parts[0], ['showhints'=>false,]);
                        $message = $res['html'];
                        $message = preg_replace('/<div class="question"[^>]*>/','<div>', $message);
                    } else {
                        require("../assessment/displayq2.php");
                        $message = displayq($parts[0],$parts[1],$parts[2],false,false,0,true);
                    }
					$message = printfilter(forcefiltergraph($message));
					if (isset($CFG['GEN']['AWSforcoursefiles']) && $CFG['GEN']['AWSforcoursefiles'] == true) {
						require_once("../includes/filehandler.php");
						$message = preg_replace_callback('|'.$imasroot.'/filter/graph/imgs/([^\.]*?\.png)|', function ($matches) {
							$curdir = rtrim(dirname(__FILE__), '/\\');
							return relocatefileifneeded($curdir.'/../filter/graph/imgs/'.$matches[1], 'gimgs/'.$matches[1]);
						    }, $message);
					}
					$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);

					$line['message'] = '<p> </p><br/><hr/>'.$message;
					if (isset($parts[3])) {
						$stm = $DBH->prepare("SELECT name,itemorder FROM imas_assessments WHERE id=:id");
						$stm->execute(array(':id'=>$parts[3]));
						list($aname, $itemorder) = $stm->fetch(PDO::FETCH_NUM);
						// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						$line['subject'] = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;', $aname);
						$isgroupedq = false;
						if (strpos($itemorder, '~')!==false) {
							$itemorder = explode(',',$itemorder);
							$icnt = 0;
							foreach ($itemorder as $item) {
								if (strpos($item,'~')===false) {
									if ($icnt==$parts[0]) { break;}
									$icnt++;
								} else {
									$pts = explode('|',$item);
									if ($parts[0]<$icnt+$pts[0]) {
										$isgroupedq = true; break;
									}
									$icnt += $pts[0];
								}
							}
						}

						if (!$isgroupedq) {
							$query = "SELECT ift.id FROM imas_forum_posts AS ifp JOIN imas_forum_threads AS ift ON ifp.threadid=ift.id AND ifp.parent=0 ";
							$query .= "WHERE ifp.subject=:subject AND ift.forumid=:forumid";
							$array = array(':forumid'=>$forumid, ':subject'=>trim(strip_tags(htmlentities(html_entity_decode($line['subject'])))));
							if ($groupsetid >0 && !$isteacher) {
								$query .= " AND ift.stugroupid=:groupid";
								$array[':groupid'] =$groupid;
							}
							$stm = $DBH->prepare($query);
							$stm->execute($array);
							// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							if ($stm->rowCount()>0) {
								$notice =  '<span class=noticetext style="font-weight:bold">This question has already been posted about.</span><br/>';
								$notice .= 'Please read and participate in the existing discussion.';
								while ($row = $stm->fetch(PDO::FETCH_NUM)) {
									$notice .=  "<br/><a href=\"posts.php?cid=$cid&forum=$forumid&thread=" . Sanitize::encodeUrlParam($row[0]) . "\">".Sanitize::encodeStringForDisplay($line['subject'])."</a>";
								}
							}
						}
					}
				} //end if quoteq
			}
		}
		$stm = $DBH->prepare("SELECT name,settings,forumtype,taglist,postinstr,replyinstr FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$forumid));
		$forumsettings = $stm->fetch(PDO::FETCH_ASSOC);
		$allowanon = $forumsettings['settings']%2;
		if ($_GET['modify']=='new') {
			echo Sanitize::encodeStringForDisplay($forumsettings['name']).'</h1>';
		}
		$forumtype = $forumsettings['forumtype'];
		$taglist = $forumsettings['taglist'];
		if ($replyby!=null && $replyby<2000000000 && $replyby>0) {
			$replybydate = tzdate("m/d/Y",$replyby);
			$replybytime = tzdate("g:i a",$replyby);
		} else {
			$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
			$replybytime = tzdate("g:i a",time()+7*24*60*60);
		}
		if ($forumsettings['postinstr'] != '' && $_GET['modify']=="new") {
			echo '<h3>'._('Posting Instructions').'</h3>';
			// $forumsettings['postinstr'] contains HTML.
			echo '<div class="intro">'.Sanitize::outgoingHtml($forumsettings['postinstr']).'</div><br/>';
		} else if ($forumsettings['replyinstr'] != '' && $_GET['modify']=="reply") {
			echo '<h3>'._('Reply Instructions').'</h3>';
			// $forumsettings['replyinstr'] contains HTML.
			echo '<div class="intro">'.Sanitize::outgoingHtml($forumsettings['replyinstr']).'</div><br/>';
		}
		echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"$returnurl&modify=".Sanitize::encodeUrlParam($_GET['modify'])."&replyto=".Sanitize::encodeUrlParam($_GET['replyto'])."\">\n";
		echo '<input type="hidden" name="MAX_FILE_SIZE" value="10485760" />';
		if (isset($notice) && $notice!='') {
			echo '<span class="form">&nbsp;</span><span class="formright">'.$notice.'</span><br class="form"/>';
		} else {
			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"".Sanitize::encodeStringForDisplay($line['subject'])."\"></span><br class=form>\n";
			if ($forumtype==1) { //file forum
				echo '<script type="text/javascript">
					var filecnt = 1;
					function addnewfile(t) {
						var s = document.createElement("span");
						s.innerHTML = \'<label for="newfiledesc-\'+filecnt+\'">Description</label>: <input type="text" name="newfiledesc-\'+filecnt+\' id="newfiledesc-\'+filecnt+\'" /><br/><label for="newfile-\'+filecnt+\'">File</label>: <input type="file" name="newfile-\'+filecnt+\'" id="newfile-\'+filecnt+\'" /><br/>\';
						t.parentNode.insertBefore(s,t);
						filecnt++;
					}</script>';
				echo "<span class=form>Files:</span>";
				echo "<span class=formright>";
				if ($line['files']!='') {
					require_once('../includes/filehandler.php');
					$files = explode('@@',$line['files']);
					for ($i=0;$i<count($files)/2;$i++) {
						echo '<input type="text" name="filedesc['.$i.']" value="'.Sanitize::encodeStringForDisplay($files[2*$i]).'" aria-label="'._('Description').'"/>';
						// $_GET['modify'] will be sanitized by getuserfileurl().
						echo '<a href="'.getuserfileurl('ffiles/'.$_GET['modify'].'/'.$files[2*$i+1]).'" target="_blank">View</a> ';
						echo '<label for="filedel['.$i.']">Delete?</label> <input type="checkbox" name="filedel['.$i.']" id="filedel['.$i.']" value="1"/><br/>';
					}
				}
				echo '<label for="newfiledesc-0">Description</label>: <input type="text" name="newfiledesc-0" id="newfiledesc-0" /><br/>';
				echo '<label for=>File</label>: <input type="file" name="newfile-0" id="newfile-0" /><br/>';
				echo '<a href="#" onclick="addnewfile(this);return false;">Add another file</a>';
				echo "</span><br class=form>\n";
			}
			if ($taglist!='' && ($_GET['modify']=='new' || $_GET['modify']==$threadid)) {
				$p = strpos($taglist,':');
				echo '<span class="form"><label for="tag">'.Sanitize::encodeStringForDisplay(substr($taglist,0,$p)).'</label></span>';
				echo '<span class="formright"><select name="tag" id="tag">';
				echo '<option value="">Select...</option>';
				$tags = explode(',',substr($taglist,$p+1));
				foreach ($tags as $tag) {
					$tag =  str_replace('"','&quot;',$tag);
					echo '<option value="'.$tag.'" ';
					if ($tag==$line['tag']) {echo 'selected="selected"';}
					echo '>'.$tag.'</option>';
				}
				echo '</select></span><br class="form" />';
			}
			echo "<span class=form><label for=\"message\">Message:</label></span>";
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
			echo Sanitize::encodeStringForDisplay($line['message'], true);
			echo "</textarea></div></span><br class=form>\n";
			if (!$isteacher && $allowanon==1) {
				echo "<span class=form>Post Anonymously:</span><span class=formright>";
				echo "<input type=checkbox name=\"postanon\" value=1 ";
				if ($line['isanon']==1) {echo "checked=1";}
				echo "></span><br class=form/>";
			} else if ($allowanon==1 && $line['isanon']==1) { //teacher editing an anonymous post, perhaps
				echo '<input type=hidden name=postanon value=1 />';
			}
			if ($isteacher && ($_GET['modify']=='new' || $line['userid']==$userid) && ($_GET['modify']=='new' || $_GET['modify']==$_GET['thread'] || ($_GET['modify']!='reply' && $line['parent']==0))) {
				echo "<span class=form id=posttypelabel>Post Type:</span><span class=formright role=radiogroup aria-labelledby=posttypelabel>\n";
				echo "<input type=radio name=type id=type0 value=0 ";
				if ($line['posttype']==0) { echo "checked=1 ";}
				echo "> <label for=type0>Regular</label><br>\n";
				echo "<input type=radio name=type value=1 id=type1 ";
				if ($line['posttype']==1) { echo "checked=1 ";}
				echo "> <label for=type1>Displayed at top of list</label><br>\n";
				echo "<input type=radio name=type value=2 id=type2 ";
				if ($line['posttype']==2) { echo "checked=1 ";}
				echo "> <label for=type2>Displayed at top and locked (no replies)</label><br>\n";
				echo "<input type=radio name=type value=3 id=type3 ";
				if ($line['posttype']==3) { echo "checked=1 ";}
				echo "> <label for=type3>Displayed at top and students can only see their own replies</label>\n";
				echo "</span><br class=form>";

				echo "<span class=form id=allowreplieslabel>Allow replies: </span><span class=formright role=radiogroup aria-labelledby=allowreplieslabel>\n";
				echo "<input type=radio name=replyby id=replyby0 value=\"null\" ";
				if ($line['replyby']==null) { echo "checked=1 ";}
				echo "/> <label for=replyby0>Use default</label><br/>";
				echo "<input type=radio name=replyby id=replyby1 value=\"Always\" ";
				if ($line['replyby']==2000000000) { echo "checked=1 ";}
				echo "/> <label for=replyby1>Always</label><br/>";
				echo "<input type=radio name=replyby id=replyby2 value=\"Never\" ";
				if ($line['replyby']==='0') { echo "checked=1 ";}
				echo "/> <label for=replyby2>Never</label><br/>";
				echo "<input type=radio name=replyby id=replyby3 value=\"Date\" ";
				if ($line['replyby']!==null && $line['replyby']<2000000000 && $line['replyby']>0) { echo "checked=1 ";}
				echo "/> <label for=replyby3>Before:</label> ";
				echo "<input type=text size=10 name=replybydate value=\"".Sanitize::encodeStringForDisplay($replybydate)."\" aria-label=\"reply by date\"/>";
				echo '<a href="#" onClick="displayDatePicker(\'replybydate\', this); return false">';
				//echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].replybydate,'anchor3','MM/dd/yyyy',(document.forms[0].replybydate.value==$replybydate')?(document.forms[0].replyby.value):(document.forms[0].replyby.value)); return false;\" NAME=\"anchor3\" ID=\"anchor3\">
				echo "<img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></A>";
				echo "at <input type=text size=10 name=replybytime value=\"".Sanitize::encodeStringForDisplay($replybytime)."\" aria-label=\"reply by time\"></span><br class=\"form\" />";

				$thread_lastposttime = 0;

				if ($_GET['modify']!='new' && $line['parent']==0) {
					$stm = $DBH->prepare("SELECT lastposttime FROM imas_forum_threads WHERE id=:id");
					$stm->execute(array(':id'=>$line['id']));
					$thread_lastposttime = $stm->fetchColumn(0);
					$releasebydate = tzdate("m/d/Y",$thread_lastposttime);
					$releasebytime = tzdate("g:i a",$thread_lastposttime);
				} else {
					$releasebydate = tzdate("m/d/Y",$now);
					$releasebytime = tzdate("g:i a",$now);
				}
				echo "<span class=form id=releasepostlabel>Release Post:</span><span class=formright role=radiogroup aria-labelledby=releasepostlabel>\n";
				if ($_GET['modify']=='new') {
					echo "<input type=radio name=releaseon id=releaseon1 value=\"Immediately\" ";
					if ($thread_lastposttime<=$now) { echo "checked=1 ";}
					echo "/> <label for=releaseon1>Immediately</label><br/>";
				} else {
					echo "<input type=radio name=releaseon id=releaseon1 value=\"nochange\" ";
					if ($thread_lastposttime<=$now) { echo "checked=1 ";}
					echo "/> <label for=releaseon1>Keep original post date</label><br/>";
					echo "<input type=radio name=releaseon id=releaseon2 value=\"Immediately\"/> ";
					echo "<label for=releaseon2>Change post date to now</label><br/>";
				}
				echo "<input type=radio name=releaseon id=releaseon3 value=\"Date\" ";
				if ($thread_lastposttime>$now) { echo "checked=1 ";}
				echo "/> <label for=releaseon3>Later:</label> ";
				echo "<input type=text size=10 name=releasedate value=\"$releasebydate\" aria-label=\"post release date\"/>";
				echo '<a href="#" onClick="displayDatePicker(\'releasedate\', this); return false">';
				echo "<img src=\"$staticroot/img/cal.gif\" alt=\"Calendar\"/></A>";
				echo "at <input type=text size=10 name=releasetime value=\"$releasebytime\" aria-label=\"post release time\"></span><br class=\"form\" />";
			}
			if ($groupsetid >0 && $isteacher && ($_GET['modify']=='new' || ($_GET['modify']!='reply' && $line['parent']==0))) {
                if ($isSectionGroups) {
                    echo '<script>function onstugroupchg(el) {
                        $("#nonsectionwarn").toggle(el.value==0);
                    }</script>';
                }
                echo '<span class="form"><label for="stugroup">';
                if ($isSectionGroups) {
                    echo _('Set thread to section');
                } else {
                    echo _('Set thread to group');
                }
                echo '</label>:</span><span class="formright">';
                echo '<select name="stugroup" id="stugroup"';
                if ($isSectionGroups) {
                    echo ' onchange="onstugroupchg(this)"';
                }
				echo '><option value="0" ';
				if ($curstugroupid==0) { echo 'selected="selected"';}
                echo '>';
                if ($isSectionGroups) {
                    echo _('Non section-specific');
                } else {
                    echo _('Non group-specific');
                }
                echo '</option>';
				$grpnums = 1;
				$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY name,id");
				$stm->execute(array(':groupsetid'=>$groupsetid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if ($row[1] == 'Unnamed group') {
						$row[1] .= " $grpnums";
						$grpnums++;
					}
					echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'" ';
					if ($curstugroupid==$row[0]) { echo 'selected="selected"';}
					echo '>'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
				}
                echo '</select>';
                if ($isSectionGroups) {
                    echo '<br><span id="nonsectionwarn" class="noticetext"'.($curstugroupid==0?'':' style="display:none;"').'>';
                    echo _('Warning: students from any section can reply to this post, which may expose student names cross-section.');
                    echo '</span>';
                }
                echo '</span><br class="form" />';
			}
			if ($isteacher && $haspoints && $_GET['modify']=='reply') {
				echo '<span class="form"><label for="points">Points for message you\'re replying to</label>:</span><span class="formright">';
				echo '<input type="text" size="4" name="points" id="points" value="'.Sanitize::encodeStringForDisplay($points).'" /></span><br class="form" />';
			}
			if ($_GET['modify']=='reply') {
				echo "<div class=submit><input type=submit value='Post Reply'></div>\n";
			} else if ($_GET['modify']=='new') {
				echo "<div class=submit><input type=submit value='Post Thread'></div>\n";
			} else {
				echo "<div class=submit><input type=submit value='Save Changes'></div>\n";
			}

			if ($_GET['modify']=='reply') {
				echo "<p>Replying to:</p>";
				$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName from imas_forum_posts,imas_users ";
				$query .= "WHERE imas_forum_posts.userid=imas_users.id AND (imas_forum_posts.id=:id OR imas_forum_posts.threadid=:threadid)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$threadid, ':threadid'=>$threadid));
				while ($line =  $stm->fetch(PDO::FETCH_ASSOC)) {
					$parent[$line['id']] = $line['parent'];
					$date[$line['id']] = $line['postdate'];
					$subject[$line['id']] = $line['subject'];
					if ($_SESSION['graphdisp']==0) {
						$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
					}
					$message[$line['id']] = $line['message'];
					$posttype[$line['id']] = $line['posttype'];
					if ($line['isanon']==1) {
						$poster[$line['id']] = "Anonymous";
					} else {
						$poster[$line['id']] = Sanitize::encodeStringForDisplay($line['FirstName'] . ' ' . $line['LastName']);
						if ($isteacher && $line['userid']!=$userid) {
							$poster[$line['id']] .= " <a class=\"small\" href=\"$imasroot/course/gradebook.php?cid=$cid&stu={$line['userid']}\" target=\"_popoutgradebook\">[GB]</a>";
						}
					}
				}
				function printparents($id) {
					global $parent,$date,$subject,$message,$posttype,$poster;
					echo "<div class=block>";
					echo "<b>{$subject[$id]}</b><br/>Posted by: {$poster[$id]}, ";
					echo tzdate("F j, Y, g:i a",$date[$id]);
					echo "</div><div class=blockitems>";
					echo filter($message[$id]);
					echo "</div>\n";
					if ($parent[$id]!=0) {
						printparents($parent[$id]);
					}
				}
				printparents($_GET['replyto']);
			} else {
				echo '<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>';
			}
		}
		echo '</form>';
		require("../footer.php");
		exit;
	}
} else if (isset($_GET['remove']) && $allowdel) {// $isteacher) { //removing post
	if (isset($_POST['confirm'])) {
		$go = true;
		if (!$isteacher) {
			$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE parent=:parent");
			$stm->execute(array(':parent'=>$_GET['remove']));
			if ($stm->rowCount()>0) {
				$go = false;
			}
		}
		if ($go) {
			require_once("../includes/filehandler.php");
			$stm = $DBH->prepare("SELECT parent,files FROM imas_forum_posts WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['remove']));
			list($parent,$files) = $stm->fetch(PDO::FETCH_NUM);

			if ($parent==0) {
				$stm = $DBH->prepare("SELECT id,files FROM imas_forum_posts WHERE threadid=:threadid");
				$stm->execute(array(':threadid'=>$_GET['remove']));
				$children = array();
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$children[] = $row[0];
					if ($row[1]!='') {
						deleteallpostfiles($row[0]); //delete files for each post
					}
				}
				if (count($children)>0) {
					$ph = Sanitize::generateQueryPlaceholders($children);
					$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='forum' AND refid IN ($ph)");
					$stm->execute($children);
				}
				$stm = $DBH->prepare("DELETE FROM imas_forum_posts WHERE threadid=:threadid");
				$stm->execute(array(':threadid'=>$_GET['remove']));
				$stm = $DBH->prepare("DELETE FROM imas_forum_threads WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['remove']));
				$stm = $DBH->prepare("DELETE FROM imas_forum_views WHERE threadid=:threadid");
				$stm->execute(array(':threadid'=>$_GET['remove']));
				$lastpost = true;

			} else {
				$stm = $DBH->prepare("DELETE FROM imas_forum_posts WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['remove']));
				$stm = $DBH->prepare("UPDATE imas_forum_posts SET parent=:parent WHERE parent=:parent2");
				$stm->execute(array(':parent'=>$parent, ':parent2'=>$_GET['remove']));
				$lastpost = false;

				if ($files!= '') {
					deleteallpostfiles(Sanitize::onlyInt($_GET['remove']));
				}
			}
			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='forum' AND refid=:refid");
			$stm->execute(array(':refid'=>$_GET['remove']));

		}
		if ($caller == "posts" && $lastpost) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/thread.php?page=$page&cid=$cid&forum=$forumid&r=" . Sanitize::randomQueryStringParam());
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/$returnurl&r=" . Sanitize::randomQueryStringParam());
		}
		exit;
	} else {
		$pagetitle = "Remove Post";
		$stm = $DBH->prepare("SELECT parent FROM imas_forum_posts WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['remove']));
		$parent = $stm->fetchColumn(0);

		require("../header.php");
		if (!$isteacher) {
			$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE parent=:parent");
			$stm->execute(array(':parent'=>$_GET['remove']));
			if ($stm->rowCount()>0) {
			echo "Someone has replied to this post, so you cannot remove it.  <a href=\"$returnurl\">Back</a>";
				require("../footer.php");
				exit;
			}
		}
		if (empty($_GET['embed'])) {
            echo "<div class=breadcrumb>";
            if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
                echo "$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
            }   
            if ($caller!='thread') {echo "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";}
			echo "<a href=\"$returnurl\">$returnname</a> &gt; Remove Post</div>";
		}

		echo "<h2>Remove Post</h2>\n";
		if ($parent==0) {
			echo "<p>Are you SURE you want to remove this thread and all replies?</p>\n";
		} else {
			echo "<p>Are you SURE you want to remove this post?</p>\n";
		}
		echo '<form method="post" action="'.$returnurl.'&remove='.Sanitize::onlyInt($_GET['remove']).'">';
		echo '<p><button type=submit name=confirm value=true>'._('Yes, Remove').'</button> ';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='$returnurl'\"></p>\n";
		echo '</form>';
		require("../footer.php");
		exit;
	}
} else if (isset($_GET['move']) && $isteacher) { //moving post to a different forum   NEW ONE
	if (isset($_POST['movetype'])) {
		$threadid = intval($_POST['thread']);
		$stm = $DBH->prepare("SELECT * FROM imas_forum_posts WHERE threadid=:threadid");
		$stm->execute(array(':threadid'=>$threadid));
		while ($line =  $stm->fetch(PDO::FETCH_ASSOC)) {
			$children[$line['parent']][] = $line['id'];
		}
		$tochange = array();

		function addchildren($b,&$tochange,$children) {
			if (count($children[$b])>0) {
				foreach ($children[$b] as $child) {
					$tochange[] = $child;
					if (isset($children[$child]) && count($children[$child])>0) {
						addchildren($child,$tochange,$children);
					}
				}
			}
		}
		addchildren($_GET['move'],$tochange,$children);
		$tochange[] = $_GET['move'];
		$list = implode(',', array_map('intval', $tochange));

		if ($_POST['movetype']==0) { //move to different forum
			if ($children[0][0] == $_GET['move']) { //is post head of thread?
				//if head of thread, then :
				$stm = $DBH->prepare("UPDATE imas_forum_posts SET forumid=:forumid WHERE threadid=:threadid");
				$stm->execute(array(':forumid'=>$_POST['movetof'], ':threadid'=>$_GET['move']));
				$stm = $DBH->prepare("UPDATE imas_forum_threads SET forumid=:forumid WHERE id=:id");
				$stm->execute(array(':forumid'=>$_POST['movetof'], ':id'=>$_GET['move']));
			} else {
				//if not head of thread, need to create new thread, move items to new thread, then move forum
				$stm = $DBH->prepare("SELECT lastposttime,lastpostuser FROM imas_forum_threads WHERE id=:id");
				$stm->execute(array(':id'=>$threadid));
				$row = $stm->fetch(PDO::FETCH_NUM);
				//set all lower posts to new threadid and forumid
				$stm = $DBH->prepare("UPDATE imas_forum_posts SET threadid=:threadid,forumid=:forumid WHERE id IN ($list)");
				$stm->execute(array(':threadid'=>$_GET['move'], ':forumid'=>$_POST['movetof']));
				//set post to head of thread
				$stm = $DBH->prepare("UPDATE imas_forum_posts SET parent=0 WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['move']));
				//create new threads listing
				$stm = $DBH->prepare("INSERT INTO imas_forum_threads (id,forumid,lastposttime,lastpostuser) VALUES (:id, :forumid, :lastposttime, :lastpostuser)");
				$stm->execute(array(':id'=>$_GET['move'], ':forumid'=>$_POST['movetof'], ':lastposttime'=>$row[0], ':lastpostuser'=>$row[1]));
			}
			//update grade records
			$stm = $DBH->prepare("UPDATE imas_grades SET gradetypeid=:gradetypeid WHERE gradetype='forum' AND refid IN ($list)");
			$stm->execute(array(':gradetypeid'=>$_POST['movetof']));

			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/thread.php?page=$page&cid=$cid&forum=$forumid&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else if ($_POST['movetype']==1) { //move to different thread
			if ($_POST['movetot'] != $threadid) {
	   		$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE threadid=:threadid AND parent=0");
		  	$stm->execute(array(':threadid'=>$_POST['movetot']));
				$base = $stm->fetchColumn(0);
				$stm = $DBH->prepare("UPDATE imas_forum_posts SET threadid=:threadid WHERE id IN ($list)");
				$stm->execute(array(':threadid'=>$_POST['movetot']));
				$stm = $DBH->prepare("UPDATE imas_forum_posts SET parent=:parent WHERE id=:id");
				$stm->execute(array(':parent'=>$base, ':id'=>$_GET['move']));
				if ($base != $_GET['move'] ) {//if not moving back to self,
					//delete thread.  One will only exist if moved post was head of thread
					$stm = $DBH->prepare("DELETE FROM imas_forum_threads WHERE id=:id");
					$stm->execute(array(':id'=>$_GET['move']));
				}
			}

			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/thread.php?page=$page&cid=$cid&forum=$forumid&r=" . Sanitize::randomQueryStringParam());
			exit;

		}
	} else {
		if ($caller=='thread') {
			$threadid = $_GET['move'];
		}
		$placeinhead .= '<script type="text/javascript">function toggleforumselect(v) {
			if (v==0) {document.getElementById("fsel").style.display="block";document.getElementById("tsel").style.display="none";}
			if (v==1) {document.getElementById("tsel").style.display="block";document.getElementById("fsel").style.display="none";}
			}</script>';
		$pagetitle = "Move Thread";

		require("../header.php");
		if (empty($_GET['embed'])) {
            echo "<div class=breadcrumb>";
            if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
                echo "$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
            }   
            if ($caller != 'thread') {echo "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";}
			echo "<a href=\"$returnurl\">$returnname</a> &gt; Move Thread</div>";
		}

		$stm = $DBH->prepare("SELECT parent FROM imas_forum_posts WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['move']));
		if ($stm->fetchColumn(0)==0) {
			$ishead = true;
			echo "<h2>Move Thread</h2>\n";
		} else {
			$ishead = false;
			echo "<h2>Move Post</h2>\n";
		}

		echo "<form method=post action=\"$returnurl&move=".Sanitize::encodeUrlParam($_GET['move'])."\">";
		echo '<input type="hidden" name="thread" value="'.Sanitize::encodeStringForDisplay($threadid).'"/>';
		echo "<p>What do you want to do?<br/>";
		if ($ishead) {
			echo '<input type="radio" name="movetype" value="0" id=movetype0 checked="checked" onclick="toggleforumselect(0)"/> <label for="movetype0">Move thread to different forum</label><br/>';
			echo '<input type="radio" name="movetype" value="1" id=movetype1 onclick="toggleforumselect(1)"/> <label for="movetype1">Move post to be a reply to a thread</label>';
		} else {
			echo '<input type="radio" name="movetype" value="0" id=movetype0 onclick="toggleforumselect(0)"/> <label for="movetype0">Move post to be a new thread in this or another forum</label><br/>';
			echo '<input type="radio" name="movetype" value="1" id=movetype1 checked="checked" onclick="toggleforumselect(1)"/> <label for="movetype1">Move post to be a reply to a different thread</label>';
		}
		echo '</p>';
		echo '<div id="fsel" ';
		if (!$ishead) {echo 'style="display:none;"';}
		echo '>Move to forum:<br/>';
		$stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo "<input type=\"radio\" name=\"movetof\" value=\"".Sanitize::onlyInt($row[0])."\" id=\"moveto".Sanitize::onlyInt($row[0])."\" ";
			if ($row[0]==$forumid) {echo 'checked="checked"';}
			echo "/> <label for=\"moveto".Sanitize::onlyInt($row[0])."\">".Sanitize::encodeStringForDisplay($row[1])."</label><br/>";
		}
		echo '</div>';

		echo '<div id="tsel" ';
		if ($ishead) {echo 'style="display:none;"';}
		echo '>Move to thread:<br/>';
		$stm = $DBH->prepare("SELECT threadid,subject FROM imas_forum_posts WHERE forumid=:forumid AND parent=0 ORDER BY id DESC");
		$stm->execute(array(':forumid'=>$forumid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($ishead && $row[0]==$threadid) {continue;}
			echo "<input type=\"radio\" name=\"movetot\" value=\"".Sanitize::encodeStringForDisplay($row[0])."\" id=\"movetot".Sanitize::encodeStringForDisplay($row[0])."\" ";
			if ($row[0]==$threadid) {echo 'checked="checked"';}
			echo "/> <label for=\"movetot".Sanitize::encodeStringForDisplay($row[0])."\">".Sanitize::encodeStringForDisplay($row[1])."</label><br/>";
		}
		echo '</div>';

		echo "<p><input type=submit value=\"Move\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='$returnurl'\"></p>\n";
		echo "</form>";
		require("../footer.php");
		exit;

	}
}
?>
