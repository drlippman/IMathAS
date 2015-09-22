<?php


namespace app\controllers\question;

use app\components\AppUtility;
use app\components\filehandler;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Items;
use app\models\Libraries;
use app\models\LibraryItems;
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
    public function actionAddQuestions()
    {
        $user = $this->getAuthenticatedUser();
        $groupId = $user['groupid'];
        $userId = $user['id'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $teacherId = $this->isTeacher($userId, $courseId);
        if ($user['rights'] == AppConstant::ADMIN_RIGHT) {
            $teacherId = $userId;
            $adminAsTeacher = true;
        }
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = '';
        $course = Course::getById($courseId);
        $this->checkSession($params);
        $curBreadcrumb = $course['name'];
        /*
         * Loaded by a NON-teacher
         */
        if (!$teacherId) {
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

            if (isset($teacherId) && isset($params['addset'])) {
                if (!isset($params['nchecked']) && !isset($params['qsetids'])) {
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $body = "No questions selected.  <a href=" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId) . ">Go back</a>";
                } else if (isset($params['add'])) {
                    include("../views/question/question/modQuestionGrid.php");
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
                            'penalty' => AppConstant::QUARTER_NINE,
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
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $body = "No questions selected.  <a href=" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId) . ">Go back</a>\n";
                } else {
                    include("../views/question/question/modQuestionGrid.php");
                    if (isset($params['process'])) {
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                    }
                }
            }
            if (isset($params['clearattempts'])) {
                if ($params['clearattempts'] == "confirmed") {
                    /*
                     *                     require_once('../includes/filehandler.php');
                     */
                    filehandler::deleteallaidfiles($assessmentId);
                    AssessmentSession::deleteByAssessmentId($assessmentId);
                    Questions::setWithdrawn($assessmentId, AppConstant::NUMERIC_ZERO);
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId));
                } else {
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $assessmentData = Assessments::getByAssessmentId($params['aid']);
                    $assessmentName = $assessmentData['name'];
                    $body .= "<h3>$assessmentName</h3>";
                    $body .= "<p>Are you SURE you want to delete all attempts (grades) for this assessment?</p>";
                    $body .= "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId . '&clearattempts=confirmed') . "'\">\n";
                    $body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId) . "';\"></p>\n";
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

                    //get possible points if needed
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

                    //update assessment sessions
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
                     * $body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
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
                    $body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId) . "'\"></p>\n";
                    $body .= '</form>';
                }

            }
            $address = AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $assessmentId);
            /*
             * DEFAULT LOAD PROCESSING GOES HERE
             * load filter.  Need earlier than usual header.php load
             */
            $curdir = rtrim(dirname(__FILE__), '/\\');
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
            $apointstot = AppConstant::NUMERIC_ZERO;
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
                    //output item array
                    $jsArray .= '[' . $subs[$j] . ',' . $line['questionsetid'] . ',"' . addslashes(filter(str_replace(array("\r\n", "\n", "\r"), " ", $line['description']))) . '","' . $line['qtype'] . '",' . $line['points'] . ',';
                    if ($line['userights'] > 3 || ($line['userights'] == 3 && $line['groupid'] == $groupId) || $line['ownerid'] == $userId || $adminAsTeacher) { //can edit without template?
                        $jsArray .= '1';
                    } else {
                        $jsArray .= '0';
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
                            $extRefVal += 16;
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
                        $jsArray .= '0';
                    } else {
                        $jsArray .= '1';
                    }
                    $jsArray .= ']';
                }
