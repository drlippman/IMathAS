<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 14/7/15
 * Time: 8:40 PM
 */

namespace app\controllers\block;


use app\components\AppConstant;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\controllers\AppController;
use app\models\Course;
use app\models\Student;

class BlockController extends AppController
{

    public function actionAddBlock()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $blockData = unserialize($course['itemorder']);
        $toTb = $this->getParamVal('tb');
        $block = $this->getParamVal('block');
        $modify = $this->getParamVal('modify');
        if(isset($toTb))
        {
            $toTb = $toTb;
        }
        else{
            $toTb = 'b';
        }
        if(isset($modify))
        {
           $modifyId = $this->getParamVal('id');
           $blockTree = explode('-',$modifyId);
            $existingId = array_pop($blockTree) - AppConstant::NUMERIC_ONE;
            $blockItems = $blockData;
            if (count($blockTree)>1) {
                for ($i=1;$i<count($blockTree);$i++) {
                    $blockItems = $blockItems[$blockTree[$i]-1]['items']; //-1 to adjust for 1-indexing
                }
            }
            $title = stripslashes($blockItems[$existingId]['name']);
            $title = str_replace('"','&quot;',$title);
            $startDate = $blockItems[$existingId]['startdate'];
            $endDate = $blockItems[$existingId]['enddate'];
            if (isset($blockItems[$existingId]['avail'])) { //backwards compat
                $avail = $blockItems[$existingId]['avail'];
            } else {
                $avail = 1;
            }
            if (isset($blockItems[$existingId]['public'])) { //backwards compat
                $public = $blockItems[$existingId]['public'];
            } else {
                $public = 0;
            }
            $showHide = $blockItems[$existingId]['SH'][0];
            if (strlen($blockItems[$existingId]['SH'])==1) {
                $availBeh = 'O';
            } else {
                $availBeh = $blockItems[$existingId]['SH'][1];
            }
            $fixedHeight = $blockItems[$existingId]['fixedheight'];
            $groupLimit = $blockItems[$existingId]['grouplimit'];
            $saveTitle = _("Save Changes");
           $defaultBlockData = array
           (
                'title' =>  $title,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'availBeh' => $availBeh,
                'showHide' => $showHide,
                'avail' => $avail,
                'public' => $public,
                'fixedHeight' => $fixedHeight,
                'groupLimit' => $groupLimit,
               'saveTitle' =>'save Changes',
           );
        }
        else{
        $groupLimit = array();
        $defaultBlockData = array(
        'title' => 'Enter Block name here',
        'startDate' => time() + 60*60,
        'endDate' => time() + 7*24*60*60,
        'availBeh' => 'O',
        'showHide' => 'H',
        'avail' => 1,
        'public' => 0,
        'fixedHeight' => 0,
        'groupLimit' => $groupLimit,
         'saveTitle' =>'Create Block',
        );
        }
        $page_sectionListVal = array("none");
        $page_sectionListLabel = array("No restriction");
        $sectionQuery = Student::findDistinctSection($courseId);
        foreach($sectionQuery as $data)
        {
            $page_sectionListVal[] = 's-'.$data->section;
            $page_sectionListLabel[] = 'Section '.$data->section;
        }
        return $this->render('addBlock',['page_sectionListVal' => $page_sectionListVal,'page_sectionListLabel' =>$page_sectionListLabel,'defaultBlockData' =>$defaultBlockData,'courseId' => $courseId,'toTb' => $toTb,'block' => $block,'id' => $modifyId]);
    }

    public function actionCreateBlock()
    {
        $params = $this->getRequestParams();
        $course = Course::getById($params['courseId']);
        $blockCnt = $course['blockcnt'];
        $blockData = unserialize($course['itemorder']);
        if (isset($params['block']))
        {
            $blockTree = explode('-',$params['block']);
        }else
        {
            $blockTree = explode('-',$params['id']);
            $existingId = array_pop($blockTree) - 1;
        }
        $sub =& $blockData;
        if (count($blockTree)>1) {
            for ($i=1;$i<count($blockTree);$i++) {
                $sub =& $sub[$blockTree[$i]-1]['items'];
            }
        }
        $groupLimit = array();
        if ($params['grouplimit']!='none') {
            $groupLimit[] = $params['grouplimit'];
        }
        if(isset($params['public']))
        {
            $public = AppConstant::NUMERIC_ONE;
        }
        else{
            $public = AppConstant::NUMERIC_ZERO;
        }
        if(is_numeric($params['fixedheight']))
        {
            $fixedHeight = $params['fixedheight'];
        }
        else{
            $fixedHeight  = AppConstant::NUMERIC_ZERO;
        }
        if ($params['avail']==AppConstant::NUMERIC_ONE)
        {
            if ($params['available-after']=='0') {
                $startDate = AppConstant::NUMERIC_ZERO;
            } else if ($params['available-after']=='1') {
                $startDate = time()-AppConstant::NUMERIC_TWO;
            } else {
                $startDate = AssessmentUtility::parsedatetime($params['sdate'],$params['stime']);
            }
            if ($params['available-until']=='2000000000') {
                $endDate = 2000000000;
            } else {
                $endDate = AssessmentUtility::parsedatetime($params['edate'],$params['etime']);
            }
        }else
        {
            $startDate = AppConstant::NUMERIC_ZERO;
            $endDate = 2000000000;
        }

        if(isset($existingId))
        {

            $sub[$existingId]['name'] = htmlentities(stripslashes($params['title']));
            $sub[$existingId]['id'] = strval($blockCnt);
            $sub[$existingId]['startdate'] =  $startDate;
            $sub[$existingId]['enddate'] = $endDate;
            $sub[$existingId]['avail'] = $params['avail'];
            $sub[$existingId]['SH'] = $params['showhide'] . $params['availBeh'];
            $sub[$existingId]['colors'] = "";
            $sub[$existingId]['public'] = $public ;
            $sub[$existingId]['fixedheight'] = $fixedHeight;
            $sub[$existingId]['grouplimit'] = $groupLimit;

        }
        else{
            $blockItems = array();
            $blockItems['name'] = htmlentities(stripslashes($params['title']));
            $blockItems['id'] = strval($blockCnt);
            $blockItems['startdate'] =  $startDate;
            $blockItems['enddate'] = $endDate;
            $blockItems['avail'] = $params['avail'];
            $blockItems['SH'] = $params['showhide'] . $params['availBeh'];
            $blockItems['colors'] = "";
            $blockItems['public'] = $public ;
            $blockItems['fixedheight'] = $fixedHeight;
            $blockItems['grouplimit'] = $groupLimit;
            $blockItems['items'] = array();
            if ($params['toTb']=='b') {
                array_push($sub,($blockItems));
            } else if ($params['toTb']=='t') {
                array_unshift($sub,($blockItems));
            }
            $blockCnt++;
        }
        $finalBlockItems =(serialize($blockData));
        Course::UpdateItemOrder($finalBlockItems,$params['courseId'],$blockCnt);
        $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$params['courseId']));
    }

    public function actionNewFlag()
    {
        $this->guestUserHandler();
        $courseId = $this->getParam('cid');
        $course = Course::getById($courseId);
        $blockData = unserialize($course['itemorder']);
        $newFlag = $this->getParamVal('newflag');
        if(isset($newFlag)){
            $sub =& $blockData;
        }
        $blockTree = explode('-',$newFlag);
        if (count($blockTree)>1) {
            for ($i=1;$i<count($blockTree)-1;$i++) {
                $sub =& $sub[$blockTree[$i]-1]['items']; //-1 to adjust for 1-indexing
            }
        }
        $sub =& $sub[$blockTree[$i]-1];
        if (!isset($sub['newflag']) || $sub['newflag']==0) {
            $sub['newflag']=1;
        } else {

            $sub['newflag']=0;
        }
        $finalBlockItems =(serialize($blockData));
        Course::UpdateItemOrder($finalBlockItems,$courseId,$blockCnt=null);
        $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$courseId));

    }

}