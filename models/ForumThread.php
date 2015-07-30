<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 8/6/15
 * Time: 3:07 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumThreads;

class ForumThread extends BaseImasForumThreads
{

    public function createThread($params,$userId,$threadId)
    {
        $this->forumid = isset($params['forumId']) ? $params['forumId'] : null;
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
}