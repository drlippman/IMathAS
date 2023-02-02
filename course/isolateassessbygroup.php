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
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}

	$stm = $DBH->prepare("SELECT minscore,timelimit,deffeedback,enddate,name,defpoints,itemorder,groupsetid,ver,deffeedbacktext FROM imas_assessments WHERE id=:id AND courseid=:cid");
	$stm->execute(array(':id'=>$aid, ':cid'=>$cid));
	if ($stm->rowCount()==0) {
		echo "Invalid ID";
		exit;
	}
	list($minscore,$timelimit,$deffeedback,$enddate,$name,$defpoints,$itemorder,$groupsetid,$aver,$deffeedbacktext) = $stm->fetch(PDO::FETCH_NUM);
	$deffeedback = explode('-',$deffeedback);
	$assessmenttype = $deffeedback[0];

	$placeinhead = '<script type="text/javascript">
		function showfb(id,type) {
			GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+id, 500, 500);
			return false;
		}
		function showfb2(aid,uid,type) {
			GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+aid+"&uid="+uid, 500, 500);
			return false;
		}
		</script>';
    require("../header.php");
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
        echo "<a href=\"gradebook.php?gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid\">Gradebook</a> &gt; ";
    }
    echo _('View Group Scores').'</div>';

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
	$stm = $DBH->prepare("SELECT points,id FROM imas_questions WHERE assessmentid=:assessmentid");
	$stm->execute(array(':assessmentid'=>$aid));
	$totalpossible = 0;
	while ($r = $stm->fetch(PDO::FETCH_NUM)) {
		if (($k = array_search($r[1],$aitems))!==false) { //only use first item from grouped questions for total pts
			if ($r[0]==9999) {
				$totalpossible += $aitemcnt[$k]*$defpoints; //use defpoints
			} else {
				$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
			}
		}
	}


	echo '<div id="headerisolateassessgrade" class="pagetitle"><h1>';
	echo "Group grades for " . Sanitize::encodeStringForDisplay($name) . "</h1></div>";
	echo "<p>$totalpossible points possible</p>";

