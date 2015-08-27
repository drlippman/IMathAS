<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 15/5/15
 * Time: 5:58 PM
 */

namespace app\models;


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
        $exception = Exceptions::getByAssessmentIdAndUserId($userId,$assessmentId);
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
        if($exception){
        $exception->delete();
        }
    }

    public static function getTotalData($userId)
    {
        $query = \Yii::$app->db->createCommand("SELECT items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore FROM imas_exceptions AS ex,imas_items as items,imas_assessments as i_a WHERE ex.userid='$userId' AND ex.assessmentid=i_a.id AND (items.typeid=i_a.id AND items.itemtype='Assessment')")->queryOne();
        return $query;
    }
    public static function findExceptions($courseId){
        $query = new Query();
        $query	->select(['imas_exceptions.assessmentid', 'imas_exceptions.userid', 'imas_exceptions.enddate', 'imas_exceptions.islatepass'])
            ->from('imas_exceptions')
            ->join(	'INNER JOIN',
                'imas_assessments',
                'imas_exceptions.assessmentid = imas_assessments.id'
            )
            ->where(['imas_assessments.courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function deleteByAssessmentIdAndUid($aidlist, $stulist){
        $query = Exceptions::find()->where(['IN', 'assessmentid', $aidlist])->andWhere(['IN', 'userid', $stulist])->all();
        if($query){
            foreach($query as $exception){
                $exception->delete();
            }
        }
    }

    public static function getByUserIdForTreeReader($userId)
    {
        $query = \Yii::$app->db->createCommand("SELECT items.id,ex.startdate,ex.enddate,ex.islatepass FROM imas_exceptions AS ex,imas_items as items,imas_assessments as i_a WHERE ex.userid='$userId' AND ex.assessmentid=i_a.id AND (items.typeid=i_a.id AND items.itemtype='Assessment')")->queryAll();
        return $query;
    }
} 