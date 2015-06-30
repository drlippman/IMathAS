<?php

namespace app\controllers\gradebook;


use app\components\AppUtility;
use app\models\_base\BaseImasDiags;
use app\models\_base\BaseImasGrades;
use app\models\Assessments;
use app\models\Course;
use app\models\Diags;
use app\models\forms\AddGradesForm;
use app\models\forms\AddRubricForm;
use app\models\forms\ManageTutorsForm;
use app\models\GbItems;
use app\models\GbScheme;
use app\models\Grades;
use app\models\Items;
use app\models\loginTime;
use app\models\Rubrics;
use app\models\Student;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use Yii;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use yii\rbac\Item;

class GradebookController extends AppController
{
    public function actionGradebook()
    {
        $user = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $this->includeJS(['gradebook/gradebook.js']);
        $responseData = array('course' => $course, 'user' => $user);
        return $this->renderWithData('gradebook', $responseData);
    }
    public function actionDisplayGradebookAjax()
    {
        $params = $this->getRequestParams();
        $teacherid = Teacher::getByUserId($params['userId'], $params['courseId']);
        $tutorid = Tutor::getByUserId($params['userId'], $params['courseId']);
        $tutorsection = trim($tutorid->section);
        if(isset($teacherid)){
            $isteacher = true;
        }
        if(isset($tutorid)){
            $istutor = true;
        }
        if ($isteacher || $istutor) {
            $canviewall = true;
        } else {
            $canviewall = false;
        }
        if($canviewall){
            $defgbmode = GbScheme::findOne(['courseid' => $params['courseId']]);
            $gbmode = $defgbmode->defgbmode;
            $colorized = $defgbmode->colorize;
            $catfilter = -1;
            if(isset($tutorsection) && $tutorsection != ''){
                $secfilter = $tutorsection;
            }else{
                $secfilter = -1;
            }
            $overridecollapse = array();

    //Gbmode : Links NC Dates

            $showpics = floor($gbmode/10000)%10 ; //0 none, 1 small, 2 big
            $totonleft = ((floor($gbmode/1000)%10)&1) ; //0 right, 1 left
            $avgontop = ((floor($gbmode/1000)%10)&2) ; //0 bottom, 2 top
            $lastlogin = (((floor($gbmode/1000)%10)&4)==4) ; //0 hide, 2 show last login column
            $links = ((floor($gbmode/100)%10)&1); //0: view/edit, 1 q breakdown
            $hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
            $includeduedate = (((floor($gbmode/100)%10)&4)==4); //0: hide due date, 4: show due date
            $hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
            $includelastchange = (((floor($gbmode/10)%10)&4)==4);  //: hide last change, 4: show last change
            $availshow = $gbmode%10; //0: past, 1 past&cur, 2 all, 3 past and attempted, 4=current only

        }else{
            $secfilter = -1;
            $catfilter = -1;
            $links = 0;
            $hidenc = 1;
            $availshow = 1;
            $showpics = 0;
            $totonleft = 0;
            $avgontop = 0;
            $hidelocked = 0;
            $lastlogin = false;
            $includeduedate = false;
            $includelastchange = false;
        }
        $stu = 0;

        $isdiag = false;
        if ($canviewall) {
            $query =Diags::findOne(['cid' => $params['courseId']]);
            if($query){
                $isdiag = true;
                $sel1name = $query->sel1name;
                $sel2name = $query->sel2name;
            }
        }
        if ($canviewall && func_num_args()>0) {
            $limuser = func_get_arg(0);
        } else if (!$canviewall) {
            $limuser = $params['userId'];
        } else {
            $limuser = 0;
        }
        if (!isset($lastlogin)) {
            $lastlogin = 0;
        }
        if (!isset($logincnt)) {
            $logincnt = 0;
        }
        $category = array();
        $gb = array();

        $ln = 0;

    //Pull Gradebook Scheme info
        $query = GbScheme::findOne(['courseid' => $params['courseId']]);
        $useweights = $query->useweights;
        $orderby = $query->orderby;
        $defaultcat = $query->defaultcat;
        $usersort = $query->usersort;
        if($useweights == 2){
            $useweights = 0;                //use 0 mode for calculation of totals
        }
        if(isset($GLOBALS['setorderby'])){
            $orderby = $GLOBALS['setorderby'];
        }

    //Build user ID headers

        $gb[0][0][0] = "Name";
        if ($isdiag) {
            $gb[0][0][1] = "ID";
            $gb[0][0][2] = "Term";
            $gb[0][0][3] = ucfirst($sel1name);
            $gb[0][0][4] = ucfirst($sel2name);
        } else {
            $gb[0][0][1] = "Username";
        }
        $query = Student::findByCid($params['courseId']);
        if($query){
            $countSection = 0;
            $countCode = 0;
            foreach($query as $singleData){
                if($singleData->section != null || $singleData->section != ""){
                    $countSection++;
                }
                if($singleData->code != null || $singleData->code != ""){
                    $countCode++;
                }
            }
        }
        if($countSection > 0){
            $hassection = true;
        }else{
            $hassection = false;
        }
        if($countCode > 0){
            $hascode = true;
        }else{
            $hascode = false;
        }

        if ($hassection && !$isdiag) {
            $gb[0][0][] = "Section";
        }
        if ($hascode) {
            $gb[0][0][] = "Code";
        }
        if ($lastlogin) {
            $gb[0][0][] = "Last Login";
        }
        if ($logincnt) {
            $gb[0][0][] = "Login Count";
        }

    //orderby 10: course order (11 cat first), 12: course order rev (13 cat first)
        if ($orderby>=10 && $orderby <=13) {
            $query = Course::getById($params['courseId']);
            if($query){
                $courseitemorder = unserialize($query->itemorder);
                $courseitemsimporder = array();
                function flattenitems($items,&$addto) {
                    foreach ($items as $item) {
                        if (is_array($item)) {
                            flattenitems($item['items'],$addto);
                        } else {
                            $addto[] = $item;
                        }
                    }
                }
                flattenitems($courseitemorder,$courseitemsimporder);
                $courseitemsimporder = array_flip($courseitemsimporder);
                $courseitemsassoc = array();
                $query = Items::getByCourseId($params['courseId']);
                if($query){
                    foreach($query as $item){
                        if(!isset($courseitemsimporder[$item->id])){
                            $courseitemsassoc[$item->itemtype.$item->typeid] = 999 + count($courseitemsassoc);
                        }else{
                            $courseitemsassoc[$item->itemtype.$item->typeid] = $courseitemsimporder[$item->id];
                        }
                    }
                }
            }
        }

        AppUtility::dump($gb);


        $query = Student::findByCid($params['courseId']);
        $studentInfo = array();
        foreach($query as $student){
            $user = User::findByUserId($student->userid);
            $tempUser = array($user->LastName.', '.$user->FirstName, $user->SID, $student->section, $student->code);
            $tempImageLockComment = array($user->id, $student->locked, $user->hasuserimg, !empty($student->gbcomment));

            $studentInfo = array($tempUser, $tempImageLockComment);
        }
        array_push($gradebooks, $studentInfo);

        AppUtility::dump($gradebooks[0]);
        return $this->successResponse();
    }
    public function actionAddGrades()
    {
        $model = new AddGradesForm();
        $this->guestUserHandler();
        $currentUser = $this->getAuthenticatedUser();
//        $user = $this->getAuthenticatedUser();
//        AppUtility::dump($currentUser['id']);
        $courseId = $this->getParamVal('cid');
        $studentData = Student::findByCid($courseId);
        $course = Course::getById($courseId);
        $rubricsData = Rubrics::getByUserId($currentUser['id']);
//        AppUtility::dump($rubricsData);
        $assessmentData = Assessments::getByCourseId($courseId);
//        AppUtility::dump($assessmentData);
        $studentArray = array();
        if ($studentData) {
            foreach ($studentData as $student) {
                $tempArray = array('Name' => ucfirst($student->user->FirstName). ' ' . ucfirst($student->user->LastName),
                    'Section' => $student->section,
                    'StudentId' => $student->id,
                    'userid' => $student->userid
                );
                array_push($studentArray, $tempArray);
            }
        }
        if($this->isPost()){
            $params = $_POST;
//            AppUtility::dump($params['rubric']);
            $gbItems = new GbItems();
            $gbItemsId = $gbItems->createGbItemsByCourseId($courseId,$params);
//            AppUtility::dump();
            if($params['grade_text'] || $params['feedback_text']){

                $gradeTextArray = array();
                foreach($params['grade_text'] as $index => $grade)
                {
                    foreach($params['feedback_text'] as $key => $feedback) {
                        if($index == $key){
                        $tempArray = array(
                            'studentId' => $index,
                            'gradeText' => $grade,
                            'feedbackText' => $feedback,
                        );
                        array_push($gradeTextArray, $tempArray);
                        }
                    }
                }
                foreach($gradeTextArray as $single){
                    $grades = new Grades();
                    $grades->createGradesByUserId($single,$gbItemsId);
                }

            }
            $responseData = array('course' => $course, 'user' => $currentUser);
            return $this->renderWithData('gradebook', $responseData);
        }
        $this->includeCSS(['dataTables.bootstrap.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js' ,'gradebook/addgrades.js','roster/managelatepasses.js']);
        $responseData = array('model' => $model,'studentInformation' => $studentArray,'course' => $course,'rubricsData' => $rubricsData,'assessmentData' => $assessmentData);
        return $this->renderWithData('addGrades', $responseData);

    }
    public function actionAddRubric()
    {
        $model = new AddRubricForm();
        $currentUser = $this->getAuthenticatedUser();
        $rubricId = $this->getParamVal('rubricId');
        $edit = false;
        $rubicData = Rubrics::getByUserIdAndRubricId($currentUser['id'],$rubricId);

        if($rubicData){
        $rubricItems = unserialize($rubicData['rubric']);
            $edit = true;
        }
        if($this->isPost()) {
            $params = $_POST;
            $rubricData = new Rubrics();
            $nonSerializeArrayOfData = array();

            $gradeTextArray = array();
            foreach($params['rubitem'] as $index => $feedback)
            {
                foreach($params['rubnote'] as $key => $note) {
                    if($index == $key){
                    foreach($params['feedback'] as $k => $percentage) {
                    if($k == $key){
                        $tempArray = array(
                            'feedback' => $feedback,
                            'note' => $note,
                            'percentage' => $percentage,
                        );
                        array_push($gradeTextArray, $tempArray);}
                    }}
                }

            }

//            array_push($nonSerializeArrayOfData,$params['rubitem']);
//            array_push($nonSerializeArrayOfData,$params['rubnote']);
//            array_push($nonSerializeArrayOfData,$params['feedback']);
            $rubricTextDataArray = serialize($gradeTextArray);
//            AppUtility::dump($gradeTextArray);
            $rubricData->createNewEntry($params,$currentUser['id'],$rubricTextDataArray);
        }
        $responseData = array('model' => $model,'rubricItems' => $rubricItems,'edit' => $edit);
        return $this->renderWithData('addRubric', $responseData);

    }
    public function actionEditRubric()
    {
        $currentUser = $this->getAuthenticatedUser();
        $rubicData = Rubrics::getByUserId($currentUser['id']);
        $responseData = array('rubicData' => $rubicData);
        return $this->renderWithData('editRubric', $responseData);

    }
    public function actionQuickSearchAjax()
    {
        $courseId = $this->getRequestParams();
        $studentInformation = Student::findByCid($courseId);
        $studentDetails = array();
        foreach($studentInformation as $singleStudentInformation){
//            AppUtility::dump($singleStudentInformation->user->grades->score);
            $tempArray = array(
                'id' => $singleStudentInformation->id,
                'value' => $singleStudentInformation->user->id,
                'section' => $singleStudentInformation->section,
                'label' =>ucfirst($singleStudentInformation->user->FirstName).''.ucfirst($singleStudentInformation->user->LastName),

//                'grade' => $singleStudentInformation->grades->score,
//                'feedback' => $singleStudentInformation->grade->feedback,

            );
//            $studentData = User::findByCidAndName($text,$singleStudentInformation['userid']);
            array_push($studentDetails,$tempArray);
        }
        $responseData = $studentDetails;
        return $this->successResponse($responseData);
    }
}