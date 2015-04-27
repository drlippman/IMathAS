<?php

namespace app\controllers\course;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Course;
use app\models\Assessments;
use app\models\forms\CourseSettingForm;
use Yii;
use app\controllers\AppController;


class CourseController extends AppController
{
    public function actionIndex()
    {
        if(!$this->isGuestUser())
        {
            $cid = Yii::$app->request->get('cid');

            $assessment = Assessments::getById($cid);
            //AppUtility::dump($assessment);




            return $this->render('index', ['assessment' => $assessment]);
        }else{
            return $this->redirect(Yii::$app->homeUrl.'site/login');
        }
    }

    public function actionCourseSetting()
    {
        if(!$this->isGuestUser())
        {
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
            $this->includeJS(["js/courseSetting.js"]);
            return $this->render('courseSetting', ['model' => $model]);
        }else{
            return $this->redirect('login');
        }
    }

}