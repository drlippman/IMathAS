<?php
//IMathAS:  Add end messages
//(c) 2008 David Lippman


if (!isset($imasroot)) {
	require_once "../init.php";
	if (!(isset($teacherid))) { // loaded by a NON-teacher
		echo "You must be a teacher to access this page";
		exit;
	}
}

	$cid = Sanitize::courseId($_GET['cid']);
    if (!empty($_GET['from']) && $_GET['from'] == 'addq2') {
        $addq = 'addquestions2';
        $from = 'addq2';
    } else {
        $addq = 'addquestions';
        $from = 'addq';
    }

	if (isset($_GET['record'])) {
		$endmsg = array();
		$endmsg['type'] = Sanitize::onlyInt($_POST['type']);
		$endmsg['def'] = Sanitize::incomingHtml($_POST['msg'][0]);
		$i=1;
		$msgarr = array();
		while (isset($_POST['sc'][$i]) && !empty($_POST['sc'][$i]) ) {
			$key = (int)$_POST['sc'][$i];
			if ($key>0) {
				$msgarr[$key] = Sanitize::incomingHtml($_POST['msg'][$i]);
			}
			$i++;
		}
		krsort($msgarr);
		$endmsg['msgs'] = $msgarr;
		$endmsg['commonmsg'] = Sanitize::incomingHtml($_POST['commonmsg']);
		$msgstr = serialize($endmsg);
		if (isset($_POST['aid'])) {
			$stm = $DBH->prepare("UPDATE imas_assessments SET endmsg=:endmsg WHERE id=:id");
			$stm->execute(array(':endmsg'=>$msgstr, ':id'=>Sanitize::onlyInt($_POST['aid'])));
		} else if (isset($_POST['aidlist'])) {
			$aidlist = implode(',', array_map('intval', explode(',',$_POST['aidlist'])));
			$stm = $DBH->prepare("UPDATE imas_assessments SET endmsg=:endmsg WHERE id IN ($aidlist)");
			$stm->execute(array(':endmsg'=>$msgstr));

		}
		$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid" .$btf. "&r=" . Sanitize::randomQueryStringParam());

		exit;
	}

	$pagetitle = "End of Assessment Messages";
	$useeditor = "commonmsg";

	require_once "../header.php";
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
	if (!isset($_POST['checked'])) {
		echo "<a href=\"$addq.php?cid=$cid&amp;aid=" . Sanitize::onlyInt($_GET['aid']) . "\">Add/Remove Questions</a> &gt; End of Assessment Msg</div>\n";
	} else {
		echo "<a href=\"chgassessments.php?cid=$cid\">Mass Change Assessments</a> &gt; End of Assessment Msg</div>\n";
	}
	if (!isset($_POST['checked'])) {
		$stm = $DBH->prepare("SELECT endmsg FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>Sanitize::onlyInt($_GET['aid'])));
		$endmsg = $stm->fetchColumn(0);
	} else {
		$endmsg = '';
		if (empty($_POST['checked'])) {
			echo "No assessments selected";
			require_once "../footer.php";
			exit;
		}
	}
	if ($endmsg!='') {
		$endmsg = unserialize($endmsg);
        if (!isset($endmsg['msgs'])) {
            $endmsg['def'] = '';
		    $endmsg['type'] = 0;
		    $endmsg['msgs'] = array();
		    $endmsg['commonmsg'] = '';
        }
	} else {
		$endmsg = array();
		$endmsg['def'] = '';
		$endmsg['type'] = 0;
		$endmsg['msgs'] = array();
		$endmsg['commonmsg'] = '';
	}
	echo '<div id="headerassessendmsg" class="pagetitle"><h1>End of Assessment Messages</h1></div>';
	echo "<form method=\"post\" action=\"assessendmsg.php?cid=$cid&amp;from=$from&amp;record=true\" />";
	if (isset($_POST['checked'])) {
		echo '<input type="hidden" name="aidlist" value="' . Sanitize::encodeStringForDisplay(implode(',',$_POST['checked'])) . '" />';
	} else {
		echo '<input type="hidden" name="aid" value="'.Sanitize::onlyInt($_GET['aid']).'" />';
	}
	echo '<p>Base messages on: ';
	echo '<label><input type="radio" name="type" value="0" ';
	if ($endmsg['type']==0) { echo 'checked="checked"';}
	echo ' />Points</label> <label><input type="radio" name="type" value="1" ';
	if ($endmsg['type']==1) { echo 'checked="checked"';}
	echo ' />Percents</label></p>';

	echo '<table class="gb"><thead><tr><th>If score is at least</th><th>Display this message</th></tr></thead><tbody>';
	$i=1;
	foreach($endmsg['msgs'] as $sc=>$msg) {
		$msg = Sanitize::encodeStringForDisplay($msg);
		echo "<tr><td><span class=\"sr-only\" id=\"label$i\">Rule $i</span>";
		echo "<input type=\"text\" size=\"4\" name=\"sc[$i]\" value=\"$sc\" aria-labelledby=\"label$i\"/></td>";
		echo "<td><input type=\"text\" size=\"80\" name=\"msg[$i]\" value=\"$msg\" aria-labelledby=\"label$i\"/></td></tr>";
		$i++;
	}
	for ($j=0;$j<10;$j++) {
		echo "<tr><td><span class=\"sr-only\" id=\"label$i\">Rule $i</span>";
		echo "<input type=\"text\" size=\"4\" name=\"sc[$i]\" value=\"\" aria-labelledby=\"label$i\"/></td>";
		echo "<td><input type=\"text\" size=\"80\" name=\"msg[$i]\" value=\"\" aria-labelledby=\"label$i\" /></td></tr>";
		$i++;
	}
	echo "<tr><td>Otherwise, show:</td>";
	$endmsg['def'] = Sanitize::encodeStringForDisplay($endmsg['def']);
	echo "<td><input type=\"text\" size=\"80\" name=\"msg[0]\" value=\"{$endmsg['def']}\" aria-label=\"Fallback message\"/></td></tr>";
	echo '</tbody></table>';
	echo '<p><label for="commonmsg">After the score-specific message, display this text to everyone</label>:</p>';
	echo '<div class=editor><textarea cols="50" rows="10" name="commonmsg" id="commonmsg" style="width: 100%">';
	echo Sanitize::encodeStringForDisplay($endmsg['commonmsg']);
	echo '</textarea></div>';
	echo '<div class="submit"><input type="submit" value="'._('Save').'" /></div>';
	echo '</form>';
?>
<p>Order of entries is not important; the message with highest applicable score will be reported.
The "otherwise, show" message will display if no other score messages are defined.  Use this instead
of trying to create a 0 score entry</p>
<?php
	require_once "../footer.php";
?>
