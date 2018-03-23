<?php
//IMathAS:  Display grade list for one online assessment
//(c) 2007 David Lippman
	require("../init.php");

	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);

	//TODO:  make tutor friendly by adding section filter
	if (!$isteacher && !$istutor) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);

	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else if (isset($sessiondata[$cid.'gbmode'])) {
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
	$placeinhead .= '<script type="text/javascript">
		function showfb(id,type) {
			GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+id, 500, 500);
			return false;
		}
		</script>';
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid\">Gradebook</a> &gt; View Scores</div>";

	echo '<div class="cpmid"><a href="gb-itemanalysis.php?cid='.$cid.'&amp;aid='.$aid.'">View Item Analysis</a></div>';

	//DB $query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
	//DB $query .= "AND imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
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
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
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

	//DB $query = "SELECT minscore,timelimit,deffeedback,enddate,name,defpoints,itemorder FROM imas_assessments WHERE id='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB list($minscore,$timelimit,$deffeedback,$enddate,$name,$defpoints,$itemorder) = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT minscore,timelimit,deffeedback,startdate,enddate,allowlate,name,defpoints,itemorder FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($minscore,$timelimit,$deffeedback,$startdate,$enddate,$allowlate,$name,$defpoints,$itemorder) = $stm->fetch(PDO::FETCH_NUM);
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

	//DB $query = "SELECT points,id FROM imas_questions WHERE assessmentid='$aid'";
	//DB $result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	//DB $totalpossible = 0;
	$stm = $DBH->prepare("SELECT points,id FROM imas_questions WHERE assessmentid=:assessmentid");
	$stm->execute(array(':assessmentid'=>$aid));
	$totalpossible = 0;
	//DB while ($r = mysql_fetch_row($result2)) {
	while ($r = $stm->fetch(PDO::FETCH_NUM)) {
		if (($k = array_search($r[1],$aitems))!==false) { //only use first item from grouped questions for total pts
			if ($r[0]==9999) {
				$totalpossible += $aitemcnt[$k]*$defpoints; //use defpoints
			} else {
				$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
			}
		}
	}


	echo '<div id="headerisolateassessgrade" class="pagetitle"><h2>';
	echo "Grades for " . Sanitize::encodeStringForDisplay($name) . "</h2></div>";
	echo "<p>$totalpossible points possible</p>";

