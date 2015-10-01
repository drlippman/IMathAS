<?php

namespace app\controllers\gradebook;


use app\components\AppConstant;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\filehandler;
use app\components\interpretUtility;
use app\components\LtiOutcomesUtility;
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
use app\models\Log;
use app\models\LoginLog;
use app\models\loginTime;
use app\models\Message;
use app\models\Outcomes;
use app\models\QuestionImages;
use app\models\QuestionSet;
use app\models\Rubrics;
use app\models\Questions;
use app\models\Sessions;
use app\models\Student;
use app\models\StuGroupMembers;
use app\models\Stugroups;
use app\models\StuGroupSet;
use app\models\Teacher;
use app\models\Tutor;
use app\models\User;
use Yii;
use yii\web\UploadedFile;
use app\controllers\AppController;
use app\controllers\PermissionViolationException;
use yii\rbac\Item;
include ("../components/asidutil.php");
class GradebookController extends AppController
{
    public $a;

    public function actionGradebook()
    {
        global $get;
        $this->guestUserHandler();
        $this->layout = "master";
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $countPost = $this->getNotificationDataForum($courseId, $user);
        $msgList = $this->getNotificationDataMessage($courseId, $user);
        $this->setSessionData('messageCount', $msgList);
        $this->setSessionData('postCount', $countPost);
        $course = Course::getById($courseId);

        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        if (isset($params['refreshdef']) && isset($sessionData[$courseId.'catcollapse'])) {
            unset($sessionData[$courseId.'catcollapse']);
            AppUtility::writesessiondata($sessionData,$sessionId);
        }
        if (isset($sessionData[$courseId.'catcollapse'])) {
            $overridecollapse = $sessionData[$courseId.'catcollapse'];
        } else {
            $overridecollapse = array();
        }
        if (isset($params['catcollapse'])) {
            $overridecollapse[$params['cat']] = $params['catcollapse'];
            $sessionData[$courseId.'catcollapse'] = $overridecollapse;
            AppUtility::writesessiondata($sessionData,$sessionId);
        }
        $get = $params;
        $gradebookData = $this->gbtable($user->id, $courseId);

        $this->includeCSS(['course/course.css', 'jquery.dataTables.css','gradebook.css']);
        $this->includeJS(['general.js', 'gradebook/gradebook.js','gradebook/tablescroller2.js','jquery.dataTables.min.js', 'dataTables.bootstrap.js']);
        $responseData = array('course' => $course,'overridecollapse' => $overridecollapse, 'user' => $user, 'gradebook' => $gradebookData['gradebook'], 'data' => $gradebookData);
        return $this->renderWithData('gradebook', $responseData);
    }

