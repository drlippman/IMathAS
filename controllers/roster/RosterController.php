<?php
namespace app\controllers\roster;
use app\models\Course;
use app\models\forms\StudentEnrollCourseForm;
use app\models\forms\StudentEnrollmentForm;
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
        $this->guestUserHandler();

        $params = $this->getBodyParams();
        $cid = $params['cid'];

        $newStartDate = AppUtility::getTimeStampFromDate($params['newStartDate']);
        $newEndDate = AppUtility::getTimeStampFromDate($params['newEndDate']);

        $loginLogs = LoginGrid::getById($cid, $newStartDate, $newEndDate);
        $headsArray = array();
        $headsArray[] = 'Name';
        for($curDate = $newStartDate; $curDate<= $newEndDate;  ($curDate = $curDate+86400)){
            $day = date('m/d', $curDate );
            $headsArray[] = $day;
        }
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
          $cid = $params['course_id'];

        $query = Student::findByCid($cid);

        $studentArray = array();
        foreach ($query as $abc)
        {
            $tempArray = array('lastname' => $abc->user->LastName,
                'firstname' => $abc->user->FirstName,
                'email' => $abc->user->email,
                'username' => $abc->user->SID,
                'lastaccess' => $abc->user->lastaccess,

            );
            array_push($studentArray, $tempArray);

        }
        return json_encode(['status' => '0','query' => $studentArray]);
    }

    public function actionStudentEnrollment()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $model = new StudentEnrollmentForm();
        $course = Course::getById($cid);
        if ($model->load(\Yii::$app->request->post())) {
            $param = $this->getBodyParams();
            $param = $param['StudentEnrollmentForm'];
            $user = $this->getAuthenticatedUser();
            $uid = User::findByUsername($param['usernameToEnroll']);
            $stdreccord =Student::getByUserId($uid->id);
            if($stdreccord){

            }else {
                $this->setErrorFlash('Invalid combinatio.');
            }

            }
        return $this->render('studentEnrollment',['course' => $course, 'model'=>$model]);

    }

    public function actionAssignSectionsAndCodes()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);

        return $this->render('assignSectionsAndCodes',['course' => $course]);

    }
    public function actionAssignSectionsAndCodesAjax()
    {
        $params = $this->getBodyParams();
        $cid = $params['course_id'];
        $query = Student::findByCid($cid);

        $studentArray = array();
        foreach ($query as $abc)
        {
            $tempArray = array('Name' => $abc->user->FirstName.' '.$abc->user->LastName,
            'code' => $abc->code,
                'section' => $abc->section,
                'studenId'=> $abc->id
                );
            array_push($studentArray, $tempArray);

        }
      return json_encode(['status' => '0','studentinformation' => $studentArray]);
    }
    public function actionManageLatePasses()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');

        $query = Student::findByCid($cid);
        $studentArray = array();
        foreach ($query as $abc)
        {
            $tempArray = array('Name' => $abc->user->FirstName.' '.$abc->user->LastName,
                'Section' => $abc->section,
                'Latepass' => $abc->latepass,
                'StudenId'=> $abc->id,
                'latePassHrs' => $abc->course->latepasshrs
            );
            array_push($studentArray, $tempArray);

            if(Yii::$app->request->isPost)
            {
                AppUtility::dump($_REQUEST);
            }

        }
        return $this->render('manageLatePasses',['studentInformation' => $studentArray]);

    }
}