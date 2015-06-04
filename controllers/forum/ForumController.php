<?php
namespace app\controllers\forum;

use app\models\Course;
use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\forms\ThreadForm;
use app\models\ForumPosts;
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
        if ($model->load(Yii::$app->request->post())) {
            $param = $this->getBodyParams();
            $search = $param['ForumForm']['search'];
        }
        $this->includeCSS(['../css/forums.css']);
        return $this->renderWithData('forum', ['model' => $model, 'forum' => $forum, 'cid' => $cid, 'users' => $user,'course' => $course]);
    }

    public function actionGetForumNameAjax()
    {

        $this->guestUserHandler();
        $param = $this->getBodyParams();
        $search = $param['search'];
        $cid = $param['cid'];
        $checkBoxVal = $param['value'];
        $query = ForumForm::byAllSubject($search);
        if ($query) {
            $queryarray = array();
            foreach ($query as $data) {
                $username = User::getById($data['userid']);
                $postdate = Thread::getById($data['threadid']);


                $temparray = array(
                    'forumiddata' => $data['forumid'],
                    'subject' => $data['subject'],
                    'views' => $data['views'],
                    'replyby' => $data['replyby'],
                    'postdate' => date('F d, o g:i a', $postdate->lastposttime),
                    'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),

                );

                array_push($queryarray, $temparray);

            }

            return json_encode(array('status' => 0, 'data' => $queryarray, 'checkvalue' => $checkBoxVal, 'search' => $search));
        } else {
            return json_encode(array('status' => -1, 'msg' => 'Forums not found for this course.'));
        }

    }
    public function actionGetSearchPostAjax()
    {

        $this->guestUserHandler();
        $param = $this->getBodyParams();
        $search = $param['search'];
        $checkBoxVal= $param['value'];
        $query= ForumForm::byAllSubject($search);


        if($query)
        {
            $queryarray = array();
            foreach ($query as $data)
            {
                $username = User::getById($data['userid']);
                $postdate = Thread::getById($data['threadid']);
                $forumname = Forums::getById($data['forumid']);




                $temparray = array(
                    'forumiddata' => $data['forumid'],
                    'subject' => $data['subject'],
                    'views' => $data['views'],
                    'forumname' => ucfirst($forumname->name),
                    'postdate' => date('F d, o g:i a',$postdate->lastposttime),
                    'name' => ucfirst($username->FirstName).' '.ucfirst($username->LastName),
                    'message' => $data['message'],

                );

                array_push($queryarray,$temparray);

            }

            return json_encode(array('status' => 0, 'data' =>$queryarray , 'checkvalue' => $checkBoxVal,'search' => $search));
//            return RedirectToAction('forum',['data' =>$queryarray , 'checkvalue' => $checkBoxVal,'search' => $search]);
        }else{
            return json_encode(array('status' => -1, 'msg' => 'Forums not found for this course.'));
        }

    }


    public function actionGetForumsAjax()
    {
        $this->guestUserHandler();

        $param = $this->getBodyParams();
        $cid = $param['cid'];
        $forums = Forums::getByCourseId($cid);

        if ($forums) {
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
                    'lastPostDate' => ($lastObject != '') ? date('M-d-Y h:i s', $lastObject->postdate) : ''
                );
                array_push($forumArray, $tempArray);
            }
            $this->includeCSS(['../css/forums.css']);
            return json_encode(array('status' => 0, 'forum' => $forumArray));
        } else {
            return json_encode(array('status' => -1, 'msg' => 'Forums not found for this course.'));
        }
    }


    public function actionThread()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        $forumid = Yii::$app->request->get('forumid');
        $forum = Forums::getByCourseId($cid);
        $user = Yii::$app->user->identity;
        $this->includeCSS(['../css/forums.css']);
        $this->includeJS(['../js/thread.js']);

        return $this->renderWithData('thread', ['cid' => $cid, 'users' => $user, 'forumid' => $forumid,'course' =>$course]);
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

                $temparray = array(
                    'parent' => $data['parent'],
                    'threadId' => $data['id'],
                    'forumiddata' => $data['forumid'],
                    'subject' => $data['subject'],
                    'views' => $data['views'],
                    'replyby' => $data['replyby'],
                    'postdate' => date('F d, o g:i a',$data['postdate']),
                    'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),
                );
            array_push($threadArray, $temparray);

            }
        $this->includeJS(['../js/thread.js']);
        return json_encode(array('status' => 0, 'threadData' => $threadArray));


    }
