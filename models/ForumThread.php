<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumThreads;
use yii\db\Query;
use app\controllers\AppController;

class ForumThread extends BaseImasForumThreads
{
    public function createThread($params,$userId,$threadId)
    {
        $this->forumid = isset($params['forumid']) ? $params['forumid'] : null;
        $this->id = $threadId;
        $postdate = AppController::dateToString();
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
        $poststucidlist = implode(',',$poststucidlist);
        $query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
        $query .= "AND imas_forums.courseid IN ($poststucidlist) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid= :userId ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid= :userId)) ";
        $query .= "ORDER BY imas_forum_threads.lastposttime DESC";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue('userId',$userid);
        return $data->queryAll();
    }

    public static function getPostThread($poststucidlist, $now, $userid)
    {
        $poststucidlist = implode(',',$poststucidlist);
        $query = "SELECT imas_forums.courseid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
        $query .= "AND imas_forums.courseid IN ($poststucidlist) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid= :userId ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid= :userId)) ";
        $query .= "GROUP BY imas_forums.courseid";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue('userId',$userid);
        return $data->queryAll();
    }

    public static function getDataByUserId($teacherid, $cid, $userid, $now)
    {
        $query = "SELECT imas_forum_threads.forumid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forums.courseid=':courseId' ";
        if (!($teacherid)) {
            $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
        }
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=':userId' ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        if (!($teacherid)) {
            $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
        }
        $query .= "GROUP BY imas_forum_threads.forumid";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue('courseId',$cid);
        return $data->queryAll();
    }

    public static function getNewPost($postcidlist, $userid)
    {
        $query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,imas_forums.courseid FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND imas_forums.courseid IN ($postcidlist) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=':userId' ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "ORDER BY imas_forum_threads.lastposttime DESC";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue('userId',$userid);
        return $data->queryAll();
    }

    public static function getPostData($postcidlist, $userid)
    {
        $query = "SELECT imas_forums.courseid, COUNT(imas_forum_threads.id) FROM imas_forum_threads ";
        $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
        $query .= "AND imas_forums.courseid IN ($postcidlist) ";
        $query .= "LEFT JOIN imas_forum_views as mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=':userId' ";
        $query .= "WHERE (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
        $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
        $query .= "GROUP BY imas_forums.courseid";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValue('userId',$userid);
        return $data->queryAll();
    }
}