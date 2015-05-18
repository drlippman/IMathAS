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
    public function create($params)
    {
        $this->courseid = $params['cid'];
        $this->msgfrom = $params['sender'];
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
         $message  = static::getById($msgId);
        $message -> isread = 0;
        $message -> save();
    }
    public static function updateRead($msgId)
    {
        $message = static::getById($msgId);
        $message->isread = 1;
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

}
