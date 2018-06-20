<?php
//Utility function for copying questions used in an assessment into a seperate library
//not integrated into user interface
require("../init.php");
if (!isset($teacherid)) {exit;}

if (!isset($_GET['cid'])) {
	echo "No course identified";
	exit;
}
$cid = Sanitize::courseId($_GET['cid']);
if (!isset($_POST['libs']) || $_POST['libs']=='') {
	require("../header.php");
	if (isset($_POST['libs'])) {
		echo "<p><b>No library selected.  Try again</b></p>";
	}

			echo "<h2>Copy Course Questions to </h2>\n";
			echo "<form method=post action=\"coursetolibrary.php?cid=$cid\">\n";

			echo <<<END
<script>
var curlibs = '';
function libselect() {
	window.open('libtree2.php?cid=$cid&libtree=popup&select=child&selectrights=1&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
</script>
END;
			echo "<span class=form>Library to place in: </span><span class=formright><span id=\"libnames\"></span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$parent\">\n";
			echo "<input type=button value=\"Select Library\" onClick=\"libselect()\"></span><br class=form> ";

			echo "<p><input type=submit value=\"Copy Questions to Library\">\n";
			echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='course.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;

}
$tolib = $_POST['libs'];

//DB $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid FROM imas_questions,imas_assessments,imas_questionset WHERE imas_questions.assessmentid=imas_assessments.id AND ";
//DB $query .= "imas_questionset.id=imas_questions.questionsetid AND imas_assessments.courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid FROM imas_questions,imas_assessments,imas_questionset WHERE imas_questions.assessmentid=imas_assessments.id AND ";
$query .= "imas_questionset.id=imas_questions.questionsetid AND imas_assessments.courseid=:courseid";
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$cid));
if ($stm->rowCount()==0) {
	echo "no Q found";
	exit;
}
$qarr = array();
$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ";
$first = true;
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if ($first) {
		$first = false;
	} else {
		$query .= ',';
	}
	//DB $query .= "('$tolib','{$row[0]}','{$row[1]}')";
	$query .= "(?,?,?)";
	array_push($qarr, $tolib, $row[0], $row[1]);
}
//DB mysql_query($query) or die("Query failed : " . mysql_error());
$stm = $DBH->prepare($query);
$stm->execute($qarr);
echo "Done.  <a href=\"course.php?cid=$cid\">Return to course page</a>";
exit;
