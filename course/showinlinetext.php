<?php
//IMathAS:  Displays a inline text item.  Only used in treereader, so no nav
//(c) 2018 David Lippman
	require_once "../init.php";
	require_once "../includes/filehandler.php";
	
	$inlinetextid = Sanitize::onlyInt($_GET['id']);
	$cid = Sanitize::courseId($_GET['cid']);
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) {
		require_once "../header.php";
		echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
		require_once "../footer.php";
		exit;
	}
	if (empty($inlinetextid)) {
		echo "<html><body>No item specified. <a href=\"course.php?cid=$cid\">Try again</a></body></html>\n";
		exit;
	}
	$shownav = false;
	$flexwidth = true;
	$nologo = true;
		
	$stm = $DBH->prepare("SELECT text,title,fileorder FROM imas_inlinetext WHERE id=:id AND courseid=:cid");
	$stm->execute(array(':id'=>$inlinetextid, ':cid'=>$cid));
	if ($stm->rowCount()==0) {
		echo "Invalid ID";
		exit;
	}
	list($text,$title,$fileorder) = $stm->fetch(PDO::FETCH_NUM);
	$titlesimp = strip_tags($title);


	$placeinhead = '';
	$pagetitle = $titlesimp;
	require_once "../header.php";
	
	
	echo '<div class="inlinetextholder" style="padding-left:10px; padding-right: 10px;">';
	echo Sanitize::outgoingHtml(filter($text));
	
	$stm = $DBH->prepare("SELECT id,description,filename FROM imas_instr_files WHERE itemid=:itemid");
	$stm->execute(array(':itemid'=>$inlinetextid));
	if ($stm->rowCount()>0) {
	   echo '<ul class="fileattachlist">';
	   $filenames = array();
	   $filedescr = array();
	   while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		   $filenames[$row[0]] = $row[2];
		   $filedescr[$row[0]] = $row[1];
	   }
	   foreach (explode(',',$fileorder) as $fid) {
		   //echo "<li><a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
		   echo "<li><a href=\"".getcoursefileurl($filenames[$fid])."\" target=\"_blank\">".Sanitize::encodeStringForDisplay($filedescr[$fid])."</a></li>";
	   }
	
	   echo "</ul>";
	}
	echo '</div>';
	
	require_once "../footer.php";

?>
