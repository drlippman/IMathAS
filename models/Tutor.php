<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 8:40 PM
 */

namespace app\models;


use app\models\_base\BaseImasTutors;

class Tutor extends BaseImasTutors
{
    public static function getByUserId($id,$courseid)
    {
        return static::findOne( ['userid' => $id,'courseid' => $courseid]);
    }
} 