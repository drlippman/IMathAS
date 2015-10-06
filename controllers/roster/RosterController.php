<?php
namespace app\controllers\roster;

use app\components\StudentUnenrollUtility;
use app\models\ActivityLog;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
use app\models\forms\CreateAndEnrollNewStudentForm;
use app\models\forms\EnrollFromOtherCourseForm;
use app\models\forms\EnrollStudentsForm;
use app\models\forms\ManageTutorsForm;
use app\models\forms\StudentEnrollmentForm;
use app\models\ForumPosts;
use app\models\Forums;
use app\models\InlineText;
use app\models\Links;
use app\models\LoginGrid;
use app\models\LoginLog;
use app\models\loginTime;
use app\models\Message;
use app\models\Questions;
use app\models\Student;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use app\models\Wiki;
use Yii;
use app\components\AppUtility;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use app\models\forms\ImportStudentForm;
use yii\web\UploadedFile;
use app\components\AppConstant;
use app\models\forms\ChangeUserInfoForm;
use yii\db\Exception;


class RosterController extends AppController
{
    public $newUser = array();
    public $existUserRecords = array();

    public function beforeAction($action)
    {
        $user = $this->getAuthenticatedUser();
        $courseId =  ($this->getParamVal('cid') || $this->getParamVal('courseId')) ? ($this->getParamVal('cid')?$this->getParamVal('cid'):$this->getParamVal('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForTeacher($user,$courseId);
    }
/*
 * Controller method to display student information on student roster page.
 */
    public function actionStudentRoster()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $isShowPic = $this->getParamVal('showpic');
        $course = Course::getById($courseId);
        if($isShowPic == AppConstant::NUMERIC_ZERO){
            $isImageColumnPresent = AppConstant::NUMERIC_ZERO;
        }else{
            $isImageColumnPresent = AppConstant::NUMERIC_ONE;
        }
        $this->includeCSS(['dataTables.bootstrap.css', 'roster/roster.css', 'course/course.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'roster/studentroster.js', 'general.js']);
        $responseData = array('course' => $course, 'isImageColumnPresent' => $isImageColumnPresent);
        return $this->render('studentRoster', $responseData);
    }

/*
 * Controller method for redirect to Login Grid View page.
 */
    public function actionLoginGridView()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $this->includeCSS(['jquery-ui.css', 'roster/roster.css', 'dataTables.bootstrap.css']);
        $this->includeJS(['logingridview.js', 'general.js', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        $responseData = array('course' => $course);
        return $this->render('loginGridView', $responseData);
    }

/*
 * Controller ajax method to retrieve student data form Login grid table
 */
    public function actionLoginGridViewAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $newStartDate = AppUtility::getTimeStampFromDate($params['newStartDate']);
        $newEndDate = strtotime($params['newEndDate']. " ".date('g:i a'));
        $loginLogs = LoginGrid::getById($courseId, $newStartDate, $newEndDate);
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
                $userSpecificDaysArray[$day] = AppConstant::NUMERIC_ONE;
            } else {
                $userSpecificDaysArray[$day] = $userSpecificDaysArray[$day] + AppConstant::NUMERIC_ONE;
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
        $params = $this->getRequestParams();
        $courseId = $params['course_id'];
        $students = Student::findByCid($courseId);
        $isCodePresent = false;
        $isSectionPresent = false;
        $isImageColumnPresent = 0;
        $studentArray = array();
        if ($students) {
            foreach ($students as $student) {
                $users = User::getById($student['userid']);
                if ($users['hasuserimg'] == 1) {
                    $isImageColumnPresent = 1;
                }
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
                    'hasuserimg' => $student->user->hasuserimg,
                );
                array_push($studentArray, $tempArray);
            }
        }
        $sort_by = array_column($studentArray, 'lastname');
        array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $studentArray);
        $responseData = array('query' => $studentArray, 'isCode' => $isCodePresent, 'isSection' => $isSectionPresent, 'isImageColumnPresent' => $isImageColumnPresent);
        return $this->successResponse($responseData);
    }

    public function actionStudentEnrollment()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $model = new StudentEnrollmentForm();
        $course = Course::getById($courseId);
        if ($model->load($this->isPostMethod())) {
            $param = $this->getRequestParams();
            $param = $param['StudentEnrollmentForm'];
            $user = User::findByUsername($param['usernameToEnroll']);
            if (!$user) {
                $this->setErrorFlash(AppConstant::STUDENT_ERROR_MESSAGE);
            } else {
                $teacher = Teacher::getTeacherByUserId($user->id);
                if ($teacher) {
                    $this->setErrorFlash(AppConstant::TEACHER_CANNOT_CHANGE_AS_SRUDENT);
                } else {
                    $studentRecord = Student::getByUserIdentity($user->id, $courseId);
                    if ($studentRecord) {
                        $this->setErrorFlash(AppConstant::USERNAME_ENROLLED);
                    } else {
                        $student = new Student();
                        $student->createNewStudent($user->id, $courseId, $param);
                        $this->redirect('student-roster?cid=' . $courseId);
                    }
                }
            }
        }
        $this->includeJS(['roster/studentEnrollment.js']);
        $this->includeCSS( ['roster/roster.css']);
        $responseData = array('course' => $course, 'model' => $model);
        return $this->render('studentEnrollment', $responseData);
    }

// Controller method to redirect on Assign and Section Codes page with student information
    public function actionAssignSectionsAndCodes()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $query = Student::findByCid($courseId);
        $course = Course::getById($courseId);
        $studentArray = array();
        if ($query) {
            foreach ($query as $student) {
                $tempArray = array('Name' => ucfirst($student->user->LastName).', '.ucfirst($student->user->FirstName),
                    'code' => $student->code,
                    'section' => $student->section,
                    'userid' => $student->userid
                );
                array_push($studentArray, $tempArray);
            }
        }
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            if ($params['section']) {
                foreach ($params['section'] as $key => $section) {
                    $code = trim($params['code'][$key]);
                    Student::updateSectionAndCodeValue(trim($section), $key, $code, $courseId);
                }
            }
            $this->redirect('student-roster?cid=' . $courseId);
        }
        $this->includeCSS(['jquery-ui.css', 'roster/roster.css', 'dataTables.bootstrap.css']);
        $this->includeJS(['roster/assignSectionsAndCodes.js', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('studentInformation' => $studentArray, 'cid' => $courseId, 'course' => $course);
        return $this->render('assignSectionsAndCodes', $responseData);
    }

//Controller method to redirect on Manage Late Passes page with student information,
    public function actionManageLatePasses()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $model = Student::findByCid($courseId);
        $course = Course::getById($courseId);
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
                    $params = $this->getRequestParams();
                    foreach ($params['code'] as $key => $latepass) {
                        $latePassHours = $params['passhours'];
                        Student::updateLatepasses(trim($latepass), $key, $courseId);
                    }
                    Course::updatePassHours($latePassHours, $courseId);
                    $this->redirect('student-roster?cid=' . $courseId);
                }
            }
        }
        $this->includeCSS(['jquery-ui.css', 'dataTables.bootstrap.css', 'roster/roster.css']);
        $this->includeJS(['roster/managelatepasses.js', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('studentInformation' => $studentArray, 'course' => $course);
        return $this->render('manageLatePasses', $responseData);
    }

// Controller method to display the dynamic radio list of courses
    public function actionEnrollFromOtherCourse()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $model = new EnrollFromOtherCourseForm();
        $course = Course::getById($courseId);
        $teacherId = $this->getUserId();
        $list = Teacher::getTeacherByUserId($teacherId);
        $courseDetails = array();
        if ($list) {
            foreach ($list as $teacher) {
                if ($teacher->course->id != $courseId) {
                    $tempArray = array("id" => $teacher->course->id,
                        "name" => ucfirst($teacher->course->name));
                    array_push($courseDetails, $tempArray);
                }
            }
        }
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            $selectedCourseId = isset($params['name']) ? $params['name'] : null;
            if ($selectedCourseId) {
                $this->redirect('enroll-students?cid=' . $courseId . '&courseData=' . $selectedCourseId);
            } else {
                $this->setErrorFlash(AppConstant::CHOOSE_STUDENT);
            }
        }
        $this->includeCSS( ['roster/roster.css']);
        $responseData = array('course' => $course, 'data' => $courseDetails, 'model' => $model);
        return $this->render('enrollFromOtherCourse', $responseData);
    }