//	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
//	$query .= "ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM imas_assessment_sessions AS ias,imas_users AS iu,imas_students AS istu ";
//	$query .= "WHERE iu.id = istu.userid AND istu.courseid='$cid' AND iu.id=ias.userid AND ias.assessmentid='$aid'";

	//get exceptions
	//DB $query = "SELECT userid,enddate,islatepass FROM imas_exceptions WHERE assessmentid='$aid' AND itemtype='A'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("SELECT userid,startdate,enddate,islatepass FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
	$stm->execute(array(':assessmentid'=>$aid));
	$exceptions = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$exceptions[$row[0]] = array($row[1],$row[2],$row[3]);
	}
	if (count($exceptions)>0) {
		require_once("../includes/exceptionfuncs.php");
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, !$isteacher && !$istutor);
	}

	//DB $query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
	//DB $query .= "ias.id,istu.userid,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,istu.locked FROM imas_users AS iu JOIN imas_students AS istu ON iu.id = istu.userid AND istu.courseid='$cid' ";
	//DB $query .= "LEFT JOIN imas_assessment_sessions AS ias ON iu.id=ias.userid AND ias.assessmentid='$aid' WHERE istu.courseid='$cid' ";
	//DB if ($secfilter != -1) {
		//DB $query .= " AND istu.section='$secfilter' ";
	//DB }
	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.code,istu.timelimitmult,";
	$query .= "ias.id,istu.userid,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,istu.locked FROM imas_users AS iu JOIN imas_students AS istu ON iu.id = istu.userid AND istu.courseid=:courseid ";
	$query .= "LEFT JOIN imas_assessment_sessions AS ias ON iu.id=ias.userid AND ias.assessmentid=:assessmentid WHERE istu.courseid=:courseid2 ";
	if ($secfilter != -1) {
		$query .= " AND istu.section=:section ";
	}
	if ($hidelocked) {
		$query .= ' AND istu.locked=0 ';
	}
	if ($hassection && $sortorder=="sec") {
		 $query .= " ORDER BY istu.section,iu.LastName,iu.FirstName";
	} else {
		 $query .= " ORDER BY iu.LastName,iu.FirstName";
	}
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare($query);
	if ($secfilter != -1) {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid, ':courseid2'=>$cid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid, ':courseid2'=>$cid));
	}


	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";

	echo "<table id=myTable class=gb><thead><tr><th>Name</th>";
	if ($hassection) {
		echo '<th>Section</th>';
	}
	if ($hascodes) {
		echo '<th>Code</th>';
	}
	echo "<th>Grade</th><th>%</th><th>Last Change</th><th>Time Spent (In Questions)</th><th>Feedback</th></tr></thead><tbody>";
	$now = time();
	$lc = 1;
	$n = 0;
	$ntime = 0;
	$tot = 0;
	$tottime = 0;
	$tottimeontask = 0;
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($lc%2!=0) {
			echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">";
		} else {
			echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">";
		}
		$lc++;
		if ($line['locked']>0) {
			echo '<td><span style="text-decoration: line-through;">';
			printf("%s, %s</span></td>", Sanitize::encodeStringForDisplay($line['LastName']),
				Sanitize::encodeStringForDisplay($line['FirstName']));
		} else {
			printf("<td>%s, %s</td>", Sanitize::encodeStringForDisplay($line['LastName']),
				Sanitize::encodeStringForDisplay($line['FirstName']));
		}
		if ($hassection) {
			printf("<td>%s</td>", Sanitize::encodeStringForDisplay($line['section']));
		}
		if ($hascodes) {
			if ($line['code']==null) {$line['code']='';}
			printf("<td>%s</td>", Sanitize::encodeStringForDisplay($line['code']));
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
		$useexception = false;
		if (isset($exceptions[$line['userid']])) {
			$useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$line['userid']], array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate), true);
			if ($useexception) {
				$thisenddate = $exceptions[$line['userid']][1];
			}
		} else {
			$thisenddate = $enddate;
		}
		if ($line['id']==null) {
			$querymap = array(
				'gbmode' => $gbmode,
				'cid' => $cid,
				'asid' => 'new',
				'uid' => $line['userid'],
				'from' => 'isolate',
				'aid' => $aid
			);

			echo '<td><a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . '">-</a>';
			if ($useexception) {
				if ($exceptions[$line['userid']][2]>0) {
					echo '<sup>LP</sup>';
				} else {
					echo '<sup>e</sup>';
				}
			}
			echo "</td><td>-</td><td></td><td></td><td></td>";
		} else {
			$querymap = array(
				'gbmode' => $gbmode,
				'cid' => $cid,
				'asid' => $line['id'],
				'uid' => $line['userid'],
				'from' => 'isolate',
				'aid' => $aid
			);

			echo '<td><a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . '">';
			if ($thisenddate>$now) {
				echo '<i>'.Sanitize::onlyFloat($total);
			} else {
				echo Sanitize::onlyFloat($total);
			}
			//if ($total<$minscore) {
			if (($minscore<10000 && $total<$minscore) || ($minscore>10000 && $total<($minscore-10000)/100*$totalpossible)) {
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
			if ($useexception) {
				if ($exceptions[$line['userid']][2]>0) {
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
			if ($line['endtime']==0) {
				if ($line['starttime']==0) {
					echo '<td>Never started</td>';
				} else {
					echo '<td>Never submitted</td>';
				}
			} else {
				echo '<td>'.tzdate("n/j/y g:ia",$line['endtime']).'</td>';
			}
			if ($line['endtime']==0 || $line['starttime']==0) {
				echo '<td>&nbsp;</td>';
			} else {
				echo '<td>'.round($timeused/60).' min';
				if ($timeontask>0) {
					echo ' ('.$timeontask.' min)';
					$tottimeontask += $timeontask;
				}
				echo '</td>';
				$tottime += $timeused;
				$ntime++;
			}

			$feedback = json_decode($line['feedback']);
			if ($feedback===null) {
				$hasfeedback = ($line['feedback'] != '');
			} else {
				$hasfeedback = false;
				foreach ($feedback as $k=>$v) {
					if ($v != '' && $v != '<p></p>') {
						$hasfeedback = true;
						break;
					}
				}
			}
			if ($hasfeedback) {
				echo '<td><a href="#" class="small feedbacksh pointer" onclick="return showfb('.Sanitize::onlyInt($line['id']).',\'A\')">', _('[Show Feedback]'), '</a></td>';
			} else {
				echo '<td></td>';
			}
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
		if ($tottimeontask >0 ) {
			$timeavg .= ' ('.round($tottimeontask/$ntime) . ' min)';
		}
	} else {
		$timeavg = '-';
	}
	echo "</a></td><td>$pct</td><td></td><td>$timeavg</td><td></td></tr>";
	echo "</tbody></table>";
	if ($hassection && $hascodes) {
		echo "<script> initSortTable('myTable',Array('S','S','S','N','P','D'),true);</script>";
	} else if ($hassection) {
		echo "<script> initSortTable('myTable',Array('S','S','N','P','D'),true);</script>";
	} else {
		echo "<script> initSortTable('myTable',Array('S','N','P','D'),true);</script>";
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
