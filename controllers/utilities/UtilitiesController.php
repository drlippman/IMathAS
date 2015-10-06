<?php
namespace app\controllers\utilities;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\DbSchema;
use app\models\FirstScores;
use app\models\forms\LtiUserForm;
use app\models\Groups;
use app\models\InlineText;
use app\models\Items;
use app\models\LibraryItems;
use app\models\LinkedText;
use app\models\Log;
use app\models\Message;
use app\models\QuestionSet;
use app\models\Student;
use app\models\Teacher;
use app\models\User;
use \yii\web\Controller;
use app\controllers\AppController;

class UtilitiesController extends AppController
{
    public $a = array();

    public function beforeAction($action)
    {
        $user = $this->getAuthenticatedUser();
        return $this->accessForAdmin($user['rights']);
    }

    public function actionAdminUtilities()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $removeLti = $this->getParamVal('removelti');
        $form = $this->getParamVal('form');
        $debug = $this->getParamVal('debug');
        $params = $this->getRequestParams();
        $this->layout = 'master';
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::UNAUTHORIZED;
        }
        if (isset($removeLti))
        {
            $id = intval($this->getParamVal('removelti'));
            LtiUserForm::deleteLtiUsr($id);
        }
        if (isset($form))
        {

            if ($form == 'lookup')
            {
                if(!empty($params['LastName']) || !empty($params['FirstName']) || !empty($params['SID']) || !empty($params['email']))
                {

                    if(!empty($params['SID']))
                    {
                        $queryForUser = User::getDataByJoin($params['SID'],AppConstant::NUMERIC_ZERO);
                    }
                    elseif(!empty($params['email']))
                    {
                        $queryForUser = User::getDataByJoin($params['email'],AppConstant::NUMERIC_ONE);

                    }
                    else
                    {
                        $queryForUser = User::getDataByJoinForName($params);
                    }
                    if(!$queryForUser)
                    {
                        $message = AppConstant::NORESULT;
                    }
                    else
                    {
                        foreach($queryForUser as $userData)
                        {

                            $queryForCourse[$userData['id']] = Course::queryForCourse($userData['id']);
                            $queryFromCourseForTutor[$userData['id']] = Course::queryFromCourseForTutor($userData['id']);
                            $queryFromCourseForTeacher[$userData['id']] = Course::queryFromCourseForTeacher($userData['id']);
                            $queryForLtiUser[] = LtiUserForm::getUserData($userData['id']);
                        }

                    }
                }

                }
        }
        $this->includeCSS(['utilities.css']);
        $responseData = array('form' => $form,'debug' => $debug,'body' => $body,'message' => $message,'queryForCourse' => $queryForCourse,'queryFromCourseForTutor' => $queryFromCourseForTutor,'queryFromCourseForTeacher' => $queryFromCourseForTeacher,'queryForLtiUser' => $queryForLtiUser,'params' => $params,'queryForUser' => $queryForUser);
        return $this->render('adminUtilities',$responseData);
    }

    public function actionRescueCourse()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $this->layout = "master";
        $courseId = $params['cid'];
        if(isset($courseId))
        {
           $query = Course::getItemOrderAndBlockCnt($courseId);
            $items = unserialize($query['itemorder']);
            $blockCnt = $query['blockcnt'];
        }
        global $itemsFnd;
        $itemsFnd = array();
        $this->fixSub($items);
        $recoveredItems = array();
        $queryForItems = Items::getByCourseId($courseId);
        if($queryForItems)
        {
            foreach($queryForItems  as $singleItem)
            {
                if (!in_array($singleItem['id'],$itemsFnd))
                {
                    $recoveredItems[] = $singleItem['id'];
                }
            }
        }
        if(count($recoveredItems) > AppConstant::NUMERIC_ZERO)
        {
            $block = array();
            $block['name'] = "Recovered items";
            $block['id'] = $blockCnt ;
            $block['startdate'] = AppConstant::NUMERIC_ZERO;
            $block['enddate'] = AppConstant::ALWAYS_TIME;
            $block['avail'] = AppConstant::NUMERIC_ZERO;
            $block['SH'] = "HO";
            $block['colors'] = '';
            $block['fixedheight'] = AppConstant::NUMERIC_ZERO;
            $block['public'] = AppConstant::NUMERIC_ZERO;
            $block['items'] = $recoveredItems;
            array_push($items,$block);
            $itemOrder = serialize($items);
            Course::UpdateItemOrderAndBlockCnt($itemOrder,$blockCnt,$courseId,AppConstant::NUMERIC_ZERO);
        }
        else
        {
            $itemOrder = serialize($items);
            Course::UpdateItemOrderAndBlockCnt($itemOrder,$blockCnt,$courseId,AppConstant::NUMERIC_ONE);
        }
        $this->includeCSS(['utilities.css']);
        return $this->renderWithData('rescueCourse',['items' => $items,'recoveredItems' => $recoveredItems]);
    }
    public function actionGetStudentCount()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        if($user->rights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        $now = time();
        $date = mktime(AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_SEVEN,AppConstant::NUMERIC_TEN,AppConstant::YEAR_TWENTY_ELEVEN);
        $studentCount = User::getDistinctUserCount($date);
        $days = $this->getParamVal('days');
        if(isset($days))
        {
            $days = intval($this->getParamVal('days'));
        }
        else
        {
            $days = AppConstant::NUMERIC_THIRTY;
        }
        if (isset($CFG['GEN']['guesttempaccts'])) {
            $skipCid = $CFG['GEN']['guesttempaccts'];
        } else {
            $skipCid= array();
        }
        $queryForCid = Course::getDataByTemplate();
        if($queryForCid)
        {
            foreach($queryForCid as $data)
            {
                $skipCid[] =$data['id'];
            }
        }
        $skipCidS =implode(',',$skipCid);
        $date = $now - AppConstant::SECONDS_CONVERSION*$days;
        $stuCount = User::getStuCount($skipCid,$date,$skipCidS);
        $queryForStu = User::queryForStu($skipCid,$date,$skipCidS);
        $teacherCnt = User::getCountByJoin($skipCid,$date,$skipCidS);
        $stuName = Student::getFNameAndLNameByJoin($date);
        $date = $now - AppConstant::SECONDS*AppConstant::SECONDS;
        $queryByDistinctCnt = User::getStuData($date);
        $email = $this->getParamVal('email');
        if(isset($email) && $user->rights > AppConstant::GROUP_ADMIN_RIGHT)
        {
            $userEmail = User::getUserEmail($user);
        }
        $this->includeCSS(['utilities.css']);
        $responseData = array('studentCount' => $studentCount,'stuCount' => $stuCount,'queryForStu' => $queryForStu,'teacherCnt' => $teacherCnt,'stuName' => $stuName,'queryByDistinctCnt' => $queryByDistinctCnt,'userEmail' => $userEmail,'days' => $days,'email' => $email,'user' => $user,'body' => $body,'message' => $message );
        return $this->renderWithData('getStudentCount',$responseData);
    }

    public function actionGetStudentDetailCount()
    {
        $this->layout = 'master';
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $days = $this->getParamVal('days');
        $start = $this->getParamVal('start');
        $end = $this->getParamVal('end');
        if($user->rights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        $now = time();
        $start = $now - AppConstant::SECONDS_CONVERSION *AppConstant::NUMERIC_THIRTY;
        $end = $now;
        if(isset($start))
        {
            $parts = explode('-',$start);
            if (count($parts)== AppConstant::NUMERIC_THREE)
            {
                $start = mktime(0,0,0,$parts[0],$parts[1],$parts[2]);
            }
        }
        else if(isset($days))
        {
            $start = $now - AppConstant::SECONDS_CONVERSION *intval($days);
        }
        if(isset($end))
        {
            $parts = explode('-',$end);
            if (count($parts)== AppConstant::NUMERIC_THREE)
            {
                $end = mktime(0,0,0,$parts[0],$parts[1],$parts[2]);
            }
        }
        $this->includeCSS(['utilities.css']);
        $query = Student::getstuDetails($start,$now,$end);
        return $this->renderWithData('getStudentDetailCount',['query' => $query,'start' => $start,'end'=> $end,'body' => $body,'message' => $message]);
    }

    public function actionReplaceVideo()
    {
        $this->layout = 'master';
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        if(!empty($params['from']) && ($params['to']))
        {
            $from = trim($params['from']);
            $to = trim($params['to']);
            if (strlen($from) != AppConstant::NUMERIC_ELEVEN || strlen($to) != AppConstant::NUMERIC_ELEVEN || preg_match('/[^A-Za-z0-9_\-]/',$from) || preg_match('/[^A-Za-z0-9_\-]/',$to))
            {
            }else
            {
                $updatedInlineText = InlineText::updateVideoId($from,$to);
                $updatedLinkedText = LinkedText::updateVideoId($from,$to);
                $updatedLinkedTextSummary = LinkedText::updateSummary($from,$to);
                $updatedAssessment = Assessments::updateVideoId($from,$to);
                $updatedAssessmentSummary = LinkedText::updateSummary($from,$to);
                $updatedQuestionSet = QuestionSet::updateVideoId($from,$to);
            }
        }
        $this->includeCSS(['utilities.css']);
        return $this->renderWithData('replaceVideo',['updatedInlineText' => $updatedInlineText,'updatedLinkedText' => $updatedLinkedText,'updatedLinkedTextSummary' => $updatedLinkedTextSummary,'updatedAssessment' => $updatedAssessment,'updatedAssessmentSummary' => $updatedAssessmentSummary,'updatedQuestionSet' => $updatedQuestionSet,'params' => $params,'body' => $body,'message' => $message,'from' => $from,'to' => $to]);
    }
    public function actionListExternalRef()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $questionSetData = QuestionSet::getExternalRef();
        $this->includeCSS(['utilities.css']);
        return $this->renderWithData('listExternalRef',['questionSetData' => $questionSetData]);
    }

    public function actionApprovePendingReq()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $skipN = $this->getParamVal('skipn');
        $go = $this->getParamVal('go');
        $installName = AppConstant::INSTALL_NAME;
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        if(isset($skipN))
        {
            $offset = intval($skipN);
        }
        else
        {
            $offset = AppConstant::NUMERIC_ZERO;
        }
        if(isset($go))
        {
            if (isset($params['skip']))
            {
                $offset++;
            }
            else if (isset($params['deny']))
            {
                User::updateUserForPendingReq($params['id']);
                if (isset($CFG['GEN']['enrollonnewinstructor']))
                {

                    foreach ($CFG['GEN']['enrollonnewinstructor'] as $rCId)
                    {
                        unenrollstu($rCId, array(intval($_POST['id'])));
                    }
                }
            }
            else if (isset($params['approve']))
            {
                $group = AppConstant::NUMERIC_ZERO;
                if ($params['group'] > -AppConstant::NUMERIC_ONE)
                {
                    $group = intval($params['group']);
                }
                else if (trim($params['newgroup'])!='')
                {
                    $groups = new Groups();
                    $insertId = $groups->insertNewGroupForUtilities($params['newgroup']);
                    $group = $insertId;
                }
                User::updateRights($params['id'],AppConstant::LIMITED_COURSE_CREATOR_RIGHT,$group);
                $userData = User::getUserDataForUtilities($params['id']);
                $headers = 'Account Approval';
                $message = '<style type="text/css">p {margin:0 0 1em 0} </style><p>Hi '.$userData['FirstName'].'</p>';
                if ($installName == AppConstant::INSTALL_NAME)
                {
                    $message .= '<p>Welcome to MyOpenMath.  Your account has been activated, and you\'re all set to log in at <a href="https://www.myopenmath.com">MyOpenMath.com</a> as an instructor using the username <b>'.$userData['SID'].'</b> and the password you provided.</p>';
                    $message .= '<p>I\'ve signed you up as a "student" in the Support Course, which has forums in which you can ask questions, report problems, or find out about new system improvements.</p>';
                    $message .= '<p>I\'ve also signed you up for the MyOpenMath Training Course.  This course has video tutorials and assignments that will walk you through learning how to use MyOpenMath in your classes.</p>';
                    $message .= '<p>David Lippman<br/>admin@myopenmath.com<br/>MyOpenMath administrator</p>';
                } else if ($installName == 'WAMAP')
                {
                    $message .= 'Welcome to WAMAP.  Your account has been activated, and you\'re all set to log in as an instructor using the username <b>'.$userData['SID'].'</b> and the password you provided.</p>';
                    $message .= '<p>I\'ve signed you up as a "student" in the Support Course, which has forums in which you can ask questions, report problems, or find out about new system improvements.</p>';
                    $message .= '<p>I\'ve also signed you up for the WAMAP Training Course.  This course has video tutorials and assignments that will walk you through learning how to use WAMAP in your classes.</p>';
                    $message .= '<p>If you are from outside Washington State, please be aware that WAMAP.org is only intended for regular use by Washington State faculty.  You are welcome to use this site for trial purposes.  If you wish to continue using it, we ask you set up your own installation of the IMathAS software, or use MyOpenMath.com if using content built around an open textbook.</p>';
                    $message .= '<p>David Lippman<br/>dlippman@pierce.ctc.edu<br/>Instructor, Math @ Pierce College and WAMAP administrator</p>';
                }
                if (isset($CFG['GEN']['useSESmail']))
                {
                    /*Remaining
                     * SESmail($userData['email'], 'MyOpenMath', $installName . ' Account Approval', $message);*/
                } else
                {
                    AppUtility::sendMail($headers, $message, $userData['email']);
                }
            }
            return $this->redirect('approve-pending-req?skipn='.$offset);
        }
        $findPendingUser = User::findPendingUser($offset);
        $details = '';
        if(count($findPendingUser) != AppConstant::NUMERIC_ZERO)
        {
            if($findPendingUser)
            {
                    $getLogDetails = Log::getLogDetails($findPendingUser['id']);
                    if($getLogDetails)
                    {
                        $log = explode('::', $getLogDetails[0]['log']);
                        $details = $log[1];
                    }
            }
        }
        $groupsName = Groups::getIdAndName();
        $this->includeCSS(['utilities.css']);
        return $this->renderWithData('approvePendingReq',['findPendingUser' => $findPendingUser,'details' => $details,'groupsName' => $groupsName,'message' => $message,'body' => $body,'offset' => $offset]);
    }

    public function actionListWrongLibFlag()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        $this->includeCSS(['utilities.css']);
        $data = QuestionSet::getWrongLibFlag();
        return $this->renderWithData('listWrongLibFlag',['body ' => $body,'message' => $message,'data' => $data ]);
    }
    public function actionUpdateWrongLibFlag()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        if(isset($params['data']))
        {
            $info = array();
            $lines = explode("\n",$params['data']);
            $valArray = array();
            $tot = AppConstant::NUMERIC_ZERO;
            foreach ($lines as $line)
            {
                $line = str_replace(array("\r","\t"," "),'',$line);
                list($uqId,$uLibId) = explode('@',$line);
                $valArray[] = "('$uqId','$uLibId')";
                if (count($valArray) == 500)
                {
                    $tot += $this->doQuery($valArray);
                    $valArray = array();
                }
            }
            if (count($valArray)>AppConstant::NUMERIC_ZERO)
            {
                $tot += $this->doQuery($valArray);
            }
        }
        $this->includeCSS(['utilities.css']);
        $responseData = array('tot' => $tot,'message' => $message,'body' => $body,'params' => $params);
        return $this->renderWithData('updateWrongLibFlag',$responseData);
    }
    public function actionUpdateExternalRef()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        if(isset($params['data']))
        {
            $info = array();
            $lines = explode("\n",$params['data']);
            foreach ($lines as $line)
            {
                list($uid,$lastM,$extRef) = explode('@',$line);
                $extRef = str_replace(array("\r","\t"," "),'',$extRef);
                $info[$uid] = array($lastM,$extRef);
            }
            $questions = QuestionSet::getDataToUpdateExtRef();
            foreach($questions as $question)
            {
                if (!isset($info[$question['uniqueid']])) {continue;}
                if (trim($question['extref'])!=trim($info[$question['uniqueid']][1]))
                {
                    if ($question['extref']=='')
                    {
                        QuestionSet::updateExternalRef($info[$question['uniqueid']][1],$question['id']);
                    }
                    else
                    {
                        if ($question['lastmoddate']>$info[$question['uniqueid']][0])
                        {

                        } else
                        {

                            QuestionSet::updateExternalRef($info[$question['uniqueid']][1],$question['id']);
                        }

                    }

                }
            }

        }
        $this->includeCSS(['utilities.css']);
        $responseData = array('questions' => $questions,'info' => $info,'extRef' => $extRef,'params' => $params,'body' => $body,'message' => $message);
        return $this->renderWithData('updateExternalRef',$responseData);
    }

    public function actionBlockSearch()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        if(isset($params['search']))
        {
            $Search = trim($params['search']);
            $blockTitles = Course::getBlckTitles($Search);
            if($blockTitles)
            {
                foreach($blockTitles as $singleBlock)
                {
                    $items = unserialize($singleBlock['itemorder']);
                    $det = $this->getStr($items, $Search, '0');
                }
            }
        }
        $this->layout = 'master';
        $this->includeCSS(['utilities.css']);
        $responseData = array('params' => $params,'body' => $body,'message' => $message,'blockTitles' => $blockTitles,'det' => $det);
        return $this->renderWithData('blockSearch',$responseData);
    }

    public function actionUpdateQuestionsData()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        ini_set('display_errors',AppConstant::NUMERIC_ONE);
        error_reporting(E_ALL);
        @set_time_limit(AppConstant::NUMERIC_ZERO);
        ini_set("max_input_time", "3600");
        ini_set("max_execution_time", "3600");
        ini_set("memory_limit", "712857600");
        $start = microtime(true);
        $data = DbSchema::getData();
        if(count($data) == AppConstant::NUMERIC_ZERO)
        {
            $lastUpdate = AppConstant::NUMERIC_ZERO;
            $lastFirstUpdate = AppConstant::NUMERIC_ZERO;
        }
        else
        {
            if($data)
            {
                foreach($data as  $result)
                {
                    if ($result['id']== AppConstant::NUMERIC_THREE)
                    {
                        $lastUpdate = $result['ver'];
                    } else {
                        $lastFirstUpdate = $result['ver'];
                    }
                }
            }
        }
        $trim = .2;
        $dosLowMethod = false;
        if ($dosLowMethod)
        {
            $qTimes = array();
            $query = AssessmentSession::getDataToUpdateQuestionUsageData($lastUpdate);
            if($query)
            {
                foreach($query as $row)
                {
                    if (strpos($row['questions'],';')===false)
                    {
                        $q = explode(",",$row['questions']);

                    }else
                    {
                        list($questions,$bestQuestions) = explode(";",$row['questions']);
                        $q = explode(",",$bestQuestions);
                    }

                    $t = explode(',',$row['timeontask']);
                    foreach ($q as $k=>$qn)
                    {
                        if ($t[$k]=='') {continue;}
                        if (isset($qTimes[$qn])) {
                            $qTimes[$qn] .= '~'.$t[$k];
                        } else {
                            $qTimes[$qn] = $t[$k];
                        }
                    }
                }
            }
            $qsTimes = array();
            $qsFirstTimes = array();
            $qsFirstScores = array();
            $questionSet = QuestionSet::getDataToUpdateQuestionUsageData();
            if($questionSet)
            {

                foreach($questionSet as $Question)
                {
                    if (isset($qTimes[$Question['id']])) {
                        if (isset($qsTimes[$Question['questionsetid']])) {
                            $qsTimes[$Question['questionsetid']] .= '~'.$qTimes[$Question['id']];
                        } else {
                            $qsTimes[$Question['questionsetid']] = $qTimes[$Question['id']];
                        }
                    }
                }
            }
            unset($qTimes);
            $avgTime = array();
            if($qsTimes)
            {
                foreach ($qsTimes as $qsId=>$tv)
                {
                    $times = explode('~',$tv);
                    sort($times, SORT_NUMERIC);
                    $trimN = floor($trim*count($times));
                    $times = array_slice($times,$trimN,count($times)-2*$trimN);
                    $avgTime[$qsId] = round(array_sum($times)/count($times));
                }
            }
        }
        $avgFirstTime = array();
        $avgFirstScore = array();
        $n = array();
        $thisTimes = array();
        $thisScores = array();
        $lastQ = -AppConstant::NUMERIC_ONE;
        $fScore = FirstScores::getDataForQuestionUsage($lastFirstUpdate);
        if($fScore)
        {
            foreach($fScore as $row)
            {
                if ($row['qsetid'] != $lastQ && $lastQ> AppConstant::NUMERIC_ZERO) {
                    $n[$lastQ] = count($thisScores);
                    sort($thisTimes, SORT_NUMERIC);
                    $trimN = floor($trim*count($thisTimes));
                    $thisTimes = array_slice($thisTimes,$trimN,count($thisTimes)-AppConstant::NUMERIC_TWO*$trimN);
                    $avgFirstTime[$lastQ] = round(array_sum($thisTimes)/count($thisTimes));
                    $avgFirstScore[$lastQ] = round(array_sum($thisScores)/count($thisScores));
                    $thisTimes = array();
                    $thisScores = array();
                }
                $thisTimes[] = $row['timespent'];
                $thisScores[] = $row['score'];
                if ($row['qsetid'] != $lastQ) {
                    $lastQ = $row['qsetid'];
                }
            }
        }
        if (count($thisTimes) > AppConstant::NUMERIC_ZERO)
        {
            $n[$lastQ] = count($thisScores);
            sort($thisTimes, SORT_NUMERIC);
            $trimN = floor($trim*count($thisTimes));
            $thisTimes = array_slice($thisTimes,$trimN,count($thisTimes)-AppConstant::NUMERIC_TWO*$trimN);
            $avgFirstTime[$lastQ] = round(array_sum($thisTimes)/count($thisTimes));
            $avgFirstScore[$lastQ] = round(array_sum($thisScores)/count($thisScores));
        }
        $nq = count($n);
        $toTn = array_sum($n);
        if ($lastFirstUpdate == AppConstant::NUMERIC_ZERO)
        {
            if($n)
            {
                foreach ($n as $qsId=>$nval)
                {
                    if ($dosLowMethod) {
                        $avg = addslashes($avgTime[$qsId].','.$avgFirstTime[$qsId].','.$avgFirstScore[$qsId].','.$n[$qsId]);
                    }
                    else
                    {
                        $avg = addslashes('0,'.$avgFirstTime[$qsId].','.$avgFirstScore[$qsId].','.$n[$qsId]);
                    }
                    QuestionSet::updateAvgTime($avg,$qsId);
                }
            }

        }
        else
        {
            $questionSetData = QuestionSet::getIdAndAvgTime();
            if($questionSetData)
            {
                foreach($questionSetData as $row)
                {
                    $qsId = $row['id'];
                    if (!isset($avgFirstTime[$qsId]) || $n[$qsId]== AppConstant::NUMERIC_ZERO) {continue;}

                    if (strpos($row['avgtime'],',')!==false)
                    {
                        list($oldAvgTime,$oldFirstTime,$oldFirstScore,$oldN) = explode(',',$row['avgtime']);
                        if ($dosLowMethod)
                        {
                            $avgTime[$qsId] = round(($avgTime[$qsId]*$n[$qsId] + $oldAvgTime*$oldN)/($n[$qsId]+$oldN));
                        }
                        $avgFirstTime[$qsId] = round(($avgFirstTime[$qsId]*$n[$qsId] + $oldFirstTime*$oldN)/($n[$qsId]+$oldN));
                        $avgFirstScore[$qsId] = round(($avgFirstScore[$qsId]*$n[$qsId] + $oldFirstScore*$oldN)/($n[$qsId]+$oldN));
                        $n[$qsId] += $oldN;
                    }
                    if ($dosLowMethod)
                    {
                        $avg = addslashes($avgTime[$qsId].','.$avgFirstTime[$qsId].','.$avgFirstScore[$qsId].','.$n[$qsId]);
                    }
                    else
                    {
                        $avg = addslashes('0,'.$avgFirstTime[$qsId].','.$avgFirstScore[$qsId].','.$n[$qsId]);
                    }
                    QuestionSet::updateAvgTime($avg,$qsId);
                }
            }
        }
        $maxId = FirstScores::getMaxId();
        if ($lastFirstUpdate == AppConstant::NUMERIC_ZERO)
        {
            $lastFirstUpdate = $maxId;
            if ($dosLowMethod)
            {
                $lastUpdate = time();
            }
            DbSchema::insertData($lastFirstUpdate,$lastUpdate);
        }
        else
        {
            $lastFirstUpdate = $maxId;
            DbSchema::updateData($lastFirstUpdate,AppConstant::NUMERIC_FOUR);
            if($dosLowMethod)
            {
                $lastUpdate = time();
                DbSchema::updateData($lastUpdate,AppConstant::NUMERIC_THREE);
            }
        }
        $this->includeCSS(['utilities.css']);
        return $this->renderWithData('updateQuestionUsage',['nq' => $nq,'toTn' => $toTn,'start' => $start]);
    }

    public function actionItemSearch()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $massEnd = $this->getParamVal('masssend');
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_AUTHORITY;
        }
        if ((isset($params['submit']) && $params['submit']=="Message") || isset($massEnd))
        {
            $this->a = $params;
            return $this->redirect(array('mass-end','params' => $params));
        }
        $search = $params['search'];
        if(isset($search))
        {
                $searchResult = User::getUserDetailsByJoin($search);
        }
        $this->includeCSS(['utilities.css']);
        $responseData = array('params' => $params,'body' => $body,'message' => $message,'searchResult' => $searchResult);
        return $this->renderWithData('itemSearch',$responseData);
    }

    public function actionMassEnd()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $massEnd = $this->getParamVal('masssend');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $teacherId = true;
        $calledFrom = "itemsearch";
        $aid =$this->getParamVal('aid');
        $id = $this->getParamVal('id');
        if($user->rights != AppConstant::TEACHER_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = AppConstant::NO_TEACHER_RIGHTS;
        }

        if(isset($params['message']))
        {
            $toIgnore = array();
            if (intval($params['aidselect'])!= AppConstant::NUMERIC_ZERO)
            {
                $limitAid = $params['aidselect'];
                $query = AssessmentSession::getDataForUtilities($limitAid);
                if($query)
                {
                    foreach($query as $row)
                    {
                        $toIgnore[] = $row['userid'];
                    }
                }
            }
            $message = $params['message'];
            $subject = $params['subject'];
            if($massEnd == 'Message')
            {
                $now = time();
                $toList = "'".implode("','",explode(",",$params['tolist']))."'";
                $userDetails = User::getFirstNameAndLastName($toList);
                $emailAddy = array();
                if($userDetails)
                {
                    foreach($userDetails as $user)
                    {
                        if (!in_array($user['id'],$toIgnore))
                        {
                            $fullNames[$user['id']] = $user['LastName']. ', '.$user['FirstName'];
                            $firstNames[$user['id']] = addslashes($user['FirstName']);
                            $lastNames[$user['id']] = addslashes($user['LastName']);
                        }
                    }
                }
                $toList = explode(',',$params['tolist']);
                if (isset($params['savesent']))
                {
                    $isRead = AppConstant::NUMERIC_ZERO;
                } else
                {
                    $isRead = AppConstant::NUMERIC_FOUR;
                }
                if($toList)
                {
                    foreach($toList as $msgTo)
                    {
                        if (!in_array($msgTo,$toIgnore))
                        {
                            $message = str_replace(array('LastName','FirstName'),array($lastNames[$msgTo],$firstNames[$msgTo]),$params['message']);
                            $insert = new Message();
                            $insert->insertFromUtilities($params['subject'],$message,$msgTo,$user->id,time(),$isRead,$courseId);

                        }
                    }
                }
                $toList = array();
                if ($params['self']=="self")
                {
                    $toList[] = $user->id;
                }
                elseif($params['self']=="allt")
                {
                    $teacherData = Teacher::getUserIdByJoin($courseId);
                    if($teacherData)
                    {
                        foreach($teacherData as $row)
                        {
                            $toList[] = $row['id'];
                        }
                    }
                }
                $sentTo = implode('<br/>',$fullNames);
                $srt = AppConstant::INSTRUCTORNOTE;
                $message = $params['message'] . addslashes("$srt $course->name: <br/> $sentTo </p>\n");
                foreach($toList as $data)
                {
                    $insert = new Message();
                    $insert->insertFromUtilities($params['subject'],$message,$data,$user->id,time(),AppConstant::NUMERIC_ZERO,$courseId);
                }
            }
            else
            {
                $toList = "'".implode("','",explode(",",$params['tolist']))."'";
                $query = User::getFirstNameAndLastName($toList);
                $emailAddy = array();
                foreach($query as $row)
                {
                    if (!in_array($row['id'],$toIgnore))
                    {
                        $emailAddy[] = "{$row['FirstName']} {$row['LastName']} <{$row['email']}>";
                        $firstNames[] = $row['FirstName'];
                        $lastNames[] = $row['LastName'];
                    }
                }
                $sentTo = implode('<br/>',$emailAddy);
                $subject = stripslashes($params['subject']);
                $message = stripslashes($params['message']);
                $userData = User::getById($user->id);
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $self = "{$userData['FirstName']} {$userData['LastName']} <{$userData['email']}>";
                $headers .= "From: $self\r\n";
                $teacherAddy = array();
                if ($params['self']!="none")
                {
                    $teacherAddy[] = $self;
                }
                if($emailAddy)
                {
                    foreach($emailAddy as $k=>$addy)
                    {
                        $addy = trim($addy);
                        if ($addy!='' && $addy!='none@none.com')
                        {
                            mail($addy,$subject,str_replace(array('LastName','FirstName'),array($lastNames[$k],$firstNames[$k]),$message),$headers);
                        }
                    }
                }
                $message = $params['message'] . addslashes("<p>Instructor note: Message sent to these students from course $course->name: <br/> $sentTo </p>\n");
                if ($params['self']!="allt")
                {
                    $query = Teacher::getDataForUtilities($courseId,$user);
                    if($query)
                    {
                        foreach($query as $row)
                        {
                            $teacherAddy[] = "{$row['FirstName']} {$row['LastName']} <{$row['email']}>";
                        }
                    }
                    $string = AppUtility::t('A copy was also emailed to all instructors for this course');
                    $message .= "<p>$string</p>\n";
                }
                foreach ($teacherAddy as $addy)
                {
                    mail($addy,$subject,$message,$headers);
                }

            }
            if ($calledFrom=='lu')
            {
                //LinkToListUser
            } else if ($calledFrom=='gb')
            {
                //LinkToGradeBook
            } else if ($calledFrom=='itemsearch')
            {
                return $this->redirect('item-search');
            }
        }
        else
        {
            $assessmentData = Assessments::getByCourse($courseId);
            $toList = $params['checked'];
            if($toList)
            {
                $detailsOfUser = User::insertDataFroGroups($toList);
            }
        }
        $this->includeCSS(['utilities.css']);
    $responseData = array('params' => $params,'assessmentData' => $assessmentData,'detailsOfUser' => $detailsOfUser,'calledFrom' => $calledFrom,'aid' => $aid,'id' => $id);
    return $this->renderWithData('massEnd',$responseData);
    }

    public function getStr($items,$str,$parent)
    {
        foreach ($items as $k=>$it)
        {
            if (is_array($it)) {
                if (stripos($it['name'],$str)!==false) {
                    return array($parent.'-'.($k+1), $it['name']);
                } else {
                    $val = $this->getStr($it['items'], $str, $parent.'-'.($k+1));
                    if (count($val)> AppConstant::NUMERIC_ZERO)
                    {
                        return $val;
                    }
                }
            }
        }
        return array();

    }
    public function fixSub($items)
    {
        global $itemsFnd;
        foreach($items as $k=>$item) {
            if ($item==null) {
                unset($items[$k]);
            } else if (is_array($item)) {
                if (!isset($item['items']) || !is_array($item['items'])) {
                    unset($items[$k]);
                } else if (count($item['items'])> AppConstant::NUMERIC_ZERO)
                {
                    $this->fixSub($items[$k]['items']);
                }
            } else {
                if ($item==null || $item=='') {
                    unset($items[$k]);
                } else {
                    $itemsFnd[] = $item;
                }
            }
        }
        $items = array_values($items);

    }
    public function doQuery($val)
    {
        $affectedRow = LibraryItems::updateWrongLibFlag($val);
        return $affectedRow;
    }
}