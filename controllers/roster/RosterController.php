<?php
namespace app\controllers\roster;
use app\models\Course;
use app\models\forms\CreateAndEnrollNewStudentForm;
use app\models\forms\EnrollFromOtherCourseForm;
use app\models\forms\EnrollStudentsForm;
use app\models\forms\AssignSectionAndCodesForm;
use app\models\forms\ManageTutorsForm;
use app\models\forms\StudentEnrollCourseForm;
use app\models\forms\StudentEnrollmentForm;
use app\models\LoginGrid;
use app\models\loginTime;
use app\models\Student;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use Seld\JsonLint\JsonParser;
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
       // return $this->render('studentRoster', ['course' => $course]);

        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');

        $students = Student::findByCid($cid);

        $studArray = array();
        $isCode = false;
        $isSection= false;
        foreach ($students as $stud)
        {
//            && !empty($stud->code)
            if($stud->code != '' )
            {
                $isCode = true;
            }
            if($stud->section != '')
            {
                $isSection = true;
            }
            $tempArray = array(
                'Section' => $stud->section,
                'code' => $stud->code,
            );

            array_push($studArray, $tempArray);

        }
        return $this->render('studentRoster', ['studentInformation' => $studArray,'course' => $course,'isSection' => $isSection,'isCode' => $isCode]);

    }

    public function actionLoginGridView()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);

        return $this->render('loginGridView', ['course' => $course]);

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
        for ($curDate = $newStartDate; $curDate <= $newEndDate; ($curDate = $curDate + 86400)) {
            $day = date('m/d', $curDate);
            $headsArray[] = $day;
        }
        $rowLogs = array();
        $nameHash = array();
        foreach ($loginLogs as $loginLog) {
            $day = date('m/d', $loginLog['logintime']);
            $user_id = $loginLog['userid'];
            if (!isset($rowLogs[$user_id])) {
                $rowLogs[$user_id] = array();
            }
            $userSpecificDaysArray = $rowLogs[$user_id];
            if (!isset($userSpecificDaysArray[$day])) {
                $userSpecificDaysArray[$day] = 1;
            } else {
                $userSpecificDaysArray[$day] = $userSpecificDaysArray[$day] + 1;;
            }
            if (!isset($nameHash[$user_id])) {
                $nameHash[$user_id] = $loginLog['LastName'] . ', ' . $loginLog['FirstName'];
            }
            $rowLogs[$user_id] = $userSpecificDaysArray;
        }

        foreach ($headsArray as $headElem) {
            foreach ($rowLogs as $key => $field) {
                if ($headElem == 'Name') {
                    continue;
                }
                if (!isset($field[$headElem])) {
                    $field[$headElem] = '';
                    $rowLogs[$key] = $field;
                }
            }
        }
        $stuLogs = array();
        foreach ($rowLogs as $key => $field) {
            $stuLogs[$key]['name'] = $nameHash[$key];
            $stuLogs[$key]['row'] = $field;
        }

        $retJSON = new \stdClass();
        $retJSON->header = $headsArray;
        $retJSON->rows = $stuLogs;

        $test = array('status' => '0', 'data' => $retJSON);
        return json_encode($test);
    }

    public function actionStudentRosterAjax()
    {
        $params = $this->getBodyParams();
        $cid = $params['course_id'];

        $Students = Student::findByCid($cid);
        $isCode = false;
        $isSection= false;
        $studentArray = array();
        foreach ($Students as $stud) {

            if($stud->code != '' )
            {
                $isCode = true;
            }
            if($stud->section != '')
            {
                $isSection = true;
            }
            $tempArray = array('lastname' => $stud->user->LastName,
                'firstname' => $stud->user->FirstName,
                'email' => $stud->user->email,
                'username' => $stud->user->SID,
                'lastaccess' => $stud->user->lastaccess,
                'section' => $stud->section,
                'code' => $stud->code,

            );
            array_push($studentArray, $tempArray);

        }
        return json_encode(['status' => '0', 'query' => $studentArray,'isCode'=>$isCode,'isSection'=>$isSection]);
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
            if (!$uid) {
                $this->setErrorFlash('Student not found please enter correct username.');
            } else {
                $stdrecord = Student::getByUserIdentity($uid->id);

                $teacher = Teacher::getTeacherByUserId($uid->id);

                if ($teacher) {
                    $this->setErrorFlash('Teachers can\'t be enrolled as students - use Student View, or create a separate student account.');
                } elseif (!$stdrecord) {

                    $student = new Student();
                    $student->createNewStudent($uid->id, $cid, $param);
                    $this->setSuccessFlash('Student have been enrolled in course ' . $course->name . ' successfully');
                    $model = new StudentEnrollmentForm();
                } else {
                    $this->setErrorFlash('This username is already enrolled in the class.');
                }
            }


        }

        return $this->render('studentEnrollment', ['course' => $course, 'model' => $model]);

    }