//                $alt = 1 - $alt;
                unset($subs);
            }
            $jsArray .= ']';
            /*
             * DATA MANIPULATION FOR POTENTIAL QUESTIONS
             */
            if ($sessionData['selfrom' . $assessmentId] == 'lib') { //selecting from libraries
                /*
                 * remember search
                 */
                if (isset($params['search'])) {
                    $safeSearch = $params['search'];
                    $safeSearch = str_replace(' and ', ' ', $safeSearch);
                    $search = stripslashes($safeSearch);
                    $search = str_replace('"', '&quot;', $search);
                    $sessionData['lastsearch' . $courseId] = $safeSearch; ///str_replace(" ","+",$safeSearch);
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
                    $safeSearch = '';
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($sessionData['lastsearchlibs' . $assessmentId])) {
                    $searchLibs = $sessionData['lastsearchlibs' . $assessmentId];
                } else {
                    if (isset($CFG['AMS']['guesslib']) && count($existingQuestion) > AppConstant::NUMERIC_ZERO) {
                        $maj = count($existingQuestion) / AppConstant::NUMERIC_TWO;
                        $existingQList = implode(',', $existingQuestion);  //pulled from database, so no quotes needed
                        /*
                         * Work to do for fetching library items
                         */
                        $query = LibraryItems::getByQuestionSetId($existingQList);
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
                $lList = "'" . implode("','", explode(',', $searchLibs)) . "'";

                if (!$beenTaken) {
                    //potential questions
                    $libSortOrder = array();
                    if (substr($searchLibs, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE) == "0") {
                        $lNamesArray[0] = "Unassigned";
                        $libSortOrder[0] = AppConstant::NUMERIC_ZERO;
                    }

                    $query = Libraries::getByIdList(explode(',', $searchLibs));
                    foreach ($query as $row) {
                        $lNamesArray[$row['id']] = $row['name'];
                        $libSortOrder[$row['id']] = $row['sortorder'];
                    }

                    $lNames = implode(", ", $lNamesArray);
                    $pageLibRowHeader = ($searchAll == AppConstant::NUMERIC_ONE) ? "<th>Library</th>" : "";

                    if (isset($search)) {
                        $result = QuestionSet::getByUserIdJoin($searchAll, $userId, $lList, $searchMine, $searchLikes);
                        if ($search == 'recommend' && count($existingQuestion) > AppConstant::NUMERIC_ZERO) {
                            $existingQList = implode(',', $existingQuestion);  //pulled from database, so no quotes needed
                            $result = QuestionSet::getByUserId($assessmentId, $userId, $existingQList);
                        }
                        if ($result == AppConstant::NUMERIC_ZERO) {
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
                                } else { //id
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
                                    $pageQuestionTable[$i]['avgtime'] = round($avgTimePts[0] / 60, AppConstant::NUMERIC_ONE);
                                } else if (isset($avgTimePts[1]) && isset($avgTimePts[3]) && $avgTimePts[3] > 10) {
                                    $pageUseAvgTimes = true;
                                    $pageQuestionTable[$i]['avgtime'] = round($avgTimePts[1] / 60, AppConstant::NUMERIC_ONE);
                                } else {
                                    $pageQuestionTable[$i]['avgtime'] = '';
                                }
                                if (isset($avgTimePts[3]) && $avgTimePts[3] > 10) {
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
                                    $pageQuestionTable[$i]['src'] = "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-data-set?id=' . $line['id'] . '&aid=' . $assessmentId . '&cid=' . $courseId . '&frompot=1') . "><i class='fa fa-fw'></i>Edit</a>";
                                } else {
                                    $pageQuestionTable[$i]['src'] = "<a href=" . AppUtility::getURLFromHome('question', 'question/view-source?id=' . $line['id'] . '&aid=' . $assessmentId . '&cid=' . $courseId) . ">View</a>";
                                }

                                $pageQuestionTable[$i]['templ'] = "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-data-set?id=' . $line['id'] . '&aid=' . $assessmentId . '&cid=' . $courseId . '&template=' . true) . "><i class='fa fa-archive'></i>Template</a>";
                                $ln++;

                            } //end while
                            /*
                             * pull question useage data
                             */
                            if (count($pageQuestionTable) > AppConstant::NUMERIC_ZERO) {
                                $allUsedQids = implode(',', array_keys($pageQuestionTable));
                                $query = Questions::getByQuestionSetId($allUsedQids);
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
                            $descr[$row['id']] = $row['description'];
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
                                $pageAssessmentQuestions[$x]['desc'][$y] = '<span style="color: #999">' . filter($descr[$qid]) . '</span>';
                            } else {
                                $pageAssessmentQuestions[$x]['desc'][$y] = filter($descr[$qid]);
                            }
                            //$pageAssessmentQuestions[$x]['desc'][$y] = $descr[$qid];
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
                    $pageAssessmentList = $this->addtoassessmentlist($items, $i, $itemAssoc);
                }
            }
        }
        $this->includeCSS(['question/question.css', 'course/course.css', 'roster/roster.css']);
        $this->includeJS(['jquery.min.js', 'question/addqsort.js', 'question/addquestions.js', 'tablesorter.js', 'general.js', 'question/junkflag.js']);
        $responseArray = array('course' => $course, 'assessmentId' => $assessmentId, 'params' => $params, 'overwriteBody' => $overwriteBody, 'body' => $body,
            'defpoints' => $defPoints, 'searchlibs' => $searchLibs, 'beentaken' => $beenTaken, 'pageAssessmentName' => $pageAssessmentName,
            'itemorder' => $itemOrder, 'sessiondata' => $sessionData, 'jsarr' => $jsArray, 'displaymethod' => $displayMethod, 'lnames' => $lNames,
            'search' => $search, 'searchall' => $searchAll, 'searchmine' => $searchMine, 'newonly' => $newOnly, 'noSearchResults' => $noSearchResults,
            'pageLibRowHeader' => $pageLibRowHeader, 'pageUseavgtimes' => $pageUseAvgTimes, 'pageLibstouse' => $pageLibsToUse, 'altr' => $alt,
            'lnamesarr' => $lNamesArray, 'pageLibqids' => $pageLibQIds, 'pageQuestionTable' => $pageQuestionTable, 'qid' => $qid,
            'pageAssessmentQuestions' => $pageAssessmentQuestions, 'pageAssessmentList' => $pageAssessmentList, 'address' => $address);
        return $this->renderWithData('addQuestions', $responseArray);
    }

    public function addtoassessmentlist($items, $i, $itemAssoc)
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->addtoassessmentlist($item['items'], $i, $itemAssoc);
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

    public function actionSaveQuestions()
    {
        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress'));
    }

    public function actionAddVideoTimes()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $isTeacher = $this->isTeacher($user['id'],$courseId);
        $aid = $this->getParamVal('aid');
        $params = $this->getRequestParams();
        $course = Course::getById($courseId);
        $this->layout = 'master';
        if(!($isTeacher))
        {
            $body =  AppConstant::NO_ACCESS_RIGHTS;
        }
        if(isset($params['vidid']))
        {
            $params = stripslashes_deep($params);
            $vidId = $params['vidid'];
            $data = array();
            $i = AppConstant::NUMERIC_ZERO;
            while (isset($params['segtitle'.$i]))
            {
                $n = array();
                $n[0] = trim(htmlentities($params['segtitle'.$i]));
                $thisTime = $this->timeToSec($params['segend'.$i]);
                $n[1] = $thisTime;
                if (isset($params['qn'.$i]))
                {
                    $n[2] = $params['qn'.$i];
                }
                if (isset($params['hasfollowup'.$i]))
                {
                    $n[3] = $this->timeToSec($_POST['followupend'.$i]);

                    if (isset($params['showlink'.$i]))
                    {
                        $n[4] = true;
                    }
                    else
                    {
                        $n[4] = false;
                    }
                    $n[5] = trim(htmlentities($params['followuptitle'.$i]));
                }
                $data[$thisTime] = $n;
                $i++;
            }
            ksort($data);
            $data = array_values($data);
            array_unshift($data, $vidId);
            if (trim($params['finalseg'])!='')
            {
                array_push($data, array(htmlentities($params['finalseg'])));
            }
            $data = serialize($data);
            Assessments::updateVideoCued($data,$aid);
            return $this->redirect('add-questions?cid='.$courseId.'&aid='.$aid);
        }
        $toCopy =  'itemorder,viddata';
        $assessmentData = Assessments::CommonMethodToGetAssessmentData($toCopy,$aid);
        $qOrder = explode(',',$assessmentData['itemorder']);
        $vidData = $assessmentData['viddata'];
        $qidByNum = array();
        for ($i=0;$i<count($qOrder);$i++)
        {
            if (strpos($qOrder[$i],'~')!==false)
            {
                $qIds = explode('~',$qOrder[$i]);
                if (strpos($qIds[0],'|')!==false)
                {
                    $qidByNum[$i] = $qIds[1];
                }
                else
                {
                    $qidByNum[$i] = $qIds[0];
                }
            }
            else
            {
                $qidByNum[$i] = $qOrder[$i];
            }
        }
        $qTitleById = array();
        $QuestionSetData = Questions::getDataByJoin($aid);
        if($QuestionSetData)
        {
            foreach($QuestionSetData as $row)
            {
                if (strlen($row['description']) < 30)
                {
                    $qTitle[$row['id']] = $row['description'];
                }
                else
                {
                    $qTitle[$row['id']] = substr($row['description'],0,30).'...';
                }
            }
        }
        if ($vidData != '')
        {
            $data = unserialize($vidData);
            $vidId = array_shift($data);
            $n = count($data);
            $title = array();
            $endTime = array();
            $qn = array();
            $followUpTitle = array();
            $followUpEndDTime = array();
            $hasFollowUp = array();
            $showLink = array();
            $finalSegTitle;
            for ($i=0;$i<$n;$i++)
            {
                $title[$i] = $data[$i][0];
                if (count($data[$i])==1)
                {
                    $finalSegTitle = $data[$i][0];
                    $n--;
                }
                else
                {
                    $endTime[$i] = $this->secToTime($data[$i][1]);
                }
                if (count($data[$i])>2)
                {
                    $qn[$i] = $data[$i][2];
                    if (count($data[$i])>3)
                    {
                        $followUpTitle[$i] = $data[$i][5];
                        $followUpEndDTime[$i] = $this->secToTime($data[$i][3]);
                        $showLink[$i] = $data[$i][4];
                        $hasFollowUp[$i] = true;
                    }
                    else
                    {
                        $hasFollowUp[$i] = false;
                        $followUpTitle[$i] = '';
                        $followUpEndDTime[$i] = '';
                        $showLink[$i] = true;
                    }
                }
            }
        }
        else
        {
            $n = count($qOrder);
            $title = array_fill(0, $n, '');
            $endTime = array_fill(0,$n, '');
            $qn = range(0, $n-1);
            $followUpTitle = array_fill(0, $n, '');
            $followUpEndDTime = array_fill(0,$n, '');
            $showLink = array_fill(0, $n, true);
            $finalSegTitle = '';
            $vidId = '';
        }
        $this->includeJS(['editor/plugins/media/js/embed.js']);
        $responseData = array('n' => $n,'qn' => $qn,'title' => $title,'endTime' => $endTime,'qTitle' => $qTitle,'qidByNum' => $qidByNum,'hasFollowUp' => $hasFollowUp,'followUpTitle' => $followUpTitle,'showLink' => $showLink,'finalSegTitle' => $finalSegTitle,'followUpEndDTime' => $followUpEndDTime,'vidId' => $vidId,'course' => $course,'courseId' => $courseId,'aid' => $aid);
        return $this->renderWithData('AddVideoTimes',$responseData);
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
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $courseId));
        }
        $query = Outcomes::getByCourse($courseId);
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
            $this->flattenarr($outcomeArray);
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
                $itemArray[$k] = substr($v, $p + 1);
            }
        }
        $itemArray = implode(',', $itemArray);
        $itemArray = str_replace('~', ',', $itemArray);
        $itemArray = explode(',', $itemArray);
        $this->includeJS(['question/categorize.js']);
        $responseArray = array('cid' => $courseId, 'aid' => $assessmentId, 'itemarr' => $itemArray, 'descriptions' => $descriptions, 'category' => $category,
            'outcomes' => $outcomes, 'outcomenames' => $outcomeNames, 'questionlibs' => $questionLibs, 'libnames' => $libNames,
            'extracats' => $extraCats, 'course' => $course);
        return $this->renderWithData('categorize', $responseArray);
    }

    public function flattenarr($ar)
    {
        global $outcomes;
        foreach ($ar as $v) {
            if (is_array($v)) {
                /*
                 * outcome group
                 */
                $outcomes[] = array($v['name'], AppConstant::NUMERIC_ONE);
                $this->flattenarr($v['outcomes']);
            } else {
                $outcomes[] = array($v, AppConstant::NUMERIC_ZERO);
            }
        }
    }

    public function actionPrintTest()
    {
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $this->layout = 'master';
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($user['id'], $courseId);
        //set some page specific variables and counters
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        //CHECK PERMISSIONS AND SET FLAGS
        if (!(isset($teacherId))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {    //PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
            $assessmentId = $this->getParamVal('aid');
        }
        $renderData = array('course' => $course, 'courseId' => $courseId, 'assessmentId' => $assessmentId, 'overwriteBody' => $overwriteBody,
            'params' => $params, 'body' => $body);
        return $this->renderWithData('printTest', $renderData);
    }

    public function actionLibraryTree()
    {
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $myRights = $user['rights'];
        $libraryData = Libraries::getAllLibrariesByJoin();
        $this->includeCSS(['question/libtree.css']);
        $this->includeJS(['general.js', 'question/libtree2.js']);
        $renderData = array('myRights' => $myRights, 'params' => $params, 'libraryData' => $libraryData);
        return $this->renderWithData('questionLibraries', $renderData);
    }

    public function actionModDataSet()
    {
        $user = $this->getAuthenticatedUser();
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
            echo AppConstant::NO_TEACHER_RIGHTS;
            exit;
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
            $frompot = AppConstant::NUMERIC_ONE;
        } else {
            $frompot = AppConstant::NUMERIC_ZERO;
        }

        $outputmsg = '';
        $errmsg = '';

        $course = Course::getById($courseId);
        if (isset($params['qtext'])) {
//            require("../includes/filehandler.php");
            $now = time();
            $params['qtext'] = $this->stripsmartquotes(stripslashes($params['qtext']));
            $params['control'] = addslashes($this->stripsmartquotes(stripslashes($params['control'])));
            $params['qcontrol'] = addslashes($this->stripsmartquotes(stripslashes($params['qcontrol'])));
            $params['solution'] = $this->stripsmartquotes(stripslashes($params['solution']));
            $params['qtext'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm', '$1', $params['qtext']);
            $params['solution'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm', '$1', $params['solution']);

            if (trim($params['solution']) == '<p></p>') {
                $params['solution'] = '';
            }

            if (strpos($params['qtext'], 'data:image') !== false) {
                require("../includes/htmLawed.php");
                $params['qtext'] = convertdatauris($params['qtext']);
            }
            $params['qtext'] = addslashes($params['qtext']);
            $params['solution'] = addslashes($params['solution']);

            //handle help references
            if (isset($params['id']) || isset($params['templateid'])) {
                if (isset($params['id'])) {
                    $query = QuestionSet::getQuestionDataById($params['id']);
                } else {
                    $query = QuestionSet::getQuestionDataById($params['templateid']);
                }
                $extRef = $query['extref'];
                if ($extRef == '') {
                    $extRef = array();
                } else {
                    $extRef = explode('~~', $extRef);
                }

                $newextref = array();
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($extRef); $i++) {
                    if (!isset($params["delhelp-$i"])) {
                        $newextref[] = $extRef[$i];
                    }
                }
            } else {
                $newextref = array();
            }
            //DO we need to add a checkbox or something for updating this if captions are added later?
            if ($params['helpurl'] != '') {
                $vidid = $this->getvideoid($params['helpurl']);
                if ($vidid == '') {
                    $captioned = AppConstant::NUMERIC_ZERO;
                } else {
                    $ctx = stream_context_create(array('http' =>
                        array(
                            'timeout' => AppConstant::NUMERIC_ONE
                        )
                    ));
                    $t = @file_get_contents('http://video.google.com/timedtext?lang=en&v=' . $vidid, false, $ctx);
                    $captioned = ($t == '') ? AppConstant::NUMERIC_ZERO : AppConstant::NUMERIC_ONE;
                }
                $newextref[] = $params['helptype'] . '!!' . $params['helpurl'] . '!!' . $captioned;
            }
            $extRef = implode('~~', $newextref);
            if (isset($params['doreplaceby'])) {
                $replaceby = intval($params['replaceby']);
            } else {
                $replaceby = AppConstant::NUMERIC_ZERO;
            }
            $solutionopts = AppConstant::NUMERIC_ZERO;
            if (isset($params['usesrand'])) {
                $solutionopts += AppConstant::NUMERIC_ONE;
            }
            if (isset($params['useashelp'])) {
                $solutionopts += AppConstant::NUMERIC_TWO;
            }
            if (isset($params['usewithans'])) {
                $solutionopts += AppConstant::NUMERIC_FOUR;
            }
            $params['qtext'] = preg_replace('/<([^<>]+?)>/', "&&&L$1&&&G", $params['qtext']);
            $params['qtext'] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $params['qtext']);
            $params['qtext'] = str_replace(array("&&&L", "&&&G"), array("<", ">"), $params['qtext']);
            $params['solution'] = preg_replace('/<([^<>]+?)>/', "&&&L$1&&&G", $params['solution']);
            $params['solution'] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $params['solution']);
            $params['solution'] = str_replace(array("&&&L", "&&&G"), array("<", ">"), $params['solution']);
            $params['description'] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $params['description']);

            if (isset($params['id'])) { //modifying existing
                $questionSetId = intval($params['id']);
                $isok = true;
                if ($isGrpAdmin) {
                    $query = QuestionSet::getByGroupId($params['id'], $groupId);
                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                        $isok = false;
                    }
                }
                if (!$isAdmin && !$isGrpAdmin) {  //check is owner or is allowed to modify
                    $query = QuestionSet::getByUserIdGroupId($params['id'], $userId, $groupId);
                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                        $isok = false;
                    }
                }
                $query = QuestionSet::updateQuestionSet($params, $now, $extRef, $replaceby, $solutionopts);

                //checked separately above now
                if ($isok) {
                    if (count($query) > AppConstant::NUMERIC_ZERO) {
                        $outputmsg .= "Question Updated. ";
                    } else {
                        $outputmsg .= "Library Assignments Updated. ";
                    }
                }
                $query = QImages::getByQuestionSetId($params['id']);
                $imgcnt = count($query);
                foreach ($query as $row) {
                    if (isset($params['delimg-' . $row['id']])) {
                        $file = QImages::getByFileName($row['filename']);
                        if (count($file) == AppConstant::NUMERIC_ONE) { //don't delete if file is used in other questions
                            filehandler::deleteqimage($row['filename']);
                        }
                        QImages::deleteById($row['id']);
                        $imgcnt--;
                        if ($imgcnt == AppConstant::NUMERIC_ZERO) {
                            QuestionSet::setHasImage($params['id'], AppConstant::NUMERIC_ZERO);
                        }
                    } else if ($row['var'] != $params['imgvar-' . $row['id']] || $row['alttext'] != $params['imgalt-' . $row['id']]) {
                        $newvar = str_replace('$', '', $params['imgvar-' . $row['id']]);
                        $newalt = $params['imgalt-' . $row['id']];
                        $disallowedvar = array('link', 'qidx', 'qnidx', 'seed', 'qdata', 'toevalqtxt', 'la', 'GLOBALS', 'laparts', 'anstype', 'kidx', 'iidx', 'tips', 'options', 'partla', 'partnum', 'score');
                        if (in_array($newvar, $disallowedvar)) {
                            $errmsg .= "<p>$newvar is not an allowed variable name</p>";
                        } else {
                            QImages::setVariableAndText($row['id'], $newvar, $newalt);
                        }
                    }
                }
                if ($replaceby != AppConstant::NUMERIC_ZERO) {
                    Questions::setQuestionSetId($questionSetId, $replaceby);
                }
            } else { //adding new
                $mt = microtime();
                $uqid = substr($mt, AppConstant::NUMERIC_ELEVEN) . substr($mt, AppConstant::NUMERIC_TWO, AppConstant::NUMERIC_SIX);
                $ancestors = '';
                $ancestorauthors = '';
                if (isset($params['templateid'])) {
                    $query = QuestionSet::getQuestionDataById($params['templateid']);
                    $ancestors = $query['ancestors'];
                    $lastauthor = $query['author'];
                    $ancestorauthors = $query['ancestorauthors'];
                    if ($ancestors != '') {
                        $ancestors = intval($params['templateid']) . ',' . $ancestors;
                    } else {
                        $ancestors = intval($params['templateid']);
                    }
                    if ($ancestorauthors != '') {
                        $aaarr = explode('; ', $ancestorauthors);
                        if (!in_array($lastauthor, $aaarr)) {
                            $ancestorauthors = $lastauthor . '; ' . $ancestorauthors;
                        }
                    } else if ($lastauthor != $params['author']) {
                        $ancestorauthors = $lastauthor;
                    }
                }
                $ancestorauthors = addslashes($ancestorauthors);
                $questionSetArray = array();
                $questionSetArray['uniqueid'] = $uqid;
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
                $questionSetArray['ancestors'] = $ancestors.'';
                $questionSetArray['ancestorauthors'] = $ancestorauthors;
                $questionSetArray['extref'] = $extRef;
                $questionSetArray['replaceby'] = $replaceby;
                $questionSetArray['solution'] = $params['solution'];
                $questionSetArray['solutionopts'] = $solutionopts;

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
                    $outputmsg .= AppConstant::Question_OUTPUT_MSG1;
                    $frompot = AppConstant::NUMERIC_ZERO;
                } else {
                    $outputmsg .= AppConstant::Question_OUTPUT_MSG2;
                    $frompot = AppConstant::NUMERIC_ONE;
                }
            }
            //upload image files if attached
            if ($_FILES['imgfile']['name'] != '') {
                $disallowedvar = array('link', 'qidx', 'qnidx', 'seed', 'qdata', 'toevalqtxt', 'la', 'GLOBALS', 'laparts', 'anstype', 'kidx', 'iidx', 'tips', 'options', 'partla', 'partnum', 'score');
                if (trim($params['newimgvar']) == '') {
                    $errmsg .= AppConstant::IMAGE_FILE_ERROR1;
                } else if (in_array($params['newimgvar'], $disallowedvar)) {
                    $errmsg .= $newvar . AppConstant::IMAGE_FILE_ERROR2;
                } else {
                    $uploaddir = AppConstant::UPLOAD_DIRECTORY . 'qimages/';
                    $userfilename = preg_replace('/[^\w\.]/', '', basename($_FILES['imgfile']['name']));
                    $filename = $userfilename;

                    $result_array = getimagesize($_FILES['imgfile']['tmp_name']);
                    if ($result_array === false) {
                        $errmsg .= "<p>File is not image file</p>";
                    } else {
                        if (($filename = filehandler::storeuploadedqimage('imgfile', $filename)) !== false) {
                            $params['newimgvar'] = str_replace('$', '', $params['newimgvar']);
                            $filename = addslashes($filename);
                            $questImageData = array();
                            $questImageData['var'] = $params['newimgvar'];
                            $questImageData['filename'] = $filename;
                            $questImageData['alttext'] = $params['newimgalt'];
                            $qImage = new QImages();
                            $qImage->createQImages($questionSetId, $questImageData);
                            QuestionSet::setHasImage($questionSetId, AppConstant::NUMERIC_ONE);
                        } else {
                            echo "<p>Error uploading image file!</p>\n";
                            exit;
                        }
                    }
                }
            }
            //update libraries
            $newlibs = explode(",", $params['libs']);

            if (in_array(AppConstant::ZERO_VALUE, $newlibs) && count($newlibs) > AppConstant::NUMERIC_ONE) {
                array_shift($newlibs);
            }

            if ($params['libs'] == '') {
                $newlibs = array();
            }
            $libraryData = LibraryItems::getByGroupId($groupId, $questionSetId, $userId, $isGrpAdmin, $isAdmin);
            $existing = array();
            foreach ($libraryData as $row) {
                $existing[] = $row['libid'];
            }

            $toadd = array_values(array_diff($newlibs, $existing));
            $toRemove = array_values(array_diff($existing, $newlibs));

            while (count($toRemove) > AppConstant::NUMERIC_ZERO && count($toadd) > AppConstant::NUMERIC_ZERO) {
                $tochange = array_shift($toRemove);
                $torep = array_shift($toadd);
                LibraryItems::setLibId($torep, $questionSetId, $tochange);
            }
            if (count($toadd) > AppConstant::NUMERIC_ZERO) {
                foreach ($toadd as $libId) {
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
            if (count($newlibs) == AppConstant::NUMERIC_ZERO) {
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
                $outputmsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $courseId) . ">Return to Question Set Management</a>\n";
            } else {
                if ($frompot == AppConstant::NUMERIC_ONE) {
                    $outputmsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-question?qsetid=' . $questionSetId . '&cid=' . $courseId . '&aid=' . $params['aid'] . '&process=true&usedef=true') . ">Add Question to Assessment using Defaults</a> | \n";
                    $outputmsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/mod-question?qsetid=' . $questionSetId . '&cid=' . $courseId . '&aid=' . $params['aid']) . ">Add Question to Assessment</a> | \n";
                }
                $outputmsg .= "<a href=" . AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $params['aid']) . ">Return to Assessment</a>\n";
            }
            if ($params['test'] == "Save and Test Question") {
                $outputmsg .= "<script>addr = '" . AppUtility::getURLFromHome('question', 'question/test-question?cid=' . $courseId . '&qsetid=' . $params['id']) . "';";
                $outputmsg .= "previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));\n";
                $outputmsg .= "previewpop.focus();";
                $outputmsg .= "</script>";
            } else {
                if ($errmsg == '' && !isset($params['aid'])) {
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $courseId));
                } else if ($errmsg == '' && $frompot == AppConstant::NUMERIC_ZERO) {
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/add-questions?cid=' . $courseId . '&aid=' . $params['aid']));
                } else {
                    echo $errmsg;
                    echo $outputmsg;
                }
                exit;
            }
        }
        $myname = $user['LastName'] . ',' . $user['FirstName'];
        if (isset($params['id'])) {
            $line = QuestionSet::getByQSetIdJoin($params['id']);
            $myq = ($line['ownerid'] == $userId);
            if ($isAdmin || ($isGrpAdmin && $line['groupid'] == $groupId) || ($line['userights'] == AppConstant::NUMERIC_THREE && $line['groupid'] == $groupId) || $line['userights'] > AppConstant::NUMERIC_THREE) {
                $myq = true;
            }
            $namelist = explode(", mb ", $line['author']);
            if ($myq && !in_array($myname, $namelist)) {
                $namelist[] = $myname;
            }
            if (isset($params['template'])) {
                $author = $myname;
                $myq = true;
            } else {
                $author = implode(", mb ", $namelist);
            }
            foreach ($line as $k => $v) {
                $line[$k] = str_replace('&', '&amp;', $v);
            }

            $inlibs = array();
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
                $deflib = $user['deflib'];
                $usedeflib = $user['usedeflib'];

                if (isset($params['makelocal'])) {
                    $inlibs[] = $deflib;
                    $line['description'] .= " (local for $userFullName)";
                } else {
                    $line['description'] .= " (copy by $userFullName)";
                    if ($usedeflib == AppConstant::NUMERIC_ONE) {
                        $inlibs[] = $deflib;
                    } else {
                        $query = Libraries::getByQSetId($params['id']);
                        foreach ($query as $row) {
                            if ($row['userights'] == AppConstant::NUMERIC_EIGHT || ($row['groupid'] == $groupId && ($row['userights'] % AppConstant::NUMERIC_THREE == AppConstant::NUMERIC_TWO)) || $row['ownerid'] == $userId) {
                                $inlibs[] = $row['id'];
                            }
                        }
                    }
                }
                $locklibs = array();
                $addmod = "Add";
                $line['userights'] = $user['qrightsdef'];

            } else {
                $query = LibraryItems::getDestinctLibIdByIdAndOwner($groupId, $params['id'], $userId, $isGrpAdmin, $isAdmin);
                foreach ($query as $row) {
                    $inlibs[] = $row['libid'];
                }

                $locklibs = array();
                if (!$isAdmin) {
                    $query = LibraryItems::getLibIdByQidAndOwner($groupId, $params['id'], $userId, $isGrpAdmin, $isAdmin);
                    foreach ($query as $row) {
                        $locklibs[] = $row['libid'];
                    }
                }
                $addmod = "Modify";
                $inusecnt = Questions::getQidCount($userId, $params['id']);
            }

            if (count($inlibs) == AppConstant::NUMERIC_ZERO && count($locklibs) == AppConstant::NUMERIC_ZERO) {
                $inlibs = array(AppConstant::NUMERIC_ZERO);
            }
            $inlibs = implode(",", $inlibs);
            $locklibs = implode(",", $locklibs);

            $twobx = ($line['qcontrol'] == '' && $line['answer'] == '');

            $line['qtext'] = preg_replace('/<span class="AM">(.*?)<\/span>/', '$1', $line['qtext']);
        } else {
            $myq = true;
            $twobx = true;
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
                $inlibs = $sessionData['lastsearchlibs' . $params['aid']];
            } else if (isset($sessionData['lastsearchlibs' . $courseId])) {
                $inlibs = $sessionData['lastsearchlibs' . $courseId];
            } else {
                $inlibs = $userDefLib;
            }
            $locklibs = '';
            $images = array();
            $extRef = array();
            $author = $myname;
            $inlibssafe = "'" . implode("','", explode(',', $inlibs)) . "'";
            if (!isset($params['id']) || isset($params['template'])) {
                $query = Libraries::getByIdList($inlibssafe);
                foreach ($query as $row) {
                    if ($row['userights'] == AppConstant::NUMERIC_EIGHT || ($row['groupid'] == $groupId && ($row['userights'] % AppConstant::NUMERIC_THREE == AppConstant::NUMERIC_TWO)) || $row['ownerid'] == $userId) {
                        $oklibs[] = $row['id'];
                    }
                }
                if (count($oklibs) > AppConstant::NUMERIC_ZERO) {
                    $inlibs = implode(",", $oklibs);
                } else {
                    $inlibs = AppConstant::ZERO_VALUE;
                }
            }
            $addmod = "Add";
        }
        $inlibssafe = "'" . implode("','", explode(',', $inlibs)) . "'";

        $lNames = array();
        if (substr($inlibs, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE) === AppConstant::ZERO_VALUE) {
            $lNames[] = "Unassigned";
        }
        $inlibssafe = "'" . implode("','", explode(',', $inlibs)) . "'";
        $query = Libraries::getByIdList($inlibssafe);
        foreach ($query as $row) {
            $lNames[] = $row['name'];
        }
        $lNames = implode(", ", $lNames);
        $this->includeJS(['general.js', 'question/modDataSet.js', 'editor/tiny_mce.js', 'ASCIIMathTeXImg_min.js']);
        $renderData = array('course' => $course, 'addMode' => $addmod, 'params' => $params, 'inusecnt' => $inusecnt, 'line' => $line, 'myq' => $myq,
            'frompot' => $frompot, 'author' => $author, 'userId' => $userId, 'groupId' => $groupId, 'isAdmin' => $isAdmin, 'isGrpAdmin' => $isGrpAdmin,
            'inlibs' => $inlibs, 'locklibs' => $locklibs, 'lnames' => $lNames, 'twobx' => $twobx, 'images' => $images, 'extref' => $extRef,'courseId' => $courseId,
            'myRights' => $myRights, 'sessionData' => $sessionData);
        return $this->renderWithData('modDataSet', $renderData);
    }

    public function stripsmartquotes($text)
    {
        $text = str_replace(
            array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text);
        return $text;
    }

    public function getvideoid($url)
    {
        $vidid = '';
        if (strpos($url, 'youtube.com/watch') !== false) {
            //youtube
            $vidid = substr($url, strrpos($url, 'v=') + 2);
            if (strpos($vidid, '&') !== false) {
                $vidid = substr($vidid, AppConstant::NUMERIC_ZERO, strpos($vidid, '&'));
            }
            if (strpos($vidid, '#') !== false) {
                $vidid = substr($vidid, AppConstant::NUMERIC_ZERO, strpos($vidid, '#'));
            }
            $vidid = str_replace(array(" ", "\n", "\r", "\t"), '', $vidid);
        } else if (strpos($url, 'youtu.be/') !== false) {
            //youtube
            $vidid = substr($url, strpos($url, '.be/') + AppConstant::NUMERIC_FOUR);
            if (strpos($vidid, '#') !== false) {
                $vidid = substr($vidid, AppConstant::NUMERIC_ZERO, strpos($vidid, '#'));
            }
            if (strpos($vidid, '?') !== false) {
                $vidid = substr($vidid, AppConstant::NUMERIC_ZERO, strpos($vidid, '?'));
            }
            $vidid = str_replace(array(" ", "\n", "\r", "\t"), '', $vidid);
        }
        return $vidid;
    }

    public function actionModTutorialQuestion()
    {
    }

    public function actionModQuestion()
    {
        $user = $this->getAuthenticatedUser();
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
         * CHECK PERMISSIONS AND SET FLAGS
         */
        if (!(isset($teacherId))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {/*
                 * PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
	             */
            if ($params['process'] == true) {
                if (isset($params['usedef'])) {
                    $points = AppConstant::QUARTER_NINE;
                    $attempts = AppConstant::QUARTER_NINE;
                    $penalty = AppConstant::QUARTER_NINE;
                    $regen = AppConstant::NUMERIC_ZERO;
                    $showans = AppConstant::NUMERIC_ZERO;
                    $rubric = AppConstant::NUMERIC_ZERO;
                    $showhints = AppConstant::NUMERIC_ZERO;
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
                        $penalty = AppConstant::QUARTER_NINE;
                    } else {
                        $penalty = intval($params['penalty']);
                    }
                    if ($penalty != AppConstant::QUARTER_NINE) {
                        if ($params['skippenalty'] == AppConstant::NUMERIC_TEN) {
                            $penalty = 'L' . $penalty;
                        } else if ($params['skippenalty'] > AppConstant::NUMERIC_ZERO) {
                            $penalty = 'S' . $params['skippenalty'] . $penalty;
                        }
                    }
                    $regen = $params['regen'] + AppConstant::NUMERIC_THREE * $params['allowregen'];
                    $showans = $params['showans'];
                    $rubric = intval($params['rubric']);
                    $showhints = intval($params['showhints']);
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
                $questionArray['showhints'] = $showhints;
                $questionArray['assessmentid'] = $assessmentId;
                if (isset($params['id'])) { //already have id - updating
                    if (isset($params['replacementid']) && $params['replacementid'] != '' && intval($params['replacementid']) != AppConstant::NUMERIC_ZERO) {
                        $questionArray['questionsetid'] = intval($params['replacementid']);
                    }
                    Questions::updateQuestionFields($questionArray, $params['id']);
                    if (isset($params['copies']) && $params['copies'] > AppConstant::NUMERIC_ZERO) {
                        $query = Questions::getById($params['id']);
                        $params['qsetid'] = $query['questionsetid'];
                    }
                }
                if (isset($params['qsetid'])) { //new - adding
                    $query = Assessments::getByAssessmentId($assessmentId);
                    $itemOrder = $query['itemorder'];
                    $questionArray['questionsetid'] = $params['qsetid'];
                    for ($i = AppConstant::NUMERIC_ZERO; $i < $params['copies']; $i++) {
                        $question = new Questions();
                        $qid = $question->addQuestions($questionArray);
                        //add to itemorder
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
            } else { //DEFAULT DATA MANIPULATION
                if (isset($params['id'])) {
                    $line = Questions::getById($params['id']);
                    if ($line['penalty']{AppConstant::NUMERIC_ZERO} === 'L') {
                        $line['penalty'] = substr($line['penalty'], AppConstant::NUMERIC_ONE);
                        $skippenalty = AppConstant::NUMERIC_TEN;
                    } else if ($line['penalty']{AppConstant::NUMERIC_ZERO} === 'S') {
                        $skippenalty = $line['penalty']{AppConstant::NUMERIC_ONE};
                        $line['penalty'] = substr($line['penalty'], AppConstant::NUMERIC_TWO);
                    } else {
                        $skippenalty = AppConstant::NUMERIC_ZERO;
                    }

                    if ($line['points'] == AppConstant::QUARTER_NINE) {
                        $line['points'] = '';
                    }
                    if ($line['attempts'] == AppConstant::QUARTER_NINE) {
                        $line['attempts'] = '';
                    }
                    if ($line['penalty'] == AppConstant::QUARTER_NINE) {
                        $line['penalty'] = '';
                    }
                } else {
                    //set defaults
                    $line['points'] = "";
                    $line['attempts'] = "";
                    $line['penalty'] = "";
                    $skippenalty = AppConstant::NUMERIC_ZERO;
                    $line['regen'] = AppConstant::NUMERIC_ZERO;
                    $line['showans'] = AppConstant::ZERO_VALUE;
                    $line['rubric'] = AppConstant::NUMERIC_ZERO;
                    $line['showhints'] = AppConstant::NUMERIC_ZERO;
                }

                $rubric_vals = array(AppConstant::NUMERIC_ZERO);
                $rubric_names = array('None');
                $query = Rubrics::getIdAndName($userId, $user['groupid']);
                foreach ($query as $row) {
                    $rubric_vals[] = $row['id'];
                    $rubric_names[] = $row['name'];
                }
                $query = AssessmentSession::getAssessmentIDs($assessmentId, $courseId);
                if (count($query) > AppConstant::NUMERIC_ZERO) {
                    $pageBeenTakenMsg = "<h3>Warning</h3>\n";
                    $pageBeenTakenMsg .= "<p>This assessment has already been taken.  Altering the points or penalty will not change the scores of students who already completed this question. ";
                    $pageBeenTakenMsg .= "If you want to make these changes, or add additional copies of this question, you should clear all existing assessment attempts</p> ";
                    $pageBeenTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='add-questions?cid=$courseId&aid=$assessmentId&clearattempts=ask'\"></p>\n";
                    $beenTaken = true;
                } else {
                    $beenTaken = false;
                }
            }
        }
        $renderData = array('course' => $course, 'overwriteBody' => $overwriteBody, 'body' => $body, 'pageBeenTakenMsg' => $pageBeenTakenMsg,
            'courseId' => $courseId, 'assessmentId' => $assessmentId, 'beentaken' => $beenTaken, 'params' => $params, 'skippenalty' => $skippenalty,
            'line' => $line, 'rubricNames' => $rubric_names, 'rubricVals' => $rubric_vals);
        return $this->renderWithData('modQuestion', $renderData);
    }

    public function actionTestQuestion()
    {
        $user = $this->getAuthenticatedUser();
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
        //CHECK PERMISSIONS AND SET FLAGS
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {    //PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
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
                $onlychk = AppConstant::NUMERIC_ONE;
            } else {
                $onlychk = AppConstant::NUMERIC_ZERO;
            }
            if (isset($params['formn']) && isset($params['loc'])) {
                $formn = $params['formn'];
                $loc = $params['loc'];
                if (isset($params['checked']) || isset($params['usecheck'])) {
                    $chk = "&checked=0";
                } else {
                    $chk = '';
                }
                if ($onlychk == AppConstant::NUMERIC_ONE) {
                    $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','{$params['loc']}',true);";
                } else {
                    $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','{$params['loc']}');";
                }
            }
            $lastanswers = array('');

            if (isset($params['seed'])) {
                list($score, $rawscores) = scoreq(AppConstant::NUMERIC_ZERO, $params['qsetid'], $params['seed'], $params['qn0']);
                $scores[0] = $score;
                $lastanswers[0] = stripslashes($lastanswers[0]);
                $page_scoreMsg = "<p>Score on last answer: $score/1</p>\n";
            } else {
                $page_scoreMsg = "";
                $scores = array(AppConstant::NUMERIC_NEGATIVE_ONE);
                $_SESSION['choicemap'] = array();
            }

            $page_formAction = "test-question?cid={$params['cid']}&qsetid={$params['qsetid']}";
            if (isset($params['usecheck'])) {
                $page_formAction .= "&checked=" . $params['usecheck'];
            } else if (isset($params['checked'])) {
                $page_formAction .= "&checked=" . $params['checked'];
            }
            if (isset($params['formn'])) {
                $page_formAction .= "&formn=" . $params['formn'];
                $page_formAction .= "&loc=" . $params['loc'];
            }
            if (isset($params['onlychk'])) {
                $page_formAction .= "&onlychk=" . $params['onlychk'];
            }

            $line = QuestionSet::getUserAndQuestionSetJoin($params['qsetid']);

            $lastmod = date("m/d/y g:i a", $line['lastmoddate']);

            if (isset($CFG['AMS']['showtips'])) {
                $showtips = $CFG['AMS']['showtips'];
            } else {
                $showtips = AppConstant::NUMERIC_ONE;
            }
            if (isset($CFG['AMS']['eqnhelper'])) {
                $eqnhelper = $CFG['AMS']['eqnhelper'];
            } else {
                $eqnhelper = AppConstant::NUMERIC_ZERO;
            }
            $resultLibNames = Libraries::getUserAndLibrary($params['qsetid']);
        }
        $this->includeCSS(['mathquill.css', 'question/question.css', 'course/course.css', 'roster/roster.css']);
        $this->includeJS(['eqntips.js', 'eqnhelper.js', 'mathquill_min.js', 'mathquilled.js', 'AMtoMQ.js', 'tablesorter.js',
            'question/addquestions.js', 'general.js', 'question/junkflag.js', 'AMhelpers_min.js', 'confirmsubmit.js']);
        $responseArray = array('course' => $course, 'params' => $params, 'overwriteBody' => $overwriteBody, 'body' => $body, 'showtips' => $showtips,
            'eqnhelper' => $eqnhelper, 'page_onlyChkMsg' => $page_onlyChkMsg, 'chk' => $chk, 'formn' => $formn, 'onlychk' => $onlychk, 'page_scoreMsg' => $page_scoreMsg,
            'page_formAction' => $page_formAction, 'seed' => $seed, 'attempt' => $attempt, 'rawscores' => $rawscores, 'line' => $line, 'lastmod' => $lastmod,
            'resultLibNames' => $resultLibNames, 'myRights' => $myRights, 'params' => $params);
        return $this->renderWithData('testQuestion', $responseArray);

    }

    public function actionAddQuestionsSave()
    {
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $assessmentId = $params['aid'];
        $teacherId = $this->isTeacher($user['id'], $courseId);
        if (!isset($teacherId)) {
            echo "error: validation";
        }
        $query = Assessments::getByAssessmentId($assessmentId);
        $rawitemorder = $query['itemorder'];
        $vidData = $query['viddata'];
        $itemOrder = str_replace('~', ',', $rawitemorder);
        $curitems = array();
        foreach (explode(',', $itemOrder) as $qid) {
            if (strpos($qid, '|') === false) {
                $curitems[] = $qid;
            }
        }

        $submitted = $params['order'];
        $submitted = str_replace('~', ',', $submitted);
        $newitems = array();
        foreach (explode(',', $submitted) as $qid) {
            if (strpos($qid, '|') === false) {
                $newitems[] = $qid;
            }
        }
        $toRemove = array_diff($curitems, $newitems);

        if ($vidData != '') {
            $vidData = unserialize($vidData);
            $qorder = explode(',', $rawitemorder);
            $qidbynum = array();
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($qorder); $i++) {
                if (strpos($qorder[$i], '~') !== false) {
                    $qids = explode('~', $qorder[$i]);
                    if (strpos($qids[0], '|') !== false) { //pop off nCr
                        $qidbynum[$i] = $qids[1];
                    } else {
                        $qidbynum[$i] = $qids[0];
                    }
                } else {
                    $qidbynum[$i] = $qorder[$i];
                }
            }

            $qorder = explode(',', $params['order']);
            $newbynum = array();
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($qorder); $i++) {
                if (strpos($qorder[$i], '~') !== false) {
                    $qids = explode('~', $qorder[$i]);
                    if (strpos($qids[0], '|') !== false) { //pop off nCr
                        $newbynum[$i] = $qids[1];
                    } else {
                        $newbynum[$i] = $qids[0];
                    }
                } else {
                    $newbynum[$i] = $qorder[$i];
                }
            }

            $qidbynumflip = array_flip($qidbynum);

            $newviddata = array();
            $newviddata[0] = $vidData[0];
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($newbynum); $i++) {   //for each new item
                $oldnum = $qidbynumflip[$newbynum[$i]];
                $found = false; //look for old item in viddata
                for ($j = AppConstant::NUMERIC_ONE; $j < count($vidData); $j++) {
                    if (isset($vidData[$j][2]) && $vidData[$j][2] == $oldnum) {
                        //if found, copy data, and any non-question data following
                        $new = $vidData[$j];
                        $new[2] = $i;  //update question number;
                        $newviddata[] = $new;
                        $j++;
                        while (isset($vidData[$j]) && !isset($vidData[$j][2])) {
                            $newviddata[] = $vidData[$j];
                            $j++;
                        }
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    /*item was not found in viddata.  it should have been.
                     *count happen if the first item in a group was removed, perhaps
                     *Add a blank item
                     */
                    $newviddata[] = array('', '', $i);
                }
            }
            /*
             *any old items will not get copied.
             */
            $vidData = addslashes(serialize($newviddata));
        }
        /*
         * delete any removed questions
         */
        Questions::deleteById($toRemove);
        /*
         * store new itemorder
         */
        $query = Assessments::setVidData($params['order'], $vidData, $assessmentId);

        if (count($query) > AppConstant::NUMERIC_ZERO) {
            echo "OK";
        } else {
            echo "error: not saved";
        }
    }

    public function actionSaveLibAssignFlag()
    {
        $params = $this->getRequestParams();
        $user = $this->getAuthenticatedUser();
        $myRights = $user['rights'];
        if (!isset($params['libitemid']) || $myRights < AppConstant::TEACHER_RIGHT) {
            exit;
        }
        $isChanged = false;
        $query = LibraryItems::UpdateJunkFlag($params['libitemid'], $params['flag']);
        if ($query > AppConstant::NUMERIC_ZERO) {
            $isChanged = true;
        }
        if ($isChanged) {
            echo "OK";
        } else {
            echo "Error";
        }
    }

    public function actionShowTest()
    {
        return $this->redirect(AppUtility::getURLFromHome('site', 'work-in-progress'));
    }

    public function actionPrintLayout()
    {
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $this->layout = 'master';
        $course = Course::getById($courseId);
        $teacherId = $this->isTeacher($user['id'], $courseId);
        //set some page specific variables and counters
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        if (!(isset($teacherId))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {    //PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
            $assessmentId = $this->getParamVal('aid');
            if (isset($params['vert'])) {
                $ph = 11 - $params['vert'];
                $pw = 8.5 - $params['horiz'];
                if ($params['browser'] == 1) {
                    $ph -= .5;
                    $pw -= .5;
                }
            } else if (isset ($params['pw'])) {
                $ph = $params['ph'];
                $pw = $params['pw'];
            }
            $isfinal = isset($params['final']);
            $line = Assessments::getByAssessmentId($assessmentId);
            $ioquestions = explode(",", $line['itemorder']);
            $questions = array();
            foreach ($ioquestions as $k => $q) {
                if (strpos($q, '~') !== false) {
                    $sub = explode('~', $q);
                    if (strpos($sub[0], '|') === false) { //backwards compat
                        $questions[] = $sub[array_rand($sub, 1)];
                    } else {
                        $grpqs = array();
                        $grpparts = explode('|', $sub[0]);
                        array_shift($sub);
                        if ($grpparts[1] == 1) { // With replacement
                            for ($i = 0; $i < $grpparts[0]; $i++) {
                                $questions[] = $sub[array_rand($sub, 1)];
                            }
                        } else if ($grpparts[1] == 0) { //Without replacement
                            shuffle($sub);
                            for ($i = 0; $i < min($grpparts[0], count($sub)); $i++) {
                                $questions[] = $sub[$i];
                            }
                            //$grpqs = array_slice($sub,0,min($grpparts[0],count($sub)));
                            if ($grpparts[0] > count($sub)) { //fix stupid inputs
                                for ($i = count($sub); $i < $grpparts[0]; $i++) {
                                    $questions[] = $sub[array_rand($sub, 1)];
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
                if ($row['points'] == 9999) {
                    $points[$row['id']] = $line['defpoints'];
                } else {
                    $points[$row['id']] = $row['points'];
                }
                $qn[$row['id']] = $row['questionsetid'];
            }


            $numq = count($questions);
            $phs = $ph - 0.6;
            $pws = $pw - 0.5;
            $pwss = $pw - 0.6;

        }
        $this->includeCSS(['mathtest.css', 'print.css']);
        $this->includeJS(['AMhelpers.js']);
        $renderData = array('course' => $course, 'overwriteBody' => $overwriteBody, 'body' => $body, 'courseId' => $courseId,
            'assessmentId' => $assessmentId, 'line' => $line, 'user' => $user, 'pwss' => $pwss, 'pws' => $pws, 'phs' => $phs, 'numq' => $numq);
        return $this->renderWithData('printLayout', $renderData);
    }

    public function actionPrintLayoutBare()
    {
        $user = $this->getAuthenticatedUser();
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
        if (!(isset($teacherId))) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        }
        if (isset($params['versions'])) {
            $this->includeCSS(['print.css']);
        }

        $nologo = true;
        if (isset($params['mathdisp']) && $params['mathdisp']=='text') {
            $sessionData['mathdisp'] = AppConstant::NUMERIC_ZERO;
        } else {
            $sessionData['mathdisp'] = AppConstant::NUMERIC_TWO;
        }
        if (isset($params['mathdisp']) && $params['mathdisp']=='tex') {
            $sessionData['texdisp'] = true;
        }
        if (isset($params['mathdisp']) && $params['mathdisp']=='textandimg') {
            $printtwice = AppConstant::NUMERIC_TWO;
        } else {
            $printtwice = AppConstant::NUMERIC_ONE;
        }

        $sessionData['graphdisp'] = AppConstant::NUMERIC_TWO;
        if (!isset($params['versions'])) {

        }else{
            $line = Assessments::getByAssessmentId($assessmentId);
            $ioquestions = explode(",",$line['itemorder']);
            $aname = $line['name'];
            $questions = array();
            foreach($ioquestions as $k=>$q) {
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
                            //$grpqs = array_slice($sub,0,min($grpparts[0],count($sub)));
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
            for ($j=0; $j<$copies; $j++) {
                $seeds[$j] = array();
                if ($line['shuffle']&2) {  //all questions same random seed
                    if ($shuffle&4) { //all students same seed
                        $seeds[$j] = array_fill(0,count($questions),$assessmentId+$j);
                    } else {
                        $seeds[$j] = array_fill(0,count($questions),rand(1,9999));
                    }
                } else {
                    if ($shuffle&4) { //all students same seed
                        for ($i = 0; $i<count($questions);$i++) {
                            $seeds[$j][] = $assessmentId + $i + $j;
                        }
                    } else {
                        for ($i = 0; $i<count($questions);$i++) {
                            $seeds[$j][] = rand(1,9999);
                        }
                    }
                }
            }
            $numq = count($questions);
        }
        $this->includeCSS(['default.css','handheld.css','print.css']);
        $renderData = array('sessiondata' => $sessionData, 'overwriteBody' => $overwriteBody, 'body' => $body, 'nologo' => $nologo, 'numq' => $numq,
            'printtwice' => $printtwice, ' course' => $course, 'assessmentId' => $assessmentId, 'params' => $params, 'copies' => $copies, 'line' => $line,
            'qn' => $qn, 'courseId' => $courseId);
        return $this->renderWithData('printLayoutBare', $renderData);
    }

    public function actionManageQuestionSet()
    {
        $user = $this->getAuthenticatedUser();
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
        $pagetitle = "Manage Question Sets";
        $helpicon = "";
        $isAdmin = false;
        $isGrpAdmin = false;
        //CHECK PERMISSIONS AND SET FLAGS
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } elseif (isset($params['cid']) && $params['cid'] == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::REQUIRED_ADMIN_ACCESS;
        } elseif (!(isset($params['cid'])) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::ACCESS_THROUGH_MENU;
        } else {    //PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
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
                    $pagetitle = "Transfer Ownership";
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    $query = User::getUserGreaterThenTeacherRights();
                    $i = AppConstant::NUMERIC_ZERO;
                    $page_transferUserList = array();
                    foreach ($query as $row) {
                        $page_transferUserList['val'][$i] = $row['id'];
                        $page_transferUserList['label'][$i] = $row['LastName'] . ", " . $row['FirstName'];
                        $i++;
                    }
                }
            } else if (isset($params['chglib'])) {
                if (isset($params['qtochg'])) {
                    if ($params['chglib'] != '') {
                        $newlibs = $params['libs']; //array is sanitized later
                        if ($params['libs'] == '') {
                            $newlibs = array();
                        } else {
                            if ($newlibs[0] == AppConstant::NUMERIC_ZERO && count($newlibs) > AppConstant::NUMERIC_ONE) { //get rid of unassigned if checked and others are checked
                                array_shift($newlibs);
                            }
                        }
                        $libarray = explode(',', $params['qtochg']); //qsetids to change
                        if ($params['qtochg'] == '') {
                            $libarray = array();
                        }
                        $chglist = "'" . implode("','", $libarray) . "'";

                        $alllibs = array();
                        $query = LibraryItems::getByList($libarray);
                        foreach ($query as $row) {
                            $alllibs[$row['qsetid']][] = $row['libid'];
                        }
                        if ($isGrpAdmin) {
                            $query = LibraryItems::getByLibAndUserTable($groupId, $libarray);
                        } else {
                            $query = LibraryItems::getByListAndOwnerId($isAdmin, $chglist, $userId);
                        }
                        $mylibs = array();
                        foreach ($query as $row) {
                            $mylibs[$row['qsetid']][] = $row['libid'];
                        }

                        if ($params['action'] == AppConstant::NUMERIC_ZERO) {//add, keep existing
                            /*
                             * get list of existing library assignments, remove any additions that already exist and add to new libraries
                             */
                            foreach ($libarray as $qsetid) { //for each question determine which checked libraries it's not already in
                                $toadd = array_values(array_diff($newlibs, $alllibs[$qsetid]));
                                //and add them
                                foreach ($toadd as $libid) {
                                    if ($libid == AppConstant::NUMERIC_ZERO) {
                                        continue;
                                    } //no need to add to unassigned using "keep existing"
                                    $tempLibArray['libid'] = $libid;
                                    $tempLibArray['qsetid'] = $qsetid;
                                    $tempLibArray['userid'] = $userId;
                                    $library = new LibraryItems();
                                    $library->createLibraryItems($tempLibArray);
                                }
                                if (count($toadd) > AppConstant::NUMERIC_ONE || (count($toadd) > AppConstant::NUMERIC_ZERO && $toadd[0] != AppConstant::NUMERIC_ZERO)) {
                                    LibraryItems::deleteLibraryItems(AppConstant::NUMERIC_ZERO, $qsetid);
                                }
                            }
                        } else if ($params['action'] == AppConstant::NUMERIC_ONE) { //add, remove existing
                            /*
                             *get list of existing library assignments, rework existing to new libs, remove any excess existing and add to any new
                             */
                            foreach ($libarray as $qsetid) { //for each question determine which checked libraries it's not already in.
                                $toadd = array_diff($newlibs, $alllibs[$qsetid]);
                                //and add them
                                foreach ($toadd as $libid) {
                                    $tempLibArray['libid'] = $libid;
                                    $tempLibArray['qsetid'] = $qsetid;
                                    $tempLibArray['userid'] = $userId;
                                    $library = new LibraryItems();
                                    $library->createLibraryItems($tempLibArray);
                                }
                                //determine which libraries to remove from; my lib assignments - newlibs
                                if (isset($mylibs[$qsetid])) {
                                    $toremove = array_diff($mylibs[$qsetid], $newlibs);
                                    foreach ($toremove as $libid) {
                                        LibraryItems::deleteLibraryItems($libid, $qsetid);
                                    }
                                    //check for unassigneds
                                    $query = LibraryItems::getIdByQid($qsetid);
                                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                                        $tempLibArray['libid'] = AppConstant::NUMERIC_ZERO;
                                        $tempLibArray['qsetid'] = $qsetid;
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
                            foreach ($libarray as $qsetid) {
                                /*
                                 * for each question determine which libraries to remove from; my lib assignments - newlibs
                                 */
                                if (isset($mylibs[$qsetid])) {
                                    $toremove = array_diff($mylibs[$qsetid], $newlibs);
                                    foreach ($toremove as $libid) {
                                        LibraryItems::deleteLibraryItems($libid, $qsetid);
                                    }
                                    //check for unassigneds
                                    $query = LibraryItems::getIdByQid($qsetid);
                                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                                        $tempLibArray['libid'] = AppConstant::NUMERIC_ZERO;
                                        $tempLibArray['qsetid'] = $qsetid;
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
                    $pagetitle = "Modify Library Assignments";
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash('No Questions Selected');
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
                    $qtochg = explode(',', $params['qtochg']);
                    $now = time();
                    $myname = $user['lastName'] . ',' . $user['firstName'];
                    $userfullname = $user['firstName'] . ',' . $user['lastName'];
                    foreach ($qtochg as $k => $qid) {
                        $row = QuestionSet::getSelectedDataByQuesSetId($qid);
                        $lastauthor = array_pop($row);
                        $ancestors = $row['ancestors'];
                        $ancestorauthors = $row['ancestorauthors'];
                        if ($ancestors != '') {
                            $ancestors = $qid . ',' . $ancestors;
                        } else {
                            $ancestors = $qid;
                        }
                        if ($ancestorauthors != '') {
                            $ancestorauthors = $lastauthor . '; ' . $ancestorauthors;
                        } else {
                            $ancestorauthors = $lastauthor;
                        }
                        $row['ancestors'] = $ancestors;
                        $row['ancestorauthors'] = $ancestorauthors;
                        $row['description'] .= " (copy by $userfullname)";
                        $mt = microtime();
                        $uqid = substr($mt, AppConstant::NUMERIC_ELEVEN) . substr($mt, AppConstant::NUMERIC_TWO, AppConstant::NUMERIC_THREE) . $k;
                        $tempQuestionArray = array();
                        $tempQuestionArray['uniqueid'] = $uqid;
                        $tempQuestionArray['adddate'] = $now;
                        $tempQuestionArray['lastmoddate'] = $now;
                        $tempQuestionArray['ownerid'] = $userId;
                        $tempQuestionArray['author'] = $myname;
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
                        $nqid = $question->createQuestionSet($tempQuestionArray);
                        $tempLibArray['libid'] = $lib;
                        $tempLibArray['qsetid'] = $nqid;
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
                            $QImage->createQImages($nqid, $QImgArray);
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pagetitle = "Template Questions";
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if(is_array($params['nchecked'])){
                        $clist = implode(",", $params['nchecked']);
                    }else{
                        $clist = $params['nchecked'];
                    }
                    $selecttype = "radio";

                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash('No Questions Selected');
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                }

            } else if (isset($params['license'])) {
                if (isset($params['qtochg'])) {
                    $qtochg = explode(',', $params['qtochg']);
                    foreach ($qtochg as $k => $qid) {
                        $qtochg[$k] = intval($qid);
                    }
                    if ($params['sellicense'] != AppConstant::NUMERIC_NEGATIVE_ONE) {
                        $selLicense = intval($params['sellicense']);
                        if (!$isAdmin) {
                            QuestionSet::setLicenseByUserId($selLicense, $qtochg, $userId);
                        } else {
                            QuestionSet::setLicense($selLicense, $qtochg);
                        }
                    }
                    if ($params['otherattribtype'] != AppConstant::NUMERIC_NEGATIVE_ONE) {
                        if ($params['otherattribtype'] == AppConstant::NUMERIC_ZERO) {
                            if (!$isAdmin) {
                                QuestionSet::setOtherAttributionByUserId($params['addattr'], $qtochg, $userId);
                            } else {
                                QuestionSet::setOtherAttribution($params['addattr'], $qtochg);
                            }
                        } else {
                            if (!$isAdmin) {
                                $query = QuestionSet::getIdByQidAndOwnerId($qtochg, $userId);
                            } else {
                                $query = QuestionSet::getByIdUsingInClause($qtochg);
                            }
                            foreach ($query as $row) {
                                $attr = addslashes($row['otherattribution']) . $params['addattr'];
                                QuestionSet::setOtherAttributionById($attr, $row['id']);
                            }
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pagetitle = _("Change Question License/Attribution");
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if(is_array($params['nchecked'])){
                        $clist = implode(",", $params['nchecked']);
                    }else{
                        $clist = $params['nchecked'];
                    }

                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash('No Questions Selected');
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                }
            } else if (isset($params['chgrights'])) {
                if (isset($params['qtochg'])) {
                    if ($isGrpAdmin) {
                        $query = QuestionSet::getByQSetIdAndGroupId(explode(',', $params['qtochg']), $groupId);
                        $tochg = array();
                        foreach ($query as $row) {
                            $tochg[] = $row['id'];
                        }
                        if (count($tochg) > AppConstant::NUMERIC_ZERO) {
                            $chglist = implode(',', $tochg);
                            QuestionSet::setUserRightsByList($tochg, $params['newrights']);
                        }
                    } else {
                        $chglist = "'" . implode("','", explode(',', $params['qtochg'])) . "'";

                        if (!$isAdmin) {
                            QuestionSet::setUserRightsByListAndUserId(explode(',', $params['qtochg']), $params['newrights'], $userId);
                        } else {
                            QuestionSet::setUserRightsByList(explode(',', $params['qtochg']), $params['newrights']);
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pagetitle = "Change Question Rights";
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";
                    if(is_array($params['nchecked'])){
                        $clist = implode(",", $params['nchecked']);
                    }else{
                        $clist = $params['nchecked'];
                    }

                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash('No Questions Selected');
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                }
            } else if (isset($params['remove'])) {//post remove
                if (isset($params['confirmed'])) {
                    if ($params['remove'] != '') {
                        $removeList = explode(',', $params['remove']);
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
                } else {
                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash('No Questions Selected');
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                        $rlist = implode(",", $params['nchecked']);
                }
            } else if (isset($params['transfer'])) {
                if (isset($params['newowner'])) {
                    if ($params['transfer'] != '') {
                        $translist = explode(',', $params['transfer']);

                        if ($isGrpAdmin) {
                            $query = QuestionSet::getByQSetIdAndGroupId($translist, $groupId);
                            foreach ($query as $row) {
                                QuestionSet::setOwnerIdById($row['id'], $params['newowner']);
                            }
                        } else {
                            if (!$isAdmin) {
                                QuestionSet::setOwnerIdByIdsAndOwnerId($translist, $userId, $params['newowner']);
                            } else {
                                QuestionSet::setOwnerIdByIds($translist, $params['newowner']);
                            }
                        }
                    }
                    return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                } else {
                    $pagetitle = "Transfer Ownership";
                    $curBreadcrumb .= "<a href=" . AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid) . ">Manage Question Set </a>";

                    if (!isset($params['nchecked'])) {
                        $this->setErrorFlash('No Questions Selected');
                        return $this->redirect(AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=' . $cid));
                    }
                    $tlist = implode(",", $params['nchecked']);
                    $query = User::getUserGreaterThenTeacherRights();
                    $i = AppConstant::NUMERIC_ZERO;
                    $page_transferUserList = array();
                    foreach ($query as $row) {
                        $page_transferUserList['val'][$i] = $row['id'];
                        $page_transferUserList['label'][$i] = $row['LastName'] . ", " . $row['FirstName'];
                        $i++;
                    }
                }
            } else { //DEFAULT DATA MANIPULATION
                if ($isAdmin) {
                    $page_adminMsg = "You are in Admin mode, which means actions will apply to all questions, regardless of owner";
                } else if ($isGrpAdmin) {
                    $page_adminMsg = "You are in Group Admin mode, which means actions will apply to all group's questions, regardless of owner";
                } else {
                    $page_adminMsg = "";
                }
                require_once(Yii::$app->basePath . "/filter/filter.php");
                //remember search
                if (isset($params['search'])) {
                    $safesearch = $params['search'];
                    $safesearch = str_replace(' and ', ' ', $safesearch);
                    $search = stripslashes($safesearch);
                    $search = str_replace('"', '&quot;', $search);
                    $sessionData['lastsearch' . $cid] = $safesearch; //str_replace(" ","+",$safesearch);
                    if (isset($params['searchmine'])) {
                        $searchmine = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchmine = AppConstant::NUMERIC_ZERO;
                    }
                    $sessionData['searchmine' . $cid] = $searchmine;
                    if (isset($params['searchall'])) {
                        $searchall = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchall = AppConstant::NUMERIC_ZERO;
                    }
                    $sessionData['searchall' . $cid] = $searchall;
                    if ($searchall == AppConstant::NUMERIC_ONE && trim($search) == '' && $searchmine == AppConstant::NUMERIC_ZERO) {
                        $overwriteBody = AppConstant::NUMERIC_ONE;
                        $body = "Must provide a search term when searching all libraries <a href=\"manage-question-set\">Try again</a>";
                        $searchall = AppConstant::NUMERIC_ZERO;
                    }
                    if ($isAdmin) {
                        if (isset($params['hidepriv'])) {
                            $hidepriv = AppConstant::NUMERIC_ONE;
                        } else {
                            $hidepriv = AppConstant::NUMERIC_ZERO;
                        }
                        $sessionData['hidepriv' . $cid] = $hidepriv;
                    }

                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($sessionData['lastsearch' . $cid])) {
                    $safesearch = $sessionData['lastsearch' . $cid]; //str_replace("+"," ",$sessionData['lastsearch'.$cid]);
                    $search = stripslashes($safesearch);
                    $search = str_replace('"', '&quot;', $search);
                    $searchall = $sessionData['searchall' . $cid];
                    $searchmine = $sessionData['searchmine' . $cid];
                    if ($isAdmin) {
                        $hidepriv = $sessionData['hidepriv' . $cid];
                    }
                } else {
                    $search = '';
                    $searchall = AppConstant::NUMERIC_ZERO;
                    $searchmine = AppConstant::NUMERIC_ZERO;
                    $safesearch = '';
                }
                if (trim($safesearch) == '') {
                    $searchlikes = '';
                } else {
                    if (substr($safesearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SIX) == 'regex:') {
                        $safesearch = substr($safesearch, AppConstant::NUMERIC_SIX);
                        $searchlikes = "imas_questionset.description REGEXP '$safesearch' AND ";
                    } else if ($safesearch == 'isbroken') {
                        $searchlikes = "imas_questionset.broken=1 AND ";
                    } else if (substr($safesearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_SEVEN) == 'childof') {
                        $searchlikes = "imas_questionset.ancestors REGEXP '[[:<:]]" . substr($safesearch, AppConstant::NUMERIC_EIGHT) . "[[:>:]]' AND ";
                    } else {
                        $searchterms = explode(" ", $safesearch);
                        $searchlikes = '';
                        foreach ($searchterms as $k => $v) {
                            if (substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE) == 'type=') {
                                $searchlikes .= "imas_questionset.qtype='" . substr($v, AppConstant::NUMERIC_FIVE) . "' AND ";
                                unset($searchterms[$k]);
                            }
                        }
                        $searchlikes .= "((imas_questionset.description LIKE '%" . implode("%' AND imas_questionset.description LIKE '%", $searchterms) . "%') ";
                        if (substr($safesearch, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_THREE) == 'id=') {
                            $searchlikes = "imas_questionset.id='" . substr($safesearch, AppConstant::NUMERIC_THREE) . "' AND ";
                        } else if (is_numeric($safesearch)) {
                            $searchlikes .= "OR imas_questionset.id='$safesearch') AND ";
                        } else {
                            $searchlikes .= ") AND";
                        }
                    }
                }

                if (isset($params['libs'])) {
                    if ($params['libs'] == '') {
                        $params['libs'] = $userDefLib;
                    }
                    $searchlibs = $params['libs'];
                    $sessionData['lastsearchlibs' . $cid] = $searchlibs;
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($params['listlib'])) {
                    $searchlibs = $params['listlib'];
                    $sessionData['lastsearchlibs' . $cid] = $searchlibs;
                    $searchall = AppConstant::NUMERIC_ZERO;
                    $sessionData['searchall' . $cid] = $searchall;
                    $sessionData['lastsearch' . $cid] = '';
                    $searchlikes = '';
                    $search = '';
                    $safesearch = '';
                    $this->writesessiondata($sessionData, $sessionId);
                } else if (isset($sessionData['lastsearchlibs' . $cid])) {
                    $searchlibs = $sessionData['lastsearchlibs' . $cid];
                } else {
                    $searchlibs = $userDefLib;
                }
                $llist = "'" . implode("','", explode(',', $searchlibs)) . "'";
                $libsortorder = array();
                if (substr($searchlibs, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE) == "0") {
                    $lnamesarr[0] = "Unassigned";
                    $libsortorder[0] = AppConstant::NUMERIC_ZERO;
                }
                $query = Libraries::getById($llist);
                foreach ($query as $row) {
                    $lnamesarr[$row['id']] = $row['name'];
                    $libsortorder[$row['id']] = $row['sortorder'];
                }
                if (count($lnamesarr) > AppConstant::NUMERIC_ZERO) {
                    $lnames = implode(", ", $lnamesarr);
                } else {
                    $lnames = '';
                }
                $resultLibs = QuestionSet::getQuestionSetDataByJoin($searchlikes, $isAdmin, $searchall, $hidepriv, $llist, $searchmine, $isGrpAdmin, $userId, $groupId);
                $page_questionTable = array();
                $page_libstouse = array();
                $page_libqids = array();
                $lastlib = AppConstant::NUMERIC_NEGATIVE_ONE;
                $ln = AppConstant::NUMERIC_ONE;

                foreach ($resultLibs as $line) {
                    if (isset($page_questionTable[$line['id']])) {
                        continue;
                    }
                    if ($lastlib != $line['libid'] && (isset($lnamesarr[$line['libid']]) || $searchall == AppConstant::NUMERIC_ONE)) {
                        $page_libstouse[] = $line['libid'];
                        $lastlib = $line['libid'];
                        $page_libqids[$line['libid']] = array();
                    }
                    if ($libsortorder[$line['libid']] == AppConstant::NUMERIC_ONE) { //alpha
                        $page_libqids[$line['libid']][$line['id']] = trim($line['description']);
                    } else { //id
                        $page_libqids[$line['libid']][] = $line['id'];
                    }
                    $i = $line['id'];
                    $page_questionTable[$i]['checkbox'] = "<input class='margin-left-twelve' type=checkbox name='nchecked[]' value='" . $line['id'] . "' id='qo$ln'>";
                    if ($line['userights']==AppConstant::NUMERIC_ZERO) {
                        $page_questionTable[$i]['desc'] = '<span class="red">'.filter($line['description']).'</span>';
                    } else if ($line['replaceby']>AppConstant::NUMERIC_ZERO || $line['junkflag']>AppConstant::NUMERIC_ZERO) {
                        $page_questionTable[$i]['desc'] = '<span style="color:#ccc"><i>'.filter($line['description']).'</i></span>';
                    } else {
                        $page_questionTable[$i]['desc'] = filter($line['description']);
                    }

                    if ($line['extref'] != '') {
                        $page_questionTable[$i]['cap'] = AppConstant::NUMERIC_ZERO;
                        $extref = explode('~~', $line['extref']);
                        $hasvid = false;
                        $hasother = false;
                        $hascap = false;
                        foreach ($extref as $v) {
                            if (strtolower(substr($v, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE)) == "video" || strpos($v, 'youtube.com') !== false || strpos($v, 'youtu.be') !== false) {
                                $hasvid = true;
                                if (strpos($v, '!!1') !== false) {
                                    $page_questionTable[$i]['cap'] = AppConstant::NUMERIC_ONE;
                                }
                            } else {
                                $hasother = true;
                            }
                        }
                        $page_questionTable[$i]['extref'] = '';
                        if ($hasvid) {
                            $page_questionTable[$i]['extref'] .= "<img src=" . AppUtility::getHomeURL() . 'img/video_tiny.png' . ">";
                        }
                        if ($hasother) {
                            $page_questionTable[$i]['extref'] .= "<img src=" . AppUtility::getHomeURL() . 'img/html_tiny.png' . ">";
                        }
                    }
                    $page_questionTable[$i]['preview'] = "<input type=button value=\"Preview\" onClick=\"previewq('selform',$ln,{$line['id']})\"/>";
                    $page_questionTable[$i]['type'] = $line['qtype'];
                    if ($searchall == AppConstant::NUMERIC_ONE) {
                        $page_questionTable[$i]['lib'] = "<a href=\"manage-question-set?cid=$cid&listlib={$line['libid']}\">List lib</a>";
                    } else {
                        $page_questionTable[$i]['junkflag'] = $line['junkflag'];
                        $page_questionTable[$i]['libitemid'] = $line['libitemid'];
                    }
                    $page_questionTable[$i]['times'] = AppConstant::NUMERIC_ZERO;

                    if ($isAdmin || $isGrpAdmin) {
                        $page_questionTable[$i]['mine'] = $line['lastName'] . ',' . substr($line['firstName'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_ONE);
                        if ($line['userights'] == AppConstant::NUMERIC_ZERO) {
                            $page_questionTable[$i]['mine'] .= ' <i>Priv</i>';
                        }
                    } else if ($line['ownerid'] == $userId) {
                        if ($line['userights'] == AppConstant::NUMERIC_ZERO) {
                            $page_questionTable[$i]['mine'] = '<i>Priv</i>';
                        } else {
                            $page_questionTable[$i]['mine'] = 'Yes';
                        }
                    } else {
                        $page_questionTable[$i]['mine'] = '';
                    }
                    $page_questionTable[$i]['action'] = "<select class='form-control-for-question' onchange=\"doaction(this.value,{$line['id']})\"><option value=\"0\">Action..</option>";
                    if ($isAdmin || ($isGrpAdmin && $line['groupid'] == $groupId) || $line['ownerid'] == $userId || ($line['userights'] == AppConstant::NUMERIC_THREE && $line['groupid'] == $groupId) || $line['userights'] > AppConstant::NUMERIC_THREE) {
                        $page_questionTable[$i]['action'] .= '<option value="mod">Modify Code</option>';
                    } else {
                        $page_questionTable[$i]['action'] .= '<option value="mod">View Code</option>';
                    }
                    $page_questionTable[$i]['action'] .= '<option value="temp">Template (copy)</option>';
                    if ($isAdmin || ($isGrpAdmin && $line['groupid'] == $groupId) || $line['ownerid'] == $userId) {
                        $page_questionTable[$i]['action'] .= '<option value="del">Delete</option>';
                        $page_questionTable[$i]['action'] .= '<option value="tr">Transfer</option>';
                    }
                    $page_questionTable[$i]['action'] .= '</select>';


                    $page_questionTable[$i]['lastmod'] = date("m/d/y", $line['lastmoddate']);
                    $page_questionTable[$i]['add'] = "<a href=\"mod-question?qsetid={$line['id']}&cid=$cid\">Add</a>";
                    $ln++;
                }
                //pull question useage data
                if (count($page_questionTable) > AppConstant::NUMERIC_ZERO) {
                    $allusedqids = implode(',', array_keys($page_questionTable));
                    $query = Questions::getByQuestionSetId($allusedqids);
                    foreach ($query as $row) {
                        $page_questionTable[$row[0]]['times'] = $row[1];
                    }
                }
                //sort alpha sorted libraries
                foreach ($page_libstouse as $libid) {
                    if ($libsortorder[$libid] == AppConstant::NUMERIC_ONE) {
                        natcasesort($page_libqids[$libid]);
                        $page_libqids[$libid] = array_keys($page_libqids[$libid]);
                    }
                }
                if ($searchall == AppConstant::NUMERIC_ONE) {
                    $page_libstouse = array_keys($page_libqids);
                }
            }
        }
        $this->includeCSS(['question/question.css','question/libtree.css']);
        $this->includeJS(['general.js','tablesorter.js','question/junkflag.js','question/libtree2.js','question/manageQuestionSet.js']);
        $renderData = array('params' => $params,'overwriteBody' => $overwriteBody, 'body' => $body, 'searchlibs' => $searchlibs, 'curBreadcrumb' => $curBreadcrumb,
            'pagetitle' => $pagetitle, 'helpicon' => $helpicon, 'cid' => $cid, 'rlist' => $rlist, 'tlist' => $tlist, 'page_transferUserList' => $page_transferUserList,
            'clist' => $clist, 'page_adminMsg' => $page_adminMsg, 'lnames' => $lnames, 'search' => $search, 'searchall' => $searchall, 'searchmine' => $searchmine,
            'isadmin' => $isAdmin, 'hidepriv' => $hidepriv, 'isgrpadmin' => $isGrpAdmin, 'page_libstouse' => $page_libstouse, 'lnamesarr' => $lnamesarr,
            'page_libqids' => $page_libqids, 'page_questionTable' => $page_questionTable, 'remove' => $remove, 'transfer' => $transfer);
        return $this->renderWithData('manageQuestionSet', $renderData);
    }

    function timeToSec($t)
    {
        if (strpos($t,':')===false) {
            $time = $t;
        } else {
            $x = explode(':',$t);
            $time = 60*$x[0] + $x[1];
        }
        return $time;
    }

    function secToTime($t)
    {
        if ($t<60) {
            return $t;
        } else {
            $o = floor($t/60).':';
            $t = $t%60;
            if ($t<10) {
                $o .= '0'.$t;
            } else {
                $o .= $t;
            }
        }
        return $o;
    }
}