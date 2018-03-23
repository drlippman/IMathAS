<?php
require("../init.php");


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

$cid = (isset($_GET['cid'])) ? Sanitize::courseId($_GET['cid']) : "admin";
if ($myrights == 75 && $cid=='admin') {
	$isgrpadmin = true;
} else if ($myrights == 100 && $cid == 'admin') {
	$isadmin = true;
} else {
	$isteacher = true;
}
if (isset($_GET['ltfrom'])) {
	$ltfrom = '&amp;ltfrom='.Sanitize::encodeUrlParam($_GET['ltfrom']);
} else {
	$ltfrom = '';
}

if (isset($_POST['tname'])) {
	$privacy = 0;
	if (isset($_POST['privname'])) {$privacy += 1;}
	if (isset($_POST['privemail'])) {$privacy += 2;}
	$_POST['custom'] = str_replace("\n",'&',$_POST['custom']);
	$_POST['custom'] = preg_replace('/\s/','',$_POST['custom']);

	if (!empty($_POST['tname']) && !empty($_POST['key']) && !empty($_POST['secret'])) {
		$query = '';
		if ($_GET['id']=='new') {
			//DB $query = "INSERT INTO imas_external_tools (name,url,ltikey,secret,custom,privacy,groupid,courseid) VALUES ";
			//DB $query .= "('{$_POST['tname']}','{$_POST['url']}','{$_POST['key']}','{$_POST['secret']}','{$_POST['custom']}',$privacy";
			$query = "INSERT INTO imas_external_tools (name,url,ltikey,secret,custom,privacy,groupid,courseid) VALUES ";
			$query .= "(:name, :url, :ltikey, :secret, :custom, :privacy, :groupid, :courseid)";
			$stm = $DBH->prepare($query);
			if ($isteacher) {
				$stm->execute(array(':name'=>$_POST['tname'], ':url'=>$_POST['url'], ':ltikey'=>$_POST['key'], ':secret'=>$_POST['secret'], ':custom'=>$_POST['custom'], ':privacy'=>$privacy, ':groupid'=>$groupid, ':courseid'=>$cid));
			} else if ($isgrpadmin || ($isadmin && $_POST['scope']==1)) {
				$stm->execute(array(':name'=>$_POST['tname'], ':url'=>$_POST['url'], ':ltikey'=>$_POST['key'], ':secret'=>$_POST['secret'], ':custom'=>$_POST['custom'], ':privacy'=>$privacy, ':groupid'=>$groupid, ':courseid'=>0));
			} else {
				$stm->execute(array(':name'=>$_POST['tname'], ':url'=>$_POST['url'], ':ltikey'=>$_POST['key'], ':secret'=>$_POST['secret'], ':custom'=>$_POST['custom'], ':privacy'=>$privacy, ':groupid'=>0, ':courseid'=>0));
			}
		} else {
			//DB $query = "UPDATE imas_external_tools SET name='{$_POST['tname']}',url='{$_POST['url']}',ltikey='{$_POST['key']}',";
			//DB $query .= "secret='{$_POST['secret']}',custom='{$_POST['custom']}',privacy=$privacy";
			$query = "UPDATE imas_external_tools SET name=:name,url=:url,ltikey=:ltikey,";
			$query .= "secret=:secret,custom=:custom,privacy=:privacy";

			$qarr = array(':name'=>$_POST['tname'], ':url'=>$_POST['url'], ':ltikey'=>$_POST['key'], ':secret'=>$_POST['secret'], ':custom'=>$_POST['custom'], ':privacy'=>$privacy);
			if ($isadmin) {
				if ($_POST['scope']==0) {
					$query .= ',groupid=0';
				} else {
					//DB $query .= ",groupid='$groupid'";
				  $query .= ",groupid=:groupid";
					$qarr[':groupid']=$groupid;
				}
			}
			//DB $query .= " WHERE id='{$_GET['id']}' ";
      $query .= " WHERE id=:id ";
      $qarr[':id'] = $_GET['id'];
			if ($isteacher) {
				//DB $query .= "AND courseid='$cid'";
        $query .= "AND courseid=:courseid";
        $qarr[':courseid'] = $cid;
			} else if ($isgrpadmin) {
				//DB $query .= "AND groupid='$groupid'";
        $query .= "AND groupid=:groupid2";
        $qarr[':groupid2'] = $groupid;
			}
			$stm = $DBH->prepare($query);
	    $stm->execute($qarr);
		}
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());

	}
	$ltfrom = str_replace('&amp;','&',$ltfrom);
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/externaltools.php?cid=$cid$ltfrom&r=" .Sanitize::randomQueryStringParam());
	exit;
} else if (isset($_POST['delete']) && $_POST['delete']=='true') {
	$id = Sanitize::onlyInt($_GET['id']);
	if ($id>0) {
		if ($isadmin) {
      //DB $query = "DELETE FROM imas_external_tools WHERE id=$id ";
      $stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE id=:id ");
      $stm->execute(array(':id'=>$id));
    } else if ($isteacher) {
			//DB $query = "DELETE FROM imas_external_tools WHERE id=$id AND courseid='$cid'";
			$stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$id, ':courseid'=>$cid));
		} else if ($isgrpadmin) {
			//DB $query = "DELETE FROM imas_external_tools WHERE id=$id AND groupid='$groupid'";
			$stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE id=:id AND groupid=:groupid");
			$stm->execute(array(':id'=>$id, ':groupid'=>$groupid));
		}
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	$ltfrom = str_replace('&amp;','&',$ltfrom);
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/externaltools.php?cid=$cid$ltfrom&r=" .Sanitize::randomQueryStringParam());
	exit;
} else {
	require("../header.php");
	if ($isteacher) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		if (isset($_GET['ltfrom'])) {
			echo "&gt; <a href=\"../course/addlinkedtext.php?cid=$cid&amp;id=".Sanitize::encodeUrlParam($_GET['ltfrom'])."\">Modify Linked Text<a/> ";
		}
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"admin2.php\">Admin</a> \n";
	}
	$id = Sanitize::simpleString($_GET['id']); //can be ID int or string "new"
	if (isset($_GET['delete'])) {
		echo " &gt; <a href=\"externaltools.php?cid=$cid$ltfrom\">External Tools</a> &gt; Delete Tool</div>";
		echo "<h2>Delete Tool</h2>";
		//DB $query = "SELECT name FROM imas_external_tools WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $name = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_external_tools WHERE id=:id");
		$stm->execute(array(':id'=>$id));
		$name = $stm->fetchColumn(0);

		echo '<p>Are you SURE you want to delete the tool <b>' . Sanitize::encodeStringForDisplay($name) . '</b>?  Doing so will break ALL placements of this tool.</p>';
		echo '<form method="post" action="externaltools.php?cid=' . $cid . $ltfrom . '&amp;id=' . $id . '">';
		echo '<input type=hidden name=delete value=true />';
		echo '<input type=submit value="Yes, I\'m Sure">';
		echo '<input type=button value="Nevermind" class="secondarybtn" onclick="window.location=\'externaltools.php?cid='.$cid.'\'">';
		echo '</form>';

	} else if (!empty($id)) {
		echo " &gt; <a href=\"externaltools.php?cid=$cid$ltfrom\">External Tools</a> &gt; Edit Tool</div>";
		echo "<h2>Edit Tool</h2>";
		if ($id=='new') {
			$name = ''; $url = ''; $key = ''; $secret = ''; $custom = ''; $privacy = 3; $grp = 0;
		} else {
			$qsel = 'SELECT name,url,ltikey,secret,custom,privacy,groupid FROM imas_external_tools ';
			if ($isadmin) {
				//DB $query = "SELECT name,url,ltikey,secret,custom,privacy,groupid FROM imas_external_tools WHERE id='{$_GET['id']}'";
				$stm = $DBH->prepare($qsel."WHERE id=:id");
				$stm->execute(array(':id'=>$id));
			} else if ($isteacher) {
				//DB $query = "SELECT name,url,ltikey,secret,custom,privacy,groupid FROM imas_external_tools WHERE id='{$_GET['id']}' AND courseid='$cid'";
				$stm = $DBH->prepare($qsel."WHERE id=:id AND courseid=:courseid");
				$stm->execute(array(':id'=>$id, ':courseid'=>$cid));
			} else if ($isgrpadmin) {
				//DB $query = "SELECT name,url,ltikey,secret,custom,privacy,groupid FROM imas_external_tools WHERE id='{$_GET['id']}' AND groupid='$groupid'";
				$stm = $DBH->prepare($qsel."WHERE id=:id AND groupid=:groupid");
				$stm->execute(array(':id'=>$id, ':groupid'=>$groupid));
			}
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)==0) { die("invalid id");}
			//DB list($name,$url,$key,$secret,$custom,$privacy,$grp) = mysql_fetch_row($result);
			if ($stm->rowCount()==0) { die("invalid id");}
			list($name,$url,$key,$secret,$custom,$privacy,$grp) = $stm->fetch(PDO::FETCH_NUM);
			$custom = str_replace('&',"\n",$custom);
		}
		$tochg = array('name','url','key','secret','custom');
		foreach ($tochg as $v) {
			${$v} = htmlentities(${$v});
		}
		echo '<form method="post" action="externaltools.php?cid='.$cid.$ltfrom.'&amp;id='.$id.'">';
