<?php


namespace app\controllers\question;

use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\filehandler;
use app\controllers\AppController;
use app\models\_base\BaseImasLibraryItems;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
use app\models\Forums;
use app\models\GbCats;
use app\models\Items;
use app\models\Libraries;
use app\models\LibraryItems;
use app\models\Outcomes;
use app\models\QImages;
use app\models\Questions;
use app\models\Rubrics;
use app\models\User;
use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\StuGroupSet;
use app\models\Teacher;
use Yii;
use app\components\AppConstant;
//use app\models\User;

class QuestionController extends AppController
{
    public function actionAddQuestions()
    {
        $user = $this->getAuthenticatedUser();
        $groupid = $user['groupid'];
        $userId = $user['id'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $teacherId = $this->isTeacher($userId,$courseId);
        if ($user['rights']==AppConstant::ADMIN_RIGHT) {
            $teacherId = $userId;
            $adminasteacher = true;
        }
        $overwriteBody=AppConstant::NUMERIC_ZERO;
        $body = '';
        $course = Course::getById($courseId);
        $this->checkSession($params);
        $pagetitle = "Add/Remove Questions";

        $curBreadcrumb =  $course['name'];
        if (isset($params['clearattempts']) || isset($params['clearqattempts']) || isset($params['withdraw'])) {
            $curBreadcrumb .= "&gt; <a href=\"add-questions?cid=" . $params['cid'] . "&aid=" . $params['aid'] . "\">Add/Remove Questions</a> &gt; Confirm\n";
        } else {
            $curBreadcrumb .= "&gt; Add/Remove Questions\n";
        }
        if (!$teacherId) { // loaded by a NON-teacher
            $overwriteBody=AppConstant::NUMERIC_ONE;
            $body = "You need to log in as a teacher to access this page";
        } elseif (!(isset($params['cid'])) || !(isset($params['aid']))) {
            $overwriteBody=AppConstant::NUMERIC_ONE;
            $body = "You need to access this page from the course page menu";
        }else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
            $cid = $this->getParamVal('cid');;
            $aid = $this->getParamVal('aid');
            $sessionId = $this->getSessionId();
            $sessiondata  = $this->getSessionData($sessionId);
            if (isset($params['grp'])) {
                $sessiondata['groupopt'.$aid] = $params['grp'];
                $this->writesessiondata($sessiondata,$sessionId);
            }
            if (isset($params['selfrom'])) {
                $sessiondata['selfrom'.$aid] = $params['selfrom'];
                $this->writesessiondata($sessiondata,$sessionId);
            } else {
                if (!isset($sessiondata['selfrom'.$aid])) {
                    $sessiondata['selfrom'.$aid] = 'lib';
                    $this->writesessiondata($sessiondata,$sessionId);
                }
            }

            if (isset($teacherId) && isset($params['addset'])) {
                if (!isset($params['nchecked']) && !isset($params['qsetids'])) {
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $body = "No questions selected.  <a href=".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid).">Go back</a>";
                } else if (isset($params['add'])) {
                    require dirname(__FILE__) . '/modquestiongrid.php';
//                    include("modquestiongrid.php");
                    if (isset($params['process'])) {
                        AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid);
                        exit;
                    }
                } else {
                    $checked = $params['nchecked'];
                    foreach ($checked as $qsetid) {
                        $questionData = array(
                            'assessmentid' => $aid,
                            'points' => AppConstant::QUARTER_NINE,
                            'attempts' => AppConstant::QUARTER_NINE,
                            'penalty' => AppConstant::QUARTER_NINE,
                            'questionsetid' => $qsetid
                        );
                        $question = new Questions();
                        $questionId = $question->addQuestions($questionData);
                        $qids[] = $questionId;
                    }
                    //add to itemorder
                    $assessment = Assessments::getByAssessmentId($aid);
                    if ($assessment['itemorder']=='') {
                        $itemorder = implode(",",$qids);
                    } else {
                        $itemorder  = $assessment['itemorder'] . "," . implode(",",$qids);
                    }
                    $viddata = $assessment['viddata'];
                    if ($viddata != '') {
                        if ($assessment['itemorder']=='') {
                            $nextnum = AppConstant::NUMERIC_ZERO;
                        } else {
                            $nextnum = substr_count($assessment['itemorder'],',')+AppConstant::NUMERIC_ONE;
                        }
                        $numnew= count($checked);
                        $viddata = unserialize($viddata);
                        if (!isset($viddata[count($viddata)-AppConstant::NUMERIC_ONE][1])) {
                            $finalseg = array_pop($viddata);
                        } else {
                            $finalseg = '';
                        }
                        for ($i=$nextnum;$i<$nextnum+$numnew;$i++) {
                            $viddata[] = array('','',$i);
                        }
                        if ($finalseg != '') {
                            $viddata[] = $finalseg;
                        }
                        $viddata = serialize($viddata);
                    }
                    Assessments::setVidData($itemorder,$viddata,$aid);
                    AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid);
                    exit;
                }
            }
            if (isset($params['modqs'])) {
                if (!isset($params['checked']) && !isset($params['qids'])) {
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $body = "No questions selected.  <a href=".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid).">Go back</a>\n";
                } else {
//                    require dirname(__FILE__) . '/modquestiongrid.php';
                    include("modquestiongrid.php");
                    if (isset($params['process'])) {
                        return $this->redirect(AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid));
//                        exit;
                    }
                }
            }
            if (isset($params['clearattempts'])) {
                if ($params['clearattempts']=="confirmed") {
//                    require_once('../includes/filehandler.php');
                    filehandler::deleteallaidfiles($aid);
                    AssessmentSession::deleteByAssessmentId($aid);
                    Questions::setWithdrawn($aid,AppConstant::NUMERIC_ZERO);
                    return $this->redirect(AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid));
                } else {
                    $overwriteBody = AppConstant::NUMERIC_ONE;
                    $assessmentData = Assessments::getByAssessmentId($params['aid']);
                    $assessmentname = $assessmentData['name'];
                    $body .= "<h3>$assessmentname</h3>";
                    $body .= "<p>Are you SURE you want to delete all attempts (grades) for this assessment?</p>";
                    $body .= "<p><input type=button value=\"Yes, Clear\" onClick=\"window.location='".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid.'&clearattempts=confirmed')."'\">\n";
                    $body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid)."';\"></p>\n";
                }
            }

            if (isset($params['withdraw'])) {
                if (isset($params['confirmed'])) {
                    if (strpos($params['withdraw'],'-')!==false) {
                        $isingroup = true;
                        $loc = explode('-',$params['withdraw']);
                        $toremove = $loc[0];
                    } else {
                        $isingroup = false;
                        $toremove = $params['withdraw'];
                    }
                    $query = Assessments::getByAssessmentId($aid);
                    $itemorder = explode(',',$query['itemorder']);
                    $defpoints = $query['defpoints'];

                    $qids = array();
                    if ($isingroup && $params['withdrawtype']!='full') { //is group remove
                        $qids = explode('~',$itemorder[$toremove]);
                        if (strpos($qids[0],'|')!==false) { //pop off nCr
                            array_shift($qids);
                        }
                    } else if ($isingroup) { //is single remove from group
                        $sub = explode('~',$itemorder[$toremove]);
                        if (strpos($sub[0],'|')!==false) { //pop off nCr
                            array_shift($sub);
                        }
                        $qids = array($sub[$loc[1]]);
                    } else { //is regular item remove
                        $qids = array($itemorder[$toremove]);
                    }
                    $qidlist = implode(',',$qids);
                    //withdraw question
                    Questions::updateWithPoints(AppConstant::NUMERIC_ONE,'',$qidlist);
                    if ($params['withdrawtype']=='zero' || $params['withdrawtype']=='groupzero') {
                        Questions::updateWithPoints(AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,$qidlist);
                    }

                    //get possible points if needed
                    if ($params['withdrawtype']=='full' || $params['withdrawtype']=='groupfull') {
                        $poss = array();
                        $questionList = Questions::getByIdList($qidlist);
                        foreach ($questionList  as $list) {
                            if ($list['points']==AppConstant::QUARTER_NINE) {
                                $poss[$list['id']] = $defpoints;
                            } else {
                                $poss[$list['id']] = $list['points'];
                            }
                        }
                    }

                    //update assessment sessions
                    $assessmentSessionData = AssessmentSession::getByAssessmentId($aid);
                    foreach ($assessmentSessionData as $data) {
                        if (strpos($data['questions'],';')===false) {
                            $qarr = explode(",",$data['questions']);
                        } else {
                            list($questions,$bestquestions) = explode(";",$data['questions']);
                            $qarr = explode(",",$bestquestions);
                        }
                        if (strpos($data['bestscores'],';')===false) {
                            $bestscores = explode(',',$data['bestscores']);
                            $doraw = false;
                        } else {
                            list($bestscorelist,$bestrawscorelist,$firstscorelist) = explode(';',$data['bestscores']);
                            $bestscores = explode(',', $bestscorelist);
                            $bestrawscores = explode(',', $bestrawscorelist);
                            $firstscores = explode(',', $firstscorelist);
                            $doraw = true;
                        }
                        for ($i=AppConstant::NUMERIC_ZERO; $i<count($qarr); $i++) {
                            if (in_array($qarr[$i],$qids)) {
                                if ($params['withdrawtype']=='zero' || $params['withdrawtype']=='groupzero') {
                                    $bestscores[$i] = AppConstant::NUMERIC_ZERO;
                                } else if ($params['withdrawtype']=='full' || $params['withdrawtype']=='groupfull') {
                                    $bestscores[$i] = $poss[$qarr[$i]];
                                }
                            }
                        }
                        if ($doraw) {
                            $slist = implode(',',$bestscores).';'.implode(',',$bestrawscores).';'.implode(',',$firstscores);
                        } else {
                            $slist = implode(',',$bestscores );
                        }
                        AssessmentSession::setBestScore($slist,$data['id']);
                    }
                    AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid);
                    exit;
                } else {
                    if (strpos($params['withdraw'],'-')!==false) {
                        $isingroup = true;
                    } else {
                        $isingroup = false;
                    }
                    $overwriteBody = AppConstant::NUMERIC_ONE;
//                    $body = "<div class=breadcrumb>$curBreadcrumb</div>\n";
                    $body .= "<h3>Withdraw Question</h3>";
                    $body .= "<form method=post action=\"add-questions?cid=$cid&aid=$aid&withdraw={$params['withdraw']}&confirmed=true\">";
                    if ($isingroup) {
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
                    $body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid)."'\"></p>\n";
                    $body .= '</form>';
                }

            }
            $address = AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid);

