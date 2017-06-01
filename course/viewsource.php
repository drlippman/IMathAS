<?php
//IMathAS:  Modify a question's code
//(c) 2006 David Lippman
	require("../validate.php");


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
		echo "&gt; <a href=\"addquestions.php?aid=".Sanitize::onlyInt($_GET['aid'])."&cid=$cid\">Add/Remove Questions</a> &gt; View Source</div>";

	} else if (isset($_GET['daid'])) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"adddrillassess.php?daid={$_GET['daid']}&cid=$cid\">Add Drill Assessment</a> &gt; View Source</div>";
	} else {
		if ($_GET['cid']=="admin") {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../admin/admin.php\">Admin</a>";
			echo "&gt; <a href=\"manageqset.php?cid=admin\">Manage Question Set</a> &gt; View Source</div>\n";
			$isadmin = true;
		} else {
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a>";
			echo "&gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set</a> &gt; View Source</div>\n";
		}

	}

	$qsetid = $_GET['id'];
	//DB $query = "SELECT * FROM imas_questionset WHERE id='$qsetid'";
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id=:id");
	$stm->execute(array(':id'=>$qsetid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);

	echo '<div id="headerviewsource" class="pagetitle"><h2>Question Source</h2></div>';
	echo "<h4>Descr'ption</h4>\n";
	echo "<pre>".$line['description']."</pre>\n";
	echo "<h4>Author</h4>\n";
	echo "<pre>".$line['author']."</pre>\n";
	echo "<h4>Question Type</h4>\n";
	echo "<pre>".$line['qtype']."</pre>\n";
	echo "<h4>Common Control</h4>\n";
	echo "<pre>".$line['control']."</pre>\n";
	echo "<h4>Question Control</h4>\n";
	echo "<pre>".$line['qcontrol']."</pre>\n";
	echo "<h4>Question Text</h4>\n";
	echo "<pre>".$line['qtext']."</pre>\n";
	echo "<h4>Answer</h4>\n";
	echo "<pre>".$line['answer']."</pre>\n";


	if (!isset($_GET['aid'])) {
		echo "<a href=\"manageqset.php?cid=$cid\">Return to Question Set Management</a>\n";
	} else {
		echo "<a href=\"addquestions.php?cid=$cid&aid=".Sanitize::onlyInt($_GET['aid'])."\">Return to Assessment</a>\n";
	}
	require("../footer.php");
?>
