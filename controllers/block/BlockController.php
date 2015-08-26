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
    public  $existblocks = array();
    public  $existblockids = array();
    public  $existBlocksVals = array();
    public  $existBlocksLabels = array();

    public function actionAddBlock()
    {
        $this->guestUserHandler();
        $this->getAuthenticatedUser();
        $this->layout = 'master';
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $courseName = $course['name'];
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
            if (count($blockTree)>AppConstant::NUMERIC_ONE) {
                for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree);$i++) {
                    $blockItems = $blockItems[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
                }
            }
            $title = stripslashes($blockItems[$existingId]['name']);
            $title = str_replace('"','&quot;',$title);
            $startDate = $blockItems[$existingId]['startdate'];
            $endDate = $blockItems[$existingId]['enddate'];
            if (isset($blockItems[$existingId]['avail'])) { //backwards compat
                $avail = $blockItems[$existingId]['avail'];
            } else {
                $avail = AppConstant::NUMERIC_ONE;
            }
            if (isset($blockItems[$existingId]['public'])) { //backwards compat
                $public = $blockItems[$existingId]['public'];
            } else {
                $public = AppConstant::NUMERIC_ZERO;
            }
            $showHide = $blockItems[$existingId]['SH'][0];
            if (strlen($blockItems[$existingId]['SH'])==AppConstant::NUMERIC_ONE) {
                $availBeh = 'O';
            } else {
                $availBeh = $blockItems[$existingId]['SH'][1];
            }
            $fixedHeight = $blockItems[$existingId]['fixedheight'];
            $groupLimit = $blockItems[$existingId]['grouplimit'];
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
               'saveTitle' => AppConstant::SAVE_BUTTON,
               'pageTitle' => AppConstant::MODIFY_BlOCK,
           );
        }
        else{
        $groupLimit = array();
        $defaultBlockData = array(
        'title' => 'Enter Block name here',
        'startDate' => time() + AppConstant::MINUTES,
        'endDate' => time() + AppConstant::WEEK_TIME,
        'availBeh' => 'O',
        'showHide' => 'H',
        'avail' => AppConstant::NUMERIC_ONE,
        'public' => AppConstant::NUMERIC_ZERO,
        'fixedHeight' => AppConstant::NUMERIC_ZERO,
        'groupLimit' => $groupLimit,
         'saveTitle' => AppConstant::CREATE_BLOCK,
         'pageTitle' => AppConstant::ADD_BLOCK,
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
        $this->includeCSS(['course/items.css']);
        return $this->render('addBlock',['page_sectionListVal' => $page_sectionListVal,'page_sectionListLabel' =>$page_sectionListLabel,'defaultBlockData' =>$defaultBlockData,'courseId' => $courseId,'toTb' => $toTb,'block' => $block,'id' => $modifyId,'courseName' => $courseName]);
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
        }else {
            $blockTree = explode('-',$params['id']);
            $existingId = array_pop($blockTree) - AppConstant::NUMERIC_ONE;
        }
        $sub =& $blockData;
        if (count($blockTree)>AppConstant::NUMERIC_ONE) {
            for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree);$i++) {
                $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items'];
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
            if ($params['available-after']==AppConstant::ZERO_VALUE) {
                $startDate = AppConstant::NUMERIC_ZERO;
            } else if ($params['available-after']==AppConstant::ONE_VALUE) {
                $startDate = time()-AppConstant::NUMERIC_TWO;
            } else {
                $startDate = AssessmentUtility::parsedatetime($params['sdate'],$params['stime']);
            }
            if ($params['available-until']==AppConstant::AlWAYS_TIME_VALUE) {
                $endDate = AppConstant::ALWAYS_TIME;
            } else {
                $endDate = AssessmentUtility::parsedatetime($params['edate'],$params['etime']);
            }
        }else
        {
            $startDate = AppConstant::NUMERIC_ZERO;
            $endDate = AppConstant::ALWAYS_TIME;
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
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $blockData = unserialize($course['itemorder']);
        $newFlag = $this->getParamVal('newflag');
        if(isset($newFlag)){
            $sub =& $blockData;
        }
        $blockTree = explode('-',$newFlag);
        if (count($blockTree)>AppConstant::NUMERIC_ONE) {
            for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree)-AppConstant::NUMERIC_ONE;$i++) {
                $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
            }
        }
        $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE];
        if (!isset($sub['newflag']) || $sub['newflag']==AppConstant::NUMERIC_ZERO) {
            $sub['newflag']=AppConstant::NUMERIC_ONE;
        } else {

            $sub['newflag']=AppConstant::NUMERIC_ZERO;
        }
        $finalBlockItems =(serialize($blockData));
        Course::UpdateItemOrder($finalBlockItems,$courseId,$blockCnt=null);
        $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$courseId));
    }
    public function actionEditContent()
    {
        $this->guestUserHandler();
        $previewShift = - AppConstant::NUMERIC_ONE;
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $blockData = unserialize($course['itemorder']);
        $folder = $this->getParamVal('Folder');
        if ($folder!=AppConstant::ZERO_VALUE) {
            $now = time() + $previewShift ;
            $blockTree = explode('-',$folder);
            $backtrack = array();
            for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree);$i++) {
                $backtrack[] = array($blockData[$blockTree[$i]-AppConstant::NUMERIC_ONE]['name'],implode('-',array_slice($blockTree,AppConstant::NUMERIC_ZERO,$i+AppConstant::NUMERIC_ONE)));
                if (!isset($teacherid) && !isset($tutorid) && $blockData[$blockTree[$i]-AppConstant::NUMERIC_ONE]['avail']<AppConstant::NUMERIC_TWO && $blockData[$blockTree[$i]-AppConstant::NUMERIC_ONE]['SH'][0]!='S' &&($now< $blockData[$blockTree[$i]-AppConstant::NUMERIC_ONE]['startdate'] || $now>$blockData[$blockTree[$i]-AppConstant::NUMERIC_ONE]['enddate'] || $blockData[$blockTree[$i]-AppConstant::NUMERIC_ONE]['avail']==AppConstant::ONE_VALUE)) {
                    $folder = AppConstant::NUMERIC_ZERO;
                    unset($backtrack);
                    unset($blockTree);
                    break;
                }
                $blockData = $blockData[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items'];
            }
        }
        $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$courseId));
    }

    /**
     * @return string
     * Mass changes: Block
     */
    public function actionChangeBlock(){
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $courseID = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $userId = $user['id'];
        $teacherId = $this->isTeacher($userId,$courseId);
        $courseItemOrder = Course::getItemOrder($courseId);
        $itemOrder = $courseItemOrder->itemorder;
        $items = unserialize($itemOrder);
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        if(!(isset($teacherId))){
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to log in as a teacher to access this page";
        }elseif(isset($params['checked'])){
            $checked = array();
            foreach ($params['checked'] as $id) {
                $id = intval($id);
                if ($id != 0) {
                    $checked[] = $id;
                }
            }
            $sets = array();
            if (isset($params['chgavail'])) {
                $sets['avail'] = intval($params['avail']);
            }
            if (isset($params['chgavailbeh'])) {
                $sets['SH'] = $params['showhide'] . $params['availbeh'];
            }
            if (isset($params['chggrouplimit'])) {
                $grouplimit = array();
                if ($params['grouplimit']!='none') {
                    $grouplimit[] = $params['grouplimit'];
                }
                $sets['grouplimit'] = $grouplimit;
            }
            $items = $this->updateBlocksArray($items,$checked,$sets);
            $itemorder = serialize($items);
            $saveItemOrderIntoCourse = new Course();
            $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id));
        }
        else
        {
            $parent = AppConstant::NUMERIC_ZERO;
            $this->buildExistBlocksArray($items, $parent);

            $page_sectionlistval = array("none");
            $page_sectionlistlabel = array(_("No restriction"));
            $distinctStudSection = new Student();
            $result = $distinctStudSection->findDistinctSection($courseId);
            foreach($result as $k=> $section){
                $page_sectionlistval[] = 's-'.$section['section'];
                $page_sectionlistlabel[] = 'Section '.$section['section'];
            }
        }
        $this->includeCSS(['course/items.css']);
        $this->includeJS(['general.js']);
        $responseData = array('course' => $course, 'items' => $items, 'existblocks' => $this->existblocks, 'existblockids' => $this->existblockids, 'page_sectionlistval' => $page_sectionlistval, 'page_sectionlistlabel' => $page_sectionlistlabel);
        return $this->renderWithData('changeBlock', $responseData);
    }

    public function buildExistBlocksArray($items,$parent) {
        foreach ($items as $k=>$item) {
            if (is_array($item)) {

                $this->existblocks[$parent.'-'.($k+1)] = $item['name'];
                $this->existblockids[$parent.'-'.($k+1)] = $item['id'];
                if (count($item['items'])>0) {
                    $this->buildExistBlocksArray($item['items'],$parent.'-'.($k+1));
                }
            }
        }
        $i=0;
        foreach ($this->existblocks as $k=>$name) {
            $existBlocksVals[$i] = $k;
            $existBlocksLabels[$i] = stripslashes($name);
            $i++;
        }
    }

    public function updateBlocksArray($items,$tochg,$sets) {
        foreach ($items as $n=>$item) {
            if (is_array($item)) {
                if (in_array($item['id'], $tochg)) {
                    foreach ($sets as $k=>$v) {
                        $items[$n][$k] = $v;
                    }
                }
                if (count($item['items'])>0) {
                    $this->updateBlocksArray($items[$n]['items'], $tochg, $sets);
                }
            }
        }
        return $items;
    }

    public function actionTreeReader()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $userId = $user['id'];
        $teacherId = $this->isTeacher($userId,$courseId);
        $tutorId = $this->isTutor($userId, $courseId);
        $previewshift = -1;

        if(isset($teacherId) || isset($tutorId)){
           $viewAll = true;
        } else{
            $viewAll = false;
        }
        $courseData = Course::getById($courseId);
        $items = unserialize($courseData['itemorder']);

        if ((!isset($params['folder']) || $params['folder'] == '') && !isset($sessiondata['folder'.$courseId])) {
            $params['folder'] = '0';
            $sessiondata['folder'.$courseId] = '0';
        } else if ((isset($params['folder']) && $params['folder'] != '') && (!isset($sessiondata['folder'.$courseId]) || $sessiondata['folder'.$courseId]!= $params['folder'])) {
            $sessiondata['folder'.$courseId] = $params['folder'];
        } else if ((!isset($params['folder']) || $params['folder']=='') && isset($sessiondata['folder'.$courseId])) {
            $params['folder'] = $sessiondata['folder'.$courseId];
        }

        $sessionId = $this->getSessionId();
        $sessiondata = $this->getSessionData($sessionId);
        $student = Student::getByCourseId($courseId, $userId);
        if($student != NULL){
            $studentinfo['section'] = $student['section'];
        }

        if ($params['folder'] != '0') {
            $now = time() + $previewshift;
            $blocktree = explode('-',$params['folder']);
            $backtrack = array();
            for ($i=1;$i<count($blocktree);$i++) {
                $backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
                if (!isset($teacherid) && !isset($tutorid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
                    $_GET['folder'] = 0;
                    $items = unserialize($courseData['itemorder']);
                    unset($backtrack);
                    unset($blocktree);
                    break;
                }
                if (isset($items[$blocktree[$i]-1]['grouplimit']) && count($items[$blocktree[$i]-1]['grouplimit'])>0 && !isset($teacherid) && !isset($tutorid)) {
                    if (!in_array('s-'.$studentinfo['section'],$items[$blocktree[$i]-1]['grouplimit'])) {
                    }
                }
                $items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
            }
        }

        $responseData = array('course' => $course);
        return $this->renderWithData('treeReader', $responseData);
    }
}