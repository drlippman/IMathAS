<?php

namespace app\controllers\course;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\AppModel;
use app\models\AssessmentSession;
use app\models\Blocks;
use app\models\CalItem;
use app\models\Course;
use app\models\Assessments;
use app\models\Exceptions;
use app\models\forms\CourseSettingForm;
use app\models\InstrFiles;
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
use app\models\ExternalTools;
use Yii;
use app\controllers\AppController;
use app\models\forms\DeleteCourseForm;
use yii\db\Exception;
use yii\helpers\Html;


class CourseController extends AppController
{
    /**
     * Display all course in item order
     */
    public $enableCsrfValidation = false;
    public function actionIndex()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $userId = $user->id;
        $id = $this->getParamVal('id');
        $assessmentSession = AssessmentSession::getAssessmentSession($this->getUserId(), $id);
        $courseId = $this->getParamVal('cid');
        $exception = Exceptions::getTotalData($userId);
        $responseData = array();
        $calendarCount = array();
        $course = Course::getById($courseId);
        if ($course && count($itemOrders = unserialize($course->itemorder))) {
            foreach ($itemOrders as $key => $itemOrder) {
                $tempAray = array();
                if (is_array($itemOrder) || count($blockItems = $itemOrder['items']))
                {
                    $tempAray['Block'] = $itemOrder;
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
                            if($exception)
                            {
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
        if($message){
            foreach($message as $singleMessage){
                if(in_array($singleMessage->isread, $isReadArray))
                    array_push($msgList,$singleMessage);
            }
        }
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js', 'latePass.js']);
        $returnData = array('calendarData' =>$calendarCount,'courseDetail' => $responseData, 'course' => $course, 'students' => $student,'assessmentSession' => $assessmentSession, 'messageList' => $msgList, 'exception' => $exception);
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
        $returnData = array('cid'=> $course, 'assessments' => $assessment, 'questions' => $questionRecords, 'questionSets' => $questionSet,'assessmentSession' => $assessmentSession,'now' => time());
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
            if ($exception->islatepass != AppConstant::NUMERIC_ZERO) {
                echo "<p>Un-use late-pass</p>";
                if ($currentTime > $assessment->enddate && $exception->enddate < $currentTime + $course->latepasshrs * AppConstant::SECONDS * AppConstant::SECONDS) {
                    echo '<p>Too late to un-use this LatePass</p>';
                } else {
                    if ($currentTime < $assessment->enddate) {
                        $exception->islatepass = $exception->islatepass - AppConstant::NUMERIC_ONE;
                    } else {
                        //figure how many are unused
                        $n = floor(($exception->enddate - $currentTime) / ($course->latepasshrs * AppConstant::SECONDS * AppConstant::SECONDS));
                        $newend = $exception->enddate - $n * $course->latepasshrs * AppConstant::SECONDS * AppConstant::SECONDS;
                        if ($exception->islatepass > $n) {
                            $exception->islatepass = $exception->islatepass - $n;
                            $exception->enddate = $newend;
                        }
                    }
                    echo "<p>Returning $n LatePass" . ($n > AppConstant::NUMERIC_ONE ? "es" : "") . "</p>";
                    $student->latepass = $student->latepass + $n;
                }
            }
            $exception->attributes = $param;
            $exception->save();
            $student->save();
        }
        $this->redirect(AppUtility::getURLFromHome('course', 'course/index?id=' . $assessmentId . '&cid=' . $courseId));
    }
    /**
     * Display password, when assessment need password.
     */
    public function actionPassword()
    {
        $this->guestUserHandler();
        $model = new SetPassword();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        if ($this->isPostMethod())
        {
            $params = $this->getRequestParams();
            $password = $params['SetPassword']['password'];
            if($password == $assessment->password)
            {
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/password?id=' . $assessment->id.'&cid=' .$course->id));
            }
            else
            {
                $this->setErrorFlash(AppConstant::SET_PASSWORD_ERROR);
            }
        }
        $returnData = array('model' => $model, 'assessments' => $assessment);
        return $this->renderWithData('setPassword', $returnData);
    }
    /**
     * Create new course at admin side
     */
    public function actionAddNewCourse()
    {
        $this->guestUserHandler();
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
            }
            else{
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
            if ($this->getAuthenticatedUser()->rights == AppConstant::GROUP_ADMIN_RIGHT) // 75 is instructor right
            {
                /*
                 * Group admin not started
                 */
            } elseif ($this->getAuthenticatedUser()->rights > AppConstant::GROUP_ADMIN_RIGHT) {
                $course = Course::getByIdandOwnerId($params['cid'], $params['oldOwner']);
                if ($course) {
                    $course->ownerid = $params['newOwner'];
                    $course->save();
                    $teacher = Teacher::getByUserId($params['oldOwner'], $params['cid']);
                    if ($teacher) {
                        $teacher->delete();
                    }
                    $newTeacher = new Teacher();
                    $newTeacher->create($params['oldOwner'], $params['cid']);
                }
            } elseif ($this->getAuthenticatedUser()->rights > AppConstant::LIMITED_COURSE_CREATOR_RIGHT) {
                if ($params['oldOwner'] == $this->getUserId()) {
                    $course = Course::getByIdandOwnerId($params['cid'], $params['oldOwner']);
                    if ($course) {
                        $course->ownerid = $params['newOwner'];
                        $course->save();
                    }
                }
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
        return $this->successResponse(array('teachers' => $teacherList, 'nonTeachers' => $nonTeacher));
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
        $defFeedbackText = addslashes($assessment->deffeedbacktext);
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
        $courseId = $this->getParamVal('cid');
        $id = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $link = Links::getById($id);
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
        $assessments = Assessments::getByCourseId($cid);
        $calendarItems = CalItem::getByCourseId($cid);
        $CalendarLinkItems = Links::getByCourseId($cid);
        $calendarInlineTextItems = InlineText::getByCourseId($cid);
        $assessmentArray = array();
        foreach ($assessments as $assessment)
        {
            $assessmentArray[] = array(
                'startDate' => AppUtility::getFormattedDate($assessment['startdate']),
                'endDate' => AppUtility::getFormattedDate($assessment['enddate']),
                'reviewDate' => AppUtility::getFormattedDate($assessment['reviewdate']),
                'name' => $assessment['name'],
                'startDateString' => $assessment['startdate'],
                'endDateString' => $assessment['enddate'],
                'reviewDateString' => $assessment['reviewdate'],
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'assessmentId' => $assessment['id'],
                'courseId' => $assessment['courseid']
            );
        }
        $calendarArray = array();
        foreach ($calendarItems as $calendarItem)
        {
            $calendarArray[] = array(
                'courseId' => $calendarItem['courseid'],
                'date' => AppUtility::getFormattedDate($calendarItem['date']),
                'title' => $calendarItem['title'],
                'tag' => $calendarItem['tag']
            );
        }
        $calendarLinkArray = array();
        foreach ($CalendarLinkItems as $CalendarLinkItem)
        {
            $calendarLinkArray[] = array(
                'courseId' => $CalendarLinkItem['courseid'],
                'title' => $CalendarLinkItem['title'],
                'startDate' => AppUtility::getFormattedDate($CalendarLinkItem['startdate']),
                'endDate' => AppUtility::getFormattedDate($CalendarLinkItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $CalendarLinkItem['startdate'],
                'endDateString' => $CalendarLinkItem['enddate'],
                'linkedId' => $CalendarLinkItem['id'],
                'calTag' => $CalendarLinkItem['caltag']
            );
        }
        $calendarInlineTextArray = array();
        foreach ($calendarInlineTextItems as $calendarInlineTextItem)
        {
            $calendarInlineTextArray[] = array(
                'courseId' => $calendarInlineTextItem['courseid'],
                'endDate' => AppUtility::getFormattedDate($calendarInlineTextItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $calendarInlineTextItem['startdate'],
                'endDateString' => $calendarInlineTextItem['enddate'],
                'calTag' => $calendarInlineTextItem['caltag']
            );
        }
        $responseData = array('assessmentArray' => $assessmentArray,'calendarArray' => $calendarArray, 'calendarLinkArray' => $calendarLinkArray, 'calendarInlineTextArray' => $calendarInlineTextArray);
        return $this->successResponse($responseData);
    }

    public function actionBlockIsolate()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
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
        if($message){
            foreach($message as $singleMessage){
                if(in_array($singleMessage->isread, $isReadArray))
                    array_push($msgList,$singleMessage);
            }
        }
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js', 'latePass.js']);
        $returnData = array('course' => $course, 'messageList' => $msgList, 'courseDetail' => $responseData);
        return $this->render('blockIsolate', $returnData);
    }

//    Display calendar on click of menuBars
    public function actionCalendar()
    {
        $this->guestUserHandler();
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js']);
        return $this->render('calendar');
    }
    /**
     * Modify inline text: Teacher
     */
    public function actionModifyInlineText()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
        $inlineId = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $inlineText = InlineText::getById($inlineId);
        $params = $this->getRequestParams();
        $inlineTextId = $params['id'];
        $saveTitle = '';
        if(isset($params['id']))
        {
            $hideTitle = false;
            $pageTitle = AppConstant::INLINE_TEXT_MODIFY_TITLE;
            if($this->isPostMethod()){
                $page_formActionTag = AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inlineText->id.'&courseId=' .$course->id);
                $saveChanges = new InlineText();
                $saveChanges->updateChanges($params, $inlineTextId);
                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
            }
            $saveTitle = AppConstant::SAVE_BUTTON;
        }
        else {
            $pageTitle = AppConstant::INLINE_TEXT_ADD_TITLE;
            if($this->isPostMethod()){
                $startDate = AppUtility::parsedatetime($params['StartDate'],$params['start_end_time']);
                $endDate = AppUtility::parsedatetime($params['EndDate'],$params['end_end_time']);
                $page_formActionTag = AppUtility::getURLFromHome('course', 'course/modify-inline-text?courseId=' .$course->id);
                $saveChanges = new InlineText();
                $lastInlineId = $saveChanges->saveChanges($params);
                $saveItems = new Items();
                $lastItemsId = $saveItems->saveItems($courseId,$lastInlineId,'InlineText');
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemOrder = $courseItemOrder->itemorder;
                $items = unserialize($itemOrder);
                if ($_FILES['userfile']['name']!='') {
                    $uploadDir = rtrim(dirname(__FILE__), '/\\') .'/files/';
                    $userFileName = preg_replace('/[^\w\.]/','',basename($_FILES['userfile']['name']));
                    $fileName = $userFileName;

                    $extension = strtolower(strrchr($userFileName,"."));
                    $badExtensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
                    if (in_array($extension,$badExtensions)) {
                        echo "<p>File type is not allowed</p>";
                    } else {
                        include_once('../components/filehandler.php');
                        if (($fileName = storeuploadedcoursefile('userfile',$courseId.'/'.$fileName))!==false) {
                            if (trim($params['newfiledescr'])=='') {
                                $params['newfiledescr'] = $fileName;
                            }
                            $file = new InstrFiles();
                            $fileId = $file->saveFile($params, $lastInlineId);
                        } else {
                            echo "<p>Error uploading file!</p>\n";
                        }
                    }
                }
                $blockTree = array(AppConstant::NUMERIC_ZERO);
                $sub =& $items;
                for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree);$i++) {
                        $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
                    }
                array_unshift($sub,intval($lastItemsId));
                $itemOrder = addslashes(serialize($items));
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemOrder, $courseId);
                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
            }
            $saveTitle = AppConstant::New_Item;
        }
        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor.js']);
        $returnData = array('course' => $course, 'inlineText' => $inlineText, 'saveTitle' => $saveTitle, 'pageTitle' => $pageTitle, 'hidetitle' => $hideTitle);
        return $this->render('modifyInlineText', $returnData);
    }

    public function actionAddLink()
    {
        $params = $this->getRequestParams();
        $user = $this->getAuthenticatedUser();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $modifyLinkId = $params['id'];
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $rubricsData = Rubrics::getByUserId($user['id']);
        $query = Outcomes::getByCourse($courseId);
        $key = AppConstant::NUMERIC_ONE;
        $pageOutcomes = array();
        if ($query) {
            foreach($query as $singleData) {
                $pageOutcomes[$singleData['id']] = $singleData['name'];
                $key++;
            }
        }
        $pageOutcomesList = array();
        if ($key>AppConstant::NUMERIC_ZERO) {//there were outcomes
            $query = $course['outcomes'];
            $outcomeArray = unserialize($query);
            $result = $this->flatArray($outcomeArray);
            if($result){
                foreach($result as $singlePage){
                    array_push($pageOutcomesList,$singlePage);
                }
            }
        }
        $key = 0;
        $gbcatsData = GbCats::getByCourseId($courseId);
        foreach($gbcatsData as $group){
            $gbcatsId[$key] = $group['id'];
            $gbcatsLabel[$key] = $group['name'];
            $key++;
        }
        $toolsData = ExternalTools::externalToolsData($courseId);
        $toolvals = array();
        $toolvals[0] = AppConstant::NUMERIC_ZERO;
        $key = 1;
        foreach($toolsData as $tool){
            $toolvals[$key++] = $tool['id'];

        }
        $toollabels[0] = 'Select a tool...';
        $key = 1;
        foreach($toolsData as $tool){
            $toollabels[$key++] = $tool['name'];

        }
        if ($params['id']) {
//            $query = "SELECT * FROM imas_linkedtext WHERE id='{$_GET['id']}'";
            $linkData = LinkedText::getById($params['id']);
//            AppUtility::dump($linkData);
            $gbcat = 0;
            $cntingb = 1;
            $tutoredit = 0;
            $gradesecret = uniqid();

            if ($linkData['avail'] == 2 && $linkData['startdate'] > 0) {
                $altoncal = 1;
            } else {
                $altoncal = 0;
            }
            if (substr($linkData['text'], 0, 4) == 'http') {
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
                $toolparts = explode('~~', substr($linkData['text'], 8));

                $selectedtool = $toolparts[0];
                $toolcustom = $toolparts[1];
                if (isset($toolparts[2])) {
                    $toolcustomurl = $toolparts[2];
                } else {
                    $toolcustomurl = '';
                }
                if (isset($toolparts[3])) {
                    $gbcat = $toolparts[3];
                    $cntingb = $toolparts[4];
                    $tutoredit = $toolparts[5];
                    $gradesecret = $toolparts[6];
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
                'summary' =>  $linkData['summary'],
                'text' =>  $linkData['text'],
//            $line['text'] = "<p>Enter text here</p>";
                'startdate' => $linkData['startdate'],
                'enddate' => $linkData['enddate'],
                'gbcat' => 0,
                'cntingb' => 1,
                'tutoredit' => 0,
                'gradesecret' => uniqid(),
                'webaddr' => $webaddr,
                'filename' => $filename,
                'altoncal' => $altoncal,
                'altoncal' => 0,
                'type' => $type,
                'gradeoutcomes' => $gradeoutcomes,
//            $selectedgbitem = 0,
                'points' => $points,
                'toolparts' => $toolparts,
                'cntingb' => $cntingb,
                'gbcat' => $gbcat,
                'tutoredit' => $tutoredit,
                'gradesecret' => $gradesecret,
                'saveButtonTitle' => $saveButtonTitle,
                'saveTitle' => $saveTitle,
                'points' => $points,
            );
        } else {
//            //set defaults
            $checkboxesValues = array(
                'saveButtonTitle' => "Create Link",
                'saveTitle' => "Create Link",
                'title' => "Enter title here",
                'summary' => "Enter summary here (displays on course page)",
                'text' => "Enter text here",
                'points' => 0,

            );
//
        }if($this->isPost()){
        $outcomes = array();
        $params = $this->getRequestParams();
        $modifyLinkId = $params['id'];
        if(isset($modifyLinkId)){
//            AppUtility::dump($params);
            $link = new LinkedText();
            $link->updateLinkData($params);
        }else{
            foreach ($params['outcomes'] as $o) {
                if (is_numeric($o) && $o>0) {
                    $outcomes[] = intval($o);
                }
            }
            $outcomes = implode(',',$outcomes);

            $processingerror = false;
//            if ($params['linktype']=='text') {
//
//            } else
            if ($params['linktype']=='file') {
//                require_once("../includes/filehandler.php");
//AppUtility::dump($_FILES['userfile']['name']);\
                if ($_FILES['userfile']['name']!='') {
                    //$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
                    //$uploadfile = $uploaddir . "$cid-" . basename($_FILES['userfile']['name']);
                    $userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['userfile']['name']));
                    $filename = $userfilename;
                    $extension = strtolower(strrchr($userfilename,"."));
                    $badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
//                    AppUtility::dump($extension);
                    if (in_array($extension,$badextensions)) {
                        $overwriteBody = 1;
                        $body = "<p>File type is not allowed</p>";
                    } else {

                        if ($_FILES['userfile']['error']==1 || $_FILES['userfile']['error']==2) {

                            $errormsg = "File size too large";
                            $params['text'] = "File upload error - $errormsg";
                            $uploaderror = true;
                        } else {

                            if (($filename=$this->storeuploadedcoursefile('userfile',$courseId.'/'.$filename))===false) {
//AppUtility::dump($filename);
                                $errormsg = "Try again";
                                $params['text'] = "File upload error - $errormsg";
                                $uploaderror = true;
                            } else {
                                $params['text'] = "file:$filename";
//                                AppUtility::dump($filename);
                            }

                        }
                    }

                } else if (!empty($_POST['curfile'])) {
                    //$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
                    ///if (!file_exists($uploaddir . $_POST['curfile'])) {
                    if (!($this->doesfileexist('cfile',$_POST['curfile']))) {
                        $processingerror = true;
                    } else {
                        $_POST['text'] = "file:".$_POST['curfile'];
                    }
                } else {
                    $processingerror = true;
                }
            } else if ($params['linktype']=='web') {
                $params['text'] = trim(strip_tags($params['web']));

                if (substr($params['text'],0,4)!='http') {

                    $processingerror = true;
                }

            } else if ($params['linktype']=='tool') {
                if ($params['tool']==0) {
                    $processingerror = true;
                } else {
                    //tool~~custom~~customurl~~gbcategory~~cntingb~~tutoredit~~gradesecret
                    $params['text'] = 'exttool:'.$params['tool'].'~~'.$params['toolcustom'].'~~'.$params['toolcustomurl'];
                    if ($params['usegbscore']==0 || $params['points']==0) {
                        $points = 0;
                    } else {
                        $params['text'] .= '~~'.$params['gbcat'].'~~'.$params['cntingb'].'~~'.$params['tutoredit'].'~~'.$params['gradesecret'];
                        $points = intval($params['points']);
                    }
                }
            }


            $s = new ExternalTools();
            $s->updateExternalToolsData($params);
            $linkText = new LinkedText();
            $linkTextId = $linkText->AddLinkedText($params,$outcomes);
            $itemType = AppConstant::LINK;
            $itemId = new Items();
            $lastItemId =   $itemId -> saveItems($courseId, $linkTextId, $itemType);
            $courseItemOrder = Course::getItemOrder($courseId);
            $itemOrder = $courseItemOrder->itemorder;
            $items = unserialize($itemOrder);
            $blocktree = array(0);
            $sub =& $items;
            for ($i=1;$i<count($blocktree);$i++) {
                $sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
            }
            array_unshift($sub,intval($lastItemId));
            $itemorder = addslashes(serialize($items));
            $saveItemOrderIntoCourse = new Course();
            $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);
        }

        $this->includeJS(["editor/tiny_mce.js","general.js"]);
        return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
    }

