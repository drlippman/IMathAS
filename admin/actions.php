<?php
//IMathAS:  Admin actions
//(c) 2006 David Lippman
require("../validate.php");

switch($_GET['action']) {
	case "emulateuser":
		if ($myrights < 100 ) { break;}
		$be = $_REQUEST['uid'];
		$query = "UPDATE imas_sessions SET userid='$be' WHERE sessionid='$sessionid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "chgrights":  
		if ($myrights < 100 && $_POST['newrights']>75) {echo "You don't have the authority for this action"; break;}
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		
		$query = "UPDATE imas_users SET rights='{$_POST['newrights']}'";
		if ($myrights == 100) {
			$query .= ",groupid='{$_POST['group']}'";
		}
		$query .= " WHERE id='{$_GET['id']}'";
		if ($myrights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
		mysql_query($query) or die("Query failed : " . mysql_error());
		if ($myrights == 100) { //update library groupids
			$query = "UPDATE imas_libraries SET groupid='{$_POST['group']}' WHERE ownerid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		break;
	case "resetpwd":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		if (isset($_POST['newpw'])) {
			$md5pw = md5($_POST['newpw']);
		} else {
			$md5pw =md5("password");
		}
		$query = "UPDATE imas_users SET password='$md5pw' WHERE id='{$_GET['id']}'";
		if ($myrights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "deladmin":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_users WHERE id='{$_GET['id']}'";
		if ($myrights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_affected_rows()==0) { break;}
		$query = "DELETE FROM imas_students WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_teachers WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_assessment_sessions WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_exceptions WHERE userid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "DELETE FROM imas_msgs WHERE msgto='{$_GET['id']}' AND isread>1"; //delete msgs to user
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+2 WHERE msgto='{$_GET['id']}' AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "DELETE FROM imas_msgs WHERE msgfrom='{$_GET['id']}' AND isread>1"; //delete msgs from user
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+4 WHERE msgfrom='{$_GET['id']}' AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		//todo: delete user picture files
		//todo: delete user file uploads 
		require_once("../includes/filehandler.php");
		deletealluserfiles($_GET['id']);
		//todo: delete courses if any
		break;
	case "chgpwd":
		$query = "SELECT password FROM imas_users WHERE id = '$userid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
	
		if ((md5($_POST['oldpw'])==$line['password']) && ($_POST['newpw1'] == $_POST['newpw2'])) {
			$md5pw =md5($_POST['newpw1']);
			$query = "UPDATE imas_users SET password='$md5pw' WHERE id='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error()); 
		} else {
			echo "<HTML><body>Password change failed.  <A HREF=\"forms.php?action=chgpwd\">Try Again</a>\n";
			echo "</body></html>\n";
			exit;
		}
		break;
	case "newadmin":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		if ($myrights < 100 && $_POST['newrights']>75) { break;}
		$query = "SELECT id FROM imas_users WHERE SID = '{$_POST['adminname']}';";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($row != null) {
			echo "<html><body>Username is already used.\n";
			echo "<a href=\"forms.php?action=newadmin\">Try Again</a> or ";
			echo "<a href=\"forms.php?action=chgrights&id={$row[0]}\">Change rights for existing user</a></body></html>\n";
			exit;
		}
		
		$md5pw =md5("password");
		if ($myrights < 100) {
			$newgroup = $groupid;
		} else if ($myrights == 100) {
			$newgroup = $_POST['group'];
		}
		if (isset($CFG['GEN']['homelayout'])) {
			$homelayout = $CFG['GEN']['homelayout'];
		} else {
			$homelayout = '|0,1,2||0,1';
		}
		$query = "INSERT INTO imas_users (SID,password,FirstName,LastName,rights,email,groupid,homelayout) VALUES ('{$_POST['adminname']}','$md5pw','{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['newrights']}','{$_POST['email']}','$newgroup','$homelayout');";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "logout":
		$sessionid = session_id();
		$query = "DELETE FROM imas_sessions WHERE sessionid='$sessionid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
		break;
	case "modify":
	case "addcourse":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		
		if (isset($CFG['CPS']['theme']) && $CFG['CPS']['theme'][1]==0) {
			$theme = addslashes($CFG['CPS']['theme'][0]);
		} else {
			$theme = $_POST['theme'];
		}
		
		if (isset($CFG['CPS']['picicons']) && $CFG['CPS']['picicons'][1]==0) {
			$picicons = $CFG['CPS']['picicons'][0];
		} else {
			$picicons = $_POST['picicons'];
		}
		if (isset($CFG['CPS']['hideicons']) && $CFG['CPS']['hideicons'][1]==0) {
			$hideicons = $CFG['CPS']['hideicons'][0];
		} else {
			$hideicons = $_POST['HIassess'] + $_POST['HIinline'] + $_POST['HIlinked'] + $_POST['HIforum'] + $_POST['HIblock'];
		}
		
		if (isset($CFG['CPS']['unenroll']) && $CFG['CPS']['unenroll'][1]==0) {
			$unenroll = $CFG['CPS']['unenroll'][0];
		} else {
			$unenroll = $_POST['allowunenroll'] + $_POST['allowenroll'];
		}
		
		if (isset($CFG['CPS']['copyrights']) && $CFG['CPS']['copyrights'][1]==0) {
			$copyrights = $CFG['CPS']['copyrights'][0];
		} else {
			$copyrights = $_POST['copyrights'];
		}
		
		if (isset($CFG['CPS']['msgset']) && $CFG['CPS']['msgset'][1]==0) {
			$msgset = $CFG['CPS']['msgset'][0];
		} else {
			$msgset = $_POST['msgset'];
			if (isset($_POST['msgmonitor'])) {
				$msgset += 5;
			}
			if (isset($_POST['msgqtoinstr'])) {
				$msgset += 5*2;
			}
		}
		
		if (isset($CFG['CPS']['chatset']) && $CFG['CPS']['chatset'][1]==0) {
			$chatset = intval($CFG['CPS']['chatset'][0]);
		} else {
			if (isset($_POST['chatset'])) {
				$chatset = 1;
			} else {
				$chatset = 0;
			}
		}      
		
		if (isset($CFG['CPS']['deftime']) && $CFG['CPS']['deftime'][1]==0) {
			$deftime = $CFG['CPS']['deftime'][0];
		} else {
			preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$_POST['deftime'],$tmatches);
			if (count($tmatches)==0) {
				preg_match('/(\d+)\s*([a-zA-Z]+)/',$_POST['deftime'],$tmatches);
				$tmatches[3] = $tmatches[2];
				$tmatches[2] = 0;
			}
			$tmatches[1] = $tmatches[1]%12;
			if($tmatches[3]=="pm") {$tmatches[1]+=12; }
			$deftime = $tmatches[1]*60 + $tmatches[2];
		}
		
		if (isset($CFG['CPS']['deflatepass']) && $CFG['CPS']['deflatepass'][1]==0) {
			$deflatepass = $CFG['CPS']['deflatepass'][0];
		} else {
			$deflatepass = intval($_POST['deflatepass']);
		}
		
		if (isset($CFG['CPS']['showlatepass']) && $CFG['CPS']['showlatepass'][1]==0) {
			$showlatepass = intval($CFG['CPS']['showlatepass'][0]);
		} else {
			if (isset($_POST['showlatepass'])) {
				$showlatepass = 1;
			} else {
				$showlatepass = 0;
			}
		}
		
		if (isset($CFG['CPS']['topbar']) && $CFG['CPS']['topbar'][1]==0) {
			$topbar = $CFG['CPS']['topbar'][0];
		} else {
			$topbar = array();
			if (isset($_POST['stutopbar'])) {
				$topbar[0] = implode(',',$_POST['stutopbar']);
			} else {
				$topbar[0] = '';
			}
			if (isset($_POST['insttopbar'])) {
				$topbar[1] = implode(',',$_POST['insttopbar']);
			} else {
				$topbar[1] = '';
			}
			$topbar[2] = $_POST['topbarloc'];
		}
		$topbar = implode('|',$topbar);
		
		if (isset($CFG['CPS']['toolset']) && $CFG['CPS']['toolset'][1]==0) {
			$toolset = $CFG['CPS']['toolset'][0];
		} else {
			$toolset = 1*!isset($_POST['toolset-cal']) + 2*!isset($_POST['toolset-forum']) + 4*!isset($_POST['toolset-reord']);
		}
		
		if (isset($CFG['CPS']['cploc']) && $CFG['CPS']['cploc'][1]==0) {
			$cploc = $CFG['CPS']['cploc'][0];
		} else {
			$cploc = $_POST['cploc'] + $_POST['cplocstu'] + $_POST['cplocview'];
		} 
		
		$avail = 3 - $_POST['stuavail'] - $_POST['teachavail'];
		
		$istemplate = 0;
		if ($myrights==100) {
			if (isset($_POST['istemplate'])) {
				$istemplate += 1;
			}
			if (isset($_POST['isgrptemplate'])) {
				$istemplate += 2;
			}
			if (isset($_POST['isselfenroll'])) {
				$istemplate += 4;
			}
			if (isset($_POST['isguest'])) {
				$istemplate += 8;
			}
		}
		
		$_POST['ltisecret'] = trim($_POST['ltisecret']);
		
		if ($_GET['action']=='modify') {
			$query = "UPDATE imas_courses SET name='{$_POST['coursename']}',enrollkey='{$_POST['ekey']}',hideicons='$hideicons',available='$avail',lockaid='{$_POST['lockaid']}',picicons='$picicons',chatset=$chatset,showlatepass=$showlatepass,";
			$query .= "allowunenroll='$unenroll',copyrights='$copyrights',msgset='$msgset',toolset='$toolset',topbar='$topbar',cploc='$cploc',theme='$theme',ltisecret='{$_POST['ltisecret']}',istemplate=$istemplate,deftime='$deftime',deflatepass='$deflatepass' WHERE id='{$_GET['id']}'";
			if ($myrights<75) { $query .= " AND ownerid='$userid'";}
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			$blockcnt = 1;
			$itemorder = addslashes(serialize(array()));
			$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,toolset,chatset,showlatepass,itemorder,topbar,cploc,available,istemplate,deftime,deflatepass,theme,ltisecret,blockcnt) VALUES ";
			$query .= "('{$_POST['coursename']}','$userid','{$_POST['ekey']}','$hideicons','$picicons','$unenroll','$copyrights','$msgset',$toolset,$chatset,$showlatepass,'$itemorder','$topbar','$cploc','$avail',$istemplate,'$deftime','$deflatepass','$theme','{$_POST['ltisecret']}','$blockcnt');";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$cid = mysql_insert_id();
			//if ($myrights==40) {
				$query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('$userid','$cid')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			//}
			$useweights = intval(isset($CFG['GBS']['useweights'])?$CFG['GBS']['useweights']:0);
			$orderby = intval(isset($CFG['GBS']['orderby'])?$CFG['GBS']['orderby']:0);
			$defgbmode = intval(isset($CFG['GBS']['defgbmode'])?$CFG['GBS']['defgbmode']:21);
			$usersort = intval(isset($CFG['GBS']['usersort'])?$CFG['GBS']['usersort']:0);
			
			$query = "INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES ('$cid',$useweights,$orderby,$defgbmode,$usersort)";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			
			if (isset($CFG['CPS']['templateoncreate']) && isset($_POST['usetemplate']) && $_POST['usetemplate']>0) {
				mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
				$query = "SELECT useweights,orderby,defaultcat,defgbmode,stugbmode FROM imas_gbscheme WHERE courseid='{$_POST['usetemplate']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$row = mysql_fetch_row($result);
				$query = "UPDATE imas_gbscheme SET useweights='{$row[0]}',orderby='{$row[1]}',defaultcat='{$row[2]}',defgbmode='{$row[3]}',stugbmode='{$row[4]}' WHERE courseid='$cid'";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				
				$gbcats = array();
				$query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden FROM imas_gbcats WHERE courseid='{$_POST['usetemplate']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden) VALUES ";
					$frid = array_shift($row);
					$irow = "'".implode("','",addslashes_deep($row))."'";
					$query .= "('$cid',$irow)";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					$gbcats[$frid] = mysql_insert_id();
				}
				$copystickyposts = true;
				$query = "SELECT itemorder,ancestors,outcomes FROM imas_courses WHERE id='{$_POST['usetemplate']}'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				$r = mysql_fetch_row($result);
				$items = unserialize($r[0]);
				$ancestors = $r[1];
				$outcomesarr = $r[2];
				if ($ancestors=='') {
					$ancestors = intval($_POST['usetemplate']);
				} else {
					$ancestors = intval($_POST['usetemplate']).','.$ancestors;
				}
				$ancestors = addslashes($ancestors);
				$outcomes = array();
				
				$query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
				$query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
				$query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
				$query .= "imas_assessments.courseid='{$_POST['usetemplate']}' AND imas_questionset.replaceby>0";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$replacebyarr[$row[0]] = $row[1];  
				}
				
				if ($outcomesarr!='') {
					$query = "SELECT id,name,ancestors FROM imas_outcomes WHERE courseid='{$_POST['usetemplate']}'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						if ($row[2]=='') {
							$row[2] = $row[0];
						} else {
							$row[2] = $row[0].','.$row[2];
						}
						$row[1] = addslashes($row[1]);
						$query = "INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES ";
						$query .= "('$cid','{$row[1]}','{$row[2]}')";
						mysql_query($query) or die("Query failed :$query " . mysql_error());
						$outcomes[$row[0]] = mysql_insert_id();
					}
					function updateoutcomes(&$arr) {
						global $outcomes;
						foreach ($arr as $k=>$v) {
							if (is_array($v)) {
								updateoutcomes($arr[$k]['outcomes']);
							} else {
								$arr[$k] = $outcomes[$v];
							}
						}
					}
					$outcomesarr = unserialize($outcomesarr);
					updateoutcomes($outcomesarr);
					$newoutcomearr = addslashes(serialize($outcomesarr));
				} else {
					$newoutcomearr = '';
				}
				$removewithdrawn = true;
				$usereplaceby = "all";
				$newitems = array();
				require("../includes/copyiteminc.php");
				copyallsub($items,'0',$newitems,$gbcats);
				doaftercopy($_POST['usetemplate']);
				$itemorder = addslashes(serialize($newitems));
				$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt',ancestors='$ancestors',outcomes='$newoutcomearr' WHERE id='$cid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				copyrubrics();
				mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
			}
			
			require("../header.php");
			echo '<div class="breadcrumb">'.$breadcrumbbase.'<a href="admin.php">Admin</a> &gt; Course Creation Confirmation</div>';
			echo '<h2>Your course has been created!</h2>';
			echo '<p>For students to enroll in this course, you will need to provide them two things:<ol>';
			echo '<li>The course ID: <b>'.$cid.'</b></li>';
			if (trim($_POST['ekey'])=='') {
				echo '<li>Tell them to leave the enrollment key blank, since you didn\'t specify one.  The enrollment key acts like a course ';
				echo 'password to prevent random strangers from enrolling in your course.  If you want to set an enrollment key, ';
				echo '<a href="forms.php?action=modify&id='.$cid.'">modify your course settings</a></li>';
			} else {
				echo '<li>The enrollment key: <b>'.$_POST['ekey'].'</b></li>';
			}
			echo '</ol></p>';
			echo '<p>If you forget these later, you can find them by viewing your course settings.</p>';
			echo '<a href="../course/course.php?cid='.$cid.'">Enter the Course</a>';
			require("../footer.php");
			exit;
		}
		break;
	case "delete":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		if (isset($CFG['GEN']['doSafeCourseDelete']) && $CFG['GEN']['doSafeCourseDelete']==true) {
			$oktodel = false;
			if ($myrights < 75) {
				$query = "SELECT id FROM imas_courses WHERE id='{$_GET['id']}' AND ownerid='$userid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$oktodel = true;
				}
			} else if ($myrights == 75) {
				$query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$oktodel = true;
				}
			} else if ($myrights==100) {
				$oktodel = true;
			}
			if ($oktodel) {
				$query = "UPDATE imas_courses SET available=4 WHERE id='{$_GET['id']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
			}
			break;
		} else {
			$query = "DELETE FROM imas_courses WHERE id='{$_GET['id']}'";
			if ($myrights < 75) { $query .= " AND ownerid='$userid'";}
			if ($myrights == 75) {
				$query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$query = "DELETE FROM imas_courses WHERE id='{$_GET['id']}'";
				} else {
					break;
				}
			}
			mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_affected_rows()==0) { break;}
			
			$query = "SELECT id FROM imas_assessments WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			require_once("../includes/filehandler.php");
			while ($line = mysql_fetch_row($result)) {
				deleteallaidfiles($line[0]);
				$query = "DELETE FROM imas_questions WHERE assessmentid='{$line[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$line[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_exceptions WHERE assessmentid='{$line[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			$query = "DELETE FROM imas_assessments WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT id FROM imas_drillassess WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($line = mysql_fetch_row($result)) {
				$query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid='{$line[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "DELETE FROM imas_drillassess WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT id FROM imas_forums WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$query = "SELECT id FROM imas_forum_posts WHERE forumid='{$row[0]}' AND files<>''";
				$r2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
				while ($row = mysql_fetch_row($r2)) {
					deleteallpostfiles($row[0]);
				}
				/*$q2 = "SELECT id FROM imas_forum_threads WHERE forumid='{$row[0]}'";
				$r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
				while ($row2 = mysql_fetch_row($r2)) {
					$query = "DELETE FROM imas_forum_views WHERE threadid='{$row2[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				*/
				$query = "DELETE imas_forum_views FROM imas_forum_views JOIN ";
				$query .= "imas_forum_threads ON imas_forum_views.threadid=imas_forum_threads.id ";
				$query .= "WHERE imas_forum_threads.forumid='{$row[0]}'";
				
				$query = "DELETE FROM imas_forum_posts WHERE forumid='{$row[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				
				$query = "DELETE FROM imas_forum_threads WHERE forumid='{$row[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "DELETE FROM imas_forums WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT id FROM imas_wikis WHERE courseid='{$_GET['id']}'";
			$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($wid = mysql_fetch_row($r2)) {
				$query = "DELETE FROM imas_wiki_revisions WHERE wikiid=$wid";
			mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_wiki_views WHERE wikiid=$wid";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "DELETE FROM imas_wikis WHERE courseid='{$_GET['id']}'";
			
			//delete inline text files
			$query = "SELECT id FROM imas_inlinetext WHERE courseid='{$_GET['id']}'";
			$r3 = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($ilid = mysql_fetch_row($r3)) {
				$query = "SELECT filename FROM imas_instr_files WHERE itemid='{$ilid[0]}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
				while ($row = mysql_fetch_row($result)) {
					$safefn = addslashes($row[0]);
					$query = "SELECT id FROM imas_instr_files WHERE filename='$safefn'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($r2)==1) {
						//unlink($uploaddir . $row[0]);
						deletecoursefile($row[0]);
					}
				}
				$query = "DELETE FROM imas_instr_files WHERE itemid='{$ilid[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "DELETE FROM imas_inlinetext WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			//delete linked text files
			$query = "SELECT text FROM imas_linkedtext WHERE courseid='{$_GET['id']}' AND text LIKE 'file:%'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$safetext = addslashes($row[0]);
				$query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($r2)==1) { 
					//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
					$filename = substr($row[0],5);
					//unlink($uploaddir . $filename);
					deletecoursefile($filename);
				}
			}
			
			$query = "DELETE FROM imas_linkedtext WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_items WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_teachers WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_students WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_tutors WHERE courseid='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT id FROM imas_gbitems WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid={$row[0]}";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "DELETE FROM imas_gbitems WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_gbscheme WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_gbcats WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "DELETE FROM imas_calitems WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT id FROM imas_stugroupset WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$q2 = "SELECT id FROM imas_stugroups WHERE groupsetid='{$row[0]}'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($row2 = mysql_fetch_row($r2)) {
					$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid='{$row2[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				$query = "DELETE FROM imas_stugroups WHERE groupsetid='{$row[0]}'";
			}
			$query = "DELETE FROM imas_stugroupset WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "DELETE FROM imas_external_tools WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_content_track WHERE courseid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		}	
		break;
	case "remteacher":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$tids = array();
		if (isset($_GET['tid'])) {
			$tids = array($_GET['tid']);
		} else if (isset($_POST['tid'])) {
			$tids = $_POST['tid'];
			if (count($tids)==$_GET['tot']) {
				array_shift($tids);
			}
		}
		foreach ($tids as $tid) {
			if ($myrights < 100) {
				$query = "SELECT imas_teachers.id FROM imas_teachers,imas_users WHERE imas_teachers.id='$tid' AND imas_teachers.userid=imas_users.id AND imas_users.groupid='$groupid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$query = "DELETE FROM imas_teachers WHERE id='$tid'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else {
					//break;
				}
				
				//$query = "DELETE imas_teachers FROM imas_users,imas_teachers WHERE imas_teachers.id='{$_GET['tid']}' ";
				//$query .= "AND imas_teachers.userid=imas_users.id AND imas_users.groupid='$groupid'";
			} else {
				$query = "DELETE FROM imas_teachers WHERE id='$tid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forms.php?action=chgteachers&id={$_GET['cid']}");
		exit;
	case "addteacher":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		if ($myrights < 100) {
			$query = "SELECT imas_users.groupid FROM imas_users,imas_courses WHERE imas_courses.ownerid=imas_users.id AND imas_courses.id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0) != $groupid) { 
				break;
			}
		}
		$tids = array();
		if (isset($_GET['tid'])) {
			$tids = array($_GET['tid']);
		} else if (isset($_POST['atid'])) {
			$tids = $_POST['atid'];
		}
		$ins = array();
		foreach ($tids as $tid) {
			$ins[] = "('$tid','{$_GET['cid']}')";
		}
		if (count($ins)>0) {
			$query = "INSERT INTO imas_teachers (userid,courseid) VALUES ".implode(',',$ins);
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forms.php?action=chgteachers&id={$_GET['cid']}");
		exit;
	case "importmacros":
		if ($myrights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
		$uploaddir = rtrim(dirname("../config.php"), '/\\') .'/assessment/libs/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			if (strpos($uploadfile,'.php')!==FALSE) {
				$handle = fopen($uploadfile, "r");
				$atstart = true;
				if ($handle) {
					while (!feof($handle)) {
						$buffer = fgets($handle, 4096);
						if (strpos($buffer,"//")===0) {
							$comments .= substr($buffer,2) .  "<BR>";
						} else if (strpos($buffer,"function")===0) {
							$func = substr($buffer,9,strpos($buffer,"(")-9);
							if ($comments!='') {
								$outlines .= "<h3><a name=\"$func\">$func</a></h3>\n";
								$funcs[] = $func;
								$outlines .= $comments;
								$comments = '';
							}
						} else if ($atstart && trim($buffer)=='') {
							$startcomments = $comments;
							$atstart = false;
							$comments = '';
						} else {
							$comments = '';
						}
					}
				}
				fclose($handle);
				$lib = basename($uploadfile,".php");
				$outfile = fopen($uploaddir . $lib.".html", "w");
				fwrite($outfile,"<html><body>\n<h1>Macro Library $lib</h1>\n");
				fwrite($outfile,$startcomments);
				fwrite($outfile,"<ul>\n");
				foreach($funcs as $func) {
					fwrite($outfile,"<li><a href=\"#$func\">$func</a></li>\n");
				}
				fwrite($outfile,"</ul>\n");
				fwrite($outfile, $outlines);
				fclose($outfile);
			}
			break;
		} else {
			require("../header.php");
			echo "<p>Error uploading file!</p>\n";
			require("../footer.php");
			exit;
		}
	case "importqimages":
		if ($myrights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			if (strpos($uploadfile,'.tar.gz')!==FALSE) {
				include("../includes/tar.class.php");
				include("../includes/filehandler.php");
				$tar = new tar();
				$tar->openTAR($uploadfile);
				if ($tar->hasFiles()) {
					if ($GLOBALS['filehandertypecfiles'] == 's3') {
						$n = $tar->extractToS3("qimages","public");
					} else {
						$n = $tar->extractToDir("../assessment/qimages/");	
					}
					require("../header.php");
					echo "<p>Extracted $n files.  <a href=\"admin.php\">Continue</a></p>\n";
					require("../footer.php");
					exit;
				} else {
					require("../header.php");
					echo "<p>File appears to contain nothing</p>\n";
					require("../footer.php");
					exit;
				}
				
			}
			unlink($uploadfile);
			break;
		} else {
			require("../header.php");
			echo "<p>Error uploading file!</p>\n";
			require("../footer.php");
			exit;
		}
	case "importcoursefiles":
		if ($myrights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			if (strpos($uploadfile,'.zip')!==FALSE && class_exists('ZipArchive')) {
				require("../includes/filehandler.php");
				$zip = new ZipArchive();
				$res = $zip->open($uploadfile);
				$ne = 0;  $ns = 0;
				if ($res===true) {
					for($i = 0; $i < $zip->numFiles; $i++) {
						//if (file_exists("../course/files/".$zip->getNameIndex($i))) {
						if (doesfileexist('cfile',$zip->getNameIndex($i))) {
							$ns++;
						} else {
							$zip->extractTo("../course/files/", array($zip->getNameIndex($i)));
							relocatecoursefileifneeded("../course/files/".$zip->getNameIndex($i),$zip->getNameIndex($i));
							$ne++;
						} 
					}
					require("../header.php");
					echo "<p>Extracted $ne files.  Skipped $ns files.  <a href=\"admin.php\">Continue</a></p>\n";
					require("../footer.php");
					exit;
				} else {
					require("../header.php");
					echo "<p>File appears to contain nothing</p>\n";
					require("../footer.php");
					exit;
				}
				
			}
			unlink($uploadfile);
			break;
		} else {
			require("../header.php");
			echo "<p>Error uploading file!</p>\n";
			require("../footer.php");
			exit;
		}
	case "transfer":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		$exec = false;
		$query = "UPDATE imas_courses SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['id']}'";
		if ($myrights < 75) {
			$query .= " AND ownerid='$userid'";
		}
		if ($myrights==75) {
			$query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$query = "UPDATE imas_courses SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['id']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$exec = true;
			}
			//$query = "UPDATE imas_courses,imas_users SET imas_courses.ownerid='{$_POST['newowner']}' WHERE ";
			//$query .= "imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
		} else {
			mysql_query($query) or die("Query failed : " . mysql_error());
			$exec = true;
		}
		if ($exec && mysql_affected_rows()>0) {
			$query = "SELECT id FROM imas_teachers WHERE courseid='{$_GET['id']}' AND userid='{$_POST['newowner']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) {
				$query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('{$_POST['newowner']}','{$_GET['id']}')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			$query = "DELETE FROM imas_teachers WHERE courseid='{$_GET['id']}' AND userid='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
		break;
	case "deloldusers":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$old = time() - 60*60*24*30*$_POST['months'];
		$who = $_POST['who'];
		require_once("../includes/filehandler.php");
		if ($who=="students") {
			$query = "SELECT id FROM imas_users WHERE  lastaccess<$old AND (rights=0 OR rights=10)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$uid = $row[0];
				$query = "DELETE FROM imas_assessment_sessions WHERE userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_exceptions WHERE userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_grades WHERE userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_forum_views WHERE userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_students WHERE userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				//these could break parent structure for forums!
				//$query = "DELETE FROM imas_forum_posts WHERE forumid='{$row[0]}' AND posttype=0";
				//mysql_query($query) or die("Query failed : " . mysql_error());
				deletealluserfiles($uid);
			}
			$query = "DELETE FROM imas_users WHERE lastaccess<$old AND (rights=0 OR rights=10)";
		} else if ($who=="all") {
			$query = "DELETE FROM imas_users WHERE lastaccess<$old AND rights<100";
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "addgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$query = "SELECT id FROM imas_groups WHERE name='{$_POST['gpname']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			echo "<html><body>Group name already exists.  <a href=\"forms.php?action=listgroups\">Try again</a></body></html>\n";
			exit;
		}
		$query = "INSERT INTO imas_groups (name) VALUES ('{$_POST['gpname']}')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "modgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$query = "SELECT id FROM imas_groups WHERE name='{$_POST['gpname']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			echo "<html><body>Group name already exists.  <a href=\"forms.php?action=modgroup&id={$_GET['id']}\">Try again</a></body></html>\n";
			exit;
		}
		$query = "UPDATE imas_groups SET name='{$_POST['gpname']}' WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "delgroup":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_groups WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "UPDATE imas_users SET groupid=0 WHERE groupid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "UPDATE imas_libraries SET groupid=0 WHERE groupid='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "modltidomaincred":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		if ($_GET['id']=='new') {
			$query = "INSERT INTO imas_users (email,FirstName,LastName,SID,password,rights,groupid) VALUES ";
			$query .= "('{$_POST['ltidomain']}','{$_POST['ltidomain']}','LTIcredential','{$_POST['ltikey']}',";
			$query .= "'{$_POST['ltisecret']}','{$_POST['createinstr']}','{$_POST['groupid']}')";
		} else {
			$query = "UPDATE imas_users SET email='{$_POST['ltidomain']}',FirstName='{$_POST['ltidomain']}',LastName='LTIcredential',";
			$query .= "SID='{$_POST['ltikey']}',password='{$_POST['ltisecret']}',";
			$query .= "rights='{$_POST['createinstr']}',groupid='{$_POST['groupid']}' WHERE id='{$_GET['id']}'";
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "delltidomaincred":
		if ($myrights <100) { echo "You don't have the authority for this action"; break;}
		$query = "DELETE FROM imas_users WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		break;
	case "removediag";
		if ($myrights <60) { echo "You don't have the authority for this action"; break;}
		$query = "SELECT imas_users.id,imas_users.groupid FROM imas_users JOIN imas_diags ON imas_users.id=imas_diags.ownerid AND imas_diags.id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if (($myrights<75 && $row[0]==$userid) || ($myrights==75 && $row[1]==$groupid) || $myrights==100) { 
			$query = "DELETE FROM imas_diags WHERE id='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_diag_onetime WHERE diag='{$_GET['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		break;
}

session_write_close();
if (isset($_GET['cid'])) {
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid={$_GET['cid']}");
} else {
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php");
}
exit;
?>

