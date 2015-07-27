<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 24/6/15
 * Time: 1:07 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumLikes;
use yii\db\Query;

class ForumLike extends BaseImasForumLikes
{

    public function InsertLike($params, $userId)
    {
        $isRecord = $this->find('threadid', 'userid', 'postid')->where(['threadid' => $params['threadid'], 'userid' => $userId, 'postid' => $params['id']])->all();
        if (!$isRecord) {
            $this->userid = $userId;
            $this->threadid = $params['threadid'];
            $this->postid = $params['id'];
            $this->type = $params['type'];
            $this->save();
        }
    }

    public function findCOunt($threadId)
    {
        $likeCount = new Query();
        $likeCount->select(['postid', 'type', 'COUNT(*) AS count'])->from('imas_forum_likes')->where(['threadid' => $threadId])->groupBy(['postid', 'type']);
        $command = $likeCount->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function UserLikes($threadId, $currentUser)
    {
        $myLikes = new Query();
        $myLikes->select(['postid'])->from('imas_forum_likes')->where(['threadid' => $threadId, 'userid' => $currentUser['id']]);
        $command = $myLikes->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function DeleteLike($params, $userId)
    {
        $deleteLike = $this->find('threadid', 'userid', 'postid')->where(['threadid' => $params['threadid'], 'userid' => $userId, 'postid' => $params['id']])->all();
        if ($deleteLike) {
            foreach ($deleteLike as $unlike) {
                $unlike->delete();
            }
        }
    }

    public static function checkStatus($postId, $currentUser)
    {
        $isRecord = ForumLike::findAll(['userid' => $currentUser['id'], 'postid' => $postId]);
        if ($isRecord) {
            return AppConstant::NUMERIC_ONE;
        } else {
            return AppConstant::NUMERIC_ZERO;
        }
    }

    public function checkCount($params)
    {
        $count = ForumLike::findAll(['postid' => $params['id'], 'type' => $params['type']]);
        return $count;
    }

    public function CalculateCount($postId)
    {
        $count = ForumLike::findAll(['postid' => $postId]);
        return $count;
    }
} 