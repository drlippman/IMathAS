<?php


namespace app\controllers\question;

use app\components\AppUtility;
use app\components\filehandler;
use app\components\questionUtility;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Items;
use app\models\Libraries;
use app\models\LibraryItems;
use app\models\Log;
use app\models\Message;
use app\models\Outcomes;
use app\models\QImages;
use app\models\Questions;
use app\models\Rubrics;
use app\models\User;
use app\models\QuestionSet;
use Yii;
use app\components\AppConstant;

require("../components/displayQuestion.php");
class QuestionController extends AppController
{
    public $user = null;
    public function beforeAction($action)
    {
        $actionPath = Yii::$app->controller->action->id;
        $this->user = $this->getAuthenticatedUser();
        $courseId =  ($this->getRequestParams('cid') || $this->getRequestParams('courseId')) ? ($this->getRequestParams('cid')?$this->getRequestParams('cid'):$this->getRequestParams('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForQuestionController($this->user,$courseId,$actionPath);
    }

    public function actionAddQuestions()
    {
        $user = $this->user;
        $groupId = $user['groupid'];
        $userId = $user['id'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $teacherId = $this->isTeacher($userId, $courseId);
        $isTutor = $this->isTutor($userId, $courseId);
        if ($user['rights'] == AppConstant::ADMIN_RIGHT) {
            $teacherId = $userId;
            $adminAsTeacher = true;
        }
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = '';
        $course = Course::getById($courseId);
        $this->checkSession($params);
        /*
         * Loaded by a NON-teacher
         */
        if (!$teacherId && !$isTutor) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } elseif (!(isset($params['cid'])) || !(isset($params['aid']))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_PAGE_ACCESS;
        } else {
            /*
             * PERMISSIONS ARE OK, PROCEED WITH PROCESSING
             */
            $courseId = $this->getParamVal('cid');;
            $assessmentId = $this->getParamVal('aid');
            $sessionId = $this->getSessionId();
            $sessionData = $this->getSessionData($sessionId);
            if (isset($params['grp'])) {
                $sessionData['groupopt' . $assessmentId] = $params['grp'];
                $this->writesessiondata($sessionData, $sessionId);
            }
            if (isset($params['selfrom'])) {
                $sessionData['selfrom' . $assessmentId] = $params['selfrom'];
                $this->writesessiondata($sessionData, $sessionId);
            } else {
                if (!isset($sessionData['selfrom' . $assessmentId])) {
                    $sessionData['selfrom' . $assessmentId] = 'lib';
                    $this->writesessiondata($sessionData, $sessionId);
                }
            }

            if (($teacherId) && isset($params['addset'])) {
                if (!isset($params['nchecked']) && !isset($params['qsetids'])) {
                    $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                } else if (isset($params['add'])) {
                    $addQuestionData = questionUtility::addQuestion($params, $teacherId,$assessmentId,$courseId);
                    if (isset($params['process'])) {
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                    }
                } else {
                    $checked = $params['nchecked'];
                    foreach ($checked as $questionSetId) {
                        $questionData = array(
                            'assessmentid' => $assessmentId,
                            'points' => AppConstant::QUARTER_NINE,
                            'attempts' => AppConstant::QUARTER_NINE,
                            'penalty' => AppConstant::QUARTER_NINE_STRING,
                            'questionsetid' => $questionSetId
                        );
                        $question = new Questions();
                        $questionId = $question->addQuestions($questionData);
                        $qids[] = $questionId;
                    }
                    //add to itemorder
                    $assessment = Assessments::getByAssessmentId($assessmentId);
                    if ($assessment['itemorder'] == '') {
                        $itemOrder = implode(",", $qids);
                    } else {
                        $itemOrder = $assessment['itemorder'] . "," . implode(",", $qids);
                    }
                    $vidData = $assessment['viddata'];
                    if ($vidData != '') {
                        if ($assessment['itemorder'] == '') {
                            $nextNum = AppConstant::NUMERIC_ZERO;
                        } else {
                            $nextNum = substr_count($assessment['itemorder'], ',') + AppConstant::NUMERIC_ONE;
                        }
                        $numNew = count($checked);
                        $vidData = unserialize($vidData);
                        if (!isset($vidData[count($vidData) - AppConstant::NUMERIC_ONE][1])) {
                            $finalSeg = array_pop($vidData);
                        } else {
                            $finalSeg = '';
                        }
                        for ($i = $nextNum; $i < $nextNum + $numNew; $i++) {
                            $vidData[] = array('', '', $i);
                        }
                        if ($finalSeg != '') {
                            $vidData[] = $finalSeg;
                        }
                        $vidData = serialize($vidData);
                    }
                    Assessments::setVidData($itemOrder, $vidData, $assessmentId);
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                }
            }
            if (isset($params['modqs'])) {
                if (!isset($params['checked']) && !isset($params['qids'])) {
                    $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                } else {
                    $addQuestionData = questionUtility::addQuestion($params, $teacherId,$assessmentId,$courseId);
                    if (isset($params['process'])) {
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                    }
                }
            }
            if (isset($params['clearattempts'])) {
                if ($params['clearattempts'] == "confirmed") {
                    filehandler::deleteallaidfiles($assessmentId);
                    AssessmentSession::deleteByAssessmentId($assessmentId);
                    Questions::setWithdrawn($assessmentId, AppConstant::NUMERIC_ZERO);
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                } else {
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $assessmentData = Assessments::getByAssessmentId($params['aid']);
                    $assessmentName = $assessmentData['name'];
                    $body .= "<div class='col-md-12 padding-top-five padding-left-thirty'><h3>$assessmentName</h3>";
                    $body .= "<p>Are you SURE you want to delete all attempts (grades) for this assessment?</p>";
                    $body .= "<p class='padding-top-ten'><input type=button value=\"Yes, Clear\" onClick=\"window.location='" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId . '&clearattempts=confirmed') . "'\">\n";
                    $body .= "<input type=button value=\"Cancel\" class=\"secondarybtn margin-left-ten\" onClick=\"window.location='" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId) . "';\"></p></div>";
                }
            }

            if (isset($params['withdraw'])) {
                if (isset($params['confirmed'])) {
                    if (strpos($params['withdraw'], '-') !== false) {
                        $isInGroup = true;
                        $loc = explode('-', $params['withdraw']);
                        $toRemove = $loc[0];
                    } else {
                        $isInGroup = false;
                        $toRemove = $params['withdraw'];
                    }
                    $query = Assessments::getByAssessmentId($assessmentId);
                    $itemOrder = explode(',', $query['itemorder']);
                    $defPoints = $query['defpoints'];

                    $qids = array();
                    if ($isInGroup && $params['withdrawtype'] != 'full') { //is group remove
                        $qids = explode('~', $itemOrder[$toRemove]);
                        if (strpos($qids[0], '|') !== false) { //pop off nCr
                            array_shift($qids);
                        }
                    } else if ($isInGroup) { //is single remove from group
                        $sub = explode('~', $itemOrder[$toRemove]);
                        if (strpos($sub[0], '|') !== false) { //pop off nCr
                            array_shift($sub);
                        }
                        $qids = array($sub[$loc[1]]);
                    } else { //is regular item remove
                        $qids = array($itemOrder[$toRemove]);
                    }
                    $qidList = implode(',', $qids);
                    //withdraw question
                    Questions::updateWithPoints(AppConstant::NUMERIC_ONE, '', $qidList);
                    if ($params['withdrawtype'] == 'zero' || $params['withdrawtype'] == 'groupzero') {
                        Questions::updateWithPoints(AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_ZERO, $qidList);
                    }
                    /*
                     * get possible points if needed
                     */
                    if ($params['withdrawtype'] == 'full' || $params['withdrawtype'] == 'groupfull') {
                        $poss = array();
                        $questionList = Questions::getByIdList($qidList);
                        foreach ($questionList as $list) {
                            if ($list['points'] == AppConstant::QUARTER_NINE) {
                                $poss[$list['id']] = $defPoints;
                            } else {
                                $poss[$list['id']] = $list['points'];
                            }
                        }
                    }
                    /*
                     * update assessment sessions
                     */
                    $assessmentSessionData = AssessmentSession::getByAssessmentId($assessmentId);
                    foreach ($assessmentSessionData as $data) {
                        if (strpos($data['questions'], ';') === false) {
                            $queArray = explode(",", $data['questions']);
                        } else {
                            /*
                             * Work to be done
                             */
                            list($questions, $bestQuestions) = explode(";", $data['questions']);
                            $queArray = explode(",", $bestQuestions);
                        }
                        if (strpos($data['bestscores'], ';') === false) {
                            $bestScores = explode(',', $data['bestscores']);
                            $doRaw = false;
                        } else {
                            /*
                             * Work to be done
                             */
                            list($bestScoreList, $bestRawScoreList, $firstScoreList) = explode(';', $data['bestscores']);
                            $bestScores = explode(',', $bestScoreList);
                            $bestRawScores = explode(',', $bestRawScoreList);
                            $firstScores = explode(',', $firstScoreList);
                            $doRaw = true;
                        }
                        for ($i = AppConstant::NUMERIC_ZERO; $i < count($queArray); $i++) {
                            if (in_array($queArray[$i], $qids)) {
                                if ($params['withdrawtype'] == 'zero' || $params['withdrawtype'] == 'groupzero') {
                                    $bestScores[$i] = AppConstant::NUMERIC_ZERO;
                                } else if ($params['withdrawtype'] == 'full' || $params['withdrawtype'] == 'groupfull') {
                                    $bestScores[$i] = $poss[$queArray[$i]];
                                }
                            }
                        }
                        if ($doRaw) {
                            $sList = implode(',', $bestScores) . ';' . implode(',', $bestRawScores) . ';' . implode(',', $firstScores);
                        } else {
                            $sList = implode(',', $bestScores);
                        }
                        AssessmentSession::setBestScore($sList, $data['id']);
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                } else {
                    if (strpos($params['withdraw'], '-') !== false) {
                        $isInGroup = true;
                    } else {
                        $isInGroup = false;
                    }
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    /*
                     * Work to be done
                     */
                    $body .= "<h3>Withdraw Question</h3>";
                    $body .= "<form method=post action=\"add-questions?cid=$courseId&aid=$assessmentId&withdraw={$params['withdraw']}&confirmed=true\">";
                    if ($isInGroup) {
                        $body .= '<p><b>This question is part of a group of questions</b>.  </p>';
                        $body .= '<input type=radio name="withdrawtype" value="groupzero" > Set points possible and all student scores to zero <b>for all questions in group</b><br/>';
                        $body .= '<input type=radio name="withdrawtype" value="groupfull" checked="1"> Set all student scores to points possible <b>for all questions in group</b><br/>';
                        $body .= '<input type=radio name="withdrawtype" value="full" > Set all student scores to points possible <b>for this question only</b>';
                    } else {
                        $body .= '<input type=radio name="withdrawtype" value="zero" > Set points possible and all student scores to zero<br/>';
                        $body .= '<input type=radio name="withdrawtype" value="full" checked="1"> Set all student scores to points possible';
                    }
                    $body .= '<p>This action can <b>not</b> be undone.</p>';
                    $body .= '<p><input type=submit value="Withdraw Question">';
                    $body .= "<input type=button value=\"Cancel\" class=\"secondarybtn\" onClick=\"window.location='" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId) . "'\"></p>\n";
                    $body .= '</form>';
                }
            }
            $address = AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId);
            /*
             * DEFAULT LOAD PROCESSING GOES HERE
             * load filter.  Need earlier than usual header.php load
             */
            require_once(Yii::$app->basePath . "/filter/filter.php");
            $query = AssessmentSession::getByAssessmentSessionIdJoin($assessmentId, $courseId);
            if (count($query) > AppConstant::NUMERIC_ZERO) {
                $beenTaken = true;
            } else {
                $beenTaken = false;
            }
            $result = Assessments::getByAssessmentId($assessmentId);
            $itemOrder = $result['itemorder'];
            $pageAssessmentName = $result['name'];
            $ln = AppConstant::NUMERIC_ONE;
            $defPoints = $result['defpoints'];
            $displayMethod = $result['displaymethod'];
            $showHintsDef = $result['showhints'];
            $grp0Selected = "";
            if (isset($sessionData['groupopt' . $assessmentId])) {
                $grp = $sessionData['groupopt' . $assessmentId];
                $grp1Selected = ($grp == AppConstant::NUMERIC_ONE) ? " selected" : "";
            } else {
                $grp = AppConstant::NUMERIC_ZERO;
                $grp0Selected = " selected";
            }

            $jsArray = '[';
            if ($itemOrder != '') {
                $items = explode(",", $itemOrder);
            } else {
                $items = array();
            }
            $existingQuestion = array();
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($items); $i++) {
                if (strpos($items[$i], '~') !== false) {
                    $subs = explode('~', $items[$i]);
                } else {
                    $subs[] = $items[$i];
                }
                if ($i > AppConstant::NUMERIC_ZERO) {
                    $jsArray .= ',';
                }
                if (count($subs) > AppConstant::NUMERIC_ONE) {
                    if (strpos($subs[0], '|') === false) { //for backwards compat
                        $jsArray .= '[1,0,[';
                    } else {
                        $grpParts = explode('|', $subs[0]);
                        $jsArray .= '[' . $grpParts[0] . ',' . $grpParts[1] . ',[';
                        array_shift($subs);
                    }
                }
                for ($j = AppConstant::NUMERIC_ZERO; $j < count($subs); $j++) {
                    $line = Questions::getQuestionData($subs[$j]);
                    $existingQuestion[] = $line['questionsetid'];
                    if ($j > AppConstant::NUMERIC_ZERO) {
                        $jsArray .= ',';
                    }
                    /*
                     * output item array
                     */
                    $jsArray .= '[' . $subs[$j] . ',' . $line['questionsetid'] . ',"' .  filter(str_replace(array("\r\n", "\n", "\r"), " ", $line['description'])) . '","' . $line['qtype'] . '",' . $line['points'] . ',';
                    if ($line['userights'] > AppConstant::NUMERIC_THREE || ($line['userights'] == AppConstant::NUMERIC_THREE && $line['groupid'] == $groupId) || $line['ownerid'] == $userId || $adminAsTeacher) { //can edit without template?
                        $jsArray .= AppConstant::ONE_VALUE;
                    } else {
                        $jsArray .= AppConstant::ZERO_VALUE;
                    }
                    $jsArray .= ',' . $line['withdrawn'];
                    $extRefVal = AppConstant::NUMERIC_ZERO;
                    if (($line['showhints'] == AppConstant::NUMERIC_ZERO && $showHintsDef == AppConstant::NUMERIC_ONE) || $line['showhints'] == AppConstant::NUMERIC_TWO) {
                        $extRefVal += AppConstant::NUMERIC_ONE;
                    }
                    if ($line['extref'] != '') {
                        $extRef = explode('~~', $line['extref']);
                        $hasVideo = false;
                        $hasOther = false;
                        $hasCap = false;
                        foreach ($extRef as $v) {
                            if (strtolower(substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE)) == "video" || strpos($v, 'youtube.com') !== false || strpos($v, 'youtu.be') !== false) {
                                $hasVideo = true;
                                if (strpos($v, '!!1') !== false) {
                                    $hasCap = true;
                                }
                            } else {
                                $hasOther = true;
                            }
                        }
                        $pageQuestionTable[$i]['extref'] = '';
                        if ($hasVideo) {
                            $extRefVal += AppConstant::NUMERIC_FOUR;
                        }
                        if ($hasOther) {
                            $extRefVal += AppConstant::NUMERIC_TWO;
                        }
                        if ($hasCap) {
                            $extRefVal += AppConstant::SIXTEEN;
                        }
                    }
                    if ($line['solution'] != '' && ($line['solutionopts'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO) {
                        $extRefVal += AppConstant::NUMERIC_EIGHT;
                    }
                    $jsArray .= ',' . $extRefVal;
                    $jsArray .= ']';
                }
                if (count($subs) > AppConstant::NUMERIC_ONE) {
                    $jsArray .= '],';
                    if (isset($_COOKIE['closeqgrp-' . $assessmentId]) && in_array("$i", explode(',', $_COOKIE['closeqgrp-' . $assessmentId], true))) {
                        $jsArray .= AppConstant::ZERO_VALUE;
                    } else {
                        $jsArray .= AppConstant::ONE_VALUE;
                    }
                    $jsArray .= ']';
                }
                if (isset($alt)) {
                    $alt = AppConstant::NUMERIC_ONE - $alt;
                }
                unset($subs);
            }
            $jsArray .= ']';
            /*
             * Data manipulation for potential questions
             */
            if ($sessionData['selfrom' . $assessmentId] == 'lib') {
                /*
                 * selecting from libraries
                 * remember search
                 */
                if (isset($params['search'])) {
                    $safeSearch = $params['search'];
                    $safeSearch = str_replace(' and ', ' ', $safeSearch);
                    $search = stripslashes($safeSearch);
                    $search = str_replace('"', '&quot;', $search);
                    $sessionData['lastsearch' . $courseId] = $safeSearch; //str_replace(" ","+",$safeSearch);
                    if (isset($params['searchall'])) {
                        $searchAll = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchAll = AppConstant::NUMERIC_ZERO;
                    }
                    $sessionData['searchall' . $courseId] = $searchAll;
                    if (isset($params['searchmine'])) {
                        $searchMine = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchMine = AppConstant::NUMERIC_ZERO;
                    }
                    if (isset($params['newonly'])) {
                        $newOnly = AppConstant::NUMERIC_ONE;
                    } else {
                        $newOnly = AppConstant::NUMERIC_ZERO;
                    }
                    $sessionData['searchmine' . $courseId] = $searchMine;
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($sessionData['lastsearch' . $courseId])) {
                    $safeSearch = $sessionData['lastsearch' . $courseId]; //str_replace("+"," ",$sessionData['lastsearch'.$courseId]);
                    $search = stripslashes($safeSearch);
                    $search = str_replace('"', '&quot;', $search);
                    $searchAll = $sessionData['searchall' . $courseId];
                    $searchMine = $sessionData['searchmine' . $courseId];
                } else {
                    $search = '';
                    $searchAll = AppConstant::NUMERIC_ZERO;
                    $searchMine = AppConstant::NUMERIC_ZERO;
                    $safeSearch = '';
                }
                if (trim($safeSearch) == '') {
                    $searchLikes = '';
                } else {
                    if (substr($safeSearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SIX) == 'regex:') {
                        $safeSearch = substr($safeSearch, AppConstant::NUMERIC_SIX);
                        $searchLikes = "imas_questionset.description REGEXP '$safeSearch' AND ";
                    } else {
                        $searchTerms = explode(" ", $safeSearch);
                        $searchLikes = '';
                        foreach ($searchTerms as $k => $v) {
                            if (substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE) == 'type=') {
                                $searchLikes .= "imas_questionset.qtype='" . substr($v, AppConstant::NUMERIC_FIVE) . "' AND ";
                                unset($searchTerms[$k]);
                            }
                        }
                        $searchLikes .= "((imas_questionset.description LIKE '%" . implode("%' AND imas_questionset.description LIKE '%", $searchTerms) . "%') ";
                        if (substr($safeSearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_THREE) == 'id=') {
                            $searchLikes = "imas_questionset.id='" . substr($safeSearch, AppConstant::NUMERIC_THREE) . "' AND ";
                        } else if (is_numeric($safeSearch)) {
                            $searchLikes .= "OR imas_questionset.id='$safeSearch') AND ";
                        } else {
                            $searchLikes .= ") AND";
                        }
                    }
                }

                if (isset($params['libs'])) {
                    if ($params['libs'] == '') {
                        $params['libs'] = $user['deflib'];
                    }
                    $searchLibs = $params['libs'];
                    $sessionData['lastsearchlibs' . $assessmentId] = $searchLibs;
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($params['listlib'])) {
                    $searchLibs = $params['listlib'];
                    $sessionData['lastsearchlibs' . $assessmentId] = $searchLibs;
                    $searchAll = AppConstant::NUMERIC_ZERO;
                    $sessionData['searchall' . $assessmentId] = $searchAll;
                    $sessionData['lastsearch' . $assessmentId] = '';
                    $searchLikes = '';
                    $search = '';
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($sessionData['lastsearchlibs' . $assessmentId])) {
                    $searchLibs = $sessionData['lastsearchlibs' . $assessmentId];
                } else {
                    if (isset($CFG['AMS']['guesslib']) && count($existingQuestion) > AppConstant::NUMERIC_ZERO) {
                        $maj = count($existingQuestion) / AppConstant::NUMERIC_TWO;
                        $existingQList = implode(',', $existingQuestion);
                        $query = LibraryItems::getByQuestionSetId($existingQuestion);
                        $foundMaj = false;
                        foreach ($query as $row) {
                            if ($row[COUNT('qsetid')] >= $maj) {
                                $searchLibs = $row['libid'];
                                $foundMaj = true;
                                break;
                            }
                        }
                        if (!$foundMaj) {
                            $searchLibs = $user['deflib'];
                        }
                    } else {
                        $searchLibs = $user['deflib'];
                    }
                }
//                $lList = "'" . implode("','", explode(',', $searchLibs)) . "'";
                $lList = explode(',', $searchLibs);
                if (!$beenTaken) {
                    /*
                     * Potential questions
                     */
                    $libSortOrder = array();
                    if (substr($searchLibs, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE) == AppConstant::ZERO_VALUE) {
                        $lNamesArray[0] = AppConstant::UNASSIGNED;
                        $libSortOrder[0] = AppConstant::NUMERIC_ZERO;
                    }
                    $query = Libraries::getByIdList(explode(',', $searchLibs));

                    foreach ($query as $row) {
                        $lNamesArray[$row['id']] = $row['name'];
                        $libSortOrder[$row['id']] = $row['sortorder'];
                    }
                    $lNames = implode(", ", $lNamesArray);
                    $pageLibRowHeader = ($searchAll == AppConstant::NUMERIC_ONE) ? AppConstant::TABLE_HEADER_LIB : "";

                    if (isset($search)) {
                        $result = QuestionSet::getByUserIdJoin($searchAll, $userId, $lList, $searchMine, $searchLikes);

                        if ($search == 'recommend' && count($existingQuestion) > AppConstant::NUMERIC_ZERO) {
                            $existingQList = implode(',', $existingQuestion);  //pulled from database, so no quotes needed
                            $result = QuestionSet::getByUserId($assessmentId, $userId, $existingQList);
                        }
                        if (count($result) == AppConstant::NUMERIC_ZERO) {
                            $noSearchResults = true;
                        } else {
                            $alt = AppConstant::NUMERIC_ZERO;
                            $lastLib = -AppConstant::NUMERIC_ONE;
                            $i = AppConstant::NUMERIC_ZERO;
                            $pageQuestionTable = array();
                            $pageLibsToUse = array();
                            $pageLibQIds = array();
                            $pageUseAvgTimes = false;

                            foreach ($result as $line) {
                                if ($newOnly && in_array($line['id'], $existingQuestion)) {
                                    continue;
                                }
                                if (isset($pageQuestionTable[$line['id']])) {
                                    continue;
                                }
                                if ($lastLib != $line['libid'] && isset($lNamesArray[$line['libid']])) {
                                    $pageLibsToUse[] = $line['libid'];
                                    $lastLib = $line['libid'];
                                    $pageLibQIds[$line['libid']] = array();
                                }
                                if (isset($libSortOrder[$line['libid']]) && $libSortOrder[$line['libid']] == AppConstant::NUMERIC_ONE) { //alpha
                                    $pageLibQIds[$line['libid']][$line['id']] = $line['description'];
                                } else {
                                    $pageLibQIds[$line['libid']][] = $line['id'];
                                }
                                $i = $line['id'];
                                $pageQuestionTable[$i]['checkbox'] = "<input type=checkbox name='nchecked[]' value='" . $line['id'] . "' id='qo$ln'>";
                                if (in_array($i, $existingQuestion)) {
                                    $pageQuestionTable[$i]['desc'] = '<span style="color: #999">' . filter($line['description']) . '</span>';
                                } else {
                                    $pageQuestionTable[$i]['desc'] = filter($line['description']);
                                }
                                $pageQuestionTable[$i]['preview'] = "<div onClick=\"previewq('selq','qo$ln',{$line['id']},true,false)\" style='width: 100%;' class='btn btn-primary'><img class = 'margin-right-ten small-preview-icon' src='" . AppUtility::getAssetURL() . 'img/prvAssess.png' . "'>&nbsp;Preview</div>";
                                $pageQuestionTable[$i]['type'] = $line['qtype'];
                                $avgTimePts = explode(',', $line['avgtime']);
                                if ($avgTimePts[0] > AppConstant::NUMERIC_ZERO) {
                                    $pageUseAvgTimes = true;
                                    $pageQuestionTable[$i]['avgtime'] = round($avgTimePts[0] / AppConstant::SECONDS, AppConstant::NUMERIC_ONE);
                                } else if (isset($avgTimePts[1]) && isset($avgTimePts[3]) && $avgTimePts[3] > AppConstant::NUMERIC_TEN) {
                                    $pageUseAvgTimes = true;
                                    $pageQuestionTable[$i]['avgtime'] = round($avgTimePts[1] / AppConstant::SECONDS, AppConstant::NUMERIC_ONE);
                                } else {
                                    $pageQuestionTable[$i]['avgtime'] = '';
                                }
                                if (isset($avgTimePts[3]) && $avgTimePts[3] > AppConstant::NUMERIC_TEN) {
                                    $pageQuestionTable[$i]['qdata'] = array($avgTimePts[2], $avgTimePts[1], $avgTimePts[3]);
                                }
                                if ($searchAll == AppConstant::NUMERIC_ONE) {
                                    $pageQuestionTable[$i]['lib'] = "<a href=" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId . '&listlib=' . $line['libid']) . ">List lib</a>";
                                } else {
                                    $pageQuestionTable[$i]['junkflag'] = $line['junkflag'];
                                    $pageQuestionTable[$i]['libitemid'] = $line['libitemid'];
                                }
                                $pageQuestionTable[$i]['extref'] = '';
                                $pageQuestionTable[$i]['cap'] = AppConstant::NUMERIC_ZERO;
                                if ($line['extref'] != '') {
                                    $extRef = explode('~~', $line['extref']);
                                    $hasVideo = false;
                                    $hasOther = false;
                                    $hasCap = false;
                                    foreach ($extRef as $v) {
                                        if (substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE) == "Video" || strpos($v, 'youtube.com') !== false || strpos($v, 'youtu.be') !== false) {
                                            $hasVideo = true;
                                            if (strpos($v, '!!1') !== false) {
                                                $pageQuestionTable[$i]['cap'] = AppConstant::NUMERIC_ONE;
                                            }
                                        } else {
                                            $hasOther = true;
                                        }
                                    }
                                    if ($hasVideo) {
                                        $pageQuestionTable[$i]['extref'] .= "<img src=" . AppUtility::getHomeURL() . 'img/video_tiny.png' . ">";
                                    }
                                    if ($hasOther) {
                                        $pageQuestionTable[$i]['extref'] .= "<img src=" . AppUtility::getHomeURL() . 'img/html_tiny.png' . ">";
                                    }
                                }
                                if ($line['solution'] != '' && ($line['solutionopts'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO) {
                                    $pageQuestionTable[$i]['extref'] .= "<img src=" . AppUtility::getHomeURL() . 'img/assess_tiny.png' . ">";
                                }
                                $pageQuestionTable[$i]['times'] = AppConstant::NUMERIC_ZERO;

                                if ($line['ownerid'] == $userId) {
                                    if ($line['userights'] == AppConstant::NUMERIC_ZERO) {
                                        $pageQuestionTable[$i]['mine'] = "Private";
                                    } else {
                                        $pageQuestionTable[$i]['mine'] = "Yes";
                                    }
                                } else {
                                    $pageQuestionTable[$i]['mine'] = "";
                                }
                                $pageQuestionTable[$i]['add'] = "<a style='background-color: #008E71;  width: 85%;' class='btn btn-primary add-btn-question' href=" . AppUtility::getURLFromHome('question', 'question/mod-question?qsetid=' . $line['id'] . '&aid=' . $assessmentId . '&cid=' . $courseId) . "><i class='fa fa-plus'></i>&nbsp; Add</a>";

                                if ($line['userights'] > AppConstant::NUMERIC_THREE || ($line['userights'] == AppConstant::NUMERIC_THREE && $line['groupid'] == $groupId) || $line['ownerid'] == $userId) {
                                    $pageQuestionTable[$i]['src'] = "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-data-set?id=' . $line['id'] . '&aid=' . $assessmentId . '&cid=' . $courseId . '&frompot=1') . "><i class='fa fa-fw'></i> Edit</a>";
                                } else {
                                    $pageQuestionTable[$i]['src'] = "<a href=" . AppUtility::getURLFromHome('question', 'question/view-source?id=' . $line['id'] . '&aid=' . $assessmentId . '&cid=' . $courseId) . "><i class='fa fa-fw'></i> View</a>";
                                }

                                $pageQuestionTable[$i]['templ'] = "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-data-set?id=' . $line['id'] . '&aid=' . $assessmentId . '&cid=' . $courseId . '&template=' . true) . "><i class='fa fa-archive'></i> Template</a>";
                                $ln++;

                            }
                            /*
                             * pull question use-age data
                             */
                            if (count($pageQuestionTable) > AppConstant::NUMERIC_ZERO) {
                                $allUsedQids = implode(',', array_keys($pageQuestionTable));
                                $query = Questions::getByQuestionSetId(array_keys($pageQuestionTable));
                                foreach ($query as $row) {
                                    $pageQuestionTable[$row['questionsetid']]['times'] = $row[COUNT('id')];
                                }
                            }
                            /*
                             * sort alpha sorted libraries
                             */
                            foreach ($pageLibsToUse as $libId) {
                                if ($libSortOrder[$libId] == AppConstant::NUMERIC_ONE) {
                                    natcasesort($pageLibQIds[$libId]);
                                    $pageLibQIds[$libId] = array_keys($pageLibQIds[$libId]);
                                }
                            }
                            if ($searchAll == AppConstant::NUMERIC_ONE) {
                                $pageLibsToUse = array_keys($pageLibQIds);
                            }
                        }
                    }

                }
            } else if ($sessionData['selfrom' . $assessmentId] == 'assm') {
                /*
                 * select from assessments
                 */
                if (isset($params['clearassmt'])) {
                    unset($sessionData['aidstolist' . $assessmentId]);
                }
                if (isset($params['achecked'])) {
                    if (count($params['achecked']) != AppConstant::NUMERIC_ZERO) {
                        $aidsToList = $params['achecked'];
                        $sessionData['aidstolist' . $assessmentId] = $aidsToList;
                        $this->writesessiondata($sessionData, $sessionId);
                    }
                }
                if (isset($sessionData['aidstolist' . $assessmentId])) {
                    /*
                     * list questions
                     */

                    $query = Assessments::getByAssessmentIds($sessionData['aidstolist' . $assessmentId]);
                    foreach ($query as $row) {
                        $aidNames[$row['id']] = $row['name'];
                        $items = str_replace('~', ',', $row['itemorder']);
                        if ($items == '') {
                            $aidItems[$row['id']] = array();
                        } else {
                            $aidItems[$row['id']] = explode(',', $items);
                        }
                    }
                    $x = AppConstant::NUMERIC_ZERO;
                    $pageAssessmentQuestions = array();
                    foreach ($sessionData['aidstolist' . $assessmentId] as $aidQuestion) {
                        $query = Questions::getByAssessmentIdJoin($aidQuestion);
                        if ($query == AppConstant::NUMERIC_ZERO) {
                            /*
                             * maybe defunct aid; if no questions in it, skip it
                             */
                            continue;
                        }
                        foreach ($query as $row) {
                            $questionSetId[$row['id']] = $row['qid'];
                            $description[$row['id']] = $row['description'];
                            $qTypes[$row['id']] = $row['qtype'];
                            $owner[$row['id']] = $row['ownerid'];
                            $useRights[$row['id']] = $row['userights'];
                            $extRef[$row['id']] = $row['extref'];
                            $qGroupId[$row['id']] = $row['groupid'];
                            $queResult = Questions::getQuestionCount($row['qid']);
                            $times[$row['id']] = $queResult[COUNT('id')];
                        }
                        $pageAssessmentQuestions['desc'][$x] = $aidNames[$aidQuestion];
                        $y = AppConstant::NUMERIC_ZERO;
                        foreach ($aidItems[$aidQuestion] as $qid) {
                            if (strpos($qid, '|') !== false) {
                                continue;
                            }
                            $pageAssessmentQuestions[$x]['checkbox'][$y] = "<input type=checkbox name='nchecked[]' id='qo$ln' value='" . $questionSetId[$qid] . "'>";
                            if (in_array($questionSetId[$qid], $existingQuestion)) {
                                $pageAssessmentQuestions[$x]['desc'][$y] = '<span style="color: #999">' . filter($description[$qid]) . '</span>';
                            } else {
                                $pageAssessmentQuestions[$x]['desc'][$y] = filter($description[$qid]);
                            }
                            $pageAssessmentQuestions[$x]['qsetid'][$y] = $questionSetId[$qid];
                            $pageAssessmentQuestions[$x]['preview'][$y] = "<input type=button value=\"Preview\" onClick=\"previewq('selq','qo$ln',$questionSetId[$qid],true)\"/>";
                            $pageAssessmentQuestions[$x]['type'][$y] = $qTypes[$qid];
                            $pageAssessmentQuestions[$x]['times'][$y] = $times[$qid];
                            $pageAssessmentQuestions[$x]['mine'][$y] = ($owner[$qid] == $userId) ? "Yes" : "";
                            $pageAssessmentQuestions[$x]['add'][$y] = "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-question?qsetid=' . $questionSetId[$qid] . '&aid=' . $assessmentId . '&cid=' . $courseId) . ">Add</a>";
                            $pageAssessmentQuestions[$x]['src'][$y] = ($useRights[$qid] > AppConstant::NUMERIC_THREE || ($useRights[$qid] == AppConstant::NUMERIC_THREE && $qGroupId[$qid] == $groupId) || $owner[$qid] == $userId) ? "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-data-set?id=' . $questionSetId[$qid] . '&aid=' . $assessmentId . '&cid=' . $courseId . '&frompot=1') . "><i class='fa fa-fw'></i>Edit</a>" : "<a href=" . AppUtility::getURLFromHome('question', 'question/view-source?id=' . $questionSetId[$qid] . '&aid=' . $assessmentId . '&cid=' . $courseId) . ">View</a>";
                            $pageAssessmentQuestions[$x]['templ'][$y] = "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-data-set?id=' . $questionSetId[$qid] . '&aid=' . $assessmentId . '&cid=' . $courseId . '&template=true') . ">Template</a>";
                            $pageAssessmentQuestions[$x]['extref'][$y] = '';
                            $pageAssessmentQuestions[$x]['cap'][$y] = AppConstant::NUMERIC_ZERO;
                            if ($extRef[$qid] != '') {
                                $extRefArr = explode('~~', $extRef[$qid]);
                                $hasVideo = false;
                                $hasOther = false;
                                foreach ($extRefArr as $v) {
                                    if (substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE) == "Video" || strpos($v, 'youtube.com') !== false || strpos($v, 'youtu.be') !== false) {
                                        $hasVideo = true;
                                        if (strpos($v, '!!1') !== false) {
                                            $pageAssessmentQuestions[$x]['cap'][$y] = AppConstant::NUMERIC_ONE;
                                        }
                                    } else {
                                        $hasOther = true;
                                    }
                                }
                                if ($hasVideo) {
                                    $pageAssessmentQuestions[$x]['extref'][$y] .= "<img src=" . AppUtility::getHomeURL() . '/img/video_tiny.png' . "/>";
                                }
                                if ($hasOther) {
                                    $pageAssessmentQuestions[$x]['extref'][$y] .= "<img src=" . AppUtility::getHomeURL() . '/img/html_tiny.png' . "/>";
                                }
                            }
                            $ln++;
                            $y++;
                        }
                        $x++;
                    }
                } else {
                    /*
                     * choose assessments
                     */
                    $items = unserialize($course['itemorder']);
                    $itemAssoc = array();
                    $result = Items::getByAssessmentId($courseId, $assessmentId);
                    foreach ($result as $row) {
                        $itemAssoc[$row['itemid']] = $row;
                    }
                    $i = AppConstant::NUMERIC_ZERO;
                    $pageAssessmentList = $this->addToAssessmentList($items, $i, $itemAssoc);
                }
            }
        }
        $this->includeCSS(['question/question.css', 'course/course.css', 'roster/roster.css']);
        $this->includeJS(['jquery.min.js', 'question/addquestions.js', 'tablesorter.js', 'general.js', 'question/junkflag.js', 'question/addqsort.js']);
        $responseArray = $this->addQuestionRenderData($course, $assessmentId, $params, $overwriteBody, $body, $defPoints, $searchLibs, $beenTaken, $pageAssessmentName, $itemOrder, $sessionData, $jsArray, $displayMethod, $lNames, $search, $searchAll, $searchMine, $newOnly, $noSearchResults, $pageLibRowHeader, $pageUseAvgTimes, $pageLibsToUse, $alt, $lNamesArray, $pageLibQIds, $pageQuestionTable, $qid, $pageAssessmentQuestions, $pageAssessmentList, $address,$addQuestionData);
        return $this->renderWithData('addQuestions', $responseArray);
    }

    public function addToAssessmentList($items, $i, $itemAssoc)
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->addToAssessmentList($item['items'], $i, $itemAssoc);
            } else if (isset($itemAssoc[$item])) {
                $pageAssessmentList[$i]['id'] = $itemAssoc[$item]['id'];
                $pageAssessmentList[$i]['name'] = $itemAssoc[$item]['name'];
                $itemAssoc[$item]['summary'] = strip_tags($itemAssoc[$item]['summary']);
                if (strlen($itemAssoc[$item]['summary']) > AppConstant::NUMERIC_HUNDREAD) {
                    $itemAssoc[$item]['summary'] = substr($itemAssoc[$item]['summary'], AppConstant::NUMERIC_ZERO, AppConstant::NINETY_SEVEN) . '...';
                }
                $pageAssessmentList[$i]['summary'] = $itemAssoc[$item]['summary'];
                $i++;
            }
        }
        return $pageAssessmentList;
    }

    /**
     * @param $course
     * @param $assessmentId
     * @param $params
     * @param $overwriteBody
     * @param $body
     * @param $defPoints
     * @param $searchLibs
     * @param $beenTaken
     * @param $pageAssessmentName
     * @param $itemOrder
     * @param $sessionData
     * @param $jsArray
     * @param $displayMethod
     * @param $lNames
     * @param $search
     * @param $searchAll
     * @param $searchMine
     * @param $newOnly
     * @param $noSearchResults
     * @param $pageLibRowHeader
     * @param $pageUseAvgTimes
     * @param $pageLibsToUse
     * @param $alt
     * @param $lNamesArray
     * @param $pageLibQIds
     * @param $pageQuestionTable
     * @param $qid
     * @param $pageAssessmentQuestions
     * @param $pageAssessmentList
     * @param $address
     * @return array
     */
    public function addQuestionRenderData($course, $assessmentId, $params, $overwriteBody, $body, $defPoints, $searchLibs, $beenTaken, $pageAssessmentName, $itemOrder, $sessionData, $jsArray, $displayMethod, $lNames, $search, $searchAll, $searchMine, $newOnly, $noSearchResults, $pageLibRowHeader, $pageUseAvgTimes, $pageLibsToUse, $alt, $lNamesArray, $pageLibQIds, $pageQuestionTable, $qid, $pageAssessmentQuestions, $pageAssessmentList, $address, $addQuestionData)
    {
        $responseArray = array('course' => $course, 'assessmentId' => $assessmentId, 'params' => $params, 'overwriteBody' => $overwriteBody, 'body' => $body,
            'defpoints' => $defPoints, 'searchlibs' => $searchLibs, 'beentaken' => $beenTaken, 'pageAssessmentName' => $pageAssessmentName,
            'itemorder' => $itemOrder, 'sessiondata' => $sessionData, 'jsarr' => $jsArray, 'displaymethod' => $displayMethod, 'lnames' => $lNames,
            'search' => $search, 'searchall' => $searchAll, 'searchmine' => $searchMine, 'newonly' => $newOnly, 'noSearchResults' => $noSearchResults,
            'pageLibRowHeader' => $pageLibRowHeader, 'pageUseavgtimes' => $pageUseAvgTimes, 'pageLibstouse' => $pageLibsToUse, 'altr' => $alt,
            'lnamesarr' => $lNamesArray, 'pageLibqids' => $pageLibQIds, 'pageQuestionTable' => $pageQuestionTable, 'qid' => $qid,
            'pageAssessmentQuestions' => $pageAssessmentQuestions, 'pageAssessmentList' => $pageAssessmentList, 'address' => $address,'addQuestionData' => $addQuestionData);
        return $responseArray;
    }

    public function actionSaveQuestions()
    {
        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress'));
    }

    public function actionAddVideoTimes()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $user = $this->user;
        $isTeacher = $this->isTeacher($user['id'], $courseId);
        $aid = $this->getParamVal('aid');
        $params = $this->getRequestParams();
        $course = Course::getById($courseId);
        $this->layout = 'master';
        if (!($isTeacher)) {
            $body = AppConstant::NO_ACCESS_RIGHTS;
        }
        if (isset($params['vidid'])) {
            $params = stripslashes_deep($params);
            $videoId = $params['vidid'];
            $data = array();
            $i = AppConstant::NUMERIC_ZERO;
            while (isset($params['segtitle' . $i])) {
                $n = array();
                $n[0] = trim(htmlentities($params['segtitle' . $i]));
                $thisTime = $this->timeToSec($params['segend' . $i]);
                $n[1] = $thisTime;
                if (isset($params['qn' . $i])) {
                    $n[2] = $params['qn' . $i];
                }
                if (isset($params['hasfollowup' . $i])) {
                    $n[3] = $this->timeToSec($_POST['followupend' . $i]);

                    if (isset($params['showlink' . $i])) {
                        $n[4] = true;
                    } else {
                        $n[4] = false;
                    }
                    $n[5] = trim(htmlentities($params['followuptitle' . $i]));
                }
                $data[$thisTime] = $n;
                $i++;
            }
            ksort($data);
            $data = array_values($data);
            array_unshift($data, $videoId);
            if (trim($params['finalseg']) != '') {
                array_push($data, array(htmlentities($params['finalseg'])));
            }
            $data = serialize($data);
            Assessments::updateVideoCued($data, $aid);
            return $this->redirect('add-questions?cid=' . $courseId . '&aid=' . $aid);
        }
        $toCopy = 'itemorder,viddata';
        $assessmentData = Assessments::CommonMethodToGetAssessmentData($toCopy, $aid);
        $qOrder = explode(',', $assessmentData['itemorder']);
        $vidData = $assessmentData['viddata'];
        $qidByNum = array();
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($qOrder); $i++) {
            if (strpos($qOrder[$i], '~') !== false) {
                $qIds = explode('~', $qOrder[$i]);
                if (strpos($qIds[0], '|') !== false) {
                    $qidByNum[$i] = $qIds[1];
                } else {
                    $qidByNum[$i] = $qIds[0];
                }
            } else {
                $qidByNum[$i] = $qOrder[$i];
            }
        }
        $qTitleById = array();
        $QuestionSetData = Questions::getDataByJoin($aid);
        if ($QuestionSetData) {
            foreach ($QuestionSetData as $row) {
                if (strlen($row['description']) < AppConstant::NUMERIC_THIRTY) {
                    $qTitle[$row['id']] = $row['description'];
                } else {
                    $qTitle[$row['id']] = substr($row['description'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_THIRTY) . '...';
                }
            }
        }
        if ($vidData != '') {
            $data = unserialize($vidData);
            $videoId = array_shift($data);
            $n = count($data);
            $title = array();
            $endTime = array();
            $qn = array();
            $followUpTitle = array();
            $followUpEndDTime = array();
            $hasFollowUp = array();
            $showLink = array();
            $finalSegTitle = '';
            for ($i = AppConstant::NUMERIC_ZERO; $i < $n; $i++) {
                $title[$i] = $data[$i][0];
                if (count($data[$i]) == AppConstant::NUMERIC_ONE) {
                    $finalSegTitle = $data[$i][0];
                    $n--;
                } else {
                    $endTime[$i] = $this->secToTime($data[$i][1]);
                }
                if (count($data[$i]) > AppConstant::NUMERIC_TWO) {
                    $qn[$i] = $data[$i][2];
                    if (count($data[$i]) > AppConstant::NUMERIC_THREE) {
                        $followUpTitle[$i] = $data[$i][5];
                        $followUpEndDTime[$i] = $this->secToTime($data[$i][3]);
                        $showLink[$i] = $data[$i][4];
                        $hasFollowUp[$i] = true;
                    } else {
                        $hasFollowUp[$i] = false;
                        $followUpTitle[$i] = '';
                        $followUpEndDTime[$i] = '';
                        $showLink[$i] = true;
                    }
                }
            }
        } else {
            $n = count($qOrder);
            $title = array_fill(AppConstant::NUMERIC_ZERO, $n, '');
            $endTime = array_fill(AppConstant::NUMERIC_ZERO, $n, '');
            $qn = range(AppConstant::NUMERIC_ZERO, $n - AppConstant::NUMERIC_ONE);
            $followUpTitle = array_fill(AppConstant::NUMERIC_ZERO, $n, '');
            $followUpEndDTime = array_fill(AppConstant::NUMERIC_ZERO, $n, '');
            $showLink = array_fill(AppConstant::NUMERIC_ZERO, $n, true);
            $finalSegTitle = '';
            $videoId = '';
        }
        $this->includeJS(['editor/plugins/media/js/embed.js']);
        $this->includeCSS(['question/question.css']);
        $responseData = array('n' => $n, 'qn' => $qn, 'title' => $title, 'endTime' => $endTime, 'qTitle' => $qTitle, 'qidByNum' => $qidByNum, 'hasFollowUp' => $hasFollowUp, 'followUpTitle' => $followUpTitle, 'showLink' => $showLink, 'finalSegTitle' => $finalSegTitle, 'followUpEndDTime' => $followUpEndDTime, 'vidId' => $videoId, 'course' => $course, 'courseId' => $courseId, 'aid' => $aid);
        return $this->renderWithData('AddVideoTimes', $responseData);
    }

    function timeToSec($t)
    {
        if (strpos($t, ':') === false) {
            $time = $t;
        } else {
            $x = explode(':', $t);
            $time = AppConstant::SECONDS * $x[0] + $x[1];
        }
        return $time;
    }

    function secToTime($t)
    {
        if ($t < AppConstant::SECONDS) {
            return $t;
        } else {
            $o = floor($t / AppConstant::SECONDS) . ':';
            $t = $t % AppConstant::SECONDS;
            if ($t < AppConstant::NUMERIC_TEN) {
                $o .= AppConstant::ZERO_VALUE . $t;
            } else {
                $o .= $t;
            }
        }
        return $o;
    }

    public function actionCategorize()
    {
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $assessmentId = $params['aid'];
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        if (isset($params['record'])) {
            $query = Questions::getByAssessmentId($assessmentId);
            foreach ($query as $row) {
                if ($row['category'] != $params[$row['id']]) {
                    Questions::updateQuestionFields($params[$row['id']], $row['id']);
                }
            }
            return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' . $courseId));
        }
        $query = Outcomes::getByCourseId($courseId);
        $outcomeNames = array();
        foreach ($query as $row) {
            $outcomeNames[$row['id']] = $row['name'];
        }
        $query = Course::getOutcome($courseId);
        if ($query['outcomes'] == '') {
            $outcomeArray = array();
        } else {
            $outcomeArray = unserialize($query['outcomes']);
        }
        global $outcomes;
        if ($outcomeArray) {
            $this->flattenArray($outcomeArray);
        }
        $query = Libraries::getQidAndLibID($assessmentId);
        $libNames = array();
        $questionLibs = array();
        foreach ($query as $row) {
            $questionLibs[$row['id']][] = $row['libid'];
            $libNames[$row['libid']] = $row['name'];
        }
        $query = QuestionSet::getQuestionData($assessmentId);

        $descriptions = array();
        $category = array();
        $extraCats = array();
        foreach ($query as $line) {
            $descriptions[$line['id']] = $line['description'];
            $category[$line['id']] = $line['category'];
            if (!is_numeric($line['category']) && trim($line['category']) != '' && !in_array($line['category'], $extraCats)) {
                $extraCats[] = $line['category'];
            }
        }

        $query = Assessments::getItemOrderById($assessmentId);
        $itemArray = explode(',', $query['itemorder']);
        foreach ($itemArray as $k => $v) {
            if (($p = strpos($v, '~')) !== false) {
                $itemArray[$k] = substr($v, $p + AppConstant::NUMERIC_ONE);
            }
        }
        $itemArray = implode(',', $itemArray);
        $itemArray = str_replace('~', ',', $itemArray);
        $itemArray = explode(',', $itemArray);
        $this->includeCSS(['question/question.css','dataTables.bootstrap.css']);
        $this->includeJS(['question/categorize.js','jquery.dataTables.min.js','dataTables.bootstrap.js']);
        $responseArray = array('cid' => $courseId, 'aid' => $assessmentId, 'itemarr' => $itemArray, 'descriptions' => $descriptions, 'category' => $category,
            'outcomes' => $outcomes, 'outcomenames' => $outcomeNames, 'questionlibs' => $questionLibs, 'libnames' => $libNames,
            'extracats' => $extraCats, 'course' => $course);
        return $this->renderWithData('categorize', $responseArray);
    }

    public function flattenArray($ar)
    {
        global $outcomes;
        foreach ($ar as $v) {
            if (is_array($v)) {
                /*
                 * outcome group
                 */
                $outcomes[] = array($v['name'], AppConstant::NUMERIC_ONE);
                $this->flattenArray($v['outcomes']);
            } else {
                $outcomes[] = array($v, AppConstant::NUMERIC_ZERO);
            }
        }
    }

    public function actionPrintTest()
    {
        $user = $this->user;
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $this->layout = 'master';
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        /*
         * Check permissions and set flags
         */
        if (!($teacherId)) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
//            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {
            /*
             * Permissions are ok perform data manipulation
             */
            $assessmentId = $this->getParamVal('aid');
        }
        $renderData = array('course' => $course, 'courseId' => $courseId, 'assessmentId' => $assessmentId, 'overwriteBody' => $overwriteBody,
            'params' => $params, 'body' => $body);
        return $this->renderWithData('printTest', $renderData);
    }

    public function actionLibraryTree()
    {
        $user = $this->user;
        $params = $this->getRequestParams();
        $myRights = $user['rights'];
        $libraryData = Libraries::getAllLibrariesByJoin();
        $libraryTree = array();
        foreach($libraryData as $singleLibrary){
            if(!empty($singleLibrary['id'])){

//                [id] => 4
//                            [name] => library fffffffffffffff gggggggggggggg sddddddddddddddwe we
//                            [parent] => 0
//                            [ownerid] => 26
//                            [userights] => 8
//                            [sortorder] => 0
//                            [groupid] => 0
//                            [count] => 0


                if(empty($libraryTree[$singleLibrary['parent']]['branch'])){
                    $libraryTree[$singleLibrary['parent']]['branch'] = array();
                }
                if($singleLibrary['parent'] == '0') {
                    $libraryTree[$singleLibrary['id']] = array(
                        'id' => $singleLibrary['id'],
                        'name' => $singleLibrary['name'],
                        'parent' => $singleLibrary['parent'],
                        'ownerid' => $singleLibrary['ownerid'],
                        'userights' => $singleLibrary['userights'],
                        'sortorder' => $singleLibrary['sortorder'],
                        'groupid' => $singleLibrary['groupid'],
                        'count' => $singleLibrary['count']
                    );
                }
                else{
                    array_push($libraryTree[$singleLibrary['parent']]['branch'], $singleLibrary);
                }
                $libraryTree[$singleLibrary['parent']]['branch'] = $singleLibrary;
            }
        }
        $this->includeCSS(['question/libtree.css']);
        $this->includeJS(['general.js', 'question/libtree2.js']);
        $renderData = array('myRights' => $myRights, 'params' => $params, 'libraryData' => $libraryData);
        return $this->renderWithData('questionLibraries', $renderData);
    }

    public function actionModDataSet()
    {
        $user = $this->user;
        $myRights = $user['rights'];
        $userId = $user['id'];
        $userFullName = $user['FirstName'] . ' ' . $user['LastName'];
        $groupId = $user['groupid'];
        $userDefLib = $user['deflib'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $courseId = $params['cid'];
        $sessionData = $this->getSessionData($this->getSessionId());
        $teacherId = $this->isTeacher($userId, $courseId);
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            $this->setErrorFlash(AppConstant::NO_TEACHER_RIGHTS);
            return $this->redirect('mod-data-set?cid='.$courseId); //need to check path
        }
        $isAdmin = false;
        $isGrpAdmin = false;

        if ($myRights == AppConstant::ADMIN_RIGHT) {
            $teacherId = $userId;
            $adminAsTeacher = true;
        }
        if ($params['cid'] == 'admin') {
            if ($myRights == AppConstant::ADMIN_RIGHT) {
                $isAdmin = true;
            } else if ($myRights == AppConstant::GROUP_ADMIN_RIGHT) {
                $isGrpAdmin = true;
            }
        }

        if (isset($adminAsTeacher) && $adminAsTeacher) {
            if ($myRights == AppConstant::ADMIN_RIGHT) {
                $isAdmin = true;
            } else if ($myRights == AppConstant::GROUP_ADMIN_RIGHT) {
                $isGrpAdmin = true;
            }
        }

        if (isset($params['frompot'])) {
            $fromPot = AppConstant::NUMERIC_ONE;
        } else {
            $fromPot = AppConstant::NUMERIC_ZERO;
        }

        $outputMsg = '';
        $errorMsg = '';

        $course = Course::getById($courseId);
        if (isset($params['qtext'])) {
            $now = time();
            $params['qtext'] = $this->stripSmartQuotes(stripslashes($params['qtext']));
            $params['control'] =  $this->stripSmartQuotes(stripslashes($params['control']));
            $params['qcontrol'] = $this->stripSmartQuotes(stripslashes($params['qcontrol']));
            $params['solution'] = $this->stripSmartQuotes(stripslashes($params['solution']));
            $params['qtext'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm', '$1', $params['qtext']);
            $params['solution'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm', '$1', $params['solution']);

            if (trim($params['solution']) == AppConstant::PARAGRAPH_TAG) {
                $params['solution'] = '';
            }

            if (strpos($params['qtext'], 'data:image') !== false) {
                require_once("../components/htmLawed.php");
                $params['qtext'] = convertdatauris($params['qtext']);
            }
            /*
             * handle help references
             */
            if (isset($params['id']) || isset($params['templateid'])) {
                if (isset($params['id'])) {
                    $query = QuestionSet::getExtRef($params['id']);
                } else {
                    $query = QuestionSet::getExtRef($params['templateid']);
                }
                $extRef = $query['extref'];
                if ($extRef == '') {
                    $extRef = array();
                } else {
                    $extRef = explode('~~', $extRef);
                }
                $newExtRef = array();
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($extRef); $i++) {
                    if (!isset($params["delhelp-$i"])) {
                        $newExtRef[] = $extRef[$i];
                    }
                }
            } else {
                $newExtRef = array();
            }
            /*
             * DO we need to add a checkbox or something for updating this if captions are added later?
             */
            if ($params['helpurl'] != '') {
                $videoId = $this->getVideoId($params['helpurl']);
                if ($videoId == '') {
                    $captioned = AppConstant::NUMERIC_ZERO;
                } else {
                    $ctx = stream_context_create(array('http' =>
                        array(
                            'timeout' => AppConstant::NUMERIC_ONE
                        )
                    ));
                    $t = @file_get_contents('http://video.google.com/timedtext?lang=en&v=' . $videoId, false, $ctx);
                    $captioned = ($t == '') ? AppConstant::NUMERIC_ZERO : AppConstant::NUMERIC_ONE;
                }
                $newExtRef[] = $params['helptype'] . '!!' . $params['helpurl'] . '!!' . $captioned;
            }
            $extRef = implode('~~', $newExtRef);
            if (isset($params['doreplaceby'])) {
                $replaceBy = intval($params['replaceby']);
            } else {
                $replaceBy = AppConstant::NUMERIC_ZERO;
            }
            $solutionOpts = AppConstant::NUMERIC_ZERO;
            if (isset($params['usesrand'])) {
                $solutionOpts += AppConstant::NUMERIC_ONE;
            }
            if (isset($params['useashelp'])) {
                $solutionOpts += AppConstant::NUMERIC_TWO;
            }
            if (isset($params['usewithans'])) {
                $solutionOpts += AppConstant::NUMERIC_FOUR;
            }
            $params['qtext'] = preg_replace('/<([^<>]+?)>/', "&&&L$1&&&G", $params['qtext']);
            $params['qtext'] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $params['qtext']);
            $params['qtext'] = str_replace(array("&&&L", "&&&G"), array("<", ">"), $params['qtext']);
            $params['solution'] = preg_replace('/<([^<>]+?)>/', "&&&L$1&&&G", $params['solution']);
            $params['solution'] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $params['solution']);
            $params['solution'] = str_replace(array("&&&L", "&&&G"), array("<", ">"), $params['solution']);
            $params['description'] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $params['description']);
            /*
             * modifying existing
             */
            if (isset($params['id'])) {
                $questionSetId = intval($params['id']);
                $isOk = true;
                if ($isGrpAdmin) {
                    $query = QuestionSet::getByGroupId($params['id'], $groupId);
                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                        $isOk = false;
                    }
                }
                /*
                 * check is owner or is allowed to modify
                 */
                if (!$isAdmin && !$isGrpAdmin)
                {
                    $query = QuestionSet::getByUserIdGroupId($params['id'], $userId, $groupId);
                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                        $isOk = false;
                    }
                }
                $query = QuestionSet::updateQuestionSet($params, $now, $extRef, $replaceBy, $solutionOpts);
                /*
                 * checked separately above now
                 */
                if ($isOk) {
                    if (count($query) > AppConstant::NUMERIC_ZERO) {
                        $outputMsg .= AppConstant::OUTPUT_MSG_ONE;
                    } else {
                        $outputMsg .= AppConstant::OUTPUT_MSG_TWO;
                    }
                }
                $query = QImages::getByQuestionSetId($params['id']);
                $imgCount = count($query);
                foreach ($query as $row) {
                    if (isset($params['delimg-' . $row['id']])) {
                        $file = QImages::getByFileName($row['filename']);
                        /*
                         * Don't delete if file is used in other questions
                         */
                        if (count($file) == AppConstant::NUMERIC_ONE) {
                            filehandler::deleteqimage($row['filename']);
                        }
                        QImages::deleteById($row['id']);
                        $imgCount--;
                        if ($imgCount == AppConstant::NUMERIC_ZERO) {
                            QuestionSet::setHasImage($params['id'], AppConstant::NUMERIC_ZERO);
                        }
                    } else if ($row['var'] != $params['imgvar-' . $row['id']] || $row['alttext'] != $params['imgalt-' . $row['id']]) {
                        $newVar = str_replace('$', '', $params['imgvar-' . $row['id']]);
                        $newAlt = $params['imgalt-' . $row['id']];
                        $disallowedVar = array('link', 'qidx', 'qnidx', 'seed', 'qdata', 'toevalqtxt', 'la', 'GLOBALS', 'laparts', 'anstype', 'kidx', 'iidx', 'tips', 'options', 'partla', 'partnum', 'score');
                        if (in_array($newVar, $disallowedVar)) {
                            $errorMsg .= $newVar . AppConstant::IMAGE_FILE_ERROR2;
                        } else {
                            QImages::setVariableAndText($row['id'], $newVar, $newAlt);
                        }
                    }
                }
                if ($replaceBy != AppConstant::NUMERIC_ZERO) {
                    Questions::setQuestionSetId($questionSetId, $replaceBy);
                }
            } else {
                /*
                 * adding new
                 */
                $mt = microtime();
                $uQid = substr($mt, AppConstant::NUMERIC_ELEVEN) . substr($mt, AppConstant::NUMERIC_TWO, AppConstant::NUMERIC_SIX);
                $ancestors = '';
                $ancestorAuthors = '';
                if (isset($params['templateid'])) {
                    $query = QuestionSet::getQuestionDataById($params['templateid']);
                    $ancestors = $query['ancestors'];
                    $lastAuthor = $query['author'];
                    $ancestorAuthors = $query['ancestorauthors'];
                    if ($ancestors != '') {
                        $ancestors = intval($params['templateid']) . ',' . $ancestors;
                    } else {
                        $ancestors = intval($params['templateid']);
                    }
                    if ($ancestorAuthors != '') {
                        $anAuthorArray = explode('; ', $ancestorAuthors);
                        if (!in_array($lastAuthor, $anAuthorArray)) {
                            $ancestorAuthors = $lastAuthor . '; ' . $ancestorAuthors;
                        }
                    } else if ($lastAuthor != $params['author']) {
                        $ancestorAuthors = $lastAuthor;
                    }
                }
                $questionSetArray = array();
                $questionSetArray['uniqueid'] = $uQid;
                $questionSetArray['adddate'] = $now;
                $questionSetArray['lastmoddate'] = $now;
                $questionSetArray['description'] = $params['description'];
                $questionSetArray['ownerid'] = $userId;
                $questionSetArray['author'] = $params['author'];
                $questionSetArray['userights'] = $params['userights'];
                $questionSetArray['license'] = $params['license'];
                $questionSetArray['otherattribution'] = $params['addattr'];
                $questionSetArray['qtype'] = $params['qtype'];
                $questionSetArray['control'] = $params['control'];
                $questionSetArray['qcontrol'] = $params['qcontrol'];
                $questionSetArray['qtext'] = $params['qtext'];
                $questionSetArray['answer'] = $params['answer'];
                $questionSetArray['hasimg'] = $params['hasimg'];
                $questionSetArray['ancestors'] = $ancestors . '';
                $questionSetArray['ancestorauthors'] = $ancestorAuthors;
                $questionSetArray['extref'] = $extRef;
                $questionSetArray['replaceby'] = $replaceBy;
                $questionSetArray['solution'] = $params['solution'];
                $questionSetArray['solutionopts'] = $solutionOpts;

                $questionSet = new QuestionSet();
                $questionSetId = $questionSet->createQuestionSet($questionSetArray);
                $params['id'] = $questionSetId;
                if (isset($params['templateid'])) {
                    $query = QImages::getByQuestionSetId($params['templateid']);
                    foreach ($query as $row) {
                        if (!isset($params['delimg-' . $row['id']])) {
                            $qImage = new QImages();
                            $qImage->createQImages($questionSetId, $row);
                        }
                    }
                }

                if (isset($params['makelocal'])) {
                    Questions::setQuestionSetIdById($questionSetId, $params['makelocal']);
                    $outputMsg .= AppConstant::Question_OUTPUT_MSG1;
                    $fromPot = AppConstant::NUMERIC_ZERO;
                } else {
                    $outputMsg .= AppConstant::Question_OUTPUT_MSG2;
                    $fromPot = AppConstant::NUMERIC_ONE;
                }
            }
            /*
             * upload image files if attached
             */
            if ($_FILES['imgfile']['name'] != '') {
                $disallowedVar = array('link', 'qidx', 'qnidx', 'seed', 'qdata', 'toevalqtxt', 'la', 'GLOBALS', 'laparts', 'anstype', 'kidx', 'iidx', 'tips', 'options', 'partla', 'partnum', 'score');
                if (trim($params['newimgvar']) == '') {
                    $errorMsg .= AppConstant::IMAGE_FILE_ERROR1;
                } else if (in_array($params['newimgvar'], $disallowedVar)) {
                    $errorMsg .= $newVar . AppConstant::IMAGE_FILE_ERROR2;
                } else {
                    $uploaddir = AppConstant::UPLOAD_DIRECTORY . 'qimages/';
                    $userFileName = preg_replace('/[^\w\.]/', '', basename($_FILES['imgfile']['name']));
                    $filename = $userFileName;

                    $result_array = getimagesize($_FILES['imgfile']['tmp_name']);
                    if ($result_array === false) {
                        $errorMsg .= AppConstant::IMAGE_FILE_ERROR3;
                    } else {
                        if (($filename = filehandler::storeuploadedqimage('imgfile', $filename)) !== false) {
                            $params['newimgvar'] = str_replace('$', '', $params['newimgvar']);
                            $questImageData = array();
                            $questImageData['var'] = $params['newimgvar'];
                            $questImageData['filename'] = $filename;
                            $questImageData['alttext'] = $params['newimgalt'];
                            $qImage = new QImages();
                            $qImage->createQImages($questionSetId, $questImageData);
                            QuestionSet::setHasImage($questionSetId, AppConstant::NUMERIC_ONE);
                        } else {
                            $this->setErrorFlash(AppConstant::ERROR_IN_IMAGE_UPLOAD);
                            return $this->redirect('mod-data-set?cid='.$courseId); //need to check path
                        }
                    }
                }
            }
            /*
             * update libraries
             */
            $newLibs = explode(",", $params['libs']);
            if (in_array(AppConstant::ZERO_VALUE, $newLibs) && count($newLibs) > AppConstant::NUMERIC_ONE) {
                array_shift($newLibs);
            }
            if ($params['libs'] == '') {
                $newLibs = array();
            }
            $libraryData = LibraryItems::getByGroupId($groupId, $questionSetId, $userId, $isGrpAdmin, $isAdmin);
            $existing = array();
            foreach ($libraryData as $row) {
                $existing[] = $row['libid'];
            }
            $toAdd = array_values(array_diff($newLibs, $existing));
            $toRemove = array_values(array_diff($existing, $newLibs));

            while (count($toRemove) > AppConstant::NUMERIC_ZERO && count($toAdd) > AppConstant::NUMERIC_ZERO) {
                $toChange = array_shift($toRemove);
                $toRep = array_shift($toAdd);
                LibraryItems::setLibId($toRep, $questionSetId, $toChange);
            }
            if (count($toAdd) > AppConstant::NUMERIC_ZERO) {
                foreach ($toAdd as $libId) {
                    $tempLibArray['libid'] = $libId;
                    $tempLibArray['qsetid'] = $questionSetId;
                    $tempLibArray['ownerid'] = $userId;
                    $lib = new LibraryItems();
                    $lib->createLibraryItems($tempLibArray);
                }
            } else if (count($toRemove) > AppConstant::NUMERIC_ZERO) {
                foreach ($toRemove as $libId) {
                    LibraryItems::deleteLibraryItems($libId, $questionSetId);
                }
            }
            if (count($newLibs) == AppConstant::NUMERIC_ZERO) {
                $query = LibraryItems::getByQid($questionSetId);
                if (count($query) == AppConstant::NUMERIC_ZERO) {
                    $tempLibArray['libid'] = AppConstant::NUMERIC_ZERO;
                    $tempLibArray['qsetid'] = $questionSetId;
                    $tempLibArray['ownerid'] = $userId;
                    $lib = new LibraryItems();
                    $lib->createLibraryItems($tempLibArray);
                }
            }
            if (!isset($params['aid'])) {
                $outputMsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $courseId) . ">Return to Question Set Management</a>\n";
            } else {
                if ($fromPot == AppConstant::NUMERIC_ONE) {
                    $outputMsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-question?qsetid=' . $questionSetId . '&cid=' . $courseId . '&aid=' . $params['aid'] . '&process=true&usedef=true') . ">Add Question to Assessment using Defaults</a> | \n";
                    $outputMsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-question?qsetid=' . $questionSetId . '&cid=' . $courseId . '&aid=' . $params['aid']) . ">Add Question to Assessment</a> | \n";
                }
                $outputMsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $params['aid']) . ">Return to Assessment</a>\n";
            }
            if ($params['test'] == AppConstant::SAVE_AND_TEST) {
                $outputMsg .= "<script>addr = '" . AppUtility::getURLFromHome('question', 'question/test-question?cid=' . $courseId . '&qsetid=' . $params['id']) . "';";
                $outputMsg .= AppConstant::OUTPUT_MSG_THREE;
                $outputMsg .= AppConstant::OUTPUT_MSG_FOUR;
                $outputMsg .= "</script>";
            } else {
                if ($errorMsg == '' && !isset($params['aid'])) {
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $courseId));
                } else if ($errorMsg == '' && $fromPot == AppConstant::NUMERIC_ZERO) {
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $params['aid']));
                } else {
                    $errorMsg;
                    $outputMsg;
                }
                exit;
            }
        }
        $myName = $user['LastName'] . ',' . $user['FirstName'];
        if (isset($params['id'])) {
            $line = QuestionSet::getByQSetIdJoin($params['id']);
            $myq = ($line['ownerid'] == $userId);
            if ($isAdmin || ($isGrpAdmin && $line['groupid'] == $groupId) || ($line['userights'] == AppConstant::NUMERIC_THREE && $line['groupid'] == $groupId) || $line['userights'] > AppConstant::NUMERIC_THREE) {
                $myq = true;
            }
            $nameList = explode(", mb ", $line['author']);
            if ($myq && !in_array($myName, $nameList)) {
                $nameList[] = $myName;
            }
            if (isset($params['template'])) {
                $author = $myName;
                $myq = true;
            } else {
                $author = implode(", mb ", $nameList);
            }
            foreach ($line as $k => $v) {
                $line[$k] = str_replace('&', '&amp;', $v);
            }

            $inLibs = array();
            if ($line['extref'] != '') {
                $extRef = explode('~~', $line['extref']);
            } else {
                $extRef = array();
            }
            $images = array();
            $images['vars'] = array();
            $images['files'] = array();
            $images['alttext'] = array();
            if ($line['hasimg'] > AppConstant::NUMERIC_ZERO) {
                $query = QImages::getByQuestionSetId($params['id']);
                foreach ($query as $row) {
                    $images['vars'][$row['id']] = $row['var'];
                    $images['files'][$row['id']] = $row['filename'];
                    $images['alttext'][$row['id']] = $row['alttext'];
                }
            }
            if (isset($params['template'])) {
                $defLib = $user['deflib'];
                $useDefLib = $user['usedeflib'];

                if (isset($params['makelocal'])) {
                    $inLibs[] = $defLib;
                    $line['description'] .= " (local for $userFullName)";
                } else {
                    $line['description'] .= " (copy by $userFullName)";
                    if ($useDefLib == AppConstant::NUMERIC_ONE) {
                        $inLibs[] = $defLib;
                    } else {
                        $query = Libraries::getByQSetId($params['id']);
                        foreach ($query as $row) {
                            if ($row['userights'] == AppConstant::NUMERIC_EIGHT || ($row['groupid'] == $groupId && ($row['userights'] % AppConstant::NUMERIC_THREE == AppConstant::NUMERIC_TWO)) || $row['ownerid'] == $userId) {
                                $inLibs[] = $row['id'];
                            }
                        }
                    }
                }
                $lockLibs = array();
                $addMod = AppConstant::ADD;
                $line['userights'] = $user['qrightsdef'];

            } else {
                $query = LibraryItems::getDestinctLibIdByIdAndOwner($groupId, $params['id'], $userId, $isGrpAdmin, $isAdmin);
                foreach ($query as $row) {
                    $inLibs[] = $row['libid'];
                }

                $lockLibs = array();
                if (!$isAdmin) {
                    $query = LibraryItems::getLibIdByQidAndOwner($groupId, $params['id'], $userId, $isGrpAdmin, $isAdmin);
                    foreach ($query as $row) {
                        $lockLibs[] = $row['libid'];
                    }
                }
                $addMod = AppConstant::MODIFY;
                $inUseCount = Questions::getQidCount($userId, $params['id']);
            }

            if (count($inLibs) == AppConstant::NUMERIC_ZERO && count($lockLibs) == AppConstant::NUMERIC_ZERO) {
                $inLibs = array(AppConstant::NUMERIC_ZERO);
            }
            $inLibs = implode(",", $inLibs);
            $lockLibs = implode(",", $lockLibs);

            $twoBox = ($line['qcontrol'] == '' && $line['answer'] == '');

            $line['qtext'] = preg_replace('/<span class="AM">(.*?)<\/span>/', '$1', $line['qtext']);
        } else {
            $myq = true;
            $twoBox = true;
            $line['description'] = AppConstant::QUESTION_DESCRIPTION;
            $line['userights'] = $user['qrightsdef'];
            $line['license'] = isset($CFG['GEN']['deflicense']) ? $CFG['GEN']['deflicense'] : AppConstant::NUMERIC_ONE;
            $line['qtype'] = "number";
            $line['control'] = '';
            $line['qcontrol'] = '';
            $line['qtext'] = '';
            $line['answer'] = '';
            $line['solution'] = '';
            $line['solutionopts'] = AppConstant::NUMERIC_SIX;
            $line['hasimg'] = AppConstant::NUMERIC_ZERO;
            $line['deleted'] = AppConstant::NUMERIC_ZERO;
            $line['replaceby'] = AppConstant::NUMERIC_ZERO;
            if (isset($params['aid']) && isset($sessionData['lastsearchlibs' . $params['aid']])) {
                $inLibs = $sessionData['lastsearchlibs' . $params['aid']];
            } else if (isset($sessionData['lastsearchlibs' . $courseId])) {
                $inLibs = $sessionData['lastsearchlibs' . $courseId];
            } else {
                $inLibs = $userDefLib;
            }
            $lockLibs = '';
            $images = array();
            $extRef = array();
            $author = $myName;
            if (!isset($params['id']) || isset($params['template'])) {
                $query = Libraries::getByIdList(explode(',', $inLibs));
                foreach ($query as $row) {
                    if ($row['userights'] == AppConstant::NUMERIC_EIGHT || ($row['groupid'] == $groupId && ($row['userights'] % AppConstant::NUMERIC_THREE == AppConstant::NUMERIC_TWO)) || $row['ownerid'] == $userId) {
                        $okLibs[] = $row['id'];
                    }
                }
                if (count($okLibs) > AppConstant::NUMERIC_ZERO) {
                    $inLibs = implode(",", $okLibs);
                } else {
                    $inLibs = AppConstant::ZERO_VALUE;
                }
            }
            $addMod = AppConstant::ADD;
        }
        $lNames = array();
        if (substr($inLibs, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE) === AppConstant::ZERO_VALUE) {
            $lNames[] = AppConstant::UNASSIGNED;
        }
        $inLibsSafe = "'" . implode("','", explode(',', $inLibs)) . "'";
        $query = Libraries::getByIdList($inLibsSafe);
        foreach ($query as $row) {
            $lNames[] = $row['name'];
        }
        $lNames = implode(", ", $lNames);
        $this->includeJS(['general.js', 'question/modDataSet.js', 'editor/tiny_mce.js', 'ASCIIMathTeXImg_min.js']);
        $renderData = array('course' => $course, 'addMode' => $addMod, 'params' => $params, 'line' => $line, 'myq' => $myq,
            'frompot' => $fromPot, 'author' => $author, 'userId' => $userId, 'groupId' => $groupId, 'isAdmin' => $isAdmin, 'isGrpAdmin' => $isGrpAdmin,
            'inlibs' => $inLibs, 'locklibs' => $lockLibs, 'lnames' => $lNames, 'twobx' => $twoBox, 'images' => $images, 'extref' => $extRef, 'courseId' => $courseId,
            'myRights' => $myRights, 'sessionData' => $sessionData,'inUseCount'=> $inUseCount, 'errorMsg' => $errorMsg, 'outputMsg' => $outputMsg);
        return $this->renderWithData('modDataSet', $renderData);
    }

    public function stripSmartQuotes($text)
    {
        $text = str_replace(
            array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text);
        return $text;
    }

    public function getVideoId($url)
    {
        $videoId = '';
        if (strpos($url, 'youtube.com/watch') !== false) {
            $videoId = substr($url, strrpos($url, 'v=') + 2);
            if (strpos($videoId, '&') !== false) {
                $videoId = substr($videoId, AppConstant::NUMERIC_ZERO, strpos($videoId, '&'));
            }
            if (strpos($videoId, '#') !== false) {
                $videoId = substr($videoId, AppConstant::NUMERIC_ZERO, strpos($videoId, '#'));
            }
            $videoId = str_replace(array(" ", "\n", "\r", "\t"), '', $videoId);
        } else if (strpos($url, 'youtu.be/') !== false) {
            $videoId = substr($url, strpos($url, '.be/') + AppConstant::NUMERIC_FOUR);
            if (strpos($videoId, '#') !== false) {
                $videoId = substr($videoId, AppConstant::NUMERIC_ZERO, strpos($videoId, '#'));
            }
            if (strpos($videoId, '?') !== false) {
                $videoId = substr($videoId, AppConstant::NUMERIC_ZERO, strpos($videoId, '?'));
            }
            $videoId = str_replace(array(" ", "\n", "\r", "\t"), '', $videoId);
        }
        return $videoId;
    }

    public function actionModQuestion()
    {
        $user = $this->user;
        $this->layout = 'master';
        $userId = $user['id'];
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $teacherId = $this->isTeacher($userId, $courseId);
        /*
         * Check permissions and set flags
         */
        if (!($teacherId)) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {
            /*
             * Permissions are ok perform data manipulation
             */
            if ($params['process'] == true) {
                if (isset($params['usedef'])) {
                    $points = AppConstant::QUARTER_NINE;
                    $attempts = AppConstant::QUARTER_NINE;
                    $penalty = AppConstant::QUARTER_NINE_STRING;
                    $regen = AppConstant::NUMERIC_ZERO;
                    $showAns = AppConstant::NUMERIC_ZERO;
                    $rubric = AppConstant::NUMERIC_ZERO;
                    $showHints = AppConstant::NUMERIC_ZERO;
                    $params['copies'] = AppConstant::NUMERIC_ONE;
                } else {
                    if (trim($params['points']) == "") {
                        $points = AppConstant::QUARTER_NINE;
                    } else {
                        $points = intval($params['points']);
                    }
                    if (trim($params['attempts']) == "") {
                        $attempts = AppConstant::QUARTER_NINE;
                    } else {
                        $attempts = intval($params['attempts']);
                    }
                    if (trim($params['penalty']) == "") {
                        $penalty = AppConstant::QUARTER_NINE_STRING;
                    } else {
                        $penalty = intval($params['penalty']);
                    }
                    if ($penalty != AppConstant::QUARTER_NINE_STRING) {
                        if ($params['skippenalty'] == AppConstant::NUMERIC_TEN) {
                            $penalty = 'L' . $penalty;
                        } else if ($params['skippenalty'] > AppConstant::NUMERIC_ZERO) {
                            $penalty = 'S' . $params['skippenalty'] . $penalty;
                        }
                    }
                    $regen = $params['regen'] + AppConstant::NUMERIC_THREE * $params['allowregen'];
                    $showAns = $params['showans'];
                    $rubric = intval($params['rubric']);
                    $showHints = intval($params['showhints']);
                }
                $questionArray = array();
                $questionArray['points'] = $points;
                $questionArray['attempts'] = $attempts;
                $questionArray['penalty'] = $penalty;
                $questionArray['regen'] = $regen;
                /*
                 * Default value is stored for showans field.
                 */
                $questionArray['rubric'] = $rubric;
                $questionArray['showhints'] = $showHints;
                $questionArray['assessmentid'] = $assessmentId;
                /*
                 * already have id - updating
                 */
                if (isset($params['id'])) {
                    if (isset($params['replacementid']) && $params['replacementid'] != '' && intval($params['replacementid']) != AppConstant::NUMERIC_ZERO) {
                        $questionArray['questionsetid'] = intval($params['replacementid']);
                    }
                    Questions::updateQuestionFields($questionArray, $params['id']);
                    if (isset($params['copies']) && $params['copies'] > AppConstant::NUMERIC_ZERO) {
                        $query = Questions::getById($params['id']);
                        $params['qsetid'] = $query['questionsetid'];
                    }
                }
                /*
                 * new - adding
                 */
                if (isset($params['qsetid'])) {
                    $query = Assessments::getByAssessmentId($assessmentId);
                    $itemOrder = $query['itemorder'];
                    $questionArray['questionsetid'] = $params['qsetid'];
                    for ($i = AppConstant::NUMERIC_ZERO; $i < $params['copies']; $i++) {
                        $question = new Questions();
                        $qid = $question->addQuestions($questionArray);
                        /*
                         * add to itemorder
                         */
                        if (isset($params['id'])) { //am adding copies of existing
                            $itemArray = explode(',', $itemOrder);
                            $key = array_search($params['id'], $itemArray);
                            array_splice($itemArray, $key + AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_ZERO, $qid);
                            $itemOrder = implode(',', $itemArray);
                        } else {
                            if ($itemOrder == '') {
                                $itemOrder = $qid;
                            } else {
                                $itemOrder = $itemOrder . ",$qid";
                            }
                        }
                    }
                    Assessments::UpdateItemOrder($itemOrder, $assessmentId);
                }
                return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
            } else {
                /*
                 * Default data manipulation
                 */
                if (isset($params['id'])) {
                    $line = Questions::getById($params['id']);
                    if ($line['penalty']{AppConstant::NUMERIC_ZERO} === 'L') {
                        $line['penalty'] = substr($line['penalty'], AppConstant::NUMERIC_ONE);
                        $skipPenalty = AppConstant::NUMERIC_TEN;
                    } else if ($line['penalty']{AppConstant::NUMERIC_ZERO} === 'S') {
                        $skipPenalty = $line['penalty']{AppConstant::NUMERIC_ONE};
                        $line['penalty'] = substr($line['penalty'], AppConstant::NUMERIC_TWO);
                    } else {
                        $skipPenalty = AppConstant::NUMERIC_ZERO;
                    }

                    if ($line['points'] == AppConstant::QUARTER_NINE) {
                        $line['points'] = '';
                    }
                    if ($line['attempts'] == AppConstant::QUARTER_NINE) {
                        $line['attempts'] = '';
                    }
                    if ($line['penalty'] == AppConstant::QUARTER_NINE_STRING) {
                        $line['penalty'] = '';
                    }
                } else {
                    /*
                     * Set defaults
                     */
                    $line['points'] = "";
                    $line['attempts'] = "";
                    $line['penalty'] = "";
                    $skipPenalty = AppConstant::NUMERIC_ZERO;
                    $line['regen'] = AppConstant::NUMERIC_ZERO;
                    $line['showans'] = AppConstant::ZERO_VALUE;
                    $line['rubric'] = AppConstant::NUMERIC_ZERO;
                    $line['showhints'] = AppConstant::NUMERIC_ZERO;
                }

                $rubricValues = array(AppConstant::NUMERIC_ZERO);
                $rubricNames = array('None');
                $query = Rubrics::getIdAndName($userId, $user['groupid']);
                foreach ($query as $row) {
                    $rubricValues[] = $row['id'];
                    $rubricNames[] = $row['name'];
                }
                $query = AssessmentSession::getAssessmentIDs($assessmentId, $courseId);
                if (count($query) > AppConstant::NUMERIC_ZERO) {
                    $pageBeenTakenMsg = AppConstant::PAGE_MSG1;
                    $pageBeenTakenMsg .= AppConstant::PAGE_MSG2;
                    $pageBeenTakenMsg .= AppConstant::PAGE_MSG3;
                    $pageBeenTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='add-questions?cid=$courseId&aid=$assessmentId&clearattempts=ask'\"></p>\n";
                    $beenTaken = true;
                } else {
                    $beenTaken = false;
                }
            }
        }
        $renderData = array('course' => $course, 'overwriteBody' => $overwriteBody, 'body' => $body, 'pageBeenTakenMsg' => $pageBeenTakenMsg,
            'courseId' => $courseId, 'assessmentId' => $assessmentId, 'beentaken' => $beenTaken, 'params' => $params, 'skippenalty' => $skipPenalty,
            'line' => $line, 'rubricNames' => $rubricNames, 'rubricVals' => $rubricValues);
        return $this->renderWithData('modQuestion', $renderData);
    }

    public function actionTestQuestion()
    {
        $user = $this->user;
        $userId = $user['id'];
        $myRights = $user['rights'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $asid = AppConstant::NUMERIC_ZERO;
        $teacherId = $this->isTeacher($userId, $courseId);
        /*
         * Check permissions and set flags
         */
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {
            /*
             * Permissions are ok perform data manipulation
             */
            $useeditor = AppConstant::NUMERIC_ONE;
            if (isset($params['seed'])) {
                $seed = $params['seed'];
                $attempt = AppConstant::NUMERIC_ZERO;
            } else if (!isset($params['seed']) || isset($params['regen'])) {
                $seed = rand(AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_TEN_THOUSAND);
                $attempt = AppConstant::NUMERIC_ZERO;
            } else {
                $seed = $params['seed'];
                $attempt = $params['attempt'] + AppConstant::NUMERIC_ONE;
            }
            if (isset($params['onlychk']) && $params['onlychk'] == AppConstant::NUMERIC_ONE) {
                $onlyCheck = AppConstant::NUMERIC_ONE;
            } else {
                $onlyCheck = AppConstant::NUMERIC_ZERO;
            }
            if (isset($params['formn']) && isset($params['loc'])) {
                $formN = $params['formn'];
                $loc = $params['loc'];
                if (isset($params['checked']) || isset($params['usecheck'])) {
                    $chk = "&checked=0";
                } else {
                    $chk = '';
                }
                if ($onlyCheck == AppConstant::NUMERIC_ONE) {
                    $pageOnlyChkMsg = "var prevnext = window.opener.getnextprev('$formN','{$params['loc']}',true);";
                } else {
                    $pageOnlyChkMsg = "var prevnext = window.opener.getnextprev('$formN','{$params['loc']}');";
                }
            }
            $lastAnswers = array('');

            if (isset($params['seed'])) {
                list($score, $rawScores) = scoreq(AppConstant::NUMERIC_ZERO, $params['qsetid'], $params['seed'], $params['qn0']);
                $scores[0] = $score;
                $lastAnswers[0] = stripslashes($lastAnswers[0]);
                $pageScoreMsg = "<p>Score on last answer: $score/1</p>\n";
            } else {
                $pageScoreMsg = "";
                $scores = array(AppConstant::NUMERIC_NEGATIVE_ONE);
                $_SESSION['choicemap'] = array();
            }

            $pageFormAction = "test-question?cid={$params['cid']}&qsetid={$params['qsetid']}";
            if (isset($params['usecheck'])) {
                $pageFormAction .= "&checked=" . $params['usecheck'];
            } else if (isset($params['checked'])) {
                $pageFormAction .= "&checked=" . $params['checked'];
            }
            if (isset($params['formn'])) {
                $pageFormAction .= "&formn=" . $params['formn'];
                $pageFormAction .= "&loc=" . $params['loc'];
            }
            if (isset($params['onlychk'])) {
                $pageFormAction .= "&onlychk=" . $params['onlychk'];
            }

            $line = QuestionSet::getUserAndQuestionSetJoin($params['qsetid']);

            $lastMod = date("m/d/y g:i a", $line['lastmoddate']);

            if (isset($CFG['AMS']['showtips'])) {
                $showTips = $CFG['AMS']['showtips'];
            } else {
                $showTips = AppConstant::NUMERIC_ONE;
            }
            if (isset($CFG['AMS']['eqnhelper'])) {
                $eqnHelper = $CFG['AMS']['eqnhelper'];
            } else {
                $eqnHelper = AppConstant::NUMERIC_ZERO;
            }
            $resultLibNames = Libraries::getUserAndLibrary($params['qsetid']);
        }
        $this->includeCSS(['mathquill.css', 'question/question.css', 'course/course.css', 'roster/roster.css','mathtest.css']);
        $this->includeJS(['eqntips.js', 'eqnhelper.js', 'mathquill_min.js', 'mathquilled.js', 'AMtoMQ.js', 'tablesorter.js',
            'question/addquestions.js', 'general.js', 'question/junkflag.js', 'AMhelpers_min.js', 'confirmsubmit.js','editor/tiny_mce.js']);
        $responseArray = array('course' => $course, 'params' => $params, 'overwriteBody' => $overwriteBody, 'body' => $body, 'showtips' => $showTips,
            'eqnhelper' => $eqnHelper, 'page_onlyChkMsg' => $pageOnlyChkMsg, 'chk' => $chk, 'formn' => $formN, 'onlychk' => $onlyCheck, 'page_scoreMsg' => $pageScoreMsg,
            'page_formAction' => $pageFormAction, 'seed' => $seed, 'attempt' => $attempt, 'rawscores' => $rawScores, 'line' => $line, 'lastmod' => $lastMod,
            'resultLibNames' => $resultLibNames, 'myRights' => $myRights, 'params' => $params);
        return $this->renderWithData('testQuestion', $responseArray);
    }

    public function actionAddQuestionsSave()
    {
        $user = $this->user;
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $assessmentId = $params['aid'];
        $teacherId = $this->isTeacher($user['id'], $courseId);
        if (!($teacherId)) {
            $this->setErrorFlash(AppConstant::VALIDATION_MSG);
            return $this->redirect('add-questions-save');//need to check
        }
        $query = Assessments::getByAssessmentId($assessmentId);
        $rawItemOrder = $query['itemorder'];
        $vidData = $query['viddata'];
        $itemOrder = str_replace('~', ',', $rawItemOrder);
        $curItems = array();
        foreach (explode(',', $itemOrder) as $qid) {
            if (strpos($qid, '|') === false) {
                $curItems[] = $qid;
            }
        }

        $submitted = $params['order'];
        $submitted = str_replace('~', ',', $submitted);
        $newItems = array();
        foreach (explode(',', $submitted) as $qid) {
            if (strpos($qid, '|') === false) {
                $newItems[] = $qid;
            }
        }
        $toRemove = array_diff($curItems, $newItems);
        if ($vidData != '') {
            $vidData = unserialize($vidData);
            $queOrder = explode(',', $rawItemOrder);
            $qIdByNum = array();
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($queOrder); $i++) {
                if (strpos($queOrder[$i], '~') !== false) {
                    $qids = explode('~', $queOrder[$i]);
                    if (strpos($qids[0], '|') !== false) {
                        $qIdByNum[$i] = $qids[1];
                    } else {
                        $qIdByNum[$i] = $qids[0];
                    }
                } else {
                    $qIdByNum[$i] = $queOrder[$i];
                }
            }

            $queOrder = explode(',', $params['order']);
            $newByNum = array();
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($queOrder); $i++) {
                if (strpos($queOrder[$i], '~') !== false) {
                    $qids = explode('~', $queOrder[$i]);
                    if (strpos($qids[0], '|') !== false) { //pop off nCr
                        $newByNum[$i] = $qids[1];
                    } else {
                        $newByNum[$i] = $qids[0];
                    }
                } else {
                    $newByNum[$i] = $queOrder[$i];
                }
            }

            $qidByNumFlip = array_flip($qIdByNum);

            $newVidData = array();
            $newVidData[0] = $vidData[0];
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($newByNum); $i++) {   //for each new item
                $oldNum = $qidByNumFlip[$newByNum[$i]];
                $found = false;
                /*
                 * look for old item in vidData
                 */
                for ($j = AppConstant::NUMERIC_ONE; $j < count($vidData); $j++) {
                    if (isset($vidData[$j][2]) && $vidData[$j][2] == $oldNum) {
                        /*
                         * if found, copy data, and any non-question data following
                         */
                        $new = $vidData[$j];
                        $new[2] = $i;
                        /*
                         * update question number;
                         */
                        $newVidData[] = $new;
                        $j++;
                        while (isset($vidData[$j]) && !isset($vidData[$j][2])) {
                            $newVidData[] = $vidData[$j];
                            $j++;
                        }
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    /*item was not found in vidData.  it should have been.
                     *count happen if the first item in a group was removed, perhaps
                     *Add a blank item
                     */
                    $newVidData[] = array('', '', $i);
                }
            }
            /*
             *any old items will not get copied.
             */
            $vidData =  serialize($newVidData);
        }
        /*
         * delete any removed questions
         */
        Questions::deleteById($toRemove);
        /*
         * store new itemOrder
         */
        $query = Assessments::setVidData($params['order'], $vidData, $assessmentId);

        $responseArray = array('query' => $query);
        return $this->renderWithData('addQuestionsSave', $responseArray);
    }

    public function actionSaveLibAssignFlag()
    {
        $params = $this->getRequestParams();
        $user = $this->user;
        $myRights = $user['rights'];
        if (!isset($params['libitemid']) || $myRights < AppConstant::TEACHER_RIGHT) {
            exit;
        }
        $isChanged = false;
        $query = LibraryItems::UpdateJunkFlag($params['libitemid'], $params['flag']);
        if ($query > AppConstant::NUMERIC_ZERO) {
            $isChanged = true;
        }

        $responseArray = array('isChanged' => $isChanged);
        return $this->renderWithData('saveLibAssignFlag', $responseArray);
    }

    public function actionPrintLayout()
    {
        $user = $this->user;
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
//        $this->layout = 'master';
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($user['id'], $courseId);
        /*
         * set some page specific variables and counters
         */
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        if (!($teacherId)) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {
            /*
             * Permissions are ok perform data manipulation
             */
            $assessmentId = $this->getParamVal('aid');
            if (isset($params['vert'])) {
                $ph = AppConstant::NUMERIC_ELEVEN - $params['vert'];
                $pw = AppConstant::EIGHT_POINT_FIVE - $params['horiz'];
                if ($params['browser'] == AppConstant::NUMERIC_ONE) {
                    $ph -= AppConstant::POINT_FIVE;
                    $pw -= AppConstant::POINT_FIVE;
                }
            } else if (isset ($params['pw'])) {
                $ph = $params['ph'];
                $pw = $params['pw'];
            }
            $isFinal = isset($params['final']);
            $line = Assessments::getByAssessmentId($assessmentId);
            $ioQuestions = explode(",", $line['itemorder']);
            $questions = array();
            foreach ($ioQuestions as $k => $q) {
                if (strpos($q, '~') !== false) {
                    $sub = explode('~', $q);
                    if (strpos($sub[0], '|') === false) { //backwards compatible
                        $questions[] = $sub[array_rand($sub, AppConstant::NUMERIC_ONE)];
                    } else {
                        $grpQs = array();
                        $grpParts = explode('|', $sub[0]);
                        array_shift($sub);
                        if ($grpParts[1] == AppConstant::NUMERIC_ONE) { // With replacement
                            for ($i = AppConstant::NUMERIC_ZERO; $i < $grpParts[0]; $i++) {
                                $questions[] = $sub[array_rand($sub, AppConstant::NUMERIC_ONE)];
                            }
                        } else if ($grpParts[1] == AppConstant::NUMERIC_ZERO) { //Without replacement
                            shuffle($sub);
                            for ($i = AppConstant::NUMERIC_ZERO; $i < min($grpParts[0], count($sub)); $i++) {
                                $questions[] = $sub[$i];
                            }
                            if ($grpParts[0] > count($sub)) { //fix stupid inputs
                                for ($i = count($sub); $i < $grpParts[0]; $i++) {
                                    $questions[] = $sub[array_rand($sub, AppConstant::NUMERIC_ONE)];
                                }
                            }
                        }
                    }
                } else {
                    $questions[] = $q;
                }
            }
            $points = array();
            $qn = array();
            $query = Questions::getByIdList($questions);
            foreach ($query as $row) {
                if ($row['points'] == AppConstant::QUARTER_NINE) {
                    $points[$row['id']] = $line['defpoints'];
                } else {
                    $points[$row['id']] = $row['points'];
                }
                $qn[$row['id']] = $row['questionsetid'];
            }
            $numQuestion = count($questions);
            $phs = $ph - AppConstant::POINT_SIX;
            $pws = $pw - AppConstant::POINT_FIVE;
            $pwss = $pw - AppConstant::POINT_SIX;

        }
        $this->includeCSS(['mathtest.css', 'print.css']);
        $this->includeJS(['AMhelpers.js']);
        $renderData = array('course' => $course, 'overwriteBody' => $overwriteBody, 'body' => $body, 'courseId' => $courseId,
            'params' => $params,'isfinal' => $isFinal,'points' => $points,'qn' => $qn,'ph' => $ph,
            'assessmentId' => $assessmentId, 'line' => $line, 'user' => $user, 'pwss' => $pwss, 'pws' => $pws, 'phs' => $phs, 'numq' => $numQuestion, 'questions' => $questions);
        return $this->renderWithData('printLayout', $renderData);
    }

    public function actionPrintLayoutBare()
    {
        $user = $this->user;
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $assessmentId = $params['aid'];
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        $course = Course::getById($courseId);
        $this->layout = 'master';
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        if (!($teacherId)) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        }
        if (isset($params['versions'])) {
            $this->includeCSS(['print.css']);
        }
        $noLogo = true;
        if (isset($params['mathdisp']) && $params['mathdisp'] == 'text') {
            $sessionData['mathdisp'] = AppConstant::NUMERIC_ZERO;
        } else {
            $sessionData['mathdisp'] = AppConstant::NUMERIC_TWO;
        }
        if (isset($params['mathdisp']) && $params['mathdisp'] == 'tex') {
            $sessionData['texdisp'] = true;
        }
        if (isset($params['mathdisp']) && $params['mathdisp'] == 'textandimg') {
            $printTwice = AppConstant::NUMERIC_TWO;
        } else {
            $printTwice = AppConstant::NUMERIC_ONE;
        }

        $sessionData['graphdisp'] = AppConstant::NUMERIC_TWO;
        if (!isset($params['versions'])) {

        } else {
            $line = Assessments::getByAssessmentId($assessmentId);
            $ioQuestions = explode(",", $line['itemorder']);
            $aname = $line['name'];
            $questions = array();
            foreach ($ioQuestions as $k => $q) {
                if (strpos($q, '~') !== false) {
                    $sub = explode('~', $q);
                    if (strpos($sub[0], '|') === false) { //backwards compat
                        $questions[] = $sub[array_rand($sub, 1)];
                    } else {
                        $grpqs = array();
                        $grpParts = explode('|', $sub[0]);
                        array_shift($sub);
                        if ($grpParts[1] == AppConstant::NUMERIC_ONE) { // With replacement
                            for ($i = AppConstant::NUMERIC_ZERO; $i < $grpParts[0]; $i++) {
                                $questions[] = $sub[array_rand($sub, 1)];
                            }
                        } else if ($grpParts[1] == AppConstant::NUMERIC_ZERO) { //Without replacement
                            shuffle($sub);
                            for ($i = AppConstant::NUMERIC_ZERO; $i < min($grpParts[0], count($sub)); $i++) {
                                $questions[] = $sub[$i];
                            }
                            if ($grpParts[0] > count($sub)) { //fix stupid inputs
                                for ($i = count($sub); $i < $grpParts[0]; $i++) {
                                    $questions[] = $sub[array_rand($sub, AppConstant::NUMERIC_ONE)];
                                }
                            }
                        }
                    }
                } else {
                    $questions[] = $q;
                }
            }
            $points = array();
            $qn = array();
            $query = Questions::getPointsAndQsetId($questions);
            foreach ($query as $row) {
                if ($row['points'] == AppConstant::QUARTER_NINE) {
                    $points[$row['id']] = $line['defpoints'];
                } else {
                    $points[$row['id']] = $row['points'];
                }
                $qn[$row['id']] = $row['questionsetid'];
            }
            if (is_numeric($params['versions'])) {
                $copies = $params['versions'];
            } else {
                $copies = AppConstant::NUMERIC_ONE;
            }
            $seeds = array();
            global $shuffle;
            for ($j = AppConstant::NUMERIC_ZERO; $j < $copies; $j++) {
                $seeds[$j] = array();
                if ($line['shuffle'] & 2) {  //all questions same random seed
                    if ($shuffle & 4) { //all students same seed
                        $seeds[$j] = array_fill(AppConstant::NUMERIC_ZERO, count($questions), $assessmentId + $j);
                    } else {
                        $seeds[$j] = array_fill(AppConstant::NUMERIC_ZERO, count($questions), rand(AppConstant::NUMERIC_ONE, AppConstant::QUARTER_NINE));
                    }
                } else {
                    if ($shuffle & 4) { //all students same seed
                        for ($i = AppConstant::NUMERIC_ZERO; $i < count($questions); $i++) {
                            $seeds[$j][] = $assessmentId + $i + $j;
                        }
                    } else {
                        for ($i = AppConstant::NUMERIC_ZERO; $i < count($questions); $i++) {
                            $seeds[$j][] = rand(AppConstant::NUMERIC_ONE, AppConstant::QUARTER_NINE);
                        }
                    }
                }
            }
            $numQuestion = count($questions);
        }
        $this->includeCSS(['default.css', 'handheld.css', 'print.css']);
        $renderData = array('sessionData' => $sessionData, 'overwriteBody' => $overwriteBody, 'body' => $body, 'nologo' => $noLogo, 'numq' => $numQuestion,
            'printTwice' => $printTwice, 'course' => $course, 'assessmentId' => $assessmentId, 'params' => $params, 'copies' => $copies, 'line' => $line,
            'qn' => $qn, 'courseId' => $courseId, 'questions' => $questions, 'points' => $points, 'seeds' => $seeds);
        return $this->renderWithData('printLayoutBare', $renderData);
    }

    public function actionManageQuestionSet()
    {
        $user = $this->user;
        $remove = $this->getParamVal('remove');
        $transfer = $this->getParamVal('transfer');
        $params = $this->getRequestParams();
        $cid = $params['cid'];
        $userId = $user['id'];
        $myRights = $user['rights'];
        $groupId = $user['groupid'];
        $userDefLib = $user['deflib'];
        $this->layout = 'master';
        $tempLibArray = array();
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $bodyMessage = AppConstant::NO_QUESTION_SELECTED . "  <a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Go back</a>\n";
        $curBreadcrumb = "";
        $pageTitle = AppConstant::MANAGE_QUE_SET_TITLE1;
        $helpIcon = "";
        $isAdmin = false;
        $isGrpAdmin = false;
        /*
         * Check permissions and set flags
         */
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } elseif (isset($params['cid']) && $params['cid'] == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::REQUIRED_ADMIN_ACCESS;
        } elseif (!(isset($params['cid'])) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::ACCESS_THROUGH_MENU;
        } else {
            /*
             * Permissions are ok perform data manipulation
             */
            if ($cid == 'admin') {
                if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT && $myRights < AppConstant::ADMIN_RIGHT) {
                    $isGrpAdmin = true;
                } else if ($myRights == AppConstant::ADMIN_RIGHT) {
                    $isAdmin = true;
                }
            }
            if (isset($remove)) {//get remove
                if (isset($params['confirmed'])) {
                    if ($isGrpAdmin) {
                        $query = QuestionSet::getQidByQSetIdAndGroupId($remove, $groupId);
                        if (count($query) > AppConstant::NUMERIC_ZERO) {
                            QuestionSet::setDeletedById($remove);
                        } else {
                            return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                        }
                    } else {
                        if (!$isAdmin) {
                            QuestionSet::setDeletedByIdAndOwnerId($remove, $userId);
                        } else {
                            QuestionSet::setDeletedById($remove);
                        }
                    }
                    if (count($link) > AppConstant::NUMERIC_ZERO) {
                        LibraryItems::deleteByQsetId($remove);
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                }
            } else if (isset($transfer)) {//get remove
                if (isset($params['newowner'])) {

                    if ($isGrpAdmin) {
                        $query = QuestionSet::getQidByQSetIdAndGroupId($transfer, $groupId);
                        if (count($query) > AppConstant::NUMERIC_ZERO) {
                            QuestionSet::setOwnerIdById($transfer, $params['newowner']);
                        }
                    } else {
                        if (!$isAdmin) {
                            QuestionSet::setOwnerIdByIdAndOwnerId($transfer, $userId, $params['newowner']);
                        } else {
                            QuestionSet::setOwnerIdById($transfer, $params['newowner']);
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pageTitle = AppConstant::MANAGE_QUE_SET_TITLE2;
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    $query = User::getUserGreaterThenTeacherRights();
                    $i = AppConstant::NUMERIC_ZERO;
                    $pageTransferUserList = array();
                    foreach ($query as $row) {
                        $pageTransferUserList['val'][$i] = $row['id'];
                        $pageTransferUserList['label'][$i] = $row['LastName'] . ", " . $row['FirstName'];
                        $i++;
                    }
                }
            } else if (isset($params['chglib'])) {
                if (isset($params['qtochg'])) {
                    if ($params['chglib'] != '') {
                        $newLibs = $params['libs']; //array is sanitized later
                        if ($params['libs'] == '') {
                            $newLibs = array();
                        } else {
                            if ($newLibs[0] == AppConstant::NUMERIC_ZERO && count($newLibs) > AppConstant::NUMERIC_ONE) { //get rid of unassigned if checked and others are checked
                                array_shift($newLibs);
                            }
                        }
                        $libArray = explode(',', $params['qtochg']); //qsetids to change
                        if ($params['qtochg'] == '') {
                            $libArray = array();
                        }
                        $allLibs = array();
                        $query = LibraryItems::getByList($libArray);
                        foreach ($query as $row) {
                            $allLibs[$row['qsetid']][] = $row['libid'];
                        }
                        if ($isGrpAdmin) {
                            $query = LibraryItems::getByLibAndUserTable($groupId, $libArray);
                        } else {
                            $query = LibraryItems::getByListAndOwnerId($isAdmin, $libArray, $userId);
                        }
                        $myLibs = array();
                        foreach ($query as $row) {
                            $myLibs[$row['qsetid']][] = $row['libid'];
                        }
                        if ($params['action'] == AppConstant::NUMERIC_ZERO) {//add, keep existing
                            /*
                             * get list of existing library assignments, remove any additions that already exist and add to new libraries
                             */
                            foreach ($libArray as $qSetId) {
                                /*
                                 * for each question determine which checked libraries it's not already in, and add them
                                 */
                                $toAdd = array_values(array_diff($newLibs, $allLibs[$qSetId]));
                                foreach ($toAdd as $libId) {
                                    if ($libId == AppConstant::NUMERIC_ZERO) {
                                        continue;
                                    }
                                    /*
                                     * no need to add to unassigned using "keep existing"
                                     */
                                    $tempLibArray['libid'] = $libId;
                                    $tempLibArray['qsetid'] = $qSetId;
                                    $tempLibArray['userid'] = $userId;
                                    $library = new LibraryItems();
                                    $library->createLibraryItems($tempLibArray);
                                }
                                if (count($toAdd) > AppConstant::NUMERIC_ONE || (count($toAdd) > AppConstant::NUMERIC_ZERO && $toAdd[0] != AppConstant::NUMERIC_ZERO)) {
                                    LibraryItems::deleteLibraryItems(AppConstant::NUMERIC_ZERO, $qSetId);
                                }
                            }
                        } else if ($params['action'] == AppConstant::NUMERIC_ONE) { //add, remove existing
                            /*
                             *get list of existing library assignments, rework existing to new libs, remove any excess existing and add to any new
                             */
                            foreach ($libArray as $qSetId) {
                                /*
                                 * for each question determine which checked libraries it's not already in, and add them
                                 */
                                $toAdd = array_diff($newLibs, $allLibs[$qSetId]);
                                foreach ($toAdd as $libId) {
                                    $tempLibArray['libid'] = $libId;
                                    $tempLibArray['qsetid'] = $qSetId;
                                    $tempLibArray['userid'] = $userId;
                                    $library = new LibraryItems();
                                    $library->createLibraryItems($tempLibArray);
                                }
                                /*
                                 * determine which libraries to remove from; my lib assignments - newlibs
                                 */
                                if (isset($myLibs[$qSetId])) {
                                    $toRemove = array_diff($myLibs[$qSetId], $newLibs);
                                    foreach ($toRemove as $libId) {
                                        LibraryItems::deleteLibraryItems($libId, $qSetId);
                                    }
                                    /*
                                     * check for unassigneds
                                     */
                                    $query = LibraryItems::getIdByQid($qSetId);
                                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                                        $tempLibArray['libid'] = AppConstant::NUMERIC_ZERO;
                                        $tempLibArray['qsetid'] = $qSetId;
                                        $tempLibArray['userid'] = $userId;
                                        $library = new LibraryItems();
                                        $library->createLibraryItems($tempLibArray);
                                    }
                                }
                            }
                        } else if ($params['action'] == AppConstant::NUMERIC_TWO) { //remove
                            /*
                             * get list of exisiting assignments, if not in checked list, remove
                             */
                            foreach ($libArray as $qSetId) {
                                /*
                                 * for each question determine which libraries to remove from; my lib assignments - newlibs
                                 */
                                if (isset($myLibs[$qSetId])) {
                                    $toRemove = array_diff($myLibs[$qSetId], $newLibs);
                                    foreach ($toRemove as $libId) {
                                        LibraryItems::deleteLibraryItems($libId, $qSetId);
                                    }
                                    /*
                                     * check for unassigneds
                                     */
                                    $query = LibraryItems::getIdByQid($qSetId);
                                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                                        $tempLibArray['libid'] = AppConstant::NUMERIC_ZERO;
                                        $tempLibArray['qsetid'] = $qSetId;
                                        $tempLibArray['userid'] = $userId;
                                         $library = new LibraryItems();
                                        $library->createLibraryItems($tempLibArray);
                                    }
                                }
                            }
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pageTitle = AppConstant::MANAGE_QUE_SET_TITLE3;
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    } else {
                        $query = LibraryItems::getDistinctLibId($params['nchecked']);
                        foreach ($query as $row) {
                            $checked[] = $row['libid'];
                        }
                        $params['selectrights'] = AppConstant::NUMERIC_ONE;;
                    }
                }
            } else if (isset($params['template'])) {
                if (isset($params['qtochg'])) {
                    if (!isset($params['libs'])) {
                        $this->setErrorFlash('No Library Selected');
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                    $lib = $params['libs'];
                    $qToChg = explode(',', $params['qtochg']);
                    $now = time();
                    $myName = $user['lastName'] . ',' . $user['firstName'];
                    $userFullName = $user['firstName'] . ',' . $user['lastName'];
                    foreach ($qToChg as $k => $qid) {
                        $row = QuestionSet::getSelectedDataByQuesSetId($qid);
                        $lastAuthor = array_pop($row);
                        $ancestors = $row['ancestors'];
                        $ancestorAuthors = $row['ancestorauthors'];
                        if ($ancestors != '') {
                            $ancestors = $qid . ',' . $ancestors;
                        } else {
                            $ancestors = $qid;
                        }
                        if ($ancestorAuthors != '') {
                            $ancestorAuthors = $lastAuthor . '; ' . $ancestorAuthors;
                        } else {
                            $ancestorAuthors = $lastAuthor;
                        }
                        $row['ancestors'] = $ancestors;
                        $row['ancestorauthors'] = $ancestorAuthors;
                        $row['description'] .= " (copy by $userFullName)";
                        $mt = microtime();
                        $uQid = substr($mt, AppConstant::NUMERIC_ELEVEN) . substr($mt, AppConstant::NUMERIC_TWO, AppConstant::NUMERIC_THREE) . $k;
                        $tempQuestionArray = array();
                        $tempQuestionArray['uniqueid'] = $uQid;
                        $tempQuestionArray['adddate'] = $now;
                        $tempQuestionArray['lastmoddate'] = $now;
                        $tempQuestionArray['ownerid'] = $userId;
                        $tempQuestionArray['author'] = $myName;
                        $tempQuestionArray['description'] = $row['description'];
                        $tempQuestionArray['userights'] = $row['userights'];
                        $tempQuestionArray['qtype'] = $row['qtype'];
                        $tempQuestionArray['control'] = $row['control'];
                        $tempQuestionArray['qcontrol'] = $row['qcontrol'];
                        $tempQuestionArray['qtext'] = $row['qtext'];
                        $tempQuestionArray['answer'] = $row['answer'];
                        $tempQuestionArray['hasimg'] = $row['hasimg'];
                        $tempQuestionArray['ancestors'] = $row['ancestors'];
                        $tempQuestionArray['ancestorauthors'] = $row['ancestorauthors'];
                        $tempQuestionArray['license'] = $row['license'];
                        $question = new QuestionSet();
                        $nQid = $question->createQuestionSet($tempQuestionArray);
                        $tempLibArray['libid'] = $lib;
                        $tempLibArray['qsetid'] = $nQid;
                        $tempLibArray['userid'] = $userId;
                        $library = new LibraryItems();
                        $library->createLibraryItems($tempLibArray);
                        $qImageData = QImages::getByQuestionSetId($qid);
                        $QImgArray = array();
                        $QImgArray['var'] = $row['var'];
                        $QImgArray['filename'] = $row['filename'];
                        $QImgArray['alttext'] = $row['alttext'];
                        foreach ($qImageData as $row) {
                            $QImage = new QImages();
                            $QImage->createQImages($nQid, $QImgArray);
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pageTitle = AppConstant::MANAGE_QUE_SET_TITLE4;
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if (is_array($params['nchecked'])) {
                        $cList = implode(",", $params['nchecked']);
                    } else {
                        $cList = $params['nchecked'];
                    }
                    $selectType = "radio";
                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                }
            } else if (isset($params['license'])) {
                if (isset($params['qtochg'])) {
                    $qToChg = explode(',', $params['qtochg']);
                    foreach ($qToChg as $k => $qid) {
                        $qToChg[$k] = intval($qid);
                    }
                    if ($params['sellicense'] != AppConstant::NUMERIC_NEGATIVE_ONE) {
                        $selLicense = intval($params['sellicense']);
                        if (!$isAdmin) {
                            QuestionSet::setLicenseByUserId($selLicense, $qToChg, $userId);
                        } else {
                            QuestionSet::setLicense($selLicense, $qToChg);
                        }
                    }
                    if ($params['otherattribtype'] != AppConstant::NUMERIC_NEGATIVE_ONE) {
                        if ($params['otherattribtype'] == AppConstant::NUMERIC_ZERO) {
                            if (!$isAdmin) {
                                QuestionSet::setOtherAttributionByUserId($params['addattr'], $qToChg, $userId);
                            } else {
                                QuestionSet::setOtherAttribution($params['addattr'], $qToChg);
                            }
                        } else {
                            if (!$isAdmin) {
                                $query = QuestionSet::getIdByQidAndOwnerId($qToChg, $userId);
                            } else {
                                $query = QuestionSet::getByIdUsingInClause($qToChg);
                            }
                            foreach ($query as $row) {
                                $attr =  $row['otherattribution'] . $params['addattr'];
                                QuestionSet::setOtherAttributionById($attr, $row['id']);
                            }
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pageTitle = AppConstant::MANAGE_QUE_SET_TITLE5;
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if (is_array($params['nchecked'])) {
                        $cList = implode(",", $params['nchecked']);
                    } else {
                        $cList = $params['nchecked'];
                    }
                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                }
            } else if (isset($params['chgrights'])) {
                if (isset($params['qtochg'])) {
                    if ($isGrpAdmin) {
                        $query = QuestionSet::getByQSetIdAndGroupId(explode(',', $params['qtochg']), $groupId);
                        $toChg = array();
                        foreach ($query as $row) {
                            $toChg[] = $row['id'];
                        }
                        if (count($toChg) > AppConstant::NUMERIC_ZERO) {
                            $chgList = implode(',', $toChg);
                            QuestionSet::setUserRightsByList($toChg, $params['newrights']);
                        }
                    } else {
                        $chgList = "'" . implode("','", explode(',', $params['qtochg'])) . "'";
                        if (!$isAdmin) {
                            QuestionSet::setUserRightsByListAndUserId(explode(',', $params['qtochg']), $params['newrights'], $userId);
                        } else {
                            QuestionSet::setUserRightsByList(explode(',', $params['qtochg']), $params['newrights']);
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pageTitle = AppConstant::MANAGE_QUE_SET_TITLE6;
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if (is_array($params['nchecked'])) {
                        $cList = implode(",", $params['nchecked']);
                    } else {
                        $cList = $params['nchecked'];
                    }
                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                }
            } else if (isset($params['remove'])) {//post remove
                if (isset($params['nchecked'])) {
                    if ($params['nchecked'] != '') {
                        $removeList = $params['nchecked'];
                        if ($isAdmin) {
                            LibraryItems::DeleteByIds($removeList);
                        } else if ($isGrpAdmin) {
                            $query = QuestionSet::getByQSetIdAndGroupId($removeList, $groupId);
                            foreach ($query as $row) {
                                LibraryItems::deleteByQsetId($row['id']);
                            }
                        } else {
                            $query = QuestionSet::getIdByIDAndOwnerId($removeList, $userId);
                            foreach ($query as $row) {
                                LibraryItems::deleteByQsetId($row['id']);
                            }
                        }
                        if ($isGrpAdmin) {
                            $query = $query = QuestionSet::getByQSetIdAndGroupId($removeList, $groupId);
                            foreach ($query as $row) {
                                QuestionSet::setDeletedById($row['id']);
                            }
                        } else {
                            if (!$isAdmin) {
                                QuestionSet::setDeletedByIdsAndOwnerId($removeList, $userId);
                            } else {
                                QuestionSet::setDeletedByIds($removeList);
                            }
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                }
                else if (!isset($params['nchecked'])) {
                        $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                }
            } else if (isset($params['transfer'])) {
                if (isset($params['newowner'])) {
                    if ($params['transfer'] != '') {
                        $transList = explode(',', $params['transfer']);
                        if ($isGrpAdmin) {
                            $query = QuestionSet::getByQSetIdAndGroupId($transList, $groupId);
                            foreach ($query as $row) {
                                QuestionSet::setOwnerIdById($row['id'], $params['newowner']);
                            }
                        } else {
                            if (!$isAdmin) {
                                QuestionSet::setOwnerIdByIdsAndOwnerId($transList, $userId, $params['newowner']);
                            } else {
                                QuestionSet::setOwnerIdByIds($transList, $params['newowner']);
                            }
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pageTitle = AppConstant::MANAGE_QUE_SET_TITLE2;
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";

                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash(AppConstant::NO_QUESTION_SELECTED);
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                    $tList = implode(",", $params['nchecked']);
                    $query = User::getUserGreaterThenTeacherRights();
                    $i = AppConstant::NUMERIC_ZERO;
                    $pageTransferUserList = array();
                    foreach ($query as $row) {
                        $pageTransferUserList['val'][$i] = $row['id'];
                        $pageTransferUserList['label'][$i] = $row['LastName'] . ", " . $row['FirstName'];
                        $i++;
                    }
                }
            } else {
                /*
                 * Default data manipulation
                 */
                if ($isAdmin) {
                    $pageAdminMsg = AppConstant::PAGE_ADMIN_MSG1;
                } else if ($isGrpAdmin) {
                    $pageAdminMsg = AppConstant::PAGE_ADMIN_MSG2;
                } else {
                    $pageAdminMsg = "";
                }
                require_once(Yii::$app->basePath . "/filter/filter.php");
                /*
                 * remember search
                 */
                if (isset($params['search'])) {
                    $safeSearch = $params['search'];
                    $safeSearch = str_replace(' and ', ' ', $safeSearch);
                    $search = stripslashes($safeSearch);
                    $search = str_replace('"', '&quot;', $search);
                    $sessionData['lastsearch' . $cid] = $safeSearch; //str_replace(" ","+",$safeSearch);
                    if (isset($params['searchmine'])) {
                        $searchMine = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchMine = AppConstant::NUMERIC_ZERO;
                    }
                    $sessionData['searchmine' . $cid] = $searchMine;
                    if (isset($params['searchall'])) {
                        $searchAll = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchAll = AppConstant::NUMERIC_ZERO;
                    }
                    $sessionData['searchall' . $cid] = $searchAll;
                    if ($searchAll == AppConstant::NUMERIC_ONE && trim($search) == '' && $searchMine == AppConstant::NUMERIC_ZERO) {
                        $overwriteBody = AppConstant::NUMERIC_ONE;
                        $body = "Must provide a search term when searching all libraries <a href=\"manage-question-set\">Try again</a>";
                        $searchAll = AppConstant::NUMERIC_ZERO;
                    }
                    if ($isAdmin) {
                        if (isset($params['hidepriv'])) {
                            $hidePrivate = AppConstant::NUMERIC_ONE;
                        } else {
                            $hidePrivate = AppConstant::NUMERIC_ZERO;
                        }
                        $sessionData['hidepriv' . $cid] = $hidePrivate;
                    }
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($sessionData['lastsearch' . $cid])) {
                    $safeSearch = $sessionData['lastsearch' . $cid]; //str_replace("+"," ",$sessionData['lastsearch'.$cid]);
                    $search = stripslashes($safeSearch);
                    $search = str_replace('"', '&quot;', $search);
                    $searchAll = $sessionData['searchall' . $cid];
                    $searchMine = $sessionData['searchmine' . $cid];
                    if ($isAdmin) {
                        $hidePrivate = $sessionData['hidepriv' . $cid];
                    }
                } else {
                    $search = '';
                    $searchAll = AppConstant::NUMERIC_ZERO;
                    $searchMine = AppConstant::NUMERIC_ZERO;
                    $safeSearch = '';
                }
                if (trim($safeSearch) == '') {
                    $searchLikes = '';
                } else {
                    if (substr($safeSearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SIX) == 'regex:') {
                        $safeSearch = substr($safeSearch, AppConstant::NUMERIC_SIX);
                        $searchLikes = "imas_questionset.description REGEXP '$safeSearch' AND ";
                    } else if ($safeSearch == 'isbroken') {
                        $searchLikes = "imas_questionset.broken=1 AND ";
                    } else if (substr($safeSearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SEVEN) == 'childof') {
                        $searchLikes = "imas_questionset.ancestors REGEXP '[[:<:]]" . substr($safeSearch, AppConstant::NUMERIC_EIGHT) . "[[:>:]]' AND ";
                    } else {
                        $searchTerms = explode(" ", $safeSearch);
                        $searchLikes = '';
                        foreach ($searchTerms as $k => $v) {
                            if (substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE) == 'type=') {
                                $searchLikes .= "imas_questionset.qtype='" . substr($v, AppConstant::NUMERIC_FIVE) . "' AND ";
                                unset($searchTerms[$k]);
                            }
                        }
                        $searchLikes .= "((imas_questionset.description LIKE '%" . implode("%' AND imas_questionset.description LIKE '%", $searchTerms) . "%') ";
                        if (substr($safeSearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_THREE) == 'id=') {
                            $searchLikes = "imas_questionset.id='" . substr($safeSearch, AppConstant::NUMERIC_THREE) . "' AND ";
                        } else if (is_numeric($safeSearch)) {
                            $searchLikes .= "OR imas_questionset.id='$safeSearch') AND ";
                        } else {
                            $searchLikes .= ") AND";
                        }
                    }
                }
                if (isset($params['libs'])) {
                    if ($params['libs'] == '') {
                        $params['libs'] = $userDefLib;
                    }
                    $searchLibs = $params['libs'];
                    $sessionData['lastsearchlibs' . $cid] = $searchLibs;
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($params['listlib'])) {
                    $searchLibs = $params['listlib'];
                    $sessionData['lastsearchlibs' . $cid] = $searchLibs;
                    $searchAll = AppConstant::NUMERIC_ZERO;
                    $sessionData['searchall' . $cid] = $searchAll;
                    $sessionData['lastsearch' . $cid] = '';
                    $searchLikes = '';
                    $search = '';
                    $safeSearch = '';
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($sessionData['lastsearchlibs' . $cid])) {
                    $searchLibs = $sessionData['lastsearchlibs' . $cid];
                } else {
                    $searchLibs = $userDefLib;
                }
//                $lList = "'" . implode("','", explode(',', $searchLibs)) . "'";
                $lList = explode(',', $searchLibs);
                $libSortOrder = array();
                if (substr($searchLibs, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE) == "0") {
                    $lNamesArray[0] = AppConstant::UNASSIGNED;
                    $libSortOrder[0] = AppConstant::NUMERIC_ZERO;
                }
                $query = Libraries::getLibrariesByIdList($lList);
                foreach ($query as $row) {
                    $lNamesArray[$row['id']] = $row['name'];
                    $libSortOrder[$row['id']] = $row['sortorder'];
                }
                if (count($lNamesArray) > AppConstant::NUMERIC_ZERO) {
                    $lNames = implode(", ", $lNamesArray);
                } else {
                    $lNames = '';
                }
                $resultLibs = QuestionSet::getQuestionSetDataByJoin($searchLikes, $isAdmin, $searchAll, $hidePrivate, $lList, $searchMine, $isGrpAdmin, $userId, $groupId);
                $pageQuestionTable = array();
                $pageLibsToUse = array();
                $pageLibQids = array();
                $lastLib = AppConstant::NUMERIC_NEGATIVE_ONE;
                $ln = AppConstant::NUMERIC_ONE;
                foreach ($resultLibs as $line) {
                    if (isset($pageQuestionTable[$line['id']])) {
                        continue;
                    }
                    if ($lastLib != $line['libid'] && (isset($lNamesArray[$line['libid']]) || $searchAll == AppConstant::NUMERIC_ONE)) {
                        $pageLibsToUse[] = $line['libid'];
                        $lastLib = $line['libid'];
                        $pageLibQids[$line['libid']] = array();
                    }
                    if ($libSortOrder[$line['libid']] == AppConstant::NUMERIC_ONE) {
                        $pageLibQids[$line['libid']][$line['id']] = trim($line['description']);
                    } else {
                        $pageLibQids[$line['libid']][] = $line['id'];
                    }
                    $i = $line['id'];
                    $pageQuestionTable[$i]['checkbox'] = "<input class='margin-right-two natwar' type=checkbox name='nchecked[]' value='" . $line['id'] . "' id='qo$ln'>";
                    if ($line['userights'] == AppConstant::NUMERIC_ZERO) {
                        $pageQuestionTable[$i]['desc'] = '<span class="red">' . filter($line['description']) . '</span>';
                    } else if ($line['replaceby'] > AppConstant::NUMERIC_ZERO || $line['junkflag'] > AppConstant::NUMERIC_ZERO) {
                        $pageQuestionTable[$i]['desc'] = '<span style="color:#ccc"><i>' . filter($line['description']) . '</i></span>';
                    } else {
                        $pageQuestionTable[$i]['desc'] = filter($line['description']);
                    }
                    if ($line['extref'] != '') {
                        $pageQuestionTable[$i]['cap'] = AppConstant::NUMERIC_ZERO;
                        $extRef = explode('~~', $line['extref']);
                        $hasVideo = false;
                        $hasOther = false;
                        $hasCap = false;
                        foreach ($extRef as $v) {
                            if (strtolower(substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE)) == "video" || strpos($v, 'youtube.com') !== false || strpos($v, 'youtu.be') !== false) {
                                $hasVideo = true;
                                if (strpos($v, '!!1') !== false) {
                                    $pageQuestionTable[$i]['cap'] = AppConstant::NUMERIC_ONE;
                                }
                            } else {
                                $hasOther = true;
                            }
                        }
                        $pageQuestionTable[$i]['extref'] = '';
                        if ($hasVideo) {
                            $pageQuestionTable[$i]['extref'] .= "<img src=" . AppUtility::getHomeURL() . 'img/video_tiny.png' . ">";
                        }
                        if ($hasOther) {
                            $pageQuestionTable[$i]['extref'] .= "<img src=" . AppUtility::getHomeURL() . 'img/html_tiny.png' . ">";
                        }
                    }
                    $pageQuestionTable[$i]['preview'] = "<div onClick=\"previewq('selform',$ln,{$line['id']})\" style='width: 100%;' class='btn btn-primary'><img class = 'padding-right-five small-preview-icon' src='" . AppUtility::getAssetURL() . 'img/prvAssess.png' . "'>&nbsp;Preview</div>";
                    $pageQuestionTable[$i]['type'] = $line['qtype'];
                    if ($searchAll == AppConstant::NUMERIC_ONE) {
                        $pageQuestionTable[$i]['lib'] = "<a href=\"manage-question-set?cid=$cid&listlib={$line['libid']}\">List lib</a>";
                    } else {
                        $pageQuestionTable[$i]['junkflag'] = $line['junkflag'];
                        $pageQuestionTable[$i]['libitemid'] = $line['libitemid'];
                    }
                    $pageQuestionTable[$i]['times'] = AppConstant::NUMERIC_ZERO;
                    if ($isAdmin || $isGrpAdmin) {
                        $pageQuestionTable[$i]['mine'] = $line['lastName'] . ',' . substr($line['firstName'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE);
                        if ($line['userights'] == AppConstant::NUMERIC_ZERO) {
                            $pageQuestionTable[$i]['mine'] .= ' <i>Priv</i>';
                        }
                    } else if ($line['ownerid'] == $userId) {
                        if ($line['userights'] == AppConstant::NUMERIC_ZERO) {
                            $pageQuestionTable[$i]['mine'] = '<i>Priv</i>';
                        } else {
                            $pageQuestionTable[$i]['mine'] = 'Yes';
                        }
                    } else {
                        $pageQuestionTable[$i]['mine'] = '';
                    }
                    $pageQuestionTable[$i]['action'] = "<select class='form-control-for-question' onchange=\"doaction(this.value,{$line['id']})\"><option selected value=\"0\">Action..</option>";
                    if ($isAdmin || ($isGrpAdmin && $line['groupid'] == $groupId) || $line['ownerid'] == $userId || ($line['userights'] == AppConstant::NUMERIC_THREE && $line['groupid'] == $groupId) || $line['userights'] > AppConstant::NUMERIC_THREE) {
                        $pageQuestionTable[$i]['action'] .= '<option value="mod">Modify Code</option>';
                    } else {
                        $pageQuestionTable[$i]['action'] .= '<option value="mod">View Code</option>';
                    }
                    $pageQuestionTable[$i]['action'] .= '<option value="temp">Template (copy)</option>';
                    if ($isAdmin || ($isGrpAdmin && $line['groupid'] == $groupId) || $line['ownerid'] == $userId) {
                        $pageQuestionTable[$i]['action'] .= '<option value="del">Delete</option>';
                        $pageQuestionTable[$i]['action'] .= '<option value="tr">Transfer</option>';
                    }
                    $pageQuestionTable[$i]['action'] .= '</select>';
                    $pageQuestionTable[$i]['lastmod'] = date("m/d/y", $line['lastmoddate']);
                    $pageQuestionTable[$i]['add'] = "<a href=\"mod-question?qsetid={$line['id']}&cid=$cid\">Add</a>";
                    $ln++;
                }
                /*
                 * pull question useage data
                 */
                if (count($pageQuestionTable) > AppConstant::NUMERIC_ZERO) {
                    $allUsedQids = implode(',', array_keys($pageQuestionTable));
                    $query = Questions::getByQuestionSetId(array_keys($pageQuestionTable));
                    foreach ($query as $row) {
                        $pageQuestionTable[$row['questionsetid']]['times'] = $row['COUNT(id)'];
                    }
                }
                /*
                 * sort alpha sorted libraries
                 */
                foreach ($pageLibsToUse as $libId) {
                    if ($libSortOrder[$libId] == AppConstant::NUMERIC_ONE) {
                        natcasesort($pageLibQids[$libId]);
                        $pageLibQids[$libId] = array_keys($pageLibQids[$libId]);
                    }
                }
                if ($searchAll == AppConstant::NUMERIC_ONE) {
                    $pageLibsToUse = array_keys($pageLibQids);
                    if(count($pageLibsToUse) < AppConstant::NUMERIC_ONE )
                    {
                        $this->setErrorFlash("Question Not found.");
                    }
                }

            }
        }
        $this->includeCSS(['question/question.css', 'question/libtree.css', 'dataTables.bootstrap.css']);
        $this->includeJS(['general.js', 'tablesorter.js', 'question/junkflag.js','question/libtree2.js', 'question/manageQuestionSet.js','jquery.dataTables.min.js','dataTables.bootstrap.js']);
        $renderData = array('params' => $params, 'overwriteBody' => $overwriteBody, 'body' => $body, 'searchlibs' => $searchLibs, 'curBreadcrumb' => $curBreadcrumb,
            'pagetitle' => $pageTitle, 'helpicon' => $helpIcon, 'cid' => $cid, 'tlist' => $tList, 'page_transferUserList' => $pageTransferUserList,
            'clist' => $cList, 'page_adminMsg' => $pageAdminMsg, 'lnames' => $lNames, 'search' => $search, 'searchall' => $searchAll, 'searchmine' => $searchMine,
            'isadmin' => $isAdmin, 'hidepriv' => $hidePrivate, 'isgrpadmin' => $isGrpAdmin, 'page_libstouse' => $pageLibsToUse, 'lnamesarr' => $lNamesArray,
            'page_libqids' => $pageLibQids, 'page_questionTable' => $pageQuestionTable, 'remove' => $remove, 'transfer' => $transfer);
        return $this->renderWithData('manageQuestionSet', $renderData);
    }

    public function actionModTutorialQuestion()
    {
        $user = $this->user;
        $isAdmin = false;
        $isGrpAdmin = false;
        global $qSetId;
        $this->layout = 'master';
        $makeLocal = $this->getParamVal('makelocal');
        $templateId = $this->getParamVal('templateid');
        $aid = $this->getParamVal('aid');
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $getId = $this->getParamVal('id');
        $params = $this->getRequestParams();
        $now = time();
        $editMsg = '';
        if (!isset($aid))
        {
            if ($cid == "admin")
            {
                if ($user['rights'] == AppConstant::ADMIN_RIGHT)
                {
                    $isAdmin = true;
                    $cid = 'admin';
                }
                else if ($user['rights'] == AppConstant::GROUP_ADMIN_RIGHT)
                {
                    $isGrpAdmin = true;
                }
            }
        }
        if(isset($params['text']))
        {
            if (!isset($id))
            {
                $id = 'new';
            }
            else
            {
                $id = $getId;
            }
            $params = stripslashes_deep($params);
            $qText = $this->stripSmartQuotesForTutorial($params['text']);
            $nParts = intval($params['nparts']);
            $qTypes = array();
            $qParts = array();
            $questions = array();
            $feedBack = array();
            $feedBackTxtDef = array();
            $feedBackTxtEssay = array();
            $answerBoxSize = array();
            $scoreMethod = array();
            $useEditor = array();
            $answer = array();
            $partial = array();
            $qTol = array();
            for ($n=AppConstant::NUMERIC_ZERO;$n<$nParts;$n++)
            {
                $qTypes[$n] = $params['qtype'.$n];
                $feedBack[$n] = array();
                if ($qTypes[$n] == 'choices')
                {
                    $questions[$n] = array();
                    $answer[$n] = $params['ans'.$n];
                }
                else if ($qTypes[$n] == 'number')
                {
                    $partialAns[$n] = array();
                    $qTol[$n] = (($params['qtol'.$n]=='abs')?'|':'') . $params['tol'.$n];
                    $feedBackTxtDef[$n] = $params['fb'.$n.'-def'];
                    $answer[$n] = $params['txt'.$n.'-'.$params['ans'.$n]];
                    $params['pc'.$n.'-'.$params['ans'.$n]] = AppConstant::NUMERIC_ONE;
                    $answerBoxSize[$n] = intval($params['numboxsize'.$n]);
                }
                else if ($qTypes[$n] == 'essay')
                {
                    $answer[$n] = '"'.str_replace('"','\\"',$params['essay'.$n.'-fb']).'"';
                    if (isset($params['useeditor'.$n]))
                    {
                        $useEditor[$n] = true;
                    }
                    if (isset($params['takeanything'.$n]))
                    {
                        $scoreMethod[$n] = 'takeanything';
                    }
                    $answerBoxSize[$n] = intval($params['essayrows'.$n]);
                }
                if ($qTypes[$n] == 'choices' || $qTypes[$n] == 'number')
                {
                    $qParts[$n] = intval($params['qparts'.$n]);
                    $questions[$n] = array();
                    $partialAns[$n] = array();
                    $feedBackTxt[$n] = array();
                    $partial[$n] = array();
                    for($i=0;$i<$qParts[$n];$i++)
                    {
                        if (trim($params['txt'.$n.'-'.$i]) == '') {continue;}
                        if ($qTypes[$n] == 'choices')
                        {
                            $questions[$n][] = $params['txt'.$n.'-'.$i];
                        }
                        else if ($qTypes[$n] == 'number')
                        {
                            $partialAns[$n][] = $params['txt'.$n.'-'.$i];
                        }
                        $feedBackTxt[$n][] = $params['fb'.$n.'-'.$i];
                        $partial[$n][] = floatval($params['pc'.$n.'-'.$i]);
                    }
                    $qParts[$n] = count($feedBackTxt[$n]);
                }
                else if($qTypes[$n] == 'essay')
                {
                    $qParts[$n] = AppConstant::NUMERIC_ZERO;
                    $feedBackTxtEssay[$n] = $params['essay'.$n.'-fb'];
                }
            }
            $nHints = intval($params['nhints']);
            $hintText = array();
            for ($n=0;$n<$nHints;$n++)
            {
                if (!empty($params['hint'.$n]))
                {
                    $hintText[] = $params['hint'.$n];
                }
            }
            $nHints = count($hintText);
            $code = '';
            if ($nParts == AppConstant::NUMERIC_ONE)
            {
                $qType = $qTypes[0];
                $partialOut = array();
                for ($i=0;$i<$qParts[0];$i++)
                {
                    if ($qTypes[0]=='choices')
                    {
                        $code .= '$questions['.$i.'] = "'.str_replace('"','\\"',$questions[0][$i]).'"'."\n";
                    }
                    $code .= '$feedBackTxt['.$i.'] = "'.str_replace('"','\\"',$feedBackTxt[0][$i]).'"'."\n";
                    if ($partial[0][$i]!=0 || $qTypes[0]=='number')
                    {
                        if ($qTypes[0]=='choices')
                        {
                            $partialOut[] = $i;
                        }
                        else if ($qTypes[0]=='number')
                        {
                            $partialOut[] = $partialAns[0][$i];
                        }
                        $partialOut[] = $partial[0][$i];
                    }
                }
                if (count($partialOut) > AppConstant::NUMERIC_ZERO)
                {
                    $code .= '$partialCredit = array('.implode(',',$partialOut).')'."\n";
                }
                if ($qTypes[0]=='choices')
                {
                    $code .= '$displayFormat = "'.$params['qdisp0'].'"'."\n";
                    $code .= '$noShuffle = "'.$params['qshuffle0'].'"'."\n";
                }
                else if ($qTypes[0]=='number')
                {
                    $code .= '$feedBackTxtDef = "'.str_replace('"','\\"',$feedBackTxtDef[0]).'"'."\n";
                    $code .= '$answerBoxSize = '.$answerBoxSize[0]."\n";
                    $code .= (($params['qtol0']=='abs')?'$absTolerance':'$relTolerance').' = '.$params['tol0']."\n";
                }
                else if ($qTypes[0]=='essay')
                {
                    $code .= '$feedBackTxtEssay = "'.str_replace('"','\\"',$feedBackTxtEssay[0]).'"'."\n";
                    $code .= '$answerBoxSize = '.$answerBoxSize[0]."\n";
                    if (isset($useEditor[0]))
                    {
                        $code .= '$displayFormat = "editor"'."\n";
                    }
                    if (isset($scoreMethod[0]))
                    {
                        $code .= '$scoreMethod = "'.$scoreMethod[0].'"'."\n";
                    }
                }
                $code .= '$answer = '.$answer[0]."\n\n";
            }
            else
            {
                $qType = 'multiPart';
                $code .= '$ansTypes = "'.implode(',',$qTypes).'"'."\n\n";
                for ($n=0;$n<$nParts;$n++)
                {
                    $partialOut = array();
                    for ($i=0;$i<$qParts[$n];$i++)
                    {
                        if ($qTypes[$n]=='choices')
                        {
                            $code .= '$questions['.$n.']['.$i.'] = "'.str_replace('"','\\"',$questions[$n][$i]).'"'."\n";
                        }
                        $code .= '$feedBackTxt['.$n.']['.$i.'] = "'.str_replace('"','\\"',$feedBackTxt[$n][$i]).'"'."\n";
                        if ($partial[$n][$i]!= AppConstant::NUMERIC_ZERO || $qTypes[$n]=='number')
                        {
                            if ($qTypes[$n]=='choices')
                            {
                                $partialOut[] = $i;
                            }
                            else if ($qTypes[$n]=='number')
                            {
                                $partialOut[] = $partialAns[$n][$i];
                            }
                            $partialOut[] = $partial[$n][$i];
                        }
                    }
                    if (count($partialOut) > AppConstant::NUMERIC_ZERO)
                    {
                        $code .= '$partialCredit['.$n.'] = array('.implode(',',$partialOut).')'."\n";
                    }
                    if ($qTypes[$n]=='choices')
                    {
                        $code .= '$displayFormat['.$n.'] = "'.$params['qdisp'.$n].'"'."\n";
                        $code .= '$noShuffle['.$n.'] = "'.$params['qshuffle'.$n].'"'."\n";
                    }
                    else if ($qTypes[$n]=='number')
                    {
                        $code .= '$feedBackTxtDef['.$n.'] = "'.str_replace('"','\\"',$feedBackTxtDef[$n]).'"'."\n";
                        $code .= '$answerBoxSize['.$n.'] = '.$answerBoxSize[$n]."\n";
                        $code .= (($params['qtol'.$n]=='abs')?'$absTolerance[':'$relTolerance[').$n.'] = '.$params['tol'.$n]."\n";
                    }
                    else if ($qTypes[$n]=='essay')
                    {
                        $code .= '$feedBackTxtEssay['.$n.'] = "'.str_replace('"','\\"',$feedBackTxtEssay[$n]).'"'."\n";
                        $code .= '$answerBoxSize['.$n.'] = '.$answerBoxSize[$n]."\n";
                        if (isset($useEditor[$n]))
                        {
                            $code .= '$displayFormat['.$n.'] = "editor"'."\n";
                        }
                        if (isset($scoreMethod[$n]))
                        {
                            $code .= '$scoreMethod['.$n.'] = "'.$scoreMethod[$n].'"'."\n";
                        }
                    }
                    $code .= '$answer['.$n.'] = '.$answer[$n]."\n\n";
                }
            }
            for ($i=0;$i<$nHints;$i++)
            {
                $code .= '$hintText['.$i.'] = "'.str_replace('"','\\"',$hintText[$i]).'"'."\n";
            }
            $code .= "\n//end stored values - Tutorial Style question\n\n";
            $qTexPre = '';
            if ($nHints > AppConstant::NUMERIC_ZERO)
            {
                $qTexPre .= '<p style="text-align: right">';
                for ($i=0;$i < $nHints;$i++)
                {
                    $code .= '$hintLink['.$i.'] = formHoverOver("Hint '.($i+1).'",$hintText['.$i.'])'."\n";
                    $qTexPre .= '$hintLink['.$i.'] ';
                }
                $qTexPre .= '</p>';
            }
            $code .= "\n";
            if ($nParts == AppConstant::NUMERIC_ONE)
            {
                if ($qTypes[0]=='choices')
                {
                    $code .= '$feedBack = getFeedBackTxt($stuAnswers[$thisQ], $feedBackTxt, $answer)'."\n";
                }
                else if ($qTypes[0]=='number')
                {
                    $code .= '$feedBack = getFeedBackTxtNumber($stuAnswers[$thisQ], $partialCredit, $feedBackTxt, $feedBackTxtDef, "'.$qTol[0].'")'."\n";
                }
                else if ($qTypes[0]=='essay')
                {
                    $code .= '$feedBack = getFeedBackTxtEssay($stuAnswers[$thisQ], $feedBackTxtEssay)'."\n";
                }
            }
            else
            {
                for ($n=0;$n<$nParts;$n++)
                {
                    if ($qTypes[$n]=='choices')
                    {
                        $code .= '$feedBack['.$n.'] = getFeedBackTxt($stuAnswers[$thisQ]['.$n.'], $feedBackTxt['.$n.'], $answer['.$n.'])'."\n";
                    }
                    else if ($qTypes[$n]=='number')
                    {
                        $code .= '$feedBack['.$n.'] = getFeedBackTxtNumber($stuAnswers[$thisQ]['.$n.'], $partialCredit['.$n.'], $feedBackTxt['.$n.'], $feedBackTxtDef['.$n.'], "'.$qTol[$n].'")'."\n";
                    }
                    else if ($qTypes[$n]=='essay')
                    {
                        $code .= '$feedBack['.$n.'] = getFeedBackTxEssay($stuAnswers[$thisQ]['.$n.'], $feedBackTxtEssay['.$n.'])'."\n";
                    }
                }
            }
            $qText = $qTexPre . $qText;
            if ($id == 'new')
            {
                $mt = microtime();
                $uQid = substr($mt,11).substr($mt,2,6);
                $ancestors = '';
                if(isset($templateId))
                {
                    $ancestors = QuestionSet::getAncestor($templateId);
                    if($ancestors == '')
                    {
                        $ancestors = $templateId . ','. $ancestors['ancestors'];
                    }
                    else
                    {
                        $ancestors = $templateId;
                    }
                }
                $query =new QuestionSet();
                $insertId = $query->insertDataForModTutorial($uQid,$now,$params,$user,$qType,$code,$qText,$ancestors);
                $id = $insertId;
                if(isset($makeLocal))
                {
                    QuestionSet::updateQSetForTutorial($qSetId,$makeLocal);
                    $editMsg .= " Local copy of Question Created ";
                    $fromPot = AppConstant::NUMERIC_ZERO;
                }
                else
                {
                    $editMsg .= " Question Added to QuestionSet. ";
                    $fromPot = AppConstant::NUMERIC_ONE;
                }
            }
            else
            {
                $isOk = true;
                if($isGrpAdmin)
                {
                    $query = QuestionSet::getByGroupId($id,$user['groupid']);
                    if(!$query)
                    {
                        $isOk = false;
                    }
                }
                if(!$isAdmin && !$isGrpAdmin)
                {
                    $query = QuestionSet::getByUserIdGroupId($id,$user['id'],$user['groupid']);
                    if(!$query)
                    {
                        $isOk = false;
                    }
                }
                if($isOk)
                {
                    QuestionSet::updateQueSet($params,$now,$qType,$code,$id,$qText);
                }
            }
            if (!isset($aid))
            {
                $editMsg .=  "<a href='".AppUtility::getURLFromHome('question','question/manage-question-set?cid='.$cid)."'>Return to Question Set Management</a>\n";
            }
            else
            {
                if ($fromPot == AppConstant::NUMERIC_ONE)
                {
                    $editMsg .=  "<a href='".AppUtility::getURLFromHome('question','question/mod-question?cid='.$cid.'&qsetid='.$id.'&aid='.$aid.'&process=true&usedef=true')."'>Add Question to Assessment using Defaults</a>\n";
                    $editMsg .=  "<a href='".AppUtility::getURLFromHome('question','question/mod-question?cid='.$cid.'&qsetid='.$id.'&aid='.$aid)."'>Add Question to Assessment</a>\n";
                }
                $editMsg .=  "<a href='".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid)."'>Return to Assessment</a>\n";
            }
            $newLibs = explode(",",$params['libs']);
            if (in_array('0',$newLibs) && count($newLibs) > AppConstant::NUMERIC_ONE)
            {
                array_shift($newLibs);
            }
            $qSetId = $id;
            if ($params['libs']=='')
            {
                $newLibs = array();
            }
            $libraryData = LibraryItems::getByGroupId($user['groupid'],$qSetId,$user['id'],$isGrpAdmin,$isAdmin);
            $existing = array();
            foreach ($libraryData as $row)
            {
                $existing[] = $row['libid'];
            }
            $toAdd = array_values(array_diff($newLibs,$existing));
            $toRemove = array_values(array_diff($existing,$newLibs));
            while(count($toRemove) > AppConstant::NUMERIC_ZERO && count($toAdd) > AppConstant::NUMERIC_ZERO)
            {
                $toChange = array_shift($toRemove);
                $toRep = array_shift($toAdd);
                LibraryItems::setLibId($toRep,$qSetId,$toChange);
            }
            if(count($toAdd) > AppConstant::NUMERIC_ZERO)
            {
                foreach($toAdd as $libId)
                {
                    $query = new LibraryItems();
                    $query->insertData($libId,$qSetId,$user);
                }
            }
            else if(count($toRemove) > AppConstant::NUMERIC_ZERO)
            {
                foreach($toRemove as $libId)
                {
                    LibraryItems::deleteByQsetIdAndLibId($libId,$qSetId);
                }
            }
            if(count($newLibs) == 0)
            {
                $query = LibraryItems::getIdByQid($qSetId);
                if(!$query)
                {
                    $query = new LibraryItems();
                    $query->insertData(0,$qSetId,$user);
                }
            }
        }
        $query = User::userDataForTutorial($user['id']);
        $myName = $query['LastName'].','.$query['FirstName'];
        if(isset($id) && $id == 'new')
        {
            $id = intval($id);
            $query = QuestionSet::getByUIdQSetId($id);
            $myQ = ($query['ownerid'] == $user['id']);
            if ($isAdmin || ($isGrpAdmin && $query['groupid'] == $user['groupid']) || ($query['userights']== AppConstant::NUMERIC_THREE && $query['groupid'] == $user['groupid']) || $query['userights'] > AppConstant::NUMERIC_THREE)
            {
                $myQ = true;
            }
            $nameList = explode(", mb ",$query['author']);
            if ($myQ && !in_array($myName,$nameList))
            {
                $nameList[] = $myName;
            }
            if (isset($_GET['template']))
            {
                $author = $myName;
                $myQ = true;
            }
            else
            {
                $author = implode(", mb ",$nameList);
            }
            foreach ($query as $k=>$v)
            {
                $query[$k] = str_replace('&','&amp;',$v);
            }
            $inLibs = array();
            if(isset($templateId))
            {
                $query = User::findIdentity($user['id']);
                $userFullName = "";
                $defLib = $query['deflib'];
                $useDefLib = $query['usedeflib'];
                if (isset($makeLocal))
                {
                    $inLibs[] = $defLib;
                    $query['description'] .= " (local for $userFullName)";
                }
                else
                {
                    $query['description'] .= " (copy by $userFullName)";
                    if ($useDefLib == AppConstant::NUMERIC_ONE)
                    {
                        $inLibs[] = $defLib;
                    }
                    else
                    {
                        $libData = Libraries::getByQSetId($id);
                        foreach($libData as $row)
                        {
                            if($row['userights'] == 8 || ($row['groupid'] == $user['groupid'] && ($row['userights']%3 ==2)) || $row['ownerid'] == $user['id'])
                            {
                                $inLibs[] = $row['id'];
                            }
                        }
                    }
                }
                $lockLibs = array();
                $addMod = "Add";
                $userData = User::getById($user['id']);
                $query['userights'] = $userData['qrightsdef'];
            }
            else
            {
                if($isGrpAdmin)
                {
                    $dataForLib = LibraryItems::getDataForModTutorial($user['groupid'],$id);
                }
                else
                {
                    $dataForLib = LibraryItems::getByQidIfNotAdmin($id,$isAdmin,$user['id']);
                }
                if($dataForLib)
                {
                    foreach($dataForLib as $row)
                    {
                        $inLibs[] = $row['libid'];
                    }
                }
                $lockLibs = array();
                if (!$isAdmin)
                {
                    if ($isGrpAdmin)
                    {
                        $libData = LibraryItems::getDataForModTutorial($user['groupid'],$id);
                    }
                    else if (!$isAdmin)
                    {
                        $libData = LibraryItems::getDataForModTutorialIfNoAdmin($user['id'],$id);
                    }
                    if($libData)
                    {
                        foreach($libData as $row)
                        {
                            $lockLibs[] = $row['libid'];
                        }
                    }
                }
                $addMod = "Modify";
                $query = Questions::getDataForModTutorial($user['id'],$id);
                if($query)
                {
                    $inUseCnt = $query[0]['count(imas_questions.id)'];
                }

            }
            if (count($inLibs)==0 && count($lockLibs)==0)
            {

                $inLibs = array(0);
            }
            $inLibs = implode(",",$inLibs);
            $lockLibs = implode(",",$lockLibs);

            $code = $query['control'];
            $type = $query['qtype'];
            $qText = $query['qtext'];
            if (strpos($code,'//end stored') === false)
            {
                $this->setErrorFlash('This question is not formatted in a way that allows it to be editted with this tool.');
                return $this->redirect('mod-tutorial-question?cid='.$course->id);
            }
            $mathfuncs = array("sin","cos","tan","sinh","cosh","tanh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
            $allowedmacros = $mathfuncs;
            list($nParts, $qType, $qParts, $nHints, $qDisp, $questions, $feedBackTxt, $feedBackTxtDef, $feedBackTxtEssay, $answer, $hintText, $partialcredit, $qTol, $qTold, $answerBoxSize, $displayFormat, $scoreMethod, $qShuffle) = $this->getqvalues($code,$type);
            $partial = array();

            for ($n=0;$n<$nParts;$n++) {
                $partial[$n] = array();
                for ($i=0;$i<count($partialcredit[$n]);$i+=2) {
                    if ($qType[$n]=="number") {
                        $questions[$n][floor($i/2)] = $partialcredit[$n][$i];
                        if ($partialcredit[$n][$i]==$answer[$n]) {
                            $answerloc[$n] = floor($i/2);
                        }
                        $partial[$n][floor($i/2)] = $partialcredit[$n][$i+1];
                    } else if ($qType[$n]=="choices") {
                        $partial[$n][$partialcredit[$n][$i]] = $partialcredit[$n][$i+1];
                    }
                }
                if ($qType[$n]=="number") {
                    $answer[$n] = $answerloc[$n];
                }
            }
            if ($nHints>0) { //strip out hints para
                $qText = substr($qText, strpos($qText,'</p>')+4);
            }
        } else {
            $myQ = true;
            $id = 'new';
            //new question
            $nParts = 1;
            $qParts = array(4,4,4,4,4,4,4,4,4,4);
            $answer = array(0,0,0,0,0,0,0,0,0,0);
            $qDisp = array("vert","vert","vert","vert","vert","vert","vert","vert","vert","vert");
            $qShuffle = array("all","all","all","all","all","all","all","all","all","all");
            $qType = array_fill(0,10,"choices");
            $displayFormat = array();
            $scoreMethod = array();
            $answerBoxSize = array();
            $nHints = 1;
            $questions = array();
            $feedBackTxt = array();
            $feedBackTxtDef = array_fill(0,10,"Incorrect");
            $hintText = array();
            $qTol = array_fill(0,1,"abs");
            $qText = "";

            $line['description'] = "Enter description here";
            $qRightsDef = new User();
            $query = $qRightsDef->getQuestionRights($user['id']);
            $line['userights'] = $query['qrightsdef'];
            $line['author'] = $myName;
            $line['deleted'] = 0;
            if (isset($aid) && isset($sessiondata['lastsearchlibs'.$aid])) {
                $inLibs = $sessiondata['lastsearchlibs'.$aid];
            } else if (isset($sessiondata['lastsearchlibs'.$cid])) {
                //$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
                $inLibs = $sessiondata['lastsearchlibs'.$cid];
            } else {
                $inLibs = $userdeflib;
            }
            $lockLibs='';

            $author = $myName;
            $inLibsSafeArray = explode(',',$inLibs);
            $inLibsSafe = "'".implode("','",$inLibsSafeArray)."'";
            if (!isset($aid) || isset($_GET['template'])) {
                $libs = new Libraries();
                $query = $libs->getLibData($inLibsSafeArray);
                foreach ($query as $row) {
                    if ($row['userights'] == AppConstant::NUMERIC_EIGHT || ($row['groupid']==$groupId && ($row['userights']%AppConstant::NUMERIC_THREE == AppConstant::NUMERIC_TWO)) || $row['ownerid']==$user['id']) {
                        $oklibs[] = $row['id'];
                    }
                }
                if (count($oklibs) > AppConstant::NUMERIC_ZERO) {
                    $inLibs = implode(",",$oklibs);
                } else {$inLibs = AppConstant::ZERO_VALUE;}
            }
            $addMod = "Add";

        }
        $inLibsSafeArray = explode(',',$inLibs);
        $inLibsSafe = "'".implode("','",$inLibsSafeArray)."'";

        $lNames = array();
        if (substr($inLibs,0,1)==='0') {
            $lNames[] = "Unassigned";
        }
        $query = Libraries::getByNameList($inLibsSafeArray);
        foreach ($query as $row) {
            $lNames[] = $row['name'];
        }
        $lNames = implode(", ",$lNames);

        $dispVal = array("vert","horiz","select","inline","2column");
        $dispLbl = array("Vertical list", "Horizontal list", "Pull-down", "Inline with text", "2 column");

        $qTypeVal = array("choices","number","essay");
        $qTypeLbl = array("Multiple-choice","Numeric","Essay");

        $qTolVal = array("abs","rel");
        $qTolLbl = array("absolute","relative");

        $shuffleVal = array("all","last","none");
        $shuffleLbl = array("no shuffle","shuffle all but last","shuffle all");
        $useeditor = "text,popuptxt";
        $this->includeCSS(['dataTables.bootstrap.css','handheld.css', 'question/question.css']);
        $this->includeJS(['general.js','editor/tiny_mce.js','mathjs.js','assessment/modTutorialQuestion.js','jquery.dataTables.min.js','dataTables.bootstrap.js']);
        $renderData = array('params' => $params, 'isAdmin' => $isAdmin,'course' => $course, 'addMod' => $addMod, 'editMsg' => $editMsg,
            'id' => $id, 'cid' => $cid, 'line' => $line, 'inUseCnt' => $inUseCnt, 'myQ' => $myQ, 'fromPot' => $fromPot, 'author' => $author,
            'userId' => $user['id'], 'groupID' => $groupID, 'isGrpAdmin' => $isGrpAdmin, 'inLibs' => $inLibs, 'lockLibs' => $lockLibs,
            'lNames' => $lNames, 'nParts' => $nParts, 'qParts' => $qParts, 'qTypeVal' => $qTypeVal, 'qTypeLbl' => $qTypeLbl, 'qType' => $qType,
            'dispVal' => $dispVal, 'dispLbl' => $dispLbl, 'qDisp' => $qDisp, 'qShuffle' => $qShuffle, 'shuffleLbl' => $shuffleLbl,
            'shuffleVal' => $shuffleVal, 'qTolVal' => $qTolVal, 'qTol' => $qTol, 'qTolLbl' => $qTolLbl, 'qTold' => $qTold, 'answerBoxSize' => $answerBoxSize,
            'displayFormat' => $displayFormat, 'scoreMethod' => $scoreMethod, 'answer' => $answer, 'questions' => $questions, 'feedBackTxt' => $feedBackTxt,
            'partial' => $partial, 'feedBackTxtDef' => $feedBackTxtDef, 'feedBackTxtEssay' => $feedBackTxtEssay, 'nHints' => $nHints,
            'hintText' => $hintText, 'feedBack' => $feedBack, 'qText' => $qText);
        return $this->renderWithData('modTutorialQuestion', $renderData);
    }

    function stripSmartQuotesForTutorial($text)
    {
        $text = str_replace
        (array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text);
        $text = str_replace(
            array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text);
        return $text;
    }

    public function actionSaveBrokenQuestionFlag() {
        $user = $this->user;
        $userfullname = $user['FirstName'].''.$user['LastName'];
        if (!isset($_GET['qsetid']) || $user['rights']<20) {
            exit;
        }
        $_GET['qsetid'] = intval($_GET['qsetid']);
        $ischanged = false;

        $rowAffected = QuestionSet::setBrokenFlag($_GET['flag'], $_GET['qsetid']);
        if ($rowAffected > 0) {
            $ischanged = true;
            if ($_GET['flag']==1) {
                $now = time();
                $msg =  'Question '.$_GET['qsetid'].' marked broken by '.$userfullname;
                $log = new Log();
                $log->createLog($now, $msg);
                if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
                    $questionData = QuestionSet::getOwnerId($_GET['qsetid']);
                    $msgArray = array(
                        'courseid' => $CFG['GEN']['sendquestionproblemsthroughcourse'],
                        'title' => 'Question #'.$_GET["qsetid"].' marked as broken',
                        'message' => 'This is an automated message'. $userfullname.' has marked question #'.$_GET["qsetid"].' as broken. Hopefully they follow up with you about what they think is wrong with it.',
                        'msgto' => $questionData['ownerid'],
                        'msgfrom' => $user['id'],
                        'senddate' => $now,
                    );
                    $message = new Message();
                    $message->insertNewMsg($msgArray);
                }
            }
        }
        $responseArray = array('ischanged' => $ischanged);
        return $this->renderWithData('saveBrokenQuestionFlag', $responseArray);
    }

    public function actionHelp()
    {
        $params = $this->getRequestParams();
        $section = $params['section'];
        $this->layout = 'master';
        $responseData = (['section' => $section]);
        $this->includeCSS(['question/question.css']);
        return $this->renderWithData('help', $responseData);
    }
    public function actionMicroLibHelp()
    {
        $params = $this->getRequestParams();
        $section = $params['section'];
        $this->layout = 'master';
        $responseData = (['section' => $section]);
        $this->includeCSS(['question/question.css']);
        return $this->renderWithData('libhelp', $responseData);
    }

    public function actionViewSource() {
        $user = $this->user;
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $myRights = $user['rights'];
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $this->layout = 'master';
        if (!(isset($teacherId)) && $myRights < AppConstant::ADMIN_RIGHT) {
            $this->setWarningFlash(AppConstant::NO_TEACHER_RIGHTS);
            return $this->redirect($this->goHome());
        }
        $isAdmin = false;
        $qSetId = $params['id'];
        $qSetData = QuestionSet::getByQuesSetId($qSetId);
        $responseData = (['params' => $params, 'qSetData' => $qSetData, 'isAdmin' => $isAdmin, 'course' => $course]);
        return $this->renderWithData('viewSource', $responseData);
    }

    //return array (nparts, qparts, nhints, qdisp, questions, feedbacktxt, answer, hinttext)
    function getqvalues($code,$type) {
        $partialcredit = array();
        $qTol = array();
        $feedBackTxtDef = array();
        $qtold = array();
        $code = substr($code, 0, strpos($code,'//end stored'));
        eval(interpret('control',$type,$code));

        if (!isset($hintText)) {
            $nHints = 0;
        } else {
            $nHints = count($hintText);
        }

        if ($type=='multipart') {
            $qtypes = explode(',',$anstypes);
            $nParts = count($qtypes);
            $qParts = array();
            for ($n=0;$n<$nParts;$n++) {
                if ($qtypes[$n]=='number') {
                    if (isset($reltolerance[$n])) {
                        $qTol[$n] = 'rel';
                        $qtold[$n] = $reltolerance[$n];
                    }  else if (isset($abstolerance[$n])) {
                        $qTol[$n] = 'abs';
                        $qtold[$n] = $abstolerance[$n];
                    }
                    $qParts[$n] = count($partialcredit[$n])/2;
                } else if ($qtypes[$n]=='choices') {
                    $qParts[$n] = count($questions[$n]);
                }
            }

            return array($nParts, $qtypes, $qParts, $nHints, $displayFormat, $questions, $feedBackTxt, $feedBackTxtDef, $feedBackTxtEssay, $answer, $hintText, $partialcredit, $qTol, $qtold, $answerBoxSize, $displayFormat, $scoreMethod, $noshuffle);
        } else {
            if ($type=='number') {
                if (isset($reltolerance)) {
                    $qTol[0] = 'rel';
                    $qtold[0] = $reltolerance;
                }  else if (isset($abstolerance)) {
                    $qTol[0] = 'abs';
                    $qtold[0] = $abstolerance;
                }
                $qParts = array(count($partialcredit)/2);
            }else if ($type=='choices') {
                $qParts = array(count($questions));
            }else if ($type=='essay') {
                $qParts = array(0);
            }
            return array(1, array($type), $qParts, $nHints, array($displayFormat), array($questions), array($feedBackTxt), array($feedBackTxtDef), array($feedBackTxtEssay), array($answer), $hintText, array($partialcredit), $qTol, $qtold, array($answerBoxSize), array($displayFormat), array($scoreMethod), array($noshuffle));
        }
    }

    public function actionPrintLayoutWord()
    {
        global $temp;
        $user = $this->user;
        $userId = $user['id'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $aid = intval($this->getParamVal('aid'));
        $teacherId = $this->isTeacher($userId, $cid);
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $pageTitle = "Print Layout";
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        $sessionData['texdisp'] = true;
        $sessionData['texdoubleescape'] = true;

        $sessionData['graphdisp'] = AppConstant::NUMERIC_ONE;
        $sessionData['mathdisp'] = AppConstant::NUMERIC_TWO;

        /**
         * CHECK PERMISSIONS AND SET FLAGS
         */
        if (!(isset($teacherId))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to log in as a teacher to access this page";
            //} else if (!isset($CFG['GEN']['pandocserver'])) {
            //	$overwriteBody = 1;
            //	$body = 'No pandoc server specified in config';
            //}

        } else {
            /**
             * PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
             */

        }

        if (!isset($_REQUEST['versions'])) {

        } else {
//            $out = '<!DOCTYPE html><html><body>';
            require_once(Yii::$app->basePath . "/filter/filter.php");
            $line = Assessments::getSelectedData($aid);
            $ioQuestions = explode(",",$line['itemorder']);
            $aname = $line['name'];
            $questions = array();
            foreach($ioQuestions as $k=>$q) {
                if (strpos($q,'~')!==false) {
                    $sub = explode('~',$q);
                    if (strpos($sub[0],'|')===false) { //backwards compat
                        $questions[] = $sub[array_rand($sub,1)];
                    } else {
                        $grpqs = array();
                        $grpparts = explode('|',$sub[0]);
                        array_shift($sub);
                        if ($grpparts[1]==1) { // With replacement
                            for ($i=0; $i<$grpparts[0]; $i++) {
                                $questions[] = $sub[array_rand($sub,1)];
                            }
                        } else if ($grpparts[1]==0) { //Without replacement
                            shuffle($sub);
                            for ($i=0; $i<min($grpparts[0],count($sub)); $i++) {
                                $questions[] = $sub[$i];
                            }
                            if ($grpparts[0]>count($sub)) { //fix stupid inputs
                                for ($i=count($sub); $i<$grpparts[0]; $i++) {
                                    $questions[] = $sub[array_rand($sub,1)];
                                }
                            }
                        }
                    }
                } else {
                    $questions[] = $q;
                }
            }
            $points = array();
            $qn = array();
            $questionsData = Questions::getByINId($questions);
            foreach($questionsData as $row)
            {
                if ($row['points']==9999) {
                    $points[$row['id']] = $line['defpoints'];
                } else {
                    $points[$row['id']] = $row['points'];
                }
                $qn[$row['id']] = $row['questionsetid'];
            }
            $numq = count($questions);
            if (is_numeric($_REQUEST['versions'])) {
                $copies = $_REQUEST['versions'];
            } else {
                $copies = 1;
            }
            $seeds = array();
            for ($j=0; $j<$copies; $j++) {
                $seeds[$j] = array();
                if ($line['shuffle']&2) {  //all questions same random seed
                    if ($shuffle&4) { //all students same seed
                        $seeds[$j] = array_fill(0,count($questions),$aid+$j);
                    } else {
                        $seeds[$j] = array_fill(0,count($questions),rand(1,9999));
                    }
                } else {
                    if ($shuffle&4) { //all students same seed
                        for ($i = 0; $i<count($questions);$i++) {
                            $seeds[$j][] = $aid + $i + $j;
                        }
                    } else {
                        for ($i = 0; $i<count($questions);$i++) {
                            $seeds[$j][] = rand(1,9999);
                        }
                    }
                }
            }

            if ($_REQUEST['format']=='trad') {
                for ($j=0; $j<$copies; $j++) {
                    if ($j>0) {
                        $temp .= $_REQUEST['vsep'].'<br/>';}

                    $headerleft = '';
                    $headerleft .= $line['name'];
                    if ($copies>1) {
                        $headerleft .= ' - Form ' . ($j+1);
                    }
                    if ((isset($_REQUEST['iname']) || isset($_REQUEST['cname'])) && isset($_REQUEST['aname'])) {
                        $headerleft .= "<br/>";
                    }
                    $headerright = '';
                    $temp .= "<div class=q>\n";
                    $temp .= "<div class=hdrm>\n";

                    $temp .= "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
                    $temp .= "<div id=intro>{$line['intro']}</div>\n";
                    $temp .= "</div>\n";
                    $temp .= "</div>\n";


                    for ($i=0; $i<$numq; $i++) {
                        if ($i>0) { $temp .= $_REQUEST['qsep'];}
                        list($newout,$sa[$j][$i]) = $this->printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_REQUEST['showqn']));
                        $temp .= $newout;
                    }

                }

                if ($_REQUEST['keys']>0) { //print answer keys
                    for ($j=0; $j<$copies; $j++) {
                        $temp .= $_REQUEST['vsep'].'<br/>';
                        $temp .= '<b>Key - Form ' . ($j+1) . "</b>\n";
                        $temp .= "<ol>\n";
                        for ($i=0; $i<$numq; $i++) {
                            $temp .= '<li>';
                            if (is_array($sa[$j][$i])) {
                                $temp .= printfilter(filter(implode(' ~ ',$sa[$j][$i])));
                            } else {
                                $temp .= printfilter(filter($sa[$j][$i]));
                            }
                            $temp .= "</li>\n";
                        }
                        $temp .= "</ol>\n";
                    }
                }
            }  else if ($_REQUEST['format']=='inter') {

                $headerleft = '';
                $headerleft .= $line['name'];
                if ((isset($_REQUEST['iname']) || isset($_REQUEST['cname'])) && isset($_REQUEST['aname'])) {
                    $headerleft .= "<br/>";
                }
                $headerright = '';
                $temp .= "<div class=q>\n";
                $temp .= "<div class=hdrm>\n";

                $temp .= "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
                $temp .= "<div id=intro>{$line['intro']}</div>\n";
                $temp .= "</div>\n";
                $temp .= "</div>\n";
                for ($i=0; $i<$numq; $i++) {
                    if ($i>0) { $temp .= $_REQUEST['qsep'];}
                    for ($j=0; $j<$copies;$j++) {
                        if ($j>0) { $temp .= $_REQUEST['qsep'];}
                        list($newout,$sa[]) = $this->printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($_REQUEST['showqn']));
                        $temp .= $newout;
                    }
                }
                if ($_REQUEST['keys']>0) { //print answer keys
                    $temp .= $_REQUEST['vsep'].'<br/>';
                    $temp .= "<b>Key</b>\n";
                    $temp .= "<ol>\n";
                    for ($i=0; $i<count($sa); $i++) {
                        $temp .= '<li>';
                        if (is_array($sa[$i])) {
                            $temp .= printfilter(filter(implode(' ~ ',$sa[$i])));
                        } else {
                            $temp .= printfilter(filter($sa[$i]));
                        }
                        $temp .= "</li>\n";
                    }
                    $temp .= "</ol>\n";
                }
            }
//            $licurl = $urlmode.$_SERVER['HTTP_HOST'].$imasroot.'/course/showlicense.php?id='.implode('-',$qn);
//            $out .= '<hr/><p style="font-size:70%">License info at: <a href="'.$licurl.'">'.$licurl.'</a></p>';
//            $temp .= '</body></html>';
//            $out = preg_replace('|(<img[^>]*?)src="/|', '$1 src="'.$urlmode.$_SERVER['HTTP_HOST'].'/', $out);

        }
        $responseData = array('overwriteBody' => $overwriteBody, 'body' => $body, 'cid' => $cid, 'aid' => $aid, 'teacherId' => $teacherId, 'pageTitle' => $pageTitle, 'ioQuestions' => $ioQuestions, 'line' => $line, 'course' => $course);
        return $this->renderWithData('printLayoutWord', $responseData);
    }

    function printq($qn,$qsetid,$seed,$pts,$showpts) {
        global $isfinal,$imasroot,$urlmode, $temp;
        srand($seed);

        $qdata = QuestionSet::getDataById($qsetid);

        if ($qdata['hasimg']>0) {
            $result = QImages::getDataById($qsetid);
            foreach($result as $row){
                if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                    ${$row['var']} = "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/qimages/{$row['filename']}\" alt=\"".htmlentities($row['alttext'],ENT_QUOTES)."\" />";
                } else {
                    ${$row['var']} = "<img src=\"$imasroot/assessment/qimages/{$row['filename']}\" alt=\"".htmlentities($row['alttext'],ENT_QUOTES)."\" />";
                }
            }
        }
        eval(interpret('control',$qdata['qtype'],$qdata['control']));
        eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
        $toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
        $toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
        $toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
        srand($seed+1);
        eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
        srand($seed+2);
        $la = '';

        if (isset($choices) && !isset($questions)) {
            $questions =& $choices;
        }
        if (isset($variable) && !isset($variables)) {
            $variables =& $variable;
        }
        if ($displayformat=="select") {
            unset($displayformat);
        }

        //pack options
        if (isset($ansprompt)) {$options['ansprompt'] = $ansprompt;}
        if (isset($displayformat)) {$options['displayformat'] = $displayformat;}
        if (isset($answerformat)) {$options['answerformat'] = $answerformat;}
        if (isset($questions)) {$options['questions'] = $questions;}
        if (isset($answers)) {$options['answers'] = $answers;}
        if (isset($answer)) {$options['answer'] = $answer;}
        if (isset($questiontitle)) {$options['questiontitle'] = $questiontitle;}
        if (isset($answertitle)) {$options['answertitle'] = $answertitle;}
        if (isset($answersize)) {$options['answersize'] = $answersize;}
        if (isset($variables)) {$options['variables'] = $variables;}
        if (isset($domain)) {$options['domain'] = $domain;}
        if (isset($answerboxsize)) {$options['answerboxsize'] = $answerboxsize;}
        if (isset($hidepreview)) {$options['hidepreview'] = $hidepreview;}
        if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
        if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
        if (isset($reqdecimals)) {$options['reqdecimals'] = $reqdecimals;}
        if (isset($grid)) {$options['grid'] = $grid;}
        if (isset($background)) {$options['background'] = $background;}

        if ($qdata['qtype']=="multipart") {
            if (!is_array($anstypes)) {
                $anstypes = explode(",",$anstypes);
            }
            $laparts = explode("&",$la);
            foreach ($anstypes as $kidx=>$anstype) {
                list($answerbox[$kidx],$tips[$kidx],$shans[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qn+1);
            }
        } else {
            list($answerbox,$tips[0],$shans[0]) = makeanswerbox($qdata['qtype'],$qn,$la,$options,0);
        }

        $temp .= "<div class=q>";
        if ($isfinal) {
            $temp .= "<div class=\"trq$qn\">\n";
        } else {
            $temp .= "<div class=m id=\"trq$qn\">\n";
        }
        if ($showpts) {
            $temp .= ($qn+1).'. ('.$pts.' pts) ';
        }
        $temp .= "<div>\n";
        //$retstrout .= $toevalqtext;
        eval("\$evaledqtext = \"$toevalqtxt\";");
        $temp .= printfilter(filter($evaledqtext));
        $temp .= "</div>\n"; //end question div

        if (strpos($toevalqtxt,'$answerbox')===false) {
            if (is_array($answerbox)) {
                foreach($answerbox as $iidx=>$abox) {
                    $temp .= printfilter(filter("<div>$abox</div>\n"));
                    $temp .= "<div class=spacer>&nbsp;</div>\n";
                }
            } else {  //one question only
                $temp .= printfilter(filter("<div>$answerbox</div>\n"));
            }


        }


        $temp .= "</div>";//end m div

        $temp .= "&nbsp;";
        $temp .= "</div>\n"; //end q div
        if (!isset($showanswer)) {
            return array($temp,$shans);
        } else {
            return array($temp,$showanswer);
        }
    }
    public function actionAddQuestionHelp()
    {
        $this->layout = 'master';
        return $this->render('addQuestionHelp');
    }

}