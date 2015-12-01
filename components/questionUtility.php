<?php
namespace app\components;

use app\models\Questions;
use app\models\Assessments;
use app\components\AppConstant;
use app\components\AppUtility;
use app\models\QuestionSet;
use Yii;
use yii\base\Component;
use app\controllers\AppController;
class  questionUtility extends Component
{

    public static function addQuestion($params, $teacherId,$assessmentId,$courseId)
    {

    if (!(isset($teacherId))) {
        $addQuestionData = "This page cannot be accessed directly";
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
                $questionArray['penalty'] = AppConstant::QUARTER_NINE_STRING;
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
                    $addQuestions['penalty'] = AppConstant::QUARTER_NINE_STRING;
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

$addQuestionData .= '<div id="headermodquestiongrid" class="pagetitle"><h2>Modify Question Settings</h2></div>
<p>For more advanced settings, modify the settings for individual questions after adding.';

    if (isset($params['checked'])) { //modifying existing
        $addQuestionData .= "<form method=post action=\"add-questions?modqs=true&process=true&cid=$courseId&aid=$assessmentId\">";
    } else {
        $addQuestionData .= "<form method=post action=\"add-questions?addset=true&process=true&cid=$courseId&aid=$assessmentId\">";
    }

    $addQuestionData .= "<span class='col-md-12 col-sm-12 padding-left-zero padding-bottom-one-em'>Leave items blank to use the assessment's default values</span>
<table class='table table-striped table-hover data-table dataTable'>
    <thead><tr>";

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
                $qRows[$row['id']] .= "<td><input class='form-control' type=text size=4 name=\"points{$row['id']}\" value=\"{$row['points']}\" /></td>";
                $qRows[$row['id']] .= "<td><input class='form-control' type=text size=4 name=\"attempts{$row['id']}\" value=\"{$row['attempts']}\" /></td>";
                $qRows[$row['id']] .= "<td><select class='form-control' name=\"showhints{$row['id']}\">";
                $qRows[$row['id']] .= '<option value="0" '.(($row[4]==0)?'selected="selected"':'').'>Use Default</option>';
                $qRows[$row['id']] .= '<option value="1" '.(($row[4]==1)?'selected="selected"':'').'>No</option>';
                $qRows[$row['id']] .= '<option value="2" '.(($row[4]==2)?'selected="selected"':'').'>Yes</option></select></td>';
                $qRows[$row['id']] .= "<td><input class='form-control' type=text size=4 name=\"copies{$row['id']}\" value=\"0\" /></td>";
                $qRows[$row['id']] .= '</tr>';
            }
            $addQuestionData .= "<th>Description</th><th></th><th>Points</th><th>Attempts (0 for unlimited)</th><th>Show hints &amp; video buttons?</th><th>Additional Copies to Add</th></tr></thead>";
            $addQuestionData .= "<tbody>";

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
                            $addQuestionData .= $qRows[$sub];
                        }
                    }
                } else if (isset($qRows[$item])) {
                    $addQuestionData .= $qRows[$item];
                }
            }

            $addQuestionData .= '</tbody></table>';
            $addQuestionData .= '<input type=hidden name="qids" value="'.implode(',',$qids).'" />';
            $addQuestionData .= '<input type=hidden name="mod" value="true" />';

            $addQuestionData .= '<div class="col-md-offset-3 col-md-2 col-sm-3 col-sm-offset-3 padding-left-pt-five-em padding-top-one-em">
            <input type="submit" value="'._('Save Settings').'">
            </div>';

        } else { //adding new questions
            $addQuestionData .= "<th>Description</th><th></th><th>Points</th><th>Attempts (0 for unlimited)</th><th>Show hints &amp; video buttons?</th><th>Number of Copies to Add</th></tr></thead>";
            $addQuestionData .= "<tbody>";
            $query = QuestionSet::getQuestionSetData($params['nchecked']);
            foreach ($query as $row) {
                if ($row['qtype']=='multipart') {
                    preg_match('/anstypes\s*=(.*)/',$row['control'],$match);
                    $n = substr_count($match[1],',')+1;
                } else {
                    $n = 1;
                }
                $addQuestionData .= '<tr><td>'.$row['description'].'</td>';
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
                        $addQuestionData .= "<td><img src=".AppUtility::getHomeURL().'img/video_tiny.png'."></td>";
                    }
                    if ($hasOther) {
                        $addQuestionData .= "<td><img src=".AppUtility::getHomeURL().'img/html_tiny.png'."/></td>";
                    }
                } else {
                    $addQuestionData .= '<td></td>';
                }
                $addQuestionData .= "<td><input class='form-control' type=text size=4 name=\"points{$row['id']}\" value=\"\" />";
                $addQuestionData .= '<input type="hidden" name="qparts'.$row['id'].'" value="'.$n.'"/></td>';
                $addQuestionData .= "<td><input class='form-control' type=text size=4 name=\"attempts{$row['id']}\" value=\"\" /></td>";
                $addQuestionData .= "<td><select class='form-control' name=\"showhints{$row['id']}\">";
                $addQuestionData .= '<option value="0" selected="selected">Use Default</option>';
                $addQuestionData .= '<option value="1">No</option>';
                $addQuestionData .= '<option value="2">Yes</option></select></td>';
                $addQuestionData .= "<td><input  class='form-control' type=text size=4 name=\"copies{$row['id']}\" value=\"1\" /></td>";
                $addQuestionData .= '</tr>';
            }
            $addQuestionData .= '</tbody></table>';
            $addQuestionData .= '<input type=hidden name="qsetids" value="'.implode(',',$params['nchecked']).'" />';
            $addQuestionData .= '<input type=hidden name="add" value="true" />';

            $addQuestionData .= '<div class="col-md-offset-3 col-sm-offset-3 col-md-9 col-sm-9 padding-bottom-ten">
            <div class="col-md-12 col-sm-12 padding-left-twenty-five"><input type=checkbox name="addasgroup" value="1" /> Add as a question group?</div>
            </div>';
            $addQuestionData .= '<div class="col-md-offset-3 col-sm-offset-3 col-md-9 col-sm-9 padding-bottom-ten">
            <div class="col-md-12 col-sm-12 padding-left-twenty-five">
            <input type=checkbox name="pointsforparts" value="1" /> Set the points equal to the number of parts for multipart?</div>
            </div>';
            $addQuestionData .= '<div class="col-md-offset-3 col-sm-offset-3 col-md-9 col-sm-9">
            <div class="col-md-3 col-sm-3 padding-left-twenty-five">
            <input type="submit" value="'._('Add Questions').'">
            </div>
            </div>';
        }
        $addQuestionData .= '</form>';
        }

        return $addQuestionData;
    }
}
?>