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

class ForumView extends BaseImasForumViews
{


    public static function getbythreadId($threadid)
    {

        $views = Yii::$app->db->createCommand("SELECT userid,threadid,lastview FROM imas_forum_views where threadid = $threadid ")->queryAll();
        return $views;
    }
    public static  function forumViews($threadid){


        $thread = Yii::$app->db->createCommand("SELECT * from  imas_forum_views WHERE threadid = $threadid")->queryAll();
        return $thread;

    }
    public static function updateFlagValue($row)
    {
        Yii::$app->db->createCommand("UPDATE imas_forum_views SET tagged=(tagged^1) WHERE threadid=".$row)->execute();

    }

    public static function uniqueCount($threadid)
    {

        $count = Yii::$app->db->createCommand("SELECT count(userid)'usercount' FROM imas_forum_views where threadid = $threadid")->queryAll();
        return $count;
    }

    public  function createThread($userId,$threadId)
    {

        $this->threadid = $threadId;
        $postdate = strtotime(date('F d, o g:i a'));
        $this->lastview = $postdate;
        $this->userid = $userId;
        $this->tagged = 0;
        $this->save();
    }
    public static  function removeThread($threadId)
    {
        $threads = ForumView::findAll(['threadid' => $threadId]);
        if($threads)
        {
            foreach($threads as $thread)
            {
                $thread->delete();
            }
        }
    }

    public function  updateData($threadId,$CurrentUser)
    {
         $users = ForumView::find(['lastview','tagged'])->where(['threadid' => $threadId,'userid' => $CurrentUser['id']])->all();
        if($users)
        {
            foreach($users as $user){
                        $lastView = strtotime(date('F d, o g:i a'));
                        $user->lastview = $lastView;
                        $user->save();
            }
        }
        else{
            $this->userid = $CurrentUser->id;
            $this->threadid = $threadId;
            $lastView = strtotime(date('F d, o g:i a'));
            $this->lastview = $lastView;
            $this->tagged = AppConstant::NUMERIC_ZERO;
            $this->save();
        }
    }

    public static  function getById($threadId,$CurrentUser)
    {

        $lastview = ForumView::find(['lastview'])->where(['threadid' => $threadId,'userid' => $CurrentUser['id']])->all();
        return $lastview;

    }

 public static function getLastView($currentUser,$threadId)
 {

     return ForumView::find(['lastview'])->where(['threadid' =>$threadId,'userid' => $currentUser['id']])->all();

 }
   public function inserIntoTable($threadArray)
   {

        foreach($threadArray as $thread)
        {

            $users = ForumView::find(['lastview','tagged'])->where(['threadid' =>  $thread['threadId'],'userid' => $thread['currentUserId']])->all();

            if(!$users)
            {
                $this->userid = $thread['currentUserId'];
                $this->threadid = $thread['threadId'];
                $lastView = strtotime(date('F d, o g:i a'));
                $this->lastview = $lastView;
                $this->tagged = AppConstant::NUMERIC_ZERO;
                $this->save();
            }
        }

   }

 public static function deleteByUserIdAndThreadId($threadId,$userId)
 {
     $threads = ForumView::find()->where(['threadid' => $threadId,'userid' => $userId])->all();
     if($threads)
     {
         foreach($threads as $thread)
         {
             $thread->delete();
         }
     }
 }
}

