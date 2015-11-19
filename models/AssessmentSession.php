<?php
namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasAssessmentSessions;
use yii\db\Query;
use Yii;

class AssessmentSession extends BaseImasAssessmentSessions
{
    public static function getByAssessmentSessionId($id)
    {
        return AssessmentSession::findAll(['assessmentid' => $id]);
    }

    public function createSessionForAssessment($params)
    {
        $data = AppUtility::removeEmptyAttributes($params);
        if($data){
            $this->attributes = $data;
            $this->save();
            return $this->id;
        }
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
        return AssessmentSession::find()->where(['assessmentid' => $assessmentId])->andWhere(['NOT LIKE', 'scores', AppConstant::NUMERIC_NEGATIVE_ONE])->all();
    }

    public static function removeByUserIdAndAssessmentId($userId, $assessmentId)
    {
        $session = AssessmentSession::getAssessmentSession($userId, $assessmentId);
        if ($session) {
            $session->delete();
        }
    }

    public static function modifyExistingSession($params)
    {
        $session = AssessmentSession::getById($params['id']);
        If ($session) {
            $session->scores = $params['scores'];
            $session->attempts = $params['attempts'];
            $session->seeds = $params['seeds'];
            $session->lastanswers = $params['lastanswers'];
            $session->reattempting = $params['reattempting'];
            $session->save();
        }
    }

    public static function getByUserCourseAssessmentId($assessmentId, $courseId)
    {
        $query = new Query();
        $query->select('imas_assessment_sessions.id,count(*)')->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where('imas_assessment_sessions.assessmentid = :assessmentId')->andWhere('imas_students.courseid = :courseId');
        $command = $query->createCommand()->bindValues(['assessmentId' => $assessmentId, 'courseId' => $courseId]);
        $items = $command->queryAll();
        return $items;
    }

    public static function findAssessmentsSession($courseId, $limuser)
    {
        $query = new Query();
        $query->select(['imas_assessment_sessions.id', 'imas_assessment_sessions.assessmentid', 'imas_assessment_sessions.bestscores', 'imas_assessment_sessions.starttime', 'imas_assessment_sessions.endtime', 'imas_assessment_sessions.timeontask', 'imas_assessment_sessions.feedback', 'imas_assessment_sessions.userid', 'imas_assessments.timelimit'])
            ->from('imas_assessments')
            ->join('INNER JOIN',
                'imas_assessment_sessions',
                'imas_assessments.id = imas_assessment_sessions.assessmentid'
            )
            ->where('imas_assessments.courseid = :courseId');
        $limuser > AppConstant::NUMERIC_ZERO ? $query->andWhere('imas_assessment_sessions.userid = :limuser' ) : $query->andWhere(':limuser = :limuser');
        $command = $query->createCommand()->bindValues([':courseId' => $courseId,':limuser' => $limuser]);
        $data = $command->queryAll();
        return $data;
    }

    public static function findAssessmentForOutcomes($courseId, $limuser)
    {
        $query = new Query();
        $query->select(['imas_assessment_sessions.id', 'imas_assessment_sessions.assessmentid', 'imas_assessment_sessions.questions', 'imas_assessment_sessions.bestscores', 'imas_assessment_sessions.starttime', 'imas_assessment_sessions.endtime', 'imas_assessment_sessions.timeontask', 'imas_assessment_sessions.feedback', 'imas_assessment_sessions.userid', 'imas_assessments.timelimit'])
            ->from('imas_assessments')
            ->join('INNER JOIN',
                'imas_assessment_sessions',
                'imas_assessments.id = imas_assessment_sessions.assessmentid'
            )
            ->where('imas_assessments.courseid = :courseId');
        ($limuser > AppConstant::NUMERIC_ZERO) ? $query->andWhere('imas_assessment_sessions.userid = :limuser') : $query->andWhere(':limuser = :limuser');
        $command = $query->createCommand()->bindValues([':courseId' => $courseId,':limuser' => $limuser]);
        $data = $command->queryAll();
        return $data;
    }

    public static function setGroupId($assessmentId)
    {
        $assessment = self::findOne(['assessmentid' => $assessmentId]);
        $assessment->agroupid = AppConstant::NUMERIC_ZERO;
        $assessment->save();
    }

