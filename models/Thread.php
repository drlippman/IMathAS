<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 30/5/15
 * Time: 1:05 PM
 */

namespace app\models;


use app\models\_base\BaseImasForumThreads;
use app\models\_base\BaseImasInstrFiles;

class Thread extends BaseImasForumThreads
{


    public static function getById($id)
    {
        return static::findOne(['id' => $id]);
    }
} 