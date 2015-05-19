<?php
namespace app\controllers\roster;
use app\models\Course;
use app\models\LoginGrid;
use app\models\loginTime;
use app\models\Student;
use app\models\User;
use Yii;
use app\components\AppUtility;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use yii\db\Query;


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

        $newStartDate = strtotime($pramas['newStartDate']);
        $newEndDate = strtotime($pramas['newEndDate']);
        $this->guestUserHandler();
        $headsArray = array();
        $headsArray[] = 'Name';
        for($curDate = $newStartDate; $curDate<= $newEndDate;  ($curDate = $curDate+86400)){
            $day = date('m/d', $curDate );
            $headsArray[] = $day;
        }
        $cid = $pramas['cid'];

        $loginLogs = LoginGrid::getById($cid, $newStartDate, $newEndDate);
        $rowLogs = array();
        $nameHash = array();
        foreach($loginLogs as $loginLog){
            $day = date('m/d', $loginLog['logintime']);
            $user_id = $loginLog['userid'];
            if(!isset($rowLogs[$user_id])){
                $rowLogs[$user_id] = array();
            }
            $userSpecificDaysArray = $rowLogs[$user_id];
            if(!isset($userSpecificDaysArray[$day])){
                $userSpecificDaysArray[$day] = 1;
            }else{
                $userSpecificDaysArray[$day] = $userSpecificDaysArray[$day] + 1;;
            }
            if(!isset($nameHash[$user_id])){
                $nameHash[$user_id] = $loginLog['LastName']. ', ' . $loginLog['FirstName'];
            }
            $rowLogs[$user_id] = $userSpecificDaysArray;
        }

        foreach($headsArray as $headElem){
            foreach($rowLogs as $key => $field){
                if($headElem == 'Name'){
                    continue;
                }
                if(!isset($field[$headElem])){
                    $field[$headElem] = '';
                    $rowLogs[$key] = $field;
                }
            }
        }
        $stuLogs = array();
        foreach($rowLogs as $key => $field){
            $stuLogs[$key]['name'] = $nameHash[$key];
            $stuLogs[$key]['row'] = $field;
        }

        $retJSON = new \stdClass();
        $retJSON->header = $headsArray;
        $retJSON->rows = $stuLogs;

        $test = array('status' => '0' , 'data' => $retJSON);
        return json_encode($test);
    }

    public function actionStudentRosterAjax()
    {
        $params = $this->getBodyParams();
          $id = $params['course_id'];
        $query = Yii::$app->db->createCommand("select * from imas_users")->queryAll();
        return json_encode(['status' => '0','query' => $query]);
    }
}