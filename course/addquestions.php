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
	   header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
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
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
		exit;
	}
	if (isset($teacherid) && isset($_GET['addset'])) {
		if (!isset($_POST['nchecked'])) {
				echo "No questions selected.  <a href=\"addquestions.php?cid=$cid&aid=$aid\">Go back</a>\n";
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
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
		exit;
	}
	if (isset($_GET['clearattempts'])) {
		if ($_GET['clearattempts']=="confirmed") {
			
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> &gt; Clear Attempts</div>\n";
			echo "Are you SURE you want to delete all attempts (grades) for this assessment?";
			echo "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearattempts=confirmed'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	if (isset($_GET['clearqattempts'])) {
		if (isset($_GET['confirmed'])) {
			$clearid = $_GET['clearqattempts'];
			if ($clearid!=='' && is_numeric($clearid)) {
				$query = "SELECT id,questions,scores,attempts,lastanswers,bestscores,bestattempts,bestlastanswers ";
				$query .= "FROM imas_assessment_sessions WHERE assessmentid='$aid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$questions = explode(',',$line['questions']);
					$qloc = array_search($clearid,$questions);
					if ($qloc!==false) {
						$scores = explode(',',$line['scores']);
						$attempts = explode(',',$line['attempts']);
						$lastanswers = explode('~',$line['lastanswers']);
						$bestscores = explode(',',$line['bestscores']);
						$bestattempts = explode(',',$line['bestattempts']);
						$bestlastanswers = explode('~',$line['bestlastanswers']);
						
						$scores[$qloc] = -1;
						$attempts[$qloc] = 0;
						$lastanswers[$qloc] = '';
						$bestscores[$qloc] = -1;
						$bestattempts[$qloc] = 0;
						$bestlastanswers[$qloc] = '';
						
						$scorelist = implode(',',$scores);
						$attemptslist = implode(',',$attempts);
						$lalist = addslashes(implode('~',$lastanswers));
						$bestscorelist = implode(',',$scores);
						$bestattemptslist = implode(',',$attempts);
						$bestlalist = addslashes(implode('~',$lastanswers));
						
						$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',";
						$query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestlastanswers='$bestlalist' ";
						$query .= "WHERE id='{$line['id']}'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					} 
				}
			} else {
				echo "<p>Error with question id.  Try again.</p>";
			}
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> &gt; Clear Attempts</div>\n";
			echo "<p>Are you SURE you want to delete all attempts (grades) for this question?</p>";
			echo "<p>This will allow you to safely change points and penalty for a question, or give students another attempt ";
			echo "on a question that needed fixing.  This will NOT allow you to remove the question from the assessment.</p>";
			echo "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearqattempts={$_GET['clearqattempts']}&confirmed=1'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid";
	
	$placeinhead = "<script type=\"text/javascript\">
		var previewqaddr = '$imasroot/course/testquestion.php?cid=$cid';
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
		echo "<p>This assessment has already been taken.  Adding or removing questions, or changing a question's settings (point value, penalty, attempts) now would majorly mess things up. ";
		echo "If you want to make these changes, you need to clear all existing assessment attempts</p> ";
		echo "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearattempts=ask'\"></p>\n";
		$beentaken = true;
	} else {
		$beentaken = false;
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
		echo "<form id=\"curq\">";
		if (!$beentaken) {
			echo "Use select boxes to <select name=group id=group><option value=\"0\" ";
			if ($grp==0) { echo "SELECTED";}
			echo ">Rearrange questions</option>";
			echo "<option value=\"1\" ";
			if ($grp==1) { echo "SELECTED";}
			echo ">Group questions</option</select>\n";
		}
		echo "<table cellpadding=5 class=gb>\n";
		echo "<thead><tr>";
		echo "<th>Order</th><th>Description</th><th>Preview</th><th>Type</th><th>Points</th><th>Settings</th><th>Source</th>";
		if (!$beentaken) {
			echo "<th>Template</th><th>Remove from Assessment</th>";
		} else {
			echo "<th>Clear Attempts</th>";
		}
		echo "</tr></thead><tbody>\n";
		$items = explode(",",$itemorder);
		$apointstot = 0;
		for ($i = 0; $i < count($items); $i++) {
			if (strpos($items[$i],'~')!==false) {
				$subs = explode('~',$items[$i]);
			} else {
				$subs[] = $items[$i];
			}
			if (!$beentaken) {
				$ms = generatemoveselect($i,count($items));
			}
			for ($j=0;$j<count($subs);$j++) {
				$query = "SELECT imas_questions.questionsetid,imas_questionset.description,imas_questionset.userights,imas_questionset.ownerid,imas_questionset.qtype,imas_questions.points FROM imas_questions,imas_questionset ";
				$query .= "WHERE imas_questions.id='{$subs[$j]}' AND imas_questionset.id=imas_questions.questionsetid";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				if ($alt==0) {echo "<tr class=even>";} else {echo "<tr class=odd>";}
				if (!$beentaken) {
					echo "<td>";
					if ($j==0) { 
						echo $ms;
					} else {
						echo "<a href=\"addquestions.php?ungroup=$i-$j&aid=$aid&cid=$cid\">Ungroup</a>";
					}
					echo "</td>";
				} else {
					if (count($subs)>1) {
						echo '<td>'.($i+1).'-'.($j+1).'</td>';
					} else {
						echo '<td>'.($i+1).'</td>';
					}
				}
				echo "<td><input type=hidden name=\"curq[]\" id=\"qo$ln\" value=\"{$line['questionsetid']}\"/>{$line['description']}</td>";
				echo "<td><input type=button value=\"Preview\" onClick=\"previewq('curq',$ln,{$line['questionsetid']},false,false)\"/></td>\n";
				
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
				if (!$beentaken) {
					echo "<td class=c><a href=\"moddataset.php?id={$line['questionsetid']}&aid=$aid&cid=$cid&template=true\">Template</a></td>\n";
					echo "<td class=c><a href=\"addquestions.php?remove=$i-$j&aid=$aid&cid=$cid\" onclick=\"return confirm('Are you sure you want to remove this question?')\">Remove</a></td>\n";
				} else {
					echo "<td><a href=\"addquestions.php?cid=$cid&aid=$aid&clearqattempts={$subs[$j]}\">Clear Attempts</a></td>";
				}
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
	
	//remember search
	if (isset($_POST['search'])) {
		$safesearch = $_POST['search'];
		$safesearch = str_replace(' and ', ' ',$safesearch);
		$search = stripslashes($safesearch);
		$search = str_replace('"','&quot;',$search);
		$sessiondata['lastsearch'.$cid] = str_replace(" ","+",$safesearch);
		if (isset($_POST['searchall'])) {
			$searchall = 1;
		} else {
			$searchall = 0;
		}
		$sessiondata['searchall'.$cid] = $searchall;
		if (isset($_POST['searchmine'])) {
			$searchmine = 1;
		} else {
			$searchmine = 0;
		}
		$sessiondata['searchmine'.$cid] = $searchmine;
		writesessiondata();
	} else if (isset($sessiondata['lastsearch'.$cid])) {
		$safesearch = str_replace("+"," ",$sessiondata['lastsearch'.$cid]);
		$search = stripslashes($safesearch);
		$search = str_replace('"','&quot;',$search);
		$searchall = $sessiondata['searchall'.$cid];
		$searchmine = $sessiondata['searchmine'.$cid];
	} else {
		$search = '';
		$searchall = 0;
		$searchmine = 0;
		$safesearch = '';
	}
	if (trim($safesearch)=='') {
		$searchlikes = '';
	} else {
		$searchterms = explode(" ",$safesearch);
		$searchlikes = "(imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') AND ";
	}
	
	if (isset($_POST['libs'])) {
		if ($_POST['libs']=='') {
			$_POST['libs'] = '0';
		}
		$searchlibs = $_POST['libs'];
		//$sessiondata['lastsearchlibs'] = implode(",",$searchlibs);
		$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
		writesessiondata();
	} else if (isset($_GET['listlib'])) {
		$searchlibs = $_GET['listlib'];
		$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
		$searchall = 0;
		$sessiondata['searchall'.$cid] = $searchall;
		writesessiondata();
	}else if (isset($sessiondata['lastsearchlibs'.$cid])) {
		//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
		$searchlibs = $sessiondata['lastsearchlibs'.$cid];
	} else {
		$searchlibs = '0';
	}
	$llist = "'".implode("','",explode(',',$searchlibs))."'";
	
	echo "<p><input type=button value=\"Done\" onClick=\"window.location='course.php?cid=$cid'\"> or ";
	echo "<input type=button value=\"Categorize Questions\" onClick=\"window.location='categorize.php?cid=$cid&aid=$aid'\"> or ";
	echo "<input type=button value=\"Create Print Version\" onClick=\"window.location='printtest.php?cid=$cid&aid=$aid'\"></p>";
?>	
<script type="text/javascript">
var curlibs = '<?php echo $searchlibs;?>';
</script>
<?php	
	if ($beentaken) {
		require("../footer.php");
		exit;
	}
	//potential questions
	echo "<h3>Potential Questions</h3>\n";
	echo "<form method=post action=\"addquestions.php?aid=$aid&cid=$cid\">\n";
	if (substr($searchlibs,0,1)=="0") {
		$lnamesarr[0] = "Unassigned";
	}
	$query = "SELECT name,id FROM imas_libraries WHERE id IN ($llist)";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$lnamesarr[$row[1]] = $row[0];
	}
	$lnames = implode(", ",$lnamesarr);
	echo "In Libraries: <span id=\"libnames\">$lnames</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$searchlibs\">\n";
	echo " <input type=button value=\"Select Libraries\" onClick=\"libselect()\"> ";
	echo "or <input type=button value=\"Select From Assessments\" onClick=\"window.location='addquestionsreview.php?cid=$cid&aid=$aid'\">";
		
	echo "<br>"; 
		
	echo "Search: <input type=text size=15 name=search value=\"$search\"> <input type=checkbox name=\"searchall\" value=\"1\" ";
	if ($searchall==1) {echo "checked=1";}
	echo "/>Search all libs <input type=checkbox name=\"searchmine\" value=\"1\" ";
	if ($searchmine==1) {echo "checked=1";}
	echo "/>Mine only ";
		
	echo "<input type=submit value=Search>\n";
	echo "<input type=button value=\"Add New Question\" onclick=\"window.location='moddataset.php?aid=$aid&cid=$cid'\">";
	echo "</form>";
	if ($searchall==1 && trim($search)=='') {
		echo "Must provide a search term when searching all libraries";
		require("../footer.php");
		exit;
	}
	if (isset($search)) {
		/*if (in_array('all',$searchlibs)) {
			$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype ";
			$query .= "FROM imas_questionset,imas_library_items,imas_libraries WHERE imas_questionset.description LIKE '%$safesearch%' ";
			$query .= "AND (imas_questionset.ownerid=$userid OR imas_questionset.userights>0) "; 
			$query .= "AND imas_library_items.qsetid=imas_questionset.id AND ((imas_library_items.libid=imas_libraries.id ";
			$query .= "AND (imas_libraries.ownerid=$userid OR imas_libraries.userights>0)) OR imas_library_items.libid=0)";
		} else {*/
			//$llist = implode(",",$searchlibs);
			$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_library_items.libid,imas_questionset.ownerid ";
			$query .= "FROM imas_questionset,imas_library_items WHERE $searchlikes "; //imas_questionset.description LIKE '%$safesearch%' ";
			$query .= " (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) "; 
			$query .= "AND imas_library_items.qsetid=imas_questionset.id ";
			
			if ($searchall==0) {
				$query .= "AND imas_library_items.libid IN ($llist)";
			}
			if ($searchmine==1) {
				$query .= " AND imas_questionset.ownerid='$userid'";
			} else {
				$query .= " AND (imas_library_items.libid > 0 OR imas_questionset.ownerid='$userid') "; 
			}
			$query .= " ORDER BY imas_library_items.libid,imas_questionset.id";
		//}
		
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		if (mysql_num_rows($result)==0) {
			echo "<p>No Questions matched search</p>\n";
		} else {
			echo "<form id=\"selq\" method=post action=\"addquestions.php?cid=$cid&aid=$aid&addset=true\">\n";
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
			echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
			echo "<input type=submit value=\"Add Selected (using defaults)\">\n";
			echo "<input type=button value=\"Preview Selected\" onclick=\"previewsel('selq')\" />\n";
			echo "<table cellpadding=5 id=myTable class=gb>\n";
			echo "<thead><tr><th></th><th>Description</th><th>Preview</th><th>Type</th>";
			if ($searchall==1) {
				echo "<th>Library</th>";
			}
			echo "<th>Times Used</th><th>Mine</th><th>Add</th><th>Source</th><th>Use as Template</th></tr></thead>\n";
			echo "<tbody>\n";
			$alt=0;
			$lastlib = -1;
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				if ($lastlib!=$line['libid'] && isset($lnamesarr[$line['libid']])) {
					if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
					echo "<td></td><td><b>".$lnamesarr[$line['libid']]."</b></td>";
					for ($j=0;$j<7+$searchall;$j++) {
						echo "<td></td>";
					}
					echo "</tr>";
					$lastlib = $line['libid'];
				}
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo "<td><input type=checkbox name='nchecked[]' value='{$line['id']}' id='qo$ln'></td>\n";
				echo "<td>{$line['description']}</td>";
				echo "<td><input type=button value=\"Preview\" onClick=\"previewq('selq',$ln,{$line['id']},true,false)\"/></td>\n";
				
				echo "<td>{$line['qtype']}</td>\n";
				if ($searchall==1) {
					echo "<td><a href=\"addquestions.php?cid=$cid&aid=$aid&listlib={$line['libid']}\">List lib</a></td>";
				}	
				$query = "SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$line['id']}'";
				$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$times = mysql_result($result2,0,0);
				echo "<td class=c>$times</td>";
				if ($line['ownerid']==$userid) { echo "<td>Yes</td>";} else {echo "<td></td>";}
				echo "<td class=c><a href=\"modquestion.php?qsetid={$line['id']}&aid=$aid&cid=$cid\">Add</a></td>\n";
				if ($line['userights']>2 || $line['ownerid']==$userid) {
					echo "<td class=c><a href=\"moddataset.php?id={$line['id']}&aid=$aid&cid=$cid&frompot=1\">Edit</a></td>\n";
				} else { echo "<td class=c><a href=\"viewsource.php?id={$line['id']}&aid=$aid&cid=$cid\">View</a></td>";}
				
				echo "<td class=c><a href=\"moddataset.php?id={$line['id']}&aid=$aid&cid=$cid&template=true\">Template</a></td>\n";
				echo "</tr>\n";
				$ln++;
			}
			echo "</tbody></table>\n";
			echo "<script type=\"text/javascript\">\n";
			echo "initSortTable('myTable',Array(false,'S',false,'S',";
			if ($searchall==1) {
				echo "false,";
			}
			echo "'N','S',false,false,false),true);\n";
			echo "</script>\n";
			echo "</form>\n";
		}
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
