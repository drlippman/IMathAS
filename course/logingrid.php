<?php
//IMathAS:  Grid view of login log
//(c) 2013 David Lippman for Lumen Learning

require("../init.php");


$cid = Sanitize::courseId($_GET['cid']);
if (isset($_GET['secfilter'])) {
	$secfilter = $_GET['secfilter'];
	$_SESSION[$cid.'secfilter'] = $secfilter;
} else if (isset($_SESSION[$cid.'secfilter'])) {
	$secfilter = $_SESSION[$cid.'secfilter'];
} else {
	$secfilter = -1;
}
if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
    $gbmode = $_GET['gbmode'];
    $_SESSION[$cid.'gbmode'] = $gbmode;
} else if (isset($_SESSION[$cid.'gbmode']) && !isset($_GET['refreshdef'])) {
    $gbmode =  $_SESSION[$cid.'gbmode'];
} else {
    $stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
    $stm->execute(array(':courseid'=>$cid));
    $gbmode = $stm->fetchColumn(0);
    $_SESSION[$cid.'gbmode'] = $gbmode;
}
$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked

$curBreadcrumb = $breadcrumbbase;
if (empty($_COOKIE['fromltimenu'])) {
    $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
$curBreadcrumb .= "<a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Login Grid";

$overwriteBody = 0;
$body = "";

if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$now = time();
	if (isset($_REQUEST['daterange'])) {
		require("../includes/parsedatetime.php");
		$start = parsedatetime($_REQUEST['sdate'],'12:00am');
		$end = parsedatetime($_REQUEST['edate'],'11:59pm');
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
    $downloadqs = "&download=true&daterange=go&sdate=".Sanitize::encodeUrlParam($sdate)."&edate=".Sanitize::encodeUrlParam($edate);

	$logins = array();
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
	$query = "SELECT iu.LastName,iu.FirstName,iu.id,imas_students.locked FROM imas_users as iu JOIN imas_students ON iu.id=imas_students.userid ";
    $query .= "WHERE imas_students.courseid=:courseid ";
    $query .= "ORDER BY iu.LastName,iu.FirstName";
	$stm = $DBH->prepare($query);
    $stm->execute(array(':courseid'=>$cid));
    $haslocked = false;
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        $stus[] = array($row[0].', '.$row[1], $row[2], $row[3]);
        if ($row[3]>0) { 
            $haslocked = true;
        }
	}
} //END DATA MANIPULATION

/******* begin html output ********/
if (isset($_GET['download'])) {
	header('Content-type: text/csv');
	header("Content-Disposition: attachment; filename=\"logingrid-$cid.csv\"");
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	echo _('Name');
	foreach ($dates as $date) {
		echo ','.$date;
	}
	echo "\n";
	$n = count($dates);
	foreach ($stus as $stu) {
		echo '"'.str_replace('"', '""', strip_tags($stu[0])).'"';
		for ($i=0;$i<$n;$i++) {
			echo ',';
			if (isset($logins[$stu[1]][$i])) {
				echo $logins[$stu[1]][$i];
			} else {
				echo 0;
			}
		}
		echo "\n";
	}
	exit;
}
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<style type="text/css"> table.logingrid td {text-align: center; border-right:1px solid #ccc;} table.logingrid td.left {text-align: left;}</style>';
require("../header.php");
if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerlogingrid" class="pagetitle"><h1>Login Grid View</h1></div>

	<div class="cpmid">
		<a href="logingrid.php?cid=<?php echo $cid . $downloadqs;?>">Download as CSV</a>
	</div>

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
	<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a> through
	<input type="text" size="10" name="edate" value="<?php echo $edate;?>">
	<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
	<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
	<input type="submit" name="daterange" value="Go"/></p>
    </form>
<?php
if ($haslocked) {
    echo '<p>';
    if ($hidelocked) {
        $newgbmode = $gbmode - 100;
        echo '<a href="logingrid.php?cid='.$cid.'&gbmode='.$newgbmode.'">'._('Show locked students').'</a>';
    } else {
        $newgbmode = $gbmode + 100;
        echo '<a href="logingrid.php?cid='.$cid.'&gbmode='.$newgbmode.'">'._('Hide locked students').'</a>';
    }
    echo '</p>';
}
?>

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
        if ($hidelocked && $stu[2] > 0) { continue;}
		if ($alt==0) {echo '<tr class="even">'; $alt=1;} else {echo '<tr class="odd">'; $alt=0;}
        echo '<td class="left"><a href="viewloginlog.php?cid='.$cid.'&uid='.Sanitize::onlyInt($stu[1]).'">';
        if ($stu[2] > 0) {
            echo '<span class="greystrike pii-full-name">'.Sanitize::encodeStringForDisplay($stu[0]).'</span>';
        } else {
            echo '<span class="pii-full-name">'.Sanitize::encodeStringForDisplay($stu[0]).'</span>';
        }
        echo '</a></td>';
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
