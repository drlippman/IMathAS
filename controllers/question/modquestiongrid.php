 <?php
 use app\models\Questions;
 use app\models\Assessments;
 use app\components\AppConstant;
 use app\components\AppUtility;
 use app\models\QuestionSet;
 use app\controllers\AppController;
//IMathAS:  Modify a question's settings in an assessment: grid for multiple.  Included in addquestions.php
//(c) 2006 David Lippman
// $teacherId = $this->isTeacher($userId,$courseId);
	if (!(isset($teacherId))) {
		echo "This page cannot be accessed directly";
		exit;
	}
	if ($_GET['process']== true) {
		if (isset($_POST['add'])) { //adding new questions
			$query = Assessments::getByAssessmentId($aid);
			$itemorder = $query['itemorder'];
			$viddata = $query['viddata'];
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
                    $question = new Questions();
                    $questionArray = array();
                    $questionArray['assessmentid'] = $aid;
                    $questionArray['points'] = $points;
                    $questionArray['attempts'] = $attempts;
                    $questionArray['showhints'] = $showhints;
                    $questionArray['penalty'] = AppConstant::QUARTER_NINE;
                    $questionArray['regen'] = AppConstant::NUMERIC_ZERO;
                    $questionArray['showans'] = AppConstant::NUMERIC_ZERO;
                    $questionArray['questionsetid'] = $qsetid;
                    $qid = $question->addQuestions($questionArray);
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
            Assessments::setVidData($itemorder, $viddata, $aid);
		} else if (isset($_POST['mod'])) { //modifying existing
			
			$query = Assessments::getByAssessmentId($aid);
			$itemorder = $query['itemorder'];
			
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
				$query = Questions::getByIdList($lookupid);
				foreach ($query as $row) {
					$qidtoqsetid[$row['id']] = $row['questionsetid'];
				}
			}

			foreach(explode(',',$_POST['qids']) as $qid) {
				$points = trim($_POST['points'.$qid]);
				$attempts = trim($_POST['attempts'.$qid]);
				$showhints = intval($_POST['showhints'.$qid]);
				if ($points=='') { $points = 9999;}
				if ($attempts=='') {$attempts = 9999;}
                $tempArray = array();
                $tempArray['points'] = $points;
                $tempArray['attempts'] = $attempts;
                $tempArray['showhints'] = $showhints;
				Questions::updateQuestionFields($tempArray, $qid);
                $addQuestions = array();
				if (intval($_POST['copies'.$qid])>0 && intval($qid)>0) {
					for ($i=0;$i<intval($_POST['copies'.$qid]);$i++) {
						$qsetid = $qidtoqsetid[$qid];
                        $addQuestions['assessmentid'] = $aid;
                        $addQuestions['points'] = $points;
                        $addQuestions['attempts'] = $attempts;
                        $addQuestions['showhints'] = $showhints;
                        $addQuestions['penalty'] = '9999';
                        $addQuestions['regen'] = AppConstant::NUMERIC_ZERO;
                        $addQuestions['showans'] = AppConstant::NUMERIC_ZERO;
                        $addQuestions['questionsetid'] = $qsetid;
                        $question = new Questions();
                        $newqid = $question->addQuestions($addQuestions);

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
			Assessments::setItemOrder($itemorder, $aid);
		}
	} else {
		$pagetitle = "Question Settings";
//		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
//		echo "&gt; <a href=\"addquestions.php?aid=$aid&cid=$cid\">Add/Remove Questions</a> &gt; ";
		
//		echo "Question Settings</div>\n";
	
?>
<div id="headermodquestiongrid" class="pagetitle"><h2>Modify Question Settings</h2></div>
<p>For more advanced settings, modify the settings for individual questions after adding.
<?php
if (isset($_POST['checked'])) { //modifying existing
	echo "<form method=post action=\"add-questions?modqs=true&process=true&cid=$cid&aid=$aid\">";
} else {
	echo "<form method=post action=\"add-questions?addset=true&process=true&cid=$cid&aid=$aid\">";
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
            $query = Questions::retrieveQuestionData($qids);

			foreach ($query as $row) {
				if ($row['points']==9999) {
					$row['points'] = '';
				} 
				if ($row['attempts']==9999) {
					$row['attempts'] = '';
				}
				
				$qrows[$row['id']] = '<tr><td>'.$row['description'].'</td>';
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
						$qrows[$row['id']] .= "<img src=\"$imasroot/img/video_tiny.png\"/>";
					}
					if ($hasother) {
						$qrows[$row['id']] .= "<img src=\"$imasroot/img/html_tiny.png\"/>";
					}
				} 
				$qrows[$row['id']] .= '</td>';
				$qrows[$row['id']] .= "<td><input type=text size=4 name=\"points{$row['id']}\" value=\"{$row['points']}\" /></td>";
				$qrows[$row['id']] .= "<td><input type=text size=4 name=\"attempts{$row['id']}\" value=\"{$row['attempts']}\" /></td>";
				$qrows[$row['id']] .= "<td><select name=\"showhints{$row['id']}\">";
				$qrows[$row['id']] .= '<option value="0" '.(($row[4]==0)?'selected="selected"':'').'>Use Default</option>';
				$qrows[$row['id']] .= '<option value="1" '.(($row[4]==1)?'selected="selected"':'').'>No</option>';
				$qrows[$row['id']] .= '<option value="2" '.(($row[4]==2)?'selected="selected"':'').'>Yes</option></select></td>';
				$qrows[$row['id']] .= "<td><input type=text size=4 name=\"copies{$row['id']}\" value=\"0\" /></td>";
				$qrows[$row['id']] .= '</tr>';
			} 
			echo "<th>Description</th><th></th><th>Points</th><th>Attempts (0 for unlimited)</th><th>Show hints &amp; video buttons?</th><th>Additional Copies to Add</th></tr></thead>";
			echo "<tbody>";

			$query = Assessments::getByAssessmentId($aid);
			$itemorder = explode(',', $query['itemorder']);
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
			$query = QuestionSet::getQuestionSetData($_POST['nchecked']);
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			foreach ($query as $row) {
				if ($row['qtype']=='multipart') {
					preg_match('/anstypes\s*=(.*)/',$row['control'],$match);
					$n = substr_count($match[1],',')+1;
				} else {
					$n = 1;
				}
				echo '<tr><td>'.$row['description'].'</td>';
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
						echo "<td><img src=\"$imasroot/img/video_tiny.png\"/></td>";
					}
					if ($hasother) {
						echo "<td><img src=\"$imasroot/img/html_tiny.png\"/></td>";
					}
				} else {
					echo '<td></td>';
				}
				echo "<td><input type=text size=4 name=\"points{$row['id']}\" value=\"\" />";
				echo '<input type="hidden" name="qparts'.$row['id'].'" value="'.$n.'"/></td>';
				echo "<td><input type=text size=4 name=\"attempts{$row['id']}\" value=\"\" /></td>";
				echo "<td><select name=\"showhints{$row['id']}\">";
				echo '<option value="0" selected="selected">Use Default</option>';
				echo '<option value="1">No</option>';
				echo '<option value="2">Yes</option></select></td>';
				echo "<td><input type=text size=4 name=\"copies{$row['id']}\" value=\"1\" /></td>";
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
//		require("../footer.php");
		exit;
	}
?>
