<?php
//IMathAS:  Manage LatePasses
//(c) 2007 David Lippman
	
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	
	if (isset($_POST['latepass'])) {
		foreach ($_POST['latepass'] as $uid=>$lp) {
			$query = "UPDATE imas_students SET latepass='$lp' WHERE userid='$uid' AND courseid='$cid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$query = "UPDATE imas_courses SET latepasshrs='{$_POST['hours']}' WHERE id='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
		exit;
	}
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> ";
	echo "&gt; Manage LatePasses</div>";
	
	echo "<form id=\"mainform\" method=post action=\"latepasses.php?&cid=$cid\">";
	
?>
<div id="headerlatepasses" class="pagetitle"><h2>Manage LatePasses</h2></div>

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

function sendtoall(type) {
	  var form=document.getElementById("mainform");
	  for (var e = 0; e<form.elements.length; e++) {
	      var el = form.elements[e];
	      if (el.type=="text" && el.id!="toall" && el.id!="hours") {
		      if (type==0) {
			       el.value = parseInt(el.value) + parseInt(document.getElementById("toall").value);
		      } else if (type==1) {
			      el.value = document.getElementById("toall").value;
		      }
	      }
	   }
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
		
		if ($hassection) {
			$query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0)==0) {
				$sortorder = "sec";
			} else {
				$sortorder = "name";
			}
		} else {
			$sortorder = "name";
		}
		
		if ($hassection) {
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		}
		$query = "SELECT latepasshrs FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$hours = mysql_result($result,0,0);
		echo "<p>Late Passes extend the due date by <input type=text size=3 name=\"hours\" id=\"hours\" value=\"$hours\"/> hours</p>";
		echo "<p>To all:  <input type=\"text\" value=\"1\" id=\"toall\"/> ";
		echo '<input type=button value="Add" onClick="sendtoall(0);"/> <input type=button value="Replace" onclick="sendtoall(1)"/><p>';
		echo "<table id=myTable><thead><tr><th>Name</th>";
		if ($hassection) {
			echo '<th>Section</th>';
		}
		echo "<th>LatePasses Remaining</th></tr></thead><tbody>";
		
		$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.latepass ";
		$query .= "FROM imas_users,imas_students WHERE ";
		$query .= "imas_users.id=imas_students.userid AND imas_students.courseid='$cid'";
		
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
			
			echo "<td><input type=text size=3 name=\"latepass[{$row[0]}]\" value=\"{$row[4]}\"";
			echo " onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" /></td>";
			echo "</tr>";
		}
		
		echo "</tbody></table>";
		if ($hassection) {
			echo "<script> initSortTable('myTable',Array('S','S',false),false);</script>";
		} 

	
?>
<div class=submit><input type=submit value="Submit"></div>
</form>

<?php
	require("../footer.php");
?>