    public function gbtable($userId, $courseId, $studentId = null)
    {
        global $get,$timefilter,$lnfilter,$hidelockedfromexport,$includecomments,$logincnt,$lastloginfromexport;
        $params = $get;
        $teacherid = Teacher::getByUserId($userId, $courseId);
        $tutorid = Tutor::getByUserId($userId, $courseId);
        $tutorsection = trim($tutorid->section);
        $sectionQuery = Student::findDistinctSection($courseId);
        $istutor = false;
        if (isset($teacherid)) {
            $isteacher = true;
        }
        if ($tutorid) {
            $istutor = true;
        }
        /*
         * Assign tutor value false temparary
         */

        $sessionId = $this->getSessionId();
        if ($isteacher || $istutor) {
            $canviewall = true;
            /*
             * this is for for temparary use will set value of timefilter and lnfilter and secfilter later
             */
            $secfilter = null;
        } else {
            $canviewall = false;
        }
        if ($canviewall) {

            $sessionData = $this->getSessionData($sessionId);

            if (isset($params['gbmode']) && $params['gbmode'] != '') {
                $gbmode = $params['gbmode'];
                $sessionData[$courseId.'gbmode'] = $gbmode;
            } else if (isset($sessionData[$courseId.'gbmode']) && !isset($params['refreshdef'])) {
                $gbmode = $sessionData[$courseId.'gbmode'];
            } else {
                $defgbmode = GbScheme::findOne(['courseid' => $courseId]);
                $gbmode = $defgbmode->defgbmode;
            }
            if (isset($_COOKIE["colorize-$courseId"]) && !isset($params['refreshdef'])) {
                $colorize = $_COOKIE["colorize-$courseId"];
            } else {
                $defgbmode = GbScheme::getByCourseId($courseId);
                $colorize = $defgbmode->colorize;
                setcookie("colorize-$courseId",$colorize);
            }

            if (isset($params['catfilter'])) {
                $catfilter = $params['catfilter'];

                $sessionData[$courseId.'catfilter'] = $catfilter;
                $enc = base64_encode(serialize($sessionData));
                Sessions::setSessionId($sessionId,$enc);
            } else if (isset($sessionData[$courseId.'catfilter'])) {
                $catfilter = $sessionData[$courseId.'catfilter'];
            } else {
                $catfilter = -1;
            }
            if (isset($params['catfilter'])) {
                $catfilter = $params['catfilter'];
                $sessionData[$courseId.'catfilter'] = $catfilter;
                AppUtility::writesessiondata($sessionData,$sessionId);
            } else if (isset($sessionData[$courseId.'catfilter'])) {

                $catfilter = $sessionData[$courseId.'catfilter'];
            } else {

                $catfilter = -1;
            }

            if (isset($tutorsection) && $tutorsection!='') {
                $secfilter = $tutorsection;
            } else {
                if (isset($params['secfilter'])) {
                    $secfilter = $params['secfilter'];
                    $sessionData[$courseId.'secfilter'] = $secfilter;
                    AppUtility::writesessiondata($sessionData,$sessionId);
                } else if (isset($sessionData[$courseId.'secfilter'])) {
                    $secfilter = $sessionData[$courseId.'secfilter'];
                } else {
                    $secfilter = -1;
                }
            }
            //Gbmode : Links NC Dates.
            $showpics = floor($gbmode / AppConstant::NUMERIC_TEN_THOUSAND) % AppConstant::NUMERIC_TEN; //0 none, 1 small, 2 big
            $totonleft = ((floor($gbmode / AppConstant::NUMERIC_THOUSAND) % AppConstant::NUMERIC_TEN) & AppConstant::NUMERIC_ONE); //0 right, 1 left
            $avgontop = ((floor($gbmode / AppConstant::NUMERIC_THOUSAND) % AppConstant::NUMERIC_TEN) & AppConstant::NUMERIC_TWO); //0 bottom, 2 top
            $lastlogin = (((floor($gbmode / AppConstant::NUMERIC_THOUSAND) % AppConstant::NUMERIC_TEN) & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR); //0 hide, 2 show last login column
            $links = ((floor($gbmode / AppConstant::NUMERIC_HUNDREAD) % AppConstant::NUMERIC_TEN) & AppConstant::NUMERIC_ONE); //0: view/edit, 1 q breakdown
            $hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
            $includeduedate = (((floor($gbmode / AppConstant::NUMERIC_HUNDREAD) % AppConstant::NUMERIC_TEN) & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR); //0: hide due date, 4: show due date
            $hidenc = (floor($gbmode / AppConstant::NUMERIC_TEN) % AppConstant::NUMERIC_TEN) % AppConstant::NUMERIC_FOUR; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
            $includelastchange = (((floor($gbmode / AppConstant::NUMERIC_TEN) % AppConstant::NUMERIC_TEN) & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR);  //: hide last change, 4: show last change
            $availshow = $gbmode % AppConstant::NUMERIC_TEN; //0: past, 1 past&cur, 2 all, 3 past and attempted, 4=current only
        } else {
            $secfilter = -AppConstant::NUMERIC_ONE;
            $catfilter = -AppConstant::NUMERIC_ONE;
            $links = AppConstant::NUMERIC_ZERO;
            $hidenc = AppConstant::NUMERIC_ONE;
            $availshow = AppConstant::NUMERIC_ONE;
            $showpics = AppConstant::NUMERIC_ZERO;
            $totonleft = AppConstant::NUMERIC_ZERO;
            $avgontop = AppConstant::NUMERIC_ZERO;
            $hidelocked = AppConstant::NUMERIC_ZERO;
            $lastlogin = false;
            $includeduedate = false;
            $includelastchange = false;

        }
        if(isset($lastloginfromexport)){
            $lastlogin = $lastloginfromexport;
        }
        if(isset($hidelockedfromexport)){
            $hidelocked = $hidelockedfromexport;
        }
        if ($canviewall && $studentId) {
            $stu = $studentId;
        } else {
            $stu = AppConstant::NUMERIC_ZERO;
        }
        $isdiag = false;
        if ($canviewall) {
            $query = Diags::findByCourseID($courseId);
            if ($query) {
                $isdiag = true;
                $sel1name = $query->sel1name;
                $sel2name = $query->sel2name;
            }
        }
        if ($canviewall && func_num_args() > AppConstant::NUMERIC_TWO) {
            $limuser = $studentId;
        } else if (!$canviewall) {
            $limuser = $userId;
        } else {
            $limuser = AppConstant::NUMERIC_ZERO;
        }
        if (!isset($lastlogin)) {
            $lastlogin = AppConstant::NUMERIC_ZERO;
        }
        if (!isset($logincnt)) {
            $logincnt = AppConstant::NUMERIC_ZERO;
        }

        $category = array();
        $gradebook = array();
        $ln = AppConstant::NUMERIC_ZERO;
        //Pull Gradebook Scheme info
        $query = GbScheme::findOne(['courseid' => $courseId]);
        $useweights = $query->useweights;
        $orderby = $query->orderby;
        $defaultcat = $query->defaultcat;
        $usersort = $query->usersort;
        if ($useweights == AppConstant::NUMERIC_TWO) {
            $useweights = AppConstant::NUMERIC_ZERO;                //use 0 mode for calculation of totals
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
            $countSection = AppConstant::NUMERIC_ZERO;
            $countCode = AppConstant::NUMERIC_ZERO;
            foreach ($query as $singleData) {
                if ($singleData->section != null || $singleData->section != "") {
                    $countSection++;
                }
                if ($singleData->code != null || $singleData->code != "") {
                    $countCode++;
                }
            }
        }
        if ($countSection > AppConstant::NUMERIC_ZERO) {
            $hassection = true;
        } else {
            $hassection = false;
        }
        if ($countCode > AppConstant::NUMERIC_ZERO) {
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
        if ($orderby >= AppConstant::NUMERIC_TEN && $orderby <= AppConstant::NUMERIC_THIRTEEN) {
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
                            $courseitemsassoc[$item->itemtype . $item->typeid] = AppConstant::NUMERIC_TRIPLE_NINE + count($courseitemsassoc);
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
        $overallpts = AppConstant::NUMERIC_ZERO;
        $now = time();
        $kcnt = AppConstant::NUMERIC_ZERO;
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
                if ($assessment['avail'] == AppConstant::NUMERIC_TWO) {
                    $assessment['startdate'] = AppConstant::NUMERIC_ZERO;
                    $assessment['enddate'] = AppConstant::ALWAYS_TIME;
                }
                $enddate[$kcnt] = $assessment['enddate'];
                $startdate[$kcnt] = $assessment['startdate'];
                if ($now < $assessment['startdate']) {
                    $avail[$kcnt] = AppConstant::NUMERIC_TWO;
                } else if ($now < $assessment['enddate']) {
                    $avail[$kcnt] = AppConstant::NUMERIC_ONE;
                } else {
                    $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                }
                $category[$kcnt] = $assessment['gbcategory'];
                $isgroup[$kcnt] = ($assessment['groupsetid'] != AppConstant::NUMERIC_ZERO);
                $name[$kcnt] = $assessment['name'];
                $cntingb[$kcnt] = $assessment['cntingb']; //0: ignore, 1: count, 2: extra credit, 3: no count but show
                if ($deffeedback[0] == 'Practice') { //set practice as no count in gb
                    $cntingb[$kcnt] = AppConstant::NUMERIC_THREE;
                }
                $aitems = explode(',', $assessment['itemorder']);
                if ($assessment['allowlate'] > AppConstant::NUMERIC_ZERO) {
                    $allowlate[$kcnt] = $assessment['allowlate'];
                }
                $k = AppConstant::NUMERIC_ZERO;
                $atofind = array();
                foreach ($aitems as $v) {
                    if (strpos($v, '~') !== FALSE) {
                        $sub = explode('~', $v);
                        if (strpos($sub[0], '|') === false) { //backwards compat
                            $atofind[$k] = $sub[0];
                            $aitemcnt[$k] = AppConstant::NUMERIC_ONE;
                            $k++;
                        } else {
                            $grpparts = explode('|', $sub[0]);
                            if ($grpparts[0] == count($sub) - 1) { //handle diff point values in group if n=count of group
                                for ($i = AppConstant::NUMERIC_ONE; $i < count($sub); $i++) {
                                    $atofind[$k] = $sub[$i];
                                    $aitemcnt[$k] = AppConstant::NUMERIC_ONE;
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
                        $aitemcnt[$k] = AppConstant::NUMERIC_ONE;
                        $k++;
                    }
                }
                $questions = Questions::getByAssessmentId($assessment['id']);
                $totalpossible = AppConstant::NUMERIC_ZERO;
                if ($questions) {
                    foreach ($questions as $question) {
                        if (($k = array_search($question->id, $atofind)) !== false) {//only use first item from grouped questions for total pts
                            if ($question->points == AppConstant::QUARTER_NINE) {
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
        $gbItems = GbItems::findAllOfflineGradeItem($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now);
        if ($gbItems) {
            foreach ($gbItems as $item) {
                $grades[$kcnt] = $item['id'];
                $assessmenttype[$kcnt] = "Offline";
                $category[$kcnt] = $item['gbcategory'];
                $enddate[$kcnt] = $item['showdate'];
                $startdate[$kcnt] = $item['showdate'];
                if ($now < $item['showdate']) {
                    $avail[$kcnt] = AppConstant::NUMERIC_TWO;
                } else {
                    $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                }
                $possible[$kcnt] = $item['points'];
                $name[$kcnt] = $item['name'];
                $cntingb[$kcnt] = $item['cntingb'];
                $tutoredit[$kcnt] = $item['tutoredit'];
                if (isset($courseitemsassoc)) {
                    $courseorder[$kcnt] = AppConstant::NUMERIC_TWO_THOUSAND + $kcnt;
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
                if ($item['avail'] == AppConstant::NUMERIC_TWO) {
                    $item['startdate'] = AppConstant::NUMERIC_ZERO;
                    $item['enddate'] = AppConstant::ALWAYS_TIME;
                }
                $enddate[$kcnt] = $item['enddate'];
                $startdate[$kcnt] = $item['startdate'];
                if ($now < $item['startdate']) {
                    $avail[$kcnt] = AppConstant::NUMERIC_TWO;
                } else if ($now < $item['enddate']) {
                    $avail[$kcnt] = AppConstant::NUMERIC_ONE;
                    if ($item['replyby'] > AppConstant::NUMERIC_ZERO && $item['replyby'] < AppConstant::ALWAYS_TIME) {
                        if ($item['postby'] > AppConstant::NUMERIC_ZERO && $item['postby'] < AppConstant::ALWAYS_TIME) {
                            if ($now > $item['replyby'] && $now > $item['postby']) {
                                $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                                $enddate[$kcnt] = max($item['replyby'], $item['postby']);
                            }
                        } else {
                            if ($now > $item['replyby']) {
                                $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                                $enddate[$kcnt] = $item['replyby'];
                            }
                        }
                    } else if ($item['postby'] > AppConstant::NUMERIC_ZERO && $item['postby'] < AppConstant::ALWAYS_TIME) {
                        if ($now > $item['postby']) {
                            $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                            $enddate[$kcnt] = $item['postby'];
                        }
                    }
                } else {
                    $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
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
                if (substr($text['text'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_EIGHT) != 'exttool:') {
                    continue;
                }
                $toolparts = explode('~~', substr($text['text'], AppConstant::NUMERIC_EIGHT));
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
                    $text['startdate'] = AppConstant::NUMERIC_ZERO;
                    $text['enddate'] = 2000000000;
                }
                $enddate[$kcnt] = $text['enddate'];
                $startdate[$kcnt] = $text['startdate'];
                if ($now < $text['startdate']) {
                    $avail[$kcnt] = 2;
                } else if ($now < $text['enddate']) {
                    $avail[$kcnt] = 1;
                } else {
                    $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
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
                $cats[6] = ($cats[4] == AppConstant::NUMERIC_ZERO) ? AppConstant::NUMERIC_ZERO : 1;
            }
            array_unshift($cats[0], "Default");
            array_push($cats[0], $catcolcnt);
            $catcolcnt++;
        }

        $query = GbCats::findCategoryByCourseId(['courseid' => $courseId]);

        if ($query) {
            foreach ($query as $singleQuery) {
                $row[0] = $singleQuery['id'];
                $row[1] = $singleQuery['name'];
                $row[2] = $singleQuery['scale'];
                $row[3] = $singleQuery['scaletype'];
                $row[4] = $singleQuery['chop'];
                $row[5] = $singleQuery['dropn'];
                $row[6] = $singleQuery['weight'];
                $row[7] = $singleQuery['hidden'];
                $row[8] = $singleQuery['calctype'];

                if (in_array($row[0], $category)) { //define category if used
                    if ($row[1]{0} >= '1' && $row[1]{0} <= '9') {
                        $row[1] = substr($row[1], 1);
                    }
                    $cats[$row[0]] = array_slice($row, 1);
                    array_push($cats[$row[0]], $catcolcnt);
                    $catcolcnt++;
                }
            }
        }

        //create item headers
        $pos = AppConstant::NUMERIC_ZERO;
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
                    $cntingb[$k] = AppConstant::NUMERIC_ZERO;
                }
                if ($avail[$k] < 1) { //is past
                    if ($assessmenttype[$k] != "Practice" && $cntingb[$k] == 1) {
                        $catposspast[$cat][] = $possible[$k]; //create category totals
                    } else if ($cntingb[$k] == 2) {
                        $catposspastec[$cat][] = AppConstant::NUMERIC_ZERO;
                    }
                }
                if ($avail[$k] < 2) { //is past or current
                    if ($assessmenttype[$k] != "Practice" && $cntingb[$k] == 1) {
                        $catposscur[$cat][] = $possible[$k]; //create category totals
                    } else if ($cntingb[$k] == 2) {
                        $catposscurec[$cat][] = AppConstant::NUMERIC_ZERO;
                    }
                }
                //is anytime
                if ($assessmenttype[$k] != "Practice" && $cntingb[$k] == 1) {
                    $catpossfuture[$cat][] = $possible[$k]; //create category totals
                } else if ($cntingb[$k] == 2) {
                    $catpossfutureec[$cat][] = AppConstant::NUMERIC_ZERO;
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
                        $gradebook[0][1][$pos][5] = AppConstant::NUMERIC_ZERO;
                    }
                    if (isset($assessments[$k])) {
                        $gradebook[0][1][$pos][6] = AppConstant::NUMERIC_ZERO; //0 online, 1 offline
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
        if (($orderby & 1) == AppConstant::NUMERIC_ZERO) {//if not grouped by category
            if ($orderby == AppConstant::NUMERIC_ZERO) {   //enddate
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
                    $gradebook[0][1][$pos][6] = AppConstant::NUMERIC_ZERO; //0 online, 1 offline
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
        $overallptspast = AppConstant::NUMERIC_ZERO;
        $overallptscur = AppConstant::NUMERIC_ZERO;
        $overallptsfuture = AppConstant::NUMERIC_ZERO;
        $overallptsattempted = AppConstant::NUMERIC_ZERO;
        $cattotweightpast = AppConstant::NUMERIC_ZERO;
        $cattotweightcur = AppConstant::NUMERIC_ZERO;
        $cattotweightfuture = AppConstant::NUMERIC_ZERO;
        $pos = AppConstant::NUMERIC_ZERO;
        $catpossattempted = array();
        $catpossattemptedec = array();

        foreach ($catorder as $cat) {//foreach category
            //cats: name,scale,scaletype,chop,drop,weight
            $catitemcntpast[$cat] = count($catposspast[$cat]);// + count($catposspastec[$cat]);
            $catitemcntcur[$cat] = count($catposscur[$cat]);// + count($catposscurec[$cat]);
            $catitemcntfuture[$cat] = count($catpossfuture[$cat]);// + count($catpossfutureec[$cat]);
            $catpossattempted[$cat] = $catposscur[$cat];  //a copy of the current for later use with attempted
            $catpossattemptedec[$cat] = $catposscurec[$cat];
            if ($cats[$cat][4] != AppConstant::NUMERIC_ZERO && abs($cats[$cat][4]) < count($catposspast[$cat])) { //if drop is set and have enough items
                asort($catposspast[$cat], SORT_NUMERIC);
                $catposspast[$cat] = array_slice($catposspast[$cat], $cats[$cat][4]);
            }
            if ($cats[$cat][4] != AppConstant::NUMERIC_ZERO && abs($cats[$cat][4]) < count($catposscur[$cat])) { //same for past&current
                asort($catposscur[$cat], SORT_NUMERIC);
                $catposscur[$cat] = array_slice($catposscur[$cat], $cats[$cat][4]);
            }
            if ($cats[$cat][4] != AppConstant::NUMERIC_ZERO && abs($cats[$cat][4]) < count($catpossfuture[$cat])) { //same for all items
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

            if ($catposspast[$cat] > 0 || count($catposspastec[$cat]) > AppConstant::NUMERIC_ZERO) {
                $gradebook[0][2][$pos][2] = AppConstant::NUMERIC_ZERO; //scores in past
                $cattotweightpast += $cats[$cat][5];
                $cattotweightcur += $cats[$cat][5];
                $cattotweightfuture += $cats[$cat][5];
            } else if ($catposscur[$cat] > AppConstant::NUMERIC_ZERO || count($catposscurec[$cat]) > AppConstant::NUMERIC_ZERO) {
                $gradebook[0][2][$pos][2] = 1; //scores in cur
                $cattotweightcur += $cats[$cat][5];
                $cattotweightfuture += $cats[$cat][5];
            } else if ($catpossfuture[$cat] > AppConstant::NUMERIC_ZERO || count($catpossfutureec[$cat]) > AppConstant::NUMERIC_ZERO) {
                $gradebook[0][2][$pos][2] = 2; //scores in future
                $cattotweightfuture += $cats[$cat][5];
            } else {
                $gradebook[0][2][$pos][2] = 3; //no items
            }
            if ($useweights == 0 && $cats[$cat][5] > -1) { //if scaling cat total to point value
                if ($catposspast[$cat] > AppConstant::NUMERIC_ZERO) {
                    $gradebook[0][2][$pos][3] = $cats[$cat][5]; //score for past
                } else {
                    $gradebook[0][2][$pos][3] = AppConstant::NUMERIC_ZERO; //fix to 0 if no scores in past yet
                }
                if ($catposscur[$cat] > AppConstant::NUMERIC_ZERO) {
                    $gradebook[0][2][$pos][4] = $cats[$cat][5]; //score for cur
                } else {
                    $gradebook[0][2][$pos][4] = AppConstant::NUMERIC_ZERO; //fix to 0 if no scores in cur/past yet
                }
                if ($catpossfuture[$cat] > AppConstant::NUMERIC_ZERO) {
                    $gradebook[0][2][$pos][5] = $cats[$cat][5]; //score for future
                } else {
                    $gradebook[0][2][$pos][5] = AppConstant::NUMERIC_ZERO; //fix to 0 if no scores in future yet
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
        $alt = AppConstant::NUMERIC_ZERO;
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
                $gradebook[$ln][0][0] = "{$student['LastName']}, {$student['FirstName']}";
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
                $pts = AppConstant::NUMERIC_ZERO;
                for ($j = 0; $j < count($scores); $j++) {
                    $pts += $this->getpts($scores[$j]);
                }
                $timeused = $assessment['endtime'] - $assessment['starttime'];
                if ($assessment['endtime'] == AppConstant::NUMERIC_ZERO || $assessment['starttime'] == AppConstant::NUMERIC_ZERO) {
                    $gradebook[$row][1][$col][7] = -1;
                } else {
                    $gradebook[$row][1][$col][7] = round($timeused / 60);
                }
                $timeontask = array_sum(explode(',', str_replace('~', ',', $assessment['timeontask'])));
                if ($timeontask == AppConstant::NUMERIC_ZERO) {
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
                    $IP = AppConstant::NUMERIC_ZERO;
                }
                /*
		        Moved up to exception finding so LP mark will show on unstarted assessments
		        if (isset($exceptions[$l['assessmentid']][$l['userid']])) {
			    $gb[$row][1][$col][6] = ($exceptions[$l['assessmentid']][$l['userid']][1]>0)?2:1; //had exception
		        }
		        */
                $latepasscnt = AppConstant::NUMERIC_ZERO;
                if (isset($exceptions[$assessment['assessmentid']][$assessment['userid']])) {// && $now>$enddate[$i] && $now<$exceptions[$assessment['assessmentid']][$assessment['userid']]) {
                    if ($enddate[$i] > $exceptions[$assessment['assessmentid']][$assessment['userid']][0] && $assessmenttype[$i] == "NoScores") {
                        //if exception set for earlier, and NoScores is set, use later date to hide score until later
                        $thised = $enddate[$i];
                    } else {
                        $thised = $exceptions[$assessment['assessmentid']][$assessment['userid']][0];
                        if ($limuser > AppConstant::NUMERIC_ZERO && $gradebook[0][1][$col][3] == 2) {  //change $avail past/cur/future
                            if ($now < $thised) {
                                $gradebook[0][1][$col][3] = 1;
                            } else {
                                $gradebook[0][1][$col][3] = AppConstant::NUMERIC_ZERO;
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
                    $gradebook[$row][1][$col][2] = AppConstant::NUMERIC_ZERO; //don't show link
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
                    $gradebook[$row][1][$col][1] = AppConstant::NUMERIC_ZERO; //no comment
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
        if (count($grades) > AppConstant::NUMERIC_ZERO) {
            $gradeidlist = implode(',', $grades);
            $gradetypeselects[] = "(gradetype='offline' AND gradetypeid IN ($gradeidlist))";
//            $gradetypeselects[] = (["gradetype" => "offline", "gradetypeid" => $gradeidlist]);
        }
        if (count($discuss) > AppConstant::NUMERIC_ZERO) {
            $forumidlist = implode(',', $discuss);
//            $gradetypeselects[] = (["gradetype" => "forum", "gradetypeid" => $forumidlist]);
            $gradetypeselects[] = "(gradetype='forum' AND gradetypeid IN ($forumidlist))";
        }
        if (count($exttools) > AppConstant::NUMERIC_ZERO) {
            $linkedlist = implode(',', $exttools);
//            $gradetypeselects[] = (["gradetype" => "exttool", "gradetypeid" => $linkedlist]);
            $gradetypeselects[] = "(gradetype='exttool' AND gradetypeid IN ($linkedlist))";
        }

        if (count($gradetypeselects) > AppConstant::NUMERIC_ZERO) {
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
                        if ($limuser > AppConstant::NUMERIC_ZERO || (isset($includecomments) && $includecomments)) {
                            $gradebook[$row][1][$col][1] = $gradeSelect['feedback']; //the feedback (for students)
                        } else if ($limuser == AppConstant::NUMERIC_ZERO && $gradeSelect['feedback'] != '') { //feedback
                            $gradebook[$row][1][$col][1] = 1; //yes it has it (for teachers)
                        } else {
                            $gradebook[$row][1][$col][1] = AppConstant::NUMERIC_ZERO; //no feedback
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
                            if ($limuser > 0 || (isset($includecomments) && $includecomments)) {
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
                        if ($limuser > 0 || (isset($includecomments) && $includecomments)) {
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
        $responseData = array('gradebook' => $gradebook, 'sectionQuery' => $sectionQuery, 'isDiagnostic' => $isdiag, 'isTutor' => $istutor, 'tutorSection' => $tutorsection,
            'secFilter' => $secfilter,'availShow' => $availshow, 'totOnLeft' => $totonleft, 'catFilter' => $catfilter,
            'isTeacher' => $isteacher, 'hideNC' => $hidenc, 'includeDueDate' => $includeduedate, 'defaultValuesArray' => $defaultValuesArray, 'colorize' => $colorize, 'gbCatsData' => $gbCatsData);
        return $responseData;
    }

    public function getpts($sc)
    {
        if (strpos($sc, '~') === false) {
            if ($sc > AppConstant::NUMERIC_ZERO) {
                return $sc;
            } else {
                return AppConstant::NUMERIC_ZERO;
            }
        } else {
            $sc = explode('~', $sc);
            $tot = AppConstant::NUMERIC_ZERO;
            foreach ($sc as $s) {
                if ($s > AppConstant::NUMERIC_ZERO) {
                    $tot += $s;
                }
            }
            return round($tot, AppConstant::NUMERIC_ONE);
        }
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
        $this->guestUserHandler();
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $studentData = Student::findByCid($courseId);
        $course = Course::getById($courseId);
        $assessmentData = Assessments::getByCourseId($courseId);
        $key = 0;
        foreach ($assessmentData as $assessment) {
            $assessmentId[$key] = $assessment['id'];
            $assessmentLabel[$key] = $assessment['name'];
            $key++;
        }

        if ($params['grades'] == 'all') {

            if (!isset($params['isolate'])) {

                if ($params['gbitem'] == 'new') {
                    $defaultValuesArray = array(
                        $name = '',
                        $points = 0,
                        $showdate = time(),
                        $gbcat = 0,
                        $cntingb = 1,
                        $tutoredit = 0,
                        $rubric = 0,
                        $gradeoutcomes = array(),
                    );
                } else {
                    $gbItems = GbItems::getById($params['gbitem']);
                    $gradeoutcomes = $gbItems['outcomes'];
                    if ($gradeoutcomes != '') {
                        $gradeoutcomes = explode(',', $gradeoutcomes);
                    } else {
                        $gradeoutcomes = array();
                    }
                    $defaultValuesArray = array(
                        $name = $gbItems['name'],
                        $points = $gbItems['points'],
                        $showdate = $gbItems['showdate'],
                        $gbcat = $gbItems['gbcategory'],
                        $cntingb = $gbItems['cntingb'],
                        $tutoredit = $gbItems['tutoredit'],
                        $rubric = $gbItems['rubric'],
                        $gradeoutcomes = $gradeoutcomes,
                    );
                }

                if ($showdate != 0) {
                    $defaultValuesArray['sdate'] = AppUtility::tzdate("m/d/Y", $showdate);
                    $defaultValuesArray['stime'] = AppUtility::tzdate("g:i a", $showdate);
                } else {
                    $defaultValuesArray['sdate'] = AppUtility::tzdate("m/d/Y", time() + 60 * 60);
                    $defaultValuesArray['stime'] = AppUtility::tzdate("g:i a", time() + 60 * 60);
                }
            }else{
                $gbItems = GbItems::getById($params['gbitem']);
            }
        } else {
            $gbItems = GbItems::getById($params['gbitem']);
        }
        if ($gbItems['rubric'] != 0) {
            $rubricData = Rubrics::getById($gbItems['rubric']);
            $rubricFinalData = array(
                '0' => $rubricData['id'],
                '1' => $rubricData['rubrictype'],
                '2' => $rubricData['rubric'],
            );
        }
        $count = 0;
        $hassection = false;
        $studentsDataForHasSection = User::getByUserIdAndStudentId($params['cid']);
        foreach ($studentsDataForHasSection as $student) {
            if ($student['section'] != null) {
                $count++;
                $hassection = true;
            }
        }

        if ($hassection) {
            $gbSchemeData = GbScheme::getByCourseId($params['cid']);
            if ($gbSchemeData['usersort'] == 0) {
                $sortorder = "sec";
            } else {
                $sortorder = "name";
            }
        } else {
            $sortorder = "name";
        }
        if ($params['gbitem'] == "new") {
            $studentsData = Student::getByCourseAndGradesToAllStudents($params['cid'], $params['grades'], $hassection, $sortorder);

            $finalStudentArray = array();
            foreach ($studentsData as $singleStudent) {
                $finalArray = array(
                    '0' => $singleStudent['id'],
                    '1' => $singleStudent['LastName'],
                    '2' => $singleStudent['FirstName'],
                    '3' => $singleStudent['section'],
                    '4' => $singleStudent['locked'],
                );
                array_push($finalStudentArray, $finalArray);
            }

        } else {
            $gradeData = Grades::getByGradeTypeIdAndUserId($params['gbitem'], $params['grades']);
            $studentsData = Student::getByCourseAndGrades($params['cid'], $params['grades'], $hassection, $sortorder);
            $finalStudentArray = array();
            foreach ($studentsData as $singleStudent) {
                $finalArray = array(
                    '0' => $singleStudent['id'],
                    '1' => $singleStudent['LastName'],
                    '2' => $singleStudent['FirstName'],
                    '3' => $singleStudent['section'],
                    '4' => $singleStudent['locked'],
                );
                array_push($finalStudentArray, $finalArray);
            }

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
        $keyValue = AppConstant::NUMERIC_ONE;
        $rubricsId = array(0);
        $rubricsLabel = array('None');
        $rubrics = Rubrics::getByUserId($currentUser['id']);
        foreach ($rubrics as $rubric) {
            $rubricsId[$keyValue] = $rubric['id'];
            $rubricsLabel[$keyValue] = $rubric['name'];
            $keyValue++;
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
        $istutor = false;
        $isteacher = false;
        $isteacher = $this->isTeacher($currentUser['id'], $courseId);
        $istutor = $this->isTutor($currentUser['id'], $courseId);
        if ($isteacher) {
            $istutor = true;
        }
        if ($istutor) {
            $isteacher = true;
        }

        if ($istutor) {
            $isTutorEdit = false;
            if (is_numeric($params['gbitem'])) {
                $gbIteamsData = GbItems::getById($params['gbitem']);
                if ($gbIteamsData['tutoredit'] == 1) {
                    $isTutorEdit = true;
                    $params['isolate'] = true;
                }
            }
        } else if (!$isteacher) {

            echo "You need to log in as a teacher to access this page";

            exit;
        }
        $isDelete = true;
        if (isset($params['del']) && $isteacher) {
            $isDelete = false;
            if (isset($params['confirm'])) {
                Grades::deleteByGradeTypeIdAndGradeType($params['del'], 'offline');
                GbItems::deleteById($params['del']);
                return $this->redirect('gradebook?stu=' . $params['stu'] . '&gbmode=' . $params['gbmode'] . '&cid=' . $params['cid']);
            }
        }

        if (isset($params['name']) && $isteacher) {
            if ($params['available-after'] == '0') {
                $params['showdate'] = 0;
            } else {
                $params['showdate'] = AssessmentUtility::parsedatetime($params['sdate'], $params['stime']);
            }
            $params['tutoredit'] = intval($params['tutoredit']);
            $params['rubric'] = intval($params['rubric']);
            $outcomes = array();
            if (isset($params['outcomes'])) {
                foreach ($params['outcomes'] as $o) {
                    if (is_numeric($o) && $o > 0) {
                        $outcomes[] = intval($o);
                    }
                }
            }
            $params['outcomes'] = implode(',', $outcomes);
            if ($params['gbitem'] == 'new') {
                $gbItems = new GbItems();
                $gbItemsId = $gbItems->createGbItemsByCourseId($courseId, $params);
                $params['gbitem'] = $gbItemsId;
                $isnewitem = true;
            } else {
                GbItems::updateGbItemsByCourseId($params['gbitem'], $params);
                $isnewitem = false;
            }
        }

        //check for grades marked as newscore that aren't really new
        //shouldn't happen, but could happen if two browser windows open
        if (isset($params['newscore'])) {

            $keys = array_keys($params['newscore']);

            foreach ($keys as $k => $v) {
                if (trim($v) == '') {
                    unset($keys[$k]);
                }
            }
            if (count($keys) > 0) {
                $kl = implode(',', $keys);
                $grades = Grades::getUserId($params['gbitem'], $kl);
                foreach ($grades as $singleGrade) {
                    $params['score'][$singleGrade['userid']] = $params['newscore'][$singleGrade['userid']];
                    unset($params['newscore'][$singleGrade['userid']]);
                }
            }
        }

        if (isset($params['assesssnapaid'])) {
            //doing assessment snapshot
            $assessmentSessionData = AssessmentSession::getByAssessmentId($params['assessment']);
            if ($assessmentSessionData) {
                foreach ($assessmentSessionData as $assessmentSession) {
                    $sp = explode(';', $assessmentSession['bestscores']);
                    $sc = explode(',', $sp['userid']);
                    $tot = 0;
                    $att = 0;
                    foreach ($sc as $v) {
                        if (strpos($v, '-1') === false) {
                            $att++;
                        }
                        $tot += $this->getpts($v);
                    }
                    if ($params['assesssnaptype'] == 0) {
                        $score = $tot;
                    } else {
                        $attper = $att / count($sc);
                        if ($attper >= $params['assesssnapatt'] / 100 - .001 && $tot >= $params['assesssnappts'] - .00001) {
                            $score = $params['points'];
                        } else {
                            $score = 0;
                        }
                    }
                    $updateGrades['score'] = $score;
                    $updateGrades['gradetype'] = 'offline';
                    $updateGrades['gradetypeid'] = $params['gbitem'];
                    $updateGrades['feedback'] = ' ';
                    $updateGrades['userid'] = $assessmentSession['userid'];
                    $grades = new Grades;
                    $grades->createGradesByUserId($updateGrades);
                }
            }
        } else {
            ///regular submit
            if (isset($params['score'])) {

                foreach ($params['score'] as $k => $sc) {
                    if (trim($k) == '') {
                        continue;
                    }
                    $sc = trim($sc);
                    if ($sc != '') {
                        Grades::updateGradeToStudent($sc, $params['feedback'][$k], $k, $params['gbitem']);
                    } else {
                        Grades::updateGradeToStudent('NULL', $params['feedback'][$k], $k, $params['gbitem']);
                    }
                }
            }

            if (isset($params['newscore'])) {
                foreach ($params['newscore'] as $k => $sc) {
                    if (trim($k) == '') {
                        continue;
                    }
                    if ($sc != '') {
                        $updateGrades['score'] = $sc;
                        $updateGrades['gradetype'] = 'offline';
                        $updateGrades['gradetypeid'] = $params['gbitem'];
                        $updateGrades['feedback'] = $params['feedback'][$k];
                        $updateGrades['userid'] = $k;
                        $grades = new Grades;
                        $grades->createGradesByUserId($updateGrades);
                    } else if (trim($params['feedback'][$k]) != '') {
                        $updateGrades['score'] = 'NULL';
                        $updateGrades['gradetype'] = 'offline';
                        $updateGrades['gradetypeid'] = $params['gbitem'];
                        $updateGrades['feedback'] = $params['feedback'][$k];
                        $updateGrades['userid'] = $k;
                        $grades = new Grades;
                        $grades->createGradesByUserId($updateGrades);
                    }
                }
            }
        }
        if (isset($params['score']) || isset($params['newscore']) || isset($params['name'])) {
            if ($isnewitem && isset($params['doupload'])) {
                return $this->redirect('upload-grades?gbmode=' . $params['gbmode'] . '&cid=' . $courseId . '&gbitem=' . $params['gbitem']);
            } else {
                return $this->redirect('gradebook?stu=' . $params['stu'] . '&gbmode=' . $params['gbmode'] . '&cid=' . $params['cid']);
            }
        }
        $this->includeCSS(['dataTables.bootstrap.css', 'course/items.css','gradebook.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'tablesorter.js', 'dataTables.bootstrap.js', 'general.js', 'gradebook/addgrades.js', 'gradebook/manageaddgrades.js', 'roster/managelatepasses.js']);
        $responseData = array('studentInformation' => $studentArray, 'course' => $course, 'assessmentData' => $assessmentData, 'assessmentLabel' => $assessmentLabel, 'assessmentId' => $assessmentId
        , 'gradeData' => $gradeData, 'finalStudentArray' => $finalStudentArray, 'gbcatsLabel' => $gbcatsLabel, 'sortorder' => $sortorder, 'hassection' => $hassection, 'defaultValuesArray' => $defaultValuesArray, 'gbcatsId' => $gbcatsId, 'rubricsLabel' => $rubricsLabel, 'rubricsId' => $rubricsId, 'pageOutcomesList' => $pageOutcomesList,
            'isDelete' => $isDelete, 'pageOutcomes' => $pageOutcomes, 'isteacher' => $isteacher, 'istutor' => $istutor, 'isTutorEdit' => $isTutorEdit, 'rubricFinalData' => $rubricFinalData, 'params' => $params, 'gbItems' => $gbItems);
        return $this->renderWithData('addGrades', $responseData);
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

    public function actionAddRubric()
    {
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $currentUser = $this->getAuthenticatedUser();
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $from = $params['from'];
        $isTeacher = $this->isTeacher($currentUser['id'],$courseId);
        if ($from=='modq') {
            $fromString = '&from=modq&aid='.$params['aid'].'&qid='.$params['qid'];
        } else if ($from=='addg') {
            $fromString = '&from=addg&gbitem='.$params['gbitem'];
        } else if ($from=='addf') {
            $fromString = '&from=addf&fid='.$params['fid'];
        }
        if (!$isTeacher) { // loaded by a NON-teacher
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } elseif (!(isset($params['cid']))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_PAGE_ACCESS;
        } else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

            if (isset($params['rubname'])) { //FORM SUBMITTED, DATA PROCESSING
                if (isset($params['rubisgroup'])) {
                    $rubgrp = $currentUser['groupid'];
                } else {
                    $rubgrp = AppConstant::NUMERIC_NEGATIVE_ONE;
                }
                $rubric = array();
                for ($i = AppConstant::NUMERIC_ZERO; $i < AppConstant::NUMERIC_FIFTEEN; $i++) {
                    if (!empty($params['rubitem'.$i])) {
                        $rubric[] = array(stripslashes($params['rubitem'.$i]), stripslashes($params['rubnote'.$i]), floatval($params['rubscore'.$i]));
                    }
                }
                $rubricstring =  serialize($rubric);
                if ($params['id']!='new') { //MODIFY
                    Rubrics::updateRubrics($params,$rubgrp,$rubricstring, $params['id']);
                } else {
                    $rubricEntry = new Rubrics();
                    $rubricEntry->insertInToRubric($currentUser['id'],$params,$rubgrp,$rubricstring);
                }
                $fromString = str_replace('&amp;','&',$fromString);
                return $this->redirect('add-rubric?cid='.$courseId.$fromString);
            } else { //INITIAL LOAD DATA PROCESS
                if (isset($params['id'])) { //MODIFY
                    if ($params['id']=='new') {//NEW
                        $rubric = array();
                        $rubname = "New Rubric";
                        $rubgrp = AppConstant::NUMERIC_NEGATIVE_ONE;
                        $rubtype = AppConstant::NUMERIC_ONE;
                        $savetitle = _('Create Rubric');
                    } else {
                        $rubid = intval($params['id']);
                        $rubricData = Rubrics::getById($rubid);
                        $rubname = $rubricData['name'];
                        $rubgrp = $rubricData['groupid'];
                        $rubtype = $rubricData['rubrictype'];
                        $rubric = $rubricData['rubric'];
                        $rubric = unserialize($rubric);
                        $savetitle = _('Save Changes');
                    }
                }
            }
        }
        $rubricsName = Rubrics::getByOwnerId($currentUser['id'],$currentUser['groupid']);
        $this->includeJS('gradebook/rubric.js');
        $this->includeCSS(['gradebook.css']);
        $responseData = array('from' => $from,'body' => $body,'fromstr' => $fromString,'overwriteBody' => $overwriteBody,'savetitle' => $savetitle,'course' => $course,'rubricsName' => $rubricsName,'rubtype' => $rubtype,'rubgrp' => $rubgrp,'params' => $params,'rubric' => $rubric,'rubname' => $rubname,'isTeacher' => $isTeacher);
        return $this->renderWithData('addRubric', $responseData);
    }

    public function actionQuickSearchAjax()
    {
        $courseId = $this->getRequestParams();
        $params = $this->getRequestParams();

        $likeStudentName = User::getByUserName($params['keyword']);
        $finalArray = array();
        foreach ($likeStudentName as $like) {
            array_push($finalArray, $like['FirstName']);
        }
        $responseData = $finalArray;
        return $this->successResponse($responseData);

    }

    public function actionUploadGrades()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
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
            if ($grades) {
                foreach ($grades as $grade) {
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
                $scoreColumn = $params['AddGradesForm']['gradesColumn'] - 1;
                $feedbackColumn = $params['AddGradesForm']['feedbackColumn'] - 1;
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
                    if (!$score) {
                        $score = ' ';
                    }
                    if ($studentData) {
                        $cuserid = $studentData['id'];
                        if (isset($curscores[$cuserid])) {
                            Grades::updateGradeToStudent($score, $feedback, $cuserid, $gbItemsId);
                            $successes++;
                        } else {
                            $gradeObject = new Grades();
                            $gradeObject->addGradeToStudent($cuserid, $gbItemsId, $feedback, $score);
                            $successes++;
                        }
                    } else {
                        $failures[] = $data[$usercol];
                    }
                }
            }
        }
        $this->includeCSS(['site.css', 'gradebook.css']);
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

//Controller method to assign lock on students.

    public function actionManageOfflineGrades()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
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
                $c = 0;
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
                        $c++;
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
                }
                if ($isCheckBoxChecked == false) {
                    $this->setErrorFlash('Select at least one option to modify');
                }else{
                    return $this->redirect('gradebook?cid='.$courseId);
                }
            } else {
                $this->setErrorFlash('Select atleast one grade to manage.');
            }
        }
        $this->includeCSS(['dataTables.bootstrap.css','gradebook.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js', 'gradebook/manageofflinegrades.js']);
        $responseData = array('model' => $model, 'gradeNames' => $gradeNames, 'course' => $course, 'gbcatsLabel' => $gbcatsLabel, 'gbcatsId' => $gbcatsId);
        return $this->renderWithData('manageOfflineGrades', $responseData);

    }

//Controller method to Unenroll students.

    public function actionMarkLockAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();
        foreach ($params['checkedStudents'] as $students) {
            Student::updateLocked($students, $params['courseId']);
        }
        return $this->successResponse();
    }

    public function actionMarkUnenrollAjax()
    {
        $this->layout = false;
        $params = $this->getRequestParams();

        foreach ($params['checkedStudents'] as $students) {
            Student::deleteStudent($students, $params['courseId']);
        }
        return $this->successResponse();
    }

//Controller method for gradebook comment

    public function actionGradeDeleteAjax()
    {
        $gradeType = 'offline';
        $params = $this->getRequestParams();
        foreach ($params['checkedMsg'] as $gradeId) {
            GbItems::deleteById($gradeId);
            Grades::deleteByGradeTypeIdAndGradeType($gradeId, $gradeType);
        }
        return $this->successResponse();
    }

//Controller method to upload gradebook comment

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
        $this->includeCSS(['gradebook.css']);
        $responseData = array('course' => $course, 'studentsInfo' => $studentsInfo, 'commentType' => $commentType);
        return $this->renderWithData('gbComments', $responseData);
    }

//Controller method for gradebook settings

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
                while (($data = fgetcsv($handle, 4096, ";")) !== FALSE || ($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
                    if(count($data) == 1){
                        $data = explode(',',$data[0]);
                    }
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
        $this->includeCSS(['gradebook.css']);
        $responseData = array('course' => $course, 'commentType' => $commentType, 'model' => $model, 'failures' => $failures, 'successes' => $successes, 'userCol' => $userCol);
        return $this->renderWithData('uploadComments', $responseData);
    }

    public function actionGbSettings()
    {
        $this->guestUserHandler();
        $this->layout = "master";
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
            $this->redirect(AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid=' . $course['id'] . '&refreshdef=true'));
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
        $this->layout = 'master';
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
        $this->includeCSS(['gradebook.css']);
        $responseData = array('course' => $course, 'model' => $model);
        return $this->renderWithData('uploadMultipleGrades', $responseData);
    }

    public function actionGradeBookStudentDetail()
    {
        global $get;
        $params = $this->getRequestParams();
        $get = $params;
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
            if ((isset($params['posted']) && $params['posted']=="Make Exception") || isset($params['massexception'])) {
                $calledfrom='gb';
                 $assesschk = $params['assesschk'];
                $_SESSION['assesschk']= $assesschk;
                $stusection = $params['stusection'];
                return $this->redirect(AppUtility::getURLFromHome('roster','roster/make-exception?cid=' . $courseId.'&gradebook='.$calledfrom.'&studentId='.$params['studentId'].'&section-data='.$stusection));
            }
            if (isset($params['user-comments']) && $userId > AppConstant::NUMERIC_ZERO) {
                $commentType = 'null';
                Student::updateGbComments($userId, $params['user-comments'], $courseId, $commentType);
            }
            $this->redirect('grade-book-student-detail?cid=' . $courseId . '&studentId=' . $userId);
        }
        $this->includeCSS(['dataTables.bootstrap.css', 'dashboard.css','gradebook.css']);
        $this->includeJS(['general.js?ver=012115', 'jquery.dataTables.min.js','dataTables.bootstrap.js', 'gradebook/gradebookstudentdetail.js']);
        $responseData = array('totalData' => $totalData,"params" => $params, 'course' => $course, 'currentUser' => $currentUser, 'StudentData' => $StudentData[0], 'defaultValuesArray' => $defaultValuesArray, 'contentTrackData' => $contentTrackData, 'stugbmode' => $stugbmode['stugbmode'], 'gbCatsData' => $gbCatsData, 'stugbmode' => $stugbmode, 'allStudentsinformation' => $allStudentsinformation);
        return $this->renderWithData('gradeBookStudentDetail', $responseData);
    }

    public function actionSendMessageModel()
    {
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $userId = $params['sendto'];
        $sendType = $params['sendtype'];
        $courseId = $params['cid'];
        $this->layout = 'master';
        $course = Course::getById($courseId);
        $receiverInformation = User::getById($userId);
        if ($this->isPostMethod()) {
            /*
             * Applied html lawed
             * $htmlawedconfig = array('elements' => '*-script');
             */
            $error = '';
            if ($params['sendtype'] == 'msg')
            {
                $newMessage = new Message();
                $newMessage->saveNewMessage($params, $currentUser);
                $this->setSuccessFlash('Message sent');
                return $this->redirect('grade-book-student-detail?cid=' . $courseId . '&studentId=' . $userId);
            } else if ($params['sendtype'] == 'email')
            {
                if ($receiverInformation['email'] != '' && $receiverInformation['email'] != 'none@none.com') {
                    $subject = stripslashes($params['subject']);
                    $message = stripslashes($params['message']);
                    AppUtility::sendMail($subject, $message, $receiverInformation['email']);
                    $this->setSuccessFlash('Email sent');
                    return $this->redirect('grade-book-student-detail?cid=' . $courseId . '&studentId=' . $userId);
                } else {
                    $error = AppUtility::t('Unable to send: Invalid email address');
                }
            }
            if (!$error == '')
            {
                echo $error;
            }
        }
        $this->includeCSS(['gradebook.css']);
        $this->includeJS(["editor/tiny_mce.js", "forum/addforum.js", "general.js"]);
        $responseData = array('receiverInformation' => $receiverInformation, 'params' => $params, 'course' => $course);
        return $this->renderWithData('sendMessageModel', $responseData);
    }

    public function actionNewFlag()
    {
        //recording a toggle.  Called via AHAH
        $courseId = $this->getParamVal('cid');
        Course::updateNewFlag($courseId);
        $this->redirect(AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid=' . $courseId));
    }

    public function actionManageAddGrades()
    {
        $params = $this->getRequestParams();
        $gbItems = GbItems::getById($params['gbitem']);

        if ($gbItems['rubric'] != 0) {
            $rubricsData = Rubrics::getById($gbItems['rubric']);
            $rubricData = array(
                '0' => $rubricsData['id'],
                '1' => $rubricsData['rubrictype'],
                '2' => $rubricsData['rubric'],
            );

        }
        $count = 0;
        $hassection = false;
        $studentsDataForHasSection = User::getByUserIdAndStudentId($params['cid']);
        foreach ($studentsDataForHasSection as $student) {
            if ($student['section'] != null) {
                $count++;
                $hassection = true;
            }
        }
        if ($hassection) {
            $gbSchemeData = GbScheme::getByCourseId($params['cid']);
            if ($gbSchemeData['usersort'] == 0) {
                $sortorder = "sec";
            } else {
                $sortorder = "name";
            }
        } else {
            $sortorder = "name";
        }

        $gradeData = Grades::getByGradeTypeIdAndUserId($params['gbitem'], $params['grades']);
        $studentsData = Student::getByCourseAndGrades($params['cid'], $params['grades'], $hassection, $sortorder);
        foreach ($studentsData as $singleStudent) {
            $finalStudentArray = array(
                '0' => $singleStudent['id'],
                '1' => $singleStudent['LastName'],
                '2' => $singleStudent['FirstName'],
                '3' => $singleStudent['section'],
                '4' => $singleStudent['locked'],
            );

        }
        if ($this->isPostMethod()) {
            $studentId = $params['stu'];
            $score = $params['score'][$studentId];
            if ($score == null) {
                $score = ' ';
            }
            Grades::updateGradeToStudent($score, $params['feedback'][$studentId], $studentId, $params['gbitem']);
        }
        $this->includeCSS(['dataTables.bootstrap.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js', 'gradebook/addgrades.js']);
        $responseData = array('gbItems' => $gbItems, 'finalStudentArray' => $finalStudentArray, 'params' => $params, 'gradeData' => $gradeData, 'rubricData' => $rubricData, 'hassection' => $hassection, 'sortorder' => $sortorder);
        return $this->renderWithData('manageAddGrades', $responseData);
    }

    public function  actionFetchGradebookDataAjax()
    {
        $params = $this->getRequestParams();
        $responseData = $this->gbtable($params['userId'], $params['courseId']);
        return $this->successResponse($responseData);
    }

    public function actionGradebookViewAssessmentDetails()
    {
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $teacherid = Teacher::getByUserId($currentUser['id'], $courseId);
        $tutorid = Tutor::getByUserId($currentUser['id'], $courseId);
        if (isset($teacherid)) {
            $isteacher = true;
        }
        if ($tutorid) {
            $istutor = true;
        }
        $asid = intval($params['asid']);
        if (!isset($params['uid']) && !$isteacher && !$istutor) {
            $params['uid'] = $currentUser['id'];
        }
        if ($isteacher || $istutor) {
            if (isset($sessionData[$courseId . 'gbmode'])) {
                $gbmode = $sessionData[$courseId . 'gbmode'];
            } else {
                $gbSchemeData = GbScheme::getByCourseId($courseId);
                $gbmode = $gbSchemeData['defgbmode'];
            }

            if (isset($params['stu']) && $params['stu'] != '') {
                $stu = $params['stu'];
            } else {
                $stu = 0;
            }

            if (isset($params['from'])) {
                $from = $params['from'];
            } else {
                $from = 'gb';
            }
            //Gbmode : Links NC Dates
            $totonleft = floor($gbmode / 1000) % 10; //0 right, 1 left
            $links = ((floor($gbmode / 100) % 10) & 1); //0: view/edit, 1 q breakdown
            $hidenc = (floor($gbmode / 10) % 10) % 4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
            $availshow = $gbmode % 10; //0: past, 1 past&cur, 2 all
        } else {
            $links = 0;
            $stu = 0;
            $from = 'gb';
            $now = time();
        }

        $assessmentData = Assessments::getByCourseIdJoinWithSessionData($params['asid'], $currentUser['id'], $isteacher, $istutor);

        if (!$isteacher && !$istutor) {
            $rv = new ContentTrack;
            $rv->insertFromGradebook($currentUser->id, $courseId, 'gbviewasid', $assessmentData['assessmentid'], time());
        }
        $student = Student::getByCourseId($courseId, $params['uid']);
        $studentUserData = User::getById($student['userid']);
        $studentData = array(
            '0' => $studentUserData->FirstName,
            '1' => $studentUserData->LastName,
            '2' => $student->timelimitmult,
        );
        if ($isteacher || ($istutor && $assessmentData['tutoredit'] == 1)) {
            $canedit = 1;
        } else {
            $canedit = 0;
        }
        if ($asid=="new" && $isteacher)
        {
            //student could have started, so better check to make sure it still doesn't exist
            $aid = $params['aid'];
            $newAssessmentId = AssessmentSession::getIdByUserIdAndAid($params['uid'],$aid);
            if ($newAssessmentId > 0)
            {
                $params['asid'] = $newAssessmentId;
            } else {
                $assessmentInformation = Assessments::getByAssessmentId($aid);
                $stugroupmem = array();
                $agroupid = 0;
                if ($assessmentInformation['isgroup']>0) { //if is group assessment, and groups already exist, create asid for all in group
                    $stuGroup = Stugroups::getStuGrpDataForGradebook($params['uid'],$assessmentInformation['groupsetid']);
                    if ($stuGroup > 0) { //group exists
                        $agroupid = $stuGroup['id'];
                        $stuGroupMembers = StuGroupMembers::getUserId($agroupid,$params['uid']);
                        foreach ($stuGroupMembers as $stuGroupMember ) {
                            $stugroupmem[] = $stuGroupMember['userid'];
                        }
                    }
                }
                $stugroupmem[] = $params['uid'];
                list($qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist) = generateAssessmentData($assessmentInformation['itemorder'],$assessmentInformation['shuffle'],$aid);
                foreach ($stugroupmem as $uid) {
                    $insertInSession = new AssessmentSession();
                    $asid = $insertInSession->createSessionForGradebook($uid,$agroupid,$aid,$qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist);

                }
                $params['asid'] = $asid;
            }
            $this->redirect('gradebook-view-assessment-details?stu='.$stu.'&asid='.$params['asid'].'&from='.$from.'&cid='.$course->id.'&uid='.$params['uid']);
        }

        if (($isteacher || $istutor) && !isset($params['lastver']) && !isset($params['reviewver'])) {
            if ($assessmentData['agroupid']>0)
            {
                $groupMembers = AssessmentSession::getUserForGradebook($aid,$assessmentData['agroupid']);
            }
        }
        $exceptionData = Exceptions::getByAssessmentIdAndUserId($params['uid'], $assessmentData['assessmentid']);
        $questions = Questions::getByQuestionsIdAndAssessmentId($assessmentData['assessmentid']);
            $questionsData = array();
            foreach ($questions as $question) {
                $tempArray = array(
                    '0' => $question['id'],
                    '1' => $question['points'],
                    '2' => $question['withdrawn'],
                    '3' => $question['qtype'],
                    '4' => $question['control'],
                    '5' => $question['rubric'],
                    '6' => $question['showhints'],
                    '7' => $question['extref'],
                    '8' => $question['ownerid'],
                );
                array_push($questionsData, $tempArray);
            }
        $questionIdArray = explode(',', $assessmentData['questions']);
        $librariesName = array();
        foreach ($questionIdArray as $questionId) {
            $libraryName = Questions::getByLibrariesIdAndcategory($questionId);
            $tempArray = array(
                '0' => $libraryName['questionsetid'],
                '1' => $libraryName['category'],
                '2' => $libraryName['name'],
                'questionId' => $questionId,

            );
            array_push($librariesName, $tempArray);
        }
        $assessmentSessionData = AssessmentSession::getById($params['asid']);
        $countOfQuestion = Questions::numberOfQuestionByIdAndCategory($assessmentData['assessmentid']);
        if ($assessmentSessionData['agroupid']) {
            $pers = 'group';
            $studentNameWithAssessmentName = $this->getconfirmheader(true, $isteacher, $istutor, $currentUser, $params);
        } else {
            $pers = 'student';
            $studentNameWithAssessmentName = $this->getconfirmheader(false, $isteacher, $istutor, $currentUser, $params);
        }
        if (isset($_GET['starttime']) && $isteacher)
        {
            $agroupid = $assessmentSessionData['agroupid'];
            $aid= $assessmentSessionData['assessmentid'];
            if ($agroupid>0)
            {
                $qp = array('agroupid',$agroupid,$aid);
            } else {
                $qp = array('id',$asid,$aid);
            }
            AssessmentSession::updateStartTime($params['starttime'],$qp);
        }
        $sessionId = $this->getSessionId();
        global $sessionId, $sessionData, $testsettings;
        $testsettings = Assessments::getByAssessmentId($assessmentSessionData['assessmentid']);
        if (isset($params['clearattempt']) && isset($params['asid']) && $isteacher) {

            if ($params['clearattempt'] == "confirmed") {
                $assessmentSession = AssessmentSession::getByAssessmentIdAndCourseId($params['asid'], $courseId);
                if ($assessmentSession) {
                    $aid = $assessmentSession['assessmentid'];
                    $ltisourcedid = $assessmentSession['lti_sourcedid'];
                    $sessionId = $this->getSessionId();
                    $sessionData = $this->getSessionData($sessionId);

                    if (strlen($ltisourcedid) > 1) {
                        LtiOutcomesUtility::updateLTIgrade('delete', $ltisourcedid, $aid);
                    }
                    $agroupid = $assessmentSessionData['agroupid'];
                    $aid = $assessmentSessionData['assessmentid'];
                    if ($agroupid > 0) {
                        $assessmentArray = array('agroupid', $agroupid, $aid);
                    } else {
                        $assessmentArray = array('id', $params['asid'], $aid);
                    }
                    filehandler::deleteasidfilesbyquery2($assessmentArray[0], $assessmentArray[1], $assessmentArray[2], 1);
                    AssessmentSession::deleteByAssessment($assessmentArray);
                    if ($from == 'isolate') {
                        return $this->redirect('isolate-assessment-grade?stu=' . $stu . '&cid=' . $params['cid'] . '&aid=' . $aid . '&gbmode=' . $gbmode);
                    } else if ($from == 'gisolate') {
                        /*
                         * Redirected on isolateassessbygroup page with following parametera
                         * isolateassessbygroup.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode
                         */

                    } else if ($from == 'stugrp') {
                        return $this->redirect(AppUtility::getURLFromHome('groups', 'groups/manage-student-groups?cid=' . $params['cid'] . '&aid=' . $aid));
                    } else {
                        return $this->redirect('gradebook?stu=' . $stu . '&cid=' . $params['cid'] . '&gbmode=' . $gbmode);
                    }
                }
            }
        }
        if (isset($params['breakfromgroup']) && isset($params['asid']) && $isteacher) {
            if ($params['breakfromgroup'] == "confirmed")
            {
                 StuGroupMembers::removeGrpMember($assessmentSessionData['userid'], $assessmentSessionData['agroupid']);
                //update any assessment sessions using this group
                AssessmentSession::updateAssSessionForGrpByGrpIdAndUid($assessmentSessionData['userid'], $assessmentSessionData['agroupid']);
                $now = time();
                if (isset($GLOBALS['CFG']['log']))
                {
                    $log = new Log();
                    $log->insertEntry($now,$assessmentSessionData['userid'], $assessmentSessionData['agroupid']);
                }
                return $this->redirect('gradebook-view-assessment-deyails?stu='.$stu.'&asid='.$params['asid'].'&from='.$from.'&cid='.$courseId.'&uid='.$params['uid']);
            }
        }
        if (isset($params['clearscores']) && isset($params['asid']) && $isteacher) {
            if ($_GET['clearscores'] == "confirmed") {

                $assessmentSessionDataForClearScores = AssessmentSession::getAssessmentIDForClearScores($params['asid'],$courseId);
                if ($assessmentSessionDataForClearScores) { //check that is the right cid
                    $agroupid =  $assessmentSessionData['agroupid'];
                    $aid= $assessmentSessionData['assessmentid'];
                    if ($agroupid>0) {
                        $qp =  array('agroupid',$agroupid,$aid);
                    } else {
                        $qp =  array('id',$asid,$aid);
                    }
                    filehandler::deleteasidfilesbyquery2($qp[0], $qp[1], $qp[2], 1);
                    $ltisourcedIdAndSeed = AssessmentSession::getltisourcedIdAndSeed($qp);
                    $seeds = explode(',', $ltisourcedIdAndSeed['seeds']);
                    $ltisourcedid = $ltisourcedIdAndSeed['lti_sourcedid'];
                    if (strlen($ltisourcedid) > 1) {
                        LtiOutcomesUtility::updateLTIgrade('update', $ltisourcedid, $aid, 0);
                    }
                    $scores = array_fill(0, count($seeds), -1);
                    $attempts = array_fill(0, count($seeds), 0);
                    $lastanswers = array_fill(0, count($seeds), '');
                    $scorelist = implode(",", $scores);
                    $attemptslist = implode(",", $attempts);
                    $lalist = implode("~", $lastanswers);
                    $bestscorelist = implode(',', $scores);
                    $bestattemptslist = implode(',', $attempts);
                    $bestseedslist = implode(',', $seeds);
                    $bestlalist = implode('~', $lastanswers);
                    AssessmentSession::updateForClearScores($qp,$scorelist,$scorelist,$attemptslist,$lalist,$bestscorelist,$bestattemptslist,$bestseedslist,$bestlalist);
                }
                return $this->redirect('gradebook-view-assessment-details?stu='.$stu.'&asid='.$params['asid'].'&from='.$from.'&cid='.$course->id.'&uid='.$params['uid']);
            }
        }
        $defaultValuesArray = array(
            'isteacher' => $isteacher,
            'gbmode' => $gbmode,
            'stu' => $stu,
            'istutor' => $istutor,
            'from' => $from,
            'totonleft' => $totonleft,
            'links' => $links,
            'hidenc' => $hidenc,
            'availshow' => $availshow,
            'asid' => $asid,
            'groupId' => $assessmentSessionData['agroupid'],
            'studentNameWithAssessmentName' => $studentNameWithAssessmentName,
            'pers' => $pers
        );
        $oktorec = false;
        if ($isteacher) {
            $oktorec = true;
        } else if ($istutor) {
            $tutorEdit = Assessments::getByAssessmentId($assessmentSessionData['assessmentid']);
            if ($tutorEdit['tutoredit'] == 1)
            {
                $oktorec = true;
            }
        }
        if (isset($params['update']) && ($isteacher || $istutor) && $links==0)
        {
            if ($oktorec)
            {
                $bestscores = $assessmentSessionData['bestscores'];
                $bsp = explode(';',$bestscores);
                $scores = array();
                $i = 0;
                while (isset($params[$i]) || isset($params["$i-0"])) {
                    $j=0;
                    $scpt = array();
                    if (isset($params["$i-0"])) {

                        while (isset($params["$i-$j"])) {
                            if ($params["$i-$j"]!='N/A' && $params["$i-$j"]!='NA') {
                                $scpt[$j] = $params["$i-$j"];
                            } else {
                                $scpt[$j] = -1;
                            }
                            $j++;
                        }
                        $scores[$i] = implode('~',$scpt);
                    } else {
                        if ($params[$i]!='N/A' && $params["$i-$j"]!='NA') {
                            $scores[$i] = $params[$i];
                        } else {
                            $scores[$i] = -1;
                        }
                    }
                    $i++;
                }
                $scorelist = implode(",",$scores);
                if (count($bsp)>1) { //tack on rawscores and firstscores
                    $scorelist .= ';'.$bsp[1].';'.$bsp[2];
                }
                $feedback = $params['feedback'];

                if (isset($params['updategroup']))
                {
                    $agroupid = $assessmentSessionData['agroupid'];
                    $aid = $assessmentSessionData['assessmentid'];
                    if ($agroupid > 0) {
                        $qp = array('agroupid', $agroupid, $aid);
                    } else {
                        $qp = array('id', $params['asid'], $aid);
                    }
                    AssessmentSession::setBestScoreAndFeedbackUsingGroup($scorelist,$feedback,$qp);
                } else {
                    AssessmentSession::setBestScoreAndFeedback($scorelist,$feedback,$params['asid']);
                }

                $aid = $assessmentSessionData['assessmentid'];
                if (strlen($assessmentSessionData['lti_sourcedid'])>1) {
                    //update LTI score
                    LtiOutcomesUtility::calcandupdateLTIgrade($assessmentSessionData['lti_sourcedid'],$assessmentSessionData['assessmentid'],$scores);
                }
            } else {
                $this->setWarningFlash('No authority to change scores');
                return $this->redirect('gradebook-view-assessment-details?stu='.$stu.'&asid='.$params['asid'].'&from='.$from.'&cid='.$course->id.'&uid='.$params['uid']);
            }
            if ($from=='isolate') {
                return $this->redirect('isolate-assessment-grade?stu='.$stu.'&cid='.$params['cid'].'&aid='.$aid.'&gbmode='.$gbmode);
            } else if ($from=='gisolate') {
                /*
                * Redirected on isolateassessbygroup page with following parametera
                * isolateassessbygroup.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode
                */
            } else if ($from=='stugrp') {
                return $this->redirect('manage-student-groups?cid='.$params['cid'].'&aid='.$aid);
            } else {
                return $this->redirect('gradebook?stu='.$stu.'&cid='.$params['cid'].'&gbmode='.$gbmode);
            }
        }
        if (isset($params['clearq']) && isset($params['asid']) && $isteacher) {
            if ($params['confirmed'] == "true")
            {
                $agroupid = $assessmentSessionData['agroupid'];
                $aid= $assessmentSessionData['assessmentid'];
                if ($agroupid>0) {
                    $qp = array('agroupid',$agroupid,$aid);
                    //return (" WHERE agroupid='$agroupid'");
                } else {
                    $qp = array('id',$asid,$aid);
                    //return (" WHERE id='$asid' LIMIT 1");
                }
                $line = AssessmentSession::getAssessmentIDAndAsidForClearScores($qp);
                if (strpos($line['scores'], ';') === false) {
                    $noraw = true;
                    $scores = explode(",", $line['scores']);
                    $bestscores = explode(",", $line['bestscores']);
                } else {
                    $sp = explode(';', $line['scores']);
                    $scores = explode(',', $sp[0]);
                    $rawscores = explode(',', $sp[1]);
                    $sp = explode(';', $line['bestscores']);
                    $bestscores = explode(',', $sp[0]);
                    $bestrawscores = explode(',', $sp[1]);
                    $firstrawscores = explode(',', $sp[2]);
                    $noraw = false;
                }
                $attempts = explode(",", $line['attempts']);
                $lastanswers = explode("~", $line['lastanswers']);
                $reattempting = explode(',', $line['reattempting']);
                $bestattempts = explode(",", $line['bestattempts']);
                $bestlastanswers = explode("~", $line['bestlastanswers']);

                $clearid = $params['clearq'];

                if ($clearid !== '' && is_numeric($clearid) && isset($scores[$clearid])) {
                    filehandler::deleteasidfilesfromstring2($lastanswers[$clearid] . $bestlastanswers[$clearid], $qp[0], $qp[1], $qp[2]);
                    $scores[$clearid] = -1;
                    $attempts[$clearid] = 0;
                    $lastanswers[$clearid] = '';
                    $bestscores[$clearid] = -1;
                    $bestattempts[$clearid] = 0;
                    $bestlastanswers[$clearid] = '';
                    if (!$noraw) {
                        $rawscores[$clearid] = -1;
                        $bestrawscores[$clearid] = -1;
                        $firstscores[$clearid] = -1;
                    }
                    $loc = array_search($clearid, $reattempting);
                    if ($loc !== false) {
                        array_splice($reattempting, $loc, 1);
                    }
                    if (!$noraw) {
                        $scorelist = implode(",", $scores) . ';' . implode(",", $rawscores);
                        $bestscorelist = implode(',', $bestscores) . ';' . implode(",", $bestrawscores) . ';' . implode(",", $firstscores);
                    } else {
                        $scorelist = implode(",", $scores);
                        $bestscorelist = implode(',', $bestscores);
                    }
                    $attemptslist = implode(",", $attempts);
                    $lalist = addslashes(implode("~", $lastanswers));

                    $bestattemptslist = implode(',', $bestattempts);
                    $bestlalist = addslashes(implode('~', $bestlastanswers));
                    $reattemptinglist = implode(',', $reattempting);
                    AssessmentSession::updateForClearScore($qp,$scorelist,$scorelist,$attemptslist,$lalist,$bestscorelist,$bestattemptslist,$reattemptinglist,$bestlalist);
                    if (strlen($line['lti_sourcedid']) > 1) {

                        LtiOutcomesUtility::calcandupdateLTIgrade($line['lti_sourcedid'], $aid, $bestscores);
                    }
                    $this->redirect('gradebook-view-assessment-details?stu='.$stu.'&asid='.$params['asid'].'&from='.$from.'&cid='.$course->id.'&uid='.$params['uid']);
                } else {
                    $this->setWarningFlash(' Error.  Try again.');
                }
                unset($params['clearq']);
            }
        }
        if ($canedit) {
            $rubrics = Rubrics::rubricDataByAssessmentId($assessmentData['assessmentid']);
        }

        if($links == 1){
            $currentData = User::getById($params['uid']);
            $assessmentAndAssessmentSessionData = AssessmentSession::getAssessmentData($params['asid']);
            if (!$isteacher && !$istutor) {
                $contentTrack = new ContentTrack;
                $contentTrack->insertFromGradebook($params['uid'],$course->id,'gbviewasid',$assessmentAndAssessmentSessionData['assessmentid'],time());
                $questionIds = Questions::numberOfQuestionByIdAndCategory($assessmentAndAssessmentSessionData['assessmentid']);
                $questionsInformation = Questions::retrieveQuestionDataForgradebook($assessmentAndAssessmentSessionData['questions']);
            }
        }

        $this->includeJS(['general.js','gradebook/rubric.js']);
        $resposeData = array('course' => $course,'countOfQuestion' => $countOfQuestion, 'groupMembers' =>$groupMembers, 'librariesName' => $librariesName,
            'questionsData' => $questionsData,'questionsInformation' => $questionsInformation, 'exceptionData' => $exceptionData, 'studentData' => $studentData, 'params' => $params,
            'assessmentData' => $assessmentData,';assessmentAndAssessmentSessionData' => $assessmentAndAssessmentSessionData,'canedit' => $canedit,'rubricsData' => $rubrics,
            'currentData' => $currentData, 'defaultValuesArray' => $defaultValuesArray,'questionIds' => $questionIds);
        return $this->renderWithData('gradebookViewAssessmentDetails', $resposeData);
    }
    public function getconfirmheader($group,$isteacher,$istutor,$user,$params) {
        if ($group) {
            $studentNameWithAssessmentName = '<h3>Whole Group</h3>';
        } else {
            $studentUserData = User::getById($params['uid']);
            $studentNameWithAssessmentName = "<h3>{$studentUserData['LastName']}, {$studentUserData['FirstName']}</h3>";
        }
        $assessmentName = AssessmentSession::getByIdAndUserId($params['asid'],$user->id,$isteacher,$istutor);
        $studentNameWithAssessmentName .= "<h4>".$assessmentName['name']."</h4>";
        return $studentNameWithAssessmentName;
    }

    public function actionItemAnalysis()
    {
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = false;
        $teacher = $this->isTeacher($currentUser['id'],$courseId);
        $this->layout = 'master';
        if($teacher){
            $isTeacher = true;
        }

        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        if (isset($sessionData[$courseId.'gbmode'])) {
            $gbmode =  $sessionData[$courseId.'gbmode'];
        } else {
            $gbModeData = GbScheme::getByCourseId($courseId);
            $gbmode = $gbModeData['defgbmode'];
        }
        if (isset($params['stu']) && $params['stu']!='') {
            $student = $params['stu'];
        } else {
            $student = 0;
        }
        if (isset($params['from'])) {
            $from = $params['from'];
        } else {
            $from = 'gb';
        }
        $catfilter = -1;
        if (isset($tutorsection) && $tutorsection!='') {
            $secfilter = $tutorsection;
        } else {
            if (isset($params['secfilter'])) {
                $secfilter = $params['secfilter'];
                $sessiondata[$courseId.'secfilter'] = $secfilter;
                $enc = base64_encode(serialize($sessiondata));
                Sessions::setSessionId($sessionId,$enc);
            } else if (isset($sessiondata[$courseId.'secfilter'])) {
                $secfilter = $sessiondata[$courseId.'secfilter'];
            } else {
                $secfilter = -1;
            }
        }
//Gbmode : Links NC Dates
        $totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
        $links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
        $hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
        $availshow = $gbmode%10; //0: past, 1 past&cur, 2 all
        $pagetitle = "Gradebook";
        $qtotal = array();
        $qcnt = array();
        $tcnt = array();
        $qincomplete = array();
        $timetaken = array();
        $timeontask = array();
        $attempts = array();
        $regens = array();
        $assessmentData = Assessments::getByAssessmentId($assessmentId);
        $itemarr = array();
        $itemnum = array();
        $itemorder = $assessmentData['itemorder'];
        foreach (explode(',',$itemorder) as $k=>$itel) {
            if (strpos($itel,'~')!==false) {
                $sub = explode('~',$itel);
                if (strpos($sub[0],'|')!==false) {
                    array_shift($sub);
                }
                foreach ($sub as $j=>$itsub) {
                    $itemarr[] = $itsub;
                    $itemnum[$itsub] = ($k+1).'-'.($j+1);
                }
            } else {
                $itemarr[] = $itel;
                $itemnum[$itel] = ($k+1);
            }
        }
        $StudentCount = Student::getStudentCountUsingCourseIdAndLockedStudent($courseId,$secfilter);
        $totstucnt = count($StudentCount);
        $assessmentSessionData = AssessmentSession::getByAssessmentUsingStudentJoin($courseId,$assessmentId,$secfilter);
        foreach($assessmentSessionData as $singleAssessmentSessionData){
            if (strpos( $singleAssessmentSessionData['questions'],';')===false) {
                $questions = explode(",",$singleAssessmentSessionData['questions']);
            } else {
                list($questions,$bestquestions) = explode(";",$singleAssessmentSessionData['questions']);
                $questions = explode(",",$bestquestions);
            }
            $sp = explode(';', $singleAssessmentSessionData['bestscores']);
            $scores = explode(',', $sp[0]);
            $attp = explode(',',$singleAssessmentSessionData['bestattempts']);
            $bla = explode('~',$singleAssessmentSessionData['bestlastanswers']);
            $timeot = explode(',',$singleAssessmentSessionData['timeontask']);
            foreach ($questions as $k=>$ques) {
                if (trim($ques)=='') {continue;}

                if (!isset($qincomplete[$ques])) { $qincomplete[$ques]=0;}
                if (!isset($qtotal[$ques])) { $qtotal[$ques]=0;}
                if (!isset($qcnt[$ques])) { $qcnt[$ques]=0;}
                if (!isset($tcnt[$ques])) { $tcnt[$ques]=0;}
                if (!isset($attempts[$ques])) { $attempts[$ques]=0;}
                if (!isset($regens[$ques])) { $regens[$ques]=0;}
                if (!isset($timeontask[$ques])) { $timeontask[$ques]=0;}
                if (strpos($scores[$k],'-1')!==false) {
                    $qincomplete[$ques] += 1;
                }
                $qtotal[$ques] += $this->getpts($scores[$k]);
                $attempts[$ques] += $attp[$k];
                $regens[$ques] += substr_count($bla[$k],'ReGen');
                $qcnt[$ques] += 1;
                $timeot[$k] = explode('~',$timeot[$k]);
                $tcnt[$ques] += count($timeot[$k]);
                $timeontask[$ques] += array_sum($timeot[$k]);
            }
            if ($singleAssessmentSessionData['endtime'] >0 && $singleAssessmentSessionData['starttime'] > 0) {
                $timetaken[] = $singleAssessmentSessionData['endtime']-$singleAssessmentSessionData['starttime'];
            } else {
                $timetaken[] = 0;
            }
        }

        $vidcnt = array();
        if (count($qcnt)>0) {
            $qlist = implode(',', array_keys($qcnt));
            $contentTrackData = ContentTrack::getCourseIdUsingStudentTableJoin($courseId,$qlist,$secfilter);
            foreach($contentTrackData as $row){
                $vidcnt[$row['typeid']]= count($row['userid']);
            }
        }
        $numberOfQuestions = Questions::numberOfQuestionByIdAndCategory($assessmentId);
        $notstarted = ($totstucnt - count($timetaken));
        $nonstartedper = round(100*$notstarted/$totstucnt,1);
        $qslist = implode(',',$itemarr);
        if($qslist) {
            $questionSet = QuestionSet::getByQuestionId($qslist);
            $questionData = array();
            foreach ($questionSet as $singleQuestionSet) {
                $tempArray = array(
                    '0' => $singleQuestionSet['description'],
                    '1' => $singleQuestionSet['id'],
                    '2' => $singleQuestionSet['points'],
                    '3' => $singleQuestionSet['qid'],
                    '4' => $singleQuestionSet['withdrawn'],
                    '5' => $singleQuestionSet['qtype'],
                    '6' => $singleQuestionSet['control'],
                    '7' => $singleQuestionSet['showhints'],
                    '8' => $singleQuestionSet['extref'],
                );
                array_push($questionData, $tempArray);
            }
        }
        $this->includeJS(["general.js"]);
        $this->includeCSS(['gradebook.css', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('from' => $from,'course' => $course,'questionData' => $questionData,  'qtotal' => $qtotal,'itemarr' => $itemarr,'assessmentData' => $assessmentData,'nonstartedper' => $nonstartedper,'notstarted' => $notstarted,'numberOfQuestions' => $numberOfQuestions,'isTeacher' => $isTeacher,'courseId' => $courseId,'assessmentId' => $assessmentId,'student' => $student);
        return $this->renderWithData('itemAnalysis',$responseData);
    }

    public function actionItemAnalysisDetail()
    {
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $questionId = $params['qid'];
        $type = $params['type'];
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = false;
        $teacher = $this->isTeacher($currentUser['id'],$courseId);
//        $this->layout = 'master';
        if($teacher){
            $isTeacher = true;
        }
        $catfilter = -1;
        if (isset($tutorsection) && $tutorsection!='') {
            $secfilter = $tutorsection;
        } else {
            if (isset($params['secfilter'])) {
                $secfilter = $params['secfilter'];
                $sessiondata[$courseId.'secfilter'] = $secfilter;
                $sessionId = $this->getSessionId();
                $sessionData = $this->getSessionData($sessionId);
                $enc = base64_encode(serialize($sessionData));
                Sessions::setSessionId($sessionId,$enc);
            } else if (isset($sessiondata[$courseId.'secfilter'])) {
                $secfilter = $sessiondata[$courseId.'secfilter'];
            } else {
                $secfilter = -1;
            }
        }
        $students = array();
        if ($type=='notstart') {
            $StudentIds = Student::getByUserIdUsingAssessmentSessionJoin($courseId,$assessmentId,$secfilter);
            foreach($StudentIds as $StudentId){
                $students[] = $StudentId['userid'];
            }
            $studentNames = $this->getstunames($students);
        } else if ($type=='help') {
            $StudentIds = ContentTrack::getDistinctUserIdUsingCourseIdAndQuestionId($courseId,$questionId,$secfilter);
            foreach($StudentIds as $StudentId){
                $students[] = $StudentId['userid'];
            }
            $studentNames = $this->getstunames($students);
        } else {

            $stuincomp = array();
            $stuscores = array();
            $stutimes = array();
            $sturegens = array();
            $stuatt = array();
            $assessmentSessionData = AssessmentSession::getByAssessmentUsingStudentJoin($courseId,$assessmentId,$secfilter);
            foreach($assessmentSessionData as $line){
                if (strpos($line['questions'],';')===false) {
                    $questions = explode(",",$line['questions']);
                } else {
                    list($questions,$bestquestions) = explode(";",$line['questions']);
                    $questions = explode(",",$bestquestions);
                }
                $sp = explode(';', $line['bestscores']);
                $scores = explode(',', $sp[0]);
                $attp = explode(',',$line['bestattempts']);
                $bla = explode('~',$line['bestlastanswers']);
                $timeot = explode(',',$line['timeontask']);
                $k = array_search($questionId, $questions);
                if ($k===false) {continue;}
                if (strpos($scores[$k],'-1')!==false) {
                    $stuincomp[$line['userid']] = 1;
                } else {
                    $stuscores[$line['userid']] = $this->getpts($scores[$k]);
                    $stuatt[$line['userid']] = $attp[$k];
                    $sturegens[$line['userid']] = substr_count($bla[$k],'ReGen');

                    $timeot[$k] = explode('~',$timeot[$k]);

                    $stutimes[$line['userid']] = array_sum($timeot[$k]);
                }
            }
            if ($type=='incomp') {
                $studentNames = $this->getstunames(array_keys($stuincomp));
            } else if ($type=='score') {
                $studentNames = $this->getstunames(array_keys($stuscores));
            } else if ($type=='att') {
                $studentNames = $this->getstunames(array_keys($stuatt));
            } else if ($type=='time') {
                $studentNames = $this->getstunames(array_keys($stutimes));
            }
        }
        $responseData = array('studentNames' => $studentNames,'studentTimes' => $stutimes,'studentReGens' => $sturegens,'studentAttribute' => $stuatt,'studentScores' => $stuscores,
            'courseId' => $courseId,'assessmentId' => $assessmentId,'qid' => $questionId,'type' => $type,'secfilter' => $secfilter,'course' => $course,'isTeacher' => $isTeacher,);
        return $this->renderWithData('itemAnalysisDetail',$responseData);
    }

    public function getstunames($a) {
        if (count($a)==0) { return array();}
        $a = implode(',',$a);
        $StudentNames = User::getNameByIdUsingINClause($a);
        $names = array();
        foreach($StudentNames as $StudentName) {
            $names[$StudentName['id']] = $StudentName['LastName'].', '.$StudentName['FirstName'];
        }
        return $names;
    }

    public function actionIsolateAssessmentGrade()
    {
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = false;
        $teacher = $this->isTeacher($currentUser['id'],$courseId);
        $this->layout = 'master';
        if($teacher){
            $isTeacher = true;
        }
        $isTutor = false;
        $tutor = $this->isTutor($currentUser['id'],$courseId);
        if($tutor){
            $isTutor = true;
        }
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        if (isset($params['gbmode']) && $params['gbmode']!='') {
            $gbmode = $params['gbmode'];
        } else if (isset($sessionData[$courseId.'gbmode'])) {
            $gbmode =  $sessionData[$courseId.'gbmode'];
        } else {
            $gbModeData = GbScheme::getByCourseId($courseId);
            $gbmode = $gbModeData['defgbmode'];
        }
        $hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
        $query = Student::findByCid($courseId);

        if ($query) {
            $countSection = AppConstant::NUMERIC_ZERO;
            foreach ($query as $singleData) {
                if ($singleData->section != null || $singleData->section != "") {
                    $countSection++;
                }
            }
        }
        if ($countSection > AppConstant::NUMERIC_ZERO) {
            $hassection = true;
        } else {
            $hassection = false;
        }
        if ($hassection) {
            $gbScheme = GbScheme::getByCourseId($courseId);
            if ($gbScheme['usersort']==0) {
                $sortorder = "sec";
            } else {
                $sortorder = "name";
            }
        } else {
            $sortorder = "name";
        }
        $assessmentData = Assessments::getByAssessmentId($assessmentId);
        $minscore = $assessmentData['minscore'];
        $timelimit = $assessmentData['timelimit'];
        $deffeedback = $assessmentData['deffeedback'];
        $enddate = $assessmentData['enddate'];
        $name = $assessmentData['name'];
        $defpoints = $assessmentData['defpoints'];
        $itemorder = $assessmentData['itemorder'];
        $deffeedback = explode('-',$deffeedback);
        $assessmenttype = $deffeedback[0];
        $aitems = explode(',',$itemorder);
        foreach ($aitems as $k=>$v) {
            if (strpos($v,'~')!==FALSE) {
                $sub = explode('~',$v);
                if (strpos($sub[0],'|')===false) { //backwards compat
                    $aitems[$k] = $sub[0];
                    $aitemcnt[$k] = 1;

                } else {
                    $grpparts = explode('|',$sub[0]);
                    $aitems[$k] = $sub[1];
                    $aitemcnt[$k] = $grpparts[0];
                }
            } else {
                $aitemcnt[$k] = 1;
            }
        }
        $questionData = Questions::findQuestionForOuctome($assessmentId);
        $totalpossible = 0;
        foreach($questionData as $question) {
            if (($k = array_search($question['id'],$aitems))!==false) { //only use first item from grouped questions for total pts
                if ($question['points']==9999) {
                    $totalpossible += $aitemcnt[$k]*$defpoints; //use defpoints
                } else {
                    $totalpossible += $aitemcnt[$k]*$question['points']; //use points from question
                }
            }
        }
        //get exceptions
        $exceptionData = Exceptions::getByAssessmentId($assessmentId);
        $exceptions = array();
        foreach($exceptionData as $exception) {
            $exceptions[$exception['userid']] = array($exception['enddate'],$exception['islatepass']);
        }
        $tutorData = Tutor::getByUserId($currentUser['id'],$courseId);
        $tutorsection = trim($tutorData['section']);
        $studentData = User::findUserDataForIsolateAssessmentGrade($isTutor,$tutorsection,$assessmentId,$courseId,$hidelocked,$sortorder,$hassection);
        $this->includeCSS(['gradebook.css']);
        $this->includeJS(["general.js",'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('minscore' => $minscore,'assessmenttype' => $assessmenttype,'enddate' => $enddate,'timelimit' => $timelimit,'course' => $course,'hassection' => $hassection,'gbmode' => $gbmode,'assessmentId' => $assessmentId,'studentData' => $studentData,'exceptions' => $exceptions,'name' => $name,'totalpossible' => $totalpossible,'isTeacher' => $isTeacher,'isTutor' => $isTutor);
        return $this->renderWithData('isolateAssessmentGrade',$responseData);
    }

    public function actionAssessmentExport()
    {
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = false;
        $teacher = $this->isTeacher($currentUser['id'],$courseId);
//        $this->layout = 'master';
        if($teacher){
            $isTeacher = true;
        }
        if (isset($params['options'])) {
            //ready to output
            $outcol = 0;
            if (isset($params['pts'])) { $dopts = true; $outcol++;}
            if (isset($params['ptpts'])) { $doptpts = true; $outcol++;}
            if (isset($params['ba'])) { $doba = true; $outcol++;}
            if (isset($params['bca'])) { $dobca = true; $outcol++;}
            if (isset($params['la'])) { $dola = true; $outcol++;}
            //get assessment info
            $assessment = Assessments::getByAssessmentId($assessmentId);
            $defpoints = $assessment['defpoints'];
            $assessname = $assessment['name'];
            $itemorder = $assessment['itemorder'];
            $itemarr = array();
            $itemnum = array();
            foreach (explode(',',$itemorder) as $k=>$itel) {
                if (strpos($itel,'~')!==false) {
                    $sub = explode('~',$itel);
                    if (strpos($sub[0],'|')!==false) {
                        array_shift($sub);
                    }
                    foreach ($sub as $j=>$itsub) {
                        $itemarr[] = $itsub;
                        $itemnum[$itsub] = ($k+1).'-'.($j+1);
                    }
                } else {
                    $itemarr[] = $itel;
                    $itemnum[$itel] = ($k+1);
                }
            }
            //get question info
            $qpts = array();
            $qsetids = array();
            $questions = Questions::getByAssessmentId($assessmentId);
            foreach($questions as $question){
                if ($question['points']==9999) {
                    $qpts[$question['id']] = $defpoints;
                } else {
                    $qpts[$question['id']] = $question['points'];
                }
                $qsetids[$question['id']] = $question['questionsetid'];
            }

            if ($dobca) {
                $qcontrols = array();
                $qanswers = array();
                $mathfuncs = array("sin","cos","tan","sinh","cosh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
                $allowedmacros = $mathfuncs;
                $qsetidlist = implode(',',$qsetids);
                $questionsSet = QuestionSet::getByIdUsingInClause($qsetids);

                foreach($questionsSet as $row) {
                    $qcontrols[$row['id']] = interpretUtility::interpret('control',$row['qtype'],$row['control']);
                    $qanswers[$row['id']] = interpretUtility::interpret('answer',$row['qtype'],$row['answer']);
                }
            }
            $gb = array();
            //create headers
            $gb[0][0] = "Name";
            $gb[1][0] = "";
            $qcol = array();
            foreach ($itemarr as $k=>$q) {
                $qcol[$q] = 1 + $outcol*$k;
                $offset = 0;
                if ($dopts) {
                    $gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
                    $gb[1][1 + $outcol*$k + $offset] = "Points (".$qpts[$q]." possible)";
                    $offset++;
                }
                if ($doptpts) {
                    $gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
                    $gb[1][1 + $outcol*$k + $offset] = "Part Points (".$qpts[$q]." possible)";
                    $offset++;
                }
                if ($doba) {
                    $gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
                    $gb[1][1 + $outcol*$k + $offset] = "Scored Answer";
                    $offset++;
                }
                if ($dobca) {
                    $gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
                    $gb[1][1 + $outcol*$k + $offset] = "Scored Correct Answer";
                    $offset++;
                }
                if ($dola) {
                    $gb[0][1 + $outcol*$k + $offset] = "Question ".$itemnum[$q];
                    $gb[1][1 + $outcol*$k + $offset] = "Last Answer";
                    $offset++;
                }
            }
            //create row headers
            $students = Student::findStudentsToList($courseId);
            $r = 2;
            $sturow = array();
            foreach ($students as $student){
                $gb[$r] = array_fill(0,count($gb[0]),'');
                $gb[$r][0] = $student['LastName'].', '.$student['FirstName'];
                $sturow[$student['id']] = $r;
                $r++;
            }
            //pull assessment data
            $assessmentseessions = AssessmentSession::getByCourseIdAndAssessmentId($assessmentId,$courseId);
            foreach($assessmentseessions as $line) {

                if (strpos($line['questions'],';')===false) {
                    $questions = explode(",",$line['questions']);
                    $bestquestions = $questions;
                } else {
                    list($questions,$bestquestions) = explode(";",$line['questions']);
                    $questions = explode(",",$bestquestions);
                }
                $sp = explode(';', $line['bestscores']);
                $scores = explode(',',$sp[0]);
                $seeds = explode(',',$line['bestseeds']);
                $bla = explode('~',$line['bestlastanswers']);
                $la =  explode('~',$line['lastanswers']);
                if (!isset($sturow[$line['userid']])) {
                    continue;
                }
                $r = $sturow[$line['userid']];
                foreach ($questions as $k=>$ques) {

                    $c = $qcol[$ques];
                    $offset = 0;
                    if ($dopts) {
                        $gb[$r][$c+$offset] = $this->getpts($scores[$k]);
                        $offset++;
                    }
                    if ($doptpts) {
                        $gb[$r][$c+$offset] = $scores[$k];
                        $offset++;
                    }
                    if ($doba) {
                        $laarr = explode('##',$bla[$k]);
                        $gb[$r][$c+$offset] = $laarr[count($laarr)-1];
                        if (strpos($gb[$r][$c+$offset],'$f$')) {
                            if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
                                $laparr = explode('&',$gb[$r][$c+$offset]);
                                foreach ($laparr as $lk=>$v) {
                                    if (strpos($v,'$f$')) {
                                        $tmp = explode('$f$',$v);
                                        $laparr[$lk] = $tmp[0];
                                    }
                                }
                                $gb[$r][$c+$offset] = implode('&',$laparr);
                            } else {
                                $tmp = explode('$f$',$gb[$r][$c+$offset]);
                                $gb[$r][$c+$offset] = $tmp[0];
                            }
                        }
                        if (strpos($gb[$r][$c+$offset],'$!$')) {
                            if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
                                $laparr = explode('&',$gb[$r][$c+$offset]);
                                foreach ($laparr as $lk=>$v) {
                                    if (strpos($v,'$!$')) {
                                        $tmp = explode('$!$',$v);
                                        $laparr[$lk] = $tmp[1];
                                    }
                                }
                                $gb[$r][$c+$offset] = implode('&',$laparr);
                            } else {
                                $tmp = explode('$!$',$gb[$r][$c+$offset]);
                                $gb[$r][$c+$offset] = $tmp[1];
                            }
                        }
                        if (strpos($gb[$r][$c+$offset],'$#$')) {
                            if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
                                $laparr = explode('&',$gb[$r][$c+$offset]);
                                foreach ($laparr as $lk=>$v) {
                                    if (strpos($v,'$#$')) {
                                        $tmp = explode('$#$',$v);
                                        $laparr[$lk] = $tmp[0];
                                    }
                                }
                                $gb[$r][$c+$offset] = implode('&',$laparr);
                            } else {
                                $tmp = explode('$#$',$gb[$r][$c+$offset]);
                                $gb[$r][$c+$offset] = $tmp[0];
                            }
                        }
                        $offset++;
                    }
                    if ($dobca) {
                        $gb[$r][$c+$offset] = $this->evalqsandbox($seeds[$k],$qcontrols[$qsetids[$ques]],$qanswers[$qsetids[$ques]]);
                    }
                    if ($dola) {
                        $laarr = explode('##',$la[$k]);
                        $gb[$r][$c+$offset] = $laarr[count($laarr)-1];
                        if (strpos($gb[$r][$c+$offset],'$f$')) {
                            if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
                                $laparr = explode('&',$gb[$r][$c+$offset]);
                                foreach ($laparr as $lk=>$v) {
                                    if (strpos($v,'$f$')) {
                                        $tmp = explode('$f$',$v);
                                        $laparr[$lk] = $tmp[0];
                                    }
                                }
                                $gb[$r][$c+$offset] = implode('&',$laparr);
                            } else {
                                $tmp = explode('$f$',$gb[$r][$c+$offset]);
                                $gb[$r][$c+$offset] = $tmp[0];
                            }
                        }
                        if (strpos($gb[$r][$c+$offset],'$!$')) {
                            if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
                                $laparr = explode('&',$gb[$r][$c+$offset]);
                                foreach ($laparr as $lk=>$v) {
                                    if (strpos($v,'$!$')) {
                                        $tmp = explode('$!$',$v);
                                        $laparr[$lk] = $tmp[1];
                                    }
                                }
                                $gb[$r][$c+$offset] = implode('&',$laparr);
                            } else {
                                $tmp = explode('$!$',$gb[$r][$c+$offset]);
                                $gb[$r][$c+$offset] = $tmp[1];
                            }
                        }
                        if (strpos($gb[$r][$c+$offset],'$#$')) {
                            if (strpos($gb[$r][$c+$offset],'&')) { //is multipart q
                                $laparr = explode('&',$gb[$r][$c+$offset]);
                                foreach ($laparr as $lk=>$v) {
                                    if (strpos($v,'$#$')) {
                                        $tmp = explode('$#$',$v);
                                        $laparr[$lk] = $tmp[0];
                                    }
                                }
                                $gb[$r][$c+$offset] = implode('&',$laparr);
                            } else {
                                $tmp = explode('$#$',$gb[$r][$c+$offset]);
                                $gb[$r][$c+$offset] = $tmp[0];
                            }
                        }
                        $offset++;
                    }
                }
            }
            header('Content-type: text/csv');
            header("Content-Disposition: attachment; filename=\"aexport-$assessmentId.csv\"");
            foreach ($gb as $gbline) {
                $line = '';
                foreach ($gbline as $val) {
                    # remove any windows new lines, as they interfere with the parsing at the other end
                    $val = str_replace("\r\n", "\n", $val);
                    $val = str_replace("\n", " ", $val);
                    $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
                    $val = str_replace("&nbsp;"," ",$val);

                    # if a deliminator char, a double quote char or a newline are in the field, add quotes
                    if(preg_match("/[\,\"\n\r]/", $val)) {
                        $val = '"'.str_replace('"', '""', $val).'"';
                    }
                    $line .= $val.',';
                }
                # strip the last deliminator
                $line = substr($line, 0, -1);
                $line .= "\n";
                echo $line;
            }
            exit;
        }
        $responseData = array('isTeacher' => $isTeacher,'assessmentId' => $assessmentId,'course' => $course);
        return $this->renderWithData('assessmentExport',$responseData);
    }

    function evalqsandbox($seed,$qqqcontrol,$qqqanswer) {
        $sa = '';

        srand($seed);
        eval($qqqcontrol);
        srand($seed+1);
        eval($qqqanswer);

        if (isset($anstypes) && !is_array($anstypes)) {
            $anstypes = explode(",",$anstypes);
        }
        if (isset($anstypes)) { //is multipart
            if (isset($showanswer) && !is_array($showanswer)) {
                $sa = $showanswer;
            } else {
                $sapts =array();
                for ($i=0; $i<count($anstypes); $i++) {
                    if (isset($showanswer[$i])) {
                        $sapts[] = $showanswer[$i];
                    } else if (isset($answer[$i])) {
                        $sapts[] = $answer[$i];
                    } else if (isset($answers[$i])) {
                        $sapts[] = $answers[$i];
                    }
                }
                $sa = implode('&',$sapts);
            }
        } else {
            if (isset($showanswer)) {
                $sa = $showanswer;
            } else if (isset($answer)) {
                $sa = $answer;
            } else if (isset($answers)) {
                $sa = $answers;
            }
        }
        return $sa;
    }

    public function actionItemResults()
    {
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $att = $params['att'];
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = false;
        $teacher = $this->isTeacher($currentUser['id'],$courseId);
        $this->layout = 'master';
        if($teacher){
            $isTeacher = true;
        }
        $isTutor = false;
        $tutor = $this->isTutor($currentUser['id'],$courseId);
        if($tutor){
            $isTutor = true;
        }
        $questions = Questions::getByAssessmentId($assessmentId);
        $qsids = array();
        foreach($questions as $question) {
            $qsids[$question['id']] = $question['questionsetid'];
        }
        $qsdata = array();
        $questionsSet = QuestionSet::getByIdUsingInClause($qsids);
        foreach($questionsSet as $questionSet) {
            $qsdata[$questionSet['id']] = array($questionSet['qtype'],$questionSet['control'],$questionSet['description']);
        }
        $assessmentSessions = AssessmentSession::getByAssessmentId($assessmentId);
        $sessioncnt = 0;
        $qdata = array();
        if($assessmentSessions) {
            foreach ($assessmentSessions as $row) {
                if (strpos($row['questions'], ';') === false) {
                    $questions = explode(",", $row['questions']);
                } else {
                    list($questions, $bestquestions) = explode(";", $row['questions']);
                    $questions = explode(",", $questions);
                }
                $scores = explode(',', $row['scores']);
                $seeds = explode(',', $row['seeds']);
                $attempts = explode('~', $row['lastanswers']);
                $sessioncnt++;
                foreach ($questions as $k => $q) {
                    if (!isset($qdata[$q])) {
                        $qdata[$q] = array();
                    }
                    $qatt = explode('##', $attempts[$k]);
                    if ($att == 'first') { //doesn't work with scores yet
                        $i = 0;
                        while ($qatt[$i] == 'ReGen') {
                            $i++;
                        }
                        $qatt = $qatt[$i];
                    } else {
                        $qatt = $qatt[count($qatt) - 1];
                    }
                    $qatt = explode('&', $qatt);
                    $qscore = explode('~', $scores[$k]);
                    foreach ($qatt as $kp => $lav) {
                        if (strpos($lav, '$f$') !== false) {
                            $tmp = explode('$f$', $lav);
                            $qatt[$kp] = $tmp[0];
                            $lav = $tmp[0];
                        }
                        if (strpos($lav, '$!$') !== false) {
                            $tmp = explode('$!$', $lav);
                            $qatt[$kp] = $tmp[1];
                        }
                        if (strpos($lav, '$#$') !== false) {
                            $tmp = explode('$#$', $lav);
                            $qatt[$kp] = $tmp[0];
                        }
                    }
                    if (count($qatt) == 1) {
                        $qatt = $qatt[0];
                        $qscore = $qscore[0];
                    }
                    $qtype = $qsdata[$qsids[$q]][0];
                    $qdata[$q][] = array($qatt, $qscore);
                }
            }
        }
        $this->includeCSS(['mathtest.css','gradebook.css']);
        $this->includeJS(["general.js"]);
        $assessment = Assessments::getByAssessmentId($assessmentId);
        $responseData = array('course' => $course,'qsids' => $qsids,'qsdata' => $qsdata,'assessment' => $assessment,'qdata' => $qdata,'isTeacher' => $isTeacher,'isTutor' => $isTutor);
        return $this->renderWithData('itemResults',$responseData);
    }
    public function actionGradebookTesting()
    {
        global $get,$lnfilter,$timefilter;
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $params = $this->getRequestParams();
        $get = $params;
        $this->layout = "master";
        $currentUser = $this->getAuthenticatedUser();
        $teacherId = $this->isTeacher($currentUser['id'],$courseId);
        $tutorId = $this->isTutor($currentUser['id'],$courseId);
        if ($teacherId) {
            $isTeacher = true;
        }
        if ($tutorId) {
            $isTutor = true;
        }
        if ($isTeacher || $isTutor) {
            $canviewall = true;
        } else {
            $canviewall = false;
        }
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        if ($isTeacher || $isTutor) {
            if (isset($params['timefilter'])) {
                $timefilter = $params['timefilter'];
                $sessionData[$courseId.'timefilter'] = $timefilter;
                AppUtility::writesessiondata($sessionData,$sessionId);
            } else if (isset($sessionData[$courseId.'timefilter'])) {
                $timefilter = $sessionData[$courseId.'timefilter'];
            } else {
                $timefilter = 2;
            }
            if (isset($params['lnfilter'])) {
                $lnfilter = trim($params['lnfilter']);
                $sessionData[$courseId.'lnfilter'] = $lnfilter;
                AppUtility::writesessiondata($sessionData,$sessionId);
            } else if (isset($sessionData[$courseId.'lnfilter'])) {
                $lnfilter = $sessionData[$courseId.'lnfilter'];
            } else {
                $lnfilter = '';
            }
        }
        $gradebookData = $this->gbtable($currentUser->id, $courseId);
        $studentsDistinctSection = Student::findDistinctSection($courseId);
        $this->includeCSS(['jquery.dataTables.css','gradebook.css']);
        $this->includeJS(['general.js','gradebook/gradebookstudentdetail.js','tablesorter.js','jquery.dataTables.min.js','dataTables.bootstrap.js']);
        $responseData = array('studentsDistinctSection' => $studentsDistinctSection,'lnfilter' => $lnfilter,'timefilter' => $timefilter,'gradebookData' => $gradebookData,'isTeacher' => $isTeacher,'isTutor' => $isTutor,'course' => $course);
        return $this->renderWithData('gradebookTesting',$responseData);
    }

    public function actionGradebookExport()
    {
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = $this->isTeacher($currentUser['id'],$courseId);
        $this->layout = 'master';
        if (isset($sessionData[$courseId.'gbmode'])) {
            $gbmode =  $sessionData[$courseId.'gbmode'];
        } else {
            $gbScheme = GbScheme::getByCourseId($courseId);
            $gbmode = $gbScheme['defgbmode'];
        }

        if (isset($params['stu']) && $params['stu']!='') {
            $stu = $params['stu'];
        } else {
            $stu = AppConstant::NUMERIC_ZERO;
        }
        $studentData = Student::getByCourse($courseId);
        global $hidelockedfromexport;
        if(isset($params['locked'])){
            $hidelockedfromexport = ($params['locked']=='hide')?true:false;

        }
        if(isset($params['commentloc']))
        {
            global $logincnt,$lastloginfromexport,$includecomments;
            $includecomments = true;
            $logincnt = $params['logincnt'];
            $lastloginfromexport = $params['lastlogin'];
            $totalData = $this->gbtable($currentUser['id'], $course['id'], $stu);
        }
        $this->includeCSS('imascore.css','modern.css');
        $responseData = array('studentData' => $studentData,'currentUser' => $currentUser,'totalData' => $totalData,'gbmode' => $gbmode,'stu' => $stu,'params' => $params,'isteacher' => $isTeacher,'course' => $course);
        return $this->renderWithData('gradebookExport',$responseData);
    }

    public function actionIsolateAssessmentGroup()
    {
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $params['cid'];
        $aid = $params['aid'];
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($currentUser['id'],$courseId);
        $this->noValidRights($teacherId);
        if (isset($params['gbmode']) && $params['gbmode']!='') {
            $gbMode = $params['gbmode'];
        } else
        {
            $gbScheme = GbScheme::getByCourseId($courseId);
            $gbMode = $gbScheme['defgbmode'];
        }
        $assessment = Assessments::getByAssessmentId($aid);
        $questions = Questions::getByAssessmentId($aid);
        $AssessmentGroups = AssessmentSession::getAssessmentGroups($aid);
        $stuGroups = Stugroups::getByGrpSetIdAndName($assessment['groupsetid']);
        $groupNumbers = AppConstant::NUMERIC_ONE;
        foreach ($stuGroups as $row)
        {
            if ($row['name'] == 'Unnamed group') {
                $row['name'] .= " $groupNumbers";
                $groupNumbers++;
                $user = User::getUserNameUsingStuGroup($row['id']);
                if (count($user) > AppConstant::NUMERIC_ZERO)
                {
                    $row['name'] .=  $user[0]['LastName'].','.$user[0]['FirstName'];
                }
            }
            $groupNames[$row['id']] = $row['name'];
        }
        $this->includeJS(['tablesorter.js']);
        $responseData = array('aid' => $aid,'gbmode' => $gbMode,'questions' => $questions,'groupnames' => $groupNames,'course' => $course,'AssessmentGroups' => $AssessmentGroups,'assessment' => $assessment);
        return $this->renderWithData('isolateAssessmentGroup',$responseData);
    }

    public function actionEditToolScore()
    {
        $isTutor = false;
        $isTeacher = false;
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $isTutor = $this->isTutor($currentUser['id'],$courseId);
        $isTeacher = $this->isTeacher($currentUser['id'],$courseId);
        $lid = intval($params['lid']);
        $linkData = LinkedText::getLinkDataByIdAndCourseID($lid,$courseId);
        if (!$linkData)
        {
            $this->setWarningFlash('invalid item');
            return $this->goBack();
        }
        $name = $linkData['title'];
        $text = $linkData['text'];
        $points = $linkData['points'];
        $toolParts = explode('~~',substr($text, AppConstant::NUMERIC_EIGHT));
        if (isset($toolParts[3])) {
            $gbCat = $toolParts[3];
            $countInGb = $toolParts[4];
            $tutorEdit = $toolParts[5];
        } else
        {
            $this->setWarningFlash(AppConstant::INVALID_PARAMETERS);
            return $this->goBack();
        }
        if ($isTutor) {
            $isOk = ($tutorEdit == 1);
            if (!$isOk)
            {
                $this->setWarningFlash(AppConstant::NO_AUTHORITY);
                return $this->goBack();
            }
        } else if (!$isTeacher)
        {
            $this->setWarningFlash(AppConstant::NO_TEACHER_RIGHTS);
            return $this->goBack();
        }

        if (isset($params['clear']) && $isTeacher)
        {
            if (isset($params['confirm']))
            {
                   Grades::deleteByGradeTypeId($lid);
                   $this->redirect('gradebook?stu='.$params['stu'].'&gbmode='.$params['gbmode'].'&cid='.$params['cid']);
            }
        }
        if (isset($params['newscore']))
        {
            $keys = array_keys($params['newscore']);
            foreach ($keys as $k=>$v) {
                if (trim($v)=='') {unset($keys[$k]);}
            }
            if (count($keys) > AppConstant::NUMERIC_ZERO)
            {
                $userIds = Grades::getExternalToolUserId($lid,$keys);
                foreach($userIds as $userId)
                {
                    $params['score'][$userId['userid']] = $params['newscore'][$userId['userid']];
                    unset($params['newscore'][$userId['userid']]);
                }
            }
        }
        ///regular submit
        if (isset($params['score']))
        {
            foreach($params['score'] as $k=>$sc)
            {
                if (trim($k)=='') { continue;}
                $sc = trim($sc);
                if ($sc!='')
                {
                    Grades::updateScoreToStudent($sc,$params['feedback'][$k],$k,$lid);
                } else
                {
                    Grades::updateScoreToStudent('NULL',$params['feedback'][$k],$k,$lid);
                }
            }
        }
        if (isset($params['newscore']))
        {
            foreach($params['newscore'] as $k=>$sc)
            {
                if (trim($k)=='') {continue;}
                if ($sc!='')
                {
                    $grade = array
                    (
                        'gradetype' => 'exttool',
                        'gradetypeid' => $lid,
                        'userid' => $k,
                        'score' => $sc,
                        'feedback' => $params['feedback'][$k]
                    );
                    $insertGrade = new Grades();
                    $insertGrade->createGradesByUserId($grade);
                } else if (trim($params['feedback'][$k])!='')
                {
                    $grade = array
                    (
                        'gradetype' => 'exttool',
                        'gradetypeid' => $lid,
                        'userid' => $k,
                        'score' => 'NULL',
                        'feedback' => $params['feedback'][$k]
                    );
                    $insertGrade = new Grades();
                    $insertGrade->createGradesByUserId($grade);
                }
            }
        }
        if (isset($params['score']) || isset($params['newscore']) || isset($params['name']))
        {
            $this->redirect('gradebook?stu='.$params['stu'].'&gbmode='.$params['gbmode'].'&cid='.$params['cid']);
        }
        $query = Student::findByCid($courseId);
        if ($query) {
            $countSection = AppConstant::NUMERIC_ZERO;
            foreach ($query as $singleData) {
                if ($singleData->section != null || $singleData->section != "") {
                    $countSection++;
                }
            }
        }
        if ($countSection > AppConstant::NUMERIC_ZERO) {
            $hasSection = true;
        } else {
            $hasSection = false;
        }
        if ($hasSection) {
            $gbScheme = GbScheme::getByCourseId($courseId);
            if ($gbScheme['usersort'] == AppConstant::NUMERIC_ZERO)
            {
                $sortOrder = "sec";
            } else {
                $sortOrder = "name";
            }
        } else {
            $sortOrder = "name";
        }
        $tutorData = Tutor::getByUserId($currentUser['id'],$courseId);
        $tutorSection = trim($tutorData['section']);
        $externalToolData = Grades::getExternalToolData($lid,$params['uid']);
        $studentData = User::getDataForExternalTool($params['uid'],$courseId,$isTutor,$tutorSection,$hasSection,$sortOrder);
        if ($hasSection)
        {
            $this->includeJS(['tablesorter.js']);
        }
        $this->includeJS(['gradebook/addgrades.js']);
        $responseData = array('studentData' => $studentData,'course' => $course,'externalToolData' => $externalToolData,'linkData' => $linkData,'params' => $params,'hassection' => $hasSection);
        return $this->renderWithData('editToolScore',$responseData);
    }

    public function actionException()
    {
        $overwriteBody = 0;
        $body = "";
        $pagetitle = "Make Exception";
        $params = $this->getRequestParams();
        $cid = $params['cid'];
        $course = Course::getById($cid);
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = $this->isTeacher($currentUser['id'],$course['id']);
        $assessmentId = $params['asid'];
        $aid = $params['aid'];
        $userId = $params['uid'];
        if (isset($params['stu'])) {
            $stu = $params['stu'];
        } else {
            $stu=0;
        }
        if (isset($params['from'])) {
            $from = $params['from'];
        } else {
            $from = 'gb';
        }
        if (!$isTeacher)
        { // loaded by a NON-teacher
            $overwriteBody=1;
            $body = "You need to log in as a teacher to access this page";
        } elseif (!(isset($_GET['cid']))) {
            $overwriteBody=1;
            $body = "You need to access this page from the course page menu";
        } else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
            $cid =  $course->id;
            $waivereqscore = (isset($params['waivereqscore']))?1:0;

            if (isset($params['sdate']))
            {
                $startdate = AppUtility::parsedatetime($params['sdate'],$params['stime']);
                $enddate = AppUtility::parsedatetime($params['edate'],$params['etime']);

                //check if exception already exists
                $exception = Exceptions::getByAssessmentIdAndUserId($userId, $assessmentId);
                $exceptionId = $exception['id'];
                if ($exceptionId != null)
                {
                    Exceptions::updateException($userId, $exceptionId, $startdate, $enddate, $waivereqscore);
                } else
                {
                    $param = array('userid' => $params['uid'], 'assessmentid' => $params['aid'], 'startdate' => $startdate, 'enddate' => $enddate, 'waivereqscore' => $waivereqscore);
                    $exception = new Exceptions();
                    $exception->create($param);
                }
                if (isset($params['eatlatepass']))
                {
                    $n = intval($params['latepassn']);
                    Student::updateStudentDataFromException($n,$params['uid'],$course->id);
                }
                //force regen?
                if (isset($params['forceregen']))
                {
                    //this is not group-safe
                    $stu = $params['uid'];
                    $aid = $params['aid'];
                    $assessmentSessionData = AssessmentSession::getAssessmentSession($params['uid'], $params['aid']);
                    if ($assessmentSessionData)
                    {
                        if (strpos($assessmentSessionData['questions'],';')===false)
                        {
                            $questions = explode(",",$assessmentSessionData['questions']);
                            $bestquestions = $questions;
                        } else {
                            list($questions,$bestquestions) = explode(";",$assessmentSessionData['questions']);
                            $questions = explode(",",$questions);
                        }
                        $lastanswers = explode('~',$assessmentSessionData['lastanswers']);
                        $curscorelist = $assessmentSessionData['scores'];
                        $scores = array(); $attempts = array(); $seeds = array(); $reattempting = array();
                        for ($i=0; $i<count($questions); $i++) {
                            $scores[$i] = -1;
                            $attempts[$i] = 0;
                            $seeds[$i] = rand(1,9999);
                            $newla = array();
                            $laarr = explode('##',$lastanswers[$i]);
                            //may be some files not accounted for here...
                            //need to fix
                            foreach ($laarr as $lael) {
                                if ($lael=="ReGen") {
                                    $newla[] = "ReGen";
                                }
                            }
                            $newla[] = "ReGen";
                            $lastanswers[$i] = implode('##',$newla);
                        }
                        $scorelist = implode(',',$scores);
                        if (strpos($curscorelist,';')!==false) {
                            $scorelist = $scorelist.';'.$scorelist;
                        }
                        $attemptslist = implode(',',$attempts);
                        $seedslist = implode(',',$seeds);
                        $lastanswers = str_replace('~','',$lastanswers);
                        $lalist = implode('~',$lastanswers);
                        $lalist = addslashes(stripslashes($lalist));
                        $reattemptinglist = implode(',',$reattempting);

                        $session['id'] = $assessmentSessionData['id'];
                        $session['scores'] = $scorelist;
                        $session['attempts'] = $attemptslist;
                        $session['seeds'] = $seedslist;
                        $session['lastanswers'] = $lalist;
                        $session['reattempting'] = $reattemptinglist;
                        AssessmentSession::modifyExistingSession($session);
                    }

                }
                return $this->redirect('gradebook-view-assessment-details?cid='.$cid.'&asid='.$assessmentSessionData['id'].'&uid='.$userId.'&stu='.$stu.'&from='.$from);
            } else if (isset($params['clear']))
            {
                $assessmentSessionData = AssessmentSession::getAssessmentSession($params['uid'], $params['aid']);
                Exceptions::deleteExceptionById($params['clear']);
                return $this->redirect('gradebook-view-assessment-details?cid='.$course->id.'&asid='.$assessmentSessionData['id'].'&uid='.$userId.'&stu='.$stu.'&from='.$from);
            } elseif (isset($params['aid']) && $params['aid']!='')
            {
                $userData = User::getById($userId);
                $userInformation = array();
                $userInformation['LastName'] = $userData['LastName'];
                $userInformation['FirstName'] = $userData['FirstName'];
                $stuname = implode(', ',$userInformation);
                $assessmentData = Assessments::getByAssessmentId($params['aid']);
                $sdate = AppUtility::tzdate("m/d/Y",$assessmentData['startdate']);
                $edate = AppUtility::tzdate("m/d/Y",$assessmentData['enddate']);
                $stime = AppUtility::tzdate("g:i a",$assessmentData['startdate']);
                $etime = AppUtility::tzdate("g:i a",$assessmentData['enddate']);
                //check if exception already exists
                $exception = Exceptions::getByAssessmentIdAndUserId($userId, $assessmentId);
                $page_isExceptionMsg = "";
                $savetitle = _('Create Exception');
                if ($exception)
                {
                    $savetitle = _('Save Changes');
                    $page_isExceptionMsg = "<p>An exception already exists.
            <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$course->id&aid={$params['aid']}&uid={$params['uid']}&clear={$exception['startdate']}&asid=$assessmentId&stu=$stu&from=$from'\">"._("Clear Exception").'</button> or modify below.</p>';
                    $sdate = AppUtility::tzdate("m/d/Y",$exception['startdate']);
                    $edate = AppUtility::tzdate("m/d/Y",$exception['enddate']);
                    $stime = AppUtility::tzdate("g:i a",$exception['startdate']);
                    $etime = AppUtility::tzdate("g:i a",$exception['enddate']);
                }
            }

            //DEFAULT LOAD DATA MANIPULATION
//            return $this->redirect('exception?cid='.$params['cid'].'&uid='.$params['uid'].'&asid='.$asid.'&stu='.$stu.'&from='.$from);
            $addr = 'exception?cid='.$params['cid'].'&uid='.$params['uid'].'&asid='.$asid.'&stu='.$stu.'&from='.$from;

            $allAssessment = Assessments::getByCourse($course->id);
            $page_courseSelect = array();
            $i=0;
            foreach ($allAssessment as $assessment)
            {
                $page_courseSelect['val'][$i] = $assessment['id'];
                $page_courseSelect['label'][$i] = $assessment['name'];
                $i++;
            }

        }
        $student = Student::getByCourseId($course->id,$userId);
        $latepasses = $student['latepass'];

        $responseData = array('body' => $body,'addr' => $addr,'latepasses' => $latepasses,'savetitle' => $savetitle,'etime' => $etime,'stime' => $sdate,'sdate' => $sdate,'edate' => $edate,'overwriteBody' => $overwriteBody,'pagetitle' => $pagetitle,'course' => $course,
           'from' => $from, 'params' => $params,'stuname' => $stuname,'page_isExceptionMsg' => $page_isExceptionMsg,'page_courseSelect' => $page_courseSelect,'asid' => $assessmentId,'isTeacher' => $isTeacher);
        return $this->renderWithData('exception',$responseData);
    }

}