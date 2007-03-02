<?php
	//Listing of all forums for a course - not being used
	//(c) 2006 David Lippman
	
	require("../validate.php");
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
	}
	if ($myrights > 99) {
		$isadmin = true;
	} else {
		$isadmin = false;
	}
	
	if (!isset($_GET['cid'])) {
		exit;
	}
	
	$cid = $_GET['cid'];
	
	if (isset($_GET['modify']) && $isadmin) { //adding or modifying forum
		if (isset($_POST['name'])) {  //form submitted
			if ($_GET['modify']=="new") {
				$query = "INSERT INTO imas_forums (name,description) VALUES ('{$_POST['name']}','{$_POST['description']}')";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} else {
				$query = "UPDATE imas_forums SET name='{$_POST['name']}',description='{$_POST['description']}' ";
				$query .= "WHERE id='{$_GET['modify']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forums.php?cid=$cid&cid=$cid");
			exit;
		} else { //display mod
			$pagetitle = "Add/Modify Forum";
			require("../header.php");
			if ($_GET['modify']!="new") {
				$query = "SELECT * from imas_forums WHERE id='{$_GET['modify']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				echo "<h3>Modify Forum</h3>\n";
			} else {
				$line['name'] = "Enter forum name";
				$line['description'] = "Enter forum description";
				echo "<h3>Add Forum</h3>\n";
			}
			echo "<form method=post action=\"forums.php?cid=$cid&modify={$_GET['modify']}\">\n";
			echo "<span class=form>Forum name:</span>";
			echo "<span class=formright><input type=text size=50 name=name value=\"{$line['name']}\"></span><br class=form>\n";
			echo "<span class=form>Forum description:</span>";
			echo "<span class=formright><textarea name=description rows=3 cols=50>{$line['description']}</textarea></span><br class=form>\n";
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;
		}
	} else if (isset($_GET['remove']) && $isadmin) { //removing forum
		if (isset($_GET['confirm'])) {
			$query = "DELETE FROM imas_forums WHERE id='{$_GET['remove']}'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			$query = "DELETE FROM imas_forum_posts WHERE forumid='{$_GET['remove']}'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forums.php?cid=$cid&cid=$cid");
			exit;
		} else {
			$pagetitle = "Remove Forum";
			require("../header.php");
			echo "<h3>Remove Forum</h3>\n";
			echo "<p>Are you SURE you want to remove this forum and all enclosed posts?</p>\n";
			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='forums.php?cid=$cid&remove={$_GET['remove']}&confirm=true'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='forums.php'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	$pagetitle = "Forums";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Forums</div>\n";
	
?>
	
	<h3>Forums</h3>
	
	<table class=forum>
	<thead>
	<tr><th>Forum Name</th><th>Threads</th><th>Posts</th><th>Last Post Date</th></tr>
	</thead>
	<tbody>
<?php
	$query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	$query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forum_posts.parent=0 AND imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$threadcount[$row[0]] = $row[1];
	}
	
	$query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) AS postcount,MAX(imas_forum_posts.postdate) AS maxdate FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	$query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$postcount[$row[0]] = $row[1];
		$maxdate[$row[0]] = $row[2];
	}
	
	$query = "SELECT * FROM imas_forums WHERE imas_forums.courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<tr><td><b><a href=\"thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b> ";
		if ($isadmin) {
			echo "<a href=\"forums.php?cid=$cid&modify={$line['id']}\">Modify</a> ";
			echo "<a href=\"forums.php?cid=$cid&remove={$line['id']}\">Remove</a>";
		}
		echo "<br/>{$line['description']}</td>\n";
		if (isset($threadcount[$line['id']])) {
			$threads = $threadcount[$line['id']];
			$posts = $postcount[$line['id']];
			$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
		} else {
			$threads = 0;
			$posts = 0;
			$lastpost = '';
		}
		echo "<td class=c>$threads</td><td class=c>$posts</td><td class=c>$lastpost</td></tr>\n";
	}
?>
	</tbody>
	</table>
<?php
	if ($isadmin) {
		echo "<p><a href=\"forums.php?cid=$cid&modify=new\">Add New Forum</a></p>\n";
	}
	require("../footer.php");
?>
	
	
	
	
