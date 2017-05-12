<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");


/*** pre-html data manipulation, including function code *******/

function parsecsv($data) {
	$fn = $data[$_POST['fncol']-1];
	if ($_POST['fnloc']!=0) {
		$fncol = explode(' ',$fn);
		if ($_POST['fnloc']<3) {
			$fn = $fncol[$_POST['fnloc']-1];
		} else {
			$fn = $fncol[count($fncol)-1];
		}
	}
	$ln = $data[$_POST['lncol']-1];
	if ($_POST['lncol']!=$_POST['fncol'] && $_POST['lnloc']!=0) {
		$fncol = explode(' ',$ln);
	}
	if ($_POST['lnloc']!=0) {
		if ($_POST['lnloc']<3) {
			$ln = $fncol[$_POST['lnloc']-1];
		} else {
			$ln = $fncol[count($fncol)-1];
		}
	}
	$fn = preg_replace('/\W/','',$fn);
	$ln = preg_replace('/\W/','',$ln);
	$fn = ucfirst(strtolower($fn));
	$ln = ucfirst(strtolower($ln));
	if ($_POST['unusecol']==0) {
		$un = strtolower($fn.'_'.$ln);
	} else {
		$un = $data[$_POST['unloc']-1];
		$un = preg_replace('/\W/','',$un);
	}
	if ($_POST['emailloc']>0) {
		$email = $data[$_POST['emailloc']-1];
		if ($email=='') {
			$email = 'none@none.com';
		}
	} else {
		$email = 'none@none.com';
	}
	if ($_POST['codetype']==1) {
		$code = $data[$_POST['code']-1];
	} else {
		$code = 0;
	}
	if ($_POST['sectype']==1) {
		$sec = $_POST['secval'];
	} else if ($_POST['sectype']==2) {
		$sec = $data[$_POST['seccol']-1];
	} else {
		$sec = 0;
	}
	if ($_POST['pwtype']==3) {
		$pw = $data[$_POST['pwcol']-1];
	} else {
		$pw = 0;
	}
	return array($un,$fn,$ln,$email,$code,$sec,$pw);
}

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Import Students";
$isadmin = false;

