<?php
//IMathAS:  Add/modify gradebook items/grades
//(c) 2006 David Lippman
	//add/modify gbitem w/ grade edit
	//grade edit
	//single grade edit
	require("../init.php");
	require("../includes/htmlutil.php");
	require_once("../includes/TeacherAuditLog.php");

	$istutor = false;
	$isteacher = false;
	if (isset($tutorid)) { $istutor = true;}
    if (isset($teacherid)) { $isteacher = true;}
    
    if (isset($_GET['gbitem'])) {
        $gbItem = ($_GET['gbitem'] == 'new') ? 'new' : Sanitize::onlyInt($_GET['gbitem']);
    } else {
        $gbItem = '';
    }

	if ($istutor) {
		$isok = false;
		if (is_numeric($gbItem)) {
			$stm = $DBH->prepare("SELECT tutoredit FROM imas_gbitems WHERE id=:id");
			$stm->execute(array(':id'=>$gbItem));
			if ($stm->fetchColumn(0)==1) {
				$isok = true;
				$_GET['isolate'] = true;
			}
		}
		if (!$isok) {
			require("../header.php");
			echo "You don't have authority for this action";
			require("../footer.php");
			exit;
		}
	} else if (!$isteacher) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);
	$from = Sanitize::simpleString($_GET['from'] ?? '');

	if (isset($_GET['del']) && $isteacher) {
		$delItem = Sanitize::onlyInt($_GET['del']);
		if (isset($_POST['confirm'])) {
			$stm = $DBH->prepare("SELECT name FROM imas_gbitems WHERE id=:id");
			$stm->execute(array(':id'=>$delItem));
			$gbItemName = $stm->fetchColumn(0);
			$stm = $DBH->prepare("DELETE FROM imas_gbitems WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$delItem, ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				$stm = $DBH->prepare("SELECT userid,score FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid");
				$stm->execute(array(':gradetypeid'=>$delItem));
				$grades = $stm->fetchAll(PDO::FETCH_KEY_PAIR);
				TeacherAuditLog::addTracking(
            $cid,
            "Delete Item",
            $delItem,
            [
							'item_type'=>'Offline Grade Item',
							'item_name'=>$gbItemName,
							'grades' => $grades
            ]
        );
				$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid");
				$stm->execute(array(':gradetypeid'=>$delItem));
			}
			if ($from == 'gbtesting') {
				header(sprintf('Location: %s/course/gb-testing.php?stu=%s&cid=%s&r=' .Sanitize::randomQueryStringParam(), $GLOBALS['basesiteurl'],
					Sanitize::encodeUrlParam($_GET['stu']), $cid));
			} else {
				header(sprintf('Location: %s/course/gradebook.php?stu=%s&cid=%s&r=' .Sanitize::randomQueryStringParam(), $GLOBALS['basesiteurl'],
					Sanitize::encodeUrlParam($_GET['stu']), $cid));
			}
			exit;
		} else {
			require("../header.php");
			$stm = $DBH->prepare("SELECT name,courseid FROM imas_gbitems WHERE id=:id");
			$stm->execute(array(':id'=>$delItem));
			list($itemname,$itemcourseid) = $stm->fetch(PDO::FETCH_NUM);
			if ($itemcourseid != $cid) {
				echo "Invalid ID";
				exit;
			}

			echo "<p>Are you SURE you want to delete <strong>".Sanitize::encodeStringForDisplay($itemname);
			echo "</strong> and all associated grades from the gradebook?</p>";
			echo '<form method="POST" action="'.sprintf("addgrades.php?stu=%s&cid=%s&del=%s",
				Sanitize::encodeUrlParam($_GET['stu']), $cid, Sanitize::encodeUrlParam($delItem)).'">';
			echo '<p><button type=submit name=confirm value=true>'._('Delete Item').'</button>';

			printf(" <input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='addgrades.php?stu=%s&cid=%s&gbitem=%d&grades=all'\" />",
				Sanitize::encodeUrlParam($_GET['stu']), $cid, Sanitize::encodeUrlParam($_GET['del']));

			echo '</p></form>';
			require("../footer.php");
			exit;
		}

    }
    
	if ($gbItem != 'new') {
		$stm = $DBH->prepare("SELECT courseid FROM imas_gbitems WHERE id=?");
		$stm->execute(array($gbItem));
		if ($stm->rowCount()==0 || $stm->fetchColumn(0) != $cid) {
			echo "Invalid ID";
			exit;
		}
	}


	//get excusals
	$excused = array();
	if ($gbItem != 'new') {
		$stm = $DBH->prepare("SELECT userid FROM imas_excused WHERE type='O' AND typeid=:id");
		$stm->execute(array(':id'=>$gbItem));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$excused[$row[0]] = 1;
		}
	}

	if (isset($_POST['name']) && $isteacher) {
		require_once("../includes/parsedatetime.php");
		if ($_POST['sdatetype']=='0') {
			$showdate = 0;
		} else {
			$showdate = parsedatetime($_POST['sdate'],$_POST['stime'],0);
		}
		$tutoredit = intval($_POST['tutoredit']);
		$rubric = intval($_POST['rubric']);
		$outcomes = array();
		if (isset($_POST['outcomes'])) {
			foreach ($_POST['outcomes'] as $o) {
				if (is_numeric($o) && $o>0) {
					$outcomes[] = intval($o);
				}
			}
		}
		$outcomes = implode(',',$outcomes);
		$post_points = Sanitize::onlyFloat($_POST['points']);
		if ($gbItem=='new') {
			$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes) VALUES ";
			$query .= "(:courseid, :name, :points, :showdate, :gbcategory, :cntingb, :tutoredit, :rubric, :outcomes) ";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':name'=>$_POST['name'], ':points'=>$post_points, ':showdate'=>$showdate,
				':gbcategory'=>$_POST['gbcat'], ':cntingb'=>$_POST['cntingb'], ':tutoredit'=>$tutoredit, ':rubric'=>$rubric, ':outcomes'=>$outcomes));
			$gbItem = $DBH->lastInsertId();
			$isnewitem = true;
		} else {
			$query = "UPDATE imas_gbitems SET name=:name,points=:points,showdate=:showdate,gbcategory=:gbcategory,cntingb=:cntingb,";
			$query .= "tutoredit=:tutoredit,rubric=:rubric,outcomes=:outcomes WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':name'=>$_POST['name'], ':points'=>$post_points, ':showdate'=>$showdate, ':gbcategory'=>$_POST['gbcat'],
				':cntingb'=>$_POST['cntingb'], ':tutoredit'=>$tutoredit, ':rubric'=>$rubric, ':outcomes'=>$outcomes, ':id'=>$gbItem));
			$isnewitem = false;
		}
	}
	//check for grades marked as newscore that aren't really new
	//shouldn't happen, but could happen if two browser windows open
	if (isset($_POST['newscore'])) {
		$keys = array_keys($_POST['newscore']);
		foreach ($keys as $k=>$v) {
			if (trim($v)=='') {unset($keys[$k]);}
		}
		if (count($keys)>0) {
			$kl = implode(',',array_map('intval',$keys));
			$stm = $DBH->prepare("SELECT userid FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid AND userid IN ($kl)");
			$stm->execute(array(':gradetypeid'=>$gbItem));
			while($row = $stm->fetch(PDO::FETCH_NUM)) {
				$_POST['score'][$row[0]] = $_POST['newscore'][$row[0]];
				unset($_POST['newscore'][$row[0]]);
			}
		}
	}

	if (isset($_POST['assesssnap'])) {
		$assesssnapaid = Sanitize::onlyInt($_POST['assesssnapaid']);
        $post_points = Sanitize::onlyFloat($_POST['points']);
        
        $assesssnaptype = (int) Sanitize::onlyInt($_POST['assesssnaptype']);
        $assesssnapatt = (float) Sanitize::onlyFloat($_POST['assesssnapatt']);
        $assesssnappts = (float) Sanitize::onlyFloat($_POST['assesssnappts']);

		//doing assessment snapshot
		$stm = $DBH->prepare("SELECT ver FROM imas_assessments WHERE id=:assessmentid");
		$stm->execute(array(':assessmentid'=>$assesssnapaid));
		$aver = $stm->fetchColumn(0);
		if ($aver == 1) {
			$stm = $DBH->prepare("SELECT userid,bestscores FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
		} else {
			$stm = $DBH->prepare("SELECT userid,score,scoreddata FROM imas_assessment_records WHERE assessmentid=:assessmentid");
		}
		$stm->execute(array(':assessmentid'=>$assesssnapaid));
		while($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($aver == 1) {
				$sp = explode(';',$row[1]);
				$sc = explode(',',$sp[0]);
				$tot = 0;
				$att = 0;
				foreach ($sc as $v) {
					if (strpos($v,'-1')===false) {
						$att++;
					}
					$tot += getpts($v);
                }
                $attper = $att/count($sc);
			} else {
				$tot = $row[1];
				if ($assesssnaptype>0) {
                    $attper = 0;
                    $att = 0;
                    if ($row[2] !== '') {
                        $data = json_decode(gzdecode($row[2]), true);
                        if ($data !== false) {
                            $av = $data['assess_versions'][$data['scored_version']];
                            $qcnt = 0;
                            $attcnt = 0;
                            foreach ($av['questions'] as $q) {
                                $qcnt++;
                                $qver = $q['question_versions'][$q['scored_version']];
                                if (!empty($qver['tries'])) {
                                    $attcnt++;
                                }
                            }
                            if ($qcnt > 0) {
                                $attper = $attcnt/$qcnt;
                            }
                        }
                    }
				}
			}

			if ($assesssnaptype==0) {
				$score = $tot;
			} else {
				if ($attper>=$assesssnapatt/100-.001 && $tot>=$assesssnappts-.00001) {
					$score = $post_points;
				} else {
					$score = 0;
				}
            }

			$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
			$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
			$stm2 = $DBH->prepare($query);
			$stm2->execute(array(':gradetype'=>'offline', ':gradetypeid'=>$gbItem, ':userid'=>$row[0], ':score'=>$score, ':feedback'=>''));
		}
	} else {
		///regular submit
		$submittedexcusals = array();
		$submittedstu = array();
		if (isset($_POST['score'])) {

			foreach($_POST['score'] as $k=>$sc) {
				if (trim($k)=='') { continue;}
				$submittedstu[] = $k;
				$sc = trim($sc);
				if (strtolower($sc)=='x') {
					$submittedexcusals[] = $k;
					$sc = '';
				}
				$_POST['feedback'.$k] = Sanitize::incomingHtml(trim($_POST['feedback'.$k]));
				if ($_POST['feedback'.$k] == '<p></p>') {
					$_POST['feedback'.$k] = '';
				}
				if ($sc!='') {
					$stm = $DBH->prepare("UPDATE imas_grades SET score=:score,feedback=:feedback WHERE userid=:userid AND gradetype='offline' AND gradetypeid=:gradetypeid");
					$stm->execute(array(':score'=>$sc, ':feedback'=>$_POST['feedback'.$k], ':userid'=>$k, ':gradetypeid'=>$gbItem));
				} else {
					if ($_POST['feedback'.$k] == '') {
						$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid AND userid=:userid");
						$stm->execute(array(':userid'=>$k, ':gradetypeid'=>$gbItem));
					} else {
					$stm = $DBH->prepare("UPDATE imas_grades SET score=NULL,feedback=:feedback WHERE userid=:userid AND gradetype='offline' AND gradetypeid=:gradetypeid");
					$stm->execute(array(':feedback'=>$_POST['feedback'.$k], ':userid'=>$k, ':gradetypeid'=>$gbItem));
				}
			}
		}
		}

		if (isset($_POST['newscore'])) {
			foreach($_POST['newscore'] as $k=>$sc) {
				if (trim($k)=='') {continue;}
				$submittedstu[] = $k;
				$sc = trim($sc);
				if (strtolower($sc)=='x') {
					$submittedexcusals[] = $k;
					$sc = '';
				}
				$_POST['feedback'.$k] = Sanitize::incomingHtml(trim($_POST['feedback'.$k]));
				if ($_POST['feedback'.$k] == '<p></p>') {
					$_POST['feedback'.$k] = '';
				}
				if ($sc!='') {
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':gradetype'=>'offline', ':gradetypeid'=>$gbItem, ':userid'=>$k, ':score'=>$sc, ':feedback'=>$_POST['feedback'.$k]));
				} else if (trim($_POST['feedback'.$k])!='') {
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':gradetype'=>'offline', ':gradetypeid'=>$gbItem, ':userid'=>$k, ':score'=>NULL, ':feedback'=>$_POST['feedback'.$k]));
				}
			}
		}
		if (count($submittedexcusals)>0) {
			$vals = array();
			$now = time();
			foreach($submittedexcusals as $stu) {
				array_push($vals, $stu, $cid, 'O', $gbItem, $now);
	}
			if (count($vals)>0) {
				$ph = Sanitize::generateQueryPlaceholdersGrouped($vals, 5);
				$stm = $DBH->prepare("REPLACE INTO imas_excused (userid, courseid, type, typeid, dateset) VALUES $ph");
				$stm->execute($vals);
			}
		}
		//delete any excusals
		$todel = array_diff($submittedstu, $submittedexcusals);
		if (count($todel)>0) {
			$ph = Sanitize::generateQueryPlaceholders($todel);
			$delstm = $DBH->prepare("DELETE FROM imas_excused WHERE type='O' AND typeid=? AND userid IN ($ph)");
			array_unshift($todel, $gbItem);
			$delstm->execute($todel);
		}
	}
	if (isset($_POST['score']) || isset($_POST['newscore']) || isset($_POST['name'])) {
		if ($isnewitem && isset($_POST['doupload'])) {
			header(sprintf('Location: %s/course/uploadgrades.php?gbmode=%s&cid=%s&gbitem=%s&r=' .Sanitize::randomQueryStringParam(), $GLOBALS['basesiteurl'],
                Sanitize::encodeUrlParam($_GET['gbmode']), $cid, Sanitize::encodeUrlParam($gbItem)));
		} else if ($from == 'gbtesting') {
			header(sprintf('Location: %s/course/gb-testing.php?stu=%s&gbmode=%s&cid=%s&r=' .Sanitize::randomQueryStringParam(), $GLOBALS['basesiteurl'],
                Sanitize::encodeUrlParam($_GET['stu']), Sanitize::encodeUrlParam($_GET['gbmode']), $cid));
		} else {
			header(sprintf('Location: %s/course/gradebook.php?stu=%s&gbmode=%s&cid=%s&r=' .Sanitize::randomQueryStringParam(), $GLOBALS['basesiteurl'],
                Sanitize::encodeUrlParam($_GET['stu']), Sanitize::encodeUrlParam($_GET['gbmode']), $cid));
		}
		exit;
	}

	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else if (isset($_SESSION[$cid.'gbmode']) && !isset($_GET['refreshdef'])) {
		$gbmode =  $_SESSION[$cid.'gbmode'];
	} else {
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}
	$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked

	if (isset($tutorsection) && $tutorsection!='') {
		$secfilter = $tutorsection;
	} else {
		if (isset($_GET['secfilter'])) {
			$secfilter = $_GET['secfilter'];
			$_SESSION[$cid.'secfilter'] = $secfilter;
		} else if (isset($_SESSION[$cid.'secfilter'])) {
			$secfilter = $_SESSION[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}

	$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/addgrades.js?v=012722\"></script>";
	$placeinhead .= '<style type="text/css">
		 .suggestion_list
		 {
		 background: white;
		 border: 1px solid;
		 padding: 0px;
		 }

		 .suggestion_list ul
		 {
		 padding: 0;
		 margin: 0;
		 list-style-type: none;
		 }

		 .suggestion_list a
		 {
		 text-decoration: none;
		 color: navy;
		 padding: 5px;
		 }

		 .suggestion_list .selected
		 {
		 background: #99f;
		 }

		 tr#quickadd td {
			 border-bottom: 1px solid #000;
		 }


		 #autosuggest
		 {
		 display: none;
		 }
		 #gradeboxes .fbbox {
		 	min-width: 15em;
		 	min-height: 1em;
		 	margin: 0;
		 }
		 .fbbox p {
		 	padding: 1px;
		 }
		 .fbbox p + p {
		 	padding-top: .5em;
		 }
		 </style>';
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/rubric_min.js?v=022622"></script>';
	$useeditor = "noinit";
	if ($_SESSION['useed']!=0) {
		$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",null,true);</script>';
	}
	require("../includes/rubric.php");
	require("../header.php");
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
    if ($from == 'gbtesting') {
		echo " <a href=\"gb-testing.php?stu=0&cid=$cid\">Gradebook</a> ";
	} else {
		echo " <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	}
	if (isset($_GET['stu']) && $_GET['stu']>0) {
		echo "&gt; <a href=\"gradebook.php?stu=".Sanitize::encodeUrlParam($_GET['stu'])."&cid=$cid\">Student Detail</a> ";
	} else if (isset($_GET['stu']) && $_GET['stu']==-1) {
		echo "&gt; <a href=\"gradebook.php?stu=".Sanitize::encodeUrlParam($_GET['stu'])."&cid=$cid\">Averages</a> ";
	}
	echo "&gt; Offline Grades</div>";

	if ($gbItem=='new') {
		echo "<div id=\"headeraddgrades\" class=\"pagetitle\"><h1>Add Offline Grades</h1></div>";
	} else {
		echo "<div id=\"headeraddgrades\" class=\"pagetitle\"><h1>Modify Offline Grades</h1></div>";
	}

    printf("<form id=\"mainform\" method=post action=\"addgrades.php?stu=%s&gbmode=%s&cid=%s&gbitem=%s&grades=%s&from=%s\">",
        Sanitize::encodeUrlParam($_GET['stu'] ?? ''), Sanitize::encodeUrlParam($_GET['gbmode'] ?? ''), $cid,
        Sanitize::encodeUrlParam($gbItem), Sanitize::encodeUrlParam($_GET['grades']), $from);

	if ($_GET['grades']=='all') {
	    if (!isset($_GET['isolate'])) {
		if ($gbItem=='new') {
			$name = '';
			$points = 0;
			$showdate = time();
			$gbcat = 0;
			$cntingb = 1;
			$tutoredit = 0;
			$rubric = 0;
			$gradeoutcomes = array();
		} else {
			$stm = $DBH->prepare("SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes FROM imas_gbitems WHERE id=:id");
			$stm->execute(array(':id'=>$gbItem));
			list($name,$points,$showdate,$gbcat,$cntingb,$tutoredit,$rubric,$gradeoutcomes) = $stm->fetch(PDO::FETCH_NUM);
			if ($gradeoutcomes != '') {
				$gradeoutcomes = explode(',',$gradeoutcomes);
			} else {
				$gradeoutcomes = array();
			}
		}
		if ($showdate!=0) {
			$sdate = tzdate("m/d/Y",$showdate);
			$stime = tzdate("g:i a",$showdate);
		} else {
			$sdate = tzdate("m/d/Y",time()+60*60);
			$stime = tzdate("g:i a",time()+60*60);
		}
		$rubric_vals = array(0);
		$rubric_names = array('None');
		$stm = $DBH->prepare("SELECT id,name FROM imas_rubrics WHERE ownerid IN (SELECT userid FROM imas_teachers WHERE courseid=:cid) OR groupid=:groupid ORDER BY name");
		$stm->execute(array(':cid'=>$cid, ':groupid'=>$groupid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$rubric_vals[] = $row[0];
			$rubric_names[] = $row[1];
		}
		$outcomenames = array();
		$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$outcomenames[$row[0]] = $row[1];
		}
		$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row[0]=='') {
			$outcomearr = array();
		} else {
			$outcomearr = unserialize($row[0]);
			if ($outcomearr===false) {
				$outcomearr = array();
			}
		}

		$outcomes = array();
		function flattenarr($ar) {
			global $outcomes;
			foreach ($ar as $v) {
				if (is_array($v)) { //outcome group
					$outcomes[] = array($v['name'], 1);
					flattenarr($v['outcomes']);
				} else {
					$outcomes[] = array($v, 0);
				}
			}
		}
		flattenarr($outcomearr);


?>

<span class=form>Name:</span><span class=formright><input type=text name="name" value="<?php echo Sanitize::encodeStringForDisplay($name);?>"/></span><br class="form"/>

<span class=form>Points:</span><span class=formright><input type=text name="points" size=3 value="<?php echo Sanitize::encodeStringForDisplay($points);?>"/></span><br class="form"/>

<span class=form>Show grade to students after:</span><span class=formright><input type=radio name="sdatetype" value="0" <?php if ($showdate=='0') {echo "checked=1";}?>/> Always<br/>
<input type=radio name="sdatetype" value="sdate" <?php if ($showdate!='0') {echo "checked=1";}?>/><input type=text size=10 name=sdate value="<?php echo Sanitize::encodeStringForDisplay($sdate);?>">
<a href="#" onClick="displayDatePicker('sdate', this); return false"><img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=stime value="<?php echo Sanitize::encodeStringForDisplay($stime);?>"></span><BR class=form>

<?php
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		echo "<span class=form>Gradebook Category:</span><span class=formright><select name=gbcat id=gbcat>\n";
		echo "<option value=\"0\" ";
		if ($gbcat==0) {
			echo "selected=1 ";
		}
		echo ">Default</option>\n";
		if ($stm->rowCount()>0) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				printf('<option value="%d" ', Sanitize::encodeStringForDisplay($row[0]));
				if ($gbcat==$row[0]) {
					echo "selected=1 ";
				}
				printf(">%s</option>\n", Sanitize::encodeStringForDisplay($row[1]));
			}

		}
		echo "</select></span><br class=form>\n";

		echo "<span class=form>Count: </span><span class=formright>";
		echo '<input type=radio name="cntingb" value="1" ';
		if ($cntingb==1) { echo "checked=1";}
		echo ' /> Count in Gradebook<br/><input type=radio name="cntingb" value="0" ';
		if ($cntingb==0) { echo "checked=1";}
		echo ' /> Don\'t count in grade total and hide from students<br/><input type=radio name="cntingb" value="3" ';
		if ($cntingb==3) { echo "checked=1";}
		echo ' /> Don\'t count in grade total<br/><input type=radio name="cntingb" value="2" ';
		if ($cntingb==2) {echo "checked=1";}
		echo ' /> Count as Extra Credit</span><br class=form />';
		if (!isset($CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true) {
			$page_tutorSelect['label'] = array("No access","View Scores","View and Edit Scores");
			$page_tutorSelect['val'] = array(2,0,1);
			echo '<span class="form">Tutor Access:</span> <span class="formright">';
			writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$tutoredit);
			echo '</span><br class="form"/>';
		}

		echo '<span class=form>Use Scoring Rubric</span><span class=formright>';
		writeHtmlSelect('rubric',$rubric_vals,$rubric_names,$rubric);
		echo " <a href=\"addrubric.php?cid=$cid&amp;id=new&amp;from=addg&amp;gbitem=".Sanitize::encodeUrlParam($gbItem)."\">Add new rubric</a> ";
		echo "| <a href=\"addrubric.php?cid=$cid&amp;from=addg&amp;gbitem=".Sanitize::encodeUrlParam($gbItem)."\">Edit rubrics</a> ";
		echo '</span><br class="form"/>';

		if (count($outcomes)>0) {
			echo '<span class="form">Associate Outcomes:</span></span class="formright">';
			writeHtmlMultiSelect('outcomes',$outcomes,$outcomenames,$gradeoutcomes,'Select an outcome...');
			echo '</span><br class="form"/>';
		}

		if ($gbItem!='new') {
			printf("<br class=form /><div class=\"submit\"><input type=submit value=\"Submit\"/> <a href=\"addgrades.php?stu=%s&gbmode=%s&cid=%s&del=%s\">Delete Item</a> </div><br class=form />",
                Sanitize::encodeUrlParam($_GET['stu'] ?? ''), Sanitize::encodeUrlParam($_GET['gbmode'] ?? ''), $cid, Sanitize::encodeUrlParam($gbItem));
		} else {
			echo "<span class=form>Upload grades?</span><span class=formright><input type=checkbox name=\"doupload\" /> <input type=submit value=\"Submit\"/></span><br class=form />";
		}
		if ($gbItem=='new') {
			echo '<span class="form">Assessment snapshot?</span><span class="formright">';
			echo '<input type="checkbox" name="assesssnap" onclick="if(this.checked){this.nextSibling.style.display=\'\';document.getElementById(\'gradeboxes\').style.display=\'none\';}else{this.nextSibling.style.display=\'none\';document.getElementById(\'gradeboxes\').style.display=\'\';}"/>';
			echo '<span style="display:none;"><br/>Assessment to snapshot: <select name="assesssnapaid">';
			$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
			$stm->execute(array(':courseid'=>$cid));
			while($row = $stm->fetch(PDO::FETCH_NUM)) {
				printf('<option value="%d">%s</option>', Sanitize::encodeStringForDisplay($row[0]),
                    Sanitize::encodeStringForDisplay($row[1]));
			}
			echo '<select><br/>';
			echo 'Grade type:<br/> <input type="radio" name="assesssnaptype" value="0" checked="checked">Current score ';
			echo '<br/><input type="radio" name="assesssnaptype" value="1">Participation: give full credit if &ge; ';
			echo '<input type="text" name="assesssnapatt" value="100" size="3">% of problems attempted and &ge; ';
			echo '<input type="text" name="assesssnappts" value="0" size="3"> points earned.';
			echo '<br/><input type=submit value="Submit"/></span></span><br class="form" />';
		}
	    } else {
		$stm = $DBH->prepare("SELECT name,rubric,points FROM imas_gbitems WHERE id=:id");
		$stm->execute(array(':id'=>$gbItem));
		list($rubname, $rubric, $points) = $stm->fetch(PDO::FETCH_NUM);
		echo '<h2>'.Sanitize::encodeStringForDisplay($rubname).'</h2>';
	    }
	} else {
		$stm = $DBH->prepare("SELECT name,rubric,points FROM imas_gbitems WHERE id=:id");
		$stm->execute(array(':id'=>$gbItem));
		list($rubname, $rubric, $points) = $stm->fetch(PDO::FETCH_NUM);
		echo '<h2>'.Sanitize::encodeStringForDisplay($rubname).'</h2>';
	}
	if ($rubric != 0) {
		$stm = $DBH->prepare("SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
		$stm->execute(array(':id'=>$rubric));
		if ($stm->rowCount()>0) {
			echo printrubrics(array($stm->fetch(PDO::FETCH_NUM)));
		}
	}
?>

<?php
		/*$query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
		$query .= "AND imas_students.courseid=:courseid AND imas_students.section IS NOT NULL";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid));
		if ($stm->fetchColumn(0)>0) {
			$hassection = true;
		} else {
			$hassection = false;
		}
		*/
		$stm = $DBH->prepare("SELECT COUNT(DISTINCT section), COUNT(DISTINCT code) FROM imas_students WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$seccodecnt = $stm->fetch(PDO::FETCH_NUM);
		$hassection = ($seccodecnt[0]>0);
		$hascodes = ($seccodecnt[1]>0);

		if ($hassection) {
			$stm = $DBH->prepare("SELECT usersort FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			if ($stm->fetchColumn(0)==0) {
				$sortorder = "sec";
			} else {
				$sortorder = "name";
			}
		} else {
			$sortorder = "name";
		}

		if ($_GET['grades']=='all' && $gbItem!='new' && $isteacher) {
			printf("<p><a href=\"uploadgrades.php?gbmode=%s&cid=%s&gbitem=%s\">Upload Grades</a></p>",
                Sanitize::encodeUrlParam($_GET['gbmode'] ?? ''), $cid, Sanitize::encodeUrlParam($gbItem));
		}
		/*
		if ($hassection && ($_GET['gbitem']=='new' || $_GET['grades']=='all')) {
			if ($sortorder=="name") {
				echo "<p>Sorted by name.  <a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades={$_GET['grades']}&sortorder=sec\">";
				echo "Sort by section</a>.</p>";
			} else if ($sortorder=="sec") {
				echo "<p>Sorted by section.  <a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades={$_GET['grades']}&sortorder=name\">";
				echo "Sort by name</a>.</p>";
			}
		}
		*/
		echo '<div id="gradeboxes">';
		if ($_SESSION['useed']==0) {
			echo '<input type=button value="Expand Feedback Boxes" onClick="togglefeedback(this)"/> ';
		}
		if ($hassection) {
			echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";
		}
		if ($_GET['grades']=='all') {
			echo '<button type="button" id="useqa" onclick="togglequickadd(this)">'._("Use Quicksearch Entry").'</button>';
			echo "<br/><span class=form>Add/Replace to all grades:</span><span class=formright><input type=text size=3 id=\"toallgrade\" onblur=\"this.value = doonblur(this.value);\"/>";
			echo ' <input type=button value="Add" onClick="sendtoall(0,0);"/> <input type=button value="Multiply" onclick="sendtoall(0,1)"/> <input type=button value="Replace" onclick="sendtoall(0,2)"/></span><br class="form"/>';
			echo "<span class=form>Add/Replace to all feedback:</span><span class=formright>";
			if ($_SESSION['useed']==0) {
				echo "<input type=text size=40 id=\"toallfeedback\" name=\"toallfeedback\"/>";
			} else {
				echo '<div class="fbbox" id="toallfeedback"></div>';
			}
			echo ' <input type=button value="Append" onClick="sendtoall(1,0);"/> <input type=button value="Prepend" onclick="sendtoall(1,1)"/> <input type=button value="Replace" onclick="sendtoall(1,2)"/></span><br class="form"/>';
		}
		echo '<div class="clear"></div>';
		echo "<table id=myTable><thead><tr><th>Name</th>";
		if ($hassection) {
			echo '<th>Section</th>';
		}
		if ($hascodes) {
			echo '<th>Code</th>';
		}
		echo "<th>Grade</th><th>Feedback</th><th></th></tr></thead><tbody>";
		echo '<tr id="quickadd" style="display:none;"><td><input type="text" id="qaname" /></td>';
		if ($hassection) {
			echo '<td></td>';
		}
		if ($hascodes) {
			echo '<td></td>';
		}
		echo '<td><input type="text" id="qascore" size="3" onblur="this.value = doonblur(this.value);" onkeydown="return qaonenter(event,this);" /></td>';
		if ($_SESSION['useed']==0) {
			echo '<td><textarea id="qafeedback" rows="1" cols="60"></textarea></td><td>';
		} else {
			echo '<td><div id="qafeedback" class="fbbox"></div></td><td>';
		}
		echo '<input type="button" value="Next" onclick="addsuggest()" /></td></tr>';
		if ($gbItem=="new") {
			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked,imas_students.code ";
			$query .= "FROM imas_users,imas_students WHERE ";
			$query .= "imas_users.id=imas_students.userid AND imas_students.courseid=:cid";
			$qarr = array(':cid'=>$cid);
		} else {
			$query2 = "SELECT userid,score,feedback FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid ";
			if ($_GET['grades']!='all') {
				$query2 .= "AND userid=:userid ";
			}
			$stm2 = $DBH->prepare($query2);
			if ($_GET['grades']!='all') {
				$stm2->execute(array(':gradetypeid'=>$gbItem, ':userid'=>$_GET['grades']));
			} else {
				$stm2->execute(array(':gradetypeid'=>$gbItem));
			}
            $score = [];
            $feedback = [];
			while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
				if (isset($excused[$row[0]])) {
					$score[$row[0]] = 'X';
				} else if ($row[1]!=null) {
					$score[$row[0]] = $row[1];
				} else {
					$score[$row[0]] = '';
				}
				$feedback[$row[0]] = $row[2];
			}

			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked,imas_students.code FROM imas_users,imas_students ";
			if ($_GET['grades']!='all') {
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id=:userid AND imas_students.courseid=:cid";
				$qarr = array(':userid'=>Sanitize::stripHtmlTags($_GET['grades']), ':cid'=>$cid);
			} else {
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid=:cid";
				$qarr = array(':cid'=>$cid);
			}
		}
		if ($secfilter != -1) {
			$query .= " AND imas_students.section=:section ";
			$qarr[':section']=$secfilter;
		}
		if ($hidelocked) {
			$query .= ' AND imas_students.locked=0 ';
		}
		if ($hassection && $sortorder=="sec") {
			 $query .= " ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			 $query .= " ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($row[4]>0) {
				if ($hidelocked) { continue; }
				echo '<tr><td style="text-decoration: line-through;">';
			} else {
				echo '<tr><td>';
			}
			printf("<span class='pii-full-name'>%s, %s</span>", Sanitize::encodeStringForDisplay($row[1]), Sanitize::encodeStringForDisplay($row[2]));
			echo '</td>';
			if ($hassection) {
				echo "<td>".Sanitize::encodeStringForDisplay($row[3])."</td>";
			}
			if ($hascodes) {
				if ($row[5]==null) {$row[5] = '';}
				echo "<td>".Sanitize::encodeStringForDisplay($row[5])."</td>";
			}
			if (isset($score[$row[0]])) {
				printf('<td><input type="text" size="3" autocomplete="off" name="score[%d]" id="score%d" value="%s',
                    Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[0]),
                    Sanitize::encodeStringForDisplay($score[$row[0]]));
			} else {
				printf('<td><input type="text" size="3" autocomplete="off" name="newscore[%d]" id="score%d" value="',
                    Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[0]));
                if (isset($excused[$row[0]])) {
                	echo 'X';
			}
			}
			echo "\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" pattern=\"x|X|\d*\.?\d*\"/>";
			if ($rubric != 0) {
				echo printrubriclink($rubric,$points,"score". Sanitize::onlyint($row[0]),"feedback". Sanitize::onlyint($row[0]));
			}
			echo "</td>";
			if ($_SESSION['useed']==0) {
				printf('<td><textarea cols=60 rows=1 id="feedback%d" name="feedback%d">%s</textarea></td>',
					Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[0]),
					Sanitize::encodeStringForDisplay($feedback[$row[0]]));
			} else {
				printf('<td><div class="fbbox" id="feedback%d">%s</div></td>',
					Sanitize::encodeStringForDisplay($row[0]),
					Sanitize::outgoingHtml($feedback[$row[0]] ?? ''));
			}
			echo '<td></td>';
			echo "</tr>";
		}

		echo "</tbody></table>";
		if ($hassection) {
			echo "<script> initSortTable('myTable',Array('S','S',false,false),false);</script>";
		}


?>
<div class=submit><input type=submit value="Submit"></div></div>
</form>
<p>To Excuse a grade, enter X in the grade column</p>

<?php
	$placeinfooter = '<div id="autosuggest"><ul></ul></div>';
	require("../footer.php");

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}
?>
