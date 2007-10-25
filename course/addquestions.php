<?php
//IMathAS:  Add/remove/modify questions used in an assessment
//(c) 2006 David Lippman
	require("../validate.php");
	if (!(isset($teacherid))) {
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];	
	if (isset($_GET['grp'])) { $sessiondata['groupopt'.$aid] = $_GET['grp']; writesessiondata();}
	if (isset($_GET['selfrom'])) {
		$sessiondata['selfrom'.$aid] = $_GET['selfrom'];
		writesessiondata();
	} else {
		if (!isset($sessiondata['selfrom'.$aid])) {
			$sessiondata['selfrom'.$aid] = 'lib';
			writesessiondata();
		}
	}
	if (isset($_GET['addset'])) {
		if (!isset($_POST['nchecked']) && !isset($_POST['qsetids'])) {
				echo "No questions selected.  <a href=\"addquestions.php?cid=$cid&aid=$aid\">Go back</a>\n";
				require("../footer.php");
				exit;
		}
		if (isset($_POST['add'])) {
			include("modquestiongrid.php");
			if (isset($_GET['process'])) {
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
				exit;
			}
		} else {

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
	}
	if (isset($_GET['modqs'])) {
		include("modquestiongrid.php");
		if (isset($_GET['process'])) {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestions.php?cid=$cid&aid=$aid");
			exit;
		}
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
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addqsort.js\"></script>";
	$pagetitle = "Add/Remove Questions";
	require("../header.php");
	
	

	
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
	echo "<script>var curcid = $cid; var curaid = $aid; var defpoints = $defpoints;";
	echo "var AHAHsaveurl = 'http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addquestionssave.php?cid=$cid&aid=$aid';";
	echo "</script>";
	if ($itemorder == '') {
		echo "<p>No Questions currently in assessment</p>\n";
	} else {
		echo "<form id=\"curqform\" method=post action=\"addquestions.php?cid=$cid&aid=$aid&modqs=true\">";
		//echo "method=post action=\"addquestions.php?cid=$cid&aid=$aid&removeset=true\" onsubmit=\"return confirm('Are you SURE you want to remove these questions?');\">\n";
		if (isset($sessiondata['groupopt'.$aid])) {
			$grp = $sessiondata['groupopt'.$aid];
		} else {
			$grp = 0;
		}
		if (!$beentaken) {
			echo "Use select boxes to <select name=group id=group><option value=\"0\" ";
			if ($grp==0) { echo "SELECTED";}
			echo ">Rearrange questions</option>";
			echo "<option value=\"1\" ";
			if ($grp==1) { echo "SELECTED";}
			echo ">Group questions</option></select>\n";
		}
		echo " With Selected: <input type=button value=\"Remove\" onclick=\"removeSelected()\" />";
		echo " <input type=submit value=\"Change Settings\" />";
		echo " <span id=\"submitnotice\" style=\"color:red;\"></span>";
		echo "<div id=\"curqtbl\"></div>";
		$jsarr = '[';
		$items = explode(",",$itemorder);
		$apointstot = 0;
		for ($i = 0; $i < count($items); $i++) {
			if (strpos($items[$i],'~')!==false) {
				$subs = explode('~',$items[$i]);
			} else {
				$subs[] = $items[$i];
			}
			if ($i>0) {
				$jsarr .= ',';
			}
			if (count($subs)>1) {
				if (strpos($subs[0],'|')===false) { //for backwards compat
					$jsarr .= '[1,0,['; 
				} else {
					$grpparts = explode('|',$subs[0]);
					$jsarr .= '['.$grpparts[0].','.$grpparts[1].',[';
					array_shift($subs);
				}
			} 
			for ($j=0;$j<count($subs);$j++) {
				$query = "SELECT imas_questions.questionsetid,imas_questionset.description,imas_questionset.userights,imas_questionset.ownerid,imas_questionset.qtype,imas_questions.points FROM imas_questions,imas_questionset ";
				$query .= "WHERE imas_questions.id='{$subs[$j]}' AND imas_questionset.id=imas_questions.questionsetid";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				if ($j>0) {
					$jsarr .= ',';
				} 
				//output item array
				$jsarr .= '['.$subs[$j].','.$line['questionsetid'].',"'.addslashes(str_replace(array("\r\n", "\n", "\r")," ",$line['description'])).'","'.$line['qtype'].'",'.$line['points'].',';
				if ($line['userights']>2 || $line['ownerid']==$userid) {
					$jsarr .= '1';
				} else {
					$jsarr .= '0';
				}
				$jsarr .= ']';
			}
			if (count($subs)>1) {
				$jsarr .= ']]';
			}
			$alt = 1-$alt;
			unset($subs);
		}
		$jsarr .= ']';
		echo "</tbody></table>\n";
		echo "</form>";
		echo "<p>Assessment points total: <span id=\"pttotal\"></span></p>";
		echo "<script> var itemarray = $jsarr; var beentaken = ". ($beentaken? 1:0)  ."; document.getElementById(\"curqtbl\").innerHTML = generateTable();</script>";
	}
	
	echo "<p><input type=button value=\"Done\" onClick=\"window.location='course.php?cid=$cid'\"> or ";
	echo "<input type=button value=\"Categorize Questions\" onClick=\"window.location='categorize.php?cid=$cid&aid=$aid'\"> or ";
	echo "<input type=button value=\"Create Print Version\" onClick=\"window.location='printtest.php?cid=$cid&aid=$aid'\"></p>";
		
	//POTENTIAL QUESTIONS
	
	if ($sessiondata['selfrom'.$aid]=='lib') { //selecting from libraries
	
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
			if (substr($safesearch,0,3)=='id=') {
				$searchlikes = "imas_questionset.id='".substr($safesearch,3)."' AND ";
			}
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
		echo "or <input type=button value=\"Select From Assessments\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&selfrom=assm'\">";
			
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
				echo "<input name=\"add\" type=submit value=\"Add\" />\n";
				echo "<input name=\"addquick\" type=submit value=\"Add (using defaults)\">\n";
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
					if ($line['ownerid']==$userid) { 
						if ($line['userights']==0) {
							echo "<td>Private</td>";
						} else {
							echo "<td>Yes</td>";
						}
					} else {
						echo "<td></td>";
					}
					echo "<td class=c><a href=\"modquestion.php?qsetid={$line['id']}&aid=$aid&cid=$cid\">Add</a></td>\n";
					if ($line['userights']>2 || $line['ownerid']==$userid) {
						echo "<td class=c><a href=\"moddataset.php?id={$line['id']}&aid=$aid&cid=$cid&frompot=1\">Edit</a></td>\n";
					} else { 
						echo "<td class=c><a href=\"viewsource.php?id={$line['id']}&aid=$aid&cid=$cid\">View</a></td>";
					}
					
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
		
	} else if ($sessiondata['selfrom'.$aid]=='assm') { //select from assessments
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
			echo "<form id=\"selq\" method=post action=\"addquestions.php?cid=$cid&aid=$aid&addset=true\">\n";
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
			echo "<input type=button value=\"Select Assessments\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&clearassmt=1'\">";
			echo " or <input type=button value=\"Select From Libraries\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&selfrom=lib'\"> <br/>";
			
			echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
			echo "<input name=\"add\" type=submit value=\"Add\" />\n";
			echo "<input name=\"addquick\" type=submit value=\"Add Selected (using defaults)\">\n";
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
			echo "<form method=post action=\"addquestions.php?cid=$cid&aid=$aid\">\n";
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
			echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'achecked[]', this.checked)\" checked=1>\n";
			echo "<input type=submit value=\"Use these Assessments\" /> or ";
			echo "<input type=button value=\"Select From Libraries\" onClick=\"window.location='addquestions.php?cid=$cid&aid=$aid&selfrom=lib'\">";
			
			echo "<table cellpadding=5 id=myTable class=gb>\n";
			echo "<thead><tr><th></th><th>Assessment</th><th>Summary</th></tr></thead>\n";
			echo "<tbody>\n";
			$query = "SELECT id,name,summary FROM imas_assessments WHERE courseid='$cid' AND id<>'$aid' ORDER BY enddate,name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$alt=0;
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo "<td><input type=checkbox name='achecked[]' value='{$line['id']}' checked=1></td>\n";
				echo "<td>{$line['name']}</td>";
				$line['summary'] = strip_tags($line['summary']);
				if (strlen($line['summary'])>100) {
					$line['summary'] = substr($line['summary'],0,97).'...';
				}
				echo '<td>'.$line['summary'].'</td>';
				echo "</tr>\n";
			}
			echo "</tbody></table>\n";
			echo "<script type=\"text/javascript\">\n";
			echo "initSortTable('myTable',Array(false,'S','S',false,false,false),true);\n";
			echo "</script>\n";
			echo "</form>\n";
		}
		
	}
	
	require("../footer.php");
	
	
?>
