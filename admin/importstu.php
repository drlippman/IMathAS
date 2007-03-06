<?php
	//Import and enroll students from a file
	//(c) 2006 David Lippman
	
	require("../validate.php");
	require("../header.php");
	if (!(isset($teacherid)) && $myrights<100) {
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$isadmin = false;
	if (isset($_GET['cid']) && $_GET['cid']=="admin") {
		if ($myrights <100) {
			echo "You need to log in as an admin to access this page this way";
			require("../footer.php");
			exit;
		} else {
			$isadmin = true;
		}
	} 
	$cid = $_GET['cid'];
	
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . $_POST['filename'];
		$handle = fopen($filename,'r');
		if ($_POST['hdr']==1) {
			$data = fgetcsv($handle,2096);
		}
		
		while (($data = fgetcsv($handle,2096))!==false) {
			$arr = parsecsv($data);
			addslashes_deep($arr);
			$query = "SELECT id FROM imas_users WHERE SID='$arr[0]'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$id = mysql_result($result,0,0);
				echo "Username {$arr[0]} already existed in system; using existing<br/>\n";
			} else {
				if (($_POST['pwtype']==0 || $_POST['pwtype']==1) && strlen($arr[0])<4) {
					$pw = md5($arr[0]);
				} else {
					if ($_POST['pwtype']==0) {
						$pw = md5(substr($arr[0],0,4));
					} else if ($_POST['pwtype']==1) {
						$pw = md5(substr($arr[0],-4));
					} else if ($_POST['pwtype']==2) {
						$pw = md5($_POST['defpw']);
					} else if ($_POST['pwtype']==3) {
						$pw = md5($arr[6]);
					}
				}
				$query = "INSERT INTO imas_users (SID,FirstName,LastName,email,rights,password) VALUES ('$arr[0]','$arr[1]','$arr[2]','$arr[3]',10,'$pw')";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$id = mysql_insert_id();
			}
			if ($_POST['enrollcid']!=0 || !$isadmin) {
				if ($isadmin) {
					$ncid = $_POST['enrollcid'];
				} else {
					$ncid = $cid;
				}
				$vals = "'$id','$ncid'";
				$query = "INSERT INTO imas_students (userid,courseid";
				if ($_POST['codetype']==1) {
					$query .= ",code";
					$vals .= ",'$arr[4]'";
				}
				if ($_POST['sectype']>0) {
					$query .= ",section";
					$vals .= ",'$arr[5]'";
				}
				$query .= ") VALUES ($vals)";
				
				mysql_query($query) or die("Query failed : $query" . mysql_error());
			}
			
		}
		
		fclose($handle);
		unlink($filename);
		echo "Import Successful<br/>\n";
		echo "<p><a href=\"";
		if ($isadmin) {
			echo 'http://'. $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php\">Back to Admin Page";
		} else {
			echo "http://" . $_SERVER['HTTP_HOST']  . $imasroot . "/course/course.php?cid=$cid\">Back to Course Page";
		}
		echo "</a></p>\n";
		exit;
	}
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
		}
		if ($_POST['emailloc']>0) {
			$email = $data[$_POST['emailloc']-1];
			if ($email=='') {
				$email = 'none@none.com';
			}
		} else {
			$email = 'setme@none.com';
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
	
	
	if ($isadmin) {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"admin.php\">Admin</a> &gt; Import Students</div>\n";
	} else {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Import Students</div>\n";
	}
	echo "<form enctype=\"multipart/form-data\" method=post action=\"importstu.php?cid=$cid\">\n";
	
	if (isset($_FILES['userfile'])) {
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			echo "<input type=hidden name=\"filename\" value=\"".basename($uploadfile)."\" />\n";
		} else {
			echo "<p>Error uploading file!</p>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;
		}
		$handle = fopen($uploadfile,'r');
		if ($_POST['hdr']==1) {
			$data = fgetcsv($handle,2096);
		}
		echo "<h3>Import Students</h3>\n";
		echo "<p>The first 5 students in the file are listed below.  Check the columns were identified correctly</p>\n";
		echo "<table class=gb><thead><tr><th>Username</th><th>Firstname</th><th>Lastname</th><th>e-mail</th>";
		if ($_POST['codetype']==1) {
			echo "<th>Code</th>";
		}
		if ($_POST['sectype']>0) {
			echo "<th>Section</th>";
		}
		echo "</tr></thead><tbody>\n";
		for ($i=0; $i<5; $i++) {
			$data = fgetcsv($handle,2096);
			if ($data!=FALSE) {
			$arr = parsecsv($data);
			echo "<tr><td>$arr[0]</td><td>$arr[1]</td><td>$arr[2]</td><td>$arr[3]</td>";
			if ($_POST['codetype']==1) {
				echo "<td>$arr[4]</td>";
			}
			if ($_POST['sectype']>0) {
				echo "<td>$arr[5]</td>";
			}
			echo "</tr>\n";
			}
		}
		echo "</tbody></table>\n";
		foreach($_POST as $k=>$v) {
			echo "<input type=hidden name=\"$k\" value=\"$v\">\n";
		}
		echo "<p><input type=submit name=\"process\" value=\"Accept and Enroll\"></p>\n";
		require("../footer.php");
		exit;
	}
