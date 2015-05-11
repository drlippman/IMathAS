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
        $teacher = static::findone(['courseid' => $courseid, 'userid' => $userid]);
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
}