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
            $uid = Yii::$app->user->identity->getId();
            $this->includeCSS(["../css/message.css"]);
            $this->includeJS(["../js/editor/tiny_mce.js"]);
            $this->includeJS(["../js/editor/tiny_mce_src.js"]);
            $this->includeJS(["../js/editor/themes/advanced/editor_template.js"]);
            $this->includeJS(["../js/editor/plugins/asciimath/editor_plugin.js"]);
            $this->includeJS(["../js/general.js"]);
            return $this->renderWithData('sendMessage', ['course' => $course, 'teachers' => $teacher, 'users' => $users ,'loginid'=>$uid]);

        }
    }

    public function actionConfirmMessage()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
            $params = $this->getBodyParams();
            $uid = Yii::$app->user->identity->getId();
            if ($params['receiver'] != 0 && $params['cid'] != null) {
                $message = new Message();
                $message->create($params,$uid);
            }
            return json_encode(array('status' => 0));
        }
    }

    public function actionDisplayMessageAjax()
    {

        if (!$this->isGuestUser()) {
           $uid = Yii::$app->user->identity->getId();
            $query = Message::getUsersToDisplay($uid);
            $dateArray = array();
            foreach ($query as $senddate){
                $dateArray[] = $senddate;
            }
            $newArray = array();
                foreach($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);
                    $newArray[] = $singleDate;
                }
            return json_encode(array('status' => 0, 'messageData' => $newArray));
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
            $query = Message::getUsersToDisplayMessage($uid);

            if ($query) {

                foreach ($query as $senddate) {
                    $dateArray[] = $senddate;
                }
                $newArray = array();
                foreach ($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);
                    $newArray[] = $singleDate;
                }
                return json_encode(array('status' => 0, 'messageData' => $newArray));
            }else {
                return json_encode(array('status' => 0, 'messageData' => $query));
            }
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
        $userId = Yii::$app->user->identity->getId();
        $query = Message::getUsersToUserMessage($userId);

        return json_encode(array('status' => 0, 'userData' => $query));
    }

    public function actionViewMessage()
    {
        $this->guestUserHandler();
        $msgId = Yii::$app->request->get('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId);
            Message::updateRead($msgId);
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
        $query = Message::getUsersToCourseMessage($userId);

        return json_encode(array('status' => 0, 'courseData' => $query));
    }

    public function actionGetSentUserAjax()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $params = Yii::$app->request->getBodyParams();
        $cid = $params['cid'];
        $userId = Yii::$app->user->identity->getId();
        $query = Message::getSentUsersMessage($userId);

        return json_encode(array('status' => 0, 'userData' => $query));

    }

    public function actionReplyMessageAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
        $params = Yii::$app->request->getBodyParams();
        if ($this->getAuthenticatedUser()) {
            if ($params['receiver'] != 0 && $params['cid'] != null) {
                $message = new Message();
                $message->createReply($params);
            }
         return json_encode(array('status' => 0));
         }
        }
    }

    public function actionViewConversation()
    {
        $this->guestUserHandler();
        $baseId = Yii::$app->request->get('baseid');
        $msgId = Yii::$app->request->get('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByBaseId($msgId,$baseId);
            $fromUser = User::getById($messages->msgfrom);
            return $this->renderWithData('viewConversation', ['messages' => $messages, 'fromUser' => $fromUser]);
        }
    }

    public function actionMarkSentUnsendAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
             $params = $this->getBodyParams();
            $msgIdss = $params['checkedMsgs'];
            foreach ($msgIdss as $msgId)
            {
                Message::sentUnsendMsg($msgId);
            }
            return json_encode(array('status' => 0));

        }
    }
    public function actionChangeImageAjax()
    {

        $params = $this->getBodyParams();
        $row = $params['rowId'];

       $query = Yii::$app->db->createCommand("UPDATE imas_msgs SET isread=(isread^8) WHERE id='$row';'")->queryAll();
        return json_encode(['status' => '0']);
       // $query = "UPDATE imas_msgs SET isread=(isread^8) WHERE msgto='$userid' AND id='{$_GET['threadid']}'";

    }

}