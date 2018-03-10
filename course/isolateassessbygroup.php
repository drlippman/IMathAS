<?php
//IMathAS:  Display grade list for one online assessment by group
//(c) 2007 David Lippman
	require("../init.php");

	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);

	if (!$isteacher) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);

	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else {
		//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $gbmode = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}
	$placeinhead .= '<script type="text/javascript">
		function showfb(id,type) {
			GB_show(_("Feedback"), "showfeedback?cid="+cid+"&type="+type+"&id="+id, 500, 500);
			return false;
		}
		</script>';
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid\">Gradebook</a> &gt; View Group Scores</div>";

	//DB $query = "SELECT minscore,timelimit,deffeedback,enddate,name,defpoints,itemorder,groupsetid FROM imas_assessments WHERE id='$aid'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB list($minscore,$timelimit,$deffeedback,$enddate,$name,$defpoints,$itemorder,$groupsetid) = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT minscore,timelimit,deffeedback,enddate,name,defpoints,itemorder,groupsetid FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($minscore,$timelimit,$deffeedback,$enddate,$name,$defpoints,$itemorder,$groupsetid) = $stm->fetch(PDO::FETCH_NUM);
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
	echo "Group grades for " . Sanitize::encodeStringForDisplay($name) . "</h2></div>";
	echo "<p>$totalpossible points possible</p>";

//	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
//	$query .= "ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM imas_assessment_sessions AS ias,imas_users AS iu,imas_students AS istu ";
//	$query .= "WHERE iu.id = istu.userid AND istu.courseid='$cid' AND iu.id=ias.userid AND ias.assessmentid='$aid'";

	$scoredata = array();
	//DB $query = "SELECT ias.agroupid,ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM ";
	//DB $query .= "imas_assessment_sessions AS ias WHERE ias.assessmentid='$aid' GROUP BY ias.agroupid";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	$query = "SELECT ias.agroupid,ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM ";
	$query .= "imas_assessment_sessions AS ias WHERE ias.assessmentid=:assessmentid GROUP BY ias.agroupid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':assessmentid'=>$aid));
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$scoredata[$line['agroupid']] = $line;
	}

	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";

	echo "<table id=myTable class=gb><thead><tr><th>Group</th>";
	echo "<th>Grade</th><th>%</th><th>Feedback</th></tr></thead><tbody>";
	$now = time();
	$lc = 1;
	$n = 0;
	$tot = 0;
	//DB $query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY id");
	$stm->execute(array(':groupsetid'=>$groupsetid));
	$grpnums = 1;
	$stu_name = null;
	$groupnames = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1] == 'Unnamed group') {
			$row[1] .= " $grpnums";
			$grpnums++;
			//DB $query = "SELECT iu.FirstName,iu.LastName FROM imas_users AS iu JOIN imas_stugroupmembers AS isgm ";
			//DB $query .= "ON iu.id=isgm.userid AND isgm.stugroupid='{$row[0]}' ORDER BY isgm.id LIMIT 1";
			//DB $r = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($r)>0) {
			if ($stu_name===null) {
				$query = "SELECT iu.LastName,iu.FirstName FROM imas_users AS iu JOIN imas_stugroupmembers AS isgm ";
				$query .= "ON iu.id=isgm.userid AND isgm.stugroupid=:stugroupid ORDER BY isgm.id LIMIT 1";
				$stu_name = $DBH->prepare($query);
			}
			$stu_name->execute(array(':stugroupid'=>$row[0]));
			if ($stu_name->rowCount()>0) {
				//DB $row[1] .= ' ('.mysql_result($r,0,0).', '.mysql_result($r,0,1).' &isin;)';
				$row[1] .= ' ('.implode(', ', $stu_name->fetch(PDO::FETCH_NUM)).' &isin;)';
			}
		}
		$groupnames[$row[0]] = $row[1];
	}
	natsort($groupnames);

	foreach ($groupnames as $gid=>$gname) {
		if ($lc%2!=0) {
			echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">";
		} else {
			echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">";
		}
		$lc++;
		echo "<td>" . Sanitize::encodeStringForDisplay($gname) . "</td>";
		if (!isset($scoredata[$gid])) {
			echo "<td>-</td><td>-</td><td></td></tr>";
			continue;
		} else {
			$line = $scoredata[$gid];
		}
		$total = 0;
		$sp = explode(';',$line['bestscores']);
		$scores = explode(",",$sp[0]);
		if (in_array(-1,$scores)) { $IP=1;} else {$IP=0;}
		for ($i=0;$i<count($scores);$i++) {
			$total += getpts($scores[$i]);
		}
		$timeused = $line['endtime']-$line['starttime'];

		if ($line['id']==null) {
			$querymap = array(
				'gbmode' => $gbmode,
				'cid' => $cid,
				'asid' => 'new',
				'uid' => $line['userid'],
				'from' => 'gisolate',
				'aid' => $aid
			);

			echo '<td><a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . '">-</a></td><td>-</td><td></td>';
		} else {
			$querymap = array(
                'gbmode' => $gbmode,
                'cid' => $cid,
                'asid' => $line['id'],
                'uid' => $line['userid'],
                'from' => 'gisolate',
				'aid' => $aid
			);

      echo '<td><a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . '">';
			//if ($total<$minscore) {
			if (($minscore<10000 && $total<$minscore) || ($minscore>10000 && $total<($minscore-10000)/100*$totalpossible)) {
				echo Sanitize::onlyFloat($total) . "&nbsp;(NC)";
			} else 	if ($IP==1 && $enddate>$now) {
				echo Sanitize::onlyFloat($total) . "&nbsp;(IP)";
			} else	if (($timelimit>0) &&($timeused > $timelimit*$line['timelimitmult'])) {
				echo Sanitize::onlyFloat($total) . "&nbsp;(OT)";
			} else if ($assessmenttype=="Practice") {
				echo Sanitize::onlyFloat($total) . "&nbsp;(PT)";
			} else {
				echo Sanitize::onlyFloat($total);
				$tot += $total;
				$n++;
			}

			echo "</a></td>";
			if ($totalpossible>0) {
				echo '<td>'.round(100*($total)/$totalpossible,1).'%</td>';
			} else {
				echo '<td></td>';
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
			//echo "<td>{$line['feedback']}</td>";
		}
		echo "</tr>";
	}
	echo '<tr><td>Average</td>';
	echo "<td><a href=\"gb-itemanalysis.php?cid=$cid&aid=$aid&from=gisolate\">";
	if ($n>0) {
		echo round($tot/$n,1);
	} else {
		echo '-';
	}
	if ($totalpossible > 0 && $n > 0) {
		$pct = round(100*($tot/$n)/$totalpossible,1).'%';
	} else {
		$pct = '';
	}
	echo "</a></td><td>".Sanitize::onlyFloat($pct)."</td></tr>";
	echo "</tbody></table>";
	echo "<script> initSortTable('myTable',Array('S','N','N'),true);</script>";
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
