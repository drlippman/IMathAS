<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 4/8/15
 * Time: 8:41 PM
 */
namespace app\components;
use app\models\Assessments;
use app\models\Questions;
use Yii;
use yii\base\Component;
class UpdateAssessUtility extends Component
{
    //utility code for removing withdrawn questions from assessments
//and replacing questions where replaceby exists.

//aidarr is array of assessment IDs, or course ID to do all
     public static function updateassess($aidarr,$removewithdrawn,$doreplaceby) {
        //need to look up which assessments have withdrawn questions
        //and/or replaceable questions
        //for replaceable questions, look up replacement id
        //pull itemorders, remove withdrawn or replace ids, update itemorder

        if (!$removewithdrawn && !$doreplaceby) { return 'No changes reqested';}

        if (is_array($aidarr)) {
            foreach ($aidarr as $k=>$v) {
                $aidarr[$k] = intval($v);
            }
        }

        if ($doreplaceby) {
            Questions::updateQuestionSetId($aidarr);
        }
        if ($removewithdrawn) {
            $query = Questions::FindAssessmentAndWithdrawn($aidarr);
            $todoaid = array();
            $withdrawn = array();
            if($query){
                foreach($query as $result){
                    $todoaid[] = $result['assessmentid'];
                    if($result['withdrawn'] > 0){
                        $withdrawn[$result['id']] = true;
                    }
                }
            }

            if (count($todoaid)==0) { return 'No changes to make';}

            $todoaid = array_unique($todoaid);
            $query = Assessments::selectItemOrder($todoaid);
            if($query){
                foreach($query as $assessment){
                    $items = explode(',',$assessment['itemorder']);
                    foreach ($items as $k=>$q) {
                        if (strpos($q,'~')!==false) {
                            $sub = explode('~',$q);
                            $newsub = array();
                            $front = 0;
                            if (strpos($sub[0],'|')!==false) {
                                $newsub[] = array_shift($sub);
                                $front = 1;
                            }
                            foreach ($sub as $sq) {
                                if (!isset($withdrawn[$sq])) {
                                    $newsub[] = $sq;
                                }
                            }
                            if (count($newsub)==$front) {

                            } else if (count($newsub)==$front+1) {
                                $newitems[] = $newsub[$front];
                            } else {
                                $newitems[] = implode('~',$newsub);
                            }
                        } else {
                            if (!isset($withdrawn[$q])) {
                                $newitems[] = $q;
                            }
                        }
                    }
                    $newitemlist = implode(',', $newitems);
                    Assessments::UpdateItemOrder($newitemlist, $assessment['id']);
                }
            }
        }

        $msg = '';
        if ($removewithdrawn) {
            if (count($withdrawn)>0) {
                $msg .= 'Removed '.count($withdrawn).' withdrawn questions. ';
            } else {
                $msg .= 'No withdrawn questions to remove. ';
            }
        }
//        if ($doreplaceby) {
//            if ($replacedcnt>0) {
//                $msg .= 'Updated '.$replacedcnt.' questions. ';
//            } else {
//                $msg .= 'No questions to update. ';
//            }
//        }
        return $msg;
    }
}