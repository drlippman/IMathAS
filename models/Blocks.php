<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 2/5/15
 * Time: 2:11 PM
 */

namespace app\models;


use app\models\_base\BaseImasCourses;

class Blocks extends BaseImasCourses
{
    public static function getById($Id)
    {
        return static::findAll(['id' => $Id]);
    }
} 