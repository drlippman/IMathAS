<?php
	//Displays Message list
	//(c) 2006 David Lippman
	/*

viewed: 0 unread, 1 read
deleted: 0 not deleted, 1 deleted by sender, 2 deleted by reader
tagged: 0 no, 1 yes

If deleted on both ends, delete from DB

	*/
	require_once "../init.php";
	require_once '../includes/getcourseopts.php';

	if (isset($cid) && $cid!=0 && !isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require_once "../header.php";
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require_once "../footer.php";
	   exit;
	}

	$isteacher = isset($teacherid);
    $isstudent = isset($studentid);
    $istutor = isset($tutorid);

	$cansendmsgs = false;
	$threadsperpage = intval($listperpage);

	$cid = Sanitize::courseId($_GET['cid']);
    
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = Sanitize::onlyInt($_GET['page']);
	}
	if ($page==-1) {
		$limittonew = 1;
	} else {
		$limittonew = 0;
	}
	if ($page==-2) {
		$limittotagged = 1;
	} else {
		$limittotagged = 0;
	}
	if (isset($_GET['filtercid'])) {
		$filtercid = intval($_GET['filtercid']);
	} else if ($cid!='admin' && $cid>0) {
		$filtercid = $cid;
	} else {
		$filtercid = 0;
	}
	if (isset($_GET['filteruid'])) {
		$filteruid = intval($_GET['filteruid']);
	} else {
		$filteruid = 0;
	}
	$type = $_GET['type'] ?? '';

	if (isset($_GET['getstulist'])) {
		$cid = intval($_GET['getstulist']);
		if ($cid==0) { echo '[]'; exit;}
		$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$msgset = $stm->fetchColumn(0);
		$msgmonitor = (floor($msgset/5)&1);
		$msgset = $msgset%5;

		$isauth = false;
		$isteacher = false;
		$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=? AND courseid=?");
		$stm->execute(array($userid, $cid));
		if ($stm->rowCount()>0) {
			$isteacher = true;
			$isauth = true;
		} else {
			$stm = $DBH->prepare("SELECT section FROM imas_students WHERE userid=? AND courseid=? UNION SELECT section FROM imas_tutors WHERE userid=? AND courseid=?");
			$stm->execute(array($userid, $cid, $userid, $cid));
			if ($stm->rowCount()>0) {
				$isauth = true;
				$studentinfo = ['section' => $stm->fetchColumn(0)];
			} 
		}
		if (!$isauth) {
			echo '[]';
			exit;
		}

		$opts = array();
		if ($isteacher || $msgset<2) {
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			$query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
			$query .= "imas_teachers.courseid=:courseid ORDER BY imas_users.LastName";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$opts[] = "<option value=\"".Sanitize::onlyInt($row[0])."\">".Sanitize::encodeStringForDisplay("$row[2], $row[1]")."</option>";
			}
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			$query .= "imas_users,imas_tutors WHERE imas_users.id=imas_tutors.userid AND ";
			$query .= "imas_tutors.courseid=:courseid ";
			if (!$isteacher && !empty($studentinfo['section'])) {
			     $query .= "AND (imas_tutors.section=:section OR imas_tutors.section='') ";
			}
			$query .= "ORDER BY imas_users.LastName";
			$stm = $DBH->prepare($query);
			if (!$isteacher && !empty($studentinfo['section'])) {
			   $stm->execute(array(':courseid'=>$cid, ':section'=>$studentinfo['section']));
			} else {
				$stm->execute(array(':courseid'=>$cid));
			}
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$opts[] = sprintf('<option value="%d">%s, %s</option>', $row[0],
                    Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
			}
		}
		if ($isteacher || $msgset==0 || $msgset==2) {
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			$query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
			$query .= "imas_students.courseid=:courseid ORDER BY imas_users.LastName";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$opts[] = sprintf('<option value="%d"><span class="pii-first-name">%s, %s</span></option>',
                    $row[0], Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
			}
		}
		echo json_encode($opts, JSON_INVALID_UTF8_IGNORE);
		exit;
	}
    if (isset($_POST['searchstu'])) {
        $words = preg_split('/\s+/', str_replace(',',' ',trim($_POST['searchstu'])));
        $query = "SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu JOIN ";
        $query .= "imas_students as istu on iu.id=istu.userid AND istu.courseid=? ";
        // todo: add section limit?
        if (count($words)==1) {
            $query .= "AND (iu.LastName LIKE ? OR iu.FirstName Like ?)";
            $stm = $DBH->prepare($query);
            $stm->execute([$cid, $words[0].'%', $words[0].'%']);
        } else {
            $query .= " AND ((iu.LastName LIKE ? AND iu.FirstName Like ?) OR (iu.LastName LIKE ? AND iu.FirstName Like ?))";
            $stm = $DBH->prepare($query);
            $stm->execute([$cid, $words[0].'%', $words[1].'%', $words[1].'%', $words[0].'%']);
        }
        $opts = [];
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $opts[] = sprintf('<option value="%d"><span class="pii-first-name">%s, %s</span></option>',
                $row[0], Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
        }
        echo json_encode($opts, JSON_INVALID_UTF8_IGNORE);
		exit;
    }
	if (isset($_GET['add'])) {
        if (isset($_POST['to']) && $_POST['to'] == 'search') {
            $_POST['to'] = $_POST['to2']; // use result from search selector
        }
		if (isset($_POST['subject']) && isset($_POST['to']) && $_POST['to']!='0') {
            $msgToPost = Sanitize::onlyInt($_POST['to']);
            $cidP = Sanitize::courseId($_POST['courseid']);

			// validate message settings allow this
			$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$cidP));
			$msgset = ($stm->fetchColumn(0))%5;
			$stm = $DBH->prepare("SELECT userid FROM imas_teachers WHERE courseid=?");
			$stm->execute(array($cidP));
			$teacherList = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
			$stm = $DBH->prepare("SELECT userid FROM imas_tutors WHERE courseid=?");
			$stm->execute(array($cidP));
			$tutorList = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
			$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=?");
			$stm->execute(array($cidP));
			$studentList = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

			$isvalid = false;
			if (in_array($userid, $studentList)) { // sender is student
				if ($msgset == 2 && in_array($msgToPost, $studentList)) { //only can send to student
					$isvalid = true;
				} else if ($msgset == 1 && // only can send to teacher
					(in_array($msgToPost, $teacherList) || in_array($msgToPost, $tutorList))
				) {
					$isvalid = true;
				} else if ($msgset == 0 && (
					in_array($msgToPost, $studentList) ||
					in_array($msgToPost, $teacherList) ||
					in_array($msgToPost, $tutorList)
				)) {
					$isvalid = true;
				}
			} else if (in_array($userid, $teacherList) || in_array($userid, $tutorList)) { // sender is teacher or tutor
				if ($msgset < 4 && (
					in_array($msgToPost, $studentList) ||
					in_array($msgToPost, $teacherList) ||
					in_array($msgToPost, $tutorList)
				)) {
					$isvalid = true;
				}
			}
			if (!$isvalid) {
				require_once "../header.php";
				echo 'You are not permitted to send a message to that user in this course.';
				require_once "../footer.php";
				exit;
			}

      $messagePost = Sanitize::incomingHtml($_POST['message']);
			$subjectPost = Sanitize::stripHtmlTags($_POST['subject']);
			if (trim($subjectPost)=='') {
				$subjectPost = '('._('none').')';
			}

      $now = time();
			$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,courseid) VALUES ";
			$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :courseid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':title'=>$subjectPost, ':message'=>$messagePost, ':msgto'=>$msgToPost,
        ':msgfrom'=>$userid, ':senddate'=>$now, ':courseid'=>$cidP));
			$msgid = $DBH->lastInsertId();

			if ($_GET['replyto']>0) {
				$query = "UPDATE imas_msgs SET replied=1";
				if (isset($_POST['sendunread'])) {
					$query .= ',viewed=0';
				}
        $query .= " WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$_GET['replyto']));
				$stm = $DBH->prepare("SELECT baseid FROM imas_msgs WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['replyto']));
				$baseid = $stm->fetchColumn(0);
				if ($baseid==0) {
					$baseid = $_GET['replyto'];
				}
				$stm = $DBH->prepare("UPDATE imas_msgs SET baseid=:baseid,parent=:parent WHERE id=:id");
				$stm->execute(array(':baseid'=>$baseid, ':parent'=>$_GET['replyto'], ':id'=>$msgid));
			}
			$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$cidP));
			$cname = $stm->fetchColumn(0);
			$stm = $DBH->prepare("SELECT msgnotify,email,FCMtoken FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_POST['to']));
			list($msgnotify, $email, $FCMtokenTo) = $stm->fetch(PDO::FETCH_NUM);
			if ($msgnotify==1) {
      	  		require_once "../includes/email.php";
                if (!empty($studentinfo['section'])) {
                    $cname .= ' (' . $studentinfo['section'] . ')';
                }
      	  		send_msg_notification(Sanitize::emailAddress($email), $userfullname, $subjectPost, $cidP, $cname, $msgid);
			}
			if ($FCMtokenTo != '') {
				require_once "../includes/FCM.php";
				$url = $GLOBALS['basesiteurl'] . "/msgs/viewmsg.php?cid=".Sanitize::courseId($cidP)."&msgid=$msgid";
				sendFCM($FCMtokenTo,_("Msg from:").' '.Sanitize::encodeStringForDisplay($userfullname),
					Sanitize::encodeStringForDisplay($subjectPost), $url);
			}
			if ($type=='new') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/newmsglist.php?cid=$cid&r=" .Sanitize::randomQueryStringParam());
			} else {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/msglist.php?page=$page&cid=$cid&filtercid=$filtercid&r=" .Sanitize::randomQueryStringParam());
			}
			exit;
		} else {
			$pagetitle = "New Message";
			$useeditor = "message";
			$loadgraphfilter = true;
			$placeinhead = '<script type="text/javascript">
				function checkrecipient() {
					if (document.getElementById("to").value=="0") {
						alert("No recipient selected");
						return false;
					} else if (document.getElementById("to").value=="search" && 
                        (document.getElementById("to2").value=="0" || document.getElementById("to2").value=="")) {
						alert("No recipient selected");
						return false;
					} else {
						return true;
					}
				}
				function updateTo(el) {
					var newcid = $(el).val();
					$("#to").hide();
					if (newcid>0) {
						$("#statusmsg").text(_("Loading recipients"));
						$(el).after($("<img>", {src: staticroot+"/img/updating.gif", alt: "Loading recipients..."}));
						$.ajax({
							url: "msglist.php?cid=0&getstulist="+newcid,
							dataType: "json",
						}).done(function(optarr) {
							$("#to").empty().append("<option value=\'0\'>'._('Select a recipient...').'</option>");
							for (var i=0;i<optarr.length;i++) {
								$("#to").append($(optarr[i]));
							}
							$("#to").show();
							$(el).siblings("img").remove();
							$("#statusmsg").text(_("Done"));
						});
					} else {
						$("#to").val(0);
					}
				}
                function checkTo(el) {
                    if ($(el).val() == "search") {
                        $("#stusearchwrap").show();
                    } else {
                        $("#stusearchwrap,#stusearchresultwrap").hide();
                        $("#to2").empty();
                    }
                }
                function searchForStu() {
                    var stu = $("#stusearch").val();
                    if (stu !== "") {
                        $("#stusearchresultwrap").hide();
                        $("#stusearchwrap").after($("<img>", {src: staticroot+"/img/updating.gif", alt: "Loading recipients..."}));
						$("#statusmsg").text(_("Loading recipients"));
                        $.ajax({
							url: "msglist.php?cid='.$cid.'",
                            type: "POST",
                            data: {searchstu: $("#stusearch").val()},
                            dataType: "json",
                        }).done(function(optarr) {
                            $("#to2").empty();
                            if (optarr.length > 1) {
                                $("#to2").append("<option value=\'0\'>'._('Select a recipient...').'</option>");
                            }
                            for (var i=0;i<optarr.length;i++) {
                                $("#to2").append($(optarr[i]));
                            }
                            $("#stusearchresultwrap").show();
                            $("#stusearchwrap").siblings("img").remove();
							$("#statusmsg").text(_("Done"));
                        });
                    }
                }
                </script>';

			require_once "../header.php";
			echo "<div class=breadcrumb>$breadcrumbbase ";
			if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
				echo "<a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
			}
			if ($type=='sent') {
				echo " <a href=\"sentlist.php?page=$page&cid=$cid&filtercid=$filtercid\">Sent Message List</a> ";
			} else if ($type=='allstu') {
				echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; <a href=\"allstumsglist.php?page=$page&cid=$cid&filterstu=$filterstu\">Student Messages</a> ";
			} else if ($type=='new') {
				echo " <a href=\"newmsglist.php?cid=$cid\">New Message List</a> ";
			} else {
				echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> ";
			}

			if (isset($_GET['toquote'])) {
				$replyto = $_GET['toquote'];
			} else if (isset($_GET['replyto'])) {
				$replyto = $_GET['replyto'];
			} else {
				$replyto = 0;
			}

			if ($replyto > 0) {
				echo "&gt; <a href=\"viewmsg.php?page=$page&type=".Sanitize::encodeUrlParam($type)."&cid=$cid&filtercid=$filtercid&msgid=".Sanitize::onlyInt($replyto)."\">Message</a> ";
				echo "&gt; Reply</div>";
				echo "<h1>Reply</h1>\n";
			} else {
				echo "&gt; New Message</div>";
				echo "<h1>New Message</h1>\n";
			}


			if ($filtercid>0) {
				$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$filtercid));
				$msgset = $stm->fetchColumn(0);
				$msgmonitor = (floor($msgset/5)&1);
				$msgset = $msgset%5;
			} else {
				$courseopts = getCourseOpts(true);
                $msgmonitor = 0;
			}

			$courseid=($cid==0)?$filtercid:$cid;
			if (isset($_GET['toquote']) || isset($_GET['replyto'])) {
				$stm = $DBH->prepare("SELECT title,message,courseid FROM imas_msgs WHERE id=:id");
				$stm->execute(array(':id'=>$replyto));
        list($title, $message, $courseid) = $stm->fetch(PDO::FETCH_NUM);
				$title = _("Re: ").$title;
				if (isset($_GET['toquote'])) {
					$message = '<br/><hr/>'._('In reply to:').'<br/>'.$message;
				} else {
					$message = '';
				}
			} else if (isset($_GET['quoteq'])) {
                $parts = explode('-',$_GET['quoteq']);
				$GLOBALS['assessver'] = $parts[4];
                if ($courseUIver > 1) {
                    require_once '../assess2/AssessStandalone.php';
                    $a2 = new AssessStandalone($DBH);
                    $state = array(
                        'seeds' => array($parts[0] => $parts[2]),
                        'qsid' => array($parts[0] => $parts[1])
                    );
                    $a2->setState($state);
                    $a2->loadQuestionData();
                    $res = $a2->displayQuestion($parts[0], ['showhints'=>false]);
                    $message = $res['html'];
                    $message = preg_replace('/<div class="question"[^>]*>/','<div>', $message);
                } else {
                    require_once "../assessment/displayq2.php";
                    $message = displayq($parts[0],$parts[1],$parts[2],false,false,0,true);
                }
				$message = printfilter(forcefiltergraph($message));
				if (isset($CFG['GEN']['AWSforcoursefiles']) && $CFG['GEN']['AWSforcoursefiles'] == true) {
					require_once "../includes/filehandler.php";
					$message = preg_replace_callback('|'.$imasroot.'/filter/graph/imgs/([^\.]*?\.png)|', function ($matches) {
						$curdir = rtrim(dirname(__FILE__), '/\\');
						return relocatefileifneeded($curdir.'/../filter/graph/imgs/'.$matches[1], 'gimgs/'.$matches[1]);
					    }, $message);
				}
				$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
				$qinfo = 'Question ID '.Sanitize::onlyInt($parts[1]).', seed '.Sanitize::onlyInt($parts[2]);
				$message = '<br/><hr/>'.$qinfo.'<br/><br/>'.$message;
				//$message .= '<span class="hidden">QREF::'.htmlentities($_GET['quoteq']).'</span>';
				if (isset($parts[3]) && $parts[3] === 'reperr') {
                    $title = "Problem with question ID ".Sanitize::onlyInt($parts[1]);
                    $query = 'SELECT iqs.ownerid,iu.lastaccess FROM imas_questionset AS iqs
                        JOIN imas_users AS iu ON iqs.ownerid=iu.id WHERE iqs.id=:id';
					$stm = $DBH->prepare($query);
                    $stm->execute(array(':id'=>$parts[1]));
                    $r = $stm->fetch(PDO::FETCH_ASSOC);
                    $_GET['to'] = $r['ownerid'];
                    if (!empty($CFG['GEN']['qerroronold']) && $r['lastaccess'] < time() - 60*60*24*$CFG['GEN']['qerroronold'][0]) {
                        $_GET['to'] = $CFG['GEN']['qerroronold'][1];
                    }
				} else if (isset($parts[3]) && $parts[3]>0) {  //sending out of assessment instructor
					$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
					$stm->execute(array(':id'=>$parts[3]));
					if (isset($teacherid) || isset($tutorid)) {
						$title = 'Question #'.($parts[0]+1).' in '.$stm->fetchColumn(0);
					} else {
						$title = 'Question about #'.($parts[0]+1).' in '.$stm->fetchColumn(0);
					}
					if ($_GET['to']=='instr') {
						unset($_GET['to']);
						$msgset = 1; //force instructor only list
					}
				} else {
					$title = '';
				}
			} else if (isset($_GET['title'])) {
				$title = $_GET['title'];
				$message = '';
			} else {
				$title = '';
				$message = '';
			}

			echo "<form method=post action=\"msglist.php?page=$page&type=".Sanitize::encodeUrlParam($type)."&cid=$cid&add=".Sanitize::encodeUrlParam($_GET['add'])."&replyto=".Sanitize::onlyInt($replyto).'"';
			if (!isset($_GET['to'])) {
				echo " onsubmit=\"return checkrecipient();\"";
			}
			echo ">\n";
			echo "<span class=form>To:</span><span class=formright>\n";
			if (isset($_GET['to'])) {
				$to = Sanitize::onlyInt($_GET['to']);
				$query = "SELECT iu.LastName,iu.FirstName,iu.email,i_s.lastaccess,iu.hasuserimg FROM imas_users AS iu ";
				$query .= "LEFT JOIN imas_students AS i_s ON iu.id=i_s.userid AND i_s.courseid=:courseid WHERE iu.id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$courseid, ':id'=>$_GET['to']));
				$row = $stm->fetch(PDO::FETCH_NUM);
				printf('<span class="pii-full-name">%s, %s</span>',
                    Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]));
				$ismsgsrcteacher = false;
				if ($courseid==$cid && $isteacher) {
					$ismsgsrcteacher = true;
				} else if ($courseid!=$cid) {
					$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$courseid));
					if ($stm->rowCount()!=0) {
						$ismsgsrcteacher = true;
					}
				}
				if ($ismsgsrcteacher) {
					echo " <a class=\"pii-email\" href=\"mailto:".Sanitize::emailAddress($row[2])."\"><span class='pii-safe'>email</span></a> | ";
					echo " <a href=\"$imasroot/course/gradebook.php?cid=".Sanitize::courseId($courseid)."&stu=". Sanitize::onlyInt($to)."\" target=\"_blank\">gradebook</a>";
					if ($row[3]!=null) {
						echo " | Last login ".tzdate("F j, Y, g:i a",$row[3]);
					}
				}
				echo "<input type=hidden name=to value=\"$to\"/>";
				$curdir = rtrim(dirname(__FILE__), '/\\');
				if (isset($_GET['to']) && $row[4]==1) {
					if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
						echo " <img class='pii-image' style=\"vertical-align: middle;\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm$to.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/><br/>";
					} else {
						echo " <img class='pii-image' style=\"vertical-align: middle;\" src=\"$imasroot/course/files/userimg_sm$to.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/><br/>";
					}
				}
				echo "<input type=hidden name=courseid value=\"".Sanitize::courseId($courseid)."\"/>\n";
			} else {
				if ($filtercid>0) {
					echo '<select class="pii-full-name" name="to" id="to" aria-label="'._('Select the recipient').'" onchange="checkTo(this)">';
					echo '<option value="0">' . _('Select a recipient...') . '</option>';
					if ($isteacher || $msgset<2) {
						$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
						$query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
						$query .= "imas_teachers.courseid=:courseid ORDER BY imas_users.LastName";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':courseid'=>$courseid));
						$cnt = $stm->rowCount();
						while ($row = $stm->fetch(PDO::FETCH_NUM)) {
							echo "<option value=\"".Sanitize::onlyInt($row[0])."\"";
							if ($cnt==1 && $msgset==1 && !$isteacher) {
								echo ' selected="selected"';
							}
							printf(">%s, %s</option>", Sanitize::encodeStringForDisplay($row[2]),
                                Sanitize::encodeStringForDisplay($row[1]));
						}
      			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
      			$query .= "imas_users,imas_tutors WHERE imas_users.id=imas_tutors.userid AND ";
      			$query .= "imas_tutors.courseid=:courseid ";
            if (!$isteacher && !$istutor && !empty($studentinfo['section'])) {
      			     $query .= "AND (imas_tutors.section=:section OR imas_tutors.section='') ";
            }
      			$query .= "ORDER BY imas_users.LastName";
      			$stm = $DBH->prepare($query);
            if (!$isteacher && !$istutor && !empty($studentinfo['section'])) {
      			   $stm->execute(array(':courseid'=>$cid, ':section'=>$studentinfo['section']));
            } else {
               $stm->execute(array(':courseid'=>$cid));
            }
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				            printf('<option value="%d">%s, %s</option>', $row[0],
                                Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
						}


					}
					if ($isteacher || $msgset==0 || $msgset==2) {
                        $query = "SELECT count(id) FROM imas_students WHERE courseid=?";
                        $stm = $DBH->prepare($query);
						$stm->execute([$courseid]);
                        $numstu = $stm->fetchColumn(0);
                        if ($numstu > 200) {
                            echo '<option value="search">' . _('Search for a student...') . '</option>';
                        } else {
                            $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
                            $query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
                            $query .= "imas_students.courseid=:courseid ORDER BY imas_users.LastName";
                            $stm = $DBH->prepare($query);
                            $stm->execute(array(':courseid'=>$courseid));
                            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                                printf('<option value="%d">%s, %s</option>', $row[0],
                                    Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
                            }
                        }
					}
					echo "</select>";
                    echo '<span style="display:none" id="stusearchwrap"><br/>';
                    echo '<label for="stusearch">' .  _('Search for:') . '</label>';
                    echo ' <input size=30 id="stusearch"> ';
                    echo '<button type="button" onclick="searchForStu()">' . _('Search') . '</button>';
                    echo '</span>';
                    echo '<span style="display:none" id="stusearchresultwrap"><br/>';
                    echo '<select name="to2" id="to2"></select></span>';
					echo "<input type=hidden name=courseid value=\"".Sanitize::courseId($courseid)."\"/>\n";
				} else {
					echo '<select name="courseid" onchange="updateTo(this)" aria-label="'._('Select a course').'">';
					echo '<option value="0">Select a course...</option>';
					echo $courseopts;
					echo '</select><br/>';
					echo '<select name="to" id="to" style="display:none;" aria-label="'._('Select an individual').'">';
					echo '<option value="0">'._('Select a recipient...').'</option></select>';
				}

			}



			echo "</span><br class=form />";

			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"".Sanitize::encodeStringForDisplay($title)."\"></span><br class=form>\n";
			echo "<span class=form><label for=\"message\">Message:</label></span>";
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
			echo Sanitize::encodeStringForDisplay($message, true);
			echo "</textarea></div></span><br class=form>\n";
			if ($replyto>0) {
				echo '<span class="form"></span><span class="formright"><input type="checkbox" name="sendunread" id="sendunread" value="1"/> <label for="sendunread">'._('Mark original message unread').'</label></span><br class="form"/>';
			}

			if ($msgmonitor==1) {
				echo '<span class="red form">Note:</span><span class=formright>'._('Student-to-student messages may be monitored by your instructor').'</span><br class=form>';
			}
			echo '<div class="submit"><button type="submit" name="submit" value="send">'._('Send Message').'</button></div>';

			echo '</form>';
			require_once "../footer.php";
			exit;
		}
	}
	if (isset($_POST['unread'])) {
		if (!empty($_POST['checked'])) {
      $checklist = implode(',', array_map('intval', $_POST['checked']));
  		$stm = $DBH->prepare("UPDATE imas_msgs SET viewed=0 WHERE id IN ($checklist) AND viewed=1 AND msgto=?");
      $stm->execute(array($userid));
	 }
	}
	if (isset($_POST['markread'])) {
		if (!empty($_POST['checked'])) {
      $checklist = implode(',', array_map('intval', $_POST['checked']));
      $stm = $DBH->prepare("UPDATE imas_msgs SET viewed=1 WHERE id IN ($checklist) AND viewed=0 AND msgto=?");
      $stm->execute(array($userid));
	  }
	}
	if (isset($_POST['remove'])) {
		if (!empty($_POST['checked'])) {
      $checklist = implode(',', array_map('intval', $_POST['checked']));
  		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id IN ($checklist) AND deleted=1 AND msgto=?"); // already deleted by sender
      $stm->execute(array($userid));
  		$stm = $DBH->prepare("UPDATE imas_msgs SET deleted=2 WHERE id IN ($checklist) AND msgto=?");
      $stm->execute(array($userid));
  		if ($type=='new') {
  			header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/newmsglist.php?cid=$cid&r=" .Sanitize::randomQueryStringParam());
  		} else {
  			header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/msglist.php?page=$page&cid=$cid&filtercid=$filtercid&r=" .Sanitize::randomQueryStringParam());
  		}
  		exit;
  	}
	}
	if (isset($_GET['removeid'])) {
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id=:id AND deleted=1 AND msgto=:msgto");
		$stm->execute(array(':id'=>$_GET['removeid'], ':msgto'=>$userid));
		$stm = $DBH->prepare("UPDATE imas_msgs SET deleted=2 WHERE id=:id AND msgto=:msgto");
		$stm->execute(array(':id'=>$_GET['removeid'], ':msgto'=>$userid));
	}

	$pagetitle = "Messages";
	$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/msg.js?v=052725\"></script>";
	$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '". $GLOBALS['basesiteurl'] . "/msgs/savetagged.php?cid=$cid';</script>";
	$placeinhead .= '<style type="text/css"> tr.tagged {background-color: #dff;}
		.pagelist { display: inline-block; margin:0; padding: 0}
		.pagelist li { display: inline-block; padding: 0 .5ch;} 
		.pagelist li:has(a[aria-current]) { background-color: #ddd;} </style>';
	if (isset($_SESSION['ltiitemtype'])) {
		$nologo = true;
	}
	require_once "../header.php";
	$curdir = rtrim(dirname(__FILE__), '/\\');
   
	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	}
	echo " Message List</div>";
	echo '<div id="headermsglist" class="pagetitle"><h1>';
	if ($limittotagged) {
		echo _('Tagged Messages');
	} else if ($limittonew) {
		echo _('New Messages');
	} else {
		echo _('Messages');
	}
	echo '</h1></div>';

	if ($myrights > 5 && $filtercid>0) {
		$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$filtercid));
		$msgset = $stm->fetchColumn(0);
		$msgmonitor = (floor($msgset/5)&1);
		$msgset = $msgset%5;
		if ($msgset<3 || $isteacher) {
			$cansendmsgs = true;
		}
	} else if ($myrights > 5 && $filtercid==0) {
		$cansendmsgs = true;
        $msgmonitor = 0;
	}

	$actbar = array();

	if ($cansendmsgs) {
		$actbar[] = "<button type=\"button\" onclick=\"window.location.href='msglist.php?page=$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&add=new'\">"._('Send New Message')."</button>";
	}
	if ($page==-2) {
		$actbar[] = "<a href=\"msglist.php?page=1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Show All</a>";
		$actbar[] = "<a href=\"msglist.php?page=-1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Limit to New</a>";
	} else if ($page==-1) {
		$actbar[] = "<a href=\"msglist.php?page=1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Show All</a>";
		$actbar[] = "<a href=\"msglist.php?page=-2&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Limit to Tagged</a>";
	} else {
		$actbar[] = "<a href=\"msglist.php?page=-2&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Limit to Tagged</a>";
		$actbar[] = "<a href=\"msglist.php?page=-1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Limit to New</a>";
	}
	$actbar[] = "<a href=\"sentlist.php?cid=$cid\">Sent Messages</a>";

	if ($isteacher && $filtercid>0 && $msgmonitor==1) {
		$actbar[] = "<a href=\"allstumsglist.php?cid=$cid\">Student Messages</a>";
	}
	$actbar[] = '<input type="button" value="Pictures" onclick="rotatepics()" title="View/hide student pictures, if available" />';
    $actbar[] = '<a href="cleanupmsgs.php?cid='.$cid.'">' . _('Delete old messages') . '</a>';
	echo '<div class="cpmid">'.implode(' | ',$actbar).'</div>';

	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto=:msgto AND deleted<2";
  $qarr = array(':msgto'=>$userid);
	if ($filtercid>0) {
		$query .= " AND courseid=:courseid";
    $qarr[':courseid'] = $filtercid;
	}
	if ($filteruid>0) {
		$query .= " AND msgfrom=:msgfrom";
    $qarr[':msgfrom'] = $filteruid;
	}
	if ($limittotagged==1) {
		$query .= " AND tagged=1";
	}
	if ($limittonew) {
		$query .= " AND viewed=0";
	}
  $stm = $DBH->prepare($query);
  $stm->execute($qarr);
	$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);
	if ($numpages==0 && $filteruid>0) {
		//might have changed filtercid w/o changing user.
		//we'll open up to all users then
		$filteruid = 0;
    $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto=:msgto AND deleted<2";
		if ($filtercid>0) {
			$query .= " AND courseid=:courseid";
		}
		if ($limittotagged==1) {
			$query .= " AND tagged=1";
		}
		if ($limittonew) {
			$query .= " AND viewed=0";
		}
    $stm = $DBH->prepare($query);
    if ($filtercid>0) {
      $stm->execute(array(':msgto'=>$userid, ':courseid'=>$filtercid));
    } else {
      $stm->execute(array(':msgto'=>$userid));
    }
		$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);
	}
	$prevnext = '';
	if ($numpages > 1 && !$limittotagged && !$limittonew) {
		$prevnext .= '<nav aria-labelledby="pagelbl">';
		$prevnext .= "<span id=pagelbl>Page:</span><ul class=pagelist> ";
		if ($page < $numpages/2) {
			$min = max(2,$page-4);
			$max = min($numpages-1,$page+8+$min-$page);
		} else {
			$max = min($numpages-1,$page+4);
			$min = max(2,$page-8+$max-$page);
		}
		if ($page>1) {
			$prevnext .= "<li><a href=\"msglist.php?page=".($page-1)."&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Previous</a></li>";
		} else {
			$prevnext .= '<li>Previous</li>';
		}
		if ($page==1) {
			$prevnext .= "<li><a href=\"msglist.php?page=1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\" aria-current=\"page\"><b>1</b></a></li>";
		} else {
			$prevnext .= "<li><a href=\"msglist.php?page=1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">1</a></li>";
		}
		if ($min!=2) { $prevnext .= "<li>...</li>";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				$prevnext .= "<li><a href=\"msglist.php?page=$i&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\" aria-current=\"page\"><b>$i</b></a></li>";
			} else {
				$prevnext .= "<li><a href=\"msglist.php?page=$i&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">$i</a></li>";
			}
		}
		if ($max!=$numpages-1) { $prevnext .= "<li>...</li>";}
		if ($page == $numpages) {
			$prevnext .= "<li><a href=\"msglist.php?page=$numpages&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\" aria-current=\"page\"><b>$numpages</b></a></li>";
		} else {
			$prevnext .= "<li><a href=\"msglist.php?page=$numpages&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">$numpages</a></li>";
		}
		
		if ($page < $numpages) {
			$prevnext .= "<li><a href=\"msglist.php?page=".($page+1)."&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Next</a></li>";
		} else {
			$prevnext .= '<li>Next</li>';
		}
		$prevnext .= '</nav>';
		echo "<div>$prevnext</div>\n";
	}
	$address = $GLOBALS['basesiteurl'] . "/msgs/msglist.php?cid=$cid&filtercid=";


