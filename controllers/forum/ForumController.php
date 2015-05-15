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

        $query= Yii::$app->db->createCommand("SELECT * from imas_forums where name LIKE '$search%'")->queryAll();
        AppUtility::dump($query);
   // return json_encode(array('status' => 0, 'query' => $query));


    }

    public function actionForumSearch()
    {
        $this->guestUserHandler();

        $cid = $this->getBodyParams();
        $forum = Forums::getByCourseId($cid);
        $user = Yii::$app->user->identity;
        $model = new ForumForm();
        if ($model->load(Yii::$app->request->post()))
        {
            $param = $this->getBodyParams();
            $search = $param['ForumForm']['search'];
        }
        $this->includeCSS(['../css/forums.css']);

        return $this->renderWithData('forum',['model' => $model,'forum' =>$forum, 'cid' =>$cid]);
    }
}