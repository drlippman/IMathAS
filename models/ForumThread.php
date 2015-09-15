<?php

namespace app\models;

use app\components\AppConstant;
use app\models\_base\BaseImasForumThreads;
use yii\db\Query;

class ForumThread extends BaseImasForumThreads
{

    public function createThread($params,$userId,$threadId)
    {
        $this->forumid = isset($params['forumid']) ? $params['forumid'] : null;
        $this->id = $threadId;
        $postdate = strtotime(date('F d, o g:i a'));
        $this->lastposttime = $postdate;
        $this->lastpostuser = $userId;
        $this->views = AppConstant::NUMERIC_ZERO;
        $this->save();
    }

    public static  function removeThread($threadId)
    {
        $threads = ForumThread::findAll(['id' => $threadId]);
        if($threads)
        {
            foreach($threads as $thread)
            {
                $thread->delete();
            }
        }
    }

    public static function findThreadCount($forumId)
    {
        $thread = ForumThread::findAll(['forumid' => $forumId]);
        return $thread;
    }

    public function addThread($threadid, $forumPostArray){
        $this->id = $threadid;
        $this->forumid = $forumPostArray['forumid'];
        $this->lastposttime = $forumPostArray['postdate'];
        $this->lastpostuser = $forumPostArray['userid'];
        $this->save();
    }

    public static function findByStuGrpId($grpId)
    {
        $query = new Query();
        $query ->select(['id'])
               ->from('imas_forum_threads')
               ->where('stugroupid= :stugroupid',[':stugroupid' => $grpId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function deleteForumThread($delList)
    {
        $query = "DELETE FROM imas_forum_threads WHERE id IN ($delList)";
        \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function updateThreadForGroups($grpId)
    {
        $query = ForumThread::find()->where(['stugroupid' => $grpId])->all();
        if($query)
        {
            foreach($query as $data)
            {
                $data->stugroupid = AppConstant::NUMERIC_ZERO;
                $data->save();
            }
        }
    }

    public static function deleteByForumId($forumId)
    {
        $entry = ForumThread::findOne(['forumid' => $forumId]);
        if ($entry) {
            $entry->delete();
        }
    }
}