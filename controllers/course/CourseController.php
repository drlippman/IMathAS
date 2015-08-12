<?php

namespace app\controllers\course;

use app\components\AppConstant;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\models\AppModel;
use app\models\AssessmentSession;
use app\models\Blocks;
use app\models\CalItem;
use app\models\Course;
use app\models\Assessments;
use app\models\Exceptions;
use app\models\forms\ChangeUserInfoForm;
use app\models\forms\CourseSettingForm;
use app\models\InstrFiles;
use app\models\LinkedText;
use app\models\Links;
use app\models\Forums;
use app\models\GbScheme;
use app\models\Items;
use app\models\Message;
use app\models\Questions;
use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\Teacher;
use app\models\InlineText;
use app\models\Wiki;
use app\models\User;
use app\models\GbCats;
use app\models\StuGroupSet;
use app\models\Rubrics;
use app\models\Outcomes;
use yii\web\UploadedFile;
use app\models\ExternalTools;
use Yii;
use app\controllers\AppController;
use app\models\forms\DeleteCourseForm;
use yii\db\Exception;
use yii\helpers\Html;


class CourseController extends AppController
{
    public $filehandertypecfiles = 'local';
    /**
     * Display all course in item order
     */
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $this->setSessionData('courseId',$courseId);
        $this->checkSession($params);
        $user = $this->getAuthenticatedUser();
        $this->setSessionData('user',$user);
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $this->layout = 'master';
        $this->userAuthentication($user, $courseId);
        $userId = $user->id;
        $id = $this->getParamVal('id');
        $assessmentSession = AssessmentSession::getAssessmentSession($this->getUserId(), $id);
        $exception = Exceptions::getTotalData($userId);
        $responseData = array();
        $calendarCount = array();
        $course = Course::getById($courseId);
        if ($course && ($itemOrders = unserialize($course->itemorder))) {
            foreach ($itemOrders as $key => $itemOrder) {
                $tempAray = array();
                if (is_array($itemOrder) || count($blockItems = $itemOrder['items'])) {
                    $tempAray['Block'] = $itemOrder;
                    $blockItems = $itemOrder['items'];
                    $tempItemList = array();
                    $blockItems = $itemOrder['items'];
                    foreach ($blockItems as $blockKey => $blockItem) {
                        $tempItem = array();
                        $item = Items::getById($blockItem);
                        switch ($item->itemtype) {
                            case 'Assessment':
                                $assessment = Assessments::getByAssessmentId($item->typeid);
                                $tempItem[$item->itemtype] = $assessment;
                                array_push($calendarCount, $assessment);
                                break;
                            case 'Calendar':
                                $tempItem[$item->itemtype] = $itemOrder;
                                break;
                            case 'Forum':
                                $form = Forums::getById($item->typeid);
                                $tempItem[$item->itemtype] = $form;
                                break;
                            case 'Wiki':
                                $wiki = Wiki::getById($item->typeid);
                                $tempItem[$item->itemtype] = $wiki;
                                break;
                            case 'LinkedText':
                                $linkedText = Links::getById($item->typeid);
                                $tempItem[$item->itemtype] = $linkedText;
                                break;
                            case 'InlineText':
                                $inlineText = InlineText::getById($item->typeid);
                                $tempItem[$item->itemtype] = $inlineText;
                                break;
                        }
                        array_push($tempItemList, $tempItem);
                    }
                    $tempAray['itemList'] = $tempItemList;
                    array_push($responseData, $tempAray);
                } else {
                    $item = Items::getById($itemOrder);
                    switch ($item->itemtype) {
                        case 'Assessment':
                            $assessment = Assessments::getByAssessmentId($item->typeid);
                            $exception = Exceptions::getByAssessmentIdAndUserId($user->id, $assessment->id);
                            if($exception){
                                $assessment->startdate = $exception->startdate;
                                $assessment->enddate = $exception->enddate;
                            }
                            $tempAray[$item->itemtype] = $assessment;
                            array_push($responseData, $tempAray);
                            array_push($calendarCount, $assessment);
                            break;
                        case 'Calendar':
                            $tempAray[$item->itemtype] = $itemOrder;
                            array_push($responseData, $tempAray);
                            break;
                        case 'Forum':
                            $form = Forums::getById($item->typeid);
                            $tempAray[$item->itemtype] = $form;
                            array_push($responseData, $tempAray);

                            break;
                        case 'Wiki':
                            $wiki = Wiki::getById($item->typeid);
                            $tempAray[$item->itemtype] = $wiki;
                            array_push($responseData, $tempAray);
                            break;
                        case 'InlineText':
                            $inlineText = InlineText::getById($item->typeid);
                            $tempAray[$item->itemtype] = $inlineText;
                            array_push($responseData, $tempAray);
                            break;
                        case 'LinkedText':
                            $linkedText = Links::getById($item->typeid);
                            $tempAray[$item->itemtype] = $linkedText;
                            array_push($responseData, $tempAray);
                            break;
                    }
                }
            }
        }
        $course = Course::getById($courseId);
        $student = Student::getByCId($courseId);
        $user = $this->getAuthenticatedUser();
        $message = Message::getByCourseIdAndUserId($courseId, $user->id);
        $isReadArray = array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR, AppConstant::NUMERIC_EIGHT, AppConstant::NUMERIC_TWELVE);
        $msgList = array();
        if ($message) {
            foreach ($message as $singleMessage) {
                if (in_array($singleMessage->isread, $isReadArray))
                    array_push($msgList, $singleMessage);
            }
        }
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css', 'course/course.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js', 'latePass.js']);
        $returnData = array('calendarData' => $calendarCount, 'courseDetail' => $responseData, 'course' => $course, 'students' => $student, 'assessmentSession' => $assessmentSession, 'messageList' => $msgList, 'exception' => $exception);
        return $this->render('index', $returnData);
    }

    /**
     * Display assessment details
     */
    public function actionShowAssessment()
    {
        $this->guestUserHandler();
        $id = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $assessment = Assessments::getByAssessmentId($id);
        $assessmentSession = AssessmentSession::getAssessmentSession($this->getUserId(), $id);
        $questionRecords = Questions::getByAssessmentId($id);
        $questionSet = QuestionSet::getByQuesSetId($id);
        $course = Course::getById($courseId);
        $this->saveAssessmentSession($assessment, $id);
        $this->includeCSS(['mathtest.css', 'default.css', 'showAssessment.css']);
        $this->includeJS(['timer.js']);
        $returnData = array('cid' => $course, 'assessments' => $assessment, 'questions' => $questionRecords, 'questionSets' => $questionSet, 'assessmentSession' => $assessmentSession, 'now' => time());
        return $this->render('ShowAssessment', $returnData);
    }

    /**
     * Show late passes of assessment.
     */
    public function actionLatePass()
    {
        $this->guestUserHandler();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $studentId = $this->getUserId();
        $exception = Exceptions::getByAssessmentId($assessmentId);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $student = Student::getByCourseId($courseId, $studentId);
        $course = Course::getById($courseId);
        $addTime = $course->latepasshrs * AppConstant::SECONDS * AppConstant::SECONDS;
        $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
        $usedLatepasses = round(($assessment->allowlate - $assessment->enddate) / ($course->latepasshrs * AppConstant::MINUTES));
        $startDate = $assessment->startdate;
        $endDate = $assessment->enddate + $addTime;
        $wave = AppConstant::NUMERIC_ZERO;
        $param['assessmentid'] = $assessmentId;
        $param['userid'] = $studentId;
        $param['startdate'] = $startDate;
        $param['enddate'] = $endDate;
        $param['waivereqscore'] = $wave;
        $param['islatepass'] = AppConstant::NUMERIC_ONE;
        if (count($exception)) {
            if ((($assessment->allowlate % AppConstant::NUMERIC_TEN) == AppConstant::NUMERIC_ONE || ($assessment->allowlate % AppConstant::NUMERIC_TEN) - AppConstant::NUMERIC_ONE > $usedLatepasses) && ($currentTime < $exception->enddate || ($assessment->allowlate > AppConstant::NUMERIC_TEN && ($currentTime - $exception->enddate) < $course->latepasshrs * AppConstant::MINUTES))) {
                $latepass = $student->latepass;
                $student->latepass = $latepass - AppConstant::NUMERIC_ONE;
                $exception->enddate = $exception->enddate + $addTime;
                $exception->islatepass = $exception->islatepass + AppConstant::NUMERIC_ONE;
            }
            $exception->attributes = $param;
            $exception->save();
            $student->save();
        }
        $this->redirect(AppUtility::getURLFromHome('course', 'course/index?id=' . $assessmentId . '&cid=' . $courseId));
    }
    /**
     * Create new course at admin side
     */
    public function actionAddNewCourse()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $model = new CourseSettingForm();
        if ($model->load($this->getPostData())) {
            $isSuccess = false;
            $courseData = $this->getRequestParams();
            $user = $this->getAuthenticatedUser();
            $course = new Course();
            $courseId = $course->create($user, $courseData);
            if ($courseId) {
                $teacher = new Teacher();
                $teacherId = $teacher->create($user->id, $courseId);
                $gbScheme = new GbScheme();
                $gbSchemeId = $gbScheme->create($courseId);
                if ($teacherId && $gbSchemeId) {
                    $this->setSuccessFlash('Course added successfully. Course id: ' . $courseId . ' and Enrollment key: ' . $courseData['CourseSettingForm']['enrollmentKey']);
                    $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
                    $model = new CourseSettingForm();
                    $isSuccess = true;
                }
            }
            if (!$isSuccess) {
                $this->setErrorFlash(AppConstant::SOMETHING_WENT_WRONG);
            }
        }
        $this->includeCSS(["courseSetting.css"]);
        $this->includeJS(["courseSetting.js"]);
        $returnData = array('model' => $model);
        return $this->renderWithData('addNewCourse', $returnData);
    }

    /**
     * Setting in created course.
     */
    public function actionCourseSetting()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        if ($course) {
            $model = new CourseSettingForm();
            if ($model->load($this->getPostData())) {
                $courseData = $this->getRequestParams();
                $params = $courseData['CourseSettingForm'];
                $courseSetting['name'] = $params['courseName'];
                $courseSetting['enrollkey'] = $params['enrollmentKey'];
                $available = $this->getSanitizedValue($params['available'], AppConstant::AVAILABLE_NOT_CHECKED_VALUE);
                $courseSetting['available'] = AppUtility::makeAvailable($available);
                $courseSetting['copyrights'] = $params['copyCourse'];
                $courseSetting['msgset'] = $params['messageSystem'];
                $toolSet = $this->getSanitizedValue($params['navigationLink'], AppConstant::NAVIGATION_NOT_CHECKED_VALUE);
                $courseSetting['toolset'] = AppUtility::makeToolset($toolSet);
                $courseSetting['deflatepass'] = $params['latePasses'];
                $courseSetting['theme'] = $params['theme'];
                $courseSetting['deftime'] = AppUtility::calculateTimeDefference($courseData['start_time'], $courseData['end_time']);
                $courseSetting['end_time'] = $courseData['end_time'];
                $courseSetting = AppUtility::removeEmptyAttributes($courseSetting);
                $course->attributes = $courseSetting;
                $course->save();
            } else {
                $selectionList = AppUtility::prepareSelectedItemOfCourseSetting($course);
                $this->includeCSS(["courseSetting.css"]);
                $returnData = array('model' => $model, 'course' => $course, 'selectionList' => $selectionList);
                return $this->renderWithData('courseSetting', $returnData);
            }
        }
        return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
    }

    /**
     * To delete existing course.
     */
    public function actionDeleteCourse()
    {
        $model = new DeleteCourseForm();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        if ($course) {
            $status = Course::deleteCourse($course->id);
            if ($status) {
                $this->setSuccessFlash(AppConstant::DELETED_SUCCESSFULLY);
            } else {
                $this->setErrorFlash(AppConstant::SOMETHING_WENT_WRONG);
            }
        } else {
            $this->setErrorFlash(AppConstant::SOMETHING_WENT_WRONG);
        }
        $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
    }
    /**
     * @return string
     */
    public function actionTransferCourse()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $sortBy = AppConstant::FIRST_NAME;
        $order = AppConstant::ASCENDING;
        $users = User::findAllUsers($sortBy, $order);
        $course = Course::getById($courseId);
        $this->includeCSS(['dashboard.css']);
        $this->includeJS(['course/transferCourse.js']);
        $returnData = array('users' => $users, 'course' => $course);
        return $this->renderWithData('transferCourse', $returnData);
    }

    public function actionUpdateOwner()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $user = $this->getAuthenticatedUser();
            if ($user->rights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT) {
                $this->setErrorFlash(AppConstant::NO_ACCESS_RIGHTS);
            }
            $exec = false;
            $row = Course::setOwner($params, $user);
            if ($user->rights == AppConstant::GROUP_ADMIN_RIGHT) {
                $courseitem = Course::getByCourseAndGroupId($params, $user);
                if ($courseitem > 0) {
                    $row = Course::setOwner($params, $user);
                    $exec = true;
                }
            } else {
                $exec = true;
            }

            if ($exec && $row > 0) {
                $teacher = Teacher::getByUserId($user->id, $params['cid']);
                if ($teacher == 0) {
                    $newTeacher = new Teacher();
                    $newTeacher->create($params['newOwner'], $params['cid']);
                }
                Teacher::removeTeacher($user->id, $params['cid']);
            }
            return $this->successResponse();
        }
    }

    public function actionAddRemoveCourse()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $this->includeJS(['course/addremovecourse.js']);
        $returnData = array('cid' => $cid);
        return $this->renderWithData('addRemoveCourse', $returnData);
    }

    public function actionGetTeachers()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $sortBy = AppConstant::FIRST_NAME;
        $order = AppConstant::ASCENDING;
        $users = User::findAllTeachers($sortBy, $order);
        $teachers = Teacher::getAllTeachers($courseId);
        $countTeach = count($teachers);
        $nonTeacher = array();
        $teacherIds = array();
        $teacherList = array();
        if ($teachers) {
            foreach ($teachers as $teacher) {
                $teacherIds[$teacher['userid']] = true;
            }
        }
        if ($users) {
            foreach ($users as $user) {
                if (isset($teacherIds[$user['id']])) {
                    array_push($teacherList, $user);
                } else {
                    array_push($nonTeacher, $user);
                }
            }
        }
        return $this->successResponse(array('teachers' => $teacherList, 'nonTeachers' => $nonTeacher,'countTeach' =>$countTeach));
    }

    public function actionAddTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $teacher = new Teacher();
            if ($params['userId'] != null && $params['cid'] != null) {
                $teacher->create($params['userId'], $params['cid']);
            }
            return $this->successResponse();
        }
    }

    public function actionRemoveTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $teacher = new Teacher();
            if ($params['userId'] != null && $params['cid'] != null) {
                $teacher->removeTeacher($params['userId'], $params['cid']);
            }
            return $this->successResponse();
        }
    }

    public function actionAddAllAsTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $courseId = $params['cid'];
            $usersIds = json_decode($params['usersId']);
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($usersIds); $i++) {
                $teacher = new Teacher();
                $teacher->create($usersIds[$i], $courseId);
            }
            return $this->successResponse();
        }
    }

    public function actionRemoveAllAsTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $courseId = $params['cid'];
            $usersIds = json_decode($params['usersId']);
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($usersIds); $i++) {
                $teacher = new Teacher();
                $teacher->removeTeacher($usersIds[$i], $courseId);
            }
            return $this->successResponse();
        }
    }

    /**
     * @param $assessment
     * @param $param
     * @param $id
     */
    public function saveAssessmentSession($assessment, $id)
    {
        list($qList, $seedList, $reviewSeedList, $scoreList, $attemptsList, $laList) = AppUtility::generateAssessmentData($assessment->itemorder, $assessment->shuffle, $assessment->id);
        $bestscorelist = $scoreList . ';' . $scoreList . ';' . $scoreList;
        $scoreList = $scoreList . ';' . $scoreList;
        $bestAttemptsList = $attemptsList;
        $bestSeedsList = $seedList;
        $bestLaList = $laList;
        $startTime = time();
        $defFeedbackText = $assessment->deffeedbacktext;
        $ltiSourcedId = '';
        $param['questions'] = $qList;
        $param['seeds'] = $seedList;
        $param['userid'] = $id;
        $param['assessmentid'] = $id;
        $param['attempts'] = $attemptsList;
        $param['lastanswers'] = $laList;
        $param['reviewscores'] = $scoreList;
        $param['reviewseeds'] = $reviewSeedList;
        $param['bestscores'] = $bestscorelist;
        $param['scores'] = $scoreList;
        $param['bestattempts'] = $bestAttemptsList;
        $param['bestseeds'] = $bestSeedsList;
        $param['bestlastanswers'] = $bestLaList;
        $param['starttime'] = $startTime;
        $param['feedback'] = $defFeedbackText;
        $param['lti_sourcedid'] = $ltiSourcedId;
        $assessmentSession = new AssessmentSession();
        $assessmentSession->attributes = $param;
        $assessmentSession->save();
    }

    /**
     * Display linked text on course page
     */
    public function actionShowLinkedText()
    {
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $id = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $link = Links::getById($id);
        $this->includeCSS(['course/items.css']);
        $returnData = array('course' => $course, 'links' => $link);
        return $this->renderWithData('showLinkedText', $returnData);
    }

    /**
     * To handle event on calendar.
     */
    public function actionGetAssessmentDataAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $cid = $params['cid'];
        $currentDate = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
        $assessments = Assessments::getByCourseId($cid);
        $calendarItems = CalItem::getByCourseId($cid);
        $CalendarLinkItems = Links::getByCourseId($cid);
        $calendarInlineTextItems = InlineText::getByCourseId($cid);
        $assessmentArray = array();
        foreach ($assessments as $assessment) {
            $assessmentArray[] = array(
                'startDate' => AppUtility::getFormattedDate($assessment['startdate']),
                'endDate' => AppUtility::getFormattedDate($assessment['enddate']),
                'dueTime' => AppUtility::getFormattedTime($assessment['enddate']),
                'reviewDate' => AppUtility::getFormattedDate($assessment['reviewdate']),
                'name' => ucfirst($assessment['name']),
                'startDateString' => $assessment['startdate'],
                'endDateString' => $assessment['enddate'],
                'reviewDateString' => $assessment['reviewdate'],
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'assessmentId' => $assessment['id'],
                'courseId' => $assessment['courseid']
            );
        }
        $calendarArray = array();
        foreach ($calendarItems as $calendarItem) {
            $calendarArray[] = array(
                'courseId' => $calendarItem['courseid'],
                'date' => AppUtility::getFormattedDate($calendarItem['date']),
                'dueTime' => AppUtility::getFormattedTime($calendarItem['date']),
                'title' => ucfirst($calendarItem['title']),
                'tag' => ucfirst($calendarItem['tag'])
            );
        }
        $calendarLinkArray = array();
        foreach ($CalendarLinkItems as $CalendarLinkItem) {
            $calendarLinkArray[] = array(
                'courseId' => $CalendarLinkItem['courseid'],
                'title' => ucfirst($CalendarLinkItem['title']),
                'startDate' => AppUtility::getFormattedDate($CalendarLinkItem['startdate']),
                'endDate' => AppUtility::getFormattedDate($CalendarLinkItem['enddate']),
                'dueTime' => AppUtility::getFormattedTime($CalendarLinkItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $CalendarLinkItem['startdate'],
                'endDateString' => $CalendarLinkItem['enddate'],
                'linkedId' => $CalendarLinkItem['id'],
                'calTag' => ucfirst($CalendarLinkItem['caltag'])
            );
        }
        $calendarInlineTextArray = array();
        foreach ($calendarInlineTextItems as $calendarInlineTextItem) {
            $calendarInlineTextArray[] = array(
                'courseId' => $calendarInlineTextItem['courseid'],
                'endDate' => AppUtility::getFormattedDate($calendarInlineTextItem['enddate']),
                'dueTime' => AppUtility::getFormattedTime($calendarInlineTextItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $calendarInlineTextItem['startdate'],
                'endDateString' => $calendarInlineTextItem['enddate'],
                'calTag' => ucfirst($calendarInlineTextItem['caltag'])
            );
        }
        $responseData = array('assessmentArray' => $assessmentArray, 'calendarArray' => $calendarArray, 'calendarLinkArray' => $calendarLinkArray, 'calendarInlineTextArray' => $calendarInlineTextArray, 'currentDate' => $currentDate);
        return $this->successResponse($responseData);
    }

    public function actionBlockIsolate()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $responseData = array();
        $calendarCount = array();
        $course = Course::getById($courseId);
        if ($course) {
            $itemOrders = unserialize($course->itemorder);
            if (count($itemOrders)) {
                foreach ($itemOrders as $key => $itemOrder) {
                    $tempAray = array();
                    if (is_array($itemOrder)) {
                        $tempAray['Block'] = $itemOrder;
                        $blockItems = $itemOrder['items'];
                        $tempItemList = array();
                        if (count($blockItems)) {
                            foreach ($blockItems as $blockKey => $blockItem) {

                                $tempItem = array();
                                $item = Items::getById($blockItem);
                                switch ($item->itemtype) {
                                    case 'Assessment':
                                        $assessment = Assessments::getByAssessmentId($item->typeid);
                                        $tempItem[$item->itemtype] = $assessment;
                                        array_push($calendarCount, $assessment);
                                        break;
                                    case 'Calendar':
                                        $tempItem[$item->itemtype] = AppConstant::NUMERIC_ONE;
                                        break;
                                    case 'Forum':
                                        $form = Forums::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $form;
                                        break;
                                    case 'Wiki':
                                        $wiki = Wiki::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $wiki;
                                        break;
                                    case 'LinkedText':
                                        $linkedText = Links::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $linkedText;
                                        break;
                                    case 'InlineText':
                                        $inlineText = InlineText::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $inlineText;
                                        break;
                                }
                                array_push($tempItemList, $tempItem);
                            }
                        }
                        $tempAray['itemList'] = $tempItemList;
                        array_push($responseData, $tempAray);
                    }
                }
            }
        }
        $message = Message::getByCourseIdAndUserId($courseId, $user->id);
        $isReadArray = array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR, AppConstant::NUMERIC_EIGHT, AppConstant::NUMERIC_TWELVE);
        $msgList = array();
        if ($message) {
            foreach ($message as $singleMessage) {
                if (in_array($singleMessage->isread, $isReadArray))
                    array_push($msgList, $singleMessage);
            }
        }
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css', 'course/course.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js', 'latePass.js', 'course/addItem.js', 'course/instructor.js']);
        $returnData = array('course' => $course, 'messageList' => $msgList, 'courseDetail' => $responseData, 'user' => $user);
        return $this->render('blockIsolate', $returnData);
    }

