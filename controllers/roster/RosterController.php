<?php
namespace app\controllers\roster;

use app\models\Assessments;
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
//Controller method to display student information on student roster page.
    public function actionStudentRoster()
    {
        $this->guestUserHandler();
        $courseid = Yii::$app->request->get('cid');
        $course = Course::getById($courseid);
        $students = Student::findByCid($courseid);
        $isCodePresent = false;
        $isSectionPresent = false;
        foreach ($students as $student) {
            if ($student->code != '') {
                $isCodePresent = true;
            }
            if ($student->section != '') {
                $isSectionPresent = true;
            }
        }
        if ($this->isPost()) {
            $params = $this->getBodyParams();
        }
        $this->includeCSS(['../css/jquery-ui.css', '../css/dataTables-jqueryui.css']);
        $this->includeJS(['../js/roster/studentroster.js','../js/general.js']);
        return $this->render('studentRoster', ['course' => $course, 'isSection' => $isSectionPresent, 'isCode' => $isCodePresent]);
     }

    public function actionLoginGridView()
    {
        $this->guestUserHandler();
        $courseid = Yii::$app->request->get('cid');
        $course = Course::getById($courseid);
        $this->includeCSS(['../css/jquery-ui.css']);
        $this->includeJS(['../js/logingridview.js', '../js/general.js']);
        return $this->render('loginGridView', ['course' => $course]);
    }

    public function actionLoginGridViewAjax()
    {
        $this->guestUserHandler();
        $params = $this->getBodyParams();
        $courseid = $params['cid'];
        $newStartDate = AppUtility::getTimeStampFromDate($params['newStartDate']);
        $newEndDate = AppUtility::getTimeStampFromDate($params['newEndDate']);
        $loginLogs = LoginGrid::getById($courseid, $newStartDate, $newEndDate);
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
        $this->layout = false;
        $params = $this->getBodyParams();
        $courseid = $params['course_id'];
        $Students = Student::findByCid($courseid);
        $isCodePresent = false;
        $isSectionPresent = false;
        $studentArray = array();
        foreach ($Students as $student) {

            if ($student->code != '') {
                $isCodePresent = true;
            }
            if ($student->section != '') {
                $isSectionPresent = true;
            }
            $tempArray = array('id' => $student->user->id
            , 'lastname' => $student->user->LastName,
                'firstname' => $student->user->FirstName,
                'email' => $student->user->email,
                'username' => $student->user->SID,
                'lastaccess' => $student->lastaccess,
                'locked' =>$student->locked  ,
                'section' => $student->section,
                'code' => $student->code,
            );
            array_push($studentArray, $tempArray);
        }
        return json_encode(['status' => '0', 'query' => $studentArray, 'isCode' => $isCodePresent, 'isSection' => $isSectionPresent]);
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

                $teacher = Teacher::getTeacherByUserId($uid->id);
                if ($teacher) {
                    $this->setErrorFlash('Teachers can\'t be enrolled as students - use Student View, or create a separate student account.');
                } else {
                    $stdrecord = Student::getByUserIdentity($uid->id, $cid);
                    if ($stdrecord) {
                        $this->setErrorFlash('This username is already enrolled in the class');
                    } else {
                        $student = new Student();
                        $student->createNewStudent($uid->id, $cid, $param);
                        $this->setSuccessFlash('Student have been enrolled in course ' . $course->name . ' successfully');
                        $model = new StudentEnrollmentForm();
                    }
                }
            }
        }
        return $this->render('studentEnrollment', ['course' => $course, 'model' => $model]);
    }

    public function actionAssignSectionsAndCodes()
    {
        $this->guestUserHandler();
        $courseid = Yii::$app->request->get('cid');
        $query = Student::findByCid($courseid);
        $course = Course::getById($courseid);
        $studentArray = array();
        foreach ($query as $student) {
            $tempArray = array('Name' => $student->user->FirstName . ' ' . $student->user->LastName,
                'code' => $student->code,
                'section' => $student->section,
                'userid' => $student->userid
            );
            array_push($studentArray, $tempArray);
        }
        if ($this->isPost()) {
            $paramas = $_POST;
            foreach ($paramas['section'] as $key => $section) {
                $code = trim($paramas['code'][$key]);
                Student::updateSectionAndCodeValue(trim($section), $key, $code, $courseid);
            }
            $this->redirect('student-roster?cid=' . $courseid);
        }
        return $this->render('assignSectionsAndCodes', ['studentInformation' => $studentArray, 'cid' => $courseid,'course'=>$course]);
    }

    public function actionManageLatePasses()
    {
        $this->guestUserHandler();
        $courseid = Yii::$app->request->get('cid');
        $model = Student::findByCid($courseid);
        $course = Course::getById($courseid);
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
                if ($this->isPost()) {
                $paramas = $_POST;
                foreach ($paramas['code'] as $key => $latepass) {
                    $latepasshours = $paramas['passhours'];
                    Student::updateLatepasses(trim($latepass), $key, $courseid);
                }
                Course::updatePassHours($latepasshours, $courseid);
                $this->redirect('student-roster?cid=' . $courseid);
            }
        }
        $this->includeJS(['../js/managelatepasses.js']);
        return $this->render('manageLatePasses', ['studentInformation' => $studentArray,'course' => $course]);
    }

