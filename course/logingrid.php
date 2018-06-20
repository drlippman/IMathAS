<?php
//IMathAS:  Grid view of login log
//(c) 2013 David Lippman for Lumen Learning

require("../init.php");


$cid = Sanitize::courseId($_GET['cid']);
if (isset($_GET['secfilter'])) {
	$secfilter = $_GET['secfilter'];
	$sessiondata[$cid.'secfilter'] = $secfilter;
	writesessiondata();
} else if (isset($sessiondata[$cid.'secfilter'])) {
	$secfilter = $sessiondata[$cid.'secfilter'];
} else {
	$secfilter = -1;
}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Login Grid";

$overwriteBody = 0;
$body = "";

if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$now = time();
	if (isset($_POST['daterange'])) {
		require("../includes/parsedatetime.php");
		$start = parsedatetime($_POST['sdate'],'12:00am');
		$end = parsedatetime($_POST['edate'],'11:59pm');
		if (($end-$start)/86400>365) {
			$start = $end-365*24*60*60;
		}
	} else if (isset($_GET['start']) && $_GET['start']+7*24*60*60<=$now) {
		$start = intval($_GET['start']);
		$end = $start + 7*24*60*60;
	} else {
		$end = $now;
		$start = 86400*ceil(($now-$tzoffset*60)/86400)+$tzoffset*60-7*24*60*60;
	}
	$starttime = tzdate("M j, Y, g:i a", $start);
	$endtime = tzdate("M j, Y, g:i a", $end);
	$sdate = tzdate("m/d/Y",$start);
	$edate = tzdate("m/d/Y",$end);

	$logins = array();
	//DB $query = "SELECT userid,logintime FROM imas_login_log WHERE courseid='$cid' AND logintime>=$start AND logintime<=$end";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT userid,logintime FROM imas_login_log WHERE courseid=:courseid AND logintime>=:start AND logintime<=:end");
	$stm->execute(array(':courseid'=>$cid, ':start'=>$start, ':end'=>$end));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (!isset($logins[$row[0]])) { $logins[$row[0]] = array(); }
		$day = floor(($row[1] - $start)/86400);
		if (!isset($logins[$row[0]][$day])) {
			$logins[$row[0]][$day] = 1;
		} else {
			$logins[$row[0]][$day]++;
		}
	}

	$dates = array();
	for ($time=$start;$time<$end;$time+=86400) {
		$dates[] = tzdate("n/d",$time);
	}

	$stus = array();
	//DB $query = "SELECT iu.LastName,iu.FirstName,iu.id FROM imas_users as iu JOIN imas_students ON iu.id=imas_students.userid ";
	//DB $query .= "WHERE imas_students.courseid='$cid' ORDER BY iu.LastName,iu.FirstName";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query = "SELECT iu.LastName,iu.FirstName,iu.id FROM imas_users as iu JOIN imas_students ON iu.id=imas_students.userid ";
	$query .= "WHERE imas_students.courseid=:courseid ORDER BY iu.LastName,iu.FirstName";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stus[] = array($row[0].', '.$row[1], $row[2]);
	}

} //END DATA MANIPULATION

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<style type="text/css"> table.logingrid td {text-align: center; border-right:1px solid #ccc;} table.logingrid td.left {text-align: left;}</style>';
require("../header.php");
if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerlogingrid" class="pagetitle"><h1>Login Grid View</h1></div>

	<form method="post" action="logingrid.php?cid=<?php echo $cid;?>">
	<p>Showing Number of Logins <?php echo "$starttime through $endtime";?></p>
	<p>
<?php
	echo '<a href="logingrid.php?cid='.$cid.'&start='.($start-7*24*60*60).'">Show previous week</a>. ';
	if ($end<$now) {
		echo '<a href="logingrid.php?cid='.$cid.'&start='.($start+7*24*60*60).'">Show following week</a>. ';
	}
?>
	Show <input type="text" size="10" name="sdate" value="<?php echo $sdate;?>">
	<a href="#" onClick="displayDatePicker('sdate', this); return false">
	<img src="../img/cal.gif" alt="Calendar"/></a> through
	<input type="text" size="10" name="edate" value="<?php echo $edate;?>">
	<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
	<img src="../img/cal.gif" alt="Calendar"/></a>
	<input type="submit" name="daterange" value="Go"/></p>
	</form>

	<table class="gb logingrid" id="myTable">
	<thead>
	<tr>
	<th>Name</th>
<?php
	foreach ($dates as $date) {
		echo '<th>'.$date.'</th>';
	}
?>
	</tr></thead>
	<tbody>
<?php
	$alt = 0;
	$n = count($dates);
	foreach ($stus as $stu) {
		if ($alt==0) {echo '<tr class="even">'; $alt=1;} else {echo '<tr class="odd">'; $alt=0;}
		echo '<td class="left"><a href="viewloginlog.php?cid='.$cid.'&uid='.Sanitize::onlyInt($stu[1]).'">'.Sanitize::encodeStringForDisplay($stu[0]).'</a></td>';
		for ($i=0;$i<$n;$i++) {
			echo '<td>';
			if (isset($logins[$stu[1]][$i])) {
				echo $logins[$stu[1]][$i];
			} else {
				echo ' ';
			}
			echo '</td>';
		}
		echo '<tr/>';
	}
?>
	</tbody>
	</table>
	<p>Note:  Be aware that login sessions last for 24 hours, so if a student logins in Wednesday at 7pm and never
	closes their browser, they can continue using the same session on the same computer until 7pm Thursday.</p>
<?php
}
require("../footer.php");
?>
