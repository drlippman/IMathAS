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
			$stm = $DBH->prepare("SELECT itemorder,viddata,defpoints,ver FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
            list($itemorder, $viddata, $defpoints, $aver) = $stm->fetch(PDO::FETCH_NUM);
            if (!isset($_POST['lastitemhash']) || $_POST['lastitemhash'] !== md5($itemorder)) {
                header('Content-Type: application/json; charset=utf-8');
                echo '{"error": "Assessment content has changed since last loaded. Reload the page and try again"}';
                exit;
            }

			$newitemorder = '';
            $points = '';
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
                    $showhints = !empty($_POST['showhintsusedef'.$qsetid]) ? -1 : (
                        (empty($_POST['showhints1'.$qsetid]) ? 0 : 1) +
                        (empty($_POST['showhints2'.$qsetid]) ? 0 : 2) +
                        (empty($_POST['showhints4'.$qsetid]) ? 0 : 4)
                    );
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
			$stm = $DBH->prepare("SELECT itemorder,defpoints,ver,intro FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			list($itemorder, $defpoints, $aver, $intro) = $stm->fetch(PDO::FETCH_NUM);
            if (!isset($_POST['lastitemhash']) || $_POST['lastitemhash'] !== md5($itemorder)) {
                header('Content-Type: application/json; charset=utf-8');
                echo '{"error": "Assessment content has changed since last loaded. Reload the page and try again"}';
                exit;
            }
			$jsonintro = json_decode($intro,true);

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
                $showhints = !empty($_POST['showhintsusedef'.$qid]) ? -1 : (
                    (empty($_POST['showhints1'.$qid]) ? 0 : 1) +
                    (empty($_POST['showhints2'.$qid]) ? 0 : 2) +
                    (empty($_POST['showhints4'.$qid]) ? 0 : 4)
                );
                $showwork = intval($_POST['showwork'.$qid]);
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
					if ($jsonintro!==null) { //is json intro
						$toadd = intval($_POST['copies'.$qid]);
						for ($j = 1; $j < count($jsonintro); $j++) {
							if ($jsonintro[$j]['displayBefore']>$key) {
								$jsonintro[$j]['displayBefore'] += $toadd;
								$jsonintro[$j]['displayUntil'] += $toadd;
							}
						}
					}
				}
				if ($jsonintro !== null) {
					$intro = json_encode($jsonintro);
				}
			}
			$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder,intro=:intro WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':intro'=>$intro, ':id'=>$aid));

			updatePointsPossible($aid, $itemorder, $defpoints);
        }

        // Delete any teacher or tutor attempts on this assessment
        $query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
            imas_teachers AS usr ON usr.userid=iar.userid AND usr.courseid=?
            WHERE iar.assessmentid=?';
        $stm = $DBH->prepare($query);
        $stm->execute(array($cid, $aid));
        $query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
            imas_tutors AS usr ON usr.userid=iar.userid AND usr.courseid=?
            WHERE iar.assessmentid=?';
        $stm = $DBH->prepare($query);
        $stm->execute(array($cid, $aid));
        
        require('../includes/addquestions2util.php');
        list($jsarr,$existingqs) = getQuestionsAsJSON($cid, $aid);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['itemarray'=>$jsarr, 'lastitemhash'=>md5($itemorder)], 
            JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_INVALID_UTF8_IGNORE);
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
        } else {
            $ht = [];
            if ($defaults['showhints']&1) {
                $ht[] = _('Hints');
            } 
            if ($defaults['showhints']&2) {
                $ht[] = _('Videos');
            } 
            if ($defaults['showhints']&4) {
                $ht[] = _('Examples');
            } 
            $defaults['showhints'] = implode(' &amp; ', $ht);
        }
        $showworkoptions = [
            '-1' => _('Use Default'),
            '0' => _('No'),
            '1' => _('During'),
            '2' => _('After'),
            '3' => _('During &amp; After')
        ];
        $defaults['showwork'] = $showworkoptions[$defaults['showwork'] & 3];

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
                        if (msg.hasOwnProperty("error")) {
                            alert(msg.error);
                            return;
                        }
                        window.parent.doneadding(msg);
		                window.parent.GB_hide();
                    });
                });
                $(".showhintsdef").on("change", function() {
                    $(this).closest("td").find("span").toggle(!this.checked);
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
<input type=hidden name="lastitemhash" value="<?php echo Sanitize::encodeStringForDisplay($_GET['lih']);?>" />
<p>Leave items blank to use the assessment's default values</p>
<table class=gb>
<thead><tr>
<?php
		if (isset($_GET['modqs'])) { //modifying existing questions
            $modqs = explode(';', $_GET['modqs']);
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

				$qrows[$row['id']] = '<tr><td>'.Sanitize::encodeStringForDisplay($qns[$row['id']]).'</td><td>'.Sanitize::encodeStringForDisplay($row['description']).'</td>';
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

                $qrows[$row['id']] .= '<td><label><input type=checkbox class=showhintsdef name="showhintsusedef'.$row['id'].'" value=1 '. 
                                        ($row['showhints']==-1 ? 'checked':'').'> '._('Use Default').'</label>';
                $qrows[$row['id']] .= '<span '. ($row['showhints']==-1 ? 'style="display:none"':'').'>';
                $qrows[$row['id']] .=   '<br><label>&nbsp; <input type=checkbox name="showhints1'.$row['id'].'" value=1 '. 
                                        ($row['showhints']&1 ? 'checked':'').'> '._('Hints').'</label>'; 
                $qrows[$row['id']] .=   '<br><label>&nbsp; <input type=checkbox name="showhints2'.$row['id'].'" value=2 '. 
                                        ($row['showhints']&2 ? 'checked':'').'> '._('Videos/Text').'</label>'; 
                $qrows[$row['id']] .=   '<br><label>&nbsp; <input type=checkbox name="showhints4'.$row['id'].'" value=4 '. 
                                        ($row['showhints']&4 ? 'checked':'').'> '._('Written Examples').'</label></span></td>'; 

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
            $addqs = explode(';', $_GET['toaddqs']);
            
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
					if ($hasvid) {
						echo "<td><img src=\"$staticroot/img/video_tiny.png\" alt=\"Video\"/></td>";
					}
					if ($hasother) {
						echo "<td><img src=\"$staticroot/img/html_tiny.png\" alt=\"Help Resource\"/></td>";
					}
				} else {
					echo '<td></td>';
				}
                $qsid = Sanitize::encodeStringForDisplay($row[0]);
				echo '<td><button type="button" onclick="previewq('.Sanitize::encodeStringForJavascript($row[0]).')">'._('Preview').'</button></td>';
				echo "<td><input class=ptscol type=text size=2 name=\"points" . $qsid . "\" value=\"\" />";
				if ($first) {
					echo '<input type=hidden name="firstqsetid" value="'.Sanitize::onlyInt($row[0]).'" />';
					$first = false;
				}
				echo '<input type="hidden" name="qparts'.$qsid.'" value="'.Sanitize::onlyInt($n).'"/></td>';
				echo "<td><input type=text size=3 name=\"attempts" . $qsid ."\" value=\"\" /></td>";
                echo '<td><label><input type=checkbox class=showhintsdef name="showhintsusedef'.$qsid.'" value=1 checked> '._('Use Default').'</label>';
                echo '<span style="display:none>';
                echo '<br><label><input type=checkbox name="showhints1'.$qsid.'" value=1> '._('Hints').'</label>'; 
                echo '<br><label><input type=checkbox name="showhints2'.$qsid.'" value=2> '._('Videos').'</label>'; 
                echo '<br><label><input type=checkbox name="showhints4'.$qsid.'" value=4> '._('Written Examples').'</label></span></td>'; 

                echo "<td><select name=\"showwork" . $qsid . "\">";
                foreach ($showworkoptions as $v=>$l) {
                    echo '<option value="'.$v.'" '.($v==-1 ?'selected':'').'>';
                    echo Sanitize::encodeStringForDisplay($l).'</option>';
                }
                echo '</select></td>';
				echo "<td><input type=text size=1 name=\"copies" . $qsid . "\" value=\"1\" /></td>";
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