// Controller method to dynamically create student list with checkbox and enroll students displayed in a list in current course.
    public function actionEnrollStudents()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $selectedCourseId = $this->getParamVal('courseData');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $model = new EnrollStudentsForm();
        $query = Student::findByCid($selectedCourseId);
        $queryCheck = Student::findByCid($courseId);
        $checkedArray = array();
        foreach ($queryCheck as $check) {
            foreach ($query as $singleCourse) {
                if ($singleCourse->userid == $check->userid) {
                    array_push($checkedArray, $singleCourse->userid);
                }
            }
        }
        $studentDetails = array();
        if ($query) {
            foreach ($query as $student) {
                $users = User::getById($student->userid);
                $isCheck = AppConstant::NUMERIC_ZERO;
                if (in_array($student->userid, $checkedArray)) {
                    $isCheck = AppConstant::NUMERIC_ONE;
                }
                $tempArray = array("id" => $student->userid,
                    "firstName" => ucfirst($users->FirstName),
                    "lastName" => ucfirst($users->LastName),
                    "isCheck" => $isCheck);
                array_push($studentDetails, $tempArray);
            }
        }
        $sort_by = array_column($studentDetails, 'lastName');
        array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $studentDetails);
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            $record = array();
            $count = AppConstant::NUMERIC_ZERO;
            foreach ($params as $result) {
                array_push($record, $result);
                $count++;
            }
            if ($count != AppConstant::NUMERIC_FIVE) {
                $storedArray = array();
                foreach ($record[3] as $entry) {
                    $studentList = array("id" => $entry, "courseId" => $courseId, "section" => $params['EnrollStudentsForm']['section']);
                    array_push($storedArray, $studentList);
                }
                foreach ($storedArray as $studentData) {
                    $studentRecord = Student::getByCourseId($studentData['courseId'], $studentData['id']);
                    if (!$studentRecord) {
                        $student = new Student();
                        $student->insertNewStudent($studentData['id'], $studentData['courseId'], $studentData['section']);
                    }
                }
                $this->redirect('student-roster?cid=' . $courseId);
            } else {
                $this->setErrorFlash('Select student from list to enroll in a course');
            }
        }
        $this->includeCSS( ['roster/roster.css']);
        $this->includeJS(['roster/enrollstudents.js']);
        $responseData = array('course' => $course, 'data' => $studentDetails, 'model' => $model, 'cid' => $courseId);
        return $this->render('enrollStudents', $responseData);
    }

// Controller method for create and enroll new student in current course
    public function actionCreateAndEnrollNewStudent()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $model = new CreateAndEnrollNewStudentForm();
        if ($this->isPost()) {
            $params = $this->getRequestParams();
            $findUser = User::findByUsername($params['CreateAndEnrollNewStudentForm']['username']);
            if (!$findUser) {
                $user = new User();
                $user->createAndEnrollNewStudent($params['CreateAndEnrollNewStudentForm']);
                $student = User::findByUsername($params['CreateAndEnrollNewStudentForm']['username']);
                $params['latepass'] = $course['deflatepass'];
                $newStudent = new Student();
                $newStudent->createNewStudent($student['id'], $courseId, $params['CreateAndEnrollNewStudentForm']);
                $this->setSuccessFlash('Student have been created and enrolled in course ' . $course->name . ' successfully');
                return $this->redirect('student-roster?cid=' . $courseId);
            } else {
                $this->setErrorFlash(AppConstant::USER_EXISTS);
            }
        }
        $this->includeCSS( ['roster/roster.css']);
        $responseData = array('course' => $course, 'model' => $model);
        return $this->renderWithData('createAndEnrollNewStudent', $responseData);
    }

