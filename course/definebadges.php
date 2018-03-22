<?php

require("../init.php");


if (!isset($teacherid)) {
	echo "You are not authorized to view this page";
	exit;
}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=". Sanitize::courseId($_GET['cid']). "\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";

if (empty($_GET['badgeid'])) {
	require("../header.php");

	echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Badge Settings</div>';
	echo '<div id="headerbadgesettings" class="pagetitle"><h2>Badge Settings</h2></div>';

	//DB $query = "SELECT id,name FROM imas_badgesettings WHERE courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)!=0) {
	$stm = $DBH->prepare("SELECT id,name FROM imas_badgesettings WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	if ($stm->rowCount()!=0) {
		echo '<ul>';
		//DB while ($row=mysql_fetch_row($result)) {
		while ($row=$stm->fetch(PDO::FETCH_NUM)) {
			echo '<li><a href="definebadges.php?cid='.$cid.'&amp;badgeid=' . Sanitize::encodeUrlParam($row[0]) . '">'.Sanitize::encodeStringForDisplay($row[1]).'</a> ';
			echo '<a class="small" href="definebadges.php?cid='.$cid.'&amp;badgeid=' . Sanitize::encodeUrlParam($row[0]) . '&amp;delete=true" onclick="return confirm(\'Are you sure you want to delete this badge definition and invalidate all awarded badges?\');">[Delete]</a> ';
			echo '<br/><a href="claimbadge.php?cid='.$cid.'&amp;badgeid=' . Sanitize::encodeUrlParam($row[0]) . '">Link to claim badge</a> (provide to students)';
			echo '</li>';
		}
		echo '</ul>';
	} else {
		echo '<p>No badges have been defined</p>';
	}
	echo '<p><a href="definebadges.php?cid='.$cid.'&amp;badgeid=new">Add New Badge</a></p>';
	require("../footer.php");


} else {
	if (!empty($_GET['delete'])) {
		$badgeid = Sanitize::onlyInt($_GET['badgeid']);
		if ($badgeid==0) { echo 'Can not delete - invalid badgeid'; exit;}
		//DB $query = "SELECT courseid FROM imas_badgesettings WHERE id=$badgeid";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_result($result,0,0) != $cid) { echo 'Can not delete - badgeid is for a different course'; exit;}
		$stm = $DBH->prepare("SELECT courseid FROM imas_badgesettings WHERE id=:id");
		$stm->execute(array(':id'=>$badgeid));
		if ($stm->fetchColumn(0) != $cid) { echo 'Can not delete - badgeid is for a different course'; exit;}

		//DB $query = "DELETE FROM imas_badgesettings WHERE id=$badgeid";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_badgesettings WHERE id=:id");
		$stm->execute(array(':id'=>$badgeid));
		//DB $query = "DELETE FROM imas_badgerecords WHERE badgeid=$badgeid";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_badgerecords WHERE badgeid=:badgeid");
		$stm->execute(array(':badgeid'=>$badgeid));
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/definebadges.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
		exit;
	}
	if (!empty($_POST['badgename'])) { //postback
		$badgename = $_POST['badgename'];
		$badgetext = $_POST['badgetext'];
		$descr = $_POST['description'];
		$longdescr = $_POST['longdescription'];

		$req = array('data'=>array());
		$i = 0;
		while (isset($_POST['catselect'.$i])) {
			$_POST['catscore'.$i] = preg_replace('/[^\d\.]/','',$_POST['catscore'.$i]);
			if ($_POST['catselect'.$i]!='NS' && $_POST['catscore'.$i]!='' && is_numeric($_POST['catscore'.$i])) {
				$req['data'][] = array($_POST['catselect'.$i], $_POST['cattype'.$i], $_POST['catscore'.$i]);
			}
			$i++;
		}
		//DB $req = addslashes(serialize($req));
		$req = serialize($req);
		if ($_GET['badgeid']=='new') {
			//DB $query = "INSERT INTO imas_badgesettings (name, badgetext, description, longdescription, courseid, requirements) VALUES ('$badgename','$badgetext','$descr','$longdescr','$cid','$req')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("INSERT INTO imas_badgesettings (name, badgetext, description, longdescription, courseid, requirements) VALUES (:name, :badgetext, :description, :longdescription, :courseid, :requirements)");
			$stm->execute(array(':name'=>$badgename, ':badgetext'=>$badgetext, ':description'=>$descr, ':longdescription'=>$longdescr, ':courseid'=>$cid, ':requirements'=>$req));
		} else {
			$badgeid = Sanitize::onlyInt($_GET['badgeid']);
			//DB $query = "UPDATE imas_badgesettings SET name='$badgename',badgetext='$badgetext',description='$descr', longdescription='$longdescr', requirements='$req' WHERE id='$badgeid' AND courseid='$cid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_badgesettings SET name=:name,badgetext=:badgetext,description=:description, longdescription=:longdescription, requirements=:requirements WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':name'=>$badgename, ':badgetext'=>$badgetext, ':description'=>$descr, ':longdescription'=>$longdescr, ':requirements'=>$req, ':id'=>$badgeid, ':courseid'=>$cid));
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/definebadges.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
		exit;

	} else {  // create form
		require("../includes/htmlutil.php");
		if ($_GET['badgeid']=='new') {
			$name = "Enter badge title";
			$badgetext = "Enter text";
			$descr = "";
			$longdescr = "";
			$badgeid = 'new';
			$req = array('data'=>array());
		} else {
			$badgeid = Sanitize::onlyInt($_GET['badgeid']);
			//DB $query = "SELECT name,badgetext,description,longdescription,requirements FROM imas_badgesettings WHERE id=$badgeid AND courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)==0) { echo 'Invalid badge id for this course'; exit;}
			$stm = $DBH->prepare("SELECT name,badgetext,description,longdescription,requirements FROM imas_badgesettings WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$badgeid, ':courseid'=>$cid));
			if ($stm->rowCount()==0) { echo 'Invalid badge id for this course'; exit;}

			//DB list($name, $badgetext, $descr, $longdescr, $req) = mysql_fetch_row($result);
			list($name, $badgetext, $descr, $longdescr, $req) = $stm->fetch(PDO::FETCH_NUM);
			$req = unserialize($req);
		}
		//DB $query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
		$gbvals = array('-1');
		$gblabels = array('Course total');
		//DB while ($row=mysql_fetch_row($result)) {
		while ($row=$stm->fetch(PDO::FETCH_NUM)) {
			$gbvals[]= $row[0];
			$gblabels[] = $row[1];
		}

		$gtvals = array('0','3','1','2');
		$gtlabels = array('Past Due', 'Past and Attempted', 'Past and Available', 'All (including future)');


		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; <a href="definebadges.php?cid='.$cid.'">Badge Settings</a> ';
		echo '&gt; Details</div>';
		echo '<div id="headerbadgesettings" class="pagetitle"><h2>Badge Setting Details</h2></div>';

		echo '<form method="post" action="definebadges.php?cid='.$cid.'&amp;badgeid='.$badgeid.'">';

		echo '<p>Badge Name: <input type="text" size="80" maxlength="128" name="badgename" value="' . Sanitize::encodeStringForDisplay($name) . '"/><br/>Max 128 characters</p>';
		echo '<p>Badge Short Name: <input type="text" size="30" maxlength="128" name="badgetext" value="' . Sanitize::encodeStringForDisplay($badgetext) . '"/> <br/>';
		echo 'This text also displays on the badge image. <br/>Keep it under 24 characters, and not more than 12 characters in a single word.';
		echo '<br>Alternatively, provide a URL for a 90x90 .png to use as the badge image</p>';

		echo '<p>Badge Short Description: <input type="text" size="80" maxlength="128" name="description" value="' . Sanitize::encodeStringForDisplay($descr) . '"/><br/>Max 128 characters</p>';

		echo '<p>Badge Long Description:<br/> <textarea name="longdescription" cols="80" rows="5">' . Sanitize::encodeStringForDisplay($longdescr) . '</textarea></p>';

		echo '<p>Select the badge requirements.  All conditions must be met for the badge to be earned</p>';


		echo '<table class="gb"><thead><tr><th>Gradebook Category Total</th><th>Score to Use</th><th>Minimum score required (%)</th></tr></thead><tbody>';
		for ($i=0; $i<count($gbvals)+4; $i++) {
			echo '<tr><td>';
			writeHtmlSelect("catselect$i",$gbvals,$gblabels,isset($req['data'][$i])?$req['data'][$i][0]:null,'Select...','NS');
			echo '</td><td>';
			writeHtmlSelect("cattype$i",$gtvals,$gtlabels,isset($req['data'][$i])?$req['data'][$i][1]:0);
			echo '</td><td><input type="text" size="3" name="catscore' . $i . '" value="' . (isset($req['data'][$i]) ? Sanitize::encodeStringForDisplay($req['data'][$i][2]) : '').'"/>%</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p><input type="submit" value="Save"/></p>';
		echo '</form>';
		require("../footer.php");
	}
}



?>
