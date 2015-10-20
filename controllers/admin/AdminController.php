<?php

namespace app\controllers\admin;
use app\components\filehandler;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use app\models\_base\BaseImasDiags;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\CalItem;
use app\models\ContentTrack;
use app\models\Course;
use app\models\DiagOneTime;
use app\models\Diags;
use app\models\ExternalTools;
use app\models\Exceptions;
use app\models\forms\ChangeRightsForm;
use app\models\ForumPosts;
use app\models\Forums;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\GbCats;
use app\models\GbItems;
use app\models\GbScheme;
use app\models\Grades;
use app\models\Groups;
use app\models\InlineText;
use app\models\InstrFiles;
use app\models\Items;
use app\models\Libraries;
use app\models\LibraryItems;
use app\models\LinkedText;
use app\models\Message;
use app\models\Questions;
use app\models\QImages;
use app\models\QuestionSet;
use app\models\Sessions;
use app\models\Student;
use app\models\StuGroupMembers;
use app\models\Stugroups;
use app\models\StuGroupSet;
use app\models\Teacher;
use app\models\Tutor;
use app\models\Wiki;
use app\models\WikiRevision;
use app\models\WikiView;
use Yii;
use app\models\forms\AddNewUserForm;
use app\components\AppUtility;
use app\models\User;
use app\components\AppConstant;
use app\models\forms\AdminDiagnosticForm;
use tar;
use yii\base\Exception;

