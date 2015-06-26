<?php
namespace app\controllers\forum;
use app\components\AppConstant;
use app\models\Course;
use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\forms\ThreadForm;
use app\models\ForumLike;
use app\models\ForumPosts;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\Forums;
use app\models\Thread;
use app\models\User;
use app\components\AppUtility;
use Yii;

class ForumController extends AppController
{
    public $postData = array();
    public $totalPosts = array();
    public $children = array();
/*Controller Action To Redirect To Search Forum Page*/
    public function actionSearchForum()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $forum = Forums::getByCourseId($cid);
        $course = Course::getById($cid);
        $user = $this->getAuthenticatedUser();
        $model = new ForumForm();
        $model->thread = 'subject';
        $this->includeCSS(['dataTables.bootstrap.css','forums.css','dashboard.css']);
        $this->includeJS(['forum/forum.js','general.js?ver=012115', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        $this->setReferrer();
        $responseData = array('model' => $model, 'forum' => $forum, 'cid' => $cid, 'users' => $user,'course' => $course);
        return $this->renderWithData('forum',$responseData);
    }
    /*Controller Action To Search All threads By Subject*/
    public function actionGetForumNameAjax()
    {
        $this->guestUserHandler();
        $param = $this->getBodyParams();
        $search = $param['search'];
        $courseid=$param['courseid'];
        $checkBoxVal = $param['value'];
        $query = ForumForm::byAllSubject($search);
            if ($query)
            {
                $searchthread = array();
                foreach ($query as $data) {
                    $username = User::getById($data['userid']);
                    $postdate = Thread::getById($data['threadid']);
                    $temparray = array
                    (
                        'parent' => $data['parent'],
                        'forumiddata' => $data['forumid'],
                        'threadId' => $data['threadid'],
                        'subject' => $data['subject'],
                        'views' => $data['views'],
                        'replyby' => $data['replyby'],
                        'postdate' => date('F d, o g:i a', $postdate->lastposttime),
                        'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),
                    );
                    array_push($searchthread, $temparray);
                }
                $this->includeJS(['forum/forum.js']);
                return $this->successResponse($searchthread);

            }
            else
            {
             return $this->terminateResponse("No data Found");
            }
    }

/*Controller Action To Display All The Forums*/
    public function actionGetForumsAjax()
    {
        $this->guestUserHandler();
        $currentime = time();
        $param = $this->getBodyParams();
        $cid = $param['cid'];
        $forums = Forums::getByCourseId($cid);
        $user = $this->getAuthenticatedUser();
        if ($forums)
        {
            $forumArray = array();
            foreach ($forums as $key => $forum) {
                $threadCount = count($forum->imasForumThreads);
                $postCount = count($forum->imasForumPosts);
                $lastObject = '';
                if ($postCount > AppConstant::NUMERIC_ZERO) {
                    $lastObject = $forum->imasForumPosts[$postCount - AppConstant::NUMERIC_ONE];
                }
                $tempArray = array
                (
                    'forumId' => $forum->id,
                    'forumname' => $forum->name,
                    'threads' => $threadCount,
                    'posts' => $postCount,
                    'currenttime' => $currentime,
                    'enddate' => $forum->enddate,
                    'rights' => $user->rights,
                    'lastPostDate' => ($lastObject != '') ? date('F d, o g:i a', $lastObject->postdate) : ''
                );
                array_push($forumArray, $tempArray);
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

/*Controller Action To Redirect To Thread Page*/
    public function actionThread()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $forumid = $this->getParamVal('forumid');
        $users = $this->getAuthenticatedUser();
        $this->setReferrer();
        $this->includeCSS(['dataTables.bootstrap.css','forums.css','dashboard.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js','general.js?ver=012115','forum/thread.js?ver='.time().'']);
        $responseData = array('cid' => $cid, 'users' => $users, 'forumid' => $forumid,'course' =>$course);
        return $this->renderWithData('thread',$responseData);
    }
    /*Controller Action To Display The Thraeds Present In That Particular Forum */
    public function actionGetThreadAjax()
    {
        $params = $this->getBodyParams();
        $currentUser = $this->getAuthenticatedUser();
        $isValue = $params['isValue'];
        $forumid = $params['forumid'];
        $threads = ThreadForm::thread($forumid);
        $threadArray = array();
        $uniquesDataArray = array();
        if(!empty($threads))
        {
            if ($isValue == 1) {
                foreach ($threads as $thread) {

                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getbythreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser,$thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $tagged = ForumView::forumViews($thread['threadid']);
                    if ($tagged[0]['tagged'] == AppConstant::NUMERIC_ONE) {
                        $temparray = array
                        (
                            'parent' => $thread['parent'],
                            'threadId' => $thread['threadid'],
                            'forumiddata' => $thread['forumid'],
                            'subject' => $thread['subject'],
                            'views' => $thread['views'],
                            'replyby' => $thread['replyby'],
                            'postdate' => date('F d, o g:i a', $thread['postdate']),
                            'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                            'tagged' => $tagged[0]['tagged'],
                            'lastview' =>date('F d, o g:i a', $lastView[0]['lastview']),
                            'userright' => $currentUser['rights'],
                            'postUserId' => $username->id,
                            'currentUserId' => $currentUser['id'],
                            'countArray' => $count,
                            'posttype' => $thread['posttype'],

                        );
                        array_push($threadArray, $temparray);
                        array_push($uniquesDataArray, $uniquesData);

                    }
                }
            }else if ($isValue == 2 || $isValue == 3)
            {
                foreach ($threads as $thread) {
                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getbythreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser,$thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $tagged = ForumView::forumViews($thread['threadid']);
                    if ($thread['postdate'] >=$lastView[0]['lastview'] && $currentUser['id'] != $username->id) {
                        $temparray = array
                        (
                            'parent' => $thread['parent'],
                            'threadId' => $thread['threadid'],
                            'forumiddata' => $thread['forumid'],
                            'subject' => $thread['subject'],
                            'views' => $thread['views'],
                            'replyby' => $thread['replyby'],
                            'postdate' => date('F d, o g:i a', $thread['postdate']),
                            'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                            'tagged' => $tagged[0]['tagged'],
                            'lastview' =>date('F d, o g:i a', $lastView[0]['lastview']),
                            'userright' => $currentUser['rights'],
                            'postUserId' => $username->id,
                            'currentUserId' => $currentUser['id'],
                            'countArray' => $count,
                            'posttype' => $thread['posttype'],
                        );

                       if($isValue == 3)
                       {
                            array_push($threadArray, $temparray);
                           $ViewData = new ForumView();
                           $ViewData->inserIntoTable($threadArray);
//                           $ViewData->updateData($threadArray[0]['threadId'],$currentUser);
                       }
                       else
                       {
                            array_push($threadArray, $temparray);
                       }
                        array_push($uniquesDataArray, $uniquesData);
                    }
                }
            }
            else {
                foreach ($threads as $thread) {

                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getbythreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser,$thread['threadid']);

                    $tagged = ForumView::forumViews($thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $temparray = array
                    (
                        'parent' => $thread['parent'],
                        'threadId' => $thread['threadid'],
                        'forumiddata' => $thread['forumid'],
                        'subject' => $thread['subject'],
                        'views' => $thread['views'],
                        'replyby' => $thread['replyby'],
                        'postdate' => date('F d, o g:i a', $thread['postdate']),
                        'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                        'tagged' => $tagged[0]['tagged'],
                        'userright' => $currentUser['rights'],
                        'lastview' =>date('F d, o g:i a', $lastView[0]['lastview']),
                        'postUserId' => $username->id,
                        'currentUserId' => $currentUser['id'],
                        'countArray' => $count,
                        'posttype' => $thread['posttype'],
                    );
                    array_push($threadArray, $temparray);
                  array_push($uniquesDataArray, $uniquesData);
                }
            }
            $FinalUniquesData = array();
            foreach($uniquesDataArray as $unique){
                foreach($unique as $un){
                    $username = User::getById($un['userid']);
            $temparrayForUnique = array(
                'threadId' => $un['threadid'],
                'lastView' => date('F d, o g:i a', $un['lastview']),
                'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
            );
            array_push($FinalUniquesData,$temparrayForUnique);}
            }

        }
        else
        {
            return $this->terminateResponse('');

        }
        $this->includeJS(['forum/forum.js']);
        $responseData = array('threadArray' => $threadArray,'uniquesDataArray' => $FinalUniquesData,'isValue'=>  $isValue);
        return $this->successResponse($responseData);

    }

//controller method for redirect to Move Thread page,This method is used to store moved thread data in database.
    public function actionMoveThread()
    {
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
                'enddate' => $forum->enddate
            );
            array_push($forumArray, $tempArray);
        }
        if ($thread) {
            $threadArray = array();
            foreach ($thread as $data) {
                $temparray = array(
                    'threadId' => $data['id'],
                    'forumiddata' => $data['forumid'],
                    'subject' => $data['subject'],
                    'parent' => $data['parent'],
                );
                array_push($threadArray, $temparray);
            }
            if ($this->isPost())
            {
                $paramas = $this->getRequestParams();
                $movetype = $paramas['movetype'];
                $thread_Id = $paramas['threadId'];
                if($movetype == 1)
                {
                    $moveThreadId = $paramas['thread-name'];
                    ForumPosts::updatePostMoveThread($thread_Id,$moveThreadId);
                }
                else
                {
                    $forum_Id = $paramas['forum-name'];
                    ForumPosts::updateMoveThread($forum_Id, $thread_Id);
                }

                $this->includeCSS(['forums.css']);
                $this->includeJS(['forum/thread.js?ver='.time().'']);
                $responseData = array('cid' => $courseId, 'users' => $user, 'forumid' => $forumId,'course' =>$course);
                return $this->renderWithData('thread',$responseData);

            }
            $this->setReferrer();
            $this->includeJS(['forum/movethread.js']);
            $responseData = array('forums' => $forumArray,'threads' => $threadArray,'threadId'=>$threadId,'forumId'=>$forumId,'course'=>$course,'user' => $user);
            return $this->renderWithData('moveThread',$responseData);
        }
    }
//controller method for redirect to modify post page with selected thread data.
    public function actionModifyPost()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $threadId = $this->getParamVal('threadId');
        $forumId = $this->getParamVal('forumId');
        $thread = ThreadForm::thread($forumId);
        $threadArray = array();
        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js','forum/modifypost.js']);
        foreach ($thread as $data)
        {
            if(($data['id']) == $threadId)
            {
                $temparray = array(
                    'threadId' => $data['threadid'],
                    'forumiddata' => $data['forumid'],
                    'subject' => $data['subject'],
                    'message' => $data['message'],
                );
                array_push($threadArray, $temparray);
            }
         }
        $this->setReferrer();
        $responseData = array('threadId' => $threadId,'forumId'=>$forumId,'course'=>$course,'thread'=>$threadArray,'currentUser' => $currentUser);
        return $this->renderWithData('modifyPost',$responseData);
    }
    //controller ajax method for fetch modified thread from Modify page and store in database.
    public function actionModifyPostAjax()
    {
        $params = $this->getBodyParams();
        $threadid = $params['threadId'];
        $message = trim($params['message']);
        $subject = trim($params['subject']);
        ForumPosts::modifyThread($threadid,$message,$subject);
        $this->includeJS(['forum/modifypost.js']);
        return $this->successResponse();
    }

/*Controller Action To Redirect To Post Page*/
    public function actionPost()
    {
        $this->guestUserHandler();
        $currentUser = $this->getAuthenticatedUser();

        $courseId=$this->getParamVal('cid');
        $course = Course::getById($courseId);
        $threadId = $this->getParamVal('threadid');
        $prev = $this->getParamVal('prev');
        $next = $this->getParamVal('next');
        if($next || $prev ){
            $prevNextValueArray = Thread::getNextThreadId($threadId,$next,$prev);
        $threadId = $prevNextValueArray['threadId'];
        }
        $isNew = ForumView::getById( $threadId, $currentUser);
        $tagValue = $isNew[0]['tagged'];
        $forumId = $this->getParamVal('forumid');
        $Fullthread = ForumPosts::getbyid($threadId);
        $data = array();
        if($currentUser['rights'] == AppConstant::NUMERIC_TEN && $Fullthread[0]['posttype']== AppConstant::NUMERIC_THREE ){
            $forumPostData = ForumPosts::getbyThreadIdAndUserID($threadId,$currentUser['id']);
            $parentThread = ForumPosts::getbyParentId($forumPostData[0]['parent']);
             array_push($data,$parentThread);
            foreach($forumPostData as $single)
            {
                array_push($data,$single);
            }
        }else{
            $data = ForumPosts::getbyid($threadId);
        }
        $titleCountArray = array();
         foreach ($data as $postdata)
        {
            $this->children[$postdata['parent']][] = $postdata['id'];
            $username = User::getById($postdata['userid']);
            $forumname = Forums::getById($postdata['forumid']);
            $titleLevel = AppUtility::calculateLevel($postdata['subject']);
            $likeimage = ForumLike::checkStatus($postdata['id'],$currentUser);
            $count = new ForumLike();
            $likecnt = $count->CalculateCount($postdata['id']);

            $studentCount = AppConstant::NUMERIC_ZERO;
            $teacherCount = AppConstant::NUMERIC_ZERO;

            foreach($likecnt as $like)
            {
                $Rights = User::getById($like['userid']);
                if($Rights->rights == AppConstant::STUDENT_RIGHT)
                {
                   $studentCount = $studentCount + 1;

                }elseif($Rights->rights >= AppConstant::TEACHER_RIGHT)
                {
                    $teacherCount = $teacherCount + 1;
                }
                $tempArray = array(
                    'postId' => $like['postid'],
                    'studentCount' => $studentCount,
                    'teacherCount' => $teacherCount,
                );

            }
            array_push($titleCountArray,$tempArray);
            $tempArray = array();
            $tempArray['id'] = $postdata['id'];
            $tempArray['threadId'] = $postdata['threadid'];
            $tempArray['forumiddata'] = $postdata['forumid'];
            $tempArray['subject'] = $titleLevel['title'];
            $tempArray['forumname'] = ucfirst($forumname->name);
            $tempArray['postdate'] = date('F d, o g:i a', $postdata->postdate);
            $tempArray['posttype'] = $postdata['posttype'];
            $tempArray['name'] = AppUtility::getFullName($username->FirstName, $username->LastName);
            $tempArray['userRights'] = $username->rights;
            $tempArray['userId'] = $username->id;
            $tempArray['settings'] = $forumname->settings;
            $tempArray['hasImg'] = $username->hasuserimg;
            $tempArray['likeimage'] = $likeimage;
            $tempArray['studentCount'] = $studentCount;
            $tempArray['teacherCount'] = $teacherCount;
            $tempArray['likecnt'] =count($likecnt);
            $tempArray['lastview'] = $isNew[0]['lastview'];
            $tempArray['message'] = $postdata['message'];
            $tempArray['level'] = $titleLevel['level'];
            $tempArray['replyby'] = $postdata['replyby'];
            $this->postData[$postdata['id']] = $tempArray;

        }

        ForumPosts::saveViews($threadId);
        $viewsData = new ForumView();
        $viewsData->updateData($threadId,$currentUser);
        $this->createChild($this->children[key($this->children)]);
        $Count = new ForumLike();
        $likeCount =$Count->findCOunt($threadId);
        $mylikes = $Count->UserLikes($threadId,$currentUser);
        $this->setReferrer();
        $this->includeCSS(['forums.css']);
        $this->includeJS(['forum/post.js']);

        $responseData = array('postdata' => $this->totalPosts,'course' => $course,'currentUser' => $currentUser,'forumId' => $forumId,'threadId'=>$threadId,'tagValue' => $tagValue,'prevNextValueArray' => $prevNextValueArray,'likeCount' =>$likeCount,'mylikes'=>$mylikes,'titleCountArray' =>$titleCountArray);
        return $this->render('post', $responseData);
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
    //controller ajax method for fetch select as remove thread from Thread page and remove from database.
    public function actionMarkAsRemoveAjax()
    {
            $params = $this->getBodyParams();
            $threadid = $params['threadId'];
            $checkPostOrThread = $params['checkPostOrThread'];
            ForumPosts::removeThread($threadid,$checkPostOrThread);
            ForumThread::removeThread($threadid);
            ForumView::removeThread($threadid);
            return $this->successResponse();
    }
    /*Controller Action To Reply To A Post*/
   public function actionReplyPost()
   {
       $this->guestUserHandler();
       $courseId = $this->getParamVal('cid');
       $course = Course::getById($courseId);
       $forumId = $this->getParamVal('forumid');
       $Id = $this->getParamVal('id');
       $threadid = $this->getParamVal('threadId');
       $threaddata =ForumPosts::getbyidpost($Id);
       $threadArray = array();
       foreach ($threaddata as $data)
       {
               $temparray = array
               (

                   'subject' => $data['subject'],

               );
               array_push($threadArray, $temparray);
       }
       $this->includeJS(['editor/tiny_mce.js' ,'editor/tiny_mce_src.js', 'general.js','forum/replypost.js']);
       $responseData = array('reply' => $threadArray,'course' => $course,'forumid' => $forumId,'threadid' => $threadid,'parentId' => $Id);
       return $this->renderWithData('replypost', $responseData);
   }
    public function actionReplyPostAjax()
    {
        $this->guestUserHandler();
        if ($this->isPost())
        {
            $params = $this->getRequestParams();
            $user =$this->getAuthenticatedUser();
            $reply = new ForumPosts();
            $reply->createReply($params,$user);
            return $this->successResponse();
        }
    }
    /*Controller Action To Redirect To New Thread Page*/
    public function actionAddNewThread()
    {
        $users = $this->getAuthenticatedUser();
        $userId = $this->getUserId();
        $rights =$users->rights;
        $forumId = $this->getParamVal('forumid');
        $courseId =  $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $forumName = Forums::getById($forumId);
        $this->includeJS(['editor/tiny_mce.js' ,'editor/tiny_mce_src.js', 'general.js','forum/addnewthread.js']);
        $responseData = array('forumName' => $forumName, 'course' => $course,'userId' => $userId,'rights' =>$rights);
        return $this->renderWithData('addNewThread',$responseData);
    }
/*Controller Action To Save The Newly Added Thread In Database*/
    public function actionAddNewThreadAjax()
    {
        $this->guestUserHandler();
        if ($this->isPost())
        {
            $params = $this->getRequestParams();
            $postType = AppConstant::NUMERIC_ZERO;
            if($this->getAuthenticatedUser()->rights >AppConstant::NUMERIC_TEN){
                $postType = $params['postType'];
            }
            $alwaysReplies = $params['alwaysReplies'];
            $date =strtotime($params['date'].' '.$params['time']);
            $userId = $this->getUserId();

            $newThread = new ForumPosts();
            $threadId = $newThread->createThread($params,$userId,$postType,$alwaysReplies,$date);
            $newThread = new ForumThread();
           $newThread->createThread($params,$userId,$threadId);
            $views = new ForumView();
            $views->createThread($userId,$threadId);
            return $this->successResponse();
        }
    }
    /*Controller Action To Toggle The Flag Image On Click*/
    public function actionChangeImageAjax()
    {
        $params = $this->getRequestParams();
        $rowId = $params['rowId'];
        if($rowId == -1){
            $threadId = $params['threadId'];
            $userId = $params['userId'];
            ForumView::deleteByUserIdAndThreadId($threadId,$userId);
        }else{
        ForumView::updateFlagValue($rowId);
    }
        return $this->successResponse() ;
    }

    /*Controller Action To Search All Post In A Forum*/
    public function actionGetSearchPostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseid =$params['courseid'];
        $now = time();
        $forum = Forums::getByCourseId( $courseid);
        $search = $params['search'];
        $checkBoxVal= $params['value'];
        $sort = AppConstant::DESCENDING;
        $orderby = 'postdate';
        $query= ForumForm::byAllpost($search,$sort,$orderby);
        if($query)
        {
            $searchpost = array();
            foreach($forum as $forumid){
                foreach ($query as $data)
                {

                    if($forumid['id'] == $data['forumid'] )
                    {
                        if($this->getAuthenticatedUser()->rights == AppConstant::NUMERIC_TEN)
                        {
                            if($forumid['enddate'] > $now)
                            {

                                $username = User::getById($data['userid']);
                                $postdate = Thread::getById($data['threadid']);
                                $forumname = Forums::getById($data['forumid']);
                                $temparray = array
                                (
                                    'forumiddata' => $data['forumid'],
                                    'threadId' => $data['threadid'],
                                    'subject' => $data['subject'],
                                    'views' => $data['views'],
                                    'forumname' => ucfirst($forumname->name),
                                    'postdate' => date('F d, o g:i a',$postdate->lastposttime),
                                    'name' => ucfirst($username->FirstName).' '.ucfirst($username->LastName),
                                    'message' => $data['message'],

                                );
                                array_push($searchpost, $temparray);
                            }
                        }else
                        {
                            $username = User::getById($data['userid']);
                            $postdate = Thread::getById($data['threadid']);
                            $forumname = Forums::getById($data['forumid']);
                            $temparray = array
                            (
                                'forumiddata' => $data['forumid'],
                                'threadId' => $data['threadid'],
                                'subject' => $data['subject'],
                                'views' => $data['views'],
                                'forumname' => ucfirst($forumname->name),
                                'postdate' => date('F d, o g:i a',$postdate->lastposttime),
                                'name' => ucfirst($username->FirstName).' '.ucfirst($username->LastName),
                                'message' => $data['message'],

                            );
                            array_push($searchpost, $temparray);
                        }

                    }
                }
            }
            $this->includeJS(['forum/forum.js','forum/thread.js']);
            $responseData = array('data' =>$searchpost , 'checkvalue' => $checkBoxVal,'search' => $search);
            return $this->successResponse($responseData);
        }else
        {
            return $this->terminateResponse('No data');
        }
    }

/*Controller Action To Search Post Of That Forum*/
    public function actionGetOnlyPostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $search = $params['search'];
        $forumid = $params['forumid'];
        $query= ForumForm::byAllpost($search);
        if($query)
        {
            $searchpost = array();
            foreach ($query as $data)
            {
                if($forumid == $data['forumid'])
                {
                    $username = User::getById($data['userid']);
                    $postdate = Thread::getById($data['threadid']);
                    $forumname = Forums::getById($data['forumid']);
                    $temparray = array
                    (
                        'forumiddata' => $data['forumid'],
                        'subject' => $data['subject'],
                        'views' => $data['views'],
                        'forumname' => ucfirst($forumname->name),
                        'postdate' => date('F d, o g:i a',$postdate->lastposttime),
                        'name' => ucfirst($username->FirstName).' '.ucfirst($username->LastName),
                        'message' => $data['message'],
                    );
                    array_push($searchpost, $temparray);
                }
            }
            $this->includeJS(['forum/forum.js','forum/thread.js']);
            $responseData = array('data' =>$searchpost);
            return $this->successResponse($responseData);
        }else
        {
            return $this->terminateResponse('');
        }
    }

    public function actionListPostByName()
    {
        $this->guestUserHandler();
        $userRights = $this->getAuthenticatedUser()->rights;
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $sort = AppConstant::DESCENDING;
        $forumid = $params['forumid'];
        $forumname = Forums::getById($forumid);
        $orderby = 'postdate';
        $thread = ThreadForm::postByName($forumid,$sort,$orderby);
        if($thread)
        {
                $nameArray = array();
            $sortbyname = array();
            $finalSortedArray = array();
                $threadArray = array();
                foreach ($thread as $data)
                {
                    $username = User::getById($data['userid']);
                     $temparray = array
                        (
                            'id' => $data['id'],
                            'parent' => $data['parent'],
                            'threadId' => $data['threadid'],
                            'forumiddata' => $data['forumid'],
                            'userId' => $username->id,
                            'hasImg' => $username->hasuserimg,
                            'subject' => $data['subject'],
                            'postdate' => date('F d, o g:i a', $data['postdate']),
                           'message' => $data['message'],
                            'name' => AppUtility::getFullName($username->LastName, $username->FirstName),
                        );
                    if(!in_array($temparray['name'],$nameArray))
                        array_push($nameArray,$temparray['name']);
                        array_push($threadArray, $temparray);
                    }
            sort($nameArray);
            foreach($nameArray as $name){
                foreach($threadArray as $threadA){
                    if($name == $threadA['name']){
                        array_push($finalSortedArray, $threadA);
                    }
                }
                array_push($sortbyname,$name);
            }
            $this->setReferrer();
            $this->includeCSS(['forums.css']);
            $status = AppConstant::NUMERIC_ONE;
            $responseData = array('threadArray' => $finalSortedArray,'forumid' => $forumid,'forumname' => $forumname,'course' => $course,'status' => $status,'userRights' => $userRights);
            return $this->renderWithData('listPostByName',$responseData);
        }
        else
        {
                $status = AppConstant::NUMERIC_ZERO;
            $responseData = array('status' => $status,'forumid' => $forumid,'course' => $course);
            return $this->renderWithData('listPostByName',$responseData);
        }
    }

    function actionReplyPostByName()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $forumId = $this->getParamVal('forumid');
        $Id = $this->getParamVal('replyto');
        $threadid = $this->getParamVal('threadId');
        $threaddata =ForumPosts::getbyidpost($Id);
        $threadArray = array();
        foreach ($threaddata as $data)
        {
            $temparray = array
            (

                'subject' => $data['subject'],

            );
            array_push($threadArray, $temparray);
        }
        $this->includeJS(['editor/tiny_mce.js' ,'editor/tiny_mce_src.js', 'general.js','forum/replypostbyname.js']);
        $responseData = array('reply' => $threadArray,'courseid' => $courseId,'forumid' => $forumId,'threadid' => $threadid);
        return $this->renderWithData('replyPostByName',$responseData);
    }
    public function actionReplyListPostAjax()
    {
        $this->guestUserHandler();
        if ($this->isPost())
        {
            $params = $this->getRequestParams();
            $user =$this->getAuthenticatedUser();
            $reply = new ForumPosts();
            $reply->createReply($params,$user);
            return $this->successResponse();
        }
    }

    public function actionLikePostAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getAuthenticatedUser()->id;
        $params = $this->getRequestParams();
        $like = $params['like'];
        if ($this->isPost())
        {

            if($like == 0)
            {
                $like = new ForumLike();
                $like->InsertLike($params,$userId);

//                $count = new ForumLike();
//                $count->checkCOunt($params);
            }
            elseif($like == 1)
            {
                $like = new ForumLike();
                $like->DeleteLike($params,$userId);
            }
        }


        return $this->successResponse();
    }

    public function actionDataLikePostAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getAuthenticatedUser()->id;
        $params = $this->getRequestParams();
        $count = new ForumLike();
        $displayCountData =  $count->checkCount($params);
        $countDataArray = array();

        foreach($displayCountData as $data){
             $user = User::getById($data->userid);
            $temparray = array('id' => $data->userid, 'userName' => AppUtility::getFullName($user->FirstName,$user->LastName));
            array_push($countDataArray, $temparray);
        }
        $responseData = array('displayCountData' =>$countDataArray);
        return $this->successResponse($responseData);
    }

}