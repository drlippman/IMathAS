<?php

namespace app\models;
use app\models\_base\BaseImasLoginLog;

use Yii;


class LoginGrid extends BaseImasLoginLog
{

    public static function getById($cid, $newStartDate, $NewEndDate)
    {
        return static::find(['courseid' => $cid])->where(['>=', 'logintime', $newStartDate])
            ->andWhere(['<=', 'logintime', $NewEndDate])
            ->all();
        ;
    }


}