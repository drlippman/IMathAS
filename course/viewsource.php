<?php
//IMathAS:  Modify a question's code
//(c) 2006 David Lippman
	require("../init.php");


	$pagetitle = "Question Source";
	require("../header.php");
	if (!(isset($teacherid)) && $myrights<100) {
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);
	$isadmin = false;
	if (isset($_GET['aid'])) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"addquestions2.php?aid=".Sanitize::onlyInt($_GET['aid'])."&cid=$cid\">Add/Remove Questions</a> &gt; View Source</div>";

	} else if (isset($_GET['daid'])) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"adddrillassess.php?daid=".Sanitize::onlyInt($_GET['daid'])."&cid=".Sanitize::courseId($cid)."\">Add Drill Assessment</a> &gt; View Source</div>";
	} else {
		if ($_GET['cid']=="admin") {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../admin/admin2.php\">Admin</a>";
			echo "&gt; <a href=\"manageqset.php?cid=admin\">Manage Question Set</a> &gt; View Source</div>\n";
			$isadmin = true;
		} else {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a>";
			echo "&gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set</a> &gt; View Source</div>\n";
		}

	}

	$qsetid = $_GET['id'];
	$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id=:id");
	$stm->execute(array(':id'=>$qsetid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);

	echo '<div id="headerviewsource" class="pagetitle"><h1>Question Source</h1></div>';
	echo "<h3>Description</h3>\n";
	echo "<pre>".Sanitize::encodeStringForDisplay($line['description'])."</pre>\n";
	echo "<h3>Author</h3>\n";
	echo "<pre>".Sanitize::encodeStringForDisplay($line['author'])."</pre>\n";
	echo "<h3>Question Type</h3>\n";
	echo "<pre>".Sanitize::encodeStringForDisplay($line['qtype'])."</pre>\n";
	echo "<h3>Common Control</h3>\n";
	echo "<pre>".Sanitize::encodeStringForDisplay($line['control'])."</pre>\n";
	echo "<h3>Question Control</h3>\n";
	echo "<pre>".Sanitize::encodeStringForDisplay($line['qcontrol'])."</pre>\n";
	echo "<h3>Question Text</h3>\n";
	echo "<pre>".Sanitize::encodeStringForDisplay($line['qtext'])."</pre>\n";
	echo "<h3>Answer</h3>\n";
	echo "<pre>".Sanitize::encodeStringForDisplay($line['answer'])."</pre>\n";


	if (!isset($_GET['aid'])) {
		echo "<a href=\"manageqset.php?cid=$cid\">Return to Question Set Management</a>\n";
	} else {
		echo "<a href=\"addquestions2.php?cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."\">Return to Assessment</a>\n";
	}
	require("../footer.php");
?>
