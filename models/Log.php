<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 10/9/15
 * Time: 1:37 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasLog;
use yii\db\Query;

class Log extends BaseImasLog
{

    public static function getLogDetails($userId)
    {
        $query = new Query();
        $query ->select(['log'])->from('imas_log')
            ->where(['LIKE','log','New Instructor Request: '.$userId.'::%']);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }
} 