<?php
namespace app\controllers\forum;
use app\components\AppConstant;
use app\models\Course;
use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\forms\ThreadForm;
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
        $this->includeCSS(['forums.css','dashboard.css']);
        $this->includeJS(['forum/forum.js','general.js?ver=012115']);
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
             return $this->terminateResponse(message);
            }
    }
    /*Controller Action To Search All Post In A Forum*/
    public function actionGetSearchPostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseid =$params['courseid'];
         $forum = Forums::getByCourseId( $courseid);
        $search = $params['search'];
        $checkBoxVal= $params['value'];
        $query= ForumForm::byAllpost($search);
        if($query)
        {
            $searchpost = array();
            foreach($forum as $forumid){
            foreach ($query as $data)
            {

                if($forumid['id'] == $data['forumid'] )
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
            $this->includeJS(['forum/forum.js','forum/thread.js']);
            $responseData = array('data' =>$searchpost , 'checkvalue' => $checkBoxVal,'search' => $search);
            return $this->successResponse($responseData);
        }else
        {
          return $this->terminateResponse(message);
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
                if ($postCount > 0) {
                    $lastObject = $forum->imasForumPosts[$postCount - 1];
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
            return $this->terminateResponse(message);
        }
    }

/*Controller Action To Redirect To Thread Page*/
    public function actionThread()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $forumid = $this->getParamVal('forumid');
        $user = $this->getAuthenticatedUser();
        $this->includeCSS(['forums.css']);
        $this->includeJS(['forum/thread.js?ver='.time().'']);
        $responseData = array('cid' => $cid, 'users' => $user, 'forumid' => $forumid,'course' =>$course);
        return $this->renderWithData('thread',$responseData);
    }
    /*Controller Action To Display The Thraeds Present In That Particular Forum */
    public function actionGetThreadAjax()
    {
        $params = $this->getBodyParams();
        $currentUser = $this->getAuthenticatedUser();
        $ShowRedFlagRow = $params['ShowRedFlagRow'];
        $forumid = $params['forumid'];
        $threads = ThreadForm::thread($forumid);
        if(!empty($threads)) {
            $threadArray = array();
            $uniquesDataArray = array();
            if ($ShowRedFlagRow == 1) {
                foreach ($threads as $thread) {

                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getbythreadId($thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $tagged = ForumView::forumViews($thread['threadid']);
                    if ($tagged[0]['tagged'] == 1) {
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
                            'postUserId' => $username->id,
                            'currentUserId' => $currentUser['id'],
                            'countArray' => $count,
                            'posttype' => $thread['posttype'],
                        );
                        array_push($threadArray, $temparray);
                        array_push($uniquesDataArray, $uniquesData);

                    }
                }
            } else {
                foreach ($threads as $thread) {

                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getbythreadId($thread['threadid']);
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
                        'postUserId' => $username->id,
                        'currentUserId' => $currentUser['id'],
                        'countArray' => $count,
                        'posttype' => $thread['posttype'],
                    );
                    array_push($threadArray, $temparray);
//                    foreach($uniquesData as )
//                    {
//
//                    }
                    array_push($uniquesDataArray, $uniquesData);
                }
            }
        }
        else
        {
            return $this->terminateResponse('');

        }
        $this->includeJS(['forum/forum.js']);
        $responseData = array('threadArray' => $threadArray,'uniquesDataArray' => $uniquesDataArray);
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
            $this->includeJS(['forum/movethread.js']);
            $responseData = array('forums' => $forumArray,'threads' => $threadArray,'threadId'=>$threadId,'forumId'=>$forumId,'courseId'=>$courseId);
            return $this->renderWithData('moveThread',$responseData);
        }
    }
