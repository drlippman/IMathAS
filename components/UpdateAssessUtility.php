<?php
namespace app\components;

use app\models\Assessments;
use app\models\Questions;
use Yii;
use yii\base\Component;

class UpdateAssessUtility extends Component
{
    public static function updateassess($aidarr, $removewithdrawn, $doreplaceby)
    {
        if (!$removewithdrawn && !$doreplaceby) {
            return 'No changes reqested';
        }
        if (is_array($aidarr)) {
            foreach ($aidarr as $k => $v) {
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
            if ($query) {
                foreach ($query as $result) {
                    $todoaid[] = $result['assessmentid'];
                    if ($result['withdrawn'] > AppConstant::NUMERIC_ZERO) {
                        $withdrawn[$result['id']] = true;
                    }
                }
            }

            if (count($todoaid) == AppConstant::NUMERIC_ZERO) {
                return 'No changes to make';
            }

            $todoaid = array_unique($todoaid);
            $query = Assessments::selectItemOrder($todoaid);
            if ($query) {
                foreach ($query as $assessment) {
                    $items = explode(',', $assessment['itemorder']);
                    foreach ($items as $k => $q) {
                        if (strpos($q, '~') !== false) {
                            $sub = explode('~', $q);
                            $newsub = array();
                            $front = AppConstant::NUMERIC_ZERO;
                            if (strpos($sub[0], '|') !== false) {
                                $newsub[] = array_shift($sub);
                                $front = AppConstant::NUMERIC_ONE;
                            }
                            foreach ($sub as $sq) {
                                if (!isset($withdrawn[$sq])) {
                                    $newsub[] = $sq;
                                }
                            }
                            if (count($newsub) == $front) {

                            } else if (count($newsub) == $front + AppConstant::NUMERIC_ONE) {
                                $newitems[] = $newsub[$front];
                            } else {
                                $newitems[] = implode('~', $newsub);
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
            if (count($withdrawn) > AppConstant::NUMERIC_ZERO) {
                $msg .= 'Removed ' . count($withdrawn) . ' withdrawn questions. ';
            } else {
                $msg .= 'No withdrawn questions to remove. ';
            }
        }
        return $msg;
    }
}