    public static function deleteByAssessmentId($assessmentId)
    {
        $assessmentData = AssessmentSession::getByAssessmentId($assessmentId);
        if ($assessmentData) {
            foreach ($assessmentData as $singleAssessment) {
                $singleAssessment->delete();
            }
        }
    }

    public static function deleteSessionByAssessmentId($aidlist, $stulist)
    {
        $query = AssessmentSession::find()->where(['IN', 'assessmentid', $aidlist])->andWhere(['IN', 'userid', $stulist])->all();
        if ($query) {
            foreach ($query as $assessmentSession) {
                $assessmentSession->delete();
            }
        }
    }

    public static function getSessionDataForUnenroll($val,$tosearchby,$aid,$lim)
    {
        $query = new Query();
        $query->select('lastanswers,bestlastanswers,reviewlastanswers')->from('imas_assessment_sessions');
        if (is_array($val))
        {
            $query->where(['NOT IN',$tosearchby,$val]);
        } else
        {
            $query->where(['<>',$tosearchby, $val ]);
        }
        if ($aid != null)
        {
            if (is_array($aid))
            {
                $query->andWhere(['IN','assessmentid',$aid]);
            } else {
                $query->andWhere('assessmentid =:assessmentid',[':assessmentid' => $aid]);
            }
        }
        if ($lim > 0) {
            $query->limit($lim);
        }
        return $query->createCommand()->queryAll();
    }

    public static function getSessionInfoForUnenroll($val,$tosearchby,$aid,$lim, $todel)
    {
        $query = new Query();
        $query->select('lastanswers,bestlastanswers,reviewlastanswers')->from('imas_assessment_sessions');
        if (is_array($val))
        {
            $query->where(['NOT IN',$tosearchby,$val]);
        } else
        {
            $query->where($tosearchby <> ':val',[':val' => $val]);
        }
        if ($aid != null)
        {
            if (is_array($aid))
            {
                $query->andWhere(['IN','assessmentid',$aid]);
            } else {
                $query->andWhere('assessmentid = :assessmentid',[':assessmentid' => $aid]);
            }
        }
        foreach ($todel as $file)
        {
            $query->andWhere(['LIKE','lastanswers',$file] or ['LIKE','bestlastanswers',$file] or ['LIKE','reviewlastanswers',$file]);
        }
        if ($lim > 0) {
            $query->limit($lim);
        }
        return $query->createCommand()->queryAll();
    }

    public static function getByAssessmentId($assessmentId)
    {
        $assessment = AssessmentSession::findAll(['assessmentid' => $assessmentId]);
        if ($assessment) {
            return $assessment;
        }
    }

    public static function setBestScore($bestScore, $id)
    {
        $assessmentSessionData = AssessmentSession::getById($id);
        if ($assessmentSessionData) {
            $assessmentSessionData->bestscores = $bestScore;
            $assessmentSessionData->save();
        }
    }

    public static function getByAssessmentSessionIdJoin($assessmentId, $courseId)
    {
        $query = new Query();
        $query->select(['imas_assessment_sessions.id'])->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where('imas_assessment_sessions.assessmentid = :assessmentId')->andWhere('imas_students.courseid = :courseId')
            ->limit(AppConstant::NUMERIC_ONE);
        $command = $query->createCommand();
        $items = $command->bindValues([':assessmentId' => $assessmentId, ':courseId' => $courseId])->queryAll();
        return $items;
    }

    public static function updateAssSessionForGrp($grpId)
    {
        $query = AssessmentSession::find()->where(['agroupid' => $grpId])->all();
        if ($query) {
            foreach ($query as $data) {
                $data->agroupid = AppConstant::NUMERIC_ZERO;
                $data->save();
            }
        }
    }

    public static function getByIdAndUserId($assessmentId, $userId, $isteacher, $istutor)
    {
        $query = new Query();
        $query->select(['imas_assessments.name'])->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_assessments', 'imas_assessments.id=imas_assessment_sessions.assessmentid')
            ->where('imas_assessment_sessions.id = :assessmentId');
        (!$isteacher && !$istutor) ? $query->andWhere('imas_assessment_sessions.userid = :userId') : $query->andWhere(':userId = :userId');
        $command = $query->createCommand()->bindValues([':assessmentId' => $assessmentId,':userId' => $userId]);
        $items = $command->queryOne();
        return $items;
    }

