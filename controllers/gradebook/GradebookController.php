<?php

namespace app\controllers\gradebook;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\ContentTrack;
use app\models\Course;
use app\models\Diags;
use app\models\Exceptions;
use app\models\forms\AddGradesForm;
use app\models\forms\AddRubricForm;
use app\models\forms\ManageTutorsForm;
use app\models\forms\UploadCommentsForm;
use app\models\Forums;
use app\models\GbCats;
use app\models\GbItems;
use app\models\GbScheme;
use app\models\Grades;
use app\models\Items;
use app\models\LinkedText;
use app\models\LoginLog;
use app\models\loginTime;
use app\models\Message;
use app\models\Outcomes;
use app\models\Rubrics;
use app\models\Questions;
use app\models\Student;
use app\models\StuGroupSet;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use Yii;
use yii\web\UploadedFile;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use yii\rbac\Item;

class GradebookController extends AppController
{
    public $a;

    public function getpts($sc)
    {
        if (strpos($sc, '~') === false) {
            if ($sc > 0) {
                return $sc;
            } else {
                return 0;
            }
        } else {
            $sc = explode('~', $sc);
            $tot = 0;
            foreach ($sc as $s) {
                if ($s > 0) {
                    $tot += $s;
                }
            }
            return round($tot, 1);
        }
    }

