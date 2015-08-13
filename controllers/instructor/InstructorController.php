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
use app\models\LinkedText;
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
use yii\db\Exception;



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
        $courseId = $this->getParamVal('cid');
        $isTeacher = $this->isTeacher($user['id'], $courseId);
        $isTutor = $this->isTutor($user['id'], $courseId);
        if ($isTeacher) {
            $canEdit = true;
            $viewAll = true;
        } else if ($isTutor) {
            $canEdit = false;
            $viewAll = true;
        } else {
            $canEdit = false;
            $viewAll = false;
        }
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $this->setSessionData('courseId',$courseId);
        $this->setSessionData('user',$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $this->layout = "master";
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
                                        $tempItem['assessment'] = $item;
                                        break;
                                    case 'Calendar':
                                        $tempItem[$item->itemtype] = $item;
                                        break;
                                    case 'Forum':
                                        $form = Forums::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $form;
                                        $tempItem['forum'] = $item;
                                        break;
                                    case 'Wiki':
                                        $wiki = Wiki::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $wiki;
                                        $tempItem['wiki'] = $item;
                                        break;
                                    case 'LinkedText':
                                        $linkedText = Links::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $linkedText;
                                        $tempItem['link'] = $item;
                                        break;
                                    case 'InlineText':
                                        $inlineText = InlineText::getById($item->typeid);
                                        $tempItem[$item->itemtype] = $inlineText;
                                        $tempItem['inline'] = $item;
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
                                $exceptionData = Exceptions::getByAssessmentId($item->typeid);
                                $exceptions = array();
                                foreach ($exceptionData as $data) {
                                    $exceptions[$data['userid']] = array($data['enddate'],$data['islatepass']);
                                }
                                $nothidden = true;
                                if ($assessment['reqscore']>0 && $assessment['reqscoreaid']>0 && !$viewAll && $assessment['enddate']>time()) {
                                    $bestScore = AssessmentSession::getAssessmentSession($user['id'], $assessment['reqscoreaid']);
                                    if (count($bestScore['bestscores']) == 0) {
                                        $nothidden = false;
                                    } else {
                                        $scores = explode(';', $bestScore['bestscores']);
                                        if (round($this->getpts($scores[0]), 1) + .02 < $assessment['reqscore']) {
                                            $nothidden = false;
                                        }
                                    }
                                }
                                $tempAray[$item->itemtype] = $assessment;
                                $tempAray['assessment'] = $item;
                                $tempAray['nothidden'] = $nothidden;
                                $tempAray['exceptions'] = $exceptions;
                                break;
                            case 'Calendar':
                                $tempAray[$item->itemtype] = $item;
                                break;
                            case 'Forum':
                                $form = Forums::getById($item->typeid);
                                $tempAray[$item->itemtype] = $form;
                                $tempAray['forum'] = $item;
                                break;
                            case 'Wiki':
                                $wiki = Wiki::getById($item->typeid);
                                $tempAray[$item->itemtype] = $wiki;
                                $tempAray['wiki'] = $item;
                                break;
                            case 'InlineText':
                                $inlineText = InlineText::getById($item->typeid);
                                $tempAray[$item->itemtype] = $inlineText;
                                $tempAray['inline'] = $item;
                                break;
                            case 'LinkedText':
                                $linkedText = Links::getById($item->typeid);
                                $tempAray[$item->itemtype] = $linkedText;
                                $tempAray['link'] = $item;
                                break;
                        }
                        array_push($responseData, $tempAray);
                    }
                }
        }else {
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
            $blockLoc = $blockTree[count($blockTree) - AppConstant::NUMERIC_ONE]-AppConstant::NUMERIC_ONE;
            if (strpos($toPosition ,'-')!==false) {
               if ($toPosition [0]=='O') { //out of block
                  $itemToMove = $curBlock[$fromPosition - AppConstant::NUMERIC_ONE];
                  array_splice($curBlock,$fromPosition - AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_ONE);
                  if (is_array($itemToMove)) {
                     array_splice($sub,$blockLoc+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,array($itemToMove));
                  } else {
                      array_splice($sub,$blockLoc+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,$itemToMove);
                  }
                } else { // in to block
                    $itemToMove = $curBlock[$fromPosition - AppConstant::NUMERIC_ONE];
                    array_splice($curBlock,$fromPosition - AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_ONE);
                    $toPosition  = substr($toPosition ,AppConstant::NUMERIC_TWO);
                    if ($fromPosition <$toPosition ) {
                        $adj=AppConstant::NUMERIC_ONE;
                    } else {
                        $adj=AppConstant::NUMERIC_ZERO;
                    }
                    array_push($curBlock[$toPosition - AppConstant::NUMERIC_ONE - $adj]['items'],$itemToMove);
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
        $returnData = array('calendarData' =>$calendarCount,'messageList' => $msgList,'courseDetail' => $responseData,
            'course' => $course, 'students' => $student, 'assessmentSession' => $assessmentSession,'canEdit'=> $canEdit, 'viewAll'=> $viewAll);
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
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $block = $params['block'];
        $itemType = $params['itemType'];
        $itemId = $params['id'];
        $connection = $this->getDatabase();
        $transaction = $connection->beginTransaction();
        try{
            switch($itemType){
                case AppConstant::FORUM:
                    $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                    AppUtility::UpdateitemOrdering($courseId,$block,$itemDeletedId);
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
                    AppUtility::UpdateitemOrdering($courseId,$block,$itemDeletedId);
                    break;
                case AppConstant::CALENDAR:
                    $itemDeletedId = Items::deletedCalendar($itemId,$itemType);
                    AppUtility::UpdateitemOrdering($courseId,$block,$itemDeletedId);
                    break;
                case AppConstant::INLINE_TEXT:
                    $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                    InlineText::deleteInlineTextId($itemId);
                    InstrFiles::deleteById($itemId);
                    AppUtility::UpdateitemOrdering($courseId,$block,$itemDeletedId);
                    break;
                case AppConstant::WIKI:
                    $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                    Wiki::deleteById($itemId);
                    WikiRevision::deleteByWikiId($itemId);
                    WikiView::deleteByWikiId($itemId);
                    AppUtility::UpdateitemOrdering($courseId,$block,$itemDeletedId);
                    break;
                case AppConstant::LINK:
                    $itemDeletedId = Items::deleteByTypeIdName($itemId,$itemType);
                    $linkData = Links::getById($itemId);
                    $points = $linkData['points'];
                    if($points > AppConstant::NUMERIC_ZERO){
                        Grades::deleteByGradeTypeId($itemId);
                    }
                    Links::deleteById($itemId);
                    AppUtility::UpdateitemOrdering($courseId,$block,$itemDeletedId);
                    break;
                case AppConstant::BLOCK:
                    $course = Course::getById($courseId);
                    $blockData = unserialize($course['itemorder']);
                    $blockTree = explode('-',$itemId);
                    $blockCnt='';
                    $blockId = array_pop($blockTree) - AppConstant::NUMERIC_ONE;
                    $sub =& $blockData;
                    if (count($blockTree)>AppConstant::NUMERIC_ONE)
                    {
                        for ($i=AppConstant::NUMERIC_ONE;$i<count($blockTree);$i++)
                        {
                            $sub =& $sub[$blockTree[$i]-AppConstant::NUMERIC_ONE]['items'];
                        }
                    }
                    if (is_array($sub[$blockId])) {
                        $blockItems = $sub[$blockId]['items'];
                        $obId = $sub[$blockId]['id'];

                        if (count($blockItems)>AppConstant::NUMERIC_ZERO)
                        {
                            $this->deleteRecursive($blockItems);
                            array_splice($sub,$blockId,AppConstant::NUMERIC_ONE);

                        }else
                        {
                                array_splice($sub,$blockId,AppConstant::NUMERIC_ONE);
                        }
                    }
                    $itemList =(serialize($blockData));
                    Course::setBlockCount($itemList,$blockCnt=null,$courseId);
            }
            $transaction->commit();
        }catch (Exception $e){
            $transaction->rollBack();
            return false;
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
        $connection = $this->getDatabase();
        $transaction = $connection->beginTransaction();
        try{
            $notImportant = array();
            $this->copyCourseItems($items, AppConstant::NUMERIC_ZERO, false, $notImportant, $copyItemId, $blockCount, $gradeBookCategory, $params);
            CopyItemsUtility::copyrubrics();
            $itemOrder = serialize($items);
            Course::setBlockCount($itemOrder,$blockCount,$courseId);
            $transaction->commit();
        }catch (Exception $e){
            $transaction->rollBack();
            return false;
        }
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

    public function actionTimeShift()
    {
        $this->guestUserHandler();
         $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessments = Assessments::getByCourseId($courseId);
            if (isset($params['sdate'])) {
                $assessment = Assessments::getByAssessmentId($params['aid']);
                if (($params['base'] == 0)) {
                    $basedate = $assessment['startdate'];
                } else {
                    $basedate = $assessment['enddate'];
                }
                preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/', $params['sdate'], $dmatches);
                $newstamp = mktime(date('G', $basedate), date('i', $basedate), 0, $dmatches[1], $dmatches[2], $dmatches[3]);
                $shift = $newstamp - $basedate;
                $items = unserialize($course['itemorder']);
                $this->shiftsub($items);
                $itemorder = serialize($items);
                Course::setItemOrder($itemorder, $courseId);
                $itemsData = Items::getByCourseId($courseId);
                foreach ($itemsData as $item) {
                    if ($item['itemtype'] == "InlineText") {
                        InlineText::setStartDate($shift, $item['typeid']);
                        InlineText::setEndDate($shift, $item['typeid']);
                    } else if ($item['itemtype'] == "LinkedText") {
                        LinkedText::setStartDate($shift, $item['typeid']);
                        LinkedText::setStartDate($shift, $item['typeid']);
                    } else if ($item['itemtype'] == "Forum") {
                        Forums::setStartDate($shift, $item['typeid']);
                        Forums::setEndDate($shift, $item['typeid']);
                    } else if ($item['itemtype'] == "Assessment") {
                        Assessments::setStartDate($shift, $item['typeid']);
                        Assessments::setEndDate($shift, $item['typeid']);
                    } else if ($item['itemtype'] == "Calendar") {
                        continue;
                    } else if ($item['itemtype'] == "Wiki") {
                        Wiki::setStartDate($shift, $item['typeid']);
                        Wiki::setEndDate($shift, $item['typeid']);
                    }
                    CalItem::setDateByCourseId($shift, $courseId);
                }
                return $this->redirect(AppUtility::getURLFromHome('instructor','instructor/index?cid='.$courseId));
            }else { //DEFAULT DATA MANIPULATION
            $sdate = AppUtility::tzdate("m/d/Y",time());
            $i=0;
            foreach($assessments as $singleData){
                $page_assessmentList['val'][$i] = $singleData['id'];
                $page_assessmentList['label'][$i] = $singleData['name'];
                $i++;
            }
        }
        $responseData = array('course' => $course, 'assessments' =>$assessments,'pageAssessmentList' => $page_assessmentList, 'date' => $sdate);
        return $this->renderWithData('timeShift', $responseData);
    }

    public function shiftsub($itema) {
        global $shift;
        if($itema){
            foreach ($itema as $k=>$item) {
                if (is_array($item)) {
                    if ($itema[$k]['startdate'] > AppConstant::NUMERIC_ZERO) {
                        $itema[$k]['startdate'] += $shift;
                    }
                    if ($itema[$k]['enddate'] < AppConstant::ALWAYS_TIME) {
                        $itema[$k]['enddate'] += $shift;
                    }
                    $this->shiftsub($itema[$k]['items']);
                }
            }
        }
    }

    public function actionMassChangeDates(){
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($user['id'],$courseId);
        $this->includeJS(['general.js']);
        $this->includeJS(['masschgdates.js']);
        $responseData = array('course' => $course);
        return $this->renderWithData('massChangeDates', $responseData);
    }

    public function getpts($sc) {
        if (strpos($sc,'~')===false) {
            if ($sc>0) {
                return $sc;
            } else {
                return 0;
            }
        } else {
            $sc = explode('~',$sc);
            $tot = 0;
            foreach ($sc as $s) {
                if ($s>0) {
                    $tot+=$s;
                }
            }
            return round($tot,1);
        }
    }
}

