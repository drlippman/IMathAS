<?php

namespace app\controllers\course;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\AppModel;
use app\models\Course;
use app\models\Assessments;
use app\models\forms\CourseSettingForm;
use app\models\forms\Links;
use app\models\Forums;
use app\models\Teacher;
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
        $cid = Yii::$app->request->get('cid');
        $assessment = Assessments::getById($cid);
        $course = Course::getById($cid);
        $forum = Forums::getByCourseId($cid);
        $wiki = Wiki::getByCourseId($cid);
        $link = Links::getByCourseId($cid);
        //AppUtility::dump($link);

        return $this->render('index', ['assessments' => $assessment, 'course' => $course, 'forums' => $forum, 'wiki' => $wiki, 'links' => $link]);
    }


    public function actionAddNewCourse()
    {
        $this->guestUserHandler();
        $model = new CourseSettingForm();

        if ($model->load(Yii::$app->request->post()))
        {
            $bodyParams = $this->getBodyParams();
            $user = $this->getAuthenticatedUser();

            $params = $bodyParams['CourseSettingForm'];
            $course['ownerid'] = $user->id;
            $course['name'] = $params['courseName'];
            $course['enrollkey'] = $params['enrollmentKey'];
            $availables = isset($params['available']) ? $params['available'] : AppConstant::AVAILABLE_NOT_CHECKED_VALUE;
            $course['available'] = AppUtility::makeAvailable($availables);
            $course['picicons'] = AppConstant::PIC_ICONS_VALUE;
            $course['allowunenroll'] = AppConstant::UNENROLL_VALUE;
            $course['copyrights'] = $params['copyCourse'];
            $course['msgset'] = $params['messageSystem'];
            $toolsets = isset($params['navigationLink']) ? $params['navigationLink'] : AppConstant::NAVIGATION_NOT_CHECKED_VALUE;
            $course['toolset']  = AppUtility::makeToolset($toolsets);
            $course['cploc']= AppConstant::CPLOC_VALUE;
            $course['deflatepass']= $params['latePasses'];
            $course['showlatepass']= AppConstant::SHOWLATEPASS;
            $course['theme']= $params['theme'];
            $course['deftime'] = AppUtility::calculateTimeDefference($bodyParams['start_time'],$bodyParams['end_time']);
            $course['end_time'] = $bodyParams['end_time'];
            $course['chatset'] = AppConstant::CHATSET_VALUE;
            $course['topbar'] = AppConstant::TOPBAR_VALUE;
            $course['hideicons'] = AppConstant::HIDE_ICONS_VALUE;
            $courseSetting = new Course();
            $course = AppUtility::removeEmptyAttributes($course);
            $courseSetting->attributes = $course;
            $courseSetting->save();
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
        if($course)
        {
            $model = new CourseSettingForm();

            if ($model->load(Yii::$app->request->post()))
            {
                $bodyParams = $this->getBodyParams();
                $params = $bodyParams['CourseSettingForm'];
                $courseSetting['name'] = $params['courseName'];
                $courseSetting['enrollkey'] = $params['enrollmentKey'];
                $availables = isset($params['available']) ? $params['available'] : AppConstant::AVAILABLE_NOT_CHECKED_VALUE;
                $courseSetting['available'] = AppUtility::makeAvailable($availables);
                $courseSetting['copyrights'] = $params['copyCourse'];
                $courseSetting['msgset'] = $params['messageSystem'];
                $toolsets = isset($params['navigationLink']) ? $params['navigationLink'] : AppConstant::NAVIGATION_NOT_CHECKED_VALUE;
                $courseSetting['toolset']  = AppUtility::makeToolset($toolsets);
                $courseSetting['deflatepass']= $params['latePasses'];
                $courseSetting['theme']= $params['theme'];
                $courseSetting['deftime'] = AppUtility::calculateTimeDefference($bodyParams['start_time'],$bodyParams['end_time']);
                $courseSetting['end_time'] = $bodyParams['end_time'];
                $courseSetting = AppUtility::removeEmptyAttributes($courseSetting);
                $course->attributes = $courseSetting;
                $course->save();
            }
            $selectionList = AppUtility::prepareSelectedItemOfCourseSetting($course);
            $this->includeCSS(["../css/courseSetting.css"]);
            return $this->renderWithData('courseSetting', ['model' => $model, 'course' => $course, 'selectionList' => $selectionList]);

        }else{
            return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
        }
    }

    public function actionDeleteCourse()
    {
        $model = new DeleteCourseForm();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        if($course)
        {

            $connection = Yii::$app->getDb();
            $transaction = $connection->beginTransaction();
            try {
                $connection->createCommand()->delete('imas_courses', 'id ='.$cid)->execute();
                $connection->createCommand()->delete('imas_assessments', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_badgesettings', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_calitems', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_content_track', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_diags', 'cid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_external_tools', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_drillassess', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_firstscores', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_forums', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_gbcats', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_gbitems', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_gbscheme', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_inlinetext', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_items', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_linkedtext', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_login_log', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_lti_courses', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_msgs', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_outcomes', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_students', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_stugroupset', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_teachers', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_tutors', 'courseid ='.$cid)->execute();
                $connection->createCommand()->delete('imas_wikis', 'courseid ='.$cid)->execute();
                //.... other SQL executions
                $transaction->commit();

                $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
            } catch (Exception $e) {
                $transaction->rollBack();
            }
        }

        $this->includeJS(["../js/dashboard.js"]);
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
        if (Yii::$app->request->post())
        {
            $params = $this->getBodyParams();


            if(Yii::$app->user->identity->rights == 75)
            {

            }

            elseif(Yii::$app->user->identity->rights > 75)
            {
                $course = Course::getByIdandOwnerId($params['cid'], $params['oldOwner']);
                if($course)
                {
                    $course->ownerid = $params['newOwner'];
                    $course->save();

                    $teacher = Teacher::getByUserId($params['oldOwner'],$params['cid']);
                    if($teacher)
                    {
                        $teacher->delete();
                    }

                    $newTeacher = new Teacher();
                    $newTeacher->create($params);
                }
            }
            elseif(Yii::$app->user->identity->rights > 40)
            {
                if($params['oldOwner'] == Yii::$app->user->identity->id)
                {
                    $course = Course::getByIdandOwnerId($params['cid'], $params['oldOwner']);
                    if($course)
                    {
                        $course->ownerid = $params['newOwner'];
                        $course->save();
                    }
                }
            }
        }

        return json_encode(array('status' => '0'));
    }
}