    public function actionGradebook()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $countPost = $this->getNotificationDataForum($courseId,$user);
        $msgList = $this->getNotificationDataMessage($courseId,$user);
        $this->setSessionData('messageCount',$msgList);
        $this->setSessionData('postCount',$countPost);
        $course = Course::getById($courseId);
        $gradebookData = $this->gbtable($user->id, $courseId);
        $this->includeCSS(['jquery.dataTables.css']);
        $this->includeJS(['gradebook/gradebook.js', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        $responseData = array('course' => $course, 'user' => $user, 'gradebook' => $gradebookData['gradebook'], 'data' => $gradebookData);
        $this->includeJS(['general.js']);
        $this->includeCSS(['course/course.css']);
        return $this->renderWithData('gradebook', $responseData);

    }

    public function gbtable($userId, $courseId, $studentId = null)
    {

        $teacherid = Teacher::getByUserId($userId, $courseId);
        $tutorid = Tutor::getByUserId($userId, $courseId);
        $tutorsection = trim($tutorid->section);
        $sectionQuery = Student::findDistinctSection($courseId);

        $sections = array();
        if ($sectionQuery) {
            foreach ($sectionQuery as $item) {
                array_push($sections, $item->section);
            }
        }
        if (isset($teacherid)) {
            $isteacher = true;
        }
        if ($tutorid) {
            $istutor = true;
        }
        /*
         * Assign tutor value false temparary
         */
        $istutor = false;

        if ($isteacher || $istutor) {
            $canviewall = true;

            // get from gb-testing
//            if (isset($_GET['timefilter'])) {
//                $timefilter = $_GET['timefilter'];
//                $sessiondata[$params['courseId'].'timefilter'] = $timefilter;
//                writesessiondata();
//            } else if (isset($sessiondata[$params['courseId'].'timefilter'])) {
//                $timefilter = $sessiondata[$params['courseId'].'timefilter'];
//            } else {
//                $timefilter = 2;
//            }
//            if (isset($_GET['lnfilter'])) {
//                $lnfilter = trim($_GET['lnfilter']);
//                $sessiondata[$params['courseId'].'lnfilter'] = $lnfilter;
//                writesessiondata();
//            } else if (isset($sessiondata[$params['courseId'].'lnfilter'])) {
//                $lnfilter = $sessiondata[$params['courseId'].'lnfilter'];
//            } else {
//                $lnfilter = '';
//            }
//            if (isset($tutorsection) && $tutorsection!='') {
//                $secfilter = $tutorsection;
//            } else {
//                if (isset($_GET['secfilter'])) {
//                    $secfilter = $_GET['secfilter'];
//                    $sessiondata[$params['courseId'].'secfilter'] = $secfilter;
//                    writesessiondata();
//                } else if (isset($sessiondata[$params['courseId'].'secfilter'])) {
//                    $secfilter = $sessiondata[$params['courseId'].'secfilter'];
//                } else {
//                    $secfilter = -1;
//                }
//            }

            /*
             * this is for for temparary use will set value of timefilter and lnfilter and secfilter later
             */
            $timefilter = null;
            $lnfilter = null;
            $secfilter = null;

        } else {
            $canviewall = false;
        }
        if ($canviewall) {
            $sessionId = $this->getSessionId();
            $session = $this->getSessionData($sessionId);
            $sessionData = unserialize(base64_decode($session));
            if (isset($_GET['gbmode']) && $_GET['gbmode'] != '') {
                $gbmode = $_GET['gbmode'];
                $sessionData['gbmode'] = $gbmode;
                writesessiondata();
            } else if (isset($sessionData['gbmode'])) {

                $gbmode = $sessionData['gbmode'];
            } else {
                $defgbmode = GbScheme::findOne(['courseid' => $courseId]);
                $gbmode = $defgbmode->defgbmode;
            }

            $defgbmode = GbScheme::findOne(['courseid' => $courseId]);
            $colorized = $defgbmode->colorize;
            $catfilter = -1;
//            if (isset($tutorsection) && $tutorsection != '') {
//                $secfilter = $tutorsection;
//            } else {
            $secfilter = -1;
//            }
            $overridecollapse = array();

            //Gbmode : Links NC Dates

            $showpics = floor($gbmode / 10000) % 10; //0 none, 1 small, 2 big
            $totonleft = ((floor($gbmode / 1000) % 10) & 1); //0 right, 1 left
            $avgontop = ((floor($gbmode / 1000) % 10) & 2); //0 bottom, 2 top
            $lastlogin = (((floor($gbmode / 1000) % 10) & 4) == 4); //0 hide, 2 show last login column
            $links = ((floor($gbmode / 100) % 10) & 1); //0: view/edit, 1 q breakdown
            $hidelocked = ((floor($gbmode / 100) % 10 & 2)); //0: show locked, 1: hide locked
            $includeduedate = (((floor($gbmode / 100) % 10) & 4) == 4); //0: hide due date, 4: show due date
            $hidenc = (floor($gbmode / 10) % 10) % 4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
            $includelastchange = (((floor($gbmode / 10) % 10) & 4) == 4);  //: hide last change, 4: show last change
            $availshow = $gbmode % 10; //0: past, 1 past&cur, 2 all, 3 past and attempted, 4=current only

        } else {
            $secfilter = -1;
            $catfilter = -1;
            $links = 0;
            $hidenc = 1;
            $availshow = 1;
            $showpics = 0;
            $totonleft = 0;
            $avgontop = 0;
            $hidelocked = 0;
            $lastlogin = false;
            $includeduedate = false;
            $includelastchange = false;
        }
        if ($canviewall && $studentId) {
        $stu = $studentId;
        } else {
            $stu = 0;
        }
        $isdiag = false;
        if ($canviewall) {
            $query = Diags::findOne(['cid' => $courseId]);
            if ($query) {
                $isdiag = true;
                $sel1name = $query->sel1name;
                $sel2name = $query->sel2name;
            }
        }
        if ($canviewall && func_num_args() > 2) {
            $limuser = $studentId;
        } else if (!$canviewall) {
            $limuser = $userId;
        } else {
            $limuser = 0;
        }
        if (!isset($lastlogin)) {
            $lastlogin = 0;
        }
        if (!isset($logincnt)) {
            $logincnt = 0;
        }
        $category = array();
        $gradebook = array();

        $ln = 0;
        //Pull Gradebook Scheme info
        $query = GbScheme::findOne(['courseid' => $courseId]);
        $useweights = $query->useweights;
        $orderby = $query->orderby;
        $defaultcat = $query->defaultcat;
        $usersort = $query->usersort;
        if ($useweights == 2) {
            $useweights = 0;                //use 0 mode for calculation of totals
        }
        if (isset($GLOBALS['setorderby'])) {
            $orderby = $GLOBALS['setorderby'];
        }
        //Build user ID headers

        $gradebook[0][0][0] = "Name";
        if ($isdiag) {
            $gradebook[0][0][1] = "ID";
            $gradebook[0][0][2] = "Term";
            $gradebook[0][0][3] = ucfirst($sel1name);
            $gradebook[0][0][4] = ucfirst($sel2name);
        } else {
            $gradebook[0][0][1] = "Username";
        }
        $query = Student::findByCid($courseId);
        if ($query) {
            $countSection = 0;
            $countCode = 0;
            foreach ($query as $singleData) {
                if ($singleData->section != null || $singleData->section != "") {
                    $countSection++;
                }
                if ($singleData->code != null || $singleData->code != "") {
                    $countCode++;
                }
            }
        }
        if ($countSection > 0) {
            $hassection = true;
        } else {
            $hassection = false;
        }
        if ($countCode > 0) {
            $hascode = true;
        } else {
            $hascode = false;
        }

        if ($hassection && !$isdiag) {
            $gradebook[0][0][] = "Section";
        }
        if ($hascode) {
            $gradebook[0][0][] = "Code";
        }
        if ($lastlogin) {
            $gradebook[0][0][] = "Last Login";
        }
        if ($logincnt) {
            $gradebook[0][0][] = "Login Count";
        }

        //orderby 10: course order (11 cat first), 12: course order rev (13 cat first)
        if ($orderby >= 10 && $orderby <= 13) {
            $query = Course::getById($courseId);
            if ($query) {
                $courseitemorder = unserialize($query->itemorder);
                $courseitemsimporder = array();
                function flattenitems($items, &$addto)
                {
                    foreach ($items as $item) {
                        if (is_array($item)) {
                            flattenitems($item['items'], $addto);
                        } else {
                            $addto[] = $item;
                        }
                    }
                }

                flattenitems($courseitemorder, $courseitemsimporder);
                $courseitemsimporder = array_flip($courseitemsimporder);
                $courseitemsassoc = array();
                $query = Items::getByCourseId($courseId);
                if ($query) {
                    foreach ($query as $item) {
                        if (!isset($courseitemsimporder[$item->id])) {
                            $courseitemsassoc[$item->itemtype . $item->typeid] = 999 + count($courseitemsassoc);
                        } else {
                            $courseitemsassoc[$item->itemtype . $item->typeid] = $courseitemsimporder[$item->id];
                        }
                    }
                }
            }
        }
        //Pull Assessment Info
        $now = time();
        $query = Assessments::findAllAssessmentForGradebook($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now);
        $overallpts = 0;
        $now = time();
        $kcnt = 0;
        $assessments = array();
        $grades = array();
        $discuss = array();
        $exttools = array();
        $timelimits = array();
        $minscores = array();
        $assessmenttype = array();
        $startdate = array();
        $enddate = array();
        $tutoredit = array();
        $isgroup = array();
        $avail = array();
        $sa = array();
        $category = array();
        $name = array();
        $possible = array();
        $courseorder = array();
        $allowlate = array();

        if ($query) {
            foreach ($query as $assessment) {
                $assessments[$kcnt] = $assessment['id'];
                if (isset($courseitemsassoc)) {
                    $courseorder[$kcnt] = $courseitemsassoc['Assessment' . $assessment['id']];
                }
                $timelimits[$kcnt] = $assessment['timelimit'];
                $minscores[$kcnt] = $assessment['minscore'];
                $deffeedback = explode('-', $assessment['deffeedback']);
                $assessmenttype[$kcnt] = $deffeedback[0];
                $sa[$kcnt] = $deffeedback[1];
                if ($assessment['avail'] == 2) {
                    $assessment['startdate'] = 0;
                    $assessment['enddate'] = 2000000000;
                }
                $enddate[$kcnt] = $assessment['enddate'];
                $startdate[$kcnt] = $assessment['startdate'];
                if ($now < $assessment['startdate']) {
                    $avail[$kcnt] = 2;
                } else if ($now < $assessment['enddate']) {
                    $avail[$kcnt] = 1;
                } else {
                    $avail[$kcnt] = 0;
                }
                $category[$kcnt] = $assessment['gbcategory'];
                $isgroup[$kcnt] = ($assessment['groupsetid'] != 0);
                $name[$kcnt] = $assessment['name'];
                $cntingb[$kcnt] = $assessment['cntingb']; //0: ignore, 1: count, 2: extra credit, 3: no count but show
                if ($deffeedback[0] == 'Practice') { //set practice as no count in gb
                    $cntingb[$kcnt] = 3;
                }
                $aitems = explode(',', $assessment['itemorder']);
                if ($assessment['allowlate'] > 0) {
                    $allowlate[$kcnt] = $assessment['allowlate'];
                }
                $k = 0;
                $atofind = array();
                foreach ($aitems as $v) {
                    if (strpos($v, '~') !== FALSE) {
                        $sub = explode('~', $v);
                        if (strpos($sub[0], '|') === false) { //backwards compat
                            $atofind[$k] = $sub[0];
                            $aitemcnt[$k] = 1;
                            $k++;
                        } else {
                            $grpparts = explode('|', $sub[0]);
                            if ($grpparts[0] == count($sub) - 1) { //handle diff point values in group if n=count of group
                                for ($i = 1; $i < count($sub); $i++) {
                                    $atofind[$k] = $sub[$i];
                                    $aitemcnt[$k] = 1;
                                    $k++;
                                }
                            } else {
                                $atofind[$k] = $sub[1];
                                $aitemcnt[$k] = $grpparts[0];
                                $k++;
                            }
                        }
                    } else {
                        $atofind[$k] = $v;
                        $aitemcnt[$k] = 1;
                        $k++;
                    }
                }

                $questions = Questions::getByAssessmentId($assessment['id']);
                $totalpossible = 0;
                if ($questions) {
                    foreach ($questions as $question) {
                        if (($k = array_search($question->id, $atofind)) !== false) {//only use first item from grouped questions for total pts
                            if ($question->points == 9999) {
                                $totalpossible += $aitemcnt[$k] * $assessment['defpoints'];//use defpoints
                            } else {
                                $totalpossible += $aitemcnt[$k] * $question->points;//use points from question
                            }
                        }
                    }
                }
                $possible[$kcnt] = $totalpossible;
                $kcnt++;

            }
        }
//Pull Offline Grade item info
        $istutor = false;
        $gbItems = GbItems::findAllOfflineGradeItem($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now);
        if ($gbItems) {
            foreach ($gbItems as $item) {
                $grades[$kcnt] = $item['id'];
                $assessmenttype[$kcnt] = "Offline";
                $category[$kcnt] = $item['gbcategory'];
                $enddate[$kcnt] = $item['showdate'];
                $startdate[$kcnt] = $item['showdate'];
                if ($now < $item['showdate']) {
                    $avail[$kcnt] = 2;
                } else {
                    $avail[$kcnt] = 0;
                }
                $possible[$kcnt] = $item['points'];
                $name[$kcnt] = $item['name'];
                $cntingb[$kcnt] = $item['cntingb'];
                $tutoredit[$kcnt] = $item['tutoredit'];
                if (isset($courseitemsassoc)) {
                    $courseorder[$kcnt] = 2000 + $kcnt;
                }
                $kcnt++;
            }
        }
        //Pull Discussion Grade info
        $query = Forums::findDiscussionGradeInfo($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now);
        if ($query) {
            foreach ($query as $item) {
                $discuss[$kcnt] = $item['id'];
                $assessmenttype[$kcnt] = "Discussion";
                $category[$kcnt] = $item['gbcategory'];
                if ($item['avail'] == 2) {
                    $item['startdate'] = 0;
                    $item['enddate'] = 2000000000;
                }
                $enddate[$kcnt] = $item['enddate'];
                $startdate[$kcnt] = $item['startdate'];
                if ($now < $item['startdate']) {
                    $avail[$kcnt] = 2;
                } else if ($now < $item['enddate']) {
                    $avail[$kcnt] = 1;
                    if ($item['replyby'] > 0 && $item['replyby'] < 2000000000) {
                        if ($item['postby'] > 0 && $item['postby'] < 2000000000) {
                            if ($now > $item['replyby'] && $now > $item['postby']) {
                                $avail[$kcnt] = 0;
                                $enddate[$kcnt] = max($item['replyby'], $item['postby']);
                            }
                        } else {
                            if ($now > $item['replyby']) {
                                $avail[$kcnt] = 0;
                                $enddate[$kcnt] = $item['replyby'];
                            }
                        }
                    } else if ($item['postby'] > 0 && $item['postby'] < 2000000000) {
                        if ($now > $item['postby']) {
                            $avail[$kcnt] = 0;
                            $enddate[$kcnt] = $item['postby'];
                        }
                    }
                } else {
                    $avail[$kcnt] = 0;
                }
                $possible[$kcnt] = $item['points'];
                $name[$kcnt] = $item['name'];
                $cntingb[$kcnt] = $item['cntingb'];
                if (isset($courseitemsassoc)) {
                    $courseorder[$kcnt] = $courseitemsassoc['Forum' . $item['id']];
                }
                $kcnt++;
            }
        }
        //Pull External Tools info
        $query = LinkedText::findExternalToolsInfo($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now);
        if ($query) {
            foreach ($query as $text) {
                if (substr($text['text'], 0, 8) != 'exttool:') {
                    continue;
                }
                $toolparts = explode('~~', substr($text['text'], 8));
                if (isset($toolparts[3])) {
                    $thisgbcat = $toolparts[3];
                    $thiscntingb = $toolparts[4];
                    $thistutoredit = $toolparts[5];
                } else {
                    continue;
                }
                if ($istutor && $thistutoredit == 2) {
                    continue;
                }
                if ($catfilter > -1 && $thisgbcat != $catfilter) {
                    continue;
                }
                $exttools[$kcnt] = $text['id'];
                $assessmenttype[$kcnt] = "External Tool";
                $category[$kcnt] = $thisgbcat;
                if ($text['avail'] == 2) {
                    $text['startdate'] = 0;
                    $text['enddate'] = 2000000000;
                }
                $enddate[$kcnt] = $text['enddate'];
                $startdate[$kcnt] = $text['startdate'];
                if ($now < $text['startdate']) {
                    $avail[$kcnt] = 2;
                } else if ($now < $text['enddate']) {
                    $avail[$kcnt] = 1;
                } else {
                    $avail[$kcnt] = 0;
                }
                $possible[$kcnt] = $text['points'];
                $name[$kcnt] = $text['title'];
                $cntingb[$kcnt] = $thiscntingb;
                if (isset($courseitemsassoc)) {
                    $courseorder[$kcnt] = $courseitemsassoc['LinkedText' . $text['id']];
                }
                $kcnt++;
            }
        }

        $cats = array();
        $catcolcnt = 0;
        //Pull Categories:  Name, scale, scaletype, chop, drop, weight, calctype
        if (in_array(0, $category)) {  //define default category, if used
            $cats[0] = explode(',', $defaultcat);
            if (!isset($cats[6])) {
                $cats[6] = ($cats[4] == 0) ? 0 : 1;
            }
            array_unshift($cats[0], "Default");
            array_push($cats[0], $catcolcnt);
            $catcolcnt++;
        }

        $query = GbCats::findCategoryByCourseId(['courseid' => $courseId]);
        if ($query) {
            foreach ($query as $row) {
                if (in_array($row['id'], $category)) { //define category if used
                    if ($row['name']{0} >= '1' && $row['name']{0} <= '9') {
                        $row['name'] = substr($row['name'], 1);
                    }
                    $cats[$row['id']] = array_slice($row, 1);
                    array_push($cats[$row['id']], $catcolcnt);
                    $catcolcnt++;
                }
            }
        }
        //create item headers
        $pos = 0;
        $catposspast = array();
        $catposspastec = array();
        $catposscur = array();
        $catposscurec = array();
        $catpossfuture = array();
        $catpossfutureec = array();
        $cattotpast = array();
        $cattotpastec = array();
        $cattotcur = array();
        $cattotcurec = array();
        $cattotattempted = array();
        $cattotfuture = array();
        $cattotfutureec = array();
        $itemorder = array();
        $assesscol = array();
        $gradecol = array();
        $discusscol = array();
        $exttoolcol = array();

        if ($orderby == 1) { //order $category by enddate
            asort($enddate, SORT_NUMERIC);
            $newcategory = array();
            foreach ($enddate as $k => $v) {
                $newcategory[$k] = $category[$k];
            }
            $category = $newcategory;
        } else if ($orderby == 5) { //order $category by enddate reverse
            arsort($enddate, SORT_NUMERIC);
            $newcategory = array();
            foreach ($enddate as $k => $v) {
                $newcategory[$k] = $category[$k];
            }
            $category = $newcategory;
        } else if ($orderby == 7) { //order $category by startdate
            asort($startdate, SORT_NUMERIC);
            $newcategory = array();
            foreach ($startdate as $k => $v) {
                $newcategory[$k] = $category[$k];
            }
            $category = $newcategory;
        } else if ($orderby == 9) { //order $category by startdate reverse
            arsort($startdate, SORT_NUMERIC);
            $newcategory = array();
            foreach ($startdate as $k => $v) {
                $newcategory[$k] = $category[$k];
            }
            $category = $newcategory;
        } else if ($orderby == 3) { //order $category alpha
            natcasesort($name);     //asort($name);
            $newcategory = array();
            foreach ($name as $k => $v) {
                $newcategory[$k] = $category[$k];
            }
            $category = $newcategory;
        } else if ($orderby == 11) { //order $category courseorder
            asort($courseorder, SORT_NUMERIC);
            $newcategory = array();
            foreach ($courseorder as $k => $v) {
                $newcategory[$k] = $category[$k];
            }
            $category = $newcategory;
        } else if ($orderby == 13) { //order $category courseorder rev
            arsort($courseorder, SORT_NUMERIC);
            $newcategory = array();
            foreach ($courseorder as $k => $v) {
                $newcategory[$k] = $category[$k];
            }
            $category = $newcategory;
        }
        foreach (array_keys($cats) as $cat) {//foreach category
            $catposspast[$cat] = array();
            $catposscur[$cat] = array();
            $catpossfuture[$cat] = array();
            $catkeys = array_keys($category, $cat); //pull items in that category
            if (($orderby & 1) == 1) { //order by category
                array_splice($itemorder, count($itemorder), 0, $catkeys);
            }
            foreach ($catkeys as $k) {
                if (isset($cats[$cat][6]) && $cats[$cat][6] == 1) {//hidden
                    $cntingb[$k] = 0;
                }
                if ($avail[$k] < 1) { //is past
                    if ($assessmenttype[$k] != "Practice" && $cntingb[$k] == 1) {
                        $catposspast[$cat][] = $possible[$k]; //create category totals
                    } else if ($cntingb[$k] == 2) {
                        $catposspastec[$cat][] = 0;
                    }
                }
                if ($avail[$k] < 2) { //is past or current
                    if ($assessmenttype[$k] != "Practice" && $cntingb[$k] == 1) {
                        $catposscur[$cat][] = $possible[$k]; //create category totals
                    } else if ($cntingb[$k] == 2) {
                        $catposscurec[$cat][] = 0;
                    }
                }
                //is anytime
                if ($assessmenttype[$k] != "Practice" && $cntingb[$k] == 1) {
                    $catpossfuture[$cat][] = $possible[$k]; //create category totals
                } else if ($cntingb[$k] == 2) {
                    $catpossfutureec[$cat][] = 0;
                }

                if (($orderby & 1) == 1) {
                    //display item header if displaying by category
                    //$cathdr[$pos] = $cats[$cat][6];
                    $gradebook[0][1][$pos][0] = $name[$k]; //item name
                    $gradebook[0][1][$pos][1] = $cats[$cat][8]; //item category number
                    $gradebook[0][1][$pos][2] = $possible[$k]; //points possible
                    $gradebook[0][1][$pos][3] = $avail[$k]; //0 past, 1 current, 2 future
                    $gradebook[0][1][$pos][4] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
                    if ($assessmenttype[$k] == "Practice") {
                        $gradebook[0][1][$pos][5] = 1;  //0 regular, 1 practice test
                    } else {
                        $gradebook[0][1][$pos][5] = 0;
                    }
                    if (isset($assessments[$k])) {
                        $gradebook[0][1][$pos][6] = 0; //0 online, 1 offline
                        $gradebook[0][1][$pos][7] = $assessments[$k];
                        $gradebook[0][1][$pos][10] = $isgroup[$k];
                        $assesscol[$assessments[$k]] = $pos;
                    } else if (isset($grades[$k])) {
                        $gradebook[0][1][$pos][6] = 1; //0 online, 1 offline
                        $gradebook[0][1][$pos][8] = $tutoredit[$k]; //tutoredit
                        $gradebook[0][1][$pos][7] = $grades[$k];
                        $gradecol[$grades[$k]] = $pos;
                    } else if (isset($discuss[$k])) {
                        $gradebook[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
                        $gradebook[0][1][$pos][7] = $discuss[$k];
                        $discusscol[$discuss[$k]] = $pos;
                    } else if (isset($exttools[$k])) {
                        $gradebook[0][1][$pos][6] = 3; //0 online, 1 offline, 2 discuss, 3 exttool
                        $gradebook[0][1][$pos][7] = $exttools[$k];
                        $exttoolcol[$exttools[$k]] = $pos;
                    }
                    if ((isset($GLOBALS['includeduedate']) && $GLOBALS['includeduedate'] == true) || isset($allowlate[$k])) {
                        $gradebook[0][1][$pos][11] = $enddate[$k];
                    }
                    if (isset($allowlate[$k])) {
                        $gradebook[0][1][$pos][12] = $allowlate[$k];
                    }

                    $pos++;
                }
            }
        }
        if (($orderby & 1) == 0) {//if not grouped by category
            if ($orderby == 0) {   //enddate
                asort($enddate, SORT_NUMERIC);
                $itemorder = array_keys($enddate);
            } else if ($orderby == 2) {  //alpha
                natcasesort($name);//asort($name);
                $itemorder = array_keys($name);
            } else if ($orderby == 4) { //enddate reverse
                arsort($enddate, SORT_NUMERIC);
                $itemorder = array_keys($enddate);
            } else if ($orderby == 6) { //startdate
                asort($startdate, SORT_NUMERIC);
                $itemorder = array_keys($startdate);
            } else if ($orderby == 8) { //startdate reverse
                arsort($startdate, SORT_NUMERIC);
                $itemorder = array_keys($startdate);
            } else if ($orderby == 10) { //courseorder
                asort($courseorder, SORT_NUMERIC);
                $itemorder = array_keys($courseorder);
            } else if ($orderby == 12) { //courseorder rev
                arsort($courseorder, SORT_NUMERIC);
                $itemorder = array_keys($courseorder);
            }

            foreach ($itemorder as $k) {
                $gradebook[0][1][$pos][0] = $name[$k]; //item name
                $gradebook[0][1][$pos][1] = $cats[$category[$k]][7]; //item category name
                $gradebook[0][1][$pos][2] = $possible[$k]; //points possible
                $gradebook[0][1][$pos][3] = $avail[$k]; //0 past, 1 current, 2 future
                $gradebook[0][1][$pos][4] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
                $gradebook[0][1][$pos][5] = ($assessmenttype[$k] == "Practice");  //0 regular, 1 practice test
                if (isset($assessments[$k])) {
                    $gradebook[0][1][$pos][6] = 0; //0 online, 1 offline
                    $gradebook[0][1][$pos][7] = $assessments[$k];
                    $gradebook[0][1][$pos][10] = $isgroup[$k];
                    $assesscol[$assessments[$k]] = $pos;
                } else if (isset($grades[$k])) {
                    $gradebook[0][1][$pos][6] = 1; //0 online, 1 offline
                    $gradebook[0][1][$pos][8] = $tutoredit[$k]; //tutoredit
                    $gradebook[0][1][$pos][7] = $grades[$k];
                    $gradecol[$grades[$k]] = $pos;
                } else if (isset($discuss[$k])) {
                    $gradebook[0][1][$pos][6] = 2; //0 online, 1 offline, 2 discuss
                    $gradebook[0][1][$pos][7] = $discuss[$k];
                    $discusscol[$discuss[$k]] = $pos;
                } else if (isset($exttools[$k])) {
                    $gradebook[0][1][$pos][6] = 3; //0 online, 1 offline, 2 discuss, 3 exttool
                    $gradebook[0][1][$pos][7] = $exttools[$k];
                    $exttoolcol[$exttools[$k]] = $pos;
                }
                if (isset($GLOBALS['includeduedate']) && $GLOBALS['includeduedate'] == true || isset($allowlate[$k])) {
                    $gradebook[0][1][$pos][11] = $enddate[$k];
                }
                if (isset($allowlate[$k])) {
                    $gradebook[0][1][$pos][12] = $allowlate[$k];
                }
                $pos++;
            }
        }
        $totalspos = $pos;
        //create category headers

        $catorder = array_keys($cats);
        $overallptspast = 0;
        $overallptscur = 0;
        $overallptsfuture = 0;
        $overallptsattempted = 0;
        $cattotweightpast = 0;
        $cattotweightcur = 0;
        $cattotweightfuture = 0;
        $pos = 0;
        $catpossattempted = array();
        $catpossattemptedec = array();
        foreach ($catorder as $cat) {//foreach category

            //cats: name,scale,scaletype,chop,drop,weight
            $catitemcntpast[$cat] = count($catposspast[$cat]);// + count($catposspastec[$cat]);
            $catitemcntcur[$cat] = count($catposscur[$cat]);// + count($catposscurec[$cat]);
            $catitemcntfuture[$cat] = count($catpossfuture[$cat]);// + count($catpossfutureec[$cat]);
            $catpossattempted[$cat] = $catposscur[$cat];  //a copy of the current for later use with attempted
            $catpossattemptedec[$cat] = $catposscurec[$cat];

            if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($catposspast[$cat])) { //if drop is set and have enough items
                asort($catposspast[$cat], SORT_NUMERIC);
                $catposspast[$cat] = array_slice($catposspast[$cat], $cats[$cat][4]);
// print_r($catposspast[$cat]);
            }
            if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($catposscur[$cat])) { //same for past&current
                asort($catposscur[$cat], SORT_NUMERIC);
                $catposscur[$cat] = array_slice($catposscur[$cat], $cats[$cat][4]);
            }
            if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($catpossfuture[$cat])) { //same for all items
                asort($catpossfuture[$cat], SORT_NUMERIC);
                $catpossfuture[$cat] = array_slice($catpossfuture[$cat], $cats[$cat][4]);
            }
            $catposspast[$cat] = array_sum($catposspast[$cat]);
            $catposscur[$cat] = array_sum($catposscur[$cat]);
            $catpossfuture[$cat] = array_sum($catpossfuture[$cat]);


            $gradebook[0][2][$pos][0] = $cats[$cat][0];
            $gradebook[0][2][$pos][1] = $cats[$cat][8];
            $gradebook[0][2][$pos][10] = $cat;
            $gradebook[0][2][$pos][12] = $cats[$cat][6];
            $gradebook[0][2][$pos][13] = $cats[$cat][7];
            if ($catposspast[$cat] > 0 || count($catposspastec[$cat]) > 0) {
                $gradebook[0][2][$pos][2] = 0; //scores in past
                $cattotweightpast += $cats[$cat][5];
                $cattotweightcur += $cats[$cat][5];
                $cattotweightfuture += $cats[$cat][5];
            } else if ($catposscur[$cat] > 0 || count($catposscurec[$cat]) > 0) {
                $gradebook[0][2][$pos][2] = 1; //scores in cur
                $cattotweightcur += $cats[$cat][5];
                $cattotweightfuture += $cats[$cat][5];
            } else if ($catpossfuture[$cat] > 0 || count($catpossfutureec[$cat]) > 0) {
                $gradebook[0][2][$pos][2] = 2; //scores in future
                $cattotweightfuture += $cats[$cat][5];
            } else {
                $gradebook[0][2][$pos][2] = 3; //no items
            }
            if ($useweights == 0 && $cats[$cat][5] > -1) { //if scaling cat total to point value
                if ($catposspast[$cat] > 0) {
                    $gradebook[0][2][$pos][3] = $cats[$cat][5]; //score for past
                } else {
                    $gradebook[0][2][$pos][3] = 0; //fix to 0 if no scores in past yet
                }
                if ($catposscur[$cat] > 0) {
                    $gradebook[0][2][$pos][4] = $cats[$cat][5]; //score for cur
                } else {
                    $gradebook[0][2][$pos][4] = 0; //fix to 0 if no scores in cur/past yet
                }
                if ($catpossfuture[$cat] > 0) {
                    $gradebook[0][2][$pos][5] = $cats[$cat][5]; //score for future
                } else {
                    $gradebook[0][2][$pos][5] = 0; //fix to 0 if no scores in future yet
                }
            } else {
                $gradebook[0][2][$pos][3] = $catposspast[$cat];
                $gradebook[0][2][$pos][4] = $catposscur[$cat];
                $gradebook[0][2][$pos][5] = $catpossfuture[$cat];
            }
            if ($useweights == 1) {
                $gradebook[0][2][$pos][11] = $cats[$cat][5];
            }


            $overallptspast += $gradebook[0][2][$pos][3];
            $overallptscur += $gradebook[0][2][$pos][4];
            $overallptsfuture += $gradebook[0][2][$pos][5];
            $pos++;
        }
        //find total possible points
        if ($useweights == 0) { //use points grading method
            $gradebook[0][3][0] = $overallptspast;
            $gradebook[0][3][1] = $overallptscur;
            $gradebook[0][3][2] = $overallptsfuture;
        }

