<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 6:30 PM
 */

namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\models\_base\BaseImasMsgs;

class Message extends BaseImasMsgs
{
    public function create($params,$uid)
    {
        $this->courseid = $params['cid'];
        $this->msgfrom = $uid;
        $this->msgto = $params['receiver'];
        $this->title = $params['subject'];
        $this->message = $params['body'];
        $sendDate = strtotime(date('F d, o g:i a'));
        $this->senddate = $sendDate;
        if($params['isread'] == 4)
        {
            $this->isread = 4;
        }
        $this->save();
        return $this->id;
    }

    public static function getByCourseId($cid)
    {
        return Message::find()->where(['courseid' => $cid])->all();
    }

    public static function getSenders($cid)
    {
        return Message::find()->where(['courseid' => $cid])->groupBy(['msgfrom'])->all();
    }

    public static function getByCourseIdAsArray($cid)
    {
        return Message::find()->where(['courseid' => $cid])->asArray()->all();
    }

    public static function updateUnread($msgId)
    {
        $message = Message::getById($msgId);
        if($message->isread==1){
            $message->isread=0;
        }
        elseif($message->isread == 5) {
            $message->isread = 4;
        }elseif($message->isread==4) {
            $message->isread = 4;
        }elseif($message->isread>=9){
            $message->isread=8;
        }elseif($message->isread>=13){
            $message->isread=12;
        }
        else{
            $message->isread = 0;
        }
        $message -> save();
    }
    public static function updateRead($msgId)
    {
        $message = Message::getById($msgId);
        if($message->isread==0) {
            $message->isread=1;
        }
        elseif($message->isread==1) {
            $message->isread=1;
        }
        elseif($message->isread== 4) {
            $message->isread = 5;
        }
        elseif($message->isread==5){
                $message->isread=5;
        }
        elseif($message->isread==8){
            $message->isread=9;
        }
        elseif($message->isread==12){
            $message->isread=13;
        }
          $message->save();
    }
    public static function getById($id)
    {
        return Message::findOne($id);
    }

    public static function getByMsgId($msgId)
    {
        return Message::findOne($msgId);
    }
    public static function deleteFromReceivedMsg($msgId)
    {
        $message =Message::getById($msgId);
        if($message)
        {
            if($message->isread != 4){
                if($message->isread == 5 ) {
                    $message->delete();
                }
                elseif($message->isread == 1) {
                    $message->isread = 3;
                    $message->save();
                }
                elseif($message->isread == 0) {
                    $message->isread = 2;
                    $message->save();
               }
                else{
                    $message->isread = 3;
                   $message->save();
                }
            }
            elseif($message->isread==4)
            {
                $message->delete();
             }
        }
    }
    public static function deleteFromSentMsg($msgId)
    {
        $message =Message::getById($msgId);
        if($message){
            if($message->isread==2) {
                $message->delete();
            }
            elseif($message->isread==3) {
                $message->delete();
            }
            else {
                if($message->isread>=8) {
                    $message->isread=$message->isread+4;
                }
                else {
                    $message->isread = 4;
                }
                $message->save();
            }
        }
    }
    public static function sentUnsendMsg($msgId)
    {
        $message =Message::getById($msgId);
            if($message){
                $message->delete();
            }
    }

    public function createReply($params)
    {
        $this->courseid = $params['cid'];
        $this->msgfrom = isset($params['sender']) ? $params['sender'] : null;
        $this->msgto = isset($params['receiver']) ? $params['receiver'] : null;
        $this->title = isset($params['subject']) ? $params['subject'] : null;
        $this->message = isset($params['body']) ? $params['body'] : null;
        $this->parent = isset($params['parentId']) ? $params['parentId'] : null;
        $baseId = isset($params['baseId']) ? $params['baseId'] : null;
        if ($baseId != 0)
        {
            $this->baseid = isset($params['baseId']) ? $params['baseId'] : null;

        }else{

            $baseId = isset($params['parentId']) ? $params['parentId'] : null;
            $this->baseid = $baseId;
        }
        $sendDate = strtotime(date('F d, o g:i a'));
        $this->senddate = $sendDate;
        $this->save();
        return $this->id;
    }

    public static function getByBaseId($msgId, $baseId)
    {
        if($baseId == 0)
        {
            $baseId = $msgId;
        }
        return Message::find()->where(['id' => $baseId])->orWhere(['baseid' => $baseId])->orderBy('senddate')->asArray()->all();
    }

    public static function getUsersToDisplay($uid)
    {
        $query = Yii::$app->db->createCommand("SELECT imas_msgs.id,imas_msgs.courseid,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.isread,imas_courses.name,imas_msgs.msgfrom,imas_users.hasuserimg FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE imas_msgs.msgto='$uid' AND (imas_msgs.isread&2)=0 ORDER BY imas_msgs.id DESC")->queryAll();
        return $query;
    }

    public static function getUsersToDisplayMessage($uid){
        $query = Yii::$app->db->createCommand("SELECT imas_msgs.id,imas_msgs.courseid,imas_msgs.title,imas_msgs.msgto,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.isread FROM imas_msgs,imas_users WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom='$uid' AND (imas_msgs.isread&4)=0 ORDER BY imas_msgs.id DESC")->queryAll();
        return $query;
    }

    public static function getUsersToUserMessage($userId){
        $query = Yii::$app->db->createCommand("SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users JOIN imas_msgs ON imas_msgs.msgfrom=imas_users.id WHERE imas_msgs.msgto='$userId'")->queryAll();
        return $query;
    }

    public static function getUsersToCourseMessage($userId){
        $query = Yii::$app->db->createCommand("SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgfrom='$userId'")->queryAll();
        return $query;
    }

    public static function getSentUsersMessage($userId){
        $query = Yii::$app->db->createCommand("SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users JOIN imas_msgs ON imas_msgs.msgto=imas_users.id WHERE imas_msgs.msgfrom='$userId'")->queryAll();
        return $query;
    }
    public static function updateFlagValue($row)
    {
        $query = Yii::$app->db->createCommand("UPDATE imas_msgs SET isread=(isread^8) WHERE id='$row';'")->queryAll();
    }
    public static function getByCourseIdAndUserId($courseid, $userId)
    {
        return Message::find()->where(['courseid' => $courseid, 'msgto' => $userId])->all();
    }
}

