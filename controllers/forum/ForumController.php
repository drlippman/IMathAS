<?php
namespace app\controllers\forum;

use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\forms\ThreadForm;
use app\models\Forums;
use app\models\Thread;
use app\models\User;
use app\components\AppUtility;
use app\components\AppConstant;
use Yii;

class ForumController extends AppController{


    public function actionSearchForum()
    {
        $this->guestUserHandler();

        $cid = Yii::$app->request->get('cid');
        $forum = Forums::getByCourseId($cid);
        $user = Yii::$app->user->identity;
        $model = new ForumForm();
        $model->thread ='subject';
        if ($model->load(Yii::$app->request->post()))
        {
            $param = $this->getBodyParams();
            $search = $param['ForumForm']['search'];
        }
        $this->includeCSS(['../css/forums.css']);
        return $this->renderWithData('forum',['model' => $model,'forum' =>$forum, 'cid' =>$cid, 'users' => $user]);
    }
    public function actionGetForumNameAjax(){

        $this->guestUserHandler();
        $param = $this->getBodyParams();
        $search = $param['search'];
        $cid = $param['cid'];
        $checkBoxVal= $param['value'];
        $query= ForumForm::byAllSubject($search);
        if($query)
        {
            $queryarray = array();
            foreach ($query as $data)
            {
                $username = User::getById($data['userid']);
                $postdate = Thread::getById($data['threadid']);


                $temparray = array(
                    'forumiddata' => $data['forumid'],
                    'subject' => $data['subject'],
                    'views' => $data['views'],
                    'replyby' => $data['replyby'],
                    'postdate' => date('F d, o g:i a',$postdate->lastposttime),
                    'name' => ucfirst($username->FirstName).' '.ucfirst($username->LastName),

                );

                array_push($queryarray,$temparray);

            }

            return json_encode(array('status' => 0, 'data' =>$queryarray , 'checkvalue' => $checkBoxVal,'search' => $search));
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

            if($forums)
            {
                $forumArray = array();
                foreach($forums as $key => $forum)
                {
                    $threadCount = count($forum->imasForumThreads);
                    $postCount = count($forum->imasForumPosts);
                    $lastObject = '';
                    if($postCount > 0)
                    {
                        $lastObject = $forum->imasForumPosts[$postCount-1];
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
                return json_encode(array('status' => 0, 'forum' =>$forumArray ));
            }else{
                return json_encode(array('status' => -1, 'msg' => 'Forums not found for this course.'));
            }
    }


    public function actionThread()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $forumid = Yii::$app->request->get('forumid');
        $forum = Forums::getByCourseId($cid);
        $user = Yii::$app->user->identity;
        $this->includeCSS(['../css/forums.css']);
        $this->includeJS(['../js/thread.js']);

        return $this->renderWithData('thread',['forum' =>$forum, 'cid' =>$cid, 'users' => $user,'forumid' => $forumid]);
    }

    public function actionGetThreadAjax(){
        $param = $this->getBodyParams();
        $forumid = $param['forumid'];
        $thread = ThreadForm::thread($forumid);
        if($thread)
        {

                $threadArray = array();

                foreach ($thread as $data)
                {
                    $username = User::getById($data['userid']);
                    $postdate = Thread::getById($data['threadid']);


                            $temparray = array(
                           'forumiddata' => $data['forumid'],
                           'subject' => $data['subject'],
                           'views' => $data['views'],
                           'replyby' => $data['replyby'],
                           'postdate' => date('F d, o g:i a',$postdate->lastposttime),
                            'name' => ucfirst($username->FirstName).' '.ucfirst($username->LastName),

                    );

                    array_push($threadArray,$temparray);


                }

            return json_encode(array('status' => 0, 'threadData' => $threadArray));
        }
        else{
            return json_encode(array('status' => -1, 'msg' => 'Forums not found for this course.'));
        }

    }

}