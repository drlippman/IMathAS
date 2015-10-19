<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumPosts;
use Yii;
use app\controllers\AppController;
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

    public static function modifyPost($params,$fileName)
    {
        $threadPost = ForumPosts::findOne(['id' => $params['threadId']]);
        $threadPost->subject = trim($params['subject']);
        $threadPost->message = $params['message'];

            if($params['always-replies'] == AppConstant::NUMERIC_THREE)
            {
                $replyBy = AppUtility::parsedatetime($params['startDate'], $params['startTime']);
                $threadPost->replyby = $replyBy;
            }
            else if($params['always-replies'] == AppConstant::NUMERIC_ONE)
            {
                $threadPost->replyby = 'null';
            }
            else
            {
                $replyBy = $params['always-replies'];
            }
            $isANonValue = AppConstant::NUMERIC_ZERO;
            if($params['post-anonymously'])
            {
                $isANonValue = $params['post-anonymously'];
            }
            $threadPost->isanon = $isANonValue;
            $threadPost->replyby = $replyBy;
            $threadPost->posttype = $params['post-type'];
            $threadPost->files = $fileName;
            $threadPost->save();
            return $threadPost->threadid;
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

        $ForumPosts = ForumPosts::findAll(['threadid' => $threadId]);
        foreach($ForumPosts as $ForumPost){
        $ForumPost->forumid = $forumId;
        $ForumPost->save();
        }
    }

    public function createReply($params, $user,$fileName)
    {
        $this->threadid = isset($params['threadId']) ? $params['threadId'] : null;
        $this->forumid = isset($params['forumid']) ? $params['forumid'] : null;
        $this->subject = isset($params['Subject']) ? $params['Subject'] : null;
        $this->userid = isset($user->id) ? $user->id : null;
        $this->parent = $params['parentId'];
        $this->message = isset($params['post-reply']) ? $params['post-reply'] : null;
        $postdate = AppController::dateToString();
        $this->postdate = $postdate;
        $this->files = $fileName;
        $this->save();
    }

    public function createThread($params, $userId, $postType, $alwaysReplies, $date, $isNonValue,$fileName=null)
    {
        $maxid = $this->find()->max('id');
        $maxid = $maxid + AppConstant::NUMERIC_ONE;
        $this->id = $maxid;
        $this->forumid = isset($params['forumid']) ? $params['forumid'] : null;
        $this->threadid = isset($maxid) ? $maxid : null;
        if (empty($params['subject']))
        {
            $params['subject'] = '(None)';
        }
        $this->subject = trim($params['subject']);
        $this->userid = isset($userId) ? $userId : null;
        $this->message = isset($params['message']) ? $params['message'] : null;
            $postdate = AppController::dateToString();
            $this->postdate = $postdate;
            $this->posttype = $postType;
            if ($alwaysReplies == AppConstant::NUMERIC_ONE)
            {
                $this->replyby = AppConstant::ALWAYS_TIME;
            }
            elseif ($alwaysReplies == AppConstant::NUMERIC_TWO)
            {
                $this->replyby = AppConstant::NUMERIC_ZERO;
            }
            elseif ($alwaysReplies == AppConstant::NUMERIC_THREE)
            {
                $this->replyby = $date;
            }
            else
            {
                $this->replyby = null;
            }
        $this->isanon = $isNonValue;
        $this->files = $fileName;
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
            ->where('parent=:threadId',[':threadId' => $threadId]);
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
        return ForumPosts::find()->select('id')->where(['forumid' => $itemId])->andWhere(['<>','files',''])->all();
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
        return $entry;
    }

    public static function getByForumId($forumId){
        return ForumPosts::find()->where(['forumid' => $forumId])->andWhere(['>','posttype', AppConstant::NUMERIC_ZERO])->all();

    }

    public static function setThreadIdById($id){
        $threadData = ForumPosts::findOne(['id'=> $id]);
        if($threadData){
            $threadData->threadid = $id;
            $threadData->save();
        }
    }

    public function savePost($forumPostArray){
        $this->forumid = $forumPostArray['forumid'];
        $this->userid = $forumPostArray['userid'];
        $this->parent = $forumPostArray['parent'];
        $this->postdate = $forumPostArray['postdate'];
        $this->subject = $forumPostArray['subject'];
        $this->message = $forumPostArray['message'];
        $this->posttype = $forumPostArray['posttype'];
        $this->isanon = $forumPostArray['isanon'];
        $this->replyby = $forumPostArray['replyby'];
        $this->save();
        return $this->id;
    }
    public static function MarkAllRead($forumId,$dofilter = null,$limthreads = null)
    {
          $query = new Query();
          $query ->select(['DISTINCT(threadid)'])
                    ->from('imas_forum_posts ')
                    ->where('forumid= :forumid',[':forumid' => $forumId]);
        if ($dofilter)
        {
            $query .= " AND threadid IN ($limthreads)";
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function findByForumId($forumId){
        return ForumPosts::find()->where(['forumid' => $forumId])->all();
    }
    public static function deleteForumRelatedToCurse($forumlist){
        $query = ForumPosts::find()->join('INNER JOIN', 'imas_forum_threads', 'imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.posttype=0')->where(['IN', 'imas_forum_threads.forumid', $forumlist])->all();
        if($query){
            foreach($query as $forums){
                ForumThread::removeThread($forums['threadid']);
            }
        }
    }
    public static function selectForumPosts($forumlist){
        return ForumPosts::find()->select('id')->where(['IN','forumid',$forumlist])->andWhere(['<>','files',''])->all();
    }

    public static function deleteForumPostByForumList($forumlist)
    {
        $query = ForumPosts::find()->where(['IN', 'forumid', $forumlist])->andWhere(['posttype' => AppConstant::NUMERIC_ZERO])->all();
        if($query){
            foreach($query as $object){
                $object->delete();
            }
        }
    }

    public static function deleteForumPosts($delList)
    {
        $data = ForumPosts::find()->where(['IN','threadid', $delList])->all();
        if($data){
            foreach($data as $singleData){
                $singleData->delete();
            }
        }
    }

    public static function checkLeastOneThread($forumId,$userId)
    {
        return ForumPosts::find()->select('id')->where(['forumid' => $forumId,'parent' => AppConstant::NUMERIC_ZERO, 'userid' => $userId])->limit(1)->one();
    }

    public static function getByForumPostId($forumId)
    {
        $query = new Query();
        $query ->select(['id'])
            ->from('imas_forum_posts ')
            ->where(['forumid' => $forumId]);
        $query->andWhere(['<>', 'files', '" "']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getFileDetails($modifyId)
    {
        return self::find()->select(['files'])->where(['id' => $modifyId])->one();
    }

    public static function getbyForumIdAndUserID($forumid, $currentUserId)
    {
        $ForumPost = ForumPosts::findAll(['forumid' => $forumid, 'userid' => $currentUserId]);
        return $ForumPost;
    }

    public static function getPostData($threadlist)
    {
        $query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
        $query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.id IN ($threadlist)";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getByRefIds($refids)
    {
        return ForumPosts::find()->select(['id,userid'])->where(['IN','id', $refids])->all();
    }

    public static function getThreadId($limthreads,$dofilter,$tagfilter)
    {
        $query = "SELECT threadid FROM imas_forum_posts WHERE tag='".addslashes($tagfilter)."'";
        if ($dofilter)
{
            $query .= " AND threadid IN ($limthreads)";
        }
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getBySearchText($isTeacher,$now,$courseId,$searchlikes,$searchlikes2,$searchlikes3,$forumId,$limthreads,$dofilter,$params)
    {

        if (isset($params['allforums']))
        {
            $query = "SELECT imas_forums.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.isanon FROM imas_forum_posts,imas_forums,imas_users ";
            $query .= "WHERE imas_forum_posts.forumid=imas_forums.id ";
            if (!$isTeacher) {
                $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate < $now AND imas_forums.enddate>$now)) ";
            }
            $query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid= :courseId AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
        } else {
            $query = "SELECT imas_forum_posts.forumid,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate ";
            $query .= "FROM imas_forum_posts,imas_users WHERE imas_forum_posts.forumid= :forumId AND imas_users.id=imas_forum_posts.userid AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
        }
        if ($dofilter) {
            $query .= " AND imas_forum_posts.threadid IN ($limthreads)";
        }
        $query .= " ORDER BY imas_forum_posts.postdate DESC";
        $data = Yii::$app->db->createCommand($query);
        $data->bindValues(['forumId'=> $forumId, 'courseId' => $courseId]);
        return $data->queryAll();
    }

    public static function getMaxPostDate($dofilter,$limthreads,$forumId)
    {
        $query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
        $query .= "WHERE forumid= :forumId ";
        if ($dofilter)
        {
            $query .= " AND threadid IN ($limthreads)";
        }
        $query .= "GROUP BY threadid";
        $data = Yii::$app->db->createCommand($query);
        $data->bindValue('forumId', $forumId);
        return $data->queryAll();
    }

    public static function getForumPostId($forumId,$limthreads,$dofilter)
    {
        $query = "SELECT COUNT(id) FROM imas_forum_posts WHERE parent=0 AND forumid= :forumId";
        if ($dofilter)
        {
        $query .= " AND threadid IN ($limthreads)";
        }
        $data = Yii::$app->db->createCommand($query);
        $data->bindValue('forumId', $forumId);
        return $data->queryAll();
    }

    public static function getPostIds($forumId,$dofilter,$page,$limthreads,$newpostlist,$flaggedlist)
    {
        $query = "SELECT imas_forum_posts.id,count(imas_forum_views.userid) FROM imas_forum_views,imas_forum_posts ";
        $query .= "WHERE imas_forum_views.threadid=imas_forum_posts.id AND imas_forum_posts.parent=0 AND ";
        $query .= "imas_forum_posts.forumid= :forumId ";
        if ($dofilter)
        {
            $query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
        }
        if ($page==-1)
        {
            $query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
        } else if ($page==-2)
        {
            $query .= "AND imas_forum_posts.threadid IN ($flaggedlist) ";
        }
        $query .= "GROUP BY imas_forum_posts.id";
        $data = Yii::$app->db->createCommand($query);
        $data->bindValue('forumId', $forumId);
        return $data->queryAll();
    }

    public static function getPostDataForThread($forumId,$dofilter,$page,$limthreads,$newpostlist,$flaggedlist,$sortby,$threadsperpage)
    {
        $query = "SELECT imas_forum_posts.*,imas_forum_threads.views as tviews,imas_users.LastName,imas_users.FirstName,imas_forum_threads.stugroupid FROM imas_forum_posts,imas_users,imas_forum_threads WHERE ";
        $query .= "imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.parent=0 AND imas_forum_posts.forumid= :forumId ";

        if ($dofilter) {
            $query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
        }
        if ($page==-1) {
            $query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
        } else if ($page==-2) {
            $query .= "AND imas_forum_posts.threadid IN ($flaggedlist) ";
        }
        if ($sortby==0) {
            $query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_posts.id DESC ";
        } else if ($sortby==1) {
            $query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_threads.lastposttime DESC ";
        }
        $offset = ($page-1)*$threadsperpage;
        if ($page>0) {
            $query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset";
        }
        $data = Yii::$app->db->createCommand($query);
        $data->bindValue('forumId', $forumId);
        return $data->queryAll();
    }
}