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
use kartik\base\AnimateAsset;
use Seld\JsonLint\JsonParser;
use Yii;
use app\components\AppUtility;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use yii\db\Query;

use app\models\forms\ImportStudentForm;
use yii\web\UploadedFile;
use app\components\AppConstant;


class RosterController extends AppController
{
    public function actionStudentRoster()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        $students = Student::findByCid($cid);
        $isCode = false;
        $isSection = false;
        foreach ($students as $student) {
            if ($student->code != '') {
                $isCode = true;
            }
            if ($student->section != '') {
                $isSection = true;
            }
        }
        $this->includeCSS(['../css/jquery-ui.css', '../css/dataTables-jqueryui.css']);
        $this->includeJS(['../js/studentroster.js','../js/general.js']);
        return $this->render('studentRoster', ['course' => $course, 'isSection' => $isSection, 'isCode' => $isCode]);
    }

    public function actionLoginGridView()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        $this->includeCSS(['../css/jquery-ui.css']);
        $this->includeJS(['../js/logingridview.js','../js/general.js']);
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
        $isSection = false;
        $studentArray = array();
        foreach ($Students as $student) {

            if ($student->code != '') {
                $isCode = true;
            }
            if ($student->section != '') {
                $isSection = true;
            }
            $tempArray = array('lastname' => $student->user->LastName,
                'firstname' => $student->user->FirstName,
                'email' => $student->user->email,
                'username' => $student->user->SID,
                'lastaccess' => $student->user->lastaccess,
                'section' => $student->section,
                'code' => $student->code,
            );
            array_push($studentArray, $tempArray);
        }
        return json_encode(['status' => '0', 'query' => $studentArray, 'isCode' => $isCode, 'isSection' => $isSection]);
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

    public function actionAssignSectionsAndCodes()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $query = Student::findByCid($cid);
        $studentArray = array();
        foreach ($query as $student) {
            $tempArray = array('Name' => $student->user->FirstName . ' ' . $student->user->LastName,
                'code' => $student->code,
                'section' => $student->section,
                'userid' => $student->userid
            );
            array_push($studentArray, $tempArray);
        }
        $student = array();
        if ($this->isPost()) {
            $paramas = $_POST;
            foreach ($paramas['section'] as $key => $section) {
                $code = ($paramas['code'][$key]);
                Student::updateSectionAndCodeValue($section, $key, $code, $cid);
            }
            $this->redirect('student-roster?cid=' . $cid);
        }
        return $this->render('assignSectionsAndCodes', ['studentInformation' => $studentArray, 'cid' => $cid]);
    }

    public function actionManageLatePasses()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $model = Student::findByCid($cid);
        $studentArray = array();
        foreach ($model as $student) {
            $tempArray = array('Name' => $student->user->FirstName . ' ' . $student->user->LastName,
                'Section' => $student->section,
                'Latepass' => $student->latepass,
                'StudenId' => $student->id,
                'latePassHrs' => $student->course->latepasshrs,
                'userid' => $student->userid
            );
            array_push($studentArray, $tempArray);
            $student = array();
            if ($this->isPost()) {
                $paramas = $_POST;
                foreach ($paramas['code'] as $key => $latepass) {
                    $latepasshours = $paramas['passhours'];
                    Student::updateLatepasses($latepass, $key, $cid);
                }
                Course::updatePassHours($latepasshours, $cid);
                $this->redirect('student-roster?cid=' . $cid);
            }
        }
        return $this->render('manageLatePasses', ['studentInformation' => $studentArray]);
    }

    public function actionEnrollFromOtherCourse()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $model = new EnrollFromOtherCourseForm();
        $course = Course::getById($cid);
        $teacherId = Yii::$app->user->identity->getId();
        $list = Teacher::getTeacherByUserId($teacherId);
        $courseDetails = array();
        foreach ($list as $teacher) {
            $tempArray = array("id" => $teacher->course->id,
                "name" => $teacher->course->name);
            array_push($courseDetails, $tempArray);
        }
        if ($this->isPost()) {
            $params = $this->getBodyParams();
            $courseId = isset($params['name']) ? $params['name'] : null;
            if ($courseId) {
                $this->redirect('enroll-students?cid=' . $cid . '&course=' . $courseId);
            } else {
                $this->setErrorFlash("Select course from list to choose students");
            }
        }
        return $this->render('enrollFromOtherCourse', ['course' => $course, 'data' => $courseDetails, 'model' => $model]);
    }

    public function actionEnrollStudents()
    {
        $this->guestUserHandler();
        $courseid = Yii::$app->request->get('course');
        $cid = Yii::$app->request->get('cid');
        $model = new EnrollStudentsForm();
        $course = Course::getById($courseid);
        $query = Student::findByCid($courseid);
        $studentDetails = array();
        foreach ($query as $student) {
            $tempArray = array();
            $tempArray = array("id" => $student->user->id,
                "firstName" => $student->user->FirstName,
                "lastName" => $student->user->LastName);
            array_push($studentDetails, $tempArray);
        }
        if ($this->isPost()) {
            $params = $this->getBodyParams();
            $record = array();
            $count = 0;
            foreach ($params as $result) {
                array_push($record, $result);
                $count++;
            }
            if ($count != 3) {
                $storedArray = array();

                foreach ($record[1] as $entry) {
                    $studentList = array("id" => $entry, "courseId" => $cid, "section" => $record[2]['section']);
                    array_push($storedArray, $studentList);
                }
                foreach ($storedArray as $studentData) {
                    $studentRecord = Student::getByCourseId($studentData['courseId'], $studentData['id']);
                    if (!$studentRecord) {
                        $student = new Student();
                        $student->insertNewStudent($studentData['id'], $studentData['courseId'], $studentData['section']);


                    }
                }
            } else {
                $this->setErrorFlash('Select student from list to enroll in a course');
            }
        }
        return $this->render('enrollStudents', ['course' => $course, 'data' => $studentDetails, 'model' => $model, 'cid' => $cid]);
    }

    public function actionCreateAndEnrollNewStudent()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $course = Course::getById($cid);
        $model = new CreateAndEnrollNewStudentForm();
        if ($this->isPost()) {
            $params = $this->getBodyParams();
            $record = array();
            foreach ($params as $result) {
                array_push($record, $result);
            }
            $findUser = User::findByUsername($record[1]['username']);
            if (!$findUser) {
                $user = new User();
                $user->createAndEnrollNewStudent($record[1]);
                $studentid = User::findByUsername($record[1]['username']);
                $newStudent = new Student();
                $newStudent->createNewStudent($studentid['id'], $cid, $record[1]);
                $this->setSuccessFlash('Student have been created and enrolled in course ' . $course->name . ' successfully');

            } else {
                $this->setErrorFlash('Username already exists');
            }
        }
        return $this->renderWithData('createAndEnrollNewStudent', ['course' => $course, 'model' => $model]);
    }

    public function actionManageTutors()
    {
        $this->guestUserHandler();
        $cid = Yii::$app->request->get('cid');
        $tutors = Tutor::getByCourseId($cid);
        $tutorId = array();
        $studentInfo = array();
        $sortBy = 'section';
        $order = AppConstant::ASCENDING;
        foreach ($tutors as $tutor) {
            $tempArray = array('Name' => $tutor->user->FirstName . ' ' . $tutor->user->LastName, 'id' => $tutor->user->id);
            array_push($studentInfo, $tempArray);
        }
        $sections = Student::findByCourseId($cid, $sortBy, $order);
        $sectionArray = array();
        foreach ($sections as $section) {
            array_push($sectionArray, $section->section);
        }
        return $this->renderWithData('manageTutors', ['courseid' => $cid, 'student' => $studentInfo, 'section' => $sectionArray]);
    }

    public function actionMarkUpdateAjax()
    {
        $this->guestUserHandler();

        $params = $this->getBodyParams();
        $users = explode(',', $params['username']);
        $cid = Yii::$app->request->get('cid');
        AppUtility::dump("hiii");
        $userIdArray = array();
        $userNotFoundArray = array();
        $teacherIdArray = array();
        $studentArray = array();
        foreach ($users as $entry) {
            $userId = User::findByUsername($entry);
            if (!$userId) {
                array_push($userNotFoundArray, $entry);

            } else {
                array_push($userIdArray, $userId->id);
                $isTeacher = Teacher::getUniqueByUserId($userId->id);
                if ($isTeacher) {
                    $tutors = Tutor::getByUserId($isTeacher->userid, $cid);
                } else {
                    array_push($studentArray, $userId->id);
                }
            }
        }
        return json_encode(array('status' => 0));

    }

    public function actionImportStudent()
    {
        $user = $this->getAuthenticatedUser();
        $model = new ImportStudentForm();
        $nowTime = time();
        $courseId = Yii::$app->request->get('cid');

        if ($model->load(Yii::$app->request->post())) {
            $params = $this->getRequestParams();
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $filename = AppConstant::UPLOAD_DIRECTORY . $nowTime . '.csv';
                $model->file->saveAs($filename);
            }
            $studentRecords = $this->ImportStudentCsv($filename, $courseId,$params);
            $this->setSuccessFlash('Imported student successfully.');
        }
        return $this->render('importStudent', ['model' => $model]);
    }

    public function ImportStudentCsv($fileName, $courseId, $params){
        $course = Course::getById($courseId);
        $AllUserArray = array();
        if($course)
        {
            $handle = fopen($fileName,'r');
            if ($params['ImportStudentForm']['headerRow']==1) {
                $data = fgetcsv($handle,2096);
            }

            while (($data = fgetcsv($handle,2096))!==false) {
                $StudentDataArray = $this->parsecsv($data, $params);

                for ($i=0;$i<count($StudentDataArray);$i++) {
                    $StudentDataArray[$i] = trim($StudentDataArray[$i]);
                }

                if (trim($StudentDataArray[0])=='' || trim($StudentDataArray[0])=='_') {
                    continue;
                }

                $userData = User::getByName($StudentDataArray[0]);

                if ($userData) {
                    $userExist = User::userAlreadyExist($StudentDataArray[0]);
                } else {
                    if (($params['ImportStudentForm']['setPassword']==0 || $params['ImportStudentForm']['setPassword']==1) && strlen($StudentDataArray[0])<4) {

                        $password = password_hash($StudentDataArray[0], PASSWORD_DEFAULT);

                    } else {
                        if ($params['ImportStudentForm']['setPassword'] == 0) {
                            $password = password_hash(substr($StudentDataArray[0],0,4), PASSWORD_DEFAULT);
                        } else if ($params['ImportStudentForm']['setPassword']==1) {

                            $password = password_hash(substr($StudentDataArray[0],-4), PASSWORD_DEFAULT);

                        } else if ($params['ImportStudentForm']['setPassword'] == 2) {
                            $password = password_hash($_POST['defpw'], PASSWORD_DEFAULT);

                        } else if ($params['ImportStudentForm']['setPassword']==3) {
                            if (trim($StudentDataArray[6])=='') {
                                echo "Password for {$StudentDataArray[0]} is blank; skipping import<br/>";
                                continue;
                            }

                            $password = password_hash($StudentDataArray[6], PASSWORD_DEFAULT);
                        }
                    }
                    array_push($AllUserArray,$StudentDataArray);
//                    $user = new User();
//                    $user->createUserFromCsv($StudentDataArray, AppConstant::STUDENT_RIGHT, $password);
                }
            }
            return $AllUserArray;
        }
        return false;
    }

   public function parsecsv($data, $params) {
        $firstname = $data[$params['ImportStudentForm']['firstName']-1];
        if ($params['ImportStudentForm']['nameFirstColumn']!=0) {
            $firstnameColumn = explode(' ',$firstname);
            if ($params['ImportStudentForm']['nameFirstColumn']<3) {
                $firstname = $firstnameColumn[$params['ImportStudentForm']['nameFirstColumn']-1];
            } else {
                $firstname = $firstnameColumn[count($firstnameColumn)-1];
            }
        }
       $lastname = $data[$params['ImportStudentForm']['lastName']-1];
        if ($params['ImportStudentForm']['lastName']!=$params['ImportStudentForm']['firstName'] && $params['ImportStudentForm']['nameLastColumn']!=0) {
            $lastnameColumn = explode(' ',$lastname);
        }
        if ($params['ImportStudentForm']['nameLastColumn']!=0) {
            if ($params['ImportStudentForm']['nameLastColumn']<3) {
                $lastname = $lastnameColumn[$params['ImportStudentForm']['nameLastColumn']-1];
            } else {
                $lastname = $lastnameColumn[count($lastnameColumn)-1];
            }
        }
       $firstname = preg_replace('/\W/','',$firstname);
       $lastname = preg_replace('/\W/','',$lastname);
       $firstname = ucfirst(strtolower($firstname));
       $lastname = ucfirst(strtolower($lastname));
        if ($params['ImportStudentForm']['userName']==0) {
            $username = strtolower($firstname.'_'.$lastname);
        } else {
            $username = $data[$_POST['unloc']-1];
            $username = preg_replace('/\W/','',$username);
        }
        if ($params['ImportStudentForm']['emailAddress'] > 0) {
            $email = $data[$params['ImportStudentForm']['emailAddress'] - 1];
            if ($email == '') {
                $email = 'none@none.com';
            }
        } else {
            $email = 'none@none.com';
        }
        if ($params['ImportStudentForm']['codeNumber'] == 1) {
            $code = $data[$_POST['code'] - 1];
        } else {
            $code = 0;
        }
        if ($params['ImportStudentForm']['sectionValue'] == 1) {
            $sec = $_POST['secval'];
        } else if ($params['ImportStudentForm']['sectionValue'] == 2) {
            $sec = $data[$_POST['seccol'] - 1];

        if ($params['ImportStudentForm']['sectionValue']==1) {
            $section = $_POST['secval'];
        } else if ($params['ImportStudentForm']['sectionValue']==2) {
            $section = $data[$_POST['seccol']-1];
        } else {
            $section = 0;
        }

        if ($params['ImportStudentForm']['setPassword']==3) {
            $password = $data[$_POST['pwcol']-1];
        } else {
            $password = 0;
        }
        return array($username,$firstname,$lastname,$email,$code,$section,$password);

    }
}
}
