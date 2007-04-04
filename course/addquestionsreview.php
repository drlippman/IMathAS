<?php
//IMathAS:  Add/remove/modify questions used in an assessment
//(c) 2006 David Lippman
	require("../validate.php");
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	if (isset($_GET['grp'])) { $sessiondata['groupopt'.$aid] = $_GET['grp']; writesessiondata();}
	
	if (isset($teacherid) && isset($_GET['from']) && isset($_GET['to'])) {
	   $from = $_GET['from'];
	   $to = $_GET['to'];
	   $grp = $_GET['grp'];
	   $query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
	   $itemlist = mysql_result($result,0,0);
	   $items = explode(",",$itemlist);
	   
	   $itemtomove = $items[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
	   array_splice($items,$from-1,1);
	   if ($grp==1) {
		   if ($from<$to) {$to = $to-1;}
		   $items[$to-1] = $items[$to-1].'~'.$itemtomove;
	   } else {
		   array_splice($items,$to-1,0,$itemtomove);
	   }
	   	   
	   $itemlist = implode(",",$items);
	   $query = "UPDATE imas_assessments SET itemorder='$itemlist' WHERE id='$aid'";
	   mysql_query($query) or die("Query failed : " . mysql_error()); 
	   header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestionsreview.php?cid=$cid&aid=$aid");
	   exit;
	}
	if (isset($teacherid) && isset($_GET['ungroup'])) {
		list($i,$j) = explode('-',$_GET['ungroup']);
		$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemlist = mysql_result($result,0,0);
		$items = explode(",",$itemlist);
		$sub = explode('~',$items[$i]);
		$itemtomove = $sub[$j];
		array_splice($sub,$j,1);
		$items[$i] = implode('~',$sub);
		array_splice($items,$i+1,0,$itemtomove);
		$itemlist = implode(',',$items);
		$query = "UPDATE imas_assessments SET itemorder='$itemlist' WHERE id='$aid'";
		mysql_query($query) or die("Query failed : " . mysql_error()); 
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestionsreview.php?cid=$cid&aid=$aid");
		exit;
	}
	if (isset($teacherid) && isset($_GET['addset'])) {
		if (!isset($_POST['nchecked'])) {
				echo "No questions selected.  <a href=\"addquestionsreview.php?cid=$cid&aid=$aid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
		$checked = $_POST['nchecked'];
		foreach ($checked as $qsetid) {
			$query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,questionsetid) ";
			$query .= "VALUES ('$aid',9999,9999,9999,'$qsetid');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$qids[] = mysql_insert_id();
		}
		//add to itemorder
		$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($row[0]=='') {
			$itemorder = implode(",",$qids);
		} else {
			$itemorder  = $row[0] . "," . implode(",",$qids);	
		}
	
		$query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestionsreview.php?cid=$cid&aid=$aid");
		exit;
	}
	if (isset($teacherid) && isset($_GET['removeset'])) {
		if (!isset($_POST['checked'])) {
			echo "No questions selected.  <a href=\"addquestions.php?cid=$cid&aid=$aid\">Go back</a>\n";
			require("../footer.php");
			exit;
		}
		$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemlist = mysql_result($result,0,0);
		$items = explode(",",$itemlist);
		
		foreach($_POST['checked'] as $qloc) {
			
			list($i,$j) = explode('-',$qloc);
			if (strpos($items[$i],'~')!==false) {
				$sub = explode('~',$items[$i]);
				$rem = $sub[$j];
				$sub[$j]=0;
				$items[$i] = implode('~',$sub);
			} else {
				$rem = $items[$i];
				$items[$i] = 0;
			}
			
			$query = "DELETE FROM imas_questions WHERE id='$rem'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			
		}
		//update itemorder
		$itemo = array();
		foreach($items as $k=>$v) {
			if (strpos($v,'~')!==false) {
				$sub = explode('~',$v);
				$subo = array();
				foreach($sub as $j=>$sb) {
					if ($sb>0) {
						$subo[] = $sb;
					}
				}
				if (count($subo)>1) {
					$itemo[] = implode('~',$subo);
				} else if (count($subo)==1) {
					$itemo[] = $subo[0];
				}
			} else {
				if ($v>0) {
					$itemo[] = $v;
				}
			}
		}
		$itemlist = implode(',',$itemo);
		$query = "UPDATE imas_assessments SET itemorder='$itemlist' WHERE id='$aid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if (isset($_GET['clearattempts'])) {
		if ($_GET['clearattempts']=="confirmed") {
			
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"addquestionsreview.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> &gt; Clear Attempts</div>\n";
			echo "Are you SURE you want to delete all attempts (grades) for this assessment?";
			echo "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addquestionsreview.php?cid=$cid&aid=$aid&clearattempts=confirmed'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='addquestionsreview.php?cid=$cid&aid=$aid'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestionsreview.php?cid=$cid&aid=$aid";
	
	$placeinhead = "<script type=\"text/javascript\">
		var previewqaddr = '$imasroot/course/testquestion.php?cid={$_GET['cid']}';
		var addqaddr = '$address';
		</script>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addquestions.js\"></script>";
	
	$pagetitle = "Add/Remove Questions";
	require("../header.php");
	
	if (!(isset($teacherid))) {
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	if (isset($_GET['remove'])) {
		$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemlist = mysql_result($result,0,0);
		$items = explode(",",$itemlist);
		list($i,$j) = explode('-',$_GET['remove']);
		if (strpos($items[$i],'~')!==false) {
			$sub = explode('~',$items[$i]);
			$rem = array_splice($sub,$j,1);
			$items[$i] = implode('~',$sub);
		} else {
			$rem = array_splice($items,$i,1);
		}
		$itemlist = implode(',',$items);
		
		$query = "DELETE FROM imas_questions WHERE id='{$rem[0]}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		
		//update itemorder
		
		$query = "UPDATE imas_assessments SET itemorder='$itemlist' WHERE id='$aid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; Add/Remove Questions</div>\n";
	
	$query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
	$query .= "ias.assessmentid='$aid' AND ias.userid=imas_students.userid AND imas_students.courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result) > 0) {
		echo "<h3>Warning</h3>\n";
		echo "<p>This assessment has already been taken.  Adding or removing questions, or changing a question's settings (point value, penalty, attempts) now will majorly mess things up. ";
		echo "If you want to make these changes, you should clear all existing assessment attempts</p> ";
		echo "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addquestionsreview.php?cid=$cid&aid=$aid&clearattempts=ask'\"></p>\n";
	}	
	
	//existing questions
	echo "<h2>Add/Remove Questions <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=addingquestionstoanassessment','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>";
	echo "<h3>Questions in Assessment - ";
	$query = "SELECT itemorder,name,defpoints FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$itemorder = mysql_result($result, 0,0);
	echo mysql_result($result,0,1);
	echo "</h3>\n";
	$ln = 1;
	$defpoints = mysql_result($result,0,2);
	if ($itemorder == '') {
		echo "<p>No Questions currently in assessment</p>\n";
	} else {
		if (isset($sessiondata['groupopt'.$aid])) {
			$grp = $sessiondata['groupopt'.$aid];
		} else {
			$grp = 0;
		}
		echo "<form id=\"curq\" method=post action=\"addquestionsreview.php?cid=$cid&aid=$aid&removeset=true\"  onsubmit=\"return confirm('Are you SURE you want to remove these questions?');\">\n";
		echo "Use select boxes to <select name=group id=group><option value=\"0\" ";
		if ($grp==0) { echo "SELECTED";}
		echo ">Rearrange questions</option>";
		echo "<option value=\"1\" ";
		if ($grp==1) { echo "SELECTED";}
		echo ">Group questions</option></select>\n";
		echo " With Selected: <input type=submit value=\"Remove\">";
		echo "<table cellpadding=5 class=gb>\n";
		echo "<thead><tr><th>Order</th><th>Description</th><th>Preview</th><th>Type</th><th>Points</th><th>Settings</th><th>Source</th><th>Template</th><th>Remove from Assessment</th></tr></thead><tbody>\n";
		$items = explode(",",$itemorder);
		$apointstot = 0;
		for ($i = 0; $i < count($items); $i++) {
			if (strpos($items[$i],'~')!==false) {
				$subs = explode('~',$items[$i]);
			} else {
				$subs[] = $items[$i];
			}
			$ms = generatemoveselect($i,count($items));
			for ($j=0;$j<count($subs);$j++) {
				$query = "SELECT imas_questions.questionsetid,imas_questionset.description,imas_questionset.userights,imas_questionset.ownerid,imas_questionset.qtype,imas_questions.points FROM imas_questions,imas_questionset ";
				$query .= "WHERE imas_questions.id='{$subs[$j]}' AND imas_questionset.id=imas_questions.questionsetid";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				if ($alt==0) {echo "<tr class=even>";} else {echo "<tr class=odd>";}
				echo "<td>";
				if ($j==0) { 
					echo $ms;
				} else {
					echo "<a href=\"addquestionsreview.php?ungroup=$i-$j&aid=$aid&cid=$cid\">Ungroup</a>";
				}
				echo "</td><td><input type=hidden name=\"curq[]\" id=\"qo$ln\" value=\"{$line['questionsetid']}\"/>{$line['description']}</td>";
				echo "<td><input type=button value=\"Preview\" onClick=\"previewq('curq',$ln,{$line['questionsetid']},false)\"/></td>\n";
				
				echo "<td>{$line['qtype']}</td>\n";
				if ($line['points']==9999) {
					echo "<td>$defpoints</th>";
					if ($j==0) {
						$apointstot += $defpoints;
					}
				} else {
					echo "<td>{$line['points']}</td>";
					if ($j==0) {
						$apointstot += $line['points'];
					}
				}
				echo "<td class=c><a href=\"modquestion.php?id={$subs[$j]}&aid=$aid&cid=$cid\">Change</a></td>\n";
				if ($line['userights']>2 || $line['ownerid']==$userid) {
					echo "<td class=c><a href=\"moddataset.php?id={$line['questionsetid']}&aid=$aid&cid=$cid\">Edit</a></td>\n";
				} else { echo "<td class=c><a href=\"viewsource.php?id={$line['questionsetid']}&aid=$aid&cid=$cid\">View</a></td>";}
				echo "<td class=c><a href=\"moddataset.php?id={$line['questionsetid']}&aid={$_GET['aid']}&cid={$_GET['cid']}&template=true\">Template</a></td>\n";
				echo "<td class=c><a href=\"addquestionsreview.php?remove=$i-$j&aid=$aid&cid=$cid\" onclick=\"return confirm('Are you sure you want to remove this question?')\">Remove</a> <input type=checkbox name=\"checked[]\" value=\"$i-$j\"/></td>\n";
				echo "</tr>\n";
				$ln++;
			}
			$alt = 1-$alt;
			unset($subs);
		}
		echo "</tbody></table>\n";
		echo "</form>";
	}
	echo "<p>Assessment points total: $apointstot</p>";
	
		echo "<p><input type=button value=\"Done\" onClick=\"window.location='course.php?cid=$cid'\"> or ";
	echo "<input type=button value=\"Categorize Questions\" onClick=\"window.location='categorize.php?cid=$cid&aid=$aid'\"> or ";
	echo "<input type=button value=\"Create Print Version\" onClick=\"window.location='printtest.php?cid=$cid&aid=$aid'\"></p>";
	

	//potential questions
	echo "<h3>Potential Questions</h3>\n";
	if (isset($_GET['clearassmt'])) {
		unset($sessiondata['aidstolist'.$aid]);
	}
	if (isset($_POST['achecked'])) {
		if (count($_POST['achecked'])==0) {
			echo "<p>No Assessments Selected.  Select at least one assessment.</p>";
		} else {
			$aidstolist = $_POST['achecked'];
			$sessiondata['aidstolist'.$aid] = $aidstolist;
			writesessiondata();
		}
	}
	if (isset($sessiondata['aidstolist'.$aid])) { //list questions
		echo "<form id=\"selq\" method=post action=\"addquestionsreview.php?cid=$cid&aid=$aid&addset=true\">\n";
		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		echo "<input type=button value=\"Select Assessments\" onClick=\"window.location='addquestionsreview.php?cid=$cid&aid=$aid&clearassmt=1'\">";
		echo " or <input type=button value=\"Select From Libraries\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\"> <br/>";
		
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
		echo "<input type=submit value=\"Add Selected (using defaults)\">\n";
		echo "<input type=button value=\"Preview Selected\" onclick=\"previewsel('selq')\" /> \n";
		
		echo "<table cellpadding=5 id=myTable class=gb>\n";
		echo "<thead><tr><th> </th><th>Description</th><th>Preview</th><th>Type</th><th>Times Used</th><th>Mine</th><th>Add</th><th>Source</th><th>Use as Template</th></tr></thead>\n";
		echo "<tbody>\n";
		$alt=0;
		$aidlist = "'".implode("','",addslashes_deep($sessiondata['aidstolist'.$aid]))."'";
		$query = "SELECT id,name,itemorder FROM imas_assessments WHERE id IN ($aidlist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$aidnames[$row[0]] = $row[1];
			$items = str_replace('~',',',$row[2]);
			if ($items=='') {
				$aiditems[$row[0]] = array();
			} else {
				$aiditems[$row[0]] = explode(',',$items);
			}
		}
		foreach ($sessiondata['aidstolist'.$aid] as $aidq) {
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo "<td></td><td><b>{$aidnames[$aidq]}</b></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
			$query = "SELECT imas_questions.id,imas_questionset.id,imas_questionset.description,imas_questionset.qtype,imas_questionset.ownerid,imas_questionset.userights FROM imas_questionset,imas_questions";
			$query .= " WHERE imas_questionset.id=imas_questions.questionsetid AND imas_questions.assessmentid='$aidq'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$qsetid[$row[0]] = $row[1];
				$descr[$row[0]] = $row[2];
				$qtypes[$row[0]] = $row[3];
				$owner[$row[0]] = $row[4];
				$userights[$row[0]] = $row[5];
				$query = "SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$row[1]}'";
				$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$times[$row[0]] = mysql_result($result2,0,0);
				
			}
			foreach($aiditems[$aidq] as $qid) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo "<td><input type=checkbox name='nchecked[]' id='qo$ln' value='{$qsetid[$qid]}'></td>\n";
				echo "<td>{$descr[$qid]}</td>";
				echo "<td><input type=button value=\"Preview\" onClick=\"previewq('selq',$ln,{$qsetid[$qid]},true)\"/></td>\n";
				
				echo "<td>{$qtypes[$qid]}</td>\n";
				echo "<td class=c>{$times[$qid]}</td>";
				if ($owner[$qid]==$userid) { echo "<td>Yes</td>";} else {echo "<td></td>";}
				echo "<td class=c><a href=\"modquestion.php?qsetid={$qsetid[$qid]}&aid=$aid&cid=$cid\">Add</a></td>\n";
				if ($userights[$qid]>2 || $owner[$qid]==$userid) {
					echo "<td class=c><a href=\"moddataset.php?id={$qsetid[$qid]}&aid=$aid&cid=$cid&frompot=1\">Edit</a></td>\n";
				} else { echo "<td class=c><a href=\"viewsource.php?id={$qsetid[$qid]}&aid=$aid&cid=$cid\">View</a></td>";}
				
				echo "<td class=c><a href=\"moddataset.php?id={$qsetid[$qid]}&aid=$aid&cid=$cid&template=true\">Template</a></td>\n";
				echo "</tr>\n";
				$ln++;
			}
		}
		echo "</tbody></table>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "initSortTable('myTable',Array(false,'S',false,'S','N','S',false,false,false),true);\n";
		echo "</script>\n";
		echo "</form>\n";
	} else {  //choose assessments
		echo "<h4>Choose assessments to take questions from</h4>";
		echo "<form method=post action=\"addquestionsreview.php?cid=$cid&aid=$aid\">\n";
		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'achecked[]', this.checked)\" checked=1>\n";
		echo "<input type=submit value=\"Use these Assessments\" /> or ";
		echo "<input type=button value=\"Select From Libraries\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\">";
		
		echo "<table cellpadding=5 id=myTable class=gb>\n";
		echo "<thead><tr><th></th><th>Assessment</th></tr></thead>\n";
		echo "<tbody>\n";
		$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' AND id<>'$aid' ORDER BY enddate,name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$alt=0;
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo "<td><input type=checkbox name='achecked[]' value='{$line['id']}' checked=1></td>\n";
			echo "<td>{$line['name']}</td></tr>\n";
		}
		echo "</tbody></table>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "initSortTable('myTable',Array(false,'S','S',false,false,false),true);\n";
		echo "</script>\n";
		echo "</form>\n";
	}
	
	require("../footer.php");
	function generatemoveselect($num,$count) {
		$num = $num+1;  //adjust indexing
		$html = "<select id=\"$num\" onchange=\"moveitem($num)\">\n";
		for ($i = 1; $i <= $count; $i++) {
			$html .= "<option value=\"$i\" ";
			if ($i==$num) { $html .= "SELECTED";}
			$html .= ">$i</option>\n";
		}
		$html .= "</select>\n";
		return $html;
	}
	
?>
