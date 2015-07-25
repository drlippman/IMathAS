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
        $this->layout = 'master';
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
        if ($course && ($itemOrders = unserialize($course->itemorder))) {
            foreach ($itemOrders as $key => $itemOrder) {
                $tempAray = array();
                if (is_array($itemOrder) || count($blockItems = $itemOrder['items'])) {
                    $tempAray['Block'] = $itemOrder;
                    $blockItems = $itemOrder['items'];
                    $tempItemList = array();
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
        $isreadArray = array(0, 4, 8, 12);
        $msgList = array();
        if($message){
            foreach($message as $singleMessage){
                if(in_array($singleMessage->isread, $isreadArray))
                    array_push($msgList,$singleMessage);
            }
        }
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css', 'course/course.css']);
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
        $addTime = $course->latepasshrs * 60 * 60;
        $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
        $usedlatepasses = round(($assessment->allowlate - $assessment->enddate) / ($course->latepasshrs * 3600));
        $startdate = $assessment->startdate;
        $enddate = $assessment->enddate + $addTime;
        $wave = AppConstant::NUMERIC_ZERO;

        $param['assessmentid'] = $assessmentId;
        $param['userid'] = $studentId;
        $param['startdate'] = $startdate;
        $param['enddate'] = $enddate;
        $param['waivereqscore'] = $wave;
        $param['islatepass'] = AppConstant::NUMERIC_ONE;

        if (count($exception)) {
            if ((($assessment->allowlate % 10) == AppConstant::NUMERIC_ONE || ($assessment->allowlate % 10) - AppConstant::NUMERIC_ONE > $usedlatepasses) && ($currentTime < $exception->enddate || ($assessment->allowlate > 10 && ($currentTime - $exception->enddate) < $course->latepasshrs * 3600))) {
                $latepass = $student->latepass;
                AppUtility::dump($latepass);
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
            $bodyParams = $this->getRequestParams();
            $user = $this->getAuthenticatedUser();
            $course = new Course();
            $courseId = $course->create($user, $bodyParams);
            if ($courseId) {
                $teacher = new Teacher();
                $teacherId = $teacher->create($user->id, $courseId);
                $gbScheme = new GbScheme();
                $gbSchemeId = $gbScheme->create($courseId);
                if ($teacherId && $gbSchemeId) {
                    $this->setSuccessFlash('Course added successfully. Course id: ' . $courseId . ' and Enrollment key: ' . $bodyParams['CourseSettingForm']['enrollmentKey']);
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
                $bodyParams = $this->getRequestParams();
                $params = $bodyParams['CourseSettingForm'];
                $courseSetting['name'] = $params['courseName'];
                $courseSetting['enrollkey'] = $params['enrollmentKey'];
                $available = $this->getSanitizedValue($params['available'], AppConstant::AVAILABLE_NOT_CHECKED_VALUE);
                $courseSetting['available'] = AppUtility::makeAvailable($available);
                $courseSetting['copyrights'] = $params['copyCourse'];
                $courseSetting['msgset'] = $params['messageSystem'];
                $toolset = $this->getSanitizedValue($params['navigationLink'], AppConstant::NAVIGATION_NOT_CHECKED_VALUE);
                $courseSetting['toolset'] = AppUtility::makeToolset($toolset);
                $courseSetting['deflatepass'] = $params['latePasses'];
                $courseSetting['theme'] = $params['theme'];
                $courseSetting['deftime'] = AppUtility::calculateTimeDefference($bodyParams['start_time'], $bodyParams['end_time']);
                $courseSetting['end_time'] = $bodyParams['end_time'];
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
        $sortBy = 'FirstName';
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
        $params = Yii::$app->request->getBodyParams();
        $courseId = $params['cid'];
        $sortBy = 'FirstName';
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

            for ($i = 0; $i < count($usersIds); $i++) {
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

            for ($i = 0; $i < count($usersIds); $i++) {
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
        list($qlist, $seedlist, $reviewseedlist, $scorelist, $attemptslist, $lalist) = AppUtility::generateAssessmentData($assessment->itemorder, $assessment->shuffle, $assessment->id);

        $bestscorelist = $scorelist . ';' . $scorelist . ';' . $scorelist;
        $scorelist = $scorelist . ';' . $scorelist;
        $bestattemptslist = $attemptslist;
        $bestseedslist = $seedlist;
        $bestlalist = $lalist;
        $starttime = time();
        $deffeedbacktext = addslashes($assessment->deffeedbacktext);
        $ltisourcedid = '';

        $param['questions'] = $qlist;
        $param['seeds'] = $seedlist;
        $param['userid'] = $id;
        $param['assessmentid'] = $id;
        $param['attempts'] = $attemptslist;
        $param['lastanswers'] = $lalist;
        $param['reviewscores'] = $scorelist;
        $param['reviewseeds'] = $reviewseedlist;
        $param['bestscores'] = $bestscorelist;
        $param['scores'] = $scorelist;
        $param['bestattempts'] = $bestattemptslist;
        $param['bestseeds'] = $bestseedslist;
        $param['bestlastanswers'] = $bestlalist;
        $param['starttime'] = $starttime;
        $param['feedback'] = $deffeedbacktext;
        $param['lti_sourcedid'] = $ltisourcedid;

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
        $id = Yii::$app->request->get('id');
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
        $currentDate = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
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
        foreach ($calendarItems as $calendarItem)
        {
            $calendarArray[] = array(
                'courseId' => $calendarItem['courseid'],
                'date' => AppUtility::getFormattedDate($calendarItem['date']),
                'dueTime' => AppUtility::getFormattedTime($calendarItem['date']),
                'title' => ucfirst($calendarItem['title']),
                'tag' => ucfirst($calendarItem['tag'])
            );
        }
        $calendarLinkArray = array();
        foreach ($CalendarLinkItems as $CalendarLinkItem)
        {
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
        foreach ($calendarInlineTextItems as $calendarInlineTextItem)
        {
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
        $responseData = array('assessmentArray' => $assessmentArray,'calendarArray' => $calendarArray, 'calendarLinkArray' => $calendarLinkArray, 'calendarInlineTextArray' => $calendarInlineTextArray, 'currentDate' => $currentDate);
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
                                        $tempItem[$item->itemtype] = 1;
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
        $isreadArray = array(0, 4, 8, 12);
        $msgList = array();
        if($message){
            foreach($message as $singleMessage){
                if(in_array($singleMessage->isread, $isreadArray))
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
        $this->layout = "master";
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css']);
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
        $courseId = $this->getParamVal('courseId');
        $inlineId = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $inlineText = InlineText::getById($inlineId);

        $params = $this->getRequestParams();
        $inlineTextId = $params['id'];
        $saveTitle = '';
        if(isset($params['id']))
        {
            $hidetitle = false;
            $pageTitle = 'Modify Inline Text';
            if($this->isPost()){
                $params = $_POST;
                $page_formActionTag = AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inlineText->id.'&courseId=' .$course->id);
                if ($params['title']=='##hidden##') {
                    $hidetitle = true;
                    $params['title']='';
                }
                $saveChanges = new InlineText();
                $saveChanges->updateChanges($params, $inlineTextId);
                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
            }
            $saveTitle = AppConstant::SAVE_BUTTON;
        }
        else {
            $pageTitle = 'Add Inline Text';
            if($this->isPost()){
                $params = $_POST;
                $startDate = AppUtility::parsedatetime($params['StartDate'],$params['start_end_time']);
                $endDate = AppUtility::parsedatetime($params['EndDate'],$params['end_end_time']);

//                AppUtility::dump($d);
                $page_formActionTag = AppUtility::getURLFromHome('course', 'course/modify-inline-text?courseId=' .$course->id);
                $saveChanges = new InlineText();
                $lastInlineId = $saveChanges->saveChanges($params);

                $saveItems = new Items();
                $lastItemsId = $saveItems->saveItems($courseId,$lastInlineId,'InlineText');
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemorder = $courseItemOrder->itemorder;

                $items = unserialize($itemorder);

                $blocktree = array(0);
                $sub =& $items;

                for ($i=1;$i<count($blocktree);$i++) {
                        $sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing

                    }
                array_unshift($sub,intval($lastItemsId));
                $itemorder = addslashes(serialize($items));
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);

                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
            }
            $saveTitle = AppConstant::New_Item;
        }
        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor.js']);
        $returnData = array('course' => $course, 'inlineText' => $inlineText, 'saveTitle' => $saveTitle, 'pageTitle' => $pageTitle, 'hidetitle' => $hidetitle);
        return $this->render('modifyInlineText', $returnData);
    }

    public function actionDeleteInlineText()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
        $inlineId = $this->getParamVal('id');
//        $b = $this->getParamVal('b');
//        if($b){
        $course = Course::getById($courseId);
        $inlineText = InlineText::getById($inlineId);
        $itemTypeid = Items::getByTypeId($inlineId);
        $itemId = $itemTypeid->id;

        $deleteItemId = Items::deletedItems($itemId);
        $deleteInlineTextId = InlineText::deleteInlineTextId($inlineId);
        $items = array();
        $itemOrder = Course::getItemOrder($courseId);
        $items = unserialize($itemOrder['itemorder']);

        $blocktree = array(0);
        $sub =& $items;

        for ($i=1;$i<count($blocktree);$i++) {
            $sub =& $sub[$blocktree[$i]-1]['items'];

        }
        $key = array_search($itemId, $sub);
        array_splice($sub, $key, 1);

        $itemorder = addslashes(serialize($items));

        $saveItemOrderIntoCourse = new Course();
        $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);
        $returnData = array('inline' => $inlineText, 'course' => $course);
        return $this->render('deleteInlineText', $returnData);
    }

    public function actionCopyItem()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
        $inlineId = $this->getParamVal('id');


    }

}