//            $placeinhead = "<script type=\"text/javascript\">
//		var previewqaddr = '".AppUtility::getURLFromHome('question','question/test-question?cid='.$courseId)."';
//		var addqaddr = '$address';
//		</script>";
//            $placeinhead .= "<script type=\"text/javascript\" src=".AppUtility::getAssetURL().'/js/question/addquestions.js'."></script>";
//            $placeinhead .= "<script type=\"text/javascript\" src=".AppUtility::getAssetURL().'/js/question/addqsort.js?v=030315.js'."></script>";
//            $placeinhead .= "<script type=\"text/javascript\" src=".AppUtility::getAssetURL().'/js/question/junkflag.js'."></script>";
//            $placeinhead .= "<script type=\"text/javascript\">var JunkFlagsaveurl = '".AppUtility::getURLFromHome('question','question/savelibassignflag')."';</script>";


            //DEFAULT LOAD PROCESSING GOES HERE
            //load filter.  Need earlier than usual header.php load
            $curdir = rtrim(dirname(__FILE__), '/\\');
            require_once (Yii::$app->basePath."/filter/filter.php");
            $query = AssessmentSession::getByAssessmentSessionIdJoin($aid,$cid);
            if (count($query) > AppConstant::NUMERIC_ZERO) {
                $beentaken = true;
            } else {
                $beentaken = false;
            }
            $result = Assessments::getByAssessmentId($aid);
            $itemorder = $result['itemorder'];
            $page_assessmentName = $result['name'];
            $ln = AppConstant::NUMERIC_ONE;
            $defpoints = $result['defpoints'];
            $displaymethod = $result['displaymethod'];
            $showhintsdef = $result['showhints'];

            $grp0Selected = "";
            if (isset($sessiondata['groupopt'.$aid])) {
                $grp = $sessiondata['groupopt'.$aid];
                $grp1Selected = ($grp==AppConstant::NUMERIC_ONE) ? " selected" : "";
            } else {
                $grp = AppConstant::NUMERIC_ZERO;
                $grp0Selected = " selected";
            }

            $jsarr = '[';
            if ($itemorder != '') {
                $items = explode(",",$itemorder);
            } else {
                $items = array();
            }
            $existingq = array();
            $apointstot = AppConstant::NUMERIC_ZERO;
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($items); $i++) {
                if (strpos($items[$i],'~')!==false) {
                    $subs = explode('~',$items[$i]);
                } else {
                    $subs[] = $items[$i];
                }
                if ($i>AppConstant::NUMERIC_ZERO) {
                    $jsarr .= ',';
                }
                if (count($subs)>AppConstant::NUMERIC_ONE) {
                    if (strpos($subs[0],'|')===false) { //for backwards compat
                        $jsarr .= '[1,0,[';
                    } else {
                        $grpparts = explode('|',$subs[0]);
                        $jsarr .= '['.$grpparts[0].','.$grpparts[1].',[';
                        array_shift($subs);
                    }
                }
                for ($j=AppConstant::NUMERIC_ZERO;$j<count($subs);$j++) {
                    $line = Questions::getQuestionData($subs[$j]);
                    $existingq[] = $line['questionsetid'];
                    if ($j>AppConstant::NUMERIC_ZERO) {
                        $jsarr .= ',';
                    }
                    //output item array
                    $jsarr .= '['.$subs[$j].','.$line['questionsetid'].',"'.addslashes(filter(str_replace(array("\r\n", "\n", "\r")," ",$line['description']))).'","'.$line['qtype'].'",'.$line['points'].',';
                    if ($line['userights']>3 || ($line['userights']==3 && $line['groupid']==$groupid) || $line['ownerid']==$userId || $adminasteacher) { //can edit without template?
                        $jsarr .= '1';
                    } else {
                        $jsarr .= '0';
                    }
                    $jsarr .= ','.$line['withdrawn'];
                    $extrefval = AppConstant::NUMERIC_ZERO;
                    if (($line['showhints']==AppConstant::NUMERIC_ZERO && $showhintsdef==AppConstant::NUMERIC_ONE) || $line['showhints']==AppConstant::NUMERIC_TWO) {
                        $extrefval += AppConstant::NUMERIC_ONE;
                    }
                    if ($line['extref']!='') {
                        $extref = explode('~~',$line['extref']);
                        $hasvid = false;  $hasother = false;  $hascap = false;
                        foreach ($extref as $v) {
                            if (strtolower(substr($v,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_FIVE))=="video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
                                $hasvid = true;
                                if (strpos($v,'!!1')!==false) {
                                    $hascap = true;
                                }
                            } else {
                                $hasother = true;
                            }
                        }
                        $page_questionTable[$i]['extref'] = '';
                        if ($hasvid) {
                            $extrefval += AppConstant::NUMERIC_FOUR;
                        }
                        if ($hasother) {
                            $extrefval += AppConstant::NUMERIC_TWO;
                        }
                        if ($hascap) {
                            $extrefval += 16;
                        }
                    }
                    if ($line['solution']!='' && ($line['solutionopts']&AppConstant::NUMERIC_TWO)==AppConstant::NUMERIC_TWO) {
                        $extrefval += AppConstant::NUMERIC_EIGHT;
                    }
                    $jsarr .= ','.$extrefval;
                    $jsarr .= ']';
                }
                if (count($subs)>AppConstant::NUMERIC_ONE) {
                    $jsarr .= '],';
                    if (isset($_COOKIE['closeqgrp-'.$aid]) && in_array("$i",explode(',',$_COOKIE['closeqgrp-'.$aid],true))) {
                        $jsarr .= '0';
                    } else {
                        $jsarr .= '1';
                    }
                    $jsarr .= ']';
                }
//                $alt = 1 - $alt;
                unset($subs);
            }
            $jsarr .= ']';

