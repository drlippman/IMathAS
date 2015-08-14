<?php
namespace app\controllers\forum;

use app\components\AppConstant;
use app\components\AssessmentUtility;
use app\models\ContentTrack;
use app\models\Course;
use app\models\ExternalTools;
use app\models\forms\ChangeUserInfoForm;
use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\forms\ThreadForm;
use app\models\ForumLike;
use app\models\ForumPosts;
use app\models\ForumSubscriptions;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\Forums;
use app\models\GbCats;
use app\models\Items;
use app\models\LinkedText;
use app\models\Outcomes;
use app\models\Rubrics;
use app\models\StuGroupSet;
use app\models\Thread;
use app\models\User;
use app\components\htmLawed;
use yii\web\UploadedFile;
use app\components\AppUtility;
use Yii;

class ForumController extends AppController
{
    public $postData = array();
    public $totalPosts = array();
    public $children = array();
    public $threadLevel = 1;

    /*
    * Controller Action To Redirect To Search Forum Page
    */
    public function actionSearchForum()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $countPost = $this->getNotificationDataForum($cid,$user);
        $msgList = $this->getNotificationDataMessage($cid,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $forum = Forums::getByCourseId($cid);
        $course = Course::getById($cid);
        $user = $this->getAuthenticatedUser();
        $model = new ForumForm();
        $model->thread = 'subject';
        $this->includeCSS(['dataTables.bootstrap.css', 'forums.css', 'dashboard.css']);
        $this->includeJS(['forum/forum.js', 'general.js?ver=012115', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        $this->setReferrer();
        $this->includeCSS(['course/course.css']);
        $responseData = array('model' => $model, 'forum' => $forum, 'cid' => $cid, 'users' => $user, 'course' => $course);
        return $this->renderWithData('forum', $responseData);
    }

    /*
    * Controller Action To Search All threads By Subject
    */
    public function actionGetForumNameAjax()
    {
        $this->guestUserHandler();
        $param = $this->getRequestParams();
        $userId = $this->getAuthenticatedUser()->id;
        $search = $param['search'];
        $courseId = $param['courseId'];
        $query = ForumForm::byAllSubject($search, $courseId, $userId);
        if ($query) {
            $searchThread = array();
            foreach ($query as $data) {
                $username = User::getById($data['userid']);
                $postdate = Thread::getById($data['threadid']);
                $repliesCount = ForumPosts::findCount($data['threadid']);
                $tempArray = array
                (
                    'parent' => $data['parent'],
                    'forumIdData' => $data['forumid'],
                    'threadId' => $data['threadid'],
                    'subject' => $data['subject'],
                    'views' => $data['views'],
                    'replyBy' => $repliesCount[0]['count'],
                    'postdate' => date('F d, o g:i a', $postdate->lastposttime),
                    'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),
                );
                array_push($searchThread, $tempArray);
            }
            $this->includeJS(['forum/forum.js','forum/thread.js']);
            return $this->successResponse($searchThread);

        } else {
            return $this->terminateResponse("No data Found");
        }
    }

    /*
    * Controller Action To Display All The Forums
    */
    public function actionGetForumsAjax()
    {
        $this->guestUserHandler();
        $currentTime = time();
        $param = $this->getRequestParams();
        $cid = $param['cid'];
        $sort = AppConstant::DESCENDING;
        $orderBy = 'id';
        $forums = Forums::getByCourseIdOrdered($cid, $sort, $orderBy);
        $user = $this->getAuthenticatedUser();
        $NewPostCounts = Thread::findNewPostCnt($cid,$user);
        if ($forums)
        {
           $forumArray = array();
            foreach ($forums as $key => $forum)
            {
                $threadCount = ForumThread::findThreadCount($forum['id']);
                $postCount = count($forum->imasForumPosts);
                $lastObject = '';
                if ($postCount > AppConstant::NUMERIC_ZERO) {
                    $lastObject = $forum->imasForumPosts[$postCount - AppConstant::NUMERIC_ONE];
                }
                $flag = 0;
                foreach($NewPostCounts as $count)
                {
                    if($count['forumid'] == $forum['id'] ){
                        $tempArray = array
                        (
                            'forumId' => $forum['id'],
                            'forumName' => $forum['name'],
                            'threads' => count($threadCount),
                            'posts' => $postCount,
                            'currentTime' => $currentTime,
                            'endDate' => $forum['enddate'],
                            'rights' => $user['rights'],
                            'avail' => $forum['avail'],
                            'startDate' => $forum['startdate'],
                            'countId' => $count['forumid'],
                            'count' =>$count['COUNT(imas_forum_threads.id)'],
                            'lastPostDate' => ($lastObject != '') ? date('F d, o g:i a', $lastObject->postdate) : '',
                        );
                        $flag = 1;
                        array_push($forumArray, $tempArray);
                    }

             }
             if($flag == 0){

                $tempArray = array
                (
                    'forumId' => $forum['id'],
                    'forumName' => $forum['name'],
                    'threads' => count($threadCount),
                    'posts' => $postCount,
                    'currentTime' => $currentTime,
                    'endDate' => $forum['enddate'],
                    'rights' => $user['rights'],
                    'avail' => $forum['avail'],
                    'startDate' => $forum['startdate'],
                    'countId' => AppConstant::NUMERIC_ZERO,
                    'lastPostDate' => ($lastObject != '') ? date('F d, o g:i a', $lastObject->postdate) : '',
                );
                 array_push($forumArray, $tempArray);
            }
        }
            $this->includeCSS(['forums.css']);
            $this->includeJS(['forum/forum.js']);
            return $this->successResponse($forumArray);

        }
        else
        {
            return $this->terminateResponse('No data');
        }
    }

    /*
     * Controller Action To Redirect To Thread Page
     */
    public function actionThread()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $unRead = $this->getParamVal('unread');
        $forumId = $this->getParamVal('forumid');
        $page= $this->getParamVal('page');
        $forumData = Forums::getById($forumId);
        $users = $this->getAuthenticatedUser();
        $this->setReferrer();
        $this->includeCSS(['dataTables.bootstrap.css', 'forums.css', 'dashboard.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js?ver=012115', 'forum/thread.js?ver=' . time() . '']);
        $responseData = array('cid' => $cid, 'users' => $users, 'forumid' => $forumId, 'course' => $course,'forumData' => $forumData,'page' => $page,'unRead' => $unRead);
        return $this->renderWithData('thread', $responseData);
    }

    /*
    * Controller Action To Display The Threads Present In That Particular Forum
    */
    public function actionGetThreadAjax()
    {
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $isValue = $params['isValue'];
        $forumId = $params['forumid'];
        $hideLink = $params['hideLink'];
        $threads = ThreadForm::thread($forumId);
        $forumData = Forums::getById($forumId);
        $threadArray = array();
        $uniquesDataArray = array();
        if (!empty($threads)) {
            if ($isValue == AppConstant::NUMERIC_ONE) {
                foreach ($threads as $thread) {
                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getByThreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser, $thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $tagged = ForumView::forumViews($thread['threadid'], $currentUser['id']);
                    $isReplies = AppConstant::NUMERIC_ZERO;
                    $threadReplies = ForumPosts::isThreadHaveReply($thread['id']);
                    if($threadReplies)
                    {
                        $isReplies = AppConstant::NUMERIC_ONE;
                    }
                    $views  = Thread::getByForumIdAndId($forumId,$thread['threadid']);
                    if ($tagged[0]['tagged'] == AppConstant::NUMERIC_ONE) {
                        $temparray = array
                        (
                            'parent' => $thread['parent'],
                            'threadId' => $thread['threadid'],
                            'forumiddata' => $thread['forumid'],
                            'subject' => $thread['subject'],
                            'views' => $views,
                            'replyby' => $thread['replyby'],
                            'postdate' => date('F d, o g:i a', $thread['postdate']),
                            'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                            'tagged' => $tagged[0]['tagged'],
                            'lastview' => date('F d, o g:i a', $lastView[0]['lastview']),
                            'userright' => $currentUser['rights'],
                            'postUserId' => $username->id,
                            'currentUserId' => $currentUser['id'],
                            'countArray' => $count,
                            'posttype' => $thread['posttype'],
                            'isReplies' => $isReplies,
                            'isanon' => $thread['isanon'],
                            'groupSetId' => $forumData['groupsetid'],
                        );
                        array_push($threadArray, $temparray);
                        array_push($uniquesDataArray, $uniquesData);
                    }
                }
            } else if ($isValue == AppConstant::NUMERIC_TWO || $isValue == AppConstant::NUMERIC_THREE) {
                foreach ($threads as $thread) {
                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getByThreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser, $thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $tagged = ForumView::forumViews($thread['threadid'], $currentUser['id']);
                    $views  = Thread::getByForumIdAndId($forumId,$thread['threadid']);
                    $isReplies = AppConstant::NUMERIC_ZERO;
                    $threadReplies = ForumPosts::isThreadHaveReply($thread['id']);
                    if($threadReplies)
                    {
                        $isReplies = AppConstant::NUMERIC_ONE;
                    }
                    if ($thread['postdate'] >= $lastView[AppConstant::NUMERIC_ZERO]['lastview'] && $currentUser['id'] != $username->id) {
                        $temparray = array
                        (
                            'parent' => $thread['parent'],
                            'threadId' => $thread['threadid'],
                            'forumiddata' => $thread['forumid'],
                            'subject' => $thread['subject'],
                            'views' => $views,
                            'replyby' => $thread['replyby'],
                            'postdate' => date('F d, o g:i a', $thread['postdate']),
                            'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                            'tagged' => $tagged[0]['tagged'],
                            'lastview' => date('F d, o g:i a', $lastView[0]['lastview']),
                            'userright' => $currentUser['rights'],
                            'postUserId' => $username->id,
                            'currentUserId' => $currentUser['id'],
                            'countArray' => $count,
                            'posttype' => $thread['posttype'],
                            'isReplies' => $isReplies,
                            'isanon' => $thread['isanon'],
                            'groupSetId' => $forumData['groupsetid'],
                        );
                        if ($isValue == AppConstant::NUMERIC_THREE) {
                            array_push($threadArray, $temparray);
                            $ViewData = new ForumView();
                            $ViewData->inserIntoTable($threadArray);
                        } else {
                            array_push($threadArray, $temparray);
                        }
                        array_push($uniquesDataArray, $uniquesData);
                    }
                }
            } else {
                foreach ($threads as $thread) {
                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getByThreadId($thread['threadid']);

                    $lastView = ForumView::getLastView($currentUser, $thread['threadid']);
                     if($lastView){
                         $lastView1 = date('F d, o g:i a', $lastView[0]['lastview']);
                     }else{
                         $lastView1 = 0;
                     }
                    $tagged = ForumView::forumViews($thread['threadid'], $currentUser['id']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $views  = Thread::getByForumIdAndId($forumId,$thread['threadid']);
                    $isReplies = AppConstant::NUMERIC_ZERO;
                    $threadReplies = ForumPosts::isThreadHaveReply($thread['id']);
                    if($threadReplies)
                    {
                        $isReplies = AppConstant::NUMERIC_ONE;
                    }
                    $temparray = array
                    (
                        'parent' => $thread['parent'],
                        'threadId' => $thread['threadid'],
                        'forumiddata' => $thread['forumid'],
                        'subject' => $thread['subject'],
                        'views' => $views,
                        'replyby' => $thread['replyby'],
                        'postdate' => date('F d, o g:i a', $thread['postdate']),
                        'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                        'tagged' => $tagged[0]['tagged'],
                        'userright' => $currentUser['rights'],
                        'lastview' => $lastView1,
                        'postUserId' => $username->id,
                        'currentUserId' => $currentUser['id'],
                        'countArray' => $count,
                        'posttype' => $thread['posttype'],
                        'isReplies' => $isReplies,
                        'isanon' => $thread['isanon'],
                        'groupSetId' => $forumData['groupsetid'],
                    );
                    array_push($threadArray, $temparray);
                    array_push($uniquesDataArray, $uniquesData);
                }
            }
            $FinalUniquesData = array();
            foreach ($uniquesDataArray as $unique) {
                foreach ($unique as $un) {
                    $username = User::getById($un['userid']);
                    $temparrayForUnique = array(
                        'threadId' => $un['threadid'],
                        'lastView' => date('F d, o g:i a', $un['lastview']),
                        'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                    );
                    array_push($FinalUniquesData, $temparrayForUnique);
                }
            }
        } else
        {
            return $this->terminateResponse('');

        }
        $this->includeJS(['forum/forum.js']);
        $responseData = array('threadArray' => $threadArray, 'uniquesDataArray' => $FinalUniquesData, 'isValue' => $isValue);
        return $this->successResponse($responseData);
    }

    /*
     * controller method for redirect to Move Thread page,This method is used to store moved thread data in database.
     */
    public function actionMoveThread()
    {
        $this->layout = 'master';
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $threadId = $this->getParamVal('threadId');
        $forumId = $this->getParamVal('forumId');
        $forums = Forums::getByCourseId($courseId);
        $thread = ThreadForm::thread($forumId);
        $user = $this->getAuthenticatedUser();
        $forumArray = array();
        foreach ($forums as $key => $forum) {
            $tempArray = array
            (
                'forumId' => $forum->id,
                'forumName' => $forum->name,
                'courseId' => $forum->courseid,
            );
            array_push($forumArray, $tempArray);
        }
        if ($thread) {
            $threadArray = array();
            foreach ($thread as $data) {
                $tempArray = array(
                    'threadId' => $data['id'],
                    'forumIdData' => $data['forumid'],
                    'subject' => $data['subject'],
                    'parent' => $data['parent'],
                );
                array_push($threadArray, $tempArray);
            }
            if ($this->isPostMethod()) {
                $params = $this->getRequestParams();
                $moveType = $params['movetype'];
                $thread_Id = $params['threadId'];

                if ($moveType == AppConstant::NUMERIC_ONE) {
                     if(isset($params['thread-name'])){
                         $moveThreadId = $params['thread-name'];
                         ForumPosts::updatePostMoveThread($thread_Id, $moveThreadId);
                         Thread::deleteThreadById($thread_Id);
                     }
                } else {
                    if($params['forum-name']){
                        $forum_Id = $params['forum-name'];
                        Thread::moveAndUpdateThread($forum_Id, $thread_Id);
                        ForumPosts::updateMoveThread($forum_Id, $thread_Id);
                    }
                }
                $this->includeCSS(['forums.css']);
                $this->includeJS(['forum/thread.js?ver=' . time() . '']);
                $responseData = array('cid' => $courseId, 'users' => $user, 'forumid' => $forumId, 'course' => $course);
                return $this->renderWithData('thread', $responseData);
            }
            $this->includeCSS(['forums.css']);
            $this->includeJS(['forum/movethread.js']);
            $responseData = array('forums' => $forumArray, 'threads' => $threadArray, 'threadId' => $threadId, 'forumId' => $forumId, 'course' => $course, 'user' => $user);
            return $this->renderWithData('moveThread', $responseData);
        }
    }

    /*
    * controller method for redirect to modify post page with selected thread data and fetch modified thread from Modify page and store in database.
     *
    */
    public function actionModifyPost()
    {
        $this->layout = 'master';
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $threadId = $this->getParamVal('threadId');
        $forumId = $this->getParamVal('forumId');
        $forumData = Forums::getById($forumId);
        $thread = ThreadForm::thread($forumId);
        $threadArray = array();
        $this->includeJS(["editor/tiny_mce.js", 'editor/tiny_mce_src.js', 'general.js', 'forum/modifypost.js']);
        foreach ($thread as $data) {
            if (($data['id']) == $threadId) {
                $tempArray = array(
                    'threadId' => $data['threadid'],
                    'subject' => $data['subject'],
                    'message' => $data['message'],
                    'postType'=> $data['posttype'],
                    'replyBy' => $data['replyby'],
                    'isANon'  => $data['isanon'],
                );
                array_push($threadArray, $tempArray);
            }
        }
        $forumPostData = ForumPosts::getbyid($threadId);
        $threadCreatedUserData = User::getById($forumPostData[0]['userid']);
        if($this->isPostMethod())
        {
            $params = $this->getRequestParams();
            if(strlen(trim($params['subject'])) > 0) {
                $threadIdOfPost = ForumPosts::modifyPost($params);
                $contentTrackRecord  = new ContentTrack();
                if($currentUser->rights == AppConstant::STUDENT_RIGHT)
                {
                    $contentTrackRecord->insertForumData($currentUser->id,$courseId,$forumId,$threadId,$threadIdOfPost,$type=AppConstant::NUMERIC_TWO);
                }
                $this->redirect('thread?cid='.$courseId.'&forumid='.$forumId);
            }else{
                $this->setSuccessFlash("Subject cannot be blank");
            }
        }
        $this->setReferrer();
        $this->includeCSS(['forums.css']);
        $responseData = array('threadId' => $threadId, 'forumId' => $forumId, 'course' => $course, 'thread' => $threadArray, 'currentUser' => $currentUser,'threadCreatedUserData' => $threadCreatedUserData,'forumData' => $forumData,'forumPostData' => $forumPostData );
        return $this->renderWithData('modifyPost', $responseData);
    }
    /*
    * Controller Action To Redirect To Post Page
    */
    public function actionPost()
    {
        $this->guestUserHandler();
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('courseid');
        $course = Course::getById($courseId);
        $threadId = $this->getParamVal('threadid');
        $forumId = $this->getParamVal('forumid');
        $allThreadIds = Thread::getAllThread($forumId);
        $prevNextValueArray =Thread::checkPreOrNextThreadByForunId($forumId);
        $isNew = ForumView::getById($threadId, $currentUser);
        $tagValue = $isNew[0]['tagged'];
        $FullThread = ForumPosts::getbyid($threadId);
        $data = array();
             foreach($FullThread as $singleThreadArray){
                 if($currentUser['rights'] == AppConstant::NUMERIC_TEN && $singleThreadArray['parent'] == 0 && $singleThreadArray['posttype'] == AppConstant::NUMERIC_THREE){
                     $data = array();
                     array_push($data, $singleThreadArray);
                     $forumPostData = ForumPosts::getByThreadIdAndUserID($threadId, $currentUser['id']);
                     if ($forumPostData) {
                         foreach ($forumPostData as $single) {
                             $Replies = ForumPosts::isThreadHaveReply($single['id']);
                             foreach($Replies as $singleReply){
                                 array_push($data, $singleReply);
                             }
                             if($single['parent'] == $threadId){
                                 array_push($data, $single);
                             }
                         }
                 }
              break;
             }else{
                     array_push($data, $singleThreadArray);
                 }
             }
         $titleCountArray = array();
        foreach ($data as $postData) {
            $this->children[$postData['parent']][] = $postData['id'];
            $username = User::getById($postData['userid']);
            $forumName = Forums::getById($postData['forumid']);
            $titleLevel = AppUtility::calculateLevel($postData['subject']);
            $likeImage = ForumLike::checkStatus($postData['id'], $currentUser);
            $count = new ForumLike();
            $likeCnt = $count->CalculateCount($postData['id']);
            $studentCount = AppConstant::NUMERIC_ZERO;
            $teacherCount = AppConstant::NUMERIC_ZERO;
            $isReplies = AppConstant::NUMERIC_ZERO;
            $threadReplies = ForumPosts::isThreadHaveReply($postData['id']);
            if($threadReplies)
            {
                $isReplies = AppConstant::NUMERIC_ONE;
            }
            foreach ($likeCnt as $like) {
                $Rights = User::getById($like['userid']);
                if ($Rights->rights == AppConstant::STUDENT_RIGHT) {
                    $studentCount = $studentCount + AppConstant::NUMERIC_ONE;
                } elseif ($Rights->rights >= AppConstant::TEACHER_RIGHT) {
                    $teacherCount = $teacherCount + AppConstant::NUMERIC_ONE;
                }
                $tempArray = array(
                    'postId' => $like['postid'],
                    'studentCount' => $studentCount,
                    'teacherCount' => $teacherCount,
                );
            }
            if($postData['parent'] == 0)
            {
                $replyBy = $postData['replyby'];
            }
            array_push($titleCountArray, $tempArray);
            $tempArray = array();
            $tempArray['id'] = $postData['id'];
            $tempArray['threadId'] = $postData['threadid'];
            $tempArray['forumIdData'] = $postData['forumid'];
            $tempArray['subject'] = $titleLevel['title'];
            $tempArray['forumName'] = ucfirst($forumName->name);
            $tempArray['postdate'] = date('F d, o g:i a', $postData->postdate);
            $tempArray['postType'] = $postData['posttype'];
            $tempArray['name'] = AppUtility::getFullName($username->FirstName, $username->LastName);
            $tempArray['userRights'] = $username->rights;
            $tempArray['userId'] = $username->id;
            $tempArray['settings'] = $forumName->settings;
            $tempArray['hasImg'] = $username->hasuserimg;
            $tempArray['likeImage'] = $likeImage;
            $tempArray['studentCount'] = $studentCount;
            $tempArray['teacherCount'] = $teacherCount;
            $tempArray['likeCnt'] = count($likeCnt);
            $tempArray['lastView'] = $isNew[AppConstant::NUMERIC_ZERO]['lastview'];
            $tempArray['message'] = $postData['message'];
            $tempArray['level'] = $titleLevel['level'];
            $tempArray['parent'] = $postData['parent'];
            $tempArray['isReplies'] = $isReplies;
            if($postData['parent'] != AppConstant::NUMERIC_ZERO){
                if(substr($postData['subject'],0,4) !== 'Re: '){
                    $this->threadLevel = AppConstant::NUMERIC_ONE;
                    $this->calculatePostLevel($postData);
                    $tempArray['level'] = $this->threadLevel;
                }
            }
            $this->postData[$postData['id']] = $tempArray;
        }
        Thread::saveViews($threadId);
        $viewsData = new ForumView();
        $viewsData->updateData($threadId, $currentUser);
        $this->createChild($this->children[0]);
        $Count = new ForumLike();
        $likeCount = $Count->findCOunt($threadId);
        $myLikes = $Count->UserLikes($threadId, $currentUser);
        $this->setReferrer();

        foreach($this->totalPosts as $key=>$threadArray){
            if($threadArray){
                foreach($this->totalPosts as $singleThread) {
                    if($threadArray['parent'] == $singleThread['id'])
                    {
                        if(substr($threadArray['subject'],0,2) !== 'Re') {
                            $moveThreadSubject = $threadArray['id'];
                            $threadArray['level'] = $threadArray['level'] + $singleThread['level'];
                        }
                            if( $threadArray['parent']  == $moveThreadSubject){
                                $threadArray['level'] = $threadArray['level'] + $singleThread['level'];
                            }
                    }
                }
            }
            $FinalPostArray[$key] = $threadArray;
        }
        $this->includeCSS(['forums.css']);
        $this->includeJS(["general.js", "forum/post.js?ver=<?php echo time() ?>"]);
        $responseData = array('postdata' => $FinalPostArray, 'course' => $course, 'currentUser' => $currentUser, 'forumId' => $forumId, 'threadId' => $threadId, 'tagValue' => $tagValue, 'prevNextValueArray' => $prevNextValueArray, 'likeCount' => $likeCount, 'mylikes' => $myLikes, 'titleCountArray' => $titleCountArray,'allThreadIds' => $allThreadIds,'replyBy' => $replyBy);
        return $this->render('post', $responseData);
    }
    public function calculatePostLevel($data)
    {
        $parentData = ForumPosts::getParentDataByParentId($data['parent']);
        if($parentData['parent'] == 0)
        {
            return 0;
        }else
        {
            $this->threadLevel++;
            $this->calculatePostLevel($parentData);
        }
    }

    public function createChild($childArray, $arrayKey = AppConstant::NUMERIC_ZERO)
    {
        $this->children = AppUtility::removeEmptyAttributes($this->children);
        foreach ($childArray as $superKey => $child) {
            array_push($this->totalPosts, $this->postData[$child]);
            unset($this->children[$arrayKey][$superKey]);
            if (isset($this->children[$child])) {
                return $this->createChild($this->children[$child], $child);
            } else {
                continue;
            }
        }
        if (count($this->children)) {
            $this->createChild($this->children[key($this->children)], key($this->children));
        }
    }

    /*
     * controller ajax method for fetch select as remove thread from Thread page and remove from database.
     */
    public function actionMarkAsRemoveAjax()
    {
        $params = $this->getBodyParams();
        $threadId = $params['threadId'];
        $parentId = $params['parentId'];
        $deleteThreadData = ForumPosts::getPostById($threadId);
        ForumPosts::removeThread($threadId, $parentId);
        if($parentId == AppConstant::NUMERIC_ZERO){
            ForumThread::removeThread($threadId);
            ForumView::removeThread($threadId);
        }else{
            ForumPosts::updateParentId($threadId,$deleteThreadData['parent']);
        }
          return $this->successResponse($parentId);
    }
    /*
     * Controller Action To Reply To A Post
     */
    public function actionReplyPost()
    {
        $this->layout = 'master';
        $this->guestUserHandler();
        $isPost = $this->getParamVal('listbypost');
        $courseId = $this->getParamVal('courseid');
        $course = Course::getById($courseId);
        $threadArray = array();
        $forumId = $this->getParamVal('forumid');
        $Id = $this->getParamVal('id');
        $threadId = $this->getParamVal('threadId');
        $userData = $this->getAuthenticatedUser();
        $threadData = ForumPosts::getbyidpost($Id);
        $contentTrackRecord = new ContentTrack();
        if($userData->rights == AppConstant::STUDENT_RIGHT)
        {
            $contentTrackRecord->insertForumData($userData->id,$courseId,$forumId,$Id,$threadId,$type=AppConstant::NUMERIC_ONE);
        }
        foreach ($threadData as $data) {
            $tempArray = array
            (
                'subject' => $data['subject'],
                'userName' => $data->user->FirstName . ' ' . $data->user->LastName,
                'message' => $data['message'],
                'postDate' => date('F d, o g:i a', $data['postdate']),
            );
            array_push($threadArray, $tempArray);
        }
        $this->includeCSS(['forums.css']);
        $this->includeJS(['editor/tiny_mce.js', 'editor/tiny_mce_src.js', 'general.js', 'forum/replypost.js']);
        $responseData = array('reply' => $threadArray, 'course' => $course, 'forumId' => $forumId, 'threadId' => $threadId, 'parentId' => $Id,'isPost' => $isPost);
        return $this->renderWithData('replyPost', $responseData);
    }

    public function actionReplyPostAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $isPost = $params['isPost'];
            $user = $this->getAuthenticatedUser();
            $reply = new ForumPosts();
            $reply->createReply($params, $user);
            return $this->successResponse($isPost);
        }
    }

    /*
     * Controller Action To Redirect To New Thread Page
     */
    public function actionAddNewThread()
    {
        $this->layout = 'master';
        $user = $this->getAuthenticatedUser();
        $userId = $this->getUserId();
        $rights = $user->rights;
        $forumId = $this->getParamVal('forumid');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $forumData = Forums::getById($forumId);
        $this->includeCSS(['forums.css']);
        $this->includeJS(['editor/tiny_mce.js', 'editor/tiny_mce_src.js', 'general.js', 'forum/addnewthread.js']);
        $responseData = array('forumData' => $forumData, 'course' => $course, 'userId' => $userId, 'rights' => $rights);
        return $this->renderWithData('addNewThread', $responseData);
    }
    /*
     * Controller Action To Save The Newly Added Thread In Database
     */
    public function actionAddNewThreadAjax()
    {
        $this->guestUserHandler();
            $params = $this->getRequestParams();
            $postType = AppConstant::NUMERIC_ZERO;
            $alwaysReplies = null;
            $isNonValue = AppConstant::NUMERIC_ZERO;
            if ($this->getAuthenticatedUser()->rights > AppConstant::NUMERIC_TEN) {
                $postType = $params['postType'];
                $date = strtotime($params['date'] . ' ' . $params['time']);
            }else{
                $isNonValue = $params['settings'];
            }
            $alwaysReplies = $params['alwaysReplies'];
            $userId = $this->getUserId();
            $newThread = new ForumPosts();
            $threadId = $newThread->createThread($params, $userId, $postType, $alwaysReplies, $date , $isNonValue);
            $newThread = new ForumThread();
            $newThread->createThread($params, $userId, $threadId);
            $views = new ForumView();
            $views->createThread($userId, $threadId);
            $contentTrackRecord = new ContentTrack();
            if($this->getAuthenticatedUser()->rights == AppConstant::STUDENT_RIGHT)
            {
                 $contentTrackRecord->insertForumData($this->getAuthenticatedUser()->id,$params['courseId'],$params['forumId'],$threadId,$threadIdOfPost=null,$type=AppConstant::NUMERIC_ZERO);
            }
            return $this->successResponse();
    }

    /*Controller Action To Toggle The Flag Image On Click*/
    public function actionChangeImageAjax()
    {
        $params = $this->getRequestParams();
        $rowId = $params['rowId'];
        $userId = $params['userId'];
        if ($rowId == -1) {
            $threadId = $params['threadId'];
            ForumView::deleteByUserIdAndThreadId($threadId, $userId);
        } else {
            $updateView = new ForumView();
            $updateView->updateFlagValue($rowId, $userId);
        }
        return $this->successResponse();
    }

    /*
     * Controller Action To Search All Post In A Forum
     */
    public function actionGetSearchPostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['courseid'];
        $now = time();
        $forum = Forums::getByCourseId($courseId);
        $search = $params['search'];
        $checkBoxVal = $params['value'];
        $sort = AppConstant::DESCENDING;
        $orderBy = 'postdate';
        $query = ForumForm::byAllpost($search, $sort, $orderBy);
        if ($query) {
            $searchPost = array();
            foreach ($forum as $forumId) {
                foreach ($query as $data) {
                    if ($forumId['id'] == $data['forumid']) {
                        if ($this->getAuthenticatedUser()->rights == AppConstant::NUMERIC_TEN) {
                            if ($forumId['enddate'] > $now) {
                                $username = User::getById($data['userid']);
                                $postdate = Thread::getById($data['threadid']);
                                $forumName = Forums::getById($data['forumid']);
                                $tempArray = array
                                (
                                    'forumIdData' => $data['forumid'],
                                    'threadId' => $data['threadid'],
                                    'subject' => $data['subject'],
                                    'views' => $data['views'],
                                    'forumName' => ucfirst($forumName->name),
                                    'postdate' => date('F d, o g:i a', $postdate->lastposttime),
                                    'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),
                                    'message' => $data['message'],
                                );
                                array_push($searchPost, $tempArray);
                            }
                        } else {
                            $username = User::getById($data['userid']);
                            $postdate = Thread::getById($data['threadid']);
                            $forumName = Forums::getById($data['forumid']);
                            $tempArray = array
                            (
                                'forumIdData' => $data['forumid'],
                                'threadId' => $data['threadid'],
                                'subject' => $data['subject'],
                                'views' => $data['views'],
                                'forumName' => ucfirst($forumName->name),
                                'postdate' => date('F d, o g:i a', $postdate->lastposttime),
                                'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),
                                'message' => $data['message'],
                            );
                            array_push($searchPost, $tempArray);
                        }
                    }
                }
            }
            $this->includeJS(['forum/forum.js', 'forum/thread.js']);
            $responseData = array('data' => $searchPost, 'checkvalue' => $checkBoxVal, 'search' => $search);
            return $this->successResponse($responseData);
        } else {
            return $this->terminateResponse('No data');
        }
    }

