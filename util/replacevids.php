<?php
require("../init.php");
if ($myrights<100) {exit;}

require("../header.php");
if (!empty($_POST['from']) && !empty($_POST['to'])) {
	$from = trim($_POST['from']);
	$to = trim($_POST['to']);
	if (strlen($from)!=11 || strlen($to)!=11 || preg_match('/[^A-Za-z0-9_\-]/',$from) || preg_match('/[^A-Za-z0-9_\-]/',$to)) {
		echo "<p>Check the video ID formats; they don't appear to be correct.</p>";
		echo '<p><a href="replacevids.php">Try again</p>';
		exit;
	} else {
		//DB $query = "UPDATE imas_inlinetext SET text=REPLACE(text,'$from','$to') WHERE text LIKE '%$from%'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $ni = mysql_affected_rows();
		$stm = $DBH->prepare("UPDATE imas_inlinetext SET text=REPLACE(text,:from2,:to) WHERE text LIKE :from");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
		$ni = $stm->rowCount();
		//DB $query = "UPDATE imas_linkedtext SET text=REPLACE(text,'$from','$to') WHERE text LIKE '%$from%'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $nlt = mysql_affected_rows();
		$stm = $DBH->prepare("UPDATE imas_linkedtext SET text=REPLACE(text,:from2,:to) WHERE text LIKE :from");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
		$nlt = $stm->rowCount();
		//DB $query = "UPDATE imas_linkedtext SET summary=REPLACE(summary,'$from','$to') WHERE summary LIKE '%$from%'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $nls = mysql_affected_rows();
		$stm = $DBH->prepare("UPDATE imas_linkedtext SET summary=REPLACE(summary,:from2,:to) WHERE summary LIKE :from");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
		$nls = $stm->rowCount();
		//DB $query = "UPDATE imas_assessments SET intro=REPLACE(intro,'$from','$to') WHERE intro LIKE '%$from%'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $nai = mysql_affected_rows();
		$stm = $DBH->prepare("UPDATE imas_assessments SET intro=REPLACE(intro,:from2,:to) WHERE intro LIKE :from");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
		$nai = $stm->rowCount();
		//DB $query = "UPDATE imas_assessments SET summary=REPLACE(summary,'$from','$to') WHERE summary LIKE '%$from%'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $nas = mysql_affected_rows();
		$stm = $DBH->prepare("UPDATE imas_assessments SET summary=REPLACE(summary,:from2,:to) WHERE summary LIKE :from");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
		$nas = $stm->rowCount();
		//DB $query = "UPDATE imas_questionset SET extref=REPLACE(extref,'$from','$to') WHERE extref LIKE '%$from%'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $nqe = mysql_affected_rows();
		$stm = $DBH->prepare("UPDATE imas_questionset SET extref=REPLACE(extref,:from2,:to) WHERE extref LIKE :from");
		$stm->execute(array(':from'=>"%$from%", ':from2'=>$from, ':to'=>$to));
		$nqe = $stm->rowCount();
		echo "Inline Texts changed: $ni<br/>Linked texts changed: $nlt<br/>Lined text summaries changed: $nls";
		echo "<br/>Assessment intros changed: $nai<br/>Assessment summaries changed: $nas<br/>Question video links changed: $nqe";
		echo '<p><a href="utils.php">Done</p>';
	}
	exit;
}
echo '<h2>Replace video links</h2>';
echo '<p>This will replace video links or question button links anywhere on the system</p>';
echo '<form method="post">';
echo '<p>Replace video ID <input type="text" name="from" size="11"/> with video ID <input type="text" name="to" size="11"/></p>';
echo '<p><input type="submit" value="Replace"/></p>';
echo '</form>';
require("../footer.php");
?>