//data manipulation here

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid)) && $myrights<100) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['cid']) && $_GET['cid']=="admin" && $myrights <100) {
 	$overwriteBody = 1;
	$body = "You need to log in as an admin to access this page this way";
} elseif (!(isset($_GET['cid']))) {
 	$overwriteBody = 1;
	$body = "You need to access this page from a menu link";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

	$cid = Sanitize::courseId($_GET['cid']);
	$isadmin = ($myrights==100 && $cid=="admin") ? true : false ;
	if ($isadmin) {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> &gt; Import Students</div>\n";
	} else {
		$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Import Students</div>\n";
	}

	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION
	if (isset($_POST['process'])) {
		if (isset($CFG['GEN']['newpasswords'])) {
			require_once("../includes/password.php");
		}
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . Sanitize::sanitizeFilenameAndCheckBlacklist($_POST['filename']);
		$handle = fopen($filename,'r');
		if ($_POST['hdr']==1) {
			$data = fgetcsv($handle,2096);
		}

		while (($data = fgetcsv($handle,2096))!==false) {
			$arr = parsecsv($data);
			for ($i=0;$i<count($arr);$i++) {
				$arr[$i] = trim($arr[$i]);
			}
			//DB addslashes_deep($arr);
			if (trim($arr[0])=='' || trim($arr[0])=='_') {
				continue;
			}
			//DB $query = "SELECT id FROM imas_users WHERE SID='$arr[0]'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
				//DB $id = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
			$stm->execute(array(':SID'=>$arr[0]));
			if ($stm->rowCount()>0) {
				$id = $stm->fetchColumn(0);
				echo "Username {$arr[0]} already existed in system; using existing<br/>\n";
			} else {
				if (($_POST['pwtype']==0 || $_POST['pwtype']==1) && strlen($arr[0])<4) {
					if (isset($CFG['GEN']['newpasswords'])) {
						$pw = password_hash($arr[0], PASSWORD_DEFAULT);
					} else {
						$pw = md5($arr[0]);
					}
				} else {
					if ($_POST['pwtype']==0) {
						if (isset($CFG['GEN']['newpasswords'])) {
							$pw = password_hash(substr($arr[0],0,4), PASSWORD_DEFAULT);
						} else {
							$pw = md5(substr($arr[0],0,4));
						}
					} else if ($_POST['pwtype']==1) {
						if (isset($CFG['GEN']['newpasswords'])) {
							$pw = password_hash(substr($arr[0],-4), PASSWORD_DEFAULT);
						} else {
							$pw = md5(substr($arr[0],-4));
						}
					} else if ($_POST['pwtype']==2) {
						if (isset($CFG['GEN']['newpasswords'])) {
							$pw = password_hash($_POST['defpw'], PASSWORD_DEFAULT);
						} else {
							$pw = md5($_POST['defpw']);
						}
					} else if ($_POST['pwtype']==3) {
						if (trim($arr[6])=='') {
							echo "Password for {$arr[0]} is blank; skipping import<br/>";
							continue;
						}
						if (isset($CFG['GEN']['newpasswords'])) {
							$pw = password_hash($arr[6], PASSWORD_DEFAULT);
						} else {
							$pw = md5($arr[6]);
						}
					}
				}
				//DB $query = "INSERT INTO imas_users (SID,FirstName,LastName,email,rights,password) VALUES ('$arr[0]','$arr[1]','$arr[2]','$arr[3]',10,'$pw')";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $id = mysql_insert_id();
				$stm = $DBH->prepare("INSERT INTO imas_users (SID,FirstName,LastName,email,rights,password) VALUES (:SID, :FirstName, :LastName, :email, :rights, :password)");
				$stm->execute(array(':SID'=>$arr[0], ':FirstName'=>$arr[1], ':LastName'=>$arr[2], ':email'=>$arr[3], ':rights'=>10, ':password'=>$pw));
				$id = $DBH->lastInsertId();
			}
			if ($_POST['enrollcid']!=0 || !$isadmin) {
				if ($isadmin) {
					$ncid = Sanitize::onlyInt($_POST['enrollcid']);
				} else {
					$ncid = $cid;
				}
				//DB $vals = "'$id','$ncid'";
				//DB $query = "SELECT id FROM imas_students WHERE userid='$id' AND courseid='$ncid'";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$stm = $DBH->prepare("SELECT id FROM imas_students WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$ncid));
				if ($stm->rowCount()>0) {
					echo "Username {$arr[0]} already enrolled in course.  Skipping<br/>";
					continue;
				}

				//DB $query = "INSERT INTO imas_students (userid,courseid";
				//DB if ($_POST['codetype']==1) {
				//DB 	$query .= ",code";
				//DB 	$vals .= ",'$arr[4]'";
				//DB }
				//DB if ($_POST['sectype']>0) {
				//DB 	$query .= ",section";
				//DB 	$vals .= ",'$arr[5]'";
				//DB }
				//DB $query .= ") VALUES ($vals)";

				$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,code,section) VALUES (:userid, :courseid, :code, :section)");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$ncid,
					':code'=>($_POST['codetype']==1)?$arr[4]:null,
					':section'=>($_POST['sectype']>0)?$arr[5]:null));
			}

		}

		fclose($handle);
		unlink($filename);
		$overwriteBody = 1;
		$body = "Import Successful<br/>\n";
		$body .= "<p>";
		if ($isadmin) {
			$body .= "<a href=\"". $urlmode . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php\">Back to Admin Page";
		} else {
			$body .= "<a href=\"". $urlmode . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST'])  . $imasroot . "/course/course.php?cid=$cid\">Back to Course Page";
		}
		$body .= "</a></p>\n";

	} elseif (isset($_FILES['userfile'])) {  //STEP 2 DATA MANIPULATION
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . Sanitize::sanitizeFilenameAndCheckBlacklist($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			$$uploadfilename = basename($uploadfile);
			$page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".Sanitize::sanitizeFilenameAndCheckBlacklist($$uploadfilename)."\" />\n";
		} else {
			$overwriteBody = 1;
			$body = "<p>Error uploading file!</p>\n";
		}
		$handle = fopen($uploadfile,'r');
		if ($_POST['hdr']==1) {
			$data = fgetcsv($handle,2096);
		}

		if ($_POST['codetype']==1) {
			$page_columnFiveLabel = "Code";
		}
		if ($_POST['sectype']>0) {
			$page_columnFiveLabel = "Section";
		}

		$page_sampleImport = array();
		for ($i=0; $i<5; $i++) {
			$data = fgetcsv($handle,2096);
			if ($data!=FALSE) {
				$arr = parsecsv($data);
				$page_sampleImport[$i]['col1'] = $arr[0];
				$page_sampleImport[$i]['col2'] = $arr[1];
				$page_sampleImport[$i]['col3'] = $arr[2];
				$page_sampleImport[$i]['col4'] = $arr[3];
				if ($_POST['codetype']==1) {
					$page_sampleImport[$i]['col5'] = $arr[4];
				}
				if ($_POST['sectype']>0) {
					$page_sampleImport[$i]['col5'] = $arr[5];
				}
			}
		}
	} else { //STEP 1 DATA MANIPULATION

		if ($isadmin) {
			//DB $query = "SELECT imas_courses.id,imas_courses.name,imas_users.LastName,imas_users.FirstName FROM imas_courses,imas_users ";
			//DB $query .= "WHERE imas_users.id=imas_courses.ownerid ";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "SELECT imas_courses.id,imas_courses.name,imas_users.LastName,imas_users.FirstName FROM imas_courses,imas_users ";
			$query .= "WHERE imas_users.id=imas_courses.ownerid ";
			$stm = $DBH->query($query);
			$i=0;
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_adminUserSelectVals[$i] = $row[0];
				$page_adminUserSelectLabels[$i] = "$row[1] ($row[2], $row[3])";
				$i++;
			}
		}
	}