?>
		<span class="form">Tool Name:</span>
		<span class="formright"><input type="text" size="40" name="tname" value="<?php echo Sanitize::encodeStringForDisplay($name); ?>" /></span>
		<br class="form" />

		<span class="form">Launch URL:</span>
		<span class="formright"><input type="text" size="40" name="url" value="<?php echo Sanitize::encodeStringForDisplay($url); ?>" /></span>
		<br class="form" />

		<span class="form">Key:</span>
		<span class="formright"><input type="text" size="40" name="key" value="<?php echo Sanitize::encodeStringForDisplay($key); ?>" /></span>
		<br class="form" />

		<span class="form">Secret:</span>
		<span class="formright"><input type="password" size="40" name="secret" value="<?php echo Sanitize::encodeStringForDisplay($secret); ?>" /></span>
		<br class="form" />

		<span class="form">Custom Parameters:</span>
		<span class="formright">
			<textarea rows="2" cols="30" name="custom"><?php echo Sanitize::encodeStringForDisplay($custom); ?></textarea>
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
			//DB $query = "SELECT iet.id,iet.name,ig.name FROM imas_external_tools as iet LEFT JOIN imas_groups AS ig ON ";
			//DB $query .= "iet.groupid=ig.id WHERE iet.courseid=0 ORDER BY iet.groupid,iet.name";
			$query = "SELECT iet.id,iet.name,ig.name FROM imas_external_tools as iet LEFT JOIN imas_groups AS ig ON ";
			$query .= "iet.groupid=ig.id WHERE iet.courseid=0 ORDER BY iet.groupid,iet.name";
			$stm = $DBH->query($query);
		} else if ($isgrpadmin) {
			echo '<p><b>Group Tools</b></p>';
			//DB $query = "SELECT id,name FROM imas_external_tools WHERE courseid=0 AND groupid='$groupid' ORDER BY name";
			$stm = $DBH->prepare("SELECT id,name FROM imas_external_tools WHERE courseid=0 AND groupid=:groupid ORDER BY name");
			$stm->execute(array(':groupid'=>$groupid));
		} else {
			echo '<p><b>Course Tools</b></p>';
			//DB $query = "SELECT id,name FROM imas_external_tools WHERE courseid='$cid' ORDER BY name";
			$stm = $DBH->prepare("SELECT id,name FROM imas_external_tools WHERE courseid=:courseid ORDER BY name");
			$stm->execute(array(':courseid'=>$cid));
		}
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<ul class="nomark">';
		//DB if (mysql_num_rows($result)==0) {
		if ($stm->rowCount()==0) {
			echo '<li>No tools</li>';
		} else {
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				echo '<li>' . Sanitize::encodeStringForDisplay($row[1]);
				if ($isadmin) {
					if ($row[2]==null) {
						echo ' (System-wide)';
					} else {
						echo ' (for group ' . Sanitize::encodeStringForDisplay($row[2]) . ')';
					}
				}
				echo ' <a href="externaltools.php?cid=' . $cid . $ltfrom . '&amp;id=' . Sanitize::onlyInt($row[0]) . '">Edit</a> ';
				echo '| <a href="externaltools.php?cid=' . $cid . Sanitize::encodeUrlParam($ltfrom) . '&amp;id=' . Sanitize::onlyInt($row[0]) . '&amp;delete=ask">Delete</a> ';
				echo '</li>';
			}
		}
		echo '</ul>';
		echo '<p><a href="externaltools.php?cid='.$cid.'&amp;id=new">Add a Tool</a></p>';
	}
	require("../footer.php");
}
