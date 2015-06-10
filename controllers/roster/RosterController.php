<?php
namespace app\controllers\roster;

use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\forms\CreateAndEnrollNewStudentForm;
use app\models\forms\EnrollFromOtherCourseForm;
use app\models\forms\EnrollStudentsForm;
use app\models\forms\ManageTutorsForm;
use app\models\forms\StudentEnrollmentForm;
use app\models\LoginGrid;
use app\models\loginTime;
use app\models\Message;
use app\models\Student;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use Yii;
use app\components\AppUtility;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use app\models\forms\ImportStudentForm;
use yii\web\UploadedFile;
use app\components\AppConstant;


class RosterController extends AppController
{

   // public $studentInfo = array();
    //Controller method to display student information on student roster page.
    public function actionStudentRoster()
    {
        $this->guestUserHandler();
        $courseid = $this->getParam('cid');
        $course = Course::getById($courseid);
        $students = Student::findByCid($courseid);
        if ($students) {
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
        }
        $this->includeCSS(['jquery-ui.css', 'dataTables-jqueryui.css']);
        $this->includeJS(['roster/studentroster.js', 'general.js']);
        $responseData = array('course' => $course, 'isSection' => $isSectionPresent, 'isCode' => $isCodePresent);
        return $this->render('studentRoster', $responseData);

    }

    //Controller method for redirect to Login Grid View page.
    public function actionLoginGridView()
    {
        $this->guestUserHandler();
        $courseid = $this->getParam('cid');
        $course = Course::getById($courseid);
        $this->includeCSS(['jquery-ui.css']);
        $this->includeJS(['logingridview.js', 'general.js']);
        $responseData = array('course' => $course);
        return $this->render('loginGridView', $responseData);
    }

