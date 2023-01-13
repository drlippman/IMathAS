<?php
//IMathAS:  Add/modify gradebook comments
//(c) 2006 David Lippman

	require("../init.php");


	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);
	if (isset($_GET['comtype'])) {
		$comtype = $_GET['comtype'];
	} else {
		$comtype = 'stu';
	}
	function fopen_utf8 ($filename, $mode) {
	    $file = @fopen($filename, $mode);
	    $bom = fread($file, 3);
	    if ($bom != b"\xEF\xBB\xBF") {
				fclose($file);
				$file = @fopen($filename, $mode);
	    }
	    return $file;
	}

	if (isset($_GET['upload'])) {
		require("../header.php");

        echo "<div class=breadcrumb>$breadcrumbbase ";
        if (empty($_COOKIE['fromltimenu'])) {
            echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
        }
        echo " <a href=\"gradebook.php?stu=0&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&cid=$cid\">Gradebook</a> ";
		echo "&gt; <a href=\"gbcomments.php?stu=0&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&cid=$cid&comtype=".Sanitize::encodeUrlParam($comtype)."\">Gradebook Comments</a> &gt; Upload Comments</div>";

		if ($comtype=='stu') {
			echo '<div id="headergbcomments" class="pagetitle"><h1>Upload Student Comments</h1></div>';
		} else if ($comtype=='instr') {
			echo '<div id="headergbcomments" class="pagetitle"><h1>Upload Instructor Notes</h1></div>';
		}

		if (isset($_FILES['userfile']['name']) && $_FILES['userfile']['name']!='') {
			if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {

				$failures = array();
				$successes = 0;

				if ($_POST['useridtype']==0) {
					$usercol = $_POST['usernamecol']-1;
				} else if ($_POST['useridtype']==1) {
					$usercol = $_POST['fullnamecol']-1;
				}
				$scorecol = $_POST['gradecol']-1;

				// $_FILES[]['tmp_name'] is not user provided. This is safe.
				$handle = fopen_utf8(realpath($_FILES['userfile']['tmp_name']),'r');
				if ($_POST['hashdr']==1) {
					$data = fgetcsv($handle,4096,',');
				} else if ($_POST['hashdr']==2) {
					$data = fgetcsv($handle,4096,',');
					$data = fgetcsv($handle,4096,',');
				}
				while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
					$query = "SELECT imas_users.id FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid AND imas_students.courseid=:cid AND ";
					$qarr = array(':cid'=>$cid);
					if ($_POST['useridtype']==0) {
						$query .= "imas_users.SID=:SID";
						$qarr[':SID'] = Sanitize::stripHtmlTags($data[$usercol]);
					} else if ($_POST['useridtype']==1) {
						list($last,$first) = explode(',',$data[$usercol]);
						$first = str_replace(' ','',$first);
						$last = str_replace(' ','',$last);
						$query .= "imas_users.FirstName=:first AND imas_users.LastName=:last";
						$qarr[':first'] = Sanitize::stripHtmlTags($first);
						$qarr[':last'] = Sanitize::stripHtmlTags($last);
						//echo $query;
					} else {
						$query .= "0";
					}
					$stm = $DBH->prepare($query);
					$stm->execute($qarr);
					if ($stm->rowCount()>0) {
						$cuserid=$stm->fetchColumn(0);
						if ($comtype=='stu') {
							$stm = $DBH->prepare("UPDATE imas_students SET gbcomment=:gbcomment WHERE userid=:userid AND courseid=:courseid");
							$stm->execute(array(':gbcomment'=>Sanitize::stripHtmlTags($data[$scorecol]), ':userid'=>$cuserid, ':courseid'=>$cid));
						} else if ($comtype=='instr') {
							$stm = $DBH->prepare("UPDATE imas_students SET gbinstrcomment=:gbinstrcomment WHERE userid=:userid AND courseid=:courseid");
							$stm->execute(array(':gbinstrcomment'=>Sanitize::stripHtmlTags($data[$scorecol]), ':userid'=>$cuserid, ':courseid'=>$cid));
						}
						$successes++;
					} else {
						$failures[] = $data[$usercol];
					}
				}

				echo "<p>Comments uploaded.". Sanitize::encodeStringForDisplay($successes) ."records.</p> ";
				if (count($failures)>0) {
					echo "<p>Comment upload failure on: <br/>";
					echo implode('<br/>', array_map('Sanitize::encodeStringForDisplay', $failures));
					echo '</p>';
				}
				if ($successes>0) {
					echo "<a href=\"gbcomments.php?stu=0&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&cid=$cid&comtype=".Sanitize::encodeUrlParam($comtype)."\">Return to comments list</a></p>";
					require("../footer.php");
					exit;
				}

				//unlink($_FILES['userfile']['tmp_name']);
			} else {
				echo "File Upload error";
			}
		}

		echo "<form enctype=\"multipart/form-data\" method=post action=\"gbcomments.php?cid=$cid&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&comtype=".Sanitize::encodeUrlParam($comtype)."&upload=true\">\n";

		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"3000000\" />\n";
		echo "<span class=form>Grade file (CSV): </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo '<span class=form>File has header row?</span><span class=formright>';
		echo ' <input type=radio name="hashdr" value="0" checked=1 />No header<br/>';
		echo ' <input type=radio name="hashdr" value="1" />Has 1 row header<br/>';
		echo ' <input type=radio name="hashdr" value="2" />Has 2 row header</span><br class="form" />';

		echo '<span class=form>Comments are in column:</span><span class=formright>';
		echo '<input type=text size=4 name="gradecol" value="2"/></span><br class="form" />';

		echo '<span class=form>User is identified by:</span><span class=formright>';
		echo '<input type=radio name="useridtype" value="0" checked=1 />Username (login name) in column <input type=text size=4 name="usernamecol" value="2" /><br/>';
		echo '<input type=radio name="useridtype" value="1" />Lastname, Firstname in column <input type=text size=4 name="fullnamecol" value="1" />';
		echo '</span><br class="form" />';

		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";

		echo "</form>";

		require("../footer.php");
		exit;


	}

	if (isset($_GET['record'])) {
		$stm = $DBH->prepare("SELECT id FROM imas_students WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			//if ($_POST[$row[0]]!='') {
			$rowInfo = Sanitize::stripHtmlTags($_POST[$row[0]]);
				if ($comtype=='stu') {
					$stm2 = $DBH->prepare("UPDATE imas_students SET gbcomment=:gbcomment WHERE id=:id");
					$stm2->execute(array(':gbcomment'=>$rowInfo, ':id'=>$row[0]));
				} else if ($comtype=='instr') {
					$stm2 = $DBH->prepare("UPDATE imas_students SET gbinstrcomment=:gbinstrcomment WHERE id=:id");
					$stm2->execute(array(':gbinstrcomment'=>$rowInfo, ':id'=>$row[0]));
				}
			//}
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?stu=".Sanitize::encodeUrlParam($_GET['stu'])."&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'])."&cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		exit;
	}

	require("../header.php");
	echo '<script type="text/javascript">function sendtoall(type) {'."\n";
	echo '  var form=document.getElementById("mainform");'."\n";
	echo '  for (var e = 0; e<form.elements.length; e++) {'."\n";
	echo '      var el = form.elements[e];'."\n";
	echo '      if (el.type=="textarea" && el.id!="toall") {'."\n";
	echo '		if (type==0) { el.value = document.getElementById("toall").value + el.value;}'."\n";
	echo '		else if (type==1) { el.value = el.value+document.getElementById("toall").value;}'."\n";
	echo '		else if (type==2) { el.value = document.getElementById("toall").value;}'."\n";
	echo '      }'."\n";
	echo '   }'."\n";
	echo ' } </script>'."\n";

    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
	if ($comtype=='stu') {
		echo " <a href=\"gradebook.php?stu=0&gbmode=". Sanitize::encodeUrlParam($_GET['gbmode'] ?? '')."&cid=$cid\">Gradebook</a> &gt; Gradebook Comments</div>";
		echo "<div class=\"cpmid\"><a href=\"gbcomments.php?cid=$cid&stu=".Sanitize::encodeUrlParam($_GET['stu'])."&comtype=instr\">View/Edit Instructor notes</a></div>";
		echo '<h1>Modify Gradebook Comments</h1>';
		echo "<p>These comments will display at the top of the student's gradebook score list.</p>";

	} else if ($comtype=='instr') {
		echo " <a href=\"gradebook.php?stu=0&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'] ?? '')."&cid=$cid\">Gradebook</a> &gt; Instructor Notes</div>";
		echo "<div class=\"cpmid\"><a href=\"gbcomments.php?cid=$cid&stu=".Sanitize::encodeUrlParam($_GET['stu'])."&comtype=stu\">View/Edit Student comments</a></div>";
		echo '<h1>Modify Instructor Notes</h1>';
		echo "<p>These notes will only display on this page and gradebook exports.  They will not be visible to students.</p>";
	}
	echo "<p><a href=\"gbcomments.php?cid=$cid&stu=".Sanitize::encodeUrlParam($_GET['stu'])."&gbmode=".Sanitize::encodeUrlParam($_GET['gbmode'] ?? '')."&upload=true&comtype=".Sanitize::encodeUrlParam($comtype)."\">Upload comments</a></p>";

	echo "<form id=\"mainform\" method=post action=\"gbcomments.php?cid=$cid&stu=".Sanitize::encodeUrlParam($_GET['stu'])."&comtype=".Sanitize::encodeUrlParam($comtype)."&record=true\">";
	echo "<span class=form>Add/Replace to all:</span><span class=formright><textarea cols=50 rows=3 id=\"toall\" ></textarea>";

	echo '<br/><input type=button value="Prepend" onClick="sendtoall(0);"/> <input type=button value="Append" onclick="sendtoall(1)"/> <input type=button value="Replace" onclick="sendtoall(2)"/></span><br class="form"/>';
	if ($comtype=='stu') {
		$query = "SELECT i_s.id,iu.LastName,iu.FirstName,i_s.gbcomment FROM imas_students AS i_s, imas_users as iu ";
	} else if ($comtype=='instr') {
		$query = "SELECT i_s.id,iu.LastName,iu.FirstName,i_s.gbinstrcomment FROM imas_students AS i_s, imas_users as iu ";
	}
	$query .= "WHERE i_s.userid=iu.id AND i_s.courseid=:courseid ORDER BY iu.LastName,iu.FirstName";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo "<span class='form pii-full-name'>".Sanitize::encodeStringForDisplay($row[1]).", ". Sanitize::encodeStringForDisplay($row[2])."</span><span class=formright><textarea cols=50 rows=3 name=\"".Sanitize::encodeStringForDisplay($row[0])."\">".Sanitize::encodeStringForDisplay($row[3], true)."</textarea></span><br class=form>";
	}
	echo '<div class="submit"><input type="submit" value="'._('Save Comments').'"/></div>';
	echo "</form>";
	require("../footer.php");

?>
