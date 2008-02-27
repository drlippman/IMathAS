<?php
//IMathAS:  Display grade list for one online assessent
//(c) 2007 David Lippman
	require("../validate.php");
	$isteacher = isset($teacherid);
	if (!(isset($teacherid))) {
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
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; View Scores</div>";
	
	
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
	
	$query = "SELECT minscore,timelimit,deffeedback,enddate,name FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	list($minscore,$timelimit,$deffeedback,$enddate,$name) = mysql_fetch_row($result);
	$deffeedback = explode('-',$deffeedback);
	$assessmenttype = $deffeedback[0];
	
	echo "<h3>Grades for $name</h3>";
	
	$query = "SELECT iu.LastName,iu.FirstName,istu.section,";
	$query .= "ias.id,ias.userid,ias.bestscores,ias.starttime,ias.endtime FROM imas_assessment_sessions AS ias,imas_users AS iu,imas_students AS istu ";
	$query .= "WHERE iu.id = istu.userid AND istu.courseid='$cid' AND iu.id=ias.userid AND ias.assessmentid='$aid'";
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
	echo "<th>Grade</th></tr></thead><tbody>";
	$now = time();
	$lc = 1;
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($lc%2!=0) {
			echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">"; 
		} else {
			echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">"; 
		}
		$lc++;
		echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
		if ($hassection) {
			echo "<td>{$line['section']}</td>";
		}
		$total = 0;
		$scores = explode(",",$line['bestscores']);
		if (in_array(-1,$scores)) { $IP=1;} else {$IP=0;}
		for ($i=0;$i<count($scores);$i++) {
			$total += getpts($scores[$i]);
		}
		$timeused = $line['endtime']-$line['starttime'];
		
		echo "<td><a href=\"gb-viewasid.php?gbmode=$gbmode&cid=$cid&asid={$line['id']}&uid={$line['userid']}&from=isolate&aid=$aid\">";
		if ($total<$minscore) {
			echo "{$total}&nbsp;(NC)";
		} else 	if ($IP==1 && $enddate>$now) {
			echo "{$total}&nbsp;(IP)";
		} else	if (($timelimit>0) &&($timeused > $timelimit)) {
			echo "{$total}&nbsp;(OT)";
		} else if ($assessmenttype=="Practice") {
			echo "{$total}&nbsp;(PT)";
		} else {
			echo "{$total}";
		}

		echo "</a></td></tr>";
	}
	
	echo "</tbody></table>";
	if ($hassection) {
		echo "<script> initSortTable('myTable',Array('S','S','N'),true);</script>";
	} else {
		echo "<script> initSortTable('myTable',Array('S','N'),true);</script>";
	}
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
