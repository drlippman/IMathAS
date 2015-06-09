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

    /**
     *
     * @return string
     */
    public function actionIndex()
    {
        $this->guestUserHandler();
        $cid = $this->getParam('cid');
        if ($this->getAuthenticatedUser()) {
            $model = new MessageForm();
            $course = Course::getById($cid);
            $sortBy = 'FirstName';
            $order = AppConstant::ASCENDING;
            $rights = $this->getAuthenticatedUser();
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($cid);
            $this->includeJS(["message/message.js"]);
            $responseData = array('model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher, 'userRights' => $rights);
            return $this->renderWithData('messages', $responseData);
        }
    }

    public function actionSendMessage()
    {
        $this->guestUserHandler();
        $cid = $this->getParam('cid');
        if ($this->getAuthenticatedUser()) {
            $course = Course::getById($cid);
            $teacher = Teacher::getTeachersById($cid);
            $sortBy = 'FirstName';
            $order = AppConstant::ASCENDING;
            $users = User::findAllUsers($sortBy, $order);
            $uid = $this->getUserId();
            $this->includeCSS(["message.css"]);
            $this->includeJS(['message/sendMessage.js',"editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js']);
            $responseData = array('course' => $course, 'teachers' => $teacher, 'users' => $users, 'loginid' => $uid);
            return $this->renderWithData('sendMessage', $responseData);
        }
    }

    public function actionConfirmMessage()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $uid = $this->getUserId();
            if ($params['receiver'] != 0 && $params['cid'] != null) {
                $message = new Message();
                $message->create($params, $uid);
            }
            return $this->successResponse();
        }
    }

    public function actionDisplayMessageAjax()
    {
        if (!$this->isGuestUser()) {
            $uid = $this->getUserId();
            $params = $this->getBodyParams();
            $ShowRedFlagRow = $params['ShowRedFlagRow'];
            $messages = Message::getUsersToDisplay($uid);
            if($messages)
            {
                $dateArray = array();
                if ($ShowRedFlagRow == 1) {
                    foreach ($messages as $message) {
                        if ($message['isread'] == 8 || $message['isread'] == 9 || $message['isread'] == 12 || $message['isread'] == 13) {
                            $dateArray[] = $message;
                        }
                    }
                } else {
                    foreach ($messages as $message) {
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
                return $this->successResponse($newArray);
            }
            else{
                return $this->terminateResponse('No message found.');
            }
        }
    }

    public function actionSentMessage()
    {
        $this->guestUserHandler();
        $cid = $this->getParam('cid');

        if ($this->getAuthenticatedUser()) {
            $model = new MessageForm();
            $course = Course::getById($cid);
            $sortBy = 'FirstName';
            $order = AppConstant::ASCENDING;
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($cid);
            $responseData = array('model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher);
            $this->includeJS(['message/sentMessage.js']);
            return $this->renderWithData('sentMessage',$responseData );
        }
    }

    public function actionDisplaySentMessageAjax()
    {
        if (!$this->isGuestUser()) {
            $userId = $this->getUserId();
            $messages = Message::getUsersToDisplayMessage($userId);
            $dateArray = array();
            if ($messages) {
                foreach ($messages as $message) {
                    $dateArray[] = $message;
                }
                $newArray = array();
                foreach ($dateArray as $singleDate) {
                    $singleDate['senddate'] = date('F d, o g:i a', $singleDate['senddate']);
                    $titleLevel = AppUtility::calculateLevel($singleDate['title']);
                    $singleDate['title'] = $titleLevel['title'];
                    $newArray[] = $singleDate;
                }
                return $this->successResponse($newArray);
            } else {
                return $this->terminateResponse('No message found.');
            }
        }
    }

    public function actionGetCourseAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $userId = $params['userId'];
        $teachers = Teacher::getTeacherByUserId($userId);
        $teacherArray = array();
        if($teachers)
        {
            foreach ($teachers as $teacher) {
                $tempArray = array('courseId' => $teacher->course->id,
                    'courseName' => $teacher->course->name);
                array_push($teacherArray, $tempArray);
            }
            return $this->successResponse($teacherArray);
        } else {
            return $this->terminateResponse('No Course found.');
        }
    }

    public function actionMarkAsUnreadAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsg'];
            if($msgIds){
                foreach ($msgIds as $msgId) {
                    Message::updateUnread($msgId);
                }
                return $this->successResponse();
            }else
            {
                return $this->terminateResponse('No Course found.');
            }
        }
    }

    public function actionMarkAsReadAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsg'];
            if($msgIds){
                foreach ($msgIds as $messageId) {
                    Message::updateRead($messageId);
                }
                return $this->successResponse();
            }else
            {
                return $this->terminateResponse('No Course found.');
            }
        }
    }

    public function actionGetUserAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getUserId();
        $userData = Message::getUsersToUserMessage($userId);
        if($userData)
        {
            return $this->successResponse($userData);
        } else {
            return $this->terminateResponse('No User found.');
        }
    }

    public function actionViewMessage()
    {
        $this->guestUserHandler();
        $msgId = $this->getParam('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId);
            Message::updateRead($msgId);
            $fromUser = User::getById($messages->msgfrom);
            $this->includeCSS(['jquery-ui.css']);
            $this->includeJS(['message/viewmessage.js']);
            $responseData = array('messages' => $messages, 'fromUser' => $fromUser);
            return $this->renderWithData('viewMessage', $responseData);
        }
    }

    public function actionMarkAsDeleteAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsg'];
            foreach ($msgIds as $msgId) {
                Message::deleteFromReceivedMsg($msgId);
            }
            return $this->successResponse();
        }
    }

    public function actionMarkSentRemoveAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsgs'];
            if($msgIds){
                foreach ($msgIds as $msgId) {
                    Message::deleteFromSentMsg($msgId);
                }
                return $this->successResponse();
            }else
            {
                return $this->terminateResponse('No Course found.');
            }
        }
    }

    public function actionReplyMessage()
    {
        $this->guestUserHandler();
        $baseId = $this->getParam('baseid');
        $msgId = $this->getParam('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId, $baseId);
            $fromUser = User::getById($messages->msgfrom);
            $responseData = array('messages' => $messages, 'fromUser' => $fromUser);
            $this->includeJS(["editor/tiny_mce.js","message/replyMessage.js"]);
            return $this->renderWithData('replyMessage', $responseData);
        }
    }

    public function actionGetSentCourseAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getUserId();
        $message = Message::getUsersToCourseMessage($userId);
        if($message){
            return $this->successResponse($message);
        }
        else
        {
            return $this->terminateResponse('No Course found.');
        }
    }

    public function actionGetSentUserAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getUserId();
        $message = Message::getSentUsersMessage($userId);
        if($message){
            return $this->successResponse($message);
        }
        else
        {
            return $this->terminateResponse('No User found.');
        }
    }

    public function actionReplyMessageAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            if ($this->getAuthenticatedUser()) {
                if ($params['receiver'] != 0 && $params['cid'] != null) {
                    $message = new Message();
                    $message->createReply($params);
                }
                return $this->successResponse();
            }
        }
    }

    public function actionViewConversation()
    {
        $this->guestUserHandler();
        $baseId = $this->getParam('baseid');
        $msgId = $this->getParam('id');
        $user = $this->getAuthenticatedUser();
        if($baseId == 0)
        {
            $baseId = $msgId;
        }
        $messages = Message::getByBaseId($msgId, $baseId);
        if($messages){
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
            $this->includeJS(["message/viewConversation.js"]);
            $this->createChild($this->children[key($this->children)]);
            $responseData = array('messages' => $this->totalMessages,'user' => $user);
            return $this->renderWithData('viewConversation', $responseData);
        }
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
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $msgIds = $params['checkedMsgs'];
            if($msgIds){
                foreach ($msgIds as $msgId) {
                    Message::sentUnsendMsg($msgId);
                }
                return $this->successResponse();
            }else
            {
                return $this->terminateResponse('No message');
            }
        }
    }

    public function actionChangeImageAjax()
    {
        $params = $this->getBodyParams();
        $rowId = $params['rowId'];
        if($rowId){
            Message::updateFlagValue($rowId);
            return $this->successResponse();
        }else{
            return $this->terminateResponse('No message');
        }
    }
}