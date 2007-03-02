<?
//IMathAS:  Make deadline exceptions for a student
//(c) 2006 David Lippman
	require("../validate.php");
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	
	if (isset($_POST['sdate'])) {
		require_once("parsedatetime.php");
		$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		
		//check if exception already exists
		$query = "SELECT id FROM imas_exceptions WHERE userid='{$_GET['uid']}' AND assessmentid='{$_GET['aid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($row != null) {
			$query = "UPDATE imas_exceptions SET startdate=$startdate,enddate=$enddate WHERE id='{$row[0]}'";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
		} else {
			$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate) VALUES ";
			$query .= "('{$_GET['uid']}','{$_GET['aid']}',$startdate,$enddate)";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
		
	} else if (isset($_GET['clear'])) {
		$query = "DELETE FROM imas_exceptions WHERE id='{$_GET['clear']}'";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
	} else {
		$pagetitle = "Make Exception";
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt Make Exception</div>\n";	
		echo "<h3>Make Start/Due Date Exception</h3>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "function nextpage() {\n";
		echo "   var aid = document.getElementById('aidselect').value;\n";
		$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/exception.php?cid={$_GET['cid']}&uid={$_GET['uid']}";
		echo "   var togo = '$address&aid='+aid; \n";
		echo "   window.location = togo;\n";
		echo "}\n";
		echo "</script>\n";
		echo "<select id=\"aidselect\" onchange=\"nextpage()\">\n";
		echo "<option value=\"\">Select an Assessment</option>\n";
		$query = "SELECT id,name from imas_assessments WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo "<option value=\"{$line['id']}\" ";
			if (isset($_GET['aid']) && ($_GET['aid']==$line['id'])) {echo "SELECTED";}
			echo ">{$line['name']}</option>\n";
		}
		echo "</select>\n";
		if (isset($_GET['aid']) && $_GET['aid']!='') {
			$query = "SELECT startdate,enddate FROM imas_assessments WHERE id='{$_GET['aid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$sdate = tzdate("m/d/Y",$row[0]);
			$edate = tzdate("m/d/Y",$row[1]);
			$stime = tzdate("g:i a",$row[0]);
			$etime = tzdate("g:i a",$row[1]);
			
			
			//check if exception already exists
			$query = "SELECT id,startdate,enddate FROM imas_exceptions WHERE userid='{$_GET['uid']}' AND assessmentid='{$_GET['aid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$erow = mysql_fetch_row($result);
			if ($erow != null) {
				echo "<p>Exception exists.  <a href=\"exception.php?cid=$cid&aid={$_GET['aid']}&uid={$_GET['uid']}&clear={$erow[0]}\">Clear Exception</a></p>\n";
				$sdate = tzdate("m/d/Y",$erow[1]);
				$edate = tzdate("m/d/Y",$erow[2]);
				$stime = tzdate("g:i a",$erow[1]);
				$etime = tzdate("g:i a",$erow[2]);
			}	
			
			echo "<form method=post action=\"exception.php?cid=$cid&aid={$_GET['aid']}&uid={$_GET['uid']}\">\n";
			echo "<script src=\"../javascript/CalendarPopup.js\"></script>\n";
			echo "<SCRIPT LANGUAGE=\"JavaScript\" ID=\"js1\">\n";
			echo "var cal1 = new CalendarPopup();\n";
			echo "</SCRIPT>\n";
			echo "<span class=form>For this student:</span><br class=form>\n";
			echo "<span class=form>Available After:</span><span class=formright><input type=text size=10 name=sdate value=\"$sdate\">\n"; 
			echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;\" NAME=\"anchor1\" ID=\"anchor1\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
			echo "at <input type=text size=10 name=stime value=\"$stime\"></span><BR class=form>\n";

			echo "<span class=form>Available Until:</span><span class=formright><input type=text size=10 name=edate value=\"$edate\">\n"; 
			echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].edate,'anchor2','MM/dd/yyyy',(document.forms[0].sdate.value=='$sdate')?(document.forms[0].edate.value):(document.forms[0].sdate.value)); return false;\" NAME=\"anchor2\" ID=\"anchor2\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
			echo "at <input type=text size=10 name=etime value=\"$etime\"></span><BR class=form>\n";

			echo "<div class=submit><input type=submit value=\"Submit\"></div></form>\n";
		}
		
		require("../footer.php");
	}
