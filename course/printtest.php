<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");



 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Print Test";

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {

	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> ";
	echo "&gt; Print Test</div>\n";
	if (!isset($_POST['heights'])) {
		echo '<div class="cpmid"><a href="printlayoutbare.php?cid='.$cid.'&amp;aid='.$aid.'">Generate for cut-and-paste</a>';
		if (isset($CFG['GEN']['pandocserver'])) {
			echo ' | <a href="printlayoutword.php?cid='.$cid.'&amp;aid='.$aid.'">Generate for Word</a>';
		}
		echo '</div>';
	}
	echo '<div id="headerprinttest" class="pagetitle"><h2>Print Test</h2></div>';

	if (!isset($_POST['heights'])) {
		echo "<form method=post action=\"printlayout.php?cid=$cid&aid=$aid\">\n";
		echo "<h4>Header Setup</h4>\nPlease select the items you'd like in the test header:";
		echo "<ul><li><input type=checkbox name=aname checked=1>Assessment Name</li>\n";
		echo "<li><input type=checkbox name=iname checked=1>Instructor Name</li>\n";
		echo "<li><input type=checkbox name=cname checked=1>Course Name</li>\n";
		echo "<li><input type=checkbox name=sname checked=1>Student Name blank</li>\n";
		echo "<li><input type=checkbox name=otherheader>Other student entry: <input type=text name=otherheadertext size=20></li>\n";
		echo "</ul>\n";
		echo "<h4>Settings</h4>\n";
		echo "<ul>";
		echo "<li><input type=checkbox name=points checked=1>Show point values</li>\n";
		echo "<li><input type=checkbox name=hidetxtboxes >Hide text entry lines</li>\n";
		echo "</ul>";
		echo "<h4>Print Margin Setup</h4>\n";
		echo "Please check Page Setup under the File menu of your browser, and look up your print margin settings.<br/>\n";
		echo "Left + Right:  <input type=text name=horiz size=5 value=\"1.0\"> inches<br/>\n";
		echo "Top + Bottom:  <input type=text name=vert size=5 value=\"1.0\"> inches<br/>\n";
		echo "<p>Browser: <input type=radio name=browser value=0 checked=1>Internet Explorer <input type=radio name=browser value=1>FireFox<sup>*</sup></p>\n";
		echo "<h4>Print Layout</h4>\n";
		echo "<p>On the next page, you will see alternating blue and green rectangles indicating the size of pages.  Use the resizing ";
		echo "buttons next to each question to increase or decrease the space after each question until the questions fall nicely onto ";
		echo "the pages.  You can use Print Preview in your browser to verify that the print layout looks correct.  After you have completed ";
		echo "the print layout, you will be given the chance to specify additional print options.</p>\n";
		echo "<p>Longer questions, such as those with graphs, may appear cut off in the print layout page.  Be sure to resize those questions ";
		echo "to show the entire question.</p>\n";
		echo '<p>Be warned that this feature does not work well for long assessments.</p>';
		echo "<input type=submit value=\"Continue\">\n";
		echo "<p><sup>*</sup><em>Note: FireFox prints high-quality math, but has a bug that prevents it from printing graphs with text (such as axes labels) correctly</em></p>\n";
		echo "</form>\n";
	} else {
		echo "<form method=post action=\"printlayout.php?cid=$cid&aid=$aid&final=1\">\n";
		echo "<input type=hidden name=heights value=\"".Sanitize::encodeStringForDisplay($_POST['heights'])."\">\n";
		echo "<input type=hidden name=pw value=\"".Sanitize::encodeStringForDisplay($_POST['pw'])."\">\n";
		echo "<input type=hidden name=ph value=\"".Sanitize::encodeStringForDisplay($_POST['ph'])."\">\n";
		if (isset($_POST['points'])) {
			echo "<input type=hidden name=points value=1>\n";
		}
		if (isset($_POST['aname'])) {
			echo "<input type=hidden name=aname value=1>\n";
		}
		if (isset($_POST['iname'])) {
			echo "<input type=hidden name=iname value=1>\n";
		}
		if (isset($_POST['cname'])) {
			echo "<input type=hidden name=cname value=1>\n";
		}
		if (isset($_POST['sname'])) {
			echo "<input type=hidden name=sname value=1>\n";
		}
		if (isset($_POST['hidetxtboxes'])) {
			echo "<input type=hidden name=hidetxtboxes value=1>\n";
		}
		if (isset($_POST['otherheader'])) {
			echo "<input type=hidden name=otherheader value=1>\n";
			echo "<input type=hidden name=otherheadertext value=\"".Sanitize::encodeStringForDisplay($_POST['otherheadertext'])."\">\n";
		}
		echo "<h4>Final print settings</h4>\n";
		echo "<p>Number of different versions to print: <input type=text name=versions value=\"1\"></p>\n";
		echo "<p>Print answer keys? <input type=radio name=keys value=0>No <input type=radio name=keys value=1 checked=1>Yes <input type=radio name=keys value=2>Yes, one per page</p>\n";
		echo "<p>When you press Continue, your print-ready version of the test will display.  You may wish to go into the File menu of ";
		echo "your browser and select Page Setup to change the default headers and footers printed by your browser</p>\n";
		echo "<p><input type=submit value=\"Continue\"></p>\n";
		echo '</form>';
	}
}

	require("../footer.php");
?>
