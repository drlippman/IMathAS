<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 1/9/15
 * Time: 1:59 PM
 */

namespace app\controllers\utilities;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Assessments;
use app\models\Course;
use app\models\forms\LtiUserForm;
use app\models\InlineText;
use app\models\Items;
use app\models\LinkedText;
use app\models\QuestionSet;
use app\models\Student;
use app\models\User;
use \yii\web\Controller;
use app\controllers\AppController;

class UtilitiesController extends AppController
{
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
            $message = 'You are not authorized to view this page';
        }
        if (isset($removeLti))
        {
            $id = intval($this->getParamVal('removelti'));
            $query = "DELETE FROM imas_ltiusers WHERE id=$id";
            mysql_query($query) or die("Query failed : " . mysql_error());
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
                        $message = 'No results found';
                    }
                    else
                    {
                        foreach($queryForUser as $userData)
                        {
                            $queryForCourse = Course::queryForCourse($userData['id']);
                            $queryFromCourseForTutor = Course::queryFromCourseForTutor($userData['id']);
                            $queryFromCourseForTeacher = Course::queryFromCourseForTeacher($userData['id']);
                            $queryForLtiUser = LtiUserForm::getUserData($userData['id']);
                        }

                    }
                }

                }

        }
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
        }else
        {
                exit;
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
            $message = "You do not have access to this page";
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
            $message = 'You do not have access to this page';
        }
        $now = time();
        $start = $now - AppConstant::SECONDS_CONVERSION *AppConstant::NUMERIC_THIRTY;
        $end = $now;
        if(isset($start))
        {
            $parts = explode('-',$start);
            if (count($parts)==3)
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
            if (count($parts)==3)
            {
                $end = mktime(0,0,0,$parts[0],$parts[1],$parts[2]);
            }
        }
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
            $message = 'You do not have access to this page';
        }
        if(!empty($params['from']) && ($params['to']))
        {
            $from = trim($params['from']);
            $to = trim($params['to']);
            if (strlen($from)!=11 || strlen($to)!=11 || preg_match('/[^A-Za-z0-9_\-]/',$from) || preg_match('/[^A-Za-z0-9_\-]/',$to))
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
        return $this->renderWithData('replaceVideo',['updatedInlineText' => $updatedInlineText,'updatedLinkedText' => $updatedLinkedText,'updatedLinkedTextSummary' => $updatedLinkedTextSummary,'updatedAssessment' => $updatedAssessment,'updatedAssessmentSummary' => $updatedAssessmentSummary,'updatedQuestionSet' => $updatedQuestionSet,'params' => $params,'body' => $body,'message' => $message,'from' => $from,'to' => $to]);
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
                } else if (count($item['items'])>0) {
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

} 