<?php
//IMathAS:  Upload multiple grades from .csv file
//(c) 2009 David Lippman

/*** master php includes *******/
require_once "../init.php";

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
$pagetitle = "Upload Multiple Grades";

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
	$cid = Sanitize::courseId($_GET['cid']);
	if (isset($_POST['filekey'])) {
		//already uploaded file, ready for official upload
    $filekey = Sanitize::simplestring($_POST['filekey']);
    $uploadfile = getimportfilepath($filekey);

		$stm = $DBH->prepare("SELECT imas_users.id,imas_users.SID FROM imas_users JOIN imas_students ON imas_students.userid=imas_users.id WHERE imas_students.courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$useridarr[$row[1]] = $row[0];
		}
		$coltoadd = array_map('intval', $_POST['addcol']);
		require_once "../includes/parsedatetime.php";
		if ($_POST['sdatetype']=='0') {
			$showdate = 0;
		} else {
			$showdate = parsedatetime($_POST['sdate'],$_POST['stime'],0);
		}
		$gradestodel = array();
        $gbitemid = array();
		foreach ($coltoadd as $col) {
			if (trim($_POST["colname$col"])=='') {continue;}
			$name = trim($_POST["colname$col"]);
			$pts = intval($_POST["colpts$col"]);
			$cnt = $_POST["colcnt$col"];
			$gbcat = $_POST["colgbcat$col"];
			if ($_POST["coloverwrite$col"]>0) {
				//we're going to check that this id really belongs to this course.  Don't want cross-course hacking :)
				$stm = $DBH->prepare("SELECT id FROM imas_gbitems WHERE id=:id AND courseid=:courseid");
				$stm->execute(array(':id'=>$_POST["coloverwrite$col"], ':courseid'=>$cid));
				if ($stm->rowCount()>0) {
					$gbitemid[$col] = $stm->fetchColumn(0);
					//delete old grades
					//$query = "DELETE FROM imas_grades WHERE gbitemid={$gbitemid[$col]}";
					//mysql_query($query) or die("Query failed : " . mysql_error());
					$gradestodel[$col] = array();
					continue;
				}
			}
			$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES ";
			$query .= "(:courseid, :name, :points, :showdate, :gbcategory, :cntingb, :tutoredit) ";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':name'=>$name, ':points'=>$pts, ':showdate'=>$showdate, ':gbcategory'=>$gbcat, ':cntingb'=>$cnt, ':tutoredit'=>0));
			$gbitemid[$col] = $DBH->lastInsertId();
		}
		$adds = array();
		$addsvals = array();
		if (count($gbitemid)>0) {
			$handle = fopen_utf8($uploadfile,'r');
			for ($i = 0; $i<$_POST['headerrows']; $i++) {
				$line = fgetcsv($handle,4096, ',', '"', '');
			}
			$sidcol = $_POST['sidcol'] - 1;
			while ($line = fgetcsv($handle, 4096, ',', '"', '')) { //for each student
				if ($line[$sidcol]=='' || !isset($useridarr[$line[$sidcol]])) {
					//echo "breaking 1";
					//print_r($line);
					continue;
				}
				$stu = $useridarr[$line[$sidcol]];
				foreach ($gbitemid as $col=>$gid) { //for each gbitem we're adding
					$fbcol = $_POST["colfeedback$col"];
					$feedback = '';
					if (trim($fbcol)!='' && intval($fbcol)>0) {
						$feedback = Sanitize::incomingHtml($line[intval($fbcol)-1]);
					}
					if (trim($line[$col])=='' || $line[$col] == '-') {
						//echo "breaking 2";
						//print_r($line);
						if ($feedback != '') {
							$score = null;
						} else {
							continue;
						}
					} else {
						$score = floatval($line[$col]);
					}

					if (isset($gradestodel[$col])) {
						$gradestodel[$col][] = $stu;
					}
					$adds[] = "('offline',?,?,?,?)";
					array_push($addsvals, $gid,$stu,$score,$feedback);
				}
			}
			fclose($handle);
			//delete any data we're overwriting
			foreach ($gradestodel as $col=>$stus) {
				if (count($stus)>0) {
					$stulist = implode(',', array_map('intval', $stus));
					$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid AND userid IN ($stulist)");
					$stm->execute(array(':gradetypeid'=>$gbitemid[$col]));
				}
			}
			//now we load in the data!
			if (count($adds)>0) {
				$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,score,feedback) VALUES ".implode(',',$adds);
				$stm = $DBH->prepare($query);
				$stm->execute($addsvals);
				//echo $query;
			}
		}
		deleteimport($filekey);
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/chgoffline.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		exit;
	} else if (isset($_FILES['userfile']['name']) && $_FILES['userfile']['name']!='') {
		//upload file
    if ($filekey = storeimportfile('userfile')) {
      $page_fileHiddenInput = "<input type=hidden name=\"filekey\" value=\"".Sanitize::encodeStringForDisplay($filekey)."\" />\n";
    } else {
      echo "<p>Error uploading file!</p>\n";
      echo Sanitize::encodeStringForDisplay($_FILES["userfile"]['error']);
      exit;
    }
    $uploadfile = getimportfilepath($filekey);
		//parse out header info
		$handle = fopen_utf8($uploadfile,'r');
		$hrow = fgetcsv($handle,4096, ',', '"', '');
		$columndata = array();
		$names = array();
		if ($_POST['headerrows']==2) {
			$srow = fgetcsv($handle,4096, ',', '"', '');
		}
		fclose($handle);
		for ($i=0; $i<count($hrow); $i++) {
			if ($hrow[$i]=='Username') {
				$usernamecol = $i;
			} else if ($hrow[$i]=='Name' || $hrow[$i]=='Section' || $hrow[$i]=='Code') {
				continue;
			} else if (strpos(strtolower($hrow[$i]),'comment')!==false || strpos(strtolower($hrow[$i]),'feedback')!==false) {
				$columndata[$i-1][2] = $i;
			} else {
				if (isset($srow[$i])) {
					$p = explode(':',$hrow[$i]);
					if (count($p)>1) {
					    $names[$i] = Sanitize::stripHtmlTags($p[0]);
					} else {
						$names[$i] = Sanitize::stripHtmlTags($hrow[$i]);
					}
					$pts = intval(preg_replace('/[^\d\.]/','',$srow[$i]));
					//if ($pts==0) {$pts = '';}
				} else {
					$p = explode(':',$hrow[$i]);
					if (count($p)>1) {
						$pts = intval(preg_replace('/[^\d\.]/','',$p[count($p)-1]));
						$names[$i] = Sanitize::stripHtmlTags($p[0]);
					} else {
						$pts = 0;
						$names[$i] = Sanitize::stripHtmlTags($hrow[$i]);
					}
					//if ($pts==0) {$pts = '';}
				}
				$columndata[$i] = array($names[$i],$pts,-1,0);
			}
		}
		//look to see if any of these names have been used before
		if (count($names)>0) {
			$query_placeholders = Sanitize::generateQueryPlaceholders($names);
			$stm = $DBH->prepare("SELECT id,name FROM imas_gbitems WHERE name IN ($query_placeholders) AND courseid=?");
			$stm->execute(array_merge($names, array($cid)));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$loc = array_search($row[1],$names);
				if ($loc===false) {continue; } //shouldn't happen
				$columndata[$loc][3] = $row[0];  //store existing gbitems.id
			}
		}
		if (!isset($usernamecol)) {
			$usernamecol = 1;
		}
        $showdate = 0;
	}

    $curBreadcrumb = $breadcrumbbase;
    if (empty($_COOKIE['fromltimenu'])) {
        $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
    $curBreadcrumb .=" <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	$curBreadcrumb .=" &gt; <a href=\"chgoffline.php?stu=0&cid=$cid\">Manage Offline Grades</a> &gt; Upload Multiple Grades";

}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
require_once "../header.php";
echo '<div class="breadcrumb">'.$curBreadcrumb.'</div>';
echo '<div id="headeruploadmultgrades" class="pagetitle"><h1>Upload Multiple Grades</h1></div>';
if ($overwriteBody==1) {
	echo $body;
} else {
	echo '<form id="qform" enctype="multipart/form-data" method=post action="uploadmultgrades.php?cid='.$cid.'">';

	if (isset($page_fileHiddenInput)) {
		//file has been uploaded, need to know what to import
		echo $page_fileHiddenInput;
		echo '<input type="hidden" name="headerrows" value="'.Sanitize::encodeStringForDisplay($_POST['headerrows']).'" />';
		$sdate = tzdate("m/d/Y",time());
		$stime = tzdate("g:i a",time());
	?>

		<label for="sidcol" class=form>Username is in column:</label>
		<span class=formright><input type=text name="sidcol" id=sidcol size=4 value="<?php echo $usernamecol+1; ?>"></span><br class=form />

		<span class=form id="showlbl">Show grade to students after:</span>
		<span class=formright role=group aria-labelledby="showlbl">
			<label><input type=radio name="sdatetype" value="0" <?php if ($showdate=='0') {echo "checked=1";}?>/> Always</label><br/>
			<input type=radio name="sdatetype" value="sdate" <?php if ($showdate!='0') {echo "checked=1";}?> aria-label="Date" />
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>" aria-label="show after date">
			<a href="#" onClick="displayDatePicker('sdate', this); return false"><img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>" aria-label="show after time"></span><BR class=form>

		<p>Check: <a href="#" onclick="return chkAllNone('qform','addcol[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','addcol[]',false)">None</a></p>

		<table class="gb">
        <caption class="sr-only">Grades to import</caption>
		<thead>
		  <tr><th>In column</th><th>Name</th><th>Import this?</th><th>Overwrite?</th><th>Points</th><th>Count?</th><th>Gradebook Category</th><th>Feedback in column<br/>(blank for none)</th></tr>
		</thead>
		<tbody>
	<?php
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbcatoptions = '<option value="0" selected=1>Default</option>';
		if ($stm->rowCount()>0) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$gbcatoptions .= "<option value=\"".Sanitize::onlyInt($row[0])."\">".Sanitize::encodeStringForDisplay($row[1])."</option>\n";
			}
		}
		foreach ($columndata as $col=>$data) {
			$col = intval($col);
			echo '<tr><td id="lbl'.$col.'"><span class="sr-only">Column</span> '.(intval($col)+1).'</td>';
			echo '<td><input type="text" size="20" name="colname'.Sanitize::onlyInt($col).'" value="'.Sanitize::encodeStringForDisplay($data[0]).'" aria-labelledby="lbl'.$col.'"/></td>';

			echo '<td><input type="checkbox" name="addcol[]" value="'.$col.'" aria-labelledby="lbl'.$col.'" /></td>';
			echo '<td><select name="coloverwrite'.Sanitize::onlyInt($col).'" aria-labelledby="lbl'.$col.'"><option value="0" ';
			if ($data[3]==0) {echo 'selected="selected"';}
			echo '>Add as new item</option>';
			if ($data[3]>0) {
				echo '<option value="'.Sanitize::encodeStringForDisplay($data[3]).'" selected="selected">Overwrite existing scores</option>';
			}
			echo '</select></td>';
			echo '<td><input type="text" size="3" name="colpts'.Sanitize::onlyInt($col).'"  value="'.Sanitize::encodeStringForDisplay($data[1]).'" aria-labelledby="lbl'.$col.'"/></td>';
			echo '<td><select name="colcnt'.Sanitize::onlyInt($col).'" aria-labelledby="lbl'.$col.'">';
			echo '<option value="1" selected="selected">Count in gradebook</option>';
			echo '<option value="0">Don\'t count and hide from students</option>';
			echo '<option value="3">Don\'t count in grade total</option>';
			echo '<option value="2">Count as extra credit</option></select></td>';
			echo '<td><select name="colgbcat'.Sanitize::onlyInt($col).'" aria-labelledby="lbl'.$col.'">'.$gbcatoptions.'</select></td>';
			echo '<td><input type="text" size="3" name="colfeedback'.Sanitize::onlyInt($col).'"  value="'.Sanitize::encodeStringForDisplay($data[2]>-1?$data[2]+1:'').'" aria-labelledby="lbl'.$col.'" /></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p><input type="submit" value="Upload" /></p>';
		echo '<p>Note:  If you choose to overwrite existing scores, it will replace existing scores with any non-blank scores in your upload.</p>';
	} else {
		//need file
	?>
		<p>The uploaded file must be in Comma Separated Values (.CSV) file format, and contain a column with
		the students' usernames.  If you are including feedback as well as grades, upload will be much easier if the
		feedback is in the column immediately following the scores, and if the column header contains the word Comment or Feedback</p>
		<p>
		<label for=userfile class=form>Import File: </label>
		<span class=formright>
			<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
			<input name="userfile" id=userfile type="file" />
		</span><br class=form />
		<span class=form id="rowlbl">File contains a header row:</span>
		<span class=formright role=group aria-labelledby="rowlbl">
			<label><input type=radio name="headerrows" value="1" checked="checked">Yes, one</label><br/>
			<label><input type=radio name="headerrows"  value="2">Yes, with second for points possible</label>
		</span><br class=form />
		<div class=submit><input type=submit value="Continue"></div>
		</p>

	<?php
	}
	echo '</form>';
}

require_once "../footer.php";

?>