        //Pull student data
        $ln = 1;
        $query = Student::findStudentByCourseId($courseId, $limuser, $secfilter, $hidelocked, $timefilter, $lnfilter, $isdiag, $hassection, $usersort);
        $alt = 0;
        $sturow = array();
        $timelimitmult = array();
        if ($query) {
            foreach ($query as $student) {
                unset($asid);
                unset($pts);
                unset($IP);
                unset($timeused);
                $student['LastName'] = ucfirst($student['LastName']);
                $student['FirstName'] = ucfirst($student['FirstName']);
                $cattotpast[$ln] = array();
                $cattotpastec[$ln] = array();
                $cattotcur[$ln] = array();
                $cattotfuture[$ln] = array();
                $cattotcurec[$ln] = array();
                $cattotfutureec[$ln] = array();
                //Student ID info
                $gradebook[$ln][0][0] = "{$student['LastName']},&nbsp;{$student['FirstName']}";
                $gradebook[$ln][4][0] = $student['id'];
                $gradebook[$ln][4][1] = $student['locked'];
                $gradebook[$ln][4][2] = $student['hasuserimg'];
                $gradebook[$ln][4][3] = !empty($student['gbcomment']);

                if ($isdiag) {
                    $selparts = explode('~', $student['SID']);
                    $gradebook[$ln][0][1] = $selparts[0];
                    $gradebook[$ln][0][2] = $selparts[1];
                    $selparts = explode('@', $student['email']);
                    $gradebook[$ln][0][3] = $selparts[0];
                    $gradebook[$ln][0][4] = $selparts[1];
                } else {
                    $gradebook[$ln][0][1] = $student['SID'];
                }
                if ($hassection && !$isdiag) {
                    $gradebook[$ln][0][] = ($student['section'] == null) ? '' : $student['section'];
                }
                if ($hascode) {
                    $gradebook[$ln][0][] = $student['code'];
                }
                if ($lastlogin) {
                    $gradebook[$ln][0][] = date("n/j/y", $student['lastaccess']);
                }
                $sturow[$student['id']] = $ln;
                $timelimitmult[$student['id']] = $student['timelimitmult'];
                $ln++;
            }
        }

        //pull logincnt if needed
        if ($logincnt == 1) {
            $query = LoginLog::findLoginCount($courseId);
            if ($query) {
                foreach ($query as $log) {
                    $gradebook[$sturow[$log['userid']]][0][] = $log['count'];
                }
            }
        }

        //pull exceptions
        $exceptions = array();
        $query = Exceptions::findExceptions($courseId);
        if ($query) {
            foreach ($query as $exception) {
                if (!isset($sturow[$exception['userid']])) {
                    continue;
                }
                $exceptions[$exception['assessmentid']][$exception['userid']] = array($exception['enddate'], $exception['islatepass']);
                $gradebook[$sturow[$exception['userid']]][1][$assesscol[$exception['assessmentid']]][6] = ($exception['islatepass'] > 0) ? (1 + $exception['islatepass']) : 1;
                $gradebook[$sturow[$exception['userid']]][1][$assesscol[$exception['assessmentid']]][3] = 10; //will get overwritten later if assessment session exists
            }
        }

