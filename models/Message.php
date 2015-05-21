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
        $this->save();
        return $this->id;
    }

    public static function getByCourseId($cid)
    {
        return static::find()->where(['courseid' => $cid])->all();
    }

    public static function getSenders($cid)
    {
        return static::find()->where(['courseid' => $cid])->groupBy(['msgfrom'])->all();
    }

    public static function getByCourseIdAsArray($cid)
    {
        return static::find()->where(['courseid' => $cid])->asArray()->all();
    }

    public static function updateUnread($msgId)
    {
        $message = static::getById($msgId);
        if($message->isread==1){
            $message->isread=0;
        }
        elseif($message->isread == 5) {
            $message->isread = 4;
        }elseif($message->isread==4) {
            $message->isread = 4;
        }else{
            $message->isread = 0;
        }
        $message -> save();
    }
    public static function updateRead($msgId)
    {
        $message = static::getById($msgId);
        if($message->isread==0)
        {
            $message->isread=1;
        }
        elseif($message->isread==1)
        {
            $message->isread=1;
        }
        elseif($message->isread== 4)
        {
            $message->isread = 5;
        }
        elseif($message->isread==5){
                $message->isread=5;
            }
          $message->save();
    }
    public static function getById($id)
    {
        return static::findOne($id);
    }

    public static function getByMsgId($msgId)
    {
        return static::findOne($msgId);
    }
    public static function deleteFromReceivedMsg($msgId)
    {
        $message =static::getById($msgId);
        if($message->isread!=4)
        {
            if($message->isread==5)
            {
                $message->delete();
            }
            elseif($message->isread==1)
            {
                $message->isread=3;
                $message->save();
            }
            elseif($message->isread==0)
            {
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
    public static function deleteFromSentMsg($msgId)
    {
        $message =static::getById($msgId);
        if($message->isread==2)
        {
            $message->delete();
        }
        elseif($message->isread==3) {
            $message->delete();
        }
        else
        {
            $message->isread=4;
            $message->save();
        }

    }
    public static function sentUnsendMsg($msgId)
    {
        $message =static::getById($msgId);
            $message->delete();
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
        return static::find()->where(['id' => $msgId] or ['baseid' => $baseId])->orderBy('id')->all();
    }

}
