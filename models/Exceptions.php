<?php
namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasExceptions;
use yii\db\Query;

class Exceptions extends BaseImasExceptions
{
    public static function getByAssessmentId($assessmentId)
    {
        return Exceptions::findAll(['assessmentid' => $assessmentId]);
    }

    public function create($param)
    {
        $this->attributes = $param;
        $this->save();
    }

    public static function getByAssessmentIdAndUserId($userId, $assessmentId)
    {
        return static::findOne(['assessmentid' => $assessmentId, 'userid' => $userId]);
    }

    public static function modifyExistingException($userId, $assessmentId, $startdate, $enddate, $waivereqscore)
    {
        $exception = Exceptions::getByAssessmentIdAndUserId($userId, $assessmentId);
        $exception->startdate = $startdate;
        $exception->enddate = $enddate;
        $exception->waivereqscore = $waivereqscore;
        $exception->save();
    }

    public static function getById($id)
    {
        return static::findOne(['id' => $id]);
    }

    public static function deleteExceptionById($id)
    {
        $exception = Exceptions::getById($id);
        if ($exception) {
            $exception->delete();
        }
    }

    public static function findExceptions($courseId)
    {
        $query = new Query();
        $query->select(['imas_exceptions.assessmentid', 'imas_exceptions.userid', 'imas_exceptions.enddate', 'imas_exceptions.islatepass'])
            ->from('imas_exceptions')
            ->join('INNER JOIN',
                'imas_assessments',
                'imas_exceptions.assessmentid = imas_assessments.id'
            )
            ->where('imas_assessments.courseid = :courseId');
        $command = $query->createCommand()->bindValue('courseId', $courseId);
        $data = $command->queryAll();
        return $data;
    }

    public static function deleteByAssessmentIdAndUid($aidlist, $stulist)
    {
        $query = Exceptions::find()->where(['IN', 'assessmentid', $aidlist])->andWhere(['IN', 'userid', $stulist])->all();
        if ($query) {
            foreach ($query as $exception) {
                $exception->delete();
            }
        }
    }

    public static function getByUserIdForTreeReader($userId)
    {
        return self::find()->select('items.id,ex.startdate,ex.enddate,ex.islatepass')->from('imas_exceptions AS ex,imas_items as items,imas_assessments as i_a')
            ->where('ex.userid= :userId')->andWhere('ex.assessmentid=i_a.id')->andWhere('items.typeid = i_a.id' or ['items.itemtype' => 'Assessment'])->all();
    }

    public static function deleteByUserId($userId)
    {
        $exceptions = Exceptions::find()->where(['userid' => $userId])->all();
        foreach ($exceptions as $exception) {
            $exception->delete();
        }
    }

    public static function deleteByAssessmentId($id)
    {
        $assessmentData = Exceptions::findOne(['assessmentid', $id]);
        if ($assessmentData) {
            $assessmentData->delete();
        }
    }

    public static function getByUIdAndAssId($userId,$aid)
    {
        return self::find()->select(['enddate', 'islatepass'])->where(['userid' => $userId, 'assessmentid' => $aid])->one();
    }

    public static function deleteByUserIdAndAssId($userId, $aid)
    {
        $exceptions = Exceptions::find()->where(['userid' => $userId, 'assessmentid' => $aid])->one();
        if($exceptions) {
            $exceptions->delete();
        }
    }

    public static function updateData($n, $newend, $userid, $aid)
    {
        $exceptions = self::find()->where(['userid' => $userid])->andWhere(['assessmentid' => $aid])->all();
        if($exceptions)
        {
            foreach($exceptions as $exception)
            {
                $exception->islatepass = $exception->islatepass - $n;
                $exception->enddate = $newend;
                $exception->save();
            }
        }
    }

    public static function getEndDateById($userId, $aid)
    {
        return Exceptions::find()->select('enddate')->where(['userid' => $userId, 'assessmentid' => $aid])->one();
    }

    public static function updateIsLatePass($addtime,$userid, $aid)
    {
        $exceptions = self::find()->where(['userid' => $userid])->andWhere(['assessmentid' => $aid])->all();
        if($exceptions)
        {
            foreach($exceptions as $exception)
            {
                $exception->islatepass = $exception->islatepass + 1;
                $exception->enddate = $exception->enddate + $addtime;
                $exception->save();
            }
        }
    }

    public function insertByUserData($userId, $assessmentId, $startdate, $enddate)
    {
        $this->userid = $userId;
        $this->assessmentid = $assessmentId;
        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->islatepass = 1;
        $this->save();
    }

    public static function getExceptionDataLatePass($userId)
    {
        $query = new Query();
        $query->select('items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore, items.typeid')
            ->from('imas_exceptions AS ex,imas_items as items,imas_assessments as i_a')->where('ex.userid = :userId')
            ->andWhere('ex.assessmentid = i_a.id')->andWhere('items.typeid = i_a.id' and ['items.itemtype' => 'Assessment']);
        $data = $query->createCommand();
        $data->bindValue('userId',$userId);
        return $data->queryAll();
    }

    public static function getStartDateEndDate($userId, $assessmentId)
    {
        return Exceptions::find()->select('startdate,enddate')->where(['assessmentid' => $assessmentId, 'userid' => $userId])->one();
    }

    public static function updateException($userId, $exceptionId, $startdate, $enddate, $waivereqscore)
    {
        $exception = Exceptions::find()->where(['id' => $exceptionId])->one();
        $exception->startdate = $startdate;
        $exception->enddate = $enddate;
        $exception->waivereqscore = $waivereqscore;
        $exception->islatepass = AppConstant::NUMERIC_ZERO;
        $exception->save();
    }

    public static function getItemData($userId)
    {
        return self::find()->select('items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore')->from('imas_exceptions AS ex,imas_items as items,imas_assessments as i_a')
            ->where(['ex.userid' => $userId])->andWhere('ex.assessmentid = i_a.id')->andWhere('items.typeid = i_a.id' and ['items.itemtype' => 'Assessment'])->all();
    }
}