class AdminController extends AppController
{
    public function beforeAction($action)
    {
        $actionPath = Yii::$app->controller->action->id;
        $user = $this->getAuthenticatedUser();
        return $this->accessForAdminController($user['rights'],$actionPath);
    }

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
        $showUser = $this->getParamVal('showusers');
        AppUtility::tzdate("g:i a",time());
        if ($myRights == AppConstant::NUMERIC_HUNDREAD) {
            if (isset($showUser))
            {
                setcookie('showusers',$showUser);
                $showusers = $showUser;
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
                $showcourses = $showCid;
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
        if($resultTeacher)
        {
            foreach($resultTeacher as $key => $teacher)
            {
                $page_teacherSelectVal[$i] = $teacher['id'];
                $page_teacherSelectLabel[$i] = $teacher['LastName'] . ", " . $teacher['FirstName']. ' ('.$teacher['SID'].')';
                $i++;
            }

        }
        if ($myRights >= AppConstant::DIAGNOSTIC_CREATOR_RIGHT)
        {
            $result = Diags::getDiagnostic($myRights, $userId, $groupId);
            $i = AppConstant::NUMERIC_ZERO;
            if($result)
            {
                foreach($result as $key => $row){
                    $page_diagnosticsId[$i] = $row['id'];
                    $page_diagnosticsName[$i] = $row['name'];
                    $page_diagnosticsAvailable[$i] = ($row['public']&1) ? "Yes" : "No";
                    $page_diagnosticsPublic[$i] = ($row['public']&2) ? "Yes" : "No";
                    $i++;
                }
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
        $this->includeJS(['general.js', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js','adminIndex.js']);
        return $this->renderWithData('index', ['showCid' => $showCid ,'users' => $users, 'page_userDataId' =>$page_userDataId,'page_userDataLastName' => $page_userDataLastName, 'page_userDataFirstName' => $page_userDataFirstName, 'page_userDataSid' => $page_userDataSid,'page_userDataEmail' => $page_userDataEmail,'page_userDataType' => $page_userDataType,'page_userDataLastAccess' => $page_userDataLastAccess, 'page_userSelectVal' => $page_userSelectVal,'page_userSelectLabel' => $page_userSelectLabel,'showusers' => $showusers,'myRights' => $myRights, 'page_courseList' => $page_courseList, 'resultTeacher' => $resultTeacher, 'page_diagnosticsId' => $page_diagnosticsId, 'page_diagnosticsName' => $page_diagnosticsName, 'page_diagnosticsAvailable' => $page_diagnosticsAvailable, 'page_diagnosticsPublic' => $page_diagnosticsPublic, 'page_userBlockTitle' => $page_userBlockTitle, 'showcourses' => $showcourses, 'page_teacherSelectVal' => $page_teacherSelectVal, 'page_teacherSelectLabel' => $page_teacherSelectLabel, 'userId' => $userId, 'userName' => $userName]);
    }
/*
 * This method to add new user
 */
    public function actionAddNewUser()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
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
        $this->layout = 'master';
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
        $this->layout = 'master';
        $userId = $user->id;
        $myRights = $user['rights'];
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
                } else if (strpos($k,'ipout') !== FALSE) {
                    $ips[] = $v;
                } else if (strpos($k,'pwout') !== FALSE) {
                    $pws[] = $v;
                } else if (strpos($k,'pwsout') !== FALSE) {
                    $spws[] = $v;
                }
            }
            if (isset($params['alpha'])) {
                natsort($sel1);
                $sel1 = array_values($sel1);
            }
            $sel1list = implode(',',$sel1);
            $iplist = implode(',',$ips);
            $pwlist = implode(',',$pws) . ';'. implode(',',$spws);
            $public = 1*$params['avail'] + 2*$params['public'] + 4*$params['reentry'];

            if ($params['termtype']=='mo') {
                $params['term'] = '*mo*';
            } else if ($params['termtype']=='day') {
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
                for ($i = 0; $i < count($s1l); $i++) {
                    $sel2[$s1l[$i]] = explode('~',$s2l[$i]);
                }
                $sel2name = $row['sel2name'];
                $aids = explode(',',$row['aidlist']);
                $page_updateId = $params['id'];
                $forceregen = $row['forceregen'];
            } else {
                $sel2name = "instructor";
                $aids = array();
                $page_updateId = AppConstant::NUMERIC_ZERO;
                $forceregen = AppConstant::NUMERIC_ZERO;
            }
            foreach($sel1 as $k=>$s1) {

                $page_selectValList[$k] = array();
                 $page_selectLabelList[$k] = array();
                $page_selectName[$k] = "aid" . $k;

                $i= AppConstant::NUMERIC_ZERO;
                $courseId = $params['cid'];
                $result = Assessments::getByCId($courseId);
                foreach($result as $key => $row)
                {
                    $page_selectValList[$k][$i] = $row['id'];
                    $page_selectLabelList[$k][$i] = $row['name'];
                    if (isset($aids[$k]) && $row['id']==$aids[$k]) {
                        $page_selectedOption[$k] = $aids[$k];
                    }
                    $i++;
                }
            }
            $page_cntScript = (isset($sel2[$s1]) && count($sel2[$s1])>0) ? "<script> cnt['out$k'] = ".count($sel2[$s1]).";</script>\n"  : "<script> cnt['out$k'] = 0;</script>\n";
        } elseif (isset($_GET['step']) && $_GET['step']== AppConstant::NUMERIC_THREE) {  //STEP 3 DATA PROCESSING
            $sel1 = explode(',',$params['sel1list']);
            $aids = array();
            $forceregen = AppConstant::NUMERIC_ZERO;
            for ($i = 0;$i < count($sel1);$i++) {
                $aids[$i] = $params['aid'.$i];
                if (isset($params['reg'.$i]) && $params['reg'.$i] == 1) {
                    $forceregen = $forceregen ^ (1<<$i);
                }
            }
            $aidlist = implode(',',$aids);
            $sel2 = array();
            foreach ($params as $k=>$v) {
                if (strpos($k,'out')!==FALSE) {
                    $n = substr($k,3,strpos($k,'-')-3);
                    $sel2[$n][] = ucfirst($v);
                }
            }
            if (isset($params['useoneforall'])) { //use first sel2 for all
                if (isset($params['alpha'])) {
                    sort($sel2[0]);
                }
                $sel2[0] = implode('~',$sel2[0]);
                for ($i=1; $i<count($sel1); $i++) {
                    $sel2[$i] = $sel2[0];
                }
            } else {
                for ($i = 0;$i < count($sel2);$i++) {
                    if (isset($params['alpha'])) {
                        sort($sel2[$i]);
                    }
                    $sel2[$i] = ($sel2[$i]);
                }
            }
            $sel2list = implode(';',$sel2);

            if (isset($params['id']) && $params['id'] != 0) {
                $query = new Diags();
                $id = $query->updateDiagnostics($params);
                $page_successMsg = "<br/><div class='col-md-12'>Diagnostic Updated</div><br/>";
            } else {
                $query = new Diags();
                $id = $query->saveDiagnostic($params, $userId);
                $page_successMsg = "<BR class=form><div class='col-md-2'>Diagnostic Added</div><br class='form'>";
            }
            $page_diagLink = "<BR/><div class=col-md-10>Direct link to diagnostic  <b>".AppUtility::getURLFromHome('site', 'diagnostics?id='.$id)."</b></div><BR class=form><br>";
            $page_publicLink = ($params['public']&2) ? "<div class=col-md-10>Diagnostic is listed on the public listing at <b>".AppUtility::getURLFromHome('site', 'diagnostics')."</b></div><BR class=form><br>\n" : ""  ;

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
                if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT) {
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
                $cid = AppConstant::NUMERIC_ZERO;
                $public = AppConstant::NUMERIC_SEVEN;
                $idprompt = "Enter your student ID number";
                $ips = '';
                $pws = '';
                $sel = 'course';
                $sel1list = '';
                $term = '';
                $entryformat = 'C0';
                $forceregen = AppConstant::NUMERIC_ZERO;
                $reentrytime = AppConstant::NUMERIC_ZERO;
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
            $i = AppConstant::NUMERIC_ZERO;
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
            for ($j = 0;$j < 15;$j++) {
                $page_entryNums['val'][$j] = $j;
                if ($j == 0) {
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
        'page_selectLabelList' => $page_selectLabelList, 'page_selectedOption' => $page_selectedOption, 'sel2list' => $sel2list, 'aidlist' => $aidlist, 'overwriteBody' => $overwriteBody, 'page_cntScript' => $page_cntScript);
        return $this->renderWithData('diagnostics', $responseData);
    }

    public function actionDeleteDiagnosticsAjax()
    {
        $params = $this->getRequestParams();
        $id = $params['diagnoId'];
        $responseData = array('id' => $id);
        return $this->successResponse($responseData);
    }

   public function actionExternalTool()
   {
       $this->guestUserHandler();
       $user = $this->getAuthenticatedUser();
       $this->layout = 'master';
       $userId = $user->id;
       $myRights = $user['rights'];
       $groupId= $user['groupid'];
       $isAdmin = false;
       $isGrpAdmin = false;
       $isTeacher = false;
       $id = $this->getParamVal('id');
       $delete = $this->getParamVal('delete');
       $params = $this->getRequestParams();
       $courseId = (isset($params['cid'])) ? $params['cid'] : "admin";
       $course = Course::getById($courseId);
       $teacherId = $this->isTeacher($userId,$courseId);
       $err = '';

       if ($myRights == AppConstant::GROUP_ADMIN_RIGHT && $courseId=='admin') {
           $isGrpAdmin = true;
       } else if ($myRights == AppConstant::ADMIN_RIGHT && $courseId == 'admin') {
           $isAdmin = true;
       } else {
           $isTeacher = true;
       }
       if (isset($params['ltfrom'])) {
           $ltfrom = '&amp;ltfrom='.$params['ltfrom'];
       } else {
           $ltfrom = '';
       }

       if (isset($params['tname']))
       {
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
               if ($params['id'] == 'new') {
                   /**
                    * Create tool
                    */
                   $external = new ExternalTools();
                   $external->saveExternalTool($courseId,$groupId,$params, $isTeacher, $isGrpAdmin, $isAdmin, $privacy);
               } else
               {
                   /**
                    * Update external tool
                    */
                    $params['groupId'] = $groupId;
                   if ($isTeacher) {
                       $attr = 'courseid';
                       $attrValue = $courseId;
                       ExternalTools::updateExternalToolByAdmin($params, $isAdmin,$attrValue,$attr, $privacy);
                   } else if ($isGrpAdmin)
                   {
                       $attr = 'groupid';
                       $attrValue = $groupId;
                       ExternalTools::updateExternalToolByAdmin($params, $isAdmin,$attrValue,$attr, $privacy);
                   }
                   else{
                       if($isAdmin)
                       {
                           if($params['scope'] == AppConstant::NUMERIC_ZERO)
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
       }  else if (isset($delete) && $delete =='true')
       {
           $id = intval($params['id']);
           if ($id > AppConstant::NUMERIC_ZERO)
           {
                ExternalTools::deleteById($id, $isTeacher, $isGrpAdmin, $courseId, $groupId);
           }
           $ltfrom = str_replace('&amp;','&',$ltfrom);
           return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId.$ltfrom));
       } else
       {
           if (isset($params['delete']))
           {

           } else if ($id)
           {
               if ($params['id'] == 'new')
               {
                   $name = ''; $url = ''; $key = ''; $secret = ''; $custom = ''; $privacy = AppConstant::NUMERIC_THREE; $grp = AppConstant::NUMERIC_ZERO;
               }
               else
               {
                   $result = ExternalTools::getByRights($id, $isTeacher, $courseId, $isGrpAdmin, $groupId);
                   if (count($result) == AppConstant::NUMERIC_ZERO) { die("invalid id");}
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
       $this->includeCSS(['course/items.css']);
       $responseData = array('myRights' => $myRights, 'teacherId' => $teacherId, 'params' => $params, 'err' => $err, 'isAdmin' => $isAdmin, 'isGrpAdmin' => $isGrpAdmin, 'resultFirst' => $resultFirst, 'courseId' => $courseId, 'ltfrom' => $ltfrom,
       'name' => $name, 'grp' => $grp, 'privacy' => $privacy, 'url' => $url, 'key' => $key, 'secret' => $secret, 'custom' => $custom, 'course' => $course);
       return $this->renderWithData('externalTool', $responseData);
   }

   public function actionDeleteExternalToolAjax()
   {
       $params = $this->getRequestParams();
       $cid = $params['cid'];
       $id = $params['ExternalToolId'];
       $extTool = ExternalTools::getExternalToolName($id);
       $nameOfExtTool = $extTool['name'];
       $responseData = array('nameOfExtTool' => $nameOfExtTool,'cid' => $cid,'id' => $id);
       return $this->successResponse($responseData);
   }

    public function actionForms()
    {
        $installname = "OpenMath";
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $myRights = $currentUser['rights'];
        $this->layout = 'master';
        $enablebasiclti = true;
        $groupId= $currentUser['groupid'];
        $action = $params['action'];
        $getAction = $this->getParamVal('action');
        $getId = $this->getParamVal('id');
        switch($action) {
            case "delete":
                $course = Course::getById($params['id']);
                $name = $course['name'];
                break;
            case "deladmin":
                if($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                 break;
            case "chgpwd":
                if($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                 break;
            case "chgrights":
                if($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
            case "newadmin":
            if($myRights < AppConstant::GROUP_ADMIN_RIGHT)
            {
                $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                return $this->redirect($this->goHome());
            }
            if ($getAction == "newadmin") {
                $oldGroup = 0;
                $oldRights = 10;
            } else {
                $line = User::getDataById($getId);
            }
            if ($myRights == AppConstant::ADMIN_RIGHT) {
                $resultGroup = Groups::getIdNameByName();
            }
                break;
            case "addnewcourse":
                break;
            case "modify":
            case "addcourse":
            if ($params['action']=='modify')
            {
                $line = Course::getById($params['cid']);
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
                $assessment = Assessments::getByName($courseid);
            } else
            {
                $courseid ="Will be assigned when the course is created";
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
                break;
            case "chgteachers":
                 break;
            case "importmacros":
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                break;
            case "importqimages":
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                break;
            case "importcoursefiles":
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                break;
            case "transfer":
                $queryUser = User::getByUserRight($myRights, $groupId);
                 break;
            case "deloldusers":
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                break;
            case "listltidomaincred": // access for admin
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $users = User::getByRights();
                $groupsName = Groups::getIdAndName();
                break;
            case "modltidomaincred": // // access for admin
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                 $user = User::getById($params['id']);
                $groupsName = Groups::getIdAndName();
                break;
            case "listgroups": // access for admin
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $groupsName = Groups::getIdAndName();
                break;
            case "modgroup": // access for admin
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $groupsName = Groups::getById($params['id']);
                break;
            case "removediag": //// access for admin,gadmin,diagnostic
                if ($myRights < AppConstant::DIAGNOSTIC_CREATOR_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                break;
        }
        $this->includeCSS(['assessment.css','dataTables.bootstrap.css']);
        $this->includeJS(['general.js','forms.js','jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        $responseData = array('users' => $users,'params'=> $params,'groupsName' => $groupsName,'user' =>$user,'myRights' => $myRights,'course' => $course,'action' => $action, 'courseid' => $courseid, 'name' => $name,
            'ekey' => $ekey, 'hideicons' => $hideicons, 'picicons' => $picicons, 'allowunenroll'=> $allowunenroll, 'copyrights' => $copyrights, 'msgset' => $msgset, 'toolset' => $toolset, 'msgmonitor' => $msgmonitor, 'msgQtoInstr' => $msgQtoInstr,'cploc' => $cploc, 'topbar' => $topbar, 'theme' => $theme,
            'chatset' => $chatset, 'showlatepass' => $showlatepass, 'istemplate' => $istemplate,
            'avail' => $avail, 'lockaid' => $lockaid, 'deftime' => $deftime, 'deflatepass' => $deflatepass,
            'ltisecret' => $ltisecret, 'defstimedisp' => $defstimedisp, 'deftimedisp' => $deftimedisp,'assessment' => $assessment, 'enablebasiclti' => $enablebasiclti, 'installname' => $installname, 'queryUser' => $queryUser, 'getAction' => $getAction, 'getId' => $getId, 'line' => $line, 'resultGroup' => $resultGroup);
        return $this->renderWithData('forms',$responseData);
    }

    public function actionDeleteCourseAjax()
    {
        $params = $this->getRequestParams();
        $id = $params['id'];
        $course = Course::getById($params['id']);
        $name = $course['name'];
        $responseData = array('name' => $name,'id' => $id);
        return $this->successResponse($responseData);
    }

    public function actionDeleteUserAjax()
    {
        $params = $this->getRequestParams();
        $id = $params['id'];
        $responseData = array('id' => $id);
        return $this->successResponse($responseData);
    }

    public function actionActions()  // access only admin
    {
        $params = $this->getRequestParams();
        $allowmacroinstall = true;
        $this->layout = 'master';
        $currentUser = $this->getAuthenticatedUser();
        $userId = $currentUser['id'];
        $getId = $this->getParamVal('id');
        $action = $params['action'];
        $myRights = $currentUser['rights'];
        $groupid = AppConstant::NUMERIC_ZERO;
        switch($action) {
            case "emulateuser":
                if ($myRights < AppConstant::ADMIN_RIGHT )
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $sessionId = $this->getSessionId();
                $be = $params['uid'];
                Sessions::updateUId($be,$sessionId);
                break;
            case "chgrights":
                if ($myRights < AppConstant::ADMIN_RIGHT && $params['newrights'] > AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                if ($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }

                $group = $params['group'];
                $newRights = $params['newrights'];
                User::updateUserRight($myRights, $newRights, $group, $getId, $groupid);
                if ($myRights == AppConstant::GROUP_ADMIN_RIGHT) { //update library groupids
                    $groupId = $params['group'];
                    Libraries::updateGroupIdAdmin($getId, $groupId);
                }
                break;
            case "resetpwd":
                if ($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
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
                if ($myRights < 75)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    User::deleteAdmin($groupid, $getId);
                } else
                {
                    User::deleteUserById($getId);
                }
                Student::deleteByUserId($getId);
                Teacher::deleteUser($getId);
                AssessmentSession::deleteByUserId($getId);
                Exceptions::deleteByUserId($getId);
                Message::deleteByMsgTo($getId);
                Message::setIsRead($getId);
                Message::deleteByMsgFrom($getId);
                Message::setMsgFrom($getId);
                filehandler::deletealluserfiles($getId);
                break;
            case "chgpwd":
                break;
            case "newadmin":
                if ($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                if ($myRights < AppConstant::ADMIN_RIGHT && $params['newrights'] > AppConstant::GROUP_ADMIN_RIGHT)
                {
                    break;
                }
                $adminName = $params['adminname'];
                $row = User::getBySIDForAdmin($adminName);
                if ($row != null) {
                    echo "<html><body>Username is already used.\n";
                    echo "<a href=\"forms?action=newadmin\">Try Again</a> or ";
                    echo "<a href=\"forms?action=chgrights&id={$row['id']}\">Change rights for existing user</a></body></html>\n";
                    exit;
                }
                if (isset($CFG['GEN']['newpasswords'])) {
                    $md5pw = password_hash($params['password'], PASSWORD_DEFAULT);
                } else {
                    $md5pw =md5($params['password']);
                }
                if ($myRights < 100) {
                    $newGroup = $groupid;
                } else if ($myRights == 100) {
                    $newGroup = $params['group'];
                }
                if (isset($CFG['GEN']['homelayout'])) {
                    $homelayout = $CFG['GEN']['homelayout'];
                } else {
                    $homelayout = '|0,1,2||0,1';
                }
                $firstName = $params['firstname'];
                $lastName = $params['lastname'];
                $rights = $params['newrights'];
                $email = $params['email'];
                $user = new User();
                $newUserid = $user->addUserFromAdmin($adminName, $md5pw, $firstName, $lastName,$rights, $email, $newGroup,$homelayout);
                if (isset($CFG['GEN']['enrollonnewinstructor'])) {
                    $valbits = array();
                    foreach ($CFG['GEN']['enrollonnewinstructor'] as $ncid) {
                        $valbits[] = "('$newUserid','$ncid')";
                        $newUserId = $newUserid;
                        $courseId = $ncid;

                    }
                    $query = new Student();
                    $query->insertUIdCId($newUserId, $courseId);
                }
                break;
            case "logout":
                break;
            case "modify":
            case "addcourse":
            if ($myRights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT)
            {
                $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                return $this->redirect($this->goHome());
            }
            $avail = AppConstant::NUMERIC_THREE - $params['stuavail'] - $params['teachavail'];
            $istemplate = AppConstant::NUMERIC_ZERO;
            if ($myRights == AppConstant::ADMIN_RIGHT) {
                if (isset($params['istemplate'])) {
                    $istemplate += AppConstant::NUMERIC_ONE;
                }
                if (isset($params['isselfenroll'])) {
                    $istemplate += AppConstant::NUMERIC_FOUR;
                }
                if (isset($params['isguest'])) {
                    $istemplate += AppConstant::NUMERIC_EIGHT;
                }
            }
            if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT) {
                if (isset($params['isgrptemplate'])) {
                    $istemplate += AppConstant::NUMERIC_TWO;
                }
            }
            $params['ltisecret'] = trim($params['ltisecret']);

            if ($params['action'] == 'modify') {
                $available = $this->getSanitizedValue($params['avail'], AppConstant::AVAILABLE_NOT_CHECKED_VALUE);
                $toolSet = $this->getSanitizedValue($params['toolSet'], AppConstant::NAVIGATION_NOT_CHECKED_VALUE);
                $defTime = AppUtility::calculateTimeDefference($params['defstime'], $params['deftime']);
                if ($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $columnName = 'ownerid'; $columnValue = $userId;
                    $updateResult = new Course();
                    $updateResult->updateCourse($params, $available, $toolSet, $defTime, $columnName, $columnValue);
                }else{
                    $columnName = 'id'; $columnValue = $params['id'];
                    $updateResult = new Course();
                    $updateResult->updateCourse($params, $available, $toolSet, $defTime, $columnName, $columnValue);
                }
                return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
            } else {

                $blockcnt = AppConstant::NUMERIC_ONE;
                $itemorder = addslashes(serialize(array()));
                $query = new Course();
                $cid = $query->create($userId, $params,$blockcnt);

                $queryTeacher = new Teacher();
                $queryTeacher->create($userId, $cid);

                $queryGBSchema = new GbScheme();
                $queryGBSchema->create($cid);
                $responseData = array('params' => $params, 'cid' => $cid, 'action' => 'addnewcourse');
                return $this->renderWithData('forms', $responseData);
            }
            break;
            case "delete":
                if ($myRights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $connection = $this->getDatabase();
                $transaction = $connection->beginTransaction();
                try{
                if (isset($CFG['GEN']['doSafeCourseDelete']) && $CFG['GEN']['doSafeCourseDelete']==true) {
                    $oktodel = false;
                    if ($myRights < AppConstant::GROUP_ADMIN_RIGHT) {
                        $result = Course::getByIdandOwnerIdByAll($params['id'], $userId);
                        if (count($result) > AppConstant::NUMERIC_ZERO) {
                            $oktodel = true;
                        }
                    } else if ($myRights == AppConstant::GROUP_ADMIN_RIGHT) {
                        $result = Course::getCidAndUid($params, $groupid);
                        if (count($result) > AppConstant::NUMERIC_ZERO) {
                            $oktodel = true;
                        }
                    } else if ($myRights == AppConstant::ADMIN_RIGHT) {
                        $oktodel = true;
                    }
                    if ($oktodel) {
                        Course::setAvailable($params);
                    }
                } else {
                    $affectedRowsData = Course::deleteByCourseId($params, $myRights, $userId);
                    if ($myRights == AppConstant::GROUP_ADMIN_RIGHT)
                    {
                        $result = Course::getCidAndUid($params, $groupid);
                        if (count($result) > AppConstant::NUMERIC_ZERO) {
                           Course::deleteById($params);
                        } else {
                            break;
                        }
                    }
                    if ($affectedRowsData == AppConstant::NUMERIC_ZERO) { break;}
                    $result = Assessments::getByAssId($params['id']);
                    if($result){
                    foreach($result as $key => $line){
                         filehandler::deleteallaidfiles($line['id']);
                         Questions::deleteByAssessmentId($line['id']);
                         AssessmentSession::deleteByAssessmentId($line['id']);
                         Exceptions::deleteByAssessmentId($line['id']);
                    }
                     Assessments::deleteByCourseId($params['id']);
                    }
                    /**
                     * Forum
                     */
                    $result = Forums::getByCid($params['id']);
                    if($result){
                    foreach($result as $key => $row) {
                        $r2 = ForumPosts::getByForumPostId($row['id']);
                        foreach($r2 as $row1) {
                            filehandler::deleteallpostfiles($row1['id']);
                        }
                         ForumView::deleteByForumId($row['id']);
                         ForumPosts::deleteForumPost($row['id']);
                         ForumThread::deleteByForumId($row['id']);
                    }
                     Forums::deleteByCourseId($params['id']);
                    }
                     /**
                      * delete wiki
                      */
                    $r2 = Wiki::getByCourseIdAll($params['id']);
                    if($r2){
                    foreach($r2 as $key => $wid)
                    {
                           WikiRevision::deleteByWikiRevisionId($wid['id']);
                           WikiView::deleteWikiId($wid['id']);
                    }
                     Wiki::deleteCourseId($params['id']);
                    }
                    /**
                     * delete inline text files
                     */
                    $r3 = InlineText::getByCourseIdAll($params['id']);
                    if($r3){
                        foreach($r3 as $key => $ilid)
                        {
                            $result = InstrFiles::getByName($ilid['id']);
                            $uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
                            foreach($result as $key1 => $row) {
                                $safefn = $row['filename'];
                                $r2 = InstrFiles::getIdName($safefn);
                                if($r2) {
                                    if (count($r2) == 1) {
                                        filehandler::deletecoursefile($row['filename']);
                                    }
                                }
                            }
                            InstrFiles::deleteByItemId($ilid['id']);
                        }
                        InlineText::deleteCourseId($params['id']);
                    }
                    /**
                     * delete linked text files
                     */
                    $result = LinkedText::getByTextAndId($params['id']);
                    foreach($result as $key => $row) {
                        $safetext = $row['text'];
                        $r2 = LinkedText::getByIdForFile($safetext);
                        if (count($r2) == 1) {
                            $filename = substr($row['text'],5);
                            filehandler::deletecoursefile($filename);
                        }
                        if ($row['points'] > 0)
                        {
                            Grades::deleteById($row['id']);
                        }
                    }
                   LinkedText::deleteByCourseId($params['id']);
                   Items::deleteByCourseId($params['id']);
                   Teacher::deleteByCourseId($params['id']);
                   Student::deleteByCourseId($params['id']);
                   Tutor::deleteByCourseId($params['id']);
                    $result = GbItems::getByCourseIdAll($params['id']);
                    foreach($result as $key => $row){
                        Grades::deleteByGradeId($row['id']);
                    }
                    GbItems::deleteByCId($params['id']);
                    GbScheme::deleteByCourseId($params['id']);
                    GbCats::deleteByCourseId($params['id']);
                    CalItem::deleteByCourseIdOne($params['id']);
                    $result = StuGroupSet::getByCid($params['id']);
                     foreach($result as $key => $row)
                     {
                       $r2 = Stugroups::getByGrpSetId($row['id']);
                       foreach($r2 as $key1 => $row2)
                       {
                            StuGroupMembers::deleteByStudGrpId($row2['id']);
                       }
                       Stugroups::deleteByStudGrpId($row['id']);
                    }
                    StuGroupSet::deleteByCourseId($params['id']);
                    ExternalTools::deleteByCourseId($params['id']);
                    ContentTrack::deleteByCourseId($params['id']);
                }
                $transaction->commit();
                }catch (Exception $e){
                    $transaction->rollBack();
                    return false;
                }
                break;
            case "remteacher":
                exit;
            case "addteacher":
                exit;
            case "importmacros":
                if ($myRights < AppConstant::ADMIN_RIGHT || !$allowmacroinstall) {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
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
                                if (strpos($buffer,"//") === 0) {
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
                    $this->setWarningFlash(AppConstant::ERROR_UPLOADING_FILE);
                    return $this->redirect('forms?action=importmacros');
                }
            case "importqimages":
                if ($myRights < AppConstant::ADMIN_RIGHT || !$allowmacroinstall) {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
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
                               $n = $tar->extractToDir(AppConstant::UPLOAD_DIRECTORY.'macro/');
                            }
                            echo "<p>Extracted $n files.  <a href='".AppUtility::getURLFromHome('admin','admin/index')."'>Continue</a></p>\n";
                            exit;
                        } else {
                            $this->setWarningFlash(AppConstant::EMPTY_FILE);
                            return $this->redirect('forms?action=importqimages');
                        }
                    }
                    unlink($uploadfile);
                    break;
                } else {
                    $this->setWarningFlash(AppConstant::ERROR_UPLOADING_FILE);
                    return $this->redirect('forms?action=importqimages');
                }
            case "importcoursefiles":
                if ($myRights < AppConstant::ADMIN_RIGHT || !$allowmacroinstall) {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $uploaddir = AppConstant::UPLOAD_DIRECTORY.'coursefile/';
                $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
                if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
                    if (strpos($uploadfile,'.zip')!==FALSE && class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        $res = $zip->open($uploadfile);
                        $ne = AppConstant::NUMERIC_ZERO;  $ns = AppConstant::NUMERIC_ZERO;
                        if ($res===true) {
                            for($i = 0; $i < $zip->numFiles; $i++) {

                                if (filehandler::doesfileexist('cfile',$zip->getNameIndex($i))) {
                                    $ns++;
                                } else {
                                    $zip->extractTo("course/files/", array($zip->getNameIndex($i)));
                                    filehandler::relocatecoursefileifneeded("../course/files/".$zip->getNameIndex($i),$zip->getNameIndex($i));
                                    $ne++;
                                }
                            }
                            $this->setWarningFlash('Extracted '.$ne.' files.');
                            return $this->redirect('index');
                        } else {
                            $this->setWarningFlash(AppConstant::EMPTY_FILE);
                            return $this->redirect('forms?action=importcoursefiles');
                        }
                    }
                    unlink($uploadfile);
                    break;
                } else {
                     $this->setWarningFlash(AppConstant::ERROR_UPLOADING_FILE);
                     return $this->redirect('forms?action=importcoursefiles');
                }
            case "transfer":
                if($myRights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT)
                {
                   $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                   return $this->redirect($this->goHome());
                }
                $exec = false;
                if ($myRights < AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $columnName = 'ownerid'; $columnValue = $userId;
                    $updateResult = new Course();
                    $updateResult->setOwnerId($params, $columnName, $columnValue);
                } else
                {
                    $columnName = 'id'; $columnValue = $params['id'];
                    $updateResult = new Course();
                    $updateResult->setOwnerId($params, $columnName, $columnValue);
                }
                if ($myRights == AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $resultOfQuery = Course::getCidAndUid($params, $groupid);
                    if (count($resultOfQuery) > AppConstant::NUMERIC_ZERO) {
                        $updateResult = new Course();
                        $affectedRow = $updateResult->setOwnerIdByExecute($params);
                        $exec = true;
                    }
                } else {
                    $exec = true;
                }
                if ($exec && $affectedRow > AppConstant::NUMERIC_ZERO) {
                    $result = Teacher::getByCourseId($params);
                    if (count($result) == AppConstant::NUMERIC_ZERO) {
                        $teacherData = new Teacher();
                        $teacherData->insertUidAndCid($params);
                    }
                     Teacher::deleteCidAndUid($params, $userId);
                }
                break;
            case "deloldusers":
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $old = time() - AppConstant::SECONDS * AppConstant::SECONDS * AppConstant::HOURS * AppConstant::NUMERIC_THIRTY * $params['months'];
                $who = $params['who'];
                if ($who=="students")
                {
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
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $existingGroupData = Groups::getByName($params['gpname']);
                if (strlen($existingGroupData['name']) > AppConstant::NUMERIC_ZERO)
                {
                    $this->setWarningFlash('Group name already exists.');
                    return $this->redirect('forms?action=listgroups&id='.$existingGroupData['id']);
                }else
                {
                    if($params['gpname'])
                    {
                        $newGroup = new Groups();
                        $newGroup->insertNewGroup($params['gpname']);
                    }else
                    {
                        $this->setWarningFlash('Group name should not blank.');
                        return $this->redirect('forms?action=listgroups');
                    }
                }
                break;
            case "modgroup":
                if ($myRights < AppConstant::ADMIN_RIGHT) {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                $existingGroupData = Groups::getByName($params['gpname']);
                if (strlen($existingGroupData['name']) > AppConstant::NUMERIC_ZERO) {
                    $this->setWarningFlash('Group name already exists.');
                    return $this->redirect('forms?action=modgroup&id='.$existingGroupData['id']);
                }else
                {
                    if($params['gpname'])
                    {
                        Groups::updateGroup($params);
                    }else
                    {
                        $this->setWarningFlash('Group name should not blank.');
                        return $this->redirect('forms?action=modgroup&id='.$params['id']);
                    }
                }
                break;
            case "delgroup":
                if ($myRights < AppConstant::ADMIN_RIGHT) {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                Groups::deleteById($params['id']);
                User::updateGroupId($params['id']);
                Libraries::updateGroupId($params['id']);
                break;
            case "modltidomaincred":
                if ($myRights < AppConstant::ADMIN_RIGHT) {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                if ($params['id']=='new') {
                    $user = new User();
                    $user->createLTIDomainCredentials($params);
                } else {
                    User::updateLTIDomainCredentials($params);
                }
                break;
            case "delltidomaincred":
                if ($myRights < AppConstant::ADMIN_RIGHT)
                {
                    $this->setWarningFlash(AppConstant::UNAUTHORIZED);
                    return $this->redirect($this->goHome());
                }
                User::deleteUserById($params['id']);
                break;
            case "removediag";

                $row = User::getByUserIdASDiagnoId($params);
                if (($myRights < 75 && $row['id'] == $userId) || ($myRights == 75 && $row['groupid'] == $groupid) || $myRights == 100) {
                     Diags::deleteDiagno($params);
                     DiagOneTime::deleteDiagOneTime($params);
                }
                break;
        }
        session_write_close();
        if (isset($params['cid'])) {
            return $this->redirect(AppUtility::getURLFromHome('instructor','instructor/index?cid='.$params['cid']));
        } else {
            return $this->redirect('index');
        }
    }

    public function actionDiagOneTime()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $myRights = $user['rights'];
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
                $n = intval($params['n']);
                $goodfor = intval($params['multi']);
                for ($i = 0; $i < $n; $i++) {
                    $code = '';
                    for ($j = 0; $j < 3; $j++) {
                        $code .= substr($lets,rand(0,23),1);
                    }
                    for ($j = 0; $j < 3; $j++) {
                        $code .= rand(1,9);
                    }
                    $query = new DiagOneTime();
                    $query->generateDiagOneTime($diag, $now, $code, $goodfor);
                    /* Work-in progress Group Admin
                     * if ($i>0) { $query .= ','; }
                    $query .= "('$diag',$now,'$code',$goodfor)"; */
                    $code_list[] = $code;
                }
                $code_list = array();
                $result = DiagOneTime::getByTime($now);
                foreach($result as $key => $row) {
                    if ($row['goodfor'] == AppConstant::NUMERIC_ZERO) {
                        $row['goodfor'] = "One-time";
                    } else if ($row['goodfor'] > 1000000000) {
                        if ($row['goodfor'] < time()) {
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
            if ($_GET['delete'] == 'true') {
                 DiagOneTime::deleteDiagOneTime($diag);
                return $this->redirect(AppUtility::getURLFromHome('admin', 'admin/index'));
            }
        } else {
            /*Work-in progress group admin
             * $old = time() - 365*24*60*60; //one year ago
            $now = time();
            $queryDelete = DiagOneTime::deleteByTime($old, $now); */
            $code_list = array();
            $diagByTime = DiagOneTime::getByDiag($diag);
            foreach($diagByTime as $key => $row)
             {
                $row['time'] =  $this->tzdate("F j, Y",$row['time']);
                if ($row['goodfor'] == AppConstant::NUMERIC_ZERO) {
                    $row['goodfor'] = "One-time";
                } else if ($row['goodfor'] > 1000000000) {
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

    public function actionImportLib() // access for admin,gadmin
    {
        $this->guestUserHandler();
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $this->layout = "master";
        global $isAdmin,$isGrpAdmin,$user,$params;
        $isAdmin = false;
        $allowNonGroupLibs = false;
        $isGrpAdmin = false;
        $courseId = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $teacherId = AppConstant::TEACHER_RIGHT;
        $myRights  = $user->rights;
        if (!(isset($teacherId)) && $myRights< AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;

        } elseif (isset($courseId) && $courseId == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::REQUIRED_ADMIN_ACCESS;
        }
        elseif (!(isset($courseId)) && $myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::ACCESS_THROUGH_MENU;
        } else
        {
            $courseId = (isset($courseId)) ? $courseId : "admin" ;

            if ($myRights < AppConstant::ADMIN_RIGHT)
            {
                $isGrpAdmin = true;
            } else if ($myRights == AppConstant::ADMIN_RIGHT)
            {
                $isAdmin = true;
            }
            if (isset($params['process']))
            {
                $filename = AppConstant::UPLOAD_DIRECTORY.'importLibrary/' . $params['filename'];
                $libsToAdd = $params['libs'];
                list($packName,$names,$parents,$libItems,$unique,$lastModDate) = $this->parseLibs($filename);
                $names = array_map(array($this,'addslashes_deep'),$names);
                $parents = array_map(array($this,'addslashes_deep'), $parents);
                $libItems = array_map(array($this,'addslashes_deep'), $libItems);
                $unique = array_map(array($this,'addslashes_deep'), $unique);
                $lastModDate = array_map(array($this,'addslashes_deep'), $lastModDate);
                $root = $params['parent'];
                $libRights = $params['librights'];
                $qRights = $params['qrights'];
                $toUse = '';
                $lookup = implode("','",$unique);
                $librariesData = Libraries::dataForImportLib($unique);
                if($librariesData)
                {
                    foreach($librariesData as $row)
                    {
                        $exists[$row['uniqueid']] = $row['id'];
                        $addDate[$row['id']] = $row['adddate'];
                        $lastMod[$row['id']] = $row['lastmoddate'];
                    }
                }
                global $updateQ,$newQ;
                $mt = microtime();
                $updateL = AppConstant::NUMERIC_ZERO;
                $newL = AppConstant::NUMERIC_ZERO;
                $newLi = AppConstant::NUMERIC_ZERO;
                $updateQ = AppConstant::NUMERIC_ZERO;
                $newQ = AppConstant::NUMERIC_ZERO;
                $connection = $this->getDatabase();
                $transaction = $connection->beginTransaction();
                try
                {
                    if($libsToAdd)
                    {
                        foreach($libsToAdd as $libId)
                        {
                            if ($parents[$libId] == 0)
                            {
                                $parent = $root;
                            }
                            else if (isset($libs[$parents[$libId]]))
                            {
                                $parent = $libs[$parents[$libId]];
                            }
                            else
                            {
                                continue;
                            }
                            $now = time();
                            if (isset($exists[$unique[$libId]]) && $params['merge'] == AppConstant::NUMERIC_ONE)
                            {
                                if ($lastModDate[$libId]>$addDate[$exists[$unique[$libId]]])
                                {
                                    $affectedRow = Libraries::updateLibData($isGrpAdmin, $isAdmin,$names[$libId],$now,$exists[$unique[$libId]],$user);

                                    if ($affectedRow > AppConstant::NUMERIC_ZERO)
                                    {
                                        $updateL++;
                                    }
                                }
                                $libs[$libId] = $exists[$unique[$libId]];
                            }
                            else if (isset($exists[$unique[$libId]]) && $params['merge']== -AppConstant::NUMERIC_ONE)
                            {
                                $libs[$libId] = $exists[$unique[$libId]];
                            }
                            else
                            {
                                if ($unique[$libId] == AppConstant::NUMERIC_ZERO || (isset($exists[$unique[$libId]]) && $params['merge'] == AppConstant::NUMERIC_ZERO))
                                {
                                    $unique[$libId] = substr($mt,11).substr($mt,2,2).$libId;
                                }
                                $data = new Libraries();
                                $insertId = $data->insertData($unique[$libId],$now,$names[$libId],$user,$libRights,$parent);
                                $libs[$libId] = $insertId;
                                $newL++;
                            }
                            if (isset($libs[$libId]))
                            {
                                if ($toUse == '')
                                {
                                    $toUse = $libItems[$libId];
                                }
                                else if (isset($libItems[$libId]))
                                {
                                    $toUse .= ','.$libItems[$libId];
                                }
                            }
                        }
                        $qIds = $this->parseQs($filename,$toUse,$qRights);
                         if(count($qIds) > AppConstant::NUMERIC_ZERO)
                        {
                            $qIdsToCheck = implode(',',$qIds);
                            $qIdsToUpdate = array();
                            $includedQs = array();
                            $questionSetData = QuestionSet::findDataToImportLib($qIdsToCheck);
                            if($questionSetData)
                            {
                                foreach($questionSetData as $row)
                                {
                                    $qIdsToUpdate[] = $row['id'];
                                    if (preg_match_all('/includecodefrom\(UID(\d+)\)/',$row['control'],$matches,PREG_PATTERN_ORDER) > AppConstant::NUMERIC_ZERO)
                                    {
                                        $includedQs = array_merge($includedQs,$matches[1]);
                                    }
                                    if (preg_match_all('/includeqtextfrom\(UID(\d+)\)/',$row['qtext'],$matches,PREG_PATTERN_ORDER) > AppConstant::NUMERIC_ZERO)
                                    {
                                        $includedQs = array_merge($includedQs,$matches[1]);
                                    }
                                }
                            }
                            if (count($qIdsToUpdate) > AppConstant::NUMERIC_ZERO)
                            {
                                $includedBackRef = array();
                                if (count($includedQs)> AppConstant::NUMERIC_ZERO)
                                {
                                    $includedList = implode(',',$includedQs);
                                    $idAndUniqueId = QuestionSet::getUniqueId($includedQs);
                                    if($idAndUniqueId)
                                    {
                                        foreach($idAndUniqueId as $row)
                                        {
                                            $includedBackRef[$row['uniqueid']] = $row['id'];
                                        }
                                    }
                                }
                                $data = QuestionSet::getDataToImportLib($qIdsToUpdate);
                                if($data)
                                {
                                    foreach($data as $row)
                                    {
                                        $control = addslashes(preg_replace('/includecodefrom\(UID(\d+)\)/e','"includecodefrom(".$includedbackref["\\1"].")"',$row['control']));
                                        $qText = addslashes(preg_replace('/includeqtextfrom\(UID(\d+)\)/e','"includeqtextfrom(".$includedbackref["\\1"].")"',$row['qtext']));
                                        QuestionSet::updateQuestionSetToImportLib($control,$qText,$row['id']);
                                    }
                                }
                            }
                            foreach ($libsToAdd as $libId)
                            {
                                if (!isset($libs[$libId]))
                                {
                                    $libs[$libId]=0;
                                }
                                $query = LibraryItems::getQueSetId($libs[$libId]);
                                $existingLib = array();
                                foreach($query as $row)
                                {
                                    $existingLib[] = $row['qsetid'];
                                }
                                $qIdList = explode(',',$libItems[$libId]);
                                foreach ($qIdList as $qid)
                                {
                                    if (isset($qIds[$qid]) && (array_search($qIds[$qid],$existingLib) === false))
                                    {
                                        $LibraryItems = new LibraryItems();
                                        $LibraryItems->insertData($libs[$libId],$qIds[$qid],$user);
                                        $newLi++;
                                    }
                                }
                                unset($existingLib);
                            }

                        }
                    }

                }
                catch (Exception $e)
                {
                    $transaction->rollBack();
                    return false;
                }
                unlink($filename);
                $page_uploadSuccessMsg = "Import Successful.<br>\n";
                $page_uploadSuccessMsg .= "New Libraries: $newL.<br>";
                $page_uploadSuccessMsg .= "New Questions: $newQ.<br>";
                $page_uploadSuccessMsg .= "Updated Libraries: $updateL.<br>";
                $page_uploadSuccessMsg .= "Updated Questions: $updateQ.<br>";
                $page_uploadSuccessMsg .= "New Library items: $newLi.<br>";
            }
            elseif ($_FILES['userfile']['name']!='')
            {
                $page_fileErrorMsg = "";
                $uploadDir = AppConstant::UPLOAD_DIRECTORY.'importLibrary/';
                $uploadFile = $uploadDir . basename($_FILES['userfile']['name']);

                if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile))
                {
                    $page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".basename($uploadFile)."\" />\n";
                } else
                {
                    $page_fileErrorMsg .= "<p>Error uploading file!</p>\n";
                }
                list($packName,$names,$parents,$libItems,$unique,$lastModDate) = $this->parseLibs($uploadFile);
                if (!isset($parents))
                {
                    $page_fileErrorMsg .=  "<p>This file does not appear to contain a library structure.  It may be a question set export. ";
                    $page_fileErrorMsg .=  "Try the <a href='import.php?cid='.$courseId>Import Question Set</a> page</p>\n";
                }
            }

        }
     $this->includeCSS(['libtree.css']);
     $this->includeJS(['importLibrary.js']);
     $responseData = array('overwriteBody' => $overwriteBody,'body' => $body,'page_uploadSuccessMsg' => $page_uploadSuccessMsg,'params' => $params,'page_fileErrorMsg' => $page_fileErrorMsg,
     'page_fileHiddenInput' => $page_fileHiddenInput,'courseId' => $courseId,'packName' => $packName,'isAdmin' => $isAdmin,'isGrpAdmin' => $isGrpAdmin
     ,'myRights' => $myRights,'parentsData' => $parents,'namesData' => $names);
        return $this->renderWithData('importLibrary',$responseData);
    }

    public function actionExportLib()
    {
        $this->guestUserHandler();
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $myRights  = $user->rights;
        $teacherId = AppConstant::TEACHER_RIGHT;
        if (!(isset($teacherId)) && $myRights< AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;

        } elseif (isset($courseId) && $courseId == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::REQUIRED_ADMIN_ACCESS;
        }
        elseif (!(isset($courseId)) && $myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::ACCESS_THROUGH_MENU;
        }
        else
        {
            $courseId = (isset($courseId)) ? $courseId : "admin" ;

            if ($myRights < AppConstant::ADMIN_RIGHT)
            {
                $isGrpAdmin = true;
            } else if ($myRights == AppConstant::ADMIN_RIGHT)
            {
                $isAdmin = true;
            }
            elseif(!isset($teacherId))
            {
                $isAdminPage = true;
            }

            if(isset($params['submit']) && $params['submit'] == 'Export')
            {
                if (count($params['libs'])== AppConstant::NUMERIC_ZERO)
                {
                    $this->setErrorFlash('No libraries selected');
                  return $this->redirect('export-lib?cid='.$courseId);
                }
                header('Content-type: text/imas');
                header("Content-Disposition: attachment; filename='imasexport.imas'");
                echo "PACKAGE DESCRIPTION\n";
                echo $params['packdescription'];
                echo "\n";
                $rootLibs = $params['libs'];
                if (isset($params['rootlib']))
                {
                    array_unshift($rootLibs,$params['rootlib']);
                }
                $rootList = "'".implode("','",$rootLibs)."'";
                global $libCnt,$libs,$nonPrivate;
                $libCnt = AppConstant::NUMERIC_ONE;
                $libs = Array();
                $parents = Array();
                $nonPrivate = isset($params['nonpriv']);
                $libraryData = Libraries::getLibraryData($rootList,$nonPrivate);
                if($libraryData)
                {
                    foreach($libraryData as $row)
                    {
                        if (!in_array($row['parent'],$rootLibs))
                        {
                            $libs[$row['id']] = $libCnt;
                            $parents[$libCnt] = 0;
                            echo "\nSTART LIBRARY\n";
                            echo "ID\n";
                            echo rtrim($libCnt) . "\n";
                            echo "UID\n";
                            echo rtrim($row['uniqueid']) . "\n";
                            echo "LASTMODDATE\n";
                            echo rtrim($row['lastmoddate']) . "\n";
                            echo "NAME\n";
                            echo rtrim($row['name']) . "\n";
                            echo "PARENT\n";
                            echo "0\n";
                            $libCnt++;
                        }
                    }
                }
                if($rootLibs)
                {
                    foreach ($rootLibs as $k=>$rootLib)
                    {
                        $this->getchildlibs($rootLib);
                    }
                }
                $library = array_keys($libs);
                foreach($library as $k=>$v)
                {
                    $library[$k] = "'".$v."'";
                }
                $libList = implode(',',$library);
                $libraryItemData = LibraryItems::getDataToExportLib($libList,$nonPrivate);
                $qAssoc = Array();
                $libItems = Array();
                $qCnt = 0;
                if($libraryItemData)
                {
                    foreach($libraryItemData as $row)
                    {
                        if (!isset($qAssoc[$row['qsetid']]))
                        {
                            $qAssoc[$row['qsetid']] = $qCnt;
                            $qCnt++;
                        }
                        $libItems[$libs[$row['libid']]][] = $qAssoc[$row['qsetid']];
                    }
                }
                if($libs)
                {
                    foreach ($libs as $newId)
                    {
                        if (isset($libItems[$newId]))
                        {
                            echo "\nSTART LIBRARY ITEMS\n";
                            echo "LIBID\n";
                            echo rtrim($newId) . "\n";
                            echo "QSETIDS\n";
                            echo rtrim(implode(',',$libItems[$newId])) . "\n";
                        }
                    }
                }
                $imgFiles = array();
                $qList = implode(',',array_unique(array_keys($qAssoc)));
                $questionSetData = QuestionSet::getDataToExportLib($qList,$nonPrivate,AppConstant::NUMERIC_ZERO);
                $includedGs = array();
                if($questionSetData)
                {
                    foreach($questionSetData as $line)
                    {
                        if (preg_match_all('/includecodefrom\((\d+)\)/',$line['control'],$matches,PREG_PATTERN_ORDER) > AppConstant::NUMERIC_ZERO)
                        {
                            $includedGs = array_merge($includedGs,$matches[1]);
                        }
                        if (preg_match_all('/includeqtextfrom\((\d+)\)/',$line['qtext'],$matches,PREG_PATTERN_ORDER) > AppConstant::NUMERIC_ZERO)
                        {
                            $includedGs = array_merge($includedGs,$matches[1]);
                        }
                    }
                }
                $includedBackRef = array();
                if(count($includedGs) > AppConstant::NUMERIC_ZERO)
                {
                    $data = QuestionSet::getUniqueIdToExportLib($includedGs);
                    if($data)
                    {
                        foreach($data as $row)
                        {
                            $includedBackRef[$row['id']] = $row['uniqueid'];
                        }
                    }
                }
                $questionData = QuestionSet::getDataToExportLib($qList,$nonPrivate,AppConstant::NUMERIC_ONE);
                if($questionData)
                {
                    foreach($questionData as $line)
                    {
                        $line['control']  = preg_replace_callback('/^( +)/', function($matches){return str_repeat("&nbsp;", strlen($matches["$1"]));},$line['control']);
                        $line['qtext']  = preg_replace_callback('/^( +)/', function($matches){return str_repeat("&nbsp;", strlen($matches["$1"]));},$line['qtext']);
                        echo "\nSTART QUESTION\n";
                        echo "QID\n";
                        echo rtrim($qAssoc[$line['id']]) . "\n";
                        echo "\nUQID\n";
                        echo rtrim($line['uniqueid']) . "\n";
                        echo "\nLASTMOD\n";
                        echo rtrim($line['lastmoddate']) . "\n";
                        echo "\nDESCRIPTION\n";
                        echo rtrim($line['description']) . "\n";
                        echo "\nAUTHOR\n";
                        echo rtrim($line['author']) . "\n";
                        echo "\nCONTROL\n";
                        echo rtrim($line['control']) . "\n";
                        echo "\nQCONTROL\n";
                        echo rtrim($line['qcontrol']) . "\n";
                        echo "\nQTYPE\n";
                        echo rtrim($line['qtype']) . "\n";
                        echo "\nQTEXT\n";
                        echo rtrim($line['qtext']) . "\n";
                        echo "\nANSWER\n";
                        echo rtrim($line['answer']) . "\n";
                        echo "\nSOLUTION\n";
                        echo rtrim($line['solution']) . "\n";
                        echo "\nSOLUTIONOPTS\n";
                        echo rtrim($line['solutionopts']) . "\n";
                        echo "\nEXTREF\n";
                        echo rtrim($line['extref']) . "\n";
                        echo "\nLICENSE\n";
                        echo rtrim($line['license']) . "\n";
                        echo "\nANCESTORAUTHORS\n";
                        echo rtrim($line['ancestorauthors']) . "\n";
                        echo "\nOTHERATTRIBUTION\n";
                        echo rtrim($line['otherattribution']) . "\n";
                        if ($line['hasimg']==1)
                        {
                            echo "\nQIMGS\n";
                            $QImages = QImages::dataForExportLib($line['id']);
                            if($QImages)
                            {
                                foreach($QImages as $row)
                                {
                                    $row['filename'] = trim($row['filename']);
                                    echo $row['var'].','.$row['filename']. "\n";
                                    if ($GLOBALS['filehandertypecfiles'] == 's3')
                                    {
                                        /*
                                         * Work-in-progress
                                         * copyqimage($row['filename'],realpath("../assessment/qimages").DIRECTORY_SEPARATOR.$row['filename']);
                                         */

                                    }
                                    $imgFiles[] =AppConstant::UPLOAD_DIRECTORY.'exportLibrary/'.DIRECTORY_SEPARATOR.$row['filename'];
                                }
                            }
                        }
                    }
                    require dirname(__FILE__) . '/tar.class.php';
                    if (file_exists(AppConstant::UPLOAD_DIRECTORY."Qimages.tar.gz"))
                    {
                        unlink(AppConstant::UPLOAD_DIRECTORY."Qimages.tar.gz");
                    }
                    $tar = new tar();
                    $tar->addFiles($imgFiles);
                    $tar->toTar(AppConstant::UPLOAD_DIRECTORY."Qimages.tar.gz",TRUE);
                }
            }
        }
        $this->includeCSS(['libtree.css']);
        $this->includeJS(['libtree.js']);
        $responseData = array('courseId' => $courseId,'overwriteBody' => $overwriteBody,'body' => $body,'params' => $params,'myRights' => $myRights,'nonPrivate' => $nonPrivate);
        return $this->renderWithData('exportLibrary',$responseData);
    }

    function getChildLibs($lib)
    {
        global $libCnt,$libs,$nonPrivate;
        $parentData = Libraries::getDataByParent($lib,$nonPrivate);
        if($parentData)
        {
            foreach($parentData as $row)
            {
                if (!isset($libs[$row[0]]))
                {
                    $libs[$row['id']] = $libCnt;
                    $parents[$libCnt] = $libs[$lib];
                    echo "\nSTART LIBRARY\n";
                    echo "ID\n";
                    echo rtrim($libCnt) . "\n";
                    echo "UID\n";
                    echo rtrim($row['uniqueid']) . "\n";
                    echo "LASTMODDATE\n";
                    echo rtrim($row['lastmoddate']) . "\n";
                    echo "NAME\n";
                    echo rtrim($row['name']) . "\n";
                    echo "PARENT\n";
                    echo rtrim($libs[$lib]) . "\n";
                    $libCnt++;
                    $this->getchildlibs($row['id']);
                }
            }
        }
    }

    public function parseLibs($file)
    {
        if (!function_exists('gzopen'))
        {
            $handle = fopen($file,"r");
            $noGz = true;
        } else
        {
            $noGz = false;
            $handle = gzopen($file,"r");
        }
        if (!$handle) {
            echo "eek!  handle doesn't exist";
            exit;
        }
        $line = '';
        while (((!$noGz || !feof($handle)) && ($noGz || !gzeof($handle))) && $line!="START QUESTION") {
            if ($noGz)
            {
                $line = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            else {
                $line = rtrim(gzgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            if ($line=="PACKAGE DESCRIPTION")
            {
                $doPacked = true;
                $packName = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            else if ($line=="START LIBRARY")
            {
                $doPacked = false;
                $libId = -1;
            }
            else if ($line=="ID")
            {
                $libId = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            else if ($line=="UID")
            {
                $unique[$libId] = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            else if ($line=="LASTMODDATE")
            {
                $lastModDate[$libId] = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            else if ($line=="NAME")
            {
                if ($libId != -1)
                {
                    $names[$libId] = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
                }
            } else if ($line=="PARENT") {
                if ($libId != -1)
                {
                    $parents[$libId]= rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
                }
            }else if ($line=="START LIBRARY ITEMS")
            {
                $libItemId = -1;
            }
            else if ($line=="LIBID")
            {
                $libItemId = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            else if ($line=="QSETIDS")
            {
                if ($libItemId != -1)
                {
                    $libItems[$libItemId] = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
                }
            } else if ($doPacked ==true) {
                $packName .= rtrim($line);
            }
        }
        if ($noGz)
        {
            fclose($handle);
        } else
        {
            gzclose($handle);
        }
        return array($packName,$names,$parents,$libItems,$unique,$lastModDate);
    }

    function parseQs($file,$toUse,$rights)
    {
        $toUse = explode(',',$toUse);
        $qNum = -1;
        $part = '';
        if (!function_exists('gzopen'))
        {
            $handle = fopen($file,"r");
            $nogz = true;
        }
        else
        {
            $nogz = false;
            $handle = gzopen($file,"r");
        }
        $line = '';
        while ((!$nogz || !feof($handle)) && ($nogz || !gzeof($handle)))
        {
            if ($nogz)
            {
                $line = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            else
            {
                $line = rtrim(gzgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            }
            if ($line == "START QUESTION")
            {
                $part = '';
                if ($qNum > -1)
                {
                    foreach($qdata as $k=>$val)
                    {
                        $qdata[$k] = rtrim($val);
                    }
                    if (in_array($qdata['qid'],$toUse))
                    {
                        $qid = $this->writeQ($qdata,$rights,$qNum);
                        if ($qid!==false)
                        {
                            $qIds[$qdata['qid']] = $qid;
                        }
                    }
                    unset($qdata);
                }
                $qNum++;
                continue;
            }
            else if ($line == "DESCRIPTION")
            {
                $part = 'description';
                continue;
            }
            else if ($line == "QID")
            {
                $part = 'qid';
                continue;
            }
            else if ($line == "UQID")
            {
                $part = 'uqid';
                continue;
            }
            else if ($line == "LASTMOD")
            {
                $part = 'lastmod';
                continue;
            }
            else if ($line == "AUTHOR")
            {
                $part = 'author';
                continue;
            }
            else if ($line == "CONTROL")
            {
                $part = 'control';
                continue;
            }
            else if ($line == "QCONTROL")
            {
                $part = 'qcontrol';
                continue;
            }
            else if ($line == "QTEXT")
            {
                $part = 'qtext';
                continue;
            }
            else if ($line == "QTYPE")
            {
                $part = 'qtype';
                continue;
            }
            else if ($line == "ANSWER")
            {
                $part = 'answer';
                continue;
            } else if ($line == "SOLUTION")
            {
                $part = 'solution';
                continue;
            } else if ($line == "SOLUTIONOPTS")
            {
                $part = 'solutionopts';
                continue;
            } else if ($line == "EXTREF")
            {
                $part = 'extref';
                continue;
            } else if ($line == "LICENSE")
            {
                $part = 'license';
                continue;
            } else if ($line == "ANCESTORAUTHORS")
            {
                $part = 'ancestorauthors';
                continue;
            } else if ($line == "OTHERATTRIBUTION")
            {
                $part = 'otherattribution';
                continue;
            } else if ($line == "QIMGS")
            {
                $part = 'qimgs';
                continue;
            } else
            {
                if ($part=="qtype")
                {
                    $qdata['qtype'] .= $line;
                } else if ($qNum>-1)
                {
                    $qdata[$part] .= $line . "\n";
                }
            }
        }
        if ($nogz)
        {
            fclose($handle);
        } else {
            gzclose($handle);
        }
        foreach($qdata as $k=>$val)
        {
            $qdata[$k] = rtrim($val);
        }
        if (in_array($qdata['qid'],$toUse))
        {
            $qid =$this->writeq($qdata,$rights,$qNum);
            if ($qid!==false)
            {
                $qIds[$qdata['qid']] = $qid;
            }
        }
        return $qIds;
    }

    function writeQ($qd,$rights,$qn)
    {
        global $user,$isAdmin,$updateQ,$newQ,$isGrpAdmin,$params;
        $now = time();
        $qd = array_map(array($this,'addslashes_deep'),$qd);
        $QuestionSetData = QuestionSet::getLastModDateAndId($qd['uqid']);
        if ($QuestionSetData)
        {
            $qSetId = $QuestionSetData['id'];
            $addDate = $QuestionSetData['adddate'];
            $exists = true;
        } else
        {
            $exists = false;
        }
        if ($exists && ($params['merge']==1 || $params['merge'] == AppConstant::NUMERIC_TWO))
        {
            if ($qd['lastmod']>$addDate || $params['merge'] == AppConstant::NUMERIC_TWO)
            {
                if (!empty($qd['qimgs']))
                {
                    $hasImg = AppConstant::NUMERIC_ONE;
                } else
                {
                    $hasImg = AppConstant::NUMERIC_ZERO;
                }
                if ($isGrpAdmin)
                {
                    $QuestionSetId = QuestionSet::getQSetAndUserData($qSetId,$user->groupid);

                    if($QuestionSetId)
                    {
                        $affectedRow = QuestionSet::UpdateQuestionsetData($qd,$hasImg,$now,$qSetId);
                    }
                    else
                    {
                        return $qSetId;
                    }
                }
                else
                {
                    $affectedRow = QuestionSet::UpdateQuestionsetDataIfNotAdmin($qd,$hasImg,$now,$qSetId,$user,$isAdmin,0);
                }
                if ($affectedRow > AppConstant::NUMERIC_ZERO)
                {
                    $updateQ++;
                    if (!empty($qd['qimgs']))
                    {
                        QImages::deleteByQsetId($qSetId);
                        $qImages = explode("\n",trim($qd['qimgs']));
                        if($qImages)
                        {
                            foreach($qImages as $qimg)
                            {
                                $p = explode(',',$qimg);
                                if (count($p) < AppConstant::NUMERIC_TWO)
                                {
                                    continue;
                                }
                                $QImages = new QImages();
                                $QImages->insertFilename($qSetId,$p);
                            }

                        }
                    }
                }
            }
            return $qSetId;
        } else if ($exists && $params['merge'] == -1)
        {
            return $qSetId;
        }
        else
        {
            $importUIdVal = '';
            if ($qd['uqid']=='0' || ($exists && $params['merge'] == AppConstant::NUMERIC_ZERO))
            {
                $importUIdVal = $qd['uqid'];
                $mt = microtime();
                $qd['uqid'] = substr($mt,11).substr($mt,2,2).$qn;
            }
            if (!empty($qd['qimgs']))
            {
                $hasImg = AppConstant::NUMERIC_ONE;
            }
            else
            {
                $hasImg = AppConstant::NUMERIC_ZERO;
            }
            $insert = new QuestionSet();
            $insertId = $insert->InsertData($now,$user,$qd,$importUIdVal,$hasImg,$rights);
            $newQ++;
            $qSetId = $insertId;
            if(!empty($qd['qimgs']))
            {
                $qImages = explode("\n",$qd['qimgs']);
                if($qImages)
                {
                    foreach($qImages as $qimg)
                    {
                        $p = explode(',',$qimg);
                        $QImages = new QImages();
                        $QImages->insertFilename($qSetId,$p);
                    }
                }

            }
            return $qSetId;
        }
    }

    function addslashes_deep($value=null)
    {
        return (is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value));
    }

    function setparentrights($alibid)
    {
        global $rights,$parents;
        if ($parents[$alibid] > AppConstant::NUMERIC_ZERO) {
            if ($rights[$parents[$alibid]] < $rights[$alibid]) {
                $rights[$parents[$alibid]] = $rights[$alibid];
            }
            $this->setparentrights($parents[$alibid]);
        }
        return $alibid;
    }

    function addupchildqs($p)
    {
        global $qcount,$ltlibs;
        if (isset($ltlibs[$p])) { //if library has children
            foreach ($ltlibs[$p] as $child)
            {
                $qcount[$p] += $this->addupchildqs($child);
            }
        }
        return $qcount[$p];
    }

    public function actionManageLib()
    {
        global $rights,$parents,$qcount,$ltlibs, $names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin;
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $userId = $user->id;
        $allownongrouplibs = false;
        $myRights = $user['rights'];
        $groupId= $user['groupid'];
        $isAdmin = false;
        $isGrpAdmin = false;
        $params = $this->getRequestParams();
        $transfer = $this->getParamVal('transfer');
        $remove = $this->getParamVal('remove');
        if ($myRights < AppConstant::TEACHER_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } elseif (isset($params['cid']) && $params['cid']=="admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } elseif (!(isset($params['cid'])) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } else {
            $cid = $params['cid'];
            if ($cid == 'admin') {
                if ($myRights > 74 && $myRights < AppConstant::ADMIN_RIGHT) {
                    $isGrpAdmin = true;
                } else if ($myRights == AppConstant::ADMIN_RIGHT) {
                    $isAdmin = true;
                }
            }
            $now = time();
            if (isset($remove)) {
                if (isset($params['confirmed'])) {
                    $result = LibraryItems::getDistinctQSet($params['remove']);
                    foreach($result as $key => $row) {
                        $qidstocheck[] = $row[0];
                    }
                    $remove = $params['remove'];
                    $deleteSingleLib = new Libraries();
                    $affectedRow = $deleteSingleLib->deleteLibrarySingle($remove,$isAdmin,$groupId,$isGrpAdmin,$userId);
                    if ($affectedRow > AppConstant::NUMERIC_ZERO && count($qidstocheck) > AppConstant::NUMERIC_ZERO)
                    {
                        $libid = $params['remove'];
                        LibraryItems::deleteLibraryGrpAdmin($libid);
                        $qids = implode(",",$qidstocheck);
                        $result = LibraryItems::getByDistinctQid($qids);
                        $okqids = array();
                        foreach($result as $key => $row) {
                            $okqids[] = $row['qsetid'];
                        }
                        $qidstofix = array_diff($qidstocheck,$okqids);
                        if ($params['delq'] == 'yes' && count($qidstofix) > AppConstant::NUMERIC_ZERO)
                        {
                            $qlist = implode(',',$qidstofix);
                            $updateQdata = new QuestionSet();
                            $updateQdata->updateIdIn($qlist);
                        } else {
                            foreach($qidstofix as $qid) {
                                $insertDataLibItem = new LibraryItems();
                                $insertDataLibItem->insertDataLib(0,$qid);
                            }
                        }
                    }
                    return $this->redirect('manage-lib?cid='.$cid);
                } else {
                    $parentLib = $params['remove'];
                    $libcnt = Libraries::getCountOfId($parentLib);
                    $libcnt = count($libcnt);
                    $pagetitle = ($libcnt > AppConstant::NUMERIC_ZERO) ? "Error" : "Remove Library";
                }
            } elseif(isset($params['remove']))
            {
                if (isset($params['confirmed']))
                {
                    if ($params['remove']!='') {
                        $removeArray = explode(',',$params['remove']);
                        $remlist = "'".implode("','",explode(',',$params['remove']))."'";

                        $result = LibraryItems::getDistictqlibData($remlist);
                       foreach($result as $key => $row) {
                            $qidstocheck[] = $row['qsetid'];
                        }
                        if ($isAdmin)
                        {
                            $deleteAdmin = new LibraryItems();
                            $deleteAdmin->deleteLibraryAdmin($removeArray);
                        } else if ($isGrpAdmin) {
                            $result = Libraries::getByIdGroupAdmin($removeArray, $groupId);
                            foreach($result as $key => $row)
                            {
                                $deleteLibItem = new LibraryItems();
                                $deleteLibItem->deleteLibraryGrpAdmin($row['id']);
                            }
                        } else {
                            $result = Libraries::getByIdAdmin($removeArray, $userId);
                            foreach($result as $key => $row)
                            {
                                $deleteLibItem = new LibraryItems();
                                $deleteLibItem->deleteLibraryGrpAdmin($row['id']);
                            }
                        }
                        if (isset($qidstocheck)) {
                            $qids = implode(",",$qidstocheck);
                            $result = LibraryItems::getByDistinctQid($qids);
                            $okqids = array();
                            foreach($result as $key => $row){
                                $okqids[] = $row['qsetid'];
                            }
                            $qidstofix = array_diff($qidstocheck,$okqids);
                            if ($params['delq'] == 'yes' && count($qidstofix) > AppConstant::NUMERIC_ZERO)
                            {
                                $qlist = implode(',',$qidstofix);
                                QuestionSet::updateIdIn($qlist);
                            } else {
                                foreach($qidstofix as $qid) {
                                    $query = new LibraryItems();
                                    $query->insertDataLib($qid, AppConstant::NUMERIC_ZERO);
                                }
                            }
                        }
                        $deleteLib = new Libraries();
                        $deleteLib->deleteLibraryAdmin($remlist,$isAdmin,$groupId,$isGrpAdmin,$userId);
                    }
                    return $this->redirect('manage-lib?cid='.$cid);
                } else {
                    $pagetitle = "Confirm Removal";
                    if (!isset($params['nchecked'])) {
                        $overwriteBody = AppConstant::NUMERIC_ONE;
                        $body = "No libraries selected.  <a href=\"manage-lib?cid=$cid\">Go back</a>\n";
                    } else
                    {
                        $oktorem = array();
                        for ($i = AppConstant::NUMERIC_ZERO; $i < count($params['nchecked']); $i++)
                        {
                            $parentLib = $params['nchecked'][$i];
                            $libcnt = Libraries::getCountOfId($parentLib);
                            $libcnt = count($libcnt);
                            if ($libcnt == AppConstant::NUMERIC_ZERO) {
                                $oktorem[] = $params['nchecked'][$i];
                            }
                        }
                        $rlist = implode(",",$oktorem);
                        $hasChildWarning = (count($params['nchecked']) > count($oktorem)) ? "<p>Warning:  Some libraries selected have children, and cannot be deleted.</p>\n": "";
                    }
                }
            }
            else if (isset($params['chgrights']))
            {
                if (isset($params['newrights']))
                {
                    if ($params['newrights'] != '')
                    {
                        $llist = "'".implode("','",explode(',',$params['chgrights']))."'";
                        $rights = $params['newrights'];
                        $query = new Libraries();
                        $query->updateUserRightLastModeDate($rights,$now, $llist,$isAdmin,$groupId,$isGrpAdmin,$userId);
                    }
                    return $this->redirect('manage-lib?cid='.$cid);
                } else {
                    $pagetitle = "Change Library Rights";
                    if (!isset($params['nchecked']))
                    {
                        $overwriteBody = AppConstant::NUMERIC_ONE;
                        $body = "No libraries selected.  <a href=\"manage-lib?cid=$cid\">Go back</a>\n";
                    } else {
                        $tlist = implode(",",$params['nchecked']);

                        $page_libRights = array();
                        $page_libRights['val'][0] = AppConstant::NUMERIC_ZERO;
                        $page_libRights['val'][1] = AppConstant::NUMERIC_ONE;
                        $page_libRights['val'][2] = AppConstant::NUMERIC_TWO;

                        $page_libRights['label'][0] = "Private";
                        $page_libRights['label'][1] = "Closed to group, private to others";
                        $page_libRights['label'][2] = "Open to group, private to others";

                        if ($isAdmin || $isGrpAdmin || $allownongrouplibs) {
                            $page_libRights['label'][3] = "Closed to all";
                            $page_libRights['label'][4] = "Open to group, closed to others";
                            $page_libRights['label'][5] = "Open to all";
                            $page_libRights['val'][3] = AppConstant::NUMERIC_FOUR;
                            $page_libRights['val'][4] = AppConstant::NUMERIC_FIVE;
                            $page_libRights['val'][5] = AppConstant::NUMERIC_EIGHT;
                        }
                    }
                }
            }
            else if (isset($transfer))
            {
                if (isset($params['newowner'])) {
                    $newgroup = User::getByGroupId($params['newowner']);
                    $newgpid = $newgroup[0]['groupid'];
                    $idTransfer = $params['transfer'];
                    $queryLibrary = new Libraries();
                    $queryLibrary->updateByGrpUserIdSingle($params, $newgpid, $idTransfer, $isAdmin, $groupId, $isGrpAdmin, $userId);
                    return $this->redirect('manage-lib?cid='.$cid);
                } else {
                    $pagetitle = "Transfer Library";
                    $result = User::getByIdOrdered();
                    $i= AppConstant::NUMERIC_ZERO;
                    $page_newOwnerList = array();
                    foreach($result as $key => $row) {
                        $page_newOwnerList['val'][$i] = $row['id'];
                        $page_newOwnerList['label'][$i] = $row['LastName'] . ", " . $row['FirstName'];
                        $i++;
                    }
                }
            } else if(isset($params['transfer']))
            {
                if (isset($params['newowner'])) {
                    if ($params['transfer']!='') {
                        $translist = "'".implode("','",explode(',',$params['transfer']))."'";
                        $newgroup = User::getByGroupId($params['newowner']);
                        $newgpid = $newgroup[0]['groupid'];
                        $libraryUpdate = new Libraries();
                        $libraryUpdate->updateByGrpIdUserId($params, $newgpid,$isAdmin,$groupId, $isGrpAdmin, $userId, $translist);
                    }
                    return $this->redirect('manage-lib?cid='.$cid);
                } else {
                    $pagetitle = "Confirm Transfer";
                    if (!isset($params['nchecked'])) {
                        $overwriteBody = AppConstant::NUMERIC_ONE;
                        $body = "No libraries selected.  <a href=\"manage-lib?cid=$cid\">Go back</a>\n";
                    } else {
                        $tlist = implode(",",$params['nchecked']);
                        $result = User::getByIdOrdered();
                        $i=0;
                       foreach($result as $key => $row) {
                            $page_newOwnerList['val'][$i] = $row['id'];
                            $page_newOwnerList['label'][$i] = $row['LastName'] . ", " . $row['FirstName'];
                            $i++;
                        }
                    }
                }
            } else if (isset($params['setparent']))
            {
                if (isset($params['libs'])) {
                    if ($params['libs']!='') {
                        $toset = array();
                        $params['setparent'] = explode(',',$params['setparent']);
                        foreach ($params['setparent'] as $alib) {
                            if ($alib != $params['libs']) {
                                $toset[] = $alib;
                            }
                        }
                        if (count($toset) > 0)
                        {
                            $parlist = "'".implode("','",$toset)."'";
                            $lib = $params['libs'];
                            $query = new Libraries();
                            $query->updateParent($lib,$now,$parlist,$isAdmin,$groupId,$isGrpAdmin,$userId);
                        }
                    }
                    return $this->redirect('manage-lib?cid='.$cid);
                } else {
                    $pagetitle = "Set Parent";
                    $parent1 = "";

                    if (!isset($params['nchecked'])) {
                        $overwriteBody = AppConstant::NUMERIC_ONE;
                        $body = "No libraries selected.  <a href=\"manage-lib?cid=$cid\">Go back</a>\n";
                    } else {
                        $tlist = implode(",",$params['nchecked']);
                    }
                }
            } else if (isset($params['modify']))
            {
                if (isset($params['name']) && trim($params['name'])!='')
                {
                    if ($params['modify']=="new")
                    {
                        $params['name'] = str_replace(array(',','\\"','\\\'','~'),"",$params['name']);
                        $nameM = $params['name'];
                        $parentsM = $params['libs'];
                        $result = Libraries::getByNameParents($nameM,$parentsM);
                        if (count($result) > AppConstant::NUMERIC_ZERO) {
                            $overwriteBody = AppConstant::NUMERIC_ONE;
                            $body = "Library already exists by that name with this parent.\n";
                            $body .= "<p><a href=\"manage-lib?cid=$cid&modify=new\">Try Again</a></p>\n";
                        } else {
                            $mt = microtime();
                            $uqid = substr($mt,11).substr($mt,2,6);

                            $insertData = new Libraries();
                            $insertData->insertDataWithSort($uqid,$now, $now,$params,$userId,$groupId);
                            return $this->redirect('manage-lib?cid='.$cid);
                        }
                    } else {
                        $updateLibData = new Libraries();
                        $updateLibData->updateById($params,$isAdmin,$groupId,$isGrpAdmin,$userId,$now);
                        return $this->redirect('manage-lib?cid='.$cid);
                    }
                } else {
                    if ($params['modify'] != "new")
                    {
                        $pagetitle = "Modify Library";
                        $row = Libraries::getByModifyId($params['modify'], $isAdmin, $userId);
                        if ($row) {
                            $name = $row['name'];
                            $rights = $row['userights'];
                            $parent = $row['parent'];
                            $sortorder = $row['sortorder'];
                        }
                    } else {
                        $pagetitle = "Add Library\n";
                        if (isset($params['parent'])) {
                            $parent = $params['parent'];
                        }
                    }
                    if (!isset($name)) { $name = '';}
                    if (!isset($rights)) {
                        if ($isadmin || $allownongrouplibs) {
                            $rights = AppConstant::NUMERIC_EIGHT;
                        } else {
                            $rights = AppConstant::NUMERIC_TWO;
                        }
                    }
                    if (!isset($parent)) {$parent = AppConstant::NUMERIC_ZERO;}
                    if (!isset($sortorder)) {$sortorder = AppConstant::NUMERIC_ZERO;}
                    $parent1 = $parent;
                    if ($parent == AppConstant::NUMERIC_ZERO) {
                        $lnames = "Root";
                    } else {
                        $result = Libraries::getByName($parent);
                        $lnames = $result[0]['name'];
                    }
                }
                $page_libRights = array();
                $page_libRights['val'][0] = AppConstant::NUMERIC_ZERO;
                $page_libRights['val'][1] = AppConstant::NUMERIC_ONE;
                $page_libRights['val'][2] = AppConstant::NUMERIC_TWO;

                $page_libRights['label'][0] = "Private";
                $page_libRights['label'][1] = "Closed to group, private to others";
                $page_libRights['label'][2] = "Open to group, private to others";
                if ($isAdmin || $isGrpAdmin || $allownongrouplibs) {
                    $page_libRights['label'][3] = "Closed to all";
                    $page_libRights['label'][4] = "Open to group, closed to others";
                    $page_libRights['label'][5] = "Open to all";
                    $page_libRights['val'][3] = AppConstant::NUMERIC_FOUR;
                    $page_libRights['val'][4] = AppConstant::NUMERIC_FIVE;
                    $page_libRights['val'][5] = AppConstant::NUMERIC_EIGHT;
                }
            } else { //DEFAULT PROCESSING HERE
                $pagetitle = "Library Management";
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
                foreach ($rights as $k=>$n) {
                $this->setparentrights($k);
            }
                $qcount[0] = $this->addupchildqs(0);

                $count = AppConstant::NUMERIC_ZERO;
            }
            $page_appliesToMsg = (!$isAdmin) ? "(Only applies to your libraries)" : "";
            }
        $this->includeCSS(['libtree.css']);
        $this->includeJS(['libtree.js', 'general.js']);

        $responseData = array('page_appliesToMsg' => $page_appliesToMsg, 'page_AdminModeMsg' => $page_AdminModeMsg, 'rights' => $rights, 'cid' => $cid, 'page_libRightsLabel' => $page_libRights['label'], 'page_libRightsVal' => $page_libRights['val'], 'lnames' => $lnames, 'parent' => $parent, 'names' => $names, 'ltlibs' => $ltlibs, 'count' => $count,'qcount' => $qcount, 'sortorder' => $sortorder,'ownerids' => $ownerids, 'userid' => $userId, 'isadmin' => $isAdmin, 'groupids' => $groupids, 'groupid' => $groupId,'isgrpadmin' => $isGrpAdmin, 'name' => $name, 'tlist' => $tlist, 'page_newOwnerListVal' => $page_newOwnerList['val'], 'page_newOwnerListLabel' => $page_newOwnerList['label'], 'pagetitle' => $pagetitle, 'overwriteBody' => $overwriteBody, 'body' => $body, 'rlist' => $rlist, 'hasChildWarning' => $hasChildWarning,
        'parent1' => $parent1, 'params' => $params);
        return $this->renderWithData('manageLib', $responseData);
    }

    public function actionImportQuestionSet()
    {
        $this->guestUserHandler();
        @set_time_limit(0);
        ini_set("max_input_time", "600");
        ini_set("max_execution_time", "600");
        ini_set("memory_limit", "104857600");
        ini_set("upload_max_filesize", "10485760");
        ini_set("post_max_size", "10485760");
        $installName = "MyOpenMath";
        $overwriteBody = 0;
        $body = "";
        $pageTitle = $installName . "Import Questions";
        $isAdmin = false;
        $isGrpAdmin = false;
        $teacherId = AppConstant::TEACHER_RIGHT;
        $courseId = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $myRights  = $user->rights;
        if (!(isset($teacherId)) && $myRights< AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;

        } elseif (isset($courseId) && $courseId == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::REQUIRED_ADMIN_ACCESS;
        }
        elseif (!(isset($courseId)) && $myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::ACCESS_THROUGH_MENU;
        } else
        {
            $courseId = (isset($courseId)) ? $courseId : "admin" ;

            if ($myRights < AppConstant::ADMIN_RIGHT)
            {
                $isGrpAdmin = true;
            } else if ($myRights == AppConstant::ADMIN_RIGHT)
            {
                $isAdmin = true;
            }
            if (isset($params['process']))
            {
                $filename = AppConstant::UPLOAD_DIRECTORY.'importLibrary/' . $params['filename'];
                $qData = $this->parseFile($filename);
                $newLibs = explode(",",$params['libs']);
                if (in_array('0',$newLibs) && count($newLibs) > AppConstant::NUMERIC_ONE)
                {
                    array_shift($newLibs);
                }
                $checked = $params['checked'];
                $rights = $params['userights'];
                foreach ($checked as $qn)
                {
                    if (is_numeric($qData[$qn]['uqid']))
                    {
                        $lookup[] = $qData[$qn]['uqid'];
                    }
                }
                $questionSetData = QuestionSet::getDataForImportQSet($lookup);
                if($questionSetData)
                {
                    foreach($questionSetData as $row)
                    {
                        $exists[$row['uniqueid']] = $row['id'];
                        $addDate[$row['id']] = $row['adddate'];
                        $lastMod[$row['id']] = $row['lastmoddate'];
                    }
                }
                if (count($exists) > AppConstant::NUMERIC_ZERO)
                {
                    $LibraryItemData = LibraryItems::getDataForImportQSet($exists);
                    if($LibraryItemData)
                    {
                        foreach($LibraryItemData as $row)
                        {
                            $doNotAddLi[$row['libid']] = $row['qsetid'];
                        }
                    }
                }
                $mt = microtime();
                $newQ = AppConstant::NUMERIC_ZERO;
                $updateQ = AppConstant::NUMERIC_ZERO;
                $newLi = AppConstant::NUMERIC_ZERO;
                $now = time();
                $allQIds = array();
                $connection = $this->getDatabase();
                $transaction = $connection->beginTransaction();
                try
                {
                    foreach ($checked as $qn)
                    {
                        if (!empty($qData[$qn]['qimgs']))
                        {
                            $hasImg = AppConstant::NUMERIC_ONE;
                        } else
                        {
                            $hasImg = AppConstant::NUMERIC_ZERO;
                        }
                        if (isset($exists[$qData[$qn]['uqid']]) && $params['merge'] == AppConstant::NUMERIC_ONE)
                        {
                            $qSetId = $exists[$qData[$qn]['uqid']];
                            if ($qData[$qn]['lastmod']>$addDate[$qSetId])
                            {
                                if ($isGrpAdmin)
                                {
                                    $query = QuestionSet::getQSetAndUserData($qSetId,$user->groupid);
                                    if($query)
                                    {
                                        $affectedRow = QuestionSet::updateDataForImportQSet($qData[$qn],$now,$qSetId,$hasImg);
                                    }
                                    else
                                    {
                                        continue;
                                    }
                                }
                                else
                                {
                                    $affectedRow = QuestionSet::UpdateQuestionsetDataIfNotAdmin($qData[$qn],$hasImg,$now,$qSetId,$user,$isAdmin,1);
                                }
                                if($affectedRow > AppConstant::NUMERIC_ZERO)
                                {
                                    $updateQ++;
                                    if (!empty($qd['qimgs']))
                                    {
                                        QImages::deleteByQsetId($qSetId);
                                        $qImages = explode("\n",trim($qd['qimgs']));
                                        if($qImages)
                                        {
                                            foreach($qImages as $qimg)
                                            {
                                                $p = explode(',',$qimg);
                                                $QImages = new QImages();
                                                $QImages->insertFilename($qSetId,$p);
                                            }
                                        }
                                    }
                                    else
                                    {
                                        continue;
                                    }
                                }
                            }
                        }
                        elseif(isset($exists[$qData[$qn]['uqid']]) && $params['merge']==-AppConstant::NUMERIC_ONE)
                        {
                            $qSetId = $exists[$qData[$qn]['uqid']];
                        }
                        else
                        {
                            $importUIdVal = '';
                            if ($qData[$qn]['uqid']=='0' || (isset($exists[$qData[$qn]['uqid']]) && $params['merge'] == AppConstant::NUMERIC_ZERO))
                            {
                                $importUIdVal = $qData[$qn]['uqid'];
                                $qData[$qn]['uqid'] = substr($mt,11).substr($mt,2,2).$qn;
                            }
                            $insert = new QuestionSet();
                            $insertId = $insert->InsertData($now,$user,$qData[$qn],$importUIdVal,$hasImg,$rights);
                            $qSetId = $insertId;
                            if (!empty($qData[$qn]['qimgs']))
                            {
                                $qImages = explode("\n",$qData[$qn]['qimgs']);
                                if($qImages)
                                {
                                    foreach($qImages as $qImg)
                                    {
                                        $p = explode(',',$qImg);
                                        $QImages = new QImages();
                                        $QImages->insertFilename($qSetId,$p);
                                    }
                                }
                            }
                            $newQ++;
                        }
                        foreach ($newLibs as $lib)
                        {
                            if (!(isset($doNotAddLi[$lib]) && $doNotAddLi[$lib] == $qSetId))
                            {

                                $insert = new LibraryItems();
                                $insert->insertData($lib,$qSetId,$user);
                                $newLi++;
                            }
                        }
                        $allQIds[] = $qSetId;
                    }
                    unlink($filename);
                    $qIdsToUpdate = array();
                    $qIdsToCheck = implode(',',$allQIds);
                    $questionSetData = QuestionSet::findDataToImportLib($qIdsToCheck);
                    $includedQs = array();
                    if($questionSetData)
                    {
                        foreach($questionSetData as $row)
                        {
                            $qIdsToUpdate[] = $row['id'];
                            if (preg_match_all('/includecodefrom\(UID(\d+)\)/',$row['control'],$matches,PREG_PATTERN_ORDER) > AppConstant::NUMERIC_ZERO)
                            {
                                $includedQs = array_merge($includedQs,$matches[1]);
                            }
                            if (preg_match_all('/includeqtextfrom\(UID(\d+)\)/',$row['qtext'],$matches,PREG_PATTERN_ORDER) > AppConstant::NUMERIC_ZERO)
                            {
                                $includedQs = array_merge($includedQs,$matches[1]);
                            }
                        }
                    }
                    if(count($qIdsToUpdate) > AppConstant::NUMERIC_ZERO)
                    {
                        $includedBackRef = array();
                        if(count($includedQs) > AppConstant::NUMERIC_ZERO)
                        {
                            $includedList = implode(',',$includedQs);
                            $idAndUniqueId = QuestionSet::getUniqueId($includedList);
                            if($idAndUniqueId)
                            {
                                foreach($idAndUniqueId as $row)
                                {
                                    $includedBackRef[$row['uniqueid']] = $row['id'];
                                }
                            }
                        }
                        $updateList = implode(',',$qIdsToUpdate);
                        $data = QuestionSet::getDataToImportLib($updateList);
                        if($data)
                        {
                            foreach($data as $row)
                            {
                                $control = addslashes(preg_replace('/includecodefrom\(UID(\d+)\)/e','"includecodefrom(".$includedbackref["\\1"].")"',$row['control']));
                                $qText = addslashes(preg_replace('/includeqtextfrom\(UID(\d+)\)/e','"includeqtextfrom(".$includedbackref["\\1"].")"',$row['qtext']));
                                QuestionSet::updateQuestionSetToImportLib($control,$qText,$row['id']);
                            }
                        }
                    }
                    $transaction->commit();
                }
                catch (Exception $e)
                {
                    $transaction->rollBack();
                    return false;
                }
            }
            elseif($_FILES['userfile']['name']!='')
            {
                $page_fileErrorMsg = "";
                $page_fileNoticeMsg = "";
                $uploadDir = AppConstant::UPLOAD_DIRECTORY.'importLibrary/';
                $uploadFile = $uploadDir . basename($_FILES['userfile']['name']);

                if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile))
                {
                    $page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".basename($uploadFile)."\" />\n";
                } else
                {
                    $page_fileErrorMsg .= "<p>Error uploading file!</p>\n";
                }
                $qData = $this->parseFile($uploadFile);
                if (!isset($qData['pack']) && !isset($qData['libdesc']))
                {
                    $page_fileErrorMsg .= "This does not appear to be a valid IMathAS file. <a href='".AppUtility::getURLFromHome('admin','admin/import-question-set?cid='.$courseId)."'>Try Again</a>";
                }
                if($qData)
                {
                    foreach ($qData as $qnd)
                    {
                        if(is_array($qnd))
                        {
                            if (is_numeric($qnd['uqid']))
                            {
                                $lookup[] = $qnd['uqid'];
                            }
                        }
                    }
                }
                if($lookup)
                {
                    $questionSetData = QuestionSet::getDataForImportQSet($lookup);
                }
                if($questionSetData)
                {
                    $existing = true;
                    $page_existingMsg = "<p>This file contains questions with uniqueids that already exist on this system.  With these questions, do you want to:<br/>\n";
                    $page_existingMsg .= "<input type=radio name=merge value='1' CHECKED>Update existing questions<br><input type=radio name=merge value='0'>Add as new question<br><input type=radio name=merge value='-1'>Keep existing question</p>\n";
                }
                else
                {
                    $existing = false;
                    $page_existingMsg = "";
                }
                if (isset($qData['pack']))
                {
                    $importLibLink = AppUtility::getURLFromHome('admin','admin/import-lib?cid='.$courseId);
                    $page_fileNoticeMsg .=  "<p>This file contains a library structure as well as questions.  Continue to use this form ";
                    $page_fileNoticeMsg .=  "if you with to import individual questions.<br />  Use the <a href='.$importLibLink'>Import Libraries</a> ";
                    $page_fileNoticeMsg .=  "page to import the libraries with structure</p>\n";
                }
            }
            else
            {
                //STEP 1 DATA MANIPULATION
            }
        }
        $this->layout = 'master';
        $this->includeJS(['importQuestionSet.js']);
        $responseData = array('overwriteBody' => $overwriteBody,'body' =>$body,'params' => $params,'courseId' => $courseId,'newQ' => $newQ,'updateQ' => $updateQ,'newLi' => $newLi,
            'page_fileNoticeMsg' => $page_fileNoticeMsg,'page_fileErrorMsg' => $page_fileErrorMsg,'page_fileHiddenInput' => $page_fileHiddenInput,'qData' => $qData,'page_existingMsg' => $page_existingMsg,'isAdmin' => $isAdmin,'isGrpAdmin' => $isGrpAdmin);
        return $this->renderWithData('importQuestionSet',$responseData);
    }

    function parseFile($file)
    {
        $handle = fopen($file,"r");
        $qNum = -AppConstant::NUMERIC_ONE;
        $part = '';
        while (!feof($handle))
        {
            $line = rtrim(fgets($handle, AppConstant::FOUR_ZERO_NINE_SIX));
            if ($line == "LIBRARY DESCRIPTION")
            {
                $part = "libdesc";
                continue;
            } else if ($line=="PACKAGE DESCRIPTION")
            {
                $qData['pack'] = 'set';
                continue;
            }
            else if ($line == "START QUESTION")
            {
                $part = '';
                if ($qNum>-1)
                {
                    foreach($qData[$qNum] as $k=>$val)
                    {
                        $qData[$qNum][$k] = rtrim($val);
                    }
                }
                $qNum++;
                continue;
            }
            else if ($line == "UQID")
            {
                $part = 'uqid';
                continue;
            }
            else if ($line == "LASTMOD")
            {
                $part = 'lastmod';
                continue;
            }
            else if ($line == "DESCRIPTION")
            {
                $part = 'description';
                continue;
            }
            else if ($line == "AUTHOR")
            {
                $part = 'author';
                continue;
            }else if ($line == "CONTROL")
            {
                $part = 'control';
                continue;
            }else if ($line == "QCONTROL")
            {
                $part = 'qcontrol';
                continue;
            }else if ($line == "QTEXT")
            {
                $part = 'qtext';
                continue;
            }else if ($line == "QTYPE")
            {
                $part = 'qtype';
                continue;
            }else if ($line == "ANSWER")
            {
                $part = 'answer';
                continue;
            }else if ($line == "SOLUTION")
            {
                $part = 'solution';
                continue;
            }else if ($line == "SOLUTIONOPTS")
            {
                $part = 'solutionopts';
                continue;
            }else if ($line == "EXTREF")
            {
                $part = 'extref';
                continue;
            }else if ($line == "LICENSE")
            {
                $part = 'license';
                continue;
            }else if ($line == "ANCESTORAUTHORS")
            {
                $part = 'ancestorauthors';
                continue;
            }else if ($line == "OTHERATTRIBUTION")
            {
                $part = 'otherattribution';
                continue;
            }else if ($line == "QIMGS")
            {
                $part = 'qimgs';
                continue;
            }else
            {
                if ($part=="libdesc")
                {
                    $qData['libdesc'] .= $line . "\n";
                }
                else if ($part=="qtype")
                {
                    if ($qNum>-1)
                    {
                        $qData[$qNum]['qtype'] .= $line;
                    }
                }
                else
                {
                    if ($qNum>-1 && $part!='')
                    {
                        $qData[$qNum][$part] .= $line . "\n";
                    }
                }
            }
        }
        fclose($handle);
        if ($qNum > -1)
        {
            foreach($qData[$qNum] as $k=>$val)
            {
                $qData[$qNum][$k] = rtrim($val);
            }
        }
        return $qData;
    }

    public function actionExportQuestionSet()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $userId = $user->id;
        $myRights = $user['rights'];
        $groupId= $user['groupid'];
        $isAdmin = false;
        $isGrpAdmin = false;
        $overWriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $page_hasSearchResults = AppConstant::NUMERIC_ZERO;
        $cId = (isset($params['cid'])) ? $params['cid'] : "admin" ;
        $params = $this->getRequestParams();
        $isTeacher = $this->isTeacher($userId, $cId);

        if (!(isset($isTeacher)) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
        } elseif (isset($params['cid']) && $params['cid']=="admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
        } elseif (!(isset($params['cid'])) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
        } else
        {
            if ($myRights < AppConstant::ADMIN_RIGHT) {
                $isGrpAdmin = true;
            } else if ($myRights == AppConstant::ADMIN_RIGHT) {
                $isAdmin = true;
            }
            $sessionId = $this->getSessionId();
            $sessiondata = $this->getSessionData($sessionId);

            if (isset($params['search']))
            {
                $safesearch = $params['search'];
                $search = stripslashes($safesearch);
                $search = str_replace('"','&quot;',$search);
                $sessiondata['lastsearch'] = str_replace(" ","+",$safesearch);
                Sessions::setSessionId($sessionId,$sessiondata);
            }
            else if (isset($sessiondata['lastsearch']))
            {
                $safesearch = str_replace("+"," ",$sessiondata['lastsearch']);
                $search = stripslashes($safesearch);
                $search = str_replace('"','&quot;',$search);
            }
            else
            {
                $search = '';
            }

            if (isset($params['libs'])) {
                if ($params['libs'] == '') {
                    $params['libs'] = '0';
                }
                $searchlibs = $params['libs'];
                $sessiondata['lastsearchlibs'] = $searchlibs;
                Sessions::setSessionId($sessionId,$sessiondata);
            } else if (isset($sessiondata['lastsearchlibs'])) {
                $searchlibs = $sessiondata['lastsearchlibs'];
            } else {
                $searchlibs = '0';
            }
            /*
             * get list of items already checked for export
             * USED FOR STEP 2
             */
            $checked = array_merge((array)$params['pchecked'],(array)$params['nchecked']);
            $clist = "'".implode("','",$checked)."'";
            $now = time();
            $result = QuestionSet::getById($checked);
            $i = AppConstant::NUMERIC_ZERO;
            $page_pChecked = array();
            foreach($result as $key => $line) {
                $page_pChecked[$i]['id'] = $line['id'];
                $page_pChecked[$i]['description'] = $line['description'];
                $page_pChecked[$i]['qtype'] = $line['qtype'];
                $i++;
            }
            /*
             * GRAB LIST OF LIBS/QUESTIONS, USED IN STEP 1 AND 2;
             */
            $llist = "'".implode("','",explode(',',$searchlibs))."'";

            if (substr($searchlibs, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE) == "0")
                $lnames[] = "Unassigned";

            $resultLib = Libraries::getByNameList($llist);
            foreach($resultLib as $key => $row) {
                    $lnames[] = $row['name'];
            }
            $lnames = implode(", ",$lnames);

            if (isset($search)) {
                if ($isAdmin) {
                    $query = new LibraryItems();
                    $query = $query->getDataByAdmin($safesearch, $llist, $checked, $clist);
                } else if ($isGrpAdmin) {
                    $query = new LibraryItems();
                    $query = $query->getDataByGrpAdmin($groupId, $llist, $safesearch, $checked, $clist);
                } else {
                   $query = new LibraryItems();
                   $query = $query->getDataByUserId($userId,$safesearch,$llist, $checked, $clist);
                }

                if ($query != AppConstant::NUMERIC_ZERO) {
                    $page_hasSearchResults = AppConstant::NUMERIC_ONE;
                    $i = AppConstant::NUMERIC_ZERO;
                    $page_nChecked = array();

                    foreach($query as $key => $line)
                    {
                        $page_nChecked[$i]['id'] = $line['id'];
                        $page_nChecked[$i]['description'] = $line['description'];
                        $page_nChecked[$i]['qtype'] = $line['qtype'];
                        $i++;
                    }
                }
            }

            /*
             * output export file here
             */
            if (isset($params['export']))
            {
                header('Content-type: text/imas');
                header("Content-Disposition: attachment; filename=\"imasexport.imas\"");
                echo "PACKAGE DESCRIPTION\n";
                echo $params['libdescription'];
                echo "\n";
                echo "\nSTART LIBRARY\nID\n1\nUID\n0\nLASTMODDATE\n$now\nNAME\n{$params['libname']}\nPARENT\n0\n";
                $qsetlist = implode(',',range(0,count($checked)-1));
                echo "\nSTART LIBRARY ITEMS\nLIBID\n1\nQSETIDS\n$qsetlist\n";

                /*
                 * first, lets pull any questions that have include__from so we can lookup backrefs
                 */

                $resultQSet = new QuestionSet();
                $resultQSet = $resultQSet->getByIdLike($clist);
                $includedqs = array();
                if($resultQSet)
                {
                    foreach($resultQSet as $key => $line)
                    {
                        if (preg_match_all('/includecodefrom\((\d+)\)/',$line['control'],$matches,PREG_PATTERN_ORDER) > 0)
                        {
                            $includedqs = array_merge($includedqs,$matches[1]);
                        }
                        if (preg_match_all('/includeqtextfrom\((\d+)\)/',$line['qtext'],$matches,PREG_PATTERN_ORDER) > 0)
                        {
                            $includedqs = array_merge($includedqs,$matches[1]);
                        }
                    }
                }
                $includedbackref = array();
                if (count($includedqs) > AppConstant::NUMERIC_ZERO)
                {
                    $includedlist = implode(',',$includedqs);
                    $resultId = new QuestionSet();
                    $resultId = $resultId->getUniqueIdToExportLib($includedlist);
                    foreach($resultId as $key => $row)
                    {
                        $includedbackref[$row['id']] = $row['uniqueid'];
                    }
                }
                $resultClist = new QuestionSet();
                $resultClist = $resultClist->getByIdUsingInClause($checked);
                $qcnt = AppConstant::NUMERIC_ZERO;
                foreach($resultClist as $key => $line)
                {
                    $line['control']  = preg_replace_callback('/^( +)/', function($matches){return str_repeat("&nbsp;", strlen($matches["$1"]));},$line['control']);
                    $line['qtext']  = preg_replace_callback('/^( +)/', function($matches){return str_repeat("&nbsp;", strlen($matches["$1"]));},$line['qtext']);
                    echo "\nSTART QUESTION\n";
                    echo "QID\n";
                    echo "$qcnt\n";
                    $qcnt++;
                    echo "\nUQID\n";
                    echo rtrim($line['uniqueid']) . "\n";
                    echo "\nLASTMOD\n";
                    echo rtrim($line['lastmoddate']) . "\n";
                    echo "\nDESCRIPTION\n";
                    echo rtrim($line['description']) . "\n";
                    echo "\nAUTHOR\n";
                    echo rtrim($line['author']) . "\n";
                    echo "\nCONTROL\n";
                    echo rtrim($line['control']) . "\n";
                    echo "\nQCONTROL\n";
                    echo rtrim($line['qcontrol']) . "\n";
                    echo "\nQTYPE\n";
                    echo rtrim($line['qtype']) . "\n";
                    echo "\nQTEXT\n";
                    echo rtrim($line['qtext']) . "\n";
                    echo "\nANSWER\n";
                    echo rtrim($line['answer']) . "\n";
                    echo "\nSOLUTION\n";
                    echo rtrim($line['solution']) . "\n";
                    echo "\nSOLUTIONOPTS\n";
                    echo rtrim($line['solutionopts']) . "\n";
                    echo "\nEXTREF\n";
                    echo rtrim($line['extref']) . "\n";
                    echo "\nLICENSE\n";
                    echo rtrim($line['license']) . "\n";
                    echo "\nANCESTORAUTHORS\n";
                    echo rtrim($line['ancestorauthors']) . "\n";
                    echo "\nOTHERATTRIBUTION\n";
                    echo rtrim($line['otherattribution']) . "\n";

                    if ($line['hasimg'] == AppConstant::NUMERIC_ONE) {
                        echo "\nQIMGS\n";
                        $r2 = new QImages();
                        $r2 = $r2->dataForExportLib($line['id']);
                        foreach($r2 as $key => $row){
                            echo $row['var'].','.$row['filename']. "\n";
                        }
                    }
                }
                exit;
            }
        }
        $this->includeJS(['tablesorter.js', 'general.js']);
        $responseData = array('myRights' => $myRights, 'isTeacher' => $isTeacher, 'overWriteBody' => $overWriteBody, 'body' => $body, 'params' => $params, 'cid' => $cId, 'search' => $search, 'searchlibs' => $searchlibs, 'page_pChecked' => $page_pChecked, 'lnames' => $lnames,
        'page_hasSearchResults' => $page_hasSearchResults, 'checked' => $checked, 'page_nChecked' => $page_nChecked);
        return $this->renderWithData('exportQuestionSet', $responseData);
    }

    public function actionReviewLibrary()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $userId = $user->id;
        $myRights = $user['rights'];
        $groupId= $user['groupid'];
        $isAdmin = false;
        $isGrpAdmin = false;
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $page_updatedMsg = "";

        if($myRights < AppConstant::TEACHER_RIGHT)
        {
            $overwriteBody = AppConstant::NUMERIC_ONE;
        } else
        {
            if (isset($params['source']))
            {
                $source = $params['source'];
            } else
            {
                $source = AppConstant::NUMERIC_ZERO;
            }

            if ($params['cid'] === "admin")
            {
                if ($myRights == AppConstant::ADMIN_RIGHT) {
                    $isAdmin = true;
                } else if ($myRights == AppConstant::GROUP_ADMIN_RIGHT) {
                    $isGrpAdmin = true;
                }
            }
            $sessionId = $this->getSessionId();
            $sessionData = $this->getSessionData($sessionId);
            Sessions::setSessionId($sessionId,$sessionData);

            if (!isset($_REQUEST['lib']))
            {
                if (isset($sessiondata['lastsearchlibs']))
                {
                    $inlibs = $sessiondata['lastsearchlibs'];
                } else
                {
                    $inlibs = '0';
                }
                if (substr($inlibs,0,1) == '0')
                {
                    $lnames[] = "Unassigned";
                }
                $result = Libraries::getByNameList($inlibs);
                foreach($result as $key => $row)
                {
                    $lnames[] = $row['name'];
                }
                $lnames = implode(", ",$lnames);
            }  else {

                $lib = $_REQUEST['lib'];
                if (isset($params['offset'])) {
                    $offset = $params['offset'];
                } else {
                    $offset = AppConstant::NUMERIC_ZERO;
                }
                $result = LibraryItems::getQSetId($lib);
                $cnt = count($result);
                if ($cnt == AppConstant::NUMERIC_ZERO) {
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $body = "Library empty";
                }

                $resultLibLimit = LibraryItems::getByLibIdWithLimit($lib, $offset);
                $qSetId = $resultLibLimit[0]['qsetid'];

                /*
                 * delete selected question
                 */

                if (isset($params['remove']) || isset($params['delete']))
                {
                     if (isset($params['confirm']))
                     {
                        if (isset($params['delete']))
                        {
                            if ($isGrpAdmin)
                            {
                                $result = QuestionSet::getQSetAndUserData($qSetId,$groupId);
                                if (count($result) > AppConstant::NUMERIC_ZERO)
                                {
                                    $result = new QuestionSet();
                                    $deleteQSetId = $result->updateId($qSetId);
                                    if ($deleteQSetId > AppConstant::NUMERIC_ZERO)
                                    {
                                        LibraryItems::deleteByQsetId($qSetId);
                                        $cnt--;
                                    }
                                }
                            } else {
                                $result = QuestionSet::updateInAdmin($qSetId,$isAdmin, $userId);
                                if ($result > AppConstant::NUMERIC_ZERO)
                                {
                                    LibraryItems::deleteByQsetId($qSetId);
                                    $cnt--;
                                }
                            }
                        }
                        if (isset($params['remove'])) {
                            $madeChange = false;
                            if ($isGrpAdmin)
                            {
                                $result = LibraryItems::getByLibItem($groupId, $qSetId, $lib);
                                if (count($result) > AppConstant::NUMERIC_ZERO) {
                                    $result = LibraryItems::deleteLibraryItems($lib,$qSetId);
                                    if ($result > AppConstant::NUMERIC_ZERO) {
                                        $madeChange = true;
                                    }
                                }

                            }
                            else {
                                $result = LibraryItems::deleteLib($qSetId,$lib,$isAdmin,$userId);
                                if ($result > AppConstant::NUMERIC_ZERO) {
                                    $madeChange = true;
                                }
                            }
                            if ($madeChange)
                            {
                                $result = LibraryItems::getIdByQid($qSetId);
                                if (count($result) == AppConstant::NUMERIC_ZERO)
                                {
                                    $tempLibArray['libid'] = AppConstant::NUMERIC_ZERO;
                                    $tempLibArray['qsetid'] = $qSetId;
                                    $tempLibArray['userid'] = $userId;
                                    $library = new LibraryItems();
                                    $library->createLibraryItems($tempLibArray);
                                }
                                $cnt--;
                            }
                        }

                        if ($offset == $cnt)
                        { /*
                            Just deleted last problem in library
                           */
                            if ($offset == AppConstant::NUMERIC_ZERO)
                            { /*
                                if already on first question
                              */
                                $overwriteBody = AppConstant::NUMERIC_ONE;
                                $body = "Library empty";
                            } else {
                            /*
                             * go back to last question
                             */
                                $offset--;
                            }
                        }
                        $resultLibLimit = LibraryItems::getByLibIdWithLimit($lib, $offset);
                        $qSetId = $resultLibLimit[0]['qsetid'];
                    }
                }
                elseif (isset($params['update'])) {
                    $params['qtext'] = preg_replace('/<([^<>]+?)>/',"&&&L$1&&&G",$params['qtext']);
                    $params['qtext'] = str_replace(array("<",">"),array("&lt;","&gt;"),$params['qtext']);
                    $params['qtext'] = str_replace(array("&&&L","&&&G"),array("<",">"),$params['qtext']);
                    $params['description'] = str_replace(array("<",">"),array("&lt;","&gt;"),$params['description']);
                    $now = time();

                    if ($isGrpAdmin)
                    {
                        $result = QuestionSet::getByOrUserId($qSetId,$groupId);
                        if (count($result) > AppConstant::NUMERIC_ZERO)
                        {
                            $result = QuestionSet::updateQSetId($params,$now,$qSetId);
                        }
                    } else {

                        $result = QuestionSet::updateQSetAdmin($params,$now,$qSetId,$isAdmin,$userId);
                    }
                    $page_updatedMsg = "Question Updated. ";
                }

                /*
                 * Default Display
                 */
                $row = LibraryItems::getByQSetANDLibAndUId($lib,$qSetId);
                $myLib = (intval($row['ownerid']) == $userId);
                if ($isAdmin || ($isGrpAdmin && intval($row['groupid']) == $groupId)) {
                    $myLib = true;
                }
                $lineQSet = QuestionSet::getByUIdQSetId($qSetId);
                $myQ = (intval($lineQSet['ownerid']) == $userId);

                if ($isAdmin || ($isGrpAdmin && intval($lineQSet['groupid']) == $groupId) || $lineQSet['userights'] == AppConstant::NUMERIC_FOUR)
                {
                    $myQ = true;
                }
                $seed = rand(0,10000);
                require("../components/displayQuestion.php");
                if (isset($params['seed']))
                {

                    list($score,$rawscores) = scoreq(AppConstant::NUMERIC_ZERO,$qSetId,$params['seed'],$params['qn0']);
                    $page_lastScore = "<p>Score on last answer: $score/1</p>\n";
                }
                $twobx = ($lineQSet['qcontrol']=='' && $lineQSet['answer']=='');

                if (!$myQ) {
                    $page_canModifyMsg = "<p>This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments</p>";
                }
                if (isset($CFG['AMS']['showtips'])) {
                    $showtips = $CFG['AMS']['showtips'];
                } else {
                    $showtips = AppConstant::NUMERIC_ONE;
                }
            }
        }
        if ($showtips == AppConstant::NUMERIC_TWO)
        {
            $this->includeJS(['eqntips.js']);
        }
        $this->includeJS(['AMhelpers_min.js', 'reviewlibrary.js']);
        $responseData = array('params' => $params,'inlibs' => $inlibs, 'source' => $source, 'cid' => $courseId, 'lnames' => $lnames, 'lib' => $lib, 'offset' => $offset, 'page_updatedMsg' => $page_updatedMsg, 'cnt' => $cnt,
        'qsetid' => $qSetId, 'lineQSet' => $lineQSet, 'page_lastScore' => $page_lastScore, 'seed' => $seed, 'page_canModifyMsg' => $page_canModifyMsg, 'myq' => $myQ, 'twobx' => $twobx, 'overwriteBody' => $overwriteBody, 'myLib' => $myLib);
        return $this->renderWithData('reviewLibrary', $responseData);
    }

    public function actionLatePass()
    {
        $this->guestUserHandler();
        $assessmentId = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $undo = $this->getParamVal('undo');
        $confirm = $this->getParamVal('confirm');
        $user = $this->getAuthenticatedUser();
        $userId = $user['id'];

        $result = Course::getByLatePasshrs($courseId);
        $hours = $result[0]['latepasshrs'];
        $now = time();
        $viewedAssess = array();
        $contentTrack = ContentTrack::getByTypeId($courseId, $userId);
        foreach($contentTrack as $key => $row){
            $viewedAssess[] = $row['typeid'];
        }

        if (isset($undo)) {
            echo "<div>Un-use LatePass</div>";
            $resultExp = Exceptions::getByUIdAndAssId($userId,$assessmentId);
            if ($resultExp == AppConstant::NUMERIC_ZERO)
            {
                echo '<p>Invalid</p>';
            }
            else {
                $row = $resultExp;
                if ($row['islatepass'] == AppConstant::NUMERIC_ZERO)
                {
                    echo '<p>Invalid</p>';
                }
                else {
                    $now = time();
                    $endDateData = Assessments::getEndDateById($assessmentId);
                    $endDate = $endDateData[0]['enddate'];
                    /*
                     * if it's past original due date and latepass is for less than latepasshrs past now, too late
                     */
                    if ($now > $endDate && $row['enddate'] < $now + $hours * 60 * 60) {
                        echo '<p>Too late to un-use this LatePass</p>';
                    } else {
                        if ($now < $endDate) {
                         /*
                          * before enddate, return all latepasses
                          */
                            $n = $row['islatepass'];
                            Exceptions::deleteByUserIdAndAssId($userId, $assessmentId);
                        }
                        else {
                         /*
                          * figure how many are unused
                          */
                            $n = floor(($row['enddate'] - $now)/($hours*60*60));
                            $newEnd = $row['enddate'] - $n*$hours*60*60;
                            if ($row['islatepass'] > $n) {
                                Exceptions::updateData($n, $newEnd, $userId, $assessmentId);
                            } else {
                                Exceptions::deleteByUserIdAndAssId($userId, $assessmentId);
                                $n = $row['islatepass'];
                            }
                        }
                        echo "<p>Returning $n LatePass".($n > AppConstant::NUMERIC_ONE?"es":"")."</p>";
                        Student::updateLatePass($n, $userId, $courseId);
                    }
                }
            }
        } else if (isset($confirm)) {
            $addTime = $hours*60*60;
            $result = Assessments::getDateAndAllowById($assessmentId);
            list($allowLate,$enddate,$startdate) = $result;

            $result = Exceptions::getEndDateById($userId, $assessmentId);
            $hasException = false;
            if ($result == AppConstant::NUMERIC_ZERO) {
                $usedLatepasses = AppConstant::NUMERIC_ZERO;
                $thised = $enddate;
            } else {
                $r = $result;
                $usedlatepasses = round(($r['enddate'] - $enddate)/($hours*3600));
                $hasException = true;
                $thised = $r['enddate'];
            }

            if (($allowLate % AppConstant::NUMERIC_TEN == AppConstant::NUMERIC_ONE || $allowLate%10-1 > $usedLatepasses) && ($now < $thised || ($allowLate > 10 && ($now - $thised) < $hours*3600 && !in_array($assessmentId, $viewedAssess))))
            {
                $result = Student::updateLatePassById($userId,$courseId);
                if ($result > AppConstant::NUMERIC_ZERO)
                {
                    if ($hasException) {
                    /*
                     * already have exception
                     */
                        Exceptions::updateIsLatePass($addTime,$userId, $assessmentId);
                    } else {
                        $enddate = $enddate + $addTime;
                        $insertData = new Exceptions();
                        $insertData->insertByUserData($userId, $assessmentId, $startdate, $enddate);
                    }
                }
            }
            $this->redirect(AppUtility::getURLFromHome('course', 'course/index?id=' . $assessmentId . '&cid=' . $courseId));
        } else {
            echo "Redeem LatePass</div>\n";

            $result = Student::getLatePassById($userId, $courseId);
            $numLatepass = $result[0]['latepass'];

            $result = Assessments::getDateAndAllowById($assessmentId);
            list($allowlate,$enddate,$startdate) = $result;

            $result = Exceptions::getEndDateById($userId, $assessmentId);
            $hasException = false;
            if ($result == AppConstant::NUMERIC_ZERO)
            {
                $usedLatepasses = AppConstant::NUMERIC_ZERO;
                $thised = $enddate;
            } else {
                $r = $result;
                $usedLatepasses = round(($r['enddate'] - $enddate)/($hours*3600));
                $hasException = true;
                $thised = $r['enddate'];
            }

            if ($numLatepass == AppConstant::NUMERIC_ZERO)
            {
                echo "<p>You have no late passes remaining.</p>";
            }
            else if (($allowlate % 10 == AppConstant::NUMERIC_ONE || $allowlate %10-1 > $usedLatepasses) && ($now < $thised || ($allowlate > 10 && ($now - $thised) < $hours*3600 && !in_array($assessmentId,$viewedAssess))))
            {

            } else {
                echo "<p>You are not allowed to use additional latepasses on this assessment.</p>";
            }
        }
    }
}