//END OF DATA MANIPULATION
}


/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
	echo $curBreadcrumb;
?>

	<form enctype="multipart/form-data" method=post action="importstu.php?cid=<?php echo $cid ?>">

<?php
	if (isset($_FILES['userfile'])) {  //STEP 2 DISPLAY
		echo $page_fileHiddenInput;
?>

		<div id="headerimportstu" class="pagetitle"><h2>Import Students</h2></div>
		<p>The first 5 students in the file are listed below.  Check the columns were identified correctly</p>
			<table class=gb>
			<thead>
				<tr>
					<th>Username</th><th>Firstname</th><th>Lastname</th><th>e-mail</th>
					<th><?php echo $page_columnFiveLabel ?></th>
				</tr>
			</thead>
			<tbody>

<?php
		for ($i=0; $i<count($page_sampleImport); $i++) {
?>
				<tr>
					<td><?php echo $page_sampleImport[$i]['col1'] ?></td>
					<td><?php echo $page_sampleImport[$i]['col2'] ?></td>
					<td><?php echo $page_sampleImport[$i]['col3'] ?></td>
					<td><?php echo $page_sampleImport[$i]['col4'] ?></td>
					<td><?php echo $page_sampleImport[$i]['col5'] ?></td>
				</tr>

<?php
		}
?>
			</tbody>
			</table>

<?php
		foreach($_POST as $k=>$v) {
			echo "<input type=hidden name=\"$k\" value=\"$v\">\n";
		}
		echo "<p><input type=submit name=\"process\" value=\"Accept and Enroll\"></p>\n";
	} else { //STEP 1 DISPLAY
?>
		<div id="headerimportstu" class="pagetitle"><h2>Import Students from File</h2></div>
		<p>Register and enroll students from a CSV (comma separated values) file</p>

		<span class=form>Import File: </span>
		<span class=formright>
			<input type="hidden" name="MAX_FILE_SIZE" value="300000" />
			<input name="userfile" type="file" />
		</span><br class=form>
		<span class=form>File contains a header row:</span>
		<span class=formright>
			<input type=radio name=hdr value="1">Yes<br/>
			<input type=radio name=hdr value="0" CHECKED>No<br/>
		</span><br class=form>

		<span class=form>First name is in column:</span>
		<span class=formright><input type=text name=fncol size=4 value="3"></span><br class=form>

		<span class=form>In that column, first name is:</span>
		<span class=formright>
			<select name=fnloc>
				<option value="0">Whole entry</option>
				<option value="1">First word in entry</option>
				<option value="2" SELECTED>Second word in entry</option>
				<option value="3">Last word in entry</option>
			</select>
		</span><br class=form>

		<span class=form>Last name is in column:</span>
		<span class=formright><input type=text name=lncol size=4 value="3"></span><br class=form>

		<span class=form>In that column, Last name is:</span>
		<span class=formright>
			<select name=lnloc>
				<option value="0">Whole entry</option>
				<option value="1" SELECTED>First word in entry</option>
				<option value="2">Second word in entry</option>
				<option value="3">Last word in entry</option>
			</select>
		</span><br class=form>

		<span class=form>Email address is in column:<br/>Enter 0 if no email column</span>
		<span class=formright><input type=text name=emailloc size=4 value="7"></span><br class=form>
<?php
if (isset($CFG['GEN']['allowInstrImportStuByName']) && $CFG['GEN']['allowInstrImportStuByName']==false) {
?>

		<span class=form>Unique username is in column:</span>
		<span class=formright>
			<input type=hidden name=unusecol value="1"/><input type=text name=unloc size=4 value="2"/>
		</span><br class=form>
<?php
} else {
	?>

		<span class=form>Does a column contain a desired username:</span>
		<span class=formright>
			<input type=radio name=unusecol value="1" CHECKED>Yes, Column
			<input type=text name=unloc size=4 value="2"/><br/>
			<input type=radio name=unusecol value="0">No, Use as username: firstname_lastname
		</span><br class=form>
<?php
}
?>
		<span class=form>Set password to:</span>
		<span class=formright>
			<input type=radio name=pwtype value="0">First 4 characters of username<br/>
			<input type=radio name=pwtype value="1" CHECKED>Last 4 characters of username<br/>
			<input type=radio name=pwtype value="3">Use value in column
			<input type=text name="pwcol" size=4 value="1"/><br/>
			<input type=radio name=pwtype value="2">Set to:
			<input type=text name="defpw" value="password"/>
		</span><br class=form>

		<span class=form>Assign code number?</span>
		<span class=formright>
			<input type=radio name=codetype value="0" CHECKED>No<br/>
			<input type=radio name=codetype value="1">Yes, use value in column:
			<input type=text name="code" size=4 value="1"/>
		</span><br class=form>

		<span class=form>Assign section value?</span>
		<span class=formright>
			<input type=radio name=sectype value="0" CHECKED>No<br/>
			<input type=radio name=sectype value="1">Yes, use:
			<input type=text name="secval" size=6 value=""/><br/>
			<input type=radio name=sectype value="2">Yes, use value in column:
			<input type=text name="seccol" size=4 value="4"/>
		</span><br class=form>

		<span class=form>Enroll students in:</span><span class=formright>
<?php
		if ($isadmin) {
			writeHtmlSelect ("enrollcid",$page_adminUserSelectVals,$page_adminUserSelectLabels,$selectedVal=null,$defaultLabel="None",$defaultVal=0,$actions=null);
		} else {
			echo "This class";
		}
?>
		</span><br class=form>

		<div class=submit><input type=submit value="Submit and Review"></div>

<?php
	}
	echo "	</form>\n";
}

require("../footer.php");
?>
