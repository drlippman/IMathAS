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
			$query = "UPDATE imas_external_tools SET name=:name,url=:url,ltikey=:ltikey,";
			$query .= "secret=:secret,custom=:custom,privacy=:privacy";

			$qarr = array(':name'=>$_POST['tname'], ':url'=>$_POST['url'], ':ltikey'=>$_POST['key'], ':secret'=>$_POST['secret'], ':custom'=>$_POST['custom'], ':privacy'=>$privacy);
			if ($isadmin) {
				if ($_POST['scope']==0) {
					$query .= ',groupid=0';
				} else {
				  $query .= ",groupid=:groupid";
					$qarr[':groupid']=$groupid;
				}
			}
      $query .= " WHERE id=:id ";
      $qarr[':id'] = $_GET['id'];
			if ($isteacher) {
        $query .= "AND courseid=:courseid";
        $qarr[':courseid'] = $cid;
			} else if ($isgrpadmin) {
        $query .= "AND groupid=:groupid2";
        $qarr[':groupid2'] = $groupid;
			}
			$stm = $DBH->prepare($query);
	    $stm->execute($qarr);
		}

	}
	$ltfrom = str_replace('&amp;','&',$ltfrom);
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/externaltools.php?cid=$cid$ltfrom&r=" .Sanitize::randomQueryStringParam());
	exit;
} else if (isset($_POST['delete']) && $_POST['delete']=='true') {
	$id = Sanitize::onlyInt($_GET['id']);
	if ($id>0) {
		if ($isadmin) {
      $stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE id=:id ");
      $stm->execute(array(':id'=>$id));
    } else if ($isteacher) {
			$stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$id, ':courseid'=>$cid));
		} else if ($isgrpadmin) {
			$stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE id=:id AND groupid=:groupid");
			$stm->execute(array(':id'=>$id, ':groupid'=>$groupid));
		}
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
	if (isset($_GET['delete'])) {
		echo " &gt; <a href=\"externaltools.php?cid=$cid$ltfrom\">External Tools</a> &gt; Delete Tool</div>";
		echo "<h1>Delete Tool</h1>";
		$stm = $DBH->prepare("SELECT name FROM imas_external_tools WHERE id=:id");
		$stm->execute(array(':id'=>$id));
		$name = $stm->fetchColumn(0);

		echo '<p>Are you SURE you want to delete the tool <b>' . Sanitize::encodeStringForDisplay($name) . '</b>?  Doing so will break ALL placements of this tool.</p>';
		echo '<form method="post" action="externaltools.php?cid=' . $cid . $ltfrom . '&amp;id=' . $id . '">';
		echo '<input type=hidden name=delete value=true />';
		echo '<input type=submit value="Yes, I\'m Sure">';
		echo '<input type=button value="Nevermind" class="secondarybtn" onclick="window.location=\'externaltools.php?cid='.$cid.'\'">';
		echo '</form>';

	} else if (!empty($_GET['id'])) {
        $id = Sanitize::simpleString($_GET['id']); //can be ID int or string "new"
		echo " &gt; <a href=\"externaltools.php?cid=$cid$ltfrom\">External Tools</a> &gt; Edit Tool</div>";
		echo "<h1>Edit Tool</h1>";
		if ($id=='new') {
			$name = ''; $url = ''; $key = ''; $secret = ''; $custom = ''; $privacy = 3; $grp = 0;
		} else {
			$qsel = 'SELECT name,url,ltikey,secret,custom,privacy,groupid FROM imas_external_tools ';
			if ($isadmin) {
				$stm = $DBH->prepare($qsel."WHERE id=:id");
				$stm->execute(array(':id'=>$id));
			} else if ($isteacher) {
				$stm = $DBH->prepare($qsel."WHERE id=:id AND courseid=:courseid");
				$stm->execute(array(':id'=>$id, ':courseid'=>$cid));
			} else if ($isgrpadmin) {
				$stm = $DBH->prepare($qsel."WHERE id=:id AND groupid=:groupid");
				$stm->execute(array(':id'=>$id, ':groupid'=>$groupid));
			}
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
		echo "<h1>External Tools</h1>";

		if ($isadmin) {
			echo '<p><b>System and Group Tools</b></p>';
			$query = "SELECT iet.id,iet.name,ig.name FROM imas_external_tools as iet LEFT JOIN imas_groups AS ig ON ";
			$query .= "iet.groupid=ig.id WHERE iet.courseid=0 ORDER BY iet.groupid,iet.name";
			$stm = $DBH->query($query);
		} else if ($isgrpadmin) {
			echo '<p><b>Group Tools</b></p>';
			$stm = $DBH->prepare("SELECT id,name FROM imas_external_tools WHERE courseid=0 AND groupid=:groupid ORDER BY name");
			$stm->execute(array(':groupid'=>$groupid));
		} else {
			echo '<p><b>Course Tools</b></p>';
			$stm = $DBH->prepare("SELECT id,name FROM imas_external_tools WHERE courseid=:courseid ORDER BY name");
			$stm->execute(array(':courseid'=>$cid));
		}
		echo '<ul class="nomark">';
		if ($stm->rowCount()==0) {
			echo '<li>No tools</li>';
		} else {
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
