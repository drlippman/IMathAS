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
use Yii;
use app\models\_base\BaseImasInstrFiles;

class Thread extends BaseImasForumThreads
{


    public static function getById($id)
    {
        return Thread::findOne(['id' => $id]);
    }

    public static function getPreOrNextThreadId($currentId,$next=null,$prev=null,$forumId)
    {

        if($next == AppConstant::NUMERIC_TWO){
            $thread = Thread::find()->where(['>', 'id', $currentId])->andWhere(['forumid' => $forumId])->one();
//            AppUtility::dump($thread['id']);
        }elseif($prev == AppConstant::NUMERIC_ONE){
            $thread = Thread::find()->where(['<', 'id', $currentId])->andWhere(['forumid' => $forumId])->one();
//            AppUtility::dump($thread['id']);
        }
        return $thread;
    }
    public static function deleteThreadByForumId($itemId)
    {
        $thread = Thread::findOne(['forumid' => $itemId]);
        if($thread){
            $thread->delete();
        }

    }

    public static function deleteThreadById($itemId)
    {
        $thread = Thread::findOne(['id' => $itemId]);
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
    public static function getByForumId($forumId)
    {
        $threadData = Yii::$app->db->createCommand("SELECT * FROM imas_forum_threads WHERE forumid = $forumId AND stugroupid>0 LIMIT 1")->queryAll();
        return $threadData;
    }
    public static function checkPreOrNextThreadByForunId($forumId)
    {
        $minThreadId = Thread::find()->where(['forumid' => $forumId])->min('id');
        $maxThreadId = Thread::find()->where(['forumid' => $forumId])->max('id');
        $prevNextValueArray = array(
            'maxThread' =>$maxThreadId,
            'minThread' =>$minThreadId,
        );
        return $prevNextValueArray;
    }
    public static function getAllThread($forumId)
    {

        $forumData = Thread::findAll(['forumid'=> $forumId]);
        $allThreadId = array();
        foreach($forumData as $key=>$singleForum){
            $allThreadId[$key] = $singleForum['id'];
        }
        return $allThreadId;
    }
    public static function getByForumIdAndId($forumId,$threadId)
    {
        $thread = Thread::find()->where(['id' => $threadId])->andWhere(['forumid' => $forumId])->one();
        return $thread['views'];
    }

    public static function saveViews($threadid)
    {
        $views = Thread::find(['views'])->where(['id' => $threadid])->one();
        $views->views++;
        $views->save();
    }
} 