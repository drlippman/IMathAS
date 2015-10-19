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
use app\models\Assessments;
use app\models\Bookmark;
use app\models\Course;
use app\models\Exceptions;
use app\models\Forums;
use app\models\Items;
use app\models\LinkedText;
use app\models\Student;
use app\models\Wiki;

class BlockController extends AppController
{
    public  $existblocks = array();
    public  $existblockids = array();
    public  $existBlocksVals = array();
    public  $existBlocksLabels = array();

    public function beforeAction($action)
    {
        $user = $this->getAuthenticatedUser();
        $courseId =  ($this->getParamVal('cid') || $this->getParamVal('courseId')) ? ($this->getParamVal('cid')?$this->getParamVal('cid'):$this->getParamVal('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForTeacher($user,$courseId);
    }

    public function actionAddBlock()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $courseName = $course['name'];
        $blockData = unserialize($course['itemorder']);
        $toTb = $this->getParamVal('tb');
        $block = $this->getParamVal('block');
        $modify = $this->getParamVal('modify');
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $this->noValidRights($teacherId);
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
                $startDate = AppUtility::parsedatetime($params['sdate'],$params['stime']);
            }
            if ($params['available-until']==AppConstant::AlWAYS_TIME_VALUE) {
                $endDate = AppConstant::ALWAYS_TIME;
            } else {
                $endDate = AppUtility::parsedatetime($params['edate'],$params['etime']);
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
        else
        {
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
    public function actionChangeBlock()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $userId = $user['id'];
        $teacherId = $this->isTeacher($userId,$courseId);
        $courseItemOrder = Course::getItemOrder($courseId);
        $itemOrder = $courseItemOrder->itemorder;
        $items = unserialize($itemOrder);
        if(!(isset($teacherId))){
            $overwriteBody = AppConstant::NUMERIC_ONE;
        }elseif(isset($params['checked'])){
            $checked = array();
            foreach ($params['checked'] as $id) {
                $id = intval($id);
                if ($id != AppConstant::NUMERIC_ZERO) {
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
        $responseData = array('course' => $course, 'items' => $items, 'existblocks' => $this->existblocks, 'existblockids' => $this->existblockids, 'page_sectionlistval' => $page_sectionlistval, 'page_sectionlistlabel' => $page_sectionlistlabel, 'overwriteBody' => $overwriteBody);
        return $this->renderWithData('changeBlock', $responseData);
    }

    public function buildExistBlocksArray($items,$parent) {
        foreach ($items as $k=>$item) {
            if (is_array($item)) {

                $this->existblocks[$parent.'-'.($k+1)] = $item['name'];
                $this->existblockids[$parent.'-'.($k+1)] = $item['id'];
                if (count($item['items'])> AppConstant::NUMERIC_ZERO) {
                    $this->buildExistBlocksArray($item['items'],$parent.'-'.($k+1));
                }
            }
        }
        $i= AppConstant::NUMERIC_ZERO;
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
                if (count($item['items'])> AppConstant::NUMERIC_ZERO) {
                    $this->updateBlocksArray($items[$n]['items'], $tochg, $sets);
                }
            }
        }
        return $items;
    }

    public function actionTreeReader()
    {
        global $courseId,$foundfirstitem, $foundopenitem, $openitem, $astatus, $studentinfo, $now, $viewall, $exceptions;
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
        $folder = 'TR'.$params['folder'];
        $astatus = array();
        $foundfirstitem = '';
        $foundopenitem = '';
        $now = time();
        $value = Bookmark::getValue($userId, $courseId, $folder);

        if(count($value) == AppConstant::NUMERIC_ZERO) {
            $openitem = '';
        } else{
            $openitem = $value['value'];
        }
        if(isset($teacherId) || isset($tutorId)){
           $viewall = true;
        } else{
            $viewall = false;
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
        if ($params['folder'] != '0')
        {
            $now = time() + $previewshift;
            $blocktree = explode('-',$params['folder']);
            $backtrack = array();
            for ($i = AppConstant::NUMERIC_ONE;$i<count($blocktree);$i++) {
                $backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
                if (!isset($teacherid) && !isset($tutorid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
                    $_GET['folder'] = AppConstant::NUMERIC_ZERO;
                    $items = unserialize($courseData['itemorder']);
                    unset($backtrack);
                    unset($blocktree);
                    break;
                }
                if (isset($items[$blocktree[$i]-1]['grouplimit']) && count($items[$blocktree[$i]-1]['grouplimit'])>0 && !isset($teacherid) && !isset($tutorid)) {
                    if (!in_array('s-'.$studentinfo['section'],$items[$blocktree[$i]-1]['grouplimit'])) {
                        echo 'Not authorized';
                        exit;
                    }
                }
                $items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
            }
        }

        if (!$viewall) {
            $assessment = Assessments::getCourseAndUserId($courseId, $userId);
            foreach ($assessment as $key => $row) {
                if (strpos($row['bestscores'],'-1') === false) {
                    $astatus[$row['id']] = AppConstant::NUMERIC_TWO; //completed
                } else { //at least some undone
                    $p = explode(',',$row['bestscores']);
                    foreach ($p as $v) {
                        if (strpos($v,'-1')===false) {
                            $astatus[$row['id']] = AppConstant::NUMERIC_ONE; //at least some is done
                            continue 2;
                        }
                    }
                    $astatus[$row['id']] = AppConstant::NUMERIC_ZERO; //unstarted
                }
            }
            $exceptions = array();
            if (!isset($teacherId) && !isset($tutorId)) {
                $query = Exceptions::getByUserIdForTreeReader($userId);
                foreach($query as $key => $line){
                    $exceptions[$line['id']] = array($line['startdate'],$line['enddate'],$line['islatepass']);
                }
            }
            //update block start/end dates to show blocks containing items with exceptions
            if (count($exceptions) > AppConstant::NUMERIC_ZERO) {
                $this->upsendexceptions($items);
            }
        }
        if (isset($backtrack) && count($backtrack) > AppConstant::NUMERIC_ZERO) {
            $blockName = $backtrack[count($backtrack)-1][0];

            if (count($backtrack) == AppConstant::NUMERIC_ONE) {
                $backlink =  "<span class=right><a href='".AppUtility::getURLFromHome('instructor', 'instructor/index?cid='.$courseId)."'>Back</a></span><br class=\"form\" />";
            } else {
                $backlink = "<span class=right><a href=".AppUtility::getURLFromHome('instructor', 'instructor/index?cid='.$courseId. '&folder='.$backtrack[count($backtrack)-2][1]).">Back</a></span><br class=\"form\" />";
            }
        } else {
            $blockName = $course->name;

        }
        $printList = $this->printlist($items);

        $this->includeCSS(['libtree.css', 'treeReader.css']);
        $this->includeJS(['general.js']);
        $responseData = array('course' => $course, 'printList' => $printList, 'openitem' => $openitem, 'foundfirstitem' => $foundfirstitem, 'foundopenitem' => $foundopenitem, 'item'=> $items, 'blockName' => $blockName, 'backlink' => $backlink);
        return $this->renderWithData('treeReader', $responseData);
    }

    function printlist($items) {
        global $courseId,$foundfirstitem, $foundopenitem, $openitem, $astatus, $studentinfo, $now, $viewall, $exceptions;
        $out = '';
        $isopen = false;
        foreach ($items as $item) {
            if (is_array($item)) { //is block
                $path = AppUtility::getHomeURL();
                //TODO check that it's available
                if ($viewall || $item['avail']== AppConstant::NUMERIC_TWO || ($item['avail']== AppConstant::NUMERIC_ONE && $item['startdate']<$now && $item['enddate']>$now)) {
                    list($subcontent,$bisopen) = $this->printlist($item['items']);
                    if ($bisopen) {
                        $isopen = true;
                    }
                    if ($bisopen) {
                        $out .=  "<li class=lihdr><span class=hdr onClick=\"toggle({$item['id']})\"><span class=btn id=\"b{$item['id']}\">-</span> <img class=too-small-icon src=\"$path/img/block.png\"> ";
                        $out .=  "{$item['name']}</span>\n";
                        $out .=  '<ul class="show nomark" id="'.$item['id'].'">';
                    } else {
                        $out .=  "<li class=lihdr><span class=hdr onClick=\"toggle({$item['id']})\"><span class=btn id=\"b{$item['id']}\">+</span> <img class=too-small-icon src=\"$path/img/block.png\"> ";
                        $out .=  "{$item['name']}</span>\n";
                        $out .=  '<ul class="hide nomark" id="'.$item['id'].'">';
                    }
                    $out .= $subcontent;
                    $out .=  '</ul></li>';
                }
            } else {
                $line = Items::getByItem($item);
                $typeid = $line['typeid'];
                $itemtype = $line['itemtype'];


                if ($line['itemtype']=='Assessment') {
                    //TODO check availability, timelimit, etc.
                    //TODO: reqscoreaid, latepasses
                    $line = Assessments::getByAssessmentId($typeid);
                    if (isset($exceptions[$item])) {
                        $line['startdate'] = $exceptions[$item][0];
                        $line['enddate'] = $exceptions[$item][1];
                    }
                    if ($viewall || ($line['avail']== AppConstant::NUMERIC_ONE && $line['startdate']<$now && ($line['enddate']>$now || $line['reviewdate']>$now))) {
                        if ($openitem=='' && $foundfirstitem=='') {
                            $foundfirstitem = 'assessment/assessment/show-assessment?cid='.$courseId.'&id='.$typeid; $isopen = true;
                        }
                        if ($itemtype.$typeid===$openitem) {
                            $foundopenitem = 'assessment/assessment/show-assessment?cid='.$courseId.'&id='.$typeid; $isopen = true;
                        }
                        $out .= '<li>';

                        if ($line['displaymethod']!='Embed') {
                            $out .=  '<img class=too-small-icon src="'.AppUtility::getHomeURL().'/img/iconAssessment.png"> ';
                        } else {
                            if (!isset($astatus[$typeid]) || $astatus[$typeid]== AppConstant::NUMERIC_ONE) {
                                $out .= '<img id="aimg'.$typeid.'" src="'.AppUtility::getHomeURL().'/img/q_fullbox.gif" /> ';
                            } else if ($astatus[$typeid]==1) {
                                $out .= '<img id="aimg'.$typeid.'" src="'.AppUtility::getHomeURL().'/img/q_halfbox.gif" /> ';
                            } else {
                                $out .= '<img id="aimg'.$typeid.'" src="'.AppUtility::getHomeURL().'/img/q_emptybox.gif" /> ';
                            }
                        }
                        if (isset($studentinfo['timelimitmult'])) {
                            $line['timelimit'] *= $studentinfo['timelimitmult'];
                        }
                        $line['timelimit'] = abs($line['timelimit']);

                        if ($line['timelimit']> AppConstant::NUMERIC_ONE) {

                            if ($line['timelimit'] > AppConstant::MINUTES) {

                                $tlhrs = floor($line['timelimit'] / AppConstant::MINUTES);
                                $tlrem = $line['timelimit'] % AppConstant::MINUTES;
                                $tlmin = floor($tlrem/AppConstant::SIXTY);
                                $tlsec = $tlrem % AppConstant::SIXTY;
                                $tlwrds = "$tlhrs " . _('hour');

                                if ($tlhrs > AppConstant::NUMERIC_ONE) { $tlwrds .= "s";}
                                if ($tlmin > AppConstant::NUMERIC_ZERO) { $tlwrds .= ", $tlmin " . _('minute');}
                                if ($tlmin > AppConstant::NUMERIC_ONE) { $tlwrds .= "s";}
                                if ($tlsec > AppConstant::NUMERIC_ZERO) { $tlwrds .= ", $tlsec " . _('second');}
                                if ($tlsec > AppConstant::NUMERIC_ONE) { $tlwrds .= "s";}
                            } else if ($line['timelimit'] > AppConstant::SIXTY) {
                                $tlmin = floor($line['timelimit']/ AppConstant::SIXTY);
                                $tlsec = $line['timelimit'] % AppConstant::SIXTY;
                                $tlwrds = "$tlmin " . _('minute');
                                if ($tlmin > AppConstant::NUMERIC_ONE) { $tlwrds .= "s";}
                                if ($tlsec > AppConstant::NUMERIC_ZERO) { $tlwrds .= ", $tlsec " . _('second');}
                                if ($tlsec > AppConstant::NUMERIC_ONE) { $tlwrds .= "s";}
                            } else {
                                $tlwrds = $line['timelimit'] . _(' second(s)');
                            }
                        } else {
                            $tlwrds = '';
                        }
                        if ($tlwrds != '') {
                            $onclick = 'onclick="return confirm(\''. sprintf(_('This assessment has a time limit of %s.  Click OK to start or continue working on the assessment.'), $tlwrds). '\')"';
                        } else {
                            $onclick = 'onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')"';
                        }
                        $out .= '<a href="'.AppUtility::getURLFromHome("assessment", "assessment/show-assessment?cid=".$courseId."&id=".$typeid).'"onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')"  target="readerframe">'.$line['name'].'</a></li>';
                    }
                } else if ($line['itemtype']=='LinkedText') {
                    //TODO check availability, etc.
                    $line = LinkedText::getById($typeid);
                    if ($viewall || $line['avail'] == AppConstant::NUMERIC_TWO || ($line['avail'] == AppConstant::NUMERIC_ONE && $line['startdate']< $now && $line['enddate'] > $now))
                    {
                        if ($openitem == '' && $foundfirstitem == '') {
                            $foundfirstitem = 'course/course/show-linked-text?cid='.$courseId.'&amp;id='.$typeid; $isopen = true;
                        }
                        if ($itemtype.$typeid===$openitem) {
                            $foundopenitem =  'course/course/show-linked-text?cid='.$courseId.'&amp;id='.$typeid; $isopen = true;
                        }
                        $out .=  '<li><img class=too-small-icon src="'.AppUtility::getHomeURL().'/img/link.png"> <a href="'.AppUtility::getURLFromHome('course', 'course/show-linked-text?cid='.$courseId.'&amp;id='.$typeid.'"').'onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')"  target="readerframe">'.$line['title'].'</a></li>';
                    }
                } else if ($line['itemtype']=='Wiki') {
                    //TODO check availability, etc.
                    $line = Wiki::getById($typeid);
                    if ($viewall || $line['avail']== AppConstant::NUMERIC_TWO || ($line['avail']== AppConstant::NUMERIC_ONE && $line['startdate']<$now && $line['enddate']>$now)) {
                        if ($openitem=='' && $foundfirstitem=='') {
                            $foundfirstitem = 'wiki/wikis/show-wiki?cid='.$courseId.'id='.$typeid.'&framed=true'; $isopen = true;
                        }
                        if ($itemtype.$typeid===$openitem) {
                            $foundopenitem = 'wiki/wikis/show-wiki?cid='.$courseId.'id='.$typeid.'&framed=true'; $isopen = true;
                        }
                        $out .=  '<li><img class=too-small-icon src="'.AppUtility::getHomeURL().'/img/iconWiki.png"> <a href="'.AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?cid='.$courseId.'id='.$typeid.'"').'onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
                    }
                } else if ($line['itemtype']=='Forum') {
				//TODO check availability.
				 $line = Forums::getById($typeid);
				 if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = 'forum/forums/thread?cid='.$courseId.'&id='.$typeid; $isopen = true;
				 }
				 if ($itemtype.$typeid===$openitem) {
				 	 $foundopenitem = 'forum/forums/thread.php?cid='.$courseId.'&id='.$typeid; $isopen = true;
				 }
				 $out .=  '<li><img class=too-small-icon src="'.AppUtility::getHomeURL().'/img/iconForum.png"> <a href="'.AppUtility::getURLFromHome('forum', 'forum/thread?cid='.$courseId.'&id='.$typeid.'"').'onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
			} else if ($line['itemtype']=="Calendar") {
                $out .=  '<li><img class=too-small-icon src="'.AppUtility::getHomeURL().'/img/iconCalendar.png">
                <a href="'.AppUtility::getURLFromHome('course', 'course/calendar?cid='.$courseId.'"'). 'target="readerframe">Calendar</a></li>';
                if ($openitem=='' && $foundfirstitem=='') {
                      $foundfirstitem = 'course/course/calendar.php?cid='.$courseId;
                      $isopen = true;
                }
            }
            }
        }
        return array($out,$isopen);
    }

   public function upsendexceptions(&$items) {
        global $exceptions;
        $minsdate = AppConstant::MIN_START_DATE;
        $maxedate = AppConstant::NUMERIC_ZERO;
        foreach ($items as $k=>$item) {
            if (is_array($item)) {
                $hasexc = $this->upsendexceptions($items[$k]['items']);
                if ($hasexc!=FALSE) {
                    if ($hasexc[0]<$items[$k]['startdate']) {
                        $items[$k]['startdate'] = $hasexc[0];
                    }
                    if ($hasexc[1]>$items[$k]['enddate']) {
                        $items[$k]['enddate'] = $hasexc[1];
                    }
                    if ($hasexc[0]<$minsdate) { $minsdate = $hasexc[0];}
                    if ($hasexc[1]>$maxedate) { $maxedate = $hasexc[1];}
                }
            } else {
                if (isset($exceptions[$item])) {
                    if ($exceptions[$item][0]<$minsdate) { $minsdate = $exceptions[$item][0];}
                    if ($exceptions[$item][1]>$maxedate) { $maxedate = $exceptions[$item][1];}
                }
            }
        }
        if ($minsdate < AppConstant::MIN_START_DATE || $maxedate > AppConstant::NUMERIC_ZERO) {
            return (array($minsdate,$maxedate));
        } else {
            return false;
        }
    }
}