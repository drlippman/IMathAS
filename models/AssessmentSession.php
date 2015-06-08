<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 9/5/15
 * Time: 6:28 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasAssessmentSessions;

class AssessmentSession extends BaseImasAssessmentSessions
{
    public static function getByAssessmentSessionId($id)
    {
        return AssessmentSession::findAll(['assessmentid' => $id]);
    }

    public static function createSessionForAssessment($params)
    {
        $params['starttime'] = '0';
        $assessmentSession = new AssessmentSession();
        $assessmentSession->attributes = $params;
        $assessmentSession->save();
    }
    public static function getById($id)
    {
        return AssessmentSession::findOne($id);
    }

    public static function getAssessmentSession($userId, $aid)
    {
        return AssessmentSession::findOne(['userid' => $userId, 'assessmentid' => $aid]);
    }

    public static function getByUserId($uid)
    {
        return AssessmentSession::findOne(['userid' => $uid]);
    }

    public function saveAssessmentSession($assessment, $userId)
    {
        list($qlist, $seedlist, $reviewseedlist, $scorelist, $attemptslist, $lalist) = AppUtility::generateAssessmentData($assessment->itemorder, $assessment->shuffle, $assessment->id);

        $bestscorelist = $scorelist . ';' . $scorelist . ';' . $scorelist;
        $scorelist = $scorelist . ';' . $scorelist;
        $bestattemptslist = $attemptslist;
        $bestseedslist = $seedlist;
        $bestlalist = $lalist;
        $starttime = time();
        $deffeedbacktext = addslashes($assessment->deffeedbacktext);
        $ltisourcedid = '';
        $param['questions'] = $qlist;
        $param['seeds'] = $seedlist;
        $param['userid'] = $userId;
        $param['assessmentid'] = $assessment->id;
        $param['attempts'] = $attemptslist;
        $param['reviewattempts'] = $attemptslist;
        $param['lastanswers'] = $lalist;
        $param['reviewlastanswers'] = $lalist;
        $param['reviewscores'] = $scorelist;
        $param['reviewseeds'] = $reviewseedlist;
        $param['bestscores'] = $bestscorelist;
        $param['scores'] = $scorelist;
        $param['bestattempts'] = $bestattemptslist;
        $param['bestseeds'] = $bestseedslist;
        $param['bestlastanswers'] = $bestlalist;
        $param['starttime'] = time();
        $param['feedback'] = $deffeedbacktext;
        $param['lti_sourcedid'] = $ltisourcedid;

        $this->attributes = $param;
        $this->save();
        return self::getById($this->id);
    }
    public static function getStudentByAssessments($assessmentId)
    {
        return AssessmentSession::find()->where(['assessmentid' => $assessmentId ])->andWhere(['NOT LIKE', 'scores', -1 ])->all();
    }
} 