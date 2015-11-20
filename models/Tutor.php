<?php

namespace app\models;

use app\models\_base\BaseImasTutors;
use yii\db\Query;

class Tutor extends BaseImasTutors
{
    public static function getByUserId($userid, $courseid)
    {
        return static::findOne(['userid' => $userid, 'courseid' => $courseid]);
    }

    public static function getByCourseId($courseid)
    {
        return static::findAll(['courseid' => $courseid]);
    }

    public function create($userid, $courseid)
    {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->save();
        return $this->id;
    }

    public static function getById($id)
    {
        return static::findOne(['userid' => $id]);
    }

    public static function deleteTutorByUserId($userId)
    {
        $tutor = Tutor::getById($userId);
        $tutor->delete();
    }

    public static function updateSection($userid, $courseid, $section)
    {
        $tutor = Tutor::getByUserId($userid, $courseid);
        $tutor->section = $section;
        $tutor->save();
    }

    public static function getByUser($userId)
    {
        return static::findAll(['userid' => $userId]);
    }

    public static function findTutorsToList($courseId,$isteacher=false,$section=false)
    {
        $query = new Query();
        $query->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName'])
            ->from('imas_tutors')
            ->join('INNER JOIN',
                'imas_users',
                'imas_users.id = imas_tutors.userid')
            ->where(':courseId = :courseId');
            if(!$isteacher && $section!=null){
                $query->andWhere(['imas_tutors.section' => $section])->orWhere(['imas_tutors.section' => '']);
            }
        $query->andWhere('imas_tutors.courseid = :courseId');
            $query->orderBy('imas_users.LastName');
        $command = $query->createCommand();
        $data = $command->bindValue(':courseId',$courseId)->queryAll();
        return $data;
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = Tutor::findOne(['courseid', $courseId]);
        if ($courseData) {
            $courseData->delete();
        }
    }

    public static function getDataByUserId($userid)
    {
        $query = new Query();
        $query->select('ic.id,ic.name')->from('imas_courses AS ic')->join('INNER JOIN','imas_tutors AS it','ic.id=it.courseid')
            ->where('it.userid = :userid')->orderBy('ic.name');
        $command = $query->createCommand();
        return $command->bindValue(':userid',$userid)->queryAll();
    }

    public static function getTutorData($userId)
    {
        $query = "SELECT imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.lockaid FROM imas_tutors,imas_courses ";
        $query .= "WHERE imas_tutors.courseid=imas_courses.id AND imas_tutors.userid=':userId'";
        $query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ORDER BY imas_courses.name";
        $command = \Yii::$app->db->createCommand($query)->bindValue('userId', $userId);
        $data = $command->queryAll();
        return $data;
    }
} 