?>
<h3>Import Students from File</h3>
<p>Register and enroll students from a CSV (comma separated values) file</p>

<span class=form>Import File: </span><span class=formright><input type="hidden" name="MAX_FILE_SIZE" value="300000" />
		<input name="userfile" type="file" /></span><br class=form>		

<span class=form>File contains a header row:</span>
<span class=formright><input type=radio name=hdr value="1">Yes<br/>
			<input type=radio name=hdr value="0" CHECKED>No<br/></span><br class=form>

<span class=form>First name is in column:</span>
<span class=formright><input type=text name=fncol size=4 value="3"></span><br class=form>

<span class=form>In that column, first name is:</span>
<span class=formright><select name=fnloc>
		<option value="0">Whole entry</option>
		<option value="1">First word in entry</option>
		<option value="2" SELECTED>Second word in entry</option>
		<option value="3">Last word in entry</option>
		</select></span><br class=form>
		
<span class=form>Last name is in column:</span>
<span class=formright><input type=text name=lncol size=4 value="3"></span><br class=form>

<span class=form>In that column, Last name is:</span>
<span class=formright><select name=lnloc>
		<option value="0">Whole entry</option>
		<option value="1" SELECTED>First word in entry</option>
		<option value="2">Second word in entry</option>
		<option value="3">Last word in entry</option>
		</select></span><br class=form>

<span class=form>Email address is in column:<br/>Enter 0 if no email column</span>
<span class=formright><input type=text name=emailloc size=4 value="7"></span><br class=form>
		
<span class=form>Does a column contain a desired username:</span>
<span class=formright><input type=radio name=unusecol value="1" CHECKED>Yes, Column <input type=text name=unloc size=4 value="2"/><br/>
		<input type=radio name=unusecol value="0">No, Use as username: firstname_lastname</span><br class=form>

<span class=form>Set password to:</span>
<span class=formright><input type=radio name=pwtype value="0">First 4 characters of username<br/>
	<input type=radio name=pwtype value="1" CHECKED>Last 4 characters of username<br/>
	<input type=radio name=pwtype value="3">Use value in column <input type=text name="pwcol" size=4 value="1"/><br/>
	<input type=radio name=pwtype value="2">Set to: <input type=text name="defpw" value="password"/></span><br class=form>

<span class=form>Assign code number?</span>
<span class=formright><input type=radio name=codetype value="0" CHECKED>No<br/>
	<input type=radio name=codetype value="1">Yes, use value in column: <input type=text name="code" size=4 value="1"/>
	</span><br class=form>
	
<span class=form>Assign section value?</span>
<span class=formright><input type=radio name=sectype value="0" CHECKED>No<br/>
	<input type=radio name=sectype value="1">Yes, use: <input type=text name="secval" size=6 value=""/><br/>
	<input type=radio name=sectype value="2">Yes, use value in column: <input type=text name="seccol" size=4 value="4"/>
	</span><br class=form>

<span class=form>Enroll students in:</span><span class=formright>
<?php
	if ($isadmin) {
		echo "<select name=enrollcid>";
		echo "<option value=\"0\">None</option>\n";

		$query = "SELECT imas_courses.id,imas_courses.name,imas_users.LastName,imas_users.FirstName FROM imas_courses,imas_users ";
		$query .= "WHERE imas_users.id=imas_courses.ownerid ";
		
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<option value=\"{$row[0]}\">$row[1] ($row[2], $row[3])</option>";
		}
		echo "</select>";
	} else {
		echo "This class";
	}
?>
</span><br class=form>

<div class=submit><input type=submit value="Submit and Review"></div>

<?php
	require("../footer.php");
	
	
?>
			

