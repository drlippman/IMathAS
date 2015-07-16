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
        $toTb = $this->getParamVal('tb');
        $block = $this->getParamVal('block');
        if(isset($toTb))
        {
            $toTb = $toTb;
        }
        else{
            $toTb = 'b';
        }
        $groupLimit = array();

        $defaultBlockData = array(
        'title' => 'Enter Block name here',
        'startDate' => time() + 60*60,
        'endDate' => time() + 7*24*60*60,
        'availBeh' => 'O',
        'showHide' => 'H',
        'avail' => 1,
        'public' => 0,
        'useDef' => 1,
        'fixedHeight' => 0,
        'groupLimit' => $groupLimit,
        );
        $page_sectionListVal = array("none");
        $page_sectionListLabel = array("No restriction");
        $sectionQuery = Student::findDistinctSection($courseId);
        foreach($sectionQuery as $data)
        {
            $page_sectionListVal[] = 's-'.$data->section;
            $page_sectionListLabel[] = 'Section '.$data->section;
        }
        return $this->render('addBlock',['page_sectionListVal' => $page_sectionListVal,'page_sectionListLabel' =>$page_sectionListLabel,'defaultBlockData' =>$defaultBlockData,'courseId' => $courseId,'toTb' => $toTb,'block' => $block]);
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
        { //modifying existing
            $blockTree = explode('-',$params['id']);
            $existingid = array_pop($blocktree) - AppConstant::NUMERIC_ONE; //-1 adjust for 1-index
        }
        $sub =& $blockData;
        if (count($blockTree)>1) {
            for ($i=1;$i<count($blockTree);$i++) {
                $sub =& $sub[$blockTree[$i]-1]['items']; //-1 to adjust for 1-indexing
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
        $finalBlockItems =(serialize($blockData));
        Course::UpdateItemOrder($finalBlockItems,$blockCnt,$params['courseId']);
        $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$params['courseId']));
    }

}