//Controller method to display the dynamic radio list of courses
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

//Controller method to dynamically create student list with checkbox and enroll students displayed in a list in current course.

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
                        $this->setSuccessFlash('Enrolled Successfully');
                    }

                }
            } else {
                $this->setErrorFlash('Select student from list to enroll in a course');
            }
        }
        $this->includeJS(['../js/roster/enrollstudents.js']);
        return $this->render('enrollStudents', ['course' => $course, 'data' => $studentDetails, 'model' => $model, 'cid' => $cid]);
    }

// Controller method for create and enroll new student in current course

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

//Controller method for manage tutor page

    public function actionManageTutors()
    {
        $this->guestUserHandler();
        $courseid = Yii::$app->request->get('cid');
        $course = Course::getById($courseid);
        $tutors = Tutor::getByCourseId($courseid);
        $tutorId = array();
        $tutorInfo = array();
        $sortBy = 'section';
        $order = AppConstant::ASCENDING;
        foreach ($tutors as $tutor) {

            $tempArray = array('Name' => $tutor->user->FirstName . ' ' . $tutor->user->LastName, 'id' => $tutor->user->id, 'section' => $tutor->section);
            array_push($tutorInfo, $tempArray);
        }
        $sections = Student::findByCourseId($courseid, $sortBy, $order);
        $sectionArray = array();
        foreach ($sections as $section) {
            array_push($sectionArray, $section->section);
        }
        $this->includeCSS(['../js/DataTables-1.10.6/media/css/jquery.dataTables.css']);
        $this->includeJS(['../js/general.js?ver=012115', '../js/roster/managetutors.js?ver=012115', '../js/jquery.session.js?ver=012115', '../js/DataTables-1.10.6/media/js/jquery.dataTables.js', '../js/roster/managetutors.js']);
        return $this->renderWithData('manageTutors', ['course' => $course,'courseid' => $courseid, 'tutors' => $tutorInfo, 'section' => $sectionArray]);
    }

