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
use yii\db\Query;

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
        $deffeedbacktext = ($assessment->deffeedbacktext);
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

    public static function removeByUserIdAndAssessmentId($userId, $assessmentId)
    {
        $session = AssessmentSession::getAssessmentSession($userId, $assessmentId);
        if($session){
            $session->delete();
        }
    }
    public static function modifyExistingSession($params)
    {
        $session = AssessmentSession::getById($params['id']);
        If($session){
            $session->scores = $params['scores'];
            $session->attempts = $params['attempts'];
            $session->seeds = $params['seeds'];
            $session->lastanswers = $params['lastanswers'];
            $session->reattempting = $params['reattempting'];
            $session->save();
        }
    }

    public static function getByUserCourseAssessmentId($assessmentId,$courseId)
    {
        $query = new Query();
        $query->select(['imas_assessment_sessions.id,count(*)'])->from('imas_assessment_sessions')->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where(['imas_assessment_sessions.assessmentid' => $assessmentId, 'imas_students.courseid' => $courseId])->count();
        $command = $query->createCommand();
        $items = $command->queryAll();
        return $items;
    }

    public static function findAssessmentsSession($courseId, $limuser){
        $query = new Query();
        $query	->select(['imas_assessment_sessions.id','imas_assessment_sessions.assessmentid', 'imas_assessment_sessions.bestscores', 'imas_assessment_sessions.starttime', 'imas_assessment_sessions.endtime', 'imas_assessment_sessions.timeontask', 'imas_assessment_sessions.feedback', 'imas_assessment_sessions.userid', 'imas_assessments.timelimit'])
            ->from('imas_assessments')
            ->join(	'INNER JOIN',
                'imas_assessment_sessions',
                'imas_assessments.id = imas_assessment_sessions.assessmentid'
            )
            ->where(['imas_assessments.courseid' => $courseId]);
        if($limuser > 0){
            $query->andWhere(['imas_assessment_sessions.userid' => $limuser]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function findAssessmentForOutcomes($courseId, $limuser)
    {
        $query = new Query();
        $query	->select(['imas_assessment_sessions.id','imas_assessment_sessions.assessmentid','imas_assessment_sessions.questions', 'imas_assessment_sessions.bestscores', 'imas_assessment_sessions.starttime', 'imas_assessment_sessions.endtime', 'imas_assessment_sessions.timeontask', 'imas_assessment_sessions.feedback', 'imas_assessment_sessions.userid', 'imas_assessments.timelimit'])
            ->from('imas_assessments')
            ->join(	'INNER JOIN',
                'imas_assessment_sessions',
                'imas_assessments.id = imas_assessment_sessions.assessmentid'
            )
            ->where(['imas_assessments.courseid' => $courseId]);
        if($limuser > 0){
            $query->andWhere(['imas_assessment_sessions.userid' => $limuser]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function setGroupId($assessmentId){
        $assessment = AssessmentSession::findOne(['assessmentid' => $assessmentId]);
        $assessment->agroupid = 0;
        $assessment->save();
    }

    public static function deleteByAssessmentId($assessmentId){
        $assessmentData = AssessmentSession::findAll(['assessmentid' => $assessmentId]);
        if($assessmentData){
            foreach ($assessmentData as $singleAssessment){
                $singleAssessment->delete();
            }
        }
    }
} 