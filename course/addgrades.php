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
			$query = "SELECT tutoredit FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0)==1) {
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
	$cid = $_GET['cid'];
	
	if (isset($_GET['del']) && $isteacher) {
		if (isset($_GET['confirm'])) {
			$query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid='{$_GET['del']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_gbitems WHERE id='{$_GET['del']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid={$_GET['cid']}");
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
		require_once("parsedatetime.php");
		if ($_POST['sdatetype']=='0') {
			$showdate = 0;
		} else {
			$showdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		}
		$tutoredit = intval($_POST['tutoredit']);
		$rubric = intval($_POST['rubric']);
		$outcomes = array();
		foreach ($_POST['outcomes'] as $o) {
			if (is_numeric($o) && $o>0) {
				$outcomes[] = intval($o);
			}
		}
		$outcomes = implode(',',$outcomes);
		if ($_GET['gbitem']=='new') {
			$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes) VALUES ";
			$query .= "('$cid','{$_POST['name']}','{$_POST['points']}',$showdate,'{$_POST['gbcat']}','{$_POST['cntingb']}',$tutoredit,$rubric,'$outcomes') ";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$_GET['gbitem'] = mysql_insert_id();
			$isnewitem = true;
		} else {
			$query = "UPDATE imas_gbitems SET name='{$_POST['name']}',points='{$_POST['points']}',showdate=$showdate,gbcategory='{$_POST['gbcat']}',cntingb='{$_POST['cntingb']}',tutoredit=$tutoredit,rubric=$rubric,outcomes='$outcomes' ";
			$query .= "WHERE id='{$_GET['gbitem']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
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
			$kl = implode(',',$keys);
			$query = "SELECT userid FROM imas_grades WHERE gradetype='offline' AND gradetypeid='{$_GET['gbitem']}' AND userid IN ($kl)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while($row = mysql_fetch_row($result)) {
				$_POST['score'][$row[0]] = $_POST['newscore'][$row[0]];
				unset($_POST['newscore'][$row[0]]);
			}
			
		}
	}
	
	if (isset($_POST['assesssnap'])) {
		//doing assessment snapshot
		$query = "SELECT userid,bestscores FROM imas_assessment_sessions WHERE assessmentid='{$_POST['assesssnapaid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while($row = mysql_fetch_row($result)) {
			$sc = explode(',',$row[1]);
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
			$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
			$query .= "('offline','{$_GET['gbitem']}','{$row[0]}','$score','')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	} else {
		///regular submit
		if (isset($_POST['score'])) {
			
			foreach($_POST['score'] as $k=>$sc) {
				if (trim($k)=='') { continue;}
				$sc = trim($sc);
				if ($sc!='') {
					$query = "UPDATE imas_grades SET score='$sc',feedback='{$_POST['feedback'][$k]}' WHERE userid='$k' AND gradetype='offline' AND gradetypeid='{$_GET['gbitem']}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else {
					$query = "UPDATE imas_grades SET score=NULL,feedback='{$_POST['feedback'][$k]}' WHERE userid='$k' AND gradetype='offline' AND gradetypeid='{$_GET['gbitem']}'";
					//$query = "DELETE FROM imas_grades WHERE gbitemid='{$_GET['gbitem']}' AND userid='$k'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}
		}
		
		if (isset($_POST['newscore'])) {
			foreach($_POST['newscore'] as $k=>$sc) {
				if (trim($k)=='') {continue;}			
				if ($sc!='') {
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					$query .= "('offline','{$_GET['gbitem']}','$k','$sc','{$_POST['feedback'][$k]}')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else if (trim($_POST['feedback'][$k])!='') {
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
					$query .= "('offline','{$_GET['gbitem']}','$k',NULL,'{$_POST['feedback'][$k]}')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}
		}
	}
	if (isset($_POST['score']) || isset($_POST['newscore']) || isset($_POST['name'])) {
		if ($isnewitem && isset($_POST['doupload'])) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/uploadgrades.php?gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid={$_GET['cid']}");
		}
		exit;
	}
	
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addgrades.js?v=050310\"></script>";
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
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js"></script>';
	require("../includes/rubric.php");
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
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
			$query = "SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric,outcomes FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			list($name,$points,$showdate,$gbcat,$cntingb,$tutoredit,$rubric,$gradeoutcomes) = mysql_fetch_row($result);
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
		$query = "SELECT id,name FROM imas_rubrics WHERE ownerid='$userid' OR groupid='$gropuid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$rubric_vals[] = $row[0];
			$rubric_names[] = $row[1];
		}
		$query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$outcomenames = array();
		while ($row = mysql_fetch_row($result)) {
			$outcomenames[$row[0]] = $row[1];
		}
		$query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($row[0]=='') {
			$outcomearr = array();
		} else {
			$outcomearr = unserialize($row[0]);
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
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo "<span class=form>Gradebook Category:</span><span class=formright><select name=gbcat id=gbcat>\n";
		echo "<option value=\"0\" ";
		if ($gbcat==0) {
			echo "selected=1 ";
		}
		echo ">Default</option>\n";
		if (mysql_num_rows($result)>0) {
			
			while ($row = mysql_fetch_row($result)) {
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
			$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while($row = mysql_fetch_row($result)) {
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
		$query = "SELECT name,rubric,points FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<h3>'.mysql_result($result,0,0).'</h3>';
		$rubric = mysql_result($result,0,1);
		$points = mysql_result($result,0,2);
	    }
	} else {
		$query = "SELECT name,rubric,points FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<h3>'.mysql_result($result,0,0).'</h3>';
		$rubric = mysql_result($result,0,1);
		$points = mysql_result($result,0,2);
	}
	if ($rubric != 0) {
		$query = "SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id='$rubric'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_num_rows($result)>0) {
			echo printrubrics(array(mysql_fetch_row($result)));
		}
	}
?>

<?php
		$query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
		$query .= "AND imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			$hassection = true;
		} else {
			$hassection = false;
		}
		
		if ($hassection) {
			$query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0)==0) {
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
		echo '<input type=button value="Expand Feedback Boxes" onClick="togglefeedback(this)"/>';
		echo ' Use quicksearch entry? <input type="checkbox" id="useqa" onclick="togglequickadd(this)" />';
		if ($hassection) {
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		}
		if ($_GET['grades']=='all') {
			echo "<br/><span class=form>Add/Replace to all grades:</span><span class=formright><input type=text size=3 id=\"toallgrade\" onblur=\"this.value = doonblur(this.value);\"/>"; 
			echo ' <input type=button value="Add" onClick="sendtoall(0,0);"/> <input type=button value="Multiply" onclick="sendtoall(0,1)"/> <input type=button value="Replace" onclick="sendtoall(0,2)"/></span><br class="form"/>';
			echo "<span class=form>Add/Replace to all feedback:</span><span class=formright><input type=text size=40 id=\"toallfeedback\"/>";
			echo ' <input type=button value="Append" onClick="sendtoall(1,0);"/> <input type=button value="Prepend" onclick="sendtoall(1,1)"/> <input type=button value="Replace" onclick="sendtoall(1,2)"/></span><br class="form"/>';
		}
	
		echo "<table id=myTable><thead><tr><th>Name</th>";
		if ($hassection) {
			echo '<th>Section</th>';
		}
		echo "<th>Grade</th><th>Feedback</th></tr></thead><tbody>";
		echo '<tr id="quickadd" style="display:none;"><td><input type="text" id="qaname" /></td>';
		if ($hassection) {
			echo '<td></td>';
		}
		echo '<td><input type="text" id="qascore" size="3" onblur="this.value = doonblur(this.value);" onkeydown="return qaonenter(event,this);" /></td>';
		echo '<td><textarea id="qafeedback" rows="1" cols="40"></textarea>';
		echo '<input type="button" value="Next" onfocus="addsuggest()" /></td></tr>';
		if ($_GET['gbitem']=="new") {
			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked ";
			$query .= "FROM imas_users,imas_students WHERE ";
			$query .= "imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
		} else {
			$query = "SELECT userid,score,feedback FROM imas_grades WHERE gradetype='offline' AND gradetypeid='{$_GET['gbitem']}' ";
			if ($_GET['grades']!='all') {
				$query .= "AND userid='{$_GET['grades']}' ";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if ($row[1]!=null) {
					$score[$row[0]] = $row[1];
				} else {
					$score[$row[0]] = '';
				}
				$feedback[$row[0]] = $row[2];
			}
			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.locked FROM imas_users,imas_students ";
			if ($_GET['grades']!='all') {
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id='{$_GET['grades']}' AND imas_students.courseid='$cid'";
			} else {
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
			}
			if ($istutor && isset($tutorsection) && $tutorsection!='') {
				$query .= " AND imas_students.section='$tutorsection' ";
			}
		}
		if ($hassection && $sortorder=="sec") {
			 $query .= " ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			 $query .= " ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
		while ($row = mysql_fetch_row($result)) {
			if ($row[4]==1) {
				echo '<tr><td style="text-decoration: line-through;">';
			} else {
				echo '<tr><td>';
			}
			echo "{$row[1]}, {$row[2]}";
			echo '</td>';
			if ($hassection) {
				echo "<td>{$row[3]}</td>";
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
			echo "<td><textarea style=\"display:hidden;\" cols=40 rows=1 id=\"feedback{$row[0]}\" name=\"feedback[{$row[0]}]\">{$feedback[$row[0]]}</textarea></td>";
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


