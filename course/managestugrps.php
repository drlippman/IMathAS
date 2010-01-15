<?php
//IMathAS:  Manage student groups
//(c) 2010 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/stugroups.php");

/*** pre-html data manipulation, including function code *******/
$cid = $_GET['cid'];
if ( isset($_GET['grpsetid'])) {
	$grpsetid =  $_GET['grpsetid'];
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Manage Student Groups";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else {
	if (isset($_GET['addgrp']) && isset($_POST['grpname']) && isset($_GET['grpsetid'])) {
		//adding a group.  Could be a "add new group" only, or adding a new group while assigning students
		$query = "INSERT INTO imas_stugroups (groupsetid,name) VALUES ('$grpsetid','{$_POST['grpname']}')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (!isset($_POST['stustoadd'])) { //if not adding students also
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid={$_GET['grpsetid']}");
			exit();
		} else {
			$_POST['addtogrpid'] = mysql_insert_id();
			$_GET['addstutogrp'] = true;
		}
	} 
	if (isset($_GET['addgrpset'])) {
		//adding groupset
		if (isset($_POST['grpsetname'])) {
			//if name is set
			$query = "INSERT INTO imas_stugroupset (name,courseid) VALUES ('{$_POST['grpsetname']}','$cid')";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
			exit();
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Add Collection";
	} else if (isset($_GET['delgrpset'])) {
		//deleting groupset
		if (isset($_GET['confirm'])) {
			//if name is set
			deletegroupset($_GET['delgrpset']);
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['delgrpset']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
			
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Delete Collection";
	} else if (isset($_GET['rengrpset'])) {
		//renaming groupset	
		if (isset($_POST['grpsetname'])) {
			//if name is set
			$query = "UPDATE imas_stugroupset SET name='{$_POST['grpsetname']}' WHERE id='{$_GET['rengrpset']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['rengrpset']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Rename Collection";
	} else if (isset($_GET['addstutogrp'])) {
		//submitting list of students to add to a group
		$stustoadd = $_POST['stutoadd'];
		if ($_POST['addtogrpid']=='--new--') {
			//adding a new group; need to ask for group
			$_GET['addgrp'] = true;
			$stulist = implode(',',$stustoadd);	
		} else {
			$grpid = $_POST['addtogrpid'];
			if (!is_array($stustoadd)) {
				$stustoadd = explode(',',$stustoadd);
			}
			$query = 'INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES ';
			for ($i=0;$i<count($stustoadd);$i++) {
				if ($i>0) {$query .= ',';};
				$query .= "('$grpid','{$stustoadd[$i]}')";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid={$_GET['grpsetid']}");
			exit();
		}
		
	} else if (isset($_GET['addgrp'])) {
		$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_grpsetname = mysql_result($result,0,0);
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Add Group";
	} else if (isset($_GET['delgrp'])) {
		//deleting groupset
		if (isset($_GET['confirm'])) {
			//if name is set
			deletegroup($_GET['delgrp']);
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid=$grpsetid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['delgrp']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpname = mysql_result($result,0,0);
			$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
			
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Delete Group";
	} else if (isset($_GET['rengrp'])) {
		//renaming groupset	
		if (isset($_POST['grpname'])) {
			//if name is set
			$query = "UPDATE imas_stugroups SET name='{$_POST['grpname']}' WHERE id='{$_GET['rengrp']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid=$grpsetid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['rengrp']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpname = mysql_result($result,0,0);
			$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Rename Group";
	} else if (isset($_GET['grpsetid'])) {
		//groupset selected, show groups
		$grpsetid = $_GET['grpsetid'];
		$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_grpsetname = mysql_result($result,0,0);	
		
		//$page_grps will be an array, groupid=>name
		$page_grps = array();
		$page_grpmembers = array();
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$grpsetid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$page_grps[$row[0]] = $row[1];
			$page_grpmembers[$row[0]] = array();
		}
		$grpids = implode(',',array_keys($page_grps));
		
		natsort($page_grps);
		
		//get all students
		$stunames = array();
		$query = "SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$stunames[$row[0]] = $row[2].', '.$row[1];
		}
		
		//$page_grpmembers will be groupid=>array(  userid=>stuname )
		$stuuseridsingroup = array();
		if (count($page_grps)>0) {
			$query = "SELECT stugroupid,userid FROM imas_stugroupmembers WHERE stugroupid IN ($grpids)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if (!isset($page_grpmembers[$row[0]])) {
					$page_grpmembers[$row[0]] = array();
				}
				$page_grpmembers[$row[0]][$row[1]] = $stunames[$row[1]];
				$stuuseridsingroup[] = $row[1];
			}
			//sort each group member list by name
			foreach ($page_grpmembers as $k=>$stuarr) {
				natsort($stuarr);
				$page_grpmembers[$k] = $stuarr;
			}
		}
		$ungrpids = array_diff(array_keys($stunames),$stuuseridsingroup);
		$page_ungrpstu = array();
		foreach ($ungrpids as $uid) {
			$page_ungrpstu[$uid] = $stunames[$uid];
		}
		natsort($page_ungrpstu);
		
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; $page_grpsetname";
			
	} else { 
		//no groupset selected
		$page_groupsets = array();
		$query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$page_groupsets[] = $row;
		}
		$curBreadcrumb .= " &gt; Manage Student Groups";
	}
	

}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {	
	echo "<div class=\"breadcrumb\"$curBreadcrumb</div>";
	echo "<h3>$pagetitle</h3>";

	if (isset($_GET['addgrpset'])) {
		//add new group set
		echo '<h4>Add new student group collection</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&addgrpset=true\">";
		echo '<p>New group collection name: <input name="grpsetname" type="text" /></p>';
		echo '<p><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['delgrpset'])) {
		echo '<h4>Delete student group collection</h4>';
		echo "<p>Are you SURE you want to delete the student group collection <b>$page_grpsetname</b> and all the groups contained within in?</p>";
		echo "<p><input type=button value=\"Yes, Delete\" onClick=\"window.location='managestugrps.php?cid=$cid&delgrpset={$_GET['delgrpset']}&confirm=true'\" /> ";
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		
	} else if (isset($_GET['rengrpset'])) {
		echo '<h4>Rename student group collection</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&rengrpset={$_GET['rengrpset']}\">";
		echo '<p>New group collection name: <input name="grpsetname" type="text" value="'.$page_grpsetname.'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['addgrp'])) {
		//add new group set
		echo '<h4>Add new student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&addgrp=true\">";
		if (isset($stulist)) {
			echo "<input type=\"hidden\" name=\"stustoadd\" value=\"$stulist\" />";
		}
		echo '<p>New group name: <input name="grpname" type="text" /></p>';
		echo '<p><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['delgrp'])) {
		echo '<h4>Delete student group</h4>';
		echo "<p>Are you SURE you want to delete the student group <b>$page_grpname</b>?</p>";
		echo "<p><input type=button value=\"Yes, Delete\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid&delgrp={$_GET['delgrp']}&confirm=true'\" /> ";
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
		
	} else if (isset($_GET['rengrp'])) {
		echo '<h4>Rename student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&rengrp={$_GET['rengrp']}\">";
		echo '<p>New group name: <input name="grpname" type="text" value="'.$page_grpname.'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['grpsetid'])) {
		//groupset selected - list members
		echo "<h4>Managing groups in collection $page_grpsetname</h4>";
		foreach ($page_grps as $grpid=>$grpname) {
			echo "<b>Group $grpname</b> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&rengrp=$grpid\">Rename</a> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&delgrp=$grpid\">Delete</a> | ";
			echo "Remove all members";
			echo '<ul>';
			if (count($page_grpmembers[$grpid])==0) {
				echo '<li>No group members</li>';
			} else {
				foreach ($page_grpmembers[$grpid] as $uid=>$name) {
					echo "<li>$name | Remove from group</li>";
				}
			}
			echo '</ul>';
		}
		
		echo "<p><a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&addgrp=true\">Add New Group</a></p>";
		
		echo '<h4>Students not in a group</h4>';
		if (count($page_ungrpstu)>0) {
			echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&addstutogrp=true\">";
			echo 'With selected, add to group ';
			echo '<select name="addtogrpid">';
			echo "<option value=\"--new--\">New Group</option>";
			foreach ($page_grps as $grpid=>$grpname) {
				echo "<option value=\"$grpid\">$grpname</option>";
			}
			echo '</select>';
			echo '<input type="submit" value="Add"/>';
			echo '<ul class="nomark">';
			foreach ($page_ungrpstu as $uid=>$name) {
				echo "<li><input type=\"checkbox\" name=\"stutoadd[]\" value=\"$uid\" />$name</li>";
			}
			echo '</ul>';
			echo '</form>';
		} else {
			echo 'None';
		}
		
	} else {
		//list all groups
		echo '<h4>Student Group Collections</h4>';
		if (count($page_groupsets)==0) {
			echo '<p>No existing group collections</p>';
		} else {
			echo '<p>Select a group collection to modify the groups in that collection</p>';
			echo '<ul>';
			foreach ($page_groupsets as $gs) {
				echo "<li><a href=\"managestugrps.php?cid=$cid&grpsetid={$gs[0]}\">{$gs[1]}</a> | ";
				echo "<a href=\"managestugrps.php?cid=$cid&rengrpset={$gs[0]}\">Rename</a> | ";
				echo "<a href=\"managestugrps.php?cid=$cid&delgrpset={$gs[0]}\">Delete</a>";
				
				echo '</li>';
			}
			echo '</ul>';
		}
		
		echo "<p><a href=\"managestugrps.php?cid=$cid&addgrpset=ask\">Add new group collection</a></p>";
	}
	
}

require("../footer.php");

?>