    //Controller ajax method to retrieve student data form Login grid table
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
        return $this->successResponse($retJSON);
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
        if ($Students) {
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
                    'locked' => $student->locked,
                    'section' => $student->section,
                    'code' => $student->code,
                );
                array_push($studentArray, $tempArray);
            }
        }

        $responseData = array('query' => $studentArray, 'isCode' => $isCodePresent, 'isSection' => $isSectionPresent);
        return $this->successResponse($responseData);
    }


    public function actionStudentEnrollment()
    {
        $this->guestUserHandler();
        $cid = $this->getParam('cid');
        $model = new StudentEnrollmentForm();
        $course = Course::getById($cid);
        if ($model->load(\Yii::$app->request->post())) {
            $param = $this->getRequestParams();
            $param = $param['StudentEnrollmentForm'];
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
        $responseData = array('course' => $course, 'model' => $model);
        return $this->render('studentEnrollment', $responseData);
    }

    // Controller method to redirect on Assign and Section Codes page with student information
    public function actionAssignSectionsAndCodes()
    {
        $this->guestUserHandler();
        $courseid = $this->getParamVal('cid');
        $query = Student::findByCid($courseid);
        $course = Course::getById($courseid);
        $studentArray = array();
        if ($query) {
            foreach ($query as $student) {
                $tempArray = array('Name' => $student->user->FirstName . ' ' . $student->user->LastName,
                    'code' => $student->code,
                    'section' => $student->section,
                    'userid' => $student->userid
                );
                array_push($studentArray, $tempArray);
            }
        }
        if ($this->isPost()) {
            $params = $_POST;
            if ($params['section']) {
                foreach ($params['section'] as $key => $section) {
                    $code = trim($params['code'][$key]);
                    Student::updateSectionAndCodeValue(trim($section), $key, $code, $courseid);
                }
            }
            $this->redirect('student-roster?cid=' . $courseid);
        }
        $this->includeCSS(['jquery-ui.css', '../js/DataTables-1.10.6/media/css/jquery.dataTables.css']);
        $this->includeJS(['roster/assignSectionsAndCodes.js', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('studentInformation' => $studentArray, 'cid' => $courseid, 'course' => $course);
        return $this->render('assignSectionsAndCodes', $responseData);
    }

//Controller method to redirect on Manage Late Passes page with student information,
    public function actionManageLatePasses()
    {
        $this->guestUserHandler();
        $courseid = $this->getParamVal('cid');
        $model = Student::findByCid($courseid);
        $course = Course::getById($courseid);
        $studentArray = array();
        if ($model) {
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
        }
        $this->includeCSS(['jquery-ui.css', '../js/DataTables-1.10.6/media/css/jquery.dataTables.css']);
        $this->includeJS(['roster/managelatepasses.js', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('studentInformation' => $studentArray, 'course' => $course);
        return $this->render('manageLatePasses', $responseData);
    }

    // Controller method to display the dynamic radio list of courses
    public function actionEnrollFromOtherCourse()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $model = new EnrollFromOtherCourseForm();
        $course = Course::getById($cid);
        $teacherId = $this->getUserId();
        $list = Teacher::getTeacherByUserId($teacherId);
        $courseDetails = array();
        if ($list) {
            foreach ($list as $teacher) {
                $tempArray = array("id" => $teacher->course->id,
                    "name" => $teacher->course->name);
                array_push($courseDetails, $tempArray);
            }
        }
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            $courseId = isset($params['name']) ? $params['name'] : null;
            if ($courseId) {
                $this->redirect('enroll-students?cid=' . $cid . '&courseData=' . $courseId);
            } else {
                $this->setErrorFlash("Select course from list to choose students");
            }
        }
        $responseData = array('course' => $course, 'data' => $courseDetails, 'model' => $model);
        return $this->render('enrollFromOtherCourse', $responseData);
    }

    // Controller method to dynamically create student list with checkbox and enroll students displayed in a list in current course.
    public function actionEnrollStudents()
    {
        $this->guestUserHandler();

        $courseid = $this->getParamVal('courseData');
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $model = new EnrollStudentsForm();
        $query = Student::findByCid($courseid);
        $studentDetails = array();
        if ($query) {
            foreach ($query as $student) {
                $users = User::getById($student->userid);
                $tempArray = array("id" => $student->userid,
                    "firstName" => $users->FirstName,
                    "lastName" => $users->LastName);
                array_push($studentDetails, $tempArray);
            }
        }
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            $record = array();
            $count = 0;
            foreach ($params as $result) {
                array_push($record, $result);
                $count++;
            }
            if ($count != 5) {
                $storedArray = array();
                foreach ($record[3] as $entry) {
                    $studentList = array("id" => $entry, "courseId" => $cid, "section" => $params['EnrollStudentsForm']['section']);
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
        $this->includeJS(['roster/enrollstudents.js']);
        $responseData = array('course' => $course, 'data' => $studentDetails, 'model' => $model, 'cid' => $cid);
        return $this->render('enrollStudents', $responseData);
    }

// Controller method for create and enroll new student in current course

    public function actionCreateAndEnrollNewStudent()
    {
        $this->guestUserHandler();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $model = new CreateAndEnrollNewStudentForm();
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            $findUser = User::findByUsername($params['CreateAndEnrollNewStudentForm']['username']);
            if (!$findUser) {
                $user = new User();
                $user->createAndEnrollNewStudent($params['CreateAndEnrollNewStudentForm']);
                $studentid = User::findByUsername($params['CreateAndEnrollNewStudentForm']['username']);
                $newStudent = new Student();
                $newStudent->createNewStudent($studentid['id'], $cid, $params['CreateAndEnrollNewStudentForm']);
                $this->setSuccessFlash('Student have been created and enrolled in course ' . $course->name . ' successfully');

            } else {
                $this->setErrorFlash('Username already exists');
            }
        }
        $responseData = array('course' => $course, 'model' => $model);
        return $this->renderWithData('createAndEnrollNewStudent', $responseData);
    }

//Controller method for manage tutor page

    public function actionManageTutors()
    {
        $this->guestUserHandler();
        $courseid = $this->getParamVal('cid');
        $course = Course::getById($courseid);
        $tutors = Tutor::getByCourseId($courseid);
        $tutorInfo = array();
        $sortBy = 'section';
        $order = AppConstant::ASCENDING;
        if ($tutors) {
            foreach ($tutors as $tutor) {
                $tempArray = array('Name' => $tutor->user->FirstName . ' ' . $tutor->user->LastName, 'id' => $tutor->user->id, 'section' => $tutor->section);
                array_push($tutorInfo, $tempArray);
            }
        }
        $sections = Student::findByCourseId($courseid, $sortBy, $order);
        $sectionArray = array();
        if ($sections) {
            foreach ($sections as $section) {
                array_push($sectionArray, $section->section);
            }
        }
        $this->includeCSS(['../js/DataTables-1.10.6/media/css/jquery.dataTables.css']);
        $this->includeJS(['general.js?ver=012115', 'roster/managetutors.js?ver=012115', 'jquery.session.js?ver=012115', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('course' => $course, 'courseid' => $courseid, 'tutors' => $tutorInfo, 'section' => $sectionArray);
        return $this->renderWithData('manageTutors', $responseData);
    }

// Function to add or update information After submitting the information from manage tutor page

    public function actionMarkUpdateAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $params['username'] = trim($params['username']);
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
        if (count($users)) {
            foreach ($users as $entry) {
                $entry = trim($entry);
                $userId = User::findByUsername($entry);
                if (!$userId) {
                    array_push($userNotFoundArray, $entry);
                } else {
                    array_push($userIdArray, $userId->id);
                    $isTeacher = Teacher::getUniqueByUserId($userId->id);
                    if ($isTeacher) {
                        $tutors = Tutor::getByUserId($isTeacher->userid, $courseid);
                        if (!$tutors) {
                            $tutorInfo = array('Name' => AppUtility::getFullName($userId->FirstName, $userId->LastName), 'id' => $userId->id);
                            array_push($tutorsArray, $tutorInfo);
                            $tutor = new Tutor();
                            $tutor->create($isTeacher->userid, $courseid);
                        }
                    } else {
                        array_push($studentArray, $userId->id);
                    }
                }
            }
        }
        $params['sectionArray'] = isset($params['sectionArray']) ? $params['sectionArray'] : '';

        if ($params['sectionArray']) {
            foreach ($params['sectionArray'] as $tutors) {
                Tutor::updateSection($tutors['tutorId'], $courseid, $tutors['tutorSection']);
            }
        }

        $params['checkedtutor'] = isset($params['checkedtutor']) ? $params['checkedtutor'] : '';
        if ($params['checkedtutor']) {
            foreach ($params['checkedtutor'] as $tutor) {
                Tutor::deleteTutorByUserId($tutor);
            }
        }
        $responseData = array('userNotFound' => $userNotFoundArray, 'tutors' => $tutorsArray, 'section' => $sectionArray);
        return $this->successResponse($responseData);
    }

    public function actionImportStudent()
    {
        $model = new ImportStudentForm();
        $nowTime = time();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $studentRecords = '';
        if ($model->load($this->getPostData())) {
            $params = $this->getRequestParams();
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $filename = AppConstant::UPLOAD_DIRECTORY . $nowTime . '.csv';
                $model->file->saveAs($filename);
            }
            $studentRecords = $this->ImportStudentCsv($filename, $courseId, $params);

            $newUserRecords = array();
            $existUserRecords = array();

            foreach ($studentRecords['allUsers'] as $users) {
                array_push($newUserRecords, $users);
            }
            foreach ($studentRecords['existingUsers'] as $users) {
                foreach ($users as $singleUser) {
                    $tempArray = array(
                        'userName' => $singleUser->SID,
                        'firstName' => $singleUser->FirstName,
                        'lastName' => $singleUser->LastName,
                        'email' => $singleUser->email,
                    );
                    array_push($existUserRecords, $tempArray);
                }
            }
            if ($filename) {
                $this->redirect(array('show-import-student', 'existingUsers' => $existUserRecords, 'newUsers' => $newUserRecords, 'course' => $course));
            }
        }
        if (!$studentRecords) {
            $responseData = array('model' => $model, 'course' => $course);
            return $this->render('importStudent', $responseData);
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
                if ($userData) {
                    array_push($ExistingUser, $userData);
                    array_push($AllUserArray, $StudentDataArray);
                }
            }

            return (['allUsers' => $AllUserArray, 'existingUsers' => $ExistingUser]);
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
//        if ($this->isPost()) {
//            $params = $this->getRequestParams();
//            $user = new User();
//            foreach ($studentInformation['newUsers'] as $newEntry) {
//                $user->createUserFromCsv($newEntry, AppConstant::STUDENT_RIGHT);
                $isCodePresent = false;
                $isSectionPresent = false;
                foreach ($studentInformation['newUsers'] as $student) {

                    if ($student['4'] != 0) {
                        $isCodePresent = true;
                    }
                    if ($student['5'] != 0) {
                        $isSectionPresent = true;
                    }
                }
                $this->includeCSS(['../js/DataTables-1.10.6/media/css/jquery.dataTables.css']);

                $this->includeJS(['general.js?', 'roster/importstudent.js', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
                $responseData = array('studentData' => $studentInformation, 'isSectionPresent' => $isSectionPresent, 'isCodePresent' => $isCodePresent);

                return $this->render('showImportStudent', $responseData);

//            }
//        }
    }

//Controller method to assign lock on student.

    public function actionMarkLockAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();
        foreach ($params['checkedstudents'] as $students) {
            Student::updateLocked($students, $params['courseid']);
        }
        return $this->successResponse();
    }

    public function actionRosterEmail()
    {
        if ($this->isPost()) {
            $selectedStudents = $this->getBodyParams();
            $emailSender = $this->getAuthenticatedUser();
            $isActionForEmail = isset($selectedStudents['isEmail']) ? $selectedStudents['isEmail'] : 0;
            $courseId = isset($selectedStudents['course-id']) ? $selectedStudents['course-id'] : '';

            if (!$isActionForEmail) {
                $course = Course::getById($courseId);
                $assessments = Assessments::getByCourseId($courseId);
                if ($selectedStudents['student-data'] != '') {
                    $selectedStudents = explode(',', $selectedStudents['student-data']);
                    $studentArray = array();
                    foreach ($selectedStudents as $studentId) {
                        $student = User::getById($studentId);
                        array_push($studentArray, $student->attributes);
                    }

                    $this->includeJS(['roster/rosterEmail.js','editor/tiny_mce.js' , 'editor/tiny_mce_src.js','', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
                    $responseData = array('assessments' => $assessments, 'studentDetails' => serialize($studentArray), 'course' => $course);
                    return $this->renderWithData('rosterEmail', $responseData);
                } else {
                    return $this->redirect('student-roster?cid=' . $courseId);
                }

            }else{
                $students = array();
                $studentArray = array();
                $sendToStudents = array();
                $filteredStudents = array();
                $studentsInfo = unserialize($selectedStudents['studentInformation']);

                if($selectedStudents['roster-assessment-data']!=0){
                    $query = AssessmentSession::getStudentByAssessments($selectedStudents['roster-assessment-data']);
                    if($query){
                        foreach($query as $entry){
                            foreach($studentsInfo as $record){
                                if($entry['userid'] == $record['id']){
                                    array_push($students,$record['id']);
                                }

                            }
                        }
                    }
                }
                foreach($studentsInfo as $student){
                    if (!in_array($student['id'],$students)) {
                        array_push($filteredStudents,$student['id']);

                    $tempArray = array(
                        'firstName' => $student['FirstName'],
                        'lastName' => $student['LastName'],
                        'emailId' => $student['email'],
                        'userId' => $student['id'],
                    );
                    array_push($studentArray, $tempArray);
                    $sendto = trim(ucfirst($student['LastName']).', '.ucfirst($student['FirstName']));
                    array_push($sendToStudents, $sendto);
                    }
                }
                $toList = implode("<br>",$sendToStudents);
                $message = $selectedStudents['message'];
                $subject = $selectedStudents['subject'];
                $courseId = $selectedStudents['courseId'];
                $course = Course::getById($courseId);
                $messageToTeacher = $message.addslashes("<p>Instructor note: Email sent to these students from course $course->name: <br>$toList\n");
                if($selectedStudents['emailCopyToSend'] == 'singleStudent'){
                    $this->sendEmailToSelectedUser($subject,$message, $studentArray);
                }elseif($selectedStudents['emailCopyToSend'] == 'selfStudent'){
                    AppUtility::sendMail($subject, $messageToTeacher, $emailSender['email']);
                    $this->sendEmailToSelectedUser($subject,$message, $studentArray);
                }elseif($selectedStudents['emailCopyToSend'] == 'allTeacher'){
                    $instructors = Teacher::getTeachersById($selectedStudents['courseId']);
                    foreach ($instructors as $instructor) {
                        AppUtility::sendMail($subject, $message, $instructor->user->email);
                    }
                    $this->sendEmailToSelectedUser($subject,$messageToTeacher, $studentArray);

                }
                return $this->redirect('student-roster?cid=' . $courseId);
            }
        }
    }

    public function sendEmailToSelectedUser($subject, $message, $studentArray)
    {
        foreach ($studentArray as $singleStudent) {
            AppUtility::sendMail($subject, $message, $singleStudent['emailId']);
        }
    }

    public function actionMarkUnenrollAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();
        foreach ($params['checkedstudents'] as $students) {
            Student::deleteStudent($students, $params['courseid']);
        }
        return $this->successResponse();
    }

    public function actionRosterMessage()
    {
        if ($this->isPost()) {
            $selectedStudents = $this->getRequestParams();
            $isActionForMessage = isset($selectedStudents['isMessage']) ? $selectedStudents['isMessage'] : 0;
            $courseId = isset($selectedStudents['course-id']) ? $selectedStudents['course-id'] : '';
            if (!$isActionForMessage) {
                $course = Course::getById($courseId);
                $assessments = Assessments::getByCourseId($courseId);
                if ($selectedStudents['student-data'] != '') {
                    $selectedStudents = explode(',', $selectedStudents['student-data']);
                    $studentArray = array();
                    foreach ($selectedStudents as $studentId) {
                        $student = User::getById($studentId);
                        array_push($studentArray, $student->attributes);
                    }
                    $this->includeJS(['roster/rosterMessage.js', 'editor/tiny_mce.js', 'editor/tiny_mce_src.js', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
                    $responseData = array('assessments' => $assessments, 'studentDetails' => serialize($studentArray), 'course' => $course);
                    return $this->renderWithData('rosterMessage', $responseData);
                } else {
                    return $this->redirect('student-roster?cid=' . $courseId);
                }
            } else {
                $students = array();
                $sendToStudents = array();
                $filteredStudents = array();
                $user = $this->getAuthenticatedUser();
                $studentsInfo = unserialize($selectedStudents['studentInformation']);
                $courseId = $selectedStudents['courseid'];
                $course = Course::getById($courseId);
                $subject = trim($selectedStudents['subject']);
                $messageBody = trim($selectedStudents['message']);
                if (!$selectedStudents['isChecked']) {
                    $notSaved = 4;
                }
                $save = 1;
                if ($selectedStudents['roster-assessment-data'] != 0) {
                    $query = AssessmentSession::getStudentByAssessments($selectedStudents['roster-assessment-data']);
                    if ($query) {
                        foreach ($query as $entry) {
                            foreach ($studentsInfo as $record) {
                                if ($entry['userid'] == $record['id']) {
                                    array_push($students, $record['id']);
                                }
                            }
                        }
                    }
                }
                foreach ($studentsInfo as $student) {
                    if (!in_array($student['id'], $students)) {
                        array_push($filteredStudents, $student['id']);
                        $sendto = trim(ucfirst($student['LastName']) . ', ' . ucfirst($student['FirstName']));
                        array_push($sendToStudents, $sendto);
                    }
                }
                $toList = implode("<br>", $sendToStudents);
                if ($selectedStudents['messageCopyToSend'] == 'onlyStudents') {
                    foreach ($filteredStudents as $singleStudent) {
                        $this->sendMassMessage($courseId, $singleStudent, $subject, $messageBody, $notSaved);
                    }
                    return $this->redirect('student-roster?cid=' . $courseId);
                } elseif ($selectedStudents['messageCopyToSend'] == 'selfAndStudents') {
                    foreach ($filteredStudents as $singleStudent) {
                        $this->sendMassMessage($courseId, $singleStudent, $subject, $messageBody, $notSaved);
                    }
                    $messageToTeacher = $messageBody . addslashes("<p>Instructor note: Message sent to these students from course $course->name: <br>$toList\n");
                    $this->sendMassMessage($courseId, $user->id, $subject, $messageToTeacher, $save);
                    return $this->redirect('student-roster?cid=' . $courseId);
                } elseif ($selectedStudents['messageCopyToSend'] == 'teachersAndStudents') {
                    foreach ($filteredStudents as $singleStudent) {
                        $this->sendMassMessage($courseId, $singleStudent, $subject, $messageBody, $notSaved);
                    }
                    $teachers = Teacher::getAllTeachers($courseId);
                    foreach ($teachers as $teacher) {
                        $messageToTeacher = $messageBody . addslashes("<p>Instructor note: Message sent to these students from course $course->name: <br>$toList\n");
                        $this->sendMassMessage($courseId, $teacher['userid'], $subject, $messageToTeacher, $save);
                    }
                    return $this->redirect('student-roster?cid=' . $courseId);
                }
            }
        }

    }

    public function sendMassMessage($courseId, $receiver, $subject, $messageBody, $isRead)
    {

        $user = $this->getAuthenticatedUser();
        $tempArray = array('cid' => $courseId, 'receiver' => $receiver, 'subject' => $subject, 'body' => $messageBody, 'isread' => $isRead);
        $message = new Message();
        $message->create($tempArray, $user->id);
    }

    public function actionMakeException()
    {
        if ($this->isPost()) {
            $selectedStudents = $this->getRequestParams();

            $courseId = $this->getParamVal('cid');
            $isActionForException = isset($selectedStudents['isException']) ? $selectedStudents['isException'] : 0;
//            $courseId = isset($selectedStudents['course-id']) ? $selectedStudents['course-id'] : '';
            if (!$isActionForException) {
//                $course = Course::getById($courseId);
//                $assessments = Assessments::getByCourseId($courseId);
//
                $course = Course::getById($courseId);
                $assessments = Assessments::getByCourseId($courseId);
                if ($selectedStudents['student-data'] != '') {
                    $selectedStudents = explode(',', $selectedStudents['student-data']);
                    $studentArray = array();
                    foreach ($selectedStudents as $studentId) {
                        $student = User::getById($studentId);
                        array_push($studentArray, $student->attributes);
                    }
                    $this->includeCSS(['site.css']);
                    $responseData = array('assessments' => $assessments, 'studentDetails' => serialize($studentArray), 'course' => $course);
                    return $this->renderWithData('makeException', $responseData);
                } else {
                    return $this->redirect('student-roster?cid=' . $courseId);
                }
            }else{
                AppUtility::dump($this->getRequestParams());
            }
        }
    }

    public  function actionSaveCsvFileAjax()
    {
        $params = $this->getBodyParams();

        $studentdata = $params['studentData'];

        foreach($studentdata as $newEntry){

            $user = new User();
            $student = new Student();
           $id = $user->createUserFromCsv($newEntry, AppConstant::STUDENT_RIGHT);
            $student->assignSectionAndCode($newEntry,$id);
        }
        $this->setSuccessFlash('Imported student successfully.');
        return $this->successResponse();
    }

    public function actionCopyStudentEmail(){
        if($this->isPost()){
            $selectedStudents = $this->getRequestParams();
            $selectedStudentId = explode(',',$selectedStudents['student-data']);
//            AppUtility::dump($selectedStudentId);
            $courseId = isset($selectedStudents['course-id']) ? $selectedStudents['course-id'] : '';
            $course = Course::getById($courseId);
            $studentArray = array();
            $studentData = array();
            foreach ($selectedStudentId as $studentId){
                $student = User::getById($studentId);
                array_push($studentArray,$student);
            }
            foreach($studentArray as $student){

                $sendto = trim(ucfirst($student['LastName'])." ".ucfirst($student['FirstName']))." < ".$student['email'].">;" ;
                array_push($studentData, $sendto);
            }
            $studentList = implode($studentData);
            $responseData = array('studentData' => $studentList,'course' => $course);
            $this->includeJS(['general.js']);
            return $this->renderWithData('copyStudentEmail', $responseData);
        }else{
            $courseId = $this->getParamVal('cid');
            return $this->redirect('student-roster?cid='.$courseId);
        }

    }
}

