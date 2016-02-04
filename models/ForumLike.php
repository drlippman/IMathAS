<?php

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

    public function InsertLikes($threadid,$postId,$isTeacher, $userId)
    {
        $this->userid = $userId;
        $this->threadid = $threadid;
        $this->postid = $postId;
        $this->type = $isTeacher;
        $this->save();
    }


    public function findCOunt($threadId)
    {
        $likeCount = new Query();
        $likeCount->select(['postid', 'type', 'COUNT(*) AS count'])
            ->from('imas_forum_likes')
            ->where('threadid = :threadId')
            ->groupBy(['postid', 'type']);
        $command = $likeCount->createCommand()->bindValue('threadId', $threadId);
        $data = $command->queryAll();
        return $data;
    }

    public function UserLikes($threadId, $currentUser)
    {
        return self::find()->select(['postid'])->where(['threadid' => $threadId, 'userid' => $currentUser['id']])->all();
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

    public static function getPostLikeDetails($postId)
    {
        $query = "SELECT iu.LastName,iu.FirstName FROM imas_users AS iu JOIN ";
        $query .= "imas_forum_likes AS ifl ON iu.id=ifl.userid WHERE ifl.postid=:postId ORDER BY iu.LastName,iu.FirstName";
        $data = \Yii::$app->db->createCommand($query)->bindValue(':postId', $postId)->queryAll();
        return $data;
    }

    public static function deleteLikes($postId, $userId)
    {
        return $deleteLike = ForumLike::deleteAll(['postid' => $postId, 'userid' => $userId]);
    }

    public static function getById($postId, $userId)
    {
        $query = "SELECT id FROM imas_forum_likes WHERE postid=:postId AND userid=:userId";
        $command = \Yii::$app->db->createCommand($query)->bindValues([':postId' => $postId, ':userId' => $userId]);
        $data = $command->queryAll();
        return $data;
    }

    public static function findCountLike($postId)
    {
        $query = "SELECT type,count(*) FROM imas_forum_likes WHERE postid=:postId";
        $query .= " GROUP BY type";
        $command =  \Yii::$app->db->createCommand($query)->bindValue(':postId', $postId);
        return $data = $command->queryAll();
    }


    public static function getUserId($postId)
    {
        $query = "SELECT userid FROM imas_forum_likes WHERE postid=:postId";
        $command =  \Yii::$app->db->createCommand($query)->bindValue(':postId', $postId);
        return $data = $command->queryAll();
    }

} 