        //Get assessment scores
        $assessidx = array_flip($assessments);
        $query = AssessmentSession::findAssessmentsSession($courseId, $limuser);
        if ($query) {
            foreach ($query as $assessment) {
                if (!isset($assessidx[$assessment['assessmentid']]) || !isset($sturow[$assessment['userid']]) || !isset($assesscol[$assessment['assessmentid']])) {
                    continue;
                }
                $i = $assessidx[$assessment['assessmentid']];
                $row = $sturow[$assessment['userid']];
                $col = $assesscol[$assessment['assessmentid']];

                //if two asids for same stu/assess, skip or overright one with higher ID. Shouldn't happen
                if (isset($gradebook[$row][1][$col][4]) && $gradebook[$row][1][$col][4] < $assessment['id']) {
                    continue;
                }

                $gradebook[$row][1][$col][4] = $assessment['id'];; //assessment session id

                $sp = explode(';', $assessment['bestscores']);
                $scores = explode(',', $sp[0]);
                $pts = 0;
                for ($j = 0; $j < count($scores); $j++) {
                    $pts += $this->getpts($scores[$j]);
                }
                $timeused = $assessment['endtime'] - $assessment['starttime'];
                if ($assessment['endtime'] == 0 || $assessment['starttime'] == 0) {
                    $gradebook[$row][1][$col][7] = -1;
                } else {
                    $gradebook[$row][1][$col][7] = round($timeused / 60);
                }
                $timeontask = array_sum(explode(',', str_replace('~', ',', $assessment['timeontask'])));
                if ($timeontask == 0) {
                    $gradebook[$row][1][$col][8] = "N/A";
                } else {
                    $gradebook[$row][1][$col][8] = round($timeontask / 60, 1);
                }
                if (isset($GLOBALS['includelastchange']) && $GLOBALS['includelastchange'] == true) {
                    $gradebook[$row][1][$col][9] = $assessment['endtime'];
                }
                if (in_array(-1, $scores)) {
                    $IP = 1;
                } else {
                    $IP = 0;
                }
                /*
		        Moved up to exception finding so LP mark will show on unstarted assessments
		        if (isset($exceptions[$l['assessmentid']][$l['userid']])) {
			    $gb[$row][1][$col][6] = ($exceptions[$l['assessmentid']][$l['userid']][1]>0)?2:1; //had exception
		        }
		        */
                $latepasscnt = 0;
                if (isset($exceptions[$assessment['assessmentid']][$assessment['userid']])) {// && $now>$enddate[$i] && $now<$exceptions[$assessment['assessmentid']][$assessment['userid']]) {
                    if ($enddate[$i] > $exceptions[$assessment['assessmentid']][$assessment['userid']][0] && $assessmenttype[$i] == "NoScores") {
                        //if exception set for earlier, and NoScores is set, use later date to hide score until later
                        $thised = $enddate[$i];
                    } else {
                        $thised = $exceptions[$assessment['assessmentid']][$assessment['userid']][0];
                        if ($limuser > 0 && $gradebook[0][1][$col][3] == 2) {  //change $avail past/cur/future
                            if ($now < $thised) {
                                $gradebook[0][1][$col][3] = 1;
                            } else {
                                $gradebook[0][1][$col][3] = 0;
                            }
                        }
                    }
                    $inexception = true;

                    /*
                     * In existing system fetched latepasshrs data from actions.php using template
                     */

                    $latepasshrs = null;


                    if ($enddate[$i] < $exceptions[$assessment['assessmentid']][$assessment['userid']][0] && $latepasshrs > 0) {
                        $latepasscnt = round(($exceptions[$assessment['assessmentid']][$assessment['userid']][0] - $enddate[$i]) / ($latepasshrs * 3600));
                    }
                } else {
                    $thised = $enddate[$i];
                    $inexception = false;
                }
                $allowlatethis = false;
                if (isset($allowlate[$i]) && ($allowlate[$i] % 10 == 1 || $latepasscnt < $allowlate[$i] % 10 - 1)) {
                    if ($now < $thised) {
                        $allowlatethis = true;
                    } else if ($allowlate[$i] > 10 && ($now - $thised) < $latepasshrs * 3600) {
                        $allowlatethis = true;
                    }
                }
                $gradebook[$row][1][$col][10] = $allowlatethis;

                if ($canviewall || $sa[$i] == "I" || ($sa[$i] != "N" && $now > $thised)) { //|| $assessmenttype[$i]=="Practice"
                    $gradebook[$row][1][$col][2] = 1; //show link
                } /*else if ($assessment['timelimit']<0 && (($now - $assessment['starttime'])>abs($assessment['timelimit'])) && $sa[$i]!='N' && ($assessmenttype[$k]=='EachAtEnd' || $assessmenttype[$k]=='EndReview' || $assessmenttype[$k]=='AsGo' || $assessmenttype[$k]=='Homework'))  ) {
			        //has "kickout after time limit" set, time limit has passed, and is set for showing each score
                    $gradebook[$row][1][$col][2] = 1; //show link
		        } */ else {
                    $gradebook[$row][1][$col][2] = 0; //don't show link
                }

                $countthisone = false;
                if ($assessmenttype[$i] == "NoScores" && $sa[$i] != "I" && $now < $thised && !$canviewall) {
                    $gradebook[$row][1][$col][0] = 'N/A'; //score is not available
                    $gradebook[$row][1][$col][3] = 0;  //no other info
                } else if (($minscores[$i] < 10000 && $pts < $minscores[$i]) || ($minscores[$i] > 10000 && $pts < ($minscores[$i] - 10000) / 100 * $possible[$i])) {
                    //else if ($pts<$minscores[$i]) {
                    if ($canviewall) {
                        $gradebook[$row][1][$col][0] = $pts; //the score
                        $gradebook[$row][1][$col][3] = 1;  //no credit
                    } else {
                        $gradebook[$row][1][$col][0] = 'NC'; //score is No credit
                        $gradebook[$row][1][$col][3] = 1;  //no credit
                    }
                } else if ($IP == 1 && $thised > $now && (($timelimits[$i] == 0) || ($timeused < $timelimits[$i] * $timelimitmult[$assessment['userid']]))) {
                    $gradebook[$row][1][$col][0] = $pts; //the score
                    $gradebook[$row][1][$col][3] = 2;  //in progress
                    $countthisone = true;
                } else if (($timelimits[$i] > 0) && ($timeused > $timelimits[$i] * $timelimitmult[$assessment['userid']])) {
                    $gradebook[$row][1][$col][0] = $pts; //the score
                    $gradebook[$row][1][$col][3] = 3;  //over time
                } else if ($assessmenttype[$i] == "Practice") {
                    $gradebook[$row][1][$col][0] = $pts; //the score
                    $gradebook[$row][1][$col][3] = 4;  //practice test
                } else { //regular score available to students
                    $gradebook[$row][1][$col][0] = $pts; //the score
                    $gradebook[$row][1][$col][3] = 0;  //no other info
                    $countthisone = true;
                }
                if ($now < $thised) { //still active
                    $gradebook[$row][1][$col][3] += 10;
                }
                if ($countthisone) {
                    if ($cntingb[$i] == 1) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpast[$row][$category[$i]][$col] = $pts;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcur[$row][$category[$i]][$col] = $pts;
                        }
                        $cattotfuture[$row][$category[$i]][$col] = $pts;
                    } else if ($cntingb[$i] == 2) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpastec[$row][$category[$i]][$col] = $pts;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcurec[$row][$category[$i]][$col] = $pts;
                        }
                        $cattotfutureec[$row][$category[$i]][$col] = $pts;
                    }
                }
                if ($limuser > 0 || (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments'])) {
                    $gradebook[$row][1][$col][1] = $assessment['feedback']; //the feedback
                } else if ($limuser == 0 && $assessment['feedback'] != '') {
                    $gradebook[$row][1][$col][1] = 1; //has comment
                } else {
                    $gradebook[$row][1][$col][1] = 0; //no comment
                }

            }
        }
        //Get other grades
        $gradeidx = array_flip($grades);
        unset($gradeid);
        unset($opts);
        unset($discusspts);
        $discussidx = array_flip($discuss);
        $exttoolidx = array_flip($exttools);
        $gradetypeselects = array();
        if (count($grades) > 0) {
            $gradeidlist = implode(',', $grades);
//            $gradetypeselects[] = "(gradetype='offline' AND gradetypeid IN ($gradeidlist))";
            $gradetypeselects[] = (["gradetype" => "offline", "gradetypeid" => $gradeidlist]);
        }
        if (count($discuss) > 0) {
            $forumidlist = implode(',', $discuss);
            $gradetypeselects[] = (["gradetype" => "forum", "gradetypeid" => $forumidlist]);
//            $gradetypeselects[] = "(gradetype='forum' AND gradetypeid IN ($forumidlist))";
        }
        if (count($exttools) > 0) {
            $linkedlist = implode(',', $exttools);
            $gradetypeselects[] = (["gradetype" => "exttool", "gradetypeid" => $linkedlist]);
//            $gradetypeselects[] = "(gradetype='exttool' AND gradetypeid IN ($linkedlist))";
        }
        if (count($gradetypeselects) > 0) {
            $query = Grades::GetOtherGrades($gradetypeselects, $limuser);

            if ($query) {
                foreach ($query as $gradeSelect) {
                    if ($gradeSelect['gradetype'] == 'offline') {
                        if (!isset($gradeidx[$gradeSelect['gradetypeid']]) || !isset($sturow[$gradeSelect['userid']]) || !isset($gradecol[$gradeSelect['gradetypeid']])) {
                            continue;
                        }
                        $i = $gradeidx[$gradeSelect['gradetypeid']];
                        $row = $sturow[$gradeSelect['userid']];
                        $col = $gradecol[$gradeSelect['gradetypeid']];

                        $gradebook[$row][1][$col][2] = $gradeSelect['id'];
                        if ($gradeSelect['score'] != null) {
                            $gradebook[$row][1][$col][0] = 1 * $gradeSelect['score'];
                        }

                        if ($limuser > 0 || (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments'])) {
                            $gradebook[$row][1][$col][1] = $gradeSelect['feedback']; //the feedback (for students)
                        } else if ($limuser == 0 && $gradeSelect['feedback'] != '') { //feedback
                            $gradebook[$row][1][$col][1] = 1; //yes it has it (for teachers)
                        } else {
                            $gradebook[$row][1][$col][1] = 0; //no feedback
                        }

                        if ($cntingb[$i] == 1) {
                            if ($gradebook[0][1][$col][3] < 1) { //past
                                $cattotpast[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            if ($gradebook[0][1][$col][3] < 2) { //past or cur
                                $cattotcur[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            $cattotfuture[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                        } else if ($cntingb[$i] == 2) {
                            if ($gradebook[0][1][$col][3] < 1) { //past
                                $cattotpastec[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            if ($gradebook[0][1][$col][3] < 2) { //past or cur
                                $cattotcurec[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            $cattotfutureec[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                        }

                    } else if ($gradeSelect['gradetype'] == 'forum') {
                        if (!isset($discussidx[$gradeSelect['gradetypeid']]) || !isset($sturow[$gradeSelect['userid']]) || !isset($discusscol[$gradeSelect['gradetypeid']])) {
                            continue;
                        }
                        $i = $discussidx[$gradeSelect['gradetypeid']];
                        $row = $sturow[$gradeSelect['userid']];
                        $col = $discusscol[$gradeSelect['gradetypeid']];
                        if ($gradeSelect['score'] != null) {
                            if (isset($gradebook[$row][1][$col][0])) {
                                $gradebook[$row][1][$col][0] += 1 * $gradeSelect['score']; //adding up all forum scores
                            } else {
                                $gradebook[$row][1][$col][0] = 1 * $gradeSelect['score'];
                            }
                        }
                        if ($limuser == 0 && !isset($gradebook[$row][1][$col][1])) {
                            $gradebook[$row][1][$col][1] = 0; //no feedback
                        }
                        if (trim($gradeSelect['feedback']) != '') {
                            if ($limuser > 0 || (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments'])) {
                                if (isset($gradebook[$row][1][$col][1])) {
                                    $gradebook[$row][1][$col][1] .= "<br/>" . $gradeSelect['feedback'];
                                } else {
                                    $gradebook[$row][1][$col][1] = $gradeSelect['feedback'];
                                }
                                //the feedback (for students)
                            } else if ($limuser == 0) { //feedback
                                $gradebook[$row][1][$col][1] = 1; //yes it has it (for teachers)
                            }
                        }
                        $gradebook[$row][1][$col][2] = 1; //show link
                        $gradebook[$row][1][$col][3] = 0; //is counted
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpast[$row][$category[$i]][$col] = $gradebook[$row][1][$col][0];
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcur[$row][$category[$i]][$col] = $gradebook[$row][1][$col][0];
                        }
                        $cattotfuture[$row][$category[$i]][$col] = $gradebook[$row][1][$col][0];
                    } else if ($gradeSelect['gradetype'] == 'exttool') {
                        if (!isset($exttoolidx[$gradeSelect['gradetypeid']]) || !isset($sturow[$gradeSelect['userid']]) || !isset($exttoolcol[$gradeSelect['gradetypeid']])) {
                            continue;
                        }
                        $i = $exttoolidx[$gradeSelect['gradetypeid']];
                        $row = $sturow[$gradeSelect['userid']];
                        $col = $exttoolcol[$gradeSelect['gradetypeid']];

                        $gradebook[$row][1][$col][2] = $gradeSelect['id'];
                        if ($gradeSelect['score'] != null) {
                            $gradebook[$row][1][$col][0] = 1 * $gradeSelect['score'];
                        }
                        if ($limuser > 0 || (isset($GLOBALS['includecomments']) && $GLOBALS['includecomments'])) {
                            $gradebook[$row][1][$col][1] = $gradeSelect['feedback']; //the feedback (for students)
                        } else if ($limuser == 0 && $gradeSelect['feedback'] != '') { //feedback
                            $gradebook[$row][1][$col][1] = 1; //yes it has it (for teachers)
                        } else {
                            $gradebook[$row][1][$col][1] = 0; //no feedback
                        }

                        if ($cntingb[$i] == 1) {
                            if ($gradebook[0][1][$col][3] < 1) { //past
                                $cattotpast[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            if ($gradebook[0][1][$col][3] < 2) { //past or cur
                                $cattotcur[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            $cattotfuture[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                        } else if ($cntingb[$i] == 2) {
                            if ($gradebook[0][1][$col][3] < 1) { //past
                                $cattotpastec[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            if ($gradebook[0][1][$col][3] < 2) { //past or cur
                                $cattotcurec[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                            }
                            $cattotfutureec[$row][$category[$i]][$col] = 1 * $gradeSelect['score'];
                        }
                    }
                }
            }
        }
        //fill out cattot's with zeros
        for ($ln = 1; $ln < count($sturow) + 1; $ln++) {
            $cattotattempted[$ln] = $cattotcur[$ln];  //copy current to attempted - we will fill in zeros for past due stuff
            $cattotattemptedec[$ln] = $cattotcurec[$ln];
            foreach ($assessidx as $aid => $i) {
                $col = $assesscol[$aid];
                if (!isset($gradebook[$ln][1][$col][0]) || $gradebook[$ln][1][$col][3] % 10 == 1) {
                    if ($cntingb[$i] == 1) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpast[$ln][$category[$i]][$col] = 0;
                            $cattotattempted[$ln][$category[$i]][$col] = 0;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcur[$ln][$category[$i]][$col] = 0;
                        }
                        $cattotfuture[$ln][$category[$i]][$col] = 0;
                    } else if ($cntingb[$i] == 2) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpastec[$ln][$category[$i]][$col] = 0;
                            $cattotattemptedec[$ln][$category[$i]][$col] = 0;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcurec[$ln][$category[$i]][$col] = 0;
                        }
                        $cattotfutureec[$ln][$category[$i]][$col] = 0;
                    }
                }
            }
            foreach ($gradeidx as $aid => $i) {
                $col = $gradecol[$aid];
                if (!isset($gradebook[$ln][1][$col][0])) {
                    if ($cntingb[$i] == 1) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpast[$ln][$category[$i]][$col] = 0;
                            $cattotattempted[$ln][$category[$i]][$col] = 0;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcur[$ln][$category[$i]][$col] = 0;
                        }
                        $cattotfuture[$ln][$category[$i]][$col] = 0;
                    } else if ($cntingb[$i] == 2) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpastec[$ln][$category[$i]][$col] = 0;
                            $cattotattemptedec[$ln][$category[$i]][$col] = 0;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcurec[$ln][$category[$i]][$col] = 0;
                        }
                        $cattotfutureec[$ln][$category[$i]][$col] = 0;
                    }
                }
            }
            foreach ($discussidx as $aid => $i) {
                $col = $discusscol[$aid];
                if (!isset($gradebook[$ln][1][$col][0])) {
                    if ($cntingb[$i] == 1) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpast[$ln][$category[$i]][$col] = 0;
                            $cattotattempted[$ln][$category[$i]][$col] = 0;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcur[$ln][$category[$i]][$col] = 0;
                        }
                        $cattotfuture[$ln][$category[$i]][$col] = 0;
                    } else if ($cntingb[$i] == 2) {
                        if ($gradebook[0][1][$col][3] < 1) { //past
                            $cattotpastec[$ln][$category[$i]][$col] = 0;
                            $cattotattemptedec[$ln][$category[$i]][$col] = 0;
                        }
                        if ($gradebook[0][1][$col][3] < 2) { //past or cur
                            $cattotcurec[$ln][$category[$i]][$col] = 0;
                        }
                        $cattotfutureec[$ln][$category[$i]][$col] = 0;
                    }
                }
            }
        }
        //create category totals
        for ($ln = 1; $ln < count($sturow) + 1; $ln++) { //foreach student calculate category totals and total totals

            $totpast = 0;
            $totcur = 0;
            $totfuture = 0;
            $totattempted = 0;
            $cattotweightattempted = 0;
            $pos = 0; //reset position for category totals

            //update attempted for this student
            unset($catpossattemptedstu);
            unset($catpossattemptedecstu);
            $catpossattemptedstu = $catpossattempted;  //copy attempted array for each stu
            $catpossattemptedecstu = $catpossattemptedec;
            foreach ($assessidx as $aid => $i) {
                $col = $assesscol[$aid];
                if (!isset($gradebook[$ln][1][$col][0])) {
                    if ($gradebook[0][1][$col][3] == 1) {  //if cur , clear out of cattotattempted
                        if ($gradebook[0][1][$col][4] == 1) {
                            $atloc = array_search($gradebook[0][1][$col][2], $catpossattemptedstu[$category[$i]]);
                            if ($atloc !== false) {
                                unset($catpossattemptedstu[$category[$i]][$atloc]);
                            }
                        } else if ($gradebook[0][1][$col][4] == 2) {
                            $atloc = array_search($gradebook[0][1][$col][2], $catpossattemptedecstu[$category[$i]]);
                            if ($atloc !== false) {
                                unset($catpossattemptedecstu[$category[$i]][$atloc]);
                            }
                        }
                    }
                }
            }
            foreach ($catorder as $cat) {//foreach category
                if (isset($cattotpast[$ln][$cat])) {  //past items
                    //cats: name,scale,scaletype,chop,drop,weight,calctype
                    //if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotpast[$ln][$cat])) { //if drop is set and have enough items
                    if ($cats[$cat][7] == 1) {
                        foreach ($cattotpast[$ln][$cat] as $col => $v) {
                            if ($gradebook[0][1][$col][2] == 0) {
                                $cattotpast[$ln][$cat][$col] = 0;
                            } else {
                                $cattotpast[$ln][$cat][$col] = $v / $gradebook[0][1][$col][2];    //convert to percents
                            }
                        }
                        if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($cattotpast[$ln][$cat])) {
                            asort($cattotpast[$ln][$cat], SORT_NUMERIC);
                            if ($cats[$cat][4] < 0) {  //doing keep n
                                $ntodrop = count($cattotpast[$ln][$cat]) + $cats[$cat][4];
                            } else {  //doing drop n
                                $ntodrop = $cats[$cat][4] - ($catitemcntpast[$cat] - count($cattotpast[$ln][$cat]));
                            }

                            if ($ntodrop > 0) {
                                $ndropcnt = 0;
                                foreach ($cattotpast[$ln][$cat] as $col => $v) {
                                    $gradebook[$ln][1][$col][5] = 1; //mark as dropped
                                    $ndropcnt++;
                                    if ($ndropcnt == $ntodrop) {
                                        break;
                                    }
                                }
                            }

                            while (count($cattotpast[$ln][$cat]) < $catitemcntpast[$cat]) {
                                array_unshift($cattotpast[$ln][$cat], 0);
                            }
                            $cattotpast[$ln][$cat] = array_slice($cattotpast[$ln][$cat], $cats[$cat][4]);
                            $tokeep = ($cats[$cat][4] < 0) ? abs($cats[$cat][4]) : ($catitemcntpast[$cat] - $cats[$cat][4]);
                            $cattotpast[$ln][$cat] = round($catposspast[$cat] * array_sum($cattotpast[$ln][$cat]) / ($tokeep), 1);
                        } else {
                            $cattotpast[$ln][$cat] = round($catposspast[$cat] * array_sum($cattotpast[$ln][$cat]) / count($cattotpast[$ln][$cat]), 2);
                        }
                    } else {
                        $cattotpast[$ln][$cat] = array_sum($cattotpast[$ln][$cat]);
                    }
                    if ($cats[$cat][1] != 0) { //scale is set
                        if ($cats[$cat][2] == 0) { //pts scale
                            $cattotpast[$ln][$cat] = round($catposspast[$cat] * ($cattotpast[$ln][$cat] / $cats[$cat][1]), 1);
                        } else if ($cats[$cat][2] == 1) { //percent scale
                            $cattotpast[$ln][$cat] = round($cattotpast[$ln][$cat] * (100 / ($cats[$cat][1])), 1);
                        }
                    }
                    if (isset($cattotpastec[$ln][$cat])) { //add in EC
                        $cattotpast[$ln][$cat] += array_sum($cattotpastec[$ln][$cat]);
                    }
                    if ($useweights == 0 && $cats[$cat][5] > -1) {//use fixed pt value for cat
                        $cattotpast[$ln][$cat] = ($catposspast[$cat] == 0) ? 0 : round($cats[$cat][5] * ($cattotpast[$ln][$cat] / $catposspast[$cat]), 1);
                    }
                    if ($cats[$cat][3] > 0) { //chop score - no over 100%
                        if ($useweights == 0 && $cats[$cat][5] > -1) { //set cat pts
                            $cattotpast[$ln][$cat] = min($cats[$cat][5] * $cats[$cat][3], $cattotpast[$ln][$cat]);
                        } else {
                            $cattotpast[$ln][$cat] = min($catposspast[$cat] * $cats[$cat][3], $cattotpast[$ln][$cat]);
                        }
                    }

                    $gradebook[$ln][2][$pos][0] = $cattotpast[$ln][$cat];

                    if ($useweights == 1) {
                        if ($cattotpast[$ln][$cat] > 0 && $catposspast[$cat] > 0) {
                            $totpast += ($cattotpast[$ln][$cat] * $cats[$cat][5]) / (100 * $catposspast[$cat]); //weight total
                        }
                    }
                } else if (isset($cattotpastec[$ln][$cat])) {
                    $cattotpast[$ln][$cat] = array_sum($cattotpastec[$ln][$cat]);
                    $gradebook[$ln][2][$pos][0] = $cattotpast[$ln][$cat];

                } else { //no items in category yet?
                    $gradebook[$ln][2][$pos][0] = 0;
                }
                if (isset($cattotcur[$ln][$cat])) {  //cur items
                    //cats: name,scale,scaletype,chop,drop,weight,calctype
                    //if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotcur[$ln][$cat])) { //if drop is set and have enough items
                    if ($cats[$cat][7] == 1) {
                        foreach ($cattotcur[$ln][$cat] as $col => $v) {
                            if ($gradebook[0][1][$col][2] == 0) {
                                $cattotcur[$ln][$cat][$col] = 0;
                            } else {
                                $cattotcur[$ln][$cat][$col] = $v / $gradebook[0][1][$col][2];    //convert to percents
                            }
                        }
                        if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($cattotcur[$ln][$cat])) {
                            asort($cattotcur[$ln][$cat], SORT_NUMERIC);

                            if ($cats[$cat][4] < 0) {  //doing keep n
                                $ntodrop = count($cattotcur[$ln][$cat]) + $cats[$cat][4];
                            } else {  //doing drop n
                                $ntodrop = $cats[$cat][4] - ($catitemcntcur[$cat] - count($cattotcur[$ln][$cat]));
                            }

                            if ($ntodrop > 0) {
                                $ndropcnt = 0;
                                foreach ($cattotcur[$ln][$cat] as $col => $v) {
                                    $gradebook[$ln][1][$col][5] += 2; //mark as dropped
                                    $ndropcnt++;
                                    if ($ndropcnt == $ntodrop) {
                                        break;
                                    }
                                }
                            }

                            while (count($cattotcur[$ln][$cat]) < $catitemcntcur[$cat]) {
                                array_unshift($cattotcur[$ln][$cat], 0);
                            }

                            $cattotcur[$ln][$cat] = array_slice($cattotcur[$ln][$cat], $cats[$cat][4]);
                            $tokeep = ($cats[$cat][4] < 0) ? abs($cats[$cat][4]) : ($catitemcntcur[$cat] - $cats[$cat][4]);
                            $cattotcur[$ln][$cat] = round($catposscur[$cat] * array_sum($cattotcur[$ln][$cat]) / ($tokeep), 1);
                        } else {
                            $cattotcur[$ln][$cat] = round($catposscur[$cat] * array_sum($cattotcur[$ln][$cat]) / count($cattotcur[$ln][$cat]), 2);
                        }
                    } else {
                        $cattotcur[$ln][$cat] = array_sum($cattotcur[$ln][$cat]);
                    }

                    if ($cats[$cat][1] != 0) { //scale is set
                        if ($cats[$cat][2] == 0) { //pts scale
                            $cattotcur[$ln][$cat] = round($catposscur[$cat] * ($cattotcur[$ln][$cat] / $cats[$cat][1]), 1);
                        } else if ($cats[$cat][2] == 1) { //percent scale
                            $cattotcur[$ln][$cat] = round($cattotcur[$ln][$cat] * (100 / ($cats[$cat][1])), 1);
                        }
                    }
                    if (isset($cattotcurec[$ln][$cat])) {
                        $cattotcur[$ln][$cat] += array_sum($cattotcurec[$ln][$cat]);
                    }
                    if ($useweights == 0 && $cats[$cat][5] > -1) {//use fixed pt value for cat
                        $cattotcur[$ln][$cat] = ($catposscur[$cat] == 0) ? 0 : round($cats[$cat][5] * ($cattotcur[$ln][$cat] / $catposscur[$cat]), 1);
                    }

                    if ($cats[$cat][3] > 0) {
                        if ($useweights == 0 && $cats[$cat][5] > -1) { //set cat pts
                            $cattotcur[$ln][$cat] = min($cats[$cat][5] * $cats[$cat][3], $cattotcur[$ln][$cat]);
                        } else {
                            $cattotcur[$ln][$cat] = min($catposscur[$cat] * $cats[$cat][3], $cattotcur[$ln][$cat]);
                        }
                    }

                    $gradebook[$ln][2][$pos][1] = $cattotcur[$ln][$cat];

                    if ($useweights == 1) {
                        if ($cattotcur[$ln][$cat] > 0 && $catposscur[$cat] > 0) {
                            $totcur += ($cattotcur[$ln][$cat] * $cats[$cat][5]) / (100 * $catposscur[$cat]); //weight total
                        }
                    }
                } else if (isset($cattotcurec[$ln][$cat])) {
                    $cattotcur[$ln][$cat] = array_sum($cattotcurec[$ln][$cat]);
                    $gradebook[$ln][2][$pos][1] = $cattotcur[$ln][$cat];

                } else { //no items in category yet?
                    $gradebook[$ln][2][$pos][1] = 0;
                }

                if (isset($cattotfuture[$ln][$cat])) {  //future items
                    //cats: name,scale,scaletype,chop,drop,weight,calctype
                    //if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattotfuture[$ln][$cat])) { //if drop is set and have enough items
                    if ($cats[$cat][7] == 1) {
                        foreach ($cattotfuture[$ln][$cat] as $col => $v) {
                            if ($gradebook[0][1][$col][2] == 0) {
                                $cattotfuture[$ln][$cat][$col] = 0;
                            } else {
                                $cattotfuture[$ln][$cat][$col] = $v / $gradebook[0][1][$col][2];    //convert to percents
                            }
                        }
                        if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($cattotfuture[$ln][$cat])) {
                            asort($cattotfuture[$ln][$cat], SORT_NUMERIC);

                            if ($cats[$cat][4] < 0) {  //doing keep n
                                $ntodrop = count($cattotfuture[$ln][$cat]) + $cats[$cat][4];
                            } else {  //doing drop n
                                $ntodrop = $cats[$cat][4] - ($catitemcntfuture[$cat] - count($cattotfuture[$ln][$cat]));
                            }
                            if ($ntodrop > 0) {
                                $ndropcnt = 0;
                                foreach ($cattotfuture[$ln][$cat] as $col => $v) {
                                    $gradebook[$ln][1][$col][5] += 4; //mark as dropped
                                    $ndropcnt++;
                                    if ($ndropcnt == $ntodrop) {
                                        break;
                                    }
                                }
                            }

                            while (count($cattotfuture[$ln][$cat]) < $catitemcntfuture[$cat]) {
                                array_unshift($cattotfuture[$ln][$cat], 0);
                            }
                            $cattotfuture[$ln][$cat] = array_slice($cattotfuture[$ln][$cat], $cats[$cat][4]);
                            $tokeep = ($cats[$cat][4] < 0) ? abs($cats[$cat][4]) : ($catitemcntfuture[$cat] - $cats[$cat][4]);
                            $cattotfuture[$ln][$cat] = round($catpossfuture[$cat] * array_sum($cattotfuture[$ln][$cat]) / ($tokeep), 1);
                        } else {
                            $cattotfuture[$ln][$cat] = round($catpossfuture[$cat] * array_sum($cattotfuture[$ln][$cat]) / count($cattotfuture[$ln][$cat]), 2);
                        }
                    } else {
                        $cattotfuture[$ln][$cat] = array_sum($cattotfuture[$ln][$cat]);
                    }

                    if ($cats[$cat][1] != 0) { //scale is set
                        if ($cats[$cat][2] == 0) { //pts scale
                            $cattotfuture[$ln][$cat] = round($catpossfuture[$cat] * ($cattotfuture[$ln][$cat] / $cats[$cat][1]), 1);
                        } else if ($cats[$cat][2] == 1) { //percent scale
                            $cattotfuture[$ln][$cat] = round($cattotfuture[$ln][$cat] * (100 / ($cats[$cat][1])), 1);
                        }
                    }
                    if (isset($cattotfutureec[$ln][$cat])) {
                        $cattotfuture[$ln][$cat] += array_sum($cattotfutureec[$ln][$cat]);
                    }
                    if ($useweights == 0 && $cats[$cat][5] > -1) {//use fixed pt value for cat
                        $cattotfuture[$ln][$cat] = round($cats[$cat][5] * ($cattotfuture[$ln][$cat] / $catpossfuture[$cat]), 1);
                    }
                    if ($cats[$cat][3] > 0) {
                        if ($useweights == 0 && $cats[$cat][5] > -1) { //set cat pts
                            $cattotfuture[$ln][$cat] = min($cats[$cat][5] * $cats[$cat][3], $cattotfuture[$ln][$cat]);
                        } else {
                            $cattotfuture[$ln][$cat] = min($catpossfuture[$cat] * $cats[$cat][3], $cattotfuture[$ln][$cat]);
                        }
                    }

                    $gradebook[$ln][2][$pos][2] = $cattotfuture[$ln][$cat];

                    if ($useweights == 1) {
                        if ($cattotfuture[$ln][$cat] > 0 && $catpossfuture[$cat] > 0) {
                            $totfuture += ($cattotfuture[$ln][$cat] * $cats[$cat][5]) / (100 * $catpossfuture[$cat]); //weight total
                        }
                    }

                } else if (isset($cattotfutureec[$ln][$cat])) {
                    $cattotfuture[$ln][$cat] = array_sum($cattotfutureec[$ln][$cat]);
                    $gradebook[$ln][2][$pos][2] = $cattotfuture[$ln][$cat];

                } else { //no items in category yet?
                    $gradebook[$ln][2][$pos][2] = 0;
                }

                //update attempted for this student; adjust for drops
                if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($catpossattemptedstu[$cat])) { //same for past&current
                    asort($catpossattemptedstu[$cat], SORT_NUMERIC);
                    $catpossattemptedstu[$cat] = array_slice($catpossattemptedstu[$cat], $cats[$cat][4]);
                }

                if (isset($cattotattempted[$ln][$cat])) {  //attempted and attempted items
                    $catitemcntattempted[$cat] = count($catpossattemptedstu[$cat]);
                    $catpossattemptedstu[$cat] = array_sum($catpossattemptedstu[$cat]);
                    //cats: name,scale,scaletype,chop,drop,weight
                    if ($cats[$cat][4] != 0 && abs($cats[$cat][4]) < count($cattotattempted[$ln][$cat])) { //if drop is set and have enough items
                        foreach ($cattotattempted[$ln][$cat] as $col => $v) {
                            if ($gradebook[0][1][$col][2] == 0) {
                                $cattotattempted[$ln][$cat][$col] = 0;
                            } else {
                                $cattotattempted[$ln][$cat][$col] = $v / $gradebook[0][1][$col][2];    //convert to percents
                            }
                        }
                        asort($cattotattempted[$ln][$cat], SORT_NUMERIC);

                        if ($cats[$cat][4] < 0) {  //doing keep n
                            $ntodrop = count($cattotattempted[$ln][$cat]) + $cats[$cat][4];
                        } else {  //doing drop n
                            $ntodrop = $cats[$cat][4];// - ($catitemcntattempted[$cat]-count($cattotattempted[$ln][$cat]));
                        }
                        if ($ntodrop > 0) {
                            $ndropcnt = 0;
                            foreach ($cattotattempted[$ln][$cat] as $col => $v) {
                                $gradebook[$ln][1][$col][5] += 8; //mark as dropped
                                $ndropcnt++;
                                if ($ndropcnt == $ntodrop) {
                                    break;
                                }
                            }
                        }
                        while (count($cattotattempted[$ln][$cat]) < $catitemcntattempted[$cat]) {
                            array_unshift($cattotattempted[$ln][$cat], 0);
                        }
                        $cattotattempted[$ln][$cat] = array_slice($cattotattempted[$ln][$cat], $cats[$cat][4]);
                        //$tokeep = ($cats[$cat][4]<0)? abs($cats[$cat][4]) : ($catitemcntattempted[$cat] - $cats[$cat][4]);
                        $tokeep = $catitemcntattempted[$cat];
                        $cattotattempted[$ln][$cat] = round($catpossattemptedstu[$cat] * array_sum($cattotattempted[$ln][$cat]) / ($tokeep), 1);
                    } else {
                        $cattotattempted[$ln][$cat] = array_sum($cattotattempted[$ln][$cat]);
                    }

                    if ($cats[$cat][1] != 0) { //scale is set
                        if ($cats[$cat][2] == 0) { //pts scale
                            $cattotattempted[$ln][$cat] = round($catpossattemptedstu[$cat] * ($cattotattempted[$ln][$cat] / $cats[$cat][1]), 1);
                        } else if ($cats[$cat][2] == 1) { //percent scale
                            $cattotattempted[$ln][$cat] = round($cattotattempted[$ln][$cat] * (100 / ($cats[$cat][1])), 1);
                        }
                    }
                    if (isset($cattotattemptedec[$ln][$cat])) { //add in EC
                        $cattotattempted[$ln][$cat] += array_sum($cattotattemptedec[$ln][$cat]);
                    }
                    if ($useweights == 0 && $cats[$cat][5] > -1) {//use fixed pt value for cat
                        $cattotattempted[$ln][$cat] = ($catpossattemptedstu[$cat] == 0) ? 0 : round($cats[$cat][5] * ($cattotattempted[$ln][$cat] / $catpossattemptedstu[$cat]), 1);
                        $catpossattemptedstu[$cat] = ($catpossattemptedstu[$cat] == 0) ? 0 : $cats[$cat][5];
                    }

                    if ($cats[$cat][3] > 0) { //chop score - no over 100%
                        if ($useweights == 0 && $cats[$cat][5] > -1) { //set cat pts
                            $cattotattempted[$ln][$cat] = min($cats[$cat][5] * $cats[$cat][3], $cattotattempted[$ln][$cat]);
                        } else {
                            $cattotattempted[$ln][$cat] = min($catpossattemptedstu[$cat] * $cats[$cat][3], $cattotattempted[$ln][$cat]);
                        }
                    }

                    $gradebook[$ln][2][$pos][3] = $cattotattempted[$ln][$cat];

                    if ($useweights == 1) {
                        if ($cattotattempted[$ln][$cat] > 0 && $catpossattemptedstu[$cat] > 0) {
                            $totattempted += ($cattotattempted[$ln][$cat] * $cats[$cat][5]) / (100 * $catpossattemptedstu[$cat]); //weight total
                        }
                    }
                    $gradebook[$ln][2][$pos][4] = $catpossattemptedstu[$cat];
                } else if (isset($cattotattemptedec[$ln][$cat])) {
                    $cattotattempted[$ln][$cat] = array_sum($cattotattemptedec[$ln][$cat]);
                    $catpossattemptedstu[$cat] = 0;
                    $gradebook[$ln][2][$pos][3] = $cattotattempted[$ln][$cat];
                    $gradebook[$ln][2][$pos][4] = 0;
                } else { //no items in category yet?
                    $gradebook[$ln][2][$pos][3] = 0;
                    $gradebook[$ln][2][$pos][4] = 0;
                    $catpossattemptedstu[$cat] = 0;
                }
                if ($catpossattemptedstu[$cat] > 0 || count($catpossattemptedecstu[$cat]) > 0) {
                    $cattotweightattempted += $cats[$cat][5];
                }

                $pos++;
            }
            $overallptsattempted = array_sum($catpossattemptedstu);

            if ($useweights == 0) { //use points grading method
                if (!isset($cattotpast)) {
                    $totpast = 0;
                } else {
                    $totpast = array_sum($cattotpast[$ln]);
                }
                if (!isset($cattotcur)) {
                    $totcur = 0;
                } else {
                    $totcur = array_sum($cattotcur[$ln]);
                }
                if (!isset($cattotfuture)) {
                    $totfuture = 0;
                } else {
                    $totfuture = array_sum($cattotfuture[$ln]);
                }
                if (!isset($cattotattempted)) {
                    $totattempted = 0;
                } else {
                    $totattempted = array_sum($cattotattempted[$ln]);
                }
                $gradebook[$ln][3][0] = $totpast;
                $gradebook[$ln][3][1] = $totcur;
                $gradebook[$ln][3][2] = $totfuture;
                $gradebook[$ln][3][6] = $totattempted;
                $gradebook[$ln][3][7] = $overallptsattempted;
                if ($overallptspast > 0) {
                    $gradebook[$ln][3][3] = sprintf("%01.1f", 100 * $totpast / $overallptspast);
                } else {
                    $gradebook[$ln][3][3] = '0.0';
                }
                if ($overallptscur > 0) {
                    $gradebook[$ln][3][4] = sprintf("%01.1f", 100 * $totcur / $overallptscur);
                } else {
                    $gradebook[$ln][3][4] = '0.0';
                }
                if ($overallptsfuture > 0) {
                    $gradebook[$ln][3][5] = sprintf("%01.1f", 100 * $totfuture / $overallptsfuture);
                } else {
                    $gradebook[$ln][3][5] = '0.0';
                }
                if ($overallptsattempted > 0) {
                    $gradebook[$ln][3][8] = sprintf("%01.1f", 100 * $totattempted / $overallptsattempted);
                } else {
                    $gradebook[$ln][3][8] = '0.0';
                }
            } else if ($useweights == 1) { //use weights (%) grading method
                //already calculated $tot
                //if ($overallptspast>0) {
                //	$totpast = 100*($totpast/$overallptspast);
                //} else {
                //	$totpast = 0;
                //}
                if ($cattotweightpast == 0) {
                    $gradebook[$ln][3][0] = '0.0';
                } else {
                    $gradebook[$ln][3][0] = sprintf("%01.1f", 10000 * $totpast / $cattotweightpast);
                }
                $gradebook[$ln][3][3] = null;

                //if ($overallptscur>0) {
                //	$totcur = 100*($totcur/$overallptscur);
                //} else {
                //	$totcur = 0;
                //}
                if ($cattotweightcur == 0) {
                    $gradebook[$ln][3][1] = '0.0';
                } else {
                    $gradebook[$ln][3][1] = sprintf("%01.1f", 10000 * $totcur / $cattotweightcur);
                }
                $gradebook[$ln][3][4] = null;

                //if ($overallptsfuture>0) {
                //	$totfuture = 100*($totfuture/$overallptsfuture);
                //} else {
                //	$totfuture = 0;
                //}
                if ($cattotweightfuture == 0) {
                    $gradebook[$ln][3][2] = '0.0';
                } else {
                    $gradebook[$ln][3][2] = sprintf("%01.1f", 10000 * $totfuture / $cattotweightfuture);
                }
                $gradebook[$ln][3][5] = null;

                if ($cattotweightattempted == 0) {
                    $gradebook[$ln][3][6] = '0.0';
                } else {
                    //$gradebook[$ln][3][6] = $totattempted.'/'.$cattotweightattempted;
                    $gradebook[$ln][3][6] = sprintf("%01.1f", 10000 * $totattempted / $cattotweightattempted);
                }
                $gradebook[$ln][3][7] = null;
                $gradebook[$ln][3][8] = null;

            }
        }
        if ($limuser < 1) {
            //create averages
            $gradebook[$ln][0][0] = "Averages";
            $avgs = array();

            for ($j = 0; $j < count($gradebook[0][1]); $j++) { //foreach assessment
                $avgs[$j] = array();

                for ($i = 1; $i < $ln; $i++) { //foreach student
                    if (isset($gradebook[$i][1][$j][0]) && $gradebook[$i][4][1] == 0) { //score exists and student is not locked
                        if ($gradebook[$i][1][$j][3] % 10 == 0 && is_numeric($gradebook[$i][1][$j][0])) {
                            $avgs[$j][] = $gradebook[$i][1][$j][0];
                        }
                    }
                }

                if (count($avgs[$j]) > 0) {
                    sort($avgs[$j], SORT_NUMERIC);
                    $fivenum = array();
                    for ($k = 0; $k < 5; $k++) {
                        $fivenum[] = $this->gbpercentile($avgs[$j], $k * 25);
                    }
                    $fivenumsum = 'n = ' . count($avgs[$j]) . '<br/>';
                    $fivenumsum .= implode(',&nbsp;', $fivenum);
                    if ($gradebook[0][1][$j][2] > 0) {
                        for ($k = 0; $k < 5; $k++) {
                            $fivenum[$k] = round(100 * $fivenum[$k] / $gradebook[0][1][$j][2], 1);
                        }
                        $fivenumsum .= '<br/>' . implode('%,&nbsp;', $fivenum) . '%';
                    }
                } else {
                    $fivenumsum = '';
                }
                $gradebook[0][1][$j][9] = $fivenumsum;
                //$gradebook[0][1][$j][9] = gbpercentile($avgs[$j],0).',&nbsp;'.gbpercentile($avgs[$j],25).',&nbsp;'.gbpercentile($avgs[$j],50).',&nbsp;'.gbpercentile($avgs[$j],75).',&nbsp;'.gbpercentile($avgs[$j],100);
            }
            //cat avgs
            $catavgs = array();
            for ($j = 0; $j < count($gradebook[0][2]); $j++) { //category headers
                $catavgs[$j][0] = array();
                $catavgs[$j][1] = array();
                $catavgs[$j][2] = array();
                $catavgs[$j][3] = array();
                for ($i = 1; $i < $ln; $i++) { //foreach student
                    if ($gradebook[$i][4][1] == 0) {
                        $catavgs[$j][0][] = $gradebook[$i][2][$j][0];
                        $catavgs[$j][1][] = $gradebook[$i][2][$j][1];
                        $catavgs[$j][2][] = $gradebook[$i][2][$j][2];
                        if ($gradebook[$i][2][$j][4] > 0) {
                            $catavgs[$j][3][] = round(100 * $gradebook[$i][2][$j][3] / $gradebook[$i][2][$j][4], 1);
                        } else {
                            //$catavgs[$j][3][] = 0;
                        }
                    }
                }
                for ($i = 0; $i < 4; $i++) {
                    if (count($catavgs[$j][$i]) > 0) {
                        sort($catavgs[$j][$i], SORT_NUMERIC);
                        $fivenum = array();
                        for ($k = 0; $k < 5; $k++) {
                            $fivenum[] = $this->gbpercentile($catavgs[$j][$i], $k * 25);
                        }
                        if ($i == 3) {
                            $fivenumsum = implode('%,&nbsp;', $fivenum) . '%';
                        } else {
                            $fivenumsum = implode(',&nbsp;', $fivenum);
                        }
                        if ($i < 3 && $gradebook[0][2][$j][3 + $i] > 0) {
                            for ($k = 0; $k < 5; $k++) {
                                $fivenum[$k] = round(100 * $fivenum[$k] / $gradebook[0][2][$j][3 + $i], 1);
                            }
                            $fivenumsum .= '<br/>' . implode('%,&nbsp;', $fivenum) . '%';
                        }
                    } else {
                        $fivenumsum = '';
                    }
                    $gradebook[0][2][$j][6 + $i] = $fivenumsum;
                }
            }
            //tot avgs
            $totavgs = array();
            for ($j = 0; $j < count($gradebook[1][3]); $j++) {
                if ($gradebook[1][3][$j] === null) {
                    continue;
                }
                $totavgs[$j] = array();
                for ($i = 1; $i < $ln; $i++) { //foreach student
                    if ($gradebook[$i][4][1] == 0) {
                        $totavgs[$j][] = $gradebook[$i][3][$j];
                    }
                }
            }
            foreach ($avgs as $j => $avg) {
                if (count($avg) > 0) {
                    $gradebook[$ln][1][$j][0] = round(array_sum($avg) / count($avg), 1);
                    $gradebook[$ln][1][$j][4] = 'average';
                }
            }
            foreach ($catavgs as $j => $avg) {
                for ($m = 0; $m < 4; $m++) {
                    if (count($avg[$m]) > 0) {
                        $gradebook[$ln][2][$j][$m] = round(array_sum($avg[$m]) / count($avg[$m]), 1);
                    } else {
                        $gradebook[$ln][2][$j][$m] = 0;
                    }
                }
            }
            foreach ($totavgs as $j => $avg) {
                if (count($avg) > 0) {
                    $gradebook[$ln][3][$j] = round(array_sum($avg) / count($avg), 1);
                }
            }
            $gradebook[$ln][4][0] = -1;
        }

        if ($limuser == -1) {
            $gradebook[1] = $gradebook[$ln];
        }

        //        for($i=2;$i<count($gradebook);$i++){
//            for($j=1;$j<count($gradebook[0][1]);$j++){
//                if($gradebook[0][1][$j][6]==0)
//                return $gradebook;
//            }
//        }
        $defaultValuesArray = array(
            'secfilter' => $secfilter,
            'catfilter' => $catfilter,
            'links' => $links,
            'hidenc' => $hidenc,
            'availshow' => $availshow,
            'showpics' => $showpics,
            'totonleft' => $totonleft,
            'avgontop' => $avgontop,
            'hidelocked' => $hidelocked,
            'lastlogin' => $lastlogin,
            'includeduedate' => $includeduedate,
            'includelastchange' => $includelastchange,
            'studentId' => $stu,
        );
        $gbCatsData = GbCats::getByCourseIdAndOrderByName($courseId);
        $responseData = array('gradebook' => $gradebook, 'sections' => $sections, 'isDiagnostic' => $isdiag, 'isTutor' => $istutor, 'tutorSection' => $tutorsection,
            'secFilter' => $secfilter, 'overrideCollapse' => $overridecollapse, 'availShow' => $availshow, 'totOnLeft' => $totonleft, 'catFilter' => $catfilter,
            'isTeacher' => $isteacher, 'hideNC' => $hidenc, 'includeDueDate' => $includeduedate, 'defaultValuesArray' => $defaultValuesArray, 'colorized' => $colorized, 'gbCatsData' => $gbCatsData);
        return $responseData;
    }

    public function gbpercentile($a, $p)
    {
        if ($p == 0) {
            return $a[0];
        } else if ($p == 100) {
            return $a[count($a) - 1];
        }

        $l = $p * count($a) / 100;
        if (floor($l) == $l) {
            return (($a[$l - 1] + $a[$l]) / 2);
        } else {
            return ($a[ceil($l) - 1]);
        }
    }

    public function actionAddGrades()
    {
        $model = new AddGradesForm();
        $this->guestUserHandler();
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $studentData = Student::findByCid($courseId);
        $course = Course::getById($courseId);
        $assessmentData = Assessments::getByCourseId($courseId);
        $key = 0;
        foreach ($assessmentData as $assessment) {
            $assessmentId[$key] = $assessment['id'];
            $assessmentLabel[$key] = $assessment['name'];
            $key++;
        }

        $studentArray = array();
        if ($studentData) {
            foreach ($studentData as $student) {
                $tempArray = array('Name' => ucfirst($student->user->FirstName) . ' ' . ucfirst($student->user->LastName),
                    'Section' => $student->section,
                    'StudentId' => $student->id,
                    'userid' => $student->userid
                );
                array_push($studentArray, $tempArray);
            }
        }

        $key = AppConstant::NUMERIC_ZERO;
        $gbcatsData = GbCats::getByCourseId($courseId);
        foreach ($gbcatsData as $singleGbcatsData) {
            $gbcatsId[$key] = $singleGbcatsData['id'];
            $gbcatsLabel[$key] = $singleGbcatsData['name'];
            $key++;
        }

        $rubrics = Rubrics::getByUserId($currentUser['id']);
        foreach ($rubrics as $rubric) {
            $rubricsId[$key] = $rubric['id'];
            $rubricsLabel[$key] = $rubric['name'];
            $key++;
        }

        $OutcomesData = Outcomes::getByCourse($courseId);
        $key = AppConstant::NUMERIC_ONE;
        $pageOutcomes = array();
        if ($OutcomesData) {
            foreach ($OutcomesData as $singleData) {
                $pageOutcomes[$singleData['id']] = $singleData['name'];
                $key++;
            }
        }
        $pageOutcomesList = array();
        $query = $course['outcomes'];
        $outcomeArray = unserialize($query);
        $result = $this->flatArray($outcomeArray);
        if ($result) {
            foreach ($result as $singlePage) {
                array_push($pageOutcomesList, $singlePage);
            }
        }
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            if(count($params['outcomes']) > 1){
                foreach ($params['outcomes'] as $outcomeId) {

                    if (is_numeric($outcomeId) && $outcomeId > 0) {
                        $outcomes[] = intval($outcomeId);
                    }
                }
                $params['outcomes'] = implode(',',$outcomes);

            }else{
                $params['outcomes'] = ' ';
            }
            $gbItems = new GbItems();
            $gbItemsId = $gbItems->createGbItemsByCourseId($courseId, $params);
            if ($params['uploade-grade'] != AppConstant::NUMERIC_ONE) {
                if ($params['grade_text'] || $params['feedback_text']) {

                    $gradeTextArray = array();
                    foreach ($params['grade_text'] as $index => $grade) {
                        foreach ($params['feedback_text'] as $key => $feedback) {
                            if ($index == $key) {
                                $tempArray = array(
                                    'studentId' => $index,
                                    'gradeText' => $grade,
                                    'feedbackText' => $feedback,
                                );
                                array_push($gradeTextArray, $tempArray);
                            }
                        }
                    }
                    foreach ($gradeTextArray as $single) {
                        $grades = new Grades();
                        $grades->createGradesByUserId($single, $gbItemsId);
                    }

                }
            } else {
                $this->redirect('upload-grades?gbItems=' . $gbItemsId.'&cid='.$courseId);
            }
            $this->redirect('gradebook?cid='.$courseId);
         }
        $this->includeCSS(['dataTables.bootstrap.css', 'course/items.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js', 'gradebook/addgrades.js', 'roster/managelatepasses.js']);
        $responseData = array('studentInformation' => $studentArray, 'course' => $course, 'assessmentData' => $assessmentData, 'assessmentLabel' => $assessmentLabel, 'assessmentId' => $assessmentId
        , 'gbcatsLabel' => $gbcatsLabel, 'gbcatsId' => $gbcatsId, 'rubricsLabel' => $rubricsLabel, 'rubricsId' => $rubricsId, 'pageOutcomesList' => $pageOutcomesList,
            'pageOutcomes' => $pageOutcomes);
        return $this->renderWithData('addGrades', $responseData);
    }

    public function actionAddRubric()
    {
        $model = new AddRubricForm();
        $currentUser = $this->getAuthenticatedUser();
        $rubricId = $this->getParamVal('rubricId');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $edit = false;

        if ($rubricId) {
            $rubicData = Rubrics::getByUserIdAndRubricId($currentUser['id'], $rubricId);
            $rubricItems = unserialize($rubicData['rubric']);
            $edit = true;
        }

        if ($this->isPost()) {
            $params = $this->getRequestParams();

            $rubricId = $params['rubricId'];
            $gradeTextArray = array();
            foreach ($params['rubitem'] as $index => $feedback) {
                foreach ($params['rubnote'] as $key => $note) {
                    if ($index == $key) {
                        foreach ($params['feedback'] as $k => $percentage) {
                            if ($k == $key) {
                                if ($feedback || $note || $percentage) {
                                    $tempArray = array(
                                        '0' => $feedback,
                                        '1' => $note,
                                        '2' => $percentage,
                                    );
                                    array_push($gradeTextArray, $tempArray);
                                }
                            }
                        }
                    }
                }
            }

            $rubricTextDataArray = serialize($gradeTextArray);
            if ($rubricId) {
                Rubrics::updateRubrics($params, $currentUser['id'], $rubricTextDataArray, $rubricId);
            } else {
                $rubricData = new Rubrics();
                $rubricData->createNewEntry($params, $currentUser['id'], $rubricTextDataArray);
            }
            $rubicsData = Rubrics::getByUserId($currentUser['id']);
            $responseData = array('rubicData' => $rubicsData, 'course' => $course);
            return $this->renderWithData('editRubric', $responseData);
        }
        $responseData = array('model' => $model, 'rubricItems' => $rubricItems, 'rubicData' => $rubicData, 'edit' => $edit, 'rubricId' => $rubricId, 'course' => $course);
        return $this->renderWithData('addRubric', $responseData);
    }

    public function actionEditRubric()
    {
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $rubicData = Rubrics::getByUserId($currentUser['id']);
        $responseData = array('rubicData' => $rubicData, 'course' => $course);
        return $this->renderWithData('editRubric', $responseData);

    }

    public function actionQuickSearchAjax()
    {
        $courseId = $this->getRequestParams();
        $studentInformation = Student::findByCid($courseId);
        $studentDetails = array();
        foreach ($studentInformation as $singleStudentInformation) {
            $tempArray = array(
                'value' => $singleStudentInformation->id,
                'userId' => $singleStudentInformation->user->id,
                'section' => $singleStudentInformation->section,
                'label' => ucfirst($singleStudentInformation->user->FirstName) . '' . ucfirst($singleStudentInformation->user->LastName),

//                'grade' => $singleStudentInformation->grades->score,
//                'feedback' => $singleStudentInformation->grade->feedback,

            );
//            $studentData = User::findByCidAndName($text,$singleStudentInformation['userid']);
            array_push($studentDetails, $tempArray);
        }
        $responseData = $studentDetails;
        return $this->successResponse($responseData);
    }

    public function actionUploadGrades()
    {
        $this->guestUserHandler();
        $course = Course::getById($this->getParamVal('cid'));
        $nowTime = time();
        $model = new AddGradesForm();
        $model->fileHeaderRow = AppConstant::NUMERIC_ZERO;
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $gbItemsId = $params['gbItems'];
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $filename = AppConstant::UPLOAD_DIRECTORY . $nowTime . '.csv';
                $model->file->saveAs($filename);
            }
            $curscores = array();
            $grades = Grades::getByGradeTypeId($gbItemsId);
            if($grades){
              foreach($grades as $grade){
                    $curscores[$grade['userid']] = $grade['score'];
                }
            }
            $failures = array();
            $successes = AppConstant::NUMERIC_ZERO;
            if ($params['userIdType'] == AppConstant::NUMERIC_ZERO) {
                $usercol = $params['userNameCol'] - AppConstant::NUMERIC_ONE;
            } else if ($params['userIdType'] == AppConstant::NUMERIC_ONE) {
                $usercol = $params['fullNameCol'] - AppConstant::NUMERIC_ONE;
            }
            if ($usercol != AppConstant::NUMERIC_NEGATIVE_ONE) {
                $scoreColumn = $params['AddGradesForm']['gradesColumn']- 1;
                $feedbackColumn = $params['AddGradesForm']['feedbackColumn']-1;
                $handle = fopen($filename, 'r');
                if ($params['AddGradesForm']['fileHeaderRow'] == AppConstant::NUMERIC_ONE) {
                    $data = fgetcsv($handle, 4096, ',');
                } else if ($params['AddGradesForm']['fileHeaderRow'] == AppConstant::NUMERIC_TWO) {
                    $data = fgetcsv($handle, 4096, ',');
                    $data = fgetcsv($handle, 4096, ',');
                }
                while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {

                    $studentData = Student::findStudentToUpdateFeedbackAndScore($data, $params, $course, $usercol);
                    if ($feedbackColumn == -1) {
                        $feedback = '';
                    } else {
                        $feedback = addslashes($data[$feedbackColumn]);
                    }
                    $score = $data[$scoreColumn];
                    if(!$score){
                        $score = ' ';
                    }
                    if ($studentData) {
                        $cuserid = $studentData['id'];
                        if (isset($curscores[$cuserid])) {
                            Grades::updateGradeToStudent($score,$feedback,$cuserid,$gbItemsId);
                            $successes++;
                        } else {
                            $gradeObject = new Grades();
                            $gradeObject->addGradeToStudent($cuserid,$gbItemsId,$feedback,$score);
                            $successes++;
                        }
                    } else {
                        $failures[] = $data[$usercol];
                    }
                }
            }
        }
        $this->includeCSS(['site.css']);
        $responseData = array('course' => $course, 'model' => $model, 'failures' => $failures, 'successes' => $successes, 'userCol' => $usercol);
         return $this->renderWithData('uploadGrades', $responseData);
    }

    public function ImportStudentCsv($fileName, $params)
    {
        $this->guestUserHandler();
        $allUserArray = array();
        $handle = fopen($fileName, 'r');
        while (($data = fgetcsv($handle, 2096)) !== false) {
            array_push($allUserArray, $data);
        }
        return $allUserArray;
    }


    public function actionManageOfflineGrades()
    {
        $this->guestUserHandler();
        $model = new AddGradesForm();
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $gradeNames = GbItems::getbyCourseId($courseId);
        $key = AppConstant::NUMERIC_ZERO;
        $gbcatsData = GbCats::getByCourseId($courseId);
        foreach ($gbcatsData as $singleGbcatsData) {

            $gbcatsId[$key] = $singleGbcatsData['id'];
            $gbcatsLabel[$key] = $singleGbcatsData['name'];
            $key++;
        }
        if ($this->isPostMethod()) {
            $tempArray = array();
            if ($params['grade-name-check']) {
                $isCheckBoxChecked = false;
                foreach ($params['grade-name-check'] as $gradeId) {
                    if ($params['Show-after-check'] == 1) {
                        if ($params['Show-after']) {
                            $showdate = 0;
                            if ($params['Show-after'] == 2) {
                                $showdate = strtotime(date('F d, o g:i a'));
                            }
                            $temp = 1;
                            GbItems::updateGrade($gradeId, $showdate, $temp);
                        }
                        $isCheckBoxChecked = true;
                    }
                    if ($params['count-check'] == 1) {
                        $countValue = $params['count'];
                        if ($countValue) {
                            $temp = 2;
                            GbItems::updateGrade($gradeId, $countValue, $temp);
                        }
                        $isCheckBoxChecked = true;
                    }
                    if ($params['tutor-access'] == 1) {
                        $tutoredit = $params['tutoredit'];
                        $temp = 3;
                        GbItems::updateGrade($gradeId, $tutoredit, $temp);
                        $isCheckBoxChecked = true;
                    }
                    if ($params['gradebook-category-check'] == 1) {
                        $gbcat = $params['gbcat'];
                        $temp = 4;
                        GbItems::updateGrade($gradeId, $gbcat, $temp);
                        $isCheckBoxChecked = true;
                    }
                    if ($isCheckBoxChecked == false) {
                        $this->setErrorFlash('Select at least one option to modify');
                    }
                }
            } else {
                $this->setErrorFlash('Select atleast one grade to manage.');
            }
        }
        $this->includeCSS(['dataTables.bootstrap.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js', 'gradebook/manageofflinegrades.js']);
        $responseData = array('model' => $model, 'gradeNames' => $gradeNames, 'course' => $course, 'gbcatsLabel' => $gbcatsLabel, 'gbcatsId' => $gbcatsId);
        return $this->renderWithData('manageOfflineGrades', $responseData);

    }

//Controller method to assign lock on students.
    public function actionMarkLockAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();
        foreach ($params['checkedStudents'] as $students) {
            Student::updateLocked($students, $params['courseId']);
        }
        return $this->successResponse();
    }

