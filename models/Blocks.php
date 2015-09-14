<?php

namespace app\models;


use app\models\_base\BaseImasCourses;

class Blocks extends BaseImasCourses
{
    public static function getById($Id)
    {
        return static::findAll(['id' => $Id]);
    }
} 