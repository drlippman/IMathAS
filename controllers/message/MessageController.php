<?php

namespace app\controllers\message;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Course;
use app\models\Message;
use app\models\Student;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use Yii;
use app\controllers\AppController;
use app\models\forms\MessageForm;
use app\components\htmLawed;

class MessageController extends AppController
{
    public $messageData = array();
    public $totalMessages = array();
    public $children = array();
    public $enableCsrfValidation = false;
    /*
     * Initial load of message index page.
     */
    public function actionIndex()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $this->userAuthentication($user, $courseId);
        $isNewMessage = $this->getParamVal('newmsg');
        $isImportant = $this->getParamVal('show');
        if ($this->getAuthenticatedUser()) {
            $model = new MessageForm();
            $course = Course::getById($courseId);
            $sortBy = AppConstant::FIRST_NAME;
            $order = AppConstant::ASCENDING;
            $rights = $this->getAuthenticatedUser();
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($courseId);
            $this->includeCSS(['dataTables.bootstrap.css', 'message.css']);
            $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js' ]);
            $responseData = array('model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher, 'userRights' => $rights, 'isNewMessage' => $isNewMessage, 'isImportant' => $isImportant, 'userId' => $user->id);
            return $this->renderWithData('messages', $responseData);
        }
    }
    /*
     * Send new message initial load
     */
    public function actionSendMessage()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $userRights = $this->getAuthenticatedUser();
        $newTo = $this->getParamVal('new');
        $courseId = $this->getParamVal('cid');
        $userId = $this->getParamVal('userid');
        $userName = User::getById($userId );
        if ($this->getAuthenticatedUser()) {
            $course = Course::getById($courseId);
            $teacher = Teacher::getTeachersById($courseId);
            $users = User::findTeachersToList($courseId);
            $tutors = Tutor::findTutorsToList($courseId);
            foreach($tutors as $tutor){
                array_push($users,$tutor);
            }
                $students = Student::findStudentsToList($courseId);
                foreach($students as $student){
                    array_push($users,$student);
                }
            $userId = $this->getUserId();
            $this->includeCSS(["message.css"]);
            $this->includeJS(['message/sendMessage.js', "editor/tiny_mce.js", 'editor/tiny_mce_src.js', 'general.js']);
            $responseData = array('course' => $course, 'teachers' => $teacher, 'users' => $users, 'loginid' => $userId , 'userRights' => $userRights,'newTo' =>  $newTo,'username' => $userName);
            return $this->renderWithData('sendMessage', $responseData);
        }
    }
    /*
     * Ajax call method for sending the message.
     */
    public function actionConfirmMessage()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $userId = $this->getUserId();
            if ($params['receiver'] != AppConstant::ZERO_VALUE && $params['cid'] != null) {
                $message = new Message();
                $message->create($params, $userId);
            }
            $this->setSuccessFlash(AppConstant::MESSAGE_SUCCESS);
            return $this->successResponse();
        }
    }
    /*
     * Ajax call method to display received message
     */
    public function actionDisplayMessageAjax()
    {
        if (!$this->isGuestUser()) {
            $userId = $this->getUserId();
            $params = $this->getRequestParams();
            $ShowRedFlagRow = $params['ShowRedFlagRow'];
            $showNewMsg = $params['showNewMsg'];
            $isreadArray = array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR, AppConstant::NUMERIC_EIGHT, AppConstant::NUMERIC_TWELVE);
            $messages = array();
            if($showNewMsg == AppConstant::NUMERIC_ONE){
                $query = Message::getUsersToDisplay($userId);
                if($query){
                    foreach($query as $message){
                         if(in_array($message['isread'], $isreadArray)){
                             array_push($messages, $message);
                         }
                    }
                }
            }else {
                $messages = Message::getUsersToDisplay($userId);
            }
            if ($messages) {
                $dateArray = array();
                if ($ShowRedFlagRow == AppConstant::NUMERIC_ONE) {
                    foreach ($messages as $message) {
                        if ($message['isread'] == AppConstant::NUMERIC_EIGHT || $message['isread'] == AppConstant::NUMERIC_NINE || $message['isread'] == AppConstant::NUMERIC_TWELVE || $message['isread'] == AppConstant::NUMERIC_THIRTEEN) {
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
            } else {
                return $this->terminateResponse(AppConstant::NO_MESSAGE_FOUND);
            }
        }
    }
    /*
     *  Sent message page initial load
     */
    public function actionSentMessage()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $userRights = $this->getAuthenticatedUser();
        if ($this->getAuthenticatedUser()) {
            $model = new MessageForm();
            $course = Course::getById($courseId);
            $sortBy = AppConstant::FIRST_NAME;
            $order = AppConstant::ASCENDING;
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($courseId);
            $responseData = array('model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher, 'userRights' => $userRights);
            $this->includeCSS(['dataTables.bootstrap.css',"message.css"]);
            $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js','general.js' ]);
            return $this->renderWithData('sentMessage', $responseData);
        }
    }
    /*
     * Ajax call method to display sent message
     */
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
                return $this->terminateResponse(AppConstant::NO_MESSAGE_FOUND);
            }
        }
    }
    /*
     * Ajax call method to get course list
     */
    public function actionGetCourseAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $userId = $params['userId'];
        $teachers = Teacher::getTeacherByUserId($userId);
        $students = Student::getByUserId($userId);
        $courseArray = array();
        if ($teachers) {
            foreach ($teachers as $teacher) {
                $tempArray = array(
                    'courseId' => $teacher->course->id,
                    'courseName' => $teacher->course->name
                );
                array_push($courseArray, $tempArray);
            }
            $sort_by = array_column($courseArray, 'courseName');
            array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $courseArray);
            return $this->successResponse($courseArray);
        } elseif ( $students){
            foreach ($students as $student) {
                $tempArray = array(
                    'courseId' => $student->course->id,
                    'courseName' => $student->course->name
                );
                array_push($courseArray, $tempArray);
            }
            $sort_by = array_column($courseArray, 'courseName');
            array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $courseArray);
            return $this->successResponse($courseArray);
        } else {
            return $this->terminateResponse(AppConstant::NO_COURSE_FOUND);
        }
    }
    /*
     * Ajax call method to mark message as unread
     */
    public function actionMarkAsUnreadAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $msgIds = $params['checkedMsg'];
            if ($msgIds) {
                foreach ($msgIds as $msgId) {
                    Message::updateUnread($msgId);
                }
                return $this->successResponse();
            } else {
                return $this->terminateResponse(AppConstant::NO_COURSE_FOUND);
            }
        }
    }
    /*
     * Ajax call method to mark message as read
     */
    public function actionMarkAsReadAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $msgIds = $params['checkedMsg'];
            if ($msgIds) {
                foreach ($msgIds as $messageId) {
                    Message::updateRead($messageId);
                }
                return $this->successResponse();
            } else {
                return $this->terminateResponse(AppConstant::NO_COURSE_FOUND);
            }
        }
    }
    /*
     * Ajax call method to get user list
     */
    public function actionGetUserAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getUserId();
        $userData = Message::getUsersToUserMessage($userId);
        if ($userData) {
            return $this->successResponse($userData);
        } else {
            return $this->terminateResponse(AppConstant::NO_USER_FOUND);
        }
    }
    /*
     * View message page initial load
     */
    public function actionViewMessage()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $userRights = $this->getAuthenticatedUser();
        $messageId = $this->getParamVal('message');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $msgId = $this->getParamVal('id');
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId);
            Message::updateRead($msgId);
            $fromUser = User::getById($messages->msgfrom);
            $this->includeCSS(['jquery-ui.css', 'message.css']);
            $this->includeJS(['message/viewmessage.js']);
            $responseData = array('messages' => $messages, 'fromUser' => $fromUser, 'course' => $course, 'userRights' => $userRights,'messageId' =>$messageId);
            return $this->renderWithData('viewMessage', $responseData);
        }
    }
    /*
     * Ajax call method to delete message from inbox
     */
    public function actionMarkAsDeleteAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $msgIds = $params['checkedMsg'];
            foreach ($msgIds as $msgId) {
                Message::deleteFromReceivedMsg($msgId);
            }
            return $this->successResponse();
        }
    }
    /*
     *  Ajax call method to remove  message form outbox
     */
    public function actionMarkSentRemoveAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $msgIds = $params['checkedMsgs'];
            if ($msgIds) {
                foreach ($msgIds as $msgId) {
                    Message::deleteFromSentMsg($msgId);
                }
                return $this->successResponse();
            } else {
                return $this->terminateResponse(AppConstant::NO_COURSE_FOUND);
            }
        }
    }
    /*
     * Reply message initial load
     */
    public function actionReplyMessage()
    {
        $this->layout = 'master';
        $this->guestUserHandler();
        $userRights = $this->getAuthenticatedUser();
        $baseId = $this->getParamVal('baseid');
        $msgId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getByMsgId($msgId, $baseId);
            $fromUser = User::getById($messages->msgfrom);
            $responseData = array('messages' => $messages, 'fromUser' => $fromUser, 'course' => $course, 'userRights' => $userRights);
            $this->includeCSS(['message.css']);
            $this->includeJS(["editor/tiny_mce.js", "message/replyMessage.js","general.js"]);
            return $this->renderWithData('replyMessage', $responseData);
        }
    }
    /*
     * Ajax call method to get course list on sent message page
     */
    public function actionGetSentCourseAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getUserId();
        $message = Message::getUsersToCourseMessage($userId);
        if ($message) {
            return $this->successResponse($message);
        } else {
            return $this->terminateResponse(AppConstant::NO_COURSE_FOUND);
        }
    }
    /*
     * Ajax call method to get users list on the sent message page
     */
    public function actionGetSentUserAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getUserId();
        $message = Message::getSentUsersMessage($userId);
        if ($message) {
            return $this->successResponse($message);
        } else {
            return $this->terminateResponse(AppConstant::NO_USER_FOUND);
        }
    }
    /*
     * Ajax call method to send reply message
     */
    public function actionReplyMessageAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            if ($this->getAuthenticatedUser()) {
                if ($params['receiver'] != AppConstant::ZERO_VALUE && $params['cid'] != null) {
                    $message = new Message();
                    $message->createReply($params);
                    if( $params['parentId']>0)
                    {
                        if(isset($params['checkedValue']))
                        {
                            $changeIsReadValue = new Message();
                            $changeIsReadValue ->updateIsRead($params);

                        }
                    }
                }
                $this->setSuccessFlash('Message sent successfully.');
                return $this->successResponse();
            }
        }
    }
    /*
     * View conversation page initial load.
     */
    public function actionViewConversation()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $userRights = $this->getAuthenticatedUser();
        $course = Course::getById($courseId);
        $messageId = $this->getParamVal('message');
        $baseId = $this->getParamVal('baseid');
        $msgId = $this->getParamVal('id');
        $user = $this->getAuthenticatedUser();
        if ($baseId == AppConstant::ZERO_VALUE) {
            $baseId = $msgId;
        }
        $messages = Message::getByBaseId($msgId, $baseId);
        if ($messages) {
            foreach ($messages as $message) {
                $this->children[$message['parent']][] = $message['id'];
                $hasChild = Message::isMessageHaveChild($message['id']);
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
                $tempArray['hasChild'] = $hasChild;
                $this->messageData[$message['id']] = $tempArray;
            }
            $this->includeJS(["message/viewConversation.js"]);
            $this->includeCSS(['message.css']);
            $this->createChild($this->children[key($this->children)]);
            $responseData = array('messages' => $this->totalMessages, 'user' => $user, 'messageId' => $messageId, 'course' => $course, 'userRights' => $userRights);
            return $this->renderWithData('viewConversation', $responseData);
        }
    }
    /*
     * Method to check child message and parent message
     */
    public function createChild($childArray, $arrayKey = AppConstant::ZERO_VALUE)
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
    /*
     * Ajax call method to unsent the message
     */
    public function actionMarkSentUnsendAjax()
    {
        $this->guestUserHandler();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $msgIds = $params['checkedMsgs'];
            if ($msgIds) {
                foreach ($msgIds as $msgId) {
                    Message::sentUnsendMsg($msgId);
                }
                return $this->successResponse();
            } else {
                return $this->terminateResponse(AppConstant::NO_MESSAGE_FOUND);
            }
        }
    }
    /*
     * Ajax call method to mark flag as important
     */
    public function actionChangeImageAjax()
    {
        $params = $this->getRequestParams();
        $rowId = $params['rowId'];
        if ($rowId) {
            Message::updateFlagValue($rowId);
            return $this->successResponse();
        } else {
            return $this->terminateResponse(AppConstant::NO_MESSAGE_FOUND);
        }
    }
}