<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 24/6/15
 * Time: 1:07 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasForumLikes;
use yii\db\Query;

class ForumLike extends BaseImasForumLikes
{

    public function InsertLike($params,$userId)
    {

       $isRecord = $this->find('threadid','userid','postid')->where(['threadid' => $params['threadid'],'userid' => $userId,'postid' => $params['id']])->all();
        if(!$isRecord){

            $this->userid = $userId;
            $this->threadid = $params['threadid'];
            $this->postid = $params['id'];
            $this->type = $params['type'];
            $this->save();
        }
    }

    public function findCOunt($threadId)
    {
        $likecount = new Query();
        $likecount->select(['postid', 'type', 'COUNT(*) AS count'] )
            ->from('imas_forum_likes')
            ->where(['threadid'=>$threadId])
            ->groupBy(['postid','type']);
        $command = $likecount->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function UserLikes($threadId,$currentUser)
    {

        $mylikes = new Query();
        $mylikes->select(['postid'])
            ->from('imas_forum_likes')
            ->where(['threadid'=>$threadId,'userid' => $currentUser['id']]);
        $command = $mylikes->createCommand();
        $data = $command->queryAll();
        return $data;

    }

    public function DeleteLike($params,$userId)
    {

        $deleteLike = $this->find('threadid','userid','postid')->where(['threadid' => $params['threadid'],'userid' => $userId,'postid' => $params['id']])->all();

        if($deleteLike){


            foreach($deleteLike as $unlike)
            {
                $unlike->delete();

            }
        }
    }

    public static function checkStatus($postid,$currentUser)
    {
        $isRecord = ForumLike::findAll(['userid' => $currentUser['id'],'postid' => $postid]);
        if($isRecord){
            return 1;
        }
        else
        {
            return 0;

        }
    }

    public function checkCOunt($params)
    {
        $count = ForumLike::findAll(['postid'  => $params['id'],'type' => $params['type']]);
        return $count;
    }
} 