    public static function getByAssessmentIdAndCourseId($assessmentId, $courseId)
    {
        $query = new Query();
        $query->select(['imas_assessment_sessions.assessmentid', 'imas_assessment_sessions.lti_sourcedid'])
            ->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_assessments', 'imas_assessment_sessions.assessmentid = imas_assessments.id ')
            ->where(['imas_assessment_sessions.id=:assessmentId', 'imas_assessments.courseid=:courseId']);
        $command = $query->createCommand()->bindValues(['assessmentId' => $assessmentId, 'courseId' => $courseId]);
        $items = $command->queryOne();
        return $items;
    }

    public static function deleteByAssessment($data)
    {
        /*
         *         $data[0] value change by condition it will be either 'id' or 'groupid'
         */
        $assessment = AssessmentSession::find()->where([$data[0] => $data[1]])->andWhere(['assessmentid' => $data[2]])->one();
        if ($assessment) {
            $assessment->delete();
        }
    }

    public static function getDataForGroups($fieldsToCopy, $grpId, $data)
    {
        return self::find()->select([$fieldsToCopy])->where(['agroupid' => $grpId, 'assessmentid' => $data])->all();
    }

    public static function getAssessmentIDs($assessmentId, $courseId)
    {
        $query = new Query();
        $query->select('imas_assessment_sessions.id')
            ->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where('imas_assessment_sessions.assessmentid =:assessmentId')->andWhere('imas_students.courseid =:courseId');
        $command = $query->createCommand()->bindValues([':assessmentId' => $assessmentId, ':courseId' => $courseId]);
        $items = $command->queryAll();
        return $items;
    }

    public static function getIdForGroups($stuList, $data, $fieldsToCopy)
    {
        return self::find()->select(['id',$fieldsToCopy])->where(['assessmentid' => $data])->andWhere('IN','userid',$stuList)->all();
    }

    public static function getAGroupId($stuId, $data)
    {
        return self::find()->select(['id', 'agroupid'])->where(['userid' => $stuId,'assessmentid' => $data])->all();

    }

    public static function updateAssessmentForStuGrp($id, $setsList)
    {
        $query = self::find()->where(['id' => $id])->one();
        $query->attributes = $setsList;
        $query->save();
    }

    public static function updateAssSessionForGrpByGrpIdAndUid($uid, $grpId)
    {
        $query = AssessmentSession::find()->where(['agroupid' => $grpId])->andWhere(['userid' => $uid])->all();
        if ($query) {
            foreach ($query as $data) {
                $data->agroupid = AppConstant::NUMERIC_ZERO;
                $data->save();
            }
        }
    }

