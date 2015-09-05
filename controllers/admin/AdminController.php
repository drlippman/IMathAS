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
}