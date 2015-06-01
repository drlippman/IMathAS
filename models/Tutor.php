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
} 