<?php

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

    public function createLog($time, $log){
        $this->log = $log;
        $this->time = $time;
        $this->save();
    }

    public function insertEntry($now,$uid,$grpid)
    {
        $query = "INSERT INTO imas_log (time,log) VALUES (:now,'deleting :uid from :grpid')";
        $command = \Yii::$app->db->createCommand($query)->bindValues([':now' => $now,':uid' => $uid,':grpid' => $grpid])->execute();
    }
} 