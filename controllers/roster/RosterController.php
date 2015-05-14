<?php
namespace app\controllers\roster;
use app\models\Course;
use app\models\LoginGrid;
use app\models\loginTime;
use Yii;
use app\components\AppUtility;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;


class RosterController extends AppController
{

    public function actionStudentRoster()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        return $this->render('studentRoster',['course' => $course]);

    }

    public function actionLoginGridView()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        return $this->render('loginGridView',['course' => $course]);

    }

    public function actionLoginGridViewAjax()
    {
        $pramas = $this->getBodyParams();

        $cid = $pramas['cid'];
        $newStartDate = strtotime($pramas['newStartDate']);
        $NewEndDate = strtotime($pramas['newEndDate']);
        $this->guestUserHandler();

        $loginLogs = LoginGrid::getById($cid, $newStartDate, $NewEndDate);
        $loginLogArray =array();
        foreach($loginLogs as $loginLog)
        {
            $loginArray = array(
                "id" => $loginLog->id,
                "userId" => $loginLog->userid,
                "courseId" => $loginLog->courseid,
                "loginTime" => $loginLog->logintime,
                "lastAction" => $loginLog->lastaction
            );
            array_push($loginLogArray, $loginArray);
        }
        $test = array('status' => '0' , 'loginLog' => $loginLogArray, 'startDate' => $newStartDate,'endDate' => $NewEndDate);
        return json_encode($test);
    }

}