<?php

namespace app\controllers\course;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\AppModel;
use app\models\AssessmentSession;
use app\models\Blocks;
use app\models\Course;
use app\models\Assessments;
use app\models\Exceptions;
use app\models\forms\CourseSettingForm;
use app\models\forms\SetPassword;
use app\models\Links;
use app\models\Forums;
use app\models\GbScheme;
use app\models\Items;
use app\models\Questions;
use app\models\QuestionSet;
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

    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $this->guestUserHandler();

        $id = $this->getParamVal('id');
        $assessmentSession = AssessmentSession::getAssessmentSession($this->getUserId(), $id);
        $cid = $this->getParamVal('cid');
        $responseData = array();
        $course = Course::getById($cid);
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
                    } else {
                        $item = Items::getById($itemOrder);
                        switch ($item->itemtype) {
                            case 'Assessment':
                                $assessment = Assessments::getByAssessmentId($item->typeid);
                                $tempAray[$item->itemtype] = $assessment;
                                array_push($responseData, $tempAray);
                                break;
                            case 'Calendar':
                                $tempAray[$item->itemtype] = 1;
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

        } else {
// @TODO Need to add logic here
        }

        $course = Course::getById($cid);
        $student = Student::getByCId($cid);
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js']);
        $this->includeJS(['student.js']);
        $returnData = array('courseDetail' => $responseData, 'course' => $course, 'students' => $student,'assessmentSession' => $assessmentSession);
        return $this->render('index', $returnData);
    }

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

        $addtime = $course->latepasshrs * 60 * 60;
        $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
        $usedlatepasses = round(($assessment->allowlate - $assessment->enddate) / ($course->latepasshrs * 3600));
        $startdate = $assessment->startdate;
        $enddate = $assessment->enddate + $addtime;
        $wave = 0;

        $param['assessmentid'] = $assessmentId;
        $param['userid'] = $studentId;
        $param['startdate'] = $startdate;
        $param['enddate'] = $enddate;
        $param['waivereqscore'] = $wave;
        $param['islatepass'] = 1;

        if (count($exception)) {

            if ((($assessment->allowlate % 10) == 1 || ($assessment->allowlate % 10) - 1 > $usedlatepasses) && ($currentTime < $exception->enddate || ($assessment->allowlate > 10 && ($currentTime - $exception->enddate) < $course->latepasshrs * 3600))) {
                $latepass = $student->latepass;
                $student->latepass = $latepass - 1;
                $exception->enddate = $exception->enddate + $addtime;
                $exception->islatepass = $exception->islatepass + 1;
            }

            if ($exception->islatepass != 0) {
                echo "<p>Un-use late-pass</p>";
                if ($currentTime > $assessment->enddate && $exception->enddate < $currentTime + $course->latepasshrs * 60 * 60) {
                    echo '<p>Too late to un-use this LatePass</p>';
                } else {
                    if ($currentTime < $assessment->enddate) {
                        $exception->islatepass = $exception->islatepass - 1;
                    } else {
                        //figure how many are unused
                        $n = floor(($exception->enddate - $currentTime) / ($course->latepasshrs * 60 * 60));
                        $newend = $exception->enddate - $n * $course->latepasshrs * 60 * 60;
                        if ($exception->islatepass > $n) {
                            $exception->islatepass = $exception->islatepass - $n;
                            $exception->enddate = $newend;
                        } else {
                            // @TODO push anything into db.
                        }
                    }
                    echo "<p>Returning $n LatePass" . ($n > 1 ? "es" : "") . "</p>";
                    $student->latepass = $student->latepass + $n;
                }
            } else {
                echo '<p>Invalid</p>';
            }
            $exception->attributes = $param;
            $exception->save();
            $student->save();
        }
        $this->redirect(AppUtility::getURLFromHome('course', 'course/index?id=' . $assessmentId . '&cid=' . $courseId));
    }

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
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id.'&cid=' .$course->id));
            }
            else
            {
                $this->setErrorFlash(AppConstant::SET_PASSWORD_ERROR);
            }
        }
        $returnData = array('model' => $model, 'assessments' => $assessment);
        return $this->renderWithData('setPassword', $returnData);
    }


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

    public function actionCourseSetting()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $course = Course::getById($cid);
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

    public function actionDeleteCourse()
    {
        $model = new DeleteCourseForm();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        if ($course) {
            $status = Course::deleteCourse($course->id);
            if ($status) {
                $this->setSuccessFlash('Deleted successfully.');
            } else {
                $this->setErrorFlash(AppConstant::SOMETHING_WENT_WRONG);
            }
        } else {
            $this->setErrorFlash(AppConstant::SOMETHING_WENT_WRONG);
        }
        $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
    }

    public function actionTransferCourse()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $sortBy = 'FirstName';
        $order = AppConstant::ASCENDING;
        $users = User::findAllUsers($sortBy, $order);
        $course = Course::getById($cid);
        //$this->includeJS(['/course/transfercourse.js']);
        $this->includeCSS(['dashboard.css']);
        $returnData = array('users' => $users, 'course' => $course);
        return $this->renderWithData('transferCourse', $returnData);
    }

    public function actionUpdateOwner()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();

            if ($this->getAuthenticatedUser()->rights == 75) // 75 is instructor right
            {

            } elseif ($this->getAuthenticatedUser()->rights > 75) {
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
            } elseif ($this->getAuthenticatedUser()->rights > 40) {
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
        $cid = $params['cid'];
        $sortBy = 'FirstName';
        $order = AppConstant::ASCENDING;
        $users = User::findAllTeachers($sortBy, $order);
        $teachers = Teacher::getAllTeachers($cid);
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
            $cid = $params['cid'];
            $usersIds = json_decode($params['usersId']);

            for ($i = 0; $i < count($usersIds); $i++) {
                $teacher = new Teacher();
                $teacher->create($usersIds[$i], $cid);
            }

            return $this->successResponse();
        }
    }

    public function actionRemoveAllAsTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();

            $cid = $params['cid'];
            $usersIds = json_decode($params['usersId']);

            for ($i = 0; $i < count($usersIds); $i++) {
                $teacher = new Teacher();
                $teacher->removeTeacher($usersIds[$i], $cid);
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

    public function actionShowLinkedText()
    {
        $cid = $this->getParamVal('cid');
        $id = Yii::$app->request->get('id');
        $course = Course::getById($cid);
        $link = Links::getById($id);
        $returnData = array('course' => $course, 'links' => $link);
        return $this->renderWithData('showLinkedText', $returnData);
    }

    public function actionGetAssessmentDataAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $cid = $params['cid'];
        $assessments = Assessments::getByCourseId($cid);
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
                'reviewDateString' => $assessment['reviewdate']
                );
        }
        return $this->successResponse($assessmentArray);
    }
}