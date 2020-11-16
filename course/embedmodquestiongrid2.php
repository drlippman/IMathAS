<?php
//IMathAS:  Modify a question's settings in an assessment, new assess format
// grid for multiple.  For embed
//(c) 2020 David Lippman

    require('../init.php');

	if (!(isset($teacherid))) {
		echo "You are not authorized to view this page";
		exit;
    }
    $aid = intval($_GET['aid']);

	if (isset($_POST['action'])) {
		require_once("../includes/updateptsposs.php");
		if ($_POST['action'] == 'add') { //adding new questions
			$stm = $DBH->prepare("SELECT itemorder,viddata,defpoints FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			list($itemorder, $viddata, $defpoints) = $stm->fetch(PDO::FETCH_NUM);

			$newitemorder = '';
			if (isset($_POST['addasgroup'])) {
				$newitemorder = '1|0';
			}
			if (isset($_POST['addasgroup'])) {
				$points = trim($_POST['points'.$_POST['firstqsetid']]);
			}
			foreach (explode(',',$_POST['qsetids']) as $k=>$qsetid) {
				for ($i=0; $i<$_POST['copies'.$qsetid];$i++) {
					if (!isset($_POST['addasgroup'])) {
						$points = trim($_POST['points'.$qsetid]);
					}
					$attempts = trim($_POST['attempts'.$qsetid]);
                    $showhints = intval($_POST['showhints'.$qsetid]);
                    $showwork = intval($_POST['showwork'.$qsetid]);
					if ($points=='' || $points==$defpoints) { $points = 9999;}
					if ($attempts=='' || intval($attempts)==0) {$attempts = 9999;}
					if ($points==9999 && isset($_POST['pointsforparts']) && $_POST['qparts'.$qsetid]>1 && !isset($_POST['addasgroup'])) {
						$points = intval($_POST['qparts'.$qsetid]);
					}
					$query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,showwork,penalty,regen,showans,questionsetid) ";
					$query .= "VALUES (:assessmentid, :points, :attempts, :showhints, :showwork, :penalty, :regen, :showans, :questionsetid)";
					$stm = $DBH->prepare($query);
                    $stm->execute(array(':assessmentid'=>$aid, ':points'=>$points, ':attempts'=>$attempts, 
                        ':showhints'=>$showhints, ':showwork'=>$showwork, ':penalty'=>9999, ':regen'=>0, 
                        ':showans'=>0, ':questionsetid'=>$qsetid));
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
				$nextnum = 0;
				if ($itemorder!='') {
					foreach (explode(',', $itemorder) as $iv) {
						if (strpos($iv,'|')!==false) {
							$choose = explode('|', $iv);
							$nextnum += $choose[0];
						} else {
							$nextnum++;
						}
					}
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
				$viddata = serialize($viddata);
			}

			if ($itemorder == '') {
				$itemorder = $newitemorder;
			} else {
				$itemorder .= ','.$newitemorder;
			}
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder,viddata=:viddata WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':viddata'=>$viddata, ':id'=>$aid));

			updatePointsPossible($aid, $itemorder, $defpoints);

		} else if ($_POST['action'] == 'mod') { //modifying existing
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
				$stm = $DBH->query("SELECT id,questionsetid FROM imas_questions WHERE id IN (".implode(',',$lookupid).")"); //sanitized above
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$qidtoqsetid[$row[0]] = $row[1];
				}
			}

			foreach(explode(',',$_POST['qids']) as $qid) {
				$attempts = trim($_POST['attempts'.$qid]);
                $showhints = intval($_POST['showhints'.$qid]);
                $showwork = intval($_POST['showwork'.$qid]);
				if ($points=='') { $points = 9999;}
				if ($attempts=='' || intval($attempts)==0) {$attempts = 9999;}
				$stm = $DBH->prepare("UPDATE imas_questions SET attempts=:attempts,showhints=:showhints,showwork=:showwork WHERE id=:id");
				$stm->execute(array(':attempts'=>$attempts, ':showhints'=>$showhints, ':showwork'=>$showwork, ':id'=>$qid));
				if (intval($_POST['copies'.$qid])>0 && intval($qid)>0) {
					for ($i=0;$i<intval($_POST['copies'.$qid]);$i++) {
						$qsetid = $qidtoqsetid[$qid];
						$query = "INSERT INTO imas_questions (assessmentid,points,attempts,showhints,showwork,penalty,regen,showans,questionsetid) ";
						$query .= "VALUES (:assessmentid, :points, :attempts, :showhints, :showwork, :penalty, :regen, :showans, :questionsetid)";
						$stm = $DBH->prepare($query);
                        $stm->execute(array(':assessmentid'=>$aid, ':points'=>9999, ':attempts'=>$attempts, 
                            ':showhints'=>$showhints, ':showwork'=>$showwork, ':penalty'=>9999, ':regen'=>0, 
                            ':showans'=>0, ':questionsetid'=>$qsetid));
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
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$aid));

			updatePointsPossible($aid, $itemorder, $defpoints);
        }
        
        require('../includes/addquestions2util.php');
        list($jsarr,$existingqs) = getQuestionsAsJSON($cid, $aid);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($jsarr, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_INVALID_UTF8_IGNORE);
        exit;

	} else {
		//get defaults
		$query = "SELECT defpoints,defattempts,showhints,showwork,ver FROM imas_assessments ";
		$query .= "WHERE id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$aid));
        $defaults = $stm->fetch(PDO::FETCH_ASSOC);
        
        if ($defaults['ver'] > 1) {
            $query = "SELECT iar.userid FROM imas_assessment_records AS iar,imas_students WHERE ";
            $query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
        } else {
            $query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
            $query .= "ias.assessmentid=:assessmentid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
        }
        $stm = $DBH->prepare($query);
        $stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
        if ($stm->rowCount() > 0) {
            $beentaken = true;
        } else {
            $beentaken = false;
        }

		if ($defaults['showhints'] == 0) {
      $defaults['showhints'] = _('No');
    } else if ($defaults['showhints'] == 1) {
      $defaults['showhints'] = _('Hints');
    } else if ($defaults['showhints'] == 2) {
      $defaults['showhints'] = _('Video buttons');
    } else if ($defaults['showhints'] == 3) {
      $defaults['showhints'] = _('Hints &amp; Videos');
    }
        $showworkoptions = [
            '-1' => _('Use Default'),
            '0' => _('No'),
            '1' => _('During'),
            '2' => _('After'),
            '3' => _('During &amp; After')
        ];
        $defaults['showwork'] = $showworkoptions[$defaults['showwork']];

		$pagetitle = "Question Settings";
		$placeinhead = '<script type="text/javascript">
			function previewq(qn) {
			  previewpop = window.open(imasroot+"/course/testquestion2.php?cid="+cid+"&qsetid="+qn,"Testing","width="+(.4*screen.width)+",height="+(.8*screen.height)+",scrollbars=1,resizable=1,status=1,top=20,left="+(.6*screen.width-20));
			  previewpop.focus();
			}
			function chgisgrouped() {
				if ($("input[name=addasgroup]").is(":checked")) {
					$("input[name=pointsforparts]").prop("checked", false).prop("disabled", true);
					$("input.ptscol:not(:first)").hide();
				} else {
					$("input[name=pointsforparts]").prop("disabled", false);
					$("input.ptscol").show();
				}
            }
            $(function() {
                $("form").on("submit", function(e) {
                    e.preventDefault();
                    $.ajax({
                        type: "post",
                        url: "embedmodquestiongrid2.php?cid='.$cid.'&aid='.$aid.'",
                        data: $("form").serialize()
                    }).done(function(msg) {
                        window.parent.doneadding(msg);
		                window.parent.GB_hide();
                    });
                });
            });
            </script>';
    $flexwidth = true;
    $nologo = true;        
    require("../header.php");
    
    if ($beentaken) {
        echo '<p>'._('Students have started the assessment, and you cannot change questions or order after students have started; reload the page').'</p>';
        require('../footer.php');
        exit;
    }

?>
<div id="headermodquestiongrid" class="pagetitle"><h1>Modify Question Settings</h1></div>
<p>For more advanced settings, modify the settings for individual questions after adding.</p>
<form>
<p>Leave items blank to use the assessment's default values</p>
<table class=gb>
<thead><tr>
<?php
		if (isset($_GET['modqs'])) { //modifying existing questions
            $modqs = explode('-', $_GET['modqs']);
			$qids = array();
			$qns = array();
			foreach ($modqs as $k=>$v) {
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
			$qidlist = implode(',', array_map('intval', $qids));
			$query = "SELECT imas_questions.id,imas_questionset.description,imas_questions.points,imas_questions.attempts,imas_questions.showhints,imas_questions.showwork,imas_questionset.extref,imas_questionset.id AS qsid ";
			$query .= "FROM imas_questions,imas_questionset WHERE imas_questionset.id=imas_questions.questionsetid AND ";
			$query .= "imas_questions.id IN ($qidlist)";
			$stm = $DBH->query($query);
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
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
						$qrows[$row['id']] .= "<img src=\"$staticroot/img/video_tiny.png\" alt=\"Video\"/>";
					}
					if ($hasother) {
						$qrows[$row['id']] .= "<img src=\"$staticroot/img/html_tiny.png\" alt=\"Help Resource\"/>";
					}
				}
				$qrows[$row['id']] .= '</td>';
				$qrows[$row['id']] .= '<td><button type="button" onclick="previewq('.$row['qsid'].')">'._('Preview').'</button></td>';
				$qrows[$row['id']] .= "<td><input type=text size=3 name=\"attempts{$row['id']}\" value=\"{$row['attempts']}\" /></td>";
				$qrows[$row['id']] .= "<td><select name=\"showhints{$row['id']}\">";
				$qrows[$row['id']] .= '<option value="-1" '.(($row['showhints']==-1)?'selected="selected"':'').'>'._('Use Default').'</option>';
				$qrows[$row['id']] .= '<option value="0" '.(($row['showhints']==0)?'selected="selected"':'').'>'._('No').'</option>';
				$qrows[$row['id']] .= '<option value="1" '.(($row['showhints']==1)?'selected="selected"':'').'>'._('Hints').'</option>';
				$qrows[$row['id']] .= '<option value="2" '.(($row['showhints']==2)?'selected="selected"':'').'>'._('Videos').'</option>';
				$qrows[$row['id']] .= '<option value="3" '.(($row['showhints']==3)?'selected="selected"':'').'>'._('Hints &amp; Videos').'</option>';
                $qrows[$row['id']] .= '</select></td>';
                $qrows[$row['id']] .= "<td><select name=\"showwork{$row['id']}\">";
                foreach ($showworkoptions as $v=>$l) {
                    $qrows[$row['id']] .= '<option value="'.$v.'" '.($row['showwork']==$v ? 'selected':'').'>';
                    $qrows[$row['id']] .= Sanitize::encodeStringForDisplay($l).'</option>';
                }
                $qrows[$row['id']] .= '</select></td>';
				$qrows[$row['id']] .= "<td><input type=text size=1 name=\"copies" . Sanitize::onlyInt($row['id']) . "\" value=\"0\" /></td>";
				$qrows[$row['id']] .= '</tr>';
			}
			echo "<th>Q#<br/>&nbsp;</th><th>Description<br/>&nbsp;</th><th></th><th></th>";
			echo '<th>Tries<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['defattempts']).'</i></th>';
			echo '<th>Show Hints &amp; Videos?<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['showhints']).'</i></th>';
			echo '<th>Show Work?<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['showwork']).'</i></th>';
			echo "<th>Copies to Add<br/>&nbsp;</th></tr></thead>";
			echo "<tbody>";
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
			echo '<input type=hidden name="action" value="mod" />';

			echo '<div class="submit"><input type="submit" value="'._('Save Settings').'"></div>';

        } else { //adding new questions
            $addqs = explode('-', $_GET['toaddqs']);
            
			echo "<th>Description</th><th></th><th></th>";
			echo '<th>Points<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['defpoints']).'</i></th>';
			echo '<th>Tries<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['defattempts']).'</i></th>';
            echo '<th>Show Hints &amp; Videos?<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['showhints']).'</i></th>';
			echo '<th>Show Work?<br/><i class="grey">Default: '.Sanitize::encodeStringForDisplay($defaults['showwork']).'</i></th>';
			echo "<th>Copies to Add</th></tr></thead>";
			echo "<tbody>";
			$addqs = implode(',', array_map('intval', $addqs));
			$stm = $DBH->query("SELECT id,description,extref,qtype,control FROM imas_questionset WHERE id IN ($addqs)");
			$first = true;
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
						echo "<td><img src=\"$staticroot/img/video_tiny.png\" alt=\"Video\"/></td>";
					}
					if ($hasother) {
						echo "<td><img src=\"$staticroot/img/html_tiny.png\" alt=\"Help Resource\"/></td>";
					}
				} else {
					echo '<td></td>';
				}
				echo '<td><button type="button" onclick="previewq('.Sanitize::encodeStringForJavascript($row[0]).')">'._('Preview').'</button></td>';
				echo "<td><input class=ptscol type=text size=2 name=\"points" . Sanitize::encodeStringForDisplay($row[0]) . "\" value=\"\" />";
				if ($first) {
					echo '<input type=hidden name="firstqsetid" value="'.Sanitize::onlyInt($row[0]).'" />';
					$first = false;
				}
				echo '<input type="hidden" name="qparts'.Sanitize::encodeStringForDisplay($row[0]).'" value="'.Sanitize::onlyInt($n).'"/></td>';
				echo "<td><input type=text size=3 name=\"attempts" . Sanitize::encodeStringForDisplay($row[0]) ."\" value=\"\" /></td>";
				echo "<td><select name=\"showhints" . Sanitize::encodeStringForDisplay($row[0]) . "\">";
				echo '<option value="-1" selected="selected">'._('Use Default').'</option>';
				echo '<option value="0">'._('No').'</option>';
				echo '<option value="1">'._('Hints').'</option>';
				echo '<option value="2">'._('Videos').'</option>';
                echo '<option value="3">'._('Hints &amp; Videos').'</option></select></td>';
                echo "<td><select name=\"showwork" . Sanitize::encodeStringForDisplay($row[0]) . "\">";
                foreach ($showworkoptions as $v=>$l) {
                    echo '<option value="'.$v.'" '.($v==-1 ?'selected':'').'>';
                    echo Sanitize::encodeStringForDisplay($l).'</option>';
                }
                echo '</select></td>';
				echo "<td><input type=text size=1 name=\"copies" . Sanitize::encodeStringForDisplay($row[0]) . "\" value=\"1\" /></td>";
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '<input type=hidden name="qsetids" value="'.Sanitize::encodeStringForDisplay($addqs).'" />';
			echo '<input type=hidden name="action" value="add" />';

			echo '<p><input type=checkbox name="addasgroup" value="1" onclick="chgisgrouped()"/> Add as a question group?</p>';
			echo '<p><input type=checkbox name="pointsforparts" value="1" /> Set the points equal to the number of parts for multipart?</p>';
			echo '<div class="submit"><input type="submit" value="'._('Add Questions').'"></div>';
		}
		echo '</form>';
		require("../footer.php");
		exit;
	}
?>
