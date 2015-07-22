<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumPosts;

use app\models\forms\ForumForm;
use Yii;
use yii\db\Query;


class ForumPosts extends BaseImasForumPosts
{
    public static function updatePostMoveThread($threadId, $moveThreadId)
    {
        $ForumPost = ForumPosts::find()->where(['id' => $threadId])->one();
        $ForumPosts = ForumPosts::findAll(['threadid' => $threadId]);
        if($ForumPosts)
        {
            foreach($ForumPosts as $singleForum){
                $singleForum->threadid = $moveThreadId;
                $singleForum->save();
            }
        }
        if($ForumPost) {
            $ForumPost->parent = $moveThreadId;
            $ForumPost->save();
        }
    }

    public static function getbyid($threadId)
    {
        $ForumPost = ForumPosts::findAll(['threadid' => $threadId]);
        return $ForumPost;
    }

    public static function getbyidpost($id)
    {

        $ForumPost = ForumPosts::findAll(['id' => $id]);
        return $ForumPost;
    }

    public static function modifyPost($params)
    {
        $threadPost = ForumPosts::findOne(['id' => $params['threadId']]);
        $threadPost->subject = $params['subject'];
        $threadPost->message = $params['message'];
            if($params['always-replies'] != AppConstant::NUMERIC_THREE) {
                $replyBy = $params['always-replies'];
            } else {
                $replyBy = AppUtility::parsedatetime($params['startDate'], $params['startTime']);
                $threadPost->replyby = $replyBy;
            }
            $threadPost->replyby = $replyBy;
            $threadPost->posttype = $params['post-type'];
            $threadPost->save();
    }

    public static function removeThread($threadId, $parentId)
    {

        if ($parentId == AppConstant::NUMERIC_ZERO) {
            $threads = ForumPosts::findAll(['threadid' => $threadId]);
        } else {
            $threads = ForumPosts::findAll(['id' => $threadId]);
        }
        if ($threads) {
            foreach ($threads as $thread) {
                $thread->delete();
            }
        }
    }

    public static function updateMoveThread($forumId, $threadId)
    {

        $ForumPost = ForumPosts::findOne(['threadid' => $threadId]);
        $ForumPost->forumid = $forumId;
        $ForumPost->save();
    }

    public function createReply($params, $user)
    {
        $this->threadid = isset($params['threadId']) ? $params['threadId'] : null;
        $this->forumid = isset($params['forumId']) ? $params['forumId'] : null;
        $this->subject = isset($params['subject']) ? $params['subject'] : null;
        $this->userid = isset($user->id) ? $user->id : null;
        $this->parent = $params['parentId'];
        $this->message = isset($params['body']) ? $params['body'] : null;
        $postdate = strtotime(date('F d, o g:i a'));
        $this->postdate = $postdate;
        $this->save();
    }

    public function createThread($params, $userId, $postType, $alwaysReplies, $date)
    {
        $maxid = $this->find()->max('id');
        $maxid = $maxid + AppConstant::NUMERIC_ONE;
        $this->id = $maxid;
        $this->forumid = isset($params['forumId']) ? $params['forumId'] : null;
        $this->threadid = isset($maxid) ? $maxid : null;
        if (empty($params['subject'])) {
            $params['subject'] = '(None)';
        }
        $this->subject = trim($params['subject']);
        $this->userid = isset($userId) ? $userId : null;
        $postdate = strtotime(date('F d, o g:i a'));
        $this->postdate = $postdate;
        $this->message = isset($params['body']) ? $params['body'] : null;
        $this->posttype = $postType;
        if ($alwaysReplies == AppConstant::NUMERIC_ONE) {
            $this->replyby = AppConstant::ALWAYS_TIME;
        } elseif ($alwaysReplies == AppConstant::NUMERIC_TWO) {
            $this->replyby = AppConstant::NUMERIC_ZERO;
        } elseif ($alwaysReplies == AppConstant::NUMERIC_THREE) {
            $this->replyby = $date;
        }
        $this->save();
        return ($this->threadid);

    }

    public static function getPostById($Id)
    {
        $ForumPost = ForumPosts::findOne(['id' => $Id]);
        return $ForumPost;
    }

    public static function getbyThreadIdAndUserID($threadId, $currentUserId)
    {

        $ForumPost = ForumPosts::findAll(['threadid' => $threadId, 'userid' => $currentUserId]);
        return $ForumPost;
    }

    public static function getbyParentId($parent)
    {
        $parentThread = ForumPosts::findOne(['threadid' => $parent]);
         return $parentThread;
    }

    public static function findCount($threadId)
    {
        $query = new Query();
        $query->select(['count(parent) as count'])
            ->from('imas_forum_posts')
            ->where(['parent' => $threadId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function deleteForumPost($itemId)
    {
        $entry = ForumPosts::findOne(['forumid' => $itemId]);
        if ($entry) {
            $entry->delete();
        }
    }

    public static function getForumPostByFile($itemId)
    {
        $entry = ForumPosts::findOne(['forumid' => $itemId, 'files' => '']);
        if ($entry) {
            $postId = $entry['id'];
            return $postId;
        }
    }
    public static function updateParentId($threadId,$parentId)
    {
        $entries = ForumPosts::findAll(['parent' => $threadId]);
        foreach($entries as $entry)
        {
            $entry['parent'] = $parentId;
            $entry->save();
        }
    }
    public static function getParentDataByParentId($threadId)
    {
        $entries = ForumPosts::findOne(['id' => $threadId]);
         return $entries;
    }
    public static function isThreadHaveReply($id)
    {
      $entry = ForumPosts::find()->where(['parent' => $id])->all();
        if($entry)
        {
            return AppConstant::NUMERIC_ONE;
        }
        return AppConstant::NUMERIC_ZERO;
    }
}