<?php

namespace app\models;
use app\models\_base\BaseImasLoginLog;

use Yii;


class LoginGrid extends BaseImasLoginLog
{

    public static function getById($cid, $start, $end)
    {
        $query = "SELECT userid, logintime, FirstName, LastName FROM imas_login_log inner join imas_users on imas_users.id = imas_login_log.userid WHERE courseid='$cid' AND logintime>=$start AND logintime<=$end order by FirstName, userid, logintime";

        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($query);
        $logins = $command->queryAll();
        return $logins;
    }


}