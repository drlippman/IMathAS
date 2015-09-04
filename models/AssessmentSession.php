<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 9/5/15
 * Time: 6:28 PM
 */

namespace app\models;


use app\components\AppConstant;
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
        return AssessmentSession::findOne(['id' => $id]);
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
        $assessmentData = AssessmentSession::getByAssessmentId($assessmentId);
        if($assessmentData){
            foreach ($assessmentData as $singleAssessment){
                $singleAssessment->delete();
            }
        }
    }
    public static function deleteSessionByAssessmentId($aidlist, $stulist){
        $query = AssessmentSession::find()->where(['IN', 'assessmentid', $aidlist])->andWhere(['IN', 'userid', $stulist])->all();
        if($query){
            foreach($query as $assessmentSession){
                $assessmentSession->delete();
            }
        }
    }
    public static function getSessionDataForUnenroll($searchWhere){
        $query = \Yii::$app->db->createCommand("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchWhere")->queryAll();
        return $query;
    }
    public static function getSessionInfoForUnenroll($searchNot, $lookForStr){
        $query = \Yii::$app->db->createCommand("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchNot AND ($lookForStr)")->queryAll();
        return $query;
    }

    public static function getByAssessmentId($assessmentId){
        $assessment =  AssessmentSession::findAll(['assessmentid' => $assessmentId]);
        if($assessment){
            return $assessment;
        }
    }

    public static function setBestScore($bestScore, $id){
        $assessmentSessionData = AssessmentSession::getById($id);
        if ($assessmentSessionData){
            $assessmentSessionData->bestscores = $bestScore;
            $assessmentSessionData->save();
        }
    }

    public static function getByAssessmentSessionIdJoin($assessmentId,$courseId)
    {
        $query = new Query();
        $query->select(['imas_assessment_sessions.id'])->from('imas_assessment_sessions')->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where(['imas_assessment_sessions.assessmentid' => $assessmentId, 'imas_students.courseid' => $courseId])->limit(1);
        $command = $query->createCommand();
        $items = $command->queryAll();
        return $items;
    }

    public static function updateAssSessionForGrp($grpId)
    {
        $query = AssessmentSession::find()->where(['agroupid' => $grpId])->all();
        if($query)
        {
            foreach($query as $data)
            {
                $data->agroupid = AppConstant::NUMERIC_ZERO;
                $data->save();
            }
        }
    }

    public static function getByIdAndUserId($assessmentId,$userId,$isteacher,$istutor)
    {
        $query = new Query();
        $query->select(['imas_assessments.name'])->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_assessments', 'imas_assessments.id=imas_assessment_sessions.assessmentid')
            ->where(['imas_assessment_sessions.id' => $assessmentId]);
            if (!$isteacher && !$istutor) {
                $query->andWhere(['imas_assessment_sessions.userid' => $userId]);
            }

        $command = $query->createCommand();
        $items = $command->queryOne();
        return $items;
    }

    public static function getByAssessmentIdAndCourseId($assessmentId,$courseId){
        $query = new Query();
        $query->select(['imas_assessment_sessions.assessmentid','imas_assessment_sessions.lti_sourcedid'])
            ->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_assessments', 'imas_assessment_sessions.assessmentid = imas_assessments.id ')
            ->where(['imas_assessment_sessions.id' => $assessmentId, 'imas_assessments.courseid' => $courseId]);
        $command = $query->createCommand();
        $items = $command->queryOne();
        return $items;
     }

    public static function deleteByAssessment($data)
    {
/*
 *         $data[0] value change by condition it will be either 'id' or 'groupid'
 */
        $assessment = AssessmentSession::find()->where([$data[0] => $data[1]])->andWhere(['assessmentid'=> $data[2]])->one();
        if($assessment)
        {
            $assessment->delete();
        }
    }

    public static function getDataForGroups($fieldsToCopy,$grpId,$data)
    {
        $query = new Query();
        $query->select([$fieldsToCopy])
            ->from('imas_assessment_sessions')
            ->where(['agroupid' => $grpId])
            ->andWhere(['assessmentid' => $data]);
        $command = $query->createCommand();
        $items = $command->queryAll();
        return $items;
    }
    public static function getAssessmentIDs($assessmentId,$courseId){
        $query = new Query();
        $query->select(['imas_assessment_sessions.id'])->from('imas_assessment_sessions')->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where(['imas_assessment_sessions.assessmentid' => $assessmentId, 'imas_students.courseid' => $courseId]);
        $command = $query->createCommand();
        $items = $command->queryAll();
        return $items;
    }

    public static function getIdForGroups($stuList,$data,$fieldsToCopy)
    {
        $query = "SELECT id,$fieldsToCopy ";
        $query .= "FROM imas_assessment_sessions WHERE userid IN ($stuList) AND assessmentid='{$data}'";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function dataForFileHandling($searchnot,$lookforstr)
    {
        $query = "SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)";
        return \Yii::$app->db->createCommand($query)->queryAll();

    }

    public static function getAGroupId($stuId,$data)
    {
        $query = new Query();
        $query->select(['id','agroupid'])
            ->from('imas_assessment_sessions')
            ->where(['userid' => $stuId])
            ->andWhere(['assessmentid' => $data]);
        $command = $query->createCommand();
        $items = $command->queryAll();
        return $items;
    }

    public static function updateAssessmentForStuGrp($id,$setsList)
    {
        $query = "UPDATE imas_assessment_sessions SET $setsList WHERE id='{$id}'";
        \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function insertDataOfGroup($fieldsToCopy,$stuId,$insRow)
    {
       $query = "INSERT INTO imas_assessment_sessions (userid,$fieldsToCopy) ";
        $query .= "VALUES ('$stuId',$insRow)";
    }

    public static function updateAssSessionForGrpByGrpIdAndUid($uid,$grpId)
    {
        $query = AssessmentSession::find()->where(['agroupid' => $grpId])->andWhere(['userid' => $uid])->all();
        if($query)
        {
            foreach($query as $data)
            {
                $data->agroupid = AppConstant::NUMERIC_ZERO;
                $data->save();
            }

        }
    }

    public static function getByAssessmentUsingStudentJoin($courseId,$assessmentId,$secfilter)
    {
        $query = new Query();
        $query	->select(['imas_assessment_sessions.questions','imas_assessment_sessions.bestscores','imas_assessment_sessions.bestattempts','imas_assessment_sessions.bestlastanswers','imas_assessment_sessions.starttime','imas_assessment_sessions.endtime','imas_assessment_sessions.timeontask'])
            ->from('imas_assessment_sessions')
            ->join(	'INNER JOIN',
                'imas_students',
                'imas_assessment_sessions.userid=imas_students.userid'
            )
            ->where(['imas_students.courseid' => $courseId])
            ->andWhere(['imas_assessment_sessions.assessmentid' => $assessmentId])
            ->andWhere(['imas_students.locked' => '0']);
        if($secfilter != -1){
            $query->andWhere(['imas_students.section' => $secfilter]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourseIdAndAssessmentId($assessmentId,$courseId)
    {
        $query = new Query();
        $query->select(['*'])->from('imas_assessment_sessions')->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where(['imas_assessment_sessions.assessmentid' => $assessmentId, 'imas_students.courseid' => $courseId]);
        $command = $query->createCommand();
        $items = $command->queryAll();
        return $items;
    }

    public static function deleteByUserId($userId)
    {
        $assessmentSessions = AssessmentSession::find()->where(['userid' => $userId])->all();
        foreach($assessmentSessions as $assessmentSession)
        {
            $assessmentSession->delete();
        }
    }
    public static function getDataToUpdateQuestionUsageData($lastUpdate)
    {
        $query = new Query();
        $query->select(['questions','timeontask'])
            ->from('imas_assessment_sessions')
            ->where(['<>','timeontask',''])
            ->andWhere(['>','endtime',$lastUpdate]);
        $command = $query->createCommand();
        $items = $command->queryAll();
        return $items;
    }
}