// Function to add or update information After submitting the information from manage tutor page

    public function actionMarkUpdateAjax()
    {
        $this->guestUserHandler();
        $params = $this->getBodyParams();
        $users = explode(',', $params['username']);
        $courseid = $params['courseid'];
        $sortBy = 'section';
        $order = AppConstant::ASCENDING;
        $userIdArray = array();
        $userNotFoundArray = array();
        $studentArray = array();
        $tutorsArray = array();
        $sections = Student::findByCourseId($courseid, $sortBy, $order);
        $sectionArray = array();
        foreach ($sections as $section) {
            array_push($sectionArray, $section->section);
        }
        foreach ($users as $entry) {
            $userId = User::findByUsername($entry);
            if (!$userId) {
                array_push($userNotFoundArray, $entry);
            } else {
                array_push($userIdArray, $userId->id);
                $isTeacher = Teacher::getUniqueByUserId($userId->id);
                if ($isTeacher) {
                    $tutors = Tutor::getByUserId($isTeacher->userid, $courseid);
                    if (!$tutors) {
                        $tutorInfo = array('Name' => $userId->FirstName . ' ' . $userId->LastName, 'id' => $userId->id);
                        array_push($tutorsArray, $tutorInfo);
                        $tutor = new Tutor();
                        $tutor->create($isTeacher->userid, $courseid);
                    }
                } else {
                    array_push($studentArray, $userId->id);
                }
            }
        }
        $params['sectionArray'] = isset($params['sectionArray']) ? $params['sectionArray'] : '';
        foreach ($params['sectionArray'] as $tutors) {
            Tutor::updateSection($tutors['tutorId'], $courseid, $tutors['tutorSection']);

        }
        $params['checkedtutor'] = isset($params['checkedtutor']) ? $params['checkedtutor'] : '';
        if ($params['checkedtutor'] != '') {
            foreach ($params['checkedtutor'] as $tutor) {
                Tutor::deleteTutorByUserId($tutor);
            }
        }
        return json_encode(array('status' => 0, 'userNotFound' => $userNotFoundArray, 'tutors' => $tutorsArray, 'section' => $sectionArray));
    }

    public function actionImportStudent()
    {
        $user = $this->getAuthenticatedUser();
        $model = new ImportStudentForm();
        $nowTime = time();
        $courseId = Yii::$app->request->get('cid');
        $course = Course::getById($courseId);
        $studentRecords = '';
        if ($model->load(Yii::$app->request->post())) {
            $params = $this->getRequestParams();
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $filename = AppConstant::UPLOAD_DIRECTORY . $nowTime . '.csv';
                $model->file->saveAs($filename);
            }
            $studentRecords = $this->ImportStudentCsv($filename, $courseId, $params);

            $newUserRecords = array();
            $existUserRecords = array();

            foreach($studentRecords['allUsers'] as $users){
                array_push($newUserRecords, $users);
            }
            foreach($studentRecords['existingUsers'] as $users){
                foreach ($users as $singleUser){
                    $tempArray = array(
                    'userName' => $singleUser->SID,
                    'firstName' => $singleUser->FirstName,
                    'lastName' => $singleUser->LastName,
                    'email' => $singleUser->email,
                    );
                    array_push($existUserRecords,$tempArray);
                }
            }
            if($filename){
                $this->redirect(array('show-import-student', 'existingUsers' => $existUserRecords , 'newUsers' => $newUserRecords,'course' => $course));
            }

        }

        if(!$studentRecords){
        return $this->render('importStudent', ['model' => $model,'course' => $course]);
          }
    }

    public function ImportStudentCsv($fileName, $courseId, $params)
    {
        $course = Course::getById($courseId);
        $AllUserArray = array();
        $ExistingUser = array();
        if ($course) {
            $handle = fopen($fileName, 'r');
            if ($params['ImportStudentForm']['headerRow'] == 1) {
                $data = fgetcsv($handle, 2096);
            }
            while (($data = fgetcsv($handle, 2096)) !== false) {
                $StudentDataArray = $this->parsecsv($data, $params);
                for ($i = 0; $i < count($StudentDataArray); $i++) {
                    $StudentDataArray[$i] = trim($StudentDataArray[$i]);
                }
                if (trim($StudentDataArray[0]) == '' || trim($StudentDataArray[0]) == '_') {
                    continue;
                }
                $userData = User::getByName($StudentDataArray[0]);
                if ($userData) {
                    $userExist = User::userAlreadyExist($StudentDataArray[0]);
                } else {
                    if (($params['ImportStudentForm']['setPassword'] == 0 || $params['ImportStudentForm']['setPassword'] == 1) && strlen($StudentDataArray[0]) < 4) {
                        $password = password_hash($StudentDataArray[0], PASSWORD_DEFAULT);
                    } else {
                        if ($params['ImportStudentForm']['setPassword'] == 0) {
                            $password = password_hash(substr($StudentDataArray[0], 0, 4), PASSWORD_DEFAULT);
                        } else if ($params['ImportStudentForm']['setPassword'] == 1) {
                            $password = password_hash(substr($StudentDataArray[0], -4), PASSWORD_DEFAULT);

                        } else if ($params['ImportStudentForm']['setPassword'] == 2) {
                            $password = password_hash($params['defpw'], PASSWORD_DEFAULT);

                        } else if ($params['ImportStudentForm']['setPassword'] == 3) {
                            if (trim($StudentDataArray[6]) == '') {
                                echo "Password for {$StudentDataArray[0]} is blank; skipping import<br/>";
                                continue;
                            }
                            $password = password_hash($StudentDataArray[6], PASSWORD_DEFAULT);
                        }
                    }

                    array_push($StudentDataArray, $password);
                    array_push($AllUserArray, $StudentDataArray);
                }
                if ($userData){
                    array_push($ExistingUser,$userData);
                    array_push($AllUserArray, $StudentDataArray);
                }
            }

            return  (['allUsers' => $AllUserArray,'existingUsers' => $ExistingUser]);
        }
        return false;
    }

    public function parsecsv($data, $params)
    {
        $firstnamePosition = $params['ImportStudentForm']['firstName'] - 1;
        $firstname = $data[$firstnamePosition];
        if ($params['ImportStudentForm']['nameFirstColumn'] != 0) {
            $firstnameColumn = explode(' ', $firstname);
            if ($params['ImportStudentForm']['nameFirstColumn'] < 3) {
                $firstname = $firstnameColumn[$params['ImportStudentForm']['nameFirstColumn'] - 1];
            } else {
                $firstname = $firstnameColumn[count($firstnameColumn) - 1];
            }
        }
        $lastnamePosition = $params['ImportStudentForm']['lastName'] - 1;
        $lastname = $data[$lastnamePosition];
        if ($params['ImportStudentForm']['lastName'] != $params['ImportStudentForm']['firstName'] && $params['ImportStudentForm']['nameLastColumn'] != 0) {
            $lastnameColumn = explode(' ', $lastname);
        }
        if ($params['ImportStudentForm']['nameLastColumn'] != 0) {
            if ($params['ImportStudentForm']['nameLastColumn'] < 3) {
                $lastname = $lastnameColumn[$params['ImportStudentForm']['nameLastColumn'] - 1];
            } else {
                $lastname = $lastnameColumn[count($lastnameColumn) - 1];
            }
        }
        $firstname = preg_replace('/\W/', '', $firstname);
        $lastname = preg_replace('/\W/', '', $lastname);
        $firstname = ucfirst(strtolower($firstname));
        $lastname = ucfirst(strtolower($lastname));
        if ($params['ImportStudentForm']['userName'] == 0) {
            $username = strtolower($firstname . '_' . $lastname);
        } else {
            $username = $data[$params['unloc'] - 1];
            $username = preg_replace('/\W/', '', $username);
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
            $code = $data[$params['code'] - 1];
        } else {
            $code = 0;
        }
        if ($params['ImportStudentForm']['sectionValue'] == 1) {
            $section = $params['secval'];
        } else if ($params['ImportStudentForm']['sectionValue'] == 2) {
            $section = $data[$params['seccol'] - 1];
        } else {
            $section = 0;
        }
        if ($params['ImportStudentForm']['setPassword'] == 3) {
            $password = $data[$params['pwcol'] - 1];
        } else {
            $password = 0;
        }

        return array($username, $firstname, $lastname, $email, $code, $section, $password);
    }

    public function actionShowImportStudent()
    {
        $studentInformation = $this->getRequestParams();
        if($this->isPost())
        {$params = $this->getRequestParams();
            AppUtility::dump($params     );
            $user = new User();
            foreach($studentInformation['newUsers'] as $newEntry){
                $user->createUserFromCsv($newEntry, AppConstant::STUDENT_RIGHT);
            }
            $this->setSuccessFlash('Imported student successfully.');
        }
        $this->includeCSS(['../js/DataTables-1.10.6/media/css/jquery.dataTables.css']);
        $this->includeJS(['../js/general.js?','../js/roster/importstudent.js','../js/DataTables-1.10.6/media/js/jquery.dataTables.js']);
        return $this->render('showImportStudent',['studentData' => $studentInformation]);
    }

//Controller method to assign lock on student.

    public function actionMarkLockAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();
        foreach($params['checkedstudents'] as $students)
        {
            Student::updateLocked($students,$params['courseid']);
        }

        return json_encode(array('status' => 0));

    }

    public function actionRosterEmail()
    {
        $courseId = Yii::$app->request->get('cid');
        $assessments = Assessments::getByCourseId($courseId);
        $selectedStudents = $this->getBodyParams();
        
        if($this->isPost()){
            return $this->renderWithData('rosterEmail');
        }
        $this->includeJS(['../js/roster/rosterEmail.js','../js/editor/tiny_mce.js' , '../js/editor/tiny_mce_src.js', '../js/general.js', '../js/editor/plugins/asciimath/editor_plugin.js', '../js/editor/themes/advanced/editor_template.js']);
        return $this->renderWithData('rosterEmail',['assessments' => $assessments]);
    }

    public function actionMarkUnenrollAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();
        foreach($params['checkedstudents'] as $students)
        {
            Student::deleteStudent($students,$params['courseid']);
        }

        return json_encode(array('status' => 0));
    }

    public function actionRosterEmailAjax()
    {
        $userData = $this->getRequestParams();
        AppUtility::dump($userData);
    }
}



