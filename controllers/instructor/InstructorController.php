<?php

namespace app\controllers\instructor;

use app\components\AppConstant;
use app\components\AppUtility;
use app\components\CopyItemsUtility;
use app\models\CalItem;
use app\models\Course;
use app\models\ForumPosts;
use app\models\ForumSubscriptions;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\Grades;
use app\models\InstrFiles;
use app\models\Message;
use app\models\AssessmentSession;
use app\models\Blocks;
use app\models\Assessments;
use app\models\Exceptions;
use app\models\forms\CourseSettingForm;
use app\models\Links;
use app\models\Forums;
use app\models\GbScheme;
use app\models\Items;
use app\models\Outcomes;
use app\models\Questions;
use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\Teacher;
use app\models\InlineText;
use app\models\Thread;
use app\models\Wiki;
use app\models\User;
use app\models\forms\ManageEventForm;
use app\models\WikiRevision;
use app\models\WikiView;
use app\models\GbCats;
use Yii;
use app\controllers\AppController;


class InstructorController extends AppController
{

public $oa = array();
    public $cn = AppConstant::NUMERIC_ONE;
    public $key = AppConstant::NUMERIC_ZERO;
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $courseId = $this->getParamVal('cid');
        $type = $this->getParamVal('type');
        if($type){
            switch ($type) {
                case 'assessment':
                     return $this->redirect(AppUtility::getURLFromHome('assessment','assessment/add-assessment?cid='.$courseId));
                     break;
                case 'inlinetext':
                     return $this->redirect(AppUtility::getURLFromHome('course','course/modify-inline-text?courseId=' .$courseId));
                    break;
                case 'linkedtext':
                     return $this->redirect(AppUtility::getURLFromHome('forum','forum/add-link?cid='.$courseId));
                    break;
                case 'forum':
                     return $this->redirect(AppUtility::getURLFromHome('forum','forum/add-forum?cid='.$courseId));
                    break;
                case 'wiki':
                     return $this->redirect(AppUtility::getURLFromHome('wiki','wiki/add-wiki?courseId='.$courseId));
                    break;
                case 'block':
                     return $this->redirect(AppUtility::getURLFromHome('block','block/add-block?courseId='.$courseId.'&block=0&tb=t'));
                    break;
                case 'calendar':
                    break;
                case '':
                    break;
            }
        }
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $courseData = $this->getRequestParams();
        $teacherId = Teacher::getByUserId($user['id'], $courseData['cid']);
        if (!($teacherId)) {
            $this->setErrorFlash(AppConstant::UNAUTHORIZED_ACCESS);
            return $this->goBack();
        }
        $id = $this->getParamVal('id');
        $assessmentSession = AssessmentSession::getAssessmentSession($this->getUserId(), $id);
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $message = Message::getByCourseIdAndUserId($courseId, $user->id);
        $isreadArray = array(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR, AppConstant::NUMERIC_EIGHT, AppConstant::NUMERIC_TWELVE);
        $msgList = array();
        if($message){
            foreach($message as $singleMessage){
                if(in_array($singleMessage->isread, $isreadArray))
                array_push($msgList,$singleMessage);
            }
        }
        $responseData = array();
        $calendarCount = array();
        /**
         * Display Items
         */
        if ($course && ($itemOrders = unserialize($course->itemorder)) &&!isset($courseData['tb']) && !isset($courseData['remove'])) {
                foreach ($itemOrders as $key => $itemOrder) {
                    $tempAray = array();
                    if (is_array($itemOrder) || count($blockItems = $itemOrder['items']))
                    {
                        $tempAray['Block'] = $itemOrder;
                        $blockItems = $itemOrder['items'];
                        $tempItemList = array();
                            foreach ($blockItems as $blockKey => $blockItem) {
                                $tempItem = array();
                                $item = Items::getById($blockItem);
                                switch ($item->itemtype) {
                                    case 'Assessment':
                                        $assessment = Assessments::getByAssessmentId($item->typeid);
                                        $tempItem[$item->itemtype] = $assessment;
                                        array_push($calendarCount, $assessment);
                                        break;
                                    case 'Calendar':
                                        $tempItem[$item->itemtype] = $itemOrder;
                                        break;
                                    case 'Forum':
                                        $form = Forums::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $form;
                                        break;
                                    case 'Wiki':
                                        $wiki = Wiki::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $wiki;
                                        break;
                                    case 'LinkedText':
                                        $linkedText = Links::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $linkedText;
                                        break;
                                    case 'InlineText':
                                        $inlineText = InlineText::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $inlineText;
                                        break;
                                }
                                array_push($tempItemList, $tempItem);
                            }
                        $tempAray['itemList'] = $tempItemList;
                        array_push($responseData, $tempAray);
                    } else {
                        $item = Items::getById($itemOrder);
                        switch ($item->itemtype) {
                            case 'Assessment':
                                $assessment = Assessments::getByAssessmentId($item->typeid);
                                $tempAray[$item->itemtype] = $assessment;
                                array_push($responseData, $tempAray);
                                array_push($calendarCount, $assessment);
                                break;
                            case 'Calendar':
                                $tempAray[$item->itemtype] = $itemOrder;
                                array_push($responseData, $tempAray);
                                break;
                            case 'Forum':
                                $form = Forums::getById($item->typeid);
                                $tempAray[$item->itemtype] = $form;
                                array_push($responseData, $tempAray);
                                break;
                            case 'Wiki':
                                $wiki = Wiki::getById($item->typeid);
                                $tempAray[$item->itemtype] = $wiki;
                                array_push($responseData, $tempAray);
                                break;
                            case 'InlineText':
                                $inlineText = InlineText::getById($item->typeid);
                                $tempAray[$item->itemtype] = $inlineText;
                                array_push($responseData, $tempAray);
                                break;
                            case 'LinkedText':
                                $linkedText = Links::getById($item->typeid);
                                $tempAray[$item->itemtype] = $linkedText;
                                array_push($responseData, $tempAray);
                                break;
                        }
                    }
                }
        }else{
            if (isset($courseData['tb'])) {
                $filter = $courseData['tb'];
            } else {
                $filter = 'b';
            }
            /*
             *Create calendar
             */
            if(isset($courseData['block']) && isset($courseData['cid']) && !isset($courseData['from']) && !isset($courseData['remove'])){
                $block = $courseData['block'];
                $calender = 'Calendar';
                $itemCalender = new Items();
                $itemId = $itemCalender->create($courseId,$calender);
                $items = unserialize($course['itemorder']);
                $blockTree = explode('-',$block);
                $sub =& $items;
                for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree);$i++) {
                    $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items'];
                }
                if ($filter=='b') {
                    $sub[] = intval($itemId);
                } else if ($filter=='t') {
                    array_unshift($sub,intval($itemId));
                }
                $itemOrder = addslashes(serialize($items));
                Course::setItemOrder($itemOrder, $courseId);
                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id.'&folder=0'));
            }
        }
        /*
         *Ordering Items
         */
        if (isset($courseData['from']) && isset($courseData['to'])) {
            $fromPosition  = $courseData['from'];
            $toPosition  = $courseData['to'];
            $block = $courseData['block'];
            $output = Course::getById($courseId);
            $items = unserialize($output['itemorder']);
            $blockTree = explode('-',$block);
            $sub =& $items;
            for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree)-AppConstant::NUMERIC_ONE;$i++) {
                $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items'];
            }
            if (count($blockTree)>AppConstant::NUMERIC_ONE) {
               $curBlock =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items'];
               $blockLoc = $blockTree[$i]-AppConstant::NUMERIC_ONE;
            } else {
                $curBlock =& $sub;
            }
            $blockLoc = $blockTree[count($blockTree)-AppConstant::NUMERIC_ONE]-AppConstant::NUMERIC_ONE;
            if (strpos($toPosition ,'-')!==false) {
               if ($toPosition [0]=='O') {
                  $itemToMove = $curBlock[$fromPosition -AppConstant::NUMERIC_ONE];
                  array_splice($curBlock,$fromPosition -AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ONE);
                  if (is_array($itemToMove)) {
                     array_splice($sub,$blockLoc+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,array($itemToMove));
                  } else {
                      array_splice($sub,$blockLoc+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,$itemToMove);
                  }
                } else {
                    $itemToMove = $curBlock[$fromPosition -AppConstant::NUMERIC_ONE];
                    array_splice($curBlock,$fromPosition -AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ONE);
                    $toPosition  = substr($toPosition ,AppConstant::NUMERIC_ONE);
                    if ($fromPosition <$toPosition ) {
                        $adj=AppConstant::NUMERIC_ONE;
                    } else {
                        $adj=AppConstant::NUMERIC_ZERO;
                    }
                    array_push($curBlock[$toPosition -AppConstant::NUMERIC_ONE-$adj]['items'],$itemToMove);
                }
            } else {
                $itemToMove = $curBlock[$fromPosition -AppConstant::NUMERIC_ONE];
                array_splice($curBlock,$fromPosition -AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ONE);
                if (is_array($itemToMove)) {
                   array_splice($curBlock,$toPosition -AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,array($itemToMove));
                } else {
                    array_splice($curBlock,$toPosition -AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,$itemToMove);
                }
            }
            $itemList = addslashes(serialize($items));
            Course::setItemOrder($itemList,$courseId);
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
        }
        $student = Student::getByCId($courseId);
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css','_leftSide.css']);
        $this->includeJS(['moment.min.js','fullcalendar.min.js', 'student.js', 'latePass.js','course.js','course/instructor.js']);
        $returnData = array('calendarData' =>$calendarCount,'messageList' => $msgList,'courseDetail' => $responseData, 'course' => $course, 'students' => $student,'assessmentSession' => $assessmentSession);
        return $this->renderWithData('index', $returnData);
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
        $defFeedbackText = addslashes($assessment->deffeedbacktext);
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

    public function actionShowLinkedText()
    {
        $courseId = $this->getParamVal('cid');
        $id = Yii::$app->request->get('id');
        $course = Course::getById($courseId);
        $link = Links::getById($id);
        $returnData = array('course' => $course, 'links' => $link);
        return $this->renderWithData('showLinkedText', $returnData);
    }
    /**
     * To handle event on calendar.
     */
    public function actionGetAssessmentDataAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $assessments = Assessments::getByCourseId($courseId);
        $calendarItems = CalItem::getByCourseId($courseId);
        $calendarLinkItems = Links::getByCourseId($courseId);
        $calendarInlineTextItems = InlineText::getByCourseId($courseId);
        /**
         * Display assessment Modes:
         * - Normal assessment
         * - Review mode assessment
         */
        $assessmentArray = array();
        foreach ($assessments as $assessment)
        {
            $assessmentArray[] = array(
                'startDate' => AppUtility::getFormattedDate($assessment['startdate']),
                'endDate' => AppUtility::getFormattedDate($assessment['enddate']),
                'reviewDate' => AppUtility::getFormattedDate($assessment['reviewdate']),
                'name' => $assessment['name'],
                'startDateString' => $assessment['startdate'],
                'endDateString' => $assessment['enddate'],
                'reviewDateString' => $assessment['reviewdate'],
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'assessmentId' => $assessment['id'],
                'courseId' => $assessment['courseid']
            );
        }
        /**
         * Display managed events by admin.
         */
        $calendarArray = array();
        foreach ($calendarItems as $calendarItem)
        {
            $calendarArray[] = array(
                'courseId' => $calendarItem['courseid'],
                'date' => AppUtility::getFormattedDate($calendarItem['date']),
                'title' => $calendarItem['title'],
                'tag' => $calendarItem['tag']
            );
        }
        /**
         * Display link text: tags.
         */
        $calendarLinkArray = array();
        foreach ($calendarLinkItems as $calendarLinkItem)
        {
            $calendarLinkArray[] = array(
                'courseId' => $calendarLinkItem['courseid'],
                'title' => $calendarLinkItem['title'],
                'startDate' => AppUtility::getFormattedDate($calendarLinkItem['startdate']),
                'endDate' => AppUtility::getFormattedDate($calendarLinkItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $calendarLinkItem['startdate'],
                'endDateString' => $calendarLinkItem['enddate'],
                'linkedId' => $calendarLinkItem['id'],
                'calTag' => $calendarLinkItem['caltag']
            );
        }
        /**
         * Display inline text: tags.
         */
        $calendarInlineTextArray = array();
        foreach ($calendarInlineTextItems as $calendarInlineTextItem)
        {
            $calendarInlineTextArray[] = array(
                'courseId' => $calendarInlineTextItem['courseid'],
                'endDate' => AppUtility::getFormattedDate($calendarInlineTextItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $calendarInlineTextItem['startdate'],
                'endDateString' => $calendarInlineTextItem['enddate'],
                'calTag' => $calendarInlineTextItem['caltag']
            );
        }
        $responseData = array('assessmentArray' => $assessmentArray,'calendarArray' => $calendarArray, 'calendarLinkArray' => $calendarLinkArray, 'calendarInlineTextArray' => $calendarInlineTextArray);
        return $this->successResponse($responseData);
    }
/*
 * Manage Calendar Event
 */
    public function actionManageEvents()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $eventData = $this->getRequestParams();
        $courseId = $eventData['cid'];
        $teacherId = Teacher::getByUserId($user->id, $courseId);
        if (!($teacherId)) {
            echo AppConstant::UNAUTHORIZED_ACCESS;
            exit;
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
            if (trim($eventData['ManageEventForm']['newEventDetails'])!='' || $eventData['ManageEventForm']['newTag'] != '!') {
                $date = $eventData['startDate'];
                $tag = $eventData['ManageEventForm']['newTag'];
                $title = $eventData['ManageEventForm']['newEventDetails'];
                $newDate = AppUtility::dateMatch($date);
                $items = new CalItem();
                $items->createEvent($newDate,$tag,$title,$courseId);
            }
            if ($eventData['Submit']=='Save') {
                if ($from=='indexPage') {
                    return $this->redirect('index?cid='. $courseId);
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
        $this->includeCSS(['dataTables.bootstrap.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        return $this->renderWithData('manageEvent',$returnData);
    }

    public function actionDeleteItemsAjax()
    {
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $block = $params['block'];
        $itemType = $params['itemType'];
        $itemId = $params['id'];
        switch($itemType){
            case AppConstant::FORUM:
                $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                AppUtility::itemOrder($courseId,$block,$itemDeletedId);
                Forums::deleteForum($itemId);
                ForumSubscriptions::deleteSubscriptionsEntry($itemId);
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
                $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                Assessments::deleteAssessmentById($itemId);
                AppUtility::itemOrder($courseId,$block,$itemDeletedId);
                break;
            case AppConstant::CALENDAR:
                $itemDeletedId = Items::deletedCalendar($itemId,$itemType);
                AppUtility::itemOrder($courseId,$block,$itemDeletedId);
                break;
            case AppConstant::INLINE_TEXT:
                $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                InlineText::deleteInlineTextId($itemId);
                InstrFiles::deleteById($itemId);
                AppUtility::itemOrder($courseId,$block,$itemDeletedId);
                break;
            case AppConstant::WIKI:
                $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                Wiki::deleteById($itemId);
                WikiRevision::deleteByWikiId($itemId);
                WikiView::deleteByWikiId($itemId);
                AppUtility::itemOrder($courseId,$block,$itemDeletedId);
                break;
            case AppConstant::LINK:
                $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                $linkData = Links::getById($itemId);
                $points = $linkData['points'];
                if($points > AppConstant::NUMERIC_ZERO){
                    Grades::deleteByGradeTypeId($itemId);
                }
                Links::deleteById($itemId);
                AppUtility::itemOrder($courseId,$block,$itemDeletedId);
                break;
            case AppConstant::BLOCK:

        }
        return $this->successResponse();
    }
    public function actionCopyItemsAjax()
    {
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $block = $params['block'];
        $itemType = $params['itemType'];
        $tocopy = $params['copyid'];
        if (isset($params['noappend'])) {
            $params['append'] = "";
        } else {
            $params['append'] = " (Copy)";
        }
        $params['ctc'] = $courseId;
        $gradebookCategory = array();
        $gradeBookData =  GbCats::getByCourseId($courseId);
        if ($gradeBookData){
            foreach ($gradeBookData as $singleRecord){
                $gradebookCategory[$singleRecord['id']] = $singleRecord['id'];
            }
        }
        $outComes = array();
        $outComesData = Outcomes::getByCourseId($courseId);
        if ($outComesData){
            foreach ($outComesData as $singleRecord){
                $outComes[$singleRecord['id']] = $singleRecord['id'];
            }
        }
        $courseData = Course::getById($courseId);
        $blockCount = $courseData['blockcnt'];
        $items = unserialize($courseData['itemorder']);
        $notImportant = array();
        $this->copysubone($items,'0',false,$notImportant);
        CopyItemsUtility::copyrubrics();

        $itemOrder = addslashes(serialize($items));
        Course::setBlockCount($itemOrder,$blockCount,$courseId);
        return $this->successResponse();
    }

    public function copysubone(&$items,$parent,$copyinside,&$addtoarr) {
        global $blockcnt,$tocopy, $gbcats, $outcomes;
        foreach ($items as $k=>$item) {
            if (is_array($item)) {
                if (($parent.'-'.($k+1)==$tocopy) || $copyinside) { //copy block
                    $newblock = array();
                    $newblock['name'] = $item['name'].stripslashes($_POST['append']);
                    $newblock['id'] = $blockcnt;
                    $blockcnt++;
                    $newblock['startdate'] = $item['startdate'];
                    $newblock['enddate'] = $item['enddate'];
                    $newblock['avail'] = $item['avail'];
                    $newblock['SH'] = $item['SH'];
                    $newblock['colors'] = $item['colors'];
                    $newblock['fixedheight'] = $item['fixedheight'];
                    $newblock['grouplimit'] = $item['grouplimit'];
                    $newblock['items'] = array();
                    if (count($item['items'])>0) {
                        $this->copysubone($items[$k]['items'],$parent.'-'.($k+1),true,$newblock['items']);
                    }
                    if (!$copyinside) {
                        array_splice($items,$k+1,0,array($newblock));
                        return 0;
                    } else {
                        $addtoarr[] = $newblock;
                    }
                } else {
                    if (count($item['items'])>0) {
                        $nothin = array();
                        $this->copysubone($items[$k]['items'],$parent.'-'.($k+1),false,$nothin);
                    }
                }
            } else {
                if ($item==$tocopy || $copyinside) {
                    $newitem = CopyItemsUtility::copyitem($item,$gbcats);
                    if (!$copyinside) {
                        array_splice($items,$k+1,0,$newitem);
                        return 0;
                    } else {
                        $addtoarr[] = $newitem;
                    }
                }
            }
        }
    }
}







