<?php

namespace app\controllers\instructor;

use app\components\AppConstant;
use app\components\AppUtility;
use app\components\CopyItemsUtility;
use app\models\CalItem;
use app\models\ContentTrack;
use app\models\Course;
use app\models\ForumPosts;
use app\models\ForumSubscriptions;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\GbItems;
use app\models\Grades;
use app\models\Groups;
use app\models\InstrFiles;
use app\models\LinkedText;
use app\models\Message;
use app\models\AssessmentSession;
use app\models\Assessments;
use app\models\Exceptions;
use app\models\Links;
use app\models\Forums;
use app\models\GbScheme;
use app\models\Items;
use app\models\Outcomes;
use app\models\Questions;
use app\models\QuestionSet;
use app\models\Sessions;
use app\models\Student;
use app\models\Stugroups;
use app\models\Teacher;
use app\models\InlineText;
use app\models\Thread;
use app\models\User;
use app\models\Wiki;
use app\models\forms\ManageEventForm;
use app\models\WikiRevision;
use app\models\WikiView;
use app\models\GbCats;
use Yii;
use app\controllers\AppController;
use yii\db\Exception;



class InstructorController extends AppController
{
    public $oa = array();
    public $cn = AppConstant::NUMERIC_ONE;
    public $key = AppConstant::NUMERIC_ZERO;
    public $enableCsrfValidation = false;
    public $shift;
    public $cnt = AppConstant::NUMERIC_ZERO;
    public $user = null;

