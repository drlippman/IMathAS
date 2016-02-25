<?php

namespace app\controllers\course;

use app\components\AppConstant;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\filehandler;
use app\components\ShowItemCourse;
use app\models\_base\BaseImasGroups;
use app\models\AppModel;
use app\models\AssessmentSession;
use app\models\Blocks;
use app\models\CalItem;
use app\models\ContentTrack;
use app\models\Course;
use app\models\Assessments;
use app\models\Exceptions;
use app\models\forms\ChangeUserInfoForm;
use app\models\forms\CourseSettingForm;
use app\models\forms\ThreadForm;
use app\models\ForumPosts;
use app\models\ForumSubscriptions;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\Grades;
use app\models\InstrFiles;
use app\models\LinkedText;
use app\models\Links;
use app\models\Forums;
use app\models\GbScheme;
use app\models\Items;
use app\models\Message;
use app\models\Questions;
use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\Teacher;
use app\models\InlineText;
use app\models\Thread;
use app\models\Wiki;
use app\models\User;
use app\models\GbCats;
use app\models\StuGroupSet;
use app\models\Rubrics;
use app\models\Outcomes;
use app\models\WikiRevision;
use app\models\WikiView;
use yii\web\UploadedFile;
use app\models\ExternalTools;
use Yii;
use app\controllers\AppController;
use app\models\forms\DeleteCourseForm;
use yii\db\Exception;
use yii\helpers\Html;
use app\components\CopyItemsUtility;

class CourseController extends AppController
{
    public $filehandertypecfiles = 'local';
    /**
     * Display all course in item order
     */
    public $enableCsrfValidation = false;
    public $user = null;

    public function beforeAction($action)
    {
        $this->user = $this->getAuthenticatedUser();
        $actionPath = Yii::$app->controller->action->id;
        $params = $this->getRequestParams();
        $courseId =   ($params['cid'] || $params['courseId']) ? ($params['cid'] ? $params['cid'] : $params['courseId'] ) : AppUtility::getDataFromSession('courseId');
        return $this->accessForCourseController($this->user,$courseId, $actionPath);
    }

    public function actionUpdateOwner()
    {
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $user = $this->user;
            if ($user->rights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT) {
                $this->setErrorFlash(AppConstant::NO_ACCESS_RIGHTS);
            }
            $exec = false;
            $row = Course::setOwner($params, $user);
            if ($user->rights == AppConstant::GROUP_ADMIN_RIGHT) {
                $courseitem = Course::getByCourseAndGroupId($params['cid'], $user['groupid']);
                if ($courseitem > AppConstant::NUMERIC_ZERO) {
                    $row = Course::setOwner($params, $user);
                    $exec = true;
                }
            } else {
                $exec = true;
            }
            if ($exec && $row > AppConstant::NUMERIC_ZERO) {
                $teacher = Teacher::getByUserId($user->id, $params['cid']);
                if ($teacher == AppConstant::NUMERIC_ZERO) {
                    $newTeacher = new Teacher();
                    $newTeacher->create($params['newOwner'], $params['cid']);
                }
                Teacher::removeTeacher($user->id, $params['cid']);
            }
            return $this->successResponse();
        }
    }

    public function actionAddRemoveCourse()
    {
        /**
         * Can access:
         *  1. Full Admin
         *  2. Group Admin
         *  3. Diagnostics
         *  4. Limited Course Creator.
         */
        $this->guestUserHandler();
        $this->layout = 'master';
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $this->includeJS(['course/addremovecourse.js']);
        $returnData = array('cid' => $cid, 'course' => $course);
        return $this->renderWithData('addRemoveCourse', $returnData);
    }

    public function actionGetTeachers()
    {
        /**
         * Ajax
         */
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $sortBy = AppConstant::FIRST_NAME;
        $order = AppConstant::ASCENDING;
        $users = User::findAllTeachers($sortBy, $order);
        $teachers = Teacher::getAllTeachers($courseId);
        $countTeach = count($teachers);
        $nonTeacher = array();
        $teacherIds = array();
        $teacherList = array();
        if ($teachers) {
            foreach ($teachers as $teacher) {
                $teacherIds[$teacher['userid']] = true;
            }
        }
        if ($users) {
            foreach ($users as $user) {
                if (isset($teacherIds[$user['id']])) {
                    array_push($teacherList, $user);
                } else {
                    array_push($nonTeacher, $user);
                }
            }
        }
        return $this->successResponse(array('teachers' => $teacherList, 'nonTeachers' => $nonTeacher,'countTeach' =>$countTeach));
    }

    public function actionAddTeacherAjax()
    {
        /**
         * Ajax
         */
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $teacher = new Teacher();
            if ($params['userId'] != null && $params['cid'] != null) {
                $teacher->create($params['userId'], $params['cid']);
            }
            return $this->successResponse();
        }
    }

    public function actionRemoveTeacherAjax()
    {
        /**
         * Ajax
         */
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $teacher = new Teacher();
            if ($params['userId'] != null && $params['cid'] != null) {
                $teacher->removeTeacher($params['userId'], $params['cid']);
            }
            return $this->successResponse();
        }
    }

    public function actionAddAllAsTeacherAjax()
    {
        /**
         * Ajax
         */
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $courseId = $params['cid'];
            $usersIds = json_decode($params['usersId']);
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($usersIds); $i++) {
                $teacher = new Teacher();
                $teacher->create($usersIds[$i], $courseId);
            }
            return $this->successResponse();
        }
    }

    public function actionRemoveAllAsTeacherAjax()
    {
        /**
         * Ajax
         */
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $courseId = $params['cid'];
            $usersIds = json_decode($params['usersId']);
            $teachers = Teacher::getTeachersById($courseId);
            if (count($teachers) == count($usersIds)) {
                $this->setWarningFlash('You can not remove all Teachers, atleast one teacher is required for the course');
            }else {
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($usersIds); $i++) {
                    $teacher = new Teacher();
                    $teacher->removeTeacher($usersIds[$i], $courseId);
                }
            }
            return $this->successResponse();

        }
    }

    /**
     * Display linked text on course page
     */
    public function actionShowLinkedText()
    {
        /**
         * Greater than guest user.
         */
        $this->layout = 'master';
        $user = $this->user;
        $courseId = $this->getParamVal('cid');
        $id = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $isTeacher = $this->isTeacher($user['id'], $courseId);
        $isTutor = $this->isTutor($user['id'], $courseId);
        $isStudent = $this->isStudent($user['id'], $courseId);
        $isGuest = $this->isGuestUser();

        $link = Links::getById($id);
        $text = $link['text'];
        $title = $link['title'];
        $target = $link['target'];
        $titlesimp = strip_tags($title);


        if (!isset($isTeacher) && !isset($isTutor) && !isset($isStudent) && !isset($isGuest)) {
            return array('status'=> false, 'message'=>"You are not enrolled in this course. Please return to the Home Page and enroll");
        }

        if (!isset($id)) {
            return array('status'=> false, 'message'=>"No item specified"); ?>

       <?php }
        if (substr($text,0,8)=='exttool:') {
            $param = "cid={$courseId}&id=$id";
            if ($target==0) {
                $height = '500px';
                $width = '95%';
                $param .= '&target=iframe';
                $text = '<iframe id="exttoolframe" src="'.AppUtility::getHomeURL().'/filter/basiclti/post.php?'.$param.'" height="'.$height.'" width="'.$width.'" ';
                $text .= 'scrolling="auto" frameborder="1" transparency>   <p>Error</p> </iframe>';
                $text .= '<script type="text/javascript">$(function() {$("#exttoolframe").css("height",$(window).height() - $(".midwrapper").position().top - ($(".midwrapper").height()-500) - ($("body").outerHeight(true) - $("body").innerHeight()));});</script>';
            } else {
                //redirect to post page
                $param .= '&target=new';
                header('Location: '.AppUtility::getHomeURL(). '/filter/basiclti/post.?'.$param);
            }
        }
        $this->includeCSS(['course/items.css']);
        $returnData = array('course' => $course, 'links' => $link, 'user' => $user);
        return $this->renderWithData('showLinkedText', $returnData);
    }
    /**
     * To handle event on calendar.
     */
    public function actionGetAssessmentDataAjax()
    {
        /**
         * Ajax
         */
        $this->guestUserHandler();
        $user = $this->user;
        $params = $this->getRequestParams();
        $cid = $params['cid'];
        $currentDate = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
        $assessments = Assessments::getByCourseId($cid);
        $calendarItems = CalItem::getByCourseId($cid);
        $CalendarLinkItems = Links::getByCourseId($cid);
        $calendarInlineTextItems = InlineText::getByCourseId($cid);
        $notStudent = false;
        $assessmentArray = array();
        foreach ($assessments as $assessment)
        {
            $assessmentArray[] = array(
                'startDate' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$assessment['startdate']),
                'endDate' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$assessment['enddate']),
                'dueTime' => AppUtility::tzdate(AppConstant::GIA_FORMAT,$assessment['enddate']),
                'reviewDate' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$assessment['reviewdate']),
                'name' => ucfirst($assessment['name']),
                'startDateString' => $assessment['startdate'],
                'endDateString' => $assessment['enddate'],
                'reviewDateString' => $assessment['reviewdate'],
                'now' => AppUtility::parsedatetime(date('m/d/Y'),date('h:i a')),
                'assessmentId' => $assessment['id'],
                'courseId' => $assessment['courseid']
            );
        }
        $calendarArray = array();
        foreach ($calendarItems as $calendarItem) {
            $calendarArray[] = array(
                'courseId' => $calendarItem['courseid'],
                'date' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$calendarItem['date']),
                'dueTime' => AppUtility::tzdate(AppConstant::GIA_FORMAT,$calendarItem['date']),
                'title' => ucfirst($calendarItem['title']),
                'tag' => ucfirst($calendarItem['tag']),
            );
        }
        $calendarLinkArray = array();
        foreach ($CalendarLinkItems as $CalendarLinkItem) {
            $aLink = '';
            $notStudent = false;
            if($CalendarLinkItem['startdate'] > $currentDate && $CalendarLinkItem['enddate'] > $currentDate){
                $notStudent = true;
            } else{
                $notStudent = false;
            }
            $courseId = $CalendarLinkItem['courseid'];
            $id = $CalendarLinkItem['id'];
            if((substr($CalendarLinkItem['text'],0,4) == "http")){
                $aLink = $CalendarLinkItem['text'];
            } else if(substr(strip_tags($CalendarLinkItem['text']),0,5)=="file:") {
                $fileName = substr(strip_tags($CalendarLinkItem['text']),5);
                $aLink = filehandler::getcoursefileurl($fileName);
            } else{
                $aLink = "show-linked-text?cid=$courseId&id=$id";
            }
            $calendarLinkArray[] = array(
                'courseId' => $CalendarLinkItem['courseid'],
                'oncal' => $CalendarLinkItem['oncal'],
                'id' => $CalendarLinkItem['id'],
                'text' => $CalendarLinkItem['text'],
                'title' => ucfirst($CalendarLinkItem['title']),
                'startDate' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$CalendarLinkItem['startdate']),
                'endDate' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$CalendarLinkItem['enddate']),
                'dueTime' => AppUtility::tzdate(AppConstant::GIA_FORMAT,$CalendarLinkItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $CalendarLinkItem['startdate'],
                'endDateString' => $CalendarLinkItem['enddate'],
                'linkedId' => $CalendarLinkItem['id'],
                'calTag' => ucfirst($CalendarLinkItem['caltag']),
                'avail' => $CalendarLinkItem['avail'],
                'notStudent' => $notStudent,
                'userRights' => $user['rights'],
                'textType' => $aLink
            );
        }

        $calendarInlineTextArray = array();
        foreach ($calendarInlineTextItems as $calendarInlineTextItem) {
            $notStudent = false;
            if($calendarInlineTextItem['startdate'] > $currentDate && $calendarInlineTextItem['enddate'] > $currentDate){
                $notStudent = true;
            } else{
                $notStudent = false;
            }
            $calendarInlineTextArray[] = array(
                'id' => $calendarInlineTextItem['id'],
                'oncal' => $calendarInlineTextItem['oncal'],
                'courseId' => $calendarInlineTextItem['courseid'],
                'endDate' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$calendarInlineTextItem['enddate']),
                'startDate' => AppUtility::tzdate(AppConstant::YMD_FORMAT,$calendarInlineTextItem['startdate']),
                'dueTime' => AppUtility::tzdate(AppConstant::GIA_FORMAT,$calendarInlineTextItem['enddate']),
                'now' => AppUtility::parsedatetime(date('m/d/Y'), date('h:i a')),
                'startDateString' => $calendarInlineTextItem['startdate'],
                'endDateString' => $calendarInlineTextItem['enddate'],
                'avail' => $calendarInlineTextItem['avail'],
                'title' => $calendarInlineTextItem['title'],
                'notStudent' => $notStudent,
                'userRights' => $user['rights'],
                'calTag' => ucfirst($calendarInlineTextItem['caltag'])
            );
        }
        $responseData = array('user' => $user,'assessmentArray' => $assessmentArray, 'calendarArray' => $calendarArray, 'calendarLinkArray' => $calendarLinkArray, 'calendarInlineTextArray' => $calendarInlineTextArray, 'currentDate' => $currentDate, 'aLink' => $aLink);
        return $this->successResponse($responseData);
    }