?>
<script type="text/javascript">
function chgfilter() {
	var filtercid = document.getElementById("filtercid").value;
	var filteruid = document.getElementById("filteruid").value;
	window.location = "<?php echo $address;?>"+filtercid+"&filteruid="+filteruid;
}
</script>
	<form id="qform" method=post action="msglist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
	<p><label for="filtercid">Filter by course</label>: <select id="filtercid" onchange="chgfilter()">
<?php
	echo "<option value=\"0\" ";
	if ($filtercid==0) {
		echo "selected=1 ";
	}
	echo ">All courses</option>";
	echo getCourseOpts(false, $filtercid);
	echo "</select> ";

	echo '<label for="filteruid">By sender</label>: <select id="filteruid" class="pii-full-name" onchange="chgfilter()"><option value="0" ';
	if ($filteruid==0) {
		echo 'selected="selected" ';
	}
	echo '>All</option>';
	$query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	$query .= "JOIN imas_msgs ON imas_msgs.msgfrom=imas_users.id WHERE imas_msgs.msgto=:msgto";
	if ($filtercid>0) {
    	$query .= " AND imas_msgs.courseid=:courseid";
  }
	$stm = $DBH->prepare($query);
  if ($filtercid>0) {
	  $stm->execute(array(':msgto'=>$userid, ':courseid'=>$filtercid));
  } else {
    $stm->execute(array(':msgto'=>$userid));
  }
	$senders = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$senders[$row[0]] = $row[1] . ', ' . $row[2];
	}
	asort($senders);
	foreach ($senders as $sid=>$sname) {
		echo "<option value=\"".Sanitize::onlyInt($sid)."\" ";
		if ($filteruid==$sid) {
			echo 'selected=1';
		}
		echo '>' . Sanitize::encodeStringForDisplay($sname) . '</option>';
	}
	echo "</select></p>";

