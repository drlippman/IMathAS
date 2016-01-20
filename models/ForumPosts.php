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

    public static function getByIdOne($threadId)
    {
        $ForumPost = ForumPosts::findOne(['threadid' => $threadId]);
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
        $this->isanon = $params['postanon'];
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
        if (count($limthreads) == 0) {
            $limthreads = '0';
        } else {
            $limthreads = implode(',', $limthreads);
        }

          $query = new Query();
          $query ->select(['DISTINCT(threadid)'])
                    ->from('imas_forum_posts ')
                    ->where('forumid= :forumid',[':forumid' => $forumId]);
        if ($dofilter)
        {
            $query->andWhere('IN', 'threadid', $limthreads);
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
        return ForumPosts::find()->select('id')->where(['forumid' => $forumId])->andWhere(['<>', 'files', '" "'])->all();
    }

    public static function getFileDetails($modifyId)
    {
        $file = ForumPosts::find()->select(['files'])->where(['id' => $modifyId])->one();
        return $file;
    }

    public static function getbyForumIdAndUserID($forumid, $currentUserId)
    {
        $ForumPost = ForumPosts::findAll(['forumid' => $forumid, 'userid' => $currentUserId]);
        return $ForumPost;
    }

    public static function getPostData($threadlist)
    {
        $query = new Query();
        $query->select('imas_forum_posts.*,imas_users.LastName,imas_users.FirstName')
            ->from('imas_forum_posts,imas_users')
            ->where('imas_forum_posts.userid=imas_users.id')
            ->andWhere(['IN','imas_forum_posts.id',$threadlist]);
        return $query->createCommand()->queryAll();
    }

    public static function getByRefIds($refids)
    {
        return ForumPosts::find()->select(['id','userid'])->where(['IN','id', $refids])->all();
    }

    public static function getThreadId($limthreads,$dofilter,$tagfilter)
    {
        $query = new Query();
        $query->select('threadid')->from('imas_forum_posts')->where(['LIKE','tag',$tagfilter]);
        if ($dofilter)
        {
            $query->andWhere(['IN','threadid',$limthreads]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getBySearchText($isTeacher,$now,$courseId,$searchlikes,$searchlikes2,$searchlikes3,$forumId,$limthreads,$dofilter,$params)
    {
        $placeholders= "";
        if($limthreads)
        {
            foreach($limthreads as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }

        if (isset($params['allforums']))
        {
            $query = "SELECT imas_forums.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.isanon FROM imas_forum_posts,imas_forums,imas_users ";
            $query .= "WHERE imas_forum_posts.forumid=imas_forums.id ";
            if (!$isTeacher) {
                $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate < :now AND imas_forums.enddate>:now)) ";
            }
            $query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid=:courseId AND (imas_forum_posts.message LIKE :searchlikes OR imas_forum_posts.subject LIKE :searchlikes2 OR imas_users.LastName LIKE :searchlikes3)";
        } else {
            $query = "SELECT imas_forum_posts.forumid,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate ";
            $query .= "FROM imas_forum_posts,imas_users WHERE imas_forum_posts.forumid=:forumId AND imas_users.id=imas_forum_posts.userid AND (imas_forum_posts.message LIKE :searchlikes OR imas_forum_posts.subject LIKE :searchlikes2 OR imas_users.LastName LIKE :searchlikes3)";
        }
        if ($dofilter) {
            $query .= " AND imas_forum_posts.threadid IN ($placeholders)";
        }
        $query .= " ORDER BY imas_forum_posts.postdate DESC";
        $command = Yii::$app->db->createCommand($query);
        if (isset($params['allforums']))
        {
            if (!$isTeacher) {
                $command->bindValue(':now',$now);
            }
            $command->bindValues([':courseId'=> $courseId, ':searchlikes' => "%".$searchlikes."%", ':searchlikes2' => "%".$searchlikes2."%", ':searchlikes3' => "%".$searchlikes3."%"]);
        } else{
            $command->bindValues([':forumId' => $forumId, ':searchlikes' => "%".$searchlikes."%", ':searchlikes2' => "%".$searchlikes2."%", ':searchlikes3' => "%".$searchlikes3."%"]);
        }
        if ($dofilter) {
            foreach($limthreads as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        }

        $data = $command->queryAll();
        return $data;
    }

    public static function getMaxPostDate($dofilter,$limthreads,$forumId)
    {
        $query = new Query();
        $query->select('threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate')
            ->from('imas_forum_posts')->where('forumid = :forumId');
        if ($dofilter)
        {
            $query->andWhere(['IN','threadid',$limthreads]);
        }
        $query->groupBy('threadid');
        return $query->createCommand()->bindValues([':forumId' => $forumId])->queryAll();
    }

    public static function getForumPostId($forumId,$limthreads,$dofilter)
    {
        $query = new Query();
        $query->select('COUNT(id)')->from('imas_forum_posts')->where('parent=0')
        ->andWhere('forumid= :forumId');
        if ($dofilter)
        {
        $query->andWhere(['IN','threadid',$limthreads]);
        }
        $data = $query->createCommand();
        $data->bindValue('forumId', $forumId);
        return $data->queryAll();
    }

    public static function getPostIds($forumId,$dofilter,$page,$limthreads,$newpostlist,$flaggedlist)
    {
        $query = new Query();
        $query->select('imas_forum_posts.id,count(imas_forum_views.userid)')
            ->from('imas_forum_views,imas_forum_posts')
            ->where('imas_forum_views.threadid=imas_forum_posts.id')
            ->andWhere('imas_forum_posts.parent=0')
            ->andWhere('imas_forum_posts.forumid= :forumId');

        if ($dofilter)
        {
            $query->andWhere(['IN','imas_forum_posts.threadid',$limthreads]);
        }
        if ($page==-1)
        {
            $query->andWhere(['IN','imas_forum_posts.threadid',$newpostlist]);
        } else if ($page==-2)
        {
            $query->andWhere(['IN','imas_forum_posts.threadid',$flaggedlist]);
        }
        $query->groupBy('imas_forum_posts.id');
        $data = $query->createCommand();
        $data->bindValue('forumId', $forumId);

        return $data->queryAll();
    }

    public static function getPostDataForThread($forumId,$dofilter,$page,$limthreads,$newpostlist,$flaggedlist,$sortby,$threadsperpage)
    {
        $placeholders= "";
        if(!empty($limthreads))
        {
            foreach($limthreads as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }

        $placePostList= "";
        if($newpostlist)
        {
            foreach($newpostlist as $i => $singleThread){
                $placePostList .= ":".$i.", ";
            }
            $placePostList = trim(trim(trim($placePostList),","));
        }

        $placeFlaggedList= "";
        if($flaggedlist)
        {
            foreach($flaggedlist as $i => $singleThread){
                $placeFlaggedList .= ":".$i.", ";
            }
            $placeFlaggedList = trim(trim(trim($placeFlaggedList),","));
        }

        $query = "SELECT imas_forum_posts.*,imas_forum_threads.views as tviews,imas_users.LastName,imas_users.FirstName,imas_forum_threads.stugroupid
                    FROM imas_forum_posts,imas_users,imas_forum_threads WHERE ";
        $query .= "imas_forum_posts.userid=imas_users.id
                    AND imas_forum_posts.threadid=imas_forum_threads.id
                    AND imas_forum_posts.parent=0 AND imas_forum_posts.forumid=:forumId ";

        if ($dofilter) {
            $query .= "AND imas_forum_posts.threadid IN ($placeholders) ";
        }
        if ($page==-1) {
            $query .= "AND imas_forum_posts.threadid IN ($placePostList) ";
        } else if ($page==-2) {
            $query .= "AND imas_forum_posts.threadid IN ($placeFlaggedList) ";
        }
        if ($sortby==0) {
            $query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_posts.id DESC ";
        } else if ($sortby==1) {
            $query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_threads.lastposttime DESC ";
        }
        $offset = ($page-1) * $threadsperpage;

        if ($page>0) {
            $query .= "LIMIT :offset,:threadsperpage";// OFFSET $offset";
        }
        $command = Yii::$app->db->createCommand($query)->bindValues([':forumId'=> $forumId]);

        if ($dofilter) {
            foreach($limthreads as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        }
        if ($page==-1) {
            foreach($newpostlist as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        }else if ($page==-2) {
            foreach($flaggedlist as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        }
        if ($page>0) {
            $command->bindValues([':offset' => $offset, ':threadsperpage' => $threadsperpage]);
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function getPosts($userId,$forumId,$limthreads,$dofilter)
    {
        /**
         * limit thread count should not be empty.
         * $placeholders, it gives placeholders.
         * In foreach we have bind it.
         */
        $placeholders= "";
        if($limthreads)
        {
            foreach($limthreads as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }

        $query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,ifv.lastview from imas_forum_posts JOIN imas_users ";
        $query .= "ON imas_forum_posts.userid=imas_users.id LEFT JOIN (SELECT DISTINCT threadid,lastview FROM imas_forum_views WHERE userid=:userId) AS ifv ON ";
        $query .= "ifv.threadid=imas_forum_posts.threadid WHERE imas_forum_posts.forumid=:forumId AND imas_forum_posts.isanon=0 ";
        if ($dofilter)
        {
            $query .= "AND imas_forum_posts.threadid IN ($placeholders) ";
        }
        $query .= "ORDER BY imas_users.LastName,imas_users.FirstName,imas_forum_posts.postdate DESC";
        $command = Yii::$app->db->createCommand($query);
        $command->bindValues([':userId' => $userId, ':forumId' => $forumId]);
        if ($dofilter)
        {
            foreach($limthreads as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function threadCount($cid)
    {
        $query = new Query();
        $query->select('imas_forums.id,COUNT(imas_forum_posts.id)')->from('imas_forums')->join('LEFT JOIN','imas_forum_posts','imas_forums.id=imas_forum_posts.forumid')
            ->where(['imas_forum_posts.parent' => '0'])->andWhere('imas_forums.courseid = :cid')->groupBy('imas_forum_posts.forumid')->orderBy('imas_forums.id');
        return $query->createCommand()->bindValue('cid',$cid)->queryAll();
    }
    public static function getBySearchTextForForum($isteacher, $now, $cid, $searchterms, $searchlikes2, $searchlikes3,$anyforumsgroup,$searchstr,$searchtag,$userid)
    {
        //        TODO: fix below query
        if(!empty($searchterms) && is_array($searchterms)){
            $searchTermsMessage = "";
            $searchTermsSubject = "";
            $searchTermsLastName = "";
            foreach($searchterms as $index => $singleTerm){
                $searchTermsMessage .= " AND imas_forum_posts.message LIKE :searchTerm".$index;
                $searchTermsSubject .= " AND imas_forum_posts.subject LIKE :searchTerm".$index;
                $searchTermsLastName .= " AND imas_users.LastName LIKE :searchTerm".$index;
            }
            $searchTermsMessage = "(".trim(trim(trim($searchTermsMessage),'AND')).")";
            $searchTermsSubject = "(".trim(trim(trim($searchTermsSubject),'AND')).")";
            $searchTermsLastName = "(".trim(trim(trim($searchTermsLastName),'AND')).")";
        }

        $query = "SELECT imas_forums.id AS forumid,imas_forum_posts.posttype,imas_forum_posts.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.files,imas_forum_posts.isanon ";
        $query .= "FROM imas_forum_posts JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id ";
        $query .= "JOIN imas_users ON imas_users.id=imas_forum_posts.userid ";
        if ($anyforumsgroup && !$isteacher) {
            $query .= "JOIN imas_forum_threads ON imas_forum_threads.id=imas_forum_posts.threadid ";
        }
        $query .= "WHERE imas_forums.courseid=:courseId ";
        if ($searchstr != '') {
            $query .= "AND ($searchTermsMessage OR $searchTermsSubject OR $searchTermsLastName) ";
        }
        if ($searchtag != '') {
            $query .= "AND imas_forum_posts.tag=:searchtag ";
        }
        if (!$isteacher) {
            $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<:now AND imas_forums.enddate>:now)) ";
        }
        if ($anyforumsgroup && !$isteacher) {
            $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userId)) ";
        }
        $query .= " ORDER BY imas_forum_posts.postdate DESC";

        $command = Yii::$app->db->createCommand($query);

        $command->bindValue(':courseId',$cid);
        if ($searchstr != '') {
            if(!empty($searchterms) && is_array($searchterms)){

                foreach($searchterms as $index => $singleTerm){
                    $command->bindValue(":searchTerm".$index , "%".$singleTerm."%");
                }
            }
        }
        if ($searchtag != '') {
            $command->bindValue(':searchtag',$searchtag);
        }
        if (!$isteacher) {
            $command->bindValue(':now', $now);
        }
        if ($anyforumsgroup && !$isteacher) {
            $command->bindValue(':userId', $userid);
        }
        return $command->queryAll();
    }

    public static function getBySearchTextForThread($isteacher, $now, $cid, $searchlikes, $anyforumsgroup,$searchstr,$searchtag,$userid)
    {
        $query = "SELECT imas_forums.id AS forumid,imas_forum_posts.posttype,imas_forum_posts.id,imas_forum_posts.subject,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name,imas_forum_posts.files,imas_forum_threads.views,imas_forum_posts.tag,imas_forum_posts.isanon,imas_forum_views.tagged ";
        $query .= "FROM imas_forum_posts JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id ";
        $query .= "JOIN imas_users ON imas_users.id=imas_forum_posts.userid ";
        $query .= "JOIN imas_forum_threads ON imas_forum_threads.id=imas_forum_posts.threadid ";
        $query .= "LEFT JOIN imas_forum_views ON imas_forum_threads.id=imas_forum_views.threadid AND imas_forum_views.userid=:userid ";

        $query .= "WHERE imas_forums.courseid=:cid AND imas_forum_posts.id=imas_forum_posts.threadid "; //these are indexed fields, but parent is not
        if ($searchstr != '') {
            $query .= "AND :searchlikes ";
        }
        if ($searchtag != '') {
            $query .= "AND imas_forum_posts.tag=:searchtag ";
        }
        if (!$isteacher) {
            $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<:now AND imas_forums.enddate>:now)) ";
        }
        if ($anyforumsgroup && !$isteacher) {
            $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid)) ";
        }
        $query .= " ORDER BY imas_forum_threads.lastposttime DESC";
        $command = Yii::$app->db->createCommand($query);
        $command->bindValues([':cid'=> $cid, ':userid' => $userid]);
        if ($searchstr != '') {
            $command->bindValue(':searchlikes', $searchlikes);
        }
        if ($searchtag != '') {
            $command->bindValue(':searchtag',$searchtag);
        }
        if (!$isteacher) {
            $command->bindValue(':now', $now);
        }
        if ($anyforumsgroup && !$isteacher) {
            $command->bindValue(':userid', $userid);
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function getMaxPostDateWithThreadId($limthreads)
    {
        $query = new Query();
        $query->select('threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate')
            ->from('imas_forum_posts')
            ->where(['IN','threadid',$limthreads])
            ->groupBy('threadid');
        return $query->createCommand()->queryAll();
    }

    public static function getDataByJoin($subject, $forumId, $groupsetid, $isteacher, $groupid)
    {
        $query = new Query();
        $query->select('ift.id')
            ->from('imas_forum_posts AS ifp')
            ->join('INNER JOIN', 'imas_forum_threads AS ift', 'ifp.threadid=ift.id AND ifp.parent=0')
            ->where('ifp.subject=:subject', [':subject' => $subject])
            ->andWhere('ift.forumid=:forumId', [':forumId' => $forumId]);

        if ($groupsetid >0 && !$isteacher) {
            $query->andWhere('ift.stugroupid=:groupid', [':groupid' => $groupid]);
        }
        return $query->createCommand()->queryAll();
    }

    public static function getDataById($threadid)
    {
        return ForumPosts::find()->select('posttype')->where(['id' => $threadid])->one();
    }

    public static function getPostPoints($courseId, $threadId)
    {
        $query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_grades.score,imas_grades.feedback,imas_students.section FROM ";
        $query .= "imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id ";
        $query .= "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid=:courseId ";
        $query .= "LEFT JOIN imas_grades ON imas_grades.gradetype='forum' AND imas_grades.refid=imas_forum_posts.id ";
        $query .= "WHERE (imas_forum_posts.id=:threadId OR imas_forum_posts.threadid=:threadid) ORDER BY imas_forum_posts.id";
        $data = Yii::$app->db->createCommand($query)->bindValues([':courseId' => $courseId, ':threadId' => $threadId, ':threadid' => $threadId])->queryAll();
        return $data;
    }

    public static function getForumPost($courseId, $threadId)
    {
        $query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,imas_students.section FROM ";
        $query .= "imas_forum_posts JOIN imas_users ON imas_forum_posts.userid=imas_users.id ";
        $query .= "LEFT JOIN imas_students ON imas_students.userid=imas_forum_posts.userid AND imas_students.courseid=:courseId ";
        $query .= "WHERE (imas_forum_posts.id=:threadId OR imas_forum_posts.threadid=:threadid) ORDER BY imas_forum_posts.id";
        $command = Yii::$app->db->createCommand($query)->bindValues([':courseId' => $courseId, ':threadId' => $threadId, ':threadid' => $threadId]);
        $data = $command->queryAll();
        return $data;
    }

    public static function updateViews($threadId, $newviews)
    {
        $id = ForumPosts::find()->where(['id' => $threadId])->one();
        if($id)
        {
            $id->views = $newviews;
            $id->save();
            return $id;
        }
    }

    public static function getLikePost($postId,$courseId)
    {
        $query = "SELECT imas_forums.id
                    FROM imas_forums JOIN imas_forum_posts
                    ON imas_forums.id=imas_forum_posts.forumid ";
        $query .= " WHERE imas_forum_posts.id=:postId AND imas_forums.courseid=:courseId";
        $command = Yii::$app->db->createCommand($query)->bindValues([':postId' => $postId,':courseId' => $courseId]);
        $data = $command->queryAll();
        return $data;
    }

    public static function getByThreadId($postId){
       return ForumPosts::find('threadid')->where(['id' => $postId])->all();
    }
}