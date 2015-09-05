<?php
use app\models\Questions;
use app\models\Assessments;
use app\components\AppConstant;
use app\components\AppUtility;
use app\models\QuestionSet;
use app\controllers\AppController;
//IMathAS:  Modify a question's settings in an assessment: grid for multiple.  Included in addquestions.php
//(c) 2006 David Lippman
if (!(isset($teacherId))) {
    echo "This page cannot be accessed directly";
    exit;
}
if ($params['process']== true) {
    if (isset($params['add'])) {
        /*
         * adding new questions
         */
        $query = Assessments::getByAssessmentId($assessmentId);
        $itemOrder = $query['itemorder'];
        $vidData = $query['viddata'];
        $newItemOrder = '';
        if (isset($params['addasgroup'])) {
            $newItemOrder = '1|0';
        }
        foreach (explode(',',$params['qsetids']) as $questionSetId) {
            for ($i=0; $i<$params['copies'.$questionSetId];$i++) {
                $points = trim($params['points'.$questionSetId]);
                $attempts = trim($params['attempts'.$questionSetId]);
                $showHints = intval($params['showhints'.$questionSetId]);
                if ($points=='') { $points = 9999;}
                if ($attempts=='') {$attempts = 9999;}
                if ($points==9999 && isset($params['pointsforparts']) && $params['qparts'.$questionSetId]>1) {
                    $points = intval($params['qparts'.$questionSetId]);
                }
                $question = new Questions();
                $questionArray = array();
                $questionArray['assessmentid'] = $assessmentId;
                $questionArray['points'] = $points;
                $questionArray['attempts'] = $attempts;
                $questionArray['showhints'] = $showHints;
                $questionArray['penalty'] = AppConstant::QUARTER_NINE;
                $questionArray['regen'] = AppConstant::NUMERIC_ZERO;
                $questionArray['showans'] = AppConstant::NUMERIC_ZERO;
                $questionArray['questionsetid'] = $questionSetId;
                $qid = $question->addQuestions($questionArray);
                if ($newItemOrder=='') {
                    $newItemOrder = $qid;
                } else {
                    if (isset($params['addasgroup'])) {
                        $newItemOrder = $newItemOrder . "~$qid";
                    } else {
                        $newItemOrder = $newItemOrder . ",$qid";
                    }
                }
            }
        }

        if ($vidData != '') {
            if ($itemOrder=='') {
                $nextNum = 0;
            } else {
                $nextNum = substr_count($itemOrder,',')+1;
            }
            $numNew= substr_count($newItemOrder,',')+1;
            $vidData = unserialize($vidData);
            if (!isset($vidData[count($vidData)-1][1])) {
                $finalSeg = array_pop($vidData);
            } else {
                $finalSeg = '';
            }
            for ($i=$nextNum;$i<$nextNum+$numNew;$i++) {
                $vidData[] = array('','',$i);
            }
            if ($finalSeg != '') {
                $vidData[] = $finalSeg;
            }
            $vidData = addslashes(serialize($vidData));
        }

        if ($itemOrder == '') {
            $itemOrder = $newItemOrder;
        } else {
            $itemOrder .= ','.$newItemOrder;
        }
        Assessments::setVidData($itemOrder, $vidData, $assessmentId);
    } else if (isset($params['mod'])) { //modifying existing

        $query = Assessments::getByAssessmentId($assessmentId);
        $itemOrder = $query['itemorder'];

        //what qsetids do we need for adding copies?
        $lookupId = array();
        foreach(explode(',',$params['qids']) as $qid) {
            if (intval($params['copies'.$qid])>0 && intval($qid)>0) {
                $lookupId[] = intval($qid);
            }
        }
        //lookup qsetids
        $qidToQSetId = array();
        if (count($lookupId)>0) {
            $query = Questions::getByIdList($lookupId);
            foreach ($query as $row) {
                $qidToQSetId[$row['id']] = $row['questionsetid'];
            }
        }

        foreach(explode(',',$params['qids']) as $qid) {
            $points = trim($params['points'.$qid]);
            $attempts = trim($params['attempts'.$qid]);
            $showHints = intval($params['showhints'.$qid]);
            if ($points=='') { $points = 9999;}
            if ($attempts=='') {$attempts = 9999;}
            $tempArray = array();
            $tempArray['points'] = $points;
            $tempArray['attempts'] = $attempts;
            $tempArray['showhints'] = $showHints;
            Questions::updateQuestionFields($tempArray, $qid);
            $addQuestions = array();
            if (intval($params['copies'.$qid])>0 && intval($qid)>0) {
                for ($i=0;$i<intval($params['copies'.$qid]);$i++) {
                    $questionSetId = $qidToQSetId[$qid];
                    $addQuestions['assessmentid'] = $assessmentId;
                    $addQuestions['points'] = $points;
                    $addQuestions['attempts'] = $attempts;
                    $addQuestions['showhints'] = $showHints;
                    $addQuestions['penalty'] = '9999';
                    $addQuestions['regen'] = AppConstant::NUMERIC_ZERO;
                    $addQuestions['showans'] = AppConstant::NUMERIC_ZERO;
                    $addQuestions['questionsetid'] = $questionSetId;
                    $question = new Questions();
                    $newQid = $question->addQuestions($addQuestions);

                    $itemArray = explode(',',$itemOrder);
                    $key = array_search($qid,$itemArray);
                    if ($key===false) {
                        $itemArray[] = $newQid;
                    } else {
                        array_splice($itemArray,$key+1,0,$newQid);
                    }
                    $itemOrder = implode(',',$itemArray);
                }
            }
        }
        Assessments::setItemOrder($itemOrder, $assessmentId);
    }
} else {
?>
<div id="headermodquestiongrid" class="pagetitle"><h2>Modify Question Settings</h2></div>
<p>For more advanced settings, modify the settings for individual questions after adding.
    <?php
    if (isset($params['checked'])) { //modifying existing
        echo "<form method=post action=\"add-questions?modqs=true&process=true&cid=$courseId&aid=$assessmentId\">";
    } else {
        echo "<form method=post action=\"add-questions?addset=true&process=true&cid=$courseId&aid=$assessmentId\">";
    }
    ?>
    Leave items blank to use the assessment's default values<br/>
<table class=gb>
    <thead><tr>
        <?php
        if (isset($params['checked'])) { //modifying existing questions

            $qids = array();
            foreach ($params['checked'] as $k=>$v) {
                $v = explode(':',$v);
                $qids[] = $v[1];
            }
            $qRows = array();
            $query = Questions::retrieveQuestionData($qids);

            foreach ($query as $row) {
                if ($row['points']==9999) {
                    $row['points'] = '';
                }
                if ($row['attempts']==9999) {
                    $row['attempts'] = '';
                }

                $qRows[$row['id']] = '<tr><td>'.$row['description'].'</td>';
                $qRows[$row['id']] .= '<td>';
                if ($row['extref']!='') {
                    $extRef = explode('~~',$row['extref']);
                    $hasVideo = false;  $hasOther = false;
                    foreach ($extRef as $v) {
                        if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false) {
                            $hasVideo = true;
                        } else {
                            $hasOther = true;
                        }
                    }
                    $pageQuestionTable[$i]['extref'] = '';
                    if ($hasVideo) {
                        $qRows[$row['id']] .= "<img src=".AppUtility::getHomeURL().'img/video_tiny.png'.">";
                    }
                    if ($hasOther) {
                        $qRows[$row['id']] .= "<img src=".AppUtility::getHomeURL().'img/html_tiny.png'.">";
                    }
                }
                $qRows[$row['id']] .= '</td>';
                $qRows[$row['id']] .= "<td><input type=text size=4 name=\"points{$row['id']}\" value=\"{$row['points']}\" /></td>";
                $qRows[$row['id']] .= "<td><input type=text size=4 name=\"attempts{$row['id']}\" value=\"{$row['attempts']}\" /></td>";
                $qRows[$row['id']] .= "<td><select name=\"showhints{$row['id']}\">";
                $qRows[$row['id']] .= '<option value="0" '.(($row[4]==0)?'selected="selected"':'').'>Use Default</option>';
                $qRows[$row['id']] .= '<option value="1" '.(($row[4]==1)?'selected="selected"':'').'>No</option>';
                $qRows[$row['id']] .= '<option value="2" '.(($row[4]==2)?'selected="selected"':'').'>Yes</option></select></td>';
                $qRows[$row['id']] .= "<td><input type=text size=4 name=\"copies{$row['id']}\" value=\"0\" /></td>";
                $qRows[$row['id']] .= '</tr>';
            }
            echo "<th>Description</th><th></th><th>Points</th><th>Attempts (0 for unlimited)</th><th>Show hints &amp; video buttons?</th><th>Additional Copies to Add</th></tr></thead>";
            echo "<tbody>";

            $query = Assessments::getByAssessmentId($assessmentId);
            $itemOrder = explode(',', $query['itemorder']);
            foreach ($itemOrder as $item) {
                if (strpos($item,'~')!==false) {
                    $subs = explode('~',$item);
                    if (strpos($subs[0],'|')!==false) {
                        array_shift($subs);
                    }
                    foreach ($subs as $sub) {
                        if (isset($qRows[$sub])) {
                            echo $qRows[$sub];
                        }
                    }
                } else if (isset($qRows[$item])) {
                    echo $qRows[$item];
                }
            }

            echo '</tbody></table>';
            echo '<input type=hidden name="qids" value="'.implode(',',$qids).'" />';
            echo '<input type=hidden name="mod" value="true" />';

            echo '<div class="submit"><input type="submit" value="'._('Save Settings').'"></div>';

        } else { //adding new questions
            echo "<th>Description</th><th></th><th>Points</th><th>Attempts (0 for unlimited)</th><th>Show hints &amp; video buttons?</th><th>Number of Copies to Add</th></tr></thead>";
            echo "<tbody>";
            $query = QuestionSet::getQuestionSetData($params['nchecked']);
            foreach ($query as $row) {
                if ($row['qtype']=='multipart') {
                    preg_match('/anstypes\s*=(.*)/',$row['control'],$match);
                    $n = substr_count($match[1],',')+1;
                } else {
                    $n = 1;
                }
                echo '<tr><td>'.$row['description'].'</td>';
                if ($row['extref']!='') {
                    $extRef = explode('~~',$row['extref']);
                    $hasVideo = false;  $hasOther = false;
                    foreach ($extRef as $v) {
                        if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false) {
                            $hasVideo = true;
                        } else {
                            $hasOther = true;
                        }
                    }
                    $pageQuestionTable[$i]['extref'] = '';
                    if ($hasVideo) {
                        echo "<td><img src=".AppUtility::getHomeURL().'img/video_tiny.png'."></td>";
                    }
                    if ($hasOther) {
                        echo "<td><img src=".AppUtility::getHomeURL().'img/html_tiny.png'."/></td>";
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
            echo '<input type=hidden name="qsetids" value="'.implode(',',$params['nchecked']).'" />';
            echo '<input type=hidden name="add" value="true" />';

            echo '<p><input type=checkbox name="addasgroup" value="1" /> Add as a question group?</p>';
            echo '<p><input type=checkbox name="pointsforparts" value="1" /> Set the points equal to the number of parts for multipart?</p>';
            echo '<div class="submit"><input type="submit" value="'._('Add Questions').'"></div>';
        }
        echo '</form>';
        exit;
        }
        ?>
