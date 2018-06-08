<?php
//IMathAS:  Course Recent Report
//(c) 2016 David Cooper, David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Course Reports";
$cid = Sanitize::courseId($_GET['cid']);

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {

	$curBreadcrumb .= "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	$curname = Sanitize::encodeStringForDisplay($coursename);
	echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Course Reports</div>';
	echo '<div class="pagetitle"><h1>Course Reports</h1></div>';
	echo '<ul class="nomark">';
	echo '<li><a href="report-weeklylab.php?cid='.$cid.'">Activity Report - Lab Style Courses</a></li>';
	echo '<li><a href="report-commonstu.php?cid='.$cid.'">Activity Report - Sort Students by Activity</a></li>';
	echo '<li><a href="outcomereport.php?cid='.$cid.'">Outcome Report</a></li>';
	echo '<li><a href="logingrid.php?cid='.$cid.'">Login Grid</a></li>';
	echo '</ul>';

	echo '<p>&nbsp;</p>';
	echo '<p>Individual student login logs and detailed activity logs can be accessed ';
	echo 'from the Gradebook report for an individual student.</p>';
}

require("../footer.php");

?>