//        AppUtility::dump($tool);
        $this->includeJS(["editor/tiny_mce.js","general.js"]);
        $responseData = array('course' => $course,'groupNames' => $groupNames,'rubricsData' => $rubricsData,'pageOutcomesList' => $pageOutcomesList,'modifyLinkId' => $modifyLinkId,
            'pageOutcomes' => $pageOutcomes,'toolvals' => $toolvals,'gbcatsLabel' => $gbcatsLabel,'gbcatsId' => $gbcatsId,'toollabels' => $toollabels,'checkboxesValues' => $checkboxesValues);
        return $this->renderWithData('addLink',$responseData);
    }

    function storeuploadedcoursefile($id,$key,$sec="public-read") {
//AppUtility::dump('fun');
        if ($this->filehandertypecfiles == 's3') {
//            AppUtility::dump($this->filehandertypecfiles);
            if ($sec=="public" || $sec=="public-read") {
                $sec = "public-read";
            } else {
                $sec = "private";
            }
            if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
//                AppUtility::dump($_FILES[$id]);
                $s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
                $t=0;
                $fn = $key;
                while ($s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, false) !==false) {
                    $key = substr($fn,0,strpos($fn,"."))."_$t".strstr($fn,".");
                    $t++;
                }
                if ($s3->putObjectFile($_FILES[$id]['tmp_name'],$GLOBALS['AWSbucket'],'cfiles/'.$key,$sec)) {
                    return $key;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {

            if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
                $base = rtrim(dirname(dirname(__FILE__)), '/\\').'/Uploads';
//                AppUtility::dump($base);
                $keydir = dirname($key);
                $dir = $base.dirname($key);
                $fn = basename($key);
                if (!is_dir($dir)) {
                    $this->mkdir_recursive($dir);
                }
                $t=0; $tfn = $fn;
                while(file_exists($dir.'/'.$fn)){
                    $fn = substr($tfn,0,strpos($tfn,"."))."_$t".strstr($tfn,".");
                    $t++;
                }
                if (move_uploaded_file($_FILES[$id]['tmp_name'],$dir.'/'.$fn)) {
                    return $keydir.'/'.$fn;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }
    function mkdir_recursive($pathname, $mode=0777)
    {
        is_dir(dirname($pathname)) || $this->mkdir_recursive(dirname($pathname), $mode);
        return is_dir($pathname) || @mkdir($pathname, $mode);
    }
    function doesfileexist($type,$key) {
        if ($type=='cfile') {
            if ($GLOBALS['filehandertypecfiles'] == 's3') {
                $s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
                return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, false);
            } else {
                $base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
                return file_exists($base.$key);
            }
        } else {
            if ($GLOBALS['filehandertype'] == 's3') {
                $s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
                return $s3->getObjectInfo($GLOBALS['AWSbucket'], $key, false);
            } else {
                $base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
                return file_exists($base.$key);
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