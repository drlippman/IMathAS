<?php

require_once "../init.php";
require_once "../includes/htmLawed.php";
require_once "../includes/convertintro.php";

$cid = Sanitize::courseId($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);

if (!isset($teacherid)) {
	echo "You are not authorized for this action";
	exit;
}

if (isset($_POST['convert']) && $_POST['convert']=='all') {
	$stm = $DBH->prepare("SELECT intro,id,name FROM imas_assessments WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$converted = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		list($introjson,$isembed) = convertintro($row[0]);
		if ($introjson !== false) {
			$stm2 = $DBH->prepare("UPDATE imas_assessments SET intro=:intro WHERE id=:id");
			$stm2->execute(array(':id'=>$row[1], ':intro'=>json_encode($introjson, JSON_INVALID_UTF8_IGNORE)));
			$converted[] = Sanitize::encodeStringForDisplay($row[2]);
		}
	}
	require_once "../header.php";
	echo '<h2>Converted:</h2>';
	echo '<p>'.implode('<br/>', $converted).'</p>';
	echo "<p><a href=\"course.php?cid=$cid\">Done</a></p>";
	require_once "../footer.php";
	exit;
} else {
	$stm = $DBH->prepare("SELECT intro,itemorder,ver FROM imas_assessments WHERE id=:id AND courseid=:courseid");
	$stm->execute(array(':id'=>$aid, ':courseid'=>$cid));
	if ($stm->rowCount()==0) {echo "Invalid id"; exit;}
	list($current_intro_json,$qitemorder,$aver) = $stm->fetch(PDO::FETCH_NUM);
	if ($aver>1) {
		$addassess = 'addassessment2.php';
	} else {
		$addassess = 'addassessment.php';
	}

	list($introjson,$isembed) = convertintro($current_intro_json);
	if ($introjson===false) {
		echo 'Already converted, or does not need converting';
		exit;
	}

	if (isset($_POST['convert'])) {
		$stm = $DBH->prepare("UPDATE imas_assessments SET intro=:intro WHERE id=:id");
		$stm->execute(array(':id'=>$aid, ':intro'=>json_encode($introjson, JSON_INVALID_UTF8_IGNORE)));
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/$addassess?id=$aid&cid=$cid&r=" . Sanitize::randomQueryStringParam());
	} else {
		$qcnt = substr_count($qitemorder, ',')+1;
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		$curBreadcrumb .= "<a href=\"$addassess?cid=$cid&id=$aid\">"._("Modify Assessment")."</a>";
		require_once "../header.php";
		echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt '._('Convert Intro').'</div>';
		echo '<div id="headeraddlinkedtext" class="pagetitle"><h1>'._('Convert Intro').'</h1></div>';
		if ($isembed) {
			echo '<p>'._('This assessment is using an older [QUESTION #] tag approach for embedding questions in text. There is now a simpler approach that will allow you to edit the between question text on the Add/Remove Questions page.').'</p>';
		} else {
			echo '<p>'._('This assessment is using an older [Q #] tag approach for providing introduction text or videos before questions. There is now a simpler approach that will allow you to edit the before question text on the Add/Remove Questions page.').'</p>';
		}
		echo '<p>'._('Converting assessments to use the new approach sometimes has issues, so please confirm below that everything looks as expected.  To be totally safe, you may wish to make a copy of your assessment before trying to convert it.').'</p>';
		echo '<h2>'._('The following will be the main intro/instruction text').'</h2>';
		echo '<div style="margin-left:30px;border:2px solid #000; padding: 10px;">';
		echo Sanitize::outgoingHtml($introjson[0]);
		array_shift($introjson);
		echo '</div>';
		if ($isembed) {
			echo '<h2>'._('The remaining shows the text, along with the position of the questions.').'</h2>';
		} else {
			echo '<h2>'._('The remaining which questions the text segments will show before.').'</h2>';
		}
		$nextquestion = 0;
		if ($isembed) {
			echo '<div>';
			foreach ($introjson as $intpc) {
				if ($intpc['displayBefore']>$nextquestion) {
					for ($i=$nextquestion;$i<$intpc['displayBefore'];$i++) {
						echo '<p style="color:#900;font-weight:bold">'.sprintf(_("Question %d displays here"), $i+1).'</p>';
					}
				}
				if ($intpc['ispage']==1) {
					echo '</div>';
					echo '<div style="margin-top: 10px; margin-left:30px;border:2px solid #000; padding: 10px;">';
					echo '<h2>'._('New Page: ').Sanitize::encodeStringForDisplay($intpc['pagetitle']).'</h2>';
				}
				echo $intpc['text'];
				$nextquestion = $intpc['displayBefore'];
			}
			for ($i=$nextquestion;$i<$qcnt;$i++) {
				echo '<p style="color:#900;font-weight:bold">'.sprintf(_("Question %d displays here"), $i+1).'</p>';
			}
			echo '</div>';
		} else {
			foreach ($introjson as $intpc) {
				if ($intpc['displayBefore']==$intpc['displayUntil']) {
					echo '<p style="color:#900;font-weight:bold">'.sprintf(_("The following will display before question %d"), $intpc['displayBefore']+1).'</p>';
				} else {
					echo '<p style="color:#900;font-weight:bold">'.sprintf(_("The following will display before questions %d - %d"), $intpc['displayBefore']+1, $intpc['displayUntil']+1).'</p>';
				}
				echo '<div style="margin-left:30px;border:2px solid #000; padding: 10px;">';
				echo $intpc['text'];
				echo '</div>';
			}
		}
		echo '<p>'._('Do you want to convert this assessment?').'</p>';

		echo '<form method="POST" action="'.sprintf('convertintro.php?cid=%d&aid=%d',$cid,$aid).'">';
		echo '<p><button type=submit name="convert" value="one">'._('Convert').'</button>';
		echo '<button type="button" class="secondarybtn" onClick="window.location=\''.sprintf('%s?cid=%d&aid=%d',$addassess,$cid,$aid).'\'">'._('Nevermind').'</button></p>';
		echo '</form>';

		echo '<p>&nbsp;</p>';
		echo '<form method="POST" action="'.sprintf('convertintro.php?cid=%d&aid=%d',$cid,$aid).'" onsubmit="return confirm(\'Are you SURE??? This is risky and can NOT be undone. Make sure you have a backup just in case something goes wrong.\');">';
		echo '<p><button type="submit" name="convert" value="all">'._('Convert All Assessments in Course').'</button></p>';
		echo '</form>';
		require_once "../footer.php";
	}
}
?>