    public function beforeAction($action)
    {
        $this->user = $this->getAuthenticatedUser();
        $actionPath = Yii::$app->controller->action->id;
        $courseId =  ($this->getParamVal('cid') || $this->getParamVal('courseId')) ? ($this->getParamVal('cid')?$this->getParamVal('cid'):$this->getParamVal('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForTeacher($this->user,$courseId, $actionPath);
    }

    /**
     * Display assessment details
     */
    public function actionShowAssessment()
    {
        $this->guestUserHandler();
        $id = $this->getParamVal('id');
        $courseId = $this->getParamVal('cid');
        $assessment = Assessments::getByAssessmentId($id);
        $assessmentSession = AssessmentSession::getAssessmentSession($this->getUserId(), $id);
        $questionRecords = Questions::getByAssessmentId($id);
        $questionSet = QuestionSet::getByQuesSetId($id);
        $course = Course::getById($courseId);
        $this->saveAssessmentSession($assessment, $id);
        $this->includeCSS(['mathtest.css', 'default.css', 'showAssessment.css']);
        $this->includeJS(['timer.js']);
        $returnData = array('cid'=> $course, 'assessments' => $assessment, 'questions' => $questionRecords, 'questionSets' => $questionSet,'assessmentSession' => $assessmentSession,'now' => time());
        return $this->render('ShowAssessment', $returnData);
    }

    public function saveAssessmentSession($assessment, $id)
    {
        list($qList, $seedList, $reviewSeedList, $scoreList, $attemptsList, $laList) = AppUtility::generateAssessmentData($assessment->itemorder, $assessment->shuffle, $assessment->id);
        $bestscorelist = $scoreList . ';' . $scoreList . ';' . $scoreList;
        $scoreList = $scoreList . ';' . $scoreList;
        $bestAttemptsList = $attemptsList;
        $bestSeedsList = $seedList;
        $bestLaList = $laList;
        $startTime = time();
        $defFeedbackText = ($assessment->deffeedbacktext);
        $ltiSourcedId = '';
        $param['questions'] = $qList;
        $param['seeds'] = $seedList;
        $param['userid'] = $id;
        $param['assessmentid'] = $id;
        $param['attempts'] = $attemptsList;
        $param['lastanswers'] = $laList;
        $param['reviewscores'] = $scoreList;
        $param['reviewseeds'] = $reviewSeedList;
        $param['bestscores'] = $bestscorelist;
        $param['scores'] = $scoreList;
        $param['bestattempts'] = $bestAttemptsList;
        $param['bestseeds'] = $bestSeedsList;
        $param['bestlastanswers'] = $bestLaList;
        $param['starttime'] = $startTime;
        $param['feedback'] = $defFeedbackText;
        $param['lti_sourcedid'] = $ltiSourcedId;
        $assessmentSession = new AssessmentSession();
        $assessmentSession->attributes = $param;
        $assessmentSession->save();
    }
    /**
     * To handle event on calendar.
     */

    /*
     * Manage Calendar Event
     */
    public function actionManageEvents()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->user;
        $eventData = $this->getRequestParams();
        $courseId = $eventData['cid'];
        $teacherId = Teacher::getByUserId($user->id, $courseId);
        if (!($teacherId)) {
//            echo AppConstant::UNAUTHORIZED_ACCESS;
//            exit;
        }
        if (isset($eventData['from']) && $eventData['from']=='cal') {
            $from = 'cal';
        } else {
            $from = 'indexPage';
        }
        if ($this->isPostMethod()) {
            /*
             * Delete Event
             */
            if (isset($eventData['delete']) && count($eventData['delete'])>AppConstant::NUMERIC_ZERO) {
                foreach ($eventData['delete'] as $id=>$val) {
                    if($val == AppConstant::NUMERIC_ONE){
                        CalItem::deleteByCourseId($id,$courseId);
                    }
                }
            }
            if (isset($eventData['tag']) && count($eventData['tag'])>AppConstant::NUMERIC_ZERO) {
                foreach ($eventData['tag'] as $id=>$tag) {
                    $date = $eventData['EventDate'.$id];
                    $title = $eventData['eventDetails'][$id];
                    $date = AppUtility::dateMatch($date);
                    CalItem::setEvent($date,$tag,$title,$id);
                }
            }
            /*
             * Add new Events
             */
            if (trim($eventData['ManageEventForm']['newTag'] != '!') || (trim($eventData['ManageEventForm']['newEventDetails'])!='')) {
                $date = $eventData['startDate'];
                $tag = $eventData['newTag'];
                $title = $eventData['ManageEventForm']['newEventDetails'];
                $newDate = AppUtility::dateMatch($date);
                $items = new CalItem();
                $items->createEvent($newDate,$tag,$title,$courseId);


            }
            if ($eventData['Submit']=='Save') {
                if ($from=='indexPage') {
                    return $this->redirect(AppUtility::getURLFromHome('course','course/calendar?cid='. $courseId));
                } else {
                    return $this->redirect(AppUtility::getURLFromHome('course','course/calendar?cid='. $courseId));
                }
            }else{
                return $this->redirect('manage-events?cid='. $courseId);
            }
        }
        $model = new ManageEventForm();
        $course = Course::getById($courseId);
        $eventItems = CalItem::getByCourse($courseId);
        $returnData = array('course' => $course, 'eventItems'=> $eventItems, 'model' => $model);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        return $this->renderWithData('manageEvent',$returnData);
    }

    public function deleteRecursive($itemArray) {
        foreach($itemArray as $itemId) {
            if (is_array($itemId)) {
                $this->deleteRecursive($itemId['items']);
            } else {
                $this->deleteItemById($itemId);
            }
        }
    }

    public function deleteItemById($itemId)
    {
        $ItemType =Items::getByTypeId($itemId);
        $user = $this->user;
        switch($ItemType['itemtype'])
        {
            case AppConstant::FORUM:
                Forums::deleteForum($itemId);
                ForumSubscriptions::deleteSubscriptionsEntry($itemId,$user['id']);
                $postId = ForumPosts::getForumPostByFile($itemId);
                $threadIdArray = ForumThread::findThreadCount($itemId);
                foreach($threadIdArray as $singleThread){
                    ForumView::deleteByForumIdThreadId($singleThread['id']);
                }
                ForumPosts::deleteForumPost($itemId);
                Thread::deleteThreadByForumId($itemId);
                break;
            case AppConstant::ASSESSMENT:
                AssessmentSession::deleteByAssessmentId($itemId);
                Questions::deleteByAssessmentId($itemId);
                Assessments::deleteAssessmentById($itemId);
                break;
            case AppConstant::CALENDAR:
                 Items::deleteByTypeIdName($itemId,$ItemType['itemtype']);
                break;
            case AppConstant::INLINE_TEXT:
                InlineText::deleteInlineTextId($itemId);
                InstrFiles::deleteById($itemId);
                break;
            case AppConstant::WIKI:
                Wiki::deleteById($itemId);
                WikiRevision::deleteByWikiId($itemId);
                WikiView::deleteByWikiId($itemId);
                break;
            case AppConstant::LINK:
                $linkData = Links::getById($itemId);
                $points = $linkData['points'];
                if($points > AppConstant::NUMERIC_ZERO){
                    Grades::deleteByGradeTypeId($itemId);
                }
                Links::deleteById($itemId);
                break;
        }
        Items::deleteByTypeIdName($itemId,$ItemType['itemtype']);
    }
//    /*
//     * Ajax method to copy course items
//     */
//    public function actionCopyItemsAjax()
//    {
//        $params = $this->getRequestParams();
//        $courseId = $params['courseId'];
//        $block = $params['block'];
//        $itemType = $params['itemType'];
//        $copyItemId = $params['copyid'];
//        if (isset($params['noappend'])) {
//            $params['append'] = "";
//        } else {
//            $params['append'] = AppConstant::COPY;
//        }
//        $params['ctc'] = $courseId;
//        $gradeBookCategory = array();
//        $gradeBookData =  GbCats::getByCourseId($courseId);
//        if ($gradeBookData){
//            foreach ($gradeBookData as $singleRecord){
//                $gradeBookCategory[$singleRecord['id']] = $singleRecord['id'];
//            }
//        }
//        global $outComes;
//        $outComes = array();
//        $outComesData = Outcomes::getByCourseId($courseId);
//        if ($outComesData){
//            foreach ($outComesData as $singleRecord){
//                $outComes[$singleRecord['id']] = $singleRecord['id'];
//            }
//        }
//        $courseData = Course::getById($courseId);
//        $blockCount = $courseData['blockcnt'];
//        $items = unserialize($courseData['itemorder']);
//        $connection = $this->getDatabase();
//        $transaction = $connection->beginTransaction();
//        try{
//            $notImportant = array();
//            $this->copyCourseItems($items, AppConstant::NUMERIC_ZERO, false, $notImportant, $copyItemId, $blockCount, $gradeBookCategory, $params);
//            CopyItemsUtility::copyrubrics();
//            $itemOrder = serialize($items);
//            Course::setBlockCount($itemOrder,$blockCount,$courseId);
//            $transaction->commit();
//        }catch (Exception $e){
//            $transaction->rollBack();
//            return false;
//        }
//        return $this->successResponse();
//    }
//
//    public function copyCourseItems(&$items, $parent, $copyInside, &$addToArray, $copyItemId, $blockCount, $gradeBookCategory, $params) {
//        foreach ($items as $k => $item) {
//            if (is_array($item)) {
//                if (($parent.'-'.($k+AppConstant::NUMERIC_ONE)==$copyItemId) || $copyInside) { //copy block
//                    $newBlock = array();
//                    $newBlock['name'] = $item['name'].stripslashes($params['append']);
//                    $newBlock['id'] = $blockCount;
//                    $blockCount++;
//                    $newBlock['startdate'] = $item['startdate'];
//                    $newBlock['enddate'] = $item['enddate'];
//                    $newBlock['avail'] = $item['avail'];
//                    $newBlock['SH'] = $item['SH'];
//                    $newBlock['colors'] = $item['colors'];
//                    $newBlock['fixedheight'] = $item['fixedheight'];
//                    $newBlock['grouplimit'] = $item['grouplimit'];
//                    $newBlock['items'] = array();
//                    if (count($item['items'])>AppConstant::NUMERIC_ZERO) {
//                        $this->copyCourseItems($items[$k]['items'], $parent.'-'.($k+AppConstant::NUMERIC_ONE), true, $newBlock['items'], $copyItemId, $blockCount, $gradeBookCategory, $params);
//                    }
//                    if (!$copyInside) {
//                        array_splice($items,$k+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,array($newBlock));
//                        return AppConstant::NUMERIC_ZERO;
//                    } else {
//                        $addToArray[] = $newBlock;
//                    }
//                } else {
//                    if (count($item['items'])>AppConstant::NUMERIC_ZERO) {
//                        $emptyArray = array();
//                        $this->copyCourseItems($items[$k]['items'],$parent.'-'.($k+AppConstant::NUMERIC_ONE),false,$emptyArray,$copyItemId,$blockCount,$gradeBookCategory,$params);
//                    }
//                }
//            } else {
//                if ($item==$copyItemId || $copyInside) {
//                    $newItem = CopyItemsUtility::copyitem($item,$gradeBookCategory,$params);
//                    if (!$copyInside) {
//                        array_splice($items,$k+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,intval($newItem));
//                        return AppConstant::NUMERIC_ZERO;
//                    } else {
//                        $addToArray[] = intval($newItem);
//                    }
//                }
//            }
//        }
//    }

    public function actionTimeShift()
    {
        $this->guestUserHandler();
        $user = $this->user;
        $this->layout = "master";
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $isTeacher = $this->isTeacher($user['id'],$courseId);
        //set some page specific variables and counters
        $overWriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
            $assessments = Assessments::getByCourseId($courseId);
        if ($isTeacher != AppConstant::NUMERIC_ONE) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to log in as a teacher to access this page";
        } else {    //PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

            if (isset($params['sdate'])) {
                $assessment = Assessments::getByAssessmentId($params['aid']);
                if (($params['base'] == AppConstant::NUMERIC_ZERO)) {
                    $basedate = $assessment['startdate'];
                } else {
                    $basedate = $assessment['enddate'];
                }
                preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/', $params['sdate'], $dmatches);
                $newstamp = mktime(date('G', $basedate), date('i', $basedate), AppConstant::NUMERIC_ZERO, $dmatches[1], $dmatches[2], $dmatches[3]);
                $this->shift = $newstamp - $basedate;
                $items = unserialize($course['itemorder']);
                $this->shiftsub($items);
                $itemorder = serialize($items);
                Course::setItemOrder($itemorder, $courseId);
                $itemsData = Items::getByCourseId($courseId);
                foreach ($itemsData as $item) {
                    if ($item['itemtype'] == "InlineText") {
                        InlineText::setStartDate($this->shift, $item['typeid']);
                        InlineText::setEndDate($this->shift, $item['typeid']);
                    } else if ($item['itemtype'] == "LinkedText") {
                        LinkedText::setStartDate($this->shift, $item['typeid']);
                        LinkedText::setEndDate($this->shift, $item['typeid']);
                    } else if ($item['itemtype'] == "Forum") {
                        Forums::setReplyBy($this->shift, $item['typeid']);
                        Forums::setPostBy($this->shift, $item['typeid']);
                    } else if ($item['itemtype'] == "Assessment") {
                        Assessments::setStartDate($this->shift, $item['typeid']);
                        Assessments::setEndDate($this->shift, $item['typeid']);
                    } else if ($item['itemtype'] == "Calendar") {
                        continue;
                    } else if ($item['itemtype'] == "Wiki") {
                        Wiki::setEditByDate($this->shift, $item['typeid']);
                    }
                    CalItem::setDateByCourseId($this->shift, $courseId);
                }
                $this->setSuccessFlash('Time shift data update successfully');
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' . $courseId));
            } else { //DEFAULT DATA MANIPULATION
                $sdate = AppUtility::tzdate("m/d/Y", time());
                $i = AppConstant::NUMERIC_ZERO;
                foreach ($assessments as $singleData) {
                    $page_assessmentList['val'][$i] = $singleData['id'];
                    $page_assessmentList['label'][$i] = $singleData['name'];
                    $i++;
                }
            }
        }
        $responseData = array('overWriteBody' => $overWriteBody,'body' => $body,'course' => $course, 'assessments' =>$assessments,'pageAssessmentList' => $page_assessmentList, 'date' => $sdate);
        return $this->renderWithData('timeShift', $responseData);
    }