/*
 *   Display calendar on click of menuBars
 */
    public function actionCalendar()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $user = $this->user;
        $courseId = $this->getParamVal('cid');
        $isLocked = $this->isLocked($user->id, $courseId);
        if($isLocked){
            $this->setErrorFlash(AppConstant::ERROR_MSG_FOR_LOCLKED_STUDENT);
            return $this->redirect(Yii::$app->getHomeUrl());
        }
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $course = Course::getById($courseId);
        $line = Course::getCourseDataById($courseId);
        $items = unserialize($line['itemorder']);
        $line = Items::getByItem($items);
        $typeid = $line['typeid'];
        $parent = AppConstant::NUMERIC_ZERO;

        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css', 'course/course.css', 'instructor.css']);
        $this->includeJS(['moment.min.js', 'fullcalendar.min.js', 'student.js']);
        $responseData = array('course' => $course, 'user' => $user, 'items' => $items, 'typeid' => $typeid, 'parent' => $parent);
        return $this->render('calendar', $responseData);
    }
    /*
     * Modify inline text: Teacher
     */
    public function actionModifyInlineText()
    {

        /**
         * Can access: greater than student
         *  1. Full Admin
         *  2. Group Admin
         *  3. Diagnostics
         *  4. Limited Course Creator.
         *  5. Teacher
         */
        global $outcomes;
        $this->guestUserHandler();
        $user = $this->user;
        $this->layout = 'master';
        $userId = $user['id'];
        $params = $this->getRequestParams();
        $cid = $params['cid'];
        $teacherId = $this->isTeacher($user['id'], $cid);
        $this->noValidRights($teacherId);
        $block = $this->getParamVal('block');
        $inlineId = $params['id'];
        $course = Course::getById($cid);
        $inlineText = InlineText::getById($inlineId);
        $teacherId = $this->isTeacher($userId,$cid);
        $tutorId = $this->isTutor($userId, $cid);
        $tb = $this->getParamVal('tb');
        $block = $this->getParamVal('block');
        $moveFile = $this->getParamVal('movefile');
        if (isset($params['tb'])) {
            $filter = $params['tb'];
        } else {
            $filter = 'b';
        }
        if (!(isset($teacherId))) { // loaded by a NON-teacher
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to log in as a teacher to access this page";
        } elseif (!($cid)) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to access this page from the course page menu";
        }  else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
            $page_formActionTag = "modify-inline-text?block=$block&cid=$cid&folder=" . $params['folder'];
            $page_formActionTag .= "&tb=$filter";

            $calTag = $params['calTag'];
            if ($params['title'] != null || $params['text'] != null || $params['sdate'] != null) { //if the form has been submitted
                if ($params['avail'] == AppConstant::NUMERIC_ONE) {
                    if ($params['sdatetype'] == '0')
                    {
                        $startDate = AppConstant::NUMERIC_ZERO;
                    } else {
                        $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
                    }
                    if ($params['edatetype'] == '2000000000') {
                        $endDate = AppConstant::ALWAYS_TIME;
                    } else {
                        $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
                    }
                    $oncal = $params['oncal'];
                } else if ($params['avail'] == AppConstant::NUMERIC_TWO) {
                    if ($params['altoncal'] == AppConstant::NUMERIC_ZERO)
                    {
                        $startDate = AppConstant::NUMERIC_ZERO;
                        $oncal = AppConstant::NUMERIC_ZERO;
                    } else {
                        $startDate = AppUtility::parsedatetime($params['cdate'], "12:00 pm");
                        $oncal = AppConstant::NUMERIC_ONE;
                        $calTag = $params['altcaltag'];
                    }
                    $endDate =  AppConstant::ALWAYS_TIME;
                }else {
                    $startDate = AppConstant::NUMERIC_ZERO;
                    $endDate = AppConstant::ALWAYS_TIME;
                    $oncal = AppConstant::NUMERIC_ZERO;
                }
                if (isset($params['hidetitle'])) {
                    $params['title'] = '##hidden##';
                }
                if (isset($params['isplaylist'])) {
                    $isplaylist = AppConstant::NUMERIC_ONE;
                } else {
                    $isplaylist = AppConstant::NUMERIC_ZERO;
                }

                $params['title'] =  htmlentities(stripslashes($params['title']));
                $params['text'] =  stripslashes($params['text']);
                $outcomes = array();
                if (isset($params['outcomes'])) {
                    foreach ($params['outcomes'] as $o) {
                        if (is_numeric($o) && $o>0) {
                            $outcomes[] = intval($o);
                        }
                    }
                }
                $outcomes = implode(',', $outcomes);

                $filestoremove = array();
                if (isset($params['id'])) {  //already have id; update
                    $tempArray = array();
                    $tempArray['startdate'] = $startDate;
                    $tempArray['courseid'] = $cid;
                    $tempArray['enddate'] = $endDate;
                    $tempArray['caltag'] = $calTag;
                    $tempArray['outcomes'] = $outcomes;
                    $tempArray['isplaylist'] = $isplaylist;
                    $tempArray['oncal'] = $oncal;
                    $tempArray['title'] = $params['title'];
                    $tempArray['text'] = $params['text'];
                    $tempArray['avail'] = $params['avail'];
                    $updateResult = new InlineText();
                    $result = $updateResult->updateChanges($tempArray, $params['id']);

                    //update attached files
                    $resultFile = InstrFiles::getFileName($params['id']);

                   foreach($resultFile as $key => $row) {
                        if (isset($params['delfile-'.$row['id']])) {
                            $filestoremove[] = $row['id'];
                             InstrFiles::deleteByItemId($row['id']);
                            $r2 = InstrFiles::getByIdForFile($row['filename']);
                            if (count($r2) == 0) {
                                //$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
                                //unlink($uploaddir . $row[2]);
                                filehandler::deletecoursefile($row['filename']);
                            }
                        } else if ($params['filedescr-'.$row['id']] != $row['description']) {
                            $query = InstrFiles::setFileDescription($row['id'], $params['filedescr-'.$row['id']]);
                        }
                    }
                    $newtextid = $params['id'];
                } else { //add new
                    $tempArray = array();
                    $tempArray['cid'] = $cid;
                    $tempArray['startdate'] = $startDate;
                    $tempArray['enddate'] = $endDate;
                    $tempArray['caltag'] = $calTag;
                    $tempArray['outcomes'] = $outcomes;
                    $tempArray['isplaylist'] = $isplaylist;
                    $tempArray['oncal'] = $params['oncal'];
                    $tempArray['title'] = $params['title'];
                    $tempArray['text'] = $params['text'];
                    $tempArray['avail'] = $params['avail'];

                    $newInline = new InlineText();
                    $newtextid = $newInline->saveInlineText($tempArray);
                    $itemType = 'InlineText';
                    $itemId = new Items();
                    $itemid = $itemId->saveItems($cid, $newtextid, $itemType);
                    $courseItemOrder = Course::getItemOrder($cid);
                    $itemOrder = $courseItemOrder->itemorder;
                    $items = unserialize($itemOrder);
                    $blockTree = explode('-',$block);
                    $sub =& $items;
                    for ($i=1; $i<count($blockTree); $i++)
                    {
                        $sub =& $sub[$blockTree[$i]-1]['items']; //-1 to adjust for 1-indexing
                    }

                    if ($filter=='b') {
                        $sub[] = $itemid;
                    } else if ($filter=='t') {
                        array_unshift($sub,$itemid);
                    }

                    $itemOrder = serialize($items);
                    $saveItemOrderIntoCourse = new Course();
                    $saveItemOrderIntoCourse->setItemOrder($itemOrder, $cid);
                }
                if ($_FILES['userfile']['name'] != '') {
                    $uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
                    $userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['userfile']['name']));
                    $filename = $userfilename;
