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
use yii\db\Query;
use yii\helpers\ArrayHelper;


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
            $sortBy = 'FirstName';
            $order = AppConstant::ASCENDING;
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($cid);
            $messages = Message::getByCourseId($cid);
            $senders = Message::getSenders($cid);
            return $this->renderWithData('messages', ['model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher]);
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
            return $this->renderWithData('sendMessage', ['course' => $course, 'teachers' => $teacher, 'users' => $users]);
        }
    }

    public function actionConfirmMessage()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
            $params = $this->getBodyParams();

            if ($params['receiver'] != 0 && $params['cid'] != null) {
                $message = new Message();
                $message->create($params);
            }
            return json_encode(array('status' => 0));
        }
    }

    public function actionDisplayMessageAjax()
    {

        if (!$this->isGuestUser()) {
           $uid = Yii::$app->user->identity->getId();
            $query = Yii::$app->db->createCommand("SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.isread,imas_courses.name,imas_msgs.msgfrom,imas_users.hasuserimg FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE imas_msgs.msgto='$uid' AND (imas_msgs.isread&2)=0")->queryAll();// AND (imas_msgs.isread&3)=0";
            return json_encode(array('status' => 0, 'messageData' => $query));
        }
    }

    public function actionSentMessage()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');

        if ($this->getAuthenticatedUser()) {
            $model = new MessageForm();
            $course = Course::getById($cid);
            $sortBy = 'FirstName';
            $order = AppConstant::ASCENDING;
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($cid);
            $messages = Message::getByCourseId($cid);
            $senders = Message::getSenders($cid);
            return $this->renderWithData('sentMessage', ['model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher]);
        }
    }

    public function actionDisplaySentMessageAjax()
    {
        if (!$this->isGuestUser()) {
            $uid = Yii::$app->user->identity->getId();
            $query = Yii::$app->db->createCommand("SELECT imas_msgs.id,imas_msgs.title,imas_msgs.msgTo,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.isread FROM imas_msgs,imas_users WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom='$uid' AND (imas_msgs.isread&4)=0 ")->queryAll();
            return json_encode(array('status' => 0, 'messageData' => $query));
        }

    }


    public function actionGetCourseAjax()
    {
        $this->guestUserHandler();

        $user = $this->getAuthenticatedUser();
        $params = Yii::$app->request->getBodyParams();
        $cid = $params['cid'];
        $userId = $params['userId'];
        $teachers = Teacher::getTeacherByUserId($userId);
        $teacherArray = array();
        foreach ($teachers as $teacher) {
            $tempArray = array('courseId' => $teacher->course->id,
                'courseName' => $teacher->course->name);

            array_push($teacherArray, $tempArray);
        }
        return json_encode(array('status' => 0, 'courseData' => $teacherArray));
    }

    public function actionMarkAsUnreadAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {

            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsg'];
            foreach ($msgIds as $msgId) {
                Message::updateUnread($msgId);

            }
            return json_encode(array('status' => 0));
        }

    }

    public function actionMarkAsReadAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsg'];
            foreach ($msgIds as $msgId) {
                Message::updateRead($msgId);
            }
            return json_encode(array('status' => 0));
        }
    }


    public function actionGetUserAjax()
    {
        $this->guestUserHandler();

        $user = $this->getAuthenticatedUser();
        $params = Yii::$app->request->getBodyParams();
        $cid = $params['cid'];
        $userId = $params['userId'];
        $query = Yii::$app->db->createCommand("SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users JOIN imas_msgs ON imas_msgs.msgfrom=imas_users.id WHERE imas_msgs.msgto='$userId'")->queryAll();

        return json_encode(array('status' => 0, 'userData' => $query));
    }

    public function actionViewMessage()
    {
        $this->guestUserHandler();
        $msgId = Yii::$app->request->get('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId);
            $fromUser = User::getById($messages->msgfrom);
            return $this->renderWithData('viewMessage', ['messages' => $messages, 'fromUser' => $fromUser]);
        }
    }

    public function actionMarkAsDeleteAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsg'];
            foreach ($msgIds as $msgId) {
                Message::deleteFromReceivedMsg($msgId);
            }
            return json_encode(array('status' => 0));

        }
    }
    public function actionMarkSentRemoveAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post())
        {
            $params = $this->getBodyParams();
            $msgIdss = $params['checkedMsgs'];
            foreach ($msgIdss as $msgId)
            {
                Message::deleteFromSentMsg($msgId);
            }
            return json_encode(array('status' => 0));

        }
    }
    public function actionReplyMessage()
    {
        $this->guestUserHandler();
        $msgId = Yii::$app->request->get('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId);
            $fromUser = User::getById($messages->msgfrom);
            $this->includeJS(["../js/editor/tiny_mce.js"]);
            return $this->renderWithData('replyMessage', ['messages' => $messages, 'fromUser' => $fromUser]);
        }
    }

    public function actionGetSentCourseAjax()
    {
        $this->guestUserHandler();

        $user = $this->getAuthenticatedUser();
       $params = Yii::$app->request->getBodyParams();
        $cid = $params['cid'];

         $userId = Yii::$app->user->identity->getId();

        $query = Yii::$app->db->createCommand("SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgfrom='$userId'")->queryAll();
        return json_encode(array('status' => 0, 'courseData' => $query));
    }

    public function actionGetSentUserAjax()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $params = Yii::$app->request->getBodyParams();
        $cid = $params['cid'];
        $userId = Yii::$app->user->identity->getId();
        $query = Yii::$app->db->createCommand("SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users JOIN imas_msgs ON imas_msgs.msgto=imas_users.id WHERE imas_msgs.msgfrom='$userId'")->queryAll();
                return json_encode(array('status' => 0, 'userData' => $query));

    }
}