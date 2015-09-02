<?php

namespace app\controllers\admin;

use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use app\models\_base\BaseImasDiags;
use app\models\Assessments;
use app\models\Course;
use app\models\Diags;
use app\models\forms\ChangeRightsForm;
use app\models\Groups;
use app\models\Stugroups;
use Yii;
use app\models\forms\AddNewUserForm;
use app\components\AppUtility;
use app\models\User;
use app\components\AppConstant;
use app\models\forms\AdminDiagnosticForm;

class AdminController extends AppController
{
    public function actionIndex()
    {
        $this->guestUserHandler();
        $sortBy = AppConstant::FIRST_NAME;
        $order = AppConstant::ASCENDING;
        $users = User::findAllUser($sortBy, $order);
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $userId = $user->id;
        $userName = $user->SID;
        $myRights = $user['rights'];
        $groupId= $user['groupid'];

        if ($myRights == AppConstant::NUMERIC_HUNDREAD) {
            if (isset($_GET['showusers'])) {
                setcookie('showusers',$_GET['showusers']);
                $showusers = $_GET['showusers'];
            } else if (isset($_COOKIE['showusers'])) {
                $showusers = $_COOKIE['showusers'];
            } else {
                $showusers = $groupId;
            }
        } else {
            $showusers = AppConstant::NUMERIC_ZERO;
        }
        if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT)
        {
            if (isset($params['showcourses']))
            {
                setcookie('showcourses',$params['showcourses']);
                $showcourses = $_GET['showcourses'];
            } else if (isset($_COOKIE['showcourses']))
            {
                $showcourses = $_COOKIE['showcourses'];
            } else
            {
                $showcourses = AppConstant::NUMERIC_ZERO; //0: mine, #: userid
            }
        } else {
            $showcourses = AppConstant::NUMERIC_ZERO;
        }

        if ($myRights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ZERO;
        } else {
            $query = Course::getCourseData($myRights, $showcourses, $userId);
            $page_courseList = array();
            $i = AppConstant::NUMERIC_ZERO;
            foreach($query as $key => $line)
            {
                $page_courseList[$i]['id'] = $line['id'];
                $page_courseList[$i]['name'] = $line['name'];
                $page_courseList[$i]['LastName'] = $line['LastName'];
                $page_courseList[$i]['FirstName'] = $line['FirstName'];
                $page_courseList[$i]['ownerid'] = $line['ownerid'];
                $page_courseList[$i]['available'] = $line['available'];
                if (isset($CFG['GEN']['addteachersrights'])) {
                    $minrights = $CFG['GEN']['addteachersrights'];
                } else {
                    $minrights = AppConstant::LIMITED_COURSE_CREATOR_RIGHT;
                }
                $page_courseList[$i]['addRemove'] = ($myRights < $minrights) ? "" : "<a href='#'>Add/Remove</a>";
                $page_courseList[$i]['transfer'] = ($line['ownerid']!=$userId && $myRights <75) ? "" : "<a href='#'>Transfer</a>";
                $i++;
            }
        }
        /**
         * get list of teachers for the select box
         */
        if ($myRights == AppConstant::GROUP_ADMIN_RIGHT) {
            $resultTeacher = User::getListOfTeacher($groupId);
        } else if ($myRights == AppConstant::ADMIN_RIGHT) {
            $resultTeacher= User::getTeacherData();
        }
        $i = AppConstant::NUMERIC_ZERO;
        foreach($resultTeacher as $key => $teacher)
        {
            $page_teacherSelectVal[$i] = $teacher['id'];
            $page_teacherSelectLabel[$i] = $teacher['LastName'] . ", " . $teacher['FirstName']. ' ('.$teacher['SID'].')';
            $i++;
        }

