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

     if (!$this->isGuestUser())
     {
        $user = $this->getAuthenticatedUser();
        $params = Yii::$app->request->getBodyParams();
        $cid = $params['cid'];
        $userId = $params['userId'];
        $messageResponse = array();
        $teachers = Teacher::getTeacherByUserId($userId);
        foreach($teachers as $teacher)
        {
            $messages = Message::getByCourseId($teacher->courseid);
            foreach($messages as $key => $message)
            {
                $fromUser = User::getById($message->msgfrom);
                $toUser = User::getById($message->msgto);
                $tempArray = array('msgId' => $message->title,
                    'title' => $message->title,
                    'replied' => $message->replied,
                    'msgFrom' => isset($fromUser) ? $fromUser->FirstName : ''.''.isset($fromUser) ? $fromUser->LastName : '',
                    'msgFromId' => isset($fromUser) ? $fromUser->id : '',
                    'msgTo' => isset($toUser) ? $toUser->FirstName : ''.''.isset($toUser) ? $toUser->LastName : '',
                    'msgToId' => isset($toUser) ? $toUser->id : '',
                    'courseId' => $message->courseid,
                    'courseName' => $message->course->name,
                    'msgDate' => $message->senddate,
                    'isReade' => $message->isread,
                    'parent' => $message->parent,
                    'baseId' => $message->baseid,
                    'msgBody' => $message->message
                );

                array_push($messageResponse, $tempArray);
            }
            return json_encode(array('status' => 0, 'messageData' => $messageResponse));
        }

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
            $user = $this->getAuthenticatedUser();
            $params = Yii::$app->request->getBodyParams();
            $cid = $params['cid'];
            $userId = $params['userId'];
            $messageResponse = array();
            $teachers = Teacher::getTeacherByUserId($userId);
            foreach ($teachers as $teacher) {
                $messages = Message::getByCourseId($teacher->courseid);
                foreach ($messages as $key => $message) {
                    $fromUser = User::getById($message->msgfrom);
                    $toUser = User::getById($message->msgto);

                    $tempArray = array('msgId' => $message->title,
                        'title' => $message->title,
                        'replied' => $message->replied,
                        'msgFrom' => isset($fromUser) ? $fromUser->FirstName : '' . '' . isset($fromUser) ? $fromUser->LastName : '',
                        'msgFromId' => isset($fromUser) ? $fromUser->id : '',
                        'msgTo' => isset($toUser) ? $toUser->FirstName : '' . '' . isset($toUser) ? $toUser->LastName : '',
                        'msgToId' => isset($toUser) ? $toUser->id : '',
                        'courseId' => $message->courseid,
                        'courseName' => $message->course->name,
                        'msgDate' => $message->senddate,
                        'isRead' => $message->isread,
                        'parent' => $message->parent,
                        'baseId' => $message->baseid,
                        'msgBody' => $message->message
                    );

                    array_push($messageResponse, $tempArray);
                }
            }
            return json_encode(array('status' => 0, 'messageData' => $messageResponse));
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
          foreach ($teachers as $teacher)
          {
              $tempArray = array('courseId' => $teacher->course->id,
              'courseName' => $teacher->course->name);

              array_push($teacherArray, $tempArray);
          }
       return json_encode(array('status' => 0, 'courseData' => $teacherArray));
   }
}