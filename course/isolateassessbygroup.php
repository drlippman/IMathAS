<?php
//IMathAS:  Display grade list for one online assessment by group
//(c) 2007 David Lippman
	require("../validate.php");
	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	
	if (!$isteacher) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmode = mysql_result($result,0,0);
	}
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; View Group Scores</div>";
	
	$query = "SELECT minscore,timelimit,deffeedback,enddate,name,defpoints,itemorder,groupsetid FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	list($minscore,$timelimit,$deffeedback,$enddate,$name,$defpoints,$itemorder,$groupsetid) = mysql_fetch_row($result);
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
	echo "Group grades for $name</h2></div>";
	echo "<p>$totalpossible points possible</p>";
	
//	$query = "SELECT iu.LastName,iu.FirstName,istu.section,istu.timelimitmult,";
//	$query .= "ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM imas_assessment_sessions AS ias,imas_users AS iu,imas_students AS istu ";
//	$query .= "WHERE iu.id = istu.userid AND istu.courseid='$cid' AND iu.id=ias.userid AND ias.assessmentid='$aid'";

	$query = "SELECT ias.agroupid,ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime,ias.feedback FROM ";
	$query .= "imas_assessment_sessions AS ias WHERE ias.assessmentid='$aid' GROUP BY ias.agroupid";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$scoredata = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$scoredata[$line['agroupid']] = $line;
	}
	
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	
	echo "<table id=myTable class=gb><thead><tr><th>Group</th>";
	echo "<th>Grade</th><th>%</th><th>Feedback</th></tr></thead><tbody>";
	$now = time();
	$lc = 1;
	$n = 0;
	$tot = 0;
	$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$grpnums = 1;
	while ($row = mysql_fetch_row($result)) {
		if ($row[1] == 'Unnamed group') { 
			$row[1] .= " $grpnums";
			$grpnums++;
			$query = "SELECT iu.FirstName,iu.LastName FROM imas_users AS iu JOIN imas_stugroupmembers AS isgm ";
			$query .= "ON iu.id=isgm.userid AND isgm.stugroupid='{$row[0]}' ORDER BY isgm.id LIMIT 1";
			$r = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($r)>0) {
				$row[1] .= ' ('.mysql_result($r,0,1).', '.mysql_result($r,0,0).' &isin;)';
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
		echo "<td>$gname</td>";
		if (!isset($scoredata[$gid])) {
			echo "<td>-</td><td>-</td><td></td>";
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
			echo "<td><a href=\"gb-viewasid.php?gbmode=$gbmode&cid=$cid&asid=new&uid={$line['userid']}&from=gisolate&aid=$aid\">-</a></td><td>-</td><td></td>";		
		} else {
			echo "<td><a href=\"gb-viewasid.php?gbmode=$gbmode&cid=$cid&asid={$line['id']}&uid={$line['userid']}&from=gisolate&aid=$aid\">";
			//if ($total<$minscore) {
			if (($minscore<10000 && $total<$minscore) || ($minscore>10000 && $total<($minscore-10000)/100*$totalpossible)) {
				echo "{$total}&nbsp;(NC)";
			} else 	if ($IP==1 && $enddate>$now) {
				echo "{$total}&nbsp;(IP)";
			} else	if (($timelimit>0) &&($timeused > $timelimit*$line['timelimitmult'])) {
				echo "{$total}&nbsp;(OT)";
			} else if ($assessmenttype=="Practice") {
				echo "{$total}&nbsp;(PT)";
			} else {
				echo "{$total}";
				$tot += $total;
				$n++;
			}
	
			echo "</a></td>";
			if ($totalpossible>0) {
				echo '<td>'.round(100*($total)/$totalpossible,1).'%</td>';
			} else {
				echo '<td></td>';
			}
			echo "<td>{$line['feedback']}</td>";
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
	if ($totalpossible > 0 ) {
		$pct = round(100*($tot/$n)/$totalpossible,1).'%';
	} else {
		$pct = '';
	}
	echo "</a></td><td>$pct</td></tr>";
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
