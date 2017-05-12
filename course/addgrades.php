<?php
//IMathAS:  Add/modify gradebook items/grades
//(c) 2006 David Lippman
	//add/modify gbitem w/ grade edit
	//grade edit
	//single grade edit
	require("../validate.php");
	require("../includes/htmlutil.php");
	

	$istutor = false;
	$isteacher = false;
	if (isset($tutorid)) { $istutor = true;}
	if (isset($teacherid)) { $isteacher = true;}

	if ($istutor) {
		$isok = false;
		if (is_numeric($_GET['gbitem'])) {
			//DB $query = "SELECT tutoredit FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_result($result,0,0)==1) {
			$stm = $DBH->prepare("SELECT tutoredit FROM imas_gbitems WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['gbitem']));
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


	if (isset($_GET['del']) && $isteacher) {
		if (isset($_GET['confirm'])) {
			//DB $query = "DELETE FROM imas_gbitems WHERE id='{$_GET['del']}'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_gbitems WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$_GET['del'], ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				//DB $query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid='{$_GET['del']}'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid");
				$stm->execute(array(':gradetypeid'=>$_GET['del']));
			}

			header('Location: ' . $urlmode  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=".Sanitize::courseId($_GET['cid']));
			exit;
		} else {
			require("../header.php");
			echo "<p>Are you SURE you want to delete this item and all associated grades from the gradebook?</p>";
			echo "<p><a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&del={$_GET['del']}&confirm=true\">Delete Item</a>";
			echo " <a href=\"gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid\">Nevermind</a>";
			require("../footer.php");
			exit;
		}

	}
	if (isset($_POST['name']) && $isteacher) {
		require_once("../includes/parsedatetime.php");
		if ($_POST['sdatetype']=='0') {
			$showdate = 0;
		} else {
			$showdate = parsedatetime($_POST['sdate'],$_POST['stime']);
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
		if ($_GET['gbitem']=='new') {
			//DB $query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes) VALUES ";
			//DB $query .= "('$cid','{$_POST['name']}','{$_POST['points']}',$showdate,'{$_POST['gbcat']}','{$_POST['cntingb']}',$tutoredit,$rubric,'$outcomes') ";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $_GET['gbitem'] = mysql_insert_id();
			$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes) VALUES ";
			$query .= "(:courseid, :name, :points, :showdate, :gbcategory, :cntingb, :tutoredit, :rubric, :outcomes) ";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':name'=>$_POST['name'], ':points'=>$_POST['points'], ':showdate'=>$showdate,
				':gbcategory'=>$_POST['gbcat'], ':cntingb'=>$_POST['cntingb'], ':tutoredit'=>$tutoredit, ':rubric'=>$rubric, ':outcomes'=>$outcomes));
			$_GET['gbitem'] = $DBH->lastInsertId();
			$isnewitem = true;
		} else {
			//DB $query = "UPDATE imas_gbitems SET name='{$_POST['name']}',points='{$_POST['points']}',showdate=$showdate,gbcategory='{$_POST['gbcat']}',cntingb='{$_POST['cntingb']}',tutoredit=$tutoredit,rubric=$rubric,outcomes='$outcomes' ";
			//DB $query .= "WHERE id='{$_GET['gbitem']}'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "UPDATE imas_gbitems SET name=:name,points=:points,showdate=:showdate,gbcategory=:gbcategory,cntingb=:cntingb,";
			$query .= "tutoredit=:tutoredit,rubric=:rubric,outcomes=:outcomes WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':name'=>$_POST['name'], ':points'=>$_POST['points'], ':showdate'=>$showdate, ':gbcategory'=>$_POST['gbcat'],
				':cntingb'=>$_POST['cntingb'], ':tutoredit'=>$tutoredit, ':rubric'=>$rubric, ':outcomes'=>$outcomes, ':id'=>$_GET['gbitem']));
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
			//DB $query = "SELECT userid FROM imas_grades WHERE gradetype='offline' AND gradetypeid='{$_GET['gbitem']}' AND userid IN ($kl)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT userid FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid AND userid IN ($kl)");
			$stm->execute(array(':gradetypeid'=>$_GET['gbitem']));
			while($row = $stm->fetch(PDO::FETCH_NUM)) {
				$_POST['score'][$row[0]] = $_POST['newscore'][$row[0]];
				unset($_POST['newscore'][$row[0]]);
			}

		}
	}

	if (isset($_POST['assesssnap'])) {
		//doing assessment snapshot
		//DB $query = "SELECT userid,bestscores FROM imas_assessment_sessions WHERE assessmentid='{$_POST['assesssnapaid']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT userid,bestscores FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
		$stm->execute(array(':assessmentid'=>$_POST['assesssnapaid']));
		while($row = $stm->fetch(PDO::FETCH_NUM)) {
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
			if ($_POST['assesssnaptype']==0) {
				$score = $tot;
			} else {
				$attper = $att/count($sc);
				if ($attper>=$_POST['assesssnapatt']/100-.001 && $tot>=$_POST['assesssnappts']-.00001) {
					$score = $_POST['points'];
				} else {
					$score = 0;
				}
			}
			//DB $query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
			//DB $query .= "('offline','{$_GET['gbitem']}','{$row[0]}','$score','')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
			$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
			$stm2 = $DBH->prepare($query);
			$stm2->execute(array(':gradetype'=>'offline', ':gradetypeid'=>$_GET['gbitem'], ':userid'=>$row[0], ':score'=>$score, ':feedback'=>''));
		}
	} else {
		///regular submit
		if (isset($_POST['score'])) {

			foreach($_POST['score'] as $k=>$sc) {
				if (trim($k)=='') { continue;}
				$sc = trim($sc);
				if ($sc!='') {
					//DB $query = "UPDATE imas_grades SET score='$sc',feedback='{$_POST['feedback'][$k]}' WHERE userid='$k' AND gradetype='offline' AND gradetypeid='{$_GET['gbitem']}'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_grades SET score=:score,feedback=:feedback WHERE userid=:userid AND gradetype='offline' AND gradetypeid=:gradetypeid");
					$stm->execute(array(':score'=>$sc, ':feedback'=>$_POST['feedback'][$k], ':userid'=>$k, ':gradetypeid'=>$_GET['gbitem']));
				} else {
					//DB $query = "UPDATE imas_grades SET score=NULL,feedback='{$_POST['feedback'][$k]}' WHERE userid='$k' AND gradetype='offline' AND gradetypeid='{$_GET['gbitem']}'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_grades SET score=NULL,feedback=:feedback WHERE userid=:userid AND gradetype='offline' AND gradetypeid=:gradetypeid");
					$stm->execute(array(':feedback'=>$_POST['feedback'][$k], ':userid'=>$k, ':gradetypeid'=>$_GET['gbitem']));
					//$query = "DELETE FROM imas_grades WHERE gbitemid='{$_GET['gbitem']}' AND userid='$k'";
				}
			}
		}

		if (isset($_POST['newscore'])) {
			foreach($_POST['newscore'] as $k=>$sc) {
				if (trim($k)=='') {continue;}
				if ($sc!='') {
					//DB $query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					//DB $query .= "('offline','{$_GET['gbitem']}','$k','$sc','{$_POST['feedback'][$k]}')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':gradetype'=>'offline', ':gradetypeid'=>$_GET['gbitem'], ':userid'=>$k, ':score'=>$sc, ':feedback'=>$_POST['feedback'][$k]));
				} else if (trim($_POST['feedback'][$k])!='') {
					//DB $query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					//DB $query .= "('offline','{$_GET['gbitem']}','$k',NULL,'{$_POST['feedback'][$k]}')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':gradetype'=>'offline', ':gradetypeid'=>$_GET['gbitem'], ':userid'=>$k, ':score'=>NULL, ':feedback'=>$_POST['feedback'][$k]));
				}
			}
		}
	}
	if (isset($_POST['score']) || isset($_POST['newscore']) || isset($_POST['name'])) {
		if ($isnewitem && isset($_POST['doupload'])) {
			header('Location: ' . $urlmode  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/uploadgrades.php?gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}");
		} else {
			header('Location: ' . $urlmode  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=".Sanitize::courseId($_GET['cid']));
		}
		exit;
	}

	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else if (isset($sessiondata[$cid.'gbmode']) && !isset($_GET['refreshdef'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $gbmode = mysql_result($result,0,0);
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
			$sessiondata[$cid.'secfilter'] = $secfilter;
			writesessiondata();
		} else if (isset($sessiondata[$cid.'secfilter'])) {
			$secfilter = $sessiondata[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}

	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addgrades.js?v=112016\"></script>";
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
		 </style>';
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=113016"></script>';
	require("../includes/rubric.php");
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	if ($_GET['stu']>0) {
		echo "&gt; <a href=\"gradebook.php?stu={$_GET['stu']}&cid=$cid\">Student Detail</a> ";
	} else if ($_GET['stu']==-1) {
		echo "&gt; <a href=\"gradebook.php?stu={$_GET['stu']}&cid=$cid\">Averages</a> ";
	}
	echo "&gt; Offline Grades</div>";

	if ($_GET['gbitem']=='new') {
		echo "<div id=\"headeraddgrades\" class=\"pagetitle\"><h2>Add Offline Grades</h2></div>";
	} else {
		echo "<div id=\"headeraddgrades\" class=\"pagetitle\"><h2>Modify Offline Grades</h2></div>";
	}

	echo "<form id=\"mainform\" method=post action=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades={$_GET['grades']}\">";

	if ($_GET['grades']=='all') {
	    if (!isset($_GET['isolate'])) {
		if ($_GET['gbitem']=='new') {
			$name = '';
			$points = 0;
			$showdate = time();
			$gbcat = 0;
			$cntingb = 1;
			$tutoredit = 0;
			$rubric = 0;
			$gradeoutcomes = array();
		} else {
			//DB $query = "SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB list($name,$points,$showdate,$gbcat,$cntingb,$tutoredit,$rubric,$gradeoutcomes) = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes FROM imas_gbitems WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['gbitem']));
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
		//DB $query = "SELECT id,name FROM imas_rubrics WHERE ownerid='$userid' OR groupid='$gropuid' ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name FROM imas_rubrics WHERE ownerid=:ownerid OR groupid=:groupid ORDER BY name");
		$stm->execute(array(':ownerid'=>$userid, ':groupid'=>$gropuid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$rubric_vals[] = $row[0];
			$rubric_names[] = $row[1];
		}
		$outcomenames = array();
		//DB $query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$outcomenames[$row[0]] = $row[1];
		}
		//DB $query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
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

<span class=form>Name:</span><span class=formright><input type=text name="name" value="<?php echo $name;?>"/></span><br class="form"/>

<span class=form>Points:</span><span class=formright><input type=text name="points" size=3 value="<?php echo $points;?>"/></span><br class="form"/>

<span class=form>Show grade to students after:</span><span class=formright><input type=radio name="sdatetype" value="0" <?php if ($showdate=='0') {echo "checked=1";}?>/> Always<br/>
<input type=radio name="sdatetype" value="sdate" <?php if ($showdate!='0') {echo "checked=1";}?>/><input type=text size=10 name=sdate value="<?php echo $sdate;?>">
<a href="#" onClick="displayDatePicker('sdate', this); return false"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span><BR class=form>

<?php
		//DB $query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		echo "<span class=form>Gradebook Category:</span><span class=formright><select name=gbcat id=gbcat>\n";
		echo "<option value=\"0\" ";
		if ($gbcat==0) {
			echo "selected=1 ";
		}
		echo ">Default</option>\n";
		//DB if (mysql_num_rows($result)>0) {
		if ($stm->rowCount()>0) {

			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				echo "<option value=\"{$row[0]}\" ";
				if ($gbcat==$row[0]) {
					echo "selected=1 ";
				}
				echo ">{$row[1]}</option>\n";
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
		echo " <a href=\"addrubric.php?cid=$cid&amp;id=new&amp;from=addg&amp;gbitem={$_GET['gbitem']}\">Add new rubric</a> ";
		echo "| <a href=\"addrubric.php?cid=$cid&amp;from=addg&amp;gbitem={$_GET['gbitem']}\">Edit rubrics</a> ";
		echo '</span><br class="form"/>';

		if (count($outcomes)>0) {
			echo '<span class="form">Associate Outcomes:</span></span class="formright">';
			writeHtmlMultiSelect('outcomes',$outcomes,$outcomenames,$gradeoutcomes,'Select an outcome...');
			echo '</span><br class="form"/>';
		}

		if ($_GET['gbitem']!='new') {
			echo "<br class=form /><div class=\"submit\"><input type=submit value=\"Submit\"/> <a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&del={$_GET['gbitem']}\">Delete Item</a> </div><br class=form />";
		} else {
			echo "<span class=form>Upload grades?</span><span class=formright><input type=checkbox name=\"doupload\" /> <input type=submit value=\"Submit\"/></span><br class=form />";
		}
		if ($_GET['gbitem']=='new') {
			echo '<span class="form">Assessment snapshot?</span><span class="formright">';
			echo '<input type="checkbox" name="assesssnap" onclick="if(this.checked){this.nextSibling.style.display=\'\';document.getElementById(\'gradeboxes\').style.display=\'none\';}else{this.nextSibling.style.display=\'none\';document.getElementById(\'gradeboxes\').style.display=\'\';}"/>';
			echo '<span style="display:none;"><br/>Assessment to snapshot: <select name="assesssnapaid">';
			//DB $query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
			$stm->execute(array(':courseid'=>$cid));
			while($row = $stm->fetch(PDO::FETCH_NUM)) {
				echo '<option value="'.$row[0].'">'.$row[1].'</option>';
			}
			echo '<select><br/>';
			echo 'Grade type:<br/> <input type="radio" name="assesssnaptype" value="0" checked="checked">Current score ';
			echo '<br/><input type="radio" name="assesssnaptype" value="1">Participation: give full credit if &ge; ';
			echo '<input type="text" name="assesssnapatt" value="100" size="3">% of problems attempted and &ge; ';
			echo '<input type="text" name="assesssnappts" value="0" size="3"> points earned.';
			echo '<br/><input type=submit value="Submit"/></span></span><br class="form" />';
		}
	    } else {
		//DB $query = "SELECT name,rubric,points FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT name,rubric,points FROM imas_gbitems WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['gbitem']));
		list($rubname, $rubric, $points) = $stm->fetch(PDO::FETCH_NUM);
		echo '<h3>'.$rubname.'</h3>';
		//DB $rubric = mysql_result($result,0,1);
		//DB $points = mysql_result($result,0,2);
	    }
	} else {
		//DB $query = "SELECT name,rubric,points FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT name,rubric,points FROM imas_gbitems WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['gbitem']));
		list($rubname, $rubric, $points) = $stm->fetch(PDO::FETCH_NUM);
		echo '<h3>'.$rubname.'</h3>';
		//DB $rubric = mysql_result($result,0,1);
		//DB $points = mysql_result($result,0,2);
	}
	if ($rubric != 0) {
		//DB $query = "SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id='$rubric'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
			//DB echo printrubrics(array(mysql_fetch_row($result)));
		$stm = $DBH->prepare("SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id=:id");
		$stm->execute(array(':id'=>$rubric));
		if ($stm->rowCount()>0) {
			echo printrubrics(array($stm->fetch(PDO::FETCH_NUM)));
		}
	}
?>

<?php
		//DB $query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
		//DB $query .= "AND imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_result($result,0,0)>0) {
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
			//DB $query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_result($result,0,0)==0) {
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

		if ($_GET['grades']=='all' && $_GET['gbitem']!='new' && $isteacher) {
			echo "<p><a href=\"uploadgrades.php?gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}\">Upload Grades</a></p>";
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
		echo '<input type=button value="Expand Feedback Boxes" onClick="togglefeedback(this)"/> ';
		echo '<button type="button" id="useqa" onclick="togglequickadd(this)">'._("Use Quicksearch Entry").'</button>';
		if ($hassection) {
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		}
		if ($_GET['grades']=='all') {
			echo "<br/><span class=form>Add/Replace to all grades:</span><span class=formright><input type=text size=3 id=\"toallgrade\" onblur=\"this.value = doonblur(this.value);\"/>";
			echo ' <input type=button value="Add" onClick="sendtoall(0,0);"/> <input type=button value="Multiply" onclick="sendtoall(0,1)"/> <input type=button value="Replace" onclick="sendtoall(0,2)"/></span><br class="form"/>';
			echo "<span class=form>Add/Replace to all feedback:</span><span class=formright><input type=text size=40 id=\"toallfeedback\"/>";
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
		echo "<th>Grade</th><th>Feedback</th></tr></thead><tbody>";
		echo '<tr id="quickadd" style="display:none;"><td><input type="text" id="qaname" /></td>';
		if ($hassection) {
			echo '<td></td>';
		}
		if ($hascodes) {
			echo '<td></td>';
		}
		echo '<td><input type="text" id="qascore" size="3" onblur="this.value = doonblur(this.value);" onkeydown="return qaonenter(event,this);" /></td>';
		echo '<td><textarea id="qafeedback" rows="1" cols="40"></textarea>';
		echo '<input type="button" value="Next" onfocus="addsuggest()" /></td></tr>';
		if ($_GET['gbitem']=="new") {
			//DB $query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked ";
			//DB $query .= "FROM imas_users,imas_students WHERE ";
			//DB $query .= "imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked,imas_students.code ";
			$query .= "FROM imas_users,imas_students WHERE ";
			$query .= "imas_users.id=imas_students.userid AND imas_students.courseid=:cid";
			$qarr = array(':cid'=>$cid);
		} else {
			//DB $query = "SELECT userid,score,feedback FROM imas_grades WHERE gradetype='offline' AND gradetypeid='{$_GET['gbitem']}' ";
			//DB if ($_GET['grades']!='all') {
				//DB $query .= "AND userid='{$_GET['grades']}' ";
			//DB }
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$query2 = "SELECT userid,score,feedback FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid ";
			if ($_GET['grades']!='all') {
				$query2 .= "AND userid=:userid ";
			}
			$stm2 = $DBH->prepare($query2);
			if ($_GET['grades']!='all') {
				$stm2->execute(array(':gradetypeid'=>$_GET['gbitem'], ':userid'=>$_GET['grades']));
			} else {
				$stm2->execute(array(':gradetypeid'=>$_GET['gbitem']));
			}

			while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
				if ($row[1]!=null) {
					$score[$row[0]] = $row[1];
				} else {
					$score[$row[0]] = '';
				}
				$feedback[$row[0]] = $row[2];
			}
			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked,imas_students.code FROM imas_users,imas_students ";
			if ($_GET['grades']!='all') {
				//DB $query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id='{$_GET['grades']}' AND imas_students.courseid='$cid'";
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id=:userid AND imas_students.courseid=:cid";
				$qarr = array(':userid'=>$_GET['grades'], ':cid'=>$cid);
			} else {
				//DB $query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid=:cid";
				$qarr = array(':cid'=>$cid);
			}
		}
		if ($secfilter != -1) {
			//DB $query .= " AND imas_students.section='$secfilter' ";
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
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);

		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($row[4]>0) {
				if ($hidelocked) { continue; }
				echo '<tr><td style="text-decoration: line-through;">';
			} else {
				echo '<tr><td>';
			}
			echo "{$row[1]}, {$row[2]}";
			echo '</td>';
			if ($hassection) {
				echo "<td>{$row[3]}</td>";
			}
			if ($hascodes) {
				if ($row[5]==null) {$row[5] = '';}
				echo "<td>{$row[5]}</td>";
			}
			if (isset($score[$row[0]])) {
				echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"score[{$row[0]}]\" id=\"score{$row[0]}\" value=\"";
				echo $score[$row[0]];
			} else {
				echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"newscore[{$row[0]}]\" id=\"score{$row[0]}\" value=\"";
			}
			echo "\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" />";
			if ($rubric != 0) {
				echo printrubriclink($rubric,$points,"score{$row[0]}","feedback{$row[0]}");
			}
			echo "</td>";
			echo "<td><textarea cols=60 rows=1 id=\"feedback{$row[0]}\" name=\"feedback[{$row[0]}]\">{$feedback[$row[0]]}</textarea></td>";
			echo "</tr>";
		}

		echo "</tbody></table>";
		if ($hassection) {
			echo "<script> initSortTable('myTable',Array('S','S',false,false),false);</script>";
		}


?>
<div class=submit><input type=submit value="Submit"></div></div>
</form>

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
