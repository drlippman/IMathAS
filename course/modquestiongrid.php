<?php
//IMathAS:  Modify a question's settings in an assessment: grid for multiple.  Included in addquestions.php
//(c) 2006 David Lippman
	if (!(isset($teacherid))) {
		echo "This page cannot be accessed directly";
		exit;
	}
	
	if ($_GET['process']== true) {
		if (isset($_POST['add'])) { //adding new questions
			$query = "SELECT itemorder,viddata FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = mysql_result($result,0,0);
			$viddata = mysql_result($result,0,1);
			
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
					$query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,penalty,regen,showans,questionsetid) ";
					$query .= "VALUES ('$aid','$points','$attempts',$showhints,9999,0,0,'$qsetid')";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$qid = mysql_insert_id();
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
				$viddata = addslashes(serialize($viddata));
			}
			
			if ($itemorder == '') {
				$itemorder = $newitemorder;
			} else {
				$itemorder .= ','.$newitemorder;
			}
			
			
			
			$query = "UPDATE imas_assessments SET itemorder='$itemorder',viddata='$viddata' WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		} else if (isset($_POST['mod'])) { //modifying existing
			
			$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = mysql_result($result,0,0);
			
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
				$query = "SELECT id,questionsetid FROM imas_questions WHERE id IN (".implode(',',$lookupid).")";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$qidtoqsetid[$row[0]] = $row[1];
				}
			}
			
			foreach(explode(',',$_POST['qids']) as $qid) {
				$points = trim($_POST['points'.$qid]);
				$attempts = trim($_POST['attempts'.$qid]);
				$showhints = intval($_POST['showhints'.$qid]);
				if ($points=='') { $points = 9999;}
				if ($attempts=='') {$attempts = 9999;}
				$query = "UPDATE imas_questions SET points='$points',attempts='$attempts',showhints=$showhints WHERE id='$qid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				if (intval($_POST['copies'.$qid])>0 && intval($qid)>0) {
					for ($i=0;$i<intval($_POST['copies'.$qid]);$i++) {
						$qsetid = $qidtoqsetid[$qid];
						$query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,penalty,regen,showans,questionsetid) ";
						$query .= "VALUES ('$aid','$points','$attempts',$showhints,9999,0,0,'$qsetid')";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						$newqid = mysql_insert_id();
						
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
			$query = "UPDATE imas_assessments SET itemorder='$itemorder' WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		}
		
	} else {
		$pagetitle = "Question Settings";
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
		echo "&gt; <a href=\"addquestions.php?aid=$aid&cid=$cid\">Add/Remove Questions</a> &gt; ";
		
		echo "Question Settings</div>\n";
	
?>
<div id="headermodquestiongrid" class="pagetitle"><h2>Modify Question Settings</h2></div>
<p>For more advanced settings, modify the settings for individual questions after adding.
<?php
if (isset($_POST['checked'])) { //modifying existing
	echo "<form method=post action=\"addquestions.php?modqs=true&process=true&cid=$cid&aid=$aid\">";
} else {
	echo "<form method=post action=\"addquestions.php?addset=true&process=true&cid=$cid&aid=$aid\">";
}
?>
Leave items blank to use the assessment's default values<br/>
<table class=gb>
<thead><tr>
<?php
		if (isset($_POST['checked'])) { //modifying existing questions
			
			$qids = array();
			foreach ($_POST['checked'] as $k=>$v) {
				$v = explode(':',$v);
				$qids[] = $v[1];
			}
			$qrows = array();
			$query = "SELECT imas_questions.id,imas_questionset.description,imas_questions.points,imas_questions.attempts,imas_questions.showhints,imas_questionset.extref ";
			$query .= "FROM imas_questions,imas_questionset WHERE imas_questionset.id=imas_questions.questionsetid AND ";
			$query .= "imas_questions.id IN ('".implode("','",$qids)."')";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if ($row[2]==9999) {
					$row[2] = '';
				} 
				if ($row[3]==9999) {
					$row[3] = '';
				}
				
				$qrows[$row[0]] = '<tr><td>'.$row[1].'</td>';
				$qrows[$row[0]] .= '<td>';
				if ($row[5]!='') {
					$extref = explode('~~',$row[5]);
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
						$qrows[$row[0]] .= "<img src=\"$imasroot/img/video_tiny.png\"/>";
					}
					if ($hasother) {
						$qrows[$row[0]] .= "<img src=\"$imasroot/img/html_tiny.png\"/>";
					}
				} 
				$qrows[$row[0]] .= '</td>';
				$qrows[$row[0]] .= "<td><input type=text size=4 name=\"points{$row[0]}\" value=\"{$row[2]}\" /></td>";
				$qrows[$row[0]] .= "<td><input type=text size=4 name=\"attempts{$row[0]}\" value=\"{$row[3]}\" /></td>";
				$qrows[$row[0]] .= "<td><select name=\"showhints{$row[0]}\">";
				$qrows[$row[0]] .= '<option value="0" '.(($row[4]==0)?'selected="selected"':'').'>Use Default</option>';
				$qrows[$row[0]] .= '<option value="1" '.(($row[4]==1)?'selected="selected"':'').'>No</option>';
				$qrows[$row[0]] .= '<option value="2" '.(($row[4]==2)?'selected="selected"':'').'>Yes</option></select></td>';
				$qrows[$row[0]] .= "<td><input type=text size=4 name=\"copies{$row[0]}\" value=\"0\" /></td>";
				$qrows[$row[0]] .= '</tr>';
			} 
			echo "<th>Description</th><th></th><th>Points</th><th>Attempts (0 for unlimited)</th><th>Show hints &amp; video buttons?</th><th>Additional Copies to Add</th></tr></thead>";
			echo "<tbody>";

			$query = "SELECT itemorder FROM imas_assessments WHERE id='$aid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemorder = explode(',', mysql_result($result,0,0));
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
			echo '<input type=hidden name="qids" value="'.implode(',',$qids).'" />';
			echo '<input type=hidden name="mod" value="true" />';
			
			echo '<div class="submit"><input type="submit" value="'._('Save Settings').'"></div>';
			
		} else { //adding new questions
			echo "<th>Description</th><th></th><th>Points</th><th>Attempts (0 for unlimited)</th><th>Show hints &amp; video buttons?</th><th>Number of Copies to Add</th></tr></thead>";
			echo "<tbody>";
			
			$query = "SELECT id,description,extref,qtype,control FROM imas_questionset WHERE id IN ('".implode("','",$_POST['nchecked'])."')";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if ($row[3]=='multipart') {
					preg_match('/anstypes\s*=(.*)/',$row[4],$match);
					$n = substr_count($match[1],',')+1;
				} else {
					$n = 1;
				}
				echo '<tr><td>'.$row[1].'</td>';
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
						echo "<td><img src=\"$imasroot/img/video_tiny.png\"/></td>";
					}
					if ($hasother) {
						echo "<td><img src=\"$imasroot/img/html_tiny.png\"/></td>";
					}
				} else {
					echo '<td></td>';
				}
				echo "<td><input type=text size=4 name=\"points{$row[0]}\" value=\"\" />";
				echo '<input type="hidden" name="qparts'.$row[0].'" value="'.$n.'"/></td>';
				echo "<td><input type=text size=4 name=\"attempts{$row[0]}\" value=\"\" /></td>";
				echo "<td><select name=\"showhints{$row[0]}\">";
				echo '<option value="0" selected="selected">Use Default</option>';
				echo '<option value="1">No</option>';
				echo '<option value="2">Yes</option></select></td>';
				echo "<td><input type=text size=4 name=\"copies{$row[0]}\" value=\"1\" /></td>";
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<input type=hidden name="qsetids" value="'.implode(',',$_POST['nchecked']).'" />';
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