?>
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	With Selected: <input type=submit name="unread" value="Mark as Unread">
	<input type=submit name="markread" value="Mark as Read">
	<input type=submit name="remove" value="Delete">


	<table class=gb id="myTable">
	<thead>
	<tr><th><span class="sr-only"><?php echo _('Select'); ?></span></th>
		<th><?php echo _('Message'); ?></th>
		<th><?php echo _('Replied'); ?></th>
		<th><span class="sr-only"><?php echo _('Image'); ?></span></th>
		<th><?php echo _('Flag'); ?></th>
		<th><?php echo _('From'); ?></th>
		<th><?php echo _('Course'); ?></th>
		<th><?php echo _('Sent'); ?></th></tr>
	</thead>
	<tbody>
<?php
  $offset = max(0, ($page-1)*$threadsperpage);

  $query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.viewed,imas_msgs.tagged,imas_courses.name,imas_msgs.msgfrom,imas_users.hasuserimg ";
	$query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE ";
	$query .= "imas_msgs.msgto=:msgto AND imas_msgs.deleted<2 ";
  $qarr = array(':msgto'=>$userid);
  if ($filteruid>0) {
		$query .= "AND imas_msgs.msgfrom=:msgfrom ";
    $qarr[':msgfrom']=$filteruid;
	}
	if ($filtercid>0) {
		$query .= "AND imas_msgs.courseid=:courseid ";
    $qarr[':courseid']=$filtercid;
	}
	if ($limittotagged) {
		$query .= "AND imas_msgs.tagged=1 ";
	}
	if ($limittonew) {
		$query .= "AND imas_msgs.viewed=0 ";
	}
	$query .= "ORDER BY senddate DESC ";
	if (!$limittotagged && !$limittonew) {
		$query .= "LIMIT $offset,$threadsperpage";// INT values
	}

  $stm = $DBH->prepare($query);
  $stm->execute($qarr);
	if ($stm->rowCount()==0) {
		echo "<tr><td></td><td>No messages</td><td></td></tr>";
	}
	$cnt = 0;
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		$n = 0;
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		$line['title'] = Sanitize::encodeStringForDisplay($line['title']);
		if ($n==1) {
			$line['title'] = 'Re: ' . $line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: " . $line['title'];
		}
		printf("<tr id=\"tr%d\" ", Sanitize::onlyInt($line['id']));
		$stripe = ($cnt%2==0)?'even':'odd';
		if ($line['tagged'] == 1) {
			echo 'class="tagged '.$stripe.'" ';
		} else {
			echo 'class="'.$stripe.'"';
		}
		echo "><td><input type=checkbox name=\"checked[]\" value=\"".Sanitize::onlyInt($line['id'])."\" id=\"cb".Sanitize::onlyInt($line['id'])."\"/></td><td>";
		echo '<label for="cb' . Sanitize::onlyInt($line['id']). '">';
        echo "<a href=\"viewmsg.php?page=$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&type=msg&msgid=".Sanitize::onlyInt($line['id'])."\">";
		if ($line['viewed']==0) {
			echo "<b>" . $line['title']. "</b>";
		} else {
			echo $line['title'];
		}
		echo "</a></label></td><td>";
		if ($line['replied']==1) {
			echo "Yes";
		}
		if ($line['LastName']==null) {
			if ($line['msgfrom']==0) {
				$line['fullname'] = _("[System Message]");
			} else {
				$line['fullname'] = _("[Deleted]");
			}
		} else {
			$line['fullname'] = sprintf('%s, %s', $line['LastName'], $line['FirstName']);
		}
		echo '</td><td>';

		if ($line['hasuserimg']==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo " <img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\"  class=\"userpic\"  alt=\"User picture\"/>";
			} else {
				echo " <img class=\"pii-image\" src=\"$imasroot/course/files/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\" class=\"userpic\"  alt=\"User picture\"/>";
			}
		}

		echo "</td><td>";
		echo '<button type=button class=plain onclick="toggletagged('.Sanitize::onlyInt($line['id']).');" role="switch" aria-checked="'.($line['tagged']==1?'true':'false').'" aria-label="'._('Tag post').'">';
		if ($line['tagged']==1) {
			echo "<img class=\"pointer\" id=\"tag".Sanitize::onlyInt($line['id'])."\" src=\"$staticroot/img/flagfilled.gif\" alt=\"\"/>";
		} else {
			echo "<img class=\"pointer\" id=\"tag".Sanitize::onlyInt($line['id'])."\" src=\"$staticroot/img/flagempty.gif\" alt=\"\"/>";
		}
		echo '</button>';
		echo '</td>';
		printf('<td><span class="pii-full-name">%s</span></td>', Sanitize::encodeStringForDisplay($line['fullname']));


		if ($line['name']==null) {
			$line['name'] = "[Deleted]";
		}
		echo "<td>".Sanitize::encodeStringForDisplay($line['name'])."</td>";
		$senddate = tzdate("F j, Y, g:i a",$line['senddate']);
		echo "<td>$senddate</td></tr>";

		$cnt++;
	}
?>
	</tbody>
	</table>
	</form>
<?php
	if ($prevnext != '') {
		echo "<p>$prevnext</p>";
	}
	if ($cansendmsgs) {
		echo "<p><button type=\"button\" onclick=\"window.location.href='msglist.php?page=$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&add=new'\">"._('Send New Message')."</button></p>";
	}

	echo '<p>&nbsp;</p>';
	require_once "../footer.php";
?>
