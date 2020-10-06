<?php
//IMathAS:  Manage LatePasses
//(c) 2007 David Lippman

	require("../init.php");


	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($_POST['hours'])) {
		if (isset($_POST['latepass'])) {
			foreach ($_POST['latepass'] as $uid=>$lp) {
				$stm = $DBH->prepare("UPDATE imas_students SET latepass=:latepass WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':latepass'=>$lp, ':userid'=>$uid, ':courseid'=>$cid));
			}
		}
		$stm = $DBH->prepare("UPDATE imas_courses SET latepasshrs=:latepasshrs WHERE id=:id");
		$stm->execute(array(':latepasshrs'=>$_POST['hours'], ':id'=>$cid));
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		exit;
	}

    require("../header.php");
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
	echo "<a href=\"listusers.php?cid=$cid\">Roster</a> ";
	echo "&gt; Manage LatePasses</div>";

	echo "<form id=\"mainform\" method=post action=\"latepasses.php?&cid=$cid\">";

?>
<div id="headerlatepasses" class="pagetitle"><h1>Manage LatePasses</h1></div>

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
		$query .= "AND imas_students.courseid=:courseid AND imas_students.section IS NOT NULL";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid));
		if ($stm->fetchColumn(0)>0) {
			$hassection = true;
		} else {
			$hassection = false;
		}

		if ($hassection) {
			$stm = $DBH->prepare("SELECT usersort FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			if ($stm->fetchColumn(0)==0) {
				$sortorder = "sec";
			} else {
				$sortorder = "name";
			}
		} else {
			$sortorder = "name";
		}

		if ($hassection) {
			echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js\"></script>\n";
		}
		$stm = $DBH->prepare("SELECT latepasshrs FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$hours = $stm->fetchColumn(0);
		echo '<p>Students can redeem LatePasses for automatic extensions to assessments where allowed by the instructor. ';
		echo 'In each assessment\'s settings, an instructor can specify whether LatePasses are allowed, ';
		echo 'limit the number of passes allowed, limit whether they can be used after the due date, ';
		echo 'or specify a hard date after which LatePasses are not allowed.</p>';
		echo "<p>Late Passes extend the due date by <input type=text size=3 name=\"hours\" id=\"hours\" value=\"" . Sanitize::encodeStringForDisplay($hours) . "\"/> hours</p>";
		echo "<p>To all students:  <input type=\"text\" size=\"3\" value=\"1\" id=\"toall\"/> ";
		echo '<input type=button value="Add" onClick="sendtoall(0);"/> <input type=button value="Replace" onclick="sendtoall(1)"/><p>';
		echo "<table id=myTable><thead><tr><th>Name</th>";
		if ($hassection) {
			echo '<th>Section</th>';
		}
		echo "<th>LatePasses Remaining</th></tr></thead><tbody>";
		$query = "SELECT imas_users.id,imas_users.LastName,imas_users.FirstName,imas_students.section,imas_students.latepass ";
		$query .= "FROM imas_users,imas_students WHERE ";
		$query .= "imas_users.id=imas_students.userid AND imas_students.courseid=:courseid";

		if ($hassection && $sortorder=="sec") {
			 $query .= " ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			 $query .= " ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo "<tr><td>" . Sanitize::encodeStringForDisplay($row[1]) . ", " . Sanitize::encodeStringForDisplay($row[2]) . "</td>";
			if ($hassection) {
				echo "<td>" . Sanitize::encodeStringForDisplay($row[3]) . "</td>";
			}

			echo "<td><input type=text size=3 name=\"latepass[" . Sanitize::encodeStringForDisplay($row[0]) . "]\" value=\"" . Sanitize::encodeStringForDisplay($row[4]) . "\"";
			echo " onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" /></td>";
			echo "</tr>";
		}

		echo "</tbody></table>";
		if ($hassection) {
			echo "<script> initSortTable('myTable',Array('S','S',false),false);</script>";
		}

		echo '<div class="submit"><input type="submit" value="'._('Save Changes').'"></div>';

?>

</form>

<?php
	require("../footer.php");
?>
