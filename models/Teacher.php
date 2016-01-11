<?php

namespace app\models;

use Yii;
use app\models\_base\BaseImasTeachers;
use yii\db\Query;

class Teacher extends BaseImasTeachers
{
    public static function getByUserId($userid, $courseid)
    {
        return static::findOne(['userid' => $userid, 'courseid' => $courseid]);
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
        $query = new Query();
        $query->select('ic.id,ic.name')->from('imas_courses AS ic')->join('INNER JOIN','imas_teachers','imas_teachers.courseid=ic.id')
            ->where('imas_teachers.userid = :userid')->orderBy('ic.name');
        return $query->createCommand()->bindValue('userid',$userid)->queryAll();
    }

    public static function getTeachersById($cid)
    {
        return static::find()->where(['courseid' => $cid])->all();
    }

    public static function getUniqueByUserId($userid)
    {
        return static::findOne(['userid' => $userid]);
    }

    public static function getUserIdByJoin($courseId)
    {
        $query = new Query();
        $query->select('imas_users.id')->from(['imas_teachers', 'imas_users'])->where('imas_teachers.courseid : :courseId')
            ->andWhere(['imas_teachers.userid=imas_users.id'])->all();
        $command = $query->createCommand();
        $data = $command->bindValue(':courseId',$courseId)->queryAll();
        return $data;
    }

    public static function getDataForUtilities($courseId, $user)
    {
        $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
        $query .= "FROM imas_teachers,imas_users WHERE imas_teachers.courseid=':courseId' AND imas_teachers.userid=imas_users.id ";
        $query .= "AND imas_users.id<>':userId'";
        return Yii::$app->db->createCommand($query)->bindValue(':userId',$user->id)->bindValue(':courseId',$courseId)->queryAll();
    }

    public static function getByCourseId($params)
    {
        return Teacher::find()->select('id')
            ->where(['courseid' => $params['id']])->andWhere(['userid' => $params['newowner']])->one();
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
        if ($deleteId) {
            $deleteId->delete();
        }
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = Teacher::findOne(['courseid', $courseId]);
        if ($courseData) {
            $courseData->delete();
        }
    }

    public static function deleteUser($userId)
    {
        $userId = Teacher::findOne(['userid', $userId]);
        if ($userId) {
            $userId->delete();
        }
    }

    public static function getDataByUserId($userId)
    {
        $query = new Query();
        $query->select('ic.id,ic.name')
            ->from('imas_courses AS ic')
            ->join('INNER JOIN','imas_teachers AS it','ic.id=it.courseid')
            ->where('it.userid = :userid')->orderBy('ic.name');
        $command = $query->createCommand();
        return $command->bindValue(':userid',$userId)->queryAll();
    }

    public function getId($courseId,$userId){
        return self::find()->select('id')->where(['courseid' => $courseId, 'userid' => $userId])->all();
    }
    public function selectByCourseId($userId){
        return self::find()->select('courseid')->where(['userid' => $userId])->all();
    }
}