//            DATA MANIPULATION FOR POTENTIAL QUESTIONS
            if ($sessiondata['selfrom'.$aid]=='lib') { //selecting from libraries

                //remember search
                if (isset($params['search'])) {
                    $safesearch = $params['search'];
                    $safesearch = str_replace(' and ', ' ',$safesearch);
                    $search = stripslashes($safesearch);
                    $search = str_replace('"','&quot;',$search);
                    $sessiondata['lastsearch'.$cid] = $safesearch; ///str_replace(" ","+",$safesearch);
                    if (isset($params['searchall'])) {
                        $searchall = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchall = AppConstant::NUMERIC_ZERO;
                    }
                    $sessiondata['searchall'.$cid] = $searchall;
                    if (isset($params['searchmine'])) {
                        $searchmine = AppConstant::NUMERIC_ONE;
                    } else {
                        $searchmine = AppConstant::NUMERIC_ZERO;
                    }
                    if (isset($params['newonly'])) {
                        $newonly = AppConstant::NUMERIC_ONE;
                    } else {
                        $newonly = AppConstant::NUMERIC_ZERO;
                    }
                    $sessiondata['searchmine'.$cid] = $searchmine;
                    $this->writesessiondata($sessiondata,$sessionId);
                } else if (isset($sessiondata['lastsearch'.$cid])) {
                    $safesearch = $sessiondata['lastsearch'.$cid]; //str_replace("+"," ",$sessiondata['lastsearch'.$cid]);
                    $search = stripslashes($safesearch);
                    $search = str_replace('"','&quot;',$search);
                    $searchall = $sessiondata['searchall'.$cid];
                    $searchmine = $sessiondata['searchmine'.$cid];
                } else {
                    $search = '';
                    $searchall = AppConstant::NUMERIC_ZERO;
                    $searchmine = AppConstant::NUMERIC_ZERO;
                    $safesearch = '';
                }
                if (trim($safesearch)=='') {
                    $searchlikes = '';
                } else {
                    if (substr($safesearch,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_SIX)=='regex:') {
                        $safesearch = substr($safesearch,AppConstant::NUMERIC_SIX);
                        $searchlikes = "imas_questionset.description REGEXP '$safesearch' AND ";
                    } else {
                        $searchterms = explode(" ",$safesearch);
                        $searchlikes = '';
                        foreach ($searchterms as $k=>$v) {
                            if (substr($v,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_FIVE) == 'type=') {
                                $searchlikes .= "imas_questionset.qtype='".substr($v,AppConstant::NUMERIC_FIVE)."' AND ";
                                unset($searchterms[$k]);
                            }
                        }
                        $searchlikes .= "((imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') ";
                        if (substr($safesearch,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_THREE)=='id=') {
                            $searchlikes = "imas_questionset.id='".substr($safesearch,AppConstant::NUMERIC_THREE)."' AND ";
                        } else if (is_numeric($safesearch)) {
                            $searchlikes .= "OR imas_questionset.id='$safesearch') AND ";
                        } else {
                            $searchlikes .= ") AND";
                        }
                    }
                }

                if (isset($params['libs'])) {
                    if ($params['libs']=='') {
                        $params['libs'] = $user['deflib'];
                    }
                    $searchlibs = $params['libs'];
                    $sessiondata['lastsearchlibs'.$aid] = $searchlibs;
                    $this->writesessiondata($sessiondata, $sessionId);
                } else if (isset($params['listlib'])) {
                    $searchlibs = $params['listlib'];
                    $sessiondata['lastsearchlibs'.$aid] = $searchlibs;
                    $searchall = AppConstant::NUMERIC_ZERO;
                    $sessiondata['searchall'.$aid] = $searchall;
                    $sessiondata['lastsearch'.$aid] = '';
                    $searchlikes = '';
                    $search = '';
                    $safesearch = '';
                    $this->writesessiondata($sessiondata, $sessionId);
                }else if (isset($sessiondata['lastsearchlibs'.$aid])) {
                    $searchlibs = $sessiondata['lastsearchlibs'.$aid];
                } else {
                    if (isset($CFG['AMS']['guesslib']) && count($existingq)>AppConstant::NUMERIC_ZERO) {
                        $maj = count($existingq)/AppConstant::NUMERIC_TWO;
                        $existingqlist = implode(',',$existingq);  //pulled from database, so no quotes needed
                        $query = LibraryItems::getByQuestionSetId($existingqlist);

                        $foundmaj = false;
                        foreach ($query as $row) {
                            if ($row[1]>=$maj) {
                                $searchlibs = $row[0];
                                $foundmaj = true;
                                break;
                            }
                        }
                        if (!$foundmaj) {
                            $searchlibs = $user['deflib'];
                        }
                    } else {
                        $searchlibs = $user['deflib'];
                    }
                }
                $llist = "'".implode("','",explode(',',$searchlibs))."'";

                if (!$beentaken) {
                    //potential questions
                    $libsortorder = array();
                    if (substr($searchlibs,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ONE)=="0") {
                        $lnamesarr[0] = "Unassigned";
                        $libsortorder[0] = AppConstant::NUMERIC_ZERO;
                    }

                    $query = Libraries::getByIdList($llist);
                    foreach ($query as $row) {
                        $lnamesarr[$row['id']] = $row['name'];
                        $libsortorder[$row['id']] = $row['sortorder'];
                    }
                    $lnames = implode(", ",$lnamesarr);

                    $page_libRowHeader = ($searchall==AppConstant::NUMERIC_ONE) ? "<th>Library</th>" : "";

                    if (isset($search)) {
                        $result = QuestionSet::getByUserIdJoin($searchall,$userId,$llist,$searchmine,$searchlikes);
                        if ($search=='recommend' && count($existingq)>AppConstant::NUMERIC_ZERO) {
                            $existingqlist = implode(',',$existingq);  //pulled from database, so no quotes needed
                            $result = QuestionSet::getByUserId($aid,$userId,$existingqlist);
                        }
                        if ($result==AppConstant::NUMERIC_ZERO) {
                            $noSearchResults = true;
                        } else {
                            $alt=AppConstant::NUMERIC_ZERO;
                            $lastlib = -AppConstant::NUMERIC_ONE;
                            $i=AppConstant::NUMERIC_ZERO;
                            $page_questionTable = array();
                            $page_libstouse = array();
                            $page_libqids = array();
                            $page_useavgtimes = false;

                            foreach ( $result as $line){
                                if ($newonly && in_array($line['id'],$existingq)) {
                                    continue;
                                }
                                if (isset($page_questionTable[$line['id']])) {
                                    continue;
                                }
                                if ($lastlib!=$line['libid'] && isset($lnamesarr[$line['libid']])) {
                                    $page_libstouse[] = $line['libid'];
                                    $lastlib = $line['libid'];
                                    $page_libqids[$line['libid']] = array();
                                }

                                if (isset($libsortorder[$line['libid']]) && $libsortorder[$line['libid']]==AppConstant::NUMERIC_ONE) { //alpha
                                    $page_libqids[$line['libid']][$line['id']] = $line['description'];
                                } else { //id
                                    $page_libqids[$line['libid']][] = $line['id'];
                                }
                                $i = $line['id'];
                                $page_questionTable[$i]['checkbox'] = "<input type=checkbox name='nchecked[]' value='" . $line['id'] . "' id='qo$ln'>";
                                if (in_array($i,$existingq)) {
                                    $page_questionTable[$i]['desc'] = '<span style="color: #999">'.filter($line['description']).'</span>';
                                } else {
                                    $page_questionTable[$i]['desc'] = filter($line['description']);
                                }
                                $page_questionTable[$i]['preview'] = "<button class='question-preview-btn'><img class = 'small-preview-icon' src='".AppUtility::getAssetURL().'img/prvAssess.png'."' onClick=\"previewq('selq','qo$ln',{$line['id']},true,false)\">&nbsp;Preview</button>";
                                $page_questionTable[$i]['type'] = $line['qtype'];
                                $avgtimepts = explode(',', $line['avgtime']);
                                if ($avgtimepts[0]>AppConstant::NUMERIC_ZERO) {
                                    $page_useavgtimes = true;
                                    $page_questionTable[$i]['avgtime'] = round($avgtimepts[0]/60,AppConstant::NUMERIC_ONE);
                                } else if (isset($avgtimepts[1]) && isset($avgtimepts[3]) && $avgtimepts[3]>10) {
                                    $page_useavgtimes = true;
                                    $page_questionTable[$i]['avgtime'] = round($avgtimepts[1]/60,AppConstant::NUMERIC_ONE);
                                } else {
                                    $page_questionTable[$i]['avgtime'] = '';
                                }
                                if (isset($avgtimepts[3]) && $avgtimepts[3]>10) {
                                    $page_questionTable[$i]['qdata'] = array($avgtimepts[2],$avgtimepts[1],$avgtimepts[3]);
                                }
                                if ($searchall==AppConstant::NUMERIC_ONE) {
                                    $page_questionTable[$i]['lib'] = "<a href=".AppUtility::getURLFromHome('question','question/add-question?cid='.$cid.'&aid='.$aid.'&listlib='.$line['libid']).">List lib</a>";
                                } else {
                                    $page_questionTable[$i]['junkflag'] = $line['junkflag'];
                                    $page_questionTable[$i]['libitemid'] = $line['libitemid'];
                                }
                                $page_questionTable[$i]['extref'] = '';
                                $page_questionTable[$i]['cap'] = AppConstant::NUMERIC_ZERO;
                                if ($line['extref']!='') {
                                    $extref = explode('~~',$line['extref']);
                                    $hasvid = false;  $hasother = false; $hascap = false;
                                    foreach ($extref as $v) {
                                        if (substr($v,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_FIVE)=="Video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
                                            $hasvid = true;
                                            if (strpos($v,'!!1')!==false) {
                                                $page_questionTable[$i]['cap'] = AppConstant::NUMERIC_ONE;
                                            }
                                        } else {
                                            $hasother = true;
                                        }
                                    }
                                    if ($hasvid) {
                                        $page_questionTable[$i]['extref'] .= "<img src=".AppUtility::getHomeURL().'/img/video_tiny.png'.">";
                                    }
                                    if ($hasother) {
                                        $page_questionTable[$i]['extref'] .= "<img src=".AppUtility::getHomeURL().'/img/html_tiny.png'.">";
                                    }
                                }
                                if ($line['solution']!='' && ($line['solutionopts']&AppConstant::NUMERIC_TWO)==AppConstant::NUMERIC_TWO) {
                                    $page_questionTable[$i]['extref'] .= "<img src=".AppUtility::getHomeURL().'/img/assess_tiny.png'."/>";
                                }
                                $page_questionTable[$i]['times'] = AppConstant::NUMERIC_ZERO;

                                if ($line['ownerid']==$userId) {
                                    if ($line['userights']==AppConstant::NUMERIC_ZERO) {
                                        $page_questionTable[$i]['mine'] = "Private";
                                    } else {
                                        $page_questionTable[$i]['mine'] = "Yes";
                                    }
                                } else {
                                    $page_questionTable[$i]['mine'] = "";
                                }


                                $page_questionTable[$i]['add'] = "<a class='btn btn-primary add-btn-question' href=".AppUtility::getURLFromHome('question','question/mod-question?qsetid='.$line['id'].'&aid='.$aid.'&cid='.$cid)."><img class = 'small-preview-icon' src='".AppUtility::getAssetURL().'img/addItem.png'."'>&nbsp; Add</a>";

                                if ($line['userights']>AppConstant::NUMERIC_THREE || ($line['userights']==AppConstant::NUMERIC_THREE && $line['groupid']==$groupid) || $line['ownerid']==$userId) {
                                    $page_questionTable[$i]['src'] = "<a href=".AppUtility::getURLFromHome('question','question/mod-data-set?id='.$line['id'].'&aid='.$aid.'&cid='.$cid.'&frompot=1').">Edit</a>";
                                } else {
                                    $page_questionTable[$i]['src'] = "<a href=".AppUtility::getURLFromHome('question','question/view-source?id='.$line['id'].'&aid='.$aid.'&cid='.$cid).">View</a>";
                                }

                                $page_questionTable[$i]['templ'] = "<a href=".AppUtility::getURLFromHome('question','question/mod-data-set?id='.$line['id'].'&aid='.$aid.'&cid='.$cid.'&template='.true).">Template</a>";
                                //$i++;
                                $ln++;

                            } //end while

                            //pull question useage data
                            if (count($page_questionTable)>AppConstant::NUMERIC_ZERO) {
                                $allusedqids = implode(',', array_keys($page_questionTable));
                                $query = Questions::getByQuestionSetId($allusedqids);
                                foreach ($query as $row) {
                                    $page_questionTable[$row[0]]['times'] = $row[1];
                                }
                            }

                            //sort alpha sorted libraries
                            foreach ($page_libstouse as $libid) {
                                if ($libsortorder[$libid]==AppConstant::NUMERIC_ONE) {
                                    natcasesort($page_libqids[$libid]);
                                    $page_libqids[$libid] = array_keys($page_libqids[$libid]);
                                }
                            }
                            if ($searchall==AppConstant::NUMERIC_ONE) {
                                $page_libstouse = array_keys($page_libqids);
                            }

                        }
                    }

                }

            } else if ($sessiondata['selfrom'.$aid]=='assm') { //select from assessments
                if (isset($params['clearassmt'])) {
                    unset($sessiondata['aidstolist'.$aid]);
                }
                if (isset($params['achecked'])) {
                    if (count($params['achecked'])!=AppConstant::NUMERIC_ZERO) {
                        $aidstolist = $params['achecked'];
                        $sessiondata['aidstolist'.$aid] = $aidstolist;
                        $this->writesessiondata($sessiondata,$sessionId);
                    }
                }
                if (isset($sessiondata['aidstolist'.$aid])) { //list questions

                    $aidlist = "'".implode("','",addslashes_deep($sessiondata['aidstolist'.$aid]))."'";
                    $query = Assessments::getByAssessmentIds($aidlist);
                    foreach ( $query as $row ) {
                       $aidnames[$row['id']] = $row['name'];
                        $items = str_replace('~',',',$row['itemorder']);
                        if ($items=='') {
                            $aiditems[$row['id']] = array();
                        } else {
                            $aiditems[$row['id']] = explode(',',$items);
                        }
                    }
                    $x=AppConstant::NUMERIC_ZERO;
                    $page_assessmentQuestions = array();
                    foreach ($sessiondata['aidstolist'.$aid] as $aidq) {
                        $query = Questions::getByAssessmentIdJoin($aidq);
                        if ($query==AppConstant::NUMERIC_ZERO) { //maybe defunct aid; if no questions in it, skip it
                            continue;
                        }
                        foreach ($query as $row) {
                            $qsetid[$row[0]] = $row[1];
                            $descr[$row[0]] = $row[2];
                            $qtypes[$row[0]] = $row[3];
                            $owner[$row[0]] = $row[4];
                            $userights[$row[0]] = $row[5];
                            $extref[$row[0]] = $row[6];
                            $qgroupid[$row[0]] = $row[7];
                            $result2 = Questions::getQuestionCount($row[1]);
                            $times[$row[0]] = $result2[0];
                        }

                        $page_assessmentQuestions['desc'][$x] = $aidnames[$aidq];
                        $y=AppConstant::NUMERIC_ZERO;
                        foreach($aiditems[$aidq] as $qid) {
                            if (strpos($qid,'|')!==false) { continue;}
                            $page_assessmentQuestions[$x]['checkbox'][$y] = "<input type=checkbox name='nchecked[]' id='qo$ln' value='" . $qsetid[$qid] . "'>";
                            if (in_array($qsetid[$qid],$existingq)) {
                                $page_assessmentQuestions[$x]['desc'][$y] = '<span style="color: #999">'.filter($descr[$qid]).'</span>';
                            } else {
                                $page_assessmentQuestions[$x]['desc'][$y] = filter($descr[$qid]);
                            }
                            //$page_assessmentQuestions[$x]['desc'][$y] = $descr[$qid];
                            $page_assessmentQuestions[$x]['qsetid'][$y] = $qsetid[$qid];
                            $page_assessmentQuestions[$x]['preview'][$y] = "<input type=button value=\"Preview\" onClick=\"previewq('selq','qo$ln',$qsetid[$qid],true)\"/>";
                            $page_assessmentQuestions[$x]['type'][$y] = $qtypes[$qid];
                            $page_assessmentQuestions[$x]['times'][$y] = $times[$qid];
                            $page_assessmentQuestions[$x]['mine'][$y] = ($owner[$qid]==$userId) ? "Yes" : "" ;
                            $page_assessmentQuestions[$x]['add'][$y] = "<a href=".AppUtility::getURLFromHome('question','question/mod-question?qsetid='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid).">Add</a>";
                            $page_assessmentQuestions[$x]['src'][$y] = ($userights[$qid]>AppConstant::NUMERIC_THREE || ($userights[$qid]==AppConstant::NUMERIC_THREE && $qgroupid[$qid]==$groupid) || $owner[$qid]==$userId) ? "<a href=".AppUtility::getURLFromHome('question','question/mod-data-set?id='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid.'&frompot=1').">Edit</a>" : "<a href=".AppUtility::getURLFromHome('question','question/view-source?id='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid).">View</a>" ;
                            $page_assessmentQuestions[$x]['templ'][$y] = "<a href=".AppUtility::getURLFromHome('question','question/mod-data-set?id='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid.'&template=true').">Template</a>";
                            $page_assessmentQuestions[$x]['extref'][$y] = '';
                            $page_assessmentQuestions[$x]['cap'][$y] = AppConstant::NUMERIC_ZERO;
                            if ($extref[$qid]!='') {
                                $extrefarr = explode('~~',$extref[$qid]);
                                $hasvid = false;  $hasother = false;
                                foreach ($extrefarr as $v) {
                                    if (substr($v,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_FIVE)=="Video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
                                        $hasvid = true;
                                        if (strpos($v,'!!1')!==false) {
                                            $page_assessmentQuestions[$x]['cap'][$y] = AppConstant::NUMERIC_ONE;
                                        }
                                    } else {
                                        $hasother = true;
                                    }
                                }
                                if ($hasvid) {
                                    $page_assessmentQuestions[$x]['extref'][$y] .= "<img src=".AppUtility::getHomeURL().'/img/video_tiny.png'."/>";
                                }
                                if ($hasother) {
                                    $page_assessmentQuestions[$x]['extref'][$y] .= "<img src=".AppUtility::getHomeURL().'/img/html_tiny.png'."/>";
                                }
                            }

                            $ln++;
                            $y++;
                        }
                        $x++;
                    }
                } else {  //choose assessments
                    $items = unserialize($course['itemorder']);
                    $itemassoc = array();
                    $result = Items::getByAssessmentId($cid,$aid);
                    foreach ($result as $row){
                        $itemassoc[$row['itemid']] = $row;
                    }
                    $i=AppConstant::NUMERIC_ZERO;
                    $page_assessmentList = $this->addtoassessmentlist($items,$i,$itemassoc);
                }
            }
        }
        $this->includeCSS(['question/question.css','course/course.css','roster/roster.css']);
        $this->includeJS(['jquery.min.js','question/addqsort.js','question/addquestions.js','tablesorter.js','general.js','question/junkflag.js']);
        $responseArray = array('course' => $course,'assessmentId' => $aid,'params' => $params, 'overwriteBody'=>$overwriteBody, 'body'=> $body,
            'defpoints' => $defpoints,'searchlibs' => $searchlibs,'beentaken' => $beentaken, 'pageAssessmentName' => $page_assessmentName,
            'itemorder' => $itemorder, 'sessiondata' => $sessiondata, 'jsarr'=>$jsarr, 'displaymethod' => $displaymethod,'lnames'=>$lnames,
            'search'=>$search,'searchall'=>$searchall, 'searchmine'=> $searchmine,'newonly'=>$newonly,'noSearchResults'=>$noSearchResults,
            'pageLibRowHeader'=>$page_libRowHeader,'pageUseavgtimes'=>$page_useavgtimes,'pageLibstouse'=>$page_libstouse,'altr'=>$alt,
            'lnamesarr' => $lnamesarr, 'pageLibqids' => $page_libqids, 'pageQuestionTable' => $page_questionTable,'qid'=>$qid,
            'pageAssessmentQuestions'=> $page_assessmentQuestions, 'pageAssessmentList' => $page_assessmentList, 'address' => $address);
        return $this->renderWithData('addQuestions',$responseArray);
    }

    public function addtoassessmentlist($items,$i,$itemassoc) {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->addtoassessmentlist($item['items'],$i,$itemassoc);
            } else if (isset($itemassoc[$item])) {
                $page_assessmentList[$i]['id'] = $itemassoc[$item]['id'];
                $page_assessmentList[$i]['name'] = $itemassoc[$item]['name'];
                $itemassoc[$item]['summary'] = strip_tags($itemassoc[$item]['summary']);
                if (strlen($itemassoc[$item]['summary'])>100) {
                    $itemassoc[$item]['summary'] = substr($itemassoc[$item]['summary'],AppConstant::NUMERIC_ZERO,97).'...';
                }
                $page_assessmentList[$i]['summary'] = $itemassoc[$item]['summary'];
                $i++;
            }
        }
        return $page_assessmentList;
    }

    public function actionSaveQuestions(){
        return  $this->redirect(AppUtility::getURLFromHome('site','work-in-progress'));
    }

    public function actionAddVideoTimes(){
        return  $this->redirect(AppUtility::getURLFromHome('site','work-in-progress'));
    }

    public function actionCategorize(){
        return  $this->redirect(AppUtility::getURLFromHome('site','work-in-progress'));
    }

    public function actionPrintTest(){
        return  $this->redirect(AppUtility::getURLFromHome('site','work-in-progress'));
    }

    public function actionAssessEndMsg(){
        return  $this->redirect(AppUtility::getURLFromHome('site','work-in-progress'));
    }

    public function actionLibraryTree(){
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $myRights = $user['rights'];
        $libraryData = Libraries::getAllLibrariesByJoin();
        $this->includeCSS(['question/libtree.css']);
        $this->includeJS(['general.js','tablesorter.js','question/addquestions.js','question/addqsort.js','question/junkflag.js','question/libtree2.js']);
        $renderData = array('myRights'=>$myRights,'params'=>$params, 'libraryData'=>$libraryData);
        return  $this->renderWithData('questionLibraries',$renderData);
    }

    public function actionModDataSet(){
        $user = $this->getAuthenticatedUser();
        $myRights = $user['rights'];
        $userId = $user['id'];
        $userFullName = $user['FirstName'] . ' ' . $user['LastName'];
        $groupId = $user['groupid'];
        $userdeflib = $user['deflib'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $sessionData = $this->getSessionData($this->getSessionId());
        $teacherId = $this->isTeacher($userId,$courseId);
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
            $params['qtext'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm','$1', $params['qtext']);
            $params['solution'] = preg_replace('/<span\s+class="AM"[^>]*>(.*?)<\/span>/sm','$1', $params['solution']);

            if (trim($params['solution'])=='<p></p>') {
                $params['solution'] = '';
            }

            if (strpos($params['qtext'],'data:image')!==false) {
                require("../includes/htmLawed.php");
                $params['qtext'] = convertdatauris($params['qtext']);
            }
            $params['qtext'] = addslashes($params['qtext']);
            $params['solution'] = addslashes($params['solution']);

            //handle help references
            if (isset($params['id']) || isset($params['templateid'])) {
                if (isset($params['id'])) {
                    $query = Questions::getById($params['id']);
                } else {
                    $query = Questions::getById($params['templateid']);
                }
                $extref = $query['extref'];
                if ($extref=='') {
                    $extref = array();
                } else {
                    $extref = explode('~~',$extref);
                }

                $newextref = array();
                for ($i=AppConstant::NUMERIC_ZERO;$i<count($extref);$i++) {
                    if (!isset($params["delhelp-$i"])) {
                        $newextref[] = $extref[$i];
                    }
                }
            } else {
                $newextref = array();
            }
            //DO we need to add a checkbox or something for updating this if captions are added later?
            if ($params['helpurl']!='') {
                $vidid = $this->getvideoid($params['helpurl']);
                if ($vidid=='') {
                    $captioned = AppConstant::NUMERIC_ZERO;
                } else {
                    $ctx = stream_context_create(array('http'=>
                        array(
                            'timeout' => AppConstant::NUMERIC_ONE
                        )
                    ));
                    $t = @file_get_contents('http://video.google.com/timedtext?lang=en&v='.$vidid, false, $ctx);
                    $captioned = ($t=='')?AppConstant::NUMERIC_ZERO:AppConstant::NUMERIC_ONE;
                }
                $newextref[] = $params['helptype'].'!!'.$params['helpurl'].'!!'.$captioned;
            }
            $extref = implode('~~',$newextref);
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
            $params['qtext'] = preg_replace('/<([^<>]+?)>/',"&&&L$1&&&G",$params['qtext']);
            $params['qtext'] = str_replace(array("<",">"),array("&lt;","&gt;"),$params['qtext']);
            $params['qtext'] = str_replace(array("&&&L","&&&G"),array("<",">"),$params['qtext']);
            $params['solution'] = preg_replace('/<([^<>]+?)>/',"&&&L$1&&&G",$params['solution']);
            $params['solution'] = str_replace(array("<",">"),array("&lt;","&gt;"),$params['solution']);
            $params['solution'] = str_replace(array("&&&L","&&&G"),array("<",">"),$params['solution']);
            $params['description'] = str_replace(array("<",">"),array("&lt;","&gt;"),$params['description']);

            if (isset($params['id'])) { //modifying existing
                $qsetid = intval($params['id']);
                $isok = true;
                if ($isGrpAdmin) {
                    $query = QuestionSet::getByGroupId($params['id'],$groupId);
                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                        $isok = false;
                    }
                }
                if (!$isAdmin && !$isGrpAdmin) {  //check is owner or is allowed to modify
                    $query = QuestionSet::getByUserIdGroupId($params['id'],$userId,$groupId);
                    if (count($query) == AppConstant::NUMERIC_ZERO) {
                        $isok = false;
                    }
                }
                $query = QuestionSet::updateQuestionSet($params,$now,$extref,$replaceby,$solutionopts);

                //checked separately above now
                if ($isok) {
                    if (count($query) > AppConstant::NUMERIC_ZERO) {
                        $outputmsg .= "Question Updated. ";
                    } else {
                        $outputmsg .= "Library Assignments Updated. ";
                    }
                }
                $query = QImages::getByQuestionSetId($params['id']);
                $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
                $imgcnt = count($query);
                foreach ($query as $row) {
                    if (isset($params['delimg-'.$row['id']])) {
                        $file = QImages::getByFileName($row['filename']);
                        if (count($file) == AppConstant::NUMERIC_ONE) { //don't delete if file is used in other questions
                            filehandler::deleteqimage($row['filename']);
                        }
                        QImages::deleteById($row['id']);
                        $imgcnt--;
                        if ($imgcnt == AppConstant::NUMERIC_ZERO) {
                            QuestionSet::setHasImage($params['id'], AppConstant::NUMERIC_ZERO);
                        }
                    } else if ($row['var']!=$params['imgvar-'.$row['id']] || $row['alttext']!=$params['imgalt-'.$row['id']]) {
                        $newvar = str_replace('$','',$params['imgvar-'.$row['id']]);
                        $newalt = $params['imgalt-'.$row['id']];
                        $disallowedvar = array('link','qidx','qnidx','seed','qdata','toevalqtxt','la','GLOBALS','laparts','anstype','kidx','iidx','tips','options','partla','partnum','score');
                        if (in_array($newvar,$disallowedvar)) {
                            $errmsg .= "<p>$newvar is not an allowed variable name</p>";
                        } else {
                            QImages::setVariableAndText($row['id'], $newvar, $newalt);
                        }
                    }
                }
                if ($replaceby!= AppConstant::NUMERIC_ZERO) {
                    Questions::setQuestionSetId($qsetid,$replaceby);
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
                $questionSetArray['ancestors'] = $ancestors;
                $questionSetArray['ancestorauthors'] = $ancestorauthors;
                $questionSetArray['extref'] = $extref;
                $questionSetArray['replaceby'] = $replaceby;
                $questionSetArray['solution'] = $params['solution'];
                $questionSetArray['solutionopts'] = $solutionopts;
                $questionSet = new QuestionSet();
                $qsetid = $questionSet->createQuestionSet($questionSetArray);
                $params['id'] = $qsetid;

                if (isset($params['templateid'])) {
                    $query = QImages::getByQuestionSetId($params['templateid']);
                    foreach ($query as $row) {
                        if (!isset($params['delimg-'.$row['id']])) {
                            $qImage = new QImages();
                            $qImage->createQImages($qsetid,$row);
                        }
                    }
                }

                if (isset($params['makelocal'])) {
                    Questions::setQuestionSetIdById($qsetid, $params['makelocal']);
                    $outputmsg .= AppConstant::Question_OUTPUT_MSG1;
                    $frompot = AppConstant::NUMERIC_ZERO;
                } else {
                    $outputmsg .= AppConstant::Question_OUTPUT_MSG2;
                    $frompot = AppConstant::NUMERIC_ONE;
                }
            }
            //upload image files if attached
            if ($_FILES['imgfile']['name']!='') {
                $disallowedvar = array('link','qidx','qnidx','seed','qdata','toevalqtxt','la','GLOBALS','laparts','anstype','kidx','iidx','tips','options','partla','partnum','score');
                if (trim($params['newimgvar'])=='') {
                    $errmsg .= AppConstant::IMAGE_FILE_ERROR1;
                } else if (in_array($params['newimgvar'],$disallowedvar)) {
                    $errmsg .= $newvar. AppConstant::IMAGE_FILE_ERROR2 ;
                } else {
                    $uploaddir = AppConstant::UPLOAD_DIRECTORY.'/qimages/';
                    $userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['imgfile']['name']));
                    $filename = $userfilename;

                    $result_array = getimagesize($_FILES['imgfile']['tmp_name']);
                    if ($result_array === false) {
                        $errmsg .= "<p>File is not image file</p>";
                    } else {
                        if (($filename= filehandler::storeuploadedqimage('imgfile',$filename))!==false) {
                            $params['newimgvar'] = str_replace('$','',$params['newimgvar']);
                            $filename = addslashes($filename);
                            $questImageData  = array();
                            $questionSetArray['var'] = $params['newimgvar'];
                            $questionSetArray['filename'] = $filename;
                            $questionSetArray['alttext'] = $params['newimgalt'];
                            $qImage = new QImages();
                            $qImage->createQImages($qsetid,$questImageData);
                            QuestionSet::setHasImage($qsetid, AppConstant::NUMERIC_ONE);
                        } else {
                            echo "<p>Error uploading image file!</p>\n";
                            exit;
                        }
                    }
                }
            }
            //update libraries
            $newlibs = explode(",",$params['libs']);

            if (in_array(AppConstant::ZERO_VALUE,$newlibs) && count($newlibs)> AppConstant::NUMERIC_ONE) {
                array_shift($newlibs);
            }

            if ($params['libs']=='') {
                $newlibs = array();
            }
            $libraryData = LibraryItems::getByGroupId($groupId, $qsetid,$userId,$isGrpAdmin,$isAdmin);
            $existing = array();
            foreach($libraryData as $row) {
                $existing[] = $row['libid'];
            }

            $toadd = array_values(array_diff($newlibs,$existing));
            $toremove = array_values(array_diff($existing,$newlibs));

            while(count($toremove)>AppConstant::NUMERIC_ZERO && count($toadd)>AppConstant::NUMERIC_ZERO) {
                $tochange = array_shift($toremove);
                $torep = array_shift($toadd);
                LibraryItems::setLibId($torep,$qsetid,$tochange);
            }
            if (count($toadd)>AppConstant::NUMERIC_ZERO) {
                foreach($toadd as $libid) {
                    $libArray = array();
                    $libArray['libid'] = $libid;
                    $libArray['qsetid'] = $qsetid;
                    $libArray['ownerid'] = $userId;
                    $lib = new LibraryItems();
                    $lib->createLibraryItems($libArray);
                }
            } else if (count($toremove)>AppConstant::NUMERIC_ZERO) {
                foreach($toremove as $libid) {
                    LibraryItems::deleteLibraryItems($libid,$qsetid);
                }
            }
            if (count($newlibs)==AppConstant::NUMERIC_ZERO) {
                $query = LibraryItems::getByQid($qsetid);
                if (count($query)==AppConstant::NUMERIC_ZERO) {
                    $libArray = array();
                    $libArray['libid'] = AppConstant::NUMERIC_ZERO;
                    $libArray['qsetid'] = $qsetid;
                    $libArray['ownerid'] = $userId;
                    $lib = new LibraryItems();
                    $lib->createLibraryItems($libArray);
                }
            }
            if (!isset($params['aid'])) {
                $outputmsg .= "<a href=".AppUtility::getURLFromHome('question','question/manage-qset?cid='.$courseId).">Return to Question Set Management</a>\n";
            } else {
                if ($frompot==AppConstant::NUMERIC_ONE) {
                    $outputmsg .=  "<a href=".AppUtility::getURLFromHome('question','question/mod-question?qsetid='.$qsetid.'&cid='.$courseId.'&aid='.$params['aid'].'&process=true&usedef=true').">Add Question to Assessment using Defaults</a> | \n";
                    $outputmsg .=  "<a href=".AppUtility::getURLFromHome('question','question/mod-question?qsetid='.$qsetid.'&cid='.$courseId.'&aid='.$params['aid']).">Add Question to Assessment</a> | \n";
                }
                $outputmsg .=  "<a href=".AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$params['aid']).">Return to Assessment</a>\n";
            }
            if ($params['test']=="Save and Test Question") {
                $outputmsg .= "<script>addr = '".AppUtility::getURLFromHome('question','question/test-question?cid='.$courseId.'&qsetid='.$params['id'])."';";
                $outputmsg .= "previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));\n";
                $outputmsg .= "previewpop.focus();";
                $outputmsg .= "</script>";
            } else {
                if ($errmsg == '' && !isset($params['aid'])) {
                    AppUtility::getURLFromHome('question','question/manage-qset?cid='.$courseId);
                } else if ($errmsg == '' && $frompot==AppConstant::NUMERIC_ZERO) {
                    AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$params['aid']);
                } else {
                    echo $errmsg;
                    echo $outputmsg;
                }
                exit;
            }
        }
        $myname = $user['LastName'].','.$user['FirstName'];
        if (isset($params['id'])) {
            $line = QuestionSet::getByQSetIdJoin($params['id']);
            $myq = ($line['ownerid']==$userId);
            if ($isAdmin || ($isGrpAdmin && $line['groupid']==$groupId) || ($line['userights']==AppConstant::NUMERIC_THREE && $line['groupid']==$groupId) || $line['userights']>AppConstant::NUMERIC_THREE) {
                $myq = true;
            }
            $namelist = explode(", mb ",$line['author']);
            if ($myq && !in_array($myname,$namelist)) {
                $namelist[] = $myname;
            }
            if (isset($params['template'])) {
                $author = $myname;
                $myq = true;
            } else {
                $author = implode(", mb ",$namelist);
            }
            foreach ($line as $k=>$v) {
                $line[$k] = str_replace('&','&amp;',$v);
            }

            $inlibs = array();
            if($line['extref']!='') {
                $extref = explode('~~',$line['extref']);
            } else {
                $extref = array();
            }
            $images = array();
            $images['vars'] = array();
            $images['files'] = array();
            $images['alttext'] = array();
            if ($line['hasimg']>AppConstant::NUMERIC_ZERO) {
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
                    if ($usedeflib==AppConstant::NUMERIC_ONE) {
                        $inlibs[] = $deflib;
                    } else {
                        $query = Libraries::getByQSetId($params['id']);
                        foreach ($query as $row) {
                            if ($row['userights'] == AppConstant::NUMERIC_EIGHT || ($row['groupid']==$groupId && ($row['userights']%AppConstant::NUMERIC_THREE==AppConstant::NUMERIC_TWO)) || $row['ownerid']==$userId) {
                                $inlibs[] = $row['id'];
                            }
                        }
                    }
                }
                $locklibs = array();
                $addmod = "Add";
                $line['userights'] = $user['qrightsdef'];

            } else {
                $query = LibraryItems::getDestinctLibIdByIdAndOwner($groupId,$params['id'],$userId,$isGrpAdmin,$isAdmin);
                foreach ($query as $row) {
                    $inlibs[] = $row['libid'];
                }

                $locklibs = array();
                if (!$isAdmin) {
                    $query = LibraryItems::getLibIdByQidAndOwner($groupId,$params['id'],$userId,$isGrpAdmin,$isAdmin);
                    foreach ($query as $row) {
                        $locklibs[] = $row['libid'];
                    }
                }
                $addmod = "Modify";
                $inusecnt = Questions::getQidCount($userId,$params['id']);
            }

            if (count($inlibs)==AppConstant::NUMERIC_ZERO && count($locklibs)==AppConstant::NUMERIC_ZERO) {
                $inlibs = array(AppConstant::NUMERIC_ZERO);
            }
            $inlibs = implode(",",$inlibs);
            $locklibs = implode(",",$locklibs);

            $twobx = ($line['qcontrol']=='' && $line['answer']=='');

            $line['qtext'] = preg_replace('/<span class="AM">(.*?)<\/span>/','$1',$line['qtext']);
        } else {
            $myq = true;
            $twobx = true;
            $line['description'] = AppConstant::QUESTION_DESCRIPTION;
            $line['userights'] = $user['qrightsdef'];
            $line['license'] = isset($CFG['GEN']['deflicense'])?$CFG['GEN']['deflicense']:AppConstant::NUMERIC_ONE;
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
            if (isset($params['aid']) && isset($sessiondata['lastsearchlibs'.$params['aid']])) {
                $inlibs = $sessiondata['lastsearchlibs'.$params['aid']];
            } else if (isset($sessiondata['lastsearchlibs'.$courseId])) {
                $inlibs = $sessiondata['lastsearchlibs'.$courseId];
            } else {
                $inlibs = $userdeflib;
            }
            $locklibs='';
            $images = array();
            $extref = array();
            $author = $myname;
            $inlibssafe = "'".implode("','",explode(',',$inlibs))."'";
            if (!isset($params['id']) || isset($params['template'])) {
                $query = Libraries::getByIdList($inlibssafe);
                foreach ($query as $row) {
                    if ($row['userights'] == AppConstant::NUMERIC_EIGHT || ($row['groupid']==$groupId && ($row['userights']%AppConstant::NUMERIC_THREE==AppConstant::NUMERIC_TWO)) || $row['ownerid']==$userId) {
                        $oklibs[] = $row['id'];
                    }
                }
                if (count($oklibs)>AppConstant::NUMERIC_ZERO) {
                    $inlibs = implode(",",$oklibs);
                } else {$inlibs = AppConstant::ZERO_VALUE;}
            }
            $addmod = "Add";
        }
        $inlibssafe = "'".implode("','",explode(',',$inlibs))."'";

        $lnames = array();
        if (substr($inlibs,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ONE)===AppConstant::ZERO_VALUE) {
            $lnames[] = "Unassigned";
        }
        $inlibssafe = "'".implode("','",explode(',',$inlibs))."'";
        $query = Libraries::getByIdList($inlibssafe);
        foreach ($query as $row) {
            $lnames[] = $row['name'];
        }
        $lnames = implode(", ",$lnames);
        $this->includeJS(['editor/tiny_mce.js','ASCIIMathTeXImg_min.js']);
        $renderData = array('course' => $course, 'addMode' => $addmod, 'params' => $params,'inusecnt' => $inusecnt, 'line'=> $line, 'myq' => $myq,
            'frompot' => $frompot, 'author' => $author, 'userId' => $userId, 'groupId' => $groupId, 'isAdmin' => $isAdmin, 'isGrpAdmin' => $isGrpAdmin,
            'inlibs' => $inlibs, 'locklibs' => $locklibs, 'lnames' => $lnames, 'twobx' => $twobx, 'images'=> $images, 'extref' => $extref,
            'myRights' => $myRights, 'sessionData' => $sessionData);
        return  $this->renderWithData('modDataSet',$renderData);
    }

    public function stripsmartquotes($text) {
        $text = str_replace(
            array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text);
        return $text;
    }

    public function getvideoid($url) {
        $vidid = '';
        if (strpos($url,'youtube.com/watch')!==false) {
            //youtube
            $vidid = substr($url,strrpos($url,'v=')+2);
            if (strpos($vidid,'&')!==false) {
                $vidid = substr($vidid,AppConstant::NUMERIC_ZERO,strpos($vidid,'&'));
            }
            if (strpos($vidid,'#')!==false) {
                $vidid = substr($vidid,AppConstant::NUMERIC_ZERO,strpos($vidid,'#'));
            }
            $vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
        } else if (strpos($url,'youtu.be/')!==false) {
            //youtube
            $vidid = substr($url,strpos($url,'.be/')+AppConstant::NUMERIC_FOUR);
            if (strpos($vidid,'#')!==false) {
                $vidid = substr($vidid,AppConstant::NUMERIC_ZERO,strpos($vidid,'#'));
            }
            if (strpos($vidid,'?')!==false) {
                $vidid = substr($vidid,AppConstant::NUMERIC_ZERO,strpos($vidid,'?'));
            }
            $vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
        }
        return $vidid;
    }

    public function actionModTutorialQuestion(){

    }

    public function actionModQuestion(){
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $userId = $user['id'];
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $assessmentId = $params['aid'];
        $overwriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $pagetitle = "Question Settings";
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
            if ($params['process']== true) {
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
                    if (trim($params['points'])=="") {
                        $points = AppConstant::QUARTER_NINE;
                    } else {
                        $points = intval($params['points']);
                    }
                    if (trim($params['attempts'])=="") {
                        $attempts = AppConstant::QUARTER_NINE;
                    } else {
                        $attempts = intval($params['attempts']);
                    }
                    if (trim($params['penalty'])=="") {
                        $penalty = AppConstant::QUARTER_NINE;
                    } else {
                        $penalty = intval($params['penalty']);
                    }
                    if ($penalty!= AppConstant::QUARTER_NINE) {
                        if ($params['skippenalty']==AppConstant::NUMERIC_TEN) {
                            $penalty = 'L'.$penalty;
                        } else if ($params['skippenalty']>AppConstant::NUMERIC_ZERO) {
                            $penalty = 'S'.$params['skippenalty'].$penalty;
                        }
                    }
                    $regen = $params['regen'] + AppConstant::NUMERIC_THREE*$params['allowregen'];
                    $showans = $params['showans'];
                    $rubric = intval($params['rubric']);
                    $showhints = intval($params['showhints']);
                }
                $questionArray = array();
                $questionArray['points'] = $points;
                $questionArray['attempts'] = $attempts;
                $questionArray['penalty'] = $penalty;
                $questionArray['regen'] = $regen;
                $questionArray['showans'] = $showans;
                $questionArray['rubric'] = $rubric;
                $questionArray['showhints'] = $showhints;
                $questionArray['assessmentid'] = $assessmentId;
                if (isset($params['id'])) { //already have id - updating
                    if (isset($params['replacementid']) && $params['replacementid']!='' && intval($params['replacementid'])!= AppConstant::NUMERIC_ZERO) {
                        $questionArray['questionsetid'] = intval($params['replacementid']);
                    }
                    Questions::updateQuestionFields($questionArray,$params['id']);
                    if (isset($params['copies']) && $params['copies']>AppConstant::NUMERIC_ZERO) {
                        $query = Questions::getById($params['id']);
                        $params['qsetid'] = $query['questionsetid'];
                    }
                }
                if (isset($params['qsetid'])) { //new - adding
                    $query = Assessments::getByAssessmentId($assessmentId);
                    $itemorder = $query['itemorder'];
                    $questionArray['questionsetid'] = $params['qsetid'];
                    for ($i=AppConstant::NUMERIC_ZERO;$i<$params['copies'];$i++) {
                        $question = new Questions();
                        $qid = $question->addQuestions($questionArray);
                        //add to itemorder
                        if (isset($params['id'])) { //am adding copies of existing  
                            $itemarr = explode(',',$itemorder);
                            $key = array_search($params['id'],$itemarr);
                            array_splice($itemarr,$key+AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO,$qid);
                            $itemorder = implode(',',$itemarr);
                        } else {
                            if ($itemorder=='') {
                                $itemorder = $qid;
                            } else {
                                $itemorder = $itemorder . ",$qid";
                            }
                        }
                    }
                    Assessments::UpdateItemOrder($itemorder,$assessmentId);
                }
                return $this->redirect(AppUtility::getURLFromHome('question','question/add-questions?cid='.$courseId.'&aid='.$assessmentId));
            } else { //DEFAULT DATA MANIPULATION
                if (isset($params['id'])) {
                    $line = Questions::getById($params['id']);
                    if ($line['penalty']{AppConstant::NUMERIC_ZERO}==='L') {
                        $line['penalty'] = substr($line['penalty'],AppConstant::NUMERIC_ONE);
                        $skippenalty = AppConstant::NUMERIC_TEN ;
                    } else if ($line['penalty']{AppConstant::NUMERIC_ZERO}==='S') {
                        $skippenalty = $line['penalty']{AppConstant::NUMERIC_ONE};
                        $line['penalty'] = substr($line['penalty'],AppConstant::NUMERIC_TWO);
                    } else {
                        $skippenalty = AppConstant::NUMERIC_ZERO;
                    }

                    if ($line['points'] == AppConstant::QUARTER_NINE) {$line['points']='';}
                    if ($line['attempts'] == AppConstant::QUARTER_NINE) {$line['attempts']='';}
                    if ($line['penalty'] == AppConstant::QUARTER_NINE) {$line['penalty']='';}
                } else {
                    //set defaults
                    $line['points']="";
                    $line['attempts']="";
                    $line['penalty']="";
                    $skippenalty = AppConstant::NUMERIC_ZERO;
                    $line['regen'] = AppConstant::NUMERIC_ZERO;
                    $line['showans']= AppConstant::ZERO_VALUE;
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
                $query = AssessmentSession::getAssessmentIDs($assessmentId,$courseId);
                if (count($query) > AppConstant::NUMERIC_ZERO) {
                    $pageBeenTakenMsg = "<h3>Warning</h3>\n";
                    $pageBeenTakenMsg .= "<p>This assessment has already been taken.  Altering the points or penalty will not change the scores of students who already completed this question. ";
                    $pageBeenTakenMsg .= "If you want to make these changes, or add additional copies of this question, you should clear all existing assessment attempts</p> ";
                    $pageBeenTakenMsg .= "<p><input type=button value=\"Clear Assessment Attempts\" onclick=\"window.location='add-questions.php?cid=$courseId&aid=$assessmentId&clearattempts=ask'\"></p>\n";
                    $beentaken = true;
                } else {
                    $beentaken = false;
                }
            }
        }
        $renderData = array('course'=>$course,'overwriteBody' => $overwriteBody, 'body' => $body, 'pageBeenTakenMsg' => $pageBeenTakenMsg,
            'courseId' => $courseId, 'assessmentId' => $assessmentId, 'beentaken' => $beentaken, 'params' => $params, 'skippenalty' => $skippenalty,
            'line' => $line, 'rubricNames' => $rubric_names,'rubricVals' => $rubric_vals);
        return $this->renderWithData('modQuestion',$renderData);
    }

    public function actionTestQuestion(){
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
        $pagetitle = "Test Question";
        $asid = AppConstant::NUMERIC_ZERO;
        $teacherId = $this->isTeacher($userId, $courseId);
        //CHECK PERMISSIONS AND SET FLAGS
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            $overwriteBody = AppConstant::NUMERIC_ONE;
            $body = AppConstant::NO_TEACHER_RIGHTS;
        } else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
            $useeditor = AppConstant::NUMERIC_ONE;
            if (isset($params['seed'])) {
                $seed = $params['seed'];
                $attempt = AppConstant::NUMERIC_ZERO;
            } else if (!isset($params['seed']) || isset($params['regen'])) {
                $seed = rand(AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_TEN_THOUSAND);
                $attempt = AppConstant::NUMERIC_ZERO;
            } else {
                $seed = $params['seed'];
                $attempt = $params['attempt']+AppConstant::NUMERIC_ONE;
            }
            if (isset($params['onlychk']) && $params['onlychk']==AppConstant::NUMERIC_ONE) {
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
                if ($onlychk==AppConstant::NUMERIC_ONE) {
                    $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','{$params['loc']}',true);";
                } else {
                    $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','{$params['loc']}');";
                }
            }
            $lastanswers = array('');

            if (isset($params['seed'])) {
                list($score,$rawscores) = scoreq(AppConstant::NUMERIC_ZERO,$params['qsetid'],$params['seed'],$params['qn0']);
                $scores[0] = $score;
                $lastanswers[0] = stripslashes($lastanswers[0]);
                $page_scoreMsg =  "<p>Score on last answer: $score/1</p>\n";
            } else {
                $page_scoreMsg = "";
                $scores = array(AppConstant::NUMERIC_NEGATIVE_ONE);
                $_SESSION['choicemap'] = array();
            }

            $page_formAction = "test-question?cid={$params['cid']}&qsetid={$params['qsetid']}";
            if (isset($params['usecheck'])) {
                $page_formAction .=  "&checked=".$params['usecheck'];
            } else if (isset($params['checked'])) {
                $page_formAction .=  "&checked=".$params['checked'];
            }
            if (isset($params['formn'])) {
                $page_formAction .=  "&formn=".$params['formn'];
                $page_formAction .=  "&loc=".$params['loc'];
            }
            if (isset($params['onlychk'])) {
                $page_formAction .=  "&onlychk=".$params['onlychk'];
            }

            $line = QuestionSet::getUserAndQuestionSetJoin($params['qsetid']);

            $lastmod = date("m/d/y g:i a",$line['lastmoddate']);

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
        $this->includeCSS(['mathquill.css','question/question.css','course/course.css','roster/roster.css']);
        $this->includeJS(['eqntips.js','eqnhelper.js','tablesorter.js','question/addquestions.js','general.js','question/addqsort.js','question/junkflag.js']);
        $responseArray = array('course' => $course,'params' => $params, 'overwriteBody' => $overwriteBody, 'body' => $body, 'showtips' => $showtips,
            'eqnhelper' => $eqnhelper, 'page_onlyChkMsg' => $page_onlyChkMsg, 'chk' => $chk, 'formn' => $formn, 'onlychk' => $onlychk, 'page_scoreMsg' => $page_scoreMsg,
            'page_formAction' => $page_formAction, 'seed' => $seed, 'attempt' => $attempt, 'rawscores' => $rawscores, 'line' => $line, 'lastmod' => $lastmod,
            'resultLibNames' => $resultLibNames, 'myRights' => $myRights, 'params' => $params);
        return $this->renderWithData('testQuestion',$responseArray);

    }

    public function actionAddQuestionsSave(){
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        $aid = $params['aid'];
        $teacherId = $this->isTeacher($user['id'], $courseId);
        if (!isset($teacherId)) {
            echo "error: validation";
        }
        $query = Assessments::getByAssessmentId($aid);
        $rawitemorder = $query['itemorder'];
        $viddata = $query['viddata'];
        $itemorder = str_replace('~',',',$rawitemorder);
        $curitems = array();
        foreach (explode(',',$itemorder) as $qid) {
            if (strpos($qid,'|')===false) {
                $curitems[] = $qid;
            }
        }

        $submitted = $params['order'];
        $submitted = str_replace('~',',',$submitted);
        $newitems = array();
        foreach (explode(',',$submitted) as $qid) {
            if (strpos($qid,'|')===false) {
                $newitems[] = $qid;
            }
        }
        $toremove = array_diff($curitems,$newitems);

        if ($viddata != '') {
            $viddata = unserialize($viddata);
            $qorder = explode(',',$rawitemorder);
            $qidbynum = array();
            for ($i=AppConstant::NUMERIC_ZERO;$i<count($qorder);$i++) {
                if (strpos($qorder[$i],'~')!==false) {
                    $qids = explode('~',$qorder[$i]);
                    if (strpos($qids[0],'|')!==false) { //pop off nCr
                        $qidbynum[$i] = $qids[1];
                    } else {
                        $qidbynum[$i] = $qids[0];
                    }
                } else {
                    $qidbynum[$i] = $qorder[$i];
                }
            }

            $qorder = explode(',',$params['order']);
            $newbynum = array();
            for ($i=AppConstant::NUMERIC_ZERO;$i<count($qorder);$i++) {
                if (strpos($qorder[$i],'~')!==false) {
                    $qids = explode('~',$qorder[$i]);
                    if (strpos($qids[0],'|')!==false) { //pop off nCr
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
            $newviddata[0] = $viddata[0];
            for ($i=AppConstant::NUMERIC_ZERO;$i<count($newbynum);$i++) {   //for each new item
                $oldnum = $qidbynumflip[$newbynum[$i]];
                $found = false; //look for old item in viddata
                for ($j=AppConstant::NUMERIC_ONE;$j<count($viddata);$j++) {
                    if (isset($viddata[$j][2]) && $viddata[$j][2]==$oldnum) {
                        //if found, copy data, and any non-question data following
                        $new = $viddata[$j];
                        $new[2] = $i;  //update question number;
                        $newviddata[] = $new;
                        $j++;
                        while (isset($viddata[$j]) && !isset($viddata[$j][2])) {
                            $newviddata[] = $viddata[$j];
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
                    $newviddata[] =  array('','',$i);
                }
            }
            /*
             *any old items will not get copied.
             */
            $viddata = addslashes(serialize($newviddata));
        }

        /*
         * delete any removed questions
         */
        $ids = implode(',',$toremove);
        Questions::deleteById($ids);
        /*
         * store new itemorder
         */
        $query = Assessments::setVidData($params['order'],$viddata,$aid);

        if (count($query)>AppConstant::NUMERIC_ZERO) {
            echo "OK";
        } else {
            echo "error: not saved";
        }
    }
}