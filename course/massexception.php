<?php
//IMathAS:  Make deadline exceptions for a multiple students; included by listusers and gradebook
//(c) 2007 David Lippman

	if (!isset($imasroot)) {
		echo "This file cannot be called directly";
		exit;
	}


	$pagetitle = "Manage Exceptions";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	if ($calledfrom=='lu') {
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt; Manage Exceptions</div>\n";
	} else if ($calledfrom=='gb') {
		echo "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; Manage Exceptions</div>\n";
	}
	
	echo "<h3>Manage Exceptions</h3>\n";
	if ($calledfrom=='lu') {
		echo "<form method=post action=\"listusers.php?cid=$cid&massexception=1\">\n";
	} else if ($calledfrom=='gb') {
		echo "<form method=post action=\"gradebook.php?cid=$cid&massexception=1\">\n";
	}
	
	if (isset($_POST['tolist'])) {
		$_POST['checked'] = explode(',',$_POST['tolist']);
	}
	echo "<input type=hidden name=\"tolist\" value=\"" . implode(',',$_POST['checked']) . "\">\n";
	$tolist = "'".implode("','",$_POST['checked'])."'";
	
	$isall = false;
	if (isset($_POST['ca'])) {
		$isall = true;
		echo "<input type=hidden name=\"ca\" value=\"1\"/>";
	}
	
	echo "<h4>Existing Exceptions</h4>";
	
	$query = "SELECT ie.id,iu.LastName,iu.FirstName,ia.name,iu.id,ia.id FROM imas_exceptions AS ie,imas_users AS iu,imas_assessments AS ia ";
	$query .= "WHERE ie.assessmentid=ia.id AND ie.userid=iu.id AND ia.courseid='$cid' AND iu.id IN ($tolist) ";
	if ($isall) {
		$query .= "ORDER BY ia.name,iu.LastName,iu.FirstName";
	} else {
		$query .= "ORDER BY iu.LastName,iu.FirstName,ia.name";
	}
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	echo '<ul>';
	if ($isall) {
		$lasta = 0;
		while ($row = mysql_fetch_row($result)) {
			if ($lasta!=$row[5]) {
				if ($lasta!=0) {
					echo "</ul></li>";
				}
				echo "<li>{$row[3]} <ul>";
			}
			echo "<li><input type=checkbox name=\"clears[]\" value=\"{$row[0]}\" />{$row[1]}, {$row[2]}</li>";
		}
		echo "</ul></li>";
	} else {
		$lasts = 0;
		while ($row = mysql_fetch_row($result)) {
			if ($lasts!=$row[4]) {
				if ($lasts!=0) {
					echo "</ul></li>";
				}
				echo "<li>{$row[1]}, {$row[2]} <ul>";
			}
			echo "<li><input type=checkbox name=\"clears[]\" value=\"{$row[0]}\" />{$row[3]}</li>";
		}
		echo "</ul></li>";
	}
	echo '</ul>';
	
	echo "<input type=submit value=\"Record Changes\" />";
	
	echo "<h4>Make New Exception</h4>";
	
	echo "<script src=\"../javascript/CalendarPopup.js\"></script>\n";
	echo "<SCRIPT LANGUAGE=\"JavaScript\" ID=\"js1\">\n";
	echo "var cal1 = new CalendarPopup();\n";
	echo "</SCRIPT>\n";
	
	$now = time();
	$wk = $now + 7*24*60*60;
	$sdate = tzdate("m/d/Y",$now);
	$edate = tzdate("m/d/Y",$wk);
	$stime = tzdate("g:i a",$now);
	$etime = tzdate("g:i a",$wk);
	echo "<span class=form>Available After:</span><span class=formright><input type=text size=10 name=sdate value=\"$sdate\">\n"; 
	echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;\" NAME=\"anchor1\" ID=\"anchor1\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
	echo "at <input type=text size=10 name=stime value=\"$stime\"></span><BR class=form>\n";

	echo "<span class=form>Available Until:</span><span class=formright><input type=text size=10 name=edate value=\"$edate\">\n"; 
	echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].edate,'anchor2','MM/dd/yyyy',(document.forms[0].sdate.value=='$sdate')?(document.forms[0].edate.value):(document.forms[0].sdate.value)); return false;\" NAME=\"anchor2\" ID=\"anchor2\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
	echo "at <input type=text size=10 name=etime value=\"$etime\"></span><BR class=form>\n";
	
	echo "Set Exception for:";
	echo "<ul>";
	$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<li><input type=checkbox name=\"addexc[]\" value=\"{$row[0]}\" />{$row[1]}</li>";
	}
	echo '</ul>';
	echo "<input type=submit value=\"Record Changes\" />";
	
	echo "<h4>Students Selected</h4>";
	$query = "SELECT LastName,FirstName FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	echo "<ul>";
	while ($row = mysql_fetch_row($result)) {
		echo "<li>{$row[0]}, {$row[1]}</li>";
	}
	echo '</ul>';
	echo '</form>';
	require("../footer.php");
	exit;
		

?>