//Controller method for manage tutor page
    public function actionManageTutors()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $tutors = Tutor::getByCourseId($courseId);
        $tutorInfo = array();
        $sortBy = 'section';
        $order = AppConstant::ASCENDING;
        if ($tutors) {
            foreach ($tutors as $tutor) {
                $tempArray = array('Name' => $tutor->user->FirstName . ' ' . $tutor->user->LastName, 'id' => $tutor->user->id, 'section' => $tutor->section);
                array_push($tutorInfo, $tempArray);
            }
        }
        $sections = Student::findByCourseId($courseId, $sortBy, $order);
        $sectionArray = array();
        if ($sections) {
            foreach ($sections as $section) {
                if($section->section !="" && $section->section != null){
                    array_push($sectionArray, $section->section);
                }
            }
        }
        $this->includeCSS(['roster/roster.css', 'dataTables.bootstrap.css']);
        $this->includeJS(['general.js?ver=012115', 'roster/managetutors.js?ver=012115', 'jquery.session.js?ver=012115', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('course' => $course, 'courseId' => $courseId, 'tutors' => $tutorInfo, 'section' => $sectionArray);
        return $this->renderWithData('manageTutors', $responseData);
    }

// Function to add or update information After submitting the information from manage tutor page

    public function actionMarkUpdateAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $params['username'] = trim($params['username']);
        $users = explode(',', $params['username']);
        $courseId = $params['courseId'];
        $sortBy = 'section';
        $order = AppConstant::ASCENDING;
        $userIdArray = array();
        $userNotFoundArray = array();
        $studentArray = array();
        $tutorsArray = array();
        $sections = Student::findByCourseId($courseId, $sortBy, $order);
        $sectionArray = array();
        foreach ($sections as $section) {
            array_push($sectionArray, $section->section);
        }
        if (count($users)) {
            foreach ($users as $entry) {
                $entry = trim($entry);
                if($entry != null){
                    $userId = User::findByUsername($entry);
                    if (!$userId) {
                        array_push($userNotFoundArray, $entry);
                    } else {
                        array_push($userIdArray, $userId->id);
                        $isTeacher = Teacher::getUniqueByUserId($userId->id);
                        if ($isTeacher) {
                            $tutors = Tutor::getByUserId($isTeacher->userid, $courseId);
                            if (!$tutors) {
                                $tutorInfo = array('Name' => AppUtility::getFullName($userId->FirstName, $userId->LastName), 'id' => $userId->id);
                                array_push($tutorsArray, $tutorInfo);
                                $tutor = new Tutor();
                                $tutor->create($isTeacher->userid, $courseId);
                            }
                        } else {
                            array_push($studentArray, $userId->id);
                        }
                    }
                }
            }
        }
        $params['sectionArray'] = isset($params['sectionArray']) ? $params['sectionArray'] : '';

        if ($params['sectionArray']) {
            foreach ($params['sectionArray'] as $tutors) {
                Tutor::updateSection($tutors['tutorId'], $courseId, $tutors['tutorSection']);
            }
        }
        $params['checkedTutor'] = isset($params['checkedTutor']) ? $params['checkedTutor'] : '';
        if ($params['checkedTutor']) {
            foreach ($params['checkedTutor'] as $tutor) {
                Tutor::deleteTutorByUserId($tutor);
            }
        }
        $responseData = array('userNotFound' => $userNotFoundArray, 'tutors' => $tutorsArray, 'section' => $sectionArray);
        return $this->successResponse($responseData);
    }

    public function actionImportStudent()
    {
        global $newUser,$existUserRecords;
        $this->guestUserHandler();
        $this->layout = "master";
        $model = new ImportStudentForm();
        $nowTime = time();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $studentRecords = '';
        $this->includeCSS(['roster/roster.css']);
        if ($model->load($this->isPostMethod()))
        {
            $params = $this->getRequestParams();
            if($courseId == 'admin' )
            {
                $courseId = $params['courseId'];
            }

            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $filename = AppConstant::UPLOAD_DIRECTORY . $nowTime . '.csv';
                $model->file->saveAs($filename);

            }
            $studentRecords = $this->ImportStudentCsv($filename, $courseId, $params);
            $existingUser = array();
            $newUser = array();

            if ($studentRecords)
            {
            foreach($studentRecords['allUsers'] as $key=>$StudentDataArray)
            {
                $userData = User::getByName($StudentDataArray[0]);
                if ($userData)
                {

                    $courseStudent = Student::getByCourseId($courseId, $userData['id']);
                    if ($courseStudent)
                    {
                        $tempArray = array(
                            'userName' => $userData->SID,
                            'firstName' => $userData->FirstName,
                            'lastName' => $userData->LastName,
                            'email' => $userData->email,
                        );
                        array_push($existingUser, $tempArray);
                    }else
                    {
                        array_push($newUser, $StudentDataArray);
                    }
                } else
                {
                    array_push($newUser, $StudentDataArray);
                }
            }
                if ($filename)
                {
                    $this->redirect(array('show-import-student', 'courseId' => $courseId,'newUser' => $newUser,'existingUser' => $existingUser));
                }
            } else {
                $this->setErrorFlash(AppConstant::ADD_AT_LEAST_ONE_RECORD);
                return $this->redirect('import-student?cid='.$course->id);
            }
        }
        if ($courseId == 'admin' )
        {
            $allCourses = Course::getAllCourses();
        }
        if (!$studentRecords) {
            $responseData = array('allCourses' => $allCourses,'model' => $model,'currentUser' => $currentUser,'courseId' => $courseId,'course' => $course);
            return $this->render('importStudent', $responseData);
        }
    }

    public function ImportStudentCsv($fileName, $courseId, $params)
    {
        $this->guestUserHandler();
        $course = Course::getById($courseId);
        $allUserArray = array();
        if ($course)
        {
            $handle = fopen($fileName, 'r');
            if ($params['ImportStudentForm']['headerRow'] == AppConstant::NUMERIC_ONE)
            {
                $data = fgetcsv($handle, 2096);
            }
            while (($data = fgetcsv($handle, 2096)) !== false)
            {
                $StudentDataArray = $this->parsecsv($data, $params);
                for ($i = 0; $i < count($StudentDataArray); $i++)
                {
                    $StudentDataArray[$i] = trim($StudentDataArray[$i]);
                }
                if (trim($StudentDataArray[0]) == '' || trim($StudentDataArray[0]) == '_') {
                    continue;
                }
                if (($params['ImportStudentForm']['setPassword'] == AppConstant::NUMERIC_ZERO || $params['ImportStudentForm']['setPassword'] == AppConstant::NUMERIC_ONE) && strlen($StudentDataArray[0]) < 4) {
                    $password = password_hash($StudentDataArray[0], PASSWORD_DEFAULT);
                } else {
                    if ($params['ImportStudentForm']['setPassword'] == 0) {
                        $password = password_hash(substr($StudentDataArray[0], 0, 4), PASSWORD_DEFAULT);
                    } else if ($params['ImportStudentForm']['setPassword'] == 1) {
                        $password = password_hash(substr($StudentDataArray[0], -4), PASSWORD_DEFAULT);

                    } else if ($params['ImportStudentForm']['setPassword'] == 2) {
                        $password = password_hash($params['defpw'], PASSWORD_DEFAULT);

                    } else if ($params['ImportStudentForm']['setPassword'] == AppConstant::NUMERIC_THREE) {
                        if (trim($StudentDataArray[6]) == '') {
                            echo "Password for {$StudentDataArray[0]} is blank; skipping import<br/>";
                            continue;
                        }
                        $password = password_hash($StudentDataArray[6], PASSWORD_DEFAULT);
                    }
                }
                array_push($StudentDataArray, $password);
                array_push($allUserArray, $StudentDataArray);
            }
            return (['allUsers' => $allUserArray]);
        }
        return false;
    }

    public function parsecsv($data, $params)
    {
        $this->guestUserHandler();
        $firstnamePosition = $params['ImportStudentForm']['firstName'] - AppConstant::NUMERIC_ONE;
        $firstname = $data[$firstnamePosition];
        if ($params['ImportStudentForm']['nameFirstColumn'] != AppConstant::NUMERIC_ZERO) {
            $firstnameColumn = explode(' ', $firstname);
            if ($params['ImportStudentForm']['nameFirstColumn'] < AppConstant::NUMERIC_THREE) {
                $firstname = $firstnameColumn[$params['ImportStudentForm']['nameFirstColumn'] - AppConstant::NUMERIC_ONE];
            } else {
                $firstname = $firstnameColumn[count($firstnameColumn) - AppConstant::NUMERIC_ONE];
            }
        }
        $lastnamePosition = $params['ImportStudentForm']['lastName'] - AppConstant::NUMERIC_ONE;
        $lastname = $data[$lastnamePosition];
        if ($params['ImportStudentForm']['lastName'] != $params['ImportStudentForm']['firstName'] && $params['ImportStudentForm']['nameLastColumn'] != 0) {
            $lastnameColumn = explode(' ', $lastname);
        }
        if ($params['ImportStudentForm']['nameLastColumn'] != AppConstant::NUMERIC_ZERO) {
            if ($params['ImportStudentForm']['nameLastColumn'] < AppConstant::NUMERIC_THREE) {
                $lastname = $lastnameColumn[$params['ImportStudentForm']['nameLastColumn'] - AppConstant::NUMERIC_ONE];
            } else {
                $lastname = $lastnameColumn[count($lastnameColumn) - AppConstant::NUMERIC_ONE];
            }
        }
        $firstname = preg_replace('/\W/', '', $firstname);
        $lastname = preg_replace('/\W/', '', $lastname);
        $firstname = ucfirst(strtolower($firstname));
        $lastname = ucfirst(strtolower($lastname));
        if ($params['ImportStudentForm']['userName'] == AppConstant::NUMERIC_ZERO) {
            $username = strtolower($firstname . '_' . $lastname);
        } else {
            $username = $data[$params['unloc'] - AppConstant::NUMERIC_ONE];
            $username = preg_replace('/\W/', '', $username);
        }
        if ($params['ImportStudentForm']['emailAddress'] > AppConstant::NUMERIC_ZERO) {
            $email = $data[$params['ImportStudentForm']['emailAddress'] - AppConstant::NUMERIC_ONE];
            if ($email == '') {
                $email = 'none@none.com';
            }
        } else {
            $email = 'none@none.com';
        }
        if ($params['ImportStudentForm']['codeNumber'] == AppConstant::NUMERIC_ONE) {
            $code = $data[$params['code'] - AppConstant::NUMERIC_ONE];
        } else {
            $code = AppConstant::NUMERIC_ZERO;
        }
        if ($params['ImportStudentForm']['sectionValue'] == AppConstant::NUMERIC_ONE) {
            $section = $params['secval'];
        } else if ($params['ImportStudentForm']['sectionValue'] == AppConstant::NUMERIC_TWO) {
            $section = $data[$params['seccol'] - AppConstant::NUMERIC_ONE];
        } else {
            $section = AppConstant::NUMERIC_ZERO;
        }
        if ($params['ImportStudentForm']['setPassword'] == AppConstant::NUMERIC_THREE) {
            $password = $data[$params['pwcol'] - AppConstant::NUMERIC_ONE];
        } else {
            $password = AppConstant::NUMERIC_ZERO;
        }

        return array($username, $firstname, $lastname, $email, $code, $section, $password);
    }

    public function actionShowImportStudent()
    {
        global $a;
        $this->guestUserHandler();
        $studentInformation = $this->getRequestParams();
        $isCodePresent = false;
        $isSectionPresent = false;
        $newStudents = array();
        $existingStudent = array();
        $existingStudentUsernameArray = array();
        $courseId = $studentInformation['courseId'];
        $existingUser = $studentInformation['existingUser'];
        $newUser = $studentInformation['newUser'];
        $course = Course::getById($courseId);
        if ($existingUser) {
            foreach ($existingUser as $singleExistingStudent) {
                array_push($existingStudentUsernameArray, $singleExistingStudent['userName']);
            }

            $tempArrayForExistingStudent = array();
            $uniqueStudentsForExistingStudent = array();
            foreach ($studentInformation['existingUser'] as $singleStudent) {
                if (!in_array($singleStudent['userName'], $tempArrayForExistingStudent)) {
                    array_push($uniqueStudentsForExistingStudent, $singleStudent);
                    array_push($tempArrayForExistingStudent, $singleStudent['userName']);
                }
            }
        }

        if ($newUser) {
            foreach ($newUser as $student) {
                if (!empty($student['4'])) {
                    $isCodePresent = true;
                }
                if (!empty($student['5'])) {
                    $isSectionPresent = true;
                }
            }
            $tempArrayForNewStudent = array();
            $uniqueStudentsForNewStudent = array();
            $duplicateStudentsForNewStudent = array();
            foreach ($newUser as $singleStudent) {
                if (!in_array($singleStudent[0], $tempArrayForNewStudent)) {
                    array_push($uniqueStudentsForNewStudent, $singleStudent);
                    array_push($tempArrayForNewStudent, $singleStudent[0]);
                } else {
                    array_push($duplicateStudentsForNewStudent, $singleStudent);
                }
            }
            if (count($uniqueStudentsForNewStudent) == 0) {
                $this->setErrorFlash(AppConstant::RECORD_EXISTS);
            }
        }else{
            $this->setSuccessFlash(AppConstant::STUDENT_EXISTS);
            return $this->redirect('import-student?cid='.$courseId);
        }
 
        $this->includeCSS(['dataTables.bootstrap.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'roster/importstudent.js', 'general.js']);
        $responseData = array( 'uniqueStudents' => $uniqueStudentsForNewStudent,'existingStudent' => $uniqueStudentsForExistingStudent, 'isSectionPresent' => $isSectionPresent, 'isCodePresent' => $isCodePresent,'duplicateStudents' => $duplicateStudentsForNewStudent,'course' => $course);
        return $this->render('showImportStudent', $responseData);
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
        $this->guestUserHandler();
        $this->layout = "master";
        if ($this->isPost()) {
            $selectedStudents = $this->getRequestParams();
            $isGradebook = $selectedStudents['gradebook'];
            $emailSender = $this->getAuthenticatedUser();
            $isActionForEmail = isset($selectedStudents['isEmail']) ? $selectedStudents['isEmail'] : AppConstant::NUMERIC_ZERO;
            $courseId = isset($selectedStudents['course-id']) ? $selectedStudents['course-id'] : '';
            if (!$isActionForEmail) {
                $course = Course::getById($courseId);
                $assessments = Assessments::getByCourseId($courseId);
                if ($selectedStudents['student-data'] != '') {
                    $selectedStudents = array_unique(explode(',', $selectedStudents['student-data']));
                    $studentArray = array();
                    foreach ($selectedStudents as $studentId) {
                        $student = User::getById($studentId);
                        array_push($studentArray, $student->attributes);
                    }
                    $this->includeCSS(['roster/roster.css']);
                    $this->includeJS(['roster/rosterEmail.js', 'editor/tiny_mce.js', 'editor/tiny_mce_src.js', '', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
                    $responseData = array('assessments' => $assessments, 'studentDetails' => serialize($studentArray), 'course' => $course,  'gradebook' => $isGradebook);
                    return $this->renderWithData('rosterEmail', $responseData);
                } else {
                    if($isGradebook == AppConstant::NUMERIC_ONE){
                        return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$courseId));
                    } else {
                        return $this->redirect('student-roster?cid=' . $courseId);
                    }
                }
            } else {
                $students = array();
                $studentArray = array();
                $sendToStudents = array();
                $filteredStudents = array();
                $studentsInfo = unserialize($selectedStudents['studentInformation']);

                if ($selectedStudents['roster-assessment-data'] != AppConstant::NUMERIC_ZERO) {
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

                        $tempArray = array(
                            'firstName' => $student['FirstName'],
                            'lastName' => $student['LastName'],
                            'emailId' => $student['email'],
                            'userId' => $student['id'],
                        );
                        array_push($studentArray, $tempArray);
                        $sendto = trim(ucfirst($student['LastName']) . ', ' . ucfirst($student['FirstName']));
                        array_push($sendToStudents, $sendto);
                    }
                }
                $toList = implode("<br>", $sendToStudents);
                $message = $selectedStudents['message'];
                $subject = $selectedStudents['subject'];
                $courseId = $selectedStudents['courseId'];
                $course = Course::getById($courseId);
                $messageToTeacher = $message . addslashes("<p>Instructor note: Email sent to these students from course $course->name: <br>$toList\n");
                if ($selectedStudents['emailCopyToSend'] == 'singleStudent') {
                    $this->sendEmailToSelectedUser($subject, $message, $studentArray);
                } elseif ($selectedStudents['emailCopyToSend'] == 'selfStudent') {
                    AppUtility::sendMail($subject, $messageToTeacher, $emailSender['email']);
                    $this->sendEmailToSelectedUser($subject, $message, $studentArray);
                } elseif ($selectedStudents['emailCopyToSend'] == 'allTeacher') {
                    $instructors = Teacher::getTeachersById($selectedStudents['courseId']);
                    foreach ($instructors as $instructor) {
                        AppUtility::sendMail($subject, $messageToTeacher, $instructor->user->email);
                    }
                    $this->sendEmailToSelectedUser($subject, $message, $studentArray);
                }
                if($isGradebook == AppConstant::NUMERIC_ONE){
                    return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$courseId));
                } else {
                    return $this->redirect('student-roster?cid=' . $courseId);
                }
            }
        } else {
            $courseId = $this->getParamVal('cid');
            $isGradebook = $this->getParamVal('gradebook');
            if($isGradebook == AppConstant::NUMERIC_ONE){
                return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$courseId));
            } else {
                return $this->redirect('student-roster?cid=' . $courseId);
            }
        }
    }

    public function sendEmailToSelectedUser($subject, $message, $studentArray)
    {
        $this->guestUserHandler();
        foreach ($studentArray as $singleStudent) {
            AppUtility::sendMail($subject, $message, $singleStudent['emailId']);
        }
    }
    public function actionUnenroll(){
        $this->guestUserHandler();
        $this->layout = "master";
        $params = $this->getRequestParams();
        if(isset($params['lockinstead'])){
            $toLock = array_unique(explode(',', $params['studentData']));
            foreach ($toLock as $student) {
                Student::updateLocked($student, $params['cid']);
            }
            if(isset($params['gradebook'])){
                return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$params['cid']));
            }else{
                return $this->redirect(AppUtility::getURLFromHome('roster', 'roster/student-roster?cid='.$params['cid']));
            }
        }else if(isset($params['confirmed'])){
            $connection = $this->getDatabase();
            $transaction = $connection->beginTransaction();
            try {
            StudentUnenrollUtility::unenrollStudent($params);
                $transaction->commit();
            }catch (Exception $e){
                $transaction->rollBack();
                return false;
            }
            if(isset($params['gradebook'])){
                return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$params['cid']));
            }else{
                return $this->redirect(AppUtility::getURLFromHome('roster', 'roster/student-roster?cid='.$params['cid']));
            }
        }
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $studentData = array_unique(explode(',',$params['student-data']));
        $students = array();
        $delForumMsg = 0;
        $delWikiMsg = 0;
        foreach($studentData as $student){
            $query = User::findAllById($student);
            array_push($students, $query[0]);
        }
        $sort_by = array_column($students, 'LastName');
        array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $students);
        $users = Student::findByCid($courseId);
        if(count($students) == count($users)){
            $studentId = 'all';
        }else{
            $studentId = 'selected';
        }
        if($studentId == 'selected'){
            if(count($studentData) > floor(count($users)/2)){
                $delForumMsg = 1;
                $delWikiMsg = 1;
            }else{
                $delForumMsg = 0;
                $delWikiMsg = 0;
            }
        }
        $gradebook = 0;
        if(isset($params['gradebook'])){
            $gradebook = 1;
        }
        $responseData = array('students' => $students, 'studentId' => $studentId, 'course' => $course, 'delForumMsg' => $delForumMsg, 'delWikiMsg' =>  $delWikiMsg, 'gradebook' => $gradebook);
        $this->includeCSS(['roster/roster.css']);
        return $this->renderWithData('unenroll', $responseData);
    }
    public function actionRosterMessage()
    {

        if ($this->isPost()) {
            $this->guestUserHandler();
            $this->layout = "master";
            $selectedStudents = $this->getRequestParams();
            $isGradebook = $selectedStudents['gradebook'];
            $isActionForMessage = isset($selectedStudents['isMessage']) ? $selectedStudents['isMessage'] : AppConstant::NUMERIC_ZERO;
            $courseId = isset($selectedStudents['course-id']) ? $selectedStudents['course-id'] : '';
            if (!$isActionForMessage) {
                $course = Course::getById($courseId);
                $assessments = Assessments::getByCourseId($courseId);
                if ($selectedStudents['student-data'] != '') {
                    $selectedStudents = array_unique(explode(',', $selectedStudents['student-data']));
                    $studentArray = array();
                    foreach ($selectedStudents as $studentId) {
                        $student = User::getById($studentId);
                        array_push($studentArray, $student->attributes);
                    }
                    $this->includeCSS(['roster/roster.css']);
                    $this->includeJS(['roster/rosterMessage.js', 'editor/tiny_mce.js', 'editor/tiny_mce_src.js', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
                    $responseData = array('assessments' => $assessments, 'studentDetails' => serialize($studentArray), 'course' => $course, 'gradebook' => $isGradebook);
                    return $this->renderWithData('rosterMessage', $responseData);
                } else {
                    if($isGradebook == AppConstant::NUMERIC_ONE){
                        return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$courseId));
                    } else {
                        return $this->redirect('student-roster?cid=' . $courseId);
                    }
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
                    $notSaved = AppConstant::NUMERIC_FOUR;
                }
                $save = AppConstant::NUMERIC_ONE;
                if ($selectedStudents['roster-assessment-data'] != AppConstant::NUMERIC_ZERO) {
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
                } elseif ($selectedStudents['messageCopyToSend'] == 'selfAndStudents') {
                    foreach ($filteredStudents as $singleStudent) {
                        $this->sendMassMessage($courseId, $singleStudent, $subject, $messageBody, $notSaved);
                    }
                    $messageToTeacher = $messageBody . addslashes("<p>Instructor note: Message sent to these students from course $course->name: <br>$toList\n");
                    $this->sendMassMessage($courseId, $user->id, $subject, $messageToTeacher, $save);
                } elseif ($selectedStudents['messageCopyToSend'] == 'teachersAndStudents') {
                    foreach ($filteredStudents as $singleStudent) {
                        $this->sendMassMessage($courseId, $singleStudent, $subject, $messageBody, $notSaved);
                    }
                    $teachers = Teacher::getAllTeachers($courseId);
                    foreach ($teachers as $teacher) {
                        $messageToTeacher = $messageBody . addslashes("<p>Instructor note: Message sent to these students from course $course->name: <br>$toList\n");
                        $this->sendMassMessage($courseId, $teacher['userid'], $subject, $messageToTeacher, $save);
                    }
                }
                if($isGradebook == AppConstant::NUMERIC_ONE){
                    return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$courseId));
                } else {
                    return $this->redirect('student-roster?cid=' . $courseId);
                }
            }
        } else {
            $courseId = $this->getParamVal('cid');
            $isGradebook = $this->getParamVal('gradebook');
            if($isGradebook == AppConstant::NUMERIC_ONE){
                return $this->redirect(AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid='.$courseId));
            } else {
                return $this->redirect('student-roster?cid=' . $courseId);
            }
        }
    }

    public function sendMassMessage($courseId, $receiver, $subject, $messageBody, $isRead)
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $tempArray = array('cid' => $courseId, 'receiver' => $receiver, 'subject' => $subject, 'body' => $messageBody, 'isread' => $isRead);
        $message = new Message();
        $message->create($tempArray, $user->id);
    }