//controller method for redirect to modify post page with selected thread data.
    public function actionModifyPost()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
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
        $responseData = array('threadId' => $threadId,'forumId'=>$forumId,'courseId'=>$courseId,'thread'=>$threadArray);
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
        $courseid=$this->getParamVal('courseid');
        $threadId = $this->getParamVal('threadid');
        $Fullthread = ForumPosts::getbyid($threadId);
        $data = array();
        if($currentUser['rights'] == 10 && $Fullthread[0]['posttype']== 3 ){
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
         foreach ($data as $postdata)
        {
            $this->children[$postdata['parent']][] = $postdata['id'];
            $username = User::getById($postdata['userid']);
            $postdate = Thread::getById($postdata['threadid']);
            $forumname = Forums::getById($postdata['forumid']);
            $titleLevel = AppUtility::calculateLevel($postdata['subject']);
            $tempArray = array();
            $tempArray['id'] = $postdata['id'];
            $tempArray['threadId'] = $postdata['threadid'];
            $tempArray['forumiddata'] = $postdata['forumid'];
            $tempArray['subject'] = $titleLevel['title'];
            $tempArray['forumname'] = ucfirst($forumname->name);
            $tempArray['postdate'] = date('F d, o g:i a', $postdate->lastposttime);
            $tempArray['posttype'] = $postdata['posttype'];
            $tempArray['name'] = AppUtility::getFullName($username->FirstName, $username->LastName);
            $tempArray['userRights'] = $username->rights;
            $tempArray['userId'] = $username->id;
            $tempArray['message'] = $postdata['message'];
            $tempArray['level'] = $titleLevel['level'];
            $tempArray['replyby'] = $postdata['replyby'];
            $this->postData[$postdata['id']] = $tempArray;
        }
        ForumPosts::saveViews($threadId);
        $this->createChild($this->children[key($this->children)]);
        $this->includeCSS(['forums.css']);
        //$this->includeJS(['forum/post.js']);
        $responseData = array('postdata' => $this->totalPosts,'courseid' => $courseid,'currentUser' => $currentUser);
        return $this->render('post', $responseData);
    }
    public function createChild($childArray, $arrayKey = 0)
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
            ForumPosts::removeThread($threadid);
            return $this->successResponse();
    }
    /*Controller Action To Reply To A Post*/
   public function actionReplyPost()
   {
       $this->guestUserHandler();
        $courseId = $this->getParamVal('courseid');
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
       $responseData = array('reply' => $threadArray,'courseid' => $courseId,'forumid' => $forumId,'threadid' => $threadid);
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
        $courseid =  $this->getParamVal('cid');
        $forumName = Forums::getById($forumId);
        $this->includeJS(['editor/tiny_mce.js' ,'editor/tiny_mce_src.js', 'general.js','forum/addnewthread.js']);
        $responseData = array('forumName' => $forumName, 'courseid' => $courseid,'userId' => $userId,'rights' =>$rights);
        return $this->renderWithData('addNewThread',$responseData);
    }
/*Controller Action To Save The Newly Added Thread In Database*/
    public function actionAddNewThreadAjax()
    {
        $this->guestUserHandler();
        if ($this->isPost())
        {
            $params = $this->getRequestParams();
            if($this->getAuthenticatedUser()->rights >10){
                $postType = $params['postType'];
            }
            $postType = 0;
            $alwaysReplies = $params['alwaysReplies'];
            $date =strtotime($params['date'].' '.$params['time']);
            $userId = $this->getUserId();
            $newThread = new ForumThread();
            $threadId = $newThread->createThread($params,$userId);
            $newThread = new ForumPosts();
            $newThread->createThread($params,$userId,$threadId,$postType,$alwaysReplies,$date);
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
        ForumView::updateFlagValue($rowId);
        return $this->successResponse() ;
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
        $params = $this->getRequestParams();
        $courseid = $this->getParamVal('cid');
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

            $responseData = array('threadArray' => $finalSortedArray,'forumid' => $forumid,'forumname' => $forumname,'courseid' => $courseid);
            return $this->renderWithData('listpostbyname',$responseData);
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

}