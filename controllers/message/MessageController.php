<?php

namespace app\controllers\message;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
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
    public $user = null;

    public function beforeAction($action)
    {
        $actionPath = Yii::$app->controller->action->id;
        $this->user = $this->getAuthenticatedUser();
        $courseId =  ($this->getParamVal('cid') || $this->getParamVal('courseId')) ? ($this->getParamVal('cid')?$this->getParamVal('cid'):$this->getParamVal('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForMessageController($this->user,$courseId,$actionPath);
    }
    /*
     * Initial load of message index page.
     */
    public function actionIndex()
    {
        $this->layout = "master";
        $this->guestUserHandler();

        $user = $this->user;
        $courseId = $this->getParamVal('cid');
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $isNewMessage = $this->getParamVal('newmsg');
        $isImportant = $this->getParamVal('show');
        $rights = $this->user;
        $isTeacher = $this->isTeacher($user['id'],$courseId);
        $course = Course::getById($courseId);
        $cansendmsgs = false;
        if ($rights) {
            $model = new MessageForm();
            if (!isset($_GET['page']) || $_GET['page']=='') {
                $page = 1;
            } else {
                $page = $_GET['page'];
            }
            if ($page==-2) {
                $limittotagged = 1;
            } else {
                $limittotagged = 0;
            }
            if (isset($_GET['filtercid'])) {
                $filtercid = $_GET['filtercid'];
            } else if ($courseId!='admin' && $courseId>0) {
                $filtercid = $courseId;
            } else {
                $filtercid = 0;
            }
            if (isset($_GET['filteruid'])) {
                $filteruid = intval($_GET['filteruid']);
            } else {
                $filteruid = 0;
            }
            if ($user['rights'] > 5 && $courseId>0) {
                $result = Course::getMsgSet($courseId);
                $msgset = $result['msgset'];
                $msgmonitor = (floor($msgset/5)&1);
                $msgset = $msgset%5;
                if ($msgset<3 || $isTeacher) {
                    $cansendmsgs = true;
                }
            }
            if($cansendmsgs == false){
                $this->setErrorFlash('Message System is "OFF" for ' .$course['name']. ' course');
                return $this->goHome();
            }
            $userData = User::getById($user['id']);
            $threadsperpage = $userData['listperpage'];
            $filterByCourse = Message::getCoursesForMessage($user['id']);
            $filterByUserName = Message::getMessagesByUserName($user['id'], $filtercid);
            $messageDisplay = Message::displayMessageById($user['id'], $filteruid, $filtercid, $limittotagged,$page, $threadsperpage);
            $sortBy = AppConstant::FIRST_NAME;
            $order = AppConstant::ASCENDING;
            $limitToTaggedMsg = new Message;
//            $limitToTagged = $limitToTaggedMsg->getCountOfIdByIsRead($user['id'], $filtercid, $limittotagged);
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($courseId);
            $this->includeCSS(['dataTables.bootstrap.css', 'message.css', 'forums.css']);
            $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js','message/msg.js','message/message.js']);
            $responseData = array('model' => $model, 'course' => $course, 'users' => $users, 'teachers' => $teacher, 'userRights' => $rights, 'isNewMessage' => $isNewMessage, 'isImportant' => $isImportant, 'userId' => $user->id, 'filtercid' => $filtercid, 'filterByCourse' => $filterByCourse, 'filteruid' => $filteruid, 'filterByUserName' => $filterByUserName, 'messageDisplay' => $messageDisplay, 'page' => $page, 'cansendmsgs' => $cansendmsgs, 'msgmonitor' => $msgmonitor,
            'isTeacher' => $isTeacher);
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
        $userRights = $this->user;
        $newTo = $this->getParamVal('new');
        $courseId = $this->getParamVal('cid');
        $userId = $this->getParamVal('userid');
        $userName = User::getById($userId );
        $isTeacher=AppController::isTeacher($userRights['id'],$courseId);
        if ($this->getAuthenticatedUser())
        {
            $course = Course::getById($courseId);
            $teacher = Teacher::getTeachersById($courseId);
            $users = User::findTeachersToList($courseId);
            $tutors = Tutor::findTutorsToList($courseId);
            $students = Student::findStudentsToList($courseId);

            if($course['msgset']==0 || $isTeacher){

                foreach($tutors as $tutor){
                array_push($users,$tutor);
                }

                foreach($students as $student){
                    array_push($users,$student);
                }
            }
            if($course['msgset']==2 ||$isTeacher){
                if(!$isTeacher)
                $users=$students;
             }
//            $uId = $this->getUserId();
            $this->includeCSS(["message.css"]);
            $this->includeJS(['message/sendMessage.js', "editor/tiny_mce.js", 'editor/tiny_mce_src.js', 'general.js']);
            $responseData = array('course' => $course, 'teachers' => $teacher, 'users' => $users, 'loginid' => $userId , 'userRights' => $userRights,'newTo' =>  $newTo,'username' => $userName);
            return $this->renderWithData('sendMessage', $responseData);
        }
    }
    /*
     * Ajax call method for sending the message.
     */
    public function actionSendMessageAjax()
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
                    $singleDate['senddate'] = AppUtility::tzdate(AppConstant::CUSTOMIZE_DATE,$singleDate['senddate']);
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
        $userRights = $this->user;
        $threadsperpage = $userRights['listperpage'];
        $isTeacher = $this->isTeacher($userRights['id'], $courseId);
        $isStudent = $this->isStudent($userRights['id'], $courseId);
        $isTutor = $this->isTutor($userRights['id'], $courseId);
        $getPage = $this->getParamVal('page');
        $filterCid = $this->getParamVal('filtercid');
        $filterUid = $this->getParamVal('filteruid');
            if ($courseId != 0 && !$isTeacher && !$isTutor && !$isStudent) {
                $this->setErrorFlash("You are not enrolled in this course.");
                return $this->redirect('sent-message');
            }
            if (isset($isTeacher)) {
                $teacherId = true;
            } else {
                $teacherId = false;
            }

            if (!isset($getPage) || $getPage=='') {
                $page = 1;
            } else {
                $page = $getPage;
            }
            if (isset($filterCid)) {
                $filtercid = $filterCid;
            } else if ($courseId != 'admin' && $courseId > 0) {
                $filtercid = $courseId;
            } else {
                $filtercid = 0;
            }
            if (isset($filterUid)) {
                $filteruid = intval($filterUid);
            } else {
                $filteruid = 0;
            }

            $byRecipient = User::getByRecipient($userRights['id'],$filtercid);

            $byCourse = Message::getByCourse($userRights['id']);
            $displayMessage = Message::getCourseFilter($userRights['id'], $filteruid, $filtercid);
            $model = new MessageForm();
            $course = Course::getById($courseId);
            $sortBy = AppConstant::FIRST_NAME;
            $order = AppConstant::ASCENDING;
            $users = User::findAllUser($sortBy, $order);
            $teacher = Teacher::getTeachersById($courseId);
            $responseData = array('model' => $model, 'course' => $course, 'users' => $users,
                'teachers' => $teacher, 'userRights' => $userRights, 'isTeacher' => $isTeacher, 'byRecipient' => $byRecipient,
                'filteruid' => $filteruid, 'filtercid' => $filtercid, 'byCourse' => $byCourse, 'displayMessage' => $displayMessage, 'page' => $page);
            $this->includeCSS(['dataTables.bootstrap.css',"message.css"]);
            $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js', 'message/sentMessage.js']);
            return $this->renderWithData('sentMessage', $responseData);

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
                    $singleDate['senddate'] = AppUtility::tzdate(AppConstant::CUSTOMIZE_DATE,$singleDate['senddate']);
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
        $userRights = $this->user;
        $messageId = $this->getParamVal('message');
        $courseId = $this->getParamVal('cid');
        $countPost = $this->getNotificationDataForum($courseId,$userRights);
        $msgList = $this->getNotificationDataMessage($courseId,$userRights);
        $isTeacher = $this->isTeacher($userRights['id'],$courseId);
        $isTutor = $this->isTutor($userRights['id'],$courseId);
        $isStudent = $this->isStudent($userRights['id'],$courseId);
        $params = $this->getRequestParams();
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $checked = $this->getParamVal('msgid');

        if ($courseId != 0 && !($isTeacher) && !($isTutor) && !($isStudent)) {
            $this->setErrorFlash('You are not enrolled in this course.');
            return $this->goHome();
        }
        if (isset($isTeacher)) {
            $isTeacher = true;
        } else {
            $isTeacher = false;
        }
        if (isset($params['filtercid'])) {
            $filtercid = $params['filtercid'];
        } else {
            $filtercid = AppConstant::NUMERIC_ZERO;
        }
        if (isset($params['filterstu'])) {
            $filterstu = $params['filterstu'];
        } else {
            $filterstu = AppConstant::NUMERIC_ZERO;
        }

        $cid = $params['cid'];
        $page = $params['page'];
        $type = $params['type'];

        $teacherof = array();
        $teacher = new Teacher();
        $result = $teacher->selectByCourseId($userRights['id']);
        foreach($result as $row) {
            $teacherof[$row['courseid']] = true;
        }

        $course = Course::getById($courseId);
        $msgId = $this->getParamVal('msgid');
        $messageData = Message::getMessageData($courseId,$msgId, $type, $isTeacher, $userRights['id']);
        if(count($messageData) == AppConstant::NUMERIC_ZERO){
            $this->setErrorFlash('Message not found');
            return $this->redirect('view-message');
        }
        if($msgId != $messageData['id']){
           $this->setErrorFlash('This message is already deleted.');
            return $this->redirect('index?cid='.$courseId);
        }
        $isTeacher = isset($teacherof[$messageData['courseid']]);
        $isTeacherChecked = isset($teacherof[$messageData['courseid']]);
        $senddate = AppUtility::tzdate("F j, Y, g:i a",$messageData['senddate']);
        if (isset($teacherof[$messageData['courseid']])) {
            if (preg_match('/Question\s+about\s+#(\d+)\s+in\s+(.*)\s*$/',$messageData['title'],$matches)) {
                $aname = addslashes($matches[2]);
                $assessmentData = Assessments::getByNameAndCourseId($aname, $courseId);
                if(count($assessmentData) > AppConstant::NUMERIC_ZERO){
                    $assessmentId = $assessmentData['id'];
                    $due = $assessmentData['enddate'];
                    $exceptionData = Exceptions::getEndDateById($messageData['msgfrom'], $assessmentId);
                    if(count($exceptionData) > AppConstant::NUMERIC_ZERO) {
                        $due = $exceptionData['enddate'];
                    }
                    $duedate = AppUtility::tzdate('D m/d/Y g:i a',$due);
                    $assessmentSessionData = AssessmentSession::getByAssessmentIdAndUserId($assessmentId,$userRights['id']);
                    }
                }
            }
        if ($type!='sent' && $type!='allstu') {
            if ($messageData['courseid'] > AppConstant::NUMERIC_ZERO) {
                $result = Course::getMsgSet($messageData['courseid']);
                $msgset = $result['msgset'];
                $msgmonitor = floor($msgset/5);
                $msgset = $msgset%5;
                if ($msgset < AppConstant::NUMERIC_THREE || $isTeacher) {
                    $cansendmsgs = true;
                    if ($msgset== AppConstant::NUMERIC_ONE && !$isTeacher) { //check if sending to teacher
                        $teacher = new Teacher();
                        $result = $teacher->getId($messageData['courseid'],$messageData['msgfrom']);
                        if (count($result) == AppConstant::NUMERIC_ZERO) {
                            $cansendmsgs = false;
                        }
                    } else if ($msgset == AppConstant::NUMERIC_TWO && !$isTeacher) { //check if sending to stu
                        $result = Student::getId($messageData['msgfrom'],$messageData['courseid']);
                        if (count($result) == AppConstant::NUMERIC_ZERO) {
                            $cansendmsgs = false;
                        }
                    }
                } else {
                    $cansendmsgs = false;
                }
            } else {
                $cansendmsgs = true;
            }
        }

        if ($type!='sent' && $type!='allstu' && ($messageData['isread']==0 || $messageData['isread']==4)) {
            Message::updateIsReadView($msgId);
        }
            $messages = Message::getById($msgId);
            $this->includeCSS(['jquery-ui.css', 'message.css', 'forums.css']);
            $this->includeJS(['message/viewmessage.js']);
            $responseData = array('messages' => $messages, 'course' => $course, 'userRights' => $userRights,'messageId' =>$messageId, 'messageData' => $messageData, 'senddate' => $senddate, 'teacherof' => $teacherof,
            'isTeacher' => $isTeacher, 'filtercid' => $filtercid, 'filterstu' => $filterstu, 'cansendmsgs' => $cansendmsgs, 'type' => $type, 'cid' => $cid, 'page' => $page,
            'isTeacherChecked' => $isTeacherChecked, 'assessmentSessionData' => $assessmentSessionData, 'due' => $due, 'duedate' => $duedate);
            return $this->renderWithData('viewMessage', $responseData);

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
            return $this->successResponse($params['checkedMsg']);
        }
    }
    /*
     *  Ajax call method to remove  message form outbox
     */
    public function actionSentMarkAsDeleteAjax()
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
        $userRights = $this->user;
        $baseId = $this->getParamVal('baseid');
        $msgId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        if ($this->getAuthenticatedUser()) {
            $messages = Message::getById($msgId, $baseId);
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
                    if( $params['parentId']>AppConstant::NUMERIC_ZERO)
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
        $userRights = $this->user;
        $course = Course::getById($courseId);
        $messageId = $this->getParamVal('message');
        $baseId = $this->getParamVal('baseid');
        $msgId = $this->getParamVal('id');
        $type = $this->getParamVal('type');
        $user = $this->user;
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
            $responseData = array('messages' => $this->totalMessages, 'user' => $user, 'type' => $type, 'messageId' => $messageId, 'course' => $course, 'userRights' => $userRights);
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
    public function actionToggleTaggedAjax()
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

    public function actionSaveTagged()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $threadid = $this->getParamVal('threadid');

        $user = $this->user;

        if (!isset($threadid)) {
             $this->setErrorFlash('Exit');
            return $this->redirect('index');
        }
        $ischanged = false;

        $saveTagged = Message::saveTagged($user['id'], $threadid);
        if(count($saveTagged) > 0)
        {
            $ischanged = true;
        }
        $responseData = array('ischanged' => $ischanged);
        return $this->renderWithData('saveTagged', $responseData);
    }
}