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
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $this->userAuthentication($user,$courseId);
        $type = $this->getParamVal('type');
            switch ($type) {
                case 'assessment':
                     return $this->redirect(AppUtility::getURLFromHome('assessment','assessment/add-assessment?cid='.$courseId));
                     break;
                case 'inlinetext':
                     return $this->redirect(AppUtility::getURLFromHome('course','course/modify-inline-text?courseId=' .$courseId));
                    break;
                case 'linkedtext':
                     return $this->redirect(AppUtility::getURLFromHome('course','course/add-link?cid='.$courseId));
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
        $courseData = $this->getRequestParams();
        $teacherId = Teacher::getByUserId($user['id'], $courseData['cid']);
//        if (!($teacherId)) {
//            $this->setErrorFlash(AppConstant::UNAUTHORIZED_ACCESS);
//            return $this->goBack();
//        }
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
                                $tempAray['assessment'] = $item;
                                array_push($responseData, $tempAray);
                                break;
                            case 'Calendar':
                                $tempAray[$item->itemtype] = $itemOrder;
                                array_push($responseData, $tempAray);
                                break;
                            case 'Forum':
                                $form = Forums::getById($item->typeid);
                                $tempAray[$item->itemtype] = $form;
                                $tempAray['forum'] = $item;
                                array_push($responseData, $tempAray);
                                break;
                            case 'Wiki':
                                $wiki = Wiki::getById($item->typeid);
                                $tempAray[$item->itemtype] = $wiki;
                                $tempAray['wiki'] = $item;
                                array_push($responseData, $tempAray);
                                break;
                            case 'InlineText':
                                $inlineText = InlineText::getById($item->typeid);
                                $tempAray[$item->itemtype] = $inlineText;
                                $tempAray['inline'] = $item;
                                array_push($responseData, $tempAray);
                                break;
                            case 'LinkedText':
                                $linkedText = Links::getById($item->typeid);
                                $tempAray[$item->itemtype] = $linkedText;
                                $tempAray['link'] = $item;
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
                $itemOrder = serialize($items);
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
            $itemList = serialize($items);
            Course::setItemOrder($itemList,$courseId);
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
        }
        $student = Student::getByCId($courseId);
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css','_leftSide.css']);
        $this->includeJS(['moment.min.js','fullcalendar.min.js', 'student.js', 'latePass.js','course.js','course/instructor.js','course/addItem.js']);
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
        $currentDate = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
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
                'dueTime' => AppUtility::getFormattedTime($assessment['enddate']),
                'reviewDate' => AppUtility::getFormattedDate($assessment['reviewdate']),
                'name' => ucfirst($assessment['name']),
                'startDateString' => $assessment['startdate'],
                'endDateString' => $assessment['enddate'],
                'reviewDateString' => $assessment['reviewdate'],
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'assessmentId' => $assessment['id'],
                'courseId' => $assessment['courseid']
            );
        }

        $calendarArray = array();
        foreach ($calendarItems as $calendarItem)
        {
            $calendarArray[] = array(
                'courseId' => $calendarItem['courseid'],
                'date' => AppUtility::getFormattedDate($calendarItem['date']),
                'dueTime' => AppUtility::getFormattedTime($calendarItem['date']),
                'title' => ucfirst($calendarItem['title']),
                'tag' => ucfirst($calendarItem['tag'])
            );
        }
        $calendarLinkArray = array();
        foreach ($calendarLinkItems as $calendarLinkItem)
        {
            $calendarLinkArray[] = array(
       'courseId' => $calendarLinkItem['courseid'],
                'title' => ucfirst($calendarLinkItem['title']),
                'startDate' => AppUtility::getFormattedDate($calendarLinkItem['startdate']),
                'endDate' => AppUtility::getFormattedDate($calendarLinkItem['enddate']),
                'dueTime' => AppUtility::getFormattedTime($calendarLinkItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $calendarLinkItem['startdate'],
                'endDateString' => $calendarLinkItem['enddate'],
                'linkedId' => $calendarLinkItem['id'],
                'calTag' => ucfirst($calendarLinkItem['caltag'])
            );
        }
        $calendarInlineTextArray = array();
        foreach ($calendarInlineTextItems as $calendarInlineTextItem)
        {
            $calendarInlineTextArray[] = array(
                'courseId' => $calendarInlineTextItem['courseid'],
                'endDate' => AppUtility::getFormattedDate($calendarInlineTextItem['enddate']),
                'dueTime' => AppUtility::getFormattedTime($calendarInlineTextItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $calendarInlineTextItem['startdate'],
                'endDateString' => $calendarInlineTextItem['enddate'],
                'calTag' => ucfirst($calendarInlineTextItem['caltag'])
            );
        }
        $responseData = array('assessmentArray' => $assessmentArray,'calendarArray' => $calendarArray, 'calendarLinkArray' => $calendarLinkArray, 'calendarInlineTextArray' => $calendarInlineTextArray, 'currentDate' => $currentDate);
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
    /*
     * Ajax method to delete course items
     */
    public function actionDeleteItemsAjax()
    {
        $params = $this->getRequestParams();AppUtility::dump($params);
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
                $course = Course::getById($courseId);
                $blockData = unserialize($course['itemorder']);
                $blockTree = explode('-',$itemId);
                $blockCnt=" ";
                $blockId = array_pop($blockTree) - 1;
                $sub =& $blockData;
                if (count($blockTree)>1)
                {
                    for ($i=1;$i<count($blockTree);$i++)
                    {
                        $sub =& $sub[$blockTree[$i]-1]['items'];
                    }
                }
                if (is_array($sub[$blockId])) {
                    $blockItems = $sub[$blockId]['items'];
                    $obId = $sub[$blockId]['id'];

                    if (count($blockItems)>0)
                    {
                        $this->deleteRecursive($blockItems);
                        array_splice($sub,$blockId,1);

                    }else
                    {
                            array_splice($sub,$blockId,1);
                    }
                }
                $itemList =(serialize($blockData));
                Course::setBlockCount($itemList,$blockCnt=null,$courseId);
        }
        return $this->successResponse();
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
        switch($ItemType['itemtype'])
        {
            case AppConstant::FORUM:
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
    /*
     * Ajax method to copy course items
     */
    public function actionCopyItemsAjax()
    {
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $block = $params['block'];
        $itemType = $params['itemType'];
        $copyItemId = $params['copyid'];
        if (isset($params['noappend'])) {
            $params['append'] = "";
        } else {
            $params['append'] = AppConstant::COPY;
        }
        $params['ctc'] = $courseId;
        $gradeBookCategory = array();
        $gradeBookData =  GbCats::getByCourseId($courseId);
        if ($gradeBookData){
            foreach ($gradeBookData as $singleRecord){
                $gradeBookCategory[$singleRecord['id']] = $singleRecord['id'];
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
        $this->copyCourseItems($items, AppConstant::NUMERIC_ZERO, false, $notImportant, $copyItemId, $blockCount, $gradeBookCategory, $params);
        CopyItemsUtility::copyrubrics();
        $itemOrder = serialize($items);
        Course::setBlockCount($itemOrder,$blockCount,$courseId);
        return $this->successResponse();
    }

    public function copyCourseItems(&$items, $parent, $copyInside, &$addToArray, $copyItemId, $blockCount, $gradeBookCategory, $params) {
        foreach ($items as $k => $item) {
            if (is_array($item)) {
                if (($parent.'-'.($k+AppConstant::NUMERIC_ONE)==$copyItemId) || $copyInside) { //copy block
                    $newBlock = array();
                    $newBlock['name'] = $item['name'].stripslashes($params['append']);
                    $newBlock['id'] = $blockCount;
                    $blockCount++;
                    $newBlock['startdate'] = $item['startdate'];
                    $newBlock['enddate'] = $item['enddate'];
                    $newBlock['avail'] = $item['avail'];
                    $newBlock['SH'] = $item['SH'];
                    $newBlock['colors'] = $item['colors'];
                    $newBlock['fixedheight'] = $item['fixedheight'];
                    $newBlock['grouplimit'] = $item['grouplimit'];
                    $newBlock['items'] = array();
                    if (count($item['items'])>AppConstant::NUMERIC_ZERO) {
                        $this->copyCourseItems($items[$k]['items'], $parent.'-'.($k+AppConstant::NUMERIC_ONE), true, $newBlock['items'], $copyItemId, $blockCount, $gradeBookCategory, $params);
                    }
                    if (!$copyInside) {
                        array_splice($items,$k+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,array($newBlock));
                        return AppConstant::NUMERIC_ZERO;
                    } else {
                        $addToArray[] = $newBlock;
                    }
                } else {
                    if (count($item['items'])>AppConstant::NUMERIC_ZERO) {
                        $emptyArray = array();
                        $this->copyCourseItems($items[$k]['items'],$parent.'-'.($k+AppConstant::NUMERIC_ONE),false,$emptyArray,$copyItemId,$blockCount,$gradeBookCategory,$params);
                    }
                }
            } else {
                if ($item==$copyItemId || $copyInside) {
                    $newItem = CopyItemsUtility::copyitem($item,$gradeBookCategory,$params);
                    if (!$copyInside) {
                        array_splice($items,$k+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,intval($newItem));
                        return AppConstant::NUMERIC_ZERO;
                    } else {
                        $addToArray[] = intval($newItem);
                    }
                }
            }
        }
    }

}





