<?php
//IMathAS:  Modify a question's settings in an assessment: grid for multiple.  Included in addquestions.php
//(c) 2006 David Lippman


	if (!(isset($teacherid))) {
		echo "This page cannot be accessed directly";
		exit;
	}

	if ($_GET['process']== true) {
		require_once("../includes/updateptsposs.php");
		if (isset($_POST['add'])) { //adding new questions
			//DB $query = "SELECT itemorder,viddata FROM imas_assessments WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $itemorder = mysql_result($result,0,0);
			//DB $viddata = mysql_result($result,0,1);
			$stm = $DBH->prepare("SELECT itemorder,viddata,defpoints FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			list($itemorder, $viddata, $defpoints) = $stm->fetch(PDO::FETCH_NUM);

			$newitemorder = '';
			if (isset($_POST['addasgroup'])) {
				$newitemorder = '1|0';
			}
			foreach (explode(',',$_POST['qsetids']) as $qsetid) {
				for ($i=0; $i<$_POST['copies'.$qsetid];$i++) {
					$points = trim($_POST['points'.$qsetid]);
					$attempts = trim($_POST['attempts'.$qsetid]);
					$showhints = intval($_POST['showhints'.$qsetid]);
					if ($points=='') { $points = 9999;}
					if ($attempts=='') {$attempts = 9999;}
					if ($points==9999 && isset($_POST['pointsforparts']) && $_POST['qparts'.$qsetid]>1) {
						$points = intval($_POST['qparts'.$qsetid]);
					}
					//DB $query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,penalty,regen,showans,questionsetid) ";
					//DB $query .= "VALUES ('$aid','$points','$attempts',$showhints,9999,0,0,'$qsetid')";
					//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB $qid = mysql_insert_id();
					$query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,penalty,regen,showans,questionsetid) ";
					$query .= "VALUES (:assessmentid, :points, :attempts, :showhints, :penalty, :regen, :showans, :questionsetid)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':assessmentid'=>$aid, ':points'=>$points, ':attempts'=>$attempts, ':showhints'=>$showhints, ':penalty'=>9999, ':regen'=>0, ':showans'=>0, ':questionsetid'=>$qsetid));
					$qid = $DBH->lastInsertId();
					if ($newitemorder=='') {
						$newitemorder = $qid;
					} else {
						if (isset($_POST['addasgroup'])) {
							$newitemorder = $newitemorder . "~$qid";
						} else {
							$newitemorder = $newitemorder . ",$qid";
						}
					}
				}
			}

			if ($viddata != '') {
				if ($itemorder=='') {
					$nextnum = 0;
				} else {
					$nextnum = substr_count($itemorder,',')+1;
				}
				$numnew= substr_count($newitemorder,',')+1;
				$viddata = unserialize($viddata);
				if (!isset($viddata[count($viddata)-1][1])) {
					$finalseg = array_pop($viddata);
				} else {
					$finalseg = '';
				}
				for ($i=$nextnum;$i<$nextnum+$numnew;$i++) {
					$viddata[] = array('','',$i);
				}
				if ($finalseg != '') {
					$viddata[] = $finalseg;
				}
				//DB $viddata = addslashes(serialize($viddata));
				$viddata = serialize($viddata);
			}

			if ($itemorder == '') {
				$itemorder = $newitemorder;
			} else {
				$itemorder .= ','.$newitemorder;
			}

			//DB $query = "UPDATE imas_assessments SET itemorder='$itemorder',viddata='$viddata' WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder,viddata=:viddata WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':viddata'=>$viddata, ':id'=>$aid));
			
			updatePointsPossible($aid, $itemorder, $defpoints);
			
		} else if (isset($_POST['mod'])) { //modifying existing

			//DB $query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $itemorder = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT itemorder,defpoints FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			list($itemorder, $defpoints) = $stm->fetch(PDO::FETCH_NUM);

			//what qsetids do we need for adding copies?
			$lookupid = array();
			foreach(explode(',',$_POST['qids']) as $qid) {
				if (intval($_POST['copies'.$qid])>0 && intval($qid)>0) {
					$lookupid[] = intval($qid);
				}
			}
			//lookup qsetids
			$qidtoqsetid = array();
			if (count($lookupid)>0) {
				//DB $query = "SELECT id,questionsetid FROM imas_questions WHERE id IN (".implode(',',$lookupid).")";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$stm = $DBH->query("SELECT id,questionsetid FROM imas_questions WHERE id IN (".implode(',',$lookupid).")"); //sanitized above
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$qidtoqsetid[$row[0]] = $row[1];
				}
			}

			foreach(explode(',',$_POST['qids']) as $qid) {
				$points = trim($_POST['points'.$qid]);
				$attempts = trim($_POST['attempts'.$qid]);
				$showhints = intval($_POST['showhints'.$qid]);
				if ($points=='') { $points = 9999;}
				if ($attempts=='') {$attempts = 9999;}
				//DB $query = "UPDATE imas_questions SET points='$points',attempts='$attempts',showhints=$showhints WHERE id='$qid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_questions SET points=:points,attempts=:attempts,showhints=:showhints WHERE id=:id");
				$stm->execute(array(':points'=>$points, ':attempts'=>$attempts, ':showhints'=>$showhints, ':id'=>$qid));
				if (intval($_POST['copies'.$qid])>0 && intval($qid)>0) {
					for ($i=0;$i<intval($_POST['copies'.$qid]);$i++) {
						$qsetid = $qidtoqsetid[$qid];
						//DB $query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,penalty,regen,showans,questionsetid) ";
						//DB $query .= "VALUES ('$aid','$points','$attempts',$showhints,9999,0,0,'$qsetid')";
						//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
						//DB $newqid = mysql_insert_id();
						$query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,penalty,regen,showans,questionsetid) ";
						$query .= "VALUES (:assessmentid, :points, :attempts, :showhints, :penalty, :regen, :showans, :questionsetid)";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':assessmentid'=>$aid, ':points'=>$points, ':attempts'=>$attempts, ':showhints'=>$showhints, ':penalty'=>9999, ':regen'=>0, ':showans'=>0, ':questionsetid'=>$qsetid));
						$newqid = $DBH->lastInsertId();

						$itemarr = explode(',',$itemorder);
						$key = array_search($qid,$itemarr);
						if ($key===false) {
							$itemarr[] = $newqid;
						} else {
							array_splice($itemarr,$key+1,0,$newqid);
						}
						$itemorder = implode(',',$itemarr);
					}
				}
			}
			//DB $query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$aid));
			
			updatePointsPossible($aid, $itemorder, $defpoints);
		}

	} else {
		//get defaults
		$query = "SELECT defpoints,defattempts,showhints FROM imas_assessments ";
		$query .= "WHERE id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$aid));
		$defaults = $stm->fetch(PDO::FETCH_ASSOC);
		$defaults['showhints'] = ($defaults['showhints']==1)?_('Yes'):_('No');

		$pagetitle = "Question Settings";
		$placeinhead = '<script type="text/javascript">
			function previewq(qn) {
			  previewpop = window.open(imasroot+"/course/testquestion.php?cid="+cid+"&qsetid="+qn,"Testing","width="+(.4*screen.width)+",height="+(.8*screen.height)+",scrollbars=1,resizable=1,status=1,top=20,left="+(.6*screen.width-20));
			  previewpop.focus();
			}
			</script>';
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"addquestions.php?aid=$aid&cid=$cid\">Add/Remove Questions</a> &gt; ";

		echo "Question Settings</div>\n";

?>
<div id="headermodquestiongrid" class="pagetitle"><h2>Modify Question Settings</h2></div>
<p>For more advanced settings, modify the settings for individual questions after adding.</p>
<?php
if (isset($_POST['checked'])) { //modifying existing
	echo "<form method=post action=\"addquestions.php?modqs=true&process=true&cid=$cid&aid=$aid\">";
} else {
	echo "<form method=post action=\"addquestions.php?addset=true&process=true&cid=$cid&aid=$aid\">";
}
?>
<p>Leave items blank to use the assessment's default values</p>
<table class=gb>
<thead><tr>
<?php
		if (isset($_POST['checked'])) { //modifying existing questions

			$qids = array();
			$qns = array();
			foreach ($_POST['checked'] as $k=>$v) {
				$v = explode(':',$v);
				$qids[] = Sanitize::onlyInt($v[1]);
				$qnpts = explode('-',$v[2]);
				if (count($qnpts)==1) {
					$qns[$v[1]] = $qnpts[0]+1;
				} else {
					$qns[$v[1]] = ($qnpts[0]+1).'-'.($qnpts[1]+1);
				}
			}
			$qrows = array();
			//DB $query = "SELECT imas_questions.id,imas_questionset.description,imas_questions.points,imas_questions.attempts,imas_questions.showhints,imas_questionset.extref ";
			//DB $query .= "FROM imas_questions,imas_questionset WHERE imas_questionset.id=imas_questions.questionsetid AND ";
			//DB $query .= "imas_questions.id IN ('".implode("','",$qids)."')";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$qidlist = implode(',', array_map('intval', $qids));
			$query = "SELECT imas_questions.id,imas_questionset.description,imas_questions.points,imas_questions.attempts,imas_questions.showhints,imas_questionset.extref,imas_questionset.id AS qsid ";
			$query .= "FROM imas_questions,imas_questionset WHERE imas_questionset.id=imas_questions.questionsetid AND ";
			$query .= "imas_questions.id IN ($qidlist)";
			$stm = $DBH->query($query);
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				if ($row['points']==9999) {
					$row['points'] = '';
				}
				if ($row['attempts']==9999) {
					$row['attempts'] = '';
				}

				$qrows[$row['id']] = '<tr><td>'.Sanitize::onlyInt($qns[$row['id']]).'</td><td>'.Sanitize::encodeStringForDisplay($row['description']).'</td>';
				$qrows[$row['id']] .= '<td>';
				if ($row['extref']!='') {
					$extref = explode('~~',$row['extref']);
					$hasvid = false;  $hasother = false;
					foreach ($extref as $v) {
						if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false) {
							$hasvid = true;
						} else {
							$hasother = true;
						}
					}
					$page_questionTable[$i]['extref'] = '';
					if ($hasvid) {
						$qrows[$row['id']] .= "<img src=\"$imasroot/img/video_tiny.png\" alt=\"Video\"/>";
					}
					if ($hasother) {
						$qrows[$row['id']] .= "<img src=\"$imasroot/img/html_tiny.png\" alt=\"Help Resource\"/>";
					}
				}
				$qrows[$row['id']] .= '</td>';
				$qrows[$row['id']] .= '<td><button type="button" onclick="previewq('.$row['qsid'].')">'._('Preview').'</button></td>';
				$qrows[$row['id']] .= "<td><input type=text size=4 name=\"points{$row['id']}\" value=\"{$row['points']}\" /></td>";
				$qrows[$row['id']] .= "<td><input type=text size=4 name=\"attempts{$row['id']}\" value=\"{$row['attempts']}\" /></td>";
				$qrows[$row['id']] .= "<td><select name=\"showhints{$row['id']}\">";
				$qrows[$row['id']] .= '<option value="0" '.(($row['showhints']==0)?'selected="selected"':'').'>Use Default</option>';
				$qrows[$row['id']] .= '<option value="1" '.(($row['showhints']==1)?'selected="selected"':'').'>No</option>';
				$qrows[$row['id']] .= '<option value="2" '.(($row['showhints']==2)?'selected="selected"':'').'>Yes</option></select></td>';
				$qrows[$row['id']] .= "<td><input type=text size=4 name=\"copies" . Sanitize::onlyInt($row['id']) . "\" value=\"0\" /></td>";
				$qrows[$row['id']] .= '</tr>';
			}
			echo "<th>Q#<br/>&nbsp;</th><th>Description<br/>&nbsp;</th><th></th><th></th>";
			echo '<th>Points<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['defpoints']).'</i></th>';
			echo '<th>Attempts (0 for unlimited)<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['defattempts']).'</i></th>';
			echo '<th>Show hints &amp; video buttons?<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['showhints']).'</i></th>';
			echo "<th>Additional Copies to Add<br/>&nbsp;</th></tr></thead>";
			echo "<tbody>";

			//DB $query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $itemorder = explode(',', mysql_result($result,0,0));
			$stm = $DBH->prepare("SELECT itemorder FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			$itemorder = explode(',', $stm->fetchColumn(0));
			foreach ($itemorder as $item) {
				if (strpos($item,'~')!==false) {
					$subs = explode('~',$item);
					if (strpos($subs[0],'|')!==false) {
						array_shift($subs);
					}
					foreach ($subs as $sub) {
						if (isset($qrows[$sub])) {
							echo $qrows[$sub];
						}
					}
				} else if (isset($qrows[$item])) {
					echo $qrows[$item];
				}
			}

			echo '</tbody></table>';
			echo '<input type=hidden name="qids" value="'.Sanitize::encodeStringForDisplay(implode(',',$qids)).'" />';
			echo '<input type=hidden name="mod" value="true" />';

			echo '<div class="submit"><input type="submit" value="'._('Save Settings').'"></div>';

		} else { //adding new questions
			echo "<th>Description</th><th></th><th></th>";
			echo '<th>Points<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['defpoints']).'</i></th>';
			echo '<th>Attempts (0 for unlimited)<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['defattempts']).'</i></th>';
			echo '<th>Show hints &amp; video buttons?<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['showhints']).'</i></th>';
			echo "<th>Number of Copies to Add</th></tr></thead>";
			echo "<tbody>";

			//DB $query = "SELECT id,description,extref,qtype,control FROM imas_questionset WHERE id IN ('".implode("','",$_POST['nchecked'])."')";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$checked = implode(',', array_map('intval', $_POST['nchecked']));
			$stm = $DBH->query("SELECT id,description,extref,qtype,control FROM imas_questionset WHERE id IN ($checked)");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if ($row[3]=='multipart') {
					preg_match('/anstypes\s*=(.*)/',$row[4],$match);
					$n = substr_count($match[1],',')+1;
				} else {
					$n = 1;
				}
				echo '<tr><td>'.Sanitize::encodeStringForDisplay($row[1]).'</td>';
				if ($row[2]!='') {
					$extref = explode('~~',$row[2]);
					$hasvid = false;  $hasother = false;
					foreach ($extref as $v) {
						if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false) {
							$hasvid = true;
						} else {
							$hasother = true;
						}
					}
					$page_questionTable[$i]['extref'] = '';
					if ($hasvid) {
						echo "<td><img src=\"$imasroot/img/video_tiny.png\" alt=\"Video\"/></td>";
					}
					if ($hasother) {
						echo "<td><img src=\"$imasroot/img/html_tiny.png\" alt=\"Help Resource\"/></td>";
					}
				} else {
					echo '<td></td>';
				}
				echo '<td><button type="button" onclick="previewq('.Sanitize::encodeStringForJavascript($row[0]).')">'._('Preview').'</button></td>';
				echo "<td><input type=text size=4 name=\"points" . Sanitize::encodeStringForDisplay($row[0]) . "\" value=\"\" />";
				echo '<input type="hidden" name="qparts'.Sanitize::encodeStringForDisplay($row[0]).'" value="'.Sanitize::onlyInt($n).'"/></td>';
				echo "<td><input type=text size=4 name=\"attempts" . Sanitize::encodeStringForDisplay($row[0]) ."\" value=\"\" /></td>";
				echo "<td><select name=\"showhints" . Sanitize::encodeStringForDisplay($row[0]) . "\">";
				echo '<option value="0" selected="selected">Use Default</option>';
				echo '<option value="1">No</option>';
				echo '<option value="2">Yes</option></select></td>';
				echo "<td><input type=text size=4 name=\"copies" . Sanitize::encodeStringForDisplay($row[0]) . "\" value=\"1\" /></td>";
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<input type=hidden name="qsetids" value="'.Sanitize::encodeStringForDisplay(implode(',',$_POST['nchecked'])).'" />';
			echo '<input type=hidden name="add" value="true" />';

			echo '<p><input type=checkbox name="addasgroup" value="1" /> Add as a question group?</p>';
			echo '<p><input type=checkbox name="pointsforparts" value="1" /> Set the points equal to the number of parts for multipart?</p>';
			echo '<div class="submit"><input type="submit" value="'._('Add Questions').'"></div>';
		}
		echo '</form>';
		require("../footer.php");
		exit;
	}
?>
