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

        $cid = $this->getParamVal('cid');
        $id = Yii::$app->request->get('id');
        $uid = Yii::$app->user->identity->getId();
        $assessmentSession = AssessmentSession::getAssessmentSession(Yii::$app->user->identity->id, $id);
        $cid = Yii::$app->request->get('cid');
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
        $this->includeCSS(['../css/fullcalendar.min.css', '../css/calendar.css']);
        $this->includeJS(['../js/moment.min.js', '../js/fullcalendar.min.js']);
        $this->includeJS(['../js/student.js']);
        return $this->render('index', ['courseDetail' => $responseData, 'course' => $course, 'students' => $student,'assessmentSession' => $assessmentSession]);

    }

    public function actionShowAssessment()
    {
        $this->guestUserHandler();

        $id = Yii::$app->request->get('id');
        $courseId = Yii::$app->request->get('cid');
        $assessment = Assessments::getByAssessmentId($id);
        $assessmentSession = AssessmentSession::getAssessmentSession(Yii::$app->user->identity->id, $id);
        $questionRecords = Questions::getByAssessmentId($id);
        $questionSet = QuestionSet::getByQuesSetId($id);
        $course = Course::getById($courseId);
//       AppUtility::dump($course);

        $assessmentclosed = false;

        if ($assessment->avail == 0) {
            $assessmentclosed = true;
        }

        $this->saveAssessmentSession($assessment, $id);

        $this->includeCSS(['../css/mathtest.css', '../css/default.css', '../css/showAssessment.css']);
        $this->includeJS(['../js/timer.js']);
        return $this->render('ShowAssessment', ['cid'=> $course, 'assessments' => $assessment, 'questions' => $questionRecords, 'questionSets' => $questionSet,'assessmentSession' => $assessmentSession,'now' => time()]);
    }

    public function actionLatePass()
    {
        $this->guestUserHandler();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $studentId = Yii::$app->user->identity->id;
        $exception = Exceptions::getByAssessmentId($assessmentId);

        $assessment = Assessments::getByAssessmentId($assessmentId);
        $student = Student::getByCourseId($courseId, $studentId);
        $course = Course::getById($courseId);

        $addtime = $course->latepasshrs * 60 * 60;
        $hasexception = true;
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

            if ($assessment->allowlate != 0 && $assessment->enddate != 0 && $assessment->startdate != 0) {

                $hasException = true;
            }


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

    public function actionQuestion()
    {
        $this->guestUserHandler();
        $questionId = $this->getParamVal('to');
        AppUtility::basicShowQuestions($questionId);
        die;
        $this->render(AppUtility::getURLFromHome('course','course/showAssessment?id='.$questionId));

    }

    public function actionPassword()
    {
        $this->guestUserHandler();
        $model = new SetPassword();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $assessment = Assessments::getByAssessmentId($assessmentId);

        if ($this->isPostMethod())
        {
            $params = $this->getBodyParams();
            $password = $params['SetPassword']['password'];
            if($password == $assessment->password)
            {
                //return $this->redirect('show-assessment');
            }
            else
            {
                $this->setErrorFlash(AppConstant::SET_PASSWORD_ERROR);
            }
        }
        return $this->renderWithData('setPassword', ['model' => $model, 'assessments' => $assessment]);
    }

    public function actionAddNewCourse()
    {
        $this->guestUserHandler();
        $model = new CourseSettingForm();

        if ($model->load(Yii::$app->request->post())) {
            $isSuccess = false;
            $bodyParams = $this->getBodyParams();
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
                    $model = new CourseSettingForm();
                    $isSuccess = true;
                }
            }
            if (!$isSuccess) {
                $this->setErrorFlash(AppConstant::SOMETHING_WENT_WRONG);
            }
        }
        $this->includeCSS(["../css/courseSetting.css"]);
        $this->includeJS(["../js/courseSetting.js"]);
        return $this->renderWithData('addNewCourse', ['model' => $model]);
    }

    public function actionCourseSetting()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $user = $this->getAuthenticatedUser();
        $course = Course::getById($cid);
        if ($course) {
            $model = new CourseSettingForm();

            if ($model->load(Yii::$app->request->post())) {
                $bodyParams = $this->getBodyParams();
                $params = $bodyParams['CourseSettingForm'];
                $courseSetting['name'] = $params['courseName'];
                $courseSetting['enrollkey'] = $params['enrollmentKey'];
                $availables = $this->getSanitizedValue($params['available'], AppConstant::AVAILABLE_NOT_CHECKED_VALUE);
                $courseSetting['available'] = AppUtility::makeAvailable($availables);
                $courseSetting['copyrights'] = $params['copyCourse'];
                $courseSetting['msgset'] = $params['messageSystem'];
                $toolsets = $this->getSanitizedValue($params['navigationLink'], AppConstant::NAVIGATION_NOT_CHECKED_VALUE);
                $courseSetting['toolset'] = AppUtility::makeToolset($toolsets);
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
            $this->includeCSS(["../css/courseSetting.css"]);
            return $this->renderWithData('courseSetting', ['model' => $model, 'course' => $course, 'selectionList' => $selectionList]);
            }

        }
            return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));

    }

    public function actionDeleteCourse()
    {
        $model = new DeleteCourseForm();
        $cid = Yii::$app->request->get('cid');
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
        $cid = Yii::$app->request->get('cid');
        $sortBy = 'FirstName';
        $order = AppConstant::ASCENDING;
        $users = User::findAllUsers($sortBy, $order);
        $course = Course::getById($cid);
        $this->includeCSS(['../css/dashboard.css']);
        return $this->renderWithData('transferCourse', array('users' => $users, 'course' => $course));
    }

    public function actionUpdateOwner()
    {
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();

            if (Yii::$app->user->identity->rights == 75) // 75 is instructor right
            {

            } elseif (Yii::$app->user->identity->rights > 75) {
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
            } elseif (Yii::$app->user->identity->rights > 40) {
                if ($params['oldOwner'] == Yii::$app->user->identity->id) {
                    $course = Course::getByIdandOwnerId($params['cid'], $params['oldOwner']);
                    if ($course) {
                        $course->ownerid = $params['newOwner'];
                        $course->save();
                    }
                }
            }
            return $this->getReturnableResponse(0);
        }
    }

    public function actionAddRemoveCourse()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        return $this->renderWithData('addRemoveCourse', ['cid' => $cid]);
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
        return $this->getReturnableResponse(0, array('teachers' => $teacherList, 'nonTeachers' => $nonTeacher));
    }

    public function actionAddTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();

            $teacher = new Teacher();

            if ($params['userId'] != null && $params['cid'] != null) {
                $teacher->create($params['userId'], $params['cid']);
            }

            return $this->getReturnableResponse(0);
        }

    }

    public function actionRemoveTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();

            $teacher = new Teacher();

            if ($params['userId'] != null && $params['cid'] != null) {
                $teacher->removeTeacher($params['userId'], $params['cid']);
            }
            return $this->getReturnableResponse(0);
        }
    }

    public function actionAddAllAsTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $cid = $params['cid'];
            $usersId = json_decode($params['usersId']);

            for ($i = 0; $i < count($usersId); $i++) {
                $teacher = new Teacher();
                $teacher->create($usersId[$i], $cid);
            }

            return $this->getReturnableResponse(0);
        }
    }

    public function actionRemoveAllAsTeacherAjax()
    {
        if ($this->isPostMethod()) {
            $params = $this->getBodyParams();
            $cid = $params['cid'];
            $usersId = json_decode($params['usersId']);

            for ($i = 0; $i < count($usersId); $i++) {
                $teacher = new Teacher();
                $teacher->removeTeacher($usersId[$i], $cid);
            }

            return $this->getReturnableResponse(0);
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
}