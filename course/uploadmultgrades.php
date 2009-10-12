<?php
//IMathAS:  Upload multiple grades from .csv file
//(c) 2009 David Lippman

/*** master php includes *******/
require("../validate.php");

	
 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Upload Multiple Grades";
	
	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION	
	$cid = $_GET['cid'];
	$dir = rtrim(dirname(dirname(__FILE__)), '/\\').'/admin/import/';
	if (isset($_POST['thefile'])) {
		//already uploaded file, ready for official upload
		$filename = basename($_POST['thefile']);
		if (!file_exists($dir.$filename)) {
			echo "File is missing!";
			exit;
		}
		$query = "SELECT imas_users.id,imas_users.SID FROM imas_users JOIN imas_students ON imas_students.userid=imas_users.id WHERE imas_students.courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$useridarr[$row[1]] = $row[0];
		}
		$coltoadd = $_POST['addcol'];
		require_once("parsedatetime.php");
		if ($_POST['sdatetype']=='0') {
			$showdate = 0;
		} else {
			$showdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		}
		foreach ($coltoadd as $col) {
			if (trim($_POST["colname$col"])=='') {continue;}
			$name = trim($_POST["colname$col"]);
			$pts = intval($_POST["colpts$col"]);
			$cnt = $_POST["colcnt$col"];
			$gbcat = $_POST["colgbcat$col"];
			$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES ";
			$query .= "('$cid','$name','$pts',$showdate,'$gbcat','$cnt',0) ";
			//echo "$query <br/>";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$gbitemid[$col] = mysql_insert_id();
		}
		$adds = array();
		if (count($gbitemid)>0) {
			$handle = fopen($dir.$filename,'r');
			for ($i = 0; $i<$_POST['headerrows']; $i++) {
				$line = fgetcsv($handle,4096);
			}
			$sidcol = $_POST['sidcol'] - 1;
			while ($line = fgetcsv($handle, 4096)) { //for each student
				if ($line[$sidcol]=='' || !isset($useridarr[$line[$sidcol]])) {
					//echo "breaking 1";
					//print_r($line);
					continue;
				}
				$stu = $useridarr[$line[$sidcol]];
				foreach ($gbitemid as $col=>$gid) { //for each gbitem we're adding
					if (trim($line[$col])=='' || $line[$col] == '-') {
						//echo "breaking 2";
						//print_r($line);
						continue;
					}
					$score = floatval($line[$col]);
					$fbcol = $_POST["colfeedback$col"]-1;
					if (trim($fbcol)!='' && intval($fbcol)>0) {
						$feedback = addslashes($line[intval($fbcol)]);	
					} else {
						$feedback = '';
					}
					$adds[] = "($gid,$stu,$score,'$feedback')";
				}
			}
			//now we load in the data!
			if (count($adds)>0) {
				$query = "INSERT INTO imas_grades (gbitemid,userid,score,feedback) VALUES ";
				$query .= implode(',',$adds);
				mysql_query($query) or die("Query failed : " . mysql_error());
				//echo $query;
			}
		}
		unlink($dir.$filename);
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/chgoffline.php?cid=$cid");
		exit;
	} else if (isset($_FILES['userfile']['name']) && $_FILES['userfile']['name']!='') {
		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			$k = 0;
			while (file_exists($dir . "upload$k.csv")) {
				$k++;
			}
			$uploadfile = "upload$k.csv";
			if (move_uploaded_file($_FILES['userfile']['tmp_name'], $dir.$uploadfile)) {
				$page_fileHiddenInput = '<input type="hidden" name="thefile" value="'.$uploadfile.'" />';
				$handle = fopen($dir.$uploadfile,'r');
				$hrow = fgetcsv($handle,4096);
				$columndata = array();
				if ($_POST['headerrows']==2) {
					$srow = fgetcsv($handle,4096);
				}
				for ($i=0; $i<count($hrow); $i++) {
					if ($hrow[$i]=='Username') {
						$usernamecol = $i;
					} else if ($hrow[$i]=='Name' || $hrow[$i]=='Section' || $hrow[$i]=='Code') {
						continue;
					} else if (strpos(strtolower($hrow[$i]),'comment')!==false || strpos(strtolower($hrow[$i]),'feedback')!==false) {
						$columndata[$i-1][2] = $i;
					} else {
						if (isset($srow[$i])) {
							$pts = intval(preg_replace('/[^\d\.]/','',$srow[$i]));
							//if ($pts==0) {$pts = '';}
						} else {
							$p = explode(':',$hrow[$i]);
							if (count($p)>1) {
								$pts = intval(preg_replace('/[^\d\.]/','',$p[count($p)-1]));
							} else {
								$pts = 0;
							}
							//if ($pts==0) {$pts = '';}
						}
						$columndata[$i] = array($hrow[$i],$pts,-1);
					}
				}
				if (!isset($usernamecol)) {
					$usernamecol = 1;
				}
			} else {
				$overwriteBody = 1;
				$body = "<p>Error uploading file!</p>\n";
			}
		} else {
			$overwriteBody = 1;
			$body = "File Upload error";
		}
	}
	$curBreadcrumb ="$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	$curBreadcrumb .=" &gt; <a href=\"gradebook.php?stu=0&gbmode={$_GET['gbmode']}&cid=$cid\">Gradebook</a> ";
	$curBreadcrumb .=" &gt; <a href=\"chgoffline.php?stu=0&cid=$cid\">Manage Offline Grades</a> &gt; Upload Multiple Grades";

	
}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
require("../header.php");
echo '<div class="breadcrumb">'.$curBreadcrumb.'</div>';
echo '<h3>Upload Multiple Grades</h3>';
if ($overwriteBody==1) {
	echo $body;
} else {		
	echo '<form enctype="multipart/form-data" method=post action="uploadmultgrades.php?cid='.$cid.'">';

	if (isset($page_fileHiddenInput)) {
		//file has been uploaded, need to know what to import
		echo $page_fileHiddenInput;
		echo '<input type="hidden" name="headerrows" value="'.$_POST['headerrows'].'" />';
		$sdate = tzdate("m/d/Y",time());
		$stime = tzdate("g:i a",time());
	?>
		<span class=form>Username is in column:</span>
		<span class=formright><input type=text name="sidcol" size=4 value="<?php echo $usernamecol+1; ?>"></span><br class=form />
		<span class=form>Show grade to students after:</span><span class=formright><input type=radio name="sdatetype" value="0" <?php if ($showdate=='0') {echo "checked=1";}?>/> Always<br/>
		<input type=radio name="sdatetype" value="sdate" <?php if ($showdate!='0') {echo "checked=1";}?>/><input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
		<a href="#" onClick="displayDatePicker('sdate', this); return false"><img src="../img/cal.gif" alt="Calendar"/></A>
		at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span><BR class=form>

		<table class="gb">
		<thead>
		  <tr><th>In column</th><th>Add this?</th><th>Name</th><th>Points</th><th>Count?</th><th>Gradebook Category</th><th>Feedback in column<br/>(blank for none)</th></tr>
		</thead>
		<tbody>
	<?php
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbcatoptions = '<option value="0" selected=1>Default</option>';
		if (mysql_num_rows($result)>0) {
			while ($row = mysql_fetch_row($result)) {
				$gbcatoptions .= "<option value=\"{$row[0]}\">{$row[1]}</option>\n";
			}
		}	
		foreach ($columndata as $col=>$data) {
			echo '<tr><td>'.($col+1).'</td>';
			echo '<td><input type="checkbox" name="addcol[]" value="'.$col.'" /></td>';
			echo '<td><input type="text" size="20" name="colname'.$col.'" value="'.htmlentities($data[0]).'" /></td>';
			echo '<td><input type="text" size="3" name="colpts'.$col.'"  value="'.$data[1].'" /></td>';
			echo '<td><select name="colcnt'.$col.'">';
			echo '<option value="1" selected="selected">Count in gradebook</option>';
			echo '<option value="0">Don\'t count and hide from students</option>';
			echo '<option value="3">Don\'t count in grade total</option>';
			echo '<option value="2">Count as extra credit</option></select></td>';
			echo '<td><select name="colgbcat'.$col.'">'.$gbcatoptions.'</select></td>';
			echo '<td><input type="text" size="3" name="colfeedback'.$col.'"  value="'.($data[2]>-1?$data[2]+1:'').'" /></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p><input type="submit" value="Upload" /></p>';
	} else {
		//need file
	?>
		<p>The uploaded file must be in Comma Separated Values (.CSV) file format, and contain a column with
		the students' usernames.  If you are including feedback as well as grades, upload will be much easier if the
		feedback is in the column immediately following the scores, and if the column header contains the word Comment or Feedback</p>
		<p>
		<span class=form>Import File: </span>
		<span class=formright>
			<input type="hidden" name="MAX_FILE_SIZE" value="300000" />
			<input name="userfile" type="file" />
		</span><br class=form />		
		<span class=form>File contains a header row:</span>
		<span class=formright>
			<input type=radio name="headerrows" value="1" checked="checked">Yes, one<br/>
			<input type=radio name="headerrows"  value="2">Yes, with second for points possible
		</span><br class=form />
		<div class=submit><input type=submit value="Continue"></div>
		</p>
	<?php
	}
	echo '</form>';
}

require("../footer.php");
	
?>
