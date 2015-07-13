<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 8:40 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasTutors;
use yii\db\Query;

class Tutor extends BaseImasTutors
{
    public static function getByUserId($id,$courseid)
    {
        return static::findOne( ['userid' => $id,'courseid' => $courseid]);
    }
    public static function getByCourseId($courseid)
    {
        return static::findAll( ['courseid' => $courseid]);
    }
    public function create($userid,$courseid)
    {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->save();
        return $this->id;
    }
    public static function getById($id)
    {
        return static::findOne( ['userid' => $id]);
    }
    public static function deleteTutorByUserId($userId)
    {
        $tutor = Tutor::getById($userId);
        $tutor->delete();
    }
    public static function updateSection($userid,$courseid,$section)
    {
        $tutor = Tutor::getByUserId($userid,$courseid);
        $tutor->section = $section;
        $tutor->save();
    }
    public static function getByUser($userId)
    {
        return static::findAll(['userid' => $userId]);
    }
    Public static function findTutorsToList($courseId)
    {
        $query = new Query();
        $query	->select(['imas_users.id', 'imas_users.FirstName', 'imas_users.LastName'])
            ->from('imas_tutors')
            ->join(	'INNER JOIN',
                'imas_users',
                'imas_users.id = imas_tutors.userid'
            )
////            if (!$isteacher && $studentinfo['section']!=null) {
////                $query .= "AND (imas_tutors.section='".addslashes($studentinfo['section'])."' OR imas_tutors.section='') ";
////            }
            ->where(['imas_tutors.courseid' => $courseId])
            ->orderBy('imas_users.LastName');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
} 