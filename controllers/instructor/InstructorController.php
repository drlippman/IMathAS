<?php

namespace app\controllers\instructor;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\CalItem;
use app\models\Course;
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
use app\models\Questions;
use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\Teacher;
use app\models\InlineText;
use app\models\Wiki;
use app\models\User;
use app\models\forms\ManageEventForm;
use Yii;
use app\controllers\AppController;


class InstructorController extends AppController
{

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
                     return $this->redirect(AppUtility::getURLFromHome('site','work-in-progress?cid='.$courseId));
                    break;
                case 'forum':
                     return $this->redirect(AppUtility::getURLFromHome('site','work-in-progress?cid='.$courseId));
                    break;
                case 'wiki':
                     return $this->redirect(AppUtility::getURLFromHome('site','work-in-progress?cid='.$courseId));
                    break;
                case 'block':
                     return $this->redirect(AppUtility::getURLFromHome('site','work-in-progress?cid='.$courseId));
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
        $isreadArray = array(0, 4, 8, 12);
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
        if ($course && !isset($courseData['tb']) && !isset($courseData['remove'])) {
            $itemOrders = unserialize($course->itemorder);
            if ($itemOrders) {
                foreach ($itemOrders as $key => $itemOrder) {
                    $tempAray = array();
                    if (is_array($itemOrder)) {
                        $tempAray['Block'] = $itemOrder;
                        $blockItems = $itemOrder['items'];
                        $tempItemList = array();
                        if (count($blockItems)) {
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

//                                $a = AppUtility::getFormattedDate($wiki['enddate'],'m/d/y');
//                                AppUtility::dump($a);
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
                    for ($i=1;$i<count($blockTree);$i++) {
                        $sub =& $sub[$blockTree[$i]-1]['items'];
                    }
                    if ($filter=='b') {
                        $sub[] = $itemId;
                    } else if ($filter=='t') {
                        array_unshift($sub,intval($itemId));
                    }
                    $itemOrder = addslashes(serialize($items));
                    Course::setItemOrder($itemOrder, $courseId);
                    return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id.'&folder=0'));
            }
            /*
             *Delete calendar
             */
            elseif(isset($courseData['remove'])){
                $block = $courseData['block'];
                $itemId = $courseData['id'];
                Items::deletedItems($itemId);
                $items = unserialize($course['itemorder']);
                $blockTree = explode('-',$block);
                $sub =& $items;
                for ($i=1;$i<count($blockTree);$i++) {
                    $sub =& $sub[$blockTree[$i]-1]['items'];
                }
                $key = array_search($itemId,$sub);
                array_splice($sub,$key,1);
                $itemOrder = addslashes(serialize($items));
                Course::setItemOrder($itemOrder,$courseId);
                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
            }else{
                $this->setErrorFlash(AppConstant::WRONG_OPTION);
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
            for ($i=1;$i<count($blockTree)-1;$i++) {
                $sub =& $sub[$blockTree[$i]-1]['items'];
            }
            if (count($blockTree)>1) {
               $curBlock =& $sub[$blockTree[$i]-1]['items'];
               $blockLoc = $blockTree[$i]-1;
            } else {
                $curBlock =& $sub;
            }
            $blockLoc = $blockTree[count($blockTree)-1]-1;
            if (strpos($toPosition ,'-')!==false) {
               if ($toPosition [0]=='O') {
                  $itemToMove = $curBlock[$fromPosition -1];
                  array_splice($curBlock,$fromPosition -1,1);
                  if (is_array($itemToMove)) {
                     array_splice($sub,$blockLoc+1,0,array($itemToMove));
                  } else {
                      array_splice($sub,$blockLoc+1,0,$itemToMove);
                  }
                } else {
                    $itemToMove = $curBlock[$fromPosition -1];
                    array_splice($curBlock,$fromPosition -1,1);
                    $toPosition  = substr($toPosition ,2);
                    if ($fromPosition <$toPosition ) {$adj=1;} else {$adj=0;}
                    array_push($curBlock[$toPosition -1-$adj]['items'],$itemToMove);
                }
            } else {
                $itemToMove = $curBlock[$fromPosition -1];
                array_splice($curBlock,$fromPosition -1,1);
                if (is_array($itemToMove)) {
                   array_splice($curBlock,$toPosition -1,0,array($itemToMove));
                } else {
                    array_splice($curBlock,$toPosition -1,0,$itemToMove);
                }
            }
            $itemList = addslashes(serialize($items));
            Course::setItemOrder($itemList,$courseId);
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
        }
        $student = Student::getByCId($courseId);
//        AppUtility::dump($user);
        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css','_leftSide.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js', 'latePass.js','course.js','course/instructor.js']);
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
        list($qlist, $seedlist, $reviewseedlist, $scorelist, $attemptslist, $lalist) = AppUtility::generateAssessmentData($assessment->itemorder, $assessment->shuffle, $assessment->id);

        $bestscorelist = $scorelist . ';' . $scorelist . ';' . $scorelist;
        $scorelist = $scorelist . ';' . $scorelist;
        $bestattemptslist = $attemptslist;
        $bestseedslist = $seedlist;
        $bestlalist = $lalist;
        $starttime = time();
        $deffeedbacktext = addslashes($assessment->deffeedbacktext);
        $ltisourcedid = '';
        $param['questions'] = $qlist;
        $param['seeds'] = $seedlist;
        $param['userid'] = $id;
        $param['assessmentid'] = $id;
        $param['attempts'] = $attemptslist;
        $param['lastanswers'] = $lalist;
        $param['reviewscores'] = $scorelist;
        $param['reviewseeds'] = $reviewseedlist;
        $param['bestscores'] = $bestscorelist;
        $param['scores'] = $scorelist;
        $param['bestattempts'] = $bestattemptslist;
        $param['bestseeds'] = $bestseedslist;
        $param['bestlastanswers'] = $bestlalist;
        $param['starttime'] = $starttime;
        $param['feedback'] = $deffeedbacktext;
        $param['lti_sourcedid'] = $ltisourcedid;

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
        $CalendarLinkItems = Links::getByCourseId($courseId);
        $calendarInlineTextItems = InlineText::getByCourseId($courseId);

        /**
         * Display assessment Modes:
         *                         - Normal assessment
         *                         - Review mode assessment
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
        foreach ($CalendarLinkItems as $CalendarLinkItem)
        {
            $calendarLinkArray[] = array(
                'courseId' => $CalendarLinkItem['courseid'],
                'title' => $CalendarLinkItem['title'],
                'startDate' => AppUtility::getFormattedDate($CalendarLinkItem['startdate']),
                'endDate' => AppUtility::getFormattedDate($CalendarLinkItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $CalendarLinkItem['startdate'],
                'endDateString' => $CalendarLinkItem['enddate'],
                'linkedId' => $CalendarLinkItem['id'],
                'calTag' => $CalendarLinkItem['caltag']
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
        if ($this->isPost()) {
            //delete any marked for deletion
            if (isset($eventData['delete']) && count($eventData['delete'])>0) {
                foreach ($eventData['delete'] as $id=>$val) {
                    if($val == AppConstant::NUMERIC_ONE){
                        CalItem::deleteByCourseId($id,$courseId);
                    }
                }
            }

            if (isset($eventData['tag']) && count($eventData['tag'])>0) {
                foreach ($eventData['tag'] as $id=>$tag) {
                    $date = $eventData['EventDate'.$id];
                    $title = $eventData['eventDetails'][$id];
                    preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
                    $date = mktime(12,0,0,$dmatches[1],$dmatches[2],$dmatches[3]);
                    CalItem::setEvent($date,$tag,$title,$id);
                }
            }

            //add new
            if (trim($eventData['ManageEventForm']['newEventDetails'])!='' || $eventData['ManageEventForm']['newTag'] != '!') {
                $date = $eventData['startDate'];
                $tag = $eventData['ManageEventForm']['newTag'];
                $title = $eventData['ManageEventForm']['newEventDetails'];
                preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
                $newdate = mktime(12,0,0,$dmatches[1],$dmatches[2],$dmatches[3]);
                $items = new CalItem();
                $items->createEvent($newdate,$tag,$title,$courseId);
            }

            if ($eventData['Submit']=='Save') {
                if ($from=='indexPage') {
                    return $this->redirect('index?cid='. $courseId);
                } else {

//                    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showcalendar.php?cid=$cid");
                }
                exit;
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
}

