<?php
namespace app\controllers\forum;

use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\Forums;
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
        $temparray= array();
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
        $query= Yii::$app->db->createCommand("SELECT * from imas_forums where name LIKE '$search%'")->queryAll();
        $forums = Forums::getByCourseId($cid);
        $result =ForumForm::byAllSubject($search);
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

            return json_encode(array('status' => 0, 'forum' =>$forumArray, 'searchData' => $query,'bySubject' => $result,'checkVal' => $checkBoxVal,'courseId' => $cid));
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

        return $this->renderWithData('thread',['forum' =>$forum, 'cid' =>$cid, 'users' => $user,'forumid' => $forumid]);
    }

    public function actionGetThreadAjax(){


        $param = $this->getBodyParams();
        $forumid = $param['forumid'];
        $thread = ForumForm::thread($forumid);

        if($thread)
        {

            return json_encode(array('status' => 0, 'threadData' => $thread));
        }
        else{
            return json_encode(array('status' => -1, 'msg' => 'Forums not found for this course.'));
        }

    }

}