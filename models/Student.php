<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 5:54 PM
 */

namespace app\models;


use app\models\_base\BaseImasStudents;

class Student extends BaseImasStudents {

    public function create($param)
    {
        $this->attributes = $param;
        $this->save();
    }

    public static function getByCourseId($courseId, $userId)
    {
        return static::findOne(['courseid' => $courseId, 'userid' => $userId]);
    }

    public static function getByUserId($id)
    {
        return static::findAll(['userid' => $id]);
    }

    public static function getByCId($cId)
    {
        return static::findOne(['courseid' => $cId]);
    }
} 