//controller method for redirect to Move Thread page,This method is used to store moved thread data in database.
    public function actionMoveThread()
    {
        $courseId = Yii::$app->request->get('courseId');
        $threadId = Yii::$app->request->get('threadId');
        $forumId = Yii::$app->request->get('forumId');
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
                );
                array_push($threadArray, $temparray);
            }
            if ($this->isPost()) {
                $paramas = $this->getRequestParams();
                $thread_Id = $paramas['threadId'];


//                if($moveThreadId){
//                    $moveThreadId = $paramas['thread-name'];
//                    ForumPosts::updatePostMoveThread($thread_Id,$moveThreadId);
//                }
               // if($forum_Id) {
                    $forum_Id = $paramas['forum-name'];
                    ForumPosts::updateMoveThread($forum_Id, $thread_Id);
                //}
                $this->includeCSS(['../css/forums.css']);
                $this->includeJS(['../js/thread.js']);
                return $this->renderWithData('thread', ['cid' => $courseId, 'users' => $user, 'forumid' => $forumId]);
            }
            return $this->renderWithData('moveThread', ['forums' => $forumArray,'threads' => $threadArray,'threadId'=>$threadId,'forumId'=>$forumId,'courseId'=>$courseId]);
        }

    }
//controller method for redirect to modify post page with selected thread data.
    public function actionModifyPost()
    {
        $this->guestUserHandler();
        $courseId = Yii::$app->request->get('courseId');
        $threadId = Yii::$app->request->get('threadId');
        $forumId = Yii::$app->request->get('forumId');
        $thread = ThreadForm::thread($forumId);
        $threadArray = array();
        $this->includeJS(["../js/editor/tiny_mce.js" , '../js/editor/tiny_mce_src.js', '../js/general.js', '../js/editor/plugins/asciimath/editor_plugin.js', '../js/editor/themes/advanced/editor_template.js','../js/modifypost.js']);
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
        return $this->renderWithData('modifyPost', ['threadId' => $threadId,'forumId'=>$forumId,'courseId'=>$courseId,'thread'=>$threadArray]);
    }
    //controller ajax method for fetch modified thread from Modify page and store in database.
    public function actionModifyPostAjax()
    {
        $params = $this->getBodyParams();
        $threadid = $params['threadId'];
        $message = trim($params['message']);
        $subject = trim($params['subject']);
        $thread = ForumPosts::modifyThread($threadid,$message,$subject);
        return json_encode(array('status' => 0));
    }

    public function actionPost()
    {
        $this->guestUserHandler();
        $threadId = Yii::$app->request->get('threadid');
        $data = ForumPosts::getbyid($threadId);
         $postArray = array();

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
            $tempArray['name'] = ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName);
            $tempArray['message'] = $postdata['message'];
           $tempArray['level'] = $titleLevel['level'];
            $this->postData[$postdata['id']] = $tempArray;
        }
//        AppUtility::dump($this->children);
        $this->createChild($this->children[key($this->children)]);
        $this->includeCSS(['../css/forums.css']);
        return $this->render('post',['postdata' => $this->totalPosts]);
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
            return json_encode(array('status' => 0));

    }

   public function actionReplyPost()
   {
       $this->guestUserHandler();
//       $courseId = Yii::$app->request->get('courseId');
       $threadId = Yii::$app->request->get('id');
//         $forumId = Yii::$app->request->get('forumId');
       $thread =ForumPosts::getbyidpost($threadId);

       $threadArray = array();
       foreach ($thread as $data)
       {


               $temparray = array
               (

                   'subject' => $data['subject'],

               );
               array_push($threadArray, $temparray);


       }

       $this->includeJS(["../js/editor/tiny_mce.js" , '../js/editor/tiny_mce_src.js', '../js/general.js', '../js/editor/plugins/asciimath/editor_plugin.js', '../js/editor/themes/advanced/editor_template.js']);

   return $this->renderWithData('replypost',['reply' => $threadArray]);

   }

}