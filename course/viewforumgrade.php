<?php
//IMathAS: View of forum grade details, to be linked from gradebook
//(c) 2011 David Lippman

	require("../init.php");


	$cid = intval($_GET['cid']);

	if (isset($teacherid)) {
		$isteacher = true;
	} else {
		$isteacher = false;
	}
	$istutor = isset($tutorid);

	$stu = intval($_GET['stu']);

	if ($isteacher || $istutor) {
		$uid = intval($_GET['uid']);
	} else {
		$uid = $userid;
	}
	$forumid = intval($_GET['fid']);
	
	$embedded = !empty($_GET['embed']);

	if (($isteacher || $istutor) && (isset($_POST['score']) || isset($_POST['newscore']))) {
		if ($istutor) {
			//DB $query = "SELECT tutoredit FROM imas_forums WHERE id='$forumid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT tutoredit FROM imas_forums WHERE id=:id");
			$stm->execute(array(':id'=>$forumid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			if ($row[0]!=1) {
				exit; //not auth for score change
			}
		}
		//check for grades marked as newscore that aren't really new
		//shouldn't happen, but could happen if two browser windows open
		if (isset($_POST['newscore'])) {
			$keys = array_keys($_POST['newscore']);
			foreach ($keys as $k=>$v) {
				if (trim($v)=='') {unset($keys[$k]);}
			}
			if (count($keys)>0) {
				$kl = implode(',', array_map('intval', $keys));
				//DB $query = "SELECT refid FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid' AND refid IN ($kl)";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while($row = mysql_fetch_row($result)) {
				$stm = $DBH->prepare("SELECT refid FROM imas_grades WHERE gradetype='forum' AND gradetypeid=:gradetypeid AND userid=:userid AND refid IN ($kl)");
				$stm->execute(array(':gradetypeid'=>$forumid, ':userid'=>$uid));
				while($row = $stm->fetch(PDO::FETCH_NUM)) {
					$_POST['score'][$row[0]] = $_POST['newscore'][$row[0]];
					unset($_POST['newscore'][$row[0]]);
				}

			}
		}
		if (isset($_POST['score'])) {
			foreach($_POST['score'] as $k=>$sc) {
				if (trim($k)=='') {continue;}
				$sc = trim($sc);
				$_POST['feedback'.$k] = Sanitize::incomingHtml(trim($_POST['feedback'.$k]));
				if ($_POST['feedback'.$k] == '<p></p>') {
					$_POST['feedback'.$k] = '';
				}
				if ($sc!='') {
					//DB $query = "UPDATE imas_grades SET score='$sc',feedback='{$_POST['feedback'][$k]}' WHERE refid='$k' AND gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_grades SET score=:score,feedback=:feedback WHERE refid=:refid AND gradetype='forum' AND gradetypeid=:gradetypeid AND userid=:userid");
					$stm->execute(array(':score'=>$sc, ':feedback'=>$_POST['feedback'.$k], ':refid'=>$k, ':gradetypeid'=>$forumid, ':userid'=>$uid));
				} else {
					//DB $query = "DELETE FROM imas_grades WHERE refid='$k' AND gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("DELETE FROM imas_grades WHERE refid=:refid AND gradetype='forum' AND gradetypeid=:gradetypeid AND userid=:userid");
					$stm->execute(array(':refid'=>$k, ':gradetypeid'=>$forumid, ':userid'=>$uid));
				}
			}
		}
		if (isset($_POST['newscore'])) {
			foreach($_POST['newscore'] as $k=>$sc) {
				if (trim($k)=='') {continue;}
				if ($sc!='') {
					//DB $query = "INSERT INTO imas_grades (gradetype,gradetypeid,refid,userid,score,feedback) VALUES ";
					//DB $query .= "('forum','$forumid','$k','$uid','$sc','{$_POST['feedback'][$k]}')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,refid,userid,score,feedback) VALUES ";
					$query .= "(:gradetype, :gradetypeid, :refid, :userid, :score, :feedback)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':gradetype'=>'forum', ':gradetypeid'=>$forumid, ':refid'=>$k, ':userid'=>$uid, ':score'=>$sc, ':feedback'=>$_POST['feedback'.$k]));
				}
			}
		}
		if ($embedded) {
			echo '<html><body><p>'._('Saved').'</p>';
			echo '<p><button type=button onclick="parent.GB_hide()">'._('Done').'</button></p>';
			echo '</body></html>';
			exit;
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?stu=$stu&cid=$cid");
		}
		exit;
	}
	
	$query = "SELECT iu.LastName,iu.FirstName,i_f.name,i_f.points,i_f.tutoredit,i_f.enddate FROM imas_users AS iu, imas_forums as i_f ";
	$query .= "WHERE iu.id=:uid AND i_f.id=:fid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':uid'=>$uid, ':fid'=>$forumid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$possiblepoints = $row[3];
	$tutoredit = $row[4];
	$caneditscore = (isset($teacherid) || (isset($tutorid) && $tutoredit==1));
	$showlink = ($caneditscore || time()<$row[5]);

	$pagetitle = "View Forum Grade";
	if ($caneditscore && $sessiondata['useed']!=0) {
		$useeditor = "noinit";
		$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",null,true);</script>';
		$placeinhead .= '<style type="text/css">
		 .fbbox {
		 	min-width: 15em;
		 	min-height: 1em;
		 	margin: 0;
		 }
		 .fbbox p {
		 	padding: 1px;
		 }
		 .fbbox p + p {
		 	padding-top: .5em;
		 }
		 </style>';
	}
	if ($embedded) {
		$flexwidth = true;
		$nologo = true;
		$showlink = false;
	}
	require("../header.php");
	if (!$embedded) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=".Sanitize::encodeUrlParam($stu)."&cid=".Sanitize::encodeUrlParam($cid)."\">Gradebook</a> ";
		echo "&gt; View Forum Grade</div>";
	}

	echo '<div id="headerviewforumgrade" class="pagetitle"><h2>View Forum Grade</h2></div>';
	echo "<p>Grades on forum <b>".Sanitize::encodeStringForDisplay($row[2])."</b> for <b>".Sanitize::encodeStringForDisplay($row[1])." ".Sanitize::encodeStringForDisplay($row[0])."</b></p>";

	if ($istutor && $tutoredit==2) {
		echo '<p>No access to scores for this forum</p>';
		require("../footer.php");
		exit;
	}

	$scores = array();
	//DB $query = "SELECT score,feedback,refid FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid' AND userid='$uid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("SELECT score,feedback,refid FROM imas_grades WHERE gradetype='forum' AND gradetypeid=:gradetypeid AND userid=:userid");
	$stm->execute(array(':gradetypeid'=>$forumid, ':userid'=>$uid));
	$totalpts = 0;
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$scores[$row[2]] = $row;
		$totalpts += $row[0];
	}

	if ($possiblepoints==0) {
		echo '<p>This forum is not a graded forum</p>';
	} else {
		echo "<p>Total: ". Sanitize::onlyInt($totalpts) ." out of ". Sanitize::onlyInt($possiblepoints)." </p>";
	}

	if ($caneditscore) {
		$embedstr = ($embedded?'&embed=true':'');
		echo "<form method=\"post\" action=\"viewforumgrade.php?cid=$cid&fid=$forumid&stu=$stu&uid=$uid$embedstr\">";
	}

	echo '<table class="gb"><thead><tr><th>Post</th><th>Points</th><th>Private Feedback</th></tr></thead><tbody>';
	//DB $query = "SELECT id,threadid,subject FROM imas_forum_posts WHERE forumid='$forumid' AND userid='$uid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT id,threadid,subject FROM imas_forum_posts WHERE forumid=:forumid AND userid=:userid");
	$stm->execute(array(':forumid'=>$forumid, ':userid'=>$uid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo "<tr><td>";
		if ($showlink) {
			echo "<a href=\"$imasroot/forums/posts.php?cid=".Sanitize::courseId($cid)."&forum=".Sanitize::encodeUrlParam($forumid)."&thread=".Sanitize::encodeUrlParam($row[1])."\">";
		}
		echo Sanitize::encodeStringForDisplay($row[2]);
		if ($showlink) {
			echo '</a>';
		}
		echo "</td>";
		if ($caneditscore) {
			if (isset($scores[$row[0]])) {
				echo "<td><input type=text size=3 name=\"score[".Sanitize::encodeStringForDisplay($row[0])."]\" id=\"score".Sanitize::encodeStringForDisplay($row[0])."\" value=\"";
				echo Sanitize::encodeStringForDisplay($scores[$row[0]][0]);
			} else {
				echo "<td><input type=text size=3 name=\"newscore[".Sanitize::encodeStringForDisplay($row[0])."]\" id=\"score".Sanitize::encodeStringForDisplay($row[0])."\" value=\"";
			}
			echo "\" /> </td>";
			//echo "<td><textarea cols=40 rows=1 id=\"feedback".Sanitize::encodeStringForDisplay($row[0])."\" name=\"feedback[".Sanitize::encodeStringForDisplay($row[0])."]\">".Sanitize::encodeStringForDisplay($scores[$row[0]][1])."</textarea></td>";
			$postid = Sanitize::onlyInt($row[0]);
			if ($sessiondata['useed']==0) {
				echo "<td><textarea class=scorebox cols=\"40\" rows=\"1\" name=\"feedback$postid\" id=\"feedback$postid\">";
				if ($scores[$row[0]][1]!==null) {
					echo Sanitize::encodeStringForDisplay($scores[$row[0]][1]);
				}
				echo "</textarea></td>";
			} else {
				echo '<td><div class="fbbox" id="feedback'.$postid.'">';
				if ($scores[$row[0]][1]!==null) {
					echo Sanitize::outgoingHtml($scores[$row[0]][1]);
				}
				echo '</div></td>';
			}
		} else {
			if (isset($scores[$row[0]])) {
				echo '<td>'.Sanitize::encodeStringForDisplay($scores[$row[0]][0]).'</td>';
			} else {
				echo "<td>-</td>";
			}
			echo '<td>'.Sanitize::outgoingHtml($scores[$row[0]][1]).'</td>';
		}
		echo "</tr>";
	}
	if ($caneditscore || isset($scores[0])) {
		echo '<tr><td>Additional score</td>';
		if ($caneditscore) {
			if (isset($scores[0])) {
				echo "<td><input type=text size=3 name=\"score[0]\" id=\"score0\" value=\"";
				echo Sanitize::encodeStringForDisplay($scores[0][0]);
			} else {
				echo "<td><input type=text size=3 name=\"newscore[0]\" id=\"score0\" value=\"";
			}
			echo "\" /> </td>";
			//echo "<td><textarea cols=40 rows=1 id=\"feedback0\" name=\"feedback[0]\">".Sanitize::encodeStringForDisplay($scores[0][1])."</textarea></td>";
			if ($sessiondata['useed']==0) {
				echo "<td><textarea class=scorebox cols=\"40\" rows=\"1\" name=\"feedback0\" id=\"feedback0\">";
				if ($scores[0][1]!==null) {
					echo Sanitize::encodeStringForDisplay($scores[0][1]);
				}
				echo "</textarea></td>";
			} else {
				echo '<td><div class="fbbox" id="feedback0">';
				if ($scores[0][1]!==null) {
					echo Sanitize::outgoingHtml($scores[0][1]);
				}
				echo '</div></td>';
			}
		} else {
			echo '<td>'.Sanitize::encodeStringForDisplay($scores[0][0]).'</td>';
			echo '<td>'.Sanitize::encodeStringForDisplay($scores[0][1]).'</td>';
		}
		echo "</tr>";
	}
	echo '</tbody></table>';
	if ($caneditscore) {
		echo '<p><input type="submit" value="Save Scores" /></p>';
		echo '</form>';
	}
	require("../footer.php");
?>