        if ($myRights >= AppConstant::DIAGNOSTIC_CREATOR_RIGHT) {
            $result = Diags::getDiagnostic($myRights, $userId, $groupId);
            $i = AppConstant::NUMERIC_ZERO;
            foreach($result as $key => $row){
                $page_diagnosticsId[$i] = $row['id'];
                $page_diagnosticsName[$i] = $row['name'];
                $page_diagnosticsAvailable[$i] = ($row['public']&1) ? "Yes" : "No";
                $page_diagnosticsPublic[$i] = ($row['public']&2) ? "Yes" : "No";
                $i++;
            }
        }
        /**
         * DATA PROCESSING FOR USERS BLOCK
         */
        if ($myRights < AppConstant::ADMIN_RIGHT) {
            $page_userBlockTitle = AppConstant::NON_STUDENT;
            $userData = User::getUserByIdAndGroupId(AppConstant::STUDENT_RIGHT,$groupId,'LastName');
        }
        else {
            if ($showusers == -1) {
                $page_userBlockTitle = AppConstant::PENDING_USERS;
                $userData = User::getUserByRights(AppConstant::UNENROLL_VALUE,AppConstant::NUMERIC_TWELVE,'LastName');
            } else if (is_numeric($showusers)) {
                $page_userBlockTitle = "Group Users";
                $userData = User::getUserByIdAndGroupId(AppConstant::STUDENT_RIGHT,$groupId,'LastName');
            } else {
                $page_userBlockTitle = "All Users - $showusers";
                $userData = User::getUserByLastNameSubstring($showusers,'LastName');
            }
        }
            foreach($userData as $i=>$line)
            {
                $page_userDataId[$i] = $line['id'];
                $page_userDataSid[$i] = $line['SID'];
                $page_userDataEmail[$i] = $line['email'];
                $page_userDataLastName[$i] = $line['LastName'];
                $page_userDataFirstName[$i] = $line['FirstName'];
            switch ($line['rights']) {
                case 5: $page_userDataType[$i] = "Guest"; break;
                case 10:$page_userDataType[$i] = "Student"; break;
                case 15: $page_userDataType[$i] = "Tutor/TA/Proctor"; break;
                case 20: $page_userDataType[$i] = "Teacher"; break;
                case 40: $page_userDataType[$i] = "LimCourseCreator"; break;
                case 60: $page_userDataType[$i] = "DiagCreator"; break;
                case 75: $page_userDataType[$i] = "GroupAdmin"; break;
                case 100: $page_userDataType[$i] = "Admin"; break;
            }
            $page_userDataLastAccess[$i] = ($line['lastaccess']>AppConstant::NUMERIC_ZERO) ? date("n/j/y g:i a",$line['lastaccess']) : "never" ;
            }
        $page_userSelectVal[0] = AppConstant::NUMERIC_NEGATIVE_ONE;
        $page_userSelectLabel[0] = "Pending";
        $page_userSelectVal[1] = AppConstant::NUMERIC_ZERO;
        $page_userSelectLabel[1] = "Default";
        $i=AppConstant::NUMERIC_TWO;
        $query = Groups::getIdAndName();
        foreach($query as $singleQuery) {
            $page_userSelectVal[$i] = $singleQuery['id'];
            $page_userSelectLabel[$i] = $singleQuery['name'];
            $i++;
        }
        for ($let=ord("A");$let<=ord("Z");$let++) {
            $page_userSelectVal[$i] = chr($let);
            $page_userSelectLabel[$i] = chr($let);
            $i++;
        }
        $this->includeCSS(['dataTables.bootstrap.css','forums.css','dashboard.css']);
        $this->includeJS(['general.js', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        return $this->renderWithData('index', ['users' => $users, 'page_userDataId' =>$page_userDataId,'page_userDataLastName' => $page_userDataLastName, 'page_userDataFirstName' => $page_userDataFirstName, 'page_userDataSid' => $page_userDataSid,'page_userDataEmail' => $page_userDataEmail,'page_userDataType' => $page_userDataType,'page_userDataLastAccess' => $page_userDataLastAccess, 'page_userSelectVal' => $page_userSelectVal,'page_userSelectLabel' => $page_userSelectLabel,'showusers' => $showusers,'myRights' => $myRights, 'page_courseList' => $page_courseList, 'resultTeacher' => $resultTeacher, 'page_diagnosticsId' => $page_diagnosticsId, 'page_diagnosticsName' => $page_diagnosticsName, 'page_diagnosticsAvailable' => $page_diagnosticsAvailable, 'page_diagnosticsPublic' => $page_diagnosticsPublic, 'page_userBlockTitle' => $page_userBlockTitle, 'showcourses' => $showcourses, 'page_teacherSelectVal' => $page_teacherSelectVal, 'page_teacherSelectLabel' => $page_teacherSelectLabel, 'userId' => $userId, 'userName' => $userName]);
    }
/*
 * This method to add new user
 */
    public function actionAddNewUser()
    {
        $this->guestUserHandler();
        $model = new AddNewUserForm();
        if ($model->load($this->isPostMethod())){
            $params = $this->getRequestParams();
            $params = $params['AddNewUserForm'];
            $params['SID'] = $params['username'];
            $params['hideonpostswidget'] = AppConstant::ZERO_VALUE;
            $params['password'] = AppUtility::passwordHash($params['password']);
            $user = new User();
            $model = new AddNewUserForm();
            $user->attributes = $params;
            $user->save();
            $this->setSuccessFlash(AppConstant::ADD_NEW_USER);
        }
        $this->includeJS(["courseSetting.js"]);
        $responseData = array('model' => $model);
        return $this->renderWithData('addNewUser',$responseData);
    }

    public function actionAdminDiagnostic()
    {
        $this->guestUserHandler();
        $model = new AdminDiagnosticForm();
        if ($model->load($this->isPostMethod()))
        {
            $params = $this->getRequestParams();
            $user = $this->getAuthenticatedUser();
            $params = $params['AdminDiagnosticForm'];
            $params['ownerid'] = $user->SID;
            $params['name'] = $params['DiagnosticName'];
            $params['term'] = $params['TermDesignator'];
            $diag = new BaseImasDiags();
            $diag->attributes = $params;
            $diag->save();
        }
        $responseData = array('model' => $model);
        return $this->renderWithData('adminDiagnostic',$responseData);
    }

    public function actionGetAllCourseUserAjax()
    {
        $sortBy = AppConstant::FIRST_NAME;
        $order = AppConstant::ASCENDING;
        $courseData = Course::findCourseDataArray();
        $user = User::findAllUsersArray($sortBy, $order);
        if($courseData){
            $responseData = (array('courses' => $courseData, 'users' => $user));
            return $this->successResponse($responseData);
        }else{
             return $this->terminateResponse('No course found.');
        }
    }

    public function actionChangeRights()
    {
        $this->guestUserHandler();
        $id = $this->getParamVal('id');
        $model = new ChangeRightsForm();
        if ($model->load($this->isPostMethod())) {
            $params = $this->getRequestParams();
            $params = $params['ChangeRightsForm'];
            User::updateRights($id, $params['rights'], $params['groupid']);
            $this->setSuccessFlash('User confirmed successfully.');
            return $this->redirect(AppUtility::getURLFromHome('admin','admin/index'));
        }
        $responseData = array('model' => $model);
        return $this->renderWithData('changeRights', $responseData);
    }

    public function actionHelpOfRights()
    {
        return $this->renderWithData('helpOfRights');
    }
    /**
     * @return string
     * Add New Diagnostics
     */
    public function actionDiagnostics()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $userId = $user->id;
        $userName = $user->SID;
        $myRights = $user['rights'];
        $groupId= $user['groupid'];
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $diagnoId = $this->getParamVal('id');
        $params = $this->getRequestParams();

        if($myRights < AppConstant::DIAGNOSTIC_CREATOR_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } elseif (isset($params['step']) && $params['step'] == AppConstant::NUMERIC_TWO) {  // STEP 2 DATA PROCESSING

            $sel1 = array();
            $ips = array();
            $pws = array();
            $spws = array();
            foreach ($_POST as $k=>$v) {
                if (strpos($k,'selout')!==FALSE) {
                    $sel1[] = $v;
                } else if (strpos($k,'ipout')!==FALSE) {
                    $ips[] = $v;
                } else if (strpos($k,'pwout')!==FALSE) {
                    $pws[] = $v;
                } else if (strpos($k,'pwsout')!==FALSE) {
                    $spws[] = $v;
                }
            }
            if (isset($_POST['alpha'])) {
                natsort($sel1);
                $sel1 = array_values($sel1);
            }

            $sel1list = implode(',',$sel1);
            $iplist = implode(',',$ips);
            $pwlist = implode(',',$pws) . ';'. implode(',',$spws);
            $public = 1*$params['avail'] + 2*$params['public'] + 4*$params['reentry'];

            if ($params['termtype']=='mo') {
                $params['term'] = '*mo*';
            } else if ($_POST['termtype']=='day') {
                $params['term'] = '*day*';
            }

            if (isset($params['entrynotunique'])) {
                $params['entrytype'] = chr(ord($params['entrytype'])-2);
            }
            $entryformat = $params['entrytype'].$params['entrydig'];

            $sel2 = array();
            if (isset($params['id'])) {
                $diagnosticId = $params['id'];
                $row = Diags::getById($diagnosticId);
                $s1l = explode(',',$row['sel1list']);
                $s2l = explode(';',$row['sel2list']);
                for ($i=0; $i<count($s1l); $i++) {
                    $sel2[$s1l[$i]] = explode('~',$s2l[$i]);
                }
                $sel2name = $row['sel2name'];
                $aids = explode(',',$row['aidlist']);
                $page_updateId = $params['id'];
                $forceregen = $row['forceregen'];
            } else {
                $sel2name = "instructor";
                $aids = array();
                $page_updateId = 0;
                $forceregen = 0;
            }
            foreach($sel1 as $k=>$s1) {
                $page_selectValList[$k] = array();
                $page_selectLabelList[$k] = array();
                $page_selectName[$k] = "aid" . $k;
                $i=0;
                $courseId = $params['cid'];
                $result = Assessments::getByCId($courseId);

                foreach($result as $key => $row)
                {
                    $page_selectValList[$k][$i] = $row[0];
                    $page_selectLabelList[$k][$i] = $row[1];
                    if (isset($aids[$k]) && $row[0]==$aids[$k]) {
                        $page_selectedOption[$k] = $aids[$k];
                    }
                    $i++;
                }
            }
            $page_cntScript = (isset($sel2[$s1]) && count($sel2[$s1])>0) ? "<script> cnt['out$k'] = ".count($sel2[$s1]).";</script>\n"  : "<script> cnt['out$k'] = 0;</script>\n";
        } elseif (isset($_GET['step']) && $_GET['step']== AppConstant::NUMERIC_THREE) {  //STEP 3 DATA PROCESSING
            $sel1 = explode(',',$params['sel1list']);
            $aids = array();
            $forceregen = 0;
            for ($i=0;$i<count($sel1);$i++) {
                $aids[$i] = $_POST['aid'.$i];
                if (isset($_POST['reg'.$i]) && $_POST['reg'.$i]==1) {
                    $forceregen = $forceregen ^ (1<<$i);
                }
            }
            $aidlist = implode(',',$aids);
            $sel2 = array();
            foreach ($_POST as $k=>$v) {
                if (strpos($k,'out')!==FALSE) {
                    $n = substr($k,3,strpos($k,'-')-3);
                    $sel2[$n][] = ucfirst($v);
                }
            }
            if (isset($_POST['useoneforall'])) { //use first sel2 for all
                if (isset($_POST['alpha'])) {
                    sort($sel2[0]);
                }
                $sel2[0] = implode('~',$sel2[0]);
                for ($i=1; $i<count($sel1); $i++) {
                    $sel2[$i] = $sel2[0];
                }
            } else {
                for ($i=0;$i<count($sel2);$i++) {
                    if (isset($_POST['alpha'])) {
                        sort($sel2[$i]);
                    }
                    $sel2[$i] = implode('~',$sel2[$i]);
                }
            }
            $sel2list = implode(';',$sel2);

            if (isset($params['id']) && $params['id'] != 0) {
                $query = new Diags();
                $id = $query->updateDiagnostics($params);
                $page_successMsg = "<p>Diagnostic Updated</p>\n";
            } else {
                $query = new Diags();
                $id = $query->saveDiagnostic($params, $userId);
                $page_successMsg = "<p>Diagnostic Added</p>\n";
            }
            $page_diagLink = "<p>Direct link to diagnostic:  <b>".AppUtility::getURLFromHome('site', 'diagnostics?id='.$id)."</b></p>";
            $page_publicLink = ($_POST['public']&2) ? "<p>Diagnostic is listed on the public listing at: <b>".AppUtility::getURLFromHome('site', 'diagnostics')."</b></p>\n" : ""  ;

        } else {  //STEP 1 DATA PROCESSING, MODIFY MODE
            if (isset($params['id'])) {
                $line = Diags::getByDiagnoId($diagnoId);
                $diagname = $line['name'];
                $cid = $line['cid'];
                $public = $line['public'];
                $idprompt = $line['idprompt'];
                $ips = $line['ips'];
                $pws = $line['pws'];
                $sel = $line['sel1name'];
                $sel1list=  $line['sel1list'];
                $term = $line['term'];
                $entryformat = $line['entryformat'];
                $forceregen = $line['forceregen'];
                $reentrytime = $line['reentrytime'];
                if ($myRights >= 75) {
                    $owner = $line['ownerid'];
                } else if ($line['ownerid'] != $userId) {
                    echo "Not yours!";
                    exit;
                } else {
                    $owner = $userId;
                }
            } else {
                 //STEP 1, ADD MODE
                $diagname = '';
                $cid = 0;
                $public = 7;
                $idprompt = "Enter your student ID number";
                $ips = '';
                $pws = '';
                $sel = 'course';
                $sel1list = '';
                $term = '';
                $entryformat = 'C0';
                $forceregen = 0;
                $reentrytime = 0;
                $owner = $userId;
            }
            $entrytype = substr($entryformat,0,1); //$entryformat{0};
            $entrydig = substr($entryformat,1); //$entryformat{1};
            $entrynotunique = false;
            if ($entrytype=='A' || $entrytype=='B') {
                $entrytype = chr(ord($entrytype)+2);
                $entrynotunique = true;
            }
            $course = Course::getByUserId($userId);
            $i=0;
            $page_courseSelectList = array();
            foreach($course as $key => $row){
                $page_courseSelectList['val'][$i] = $row['id'];
                $page_courseSelectList['label'][$i] = $row['name'];
                if ($cid==$row[0]) {
                    $page_courseSelected = $row['id'];
                }
                $i++;
            }

            $page_entryNums = array();
            for ($j=0;$j<15;$j++) {
                $page_entryNums['val'][$j] = $j;
                if ($j==0) {
                    $page_entryNums['label'][$j] = "Any number";
                } else {
                    $page_entryNums['label'][$j] = $j;
                }
                if ($entrydig==$j) {
                    $page_entryNumsSelected = $j;
                }
            }
            $page_entryType = array();
            $page_entryType['val'][0] = 'C';
            $page_entryType['label'][0] = 'Letters or numbers';
            $page_entryType['val'][1] = 'D';
            $page_entryType['label'][1] = 'Numbers';
            $page_entryType['val'][2] = 'E';
            $page_entryType['label'][2] = 'Email address';
            $page_entryTypeSelected = $entrytype;
        }
        $this->includeJS(['diag.js']);
        $responseData = array('params' => $params, 'page_courseSelectList' => $page_courseSelectList, 'page_courseSelected' => $page_courseSelected, 'cid'=> $cid, 'idprompt' => $idprompt, 'reentrytime' => $reentrytime, 'page_entryType' => $page_entryType, 'page_entryTypeSelected' => $page_entryTypeSelected, 'page_entryNums' => $page_entryNums, 'page_entryNumsSelected' => $page_entryNumsSelected, 'sel' => $sel, 'sel2name' => $sel2name,
        'sel1list' => $sel1list, 'entryformat' => $entryformat, 'public' => $public, 'owner' => $owner, 'page_updateId' => $page_updateId, 'pwlist' => $pwlist, 'forceregen' => $forceregen, 'page_successMsg' => $page_successMsg, 'page_diagLink' => $page_diagLink, 'page_publicLink' => $page_publicLink, 'diagname' =>$diagname, 'ips' => $ips, 'pws' => $pws, 'term' => $term, 'iplist' => $iplist, 'sel1' => $sel1, 'page_selectName' => $page_selectName, 'page_selectValList' => $page_selectValList,
        'page_selectLabelList' => $page_selectLabelList, 'page_selectedOption' => $page_selectedOption, 'sel2list' => $sel2list, 'aidlist' => $aidlist);
        return $this->renderWithData('diagnostics', $responseData);
    }
}