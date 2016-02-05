<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumThreads;
use yii\db\Query;
use app\controllers\AppController;
use Yii;

class ForumThread extends BaseImasForumThreads
{
    public function createThread($params,$userId,$threadId,$groupId = null)
    {
        $this->forumid = isset($params['forumid']) ? $params['forumid'] : null;
        $this->id = $threadId;
        $postdate = AppController::dateToString();
        $this->lastposttime = $postdate;
        $this->lastpostuser = $userId;
        $this->views = AppConstant::NUMERIC_ZERO;
        if($groupId)
        {
            $this->stugroupid = $groupId;
        }
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
        return self::find()->select(['id'])->where(['stugroupid' => $grpId])->all();
    }

    public static function deleteForumThread($delList)
    {
        $data = ForumThread::find()->where(['IN','id', $delList])->all();
        if($data){
            foreach($data as $singleData){
                $singleData->delete();
            }
        }
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

    public static function getNewPostData($poststucidlist, $now, $userid)
    {
        $placeholders= "";
        if($poststucidlist)
        {
            foreach($poststucidlist as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }

        $query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<:now && imas_forums.enddate>:now)) ";
        $query .= "AND imas_forums.courseid IN ($placeholders) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid= :userId ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid= :userId)) ";
        $query .= "ORDER BY imas_forum_threads.lastposttime DESC";
        $command = \Yii::$app->db->createCommand($query);

        $command->bindValues(['userId'=> $userid, ':now' => $now]);
        foreach($poststucidlist as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function getPostThread($poststucidlist, $now, $userid)
    {
        $placeholders= "";
        if($poststucidlist)
        {
            foreach($poststucidlist as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT imas_forums.courseid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<:now && imas_forums.enddate>:now)) ";
        $query .= "AND imas_forums.courseid IN ($placeholders) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid= :userId ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid= :userId)) ";
        $query .= "GROUP BY imas_forums.courseid";
        $command = \Yii::$app->db->createCommand($query);
        $command->bindValues(['userId'=> $userid, ':now' => $now]);

        foreach($poststucidlist as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataByUserId($teacherid, $cid, $userid, $now)
    {
        $query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id)
                    FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id
                    AND imas_forums.courseid =:courseId ";
        if (!($teacherid)) {
            $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1
                        AND imas_forums.startdate<:now && imas_forums.enddate>:now)) ";
        }
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid =:userId ";
        $query .= "WHERE(imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        if (!($teacherid)) {
            $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid
                        IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid =:userId)) ";
        }
        $query .= "GROUP BY imas_forum_threads.forumid";
        $command = \Yii::$app->db->createCommand($query);

        $command->bindValues([':courseId'=>$cid,':userId'=>$userid]);
        if (!($teacherid)) {
            $command->bindValues([':now' => $now, ':userId' => $userid]);
        }
        return $command->queryAll();
    }

    public static function getNewPost($postcidlist, $userid)
    {
        $placeholders= "";
        if($postcidlist)
        {
            foreach($postcidlist as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND imas_forums.courseid IN ($placeholders) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=':userId' ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "ORDER BY imas_forum_threads.lastposttime DESC";
        $command = \Yii::$app->db->createCommand($query);
        $command->bindValue('userId',$userid);

        foreach($postcidlist as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function getPostData($postcidlist, $userid)
    {
        $placeholders= "";
        if($postcidlist)
        {
            foreach($postcidlist as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT imas_forums.courseid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND imas_forums.courseid IN ($placeholders) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=':userId' ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userId)) ";
        $query .= "GROUP BY imas_forums.courseid";
        $command = \Yii::$app->db->createCommand($query);
        $command->bindValue('userId',$userid);
        foreach($postcidlist as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function forumThreadCount($cid,$userid,$teacherid)
    {
        $query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid=:courseId ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userId ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        if (!isset($teacherid)) {
            $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid
            IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userId )) ";
        }
        $query .= "GROUP BY imas_forum_threads.forumid";
        $command = Yii::$app->db->createCommand($query);
        $command->bindValues([':courseId' => $cid, ':userId' => $userid]);
        if (!isset($teacherid)) {
            $command->bindValue(':userId',$userid);
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function updateViews($threadId)
    {
        $query = "UPDATE imas_forum_threads SET views=views+1 WHERE id=:threadId";
        $data = Yii::$app->db->createCommand($query)->bindValue(':threadId', $threadId)->execute();
        return $data;
    }

    public static function getDataForPrev($forumid, $threadid,$groupid,$groupset)
    {
        $query = "SELECT id FROM imas_forum_threads WHERE forumid=:forumid AND id<:threadid ";
        if ($groupset>0 && $groupid!=-1)
        {
            $query .= "AND (stugroupid=':groupid' OR stugroupid=0) ";
        }
        $query .= "ORDER BY id DESC LIMIT 1";
        $command = Yii::$app->db->createCommand($query)->bindValues([':forumid' => $forumid, ':threadid' => $threadid]);
        if ($groupset>0 && $groupid!=-1)
        {
            $command->bindValue(':groupid', $groupid);
        }
        $data = $command->queryOne();
        return $data;
    }

    public static function getDataForNext($forumid, $threadid,$groupid,$groupset)
    {
        $query = "SELECT id FROM imas_forum_threads WHERE forumid=:forumid AND id>:threadid ";
        if ($groupset>0 && $groupid!=-1) {
            $query .= "AND (stugroupid=':groupid' OR stugroupid=0) ";
        }
        $query .= "ORDER BY id LIMIT 1";
        $command = Yii::$app->db->createCommand($query)->bindValues([':forumid' => $forumid, ':threadid' => $threadid]);
        if ($groupset>0 && $groupid!=-1)
        {
            $command->bindValue(':groupid', $groupid);
        }
        $data = $command->queryOne();
        return $data;
    }
}