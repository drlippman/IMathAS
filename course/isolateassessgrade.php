<?php
//IMathAS:  Display grade list for one online assessment
//(c) 2007 David Lippman
	require("../validate.php");
	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	
	//TODO:  make tutor friendly by adding section filter
	if (!$isteacher && !$istutor) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmode = mysql_result($result,0,0);
	}
	$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
	
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; View Scores</div>";
	
	echo '<div class="cpmid"><a href="gb-itemanalysis.php?cid='.$cid.'&amp;aid='.$aid.'">View Item Analysis</a></div>';
	
	$query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
	$query .= "AND imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	if (mysql_result($result,0,0)>0) {
		$hassection = true;
	} else {
		$hassection = false;
	}
	
	if ($hassection) {
		$query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		if (mysql_result($result,0,0)==0) {
			$sortorder = "sec";
		} else {
			$sortorder = "name";
		}
	} else {
		$sortorder = "name";
	}
	
	$query = "SELECT minscore,timelimit,deffeedback,enddate,name,defpoints,itemorder FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	list($minscore,$timelimit,$deffeedback,$enddate,$name,$defpoints,$itemorder) = mysql_fetch_row($result);
	$deffeedback = explode('-',$deffeedback);
	$assessmenttype = $deffeedback[0];
	
	$aitems = explode(',',$itemorder);
	foreach ($aitems as $k=>$v) {
		if (strpos($v,'~')!==FALSE) {
			$sub = explode('~',$v);
			if (strpos($sub[0],'|')===false) { //backwards compat
				$aitems[$k] = $sub[0];
				$aitemcnt[$k] = 1;
				
			} else {
				$grpparts = explode('|',$sub[0]);
				$aitems[$k] = $sub[1];
				$aitemcnt[$k] = $grpparts[0];
			}
		} else {
			$aitemcnt[$k] = 1;
		}
	}
		
	$query = "SELECT points,id FROM imas_questions WHERE assessmentid='$aid'";
	$result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$totalpossible = 0;
	while ($r = mysql_fetch_row($result2)) {
		if (($k = array_search($r[1],$aitems))!==false) { //only use first item from grouped questions for total pts	
			if ($r[0]==9999) {
				$totalpossible += $aitemcnt[$k]*$defpoints; //use defpoints
			} else {
				$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
			}
		}
	}
	
	
	echo '<div id="headerisolateassessgrade" class="pagetitle"><h2>';
	echo "Grades for $name</h2></div>";
	echo "<p>$totalpossible points possible</p>";
	
