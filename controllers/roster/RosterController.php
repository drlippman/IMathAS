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
        //AppUtility::dump($pramas);
        $cid = $pramas['cid'];

        $newStartDate = strtotime($pramas['newStartDate']);
        $NewEndDate = strtotime($pramas['newEndDate']);
        $this->guestUserHandler();


        $loginLogs = LoginGrid::getById($cid, $newStartDate, $NewEndDate);
        $loginLogArray =array();
        $userId = 0;
        $tempArray = array();
        foreach($loginLogs as $key => $loginLog)
        {
            if($userId != $loginLog->userid)
            {
                $userId = $loginLog->userid;
                if($key != 0)
                {
                    array_push($loginLogArray, $tempArray);
                }
                $tempArray = array();
                array_push($tempArray,$loginLog->user->FirstName.' '.$loginLog->user->LastName);
            }

            $extraTime = (86400 - $NewEndDate % 86400);
            $cnt = 0;
            for($i = $newStartDate; $i < ($NewEndDate + $extraTime); $i += 86400)
            {
                $cnt++;
                if(($loginLog->logintime - $i) <= 86400)
                {
                    array_push($tempArray, 1);
                }else{
                    array_push($tempArray, 0);

                }
            }
        }

//        AppUtility::dump($loginLogArray);
        $test = array('status' => '0' , 'loginLog' => $loginLogArray, 'startDate' => $newStartDate,'endDate' => $NewEndDate);
        return json_encode($test);
    }

}