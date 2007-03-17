<?php
//IMathAS:  Add/modify gradebook items/grades
//(c) 2006 David Lippman
	//add/modify gbitem w/ grade edit
	//grade edit
	//single grade edit
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	
	if (isset($_GET['del'])) {
		if (isset($_GET['confirm'])) {
			$query = "DELETE FROM imas_grades WHERE gbitemid='{$_GET['del']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_gbitems WHERE id='{$_GET['del']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid={$_GET['cid']}");
			exit;
		} else {
			require("../header.php");
			echo "<p>Are you SURE you want to delete this item and all associated grades from the gradebook?</p>";
			echo "<p><a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&del={$_GET['del']}&confirm=true\">Delete Item</a>";
			echo " <a href=\"gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid\">Nevermind</a>";
			require("../footer.php");
			exit;
		}
		
	}
	if (isset($_POST['name'])) {
		require_once("parsedatetime.php");
		if ($_POST['sdatetype']=='0') {
			$showdate = 0;
		} else {
			$showdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		}
		if ($_GET['gbitem']=='new') {
			$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb) VALUES ";
			$query .= "('$cid','{$_POST['name']}','{$_POST['points']}',$showdate,'{$_POST['gbcat']}','{$_POST['cntingb']}') ";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$_GET['gbitem'] = mysql_insert_id();
			$isnewitem = true;
		} else {
			$query = "UPDATE imas_gbitems SET name='{$_POST['name']}',points='{$_POST['points']}',showdate=$showdate,gbcategory='{$_POST['gbcat']}',cntingb='{$_POST['cntingb']}' ";
			$query .= "WHERE id='{$_GET['gbitem']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$isnewitem = false;
		}
	}
	if (isset($_POST['score'])) {
		foreach($_POST['score'] as $k=>$sc) {
			if ($sc!='') {
				/* //moved to javascript
				if (strpos($sc,'+')!==false || strpos($sc,'-')!==false) {
					$sc = preg_replace('/[^\d\.\+\-]/','',$sc);
					$sc = @eval("return ($sc);"); //will set to 0 if error
				}*/
				$query = "UPDATE imas_grades SET score='$sc' WHERE userid='$k' AND gbitemid='{$_GET['gbitem']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	}
	if (isset($_POST['newscore'])) {
		foreach($_POST['newscore'] as $k=>$sc) {
			if ($sc!='') {
				$query = "INSERT INTO imas_grades (gbitemid,userid,score) VALUES ";
				$query .= "('{$_GET['gbitem']}','$k','$sc')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	}
	if (isset($_POST['score']) || isset($_POST['newscore']) || isset($_POST['name'])) {
		if ($isnewitem && isset($_POST['doupload'])) {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/uploadgrades.php?gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}");
		} else {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid={$_GET['cid']}");
		}
		exit;
	}
	
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&gbmode={$_GET['gbmode']}&cid=$cid\">Gradebook</a> ";
	if ($_GET['stu']>0) {echo "&gt; <a href=\"gradebook.php?stu={$_GET['stu']}&gbmode=$gbmode&cid=$cid\">Student Detail</a> ";}
	echo "&gt; Offline Grades</div>";
	
	if ($_GET['gbitem']=='new') {
		echo "<h3>Add Offline Grades</h3>";
	} else {
		echo "<h3>Modify Offline Grades</h3>";
	}
	echo "<form method=post action=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades={$_GET['grades']}\">";
	
	if ($_GET['grades']=='all') {
		if ($_GET['gbitem']=='new') {
			$name = '';
			$points = 0;
			$showdate = time();
			$gbcat = 0;
			$cntingb = 1;
		} else {
			$query = "SELECT name,points,showdate,gbcategory,cntingb FROM imas_gbitems WHERE id='{$_GET['gbitem']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			list($name,$points,$showdate,$gbcat,$cntingb) = mysql_fetch_row($result);
		}
		if ($showdate!=0) {
			$sdate = tzdate("m/d/Y",$showdate);
			$stime = tzdate("g:i a",$showdate);
		} else {
			$sdate = tzdate("m/d/Y",time()+60*60);
			$stime = tzdate("g:i a",time()+60*60);
		}
?>

<span class=form>Name:</span><span class=formright><input type=text name="name" value="<?php echo $name;?>"/></span><br class="form"/>

<span class=form>Points:</span><span class=formright><input type=text name="points" size=3 value="<?php echo $points;?>"/></span><br class="form"/>
<script src="../javascript/CalendarPopup.js"></script>
<SCRIPT LANGUAGE="JavaScript" ID="js1">
var cal1 = new CalendarPopup();
</SCRIPT>
<span class=form>Show grade to students after:</span><span class=formright><input type=radio name="sdatetype" value="0" <?php if ($showdate=='0') {echo "checked=1";}?>/> Always<br/>
<input type=radio name="sdatetype" value="sdate" <?php if ($showdate!='0') {echo "checked=1";}?>/><input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;" NAME="anchor1" ID="anchor1"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span><BR class=form>

<?php
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo "<span class=form>Gradebook Category:</span><span class=formright><select name=gbcat id=gbcat>\n";
		echo "<option value=\"0\" ";
		if ($gbcat==0) {
			echo "selected=1 ";
		}
		echo ">Default</option>\n";
		if (mysql_num_rows($result)>0) {
			
			while ($row = mysql_fetch_row($result)) {
				echo "<option value=\"{$row[0]}\" ";
				if ($gbcat==$row[0]) {
					echo "selected=1 ";
				}
				echo ">{$row[1]}</option>\n";
			}
			
		}	
		echo "</select></span><br class=form>\n";
		
		echo "<span class=form>Count: </span><span class=formright>";
		echo '<input type=radio name="cntingb" value="1" ';
		if ($cntingb==1) { echo "checked=1";}
		echo ' /> Count in Gradebook<br/><input type=radio name="cntingb" value="0" ';
		if ($cntingb==0) { echo "checked=1";}
		echo ' /> Don\'t count in grade total<br/><input type=radio name="cntingb" value="2" ';
		if ($cntingb==2) {echo "checked=1";}
		echo ' /> Count as Extra Credit</span><br class=form />';
		
		if ($_GET['gbitem']!='new') {
			echo "<span class=form></span><span class=formright><a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&del={$_GET['gbitem']}\">Delete Item</a></span><br class=form />";
		} else {
			echo "<span class=form>Upload grades?</span><span class=formright><input type=checkbox name=\"doupload\" /></span><br class=form />";
		}
	}
?>
<script type="text/javascript">
function onenter(e,field) {
	if (window.event) {
		var key = window.event.keyCode;
	} else if (e.which) {
		var key = e.which;
	}
	if (key==13) {
		var i;
                for (i = 0; i < field.form.elements.length; i++)
                   if (field == field.form.elements[i])
                       break;
              i = (i + 1) % field.form.elements.length;
              field.form.elements[i].focus();
              return false;
	} else {
		return true;
	}
}
function onarrow(e,field) {
	if (window.event) {
		var key = window.event.keyCode;
	} else if (e.which) {
		var key = e.which;
	}
	
	if (key==40 || key==38) {
		var i;
                for (i = 0; i < field.form.elements.length; i++)
                   if (field == field.form.elements[i])
                       break;
		
	      if (key==38) {
		      i = i-1;
		      if (i<0) { i=0;}
	      } else {
		      i = (i + 1) % field.form.elements.length;
	      }
	      if (field.form.elements[i].type=='text') {
		      field.form.elements[i].focus();
	      }
              return false;
	} else {
		return true;
	}
}

function doonblur(value) {
	value = value.replace(/[^\d\.\+\-]/g,'');
	if (value=='') {return ('');}
	return (eval(value));
}
</script>
<?php
		$query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
		$query .= "AND imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			$hassection = true;
		} else {
			$hassection = false;
		}
		if (isset($_GET['sortorder'])) {
			$sortorder = $_GET['sortorder'];
		} else {
			if ($hassection) {
				$sortorder = "sec";
			} else {
				$sortorder = "name";
			}
		}
		if ($_GET['grades']=='all' && $_GET['gbitem']!='new') {
			echo "<p><a href=\"uploadgrades.php?gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}\">Upload Grades</a></p>";
		}
		if ($hassection && ($_GET['gbitem']=='new' || $_GET['grades']=='all')) {
			if ($sortorder=="name") {
				echo "<p>Sorted by name.  <a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades={$_GET['grades']}&sortorder=sec\">";
				echo "Sort by section</a>.</p>";
			} else if ($sortorder=="sec") {
				echo "<p>Sorted by section.  <a href=\"addgrades.php?stu={$_GET['stu']}&gbmode={$_GET['gbmode']}&cid=$cid&gbitem={$_GET['gbitem']}&grades={$_GET['grades']}&sortorder=name\">";
				echo "Sort by name</a>.</p>";
			}
		}
		echo "<table><thead><tr><th>Name</th>";
		if ($hassection) {
			echo '<th>Section</th>';
		}
		echo "<th>Grade</th></tr></thead><tbody>";
		if ($_GET['gbitem']=="new") {
			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section ";
			$query .= "FROM imas_users,imas_students WHERE ";
			$query .= "imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
		} else {
			$query = "SELECT userid,score FROM imas_grades WHERE gbitemid='{$_GET['gbitem']}' ";
			if ($_GET['grades']!='all') {
				$query .= "AND userid='{$_GET['grades']}' ";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$score[$row[0]] = $row[1];
			}
			$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section FROM imas_users,imas_students ";
			if ($_GET['grades']!='all') {
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id='{$_GET['grades']}' ";
			} else {
				$query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
			}
		}
		if ($hassection && $sortorder=="sec") {
			 $query .= " ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			 $query .= " ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
		while ($row = mysql_fetch_row($result)) {
			echo "<tr><td>{$row[1]}, {$row[2]}</td>";
			if ($hassection) {
				echo "<td>{$row[3]}</td>";
			}
			if (isset($score[$row[0]])) {
				echo "<td><input type=text size=3 name=\"score[{$row[0]}]\" value=\"";
				echo $score[$row[0]];
			} else {
				echo "<td><input type=text size=3 name=\"newscore[{$row[0]}]\" value=\"";
			}
			echo "\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" /></td></tr>";
		}
		
		echo "</tbody></table>";

	
?>
<div class=submit><input type=submit value="Submit"></div>
</form>

<?php
	require("../footer.php");
?>


