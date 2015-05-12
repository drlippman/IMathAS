<?php

namespace app\controllers\message;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Course;
use app\models\Message;
use app\models\Teacher;
use app\models\User;
use Yii;
use app\controllers\AppController;
use app\models\forms\MessageForm;


class MessageController extends AppController
{

    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        if ($this->getAuthenticatedUser()) {
            $model = new MessageForm();
            $course = Course::getById($cid);
            return $this->renderWithData('messages', ['model' => $model, 'course' => $course]);
        }

    }

    public function actionSendMessage()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        if ($this->getAuthenticatedUser()) {
            $course = Course::getById($cid);
            $teacher = Teacher::getTeachersById($cid);
            $sortBy = 'FirstName';
            $order = AppConstant::ASCENDING;
            $users = User::findAllUsers($sortBy, $order);
            $this->includeCSS(["../css/message.css"]);
            $this->includeJS(["../js/editor/tiny_mce.js"]);
            $this->includeJS(["../js/editor/tiny_mce_src.js"]);
            $this->includeJS(["../js/editor/themes/advanced/editor_template.js"]);
            $this->includeJS(["../js/editor/plugins/asciimath/editor_plugin.js"]);
            $this->includeJS(["../js/general.js"]);
            return $this->renderWithData('sendMessage',['course' => $course, 'teachers' => $teacher, 'users' => $users]);
        }
    }

    public function actionConfirmMessage()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post())
        {
            $params = $this->getBodyParams();

            if ($params['receiver'] != 0 && $params['cid'] != null)
            {
                $message = new Message();
                $message->create($params);
            }
            return json_encode(array('status' => 0));
        }
    }

}