//	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
//	$query .= "ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM imas_assessment_sessions AS ias,imas_users AS iu,imas_students AS istu ";
//	$query .= "WHERE iu.id = istu.userid AND istu.courseid='$cid' AND iu.id=ias.userid AND ias.assessmentid='$aid'";

	$scoredata = array();
	if ($aver>1) {
		$query = "SELECT iar.agroupid,iar.userid,iar.starttime,iar.lastchange,iar.score,iar.status,iar.timeontask FROM ";
		$query .= "imas_assessment_records AS iar WHERE iar.assessmentid=:assessmentid GROUP BY iar.agroupid";
	} else {
		$query = "SELECT ias.agroupid,ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM ";
		$query .= "imas_assessment_sessions AS ias WHERE ias.assessmentid=:assessmentid GROUP BY ias.agroupid";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':assessmentid'=>$aid));
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$scoredata[$line['agroupid']] = $line;
	}

	if ($aver>1) {
		if (!empty($CFG['assess2-use-vue-dev'])) {
			$assessGbUrl = sprintf("%s/gbviewassess.html?", $CFG['assess2-use-vue-dev-address']);
		} else {
			$assessGbUrl = "../assess2/gbviewassess.php?";
		}
	}

	echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";

	echo "<table id=myTable class=gb><thead><tr><th>Group</th>";
	echo "<th>Grade</th><th>%</th><th>Feedback</th></tr></thead><tbody>";
	$now = time();
	$lc = 1;
	$n = 0;
	$tot = 0;
	$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY id");
	$stm->execute(array(':groupsetid'=>$groupsetid));
	$grpnums = 1;
	$stu_name = null;
	$groupnames = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1] == 'Unnamed group') {
			$row[1] .= " $grpnums";
			$grpnums++;
			if ($stu_name===null) {
				$query = "SELECT iu.LastName,iu.FirstName FROM imas_users AS iu JOIN imas_stugroupmembers AS isgm ";
				$query .= "ON iu.id=isgm.userid AND isgm.stugroupid=:stugroupid ORDER BY isgm.id LIMIT 1";
				$stu_name = $DBH->prepare($query);
			}
			$stu_name->execute(array(':stugroupid'=>$row[0]));
			if ($stu_name->rowCount()>0) {
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
		if ($aver > 1) {
			$total = $line['score'];
			$timeused = $line['lastchange'] - $line['starttime'];
			$isOvertime = ($line['status']&4) == 4;
			$IP = ($line['status']&3)>0;
			$UA = ($line['status']&1)>0;
		} else {
			$sp = explode(';',$line['bestscores']);
			$scores = explode(",",$sp[0]);
			if (in_array(-1,$scores)) { $IP=1;} else {$IP=0;}
			$UA = 0;
			for ($i=0;$i<count($scores);$i++) {
				$total += getpts($scores[$i]);
			}
			$timeused = $line['endtime']-$line['starttime'];
			$isOvertime = ($timelimit>0) && ($timeused > $timelimit*$line['timelimitmult']);
		}

		if ($line['starttime']==null) {
			if ($aver > 1) {
				$querymap = array(
					'gbmode' => $gbmode,
					'cid' => $cid,
					'uid' => $line['userid'],
					'from' => 'gisolate',
					'aid' => $aid
				);

				echo '<td><a href="' . $assessGbUrl . Sanitize::generateQueryStringFromMap($querymap) . '">-</a></td><td>-</td><td></td>';
			} else {
				$querymap = array(
					'gbmode' => $gbmode,
					'cid' => $cid,
					'asid' => 'new',
					'uid' => $line['userid'],
					'from' => 'gisolate',
					'aid' => $aid
				);

				echo '<td><a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . '">-</a></td><td>-</td><td></td>';
			}
		} else {
			if ($aver > 1) {
				$querymap = array(
					'gbmode' => $gbmode,
					'cid' => $cid,
					'uid' => $line['userid'],
					'from' => 'gisolate',
					'aid' => $aid
				);

				echo '<td><a href="' . $assessGbUrl . Sanitize::generateQueryStringFromMap($querymap) . '">';
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
			}
			//if ($total<$minscore) {
			if (($minscore<10000 && $total<$minscore) || ($minscore>10000 && $total<($minscore-10000)/100*$totalpossible)) {
				echo Sanitize::onlyFloat($total) . "&nbsp;(NC)";
			} else if ($IP==1 && $enddate>$now) {
				echo Sanitize::onlyFloat($total) . "&nbsp;(IP)";
			} else if ($UA==1 && $enddate<$now) {
				echo Sanitize::onlyFloat($total) . "&nbsp;(UA)";
			} else if ($isOvertime) {
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
			if ($aver > 1 ) {
				$hasfeedback = (($line['status']&8) == 8 || $deffeedbacktext !== '');
			} else {
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
			}
			if ($hasfeedback) {
				if ($aver > 1 ) {
					echo '<td><a href="#" class="small feedbacksh pointer" ';
					echo 'onclick="return showfb2('.$aid.','.Sanitize::onlyInt($line['userid']).',\'A2\')">';
					echo _('[Show Feedback]'), '</a></td>';
				} else {
					echo '<td><a href="#" class="small feedbacksh pointer" ';
					echo 'onclick="return showfb('.Sanitize::onlyInt($line['id']).',\'A\')">';
					echo _('[Show Feedback]'), '</a></td>';
				}
			} else {
				echo '<td></td>';
			}
			//echo "<td>{$line['feedback']}</td>";
		}
		echo "</tr>";
	}
	echo '<tr><td>Average</td>';
	if ($aver > 1 ) {
		echo "<td><a href=\"gb-itemanalysis2.php?cid=$cid&aid=$aid&from=gisolate\">";
	} else {
		echo "<td><a href=\"gb-itemanalysis.php?cid=$cid&aid=$aid&from=gisolate\">";
	}
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
	echo "<script> initSortTable('myTable',Array('S','N','N'),true,false);</script>";
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
