<?php
//Utility function for copying questions used in an assessment into a seperate library
//not integrated into user interface
require("../validate.php");
if (!isset($teacherid)) {exit;}

if (!isset($_GET['cid'])) {
	echo "No course identified";
	exit;
}
$cid = $_GET['cid'];
if (!isset($_POST['libs']) || $_POST['libs']=='') {
	require("../header.php");
	if (isset($_POST['libs'])) {
		echo "<p><b>No library selected.  Try again</b></p>";
	}
			
			echo "<h3>Copy Course Questions to </h3>\n";
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

$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid FROM imas_questions,imas_assessments,imas_questionset WHERE imas_questions.assessmentid=imas_assessments.id AND ";
$query .= "imas_questionset.id=imas_questions.questionsetid AND imas_assessments.courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	echo "no Q found";
	exit;
}
$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ";
$first = true;
while ($row = mysql_fetch_row($result)) {
	if ($first) {
		$first = false;
	} else {
		$query .= ',';
	}
	$query .= "('$tolib','{$row[0]}','{$row[1]}')";
}
mysql_query($query) or die("Query failed : " . mysql_error());
echo "Done.  <a href=\"course.php?cid=$cid\">Return to course page</a>";
exit;

