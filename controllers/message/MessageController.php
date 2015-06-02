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
            $query = Message::getUsersToDisplay($uid);
            $dateArray = array();
            if ($ShowRedFlagRow == 1) {
                foreach ($query as $senddate) {
                    if ($senddate['isread'] == 8 || $senddate['isread'] == 9 || $senddate['isread'] == 12 || $senddate['isread'] == 13) {
                        $dateArray[] = $senddate;
                    }
                }

                $newArray = array();
                foreach ($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);

                    $newArray[] = $singleDate;
                }
            } else {
                foreach ($query as $senddate) {
                    $dateArray[] = $senddate;
                }
                $newArray = array();
                foreach ($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);

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
            } else {
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
        $baseId = $this->getParamVal('baseid');
        $msgId = $this->getParamVal('id');
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

        $this->createChild($this->children[0]);
//        $this->includeCSS('css/forums.css');
        return $this->renderWithData('viewConversation', ['messages' => $this->totalMessages]);
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
//        AppUtility::dump($this->totalMessages);
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
        $row = $params['rowId'];
        Message::updateFlagValue($row);
        return json_encode(['status' => '0']);

    }

}