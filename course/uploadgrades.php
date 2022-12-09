<?php
//IMathAS:  Upload grade page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");

function fopen_utf8 ($filename, $mode) {
    $file = @fopen($filename, $mode);
    $bom = fread($file, 3);
    if ($bom != b"\xEF\xBB\xBF") {
      fclose($file);
      $file = @fopen($filename, $mode);
    }
    return $file;
}

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Upload Grades";

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($_FILES['userfile']['name']) && $_FILES['userfile']['name']!='') {
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			$curscores = array();
			$stm = $DBH->prepare("SELECT userid,score FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid");
			$stm->execute(array(':gradetypeid'=>$_GET['gbitem']));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$curscores[$row[0]] = $row[1];
			}

			$failures = array();
			$successes = 0;

			if ($_POST['useridtype']==0) {
				$usercol =Sanitize::onlyInt($_POST['usernamecol'])-1;
			} else if ($_POST['useridtype']==1) {
				$usercol =Sanitize::onlyInt( $_POST['fullnamecol'])-1;
			}
			$scorecol = Sanitize::onlyInt($_POST['gradecol'])-1;
			$feedbackcol = Sanitize::onlyInt($_POST['feedbackcol'])-1;

			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			$handle = fopen_utf8(realpath($_FILES['userfile']['tmp_name']),'r');
			if ($_POST['hashdr']==1) {
				$data = fgetcsv($handle,4096,',');
			} else if ($_POST['hashdr']==2) {
				$data = fgetcsv($handle,4096,',');
				$data = fgetcsv($handle,4096,',');
			}
			while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
				$data = array_map('trim', $data);
				$query = "SELECT imas_users.id FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid AND imas_students.courseid=:courseid AND ";
				$qarr = array(':courseid'=>$cid);
				if ($_POST['useridtype']==0) {
					if ($data[$usercol]=='') {continue;}
					$query .= "imas_users.SID=:SID";
					$qarr[':SID'] = Sanitize::stripHtmlTags($data[$usercol]);
				} else if ($_POST['useridtype']==1) {
					if (strpos($data[$usercol],',')===false) { continue;}
					list($last,$first) = explode(',',$data[$usercol]);
					$first = trim($first);
					$last = trim($last);
					$query .= "imas_users.FirstName=:firstname AND imas_users.LastName=:lastname";
					$qarr[':firstname'] = Sanitize::stripHtmlTags($first);
					$qarr[':lastname'] = Sanitize::stripHtmlTags($last);
					//echo $query;
				} else {
					$query .= "0";
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);
				if ($feedbackcol==-1) {
					$feedback = '';
				} else {
					$feedback = Sanitize::incomingHtml($data[$feedbackcol]);
				}
				$score = Sanitize::onlyFloat($data[$scorecol]);
				if ($stm->rowCount()>0) {
					$cuserid=$stm->fetchColumn(0);
					if (isset($curscores[$cuserid])) {
						$stm = $DBH->prepare("UPDATE imas_grades SET score=:score,feedback=:feedback WHERE userid=:userid AND gradetype='offline' AND gradetypeid=:gradetypeid");
						$stm->execute(array(':score'=>$score, ':feedback'=>$feedback, ':userid'=>$cuserid, ':gradetypeid'=>$_GET['gbitem']));
						$successes++;
					} else {
						$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ";
						$query .= "(:gradetype, :gradetypeid, :userid, :score, :feedback)";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':gradetype'=>'offline', ':gradetypeid'=>$_GET['gbitem'], ':userid'=>$cuserid, ':score'=>$score, ':feedback'=>$feedback));
						$successes++;
					}
				} else {
					$failures[] = $data[$usercol];
				}
			}

			$overwriteBody = 1;
			$body = "<p>Grades uploaded.  $successes records.</p> ";
			if (count($failures)>0) {
				$body .= "<p>Grade upload failure on: <br/>";
				$body .= implode('<br/>', array_map('Sanitize::encodeStringForDisplay',$failures));
				$body .= '</p>';
			}
			if ($successes>0) {
				$body .= "<a href=\"addgrades.php?stu=0&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&cid=$cid&gbitem=".Sanitize::encodeUrlParam($_GET['gbitem'])."&grades=all\">Return to grade list</a></p>";
			}

		} else {
			$overwriteBody = 1;
			$body = "File Upload error";
		}
	} else { //DEFAULT DATA MANIPULATION
		$curBreadcrumb ="$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		$curBreadcrumb .=" &gt; <a href=\"gradebook.php?stu=0&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&cid=$cid\">Gradebook</a> ";
		$curBreadcrumb .=" &gt; <a href=\"addgrades.php?stu=0&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&cid=$cid&gbitem=".Sanitize::encodeUrlParam($_GET['gbitem'])."&grades=all\">Offline Grades</a> &gt; Upload Grades";
	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>

	<div id="headeruploadgrades" class="pagetitle"><h1><?php echo Sanitize::encodeStringForDisplay($pagetitle) ?></h1></div>


	<form enctype="multipart/form-data" method=post action="uploadgrades.php?cid=<?php echo $cid ?>&gbmode=<?php echo Sanitize::encodeUrlParam($_GET['gbmode']); ?>&gbitem=<?php echo Sanitize::encodeUrlParam($_GET['gbitem']); ?>">
		<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
		<span class=form>Grade file (CSV): </span>
		<span class=formright><input name="userfile" type="file" /></span><br class=form>
		<span class=form>File has header row?</span>
		<span class=formright>
			<input type=radio name="hashdr" value="0" checked=1 />No header<br/>
			<input type=radio name="hashdr" value="1" />Has 1 row header <br/>
			<input type=radio name="hashdr" value="2" />Has 2 row header <br/>
		</span><br class="form" />
		<span class=form>Grade is in column:</span>
		<span class=formright><input type=text size=4 name="gradecol" value="2"/></span><br class="form" />
		<span class=form>Feedback is in column (0 if none):</span>
		<span class=formright><input type=text size=4 name="feedbackcol" value="0"/></span><br class="form" />
		<span class=form>User is identified by:</span>
		<span class=formright>
			<input type=radio name="useridtype" value="0" checked=1 />Username (login name) in column
			<input type=text size=4 name="usernamecol" value="2" /><br/>
			<input type=radio name="useridtype" value="1" />Lastname, Firstname in column
			<input type=text size=4 name="fullnamecol" value="1" />
		</span><br class="form" />

		<div class=submit><input type=submit value="Upload Grades"></div>

	</form>

<?php
}

require("../footer.php");

?>
