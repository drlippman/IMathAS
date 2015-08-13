<?php

namespace app\models;
use app\components\AppUtility;
use app\models\_base\BaseImasLoginLog;

use Yii;
use yii\db\Query;


class LoginGrid extends BaseImasLoginLog
{

    public static function getById($cid, $start, $end)
    {
        $query = "SELECT userid, logintime, FirstName, LastName
 FROM imas_login_log inner join imas_users on imas_users.id = imas_login_log.userid WHERE
 courseid='$cid' AND logintime>=$start AND logintime<=$end order by FirstName, userid, logintime";
//
//        $query  = new Query();
//        $query  ->select(['imas_users.userid', 'imas_login_log.logintime', 'imas_users.FirstName', 'imas_users.LastName'])
//         -> from('imas_login_log')
//            ->join(
//                'INNER JOIN',
//                'imas_users',
//                'imas_users.id = imas_login_log.userid'
//            )
//            ->where(['imas_login_log.courseid'=> $cid])
//            ->andWhere(['>=','imas_login_log.logintime',$start])
//            ->andWhere(['<=','imas_login_log.logintime',$end])
//            ->orderBy('imas_users.FirstName, imas_users.userid, imas_login_log.logintime');
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($query);
        $logins = $command->queryAll();
//        $command = $query->createCommand();
//        $logins = $command->queryAll();
        return $logins;
    }
}