//Controller method to make exception
    public function actionMakeException()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        if ($this->getRequestParams()) {
            $data = $this->getRequestParams();
            $gradebook = AppConstant::NUMERIC_ZERO;
            if(isset($data['gradebook'])){
                $gradebook = AppConstant::NUMERIC_ONE;
            }
            $courseId = $this->getParamVal('cid');
            $course = Course::getById($courseId);
            $assessments = Assessments::getByCourseId($courseId);
            $studentList = array();

            if($gradebook)
            {
               array_push($studentList,$data['studentId']);
                $data['student-data'] =  $data['studentId'];
                $params['student-data'] =  $data['studentId'];
                $assesschk = $_SESSION['assesschk'];
            }else{
                $studentList = array_unique(explode(',', $data['student-data']));
            }
            $studentArray = array();
            if ($this->isPost() || $data['student-data'] != "") {
                $params = $this->getRequestParams();
                if($gradebook)
                {
                     $params['student-data'] =  $data['studentId'];
                     $params['section-data'] =  $data['section-data'];
                }
                $section = $params['section-data'];
                $isActionForException = isset($params['isException']) ? $params['isException'] : AppConstant::NUMERIC_ZERO;
                if (!$isActionForException) {
                    if ($params['student-data'] != '') {
                        foreach ($studentList as $studentId) {
                            $student = User::getById($studentId);
                            array_push($studentArray, $student->attributes);
                        }
                        $exceptionArray = $this->createExceptionList($studentArray, $assessments);
                        $latePassMsg = $this->findLatepassMsg($studentArray, $courseId);
                    } else {

                        return $this->redirect('student-roster?cid=' . $courseId);
                    }

                } else {
                    $studentArray = unserialize($params['studentInformation']);
                    $courseId = $params['courseid'];
                    $course = Course::getById($courseId);
                    $assessments = Assessments::getByCourseId($courseId);
                    $section = $params['section'];
                    if ($params['clears']) {
                        foreach ($params['clears'] as $clearEntry) {
                            Exceptions::deleteExceptionById($clearEntry);
                        }
                    }
                    if (isset($params['addExc'])) {
                        $startException = strtotime($params['startDate'] . ' ' . $params['startTime']);
                        $endException = strtotime($params['endDate'] . ' ' . $params['endTime']);
                        if($startException > $endException){
                            $this->setErrorFlash(AppConstant::GREATER_THEN_END_DATE);
                        }else{
                            $waiveReqScore = (isset($params['waiveReqScore'])) ? AppConstant::NUMERIC_ONE : AppConstant::NUMERIC_ZERO;
                            foreach ($studentArray as $student) {
                                foreach ($params['addExc'] as $assessment) {
                                    $presentException = Exceptions::getByAssessmentIdAndUserId($student['id'], $assessment);
                                    if ($presentException) {
                                        Exceptions::modifyExistingException($student['id'], $assessment, $startException, $endException, $waiveReqScore);
                                    } else {
                                        $param = array('userid' => $student['id'], 'assessmentid' => $assessment, 'startdate' => $startException, 'enddate' => $endException, 'waivereqscore' => $waiveReqScore);
                                        $exception = new Exceptions();
                                        $exception->create($param);
                                    }
                                    if (isset($params['forceReGen'])) {
                                        $query = AssessmentSession::getAssessmentSession($student['id'], $assessment);
                                        if ($query) {
                                            if (strpos($query->questions, ';') === false) {
                                                $questions = explode(",", $query->questions);
                                            } else {
                                                list($questions, $bestquestions) = explode(";", $query->questions);
                                                $questions = explode(",", $query->questions);
                                            }
                                            $lastAnswers = explode('~', $query->lastanswers);
                                            $curScoreList = $query->scores;
                                            $scores = array();
                                            $attempts = array();
                                            $seeds = array();
                                            $reattempting = array();
                                            for ($i = 0; $i < count($questions); $i++) {
                                                $scores[$i] = AppConstant::NUMERIC_NEGATIVE_ONE;
                                                $attempts[$i] = AppConstant::NUMERIC_ZERO;
                                                $seeds[$i] = rand(1, 9999);
                                                $newLastAns = array();
                                                $lastArr = explode('##', $lastAnswers[$i]);
                                                foreach ($lastArr as $lastElement) {
                                                    if ($lastElement == "ReGen") {
                                                        $newLastAns[] = "ReGen";
                                                    }
                                                }
                                                $newLastAns[] = "ReGen";
                                                $lastAnswers[$i] = implode('##', $newLastAns);
                                            }
                                            $scoreList = implode(',', $scores);
                                            if (strpos($curScoreList, ';') !== false) {
                                                $scoreList = $scoreList . ';' . $scoreList;
                                            }
                                            $attemptsList = implode(',', $attempts);
                                            $seedsList = implode(',', $seeds);
                                            $lastAnswers = str_replace('~', '', $lastAnswers);
                                            $lastAnswersList = implode('~', $lastAnswers);
                                            $lastAnswersList = addslashes(stripslashes($lastAnswersList));
                                            $reattemptingList = implode(',', $reattempting);
                                            $finalParams = Array('id' => $query->id, 'scores' => $scoreList, 'attempts' => $attemptsList, 'seeds' => $seedsList, 'lastanswers' => $lastAnswersList, 'reattempting' => $reattemptingList);
                                            AssessmentSession::modifyExistingSession($finalParams);
                                        }
                                    } elseif (isset($params['forceClear'])) {
                                        AssessmentSession::removeByUserIdAndAssessmentId($student['id'], $assessment);
                                    }
                                }
                            }
                            if (isset($params['eatLatePass'])) {
                                $n = intval($params['latePassN']);
                                foreach ($studentArray as $student) {
                                    Student::reduceLatepasses($student['id'], $courseId, $n);
                                }
                            }
                            if (isset($params['sendMsg'])) {
                                $this->includeJS(['roster/rosterMessage.js', 'editor/tiny_mce.js', 'editor/tiny_mce_src.js', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
                                $responseData = array('assessments' => $assessments, 'studentDetails' => serialize($studentArray), 'course' => $course, 'gradebook' => $gradebook);
                                return $this->renderWithData('rosterMessage', $responseData);
                            }
                        }
                    }
                    $exceptionArray = $this->createExceptionList($studentArray, $assessments);
                    $latePassMsg = $this->findLatepassMsg($studentArray, $courseId);
                }
                $sort_by = array_column($exceptionArray, 'Name');
                array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $exceptionArray);
                $sort_by = array_column($studentArray, 'LastName');
                array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $studentArray);
            }

            $this->includeCSS(['roster/roster.css']);
            $this->includeJS(['roster/makeException.js']);
            $responseData = array('assessments' => $assessments,'assesschk' => $assesschk,'gradebook' => $gradebook, 'studentDetails' => serialize($studentArray), 'course' => $course, 'existingExceptions' => $exceptionArray, 'section' => $section, 'latePassMsg' => $latePassMsg);
            if(count($studentArray) > AppConstant::NUMERIC_ZERO){

                return $this->renderWithData('makeException', $responseData);
            } else {

                $this->redirect(AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id));
            }
        } else {
            $this->setErrorFlash(AppConstant::NO_USER_FOUND);
        }
    }

    public function  createExceptionList($studentArray, $assessments)
    {
        $this->guestUserHandler();
        $exceptionArray = array();
        foreach ($studentArray as $student) {
            $exceptionList = array();
            foreach ($assessments as $singleAssessment) {
                $query = Exceptions::getByAssessmentIdAndUserId($student['id'], $singleAssessment['id']);
                if ($query) {
                    $tempArray = array('assessmentName' => $singleAssessment->name, 'exceptionId' => $query->id, 'exceptionDate' => date('m/d/y g:i a', $query->startdate) . ' - ' . date('m/d/y g:i a', $query->enddate), 'waiveReqScore' => $query->waivereqscore);
                    array_push($exceptionList, $tempArray);
                }
            }
            $sort_by = array_column($exceptionList, 'assessmentName');
            array_multisort($sort_by, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $exceptionList);
            if ($exceptionList) {
                $assessmentList = array('Name' => ucfirst($student['LastName']) . ', ' . ucfirst($student['FirstName']), 'assessments' => $exceptionList);
                array_push($exceptionArray, $assessmentList);
            }
        }
        return $exceptionArray;
    }

    public function  findLatepassMsg($studentArray, $courseId)
    {
        $this->guestUserHandler();
        $studentRecord = array();
        foreach ($studentArray as $student) {
            array_push($studentRecord, Student::getByCourseId($courseId, $student['id']));

        }
        $latePassMin = $studentRecord[0]->latepass;
        $latePassMax = $studentRecord[0]->latepass;
        foreach ($studentRecord as $record) {
            if ($record->latepass < $latePassMin) {
                $latePassMin = $record->latepass;
            }
            if ($record->latepass > $latePassMax) {
                $latePassMax = $record->latepass;
            }
        }
        if (count($studentRecord) < AppConstant::NUMERIC_TWO) {
            $latePassMsg = "This student has $latePassMin latepasses.";
        } elseif ($latePassMin == $latePassMax) {
            $latePassMsg = sprintf(AppConstant::MIN_LATEPASS,$latePassMin);
        } else {
            $latePassMsg = sprintf(AppConstant::MIN_MAX_LATEPASS,$latePassMin,$latePassMax);
        }
        return $latePassMsg;
    }

    public function actionSaveCsvFileAjax()
    {
        $params = $this->getRequestParams();
        $studentData = $params['studentData'];
        $courseId = $params['courseId'];
        if ($studentData)
        {
            foreach ($studentData as $newEntry)
            {
                $user = new User();
                $id = $user->addUser($newEntry[0], $newEntry[7], AppConstant::STUDENT_RIGHT, $newEntry[1], $newEntry[2], $newEntry[3], time());
                $sectionAndCodeValue = array(
                    'section' => $newEntry['5'],
                    'code' => $newEntry['4'],
                );
                $student = new Student();
                $student->createNewStudent($id,$courseId,$sectionAndCodeValue);
            }
            $this->setSuccessFlash(AppConstant::IMPORTED_SUCCESSFULLY);
        }
        else {
            $this->setSuccessFlash(AppConstant::STUDENT_EXISTS);
        }
        return $this->successResponse();
    }

    public function actionCopyStudentEmail()
    {
        if ($this->isPost()) {
            $this->guestUserHandler();
            $this->layout = "master";
            $selectedStudents = $this->getRequestParams();
            $isGradebook = $selectedStudents['gradebook'];
            $selectedStudentId = array_unique(explode(',', $selectedStudents['student-data']));
            $courseId = isset($selectedStudents['course-id']) ? $selectedStudents['course-id'] : '';
            $course = Course::getById($courseId);
            $studentArray = array();
            $studentData = array();
            foreach ($selectedStudentId as $studentId) {
                $student = User::getById($studentId);
                array_push($studentArray, $student);
            }
            foreach ($studentArray as $student) {
                $studentData[] = trim(ucfirst($student['LastName']).' '.ucfirst($student['FirstName'])). ' &lt;'.$student['email'].'&gt;';
            }
            $studentList = implode('; ',$studentData);
            $responseData = array('studentData' => $studentList, 'course' => $course, 'gradebook' => $isGradebook);
            $this->includeJS(['general.js']);
            $this->includeCSS(['roster/roster.css']);
            return $this->renderWithData('copyStudentEmail', $responseData);
        } else {
            $courseId = $this->getParamVal('cid');
            return $this->redirect('student-roster?cid=' . $courseId);
        }
    }

    public function actionLoginLog()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $userId = $this->getParamVal('uid');
        $from = $this->getParamVal('from');
        $userData = User::getById($userId);
        $userFullName = trim(ucfirst($userData['LastName']) . ", " . ucfirst($userData['FirstName']));
        $course = Course::getById($courseId);
        $orderBy = 'logintime';
        $sortBy = AppConstant::DESCENDING;
        $loginLog = LoginLog::getByCourseIdAndUserId($courseId, $userId, $orderBy, $sortBy);
        $loginLogData = array();
        foreach ($loginLog as $log) {
            $tempArray = array(
                'logDateTime' => AppUtility::tzdate('l, F j, Y, g:i a', $log['logintime']),
                'action' => $log['lastaction'],
            );
            array_push($loginLogData, $tempArray);
        }
        $this->includeJS(['jquery.dataTables.min.js','dataTables.bootstrap.js','roster/activityLog.js']);
        $this->includeCSS(['roster/roster.css','dataTables.bootstrap.css']);
        $responseData = array('course' => $course, 'userFullName' => $userFullName, 'lastlogin' => $loginLogData, 'userId' => $userId,'from' => $from);
        return $this->renderWithData('loginLog', $responseData);
    }

    public function actionActivityLog()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $from = $this->getParamVal('from');
        $isTeacher = $this->isTeacher($user['id'],$courseId);
        $isTutor = $this->isTutor($user['id'],$courseId);
        if (!isset($isTeacher) && !isset($isTutor)) {
            $userId = $user['id'];
        } else {
            $userId = intval($this->getParamVal('uid'));
        }
        $userData = User::getById($userId);
        $userFullName = trim(ucfirst($userData['LastName']) . ", " . ucfirst($userData['FirstName']));
        $course = Course::getById($courseId);
        $actions = array();
        $actionsArray = array('as' => array(), 'in' => array(), 'li' => array(), 'ex' => array(), 'wi' => array(), 'fo' => array(), 'forums' => array());
        $orderBy = 'viewtime';
        $sortBy = AppConstant::DESCENDING;
        $loginLog = ActivityLog::getByCourseIdAndUserId($courseId, $userId, $orderBy, $sortBy);
        foreach ($loginLog as $log) {
            $actions = $loginLog;
            $subType = substr($log['type'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_TWO);
            $actionsArray[$subType][] = intval($log['typeid']);

            if ($subType == 'fo') {
                $ip = explode(';', $log['info']);
                $actionsArray['forums'][] = $ip[0];
            }
        }
        $assessmentName = array();
        if (count($actionsArray['as']) > AppConstant::NUMERIC_ZERO) {
            $assessmentIds = array_unique($actionsArray['as']);
            foreach ($assessmentIds as $id) {
                $query = Assessments::getByAssessmentId($id);
                $assessmentName[$query['id']] = $query['name'];
            }
        }
        $inlineTextName = array();
        if (count($actionsArray['in']) > AppConstant::NUMERIC_ZERO) {
            $inlineTextIds = array_unique($actionsArray['in']);
            foreach ($inlineTextIds as $id) {
                $query = InlineText::getById($id);
                $inlineTextName[$query['id']] = $query['title'];
            }
        }
        $linkName = array();
        if (count($actionsArray['li']) > AppConstant::NUMERIC_ZERO) {
            $linkTextIds = array_unique($actionsArray['li']);
            foreach ($linkTextIds as $id) {
                $query = Links::getById($id);
                $linkName[$query['id']] = $query['title'];
            }
        }
        $wikiName = array();
        if (count($actionsArray['wi']) > AppConstant::NUMERIC_ZERO) {
            $wikiIds = array_unique($actionsArray['wi']);
            foreach ($wikiIds as $id) {
                $query = Wiki::getById($id);
                $wikiName[$query['id']] = $query['name'];
            }
        }
        $exnames = array();
        if (count($actionsArray['ex']) > AppConstant::NUMERIC_ZERO) {
            $extraCredit = array_unique($actionsArray['ex']);
            foreach ($extraCredit as $id) {
                $query = Questions::getById($id);
                $exnames[$query['id']] = $query['assessmentid'];
            }
        }
        $forumPostName = array();
        if (count($actionsArray['fo']) > AppConstant::NUMERIC_ZERO) {
            $forumPosts = array_unique($actionsArray['fo']);
            foreach ($forumPosts as $id) {
                $query = ForumPosts::getPostById($id);
                $forumPostName[$query['id']] = $query['subject'];
            }
        }
        $forumName = array();
        if (count($actionsArray['forums']) > AppConstant::NUMERIC_ZERO) {
            $forums = array_unique($actionsArray['fo']);
            foreach ($forums as $id) {
                $query = Forums::getById($id);
                $forumName[$query['id']] = $query['name'];
            }
        }
        $this->includeJS(['jquery.dataTables.min.js','dataTables.bootstrap.js','roster/activityLog.js']);
        $this->includeCSS(['roster/roster.css','dataTables.bootstrap.css']);
        $responseData = array('course' => $course, 'userFullName' => $userFullName, 'userId' => $userId, 'exnames' => $exnames,
            'forumPostName' => $forumPostName, 'actions' => $actions, 'assessmentName' => $assessmentName, 'inlineTextName' => $inlineTextName,
            'linkName' => $linkName, 'wikiName' => $wikiName, 'forumName' => $forumName, 'from' => $from);
        return $this->renderWithData('activityLog', $responseData);
    }

    public function actionLockUnlockAjax()
    {
        $params = $this->getRequestParams();
        Student::updateLockOrUnlockStudent($params);
    }

    public function actionChangeStudentInformation()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $tzName = AppUtility::getTimezoneName();
        $params = $this->getRequestParams();
        $userId = $params['uid'];
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $studentData = Student::getByCourseId($courseId, $userId);
        $user = User::findByUserId($userId);
        $model = new ChangeUserInfoForm();
        if ($model->load($this->isPostMethod())) {
            $params = $params['ChangeUserInfoForm'];
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $model->file->saveAs(AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg');
                $model->remove = AppConstant::NUMERIC_ZERO;

                if (AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg') {
                    User::updateImgByUserId($userId);
                }
            }
            if ($model->remove == AppConstant::NUMERIC_ONE) {
                User::deleteImgByUserId($userId);
                unlink(AppConstant::UPLOAD_DIRECTORY . $user->id . '.jpg');
            }
            User::saveUserRecord($params, $user);
            Student::updateSectionAndCodeValue($params['section'], $userId, $params['code'], $courseId, $params);
            $this->setSuccessFlash(AppConstant::UPDATE_STUDENT_SUCCESSFULLY);
            return $this->redirect('student-roster?cid=' . $courseId);
        }
        $this->includeCSS(['dashboard.css', 'roster/roster.css']);
        $this->includeJS(['changeUserInfo.js']);
        $responseData = array('model' => $model, 'user' => $user->attributes, 'tzname' => $tzName, 'userId' => $userId, 'studentData' => $studentData, 'courseId' => $courseId, 'course' => $course);
        return $this->renderWithData('changeStudentInformation', $responseData);
    }
}