//    public function actionAssignSectionsAndCodes()
//    {
//        $this->guestUserHandler();
//        $cid = Yii::$app->request->get('cid');
//        $course = Course::getById($cid);
//
//
//
//    }

    public function actionAssignSectionsAndCodes()
   {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $query = Student::findByCid($cid);

        $studentArray = array();
        foreach ($query as $abc) {
            $tempArray = array('Name' => $abc->user->FirstName . ' ' . $abc->user->LastName,
                'code' => $abc->code,
                'section' => $abc->section,
                'userid' => $abc->userid
            );
            array_push($studentArray, $tempArray);
        }
        $student = array();
        if($this->isPost())
        {
            $paramas = $_POST;
            foreach($paramas['section'] as $key => $section)
            {
                $code = ($paramas['code'][$key]);
                Student::updateSectionAndCodeValue($section,$key,$code,$cid);
            }
            $this->redirect('student-roster?cid='.$cid);
        }
        return $this->render('assignSectionsAndCodes', ['studentInformation' => $studentArray, 'cid' => $cid]);
    }

    public function actionManageLatePasses()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');

        $model = Student::findByCid($cid);

        $studentArray = array();

        foreach ($model as $abc)
        {
            $tempArray = array('Name' => $abc->user->FirstName.' '.$abc->user->LastName,
                'Section' => $abc->section,
                'Latepass' => $abc->latepass,
                'StudenId' => $abc->id,
                'latePassHrs' => $abc->course->latepasshrs
            );
            array_push($studentArray, $tempArray);

//            if(Yii::$app->request->isPost)
//            {
//                AppUtility::dump($_REQUEST);
//            }

        }
        return $this->render('manageLatePasses', ['studentInformation' => $studentArray]);

    }


    public function actionEnrollFromOtherCourse()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $model = new EnrollFromOtherCourseForm();
        $course = Course::getById($cid);
        $teacherId=Yii::$app->user->identity->getId();
        $list=Teacher::getTeacherByUserId($teacherId);
        $courseDetails=array();

        foreach($list as $teacher)
        {
            $tempArray = array("id" => $teacher->course->id,
            "name" => $teacher->course->name);
            array_push($courseDetails,$tempArray);
        }

        if($this->isPost())
        {
           $params = $this->getBodyParams();
            $courseId = isset($params['name']) ? $params['name'] : null;
            if($courseId)
            {
                $this->redirect('enroll-students?cid='.$cid.'&course='.$courseId);
            }else{
                $this->setErrorFlash("Select course from list to choose students");
            }
        }
        return $this->render('enrollFromOtherCourse',['course' => $course,'data'=>$courseDetails, 'model'=>$model]);
    }

    public function actionEnrollStudents(){

        $this->guestUserHandler();
        $courseid = Yii::$app->request->get('course');
        $cid = Yii::$app->request->get('cid');
        $model=new EnrollStudentsForm();
        $course = Course::getById($courseid);
        $query=Student::findByCid($courseid);
        $studentDetails=array();

        foreach($query as $student){
            $tempArray=array();
            $tempArray = array("id" => $student->user->id,
                "firstName" => $student->user->FirstName,
                "lastName"=> $student->user->LastName);
            array_push($studentDetails,$tempArray);

        }
        if($this->isPost())
        {
            $params=$this->getBodyParams();
            $record=array();
            $count=0;
            foreach($params as $result){
              array_push($record,$result);
                $count++;
            }
           if($count!=3)
           {
             $storedArray=array();

            foreach($record[1] as $entry){
                $studentList=array("id"=>$entry,"courseId"=>$cid,"section"=>$record[2]['section']);
                array_push($storedArray,$studentList);

            }

            foreach($storedArray as $studentData){
                $studentRecord=Student::getByCourseId($studentData['courseId'],$studentData['id']);

                if(!$studentRecord){
                    $student = new Student();
                    $student->insertNewStudent($studentData['id'], $studentData['courseId'], $studentData['section']);
                    $this->redirect('student-roster?cid='.$cid);
                }
            }
        }else{
               $this->setErrorFlash('Select student from list to enroll in a course');
           }
        }
        return $this->render('enrollStudents',['course' => $course,'data'=>$studentDetails,'model'=> $model,'cid'=> $cid]);
    }

    public function actionCreateAndEnrollNewStudent()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        $model= new CreateAndEnrollNewStudentForm();
        if($this->isPost()){
            $params=$this->getBodyParams();
//            AppUtility::dump($params);
            $record=array();
            foreach($params as $result){
                array_push($record,$result);
            }
            $findUser=User::findByUsername($record[1]['username']);
            if(!$findUser) {
                    $user = new User();
                    $user -> createAndEnrollNewStudent($record[1]);
                    $studentid =User::findByUsername($record[1]['username']);
                    $newStudent=new Student();
                    $newStudent->createNewStudent($studentid['id'], $cid, $record[1]);
                $this->setSuccessFlash('Student have been created and enrolled in course ' . $course->name . ' successfully');

            }else{
                $this->setErrorFlash('Username already exists');
            }
        }

        return $this->renderWithData('createAndEnrollNewStudent', ['course' => $course, 'model'=>$model]);

    }
    public function actionManageTutors()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $tutors=Tutor::getByCourseId($cid);
        $course = Course::getById($cid);
        $model= new ManageTutorsForm();


        return $this->renderWithData('manageTutors', ['course' => $course, 'model'=>$model]);

    }


}

