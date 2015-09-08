<?php

namespace app\controllers\admin;

use app\components\filehandler;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use app\models\_base\BaseImasDiags;
use app\models\Assessments;
use app\models\Course;
use app\models\DiagOneTime;
use app\models\Diags;
use app\models\ExternalTools;
use app\models\Exceptions;
use app\models\forms\ChangeRightsForm;
use app\models\ForumView;
use app\models\Grades;
use app\models\Groups;
use app\models\Libraries;
use app\models\Sessions;
use app\models\Student;
use app\models\Stugroups;
use Yii;
use app\models\forms\AddNewUserForm;
use app\components\AppUtility;
use app\models\User;
use app\components\AppConstant;
use app\models\forms\AdminDiagnosticForm;
use tar;

class AdminController extends AppController
{
    public function actionIndex()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $sortBy = AppConstant::FIRST_NAME;
        $order = AppConstant::ASCENDING;
        $users = User::findAllUser($sortBy, $order);
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $userId = $user->id;
        $showCid = $this->getParamVal('showcourses');
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
        $this->includeCSS(['dataTables.bootstrap.css','forums.css','dashboard.css', 'course/items.css']);
        $this->includeJS(['general.js', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        return $this->renderWithData('index', ['showCid' => $showCid ,'users' => $users, 'page_userDataId' =>$page_userDataId,'page_userDataLastName' => $page_userDataLastName, 'page_userDataFirstName' => $page_userDataFirstName, 'page_userDataSid' => $page_userDataSid,'page_userDataEmail' => $page_userDataEmail,'page_userDataType' => $page_userDataType,'page_userDataLastAccess' => $page_userDataLastAccess, 'page_userSelectVal' => $page_userSelectVal,'page_userSelectLabel' => $page_userSelectLabel,'showusers' => $showusers,'myRights' => $myRights, 'page_courseList' => $page_courseList, 'resultTeacher' => $resultTeacher, 'page_diagnosticsId' => $page_diagnosticsId, 'page_diagnosticsName' => $page_diagnosticsName, 'page_diagnosticsAvailable' => $page_diagnosticsAvailable, 'page_diagnosticsPublic' => $page_diagnosticsPublic, 'page_userBlockTitle' => $page_userBlockTitle, 'showcourses' => $showcourses, 'page_teacherSelectVal' => $page_teacherSelectVal, 'page_teacherSelectLabel' => $page_teacherSelectLabel, 'userId' => $userId, 'userName' => $userName]);
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

   public function actionExternalTool()
   {
       $this->guestUserHandler();
       $user = $this->getAuthenticatedUser();
       $this->layout = 'master';
       $userId = $user->id;
       $userName = $user->SID;
       $myRights = $user['rights'];
       $groupId= $user['groupid'];
       $isAdmin = false;
       $isGrpAdmin = false;
       $isTeacher = false;
       $id = $this->getParamVal('id');
       $params = $this->getRequestParams();
       $courseId = (isset($params['cid'])) ? $params['cid'] : "admin";
       $course = Course::getById($courseId);
       $teacherId = $this->isTeacher($userId,$courseId);
       $err = '';

       if ($myRights == AppConstant::GROUP_ADMIN_RIGHT && $courseId=='admin') {
           $isGrpAdmin = true;
       } else if ($myRights == 100 && $courseId == 'admin') {
           $isAdmin = true;
       } else {
           $isTeacher = true;
       }
       if (isset($params['ltfrom'])) {
           $ltfrom = '&amp;ltfrom='.$params['ltfrom'];
       } else {
           $ltfrom = '';
       }

       if (isset($params['tname'])) {
           $privacy = AppConstant::NUMERIC_ZERO;
           if (isset($params['privname']))
           {
               $privacy += AppConstant::NUMERIC_ONE;
           }
           if (isset($params['privemail']))
           {
               $privacy += AppConstant::NUMERIC_TWO;
           }
           $params['custom'] = str_replace("\n",'&',$params['custom']);
           $params['custom'] = preg_replace('/\s/','',$params['custom']);

           if (!empty($params['tname']) && !empty($params['key']) && !empty($params['secret'])) {
               $query = '';
               if ($params['id'] == 'new') {
                   $external = new ExternalTools();
                   $external->saveExternalTool($courseId,$groupId,$params, $isTeacher, $isGrpAdmin, $isAdmin, $privacy);
               } else
               {
                    $params['groupId'] = $groupId;
                   if ($isTeacher) {

                       $attr = 'courseid';
                       $attrValue = $courseId;
                       ExternalTools::updateExternalToolByAdmin($params, $isAdmin,$attrValue,$attr, $privacy);
                   } else if ($isGrpAdmin) {
                       $attr = 'groupid';
                       $attrValue = $groupId;
                       ExternalTools::updateExternalToolByAdmin($params, $isAdmin,$attrValue,$attr, $privacy);
                   }
                   else{
                       if($isAdmin)
                       {
                           if($params['scope'] == 0)
                           {
                               ExternalTools::updateExternalTool($params,0,$privacy);

                           } else{
                               ExternalTools::updateExternalTool($params,$params['groupId'],$privacy);
                           }
                       }
                   }
               }
           }
           $ltfrom = str_replace('&amp;','&',$ltfrom);
           return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId.$ltfrom));
       }  else if (isset($params['delete']) && $params['delete']=='true') {
           $id = intval($params['id']);
           if ($id>0) {
                ExternalTools::deleteById($id, $isTeacher, $isGrpAdmin, $courseId, $groupId);
           }
           $ltfrom = str_replace('&amp;','&',$ltfrom);
           return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId.$ltfrom));
       } else {
           if (isset($params['delete'])) {
               $extTool= $nameOfExtTool = ExternalTools::getById($id);
               $nameOfExtTool = $extTool['name'];
           } else if (isset($_GET['id'])) {
               if ($params['id'] == 'new') {
                   $name = ''; $url = ''; $key = ''; $secret = ''; $custom = ''; $privacy = 3; $grp = 0;
               } else {
                   $result = ExternalTools::getByRights($id, $isTeacher, $courseId, $isGrpAdmin, $groupId);
                   if (count($result)==0) { die("invalid id");}
                   $name = $result['name'];
                   $url = $result['url'];
                   $key = $result['ltikey'];
                   $secret = $result['secret'];
                   $custom = $result['custom'];
                   $privacy = $result['privacy'];
                   $grp = $result['groupid'];
                   $custom = str_replace('&',"\n",$custom);
               }
               $tochg = array('name','url','key','secret','custom');
               foreach ($tochg as $v) {
                   ${$v} = htmlentities(${$v});
               }
           } else{
               if($isAdmin){
                   $courseid = AppConstant::NUMERIC_ZERO;
                   $resultFirst = ExternalTools::getByCourseId($courseid);
               } elseif($isGrpAdmin){
                   $courseid = AppConstant::NUMERIC_ZERO;
                   $resultFirst = ExternalTools::getByGroupId($courseid, $groupId);
               } else{
                   $resultFirst = ExternalTools::getByCourseAndOrderByName($courseId);
               }

           }
       }
       $this->includeCSS(['course/item.css']);
       $responseData = array('myRights' => $myRights, 'teacherId' => $teacherId, 'params' => $params, 'err' => $err, 'isAdmin' => $isAdmin, 'isGrpAdmin' => $isGrpAdmin, 'resultFirst' => $resultFirst, 'courseId' => $courseId, 'ltfrom' => $ltfrom,
       'name' => $name, 'grp' => $grp, 'privacy' => $privacy, 'url' => $url, 'key' => $key, 'secret' => $secret, 'custom' => $custom, 'course' => $course, 'nameOfExtTool' => $nameOfExtTool);
       return $this->renderWithData('externalTool', $responseData);
   }

    public function actionForms()
    {

        $imasroot = AppUtility::getHomeURL();
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $myRights = $currentUser['rights'];
        $this->layout = 'master';
         $action = $params['action'];
        switch($action) {
            case "delete":
                $course = Course::getById($params['id']);
                $name = $course['name'];
                break;
            case "deladmin":
                 break;
            case "chgpwd":
                 break;
            case "chgrights":
            case "newadmin":
                break;
            case "modify":
            case "addcourse":
                break;
            case "chgteachers":
                 break;
            case "importmacros":

                break;

            case "importqimages":
                break;
            case "importcoursefiles":
                break;
            case "transfer":
                 break;
            case "deloldusers":
                break;
            case "listltidomaincred":
                     $users = User::getByRights();
                    $groupsName = Groups::getIdAndName();
                    break;
            case "modltidomaincred":
                 $user = User::getById($params['id']);
                $groupsName = Groups::getIdAndName();
                break;
            case "listgroups":
                $groupsName = Groups::getIdAndName();
                break;
            case "modgroup":
                $groupsName = Groups::getById($params['id']);
                break;
            case "removediag":
                break;
        }
        $this->includeCSS(['imascore.css','assessment.css']);
        $this->includeJS(["general.js"]);
        $responseData = array('users' => $users,'params'=> $params,'groupsName' => $groupsName,'user' =>$user,'course' => $course,'action' => $action);
        return $this->renderWithData('forms',$responseData);
    }

    public function actionActions()
    {

        $params = $this->getRequestParams();
        $allowmacroinstall = true;
        $currentUser = $this->getAuthenticatedUser();
        $action = $params['action'];
        $myRights = $currentUser['rights'];
        $userid = 0;
        $groupid = 0;
        switch($action) {
            case "emulateuser":
                if ($myRights < AppConstant::ADMIN_RIGHT )
                { break;}
                $sessionId = $this->getSessionId();
                $be = $params['uid'];
                Sessions::updateUId($be,$sessionId);
                break;
            case "chgrights":
                break;
            case "resetpwd":
                if ($myRights < 75)
                {
                    echo "You don't have the authority for this action"; break;
                }
                $id = $this->getParamVal('id');
                if (isset($params['newpw']))
                {
                    if (isset($CFG['GEN']['newpasswords']))
                    {
                        $md5pw = password_hash($params['newpw'], PASSWORD_DEFAULT);
                    } else {
                        $md5pw = password_hash($params['newpw'], PASSWORD_DEFAULT);
                    }
                } else {
                    if (isset($CFG['GEN']['newpasswords']))
                    {
                        $md5pw = password_hash("password", PASSWORD_DEFAULT);
                    } else {
                        $md5pw =md5("password");
                    }
                }
                User::updatePassword($md5pw,$id,$myRights,$currentUser->groupid);
                break;
            case "deladmin":
                break;
            case "chgpwd":
                break;
            case "newadmin":
                break;
            case "logout":
                break;
            case "modify":
            case "addcourse":
                break;
            case "delete":
                break;
            case "remteacher":
                exit;
            case "addteacher":
                exit;
            case "importmacros":
                if ($myRights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
                $uploaddir = AppConstant::UPLOAD_DIRECTORY.'macro/';
                $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
                if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile))
                {
                    if (strpos($uploadfile,'.php')!==FALSE) {
                        $handle = fopen($uploadfile, "r");
                        $atstart = true;
                        $comments = " ";
                        $outlines = " ";
                        if ($handle) {
                            while (!feof($handle)) {
                                $buffer = fgets($handle, 4096);
                                if (strpos($buffer,"//")===0) {
                                    $trimmed = trim(substr($buffer,2));
                                    if ($trimmed{0}!='<' && substr($trimmed,-1)!='>')
                                    {
                                        $comments .= preg_replace_callback('/^( +)/', function($matches){return str_repeat("&nbsp;", strlen($matches["$1"]));}, substr($buffer,2)).  "<BR>";
                                    } else {
                                        $comments .= trim(substr($buffer,2));
                                    }
                                } else if (strpos($buffer,"function")===0) {
                                    $func = substr($buffer,9,strpos($buffer,"(")-9);
                                    if ($comments!='') {
                                        $outlines .= "<h3><a name=\"$func\">$func</a></h3>\n";
                                        $funcs[] = $func;
                                        $outlines .= $comments;
                                        $comments = '';
                                    }
                                } else if ($atstart && trim($buffer)=='') {
                                    $startcomments = $comments;
                                    $atstart = false;
                                    $comments = '';
                                } else {
                                    $comments = '';
                                }
                            }
                        }
                        fclose($handle);
                        $lib = basename($uploadfile,".php");
                        $outfile = fopen($uploaddir . $lib.".html", "w");
                        fwrite($outfile,"<html><body>\n<h1>Macro Library $lib</h1>\n");
                        fwrite($outfile,$startcomments);
                        fwrite($outfile,"<ul>\n");
                        if($funcs){
                            foreach($funcs as $func) {
                                fwrite($outfile,"<li><a href=\"#$func\">$func</a></li>\n");
                            }
                        }
                        fwrite($outfile,"</ul>\n");
                        fwrite($outfile, $outlines);
                        fclose($outfile);
                    }
                    break;
                } else {
                    $this->setWarningFlash('Error uploading file!');
                    return $this->redirect('forms?action=importmacros');
                }
            case "importqimages":
                if ($myRights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
                $uploaddir = AppConstant::UPLOAD_DIRECTORY.'qimages/';
                $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
                if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
                    if (strpos($uploadfile,'.tar.gz')!==FALSE) {
                        require dirname(__FILE__) . '/tar.class.php';
                        $tar = new tar();
                        $tar->openTAR($uploadfile);
                        if ($tar->hasFiles()) {
                            if ($GLOBALS['filehandertypecfiles'] == 's3') {
                                $n = $tar->extractToS3("qimages","public");
                            } else {
                               $n = $tar->extractToDir("../assessment/qimages/");
                            }

                            echo "<p>Extracted $n files.  <a href='".AppUtility::getURLFromHome('admin','admin/index')."'>Continue</a></p>\n";
                            exit;
                        } else {
                            echo "<p>File appears to contain nothing</p>\n";

                            exit;
                        }
                    }
                    unlink($uploadfile);
                    break;
                } else {
                    $this->setWarningFlash('Error uploading file!');
                    return $this->redirect('forms?action=importqimages');
                }
            case "importcoursefiles":

                if ($myRights < 100 || !$allowmacroinstall) { echo "You don't have the authority for this action"; break;}
                $uploaddir = AppConstant::UPLOAD_DIRECTORY.'coursefile/';
                $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
                if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
                    if (strpos($uploadfile,'.zip')!==FALSE && class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        $res = $zip->open($uploadfile);
                        $ne = 0;  $ns = 0;
                        if ($res===true) {
                            for($i = 0; $i < $zip->numFiles; $i++) {
                                //if (file_exists("../course/files/".$zip->getNameIndex($i))) {
                                if (filehandler::doesfileexist('cfile',$zip->getNameIndex($i))) {
                                    $ns++;
                                } else {
                                    $zip->extractTo("course/files/", array($zip->getNameIndex($i)));
                                    filehandler::relocatecoursefileifneeded("../course/files/".$zip->getNameIndex($i),$zip->getNameIndex($i));
                                    $ne++;
                                }
                            }
                            echo "<p>Extracted $ne files.  <a href='".AppUtility::getURLFromHome('admin','admin/index')."'>Continue</a></p>\n";
                            exit;
                        } else {

                            echo "<p>File appears to contain nothing</p>\n";
                        }
                    }
                    unlink($uploadfile);
                    break;
                } else {

                    echo "<p>Error uploading file!</p>\n";

                    exit;
                }
            case "transfer":
                break;
            case "deloldusers":
                if ($myRights <100)
                {
                    $this->setWarningFlash("You don't have the authority for this action");
                    break;
                }
                $old = time() - 60*60*24*30*$params['months'];
                $who = $params['who'];
                if ($who=="students") {
                    $users = User::getByLastAccessAndRights($old);
                    foreach ($users as $row) {
                        $userId = $row['id'];
                        AssessmentSession::deleteByUserId($userId);
                        Exceptions::deleteByUserId($userId);
                        Grades::deleteByUserId($userId);
                        ForumView::deleteByUserId($userId);
                        Student::deleteByUserId($userId);

                        filehandler::deletealluserfiles($userId);
                    }
                    User::deleteByLastAccessAndRights($old);
                } else if ($who=="all") {
                    User::deleteUserByLastAccess($old);
                }
                break;
            case "addgroup":
                if ($myRights <100) {
                    $this->setWarningFlash("You don't have the authority for this action");
//                    return $this->redirect('forms?action=listgroups&id='.$existingGroupData['id']);
                }
                $existingGroupData = Groups::getByName($params['gpname']);
                if (strlen($existingGroupData['name'])>0) {
                    $this->setWarningFlash('Group name already exists.');
                    return $this->redirect('forms?action=listgroups&id='.$existingGroupData['id']);
                }else{
                    $newGroup = new Groups();
                    $newGroup->insertNewGroup($params['gpname']);
                }
                break;
            case "modgroup":
                if ($myRights <100) {
                    $this->setWarningFlash("You don't have the authority for this action");
//                    return $this->redirect('forms?action=listgroups&id='.$existingGroupData['id']);
                }
                $existingGroupData = Groups::getByName($params['gpname']);
                if (strlen($existingGroupData['name'])>0) {
                    $this->setWarningFlash('Group name already exists.');
                    return $this->redirect('forms?action=modgroup&id='.$existingGroupData['id']);
                }else{
                    Groups::updateGroup($params);
                }
                break;
            case "delgroup":
                if ($myRights <100) {
                    $this->setWarningFlash("You don't have the authority for this action");
//                    return $this->redirect('forms?action=listgroups&id='.$existingGroupData['id']);
                }
                Groups::deleteById($params['id']);
                User::updateGroupId($params['id']);
                Libraries::updateGroupId($params['id']);
                break;
            case "modltidomaincred":
                if ($myRights <100) {
                    $this->setWarningFlash("You don't have the authority for this action");
//                    return $this->redirect('forms?action=listgroups&id='.$existingGroupData['id']);
                }
                if ($params['id']=='new') {
                    $user = new User();
                    $user->createLTIDomainCredentials($params);
                } else {
                    User::updateLTIDomainCredentials($params);
                }

                break;
            case "delltidomaincred":
                if ($myRights <100) {
                    $this->setWarningFlash("You don't have the authority for this action");
//                    return $this->redirect('forms?action=listgroups&id='.$existingGroupData['id']);
                }
                User::deleteUserById($params['id']);
                break;
            case "removediag";
                break;
        }
        session_write_close();
        if (isset($params['cid'])) {
//            echo '<a href="'.AppUtility::getURLFromHome('admin','admin/index').'"></a>';
//            header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid={$_GET['cid']}");
        } else {
            return $this->redirect('index');
        }
    }

    public function actionDiagOneTime()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $userId = $user->id;
        $userName = $user->SID;
        $myRights = $user['rights'];
        $groupId= $user['groupid'];
        $diag = $this->getParamVal('id');
        $params = $this->getRequestParams();
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";

        if ($myRights < AppConstant::DIAGNOSTIC_CREATOR_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } else if (isset($_GET['generate'])) {
            if (isset($params['n'])) {
                $lets = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                $code_list = array();
                $now = time();
                $n = intval($_POST['n']);
                $goodfor = intval($_POST['multi']);
                for ($i=0; $i<$n; $i++) {

                    $code = '';
                    for ($j=0;$j<3;$j++) {
                        $code .= substr($lets,rand(0,23),1);
                    }
                    for ($j=0;$j<3;$j++) {
                        $code .= rand(1,9);
                    }

                    $query = new DiagOneTime();
                    $query->generateDiagOneTime($diag, $now, $code, $goodfor);
//                    if ($i>0) { $query .= ','; }
//                    $query .= "('$diag',$now,'$code',$goodfor)";
                    $code_list[] = $code;
                }
                $code_list = array();
                $result = DiagOneTime::getByTime($now);

                foreach($result as $key => $row) {
                    if ($row['goodfor']==0) {
                        $row['goodfor'] = "One-time";
                    } else if ($row['goodfor']>1000000000) {
                        if ($row['goodfor']<time()) {
                            $row['goodfor'] = "Used - Expired";
                        } else {
                            $row['goodfor'] = "Used - set to expire";
                        }
                    } else {
                        $row['goodfor'] = intval($row['goodfor']) . " minutes";
                    }
                    $code_list[] = $row;
                }
            }
        } else if (isset($_GET['delete'])) {
            if ($_GET['delete']=='true') {
                 DiagOneTime::deleteDiagOneTime($diag);
                return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
            }
        } else {
            $old = time() - 365*24*60*60; //one year ago
            $now = time();
//            $queryDelete = DiagOneTime::deleteByTime($old, $now);
            $code_list = array();
            $diagByTime = DiagOneTime::getByDiag($diag);
            foreach($diagByTime as $key => $row)
             {
                $row['time'] = AppUtility::tzdate("F j, Y",$row['time']);
                if ($row['goodfor']==0) {
                    $row['goodfor'] = "One-time";
                } else if ($row['goodfor']>1000000000) {
                    $row['goodfor'] = "Used - set to expire";
                } else {
                    $row['goodfor'] = intval($row['goodfor']) . " minutes";
                }
                $code_list[] = $row;
            }

        }
        if ($overwriteBody == AppConstant::NUMERIC_ONE) { //NO AUTHORITY
            echo $body;
            } else {
            $nameOfDiag = Diags::getNameById($diag);
        }
        $responseData = array('nameOfDiag' => $nameOfDiag, 'params' => $params, 'diag' =>$diag, 'code_list' => $code_list);
        return $this->renderWithData('diagOneTime',$responseData);
    }

    public function actionManageLib()
    {
        global $rights,$parents,$qcount,$ltlibs, $names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin;
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $userId = $user->id;
        $userName = $user->SID;
        $myRights = $user['rights'];
        $groupId= $user['groupid'];
        $helpIcon = "";
        $isAdmin = false;
        $isGrpAdmin = false;
        $params = $this->getRequestParams();

        if ($myRights < 20) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } elseif (isset($params['cid']) && $params['cid']=="admin" && $myRights <75) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } elseif (!(isset($params['cid'])) && $myRights < 75) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } else {
            $cid = $params['cid'];
            if ($cid == 'admin') {
                if ($myRights >74 && $myRights < 100) {
                    $isGrpAdmin = true;
                } else if ($myRights == 100) {
                    $isAdmin = true;
                }
            }
            $now = time();

            if (isset($_POST['remove'])) {
                if (isset($_GET['confirmed'])) {
                    if ($_POST['remove']!='') {
                        $remlist = "'".implode("','",explode(',',$_POST['remove']))."'";

                        $query = "SELECT DISTINCT qsetid FROM imas_library_items WHERE libid IN ($remlist)";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        while ($row = mysql_fetch_row($result)) {
                            $qidstocheck[] = $row[0];
                        }

                        if ($isadmin) {
                            $query = "DELETE FROM imas_library_items WHERE libid IN ($remlist)";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                        } else if ($isgrpadmin) {
                            $query = "SELECT id FROM imas_libraries WHERE id IN ($remlist) AND groupid='$groupid'";
                            $result = mysql_query($query) or die("Query failed : " . mysql_error());
                            while ($row = mysql_fetch_row($result)) {
                                $query = "DELETE FROM imas_library_items WHERE libid='$row[0]'";
                                mysql_query($query) or die("Query failed : " . mysql_error());
                            }
                        } else {
                            $query = "SELECT id FROM imas_libraries WHERE id IN ($remlist) AND ownerid='$userid'";
                            $result = mysql_query($query) or die("Query failed : " . mysql_error());
                            while ($row = mysql_fetch_row($result)) {
                                $query = "DELETE FROM imas_library_items WHERE libid='$row[0]'";
                                mysql_query($query) or die("Query failed : " . mysql_error());
                            }
                        }

                        if (isset($qidstocheck)) {
                            $qids = implode(",",$qidstocheck);
                            $query = "SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qids)";
                            $result = mysql_query($query) or die("Query failed : " . mysql_error());
                            $okqids = array();
                            while ($row = mysql_fetch_row($result)) {
                                $okqids[] = $row[0];
                            }
                            $qidstofix = array_diff($qidstocheck,$okqids);
                            if ($_POST['delq']=='yes' && count($qidstofix)>0) {
                                $qlist = implode(',',$qidstofix);
                                //$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
                                $query = "UPDATE imas_questionset SET deleted=1 WHERE id IN ($qlist)";
                                mysql_query($query) or die("Query failed : " . mysql_error());
                                /*foreach ($qidstofix as $qid) {
                                    delqimgs($qid);
                                }*/
                            } else {
                                foreach($qidstofix as $qid) {
                                    $query = "INSERT INTO imas_library_items (qsetid,libid) VALUES ('$qid',0)";
                                    mysql_query($query) or die("Query failed : " . mysql_error());
                                }
                            }
                        }
                        $query = "DELETE FROM imas_libraries WHERE id IN ($remlist)";
                        if (!$isadmin) {
                            $query .= " AND groupid='$groupid'";
                        }
                        if (!$isadmin && !$isgrpadmin) {
                            $query .= " AND ownerid='$userid'";
                        }
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");

                    exit;
                } else {
                    $pagetitle = "Confirm Removal";
                    $curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Removal ";
                    if (!isset($_POST['nchecked'])) {
                        $overwriteBody = 1;
                        $body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
                    } else {
                        $oktorem = array();
                        for ($i=0; $i<count($_POST['nchecked']); $i++) {
                            $query = "SELECT count(id) FROM imas_libraries WHERE parent='{$_POST['nchecked'][$i]}'";
                            $result = mysql_query($query) or die("Query failed : " . mysql_error());
                            $libcnt= mysql_result($result,0,0);
                            if ($libcnt == 0) {
                                $oktorem[] = $_POST['nchecked'][$i];
                            }
                        }
                        $rlist = implode(",",$oktorem);
                        $hasChildWarning = (count($_POST['nchecked'])>count($oktorem)) ? "<p>Warning:  Some libraries selected have children, and cannot be deleted.</p>\n": "";
                    }
                }
            } else if (isset($_POST['chgrights'])) {
                if (isset($_POST['newrights'])) {
                    if ($_POST['newrights']!='') {
                        $llist = "'".implode("','",explode(',',$_POST['chgrights']))."'";
                        $query = "UPDATE imas_libraries SET userights='{$_POST['newrights']}',lastmoddate=$now WHERE id IN ($llist)";
                        if (!$isadmin) {
                            $query .= " AND groupid='$groupid'";
                        }
                        if (!$isadmin && !$isgrpadmin) {
                            $query .= " AND ownerid='$userid'";
                        }
                        mysql_query($query) or die("Query failed : $query " . mysql_error());
                    }
                    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");

                    exit;

                } else {
                    $pagetitle = "Change Library Rights";
                    $curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Change Library Rights ";
                    if (!isset($_POST['nchecked'])) {
                        $overwriteBody = 1;
                        $body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
                    } else {
                        $tlist = implode(",",$_POST['nchecked']);
                        $page_libRights = array();
                        $page_libRights['val'][0] = 0;
                        $page_libRights['val'][1] = 1;
                        $page_libRights['val'][2] = 2;

                        $page_libRights['label'][0] = "Private";
                        $page_libRights['label'][1] = "Closed to group, private to others";
                        $page_libRights['label'][2] = "Open to group, private to others";

                        if ($isadmin || $isgrpadmin || $allownongrouplibs) {
                            $page_libRights['label'][3] = "Closed to all";
                            $page_libRights['label'][4] = "Open to group, closed to others";
                            $page_libRights['label'][5] = "Open to all";
                            $page_libRights['val'][3] = 4;
                            $page_libRights['val'][4] = 5;
                            $page_libRights['val'][5] = 8;
                        }
                    }

                }



            } else if (isset($_POST['transfer'])) {
                if (isset($_POST['newowner'])) {
                    if ($_POST['transfer']!='') {
                        $translist = "'".implode("','",explode(',',$_POST['transfer']))."'";

                        //added for mysql 3.23 compatibility
                        $query = "SELECT groupid FROM imas_users WHERE id='{$_POST['newowner']}'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        $newgpid = mysql_result($result,0,0);
                        $query = "UPDATE imas_libraries SET ownerid='{$_POST['newowner']}',groupid='$newgpid' WHERE imas_libraries.id IN ($translist)";

                        if (!$isadmin) {
                            $query .= " AND groupid='$groupid'";
                        }
                        if (!$isadmin && !$isgrpadmin) {
                            $query .= " AND ownerid='$userid'";
                        }
                        mysql_query($query) or die("Query failed : $query " . mysql_error());
                    }
                    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");

                    exit;
                } else {
                    $pagetitle = "Confirm Transfer";
                    $curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Transfer ";
                    if (!isset($_POST['nchecked'])) {
                        $overwriteBody = 1;
                        $body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
                    } else {
                        $tlist = implode(",",$_POST['nchecked']);
                        $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        $i=0;
                        while ($row = mysql_fetch_row($result)) {
                            $page_newOwnerList['val'][$i] = $row[0];
                            $page_newOwnerList['label'][$i] = $row[2] . ", " . $row[1];
                            $i++;
                        }
                    }
                }
            } else if (isset($_POST['setparent'])) {
                if (isset($_POST['libs'])) {
                    if ($_POST['libs']!='') {
                        $toset = array();
                        $_POST['setparent'] = explode(',',$_POST['setparent']);
                        foreach ($_POST['setparent'] as $alib) {
                            if ($alib != $_POST['libs']) {
                                $toset[] = $alib;
                            }
                        }
                        if (count($toset)>0) {
                            $parlist = "'".implode("','",$toset)."'";
                            $query = "UPDATE imas_libraries SET parent='{$_POST['libs']}',lastmoddate=$now WHERE id IN ($parlist)";
                            if (!$isadmin) {
                                $query .= " AND groupid='$groupid'";
                            }
                            if (!$isadmin && !$isgrpadmin) {
                                $query .= " AND ownerid='$userid'";
                            }
                            mysql_query($query) or die("Query failed : $query " . mysql_error());
                        }
                    }
                    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");

                    exit;
                } else {
                    $pagetitle = "Set Parent";
                    $curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Set Parent ";
                    $parent1 = "";

                    if (!isset($_POST['nchecked'])) {
                        $overwriteBody = 1;
                        $body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
                    } else {
                        $tlist = implode(",",$_POST['nchecked']);
                    }
                }
            } else if (isset($_GET['remove'])) {
                if (isset($_GET['confirmed'])) {
                    $query = "SELECT DISTINCT qsetid FROM imas_library_items WHERE libid='{$_GET['remove']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        $qidstocheck[] = $row[0];
                    }
                    $query = "DELETE FROM imas_libraries WHERE id='{$_GET['remove']}'";
                    if (!$isadmin) {
                        $query .= " AND groupid='$groupid'";
                    }
                    if (!$isadmin && !$isgrpadmin) {
                        $query .= " AND ownerid='$userid'";
                    }
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    if (mysql_affected_rows()>0 && count($qidstocheck)>0) {
                        $query = "DELETE FROM imas_library_items WHERE libid='{$_GET['remove']}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                        $qids = implode(",",$qidstocheck);
                        $query = "SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qids)";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        $okqids = array();
                        while ($row = mysql_fetch_row($result)) {
                            $okqids[] = $row[0];
                        }
                        $qidstofix = array_diff($qidstocheck,$okqids);
                        if ($_POST['delq']=='yes' && count($qidstofix)>0) {
                            $qlist = implode(',',$qidstofix);
                            //$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
                            $query = "UPDATE imas_questionset SET deleted=1 WHERE id IN ($qlist)";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                            /*foreach ($qidstofix as $qid) {
                                delqimgs($qid);
                            }*/
                        } else {
                            foreach($qidstofix as $qid) {
                                $query = "INSERT INTO imas_library_items (qsetid,libid) VALUES ('$qid',0)";
                                mysql_query($query) or die("Query failed : " . mysql_error());
                            }
                        }
                    }

                    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");

                    exit;
                } else {
                    $query = "SELECT count(id) FROM imas_libraries WHERE parent='{$_GET['remove']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    $libcnt= mysql_result($result,0,0);
                    $pagetitle = ($libcnt>0) ? "Error" : "Remove Library";
                    $curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";
                }
            } else if (isset($_GET['transfer'])) {
                if (isset($_POST['newowner'])) {

                    //added for mysql 3.23 compatibility
                    $query = "SELECT groupid FROM imas_users WHERE id='{$_POST['newowner']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    $newgpid = mysql_result($result,0,0);

                    //$query = "UPDATE imas_libraries,imas_users SET imas_libraries.ownerid='{$_POST['newowner']}'";
                    //$query .= ",imas_libraries.groupid=imas_users.groupid WHERE imas_libraries.ownerid=imas_users.id AND ";
                    //$query .= "imas_libraries.id='{$_GET['transfer']}'";
                    $query = "UPDATE imas_libraries SET ownerid='{$_POST['newowner']}',groupid='$newgpid' WHERE imas_libraries.id='{$_GET['transfer']}'";
                    if (!$isadmin) {
                        $query .= " AND groupid='$groupid'";
                    }
                    if (!$isadmin && !$isgrpadmin) {
                        $query .= " AND ownerid='$userid'";
                    }
                    mysql_query($query) or die("Query failed : $query " . mysql_error());
                    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
                    exit;
                } else {
                    $pagetitle = "Transfer Library";
                    $curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";
                    $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    $i=0;
                    $page_newOwnerList = array();
                    while ($row = mysql_fetch_row($result)) {
                        $page_newOwnerList['val'][$i] = $row[0];
                        $page_newOwnerList['label'][$i] = $row[2] . ", " . $row[1];
                        $i++;
                    }

                }

            } else if (isset($_GET['modify'])) {
                if (isset($_POST['name']) && trim($_POST['name'])!='') {
                    if ($_GET['modify']=="new") {
                        $_POST['name'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['name']);
                        $query = "SELECT * FROM imas_libraries WHERE name='{$_POST['name']}' AND parent='{$_POST['libs']}'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        if (mysql_num_rows($result)>0) {
                            $overwriteBody =1;
                            $body = "Library already exists by that name with this parent.\n";
                            $body .= "<p><a href=\"managelibs.php?cid=$cid&modify=new\">Try Again</a></p>\n";
                        } else {
                            $mt = microtime();
                            $uqid = substr($mt,11).substr($mt,2,6);
                            $query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,sortorder,parent,groupid) VALUES ";
                            $query .= "($uqid,$now,$now,'{$_POST['name']}','$userid','{$_POST['rights']}','{$_POST['sortorder']}','{$_POST['libs']}','$groupid')";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                            header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
                            exit;
                        }
                    } else {

                        $query = "UPDATE imas_libraries SET name='{$_POST['name']}',userights='{$_POST['rights']}',sortorder='{$_POST['sortorder']}',lastmoddate=$now";
                        if ($_GET['modify'] != $_POST['libs']) {
                            $query .= ",parent='{$_POST['libs']}'";
                        }
                        $query .= " WHERE id='{$_GET['modify']}'";
                        if (!$isadmin) {
                            $query .= " AND groupid='$groupid'";
                        }
                        if (!$isadmin && !$isgrpadmin) {
                            $query .= " AND ownerid='$userid'";
                        }
                        mysql_query($query) or die("Query failed : " . mysql_error());

                        header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
                        exit;
                    }
                } else {
                    $pagetitle = "Library Settings";
                    $curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";

                    if ($_GET['modify']!="new") {
                        $pagetitle = "Modify Library\n";
                        $query = "SELECT name,userights,parent,sortorder FROM imas_libraries WHERE id='{$_GET['modify']}'";
                        if (!$isadmin) {
                            $query .= " AND ownerid='$userid'";
                        }
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        if ($row = mysql_fetch_row($result)) {
                            $name = $row[0];
                            $rights = $row[1];
                            $parent = $row[2];
                            $sortorder = $row[3];
                        }
                    } else {
                        $pagetitle = "Add Library\n";
                        if (isset($_GET['parent'])) {
                            $parent = $_GET['parent'];
                        }
                    }
                    if (!isset($name)) { $name = '';}
                    if (!isset($rights)) {
                        if ($isadmin || $allownongrouplibs) {
                            $rights = 8;
                        } else {
                            $rights = 2;
                        }
                    }
                    if (!isset($parent)) {$parent = 0;}
                    if (!isset($sortorder)) {$sortorder = 0;}
                    $parent1 = $parent;

                    if ($parent==0) {
                        $lnames = "Root";
                    } else {
                        $query = "SELECT name FROM imas_libraries WHERE id='$parent'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        $lnames = mysql_result($result,0,0);
                    }
                }

                $page_libRights = array();
                $page_libRights['val'][0] = 0;
                $page_libRights['val'][1] = 1;
                $page_libRights['val'][2] = 2;

                $page_libRights['label'][0] = "Private";
                $page_libRights['label'][1] = "Closed to group, private to others";
                $page_libRights['label'][2] = "Open to group, private to others";

                if ($isadmin || $isgrpadmin || $allownongrouplibs) {
                    $page_libRights['label'][3] = "Closed to all";
                    $page_libRights['label'][4] = "Open to group, closed to others";
                    $page_libRights['label'][5] = "Open to all";
                    $page_libRights['val'][3] = 4;
                    $page_libRights['val'][4] = 5;
                    $page_libRights['val'][5] = 8;
                }

            } else { //DEFAULT PROCESSING HERE

//                $pagetitle = "Library Management";
//                $helpicon = "&nbsp;&nbsp; <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=managelibraries','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/>";
//                $curBreadcrumb .= " &gt; Manage Libraries ";
                if ($isAdmin) {
                    $page_AdminModeMsg = "You are in Admin mode, which means actions will apply to all libraries, regardless of owner";
                } else if ($isGrpAdmin) {
                    $page_AdminModeMsg =  "You are in Group Admin mode, which means actions will apply to all libraries from your group, regardless of owner";
                } else {
                    $page_AdminModeMsg = "";
                }

                $resultLib = Libraries::getById();
                $rights = array();
                $sortorder = array();

                foreach($resultLib as $key => $line)
                {
                    $id = $line['id'];
                    $name = $line['name'];
                    $parent = $line['parent'];
                    $qcount[$id] = $line['count'];
                    $ltlibs[$parent][] = $id;
                    $parents[$id] = $parent;
                    $names[$id] = $name;
                    $rights[$id] = $line['userights'];
                    $sortorder[$id] = $line['sortorder'];
                    $ownerids[$id] = $line['ownerid'];
                    $groupids[$id] = $line['groupid'];
                }
                $page_appliesToMsg = (!$isAdmin) ? "(Only applies to your libraries)" : "";
            }
            $setParentRights = $this->setparentrights($id);
            foreach ($rights as $k=>$n) {
                $this->setparentrights($k);
            }

            $qcount[0] = $this->addupchildqs(0);

            $count = 0;

            if (isset($ltlibs[0])) {
                $this->printlist(0);
            }
        }
        $responseData = array('page_appliesToMsg' => $page_appliesToMsg, 'page_AdminModeMsg' => $page_AdminModeMsg, 'rights' => $rights);
        return $this->renderWithData('manageLib', $responseData);
    }

    function delqimgs($qsid) {
        $query = "SELECT id,filename,var FROM imas_qimages WHERE qsetid='$qsid'";
        $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
        while ($row = mysql_fetch_row($result)) {
            $query = "SELECT id FROM imas_qimages WHERE filename='{$row[1]}'";
            $r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
            if (mysql_num_rows($r2)==1) { //don't delete if file is used in other questions
                unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
            }
            $query = "DELETE FROM imas_qimages WHERE id='{$row[0]}'";
            mysql_query($query) or die("Query failed :$query " . mysql_error());
        }
    }

    function printlist($parent) {
        global $names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin;
        $arr = $ltlibs[$parent];
        if ($sortorder[$parent]==1) {
            $orderarr = array();
            foreach ($arr as $child) {
                $orderarr[$child] = $names[$child];
            }
            natcasesort($orderarr);
            $arr = array_keys($orderarr);
        }

        foreach ($arr as $child) {
            //if ($rights[$child]>0 || $ownerids[$child]==$userid || $isadmin) {
            if ($rights[$child]>2 || ($rights[$child]>0 && $groupids[$child]==$groupid) || $ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) ||$isadmin) {
                if (!$isadmin) {
                    if ($rights[$child]==5 && $groupids[$child]!=$groupid) {
                        $rights[$child]=4;  //adjust coloring
                    }
                }
                if (isset($ltlibs[$child])) { //library has children
                    //echo "<li><input type=button id=\"b$count\" value=\"-\" onClick=\"toggle($count)\"> {$names[$child]}";
                    echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
                    echo "</span><input type=checkbox name=\"nchecked[]\" value=$child> <span class=hdr onClick=\"toggle($child)\"><span class=\"r{$rights[$child]}\">{$names[$child]}</span> </span>\n";
                    //if ($isadmin) {
                    echo " ({$qcount[$child]}) ";
                    //}
                    echo "<span class=op>";
                    if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {
                        echo "<a href=\"managelibs.php?cid=$cid&modify=$child\">Modify</a> | ";
                        echo "<a href=\"managelibs.php?cid=$cid&remove=$child\">Delete</a> | ";
                        echo "<a href=\"managelibs.php?cid=$cid&transfer=$child\">Transfer</a> | ";
                    }
                    echo "<a href=\"managelibs.php?cid=$cid&modify=new&parent=$child\">Add Sub</a> ";
                    echo "<ul class=hide id=$child>\n";
                    echo "</span>";
                    $count++;
                    printlist($child);
                    echo "</ul></li>\n";

                } else {  //no children

                    echo "<li><span class=dd>-</span><input type=checkbox name=\"nchecked[]\" value=$child> <span class=\"r{$rights[$child]}\">{$names[$child]}</span> ";
                    //if ($isadmin) {
                    echo " ({$qcount[$child]}) ";
                    //}
                    echo "<span class=op>";
                    if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {
                        echo "<a href=\"managelibs.php?cid=$cid&modify=$child\">Modify</a> | ";
                        echo "<a href=\"managelibs.php?cid=$cid&remove=$child\">Delete</a> | ";
                        echo "<a href=\"managelibs.php?cid=$cid&transfer=$child\">Transfer</a> | ";
                    }
                    if ($qcount[$child]==0) {
                        echo "<a href=\"managelibs.php?cid=$cid&modify=new&parent=$child\">Add Sub</a> ";
                    } else {
                        echo "<a href=\"reviewlibrary.php?cid=$cid&lib=$child\">Preview</a>";
                    }
                    echo "</span>";
                    echo "</li>\n";


                }
            }
        }
        return $parent;
    }

    function setparentrights($alibid) {
        global $rights,$parents;
        if ($parents[$alibid]>0) {
            if ($rights[$parents[$alibid]] < $rights[$alibid]) {
                //if (($rights[$parents[$alibid]]>2 && $rights[$alibid]<3) || ($rights[$alibid]==0 && $rights[$parents[$alibid]]>0)) {
                $rights[$parents[$alibid]] = $rights[$alibid];
            }
            $this->setparentrights($parents[$alibid]);
        }
        return $alibid;
    }

    function addupchildqs($p) {
        global $qcount,$ltlibs;
        if (isset($ltlibs[$p])) { //if library has children
            foreach ($ltlibs[$p] as $child) {
                $qcount[$p] += $this->addupchildqs($child);
            }
        }
        return $qcount[$p];
    }

}