//Controller method to Unenroll students.
    public function actionMarkUnenrollAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();

        foreach ($params['checkedStudents'] as $students) {
            Student::deleteStudent($students, $params['courseId']);
        }
        return $this->successResponse();
    }

    public function actionGradeDeleteAjax()
    {
        $gradeType = 'offline';
        $params = $this->getRequestParams();
           foreach ($params['checkedMsg'] as $gradeId) {
            GbItems::deleteById($gradeId);
            Grades::deleteByGradeTypeIdAndGradeType($gradeId,$gradeType);
           }
        return $this->successResponse();
    }

//Controller method for gradebook comment
    public function actionGbComments()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $params = $this->getRequestParams();
        $commentType = $this->getParamVal('comtype');
        $course = Course::getById($this->getParamVal('cid'));
        if (isset($params['isComment'])) {
            foreach ($params as $key => $values) {
                Student::updateGbComments($key, $values, $course['id'], $commentType);
            }
            return $this->redirect('gradebook?cid=' . $course['id']);
        }
        $studentsInfo = Student::findStudentsCompleteInfo($course['id']);
        $this->includeJS(['gradebook/gbComments.js']);
        $responseData = array('course' => $course, 'studentsInfo' => $studentsInfo, 'commentType' => $commentType);
        return $this->renderWithData('gbComments', $responseData);
    }

