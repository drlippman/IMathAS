<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 30/5/15
 * Time: 1:05 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumThreads;
use app\models\_base\BaseImasInstrFiles;
use yii\db\Query;

class Thread extends BaseImasForumThreads
{


    public static function getById($id)
    {
        return Thread::findOne(['id' => $id]);
    }

    public static function getNextThreadId($currentId,$next=null,$prev=null,$forumId)
    {

        if($next == AppConstant::NUMERIC_TWO){
            $thread = Thread::find()->where(['>', 'id', $currentId])->andWhere(['forumid' => $forumId])->one();
        }elseif($prev == AppConstant::NUMERIC_ONE){
            $thread = Thread::find()->where(['<', 'id', $currentId])->andWhere(['forumid' => $forumId])->one();
        }
        $minThreadId = Thread::find()->where(['forumid' => $forumId])->min('id');
        $maxThreadId = Thread::find()->where(['forumid' => $forumId])->max('id');
        $prevNextValueArray = array();
        $prevNextValueArray = array(
        'threadId' =>$thread->id,
        'maxThread' =>$maxThreadId,
            'minThread' =>$minThreadId,
    );
        return $prevNextValueArray;
    }
    public static function deleteThreadById($id)
    {
        $thread = Thread::findOne(['id' => $id]);
        if($thread){
            $thread->delete();
        }

    }
    public static function moveAndUpdateThread($forumId,$threadId)
    {
        $ForumPost = Thread::findOne(['id' => $threadId]);
        $ForumPost->forumid = $forumId;
        $ForumPost->save();
    }



} 