    public function shiftsub($itema) {
        if($itema){
            foreach ($itema as $k=>$item) {
                if (is_array($item)) {
                    if ($itema[$k]['startdate'] > AppConstant::NUMERIC_ZERO) {
                        $itema[$k]['startdate'] += $this->shift;
                    }
                    if ($itema[$k]['enddate'] < AppConstant::ALWAYS_TIME) {
                        $itema[$k]['enddate'] += $this->shift;
                    }
                    $this->shiftsub($itema[$k]['items']);
                }
            }
        }
    }

    public function actionMassChangeDates()
    {
        $this->guestUserHandler();
        $user = $this->user;
        $this->layout = "master";
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($user['id'],$courseId);
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $names = Array();
        $startdates = Array();
        $enddates = Array();
        $reviewdates = Array();
        $ids = Array();
        $avails = array();
        $types = Array();
        $courseorder = Array();
        $pres = array();
        $body = "";
        $prefix = array();

        if(!(isset($teacherId))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to log in as a teacher to access this page";
        } else {

            if (isset($params['chgcnt'])) {
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemOrder = $courseItemOrder->itemorder;
                $items = unserialize($itemOrder);
                $cnt = $params['chgcnt'];
                $blockchg = AppConstant::NUMERIC_ZERO;

                for ($i=0; $i<$cnt; $i++) {
                    $data = explode(',',$params['data'.$i]);
                    if ($data[0] == '0') {
                        $startdate = AppConstant::NUMERIC_ZERO;
                    } else {
                        $pts = explode('~',$data[0]);
                        $startdate = AppUtility::parsedatetime($pts[0],$pts[1]);
                    }

                    if ($data[1] == '2000000000') {
                        $enddate = AppConstant::ALWAYS_TIME;
                    } else {
                        $pts = explode('~',$data[1]);
                        $enddate = AppUtility::parsedatetime($pts[0],$pts[1]);
                    }

                    if ($data[2] != 'NA') {
                        if ($data[2]=='A') {
                            $reviewdate = AppConstant::ALWAYS_TIME;
                        } else if ($data[2] == 'N') {
                            $reviewdate = AppConstant::NUMERIC_ZERO;
                        } else {
                            $pts = explode('~',$data[2]);
                            $reviewdate = AppUtility::parsedatetime($pts[0],$pts[1]);
                        }
                    }
                    $type = $data[3];
                    $id = $data[4];
                    $avail = intval($data[5]);

                    if ($type == 'Assessment') {
                        if ($id > 0) {
                            Assessments::updateAssessmentForMassChange($startdate, $enddate, $reviewdate, $avail, $id);
                        }
                    } else if ($type == 'Forum') {
                        if ($id > 0) {
                            Forums::updateForumMassChange($startdate, $enddate, $avail, $id);
                        }
                    } else if ($type == 'Wiki') {
                        if ($id > 0) {
                            Wiki::updateWikiById($startdate, $enddate, $avail, $id);
                        }
                    } else if ($type == 'InlineText') {
                        if ($id > 0) {
                            InlineText::updateInlineTextForMassChanges($startdate, $enddate, $avail, $id);
                        }
                    } else if ($type == 'Link') {
                        if ($id > 0) {
                            LinkedText::updateLinkForMassChanges($startdate, $enddate, $avail, $id);
                        }
                    } else if ($type == 'Block') {
                        $blocktree = explode('-',$id);
                        $sub =& $items;
                        if (count($blocktree) > 1) {
                            for ($j=1; $j < count($blocktree)-1; $j++) {
                                $sub =& $sub[$blocktree[$j]-1]['items']; //-1 to adjust for 1-indexing
                            }
                        }
                        $sub =& $sub[$blocktree[$j]-1];
                        $sub['startdate'] = $startdate;
                        $sub['enddate'] = $enddate;
                        $sub['avail'] = $avail;
                        $blockchg++;
                    }
                }
                if ($blockchg > 0) {
                    $itemorder = serialize($items);
                    $saveItemOrderIntoCourse = new Course();
                    $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);
                }
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' . $course->id));
            }
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

            if (isset($params['orderby'])) {
                $orderby = $params['orderby'];
                $sessiondata['mcdorderby'.$courseId] = $orderby;
                Sessions::setSessionId($sessionId,$sessiondata);
            } else if (isset($sessiondata['mcdorderby'.$courseId])) {
                $orderby = $sessiondata['mcdorderby'.$courseId];
            } else {
                $orderby = AppConstant::NUMERIC_THREE;
            }

            if (isset($params['filter'])) {
                $filter = $params['filter'];
                $sessiondata['mcdfilter'.$courseId] = $filter;
                Sessions::setSessionId($sessionId,$sessiondata);
            } else if (isset($sessiondata['mcdfilter'.$courseId])) {
                $filter = $sessiondata['mcdfilter'.$courseId];
            } else {
                $filter = "all";
            }
            if ($orderby == AppConstant::NUMERIC_THREE) {  //course page order
               global $itemsassoc;
               $itemsassoc = array();
                $itemData = Items::getByCourseId($courseId);
                foreach($itemData as $k => $item) {
                    $itemsassoc[$item['id']] = $item['itemtype'].$item['typeid'];
                }
                $result = Course::getById($courseId);
                $itemorder = $result['itemorder'];
                $itemorder = unserialize($itemorder);
                $itemsimporder = array();
                $item1 = $this->flattenitems($itemorder,$itemscourseorder,'0','');
                $itemscourseorder = array_flip($itemscourseorder);
            }

            if ($filter == 'all' || $filter == 'assessments') {
                $result = Assessments::getAssessmentForMassChange($courseId);
                foreach($result as $k => $row)
                {
                    $types[] = "Assessment";
                    $names[] = $row['name'];
                    $startdates[] = $row['startdate'];
                    $enddates[] = $row['enddate'];
                    $reviewdates[] = $row['reviewdate'];
                    $ids[] = $row['id'];
                    $avails[] = $row['avail'];
                    if (isset($prefix['Assessment'.$row['id']])) {$pres[] = $prefix['Assessment'.$row['id']];} else {$pres[] = '';}
                    if ($orderby == AppConstant::NUMERIC_THREE) {
                        $courseorder[] = $itemscourseorder['Assessment'.$row['id']];
                    }
                }
            }


            if ($filter == 'all' || $filter == 'inlinetext') {
                $result = InlineText::getInlineTextForMassChanges($courseId);
                foreach($result as $k => $row)
                {
                    $types[] = "InlineText";
                    $names[] = $row['title'];
                    $startdates[] = $row['startdate'];
                    $enddates[] = $row['enddate'];
                    $reviewdates[] = -1;
                    $ids[] = $row['id'];
                    $avails[] = $row['avail'];
                    if (isset($prefix['InlineText'.$row['id']])) {$pres[] = $prefix['InlineText'.$row['id']];} else {$pres[] = '';}
                    if ($orderby == AppConstant::NUMERIC_THREE) {$courseorder[] = $itemscourseorder['InlineText'.$row['id']];

                    }
                }
            }
            if ($filter == 'all' || $filter == 'linkedtext') {
                $result = LinkedText::getLinkTextForMassChanges($courseId);
                foreach($result as $k => $row)
                {
                    $types[] = "Link";
                    $names[] = $row['title'];
                    $startdates[] = $row['startdate'];
                    $enddates[] = $row['enddate'];
                    $reviewdates[] = -1;
                    $ids[] = $row['id'];
                    $avails[] = $row['avail'];
                    if (isset($prefix['LinkedText'.$row['id']])) {$pres[] = $prefix['LinkedText'.$row['id']];} else {$pres[] = '';}
                    if ($orderby == AppConstant::NUMERIC_THREE) {$courseorder[] = $itemscourseorder['LinkedText'.$row['id']];}
                }
            }
            if ($filter == 'all' || $filter == 'forums') {
                $result = Forums::getForumMassChanges($courseId);
                foreach($result as $k => $row)
                {
                    $types[] = "Forum";
                    $names[] = $row['name'];
                    $startdates[] = $row['startdate'];
                    $enddates[] = $row['enddate'];
                    $reviewdates[] = -1;
                    $ids[] = $row['id'];
                    $avails[] = $row['avail'];
                    if (isset($prefix['Forum'.$row['id']])) {$pres[] = $prefix['Forum'.$row['id']];} else {$pres[] = '';}
                    if ($orderby == AppConstant::NUMERIC_THREE) {$courseorder[] = $itemscourseorder['Forum'.$row['id']];
                    }
                }
            }
            if ($filter=='all' || $filter == 'wikis') {
                $result = Wiki::getWikiMassChanges($courseId);
                foreach($result as $k => $row)
                {
                    $types[] = "Wiki";
                    $names[] = $row['name'];
                    $startdates[] = $row['startdate'];
                    $enddates[] = $row['enddate'];
                    $reviewdates[] = -1;
                    $ids[] = $row['id'];
                    $avails[] = $row['avail'];
                    if (isset($prefix['Wiki'.$row['id']])) {$pres[] = $prefix['Wiki'.$row['id']];} else {$pres[] = '';}
                    if ($orderby == AppConstant::NUMERIC_THREE) {$courseorder[] = $itemscourseorder['Wiki'.$row['id']];}
                }
            }

            if ($filter=='all' || $filter=='blocks') {
                $result = Course::getItemOrder($courseId);
                $itemOrder = $result->itemorder;
                $items = unserialize($itemOrder);

               $blockItems = $this->getblockinfo($items,'0',$ids,$types,$names,$startdates,$enddates,$reviewdates,$itemscourseorder,$courseorder,$orderby,$avails,$pres,$prefix);

            }
            $names = $blockItems['name'];
            $ids = $blockItems['ids'];
            $types = $blockItems['types'];
            $startdates = $blockItems['startdates'];
            $enddates = $blockItems['enddates'];
            $reviewdates = $blockItems['reviewdates'];
            $itemscourseorder = $blockItems['itemscourseorder'];
            $courseorder = $blockItems['courseorder'];
            $orderby = $blockItems['orderby'];
            $avails = $blockItems['avails'];
            $pres = $blockItems['pres'];
            $prefix = $blockItems['prefix'];

            $cnt = AppConstant::NUMERIC_ZERO;
            $now = time();
            if ($orderby == AppConstant::NUMERIC_ZERO) {
                asort($startdates);
                $keys = array_keys($startdates);
            } else if ($orderby == AppConstant::NUMERIC_ONE) {
                asort($enddates);
                $keys = array_keys($enddates);
            } else if ($orderby == AppConstant::NUMERIC_TWO) {
                natcasesort($names);
                $keys = array_keys($names);
            } else if ($orderby == AppConstant::NUMERIC_THREE) {
                asort($courseorder);
                $keys = array_keys($courseorder);
            }

        }
        $this->includeCSS(['massChangeDates.css']);
        $this->includeJS(['massChangeDates.js', 'general.js']);
        $responseData = array('course' => $course, 'overwriteBody' => $overwriteBody, 'body' => $body, 'orderby' => $orderby, 'filter' => $filter, 'keys' => $keys, 'types' => $types, 'pres' => $pres, 'names' => $names, 'startdates' => $startdates, 'enddates' => $enddates, 'reviewdates' => $reviewdates, 'ids' => $ids, 'avails' => $avails, 'courseorder' => $courseorder, 'cnt' => $cnt);
        return $this->renderWithData('massChangeDates', $responseData);
    }

    public function getpts($sc) {
        if (strpos($sc,'~')===false) {
            if ($sc>AppConstant::NUMERIC_ZERO) {
                return $sc;
            } else {
                return AppConstant::NUMERIC_ZERO;
            }
        } else {
            $sc = explode('~',$sc);
            $tot = AppConstant::NUMERIC_ZERO;
            foreach ($sc as $s) {
                if ($s>AppConstant::NUMERIC_ZERO) {
                    $tot+=$s;
                }
            }
            return round($tot,AppConstant::NUMERIC_ONE);
        }
    }

    function flattenitems($items,&$addto,$parent,$pre) {
        global $itemsimporder,$itemsassoc,$prefix,$imasroot;
        foreach ($items as $k=>$item) {
            if (is_array($item)) {
                $addto[] = 'Block'.$parent.'-'.($k+1);
                $prefix['Block'.$parent.'-'.($k+1)] = $pre;
                $this->flattenitems($item['items'],$addto,$parent.'-'.($k+1),$pre.' ');
            } else {
                $addto[] = $itemsassoc[$item];
                $prefix[$itemsassoc[$item]] = $pre;
            }
        }
        return $items;
    }

    function getblockinfo($items,$parent,$ids,$types,$names,$startdates,$enddates,$reviewdates,$itemscourseorder,$courseorder,$orderby,$avails,$pres,$prefix) {
        foreach($items as $k=>$item) {
            if (is_array($item)) {
                $ids[] = $parent.'-'.($k+1);
                $types[] = "Block";
                if ($orderby == 3) {$courseorder[] = $itemscourseorder['Block'.$parent.'-'.($k+1)]; }
                $names[] = stripslashes($item['name']);
                $startdates[] = $item['startdate'];
                $enddates[] = $item['enddate'];
                $avails[] = $item['avail'];
                $reviewdates[] = -1;

                if (isset($prefix['Block'.$parent.'-'.($k+1)])) {$pres[] = $prefix['Block'.$parent.'-'.($k+1)];} else {$pres[] = '';}
                if (count($item['items'])>0) {
                    $this->getblockinfo($item['items'],$parent.'-'.($k+1),$ids,$types,$names,$startdates,$enddates,$reviewdates,$itemscourseorder,$courseorder,$orderby,$avails,$pres,$prefix);
                }
            }
        }
        return array('name' => $names, 'ids' => $ids, 'types' => $types, 'startdates' => $startdates, 'enddates' => $enddates, 'reviewdates' => $reviewdates, 'itemscourseorder' => $itemscourseorder, 'courseorder' => $courseorder, 'orderby' => $orderby, 'avails' => $avails, 'pres' => $pres, 'prefix' => $prefix);
    }

    public function actionCopyCourseItems()
    {
         $this->guestUserHandler();
         $courseId = $this->getParamVal('cid');
         $course = Course::getById($courseId);
         $user = $this->user;
        global $userid;
        $userid = $user['id'];
         $loadToOthers = $this->getParamVal('loadothers');
         $isMaster = $this->getParamVal('isMaster');
        if($isMaster == '')
        {
            $this->layout = 'master';
        }
        if(!$this->isTeacher($user->id,$courseId))
         {
             $overwriteBody = 1;
             $message = AppConstant::GROUP_MESSAGE;
         }
           else
         {
             $okToCopy = AppConstant::NUMERIC_ONE;
             $action = $this->getParamVal('action');
             $params = $this->getRequestParams();
             if(isset($params['cid']))
             {
                 $params['courseId'] =$params['cid'];
             }

             if(isset($action))
             {
                 $query  = Course::getCidForCopyingCourse($user->id,$params['ctc']);
                 if(!$query)
                 {
                     $query = Course::getEnrollKey($params['ctc']);
                     $copyRights = $query['copyrights']*AppConstant::NUMERIC_ONE;
                     if($copyRights < AppConstant::NUMERIC_TWO)
                     {
                         $okToCopy = AppConstant::NUMERIC_ZERO;
                         if($copyRights == AppConstant::NUMERIC_ONE)
                         {
                              $query = Course::getDataForCopyCourse($params['ctc']);
                              if($query)
                              {
                                  foreach($query as $data)
                                  {
                                      if($data['groupid'] == $user->groupid)
                                      {
                                          $okToCopy = AppConstant::NUMERIC_ONE;
                                          break;
                                      }
                                  }
                              }
                         }
                         if($okToCopy == AppConstant::NUMERIC_ZERO)
                         {
                             $eKey = $query['enrollkey'];
                             if (!isset($params['ekey']) || strtolower(trim($eKey)) != strtolower(trim($params['ekey'])))
                             {
                                 $overwriteBody = 1;
                                 $message = "Invalid enrollment key entered. <a href='copy-course-items?cid={$courseId}'>Try Again</a>";
                             }
                             else
                             {
                                 $okToCopy = AppConstant::NUMERIC_ONE;
                             }

                         }
                     }

                 }
             }
             if($okToCopy == AppConstant::NUMERIC_ONE)
             {
                 if(isset($action) && $action == "copycalitems")
                 {
                     if(isset($params['clearexisting']))
                     {
                         CalItem::deleteForCopyCourse($courseId);
                     }
                     if (isset($params['checked']) && count($params['checked']) > AppConstant::NUMERIC_ZERO)
                     {
                         $checked = $params['checked'];
                         $query = CalItem::getDataForCopyCourse($checked,$params['ctc']);
                         foreach($query as $data)
                         {
                             $calItem = new CalItem();
                             $calItem->InsertDataForCopy($courseId,$data);
                         }
                         return $this->redirect('index?cid='.$courseId);
                     }
                 }
                 elseif(isset($action) && $action == "copy")
                 {

                     if ($params['whattocopy']=='all')
                     {
                         $params['copycourseopt'] = AppConstant::NUMERIC_ONE;
                         $params['copygbsetup'] = AppConstant::NUMERIC_ONE;
                         $params['removewithdrawn'] = AppConstant::NUMERIC_ONE;
                         $params['usereplaceby'] = AppConstant::NUMERIC_ONE;
                         $params['copyrubrics'] = AppConstant::NUMERIC_ONE;
                         $params['copyoutcomes'] = AppConstant::NUMERIC_ONE;
                         $params['copystickyposts'] = AppConstant::NUMERIC_ONE;
                         $params['append'] = '';
                         if (isset($params['copyofflinewhole']))
                         {
                             $params['copyoffline'] = AppConstant::NUMERIC_ONE;
                         }
                         $params['addto'] = 'none';
                     }
                     $connection = $this->getDatabase();
                     $transaction = $connection->beginTransaction();
                     $gbCats = array();
                     try
                     {
                         if (isset($params['copycourseopt']))
                         {
                             $toCopy = 'ancestors,hideicons,allowunenroll,copyrights,msgset,topbar,cploc,picicons,chatset,showlatepass,theme,latepasshrs';
                             $query = Course::getDataByCtc($toCopy,$params['ctc']);
                             $toCopyArr = explode(',',$toCopy);
                             if($query['ancestors'])
                             {
                                 $query['ancestors'] = intval($params['ctc']);
                             }else
                             {
                                 $query['ancestors'] = intval($params['ctc']).','.$query['ancestors'];
                             }
                             $sets = '';
                             foreach ($toCopy as $copy)
                             {
                                 $sets[$copy]= $query[$copy];
                             }
                             Course::updateCourseForCopyCourse($courseId,$sets);
                         }
                         if (isset($params['copygbsetup']))
                         {
                             $query = GbScheme::getDataForCopyCourse($params['ctc']);
                             if($query)
                             {
                                 GbScheme::updateDataForCopyCourse($query,$courseId);
                             }
                             $query = GbCats::getDataForCopyCourse($params['ctc']);
                             if($query)
                             {
                                 foreach($query as $data)
                                 {
                                     $gbData= GbCats::getData($courseId,$data['name']);
                                     if(count($gbData) == AppConstant::NUMERIC_ZERO)
                                     {
                                         $frId = array_shift($data);
                                         $putValue = new GbCats();
                                         $insertId = $putValue->insertData($courseId,$data);
                                         $gbCats[$frId] = $insertId;
                                     }
                                     else
                                     {
                                         $rpId = $gbData[0]['id'];
                                         GbCats::updateData($rpId,$data);
                                         $gbCats[$data['id']] = $rpId;
                                     }
                                 }
                             }
                         }
                         else
                         {
                             $gbCats = array();
                             $gbData = GbCats::getDataByJoins($params['ctc'],$courseId);
                             if($gbData)
                             {
                                 foreach($gbData as $singleData)
                                 {
                                     $gbCats[$singleData['id']] = $singleData['id'];
                                 }
                             }

                         }
                         if(isset($params['copyoutcomes']))
                         {
                            //load any existing outcomes
                             global $outcomes,$outcomesArr;
                             $outcomesData = Outcomes::getDataByJoins($params['ctc'],$courseId);
                             if($outcomesData)
                             {
                                 $hasOutcomes = true;
                             }
                             else
                             {
                                 $hasOutcomes = false;
                             }
                             foreach($outcomesData as $singleOutcome)
                             {
                                 $outcomes[$singleOutcome['id']] = $singleOutcome['id'];
                             }
                             $newOutcomes = array();
                             $query = Outcomes::getDataForCopyCourse($params['ctc']);
                             foreach($query as $data)
                             {
                                 if (isset( $outcomes[$data['id']]))
                                 {
                                     continue;
                                 }
                                 if ($data['ancestors']=='') {
                                     $data['ancestors'] = $data['id'];
                                 } else {
                                     $data['ancestors'] = $data['id'].','.$data['ancestors'];
                                 }
                                 $putData = new Outcomes();
                                 $insertId =  $putData->insertDataForCopyCourse($data,$courseId);
                                 $outcomes[$data['id']] = $insertId;
                                 $newOutcomes[] = $outcomes[$data['id']];
                             }
                             if($hasOutcomes)
                             {
                                 $courseOutcomeData = Course::getByCourseIdOutcomes($courseId);
                                 $outcomesArr = unserialize($courseOutcomeData[0]['outcomes']);
                                 foreach ($newOutcomes as $o)
                                 {
                                     $outcomesArr[] = $o;
                                 }
                             }
                             else
                             {
                                 $courseOutcomeData = Course::getByCourseIdOutcomes($params['ctc']);
                                 $outcomesArr = unserialize($courseOutcomeData[0]['outcomes']);
                                 if($outcomesArr)
                                 {
                                   $this->updateOutcomes($outcomesArr);
                                 }
                             }
                             $newOutcomeArr = serialize($outcomesArr);
                             Course::updateOutcomes($newOutcomeArr,$courseId);
                         }
                         else
                         {
                             $outcomes = array();
                             $outcomesData = Outcomes::getDataByJoins($params['ctc'],$courseId);
                             if($outcomesData)
                             {
                                 foreach($outcomesData as $singleOutcome)
                                 {
                                     $outcomes[$singleOutcome['id']] = $singleOutcome['id'];
                                 }
                             }

                         }
                         if(isset($params['removewithdrawn']))
                         {
                             $removeWithDrawn = true;
                         }
                         if (isset($params['usereplaceby']))
                         {
                             $useReplaceBy = "all";
                             $questionData = QuestionSet::getDataForCopyCourse($params['ctc']);
                             if($questionData)
                             {
                                 foreach($questionData as $singleQuestion)
                                 {
                                     $replaceByArr[$singleQuestion['id']] = $singleQuestion['replaceby'];
                                 }
                             }
                         }
                         if (isset($params['checked']) || $params['whattocopy']=='all')
                         {
                             $checked = $params['checked'];
                             $query = Course::getBlockCnt($courseId);
                             $blockCnt = $query['blockcnt'];
                             $itemOrder = Course::getItemOrder($params['ctc']);
                             $items = unserialize($itemOrder['itemorder']);
                             global $newItems, $copyStickyPosts;
                             if (isset($params['copystickyposts']))
                             {
                                 $copyStickyPosts = true;
                             }
                             else
                             {
                                 $copyStickyPosts = false;
                             }
                             if ($params['whattocopy']=='all')
                             {
                                 $copy = new CopyItemsUtility();
                                 $copy->copyAllSub($items,'0',$newItems,$gbCats,false,$params,$blockCnt);
                             }
                             else
                             {
                                 $copy = new CopyItemsUtility();
                                 $copy ->copySub($items,'0',$newItems,$gbCats,false,$params,$checked,$blockCnt);
                             }
                             $doAfterCopy = new CopyItemsUtility();
                             $doAfterCopy->doaftercopy($params['ctc'],$courseId);
                             $itemOrder = Course::getItemOrder($courseId);
                             $items = unserialize($itemOrder['itemorder']);
                             if ($params['addto']=="none")
                             {
                                 array_splice($items,count($items),0,$newItems);

                             }
                             else
                             {
                                 $blockTree = explode('-',$_POST['addto']);
                                 $sub =& $items;
                                 for ($i=1;$i<count($blockTree);$i++) {
                                     $sub =& $sub[$blockTree[$i]-1]['items']; //-1 to adjust for 1-indexing
                                 }
                                 array_splice($sub,count($sub),0,$newItems);
                             }
                             $itemOrderData = serialize($items);
                             if ($itemOrderData !=' ')
                             {
                                 Course::updateItemOrder($itemOrderData,$courseId,$blockCnt);
                             }
                         }
                         global $offLineRubrics;
                         $offLineRubrics = array();
                         if (isset($params['copyoffline']))
                         {
                             $query = GbItems::getDataForCopyCourse($params['ctc']);
                             $insArr = array();
                             if($query)
                             {
                                 foreach($query as $data)
                                 {
                                     $rubric = array_pop($data);
                                     if (isset($gbCats[$data['gbcategory']]))
                                     {
                                         $data['gbcategory'] = $gbCats[$data['gbcategory']];
                                     } else {
                                         $data['gbcategory'] = AppConstant::NUMERIC_ZERO;
                                     }
                                     $insert = new GbItems();
                                     $insertId = $insert->insertData($courseId,$data,$rubric);
                                     if ($rubric>0)
                                     {
                                         $offLineRubrics[$insertId] = $rubric;
                                     }

                                 }
                             }
                         }
                         if (isset($params['copyrubrics']))
                         {
                             $CopyRubric = new CopyItemsUtility();
                             $CopyRubric->copyrubrics($offLineRubrics,$user->id,$user->groupid);
                         }
                       $transaction->commit();
                     }catch (Exception $e)
                     {
                        $transaction->rollBack();
                        return false;
                     }
                     if (isset($params['selectcalitems']))
                     {
                         $action ='selectcalitems';
                         $calItems = array();
                         $query = CalItem::getCalItemDetails($params['ctc']);
                         foreach($query as $row)
                         {
                             $calItems[] = $row;
                         }
                     }
                     else
                     {
                         return $this->redirect('index?cid='.$courseId);
                     }
                 }
                 elseif (isset($action) && $action == "select")
                 {

                     $query = Course::getPicIcons($params['ctc']);
                     $itemOrder = $query['itemorder'];
                     $PicIcons = $query['picicons'];
                     $items = unserialize($itemOrder);
                     global $ids, $types, $names, $sums, $parents, $gitypeids, $prespace, $CFG;
                     $getsubinfo = new CopyItemsUtility();
                     $getsubinfo ->getsubinfo($items,'0','',false,'');
                     $itemOrder = Course::getItemOrder($courseId);
                     $items = unserialize($itemOrder['itemorder']);
                     global $existBlocks;
                     $existBlock = new CopyItemsUtility();
                     $existBlocks = $existBlock->buildexistblocks($items,'0');
                     $i=AppConstant::NUMERIC_ZERO;
                     $page_blockSelect = array();
                     if($existBlocks)
                     {
                         foreach ($existBlocks as $k=>$name)
                         {
                             $page_blockSelect['val'][$i] = $k;
                             $page_blockSelect['label'][$i] = $name;
                             $i++;
                         }

                     }
                 }
                 else if (isset($loadToOthers))
                 {
                     $query  = Groups::getAllIdName();
                     if(count($query) > AppConstant::NUMERIC_ZERO)
                     {
                         $pageHasGroups=true;
                         $grpNames = array();
                         $grpNames[0] = "Default Group";
                         foreach($query as $group)
                         {
                             $grpNames[$group['id']] = $group['name'];
                         }
                     }
                     $courseGroupResults = Course::getDataByJoins($user->groupid,$user->id);
                 }
                 else
                 {
                     $query = Course::getFromJoinOnTeacher($user->id,$courseId);
                     $i=AppConstant::NUMERIC_ZERO;
                     $page_mineList = array();
                     foreach($query as $row)
                     {
                         $page_mineList['val'][$i] = $row['id'];
                         $page_mineList['label'][$i] = $row['name'];
                         $i++;
                     }
                     $courseTreeResult = Course::getFromJoinsData($user->groupid,$user->id);
                     $lastTeacher = AppConstant::NUMERIC_ZERO;
                     $courseTemplateResults = Course::getTemplate();
                     $groupTemplateResults  = Course::getGroupTemplate($user->groupid);

                 }

             }
         }
        $this->includeJS(['libtree.js']);
        $this->includeCSS(['question/libtree.css']);
        $responseData = ['course'=> $course,'isMaster' => $isMaster,'pageHasGroups' => $pageHasGroups,'overwriteBody' => $overwriteBody,'message' => $message,'loadToOthers' => $loadToOthers,'action' => $action,'params' => $params,'calItems' => $calItems,'PicIcons' => $PicIcons,'ids' => $ids,'$types' => $types,'parents' => $parents,'names' => $names,'sums' => $sums,
        'page_blockSelect' => $page_blockSelect,'courseGroupResults' => $courseGroupResults,'grpNames' => $grpNames,'page_mineList' => $page_mineList,'courseTreeResult' => $courseTreeResult,'lastTeacher' => $lastTeacher,
        'courseTemplateResults' => $courseTemplateResults,'groupTemplateResults' => $groupTemplateResults];
        return $this->render('copyCourseItems',$responseData);
    }

    public function updateOutcomes(&$outcomesArr)
    {
        global $outcomes;
        foreach ($outcomesArr as $k=>$v)
        {
            if (is_array($v))
            {
                $this->updateOutcomes($outcomesArr[$k]['outcomes']);
            } else {
                $outcomesArr[$k] = $outcomes[$v];
            }
        }
    }

    public function actionContentStats()
    {
        $this->guestUserHandler();
        $user = $this->user;
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $overWriteBody = false;
        $isTeacher = $this->isTeacher($user['id'], $courseId);
        $isTutor = $this->isTutor($user['id'], $courseId);

        if (!isset($isTeacher) && !isset($isTutor)) {
            $overWriteBody = true;
            $body = 'You do not have authorization to view this page';
        }

        $sType = $this->getParamVal('type');
        $typeId = intval($this->getParamVal('id'));

        if ($typeId == 0 || !in_array($sType,array('I','L','A','W','F')))
        {
            $overWriteBody = true;
            $body = 'Invalid request';
        } else {
            $data = array();
            $descrips = array();
            if ($sType == 'I')
            {
                $queryResult = ContentTrack::getDataByCourseId($courseId, $typeId);
                $q2 = InlineText::getByName($typeId);
            } else if ($sType == 'L') {
                $query = new ContentTrack();
                $queryResult = $query->getDataForLink($courseId,$typeId);
                $linkObj = new LinkedText();
                $q2 = $linkObj->getByName($typeId);
            } else if ($sType == 'A') {
                $query = new ContentTrack();
                $queryResult = $query->getDataForAssessment($courseId,$typeId);
                $assessmentQbj = new Assessments();
                $q2 = $assessmentQbj->getAssessmentName($typeId);
            } else if ($sType == 'W') {
                $query = new ContentTrack();
                $queryResult = $query->getDataForWiki($courseId,$typeId);
                $q2 = Wiki::getByName($typeId);
            } else if ($sType == 'F') {
                $query = new ContentTrack();
                $queryResult = $query->getDataForForum($courseId,$typeId);
                $q2 = Forums::getByName($typeId);
            }

            foreach($queryResult as $key => $row)
            {
                $type = $row['type'];
                if ($type=='linkedviacal')
                {
                    $type = 'linkedlink';
                }
                if ($sType == 'F' ||in_array($type,array('inlinetext','linkedsum','linkedintext','assessintro','assesssum','wikiintext')))
                {
                    $ident = $type.'::'.$row['info'];
                } else {
                    $ident = $type;
                }
                if (!isset($descrips[$ident]))
                {
                    if (in_array($type,array('inlinetext','linkedsum','linkedintext','assessintro','assesssum','wikiintext'))) {
                        $parts = explode('::',$row['info']);
                        if (count($parts)>1) {
                            $desc = 'In-item link to <a href="'.$parts[0].'">'.$parts[1].'</a>';
                        } else {
                            $desc = 'In-item link to '.$row['info'];
                        }
                    } else if (in_array($type,array('linkedlink','linkedviacal','wiki','assess'))) {
                        $desc = 'Link to item';
                    } else if ($type=='forumpost') {
                        $desc = 'Forum post';
                    } else if ($type=='forumreply') {
                        $desc = 'Forum reply';
                    }
                    $descrips[$ident] = $desc;
                }

                if (!isset($data[$ident])) {
                    $data[$ident] = array();
                }
                if (!isset($data[$ident][$row['userid']])) {
                    $data[$ident][$row['userid']] = 1;
                } else {
                    $data[$ident][$row['userid']]++;
                }
            }

            $row = $q2;
            $itemName = $row['name'];

            $stus = array();

            $result = User::getDataByCourseId($courseId);

            foreach($result as $key => $row)
            {
                $stus[$row['id']] = $row['LastName'].', '.$row['FirstName'];
            }
        }
        $responseData = array('overWriteBody' => $overWriteBody, 'itemName' => $itemName, 'body' => $body, 'descrips' => $descrips, 'stus' => $stus, 'data' => $data, 'course' => $course);
        return $this->renderWithData('contentStats', $responseData);
    }
}