//    Display calendar on click of menuBars
    public function actionCalendar()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $course = Course::getById($courseId);
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css', 'course/course.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js']);
        $responseData = array('course' => $course, 'user' => $user);
        return $this->render('calendar', $responseData);
    }

    /**
     * Modify inline text: Teacher
     */
    public function actionModifyInlineText()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('courseId');
        $inlineId = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $inlineText = InlineText::getById($inlineId);
        $inlineTextId = $params['id'];
        $saveTitle = '';

        $pageTitle = AppConstant::ADD_INLINE_TEXT;
        $saveTitle = AppConstant::CREATE_ITEM;
        $defaultValue = array(
            'startDate' => time(),
            'sDate' => date("m-d-y"),
            'sTime' => date("g:i A"),
            'eDate' => date("m-d-y"),
            'eTime' => date("g:i A"),
        );
        if ($this->isPost()) {

            $params = $this->getRequestParams();

            if ($inlineTextId) {
                $updateForum = new InlineText();
                $updateForum->updateChanges($params, $inlineTextId);
            } else {
                $endDate = AssessmentUtility::parsedatetime($params['edate'], $params['etime']);
                $startDate = AssessmentUtility::parsedatetime($params['sdate'], $params['stime']);
                $finalArray['title'] = trim($params['title']);
                if (empty($params['text'])) {
                    $params['text'] = ' ';
                }
                $finalArray['text'] = trim($params['text']);
                $finalArray['courseid'] = $params['cid'];

                if ($params['avail'] == AppConstant::NUMERIC_ONE) {
                    if ($params['available-after'] == AppConstant::NUMERIC_ZERO) {

                        $startDate = AppConstant::NUMERIC_ZERO;
                    }
                    if ($params['available-until'] == AppConstant::ALWAYS_TIME) {
                        $endDate = AppConstant::ALWAYS_TIME;
                    }
                    $finalArray['startdate'] = $startDate;
                    $finalArray['enddate'] = $endDate;
                } else {

                    $finalArray['startdate'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['enddate'] = AppConstant::ALWAYS_TIME;
                }
                $finalArray['avail'] = $params['avail'];
                $finalArray['courseid'] = $courseId;
                $finalArray['oncal'] = $params['place-on-calendar'];
                $finalArray['isplaylist'] = $params['isplaylist'];
                $finalArray['caltag'] = '!';
                $newInline = new InlineText();
                $inlineId = $newInline->saveChanges($finalArray);
                $itemType = 'InlineText';
                $itemId = new Items();
                $lastItemId = $itemId->saveItems($courseId, $inlineId, $itemType);
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemOrder = $courseItemOrder->itemorder;
                $items = unserialize($itemOrder);
                $blockTree = array(0);
                $sub =& $items;
                for ($i = AppConstant::NUMERIC_ONE; $i < count($blockTree); $i++) {
                    $sub =& $sub[$blockTree[$i] - AppConstant::NUMERIC_ONE]['items'];
                }
                array_unshift($sub, intval($lastItemId));
                $itemOrder = serialize($items);
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemOrder, $courseId);
            }
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id));
        }
        if (isset($inlineTextId)) {
            $pageTitle = 'Modify Inline Text';
            $saveTitle = AppConstant::SAVE_BUTTON;
            $inlineTextData = InlineText::getById($inlineTextId);
            $startDate = $inlineTextData['startdate'];
            $endDate = $inlineTextData['enddate'];
            $courseDefTime = $course['deftime'] % AppConstant::NUMERIC_THOUSAND;
            $hour = floor($courseDefTime / AppConstant::SECONDS) % AppConstant::NUMERIC_TWELVE;
            $minutes = $courseDefTime % AppConstant::SECONDS;
            $am = ($courseDefTime < AppConstant::NUMERIC_TWELVE * AppConstant::SECONDS) ? AppConstant::AM : AppConstant::PM;
            $defTime = (($hour == AppConstant::NUMERIC_ZERO) ? AppConstant::NUMERIC_TWELVE : $hour) . ':' . (($minutes < AppConstant::NUMERIC_TEN) ? '0' : '') . $minutes . ' ' . $am;
            $hour = floor($courseDefTime / AppConstant::SECONDS) % AppConstant::NUMERIC_TWELVE;
            $minutes = $courseDefTime % AppConstant::SECONDS;
            $am = ($courseDefTime < AppConstant::NUMERIC_TWELVE * AppConstant::SECONDS) ? AppConstant::AM : AppConstant::PM;
            $defStartTime = (($hour == AppConstant::NUMERIC_ZERO) ? AppConstant::NUMERIC_TWELVE : $hour) . ':' . (($minutes < AppConstant::NUMERIC_TEN) ? '0' : '') . $minutes . ' ' . $am;

            if ($startDate != AppConstant::NUMERIC_ZERO) {
                $sDate = AppUtility::tzdate("m/d/Y", $startDate);
                $sTime = AppUtility::tzdate("g:i a", $startDate);
            } else {
                $sDate = AppUtility::tzdate("m/d/Y", time());
                $sTime = $defStartTime;
            }
            if ($endDate != AppConstant::ALWAYS_TIME) {
                $eDate = AppUtility::tzdate("m/d/Y", $endDate);
                $eTime = AppUtility::tzdate("g:i a", $endDate);
            } else {
                $eDate = AppUtility::tzdate("m/d/Y", time() + AppConstant::WEEK_TIME);
                $eTime = $defTime;
            }
            $defaultValue = array(
                'sDate' => $sDate,
                'sTime' => $sTime,
                'eDate' => $eDate,
                'eTime' => $eTime,
                'startDate' => $startDate,
                'endDate' => $endDate,
            );
        }
        $this->includeJS(["course/inlineText.js", "editor/tiny_mce.js", "editor/tiny_mce_src.js", "general.js","editor.js"]);

        $this->includeCSS(['course/items.css']);
        $responseData = array('course' => $course, 'saveTitle' => $saveTitle, 'inlineText' => $inlineText, 'pageTitle' => $pageTitle, 'defaultValue' => $defaultValue);
        return $this->renderWithData('modifyInlineText', $responseData);
    }

    public function actionAddLink()
    {
        $params = $this->getRequestParams();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $modifyLinkId = $params['id'];
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $rubricsData = Rubrics::getByUserId($user['id']);
        $query = Outcomes::getByCourse($courseId);
        $key = AppConstant::NUMERIC_ONE;
        $pageOutcomes = array();
        if ($query) {
            foreach ($query as $singleData) {
                $pageOutcomes[$singleData['id']] = $singleData['name'];
                $key++;
            }
        }
        $pageOutcomesList = array();
        if ($key > AppConstant::NUMERIC_ZERO) {//there were outcomes
            $query = $course['outcomes'];
            $outcomeArray = unserialize($query);
            $result = $this->flatArray($outcomeArray);
            if ($result) {
                foreach ($result as $singlePage) {
                    array_push($pageOutcomesList, $singlePage);
                }
            }
        }
        $key = AppConstant::NUMERIC_ZERO;
        $gbcatsData = GbCats::getByCourseId($courseId);
        foreach ($gbcatsData as $group) {
            $gbcatsId[$key] = $group['id'];
            $gbcatsLabel[$key] = $group['name'];
            $key++;
        }
        $toolsData = ExternalTools::externalToolsData($courseId);
        $toolvals = array();
        $toolvals[0] = AppConstant::NUMERIC_ZERO;
        $key = AppConstant::NUMERIC_ONE;
        foreach ($toolsData as $tool) {
            $toolvals[$key++] = $tool['id'];

        }
        $toollabels[0] = 'Select a tool...';
        $key = AppConstant::NUMERIC_ONE;
        foreach ($toolsData as $tool) {
            $toollabels[$key++] = $tool['name'];

        }
        if ($params['id']) {
            $linkData = LinkedText::getById($params['id']);
            $gbcat = AppConstant::NUMERIC_ZERO;
            $cntingb = AppConstant::NUMERIC_ONE;
            $tutoredit = AppConstant::NUMERIC_ZERO;
            $gradesecret = uniqid();

            if ($linkData['avail'] == AppConstant::NUMERIC_TWO && $linkData['startdate'] > AppConstant::NUMERIC_ZERO) {
                $altoncal = AppConstant::NUMERIC_ONE;
            } else {
                $altoncal = AppConstant::NUMERIC_ONE;
            }
            if (substr($linkData['text'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR) == 'http') {
                $type = 'web';
                $webaddr = $linkData['text'];
                $linkData['text'] = "<p>Enter text here</p>";
            } else if (substr($linkData['text'], 0, 5) == 'file:') {
                $type = 'file';
                $filename = substr($linkData['text'], 5);
                $line['text'] = "<p>Enter text here</p>";
            } else if (substr($linkData['text'], 0, 8) == 'exttool:') {
                $type = 'tool';
                $points = $linkData['points'];
                $toolParts = explode('~~', substr($linkData['text'], AppConstant::NUMERIC_EIGHT));

                $selectedTool = $toolParts[0];
                $toolcustom = $toolParts[1];
                if (isset($toolParts[2])) {
                    $toolcustomurl = $toolParts[2];
                } else {
                    $toolcustomurl = '';
                }
                if (isset($toolParts[3])) {
                    $gbcat = $toolParts[3];
                    $cntingb = $toolParts[4];
                    $tutoredit = $toolParts[5];
                    $gradesecret = $toolParts[6];
                }
                $line['text'] = "<p>Enter text here</p>";
            } else {
                $type = 'text';
            }
            if ($linkData['outcomes'] != '') {
                $gradeoutcomes = explode(',', $linkData['outcomes']);

            } else {
                $gradeoutcomes = array();
            }
            if ($linkData['summary'] == '') {
                $line['summary'] = "<p>Enter summary here (displays on course page)</p>";
            }
            $saveTitle = "Modify Link";
            $saveButtonTitle = "Save Changes";
            $gradesecret = uniqid();
            $gradeoutcomes = array();
            $checkboxesValues = array(
                'title' => $linkData['title'],
                'summary' => $linkData['summary'],
                'text' => $linkData['text'],
                'startdate' => $linkData['startdate'],
                'enddate' => $linkData['enddate'],
                'webaddr' => $webaddr,
                'filename' => $filename,
                'altoncal' => $altoncal,
                'type' => $type,
                'gradeoutcomes' => $gradeoutcomes,
                'toolparts' => $toolParts,
                'cntingb' => $cntingb,
                'gbcat' => $gbcat,
                'tutoredit' => $tutoredit,
                'gradesecret' => $gradesecret,
                'saveButtonTitle' => $saveButtonTitle,
                'saveTitle' => $saveTitle,
                'points' => $points,
                'selectedtool' => $selectedTool,
                'toolcustom' => $toolcustom,
                'toolcustomurl' => $toolcustomurl,
                'gradesecret' => $gradesecret,
            );
        } else {
            $checkboxesValues = array(
                'saveButtonTitle' => "Create Link",
                'saveTitle' => "Create Link",
                'title' => "Enter title here",
                'summary' => "Enter summary here (displays on course page)",
                'text' => "Enter text here",
                'points' => AppConstant::NUMERIC_ZERO,
            );
        }
        if ($this->isPost()) {
            $outcomes = array();
            $modifyLinkId = $params['id'];
            if ($modifyLinkId) {
                $link = new LinkedText();
                $link->updateLinkData($params);
            } else {
                if ($params['outcomes']) {
                    foreach ($params['outcomes'] as $outcome) {
                        if (is_numeric($outcome) && $outcome > AppConstant::NUMERIC_ZERO) {
                            $outcomes[] = intval($outcome);
                        }
                    }
                    $outcomes = implode(',', $outcomes);
                }
                if ($_POST['linktype'] == 'text') {
                    /*
                     * To add htmllawed to link text
                     */
                } else if ($params['linktype'] == 'file') {
                    if ($_FILES['userfile']['name'] != '') {
                        $userfilename = preg_replace('/[^\w\.]/', '', basename($_FILES['userfile']['name']));
                        $filename = $userfilename;
                        $extension = strtolower(strrchr($userfilename, "."));
                        $badextensions = array(".php", ".php3", ".php4", ".php5", ".bat", ".com", ".pl", ".p");
                        $params['text'] = $filename;
                    }
                } else if ($params['linktype'] == 'web') {

                    $params['text'] = trim(strip_tags($params['web']));
                    if (substr($params['text'], 0, 4) != 'http') {
                        $this->setSuccessFlash('Web link should start with http://');
                    }

                } else if ($params['linktype'] == 'tool') {
                    if ($params['tool'] == AppConstant::NUMERIC_ZERO) {
                        $this->setSuccessFlash('Select external Tool');
                    } else {
                        $params['text'] = 'exttool:' . $params['tool'] . '~~' . $params['toolcustom'] . '~~' . $params['toolcustomurl'];
                        if ($params['usegbscore'] == AppConstant::NUMERIC_ZERO || $params['points'] == AppConstant::NUMERIC_ZERO) {
                            $points = AppConstant::NUMERIC_ZERO;
                        } else {
                            $params['text'] .= '~~' . $params['gbcat'] . '~~' . $params['cntingb'] . '~~' . $params['tutoredit'] . '~~' . $params['gradesecret'];
                            $points = intval($params['points']);
                        }

                    }
                }
                if ($params['linktype'] == 'tool') {
                    $externalToolsData = new ExternalTools();
                    $externalToolsData->updateExternalToolsData($params);
                }
                if ($params['id']) {
                    $endDate = $params['enddate'];
                    $startDate = $params['startdate'];
                } else {
                    $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
                    $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
                }
                $finalArray['courseid'] = $params['cid'];
                $finalArray['title'] = $params['name'];
                $finalArray['summary'] = $params['summary'];
                $finalArray['text'] = $params['text'];
                $finalArray['avail'] = $params['avail'];
                $finalArray['oncal'] = $params['place-on-calendar'];
                $finalArray['caltag'] = $params['caltag'];
                $finalArray['target'] = $params['target'];
                $finalArray['points'] = $params['points'];
                $finalArray['target'] = $params['open-page-in'];
                $finalArray['caltag'] = $params['points'];
                $finalArray['outcomes'] = ' ';
                if ($params['outcomes']) {
                    $finalArray['outcomes'] = $outcomes;
                }
                $finalArray['points'] = $params['points'];
                if ($params['avail'] == AppConstant::NUMERIC_ONE) {
                    if ($params['available-after'] == AppConstant::NUMERIC_ZERO) {
                        $startDate = AppConstant::NUMERIC_ZERO;
                    }
                    if ($params['available-until'] == AppConstant::ALWAYS_TIME) {
                        $endDate = AppConstant::ALWAYS_TIME;
                    }
                    $finalArray['startdate'] = $startDate;
                    $finalArray['enddate'] = $endDate;
                } else {
                    $finalArray['startdate'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['enddate'] = AppConstant::ALWAYS_TIME;
                }
                $linkText = new LinkedText();
                $linkTextId = $linkText->AddLinkedText($finalArray);
                $itemType = AppConstant::LINK;
                $itemId = new Items();
                $lastItemId = $itemId->saveItems($courseId, $linkTextId, $itemType);
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemOrder = $courseItemOrder->itemorder;
                $items = unserialize($itemOrder);
                $blocktree = array(0);
                $sub =& $items;
                for ($i = AppConstant::NUMERIC_ONE; $i < count($blocktree); $i++) {
                    $sub =& $sub[$blocktree[$i] - AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
                }
                array_unshift($sub, intval($lastItemId));
                $itemorder = (serialize($items));
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);
            }
            $this->includeJS(["editor/tiny_mce.js", "general.js"]);
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id));
        }
        $model = new ChangeUserInfoForm();
        $this->includeCSS(["course/items.css"]);
        $this->includeJS(["editor/tiny_mce.js", "course/addlink.js", "general.js"]);
        $responseData = array('model' => $model, 'course' => $course, 'groupNames' => $groupNames, 'rubricsData' => $rubricsData, 'pageOutcomesList' => $pageOutcomesList, 'modifyLinkId' => $modifyLinkId,
            'pageOutcomes' => $pageOutcomes, 'toolvals' => $toolvals, 'gbcatsLabel' => $gbcatsLabel, 'gbcatsId' => $gbcatsId, 'toollabels' => $toollabels, 'checkboxesValues' => $checkboxesValues);
        return $this->renderWithData('addLink', $responseData);
    }

    function mkdir_recursive($pathname, $mode = 0777)
    {
        is_dir(dirname($pathname)) || $this->mkdir_recursive(dirname($pathname), $mode);
        return is_dir($pathname) || @mkdir($pathname, $mode);
    }

    function doesfileexist($type, $key)
    {
        if ($type == 'cfile') {
            if ($GLOBALS['filehandertypecfiles'] == 's3') {
                $s3 = new S3($GLOBALS['AWSkey'], $GLOBALS['AWSsecret']);
                return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/' . $key, false);
            } else {
                $base = rtrim(dirname(dirname(__FILE__)), '/\\') . '/course/files/';
                return file_exists($base . $key);
            }
        } else {
            if ($GLOBALS['filehandertype'] == 's3') {
                $s3 = new S3($GLOBALS['AWSkey'], $GLOBALS['AWSsecret']);
                return $s3->getObjectInfo($GLOBALS['AWSbucket'], $key, false);
            } else {
                $base = rtrim(dirname(dirname(__FILE__)), '/\\') . '/filestore/';
                return file_exists($base . $key);
            }
        }
    }

    public function flatArray($outcomesData)
    {
        global $pageOutcomesList;
        if ($outcomesData) {
            foreach ($outcomesData as $singleData) {
                if (is_array($singleData)) { //outcome group
                    $pageOutcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                    $this->flatArray($singleData['outcomes']);
                } else {
                    $pageOutcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
                }
            }
        }
        return $pageOutcomesList;
    }









}