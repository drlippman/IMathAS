<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 6:30 PM
 */

namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\models\_base\BaseImasTeachers;
use yii\db\Query;

class Teacher extends BaseImasTeachers
{
    public static function getByUserId($userid, $courseid)
    {
        return static::findOne( ['userid' => $userid,'courseid' => $courseid]);
    }

    public function create($userid, $courseid)
    {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->save();
        return $this->id;
    }

    public static function getAllTeachers($cid)
    {
        return static::find()->where(['courseid' => $cid])->asArray()->all();
    }

    public static function removeTeacher($userid, $courseid)
    {
        $teacher = static::findOne(['courseid' => $courseid, 'userid' => $userid]);
        $teacher->delete();
    }

    public static function getTeacherByUserId($userid)
    {
        return static::findAll( ['userid' => $userid]);
    }

    public static function getTeachersById($cid)
    {
        return static::find()->where(['courseid' => $cid])->all();
    }

    public static function getUniqueByUserId($userid)
    {
        return static::findOne( ['userid' => $userid]);
    }
    public static function getUserIdByJoin($courseId)
    {
        $query = new Query();
        $query ->select('imas_users.id')->from(['imas_teachers','imas_users'])->where(['imas_teachers.courseid' => $courseId])
                ->andWhere(['imas_teachers.userid=imas_users.id'])->all();
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function getDataForUtilities($courseId,$user)
    {
        $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
        $query .= "FROM imas_teachers,imas_users WHERE imas_teachers.courseid='$courseId' AND imas_teachers.userid=imas_users.id ";
        $query .= "AND imas_users.id<>'$user->id'";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getByCourseId($params)
    {
        $query = new Query();
        $query	->select(['id'])
            ->from('imas_teachers')
            ->where(['courseid' => $params['id']]);
        $query->andWhere(['userid' => $params['newowner']]);
        $command = $query->createCommand();
        $data = $command->queryone();
        return $data;
    }
    public function insertUidAndCid($params)
    {
        $this->userid = $params['id'];
        $this->courseid = $params['courseId'];
        $this->save();
        return $this->id;
    }

    public static function deleteCidAndUid($params, $userId)
    {
        $deleteId = Teacher::find()->where(['courseid' => $params['id']])->andWhere(['userid' => $userId])->one();
        if($deleteId){
            $deleteId->delete();
        }
    }
}