<?php
namespace app\controllers\forum;

use app\models\Course;
use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\forms\ForumViews;
use app\models\forms\ThreadForm;
use app\models\ForumPosts;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\Forums;
use app\models\Thread;
use app\models\User;
use app\components\AppUtility;
use app\components\AppConstant;
use Yii;

class ForumController extends AppController
{

    public $postData = array();
    public $totalPosts = array();
    public $children = array();

    public function actionSearchForum()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $forum = Forums::getByCourseId($cid);
        $course = Course::getById($cid);
        $user = Yii::$app->user->identity;
        $model = new ForumForm();
        $model->thread = 'subject';
        $this->includeCSS(['forums.css','dashboard.css']);
        $this->includeJS(['forum/forum.js','general.js?ver=012115']);
        $responseData = array('model' => $model, 'forum' => $forum, 'cid' => $cid, 'users' => $user,'course' => $course);
        return $this->renderWithData('forum',$responseData);
    }
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
    public function actionGetSearchPostAjax()
    {
        $this->guestUserHandler();
        $param = $this->getBodyParams();
        $search = $param['search'];
        $checkBoxVal= $param['value'];
        $query= ForumForm::byAllpost($search);
        if($query)
        {
            $searchpost = array();
            foreach ($query as $data)
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
            $this->includeJS(['forum/forum.js']);
            $responseData = array('data' =>$searchpost , 'checkvalue' => $checkBoxVal,'search' => $search);
            return $this->successResponse($responseData);
        }else
        {
          return $this->terminateResponse(message);
        }

    }


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
                    'lastPostDate' => ($lastObject != '') ? date('M-d-Y h:i s', $lastObject->postdate) : ''
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

    public function actionThread()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $forumid = $this->getParamVal('forumid');
//        $forum = Forums::getByCourseId($cid);
        $user = Yii::$app->user->identity;
        $this->includeCSS(['forums.css']);
        $this->includeJS(['forum/thread.js']);
        $responseData = array('cid' => $cid, 'users' => $user, 'forumid' => $forumid,'course' =>$course);
        return $this->renderWithData('thread',$responseData);
    }

    public function actionGetThreadAjax()
    {
        $params = $this->getBodyParams();
        $forumid = $params['forumid'];
        $thread = ThreadForm::thread($forumid);
        $threadArray = array();
            foreach ($thread as $data)
            {
                    $username = User::getById($data['userid']);
                    $uniques = ForumView::getbythreadId($data['threadid']);
                    $tagged = ForumView::forumViews($data['threadid']);
                    $temparray = array
                    (

                        'parent' => $data['parent'],
                        'threadId' => $data['threadid'],
                        'forumiddata' => $data['forumid'],
                        'subject' => $data['subject'],
                        'views' => $data['views'],
                        'replyby' => $data['replyby'],
                        'postdate' => date('F d, o g:i a',$data['postdate']),
                         'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                        'tagged' =>$tagged[0]['tagged'],
                    );
                          array_push($threadArray, $temparray);
            }
        $this->includeJS(['forum/thread.js','forum/forum.js']);
        return $this->successResponse($threadArray);
    }
//controller method for redirect to Move Thread page,This method is used to store moved thread data in database.
    public function actionMoveThread()
    {
        $courseId = $this->getParamVal('courseId');
        $threadId = $this->getParamVal('threadId');
        $forumId = $this->getParamVal('forumId');
        $forums = Forums::getByCourseId($courseId);
        $thread = ThreadForm::thread($forumId);
        $user = Yii::$app->user->identity;
        $forumArray = array();
        foreach ($forums as $key => $forum) {

            $tempArray = array
            (
                'forumId' => $forum->id,
                'forumName' => $forum->name,
                'courseId' => $forum->courseid

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
                $this->includeJS(['thread.js']);
                $responseData = array('cid' => $courseId, 'users' => $user, 'forumid' => $forumId);
                return $this->renderWithData('thread',$responseData);

            }
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
        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js','modifypost.js']);
        foreach ($thread as $data)
        {
            if(($data['threadid']) == $threadId)
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
        $thread = ForumPosts::modifyThread($threadid,$message,$subject);
        $this->includeJS(['forum/modifypost.js']);
        return $this->successResponse();
    }

    public function actionPost()
    {
        $this->guestUserHandler();
        $courseid=Yii::$app->request->get('courseid');
        $threadId = $this->getParamVal('threadid');
        $data = ForumPosts::getbyid($threadId);
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
            $tempArray['name'] = AppUtility::getFullName($username->FirstName, $username->LastName);
            $tempArray['message'] = $postdata['message'];
           $tempArray['level'] = $titleLevel['level'];
            $this->postData[$postdata['id']] = $tempArray;
        }
        $this->createChild($this->children[key($this->children)]);
        $this->includeCSS(['forums.css']);
        $this->includeJS(['forum/post.js']);
        $responseData = array('postdata' => $this->totalPosts,'courseid' => $courseid);
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

   public function actionReplyPost()
   {
       $this->guestUserHandler();
        $courseId = Yii::$app->request->get('courseid');
       $forumId = Yii::$app->request->get('forumid');
       $Id = Yii::$app->request->get('id');
       $threadid = Yii::$app->request->get('threadId');
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

    public function actionAddNewThread()
    {
        $this->guestUserHandler();
        $userId = $this->getUserId();
        $forumId = $this->getParamVal('forumid');
        $courseid =  $this->getParamVal('cid');
        $forumName = Forums::getById($forumId);
        $this->includeJS(['editor/tiny_mce.js' ,'editor/tiny_mce_src.js', 'general.js','forum/addnewthread.js']);
        $responseData = array('forumName' => $forumName, 'courseid' => $courseid,'userId' => $userId);
        return $this->renderWithData('addNewThread',$responseData);

    }

    public function actionAddNewThreadAjax()
    {
        $this->guestUserHandler();
        if ($this->isPost())
        {
            $params = $this->getRequestParams();
            $userId = $this->getUserId();
            $newThread = new ForumThread();
            $newThread->createThread($params,$userId);
//
//            $newThread = new ForumPosts();
//            $newThread->createThread($params,$userId);

            return $this->successResponse();

        }

    }

    public function actionChangeImageAjax()
    {
        $params = $this->getBodyParams();
        $rowId = $params['rowId'];
        ForumView::updateFlagValue($rowId);
        return $this->successResponse();
    }

    public function actionReplyPostAjax()
    {
        $this->guestUserHandler();
        if ($this->isPost())
        {
            $params = Yii::$app->request->getBodyParams();
            $user = Yii::$app->user->identity;
            $reply = new ForumPosts();
            $reply->createReply($params,$user);
            return $this->successResponse();
        }
    }


}