<?
//IMathAS:  Adjust all course dates
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
		
		$query = "SELECT startdate,enddate FROM imas_assessments WHERE id='{$_POST['aid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$basedate = mysql_result($result,0,intval($_POST['base']));
		preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$_POST['sdate'],$dmatches);
		$newstamp = mktime(date('G',$basedate),date('i',$basedate),0,$dmatches[1],$dmatches[2],$dmatches[3]);
		$shift = $newstamp-$basedate;
		
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		function shiftsub(&$itema) {
			global $shift;
			foreach ($itema as $k=>$item) {
				if (is_array($item)) {
					$itema[$k]['startdate'] += $shift;
					$itema[$k]['enddate'] += $shift;
					shiftsub($itema[$k]['items']);
				}
			}
		}
		shiftsub($items);
		$itemorder = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		mysql_query($query) or die("Query failed : $query" . mysql_error());
		
		$query = "SELECT itemtype,typeid FROM imas_items WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		while ($row=mysql_fetch_row($result)) {
			if ($row[0]=="InlineText") {
				$table = "imas_inlinetext";
			} else if ($row[0]=="LinkedText") {
				$table = "imas_linkedtext";
			} else if ($row[0]=="Forum") {
				$table = "imas_forums";
			} else if ($row[0]=="Assessment") {
				$table = "imas_assessments";
			}
			$query = "UPDATE $table SET startdate=startdate+$shift WHERE id='{$row[1]}' AND startdate>0";
			mysql_query($query) or die("Query failed : $query" . mysql_error());
			$query = "UPDATE $table SET enddate=enddate+$shift WHERE id='{$row[1]}' AND enddate<2000000000";
			mysql_query($query) or die("Query failed : $query" . mysql_error());
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");

		exit;
	}
	
	$sdate = tzdate("m/d/Y",time());
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; Shift Course Dates</div>\n";	
	echo "<h3>Shift Course Dates</h3>\n";
	echo "<p>This page will change <b>ALL</b> course available dates and due dates based on changing one item.  This is intended ";
	echo "to allow you to reset all course item dates for a new term in one action.</p>\n";
	echo "<form method=post action=\"timeshift.php?cid=$cid\">\n";
	echo "<span class=form>Select an assessment to base the change on</span><span class=formright>\n";
	echo "<select id=aid name=aid>";
	$query = "SELECT id,name from imas_assessments WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<option value=\"{$line['id']}\">{$line['name']}</option>\n";
	}
	echo "</select></span><br class=form>\n";
	echo "<span class=form>Change dates based on this assessment's:</span>";
	echo "<span class=formright><input type=radio id=base name=base value=0 >Available After date<br/>\n";
	echo "<input type=radio id=base name=base value=1 checked=1>Available Until date (Due date) <br/></span><br class=form>\n";
	echo "<script src=\"../javascript/CalendarPopup.js\"></script>\n";
	echo "<SCRIPT LANGUAGE=\"JavaScript\" ID=\"js1\">\n";
	echo "var cal1 = new CalendarPopup();\n";
	echo "</SCRIPT>\n";
	echo "<span class=form>Change date to:</span><span class=formright><input type=text size=10 name=sdate value=\"$sdate\">\n"; 
	echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;\" NAME=\"anchor1\" ID=\"anchor1\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
	echo "</span><br class=form>\n";
	echo "<div class=submit><input type=submit value=\"Change Dates\"></div>\n";
	echo "</form>\n";
	require("../footer.php");
	
	
?>