//Controller method to upload gradebook comment
    public function actionUploadComments()
    {
        $this->guestUserHandler();
        $this->layout = "master";
        $course = Course::getById($this->getParamVal('cid'));
        $nowTime = time();
        $commentType = $this->getParamVal('comtype');
        $model = new UploadCommentsForm();
        $model->fileHeaderRow = AppConstant::NUMERIC_ZERO;
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $filename = AppConstant::UPLOAD_DIRECTORY . $nowTime . '.csv';
                $model->file->saveAs($filename);
            }
            $failures = array();
            $successes = AppConstant::NUMERIC_ZERO;
            if ($params['userIdType'] == AppConstant::NUMERIC_ZERO) {
                $userCol = $params['userNameCol'] - AppConstant::NUMERIC_ONE;
            } else if ($params['userIdType'] == AppConstant::NUMERIC_ONE) {
                $userCol = $params['fullNameCol'] - AppConstant::NUMERIC_ONE;
            }
            if ($userCol != AppConstant::NUMERIC_NEGATIVE_ONE) {
                $commentColumn = $params['UploadCommentsForm']['commentsColumn'] - AppConstant::NUMERIC_ONE;
                $handle = fopen($filename, 'r');
                if ($params['UploadCommentsForm']['fileHeaderRow'] == AppConstant::NUMERIC_ONE) {
                    $data = fgetcsv($handle, 4096, ',');
                } else if ($params['UploadCommentsForm']['fileHeaderRow'] == AppConstant::NUMERIC_TWO) {
                    $data = fgetcsv($handle, 4096, ',');
                    $data = fgetcsv($handle, 4096, ',');
                }
                while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
                    $query = Student::findStudentToUpdateComment($course->id, $params['userIdType'], $data[$userCol]);
                    if ($query) {
                        foreach ($query as $result) {
                            Student::updateGbComments($result['id'], $data[$commentColumn], $course->id, $commentType);
                            $successes++;
                        }
                    } else {
                        $failures[] = $data[$userCol];
                    }
                }
            }
        }
        $responseData = array('course' => $course, 'commentType' => $commentType, 'model' => $model, 'failures' => $failures, 'successes' => $successes, 'userCol' => $userCol);
        return $this->renderWithData('uploadComments', $responseData);
    }

