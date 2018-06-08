<?php

@set_time_limit(0);
ini_set("max_input_time", "1600");
ini_set("max_execution_time", "1600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

require("../init.php");
if ($myrights<100) {exit;}

require("../header.php");
if (!empty($_POST['from']) && !empty($_POST['to'])) {
	$from = trim($_POST['from']);
	$to = trim($_POST['to']);
	if (strlen($from)<10 || strlen($to)<10 || !preg_match('/^https?:\/\/[^\s<>"]+$/',$from) || !preg_match('/^https?:\/\/[^\s<>"]+$/',$to)) {
		echo "<p>Check the URLS; they don't appear to be correct.</p>";
		echo '<p><a href="replaceurls.php">Try again</p>';
		exit;
	} else if (isset($_POST['confirm'])) {

		$stm = $DBH->prepare("UPDATE imas_inlinetext SET text=REPLACE(text,:from2,:to) WHERE text LIKE :from");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
		$ni = $stm->rowCount();

		$stm = $DBH->prepare("UPDATE imas_linkedtext SET text=REPLACE(text,:from2,:to),summary=REPLACE(summary,:from3,:to2) WHERE text LIKE :from OR summary LIKE :from4");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':from3'=>$from, ':from4'=>"%$from%", ':to'=>$to, ':to2'=>$to));
		$nlt = $stm->rowCount();

		$stm = $DBH->prepare("UPDATE imas_assessments SET intro=REPLACE(intro,:from2,:to),summary=REPLACE(summary,:from3,:to2) WHERE intro LIKE :from OR summary LIKE :from4");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':from3'=>$from, ':from4'=>"%$from%", ':to'=>$to, ':to2'=>$to));
		$nas = $stm->rowCount();

		echo "<p>Inline Texts changed: $ni<br/>Linked texts changed: $nlt";
		echo "<br/>Assessments changed: $nas</p>";
		echo '<p><a href="utils.php">Done</p>';
	} else {
		$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_inlinetext WHERE text LIKE :from");
		$stm->execute(array(':from'=>"%$from%"));
		$ni = $stm->fetchColumn(0);

		$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_linkedtext WHERE text LIKE :from OR summary LIKE :from2");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>"%$from%"));
		$nlt = $stm->fetchColumn(0);

		$stm = $DBH->prepare("SELECT COUNT(id) FROM imas_assessments WHERE intro LIKE :from OR summary LIKE :from2");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>"%$from%"));
		$nas = $stm->fetchColumn(0);

		echo "<p>This action will change: </p>";
		echo "<p>Inline Texts changed: ".Sanitize::onlyInt($ni)."<br/>Linked texts changed: ".Sanitize::onlyInt($nlt);
		echo "<br/>Assessments changed: ".Sanitize::onlyInt($nas)."</p>";
		echo '<form method="post">';
		echo '<input type="hidden" name="from" value="'.Sanitize::encodeStringForDisplay($_POST['from']).'">';
		echo '<input type="hidden" name="to" value="'.Sanitize::encodeStringForDisplay($_POST['to']).'">';
		echo '<input type="submit" name="confirm" value="Make Changes"/> (this will be slow)';
		echo '</form>';
	}
	exit;
} else if (!empty($_POST['list'])) {
	$_POST['list'] = str_replace('"','', $_POST['list']);
	$lines = explode("\n",$_POST['list']);
	$torep = array();
	foreach ($lines as $line) {
		$line = trim($line);
		$parts = preg_split('/[\t,]+/', $line);
		if (count($parts)==2 && preg_match('/^https?:\/\/[^\s<>"]+$/',$parts[0]) && preg_match('/^https?:\/\/[^\s<>"]+$/',$parts[1])) {
			$torep[] = $parts;
		}
	}
	if (isset($_POST['confirm'])) {
		$ni = 0; $nlt = 0; $nas = 0;
		foreach ($torep as $rep) {
			$from = $rep[0];
			$to = $rep[1];

			$stm = $DBH->prepare("UPDATE imas_inlinetext SET text=REPLACE(text,:from2,:to) WHERE text LIKE :from");
			$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
			$ni += $stm->rowCount();

			$stm = $DBH->prepare("UPDATE imas_linkedtext SET text=REPLACE(text,:from2,:to),summary=REPLACE(summary,:from3,:to2) WHERE text LIKE :from OR summary LIKE :from4");
			$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':from3'=>$from, ':from4'=>$from, ':to'=>$to, ':to2'=>$to));
			$nlt += $stm->rowCount();

			$stm = $DBH->prepare("UPDATE imas_assessments SET intro=REPLACE(intro,:from2,:to),summary=REPLACE(summary,:from3,:to2) WHERE intro LIKE :from OR summary LIKE :from4");
			$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':from3'=>$from, ':from4'=>$from, ':to'=>$to, ':to2'=>$to));
			$nas += $stm->rowCount();
		}
		echo "<p>Inline Texts changed: ".Sanitize::onlyInt($ni)."<br/>Linked texts changed: ".Sanitize::onlyInt($nlt);
		echo "<br/>Assessments changed: ".Sanitize::onlyInt($nas)."</p>";
		echo '<p><a href="utils.php">Done</p>';
	} else {
		echo '<p>Verify the URLs were identified correctly</p>';
		echo '<table><tr><th>Current</th><th>Replacement</th></tr>';
		$out = '';
		foreach ($torep as $rep) {
			echo '<tr><td>'.Sanitize::encodeStringForDisplay($rep[0]).'</td><td>'.Sanitize::encodeStringForDisplay($rep[1]).'</td></tr>';
			$out .= $rep[0].','.$rep[1]."\n";
		}
		echo '</table>';
		echo '<form method="post">';
		echo '<input type="hidden" name="list" value="'.Sanitize::encodeStringForDisplay($out).'">';
		echo '<input type="submit" name="confirm" value="Make Changes"/> (this will be slow)';
		echo '</form>';
	}
	exit;

}
echo '<h2>Replace URL links</h2>';
echo '<p>This will replace URLS in linkedtext summaries and text, inlinetext summaries, and assessment summaries and intros across ALL courses.</p>';
echo '<form method="post">';
echo '<p>Replace URL: <input type="text" name="from" size="50"/><br/>with URL: <input type="text" name="to" size="50"/></p>';
echo '<p>Or, paste from a spreadsheet (current URL in first column, replacement in second column)<br/><textarea cols=80 rows=6 name="list"></textarea></p>';
echo '<p><input type="submit" value="Replace"/></p>';
echo '</form>';
require("../footer.php");
?>