//                    print_r($filename); die;

                    $extension = strtolower(strrchr($userfilename,"."));
                    $badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
                    if (in_array($extension,$badextensions)) {
                        $overWriteBody = 1;
                        $body = "<p>File type is not allowed</p>";
                    } else {
//                        print_r(filehandler::storeuploadedcoursefile('userfile',$cid.'/'.$filename)); die;
                        if (($filename = filehandler::storeuploadedcoursefile('userfile',$cid.'/'.$filename)) !== false) {

                            if (trim($params['newfiledescr'])=='') {
                                $params['newfiledescr'] = $filename;
                            }

                            $addedfileOne = new InstrFiles();
                            $addedfile = $addedfileOne->saveFile($params,$filename, $newtextid);
                            $params['id'] = $newtextid;
                        } else {
                            $overWriteBody = 1;
                            $body = "<p>Error uploading file!</p>\n";
                        }
                    }
                }
            }
            if (($addedfile) || count($filestoremove) > 0 || ($params['movefile'])) {
                $resultFileOrder = InlineText::getFileOrder($params['id']);
                $fileorder = explode(',', $resultFileOrder['fileorder']);

                if ($fileorder['fileorder'] == '') {
                    $fileorder = array();
                }
                if (($addedfile)) {
                    $fileorder[] = $addedfile;
                }
                if (count($filestoremove) > 0) {
                    for ($i=0; $i<count($filestoremove); $i++) {
                        $k = array_search($filestoremove[$i],$fileorder);
                        if ($k!==FALSE) {
                            array_splice($fileorder,$k,1);
                        }
                    }
                }

                if (isset($params['movefile'])) {
                    $from = $params['movefile'];
                    $to = $params['movefileto'];
                    $itemtomove = $fileorder[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
                    array_splice($fileorder,$from-1,1);
                    array_splice($fileorder,$to-1,0,$itemtomove);
                }
                $fileorder = implode(',',$fileorder);
                InlineText::setFileOrder($params['id'],$fileorder);
            }

            if ($params['submitbtn'] == 'Submit') {
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' .$cid));
            }

            if (isset($params['id'])) {
                $line = InlineText::getById($params['id']);
                if ($line['title']=='##hidden##') {
                    $hidetitle = true;
                    $line['title']='';
                }
                $startDate = $line['startdate'];
                $endDate = $line['enddate'];
                $fileorder = explode(',',$line['fileorder']);
                if ($line['avail']== 2 && $startDate > 0) {
                    $altoncal = 1;
                } else {
                    $altoncal = 0;
                }
                if ($line['outcomes']!='') {
                    $gradeoutcomes = explode(',',$line['outcomes']);
                } else {
                    $gradeoutcomes = array();
                }

                $savetitle = "Save Changes";
                $pageTitle = 'Modify Inline Text';
            } else {
                //set defaults
                $line['title'] = "Enter title here";
                $line['text'] = "<p>Enter text here</p>";
                $line['avail'] = 1;
                $line['oncal'] = 0;
                $line['caltag'] = '!';
                $altoncal = 0;
                $startDate = time();
                $endDate = time() + 7*24*60*60;
                $pageTitle = AppConstant::ADD_INLINE_TEXT;
                $hidetitle = false;
                $fileorder = array();
                $gradeoutcomes = array();
                $savetitle = "Create Item";
            }

            if ($startDate!=0) {
                $sdate = AppUtility::tzdate("m/d/Y",$startDate);
                $stime = AppUtility::tzdate("g:i a",$startDate);
            } else {
                $sdate = AppUtility::tzdate("m/d/Y",time());
                $stime = AppUtility::tzdate("g:i a",time());
            }
            if ($endDate!=2000000000) {
                $edate = AppUtility::tzdate("m/d/Y",$endDate);
                $etime = AppUtility::tzdate("g:i a",$endDate);
            } else {
                $edate = AppUtility::tzdate("m/d/Y",time()+7*24*60*60);
                $etime = AppUtility::tzdate("g:i a",time()+7*24*60*60);
            }

            if (isset($params['id'])) {
                $result = InstrFiles::getFileName($params['id']);
                $page_fileorderCount = count($fileorder);
                $i = 0;
                $page_FileLinks = array();
                if (count($result) > 0) {

                    foreach($result as $key => $row) {
                        $filedescr[$row['id']] = $row['description'];
                        $filenames[$row['id']] = rawurlencode($row['filename']);
                    }
                    foreach ($fileorder as $k=>$fid) {
                        $page_FileLinks[$k]['link'] = $filenames[$fid];
                        $page_FileLinks[$k]['desc'] = $filedescr[$fid];
                        $page_FileLinks[$k]['fid'] = $fid;

                    }
                }
            } else {
                $stime = AppUtility::tzdate("g:i a",time());
                $etime = AppUtility::tzdate("g:i a",time()+7*24*60*60);
            }

            $resultOutCome = Outcomes::getByCourseId($cid);

            $outcomenames = array();
           foreach($resultOutCome as $key => $row) {
                $outcomenames[$row['id']] = $row['name'];
            }
            $result = Course::getOutComeByCourseId($cid);
            $row = $result;
            if ($row['outcomes']=='') {
                $outcomearr = array();
            } else {
                $outcomearr = unserialize($row['outcomes']);
            }
            $outcomes = array();
            if($outcomearr)
            {
                $this->flattenarr($outcomearr);
            }
            $page_formActionTag .= (isset($params['id'])) ? "&id=" . $params['id'] : "";
        }
        $this->includeJS(["course/inlineText.js", "editor/tiny_mce.js", "editor/tiny_mce_src.js", "general.js","editor.js"]);
        $this->includeCSS(['course/items.css']);
        $responseData = array('page_formActionTag' => $page_formActionTag, 'filter' => $filter,'savetitle' => $savetitle, 'line' => $line, 'startDate' => $startDate, 'endDate' => $endDate, 'sdate' => $sdate, 'stime' => $stime, 'edate' => $edate, 'etime' => $etime, 'outcome' => $outcomes, 'page_fileorderCount' => $page_fileorderCount, 'page_FileLinks' => $page_FileLinks, 'params' => $params, 'hidetitle' => $hidetitle, 'caltag' => $calTag, 'inlineId' => $inlineId, 'course' => $course, 'pageTitle' => $pageTitle, 'outcomenames' => $outcomenames, 'gradeoutcomes' => $gradeoutcomes, 'block' => $block, 'altoncal' => $altoncal);
        return $this->renderWithData('modifyInlineText', $responseData);
    }

    public function actionAddLink()
    {
        /**
         * Can access: greater than student
         *  1. Full Admin
         *  2. Group Admin
         *  3. Diagnostics
         *  4. Limited Course Creator.
         *  5. Teacher
         */
        $params = $this->getRequestParams();
        $user = $this->user;
        $this->layout = 'master';
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $modifyLinkId = $params['id'];
        $block = $this->getParamVal('block');
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $model = new ThreadForm();
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $this->noValidRights($teacherId);
        $query = Outcomes::getByCourseId($courseId);
        $key = AppConstant::NUMERIC_ONE;
        $pageOutcomes = array();
        if (isset($params['tb'])) {
            $filter = $params['tb'];
        } else {
            $filter = 'b';
        }
        if ($query) {
            foreach ($query as $singleData) {
                $pageOutcomes[$singleData['id']] = $singleData['name'];
                $key++;
            }
        }
        $pageOutcomesList = array();
        if ($key > AppConstant::NUMERIC_ZERO) {//there were outcomes
            $query = $course['outcomes'];
            $outcomeArray = unserialize($query);
            global $outcomesList;
             $this->flatArray($outcomeArray);
            if ($outcomesList) {
                foreach ($outcomesList as $singlePage) {
                    array_push($pageOutcomesList, $singlePage);
                }
            }
        }
        $key = AppConstant::NUMERIC_ZERO;
        $gbCatsData = GbCats::getByCourseId($courseId);
        foreach ($gbCatsData as $group) {
            $gbCatsId[$key] = $group['id'];
            $gbCatsLabel[$key] = $group['name'];
            $key++;
        }
        $toolsData = ExternalTools::externalToolsDataForLink($courseId,$user['groupid']);
        $toolVals = array();
        $toolVals[0] = AppConstant::NUMERIC_ZERO;
        $key = AppConstant::NUMERIC_ONE;
        foreach ($toolsData as $tool) {
            $toolVals[$key++] = $tool['id'];
        }
        $toolLabels[0] = AppConstant::SELECT_TOOL;
        $key = AppConstant::NUMERIC_ONE;
        foreach ($toolsData as $tool)
        {
            $toolLabels[$key++] = $tool['name'];
        }

        if ($params['id']) {
            $linkData = LinkedText::getById($params['id']);
            if ($linkData['avail'] == AppConstant::NUMERIC_TWO && $linkData['startdate'] > AppConstant::NUMERIC_ZERO) {
                $altOnCal = AppConstant::NUMERIC_ONE;
            } else {
                $altOnCal = AppConstant::NUMERIC_ZERO;
            }
            if (substr($linkData['text'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR) == 'http') {
                $type = 'web';
                $webaddr = $linkData['text'];
                $linkData['text'] = "<p>Enter text here</p>";
            } else if (substr($linkData['text'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE) == 'file:') {
                $type = 'file';
                $fileInitialCount = AppConstant::NUMERIC_SIX + strlen($courseId);
                $filename = substr($linkData['text'], $fileInitialCount);
            } else if (substr($linkData['text'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_EIGHT) == 'exttool:') {
                $type = 'tool';
                $points = $linkData['points'];
                $toolParts = explode('~~', substr($linkData['text'], AppConstant::NUMERIC_EIGHT));
                $selectedTool = $toolParts[0];
                $toolCustom = $toolParts[1];
                if (isset($toolParts[2])) {
                    $toolCustomUrl = $toolParts[2];
                } else {
                    $toolCustomUrl = '';
                }
                if (isset($toolParts[3])) {
                    $gbCat = $toolParts[3];
                    $cntInGb = $toolParts[4];
                    $tutorEdit = $toolParts[5];
                    $gradeSecret = $toolParts[6];
                }
            } else {
                $type = 'text';
            }
            if ($linkData['outcomes'] != '') {
                $gradeOutcomes = explode(',', $linkData['outcomes']);
            } else {
                $gradeOutcomes = array();
            }
            if ($linkData['summary'] == '') {
                $line['summary'] = "<p>Enter summary here (displays on course page)</p>";
            }
            $startDate = $linkData['startdate'];
            $endDate = $linkData['enddate'];
            if ($startDate != AppConstant::NUMERIC_ZERO) {
                $sDate = AppUtility::tzdate("m/d/Y", $startDate);
                $sTime = AppUtility::tzdate("g:i a", $startDate);
                $startDate =AppConstant::NUMERIC_ONE;
            } else {
                $sDate = date('m/d/Y');
                $sTime = time();
            }
            if ($endDate != AppConstant::ALWAYS_TIME) {
                $eDate = AppUtility::tzdate("m/d/Y", $endDate);
                $eTime = AppUtility::tzdate("g:i a", $endDate);
                $endDate = AppConstant::NUMERIC_ONE;
            } else {
                $eDate = date("m/d/Y",strtotime("+1 week"));
                $eTime = time();
            }
            $saveTitle = "Modify Link";
            $saveButtonTitle = "Save Changes";
            $gradeSecret = uniqid();
            $defaultValues = array(
                'title' => $linkData['title'],
                'summary' => $linkData['summary'],
                'text' => $linkData['text'],
                'startDate' => $startDate,
                'gradeoutcomes' => $gradeOutcomes,
                'endDate' => $endDate,
                'sDate' => $sDate,
                'sTime' =>$sTime,
                'eDate' => $eDate,
                'eTime' => $eTime,
                'webaddr' => $webaddr,
                'filename' => $filename,
                'altoncal' => $altOnCal,
                'type' => $type,
                'toolparts' => $toolParts,
                'cntingb' => $cntInGb,
                'gbcat' => $gbCat,
                'tutoredit' => $tutorEdit,
                'gradesecret' => $gradeSecret,
                'saveButtonTitle' => $saveButtonTitle,
                'saveTitle' => $saveTitle,
                'points' => $points,
                'selectedtool' => $selectedTool,
                'toolcustom' => $toolCustom,
                'toolcustomurl' => $toolCustomUrl,
                'calendar' => $linkData['oncal'],
                'avail' => $linkData['avail'],
                'open-page-in' => $linkData['target'],
                'caltag' => $linkData['caltag'],
            );
        } else {
            $defaultValues = array(
                'saveButtonTitle' => AppConstant::CREATE_LINK,
                'saveTitle' => AppConstant::ADD_LINK,
                'title' => AppConstant::ENTER_TITLE,
                'summary' => AppConstant::ENTER_SUMMARY,
                'text' => "Enter text here",
                'gradeoutcomes' => array(),
                'type' => 'text',
                'points' => AppConstant::NUMERIC_ZERO,
                'sDate' => date("m/d/Y"),
                'sTime' => time(),
                'eDate' => date("m/d/Y",strtotime("+1 week")),
                'eTime' => time(),
                'calendar' => AppConstant::NUMERIC_ZERO,
                'avail' => AppConstant::NUMERIC_ONE,
                'open-page-in' => AppConstant::NUMERIC_ZERO,
                'cntingb' => AppConstant::NUMERIC_ONE,
                'tutoredit' => AppConstant::NUMERIC_ZERO,
                'filename' => ' ',
                'selectedtool' => AppConstant::NUMERIC_ZERO,
                'endDate' => AppConstant::NUMERIC_ONE,
                'startDate' => AppConstant::NUMERIC_ONE,
                'gradesecret' => uniqid(),
                'altoncal' => AppConstant::NUMERIC_ZERO,
                'caltag' => '!'
            );
        }
        $page_formActionTag = "add-link?block=$block&cid=$courseId&folder=" . $params['folder'];
        $page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
        $page_formActionTag .= "&tb=$filter";
        if ($this->isPostMethod()) { //after modify done, save into database
            $outcomes = array();
            if (isset($params['outcomes'])) {
                foreach ($params['outcomes'] as $o) {
                    if (is_numeric($o) && $o> AppConstant::NUMERIC_ZERO) {
                        $outcomes[] = intval($o);
                    }
                }
            }
            $outcomes = implode(',',$outcomes);
            if ($params['linktype'] == 'text') {
                /*
                 * To add htmllawed to link text
                 */
            } else if ($params['linktype'] == 'file') {
                $model->file = UploadedFile::getInstance($model, 'file');
                $path = AppConstant::UPLOAD_DIRECTORY .$courseId.'/';
                if ( ! is_dir($path)) {
                    mkdir($path);
                }
                if ($model->file) {
                    $filename = $path.$model->file->name;
                    $model->file->saveAs($filename);
                }
                $params['text'] = 	'file:'.$courseId.'/'.$model->file->name;
            } else if ($params['linktype'] == 'web') {
                $params['text'] = trim(strip_tags($params['web']));
                if (substr($params['text'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR) != 'http') {
                    $this->setSuccessFlash('Web link should start with http://');
                    return $this->redirect(AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $course->id));
                }
            } else if ($params['linktype'] == 'tool') {
                if ($params['tool'] == AppConstant::NUMERIC_ZERO) {
                    $this->setSuccessFlash('Select external Tool');
                    return $this->redirect(AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $course->id));
                } else {

                    $params['text'] = 'exttool:' . $params['tool'] . '~~' . $params['toolcustom'] . '~~' . $params['toolcustomurl'];
                    if ($params['usegbscore'] == AppConstant::NUMERIC_ZERO || $params['points'] == AppConstant::NUMERIC_ZERO) {
                        $params['points'] = AppConstant::NUMERIC_ZERO;
                    } else {
                        $params['text'] .= '~~' . $params['gbcat'] . '~~' . $params['cntingb'] . '~~' . $params['tutoredit'] . '~~' . $params['gradesecret'];
                        $params['points'] = intval($params['points']);
                    }
                }
            }
            if ($params['linktype'] == 'tool') {
                $externalToolsData = new ExternalTools();
                $externalToolsData->updateExternalToolsData($params);
            }
            $calTag = $params['tag'];
            if ($params['avail']== AppConstant::NUMERIC_ONE) {
                if ($params['available-after']== AppConstant::NUMERIC_ZERO)
                {
                    $startDate = AppConstant::NUMERIC_ZERO;
                } else if ($params['available-after']=='now') {
                    $startDate = time();
                } else {
                    $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
                }

                if ($params['available-until']== AppConstant::ALWAYS_TIME) {
                    $endDate = AppConstant::ALWAYS_TIME;
                } else {
                    $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
                }
                $onCal = $params['oncal'];
            } else if ($params['avail']== AppConstant::NUMERIC_TWO) {
                if ($params['altoncal']== AppConstant::NUMERIC_ZERO) {
                    $startDate = AppConstant::NUMERIC_ZERO;
                    $onCal = AppConstant::NUMERIC_ZERO;
                } else {
                    $startDate = AppUtility::parsedatetime($params['cdate'],"12:00 pm");
                    $onCal = AppConstant::NUMERIC_ONE;
                    $calTag = $params['tag-always'];
                }
                $endDate =  AppConstant::ALWAYS_TIME;
            } else {
                $startDate = AppConstant::NUMERIC_ONE;
                $endDate = AppConstant::ALWAYS_TIME;
                $onCal = AppConstant::NUMERIC_ZERO;
            }
            $finalArray['courseid'] = $params['cid'];
            $finalArray['title'] = $params['name'];
            $str = AppConstant::ENTER_SUMMARY;
            if ($params['summary']== $str) {
                $finalArray['summary'] = ' ';
            } else {
                /*
                 * Apply html lawed here
                 */
                $finalArray['summary'] = $params['summary'];
            }
            $finalArray['text'] = $params['text'];
            $finalArray['avail'] = $params['avail'];
            $finalArray['oncal'] = $onCal;
            $finalArray['caltag'] = $calTag;
            $finalArray['target'] = $params['target'];
            $finalArray['points'] = $params['points'];
            $finalArray['target'] = $params['open-page-in'];
            $finalArray['startdate'] = $startDate;
            $finalArray['enddate'] = $endDate;
            $finalArray['outcomes'] = $outcomes;
            if ($modifyLinkId) {
                $finalArray['id'] = $params['id'];
                $link = new LinkedText();
                $link->updateLinkData($finalArray);
            } else {
                $linkText = new LinkedText();
                $linkTextId = $linkText->AddLinkedText($finalArray);
                $itemType = AppConstant::LINK;
                $itemId = new Items();
                $lastItemId = $itemId->saveItems($courseId, $linkTextId, $itemType);
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemOrder = $courseItemOrder->itemorder;
                $items = unserialize($itemOrder);
                $blockTree = explode('-',$block);
                $sub =& $items;
                for ($i = AppConstant::NUMERIC_ONE; $i < count($blockTree); $i++) {
                    $sub =& $sub[$blockTree[$i] - AppConstant::NUMERIC_ONE]['items']; //-1 to adjust for 1-indexing
                }
                if ($filter=='b') {
                    $sub[] = $lastItemId;
                } else if ($filter=='t') {
                    array_unshift($sub,$lastItemId);
                }
                $itemOrder = (serialize($items));
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemOrder, $courseId);
            }
            $this->includeJS(["editor/tiny_mce.js", "general.js"]);
            return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' . $course->id));
        }
        $this->includeCSS(["course/items.css"]);
        $this->includeJS(["editor/tiny_mce.js", "course/addlink.js", "general.js"]);
        $responseData = array('model' => $model, 'course' => $course, 'groupNames' => $groupNames,'pageOutcomesList' => $pageOutcomesList, 'linkData' => $linkData,
            'pageOutcomes' => $pageOutcomes, 'toolvals' => $toolVals, 'gbcatsLabel' => $gbCatsLabel, 'gbcatsId' => $gbCatsId, 'toollabels' => $toolLabels, 'defaultValues' => $defaultValues,'block' => $block, 'page_formActionTag' => $page_formActionTag);
        return $this->renderWithData('addLink', $responseData);
    }

    /**
     * recursive directory function.
     */
    function mkdir_recursive($pathname, $mode = AppConstant::TRIPLE_SEVEN)
    {
        is_dir(dirname($pathname)) || $this->mkdir_recursive(dirname($pathname), $mode);
        return is_dir($pathname) || @mkdir($pathname, $mode);
    }

    function doesfileexist($type, $key)
    {
        if ($type == 'cfile') {
            if ($GLOBALS['filehandertypecfiles'] == 's3') {
                $s3 = new \S3($GLOBALS['AWSkey'], $GLOBALS['AWSsecret']);
                return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/' . $key, false);
            } else {
                $base = rtrim(dirname(dirname(__FILE__)), '/\\') . '/course/files/';
                return file_exists($base . $key);
            }
        } else {
            if ($GLOBALS['filehandertype'] == 's3') {
                $s3 = new \S3($GLOBALS['AWSkey'], $GLOBALS['AWSsecret']);
                return $s3->getObjectInfo($GLOBALS['AWSbucket'], $key, false);
            } else {
                $base = rtrim(dirname(dirname(__FILE__)), '/\\') . '/filestore/';
                return file_exists($base . $key);
            }
        }
    }

    public function flatArray($outcomesData)
    {
        global $outcomesList;
        if ($outcomesData) {
            foreach ($outcomesData as $singleData) {
                if (is_array($singleData)) { //outcome group
                    $outcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                    $this->flatArray($singleData['outcomes']);
                } else {
                    $outcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
                }
            }
        }
        return $outcomesList;
    }

    public function flattenarr($ar) {
        global $outcomes;
        foreach ($ar as $v)
        {
            if (is_array($v)) { //outcome group
                $outcomes[] = array($v['name'], 1);
                $this->flattenarr($v['outcomes']);
            } else {
                $outcomes[] = array($v, 0);
            }
        }
    }

    public function actionCourse()
    {

        /**
         * Can access: greater than student
         *  1. Full Admin
         *  2. Group Admin
         *  3. Diagnostics
         *  4. Limited Course Creator.
         *  5. Teacher
         *  6. Studnet
         *  Greater than guest can access
         */
        global $teacherId,$isTutor,$isStudent,$courseId,$imasroot,$userId,$openBlocks,$firstLoad,$sessionData,$previewShift,$myRights,
               $hideIcons,$exceptions,$latePasses,$graphicalIcons,$isPublic,
               $studentInfo,$newPostCnts,$CFG,$latePassHrs,$hasStats,$toolSet,$readLinkedItems, $haveCalcedViewedAssess, $viewedAssess,
               $topBar, $msgSet, $newMsgs, $quickView, $courseNewFlag,$useViewButtons,$previewshift, $useviewButtons, $courseStudent;
        $user = $this->user;
        $this->layout = 'master';
        $myRights = $user['rights'];
        $userId = $user['id'];
        $courseData = $this->getRequestParams();
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $courseId = $this->getParamVal('cid');
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $this->setSessionData('courseId',$courseId);
        $teacherId = $this->isTeacher($userId, $courseId);
        $isStudent = $this->isStudent($userId, $courseId);
        $params = $this->getRequestParams();
        $this->checkSession($params);
        $teacherData = Teacher::getByUserId($userId,$courseId);
        $courseStudent = Course::getByCourseAndUser($courseId);
        $lockAId = $courseStudent['lockaid']; //ysql_result($result,0,2);
        $type = $this->getParamVal('type');

        if ($teacherData != null) {
            if ($myRights>AppConstant::STUDENT_RIGHT) {
                $teacherId = $teacherData['id'];
                if (isset($params['stuview'])) {
                    $sessionData['stuview'] = $params['stuview'];
                    $this->writesessiondata($sessionData,$sessionId);
                }
                if (isset($params['teachview'])) {
                    unset($sessionData['stuview']);
                    $this->writesessiondata($sessionData,$sessionId);
                }
                if (isset($sessionData['stuview'])) {
                    $previewShift = $sessionData['stuview'];
                    unset($teacherId);

                    $isStudent = $teacherData['id'];
                }
            } else {
                $isTutor = $teacherData['id'];
            }
        }

        $isTutor = $this->isTutor($userId, $courseId);
        $body = "";
        $from = $this->getParamVal('from');
        $to = $this->getParamVal('to');
        $toggleNewFlag = $this->getParamVal('togglenewflag');
        $quickView = $this->getParamVal('quickview');
        $folder = $this->getParamVal('folder');
        $course = Course::getById($courseId);
        $courseNewFlag = $course['newflag'];
        $courseName = $course['name'];
        $parent = AppConstant::NUMERIC_ZERO;
//        $previewShift = -1;
        $previewshift = $this->getParamVal('stuview');
        $useviewButtons = false;
        $student = Student::getByCId($courseId);
        $line  = Student::getStudentData($userId, $courseId);
        if ($line != null) {
            $isLocked = $this->isLocked($userId, $courseId);
            if($isLocked){
                $this->setErrorFlash(AppConstant::ERROR_MSG_FOR_LOCLKED_STUDENT);
                return $this->redirect(Yii::$app->getHomeUrl());
            }
            $studentId = $line['id'];
            $studentInfo['timelimitmult'] = $line['timelimitmult'];
            $studentInfo['section'] = $line['section'];
        }
        if (!isset($teacherId) && !isset($isTutor) && !isset($isStudent))
        {
            /*
             * loaded by a NON-teacher
             */
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = _("You are not enrolled in this course.  Please return to the <a href=\"#\">Home Page</a> and enroll\n");
        } else {
            /*
             * PERMISSIONS ARE OK, PROCEED WITH PROCESSING
             */
            if (($teacherId) && ($sessionData['sessiontestid']) && !($sessionData['actas']))
            {
                /*
                 * clean up coming out of an assessment
                 */
                filehandler::deleteasidfilesbyquery2('id',$sessionData['sessiontestid'],null,1);
                $sessionTestId = $sessionData['sessiontestid'];
                AssessmentSession::deleteId($sessionTestId);
            }

            if (isset($teacherId) && ($from) && ($to)) {
                $block = $this->getParamVal('block');
                $result = $course->itemorder;
                $items = unserialize($result);
                $blockTree = explode('-',$block);
                $sub =& $items;

                for($i = 1; $i < count($blockTree)-1; $i++)
                {
                    /*
                     * -1 to adjust for 1-indexing
                     */
                    $sub =& $sub[$blockTree[$i]-1]['items'];

                }
                if (count($blockTree) > 1)
                {
                    $curBlock =& $sub[$blockTree[$i]-1]['items'];
                    $blockLoc = $blockTree[$i]-1;
                } else {
                    $curBlock =& $sub;
                }

                $blockLoc = $blockTree[count($blockTree)-1]-1;

                if (strpos($to,'-')!==false)
                {
                    /*
                     * in or out of block
                     */
                    if ($to[0]=='O')
                    {
                        /*
                         * out of block
                         * +3 to adjust for other block params
                         */
                        $itemToMove = $curBlock[$from-1];
                        array_splice($curBlock, $from-1, 1);
                        if (is_array($itemToMove)) {
                            array_splice($sub,$blockLoc+1, 0, array($itemToMove));
                        } else {
                            array_splice($sub,$blockLoc+1, 0, $itemToMove);
                        }
                    } else {
                        /*
                         * in to block
                         * -1 to adjust for 0 indexing vs 1 indexing
                         */
                        $itemToMove = $curBlock[$from-1];
                        array_splice($curBlock,$from-1, 1);
                        $to = substr($to, 2);
                        if ($from<$to)
                        {
                            $adj = AppConstant::NUMERIC_ONE;
                        } else {
                            $adj = AppConstant::NUMERIC_ZERO;
                        }
                        array_push($curBlock[$to-1-$adj]['items'],$itemToMove);
                    }
                } else {
                    /*
                     * move inside block
                     * -1 to adjust for 0 indexing vs 1 indexing
                     */
                    $itemToMove = $curBlock[$from-1];
                    array_splice($curBlock, $from-1, 1);
                    if (is_array($itemToMove)) {
                        array_splice($curBlock, $to-1, 0, array($itemToMove));
                    } else {
                        array_splice($curBlock, $to-1, 0, $itemToMove);
                    }
                }
                $itemList = serialize($items);
                Course::setItemOrder($itemList, $courseId);
                return $this->redirect('course?cid='.$courseId);
            }

            $line = Course::getCourseDataById($courseId);
            if ($line == null) {
                $this->setWarningFlash("Course does not exist");
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid='.$courseId));
            }

            $allowUnEnroll = $line['allowunenroll'];
            $hideIcons = $line['hideicons'];
            $graphicalIcons = ($line['picicons']==1);
            $pageTitle = $line['name'];
            $items = unserialize($line['itemorder']);
            $msgSet = $line['msgset']%5;
            $toolSet = $line['toolset'];
            $chatSet = $line['chatset'];
            $latePassHrs = $line['latepasshrs'];
            $useLeftBar = (($line['cploc']&1)==1);
            $useLeftStuBar = (($line['cploc']&2)==2);
            $useViewButtons = (($line['cploc']&4)==4);
            $topBar = explode('|',$line['topbar']);
            $topBar[0] = explode(',',$topBar[0]);
            $topBar[1] = explode(',',$topBar[1]);
            if (!($topBar[2]))
            {
                $topBar[2] = AppConstant::NUMERIC_ZERO;
            }

            if ($topBar[0][0] == null)
            {
                unset($topBar[0][0]);
            }
            if ($topBar[1][0] == null)
            {
                unset($topBar[1][0]);
            }

            if (($teacherId) && ($toggleNewFlag))
            {
                /*
                 * handle toggle of NewFlag
                 */
                $sub =& $items;
                $blockTree = explode('-',$toggleNewFlag);
                if (count($blockTree) > 1) {
                    for ($i = 1; $i < count($blockTree)-1; $i++)
                    {
                        /*
                         * -1 to adjust for 1-indexing
                         */
                        $sub =& $sub[$blockTree[$i]-1]['items'];
                    }
                }
                $sub =& $sub[$blockTree[$i]-1];
                if (!($sub['newflag']) || $sub['newflag'] == 0)
                {
                    $sub['newflag'] = AppConstant::NUMERIC_ONE;
                } else {
                    $sub['newflag'] = AppConstant::NUMERIC_ZERO;
                }
                $itemList =  serialize($items);
                Course::setItemOrder($itemList, $courseId);
            }

            /*
             * enable teacher guest access
             */

            if ((!isset($folder) || $folder == '') && !isset($sessionData['folder'.$courseId])) {
                $folder = '0';
                $sessionData['folder'.$courseId] = '0';
            } else if ((isset($folder) && $folder != '') && (!isset($sessionData['folder'.$courseId]) || $sessionData['folder'.$courseId] != $folder)) {
                $sessionData['folder'.$courseId] = $folder;
            } else if ((!isset($folder) || $folder == '') && isset($sessionData['folder'.$courseId])) {
                $folder = $sessionData['folder'.$courseId];
            }

            if (!($quickView) && !($sessionData['quickview'.$courseId])) {
                $quickView = false;
            } else if ($quickView) {
                $quickView = ($quickView);
                $sessionData['quickview'.$courseId] = $quickView;
            } else if (($sessionData['quickview'.$courseId])) {
                $quickView = $sessionData['quickview'.$courseId];
            }

            if ($quickView == "on") {
                $folder = '0';
            }
            if (($sessionData['ltiitemtype']) && $sessionData['ltiitemtype'] == 3)
            {
                if ($sessionData['lti_keytype'] != 'cc-of') {
                    $useLeftBar = false;
                    $useLeftStuBar = false;
                }
                $noCourseNav = true;
                $usernameInHeader = false;
            }
            /*
             * get exceptions
             */
            $now = time() + $previewShift;
            $exceptions = array();
            if (!($teacherId) && !($isTutor)) {
                $result = Exceptions::getItemData($userId);
                if($result > 0) {
                    foreach ($result as $key => $line) {
                        $exceptions[$line['id']] = array($line['startdate'], $line['enddate'], $line['islatepass'], $line['waivereqscore']);
                    }
                }

                foreach($result as $key => $line){
                    $exceptions[$line['id']] = array($line['startdate'],$line['enddate'],$line['islatepass'],$line['waivereqscore']);
                }
            }

            /*
             * update block start/end dates to show blocks containing items with exceptions
             */
//            if (count($exceptions) > 0) {
//                upsendexceptions($items);
//            }

            if ($folder != '0')
            {
                $now = time() + $previewShift;
                $blockTree = explode('-',$folder);
                $backTrack = array();
                for ($i = 1; $i < count($blockTree); $i++)
                {
                    $backTrack[] = array($items[$blockTree[$i]-1]['name'],implode('-',array_slice($blockTree, 0, $i+1)));
                    if (!($teacherId) && !($isTutor) && $items[$blockTree[$i]-1]['avail'] < 2 && $items[$blockTree[$i]-1]['SH'][0]!='S' &&($now < $items[$blockTree[$i]-1]['startdate'] || $now > $items[$blockTree[$i]-1]['enddate'] || $items[$blockTree[$i]-1]['avail'] == '0'))
                    {
                        $folder = AppConstant::NUMERIC_ZERO;
                        $items = unserialize($line['itemorder']);
                        unset($backTrack);
                        unset($blockTree);
                        break;
                    }
                    if (($items[$blockTree[$i]-1]['grouplimit']) && count($items[$blockTree[$i]-1]['grouplimit']) > 0 && !($teacherId) && !($isTutor))
                    {
                        if (!in_array('s-'.$studentInfo['section'],$items[$blockTree[$i]-1]['grouplimit'])) {
                            $this->setWarningFlash("Not authorized");
                            return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid='.$courseId));
                        }
                    }
                    /*
                     * -1 to adjust for 1-indexing
                     */
                    $items = $items[$blockTree[$i]-1]['items'];
                }
            }
            //DEFAULT DISPLAY PROCESSING
            $jsAddress1 = AppUtility::getURLFromHome('course','course/course?cid=' .$courseId);
            $jsAddress2 = AppUtility::getHomeURL();

            $openBlocks = Array(0);
            $prevLoadedbLocks = array(0);
            if (isset($_COOKIE['openblocks-'.$courseId]) && $_COOKIE['openblocks-'.$courseId]!='')
            {
                $openBlocks = explode(',',$_COOKIE['openblocks-'.$courseId]);
                $firstLoad = false;
            } else
            {
                $firstLoad = true;
            }
            if (($_COOKIE['prevloadedblocks-'.$courseId]) && $_COOKIE['prevloadedblocks-'.$courseId]!='')
            {
                $prevLoadedbLocks = explode(',',$_COOKIE['prevloadedblocks-'.$courseId]);
            }
            $plbList = implode(',',$prevLoadedbLocks);
            $obList = implode(',',$openBlocks);

            $curBreadcrumb = '';
            if (($backTrack) && count($backTrack) > 0)
            {
                if (($sessionData['ltiitemtype']) && $sessionData['ltiitemtype'] == 3)
                {
                    $curBreadcrumb = '';
                    $sendcrumb = '';
                    $depth = substr_count($sessionData['ltiitemid'][1],'-');
                    for ($i = $depth-1; $i < count($backTrack); $i++)
                    {
                        if ($i > $depth-1)
                        {
                            $curBreadcrumb .= " &gt; ";
                            $sendcrumb .= " &gt; ";
                        }
                        if ($i != count($backTrack)-1)
                        {
                            $curBreadcrumb .= "<a href=\"course?cid=$courseId&folder={$backTrack[$i][1]}\" style='color: #ffffff;'>";
                        }
                        $sendcrumb .= "<a href=\"course?cid=$courseId&folder={$backTrack[$i][1]}\">".stripslashes($backTrack[$i][0]).'</a>';
                        $curBreadcrumb .= stripslashes($backTrack[$i][0]);
                        if ($i != count($backTrack)-1)
                        {
                            $curBreadcrumb .= "</a>";
                        }
                    }
                    $curName = $backTrack[count($backTrack)-1][0];

                    if (count($backTrack) > $depth)
                    {
                        $backLink = "<span class='right back-link'><a href=\"course?cid=$courseId&folder=".$backTrack[count($backTrack)-2][1]."\">" . _('Back') . "</a></span><br class=\"form\" />";
                    }
                    $_SESSION['backtrack'] = array($sendcrumb,$backTrack[count($backTrack)-1][1]);
                } else {
                    $curBreadcrumb .= "<a href=\"course?cid=$courseId&folder=0\" style='color: #ffffff;'>$courseName</a> ";
                    for ($i = 0; $i < count($backTrack); $i++)
                    {
                        $curBreadcrumb .= " &gt; ";
                        if ($i!=count($backTrack)-1)
                        {
                            $curBreadcrumb .= "<a href=\"course?cid=$courseId&folder={$backTrack[$i][1]}\" style='color: #ffffff;'>";
                        }
                        $curBreadcrumb .= stripslashes($backTrack[$i][0]);
                        if ($i != count($backTrack)-1)
                        {
                            $curBreadcrumb .= "</a>";
                        }
                    }
                    $curName = $backTrack[count($backTrack)-1][0];
                    if (count($backTrack) == 1)
                    {
                        $backLink =  "<span class='right back-link'><a href=\"course?cid=$courseId&folder=0\">" . _('Back') . "</a></span><br class=\"form\" />";
                    } else {
                        $backLink = "<spanclass='right back-link'><a href=\"course?cid=$courseId&folder=".$backTrack[count($backTrack)-2][1]."\">" . _('Back') . "</a></span><br class=\"form\" />";
                    }
                }
            } else {
                $curBreadcrumb .= $courseName;
                $curName = ucfirst($courseName);
            }

            if ($msgSet < 4)
            {
                $result = Message::getCountOfId($userId, $courseId);
                $msgCnt = $result[0]['id'];
                if ($msgCnt > 0)
                {
                    $newMsgs = " <a href=\"#\" style=\"color:red\">" . sprintf(_('New (%d)'), $msgCnt) . "</a>";
                } else {
                    $newMsgs = '';
                }
            }
            $now = time();
            $result = ForumThread::getDataByUserId($teacherId, $courseId, $userId, $now);
            $newPostCnts = array();
            foreach($result as $key => $row) {
                $newPostCnts[$row['forumid']] = $row['COUNT(imas_forum_threads.id)'];
            }
            if (array_sum($newPostCnts) > 0)
            {
                $newPostsCnt = " <a href=\"#\" style=\"color:red\">" . sprintf(_('New (%d)'), array_sum($newPostCnts)) . "</a>";
            } else {
                $newPostsCnt = '';
            }

            /**
             *  get items with content views, for enabling stats link
             */
            if (($teacherId) || ($isTutor)) {
                $hasStats = array();

                $result = ContentTrack::getStatsData($courseId);
                foreach($result as $key => $row)
                {

                    $hasStats[$row['(CONCAT(SUBSTRING(type,1,1),typeid))']] = true;
                }
            }

            /*
             * get latepasses
             */
            if (!($teacherId) && !($isTutor) && $previewShift == -1)
            {
                $result = Student::getLatePassById($userId, $courseId);
                $latePasses = $result[0]['latepass'];
            } else {
                $latePasses = AppConstant::NUMERIC_ZERO;
            }
        }
        if (isset($courseData['tb'])) {
            $filter = $courseData['tb'];
        } else {
            $filter = 'b';
        }

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
            return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' .$course->id.'&folder=0'));
        }

        $this->includeCSS(['fullcalendar.min.css', 'calendar.css', 'jquery-ui.css','course/course.css', 'instructor.css']);
        $this->includeJS(['moment.min.js','fullcalendar.min.js','course.js','student.js', 'general.js', 'question/addquestions.js', 'mootools.js', 'nested1.js','course/instructor.js']);
        $responseData = array('teacherId' => $teacherId, 'course' => $course,'courseId' => $courseId, 'usernameInHeader' => $usernameInHeader, 'useLeftBar' => $useLeftBar, 'newMsgs' => $newMsgs, 'newPostCnts' => $newPostCnts, 'useViewButtons' => $useViewButtons, 'useLeftStuBar' => $useLeftStuBar, 'toolSet' => $toolSet, 'sessionData' => $sessionData, 'allowUnEnroll' => $allowUnEnroll, 'quickView' => $quickView, 'noCourseNav' => $noCourseNav, 'overwriteBody' => $overwriteBody, 'body' => $body, 'myRights' => $myRights,
        'items' => $items, 'folder' => $folder, 'parent' => $parent, 'firstLoad' => $firstLoad, 'jsAddress1' => $jsAddress1, 'jsAddress2' => $jsAddress2, 'curName' => $curName, 'curBreadcrumb' => $curBreadcrumb, 'isStudent' => $isStudent, 'students' => $student, 'newPostsCnt' => $newPostsCnt, 'backLink' => $backLink, 'type' => $type, 'user' => $user, 'lockAId' => $lockAId);
        return $this->renderWithData('course', $responseData);
    }

    /**
     * Get block items
     */
    public function actionGetBlockItems()
    {
        /**
         * Can access: greater than equal student
         *  1. Full Admin
         *  2. Group Admin
         *  3. Diagnostics
         *  4. Limited Course Creator.
         *  5. Teacher
         *  6. Student
         */
        global $teacherId,$isTutor,$isStudent,$courseId,$imasroot,$userId,$openBlocks,$firstLoad,$sessionData,$previewShift,$myRights,
               $hideIcons,$exceptions,$latePasses,$graphicalIcons,$isPublic,
               $studentInfo,$newPostCnts,$CFG,$latePassHrs,$hasStats,$toolSet,$readLinkedItems, $haveCalcedViewedAssess, $viewedAssess;
        $user = $this->user;
        $userId = $user['id'];
        $courseId = $this->getParamVal('cid');
        $teacherId = $this->isTeacher($userId, $courseId);
        $isTutor = $this->isTutor($userId, $courseId);
        $isStudent = $this->isStudent($userId, $courseId);
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        $folder = $this->getParamVal('folder');
        $previewShift = -1;

        if (!($teacherId) && !($isTutor) && !($isStudent)) {
            $this->setWarningFlash("You are not enrolled in this course.");
            return $this->redirect(AppUtility::getHomeURL());
        }

        $line = Course::getCourseDataById($courseId);
        if ($line == null) {
            $this->setWarningFlash("Course does not exist");
            return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid='.$courseId));
        }

        $allowUnEnroll = $line['allowunenroll'];
        $hideIcons = $line['hideicons'];
        $graphicalIcons = ($line['picicons']==1);
        $pageTitle = $line['name'];
        $items = unserialize($line['itemorder']);
        $msgSet = $line['msgset']%5;
        $latePassHrs = $line['latepasshrs'];
        $useLeftBar = ($line['cploc']==1);
        $topBar = explode('|',$line['topbar']);
        $toolSet = $line['toolset'];
        $topBar[0] = explode(',',$topBar[0]);
        $topBar[1] = explode(',',$topBar[1]);
        if ($topBar[0][0] == null) {unset($topBar[0][0]);}
        if ($topBar[1][0] == null) {unset($topBar[1][0]);}

        $now = time() + $previewShift;
        $exceptions = array();

        if (!($teacherId) && !($isTutor)) {
            $result = Exceptions::getExceptionDataLatePass($userId);
            foreach($result as $key => $line)
            {
               $exceptions[$line['id']] = array($line['startdate'],$line['enddate'],$line['islatepass'],$line['waivereqscore']);
            }
        }
            if (count($exceptions)>0) {
//                upsendexceptions($items);
            }

            if (strpos($folder,'-') !== false)
            {
                $now = time() + $previewShift;
                $blockTree = explode('-',$folder);
                $backTrack = array();
                for ($i = 1; $i < count($blockTree); $i++)
                {
                    $backTrack[] = array($items[$blockTree[$i]-1]['name'],implode('-',array_slice($blockTree,0,$i+1)));

                    if (!($teacherId) && !($isTutor) && $items[$blockTree[$i]-1]['avail'] < 2 && $items[$blockTree[$i]-1]['SH'][0] != 'S' &&($now<$items[$blockTree[$i]-1]['startdate'] || $now>$items[$blockTree[$i]-1]['enddate'] || $items[$blockTree[$i]-1]['avail']=='0'))
                    {
                        $folder = 0;
                        $items = unserialize($line['itemorder']);
                        unset($backTrack);
                        unset($blockTree);
                        break;
                    }
                    $items = $items[$blockTree[$i]-1]['items']; //-1 to adjust for 1-indexing
                }
            }

            $openBlocks = Array(0);
//            if (isset($_COOKIE['openblocks-'.$courseId]) && $_COOKIE['openblocks-'.$courseId]!='')
//            {
//                $openBlocks = explode(',',$_COOKIE['openblocks-'.$courseId]);}
//            if (isset($_COOKIE['prevloadedblocks-'.$courseId]) && $_COOKIE['prevloadedblocks-'.$courseId]!='')
//            {
//                $prevLoadedBlocks = explode(',',$_COOKIE['prevloadedblocks-'.$courseId]);
//            } else {
//                $prevLoadedBlocks = array();
//            }
//            if (in_array($folder,$prevLoadedBlocks))
//            {
//                $firstLoad = false;
//            } else
//            {
//                $firstLoad = true;
//            }

            if (!($teacherId) && !($isTutor) && $previewShift == -1)
            {
                $result = Student::getLatePassById($userId, $courseId);
                $latePasses = $result[0]['latepass'];
            } else {
                $latePasses = 0;
            }
            /*
             * get new forum posts info
             */
            $result = ForumThread::getDataByUserId($teacherId, $courseId, $userId, $now);
            $newPostCnts = array();
            foreach($result as $key => $row) {
                $newPostCnts[$row['forumid']] = $row['id'];
            }
            /*
             * get items with content views, for enabling stats link
             */
            if (($teacherId) || ($$isTutor)) {
                $hasStats = array();
                $result = ContentTrack::getStatsData($courseId);
                foreach($result as $key => $row) {
                    $hasStats[$row['typeid']] = true;
                }
            }
        if (count($items) > 0) {
                /*
                 * update block start/end dates to show blocks containing items with exceptions
                 */
                $showItems = new ShowItemCourse();
                $showItems->showItems($items,$folder);
            } else if ($teacherId) {
                $courseData = new ShowItemCourse();
             }
            $this->includeJS(['course.js']);

            $responseData = array('items' => $items, 'folder' => $folder, 'isTutor' => $isTutor, 'sessionData' => $sessionData, 'teacherId' => $teacherId, 'courseData' => $courseData);
        return $this->renderWithData('getBlockItems', $responseData);
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
        global $outComes;
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
        /**
         * Ajax
         */
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

    /*
     * Ajax method to delete course items
     */
    public function actionDeleteItemsAjax()
    {
        $params = $this->getRequestParams();
        $user = $this->user;
        $courseId = $params['courseId'];
        $cid = $params['cid'];
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
                    if (is_array($sub[$blockId]))
                    {
                        $blockItems = $sub[$blockId]['items'];
                        $obId = $sub[$blockId]['id'];
                        if (count($blockItems)>AppConstant::NUMERIC_ZERO)
                        {
                            if(isset($params['selected']) && $params['selected'] == AppConstant::NUMERIC_ONE)
                            {
                                $this->deleteRecursive($blockItems);
                                array_splice($sub,$blockId,AppConstant::NUMERIC_ONE);
                            }else
                            {
                                array_splice($sub,$blockId,AppConstant::NUMERIC_ONE,$blockItems);
                            }

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

    public function actionPublic()
    {
        global $teacherId,$courseId,$userId,$openBlocks,$firstLoad,$previewShift,
               $hideIcons,$graphicalIcons,$isPublic;
        $user = $this->user;
        $userId = $user['id'];
        $this->layout = 'nonLoggedUser';
        $isPublic = true;
        $courseId = $this->getParamVal('cid');
        $folder = $this->getParamVal('folder');
        $line = Course::getDataPublicly($courseId);
        $teacherId = $this->isTeacher($userId, $courseId);

        if($line != null){
            $courseName = $line['name'];
            $hideIcons = $line['hideicons'];
            $graphicalIcons = ($line['picicons']==1);
            $pageTitle = $line['name'];
            $items = unserialize($line['itemorder']);
            $breadCrumbBase = "<a href=\"public?cid=$courseId\">$courseName</a>";

            if (!isset($folder) || $folder =='') {
                $folder = '0';
            }
            $blockIsPublic = false;
            if ($folder != '0') {
                $now = time();
                $blocktree = explode('-',$folder);
                $backtrack = array();
                for ($i = 1; $i < count($blocktree); $i++) {
                    $backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
                    if ($items[$blocktree[$i]-1]['public']==1) {
                        $blockIsPublic = true;
                    }
                    if (!isset($teacherId) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
                        $folder = 0;
                        $items = unserialize($line['itemorder']);
                        unset($backtrack);
                        unset($blocktree);
                        break;
                    }
                    $items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
                }
                if (!$blockIsPublic) {
                    $folder = 0;
                    $items = unserialize($line['itemorder']);
                    unset($backtrack);
                    unset($blocktree);
//			break;
                }
            }

            $openBlocks = Array(0);
            $prevloadedblocks = array(0);
            if (isset($_COOKIE['openblocks-'.$courseId]) && $_COOKIE['openblocks-'.$courseId]!='')
            {

                $openBlocks = explode(',',$_COOKIE['openblocks-'.$courseId]);
                $firstLoad = false;
            } else {
                $firstLoad = true;
            }
            if (isset($_COOKIE['prevloadedblocks-'.$courseId]) && $_COOKIE['prevloadedblocks-'.$courseId]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$courseId]);}
            $plblist = implode(',',$prevloadedblocks);
            $oblist = implode(',',$openBlocks);

            $curBreadcrumb = $breadCrumbBase;

            if (isset($backtrack) && count($backtrack)>0) {
                $curBreadcrumb .= "<a href=\"public?cid=$courseId&folder=0\">$courseName</a> ";
                for ($i = 0; $i < count($backtrack); $i++) {
                    $curBreadcrumb .= "&gt; ";
                    if ($i!=count($backtrack)-1) {
                        $curBreadcrumb .= "<a href=\"public?cid=$courseId&folder={$backtrack[$i][1]}\">";
                    }
                    $curBreadcrumb .= stripslashes($backtrack[$i][0]);
                    if ($i!=count($backtrack)-1) {
                        $curBreadcrumb .= "</a>";
                    }
                }
                $curname = $backtrack[count($backtrack)-1][0];
                if (count($backtrack)==1) {
                    $backlink =  "<span class=right><a href=\"public?cid=$courseId&folder=0\">Back</a></span><br class=\"form\" />";
                } else {
                    $backlink = "<span class=right><a href=\"public?cid=$courseId&folder=".$backtrack[count($backtrack)-2][1]."\">Back</a></span><br class=\"form\" />";
                }
            } else {
                $curBreadcrumb .= $courseName;
                $curname = $courseName;
            }
        }
        $responseData = array('curname' => $curname, 'curBreadcrumb' => $curBreadcrumb, 'items' => $items, 'blockIsPublic' => $blockIsPublic, 'oblist' => $oblist, 'plblist' => $plblist, 'cid' => $courseId, 'pageTitle' => $pageTitle);
        return $this->renderWithData('public', $responseData);
    }

    public function actionGetBlockItemsPublic()
    {
        global $teacherId,$courseId,$userId,$openBlocks,$firstLoad,$previewShift,
               $hideIcons,$graphicalIcons,$isPublic;
        $user = $this->user;
        $userId = $user['id'];
        $isPublic = true;

        $courseId = $this->getParamVal('cid');
        $folder = $this->getParamVal('folder');
        $line = Course::getDataPubliclyForBlock($courseId);
        $teacherId = $this->isTeacher($userId, $courseId);
        $hideIcons = $line['hideicons'];
        $graphicalIcons = ($line['picicons']==1);
        $pageTitle = $line['name'];
        $items = unserialize($line['itemorder']);

        $blockIsPublic = false;

        if (strpos($folder,'-') !== false) {

            $now = time();
            $blocktree = explode('-',$folder);
            $backtrack = array();
            for ($i = 1;$i < count($blocktree); $i++) {
                $backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
                if ($items[$blocktree[$i]-1]['public']==1) {
                    $blockIsPublic = true;
                }
                if (!isset($teacherId) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
                    $folder = 0;
                    $items = unserialize($line['itemorder']);
                    unset($backtrack);
                    unset($blocktree);
                    break;
                }
                $items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
            }
        }
        if (!$blockIsPublic) {
            $this->setErrorFlash("Content not public");
            return $this->redirect($this->goHome());
        }

        $openBlocks = Array(0);
        if (isset($_COOKIE['openblocks-'.$courseId]) && $_COOKIE['openblocks-'.$courseId]!='') {
            $openBlocks = explode(',',$_COOKIE['openblocks-'.$courseId]);
        }
        if (isset($_COOKIE['prevloadedblocks-'.$courseId]) && $_COOKIE['prevloadedblocks-'.$courseId]!='') {
            $prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$courseId]);
        } else {
            $prevloadedblocks = array();
        }
        if (in_array($_GET['folder'],$prevloadedblocks)) {
            $firstLoad = false;
        } else {
            $firstLoad = true;
        }

        if (count($items) > 0) {
            $courseData = new ShowItemCourse();
            $courseData->showitems($items,$folder,$blockIsPublic);
        }
        $responseData = array('firstLoad' => $firstLoad, 'courseId' => $courseId);
        return $this->renderWithData('getBlockItemsPublic',$responseData);
    }

    public function actionShowLinkedTextPublic()
    {
        global $isPublic, $courseId;
        $user = $this->user;
        $this->layout = 'nonLoggedUser';
        $userId = $user['id'];
        $courseId = intval($this->getParamVal('cid'));
        $from = $this->getParamVal('from');
        $id = $this->getParamVal('id');

        if (!isset($courseId)) {
            $this->setWarningFlash("Need course id.");
            return $this->redirect(AppUtility::getHomeURL());
        }

        if (isset($from)) {
            $publicCid = $courseId;  //swap out cid's before calling validate
            $cid = intval($from);
            $courseId = intval($from);
            $fcid = $cid;
            $courseId = $publicCid;
        }  else if (preg_match('/cid=(\d+)/',$_SERVER['HTTP_REFERER'],$matches) && $matches[1] != $courseId) {
            $publicCid = $courseId;  //swap out cid's before calling validate
            $courseId = intval($matches[1]);
            $courseId = intval($matches[1]);
            $fcid = $courseId;
            $courseId = $publicCid;
        } else {
            $fcid = 0;
        }

        $itemId = Items::getByItemTypeAndId($id);
        $courseData = Course::getIdPublicly($courseId);

         $items = unserialize($courseData['itemorder']);

        $courseName = $courseData['name'];
        if ($fcid == 0) {
            $breadcrumbbase = "<a href=\"public?cid=$cid\">$courseName</a> &gt; ";
        }  else {
            $breadcrumbbase = "$breadcrumbbase <a href=\"course?cid=$fcid\">$courseName</a> &gt; ";
        }

        if (!$this->findinpublic($items,$itemId)) {
            $this->setWarningFlash("This page does not appear to be publically accessible.  Please return to the Home Page and try logging in.\n");
            return $this->redirect(Yii::$app->getHomeUrl());
        }
        $isPublic = true;

        if (!isset($id)) {
            $this->setErrorFlash("<html><body>No item specified.</body></html>\n");
            return $this->redirect('show-linked-text-public?cid='.$courseId);
        }

        $linkData = LinkedText::getLinkedDataPublicly($id);
        $text = $linkData['text'];
        $title = $linkData['title'];
        $titlesImp = strip_tags($title);

        $responseData = array('titlesImp' => $titlesImp, 'text' => $text, 'fcid' => $fcid, 'courseId' => $courseId, 'from' => $from, 'breadcrumbbase' => $breadcrumbbase);
        return $this->renderWithData('showLinkedTextPublic', $responseData);
    }

    public function actionSaveQuickReorder()
    {
        global $items,$courseId,$newItems,$courseDetail,$openblocks,$previewShift;
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $order = $_POST['order'];
        $previewShift = -1;
        foreach ($_POST as $id => $val)
        {
            if ($id=="order" || $id == 'eIdentity')
            {
                continue;
            }
            $type = $id{0};
            $typeId = substr($id,1);
            if ($type=="I") {
                $query = new InlineText();
                $query->updateName($val, $typeId);
            } else if ($type=="L") {
                $query = new LinkedText();
                $query->updateName($val,$typeId);
            } else if ($type=="A") {
                $query = new Assessments();
                $query->updateName($val, $typeId);
            } else if ($type=="F") {
                $query = new Forums();
                $query->updateName($val, $typeId);
            } else if ($type=="W") {
                $query = new Wiki();
                $query->updateName($val, $typeId);
            } else if ($type=="B"){
                $query = Course::getItemOrder($courseId);
                $itemsforblock = unserialize($query['itemorder']);
                $blocktree = explode('-',$typeId);
                $existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
                $sub =& $itemsforblock;
                if (count($blocktree)>1) {
                    for ($i=1;$i<count($blocktree);$i++) {
                        $sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
                    }
                }
                $sub[$existingid]['name'] = stripslashes($val);
                $itemOrder =  serialize($itemsforblock);
                $query = new Course();
                $query->setItemOrder($itemOrder, $courseId);
            }
        }
        $query = Course::getItemOrder($courseId);
        $items = unserialize($query['itemorder']);
        $ok = '';
        $newItems = array();
        $newItems = $this->additems($order);
        $ok .= "OK";
        $itemList =  serialize($newItems);

        $query = new Course();
        $query->setItemOrder($itemList, $courseId);

        $openblocks = Array(0);
        $prevloadedblocks = array(0);
        if (isset($_COOKIE['openblocks-'.$courseId]) && $_COOKIE['openblocks-'.$courseId]!='')
        {
            $openblocks = explode(',',$_COOKIE['openblocks-'.$courseId]);
            $firstload=false;
        } else {
            $firstload=true;
        }
        if (isset($_COOKIE['prevloadedblocks-'.$courseId]) && $_COOKIE['prevloadedblocks-'.$courseId]!='')
        {
            $prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$courseId]);
        }
        $plblist = implode(',',$prevloadedblocks);
        $oblist = implode(',',$openblocks);

        $quickView = new AppUtility();
        $quickView->quickview($newItems,$courseDetail=false,0);
        $responseData = array('ok' => $ok);
        return $this->renderWithData('saveQuickReorder', $responseData);
    }

    public function additems($list) {
        global $items;
        $outarr = array();
        $list = substr($list,1,-1);
        $i = 0; $nd = 0; $last = 0;
        $listarr = array();
        while ($i<strlen($list)) {
            if ($list[$i]=='[') {
                $nd++;
            } else if($list[$i]==']') {
                $nd--;
            } else if ($list[$i]==',' && $nd==0) {
                $listarr[] = substr($list,$last,$i-$last);
                $last = $i+1;
            }
            $i++;
        }
        $listarr[] = substr($list,$last);
        foreach ($listarr as $it) {
            if (strpos($it,'-')!==false) { //is block
                $pos = strpos($it,':');
                if ($pos===false) {
                    $pts[0] = $it;
                } else {
                    $pts[0] = substr($it,0,$pos);
                    $pts[1] = substr($it,$pos+1);
                }
                $blocktree = explode('-',$pts[0]);
                $sub = $items;
                for ($i=1;$i<count($blocktree)-1;$i++) {
                    $sub = $sub[$blocktree[$i]-1]['items'];
                }
                $block = $sub[$blocktree[count($blocktree)-1]-1];

                if ($pos===false) {
                    $block['items'] = array();
                } else {
                    $subarr = $this->additems($pts[1]);
                    $block['items'] = $subarr;
                }
                $outarr[] = $block;
            } else { //regular item
                $outarr[] = $it;
            }

        }
        return $outarr;
    }

    function findinpublic($items,$id) {
        if($items)
        {
            foreach ($items as $k=>$item) {
                if (is_array($item)) {
                    if ($item['public']==1) {
                        if ($this->finditeminblock($item['items'],$id)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    function finditeminblock($items,$id) {
        foreach ($items as $k=>$item) {
            if (is_array($item)) {
                if ($this->finditeminblock($item['items'],$id)) {
                    return true;
                }
            } else {
                if ($item==$id) {
                    return true;
                }
            }
        }
        return false;
    }
}