    public static function getByAssessmentUsingStudentJoin($courseId, $assessmentId, $secfilter)
    {
        $query = new Query();
        $query->select(['imas_assessment_sessions.questions', 'imas_assessment_sessions.bestscores', 'imas_assessment_sessions.bestattempts', 'imas_assessment_sessions.bestlastanswers', 'imas_assessment_sessions.starttime', 'imas_assessment_sessions.endtime', 'imas_assessment_sessions.timeontask','imas_students.userid'])
            ->from('imas_assessment_sessions')
            ->join('INNER JOIN',
                'imas_students',
                'imas_assessment_sessions.userid=imas_students.userid'
            )
            ->where(['imas_students.courseid' => $courseId])
            ->andWhere('imas_assessment_sessions.assessmentid=:assessmentId')
            ->andWhere(['imas_students.locked' => AppConstant::ZERO_VALUE]);
        $secfilter != AppConstant::NUMERIC_NEGATIVE_ONE ? $query->andWhere('imas_students.section = :secfilter') : $query->andWhere(':secfilter = :secfilter');
        $command = $query->createCommand()->bindValues([':assessmentId' => $assessmentId,':secfilter' => $secfilter]);
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourseIdAndAssessmentId($assessmentId, $courseId)
    {
        $query = new Query();
        $query->select(['*'])->from('imas_assessment_sessions')
            ->join('INNER JOIN', 'imas_students', 'imas_assessment_sessions.userid = imas_students.userid')
            ->where(['imas_assessment_sessions.assessmentid=:assessmentId', 'imas_students.courseid=:courseId']);
        $command = $query->createCommand()->bindValues(['assessmentId' => $assessmentId, 'courseId' => $courseId]);
        $items = $command->queryAll();
        return $items;
    }

    public static function deleteByUserId($userId)
    {
        $assessmentSessions = AssessmentSession::find()->where(['userid' => $userId])->all();
        if($assessmentSessions)
        {
            foreach ($assessmentSessions as $assessmentSession) {
                $assessmentSession->delete();
            }
        }
    }

    public static function getDataToUpdateQuestionUsageData($lastUpdate)
    {
        return self::find()->select(['questions', 'timeontask'])
            ->where(['<>', 'timeontask', ''])
            ->andWhere(['>', 'endtime', $lastUpdate])->all();
    }

    public static function getDataForUtilities($limitAid)
    {
        $query = new Query();
        $query->select('userid')->from('imas_assessment_sessions AS IAS')
            ->join('NOT LIKE','IAS.scores','%-1%')->andWhere('assessmentid = :limitAid')->all();
        $data = $query->createCommand();
        $data->bindValue('limitAid', $limitAid);
        return $data->queryAll();
    }

    public static function getByIdAndStartTime($userid, $paid)
    {
        return self::find()->select(['id','starttime'])->where(['userid',$userid, 'assessmentid',$paid])->all();
    }

    public static function deleteData($userid,$aid)
    {
        $data = AssessmentSession::find()->where(['userid' => $userid, 'assessmentid' => $aid])->limit(1)->one();
        if($data){
            $data->delete();
        }
    }

    public static function getIdByUserIdAndAid($userid, $aid){
        return AssessmentSession::find()->select('id')->where(['userid' => $userid, 'assessmentid' => $aid])->orderBy('id')->limit(1)->one();
    }

    public static function getAssessmentSessionData($userid, $aid){
        return AssessmentSession::find()->select('id,agroupid,lastanswers,bestlastanswers,starttime')->where(['userid' => $userid, 'assessmentid' => $aid])->orderBy(['id' => AppConstant::DESCENDING])->limit(1)->one();
    }

    public static function setGroupIdById($stdGrpId, $id)
    {
        $assessment = AssessmentSession::findOne(['id' => $id]);
        $assessment->agroupid = $stdGrpId;
        $assessment->save();
    }

    public static function setLtiSourceId($sourceId, $id)
    {
        $assessment = AssessmentSession::findOne(['id' => $id]);
        $assessment->lti_sourcedid = $sourceId;
        $assessment->save();
    }

    public static function setStartTime($time, $id)
    {
        $assessment = AssessmentSession::findOne(['id' => $id]);
        if($assessment){
            $assessment->starttime = $time;
            $assessment->save();
        }
    }

    public static function getAssessmentSessionDataToCopy($fieldstocopy,$testid){
        return AssessmentSession::find()->select($fieldstocopy)->where(['id' => $testid])->all();
    }

    public static function getIdAndAGroupId($userid, $aid){
        return AssessmentSession::find()->select('id, agroupid')->where(['userid' => $userid, 'assessmentid' => $aid])->orderBy('id')->limit(1)->one();
    }

    public static function getFromUser($groupId){
        $query = new Query();
        $query->select('imas_users.id,imas_users.FirstName,imas_users.LastName')
            ->from('imas_users')
            ->join('INNER JOIN',
                'imas_assessment_sessions',
            'imas_users.id=imas_assessment_sessions.userid'
            )
            ->where('imas_assessment_sessions.agroupid= :groupId')->orderBy('imas_users.LastName,imas_users.FirstName');
        $command = $query->createCommand()->bindValue('groupId', $groupId);
        $items = $command->queryAll();
        return $items;
    }

    public static function getAssessmentIDForClearScores($asid,$courseId)
    {
        $query = new Query();
        $query->select(['ias.assessmentid'])
            ->from('imas_assessment_sessions AS ias')
            ->join('INNER JOIN',
                'imas_assessments AS ia',
                'ias.assessmentid=ia.id'
            )
            ->where('ias.id = :asid')
            ->andWhere('ia.courseid = :courseId');
        $command = $query->createCommand()->bindValues(['asid' => $asid, 'courseId' => $courseId]);
        $items = $command->queryOne();
        return $items;
    }

    public static function getltisourcedIdAndSeed($qp)
    {
        return  AssessmentSession::find()->select(['seeds','lti_sourcedid'])->where([$qp[0] => $qp[1]])->andWhere(['assessmentid' => $qp[2]])->one();
    }

    public static function updateForClearScores($qp,$scorelist,$scorelist,$attemptslist,$lalist,$bestscorelist,$bestattemptslist,$bestseedslist,$bestlalist)
    {
        $query = AssessmentSession::find()->where([$qp[0] => $qp[1]])->andWhere(['assessmentid' => $qp[2]])->one();
        if($query)
        {
            $query->scores = $scorelist.';'.$scorelist;
            $query->attempts = $attemptslist;
            $query->lastanswers = $lalist;
            $query->reattempting = '';
            $query->bestscores = $bestscorelist;$bestscorelist;$bestscorelist;
            $query->bestattempts = $bestattemptslist;
            $query->bestseeds = $bestseedslist;
            $query->bestlastanswers = $bestlalist;
            $query->save();
        }

    }

    public static function setBestScoreAndFeedback($bestScore,$feedback,$id)
    {
        $assessmentSessionData = AssessmentSession::getById($id);
        if ($assessmentSessionData) {
            $assessmentSessionData->bestscores = $bestScore;
            $assessmentSessionData->feedback = $feedback;
            $assessmentSessionData->save();
        }
    }

    public static function setBestScoreAndFeedbackUsingGroup($bestScore,$feedback,$qp)
    {
        $assessmentSessionData = AssessmentSession::find()->where([$qp[0] = $qp[1]])->andWhere(['assessmentid' => $qp[2]])->one();
        if ($assessmentSessionData) {
            $assessmentSessionData->bestscores = $bestScore;
            $assessmentSessionData->feedback = $feedback;
            $assessmentSessionData->save();
        }
    }

    public static function getAssessmentIDAndAsidForClearScores($qp)
    {
        return AssessmentSession::find()
            ->select(['attempts','lastanswers','reattempting','scores','bestscores','bestattempts','bestlastanswers','lti_sourcedid'])
            ->where([$qp[0] => $qp[1]])
            ->andWhere(['assessmentid' => $qp[2]])
            ->orderBy('id')->one();
    }

    public static function updateForClearScore($qp,$scorelist,$scorelist,$attemptslist,$lalist,$bestscorelist,$bestattemptslist,$reattemptinglist,$bestlalist)
    {
        $query = AssessmentSession::find()->where([$qp[0] => $qp[1]])->andWhere(['assessmentid' => $qp[2]])->one();
        if($query)
        {
            $query->scores = $scorelist;
            $query->attempts = $attemptslist;
            $query->lastanswers = $lalist;
            $query->reattempting = $reattemptinglist;
            $query->bestscores = $bestscorelist;
            $query->bestattempts = $bestattemptslist;
            $query->bestlastanswers = $bestlalist;
            $query->save();
        }
    }

    public function createSessionForGradebook($uid,$agroupid,$aid,$qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist)
    {
            $this->userid = $uid;
            $this->agroupid = $agroupid;
            $this->assessmentid = $aid;
            $this->questions = $qlist;
            $this->seeds = $seedlist;
            $this->scores = $scorelist;$scorelist;
            $this->attempts = $attemptslist;
            $this->lastanswers = $lalist;
            $this->starttime = AppConstant::NUMERIC_ZERO;
            $this->bestscores = $scorelist;$scorelist;$scorelist;
            $this->bestattempts = $attemptslist;
            $this->bestseeds = $seedlist;
            $this->bestlastanswers = $lalist;
            $this->reviewscores = $scorelist;$scorelist;
            $this->reviewattempts = $attemptslist;
            $this->reviewseeds = $reviewseedlist;
            $this->reviewlastanswers = $lalist;
            $this->save();
            return $this->id;
    }

    public static function getUserForGradebook($aid,$groupId)
    {
        $query = new Query();
        $query->select('i_u.LastName','i_u.FirstName')
            ->from('imas_assessment_sessions AS i_a_s')
            ->join('INNER JOIN',
                'imas_users AS i_u',
                'i_u.id=i_a_s.userid'
            )
            ->where('i_a_s.assessmentid= :aid')
            ->andWhere('i_a_s.agroupid= :groupId')->orderBy('LastName,FirstName');

        $command = $query->createCommand()->bindValues(['aid'=> $aid, 'groupId'=> $groupId]);
        $items = $command->queryAll();
        return $items;
    }

    public static function getAssessmentData($asid)
    {
        $query = new Query();
        $query->select(['imas_assessments.name','imas_assessments.defpoints','imas_assessments.defoutcome','imas_assessment_sessions.* '])
            ->from('imas_assessment_sessions')
            ->join('INNER JOIN',
                'imas_assessments',
                'imas_assessments.id=imas_assessment_sessions.assessmentid'
            )
            ->where('imas_assessment_sessions.id=:asid');
        $command = $query->createCommand()->bindValue('asid', $asid);
        $items = $command->queryOne();
        return $items;
    }

    public static function getAssessmentGroups($aid)
    {
        return AssessmentSession::find()->select(['agroupid','id','userid','bestscores','starttime','endtime','feedback'])
            ->where(['assessmentid' => $aid])->groupBy('agroupid')->all();
    }

    public static function updateStartTime($startTime,$qp)
    {
        $queries = AssessmentSession::find()->where([$qp[0] => $qp[1]])->andWhere(['assessmentid' => $qp[2]])->all();
        if($queries)
        {
            foreach($queries as $query)
            {
                $query->starttime = $startTime;
                $query->save();
            }
        }
    }

    public function insertAssessmentSessionData($sets)
    {
        $this->attributes = $sets;
        $this->save();
    }

    public static function updateAssessmentSessionData($sets, $id)
    {
        $data = AssessmentSession::getById($id);
        if($data)
        {
            $data->attributes = $sets;
            $data->save();
        }
    }

    public static function deleteId($data)
    {
        $data = AssessmentSession::find()->where(['id' => $data])->limit(1)->one();
        if($data){
            $data->delete();
        }
    }

    public static function getBestScore($id, $userId)
    {
        return self::find()->select(['bestscores'])->where(['assessmentid' => $id])->andWhere(['userid' => $userId])->one();
    }
    public static function getDataForGrade($params,$page,$assessmentId)
    {
        $query = new Query();
        $query->select('imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.*')
            ->from('imas_users')
            ->join(
                'INNER JOIN',
                'imas_assessment_sessions',
            'imas_assessment_sessions.userid=imas_users.id'
            )
            ->where('imas_assessment_sessions.assessmentid = :assessmentId')
             ->orderBy('imas_users.LastName')->orderBy('imas_users.FirstName');
        ($page != -1 && isset($params['userid'])) ?  $query->andWhere('userid= :userid') : $query->andWhere(':userid= :userid' );
        $command = $query->createCommand();
        $command->bindValue(':assessmentId',$assessmentId)->bindValue(':userid',$params['userid']);
        $items =  $command->queryAll();
        return $items;
    }

    public static function getDataWithUserData($assessmentId,$courseId)
    {
            $query = new Query();
        $query->select('imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.*')
            ->from('imas_users,imas_assessment_sessions,imas_students')->where('imas_assessment_sessions.userid=imas_users.id')
            ->andWhere('imas_students.userid=imas_users.id')->andWhere('imas_students.courseid=:courseId')
            ->andWhere('imas_assessment_sessions.assessmentid=:assessmentId')
            ->orderBy('imas_users.LastName,imas_users.FirstName');
        return $query->createCommand()->bindValues([':courseId' => $courseId, ':assessmentId' => $assessmentId])->queryAll();
    }

    public static function getDataWithUserDataFilterByPage($aid,$cid,$page)
    {
        $query = new Query();
         $query->select('imas_users.LastName,imas_users.FirstName,imas_assessment_sessions.*')->from('imas_users,imas_assessment_sessions,imas_students')
            ->where('imas_assessment_sessions.userid=imas_users.id')->andWhere('imas_students.userid=imas_users.id')
            ->andWhere('imas_students.courseid = :cid' )->andWhere('imas_assessment_sessions.assessmentid = :aid')
            ->orderBy('imas_users.LastName,imas_users.FirstName');
        if ($page != -1)
        {
            $query->limit(1)->offset($page);
        }
        return $query->createCommand()->bindValues([':cid' => $cid,':aid' => $aid])->queryAll();
    }

    public static function getDataWithUserIdAssessment($userId,$assessmentId)
    {
        return self::find()->select(['seeds,attempts,questions'])->where(['userid' => $userId])->andWhere(['assessmentid' => $assessmentId])->one();

    }
}