    /*
     * Controller Action To Search Post Of That Forum
     */
    public function actionGetOnlyPostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $search = $params['search'];
        $forumId = $params['forumid'];
        $query = ForumForm::byAllpost($search);
        if ($query) {
            $searchPost = array();
            foreach ($query as $data) {
                if ($forumId == $data['forumid']) {
                    $username = User::getById($data['userid']);
                    $postdate = Thread::getById($data['threadid']);
                    $forumName = Forums::getById($data['forumid']);
                    $tempArray = array
                    (
                        'forumIdData' => $data['forumid'],
                        'threadId' => $data['threadid'],
                        'subject' => $data['subject'],
                        'views' => $data['views'],
                        'forumName' => ucfirst($forumName->name),
                        'postdate' => date('F d, o g:i a', $postdate->lastposttime),
                        'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),
                        'message' => $data['message'],
                    );
                    array_push($searchPost, $tempArray);
                }
            }
            $this->includeJS(['forum/forum.js', 'forum/thread.js']);
            $responseData = array('data' => $searchPost);
            return $this->successResponse($responseData);
        } else {
            return $this->terminateResponse('No Data');
        }
    }

    /*
     *
     */
    public function actionListPostByName()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $userRights = $this->getAuthenticatedUser()->rights;
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $sort = AppConstant::DESCENDING;
        $forumId = $params['forumid'];
        $forumName = Forums::getById($forumId);
        $orderBy = 'postdate';
        $thread = ThreadForm::postByName($forumId, $sort, $orderBy);
        if ($thread) {
            $nameArray = array();
            $sortByName = array();
            $finalSortedArray = array();
            $threadArray = array();
            foreach ($thread as $data) {
                $username = User::getById($data['userid']);
                $isNew = ForumView::getLastViewOfPost($data['threadid'], $this->getAuthenticatedUser()->id);
                $tempArray = array
                (
                    'id' => $data['id'],
                    'parent' => $data['parent'],
                    'threadId' => $data['threadid'],
                    'forumIdData' => $data['forumid'],
                    'userId' => $username->id,
                    'hasImg' => $username->hasuserimg,
                    'lastView' => $isNew[0]['lastview'],
                    'subject' => $data['subject'],
                    'postdate' => date('F d, o g:i a', $data['postdate']),
                    'message' => $data['message'],
                    'name' => AppUtility::getFullName($username->LastName, $username->FirstName),
                );
                if (!in_array($tempArray['name'], $nameArray))
                    array_push($nameArray, $tempArray['name']);
                array_push($threadArray, $tempArray);
            }
            sort($nameArray);
            foreach ($nameArray as $name) {
                foreach ($threadArray as $threadA) {
                    if ($name == $threadA['name']) {
                        array_push($finalSortedArray, $threadA);
                    }
                }
                array_push($sortByName, $name);
            }
            $this->setReferrer();
            $this->includeCSS(['forums.css']);
            $this->includeJS(['forum/listpostbyname.js']);
            $status = AppConstant::NUMERIC_ONE;
            $responseData = array('threadArray' => $finalSortedArray, 'forumId' => $forumId, 'forumName' => $forumName, 'course' => $course, 'status' => $status, 'userRights' => $userRights);
            return $this->renderWithData('listPostByName', $responseData);
        } else {
            $this->includeCSS(['forums.css']);
            $this->includeJS(['forum/listpostbyname.js']);
            $status = AppConstant::NUMERIC_ZERO;
            $responseData = array('status' => $status, 'forumId' => $forumId, 'course' => $course);
            return $this->renderWithData('listPostByName', $responseData);
        }
    }
    public function actionLikePostAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getAuthenticatedUser()->id;
        $params = $this->getRequestParams();
        $like = $params['like'];
        if ($this->isPostMethod()) {

            if ($like == AppConstant::NUMERIC_ZERO) {
                $like = new ForumLike();
                $like->InsertLike($params, $userId);

            } elseif ($like == AppConstant::NUMERIC_ONE) {
                $like = new ForumLike();
                $like->DeleteLike($params, $userId);
            }
        }
        return $this->successResponse();
    }

    public function actionDataLikePostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $count = new ForumLike();
        $displayCountData = $count->checkCount($params);
        $countDataArray = array();
        foreach ($displayCountData as $data) {
            $user = User::getById($data->userid);
            $tempArray = array('id' => $data->userid, 'userName' => AppUtility::getFullName($user->FirstName, $user->LastName));
            array_push($countDataArray, $tempArray);
        }
        $responseData = array('displayCountData' => $countDataArray);
        return $this->successResponse($responseData);
    }
    public function actionMarkAllReadAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $forumId = $params['forumId'];
        $now = time();
        $userId = $this->getAuthenticatedUser()->id;
        $readThreadId = ForumPosts::MarkAllRead($forumId);
        foreach($readThreadId as $data)
        {
            $viewsData = new ForumView();
            $viewsData->updateDataForPostByName($data['threadid'],$userId,$now);
        }
        return $this->successResponse();
    }

    public function actionAddForum()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $user = $this->getAuthenticatedUser();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $modifyForumId = $params['id'];
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $key = AppConstant::NUMERIC_ZERO;
        foreach ($groupNames as $group) {
            $groupNameId[$key] = $group['id'];
            $groupNameLabel[$key] = 'Use group set:' . $group['name'];
            $key++;
        }
        $key = AppConstant::NUMERIC_ZERO;
        $gbcatsData = GbCats::getByCourseId($courseId);
        foreach ($gbcatsData as $singleGbcatsData) {
            $gbcatsId[$key] = $singleGbcatsData['id'];
            $gbcatsLabel[$key] = $singleGbcatsData['name'];
            $key++;
        }
        $rubrics = Rubrics::getByUserId($user['id']);
        foreach ($rubrics as $rubric) {
            $rubricsId[$key] = $rubric['id'];
            $rubricsLabel[$key] = $rubric['name'];
            $key++;
        }
        $OutcomesData = Outcomes::getByCourse($courseId);
        $key = AppConstant::NUMERIC_ONE;
        $pageOutcomes = array();
        if ($OutcomesData) {
            foreach ($OutcomesData as $singleData) {
                $pageOutcomes[$singleData['id']] = $singleData['name'];
                $key++;
            }
        }
        $pageOutcomesList = array();
        $query = $course['outcomes'];
        $outcomeArray = unserialize($query);
        $result = $this->flatArray($outcomeArray);
        if ($result) {
            foreach ($result as $singlePage) {
                array_push($pageOutcomesList, $singlePage);
            }
        }
        $pageTitle = AppConstant::ADD_FORUM;
        $saveTitle = AppConstant::CREATE_FORUM;
        $defaultValue = array(
            'startDate' => time(),
            'endDate' > time(),
            'replyBy' => AppConstant::ALWAYS_TIME,
            'postBy' => AppConstant::ALWAYS_TIME,
            'hasSubScrip' => false,
            'hasGroupThreads' => AppConstant::NUMERIC_ZERO,
            'postTag' => 'FP',
            'replyTag' => 'FR',
            'cntInGb' => AppConstant::NUMERIC_ZERO,
            'points' => AppConstant::NUMERIC_ZERO,
            'forumType' => AppConstant::NUMERIC_ZERO,
            'tagList' => '',
            'rubric' => AppConstant::NUMERIC_ZERO,
            'groupSetId' => AppConstant::NUMERIC_ZERO,
            'gbCat' => AppConstant::NUMERIC_ZERO,
            'sortBy' => AppConstant::NUMERIC_ZERO,
            'tutorEdit' => AppConstant::NUMERIC_ZERO,
            'sDate' => date("m-d-Y"),
            'sTime' => time(),
            'eDate' => date("m-d-Y",strtotime("+1 week")),
            'eTime' => time(),
             'postDate' => date("m-d-Y",strtotime("+1 week")),
            'replyByDate' => date("m-d-Y",strtotime("+1 week")),
            'avail' => AppConstant::NUMERIC_ZERO,
            'defDisplay' => AppConstant::NUMERIC_ZERO,
            'replyByTime' => time(),
            'postTime' => time(),
            'replyBy' => AppConstant::NUMERIC_ZERO,
            'postBy' => AppConstant::NUMERIC_ZERO,
            'outcomes' => ' ',
            'isOutcomes' => $course['outcomes'],
        );
        if ($modifyForumId) {

            $pageTitle = 'Modify Forum';
            $saveTitle = AppConstant::SAVE_BUTTON;
            $forumData = Forums::getById($modifyForumId);
            if ($forumData['groupsetid'] > AppConstant::NUMERIC_ZERO) {
                $threadData = Thread::getByForumId($modifyForumId);
                if (count($threadData) > AppConstant::NUMERIC_ZERO) {
                    $hasGroupThreads = true;
                } else {
                    $hasGroupThreads = false;
                }
            }
            $startDate = $forumData['startdate'];
            $endDate = $forumData['enddate'];
            if ($startDate != AppConstant::NUMERIC_ZERO) {
                $sDate = AppUtility::tzdate("m/d/Y", $startDate);
                $sTime = AppUtility::tzdate("g:i a", $startDate);
                $startDate =AppConstant::NUMERIC_ONE;
            } else {
                $sDate = date('m-d-Y');
                $sTime = time();
            }
            if ($endDate != AppConstant::ALWAYS_TIME) {
                $eDate = AppUtility::tzdate("m/d/Y", $endDate);
                $eTime = AppUtility::tzdate("g:i a", $endDate);
                $endDate = AppConstant::NUMERIC_ONE;
            } else {
                $eDate = date("m-d-Y",strtotime("+1 week"));
                $eTime = time();
            }
            $allNon = (($forumData['settings'] & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ONE);
            if(!$allNon){
                $allNon = AppConstant::NUMERIC_ZERO;
            }
            $allMod = (($forumData['settings'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO);
            if(!$allMod){
                $allMod = AppConstant::NUMERIC_ZERO;
            }
            $allDel = (($forumData['settings'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR);
            if(!$allDel){
                $allDel = AppConstant::NUMERIC_ZERO;
            }
            $allLikes = (($forumData['settings'] & AppConstant::NUMERIC_EIGHT) == AppConstant::NUMERIC_EIGHT);
            if(!$allLikes){
                $allLikes = AppConstant::NUMERIC_ZERO;
            }
            $viewAfterPost = (($forumData['settings'] & AppConstant::SIXTEEN) == AppConstant::SIXTEEN);
            if(!$viewAfterPost){
                $viewAfterPost = AppConstant::NUMERIC_ZERO;
            }
            $subscriptionsData = ForumSubscriptions::getByForumIdUserId($modifyForumId, $user['id']);
            if (count($subscriptionsData) > AppConstant::NUMERIC_ZERO) {
                $hasSubScrip = true;
            }

            if($forumData['replyby'] > AppConstant::NUMERIC_ZERO && $forumData['replyby'] < AppConstant::ALWAYS_TIME){
                $replyBy =  AppConstant::NUMERIC_ONE;
                $forumData['replyby'] = AppUtility::tzdate("m/d/Y", $forumData['replyby']);
                $replyByTime = AppUtility::tzdate("g:i a", $forumData['replyby']);
            }else{
                $replyBy =  $forumData['replyby'];
                $forumData['replyby'] = date("m-d-Y",strtotime("+1 week"));
                $replyByTime = time();
            }
            if($forumData['postby'] > AppConstant::NUMERIC_ZERO && $forumData['postby'] < AppConstant::ALWAYS_TIME){
                $postBy  =  AppConstant::NUMERIC_ONE;
                $forumData['postby'] = AppUtility::tzdate("m/d/Y", $forumData['postby']);
                $postByTime = AppUtility::tzdate("g:i a", $forumData['postby']);
            }else{
                $postBy =  $forumData['postby'];
                $forumData['postby'] = date("m-d-Y",strtotime("+1 week"));
                $postByTime = time();
            }
             if($forumData['outcomes'])
             {
                 $outcomes = $forumData['outcomes'];
             }else{
                 $outcomes = ' ';
             }
            list($postTag, $replyTag) = explode('--', $forumData['caltag']);
            $defaultValue = array(
                'allowAnonymous' => $allNon,
                'allowModify' => $allMod,
                'allowDelete' => $allDel,
                'allowLikes' => $allLikes,
                'viewAfterPost' => $viewAfterPost,
                'sDate' => $sDate,
                'sTime' => $sTime,
                'eDate' => $eDate,
                'eTime' => $eTime,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'hasGroupThreads' => $hasGroupThreads,
                'hasSubScrip' => $hasSubScrip,
                'defDisplay' => $forumData['defdisplay'],
                'sortBy' => $forumData['sortby'],
                'postTag' => $postTag,
                'replyTag' => $replyTag,
                'cntInGb' => $forumData['cntingb'],
                'points' => $forumData['points'],
                'gbCat' => $forumData['gbcategory'],
                'groupSetId' => $forumData['groupsetid'],
                'forumType' => $forumData['forumtype'],
                'rubric' => $forumData['rubric'],
                'tagList' => $forumData['taglist'],
                'tutorEdit' => $forumData['tutoredit'],
                'avail' => $forumData['avail'],
                'postDate' =>$forumData['postby'],
                'replyByDate' =>  $forumData['replyby'],
                'replyByTime' => $replyByTime,
                'postByTime' => $postByTime,
                'replyBy' => $replyBy,
                'postBy' => $postBy,
                'outcomes' => $outcomes,
                'isOutcomes' => $course['outcomes'],
                );
        }
        if ($this->isPostMethod()) {
             if ($params['modifyFid']) {
                 if(isset($params['outcomes'])){
                     foreach ($params['outcomes'] as $outcomeId) {

                         if (is_numeric($outcomeId) && $outcomeId > AppConstant::NUMERIC_ZERO) {
                             $outcomes[] = intval($outcomeId);
                         }
                     }
                     if($outcomes != null){
                         $params['outcomes'] = implode(',',$outcomes);
                     }else{
                         $params['outcomes'] = " ";
                     }
                 }else{
                     $params['outcomes'] = " ";
                 }
                 $updateForum = new Forums();
                 $updateForum->UpdateForum($params);
                 if(isset($params['Get-email-notify-of-new-posts'])){
                 $subscriptionEntry = new ForumSubscriptions();
                 $subscriptionEntry->AddNewEntry($params['modifyFid'], $user['id']);
                 }else{
                  ForumSubscriptions::deleteSubscriptionsEntry($params['modifyFid']);
                 }
            } else {
                $endDate =   AssessmentUtility::parsedatetime($params['edate'],$params['etime']);
                $startDate = AssessmentUtility::parsedatetime($params['sdate'],$params['stime']);
                $postDate = AppUtility::parsedatetime($params['postDate'],$params['postTime']);
                $replyByDate = AppUtility::parsedatetime($params['replyByDate'],$params['replyByTime']);
                $settingValue = $params['allow-anonymous-posts']+$params['allow-students-to-modify-posts']+$params['allow-students-to-delete-own-posts']+$params['like-post'] + $params['viewing-before-posting'];
                $finalArray['name'] = trim($params['name']);
                if(empty($params['forum-description']))
                {
                    $params['forum-description'] = ' ';
                }
                $finalArray['description'] = trim($params['forum-description']);
                $finalArray['courseid'] = $params['cid'];

                $finalArray['settings'] = $settingValue;

                if($params['avail'] == AppConstant::NUMERIC_ONE)
                {
                    if($params['available-after'] == AppConstant::NUMERIC_ZERO){

                        $startDate = AppConstant::NUMERIC_ZERO;
                    }
                    if($params['available-until'] == AppConstant::ALWAYS_TIME){
                        $endDate = AppConstant::ALWAYS_TIME;
                    }
                    $finalArray['startdate'] = $startDate;
                    $finalArray['enddate'] = $endDate;
                }else
                {

                    $finalArray['startdate'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['enddate'] = AppConstant::ALWAYS_TIME;
                }

                $finalArray['sortby'] = $params['sort-thread'];
                $finalArray['defdisplay'] = $params['default-display'];

                if($params['post'] == AppConstant::NUMERIC_ONE){
                    $finalArray['postby'] = $postDate;
                }else{
                    $finalArray['postby'] = $params['post'];
                }

                if($params['reply'] == AppConstant::NUMERIC_ONE){

                    $finalArray['replyby'] = $replyByDate;
                }else{
                    $finalArray['replyby'] = $params['reply'];
                }
                if($params['count-in-gradebook'] != AppConstant::NUMERIC_ZERO){
                    $finalArray['gbcategory'] = $params['gradebook-category'];
                    $finalArray['points'] = $params['points'];
                    $finalArray['tutoredit'] = $params['tutor-edit'];
                    $finalArray['rubric'] = $params['rubric'];
                    if(isset($params['outcomes'])){
                        foreach ($params['outcomes'] as $outcomeId) {

                            if (is_numeric($outcomeId) && $outcomeId > AppConstant::NUMERIC_ZERO) {
                                $outcomes[] = intval($outcomeId);
                            }
                        }
                        if($outcomes != null){
                            $params['outcomes'] = implode(',',$outcomes);
                        }else{
                            $params['outcomes'] = " ";
                        }
                    }else{
                        $params['outcomes'] = " ";
                    }
                    $finalArray['outcomes'] = $params['outcomes'];
                }else{
                    $finalArray['gbcategory']  = AppConstant::NUMERIC_ZERO;
                    $finalArray['points'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['tutoredit'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['rubric'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['outcomes'] = " ";
                }

                $finalArray['groupsetid'] = $params['groupsetid'];
                $finalArray['cntingb'] = $params['count-in-gradebook'];
                 $finalArray['avail'] = $params['avail'];
                $finalArray['forumtype'] = $params['forum-type'];
                $finalArray['caltag'] = $params['calendar-icon-text1'].'--'.$params['calendar-icon-text2'];
                $tagList = '';
                if($params['categorize-posts'] == AppConstant::NUMERIC_ONE){
                    $tagList = trim($params['taglist']);
                }
                $finalArray['taglist'] = $tagList;
                $newForum = new Forums();
                $forumId = $newForum->addNewForum($finalArray);
                $itemType = 'Forum';
                $itemId = new Items();
                $lastItemId = $itemId->saveItems($courseId, $forumId, $itemType);
                $subscriptionEntry = new ForumSubscriptions();
                $subscriptionEntry->AddNewEntry($forumId, $user['id']);
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemOrder = $courseItemOrder->itemorder;
                $items = unserialize($itemOrder);
                $blockTree = array(0);
                $sub =& $items;
                for ($i = AppConstant::NUMERIC_ONE; $i < count($blockTree); $i++) {
                    $sub =& $sub[$blockTree[$i] - AppConstant::NUMERIC_ONE]['items'];
                }
                array_unshift($sub, intval($lastItemId));
                $itemOrder = serialize($items);
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemOrder, $courseId);
            }
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id));
        }
            $this->includeJS(["forum/addforum.js","editor/tiny_mce.js",'assessment/addAssessment.js', 'editor/tiny_mce_src.js', 'general.js', 'editor.js']);
            $this->includeCSS(['course/items.css']);
            $responseData = array('course' => $course,'groupNameId' => $groupNameId, 'groupNameLabel' => $groupNameLabel,'saveTitle' => $saveTitle, 'pageTitle' => $pageTitle, 'rubricsLabel' => $rubricsLabel, 'rubricsId' => $rubricsId, 'pageOutcomesList' => $pageOutcomesList,
            'pageOutcomes' => $pageOutcomes, 'defaultValue' => $defaultValue,'forumData' => $forumData, 'modifyForumId' => $modifyForumId,
                'gbcatsLabel' => $gbcatsLabel, 'gbcatsId' => $gbcatsId);
            return $this->renderWithData('addForum', $responseData);
    }
    public function flatArray($outcomesData)
    {
        global $pageOutcomesList;
        if ($outcomesData) {
            foreach ($outcomesData as $singleData) {
                if (is_array($singleData)) { //outcome group
                    $pageOutcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                    $this->flatArray($singleData['outcomes']);
                } else {
                    $pageOutcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
                }
            }
        }
        return $pageOutcomesList;
    }
}