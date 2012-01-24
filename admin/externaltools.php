<?php
require("../validate.php");

$isadmin = false;
$isgrpadmin = false; 
$isteacher = false;

$err = '';
if (!(isset($teacherid)) && $myrights<75) {
 	$err = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['cid']) && $_GET['cid']=="admin" && $myrights <75) {
 	$err = "You need to log in as an admin to access this page";
} elseif (!(isset($_GET['cid'])) && $myrights < 75) {
 	$err = "Please access this page from the menu links only.";	
}

if ($err != '') {
	require("../header.php");
	echo $err;
	require("../footer.php");
}

$cid = (isset($_GET['cid'])) ? $_GET['cid'] : "admin";
if ($myrights == 75 && $cid=='admin') {
	$isgrpadmin = true;
} else if ($myrights == 100 && $cid == 'admin') {
	$isadmin = true;
} else {
	$isteacher = true;
}
if (isset($_GET['ltfrom'])) {
	$ltfrom = '&amp;ltfrom='.$_GET['ltfrom'];
} else {
	$ltfrom = '';
}

if (isset($_POST['tname'])) {
	$privacy = 0;
	if (isset($_POST['privname'])) {$privacy += 1;}
	if (isset($_POST['privemail'])) {$privacy += 2;}
	$_POST['custom'] = str_replace("\n",'&',$_POST['custom']);
	$_POST['custom'] = preg_replace('/\s/','',$_POST['custom']);
	
	if (!empty($_POST['tname']) && !empty($_POST['key']) && !empty($_POST['secret']) && !empty($_POST['url'])) {
		$query = '';
		if ($_GET['id']=='new') {
			$query = "INSERT INTO imas_external_tools (name,url,ltikey,secret,custom,privacy,groupid,courseid) VALUES ";
			$query .= "('{$_POST['tname']}','{$_POST['url']}','{$_POST['key']}','{$_POST['secret']}','{$_POST['custom']}',$privacy";
			if ($isteacher) {
				$query .= ",$groupid,$cid)";
			} else if ($isgrpadmin || ($isadmin && $_POST['scope']==1)) {
				$query .= ",$groupid,0)";
			} else {
				$query .= ",0,0)";
			}
		} else {
			$query = "UPDATE imas_external_tools SET name='{$_POST['tname']}',url='{$_POST['url']}',ltikey='{$_POST['key']}',";
			$query .= "secret='{$_POST['secret']}',custom='{$_POST['custom']}',privacy=$privacy";
			if ($isadmin) {
				if ($_POST['scope']==0) {
					$query .= ',groupid=0';
				} else {
					$query .= ",groupid='$groupid'";
				}
			}
			$query .= " WHERE id='{$_GET['id']}' ";
			if ($isteacher) {
				$query .= "AND courseid='$cid'";
			} else if ($isgrpadmin) {
				$query .= "AND groupid='$groupid'";
			}
		}
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		
	}
	$ltfrom = str_replace('&amp;','&',$ltfrom);
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/externaltools.php?cid=$cid$ltfrom");
	exit;
} else if (isset($_GET['delete']) && $_GET['delete']=='true') {
	$id = intval($_GET['id']);
	if ($id>0) {
		$query = "DELETE FROM imas_external_tools WHERE id=$id ";
		if ($isteacher) {
			$query .= "AND courseid='$cid'";
		} else if ($isgrpadmin) {
			$query .= "AND groupid='$groupid'";
		}
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	$ltfrom = str_replace('&amp;','&',$ltfrom);
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/externaltools.php?cid=$cid$ltfrom");
	exit;
} else {
	require("../header.php");
	if ($isteacher) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		if (isset($_GET['ltfrom'])) {
			echo "&gt; <a href=\"../course/addlinkedtext.php?cid=$cid&amp;id={$_GET['ltfrom']}\">Modify Linked Text<a/> ";
		}
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> \n";
	}
	if (isset($_GET['delete'])) {
		echo " &gt; <a href=\"externaltools.php?cid=$cid$ltfrom\">External Tools</a> &gt; Delete Tool</div>";
		echo "<h2>Delete Tool</h2>";
		$query = "SELECT name FROM imas_external_tools WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$name = mysql_result($result,0,0);
		
		echo '<p>Are you SURE you want to delete the tool <b>'.$name.'</b>?  Doing so will break ALL placements of this tool.</p>';
		echo '<form method="post" action="externaltools.php?cid='.$cid.$ltfrom.'&amp;id='.$_GET['id'].'&amp;delete=true">';
		echo '<input type=submit value="Yes, I\'m Sure">';
		echo '<input type=button value="Nevermind" onclick="window.location=\'externaltools.php?cid='.$cid.'\'">';
		echo '</form>';
		
	} else if (isset($_GET['id'])) {
		echo " &gt; <a href=\"externaltools.php?cid=$cid$ltfrom\">External Tools</a> &gt; Edit Tool</div>";
		echo "<h2>Edit Tool</h2>";
		if ($_GET['id']=='new') {
			$name = ''; $url = ''; $key = ''; $secret = ''; $custom = ''; $privacy = 3; $grp = 0;
		} else {
			$query = "SELECT name,url,ltikey,secret,custom,privacy,groupid FROM imas_external_tools WHERE id='{$_GET['id']}'";
			if ($isteacher) {
				$query .= " AND courseid='$cid'";
			} else if ($isgrpadmin) {
				$query .= " AND groupid='$groupid'";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) { die("invalid id");}
			list($name,$url,$key,$secret,$custom,$privacy,$grp) = mysql_fetch_row($result);
			$custom = str_replace('&',"\n",$custom);
		}
		$tochg = array('name','url','key','secret','custom');
		foreach ($tochg as $v) {
			${$v} = htmlentities(${$v});
		}
		echo '<form method="post" action="externaltools.php?cid='.$cid.$ltfrom.'&amp;id='.$_GET['id'].'">';
?>
		<span class="form">Tool Name:</span>
		<span class="formright"><input type="text" size="40" name="tname" value="<?php echo $name;?>" /></span>
		<br class="form" />
		
		<span class="form">Launch URL:</span>
		<span class="formright"><input type="text" size="40" name="url" value="<?php echo $url;?>" /></span>
		<br class="form" />
		
		<span class="form">Key:</span>
		<span class="formright"><input type="text" size="40" name="key" value="<?php echo $key;?>" /></span>
		<br class="form" />
		
		<span class="form">Secret:</span>
		<span class="formright"><input type="password" size="40" name="secret" value="<?php echo $secret;?>" /></span>
		<br class="form" />
		
		<span class="form">Custom Parameters:</span>
		<span class="formright">
			<textarea rows="2" cols="30" name="custom"><?php echo $custom;?></textarea>
		</span>
		<br class="form" />
		
		<span class="form">Privacy:</span>
		<span class="formright">
		<input type="checkbox" name="privname" value="1" <?php if (($privacy&1)==1) echo 'checked="checked"';?> /> Send name<br/>
		<input type="checkbox" name="privemail" value="2" <?php if (($privacy&2)==2) echo 'checked="checked"';?> /> Send email
		</span>
		<br class="form" />
<?php	
		if ($isadmin) {
			echo '<span class="form">Scope of tool:</span><span class="formright">';
			echo '<input type="radio" name="scope" value="0" '. (($grp==0)?'checked="checked"':'') . '> System-wide<br/>';
			echo '<input type="radio" name="scope" value="1" '. (($grp>0)?'checked="checked"':'') . '> Group';
			echo '</span><br class="form" />';
		}
		echo '<div class="submit"><input type="submit" value="Save"></div>';
		echo '</form>';

	} else {
		echo " &gt; External Tools</div>";
		echo "<h2>External Tools</h2>";
		
		if ($isadmin) {
			echo '<p><b>System and Group Tools</b></p>';
			$query = "SELECT iet.id,iet.name,ig.name FROM imas_external_tools as iet LEFT JOIN imas_groups AS ig ON ";
			$query .= "iet.groupid=ig.id WHERE iet.courseid=0 ORDER BY iet.groupid,iet.name";
		} else if ($isgrpadmin) {
			echo '<p><b>Group Tools</b></p>';
			$query = "SELECT id,name FROM imas_external_tools WHERE courseid=0 AND groupid='$groupid' ORDER BY name";
		} else {
			echo '<p><b>Course Tools</b></p>';
			$query = "SELECT id,name FROM imas_external_tools WHERE courseid='$cid' ORDER BY name";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<ul class="nomark">';
		if (mysql_num_rows($result)==0) {
			echo '<li>No tools</li>';
		} else {
			while ($row = mysql_fetch_row($result)) {
				echo '<li>'.$row[1];
				if ($isadmin) {
					if ($row[2]==null) {
						echo ' (System-wide)';
					} else {
						echo ' (for group '.$row[2].')';
					}
				}
				echo ' <a href="externaltools.php?cid='.$cid.$ltfrom.'&amp;id='.$row[0].'">Edit</a> ';
				echo '| <a href="externaltools.php?cid='.$cid.$ltfrom.'&amp;id='.$row[0].'&amp;delete=ask">Delete</a> ';
				echo '</li>';
			}
		}
		echo '</ul>';
		echo '<p><a href="externaltools.php?cid='.$cid.'&amp;id=new">Add a Tool</a></p>';	
	}
	require("../footer.php");
}
	