//Controller method for gradebook settings
    public function actionGbSettings()
    {
        $this->guestUserHandler();
        $course = Course::getById($this->getParamVal('cid'));
        $params = $this->getRequestParams();

        if ($this->isPostMethod()) {
            if (isset($params['deleteCatOnSubmit'])) {
                foreach ($params['deleteCatOnSubmit'] as $i => $catToDel) {
                    $params['deleteCatOnSubmit'][$i] = intval($catToDel);
                }
                Assessments::updateGbCat($params['deleteCatOnSubmit']);
                Forums::updateGbCat($params['deleteCatOnSubmit']);
                GbItems::updateGbCat($params['deleteCatOnSubmit']);
                GbCats::deleteGbCat($params['deleteCatOnSubmit']);
            }
            $useWeights = $params['useweights'];
            $orderBy = $params['orderby'];
            if (isset($params['grouporderby'])) {
                $orderBy += 1;
            }
            $userSort = $params['usersort'];
            //name,scale,scaletype,chop,drop,weight
            $ids = array_keys($params['weight']);
            foreach ($ids as $id) {
                $name = $params['name'][$id];
                $scale = $params['scale'][$id];
                if (trim($scale) == '') {
                    $scale = 0;
                }
                $scaleType = $params['st'][$id];
                if (isset($params['chop'][$id])) {
                    $chop = round($params['chopto'][$id] / 100, 2);
                } else {
                    $chop = 0;
                }
                if ($params['droptype'][$id] == 0) {
                    $drop = 0;
                } else if ($params['droptype'][$id] == 1) {
                    $drop = $params['dropl'][$id];
                } else if ($params['droptype'][$id] == 2) {
                    $drop = -1 * $params['droph'][$id];
                }
                $weight = $params['weight'][$id];
                $calcType = intval($params['calctype'][$id]);
                if (trim($weight) == '') {
                    if ($useWeights == 0) {
                        $weight = -1;
                    } else {
                        $weight = 0;
                    }
                }
                $hide = intval($params['hide'][$id]);

                if (substr($id, 0, 3) == 'new') {
                    if (trim($name) != '') {
                        GbCats::createGbCat($course['id'], $name, $scale, $scaleType, $chop, $weight, $hide, $calcType);
                    }
                } else if ($id == 0) {
                    $defaultCat = "$scale,$scaleType,$chop,$drop,$weight,$hide,$calcType";
                } else {
                    GbCats::updateGbCat($id, $name, $scale, $scaleType, $chop, $drop, $weight, $hide, $calcType);
                }
            }
            $defGbMode = $params['gbmode1'] + 10 * $params['gbmode10'] + 100 * ($params['gbmode100'] + $params['gbmode200']) + 1000 * $params['gbmode1000'] + 1000 * $params['gbmode1002'];
            if (isset($params['gbmode4000'])) {
                $defGbMode += 4000;
            }
            if (isset($params['gbmode400'])) {
                $defGbMode += 400;
            }
            if (isset($params['gbmode40'])) {
                $defGbMode += 40;
            }
            $stuGbMode = $_POST['stugbmode1'] + $_POST['stugbmode2'] + $_POST['stugbmode4'] + $_POST['stugbmode8'];
            GbScheme::updateGbScheme($useWeights, $orderBy, $userSort, $defaultCat, $defGbMode, $stuGbMode, $params['colorize'], $course['id']);
            if (isset($params['submit'])) {
                $this->redirect(AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid=' . $course['id'] . '&refreshdef=true'));
            }
        }

        $gbScheme = GbScheme::getByCourseId($course['id']);

        $useWeights = $gbScheme['useweights'];
        $defGbMode = $gbScheme['defgbmode'];
        $colorize = $gbScheme['colorize'];
        $totOnLeft = ((floor($defGbMode / 1000) % 10) & 1);    //0 right, 1 left
        $avgOnTop = ((floor($defGbMode / 1000) % 10) & 2);          //0 bottom, 2 top
        $lastLogin = (((floor($defGbMode / 1000) % 10) & 4) == 4);    //0 hide, 2 show last login column
        $links = ((floor($defGbMode / 100) % 10) & 1);               //0: view/edit, 1 q breakdown
        $hideLocked = ((floor($defGbMode / 100) % 10) & 2);          //0: show 2: hide locked
        $includeDuDate = (((floor($defGbMode / 100) % 10) & 4) == 4); //0: hide due date, 4: show due date
        $hideNc = (floor($defGbMode / 10) % 10) % 3;                 //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
        $includeLastChange = (((floor($defGbMode / 10) % 10) & 4) == 4);  //: hide last change, 4: show last change
        $availShow = $defGbMode % 10;                            //0: past, 1 past&cur, 2 all
        $colorVal = array(0);
        $colorLabel = array("No Color");
        for ($j = 50; $j < 90; $j += ($j < 70 ? 10 : 5)) {
            for ($k = $j + ($j < 70 ? 10 : 5); $k < 100; $k += ($k < 70 ? 10 : 5)) {
                $colorVal[] = "$j:$k";
                $colorLabel[] = "red &le; $j%, green &ge; $k%";
            }
        }
        $colorVal[] = "-1:-1";
        $colorLabel[] = "Active";
        $hideVal = array(1, 0, 2);
        $hideLabel = array(_("Hidden"), _("Expanded"), _("Collapsed"));

        $gbCategory = GbCats::findCategoryByCourseId($course['id']);

        $this->includeJS(['gradebook/gbSettings.js']);
        $responseData = array('course' => $course, 'useWeights' => $useWeights, 'gbScheme' => $gbScheme, 'hideLabel' => $hideLabel, 'hideVal' => $hideVal, 'gbCategory' => $gbCategory, 'links' => $links, 'availShow' => $availShow, 'hideNc' => $hideNc, 'hideLocked' => $hideLocked,
            'totOnLeft' => $totOnLeft, 'colorize' => $colorize, 'colorVal' => $colorVal, 'colorLabel' => $colorLabel, 'avgOnTop' => $avgOnTop, 'lastLogin' => $lastLogin, 'includeDuDate' => $includeDuDate, 'includeLastChange' => $includeLastChange);
        return $this->renderWithData('gbSettings', $responseData);
    }

    public function actionUploadMultipleGrades()
    {
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $model = new UploadCommentsForm();
        $model->fileHeaderRow = AppConstant::NUMERIC_ZERO;
        $nowTime = time();
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file) {
                $filename = AppConstant::UPLOAD_DIRECTORY . $nowTime . '.csv';
                $model->file->saveAs($filename);
            }
        }
        $responseData = array('course' => $course, 'model' => $model);
        return $this->renderWithData('uploadMultipleGrades', $responseData);
    }

    public function actionGradeBookStudentDetail()
    {
        $params = $this->getRequestParams();
        $this->layout = "master";
        $courseId = $params['cid'];
        $userId = $params['studentId'];
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $StudentData = Student::getByUserId($userId);
        $totalData = $this->gbtable($currentUser['id'], $course['id'], $userId);
        $defaultValuesArray = $totalData['defaultValuesArray'];
        $stugbmode = GbScheme::getByCourseId($courseId);
        $gbCatsData = GbCats::getByCourseIdAndOrderByName($courseId);
        $contentTrackData = ContentTrack::getByCourseIdUserIdAndType($courseId, $currentUser['id']);
        if ($totalData['gradebook'][1][0][1] != '') {
            $usersort = $stugbmode['usersort'];
        } else {
            $usersort = AppConstant::NUMERIC_ONE;
        }
        $allStudentsData = User::studentGradebookData($courseId, $usersort);
        $allStudentsinformation = array();
        foreach ($allStudentsData as $stud) {

            $tempArray[0] = $stud['id'];
            $tempArray[1] = $stud['FirstName'];
            $tempArray[2] = $stud['LastName'];
            $tempArray[3] = $stud['section'];
            array_push($allStudentsinformation, $tempArray);
        }
        if ($this->isPostMethod()) {
            if (isset($params['user-comments']) && $userId > AppConstant::NUMERIC_ZERO) {
                $commentType = 'null';
                Student::updateGbComments($userId,$params['user-comments'], $courseId, $commentType);
            }
            $this->redirect('grade-book-student-detail?cid='.$courseId.'&studentId='.$userId);
        }
        $this->includeCSS(['dataTables.bootstrap.css', 'dashboard.css']);
        $this->includeJS(['general.js?ver=012115', 'jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'gradebookstudentdetail.js']);
        $responseData = array('totalData' => $totalData, 'course' => $course, 'currentUser' => $currentUser, 'StudentData' => $StudentData[0], 'defaultValuesArray' => $defaultValuesArray, 'contentTrackData' => $contentTrackData, 'stugbmode' => $stugbmode['stugbmode'], 'gbCatsData' => $gbCatsData, 'stugbmode' => $stugbmode, 'allStudentsinformation' => $allStudentsinformation);
        return $this->renderWithData('gradeBookStudentDetail', $responseData);
    }
    public function actionSendMessageModel()
    {
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $userId = $params['sendto'];
        $sendType = $params['sendtype'];
        $coueseId = $params['cid'];
        $course = Course::getById($coueseId);
        $receiverInformation = User::getById($userId);
        if ($this->isPostMethod()) {
            $htmlawedconfig = array('elements' => '*-script');

            $msgto = intval($params['sendto']);
            $error = '';
            if ($params['sendtype'] == 'msg') {
                $newMessage = new Message();
                $newMessage->saveNewMessage($params, $currentUser);
                $success = AppUtility::t('Message sent');
            } else if ($params['sendtype'] == 'email') {
                $receiverData = array(
                    '0' => $receiverInformation['FirstName'],
                    '1' => $receiverInformation['LastName'],
                    '2' => $receiverInformation['email'],
                );
                $receiverData[2] = trim($receiverData[2]);
                if ($receiverData[2] != '' && $receiverData[2] != 'none@none.com') {
                    $receiver = "{$receiverData[0]} {$receiverData[1]} <{$receiverData[2]}>";
                    $subject = stripslashes($params['subject']);
                    $message = stripslashes($params['message']);
                    $sessiondata['mathdisp'] = 2;
                    $sessiondata['graphdisp'] = 2;
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $senderInformation = User::getById($currentUser['id']);
                    $senderInformation = array(
                        '0' => $senderInformation['FirstName'],
                        '1' => $senderInformation['LastName'],
                        '2' => $senderInformation['email'],
                    );
                    $self = "{$senderInformation[0]} {$senderInformation[1]} <{$senderInformation[2]}>";
                    $headers .= "From: $self\r\n";
                    mail($receiver, $subject, $message, $headers);
                    $success = AppUtility::t('Email sent');
                } else {
                    $error = AppUtility::t('Unable to send: Invalid email address');
                }
            }

            if ($error == '') {
                echo $success;
            } else {
                echo $error;
            }
            echo '. <input type="button" onclick="top.GB_hide()" value="Done" />';

            exit;
        }
        $responseData = array('receiverInformation' => $receiverInformation, 'params' => $params, 'course' => $course);
        return $this->renderWithData('sendMessageModel', $responseData);
    }

    public function flatArray($outcomesData)
    {
        global $pageOutcomesList;
        if ($outcomesData) {
            foreach ($outcomesData as $singleData) {
                if (is_array($singleData)) { //outcome group
                    $pageOutcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                    $this->flatArray($singleData['outcomes']);
                } else {
                    $pageOutcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
                }
            }
        }
        return $pageOutcomesList;
    }
    public function actionNewFlag()
    {
            //recording a toggle.  Called via AHAH
         $courseId = $this->getParamVal('cid');
          Course::updateNewFlag($courseId);
        $this->redirect(AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$courseId));
    }
}



