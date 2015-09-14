<?php

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasSessions;
use yii\db\Query;

class Sessions extends BaseImasSessions {

    public static function getById($sessionId){
        return Sessions::findOne(['sessionid' => $sessionId]);
    }

    public static function deleteSession($userId){
        $sessionData = Sessions::findOne(['userid' => $userId]);
        if($sessionData){
            $sessionData->delete();
        }
    }

    public static function setSessionId($sessionId,$enc){
        $sessionData = Sessions::getById($sessionId);
        if($sessionData){
            $sessionData->sessiondata = $enc;
            $sessionData->save();
        }
    }

    public static function deleteByTime($oldTime){
        $sessionData = Sessions::find()->where(['<','time', $oldTime])->all();
        if($sessionData){
            foreach($sessionData as $singleData){
                $singleData->delete();
            }
        }
    }

    public function createSession($sessionId,$userid,$now,$tzoffset,$tzdate,$enc){
        $this->sessionid = $sessionId;
        $this->userid = $userid;
        $this->time = $now;
        $this->tzoffset = $tzoffset;
        if($tzdate !=''){
            $this->tzname = $tzdate;
        }
        $this->sessiondata = $enc;
        $this->save();
    }

    public static function deleteBySessionId($sessionId){
        $sessionData = Sessions::findOne(['sessionid' => $sessionId]);
        if($sessionData){
            $sessionData->delete();
        }
    }
    public static function updateUId($be,$sessionId)
    {
        $sessionData = Sessions::findOne(['sessionid' => $sessionId]);
        if($sessionData)
        {
            $sessionData->userid = $be;
            $sessionData->save();
        }
    }

    public static function getBySessionId($sessionid)
    {
       return Sessions::find()->where(['sessionid' => $sessionid])->all();
    }
}