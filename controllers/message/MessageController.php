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

    public $messageData = array();
    public $totalMessages = array();
    public $children = array();

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
            $rights = $this->getAuthenticatedUser();
            $users = User::findAllUser($sortBy, $order);

            $teacher = Teacher::getTeachersById($cid);
            $messages = Message::getByCourseId($cid);
            $senders = Message::getSenders($cid);
            return $this->renderWithData('messages', ['model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher, 'userRights' => $rights]);
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
            $this->includeJS(['../js/message/sendMessage.js',"../js/editor/tiny_mce.js" , '../js/editor/tiny_mce_src.js', '../js/general.js', '../js/editor/plugins/asciimath/editor_plugin.js', '../js/editor/themes/advanced/editor_template.js']);
            return $this->renderWithData('sendMessage', ['course' => $course, 'teachers' => $teacher, 'users' => $users, 'loginid' => $uid]);

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
                $message->create($params, $uid);
            }
            return json_encode(array('status' => 0));
        }
    }

    public function actionDisplayMessageAjax()
    {
        if (!$this->isGuestUser()) {
            $uid = Yii::$app->user->identity->getId();
            $params = $this->getBodyParams();
            $ShowRedFlagRow = $params['ShowRedFlagRow'];
            $mesages = Message::getUsersToDisplay($uid);
            $dateArray = array();
            if ($ShowRedFlagRow == 1) {
                foreach ($mesages as $message) {
                    if ($message['isread'] == 8 || $message['isread'] == 9 || $message['isread'] == 12 || $message['isread'] == 13) {
                        $dateArray[] = $message;
                    }
                }

                $newArray = array();
                foreach ($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);
                    $titleLevel = AppUtility::calculateLevel($singleDate['title']);
                    $singleDate['title'] = $titleLevel['title'];
                    $newArray[] = $singleDate;
                }
            } else {
                foreach ($mesages as $message) {
                    $dateArray[] = $message;
                }
                $newArray = array();
                foreach ($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);
                    $titleLevel = AppUtility::calculateLevel($singleDate['title']);
                    $singleDate['title'] = $titleLevel['title'];
                    $newArray[] = $singleDate;
                }
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
            $mesages = Message::getUsersToDisplayMessage($uid);

            if ($mesages) {

                foreach ($mesages as $message) {
                    $dateArray[] = $message;
                }
                $newArray = array();
                foreach ($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);
                    $titleLevel = AppUtility::calculateLevel($singleDate['title']);
                    $singleDate['title'] = $titleLevel['title'];
                    $newArray[] = $singleDate;
                }
                return json_encode(array('status' => 0, 'messageData' => $newArray));
            } else {
                return json_encode(array('status' => 0, 'messageData' => $mesages));
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
            $cid = Yii::$app->request->get('cid');
            $messageIds = $params['checkedMsg'];
            foreach ($messageIds as $messageId) {
                Message::updateUnread($messageId);

            }
            return json_encode(array('status' => 0));
        }

    }

    public function actionMarkAsReadAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
            $params = $this->getBodyParams();
            $messageIds = $params['checkedMsg'];
            foreach ($messageIds as $messageId) {
                Message::updateRead($messageId);
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
        $mesages = Message::getUsersToUserMessage($userId);

        return json_encode(array('status' => 0, 'userData' => $mesages));
    }

    public function actionViewMessage()
    {
        $this->guestUserHandler();
        $msgId = Yii::$app->request->get('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId);
            Message::updateRead($msgId);
            $fromUser = User::getById($messages->msgfrom);
            $this->includeCSS(['../css/jquery-ui.css']);
            $this->includeJS(['../js/message/viewmessage.js']);
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
        if (Yii::$app->request->post()) {
            $params = $this->getBodyParams();
            $msgIdss = $params['checkedMsgs'];
            foreach ($msgIdss as $msgId) {
                Message::deleteFromSentMsg($msgId);
            }
            return json_encode(array('status' => 0));

        }
    }

    public function actionReplyMessage()
    {
        $this->guestUserHandler();
        $baseId = Yii::$app->request->get('baseid');
        $msgId = Yii::$app->request->get('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId, $baseId);
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
        $mesages = Message::getUsersToCourseMessage($userId);

        return json_encode(array('status' => 0, 'courseData' => $mesages));
    }

    public function actionGetSentUserAjax()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $params = Yii::$app->request->getBodyParams();
        $cid = $params['cid'];
        $userId = Yii::$app->user->identity->getId();
        $mesages = Message::getSentUsersMessage($userId);

        return json_encode(array('status' => 0, 'userData' => $mesages));

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
        $baseId = $this->getParamVal('baseid');
        $msgId = $this->getParamVal('id');
        $user = Yii::$app->user->identity;

        if($baseId == 0)
        {
            $baseId = $msgId;
        }

        $messages = Message::getByBaseId($msgId, $baseId);
        $children = array();

        foreach ($messages as $message) {
            $this->children[$message['parent']][] = $message['id'];
            $tempArray = array();
            $titleLevel = AppUtility::calculateLevel($message['title']);
            $fromUser = User::getById($message['msgfrom']);
            $tempArray['id'] = $message['id'];
            $tempArray['courseId'] = $message['courseid'];
            $tempArray['message'] = $message['message'];
            $tempArray['title'] = $titleLevel['title'];
            $tempArray['level'] = $titleLevel['level'];
            $tempArray['senderId'] = $message['msgfrom'];
            $tempArray['receiveId'] = $message['msgto'];
            $tempArray['senderName'] = $fromUser->FirstName . ' ' . $fromUser->LastName;
            $tempArray['msgDate'] = $message['senddate'];
            $tempArray['isRead'] = $message['isread'];
            $tempArray['replied'] = $message['replied'];
            $tempArray['parent'] = $message['parent'];
            $tempArray['baseId'] = $message['baseid'];
            $this->messageData[$message['id']] = $tempArray;
        }

        $this->createChild($this->children[key($this->children)]);
        return $this->renderWithData('viewConversation', ['messages' => $this->totalMessages,'user' => $user]);
    }
    public function createChild($childArray, $arrayKey = 0)
    {
        $this->children = AppUtility::removeEmptyAttributes($this->children);
        foreach ($childArray as $superKey => $child) {
            array_push($this->totalMessages, $this->messageData[$child]);

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

    public function actionMarkSentUnsendAjax()
    {
        $this->guestUserHandler();
        if (Yii::$app->request->post()) {
            $params = $this->getBodyParams();
            $msgIdss = $params['checkedMsgs'];
            foreach ($msgIdss as $msgId) {
                Message::sentUnsendMsg($msgId);
            }
            return json_encode(array('status' => 0));
        }
    }

    public function actionChangeImageAjax()
    {

        $params = $this->getBodyParams();
        $rowId = $params['rowId'];
        Message::updateFlagValue($rowId);
        return json_encode(['status' => '0']);

    }
}