//	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
//	$query .= "ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM imas_assessment_sessions AS ias,imas_users AS iu,imas_students AS istu ";
//	$query .= "WHERE iu.id = istu.userid AND istu.courseid='$cid' AND iu.id=ias.userid AND ias.assessmentid='$aid'";

	//get exceptions
	$query = "SELECT userid,enddate,islatepass FROM imas_exceptions WHERE assessmentid='$aid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$exceptions = array();
	while ($row = mysql_fetch_row($result)) {
		$exceptions[$row[0]] = array($row[1],$row[2]);
	}
	
	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
	$query .= "ias.id,istu.userid,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,istu.locked FROM imas_users AS iu JOIN imas_students AS istu ON iu.id = istu.userid AND istu.courseid='$cid' ";
	$query .= "LEFT JOIN imas_assessment_sessions AS ias ON iu.id=ias.userid AND ias.assessmentid='$aid' WHERE istu.courseid='$cid' ";
	if ($istutor && isset($tutorsection) && $tutorsection!='') {
		$query .= " AND istu.section='$tutorsection' ";
	}
	if ($hidelocked) {
		$query .= ' AND istu.locked=0 ';
	}
	if ($hassection && $sortorder=="sec") {
		 $query .= " ORDER BY istu.section,iu.LastName,iu.FirstName";
	} else {
		 $query .= " ORDER BY iu.LastName,iu.FirstName";
	}
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			
	
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	
	echo "<table id=myTable class=gb><thead><tr><th>Name</th>";
	if ($hassection) {
		echo '<th>Section</th>';
	}
	echo "<th>Grade</th><th>%</th><th>Time Spent (In Questions)</th><th>Feedback</th></tr></thead><tbody>";
	$now = time();
	$lc = 1;
	$n = 0;
	$ntime = 0;
	$tot = 0;
	$tottime = 0;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($lc%2!=0) {
			echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">"; 
		} else {
			echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">"; 
		}
		$lc++;
		if ($line['locked']>0) {
			echo '<td><span style="text-decoration: line-through;">';
			echo "{$line['LastName']}, {$line['FirstName']}</span></td>";
		} else {
			echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
		}
		if ($hassection) {
			echo "<td>{$line['section']}</td>";
		}
		$total = 0;
		$sp = explode(';',$line['bestscores']);
		$scores = explode(",",$sp[0]);
		if (in_array(-1,$scores)) { $IP=1;} else {$IP=0;}
		for ($i=0;$i<count($scores);$i++) {
			$total += getpts($scores[$i]);
		}
		$timeused = $line['endtime']-$line['starttime'];
		$timeontask = round(array_sum(explode(',',str_replace('~',',',$line['timeontask'])))/60,1);
		
		if ($line['id']==null) {
			echo "<td><a href=\"gb-viewasid.php?gbmode=$gbmode&cid=$cid&asid=new&uid={$line['userid']}&from=isolate&aid=$aid\">-</a></td><td>-</td><td></td><td></td>";		
		} else {
			if (isset($exceptions[$line['userid']])) {
				$thisenddate = $exceptions[$line['userid']][0];
			} else {
				$thisenddate = $enddate;
			}
			echo "<td><a href=\"gb-viewasid.php?gbmode=$gbmode&cid=$cid&asid={$line['id']}&uid={$line['userid']}&from=isolate&aid=$aid\">";
			if ($thisenddate>$now) {
				echo '<i>'.$total;
			} else {
				echo $total;
			}
			if ($total<$minscore) {
				echo "&nbsp;(NC)";
			} else 	if ($IP==1 && $thisenddate>$now) {
				echo "&nbsp;(IP)";
			} else	if (($timelimit>0) &&($timeused > $timelimit*$line['timelimitmult'])) {
				echo "&nbsp;(OT)";
			} else if ($assessmenttype=="Practice") {
				echo "&nbsp;(PT)";
			} else {
				$tot += $total;
				$n++;
			}
			if ($thisenddate>$now) {
				echo '</i>';
			}
			echo '</a>';
			if (isset($exceptions[$line['userid']])) {
				if ($exceptions[$line['userid']][1]>0) {
					echo '<sup>LP</sup>';
				} else {
					echo '<sup>e</sup>';
				}
			} 
			echo '</td>';
			if ($totalpossible>0) {
				echo '<td>'.round(100*($total)/$totalpossible,1).'%</td>';
			} else {
				echo '<td>&nbsp;</td>';
			}
			if ($line['endtime']==0 || $line['starttime']==0) {
				echo '<td>&nbsp;</td>';
			} else {
				echo '<td>'.round($timeused/60).' min';
				if ($timeontask>0) {
					echo ' ('.$timeontask.' min)';
				}
				echo '</td>';
				$tottime += $timeused;
				$ntime++;
			}
			echo "<td>{$line['feedback']}&nbsp;</td>";
		}
		echo "</tr>";
	}
	echo '<tr><td>Average</td>';
	if ($hassection) {
		echo '<td></td>';
	}
	echo "<td><a href=\"gb-itemanalysis.php?cid=$cid&aid=$aid&from=isolate\">";
	if ($n>0) {
		echo round($tot/$n,1);
	} else {
		echo '-';
	}
	if ($totalpossible > 0 && $n>0) {
		$pct = round(100*($tot/$n)/$totalpossible,1).'%';
	} else {
		$pct = '-';
	}
	if ($ntime>0) {
		$timeavg = round(($tottime/$ntime)/60) . ' min';
	} else {
		$timeavg = '-';
	}
	echo "</a></td><td>$pct</td><td>$timeavg</td><td></td></tr>";
	echo "</tbody></table>";
	if ($hassection) {
		echo "<script> initSortTable('myTable',Array('S','S','N'),true);</script>";
	} else {
		echo "<script> initSortTable('myTable',Array('S','N'),true);</script>";
	}
	echo "<p>Meanings:  <i>italics</i>-available to student, IP-In Progress (some questions unattempted), OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/>";
	echo "<sup>e</sup> Has exception <sup>LP</sup> Used latepass  </p>\n";
	
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
