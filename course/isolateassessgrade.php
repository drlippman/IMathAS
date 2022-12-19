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
    $now = time();
    
    $stm = $DBH->prepare("SELECT minscore,timelimit,overtime_grace,deffeedback,startdate,enddate,LPcutoff,allowlate,name,itemorder,ver,deffeedbacktext,tutoredit,ptsposs FROM imas_assessments WHERE id=:id AND courseid=:cid");
	$stm->execute(array(':id'=>$aid, ':cid'=>$cid));
	if ($stm->rowCount()==0) {
		echo "Invalid ID";
		exit;
	}
	list($minscore,$timelimit,$overtime_grace,$deffeedback,$startdate,$enddate,$LPcutoff,$allowlate,$name,$itemorder,$aver,$deffeedbacktext,$tutoredit,$totalpossible) = $stm->fetch(PDO::FETCH_NUM);
    if ($istutor && $tutoredit == 2) {  // tutor, no access to view grades
        echo 'No access';
        exit;
    }

	if ($isteacher || ($istutor && ($tutoredit&1) == 1 )) {
		if (isset($_POST['posted']) && $_POST['posted']=="Excuse Grade") {
			$calledfrom='isolateassess';
			include("gb-excuse.php");
		}
		if (isset($_POST['posted']) && $_POST['posted']=="Un-excuse Grade") {
			$calledfrom='isolateassess';
			include("gb-excuse.php");
        }
        if (isset($_POST['submitua'])) {
			require('../assess2/AssessHelpers.php');
			AssessHelpers::submitAllUnsumitted($cid, $aid);
			header(sprintf('Location: %s/course/isolateassessgrade.php?cid=%s&aid=%s&r=%s',
				$GLOBALS['basesiteurl'], $cid, $aid, Sanitize::randomQueryStringParam()));
			exit;
        }
    }
    if ($isteacher || ($istutor && $tutoredit == 3)) {
        if ((isset($_POST['posted']) && $_POST['posted']=="Make Exception") || isset($_GET['massexception'])) {
            $calledfrom='isolateassess';
            $_POST['checked'] = $_POST['stus'] ?? [];
            $_POST['assesschk'] = array($aid);
			include("massexception.php");
        }
	}

	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else if (isset($_SESSION[$cid.'gbmode'])) {
		$gbmode =  $_SESSION[$cid.'gbmode'];
	} else {
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}
	$hidesection = (((floor($gbmode/100000)%10)&1)==1);
	$hidecode = (((floor($gbmode/100000)%10)&2)==2);
	$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
	$includeduedate = (((floor($gbmode/100)%10)&4)==4); //0: hide due date, 4: show due date

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

	$placeinhead = '<script type="text/javascript">
		function showfb(id,type) {
			GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+id, 500, 500);
			return false;
		}
		function showfb2(aid,uid,type) {
			GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+aid+"&uid="+uid, 500, 500);
			return false;
		}
        $(function() {
            $("a[href*=gbviewassess]").each(function() {
                var uid = $(this).closest("tr").find("input").val();
                $(this).attr("data-gtg", uid);
                $(this).closest("tr").find(".pii-full-name").attr("data-gtu", uid);
            });
        });
		</script>';
	require("../header.php");
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
        echo "<a href=\"gradebook.php?gbmode=" . Sanitize::encodeUrlParam($gbmode) . "&cid=$cid\">Gradebook</a> &gt; ";
    }
    echo _('View Scores').'</div>';

	if ($aver > 1 ) {
		echo '<div class="cpmid"><a href="gb-itemanalysis2.php?cid='.$cid.'&amp;aid='.$aid.'">View Item Analysis</a></div>';
	} else {
		echo '<div class="cpmid"><a href="gb-itemanalysis.php?cid='.$cid.'&amp;aid='.$aid.'">View Item Analysis</a></div>';
	}
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
        if (empty($tutorsection)) {
            $stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=:courseid AND section IS NOT NULL AND section<>'' ORDER BY section");
			$stm->execute(array(':courseid'=>$cid));
            $sectionnames = $stm->fetchAll(PDO::FETCH_COLUMN,0);
        }
	} else {
		$sortorder = "name";
	}

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
	
	echo '<div id="headerisolateassessgrade" class="pagetitle"><h1>';
	echo "Grades for " . Sanitize::encodeStringForDisplay($name) . "</h1></div>";

    echo "<p>$totalpossible "._('points possible').'. ';
    if ($hassection && empty($tutorsection)) {
        echo _('Section').': ';
        echo '<select id="secfiltersel" onchange="chgsecfilter(this)">';
        echo '<option value="-1"' . ($secfilter == -1 ? ' selected' : '') . '>';
        echo _('All') . '</option>';
        foreach ($sectionnames as $secname) {
            echo  '<option value="' . Sanitize::encodeStringForDisplay($secname) . '"';
            if ($secname==$secfilter) {
                echo  ' selected';
            }
            echo  '>' . Sanitize::encodeStringForDisplay($secname) . '</option>';
        }
        echo '</select>';
        echo '<script type="text/javascript">
        function chgsecfilter(el) {
            var sec = el.value;
            var toopen = "isolateassessgrade.php?cid='.$cid.'&aid='.$aid.'&secfilter=" + encodeURIComponent(sec);
            window.location = toopen;
        }
        </script>';
    }
    echo '</p>';

	

