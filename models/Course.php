<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 5:54 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasCourses;

class Course extends BaseImasCourses {

    public function create($param)
    {
        $this->attributes = $param;
        $this->save();
    }

    public static function getByIdAndEnrollmentKey($id, $enroll)
    {
       return static::findOne(['id' =>$id, 'enrollkey' => $enroll]);
    }

    public static function getByCourseName($name)
    {
        return static::findAll(['name' => $name]);
    }

    public static function getById($cid)
    {
        return static::findOne(['id' => $cid]);
    }
} 