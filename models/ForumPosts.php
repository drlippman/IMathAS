<?php

namespace app\models;
use app\components\AppUtility;
use app\models\_base\BaseImasForumPosts;

use app\models\forms\ForumForm;
use Yii;


class ForumPosts extends BaseImasForumPosts
{

    public static function updatePostMoveThread($threadId,$moveThreadId)
    {

        $ForumPost = ForumPosts::findOne(['threadid' => $threadId]);
        $ForumPost->threadid = $moveThreadId;
        $ForumPost->save();
    }
    public static function getbyid($threadId)
    {

        $ForumPost = ForumPosts::findAll(['threadid' => $threadId]);
     return $ForumPost;
    }
    public static function getbyidpost($threadId)
    {

        $ForumPost = ForumPosts::findAll(['id' => $threadId]);
        return $ForumPost;
    }

    public static  function modifyThread($threadid,$message,$subject)
    {
        $threadPost = ForumPosts::findOne(['threadid' => $threadid]);
        $threadPost->subject = $subject;
        $threadPost->message = $message;
        $threadPost->save();
    }
    public static function removeThread($threadid)
    {
        $thread = ForumPosts::findOne($threadid);
                $thread->delete();
    }
    public static function updateMoveThread($forumId,$threadId)
    {

        $ForumPost = ForumPosts::findOne(['threadid' => $threadId]);
        $ForumPost->forumid = $forumId;
        $ForumPost->save();
    }
}