//	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
//	$query .= "ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM imas_assessment_sessions AS ias,imas_users AS iu,imas_students AS istu ";
//	$query .= "WHERE iu.id = istu.userid AND istu.courseid='$cid' AND iu.id=ias.userid AND ias.assessmentid='$aid'";

	//get exceptions
	$stm = $DBH->prepare("SELECT userid,startdate,enddate,islatepass FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
	$stm->execute(array(':assessmentid'=>$aid));
	$exceptions = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$exceptions[$row[0]] = array($row[1],$row[2],$row[3]);
	}
	if (count($exceptions)>0) {
		require_once("../includes/exceptionfuncs.php");
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, !$isteacher && !$istutor);
	}
	//get excusals
	$stm = $DBH->prepare("SELECT userid FROM imas_excused WHERE type='A' AND typeid=:assessmentid");
	$stm->execute(array(':assessmentid'=>$aid));
	$excused = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$excused[$row[0]] = 1;
	}
	if ($aver>1) {
		$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.code,istu.timelimitmult,";
		$query .= "IF((iar.status&1)=1,iar.scoreddata,'') AS scoreddata,";
		$query .= "istu.userid,iar.score,iar.starttime,iar.lastchange,iar.timeontask,iar.status,iar.timelimitexp,istu.locked FROM imas_users AS iu JOIN imas_students AS istu ON iu.id = istu.userid AND istu.courseid=:courseid ";
		$query .= "LEFT JOIN imas_assessment_records AS iar ON iu.id=iar.userid AND iar.assessmentid=:assessmentid WHERE istu.courseid=:courseid2 ";
	} else {
		$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.code,istu.timelimitmult,";
		$query .= "ias.id,istu.userid,ias.bestscores,ias.starttime,ias.endtime,ias.timeontask,ias.feedback,istu.locked FROM imas_users AS iu JOIN imas_students AS istu ON iu.id = istu.userid AND istu.courseid=:courseid ";
		$query .= "LEFT JOIN imas_assessment_sessions AS ias ON iu.id=ias.userid AND ias.assessmentid=:assessmentid WHERE istu.courseid=:courseid2 ";
	}
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
	$stm = $DBH->prepare($query);
	if ($secfilter != -1) {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid, ':courseid2'=>$cid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid, ':courseid2'=>$cid));
	}
	$lines = array();
	$hasUA = 0;
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$line['useexception'] = false;
		if (isset($exceptions[$line['userid']])) {
			$line['useexception'] = $exceptionfuncs->getCanUseAssessException($exceptions[$line['userid']], array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'LPcutoff'=>$LPcutoff), true);
			if ($line['useexception']) {
				$line['thisenddate'] = $exceptions[$line['userid']][1];
			} else {
                $line['thisenddate'] = $enddate;
            }
		} else {
			$line['thisenddate'] = $enddate;
		}
		$lines[] = $line;
		if ($aver > 1 && ($line['status']&1)>0) {
			// identify as unsubmitted if past due, or time limit is expired
			$data = json_decode(gzdecode($line['scoreddata']), true);
            if (abs($timelimit) > 0) {
			    $time_exp = $data['assess_versions'][count($data['assess_versions'])-1]['timelimit_end'];
            }
			if ($now > $line['thisenddate'] ||
				(abs($timelimit) > 0 && $now > $time_exp + $overtime_grace * $line['timelimitmult'])
			) {
				$hasUA++;
			}
		}
	}

	echo '<form method="post" id="sform" action="isolateassessgrade.php?cid='.$cid.'&aid='.$aid.'">';

	if ($hasUA > 0) {
		echo '<p>',_('One or more students has unsubmitted assessment attempts.');
		echo ' <button type="submit" name="submitua" value="submitua">',_('Submit Now'),'</button>';
		echo '<br/><span class=small>',_('This will only submit the assignment if it is past due or the time limit has expired.');
		echo '</span></p>';
	}

    echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";
    
    
    if ($isteacher || ($istutor && ($tutoredit&1) == 1)) {
        echo '<p>'._('Check').': <a href="#" onclick="return chkAllNone(\'sform\',\'stus[]\',true)">'._('All').'</a> ';
        echo '<a href="#" onclick="return chkAllNone(\'sform\',\'stus[]\',false)">'.('None').'</a>. ';
        echo _('With selected:');
        echo ' <button type="submit" value="Excuse Grade" name="posted" onclick="return confirm(\'Are you sure you want to excuse these grades?\')">',_('Excuse Grade'),'</button> ';
        echo ' <button type="submit" value="Un-excuse Grade" name="posted" onclick="return confirm(\'Are you sure you want to un-excuse these grades?\')">',_('Un-excuse Grade'),'</button> ';
        if ($isteacher || ($istutor && $tutoredit == 3)) {
            echo ' <button type="submit" value="Make Exception" name="posted">',_('Make Exception'),'</button> ';
        }
        echo '</p>';
    }

	echo "<table id=myTable class=gb><thead><tr><th>Name</th>";
	if ($hassection && !$hidesection) {
		echo '<th>Section</th>';
	}
	if ($hascodes && !$hidecode) {
		echo '<th>Code</th>';
	}
	echo "<th>Grade</th><th>%</th><th>Last Change</th>";
	if ($includeduedate) {
		echo "<th>Due Date</th>";
	}
	echo "<th>Time Spent (In Questions)</th><th>Feedback</th></tr></thead><tbody>";

	if (!empty($CFG['assess2-use-vue-dev'])) {
		$assessGbUrl = sprintf("%s/gbviewassess.html?", $CFG['assess2-use-vue-dev-address']);
	} else {
		$assessGbUrl = "../assess2/gbviewassess.php?";
	}

	$lc = 1;
	$n = 0;
	$ntime = 0;
	$tot = 0;
	$tottime = 0;
	$tottimeontask = 0;
	foreach ($lines as $line) {
		if ($aver==1) {
			$line['lastchange'] = $line['endtime'];
		}
		if ($lc%2!=0) {
			echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">";
		} else {
			echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">";
		}
		$lc++;
		echo '<td><input type=checkbox name="stus[]" value="'.Sanitize::onlyInt($line['userid']).'"> ';
		if ($line['locked']>0) {
			echo '<span style="text-decoration: line-through;">';
			printf("<span class='pii-full-name'>%s, %s</span></span>",
				Sanitize::encodeStringForDisplay($line['LastName']),
				Sanitize::encodeStringForDisplay($line['FirstName']));
		} else {
			printf("<span class='pii-full-name'>%s, %s</span>",
				Sanitize::encodeStringForDisplay($line['LastName']),
				Sanitize::encodeStringForDisplay($line['FirstName']));
		}
		echo '</td>';
		if ($hassection && !$hidesection) {
			printf("<td>%s</td>", Sanitize::encodeStringForDisplay($line['section']));
		}
		if ($hascodes && !$hidecode) {
			if ($line['code']==null) {$line['code']='';}
			printf("<td>%s</td>", Sanitize::encodeStringForDisplay($line['code']));
		}
		if ($aver>1) {
			$total = $line['score'];
			$timeused = $line['lastchange'] - $line['starttime'];
			$timeontask = round($line['timeontask']/60,1);
            // don't display OT marker anymore for new assess
            //$isOvertime = ($line['status']&4) == 4;
            $IP = 0;
            $UA = 0;
            if (($line['status']&1)>0 && ($line['thisenddate']<$now ||  //unsubmitted by-assess, and due date passed
                ($line['timelimitexp']>0 && $line['timelimitexp']<$now)) // or time limit expired on last att
            ) {
                $UA=1;
            } else if (($line['status']&3)>0 && $line['thisenddate']>$now && // unsubmitted attempt any mode and before due date
                ($line['timelimitexp']==0 || $line['timelimitexp']>$now) // and time limit not expired
            ) {
                $IP=1;
            }
			//$IP = ($line['status']&3)>0;
			//$UA = ($line['status']&1)>0;
		} else {
			$total = 0;
			$sp = explode(';',$line['bestscores']);
			$scores = explode(",",$sp[0]);
			if (in_array(-1,$scores)) { $IP=1;} else {$IP=0;}
			for ($i=0;$i<count($scores);$i++) {
				$total += getpts($scores[$i]);
			}
			$timeused = $line['endtime']-$line['starttime'];
			$timeontask = round(array_sum(explode(',',str_replace('~',',',$line['timeontask'])))/60,1);
			$isOvertime = ($timelimit>0) && ($timeused > $timelimit*$line['timelimitmult']);
			$UA = 0;
		}

		if ($line['starttime']===null) {
			if ($aver > 1) {
				$querymap = array(
					'gbmode' => $gbmode,
					'cid' => $cid,
					'uid' => $line['userid'],
					'from' => 'isolate',
					'aid' => $aid
				);

				echo '<td><a href="' . $assessGbUrl . Sanitize::generateQueryStringFromMap($querymap) . '">-</a>';
			} else {
				$querymap = array(
					'gbmode' => $gbmode,
					'cid' => $cid,
					'asid' => 'new',
					'uid' => $line['userid'],
					'from' => 'isolate',
					'aid' => $aid
				);

				echo '<td><a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . '">-</a>';
			}
			if ($line['useexception']) {
				if ($exceptions[$line['userid']][2]>0) {
					echo '<sup>LP</sup>';
				} else {
					echo '<sup>e</sup>';
				}
			}
			if (!empty($excused[$line['userid']])) {
				echo '<sup>x</sup>';
			}
			echo "</td><td>-</td><td></td>";
			if ($includeduedate) {
				echo '<td>'.tzdate("n/j/y g:ia", $line['thisenddate']).'</td>';
			}
			echo "<td></td><td></td>";
		} else {
			if ($aver > 1) {
				$querymap = array(
					'gbmode' => $gbmode,
					'cid' => $cid,
					'uid' => $line['userid'],
					'from' => 'isolate',
					'aid' => $aid
				);

				echo '<td><a href="' . $assessGbUrl . Sanitize::generateQueryStringFromMap($querymap) . '">';
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
			}
			if ($line['thisenddate'] > $now) {
				echo '<i>'.Sanitize::onlyFloat($total);
			} else {
				echo Sanitize::onlyFloat($total);
			}
			//if ($total<$minscore) {
			if (($minscore<10000 && $total<$minscore) || ($minscore>10000 && $total<($minscore-10000)/100*$totalpossible)) {
				echo "&nbsp;(NC)";
			} else 	if ($IP==1) {
				echo "&nbsp;(IP)";
			} else 	if ($UA==1) {
				echo "&nbsp;(UA)";
			} else	if (!empty($isOvertime)) {
				echo "&nbsp;(OT)";
			} else if ($assessmenttype=="Practice") {
				echo "&nbsp;(PT)";
			} else {
				$tot += $total;
				$n++;
			}
			if ($line['thisenddate'] > $now) {
				echo '</i>';
			}
			echo '</a>';
			if ($line['useexception']) {
				if ($exceptions[$line['userid']][2]>0) {
					echo '<sup>LP</sup>';
				} else {
					echo '<sup>e</sup>';
				}
			}
			if (!empty($excused[$line['userid']])) {
				echo '<sup>x</sup>';
			}
			echo '</td>';
			if ($totalpossible>0) {
				echo '<td>'.round(100*($total)/$totalpossible,1).'%</td>';
			} else {
				echo '<td>&nbsp;</td>';
			}
			if ($line['lastchange']==0) {
				if ($line['starttime']==0) {
					echo '<td>Never started</td>';
				} else {
					echo '<td>Never submitted</td>';
				}
			} else {
				echo '<td>'.tzdate("n/j/y g:ia",$line['lastchange']).'</td>';
			}
			if ($includeduedate) {
				echo '<td>'.tzdate("n/j/y g:ia", $line['thisenddate']).'</td>';
			}
			if ($line['lastchange']==0 || $line['starttime']==0) {
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
			//TODO: FINISH CHANGES
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
		}
		echo "</tr>";
	}
	echo '<tr><td>Average</td>';
	if ($hassection && !$hidesection) {
		echo '<td></td>';
	}
	if ($aver > 1 ) {
		echo "<td><a href=\"gb-itemanalysis2.php?cid=$cid&aid=$aid&from=isolate\">";
	} else {
		echo "<td><a href=\"gb-itemanalysis.php?cid=$cid&aid=$aid&from=isolate\">";
	}
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
	echo "</a></td><td>$pct</td><td></td>";
    if ($includeduedate) {
        echo '<td></td>';
    }
    echo "<td>$timeavg</td><td></td></tr>";
	echo "</tbody></table>";
	
	if ($includeduedate) {
        $duedatesort = ",'D'";
    } else {
        $duedatesort = '';
    }
	if ($hassection && !$hidesection && $hascodes && !$hidecode) {
		echo "<script> initSortTable('myTable',Array('S','S','S','N','P','D'$duedatesort,'N','S'),true,false);</script>";
	} else if ($hassection && !$hidesection) {
		echo "<script> initSortTable('myTable',Array('S','S','N','P','D'$duedatesort,'N','S'),true,false);</script>";
	} else {
		echo "<script> initSortTable('myTable',Array('S','N','P','D'$duedatesort,'N','S'),true,false);</script>";
	}
	echo "<p>Meanings:  <i>italics</i>-available to student, IP-In Progress (some questions unattempted), UA-Unsubmitted attempt, OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/>";
	echo "<sup>e</sup> Has exception, <sup>x</sup> Excused grade, <sup>LP</sup> Used latepass  </p>\n";
	echo '</form>';
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
