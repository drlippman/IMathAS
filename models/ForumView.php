<?php

/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/6/15
 * Time: 7:13 PM
 */

namespace app\models;

use app\components\AppConstant;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasForumPosts;
use app\models\_base\BaseImasForumViews;
use yii\db\Query;

class ForumView extends BaseImasForumViews
{
    public static function getByThreadId($threadId)
    {
        $views = ForumView::find('userid', 'threadid', 'lastview')->where(['threadid' => $threadId])->all();
        return $views;
    }

    public static function forumViews($threadId, $userId)
    {
        $thread = ForumView::findAll(['threadid' => $threadId, 'userid' => $userId]);
        return $thread;

    }

    public function updateFlagValue($threadId, $userId)
    {
        $thread = ForumView::find()->where(['threadid' => $threadId])->andWhere(['userid' => $userId])->one();

        if($thread){

            $thread->tagged = $thread['tagged'] ^ AppConstant::NUMERIC_ONE;
            $thread->save();
        }else{
            $this->userid = $userId;
            $this->threadid = $threadId;
            $this->lastview = AppConstant::NUMERIC_ZERO;
            $this->tagged = AppConstant::NUMERIC_ONE;
            $this->save();
        }

    }

    public static function uniqueCount($threadId)
    {
        $count = ForumView::find()->where(['threadid' => $threadId])->count();
        return $count;
    }

    public function createThread($userId, $threadId)
    {
        $this->threadid = $threadId;
        $postdate = strtotime(date('F d, o g:i a'));
        $this->lastview = $postdate;
        $this->userid = $userId;
        $this->tagged = AppConstant::NUMERIC_ZERO;
        $this->save();
    }

    public static function removeThread($threadId)
    {
        $threads = ForumView::findAll(['threadid' => $threadId]);
        if ($threads) {
            foreach ($threads as $thread)
            {
                $thread->delete();
            }
        }
    }

    public function  updateData($threadId, $CurrentUser)
    {
        $users = ForumView::find(['lastview', 'tagged'])->where(['threadid' => $threadId, 'userid' => $CurrentUser['id']])->all();
        if ($users) {
            foreach ($users as $user) {
                $lastView = strtotime(date('F d, o g:i a'));
                $user->lastview = $lastView;
                $user->save();
            }
        } else {
            $this->userid = $CurrentUser->id;
            $this->threadid = $threadId;
            $lastView = strtotime(date('F d, o g:i a'));
            $this->lastview = $lastView;
            $this->tagged = AppConstant::NUMERIC_ZERO;
            $this->save();
        }
    }

    public static function getById($threadId, $CurrentUser)
    {
        $lastview = ForumView::find(['lastview'])->where(['threadid' => $threadId, 'userid' => $CurrentUser['id']])->all();
        return $lastview;
    }

    public static function getLastView($currentUser, $threadId)
    {
        return ForumView::find(['lastview'])->where(['threadid' => $threadId, 'userid' => $currentUser['id']])->all();
    }

    public function inserIntoTable($threadArray)
    {
        foreach ($threadArray as $thread) {
            $users = ForumView::find(['lastview', 'tagged'])->where(['threadid' => $thread['threadId'], 'userid' => $thread['currentUserId']])->all();
            if (!$users) {
                $this->userid = $thread['currentUserId'];
                $this->threadid = $thread['threadId'];
                $lastView = strtotime(date('F d, o g:i a'));
                $this->lastview = $lastView;
                $this->tagged = AppConstant::NUMERIC_ZERO;
                $this->save();
            }
        }
    }

    public static function deleteByUserIdAndThreadId($threadId, $userId)
    {
        $threads = ForumView::find()->where(['threadid' => $threadId, 'userid' => $userId])->all();
        if ($threads) {
            foreach ($threads as $thread) {
                $thread->delete();
            }
        }
    }

    public static function getLastViewOfPost($threadId, $CurrentUser)
    {

        $lastview = ForumView::find()->select(['lastview'])->where(['threadid' => $threadId, 'userid' => $CurrentUser])->all();
        return $lastview;

    }

    public function  updateDataForPostByName($threadId,$userId,$now)
    {
        $query = new Query();
        $query ->select('id')
                ->from('imas_forum_views')
                ->where('userid= :userid',[':userid' => $userId])
                ->andWhere(['threadid' =>$threadId ]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        if(count($data)>0)
        {
            $id = $data[0]['id'];
            $query  = ForumView::findOne(['id' =>$id ]);
                $query->lastview = $now;
                $query->save();

        }
        else
        {
            $id = $data[0]['id'];
            $query  = ForumView::findOne(['id' =>$id ]);
            $query->userid = $userId;
            $query->threadid =$id;
            $query->lastview = $now;
            $query->save();
        }
    }

    public static function deleteByForumIdThreadId($threadId)
    {
        $viewEntry = ForumView::findOne(['threadid' => $threadId]);
        if ($viewEntry) {
            $viewEntry->delete();
        }
    }

    public function addView($threadid, $forumViewArray){
        $this->userid = $forumViewArray['userid'];
        $this->threadid = $threadid;
        $this->lastview = $forumViewArray['postdate'];
        $this->save();
    }
    public static function deleteViewRelatedToCourse($threads, $toUnEnroll)
    {
        $query = ForumView::find()->where(['IN', 'threadid', $threads])->andWhere(['IN', 'userid', $toUnEnroll])->all();
        if($query){
            foreach($query as $object){
                $object->delete();
            }
        }
    }
    public static function deleteByUserId($userId)
    {
        $views = ForumView::find()->where(['userid' => $userId])->all();
        foreach($views as $view)
        {
            $view->delete();
        }
    }

    public static function deleteByForumId($forumId)
    {
        $query = "DELETE imas_forum_views FROM imas_forum_views JOIN ";
        $query .= "imas_forum_threads ON imas_forum_views.threadid=imas_forum_threads.id ";
        $query .= "WHERE imas_forum_threads.forumid='{$forumId}'";
        Yii::$app->db->createCommand($query)->execute();
    }
}

