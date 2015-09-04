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
               $nameOfExtTool = ExternalTools::deleteExternalTool($id);


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
        $coursename = 'TOC';
        $breadcrumbbase = 1;
        $defaultcoursetheme = 'default.css';
        $action = $params['action'];
        $urlmode = 1;$installname = 'install';$groupid = 1;
        switch($action) {
            case "delete":
                $course = Course::getById($params['id']);
                $name = $course['name'];
                break;
            case "deladmin":
                echo "<p>Are you sure you want to delete this user?</p>\n";
                echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=deladmin&id={$_GET['id']}'\">\n";
                echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
                break;
            case "chgpwd":
                echo '<div id="headerforms" class="pagetitle"><h2>Change Your Password</h2></div>';
                echo "<form method=post action=\"actions.php?action=chgpwd\">\n";
                echo "<span class=form>Enter old password:</span>  <input class=form type=password name=oldpw size=40> <BR class=form>\n";
                echo "<span class=form>Enter new password:</span> <input class=form type=password name=newpw1 size=40> <BR class=form>\n";
                echo "<span class=form>Verify new password:</span>  <input class=form type=password name=newpw2 size=40> <BR class=form>\n";
                echo '<div class=submit><input type="submit" value="'._('Save').'"></div></form>';
                break;

            case "chgrights":
            case "newadmin":
                echo "<form method=post action=\"actions.php?action={$_GET['action']}";
                if ($_GET['action']=="chgrights") { echo "&id={$_GET['id']}"; }
                echo "\">\n";
                if ($_GET['action'] == "newadmin") {
                    echo "<span class=form>New User username:</span>  <input class=form type=text size=40 name=adminname><BR class=form>\n";
                    echo "<span class=form>First Name:</span> <input class=form type=text size=40 name=firstname><BR class=form>\n";
                    echo "<span class=form>Last Name:</span> <input class=form type=text size=40 name=lastname><BR class=form>\n";
                    echo "<span class=form>Email:</span> <input class=form type=text size=40 name=email><BR class=form>\n";
                    echo '<span class="form">Password:</span> <input class="form" type="text" size="40" name="password"/><br class="form"/>';
                    $oldgroup = 0;
                    $oldrights = 10;
                } else {
                    $user = User::getById($params['id']);
                }
                echo "<BR><span class=form><img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=rights','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/> Set User rights to: </span> \n";
                echo "<span class=formright><input type=radio name=\"newrights\" value=\"5\" ";
                if ($oldrights == 5) {echo "CHECKED";}
                echo "> Guest User <BR>\n";
                echo "<input type=radio name=\"newrights\" value=\"10\" ";
                if ($oldrights == 10) {echo "CHECKED";}
                echo "> Student <BR>\n";
                //obscelete
                //echo "<input type=radio name=\"newrights\" value=\"15\" ";
                //if ($oldrights == 15) {echo "CHECKED";}
                //echo "> TA/Tutor/Proctor <BR>\n";
                echo "<input type=radio name=\"newrights\" value=\"20\" ";
                if ($oldrights == 20) {echo "CHECKED";}
                echo "> Teacher <BR>\n";
                echo "<input type=radio name=\"newrights\" value=\"40\" ";
                if ($oldrights == 40) {echo "CHECKED";}
                echo "> Limited Course Creator <BR>\n";
                echo "<input type=radio name=\"newrights\" value=\"60\" ";
                if ($oldrights == 60) {echo "CHECKED";}
                echo "> Diagnostic Creator <BR>\n";
                echo "<input type=radio name=\"newrights\" value=\"75\" ";
                if ($oldrights == 75) {echo "CHECKED";}
                echo "> Group Admin <BR>\n";
                if ($myRights==100) {
                    echo "<input type=radio name=\"newrights\" value=\"100\" ";
                    if ($oldrights == 100) {echo "CHECKED";}
                    echo "> Full Admin </span><BR class=form>\n";
                }

                if ($myRights == 100) {
                    echo "<span class=form>Assign to group: </span>";
                    echo "<span class=formright><select name=\"group\" id=\"group\">";
                    echo "<option value=0>Default</option>\n";
                    $query = "SELECT id,name FROM imas_groups ORDER BY name";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        echo "<option value=\"{$row[0]}\" ";
                        if ($oldgroup==$row[0]) {
                            echo "selected=1";
                        }
                        echo ">{$row[1]}</option>\n";
                    }
                    echo "</select><br class=form />\n";
                }

                echo "<div class=submit><input type=submit value=Save></div></form>\n";
                break;
            case "modify":
            case "addcourse":
                if ($_GET['action']=='modify') {
                    $line = Course::getById($params['id']);
                    /*$query = "SELECT * FROM imas_courses WHERE id='{$_GET['id']}'";*/
//                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
//                    $line = mysql_fetch_array($result, MYSQL_ASSOC);
                    $courseid = $line['id'];
                    $name = $line['name'];
                    $ekey = $line['enrollkey'];
                    $hideicons = $line['hideicons'];
                    $picicons = $line['picicons'];
                    $allowunenroll = $line['allowunenroll'];
                    $copyrights = $line['copyrights'];
                    $msgset = $line['msgset']%5;
                    $msgmonitor = (floor($line['msgset']/5))&1;
                    $msgQtoInstr = (floor($line['msgset']/5))&2;
                    $toolset = $line['toolset'];
                    $cploc = $line['cploc'];
                    $theme = $line['theme'];
                    $topbar = explode('|',$line['topbar']);
                    $topbar[0] = explode(',',$topbar[0]);
                    $topbar[1] = explode(',',$topbar[1]);
                    if ($topbar[0][0] == null) {unset($topbar[0][0]);}
                    if ($topbar[1][0] == null) {unset($topbar[1][0]);}
                    if (!isset($topbar[2])) {$topbar[2] = 0;}
                    $avail = $line['available'];
                    $lockaid = $line['lockaid'];
                    $ltisecret = $line['ltisecret'];
                    $chatset = $line['chatset'];
                    $showlatepass = $line['showlatepass'];
                    $istemplate = $line['istemplate'];
                    $deflatepass = $line['deflatepass'];
                    $deftime = $line['deftime'];
                } else {
                    $courseid = _("Will be assigned when the course is created");
                    $name = "Enter course name here";
                    $ekey = "Enter enrollment key here";
                    $hideicons = isset($CFG['CPS']['hideicons'])?$CFG['CPS']['hideicons'][0]:0;
                    $picicons = isset($CFG['CPS']['picicons'])?$CFG['CPS']['picicons'][0]:0;
                    $allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
                    //0 no un, 1 allow un;  0 allow enroll, 2 no enroll

                    $copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
                    $msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
                    $toolset = isset($CFG['CPS']['toolset'])?$CFG['CPS']['toolset'][0]:0;
                    $msgmonitor = (floor($msgset/5))&1;
                    $msgQtoInstr = (floor($msgset/5))&2;
                    $msgset = $msgset%5;

                    $cploc = isset($CFG['CPS']['cploc'])?$CFG['CPS']['cploc'][0]:1;

                    $topbar = isset($CFG['CPS']['topbar'])?$CFG['CPS']['topbar'][0]:array(array(),array(),0);
                    $theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
                    $chatset = isset($CFG['CPS']['chatset'])?$CFG['CPS']['chatset'][0]:0;
                    $showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;
                    $istemplate = 0;
                    $avail = 0;
                    $lockaid = 0;
                    $deftime = isset($CFG['CPS']['deftime'])?$CFG['CPS']['deftime'][0]:600;
                    $deflatepass = isset($CFG['CPS']['deflatepass'])?$CFG['CPS']['deflatepass'][0]:0;
                    $ltisecret = "";
                }
                $defetime = $deftime%10000;
                $hr = floor($defetime/60)%12;
                $min = $defetime%60;
                $am = ($defetime<12*60)?'am':'pm';
                $deftimedisp = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
                if ($deftime>10000) {
                    $defstime = floor($deftime/10000);
                    $hr = floor($defstime/60)%12;
                    $min = $defstime%60;
                    $am = ($defstime<12*60)?'am':'pm';
                    $defstimedisp = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
                } else {
                    $defstimedisp = $deftimedisp;
                }

                if (isset($_GET['cid'])) {
                    $cid = $_GET['cid'];
                    echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Course Settings</div>";
                }
                echo '<div id="headerforms" class="pagetitle"><h2>Course Settings</h2></div>';
                echo "<form method=post action=\"actions.php?action={$_GET['action']}";
                if (isset($_GET['cid'])) {
                    echo "&cid=$cid";
                }
                if ($_GET['action']=="modify") { echo "&id={$_GET['id']}"; }
                echo "\">\n";
                echo "<span class=form>Course ID:</span><span class=formright>$courseid</span><br class=form>\n";
                echo "<span class=form>Enter Course name:</span><input class=form type=text size=80 name=\"coursename\" value=\"$name\"><BR class=form>\n";
                echo "<span class=form>Enter Enrollment key:</span><input class=form type=text size=30 name=\"ekey\" value=\"$ekey\"><BR class=form>\n";
                echo '<span class=form>Available?</span><span class=formright>';
                echo '<input type="checkbox" name="stuavail" value="1" ';
                if (($avail&1)==0) { echo 'checked="checked"';}
                echo '/>Available to students<br/><input type="checkbox" name="teachavail" value="2" ';
                if (($avail&2)==0) { echo 'checked="checked"';}
                echo '/>Show on instructors\' home page</span><br class="form" />';
                if ($_GET['action']=="modify") {
                    echo '<span class=form>Lock for assessment:</span><span class=formright><select name="lockaid">';
                    echo '<option value="0" ';
                    if ($lockaid==0) { echo 'selected="1"';}
                    echo '>No lock</option>';
                    $query = "SELECT id,name FROM imas_assessments WHERE courseid='{$_GET['id']}' ORDER BY name";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        echo "<option value=\"{$row[0]}\" ";
                        if ($lockaid==$row[0]) { echo 'selected="1"';}
                        echo ">{$row[1]}</option>";
                    }
                    echo '</select></span><br class="form"/>';
                }

                if (!isset($CFG['CPS']['deftime']) || $CFG['CPS']['deftime'][1]==1) {
                    echo "<span class=form>Default start/end time for new items:</span><span class=formright>";
                    echo 'Start: <input name="defstime" type="text" size="8" value="'.$defstimedisp.'"/>, ';
                    echo 'end: <input name="deftime" type="text" size="8" value="'.$deftimedisp.'"/>';
                    echo '</span><br class="form"/>';
                }

                if (!isset($CFG['CPS']['theme']) || $CFG['CPS']['theme'][1]==1) {
                    echo "<span class=form>Theme:</span><span class=formright>";
                    echo " <select name=\"theme\">";
                    if (isset($CFG['CPS']['themelist'])) {
                        $themes = explode(',',$CFG['CPS']['themelist']);
                        if (isset($CFG['CPS']['themenames'])) {
                            $themenames = explode(',',$CFG['CPS']['themenames']);
                        }
                    } else {
                        $handle = opendir("../themes/");
                        $themes = array();
                        while (false !== ($file = readdir($handle))) {
                            if (substr($file,strpos($file,'.'))=='.css') {
                                $themes[] = $file;
                            }
                        }
                        sort($themes);
                    }
                    foreach ($themes as $k=>$file) {
                        echo "<option value=\"$file\" ";
                        if ($file==$theme) { echo 'selected="selected"';}
                        echo '>';
                        if (isset($themenames)) {
                            echo $themenames[$k];
                        } else {
                            echo substr($file,0,strpos($file,'.'));
                        }
                        echo '</option>';
                    }

                    echo " </select></span><br class=\"form\" />";
                }
                if (!isset($CFG['CPS']['picicons']) || $CFG['CPS']['picicons'][1]==1) {

                    echo "<span class=form>Icons:</span><span class=formright>\n";
                    echo 'Icon Style: <input type=radio name="picicons" value="0" ';
                    if ($picicons==0) { echo "checked=1";}
                    echo '/> Text-based <input type=radio name="picicons" value="1" ';
                    if ($picicons==1) { echo "checked=1";}
                    echo '/> Images</span><br class="form" />';
                }

                if (!isset($CFG['CPS']['hideicons']) || $CFG['CPS']['hideicons'][1]==1) {

                    echo "<span class=form>Show Icons:</span><span class=formright>\n";

                    echo 'Assessments: <input type=radio name="HIassess" value="0" ';
                    if (($hideicons&1)==0) { echo "checked=1";}
                    echo '/> Show <input type=radio name="HIassess" value="1" ';
                    if (($hideicons&1)==1) { echo "checked=1";}
                    echo '/> Hide<br/>';

                    echo 'Inline Text: <input type=radio name="HIinline" value="0" ';
                    if (($hideicons&2)==0) { echo "checked=1";}
                    echo '/> Show <input type=radio name="HIinline" value="2" ';
                    if (($hideicons&2)==2) { echo "checked=1";}
                    echo '/> Hide<br/>';

                    echo 'Linked Text: <input type=radio name="HIlinked" value="0" ';
                    if (($hideicons&4)==0) { echo "checked=1";}
                    echo '/> Show <input type=radio name="HIlinked" value="4" ';
                    if (($hideicons&4)==4) { echo "checked=1";}
                    echo '/> Hide<br/>';

                    echo 'Forums: <input type=radio name="HIforum" value="0" ';
                    if (($hideicons&8)==0) { echo "checked=1";}
                    echo '/> Show <input type=radio name="HIforum" value="8" ';
                    if (($hideicons&8)==8) { echo "checked=1";}
                    echo '/> Hide<br/>';

                    echo 'Blocks: <input type=radio name="HIblock" value="0" ';
                    if (($hideicons&16)==0) { echo "checked=1";}
                    echo '/> Show <input type=radio name="HIblock" value="16" ';
                    if (($hideicons&16)==16) { echo "checked=1";}
                    echo '/> Hide</span><br class=form />';
                }
                if (!isset($CFG['CPS']['unenroll']) || $CFG['CPS']['unenroll'][1]==1) {
                    echo "<span class=form>Allow students to self-<u>un</u>enroll</span><span class=formright>";
                    echo '<input type=radio name="allowunenroll" value="0" ';
                    if (($allowunenroll&1)==0) { echo "checked=1";}
                    echo '/> No <input type=radio name="allowunenroll" value="1" ';
                    if (($allowunenroll&1)==1) { echo "checked=1";}
                    echo '/> Yes </span><br class=form />';

                    echo "<span class=form>Allow students to self-enroll</span><span class=formright>";
                    echo '<input type=radio name="allowenroll" value="2" ';
                    if (($allowunenroll&2)==2) { echo "checked=1";}
                    echo '/> No <input type=radio name="allowenroll" value="0" ';
                    if (($allowunenroll&2)==0) { echo "checked=1";}
                    echo '/> Yes </span><br class=form />';
                }
                if (!isset($CFG['CPS']['copyrights']) || $CFG['CPS']['copyrights'][1]==1) {
                    echo "<span class=form>Allow other instructors to copy course items:</span><span class=formright>";
                    echo '<input type=radio name="copyrights" value="0" ';
                    if ($copyrights==0) { echo "checked=1";}
                    echo '/> Require enrollment key from everyone<br/> <input type=radio name="copyrights" value="1" ';
                    if ($copyrights==1) { echo "checked=1";}
                    echo '/> No key required for group members, require key from others <br/><input type=radio name="copyrights" value="2" ';
                    if ($copyrights==2) { echo "checked=1";}
                    echo '/> No key required from anyone</span><br class=form />';
                }
                if (!isset($CFG['CPS']['msgset']) || $CFG['CPS']['msgset'][1]==1) {
                    echo "<span class=form>Message System:</span><span class=formright>";
                    //0 on, 1 to instr, 2 to stu, 3 nosend, 4 off
                    echo '<input type=radio name="msgset" value="0" ';
                    if ($msgset==0) { echo "checked=1";}
                    echo '/> On for send and receive<br/> <input type=radio name="msgset" value="1" ';
                    if ($msgset==1) { echo "checked=1";}
                    echo '/> On for receive, students can only send to instructor<br/> <input type=radio name="msgset" value="2" ';
                    if ($msgset==2) { echo "checked=1";}
                    echo '/> On for receive, students can only send to students<br/> <input type=radio name="msgset" value="3" ';
                    if ($msgset==3) { echo "checked=1";}
                    echo '/> On for receive, students cannot send<br/> <input type=radio name="msgset" value="4" ';
                    if ($msgset==4) { echo "checked=1";}
                    echo '/> Off <br/> <input type=checkbox name="msgmonitor" value="1" ';
                    if ($msgmonitor==1) { echo "checked=1";}
                    echo '/> Enable monitoring of student-to-student messages ';
                    //<br/><input type=checkbox name="msgqtoinstr" value="1" ';
                    //if ($msgQtoInstr==2) { echo "checked=1";}
                    //echo '/> Enable &quot;Message instructor about this question&quot; links
                    echo '</span><br class=form />';
                }
                if (!isset($CFG['CPS']['toolset']) || $CFG['CPS']['toolset'][1]==1) {
                    echo "<span class=form>Navigation Links for Students:</span><span class=formright>";
                    echo '<input type="checkbox" name="toolset-cal" value="1" ';
                    if (($toolset&1)==0) { echo 'checked="checked"';}
                    echo '> Calendar<br/>';

                    echo '<input type="checkbox" name="toolset-forum" value="2" ';
                    if (($toolset&2)==0) { echo 'checked="checked"';}
                    echo '> Forum List';

                    echo '</span><br class=form />';

                    echo '<span class="form">Pull-downs for course item reordering</span>';
                    echo '<span class="formright"><input type="checkbox" name="toolset-reord" value="4" ';
                    if (($toolset&4)==0) { echo 'checked="checked"';}
                    echo '> Show</span><br class="form"/>';
                }

                if (!isset($CFG['CPS']['chatset']) || $CFG['CPS']['chatset'][1]==1) {
                    if (isset($mathchaturl) && $mathchaturl!='') {
                        echo '<span class="form">Enable live chat:</span><span class="formright">';
                        echo '<input type=checkbox name="chatset" value="1" ';
                        if ($chatset==1) {echo 'checked="checked"';};
                        echo ' /></span><br class="form" />';
                    }
                }
                if (!isset($CFG['CPS']['deflatepass']) || $CFG['CPS']['deflatepass'][1]==1) {
                    echo '<span class="form">Auto-assign LatePasses on course enroll:</span><span class="formright">';
                    echo '<input type="text" size="3" name="deflatepass" value="'.$deflatepass.'"/> LatePasses</span><br class="form" />';
                }
                if (!isset($CFG['CPS']['showlatepass']) || $CFG['CPS']['showlatepass'][1]==1) {
                    echo '<span class="form">Show remaining LatePasses on student gradebook page:</span><span class="formright">';
                    echo '<input type=checkbox name="showlatepass" value="1" ';
                    if ($showlatepass==1) {echo 'checked="checked"';};
                    echo ' /></span><br class="form" />';
                }

                if (!isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1) {
                    echo "<span class=form>Student Quick Pick Top Bar items:</span><span class=formright>";
                    echo '<input type=checkbox name="stutopbar[]" value="0" ';
                    if (in_array(0,$topbar[0])) { echo 'checked=1'; }
                    echo ' /> Messages <br /><input type=checkbox name="stutopbar[]" value="3" ';
                    if (in_array(3,$topbar[0])) { echo 'checked=1'; }
                    echo ' /> Forums <br /><input type=checkbox name="stutopbar[]" value="1" ';
                    if (in_array(1,$topbar[0])) { echo 'checked=1'; }
                    echo ' /> Gradebook <br /><input type=checkbox name="stutopbar[]" value="2" ';
                    if (in_array(2,$topbar[0])) { echo 'checked=1'; }
                    echo ' /> Calendar <br /><input type=checkbox name="stutopbar[]" value="9" ';
                    if (in_array(9,$topbar[0])) { echo 'checked=1'; }
                    echo ' /> Log Out</span><br class=form />';

                    echo "<span class=form>Instructor Quick Pick Top Bar items:</span><span class=formright>";
                    echo '<input type=checkbox name="insttopbar[]" value="0" ';
                    if (in_array(0,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Messages<br /><input type=checkbox name="insttopbar[]" value="6" ';
                    if (in_array(6,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Forums<br /><input type=checkbox name="insttopbar[]" value="1" ';
                    if (in_array(1,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Student View<br /><input type=checkbox name="insttopbar[]" value="2" ';
                    if (in_array(2,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Gradebook<br /><input type=checkbox name="insttopbar[]" value="3" ';
                    if (in_array(3,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Roster<br /><input type=checkbox name="insttopbar[]" value="7" ';
                    if (in_array(7,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Groups<br/><input type=checkbox name="insttopbar[]" value="4" ';
                    if (in_array(4,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Calendar<br/><input type=checkbox name="insttopbar[]" value="5" ';
                    if (in_array(5,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Quick View<br /><input type=checkbox name="insttopbar[]" value="9" ';
                    if (in_array(9,$topbar[1])) { echo 'checked=1'; }
                    echo ' /> Log Out</span><br class=form />';

                    echo '<span class="form">Quick Pick Bar location:</span><span class="formright">';
                    echo '<input type="radio" name="topbarloc" value="0" '. ($topbar[2]==0?'checked="checked"':'').'>Top of course page<br/>';
                    echo '<input type="radio" name="topbarloc" value="1" '. ($topbar[2]==1?'checked="checked"':'').'>Top of all pages';
                    echo '</span><br class="form" />';
                }
                if (!isset($CFG['CPS']['cploc']) || $CFG['CPS']['cploc'][1]==1) {
                    echo '<span class=form>Instructor course management links location:</span><span class=formright>';
                    echo '<input type=radio name="cploc" value="0" ';
                    if (($cploc&1)==0) {echo "checked=1";}
                    echo ' /> Bottom of page<br /><input type=radio name="cploc" value="1" ';
                    if (($cploc&1)==1) {echo "checked=1";}
                    echo ' /> Left side bar</span><br class=form />';

                    echo '<span class=form>View Control links:</span><span class=formright>';
                    echo '<input type=radio name="cplocview" value="0" ';
                    if (($cploc&4)==0) {echo "checked=1";}
                    echo ' /> With other course management links<br /><input type=radio name="cplocview" value="4" ';
                    if (($cploc&4)==4) {echo "checked=1";}
                    echo ' /> Buttons at top right</span><br class=form />';

                    echo '<span class=form>Student links location:</span><span class=formright>';
                    echo '<input type=radio name="cplocstu" value="0" ';
                    if (($cploc&2)==0) {echo "checked=1";}
                    echo ' /> Bottom of page<br /><input type=radio name="cplocstu" value="2" ';
                    if (($cploc&2)==2) {echo "checked=1";}
                    echo ' /> Left side bar</span><br class=form />';
                }

                if (isset($enablebasiclti) && $enablebasiclti==true && isset($_GET['id'])) {
                    echo '<span class="form">LTI access secret (max 10 chars; blank to not use)</span>';
                    echo '<span class="formright"><input name="ltisecret" type="text" value="'.$ltisecret.'" maxlength="10"/> ';
                    echo '<button type="button" onclick="document.getElementById(\'ltiurl\').style.display=\'\';this.parentNode.removeChild(this);">'._('Show LTI key and URL').'</button>';
                    echo '<span id="ltiurl" style="display:none;">';
                    if (isset($_GET['id'])) {

                        echo '<br/>URL: '.$urlmode.$_SERVER['HTTP_HOST'].$imasroot.'/bltilaunch.php<br/>';
                        echo 'Key: placein_'.$_GET['id'].'_0 (to allow students to login directly to '.$installname.') or<br/>';
                        echo 'Key: placein_'.$_GET['id'].'_1 (to only allow access through the LMS )';
                    } else {
                        echo 'Course ID not yet set.';
                    }
                    echo '</span></span><br class="form" />';
                }
                if ($myRights>=75) {
                    echo '<span class="form">Mark course as template?</span>';
                    echo '<span class="formright"><input type=checkbox name="isgrptemplate" value="2" ';
                    if (($istemplate&2)==2) {echo 'checked="checked"';};
                    echo ' /> Mark as group template course';
                    if ($myRights==100) {
                        echo '<br/><input type=checkbox name="istemplate" value="1" ';
                        if (($istemplate&1)==1) {echo 'checked="checked"';};
                        echo ' /> Mark as global template course<br/>';
                        echo '<input type=checkbox name="isselfenroll" value="4" ';
                        if (($istemplate&4)==4) {echo 'checked="checked"';};
                        echo ' /> Mark as self-enroll course';
                        if (isset($CFG['GEN']['guesttempaccts'])) {
                            echo '<br/><input type=checkbox name="isguest" value="8" ';
                            if (($istemplate&8)==8) {echo 'checked="checked"';};
                            echo ' /> Mark as guest-access course';
                        }
                    }
                    echo '</span><br class="form" />';
                }

                if (isset($CFG['CPS']['templateoncreate']) && $_GET['action']=='addcourse' ) {
                    echo '<span class="form">Use content from a template course:</span>';
                    echo '<span class="formright"><select name="usetemplate" onchange="templatepreviewupdate(this)">';
                    echo '<option value="0" selected="selected">Start with blank course</option>';
                    //$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' ORDER BY ic.name";
                    $globalcourse = array();
                    $groupcourse = array();
                    $query = "SELECT id,name,copyrights,istemplate FROM imas_courses WHERE (istemplate&1)=1 AND available<4 AND copyrights=2 ORDER BY name";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        $globalcourse[$row[0]] = $row[1];
                    }

                    $query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
                    $query .= "iu.groupid='$groupid' AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        $groupcourse[$row[0]] = $row[1];
                    }
                    if (count($groupcourse)>0) {
                        echo '<optgroup label="Group Templates">';
                        foreach ($groupcourse as $id=>$name) {
                            echo '<option value="'.$id.'">'.$name.'</option>';
                        }
                        echo '</optgroup>';
                    }
                    if (count($globalcourse)>0) {
                        if (count($groupcourse)>0) {
                            echo '<optgroup label="System-wide Templates">';
                        }
                        foreach ($globalcourse as $id=>$name) {
                            echo '<option value="'.$id.'">'.$name.'</option>';
                        }
                        if (count($groupcourse)>0) {
                            echo '</optgroup>';
                        }
                    }

                    echo '</select><span id="templatepreview"></span></span><br class="form" />';

                    
//                    echo '<script type="text/javascript"> function templatepreviewupdate(el) {';
                    echo '  var outel = document.getElementById("templatepreview");';
                    echo '  if (el.value>0) {';
                    echo '  outel.innerHTML = "<a href=\"'.$imasroot.'/course/course.php?cid="+el.value+"\" target=\"preview\">Preview</a>";';
                    echo '  } else {outel.innerHTML = "";}';
                    echo '}</script>';
                }


                echo "<div class=submit><input type=submit value=Submit></div></form>\n";
                break;
            case "chgteachers":
                $query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                $line = mysql_fetch_array($result, MYSQL_ASSOC);
                echo '<div id="headerforms" class="pagetitle">';
                echo "<h2>{$line['name']}</h2>\n";
                echo '</div>';

                echo "<h4>Current Teachers:</h4>\n";
                $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_teachers.id,imas_teachers.userid ";
                $query .= "FROM imas_users,imas_teachers WHERE imas_teachers.courseid='{$_GET['id']}' AND " ;
                $query .= "imas_teachers.userid=imas_users.id ORDER BY imas_users.LastName;";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                $num = mysql_num_rows($result);
                echo '<form method="post" action="actions.php?action=remteacher&cid='.$_GET['id'].'&tot='.$num.'">';
                echo 'With Selected: <input type="submit" value="Remove as Teacher"/>';
                echo "<table cellpadding=5>\n";
                $onlyone = ($num==1);
                while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {

                    if ($onlyone) {
                        echo '<tr><td></td>';
                    } else {
                        echo '<tr><td><input type="checkbox" name="tid[]" value="'.$line['id'].'"/></td>';
                    }

                    echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
                    if ($onlyone) {
                        echo "<td></td></tr>";
                    } else {
                        echo "<td><A href=\"actions.php?action=remteacher&cid={$_GET['id']}&tid={$line['id']}\">Remove as Teacher</a></td></tr>\n";
                    }
                    $used[$line['userid']] = true;
                }
                echo "</table></form>\n";

                echo "<h4>Potential Teachers:</h4>\n";
                if ($myRights<100) {
                    $query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) AND groupid='$groupid' ORDER BY LastName;";
                } else if ($myRights==100) {
                    $query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) ORDER BY LastName;";
                }
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                echo '<form method="post" action="actions.php?action=addteacher&cid='.$_GET['id'].'">';
                echo 'With Selected: <input type="submit" value="Add as Teacher"/>';
                echo "<table cellpadding=5>\n";
                while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
                    if (trim($line['LastName'])=='' && trim($line['FirstName'])=='') {continue;}
                    if ($used[$line['id']]!=true) {
                        //if ($line['rights']<20) { $type = "Tutor/TA/Proctor";} else {$type = "Teacher";}
                        echo '<tr><td><input type="checkbox" name="atid[]" value="'.$line['id'].'"/></td>';
                        echo "<td>{$line['LastName']}, {$line['FirstName']} </td> ";
                        echo "<td><a href=\"actions.php?action=addteacher&cid={$_GET['id']}&tid={$line['id']}\">Add as Teacher</a></td></tr>\n";
                    }
                }
                echo "</table></form>\n";
                echo "<p><input type=button value=\"Done\" onclick=\"window.location='admin.php'\" /></p>\n";
                break;
            case "importmacros":

                break;

            case "importqimages":
                break;
            case "importcoursefiles":
                break;
            case "transfer":
                echo '<div id="headerforms" class="pagetitle">';
                echo "<h3>Transfer Course Ownership</h3>\n";
                echo '</div>';
                echo "<form method=post action=\"actions.php?action=transfer&id={$_GET['id']}\">\n";
                echo "Transfer course ownership to: <select name=newowner>\n";
                $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19";
                if ($myRights < 100) {
                    $query .= " AND groupid='$groupid'";
                }
                $query .= " ORDER BY LastName";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                while ($row = mysql_fetch_row($result)) {
                    echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
                }
                echo "</select>\n";
                echo "<p><input type=submit value=\"Transfer\">\n";
                echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
                echo "</form>\n";
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
                echo "<p>Are you sure you want to delete this diagnostic?  This does not delete the connected course and does not remove students or their scores.</p>\n";
                echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=removediag&id={$_GET['id']}'\">\n";
                echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
                break;
        }
        $this->includeCSS(['imascore.css']);
        $responseData = array('users' => $users,'groupsName' => $groupsName,'user' =>$user,'course' => $course,'action' => $action);
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
        $sessionid = 0;
        switch($action) {
            case "emulateuser":
                if ($myRights < 100 ) { break;}
                $be = $_REQUEST['uid'];
                $query = "UPDATE imas_sessions SET userid='$be' WHERE sessionid='$sessionid'";
                mysql_query($query) or die("Query failed : " . mysql_error());
                break;
            case "chgrights":
                if ($myRights < 100 && $_POST['newrights']>75) {echo "You don't have the authority for this action"; break;}
                if ($myRights < 75) { echo "You don't have the authority for this action"; break;}

                $query = "UPDATE imas_users SET rights='{$_POST['newrights']}'";
                if ($myRights == 100) {
                    $query .= ",groupid='{$_POST['group']}'";
                }
                $query .= " WHERE id='{$_GET['id']}'";
                if ($myRights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
                mysql_query($query) or die("Query failed : " . mysql_error());
                if ($myRights == 100) { //update library groupids
                    $query = "UPDATE imas_libraries SET groupid='{$_POST['group']}' WHERE ownerid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                }
                break;
            case "resetpwd":
                if ($myRights < 75) { echo "You don't have the authority for this action"; break;}
                if (isset($_POST['newpw'])) {
                    if (isset($CFG['GEN']['newpasswords'])) {
                        $md5pw = password_hash($_POST['newpw'], PASSWORD_DEFAULT);
                    } else {
                        $md5pw = md5($_POST['newpw']);
                    }
                } else {
                    if (isset($CFG['GEN']['newpasswords'])) {
                        $md5pw = password_hash("password", PASSWORD_DEFAULT);
                    } else {
                        $md5pw =md5("password");
                    }
                }
                $query = "UPDATE imas_users SET password='$md5pw' WHERE id='{$_GET['id']}'";
                if ($myRights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
                mysql_query($query) or die("Query failed : " . mysql_error());
                break;
            case "deladmin":
                if ($myRights < 75) { echo "You don't have the authority for this action"; break;}
                $query = "DELETE FROM imas_users WHERE id='{$_GET['id']}'";
                if ($myRights < 100) { $query .= " AND groupid='$groupid' AND rights<100"; }
                mysql_query($query) or die("Query failed : " . mysql_error());
                if (mysql_affected_rows()==0) { break;}
                $query = "DELETE FROM imas_students WHERE userid='{$_GET['id']}'";
                mysql_query($query) or die("Query failed : " . mysql_error());
                $query = "DELETE FROM imas_teachers WHERE userid='{$_GET['id']}'";
                mysql_query($query) or die("Query failed : " . mysql_error());
                $query = "DELETE FROM imas_assessment_sessions WHERE userid='{$_GET['id']}'";
                mysql_query($query) or die("Query failed : " . mysql_error());
                $query = "DELETE FROM imas_exceptions WHERE userid='{$_GET['id']}'";
                mysql_query($query) or die("Query failed : " . mysql_error());

                $query = "DELETE FROM imas_msgs WHERE msgto='{$_GET['id']}' AND isread>1"; //delete msgs to user
                mysql_query($query) or die("Query failed : $query " . mysql_error());
                $query = "UPDATE imas_msgs SET isread=isread+2 WHERE msgto='{$_GET['id']}' AND isread<2";
                mysql_query($query) or die("Query failed : $query " . mysql_error());
                $query = "DELETE FROM imas_msgs WHERE msgfrom='{$_GET['id']}' AND isread>1"; //delete msgs from user
                mysql_query($query) or die("Query failed : $query " . mysql_error());
                $query = "UPDATE imas_msgs SET isread=isread+4 WHERE msgfrom='{$_GET['id']}' AND isread<2";
                mysql_query($query) or die("Query failed : $query " . mysql_error());
                //todo: delete user picture files
                //todo: delete user file uploads
                require_once("../includes/filehandler.php");
                deletealluserfiles($_GET['id']);
                //todo: delete courses if any
                break;
            case "chgpwd":
                $query = "SELECT password FROM imas_users WHERE id = '$userid'";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                $line = mysql_fetch_array($result, MYSQL_ASSOC);

                if ((md5($_POST['oldpw'])==$line['password'] || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['oldpw'], $line['password'])) ) && ($_POST['newpw1'] == $_POST['newpw2'])) {
                    $md5pw =md5($_POST['newpw1']);
                    $query = "UPDATE imas_users SET password='$md5pw' WHERE id='$userid'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                } else {
                    echo "<HTML><body>Password change failed.  <A HREF=\"forms.php?action=chgpwd\">Try Again</a>\n";
                    echo "</body></html>\n";
                    exit;
                }
                break;
            case "newadmin":
                if ($myRights < 75) { echo "You don't have the authority for this action"; break;}
                if ($myRights < 100 && $_POST['newrights']>75) { break;}
                $query = "SELECT id FROM imas_users WHERE SID = '{$_POST['adminname']}';";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                $row = mysql_fetch_row($result);
                if ($row != null) {
                    echo "<html><body>Username is already used.\n";
                    echo "<a href=\"forms.php?action=newadmin\">Try Again</a> or ";
                    echo "<a href=\"forms.php?action=chgrights&id={$row[0]}\">Change rights for existing user</a></body></html>\n";
                    exit;
                }
                if (isset($CFG['GEN']['newpasswords'])) {
                    $md5pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
                } else {
                    $md5pw =md5($_POST['password']);
                }
                if ($myRights < 100) {
                    $newgroup = $groupid;
                } else if ($myRights == 100) {
                    $newgroup = $_POST['group'];
                }
                if (isset($CFG['GEN']['homelayout'])) {
                    $homelayout = $CFG['GEN']['homelayout'];
                } else {
                    $homelayout = '|0,1,2||0,1';
                }
                $query = "INSERT INTO imas_users (SID,password,FirstName,LastName,rights,email,groupid,homelayout) VALUES ('{$_POST['adminname']}','$md5pw','{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['newrights']}','{$_POST['email']}','$newgroup','$homelayout');";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                $newuserid = mysql_insert_id();
                if (isset($CFG['GEN']['enrollonnewinstructor'])) {
                    $valbits = array();
                    foreach ($CFG['GEN']['enrollonnewinstructor'] as $ncid) {
                        $valbits[] = "('$newuserid','$ncid')";
                    }
                    $query = "INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits);
                    mysql_query($query) or die("Query failed : " . mysql_error());
                }
                break;
            case "logout":
                $sessionid = session_id();
                $query = "DELETE FROM imas_sessions WHERE sessionid='$sessionid'";
                mysql_query($query) or die("Query failed : " . mysql_error());
                $_SESSION = array();
                if (isset($_COOKIE[session_name()])) {
                    setcookie(session_name(), '', time()-42000, '/');
                }
                session_destroy();
                break;
            case "modify":
            case "addcourse":
                if ($myRights < 40) { echo "You don't have the authority for this action"; break;}

                if (isset($CFG['CPS']['theme']) && $CFG['CPS']['theme'][1]==0) {
                    $theme = addslashes($CFG['CPS']['theme'][0]);
                } else {
                    $theme = $_POST['theme'];
                }

                if (isset($CFG['CPS']['picicons']) && $CFG['CPS']['picicons'][1]==0) {
                    $picicons = $CFG['CPS']['picicons'][0];
                } else {
                    $picicons = $_POST['picicons'];
                }
                if (isset($CFG['CPS']['hideicons']) && $CFG['CPS']['hideicons'][1]==0) {
                    $hideicons = $CFG['CPS']['hideicons'][0];
                } else {
                    $hideicons = $_POST['HIassess'] + $_POST['HIinline'] + $_POST['HIlinked'] + $_POST['HIforum'] + $_POST['HIblock'];
                }

                if (isset($CFG['CPS']['unenroll']) && $CFG['CPS']['unenroll'][1]==0) {
                    $unenroll = $CFG['CPS']['unenroll'][0];
                } else {
                    $unenroll = $_POST['allowunenroll'] + $_POST['allowenroll'];
                }

                if (isset($CFG['CPS']['copyrights']) && $CFG['CPS']['copyrights'][1]==0) {
                    $copyrights = $CFG['CPS']['copyrights'][0];
                } else {
                    $copyrights = $_POST['copyrights'];
                }

                if (isset($CFG['CPS']['msgset']) && $CFG['CPS']['msgset'][1]==0) {
                    $msgset = $CFG['CPS']['msgset'][0];
                } else {
                    $msgset = $_POST['msgset'];
                    if (isset($_POST['msgmonitor'])) {
                        $msgset += 5;
                    }
                    if (isset($_POST['msgqtoinstr'])) {
                        $msgset += 5*2;
                    }
                }

                if (isset($CFG['CPS']['chatset']) && $CFG['CPS']['chatset'][1]==0) {
                    $chatset = intval($CFG['CPS']['chatset'][0]);
                } else {
                    if (isset($_POST['chatset'])) {
                        $chatset = 1;
                    } else {
                        $chatset = 0;
                    }
                }

                if (isset($CFG['CPS']['deftime']) && $CFG['CPS']['deftime'][1]==0) {
                    $deftime = $CFG['CPS']['deftime'][0];
                } else {
                    preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$_POST['deftime'],$tmatches);
                    if (count($tmatches)==0) {
                        preg_match('/(\d+)\s*([a-zA-Z]+)/',$_POST['deftime'],$tmatches);
                        $tmatches[3] = $tmatches[2];
                        $tmatches[2] = 0;
                    }
                    $tmatches[1] = $tmatches[1]%12;
                    if($tmatches[3]=="pm") {$tmatches[1]+=12; }
                    $deftime = $tmatches[1]*60 + $tmatches[2];

                    preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$_POST['defstime'],$tmatches);
                    if (count($tmatches)==0) {
                        preg_match('/(\d+)\s*([a-zA-Z]+)/',$_POST['defstime'],$tmatches);
                        $tmatches[3] = $tmatches[2];
                        $tmatches[2] = 0;
                    }
                    $tmatches[1] = $tmatches[1]%12;
                    if($tmatches[3]=="pm") {$tmatches[1]+=12; }
                    $deftime += 10000*($tmatches[1]*60 + $tmatches[2]);
                }

                if (isset($CFG['CPS']['deflatepass']) && $CFG['CPS']['deflatepass'][1]==0) {
                    $deflatepass = $CFG['CPS']['deflatepass'][0];
                } else {
                    $deflatepass = intval($_POST['deflatepass']);
                }

                if (isset($CFG['CPS']['showlatepass']) && $CFG['CPS']['showlatepass'][1]==0) {
                    $showlatepass = intval($CFG['CPS']['showlatepass'][0]);
                } else {
                    if (isset($_POST['showlatepass'])) {
                        $showlatepass = 1;
                    } else {
                        $showlatepass = 0;
                    }
                }

                if (isset($CFG['CPS']['topbar']) && $CFG['CPS']['topbar'][1]==0) {
                    $topbar = $CFG['CPS']['topbar'][0];
                } else {
                    $topbar = array();
                    if (isset($_POST['stutopbar'])) {
                        $topbar[0] = implode(',',$_POST['stutopbar']);
                    } else {
                        $topbar[0] = '';
                    }
                    if (isset($_POST['insttopbar'])) {
                        $topbar[1] = implode(',',$_POST['insttopbar']);
                    } else {
                        $topbar[1] = '';
                    }
                    $topbar[2] = $_POST['topbarloc'];
                }
                $topbar = implode('|',$topbar);

                if (isset($CFG['CPS']['toolset']) && $CFG['CPS']['toolset'][1]==0) {
                    $toolset = $CFG['CPS']['toolset'][0];
                } else {
                    $toolset = 1*!isset($_POST['toolset-cal']) + 2*!isset($_POST['toolset-forum']) + 4*!isset($_POST['toolset-reord']);
                }

                if (isset($CFG['CPS']['cploc']) && $CFG['CPS']['cploc'][1]==0) {
                    $cploc = $CFG['CPS']['cploc'][0];
                } else {
                    $cploc = $_POST['cploc'] + $_POST['cplocstu'] + $_POST['cplocview'];
                }

                $avail = 3 - $_POST['stuavail'] - $_POST['teachavail'];

                $istemplate = 0;
                if ($myRights==100) {
                    if (isset($_POST['istemplate'])) {
                        $istemplate += 1;
                    }
                    if (isset($_POST['isselfenroll'])) {
                        $istemplate += 4;
                    }
                    if (isset($_POST['isguest'])) {
                        $istemplate += 8;
                    }
                }
                if ($myRights>=75) {
                    if (isset($_POST['isgrptemplate'])) {
                        $istemplate += 2;
                    }
                }

                $_POST['ltisecret'] = trim($_POST['ltisecret']);

                if ($_GET['action']=='modify') {
                    $query = "UPDATE imas_courses SET name='{$_POST['coursename']}',enrollkey='{$_POST['ekey']}',hideicons='$hideicons',available='$avail',lockaid='{$_POST['lockaid']}',picicons='$picicons',chatset=$chatset,showlatepass=$showlatepass,";
                    $query .= "allowunenroll='$unenroll',copyrights='$copyrights',msgset='$msgset',toolset='$toolset',topbar='$topbar',cploc='$cploc',theme='$theme',ltisecret='{$_POST['ltisecret']}',istemplate=$istemplate,deftime='$deftime',deflatepass='$deflatepass' WHERE id='{$_GET['id']}'";
                    if ($myRights<75) { $query .= " AND ownerid='$userid'";}
                    mysql_query($query) or die("Query failed : " . mysql_error());
                } else {
                    $blockcnt = 1;
                    $itemorder = addslashes(serialize(array()));
                    $query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,toolset,chatset,showlatepass,itemorder,topbar,cploc,available,istemplate,deftime,deflatepass,theme,ltisecret,blockcnt) VALUES ";
                    $query .= "('{$_POST['coursename']}','$userid','{$_POST['ekey']}','$hideicons','$picicons','$unenroll','$copyrights','$msgset',$toolset,$chatset,$showlatepass,'$itemorder','$topbar','$cploc','$avail',$istemplate,'$deftime','$deflatepass','$theme','{$_POST['ltisecret']}','$blockcnt');";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    $cid = mysql_insert_id();
                    //if ($myRights==40) {
                    $query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('$userid','$cid')";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    //}
                    $useweights = intval(isset($CFG['GBS']['useweights'])?$CFG['GBS']['useweights']:0);
                    $orderby = intval(isset($CFG['GBS']['orderby'])?$CFG['GBS']['orderby']:0);
                    $defgbmode = intval(isset($CFG['GBS']['defgbmode'])?$CFG['GBS']['defgbmode']:21);
                    $usersort = intval(isset($CFG['GBS']['usersort'])?$CFG['GBS']['usersort']:0);

                    $query = "INSERT INTO imas_gbscheme (courseid,useweights,orderby,defgbmode,usersort) VALUES ('$cid',$useweights,$orderby,$defgbmode,$usersort)";
                    mysql_query($query) or die("Query failed : " . mysql_error());


                    if (isset($CFG['CPS']['templateoncreate']) && isset($_POST['usetemplate']) && $_POST['usetemplate']>0) {
                        mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
                        $query = "SELECT useweights,orderby,defaultcat,defgbmode,stugbmode FROM imas_gbscheme WHERE courseid='{$_POST['usetemplate']}'";
                        $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
                        $row = mysql_fetch_row($result);
                        $query = "UPDATE imas_gbscheme SET useweights='{$row[0]}',orderby='{$row[1]}',defaultcat='{$row[2]}',defgbmode='{$row[3]}',stugbmode='{$row[4]}' WHERE courseid='$cid'";
                        mysql_query($query) or die("Query failed :$query " . mysql_error());

                        $gbcats = array();
                        $query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden FROM imas_gbcats WHERE courseid='{$_POST['usetemplate']}'";
                        $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
                        while ($row = mysql_fetch_row($result)) {
                            $query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden) VALUES ";
                            $frid = array_shift($row);
                            $irow = "'".implode("','",addslashes_deep($row))."'";
                            $query .= "('$cid',$irow)";
                            mysql_query($query) or die("Query failed :$query " . mysql_error());
                            $gbcats[$frid] = mysql_insert_id();
                        }
                        $copystickyposts = true;
                        $query = "SELECT itemorder,ancestors,outcomes,latepasshrs FROM imas_courses WHERE id='{$_POST['usetemplate']}'";
                        $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
                        $r = mysql_fetch_row($result);
                        $items = unserialize($r[0]);
                        $ancestors = $r[1];
                        $outcomesarr = $r[2];
                        $latepasshrs = $r[3];
                        if ($ancestors=='') {
                            $ancestors = intval($_POST['usetemplate']);
                        } else {
                            $ancestors = intval($_POST['usetemplate']).','.$ancestors;
                        }
                        $ancestors = addslashes($ancestors);
                        $outcomes = array();

                        $query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
                        $query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
                        $query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
                        $query .= "imas_assessments.courseid='{$_POST['usetemplate']}' AND imas_questionset.replaceby>0";
                        $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
                        while ($row = mysql_fetch_row($result)) {
                            $replacebyarr[$row[0]] = $row[1];
                        }

                        if ($outcomesarr!='') {
                            $query = "SELECT id,name,ancestors FROM imas_outcomes WHERE courseid='{$_POST['usetemplate']}'";
                            $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
                            while ($row = mysql_fetch_row($result)) {
                                if ($row[2]=='') {
                                    $row[2] = $row[0];
                                } else {
                                    $row[2] = $row[0].','.$row[2];
                                }
                                $row[1] = addslashes($row[1]);
                                $query = "INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES ";
                                $query .= "('$cid','{$row[1]}','{$row[2]}')";
                                mysql_query($query) or die("Query failed :$query " . mysql_error());
                                $outcomes[$row[0]] = mysql_insert_id();
                            }
                            function updateoutcomes(&$arr) {
                                global $outcomes;
                                foreach ($arr as $k=>$v) {
                                    if (is_array($v)) {
                                        updateoutcomes($arr[$k]['outcomes']);
                                    } else {
                                        $arr[$k] = $outcomes[$v];
                                    }
                                }
                            }
                            $outcomesarr = unserialize($outcomesarr);
                            updateoutcomes($outcomesarr);
                            $newoutcomearr = addslashes(serialize($outcomesarr));
                        } else {
                            $newoutcomearr = '';
                        }
                        $removewithdrawn = true;
                        $usereplaceby = "all";
                        $newitems = array();
                        require("../includes/copyiteminc.php");
                        copyallsub($items,'0',$newitems,$gbcats);
                        doaftercopy($_POST['usetemplate']);
                        $itemorder = addslashes(serialize($newitems));
                        $query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt',ancestors='$ancestors',outcomes='$newoutcomearr',latepasshrs='$latepasshrs' WHERE id='$cid'";
                        //copy offline
                        $offlinerubrics = array();
                        mysql_query($query) or die("Query failed : " . mysql_error());
                        $query = "SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric FROM imas_gbitems WHERE courseid='{$_POST['usetemplate']}'";
                        $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
                        $insarr = array();
                        while ($row = mysql_fetch_row($result)) {
                            $rubric = array_pop($row);
                            if (isset($gbcats[$row[3]])) {
                                $row[3] = $gbcats[$row[3]];
                            } else {
                                $row[3] = 0;
                            }
                            $ins = "('$cid','".implode("','",addslashes_deep($row))."')";
                            $query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES $ins";
                            mysql_query($query) or die("Query failed :$query " . mysql_error());
                            if ($rubric>0) {
                                $offlinerubrics[mysql_insert_id()] = $rubric;
                            }
                        }
                        copyrubrics();
                        mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
                    }

                    require("../header.php");
                    echo '<div class="breadcrumb">'. '<a href="admin.php">Admin</a> &gt; Course Creation Confirmation</div>';
                    echo '<h2>Your course has been created!</h2>';
                    echo '<p>For students to enroll in this course, you will need to provide them two things:<ol>';
                    echo '<li>The course ID: <b>'.$cid.'</b></li>';
                    if (trim($_POST['ekey'])=='') {
                        echo '<li>Tell them to leave the enrollment key blank, since you didn\'t specify one.  The enrollment key acts like a course ';
                        echo 'password to prevent random strangers from enrolling in your course.  If you want to set an enrollment key, ';
                        echo '<a href="forms.php?action=modify&id='.$cid.'">modify your course settings</a></li>';
                    } else {
                        echo '<li>The enrollment key: <b>'.$_POST['ekey'].'</b></li>';
                    }
                    echo '</ol></p>';
                    echo '<p>If you forget these later, you can find them by viewing your course settings.</p>';
                    echo '<a href="../course/course.php?cid='.$cid.'">Enter the Course</a>';
                    require("../footer.php");
                    exit;
                }
                break;
            case "delete":
                if ($myRights < 40) { echo "You don't have the authority for this action"; break;}
                if (isset($CFG['GEN']['doSafeCourseDelete']) && $CFG['GEN']['doSafeCourseDelete']==true) {
                    $oktodel = false;
                    if ($myRights < 75) {
                        $query = "SELECT id FROM imas_courses WHERE id='{$_GET['id']}' AND ownerid='$userid'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        if (mysql_num_rows($result)>0) {
                            $oktodel = true;
                        }
                    } else if ($myRights == 75) {
                        $query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        if (mysql_num_rows($result)>0) {
                            $oktodel = true;
                        }
                    } else if ($myRights==100) {
                        $oktodel = true;
                    }
                    if ($oktodel) {
                        $query = "UPDATE imas_courses SET available=4 WHERE id='{$_GET['id']}'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    break;
                } else {
                    $query = "DELETE FROM imas_courses WHERE id='{$_GET['id']}'";
                    if ($myRights < 75) { $query .= " AND ownerid='$userid'";}
                    if ($myRights == 75) {
                        $query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        if (mysql_num_rows($result)>0) {
                            $query = "DELETE FROM imas_courses WHERE id='{$_GET['id']}'";
                        } else {
                            break;
                        }
                    }
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    if (mysql_affected_rows()==0) { break;}

                    $query = "SELECT id FROM imas_assessments WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    require_once("../includes/filehandler.php");
                    while ($line = mysql_fetch_row($result)) {
                        deleteallaidfiles($line[0]);
                        $query = "DELETE FROM imas_questions WHERE assessmentid='{$line[0]}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                        $query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$line[0]}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                        $query = "DELETE FROM imas_exceptions WHERE assessmentid='{$line[0]}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }

                    $query = "DELETE FROM imas_assessments WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());

                    $query = "SELECT id FROM imas_drillassess WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($line = mysql_fetch_row($result)) {
                        $query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid='{$line[0]}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    $query = "DELETE FROM imas_drillassess WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());

                    $query = "SELECT id FROM imas_forums WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        $query = "SELECT id FROM imas_forum_posts WHERE forumid='{$row[0]}' AND files<>''";
                        $r2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
                        while ($row = mysql_fetch_row($r2)) {
                            deleteallpostfiles($row[0]);
                        }
                        /*$q2 = "SELECT id FROM imas_forum_threads WHERE forumid='{$row[0]}'";
                        $r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
                        while ($row2 = mysql_fetch_row($r2)) {
                            $query = "DELETE FROM imas_forum_views WHERE threadid='{$row2[0]}'";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                        }
                        */
                        $query = "DELETE imas_forum_views FROM imas_forum_views JOIN ";
                        $query .= "imas_forum_threads ON imas_forum_views.threadid=imas_forum_threads.id ";
                        $query .= "WHERE imas_forum_threads.forumid='{$row[0]}'";

                        $query = "DELETE FROM imas_forum_posts WHERE forumid='{$row[0]}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());

                        $query = "DELETE FROM imas_forum_threads WHERE forumid='{$row[0]}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    $query = "DELETE FROM imas_forums WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());

                    $query = "SELECT id FROM imas_wikis WHERE courseid='{$_GET['id']}'";
                    $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($wid = mysql_fetch_row($r2)) {
                        $query = "DELETE FROM imas_wiki_revisions WHERE wikiid=$wid";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                        $query = "DELETE FROM imas_wiki_views WHERE wikiid=$wid";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    $query = "DELETE FROM imas_wikis WHERE courseid='{$_GET['id']}'";

                    //delete inline text files
                    $query = "SELECT id FROM imas_inlinetext WHERE courseid='{$_GET['id']}'";
                    $r3 = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($ilid = mysql_fetch_row($r3)) {
                        $query = "SELECT filename FROM imas_instr_files WHERE itemid='{$ilid[0]}'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        $uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
                        while ($row = mysql_fetch_row($result)) {
                            $safefn = addslashes($row[0]);
                            $query = "SELECT id FROM imas_instr_files WHERE filename='$safefn'";
                            $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
                            if (mysql_num_rows($r2)==1) {
                                //unlink($uploaddir . $row[0]);
                                deletecoursefile($row[0]);
                            }
                        }
                        $query = "DELETE FROM imas_instr_files WHERE itemid='{$ilid[0]}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    $query = "DELETE FROM imas_inlinetext WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());

                    //delete linked text files
                    $query = "SELECT text,points,id FROM imas_linkedtext WHERE courseid='{$_GET['id']}' AND text LIKE 'file:%'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        $safetext = addslashes($row[0]);
                        $query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
                        $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
                        if (mysql_num_rows($r2)==1) {
                            //$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
                            $filename = substr($row[0],5);
                            //unlink($uploaddir . $filename);
                            deletecoursefile($filename);
                        }
                        if ($row[1]>0) {
                            $query = "DELETE FROM imas_grades WHERE gradetypeid={$row[2]} AND gradetype='exttool'";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                        }
                    }


                    $query = "DELETE FROM imas_linkedtext WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_items WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_teachers WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_students WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_tutors WHERE courseid='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());

                    $query = "SELECT id FROM imas_gbitems WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        $query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid={$row[0]}";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    $query = "DELETE FROM imas_gbitems WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_gbscheme WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_gbcats WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());

                    $query = "DELETE FROM imas_calitems WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());

                    $query = "SELECT id FROM imas_stugroupset WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    while ($row = mysql_fetch_row($result)) {
                        $q2 = "SELECT id FROM imas_stugroups WHERE groupsetid='{$row[0]}'";
                        $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
                        while ($row2 = mysql_fetch_row($r2)) {
                            $query = "DELETE FROM imas_stugroupmembers WHERE stugroupid='{$row2[0]}'";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                        }
                        $query = "DELETE FROM imas_stugroups WHERE groupsetid='{$row[0]}'";
                    }
                    $query = "DELETE FROM imas_stugroupset WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());

                    $query = "DELETE FROM imas_external_tools WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_content_track WHERE courseid='{$_GET['id']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                }
                break;
            case "remteacher":
                if ($myRights < 40) { echo "You don't have the authority for this action"; break;}
                $tids = array();
                if (isset($_GET['tid'])) {
                    $tids = array($_GET['tid']);
                } else if (isset($_POST['tid'])) {
                    $tids = $_POST['tid'];
                    if (count($tids)==$_GET['tot']) {
                        array_shift($tids);
                    }
                }
                foreach ($tids as $tid) {
                    if ($myRights < 100) {
                        $query = "SELECT imas_teachers.id FROM imas_teachers,imas_users WHERE imas_teachers.id='$tid' AND imas_teachers.userid=imas_users.id AND imas_users.groupid='$groupid'";
                        $result = mysql_query($query) or die("Query failed : " . mysql_error());
                        if (mysql_num_rows($result)>0) {
                            $query = "DELETE FROM imas_teachers WHERE id='$tid'";
                            mysql_query($query) or die("Query failed : " . mysql_error());
                        } else {
                            //break;
                        }

                        //$query = "DELETE imas_teachers FROM imas_users,imas_teachers WHERE imas_teachers.id='{$_GET['tid']}' ";
                        //$query .= "AND imas_teachers.userid=imas_users.id AND imas_users.groupid='$groupid'";
                    } else {
                        $query = "DELETE FROM imas_teachers WHERE id='$tid'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                }

//                header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forms.php?action=chgteachers&id={$_GET['cid']}");
                exit;
            case "addteacher":
                if ($myRights < 40) { echo "You don't have the authority for this action"; break;}
                if ($myRights < 100) {
                    $query = "SELECT imas_users.groupid FROM imas_users,imas_courses WHERE imas_courses.ownerid=imas_users.id AND imas_courses.id='{$_GET['cid']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    if (mysql_result($result,0,0) != $groupid) {
                        break;
                    }
                }
                $tids = array();
                if (isset($_GET['tid'])) {
                    $tids = array($_GET['tid']);
                } else if (isset($_POST['atid'])) {
                    $tids = $_POST['atid'];
                }
                $ins = array();
                foreach ($tids as $tid) {
                    $ins[] = "('$tid','{$_GET['cid']}')";
                }
                if (count($ins)>0) {
                    $query = "INSERT INTO imas_teachers (userid,courseid) VALUES ".implode(',',$ins);
                    mysql_query($query) or die("Query failed : " . mysql_error());
                }
//                header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forms.php?action=chgteachers&id={$_GET['cid']}");
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
//                                $path = AppUtility::getHomeURL().'Uploads/qimages/';
                                $path = 'openmath/web/Uploads/qimages/';

                                $n = $tar->extractToDir('../assets/qimages/');
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
                if ($myRights < 40) { echo "You don't have the authority for this action"; break;}
                $exec = false;
                $query = "UPDATE imas_courses SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['id']}'";
                if ($myRights < 75) {
                    $query .= " AND ownerid='$userid'";
                }
                if ($myRights==75) {
                    $query = "SELECT imas_courses.id FROM imas_courses,imas_users WHERE imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    if (mysql_num_rows($result)>0) {
                        $query = "UPDATE imas_courses SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['id']}'";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                        $exec = true;
                    }
                    //$query = "UPDATE imas_courses,imas_users SET imas_courses.ownerid='{$_POST['newowner']}' WHERE ";
                    //$query .= "imas_courses.id='{$_GET['id']}' AND imas_courses.ownerid=imas_users.id AND imas_users.groupid='$groupid'";
                } else {
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    $exec = true;
                }
                if ($exec && mysql_affected_rows()>0) {
                    $query = "SELECT id FROM imas_teachers WHERE courseid='{$_GET['id']}' AND userid='{$_POST['newowner']}'";
                    $result = mysql_query($query) or die("Query failed : " . mysql_error());
                    if (mysql_num_rows($result)==0) {
                        $query = "INSERT INTO imas_teachers (userid,courseid) VALUES ('{$_POST['newowner']}','{$_GET['id']}')";
                        mysql_query($query) or die("Query failed : " . mysql_error());
                    }
                    $query = "DELETE FROM imas_teachers WHERE courseid='{$_GET['id']}' AND userid='$userid'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                }

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
                if ($myRights <60) { echo "You don't have the authority for this action"; break;}
                $query = "SELECT imas_users.id,imas_users.groupid FROM imas_users JOIN imas_diags ON imas_users.id=imas_diags.ownerid AND imas_diags.id='{$_GET['id']}'";
                $result = mysql_query($query) or die("Query failed : " . mysql_error());
                $row = mysql_fetch_row($result);
                if (($myRights<75 && $row[0]==$userid) || ($myRights==75 && $row[1]==$groupid) || $myRights==100) {
                    $query = "DELETE FROM imas_diags WHERE id='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                    $query = "DELETE FROM imas_diag_onetime WHERE diag='{$_GET['id']}'";
                    mysql_query($query) or die("Query failed : " . mysql_error());
                }
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
}