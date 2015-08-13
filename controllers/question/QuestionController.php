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

use app\models\QuestionSet;
use app\models\SetPassword;
use app\models\Student;
use app\models\StuGroupSet;
use app\models\Teacher;
use Yii;
use app\components\AppConstant;
class QuestionController extends AppController
{
    public function actionAddQuestions()
    {
        $user = $this->getAuthenticatedUser();
        $groupid = $user['groupid'];
        $params = $this->getRequestParams();
//        AppUtility::dump($params);
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $teacherId = $this->isTeacher($user['id'],$courseId);
        if ($user['rights']==100) {
            $teacherId = $user['id'];
            $adminasteacher = true;
        }
        $overwriteBody=0;
        $body = '';
        $course = Course::getById($courseId);
        $this->checkSession($params);
        $pagetitle = "Add/Remove Questions";

        $curBreadcrumb =  $course['name'];
        if (isset($params['clearattempts']) || isset($params['clearqattempts']) || isset($params['withdraw'])) {
            $curBreadcrumb .= "&gt; <a href=\"question/question/add-questions?cid=" . $params['cid'] . "&aid=" . $params['aid'] . "\">Add/Remove Questions</a> &gt; Confirm\n";
        } else {
            $curBreadcrumb .= "&gt; Add/Remove Questions\n";
        }
        if (!$teacherId) { // loaded by a NON-teacher
            $overwriteBody=1;
            $body = "You need to log in as a teacher to access this page";
        } elseif (!(isset($params['cid'])) || !(isset($params['aid']))) {
            $overwriteBody=1;
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
                    $overwriteBody = 1;
                    $body = "No questions selected.  <a href=".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid).">Go back</a>";
                } else if (isset($params['add'])) {
                    include("modquestiongrid.php");
                    if (isset($params['process'])) {
                        AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid);
                        exit;
                    }
                } else {
                    $checked = $params['nchecked'];
                    foreach ($checked as $qsetid) {
                        $questionData = array(
                            'assessmentid' => $aid,
                            'points' => 9999,
                            'attempts' => 9999,
                            'penalty' => 9999,
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
                            $nextnum = 0;
                        } else {
                            $nextnum = substr_count($assessment['itemorder'],',')+1;
                        }
                        $numnew= count($checked);
                        $viddata = unserialize($viddata);
                        if (!isset($viddata[count($viddata)-1][1])) {
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
                    $overwriteBody = 1;
                    $body = "No questions selected.  <a href=".AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid).">Go back</a>\n";
                } else {
                    require dirname(__FILE__) . '/modquestiongrid.php';
//                    include("modquestiongrid.php");
                    if (isset($params['process'])) {
                        AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid);
                        exit;
                    }
                }
            }
            if (isset($params['clearattempts'])) {
                if ($params['clearattempts']=="confirmed") {
//                    require_once('../includes/filehandler.php');
//                    deleteallaidfiles($aid);
                    AssessmentSession::deleteByAssessmentId($aid);
                    Questions::setWithdrawn($aid,AppConstant::NUMERIC_ZERO);
                    AppUtility::getURLFromHome('question','question/add-questions?cid='.$cid.'&aid='.$aid);
                    exit;
                } else {
                    $overwriteBody = 1;
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
                            if ($list['points']==9999) {
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
                        for ($i=0; $i<count($qarr); $i++) {
                            if (in_array($qarr[$i],$qids)) {
                                if ($params['withdrawtype']=='zero' || $params['withdrawtype']=='groupzero') {
                                    $bestscores[$i] = 0;
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
                    $overwriteBody = 1;
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

            $placeinhead = "<script type=\"text/javascript\">
		var previewqaddr = '".AppUtility::getURLFromHome('question','question/testquestion?cid='.$cid)."';
		var addqaddr = '$address';
		</script>";
            $placeinhead .= "<script type=\"text/javascript\" src=".AppUtility::getHomeURL().'/javascript/question/addquestions.js'."></script>";
            $placeinhead .= "<script type=\"text/javascript\" src=".AppUtility::getHomeURL().'/javascript/question/addqsort.js?v=030315.js'."></script>";
            $placeinhead .= "<script type=\"text/javascript\" src=".AppUtility::getHomeURL().'/javascript/question/junkflag.js'."></script>";
            $placeinhead .= "<script type=\"text/javascript\">var JunkFlagsaveurl = '".AppUtility::getURLFromHome('question','question/savelibassignflag')."';</script>";


            //DEFAULT LOAD PROCESSING GOES HERE
            //load filter.  Need earlier than usual header.php load
            $curdir = rtrim(dirname(__FILE__), '/\\');
//            AppUtility::dump(Yii::$app->basePath."/filter/filter.php");
            require_once (Yii::$app->basePath."/filter/filter.php");
            $query = AssessmentSession::getByAssessmentSessionIdJoin($aid,$cid);
            if (count($query) > 0) {
                $beentaken = true;
            } else {
                $beentaken = false;
            }
            $result = Assessments::getByAssessmentId($aid);
            $itemorder = $result['itemorder'];
            $page_assessmentName = $result['name'];
            $ln = 1;
            $defpoints = $result['defpoints'];
            $displaymethod = $result['displaymethod'];
            $showhintsdef = $result['showhints'];

            $grp0Selected = "";
            if (isset($sessiondata['groupopt'.$aid])) {
                $grp = $sessiondata['groupopt'.$aid];
                $grp1Selected = ($grp==1) ? " selected" : "";
            } else {
                $grp = 0;
                $grp0Selected = " selected";
            }

            $jsarr = '[';
            if ($itemorder != '') {
                $items = explode(",",$itemorder);
            } else {
                $items = array();
            }
            $existingq = array();
            $apointstot = 0;
            for ($i = 0; $i < count($items); $i++) {
                if (strpos($items[$i],'~')!==false) {
                    $subs = explode('~',$items[$i]);
                } else {
                    $subs[] = $items[$i];
                }
                if ($i>0) {
                    $jsarr .= ',';
                }
                if (count($subs)>1) {
                    if (strpos($subs[0],'|')===false) { //for backwards compat
                        $jsarr .= '[1,0,[';
                    } else {
                        $grpparts = explode('|',$subs[0]);
                        $jsarr .= '['.$grpparts[0].','.$grpparts[1].',[';
                        array_shift($subs);
                    }
                }
                for ($j=0;$j<count($subs);$j++) {
                    $line = Questions::getQuestionData($subs[$j]);
                    $line .= "";
                    $existingq[] = $line['questionsetid'];
                    if ($j>0) {
                        $jsarr .= ',';
                    }
                    //output item array
                    $jsarr .= '['.$subs[$j].','.$line['questionsetid'].',"'.addslashes(filter(str_replace(array("\r\n", "\n", "\r")," ",$line['description']))).'","'.$line['qtype'].'",'.$line['points'].',';
                    if ($line['userights']>3 || ($line['userights']==3 && $line['groupid']==$groupid) || $line['ownerid']==$user['id'] || $adminasteacher) { //can edit without template?
                        $jsarr .= '1';
                    } else {
                        $jsarr .= '0';
                    }
                    $jsarr .= ','.$line['withdrawn'];
                    $extrefval = 0;
                    if (($line['showhints']==0 && $showhintsdef==1) || $line['showhints']==2) {
                        $extrefval += 1;
                    }
                    if ($line['extref']!='') {
                        $extref = explode('~~',$line['extref']);
                        $hasvid = false;  $hasother = false;  $hascap = false;
                        foreach ($extref as $v) {
                            if (strtolower(substr($v,0,5))=="video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
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
                            $extrefval += 4;
                        }
                        if ($hasother) {
                            $extrefval += 2;
                        }
                        if ($hascap) {
                            $extrefval += 16;
                        }
                    }
                    if ($line['solution']!='' && ($line['solutionopts']&2)==2) {
                        $extrefval += 8;
                    }
                    $jsarr .= ','.$extrefval;
                    $jsarr .= ']';
                }
                if (count($subs)>1) {
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
                        $searchall = 1;
                    } else {
                        $searchall = 0;
                    }
                    $sessiondata['searchall'.$cid] = $searchall;
                    if (isset($params['searchmine'])) {
                        $searchmine = 1;
                    } else {
                        $searchmine = 0;
                    }
                    if (isset($params['newonly'])) {
                        $newonly = 1;
                    } else {
                        $newonly = 0;
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
                    $searchall = 0;
                    $searchmine = 0;
                    $safesearch = '';
                }
                if (trim($safesearch)=='') {
                    $searchlikes = '';
                } else {
                    if (substr($safesearch,0,6)=='regex:') {
                        $safesearch = substr($safesearch,6);
                        $searchlikes = "imas_questionset.description REGEXP '$safesearch' AND ";
                    } else {
                        $searchterms = explode(" ",$safesearch);
                        $searchlikes = '';
                        foreach ($searchterms as $k=>$v) {
                            if (substr($v,0,5) == 'type=') {
                                $searchlikes .= "imas_questionset.qtype='".substr($v,5)."' AND ";
                                unset($searchterms[$k]);
                            }
                        }
                        $searchlikes .= "((imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') ";
                        if (substr($safesearch,0,3)=='id=') {
                            $searchlikes = "imas_questionset.id='".substr($safesearch,3)."' AND ";
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
                    $searchall = 0;
                    $sessiondata['searchall'.$aid] = $searchall;
                    $sessiondata['lastsearch'.$aid] = '';
                    $searchlikes = '';
                    $search = '';
                    $safesearch = '';
                    $this->writesessiondata($sessiondata, $sessionId);
                }else if (isset($sessiondata['lastsearchlibs'.$aid])) {
                    $searchlibs = $sessiondata['lastsearchlibs'.$aid];
                } else {
                    if (isset($CFG['AMS']['guesslib']) && count($existingq)>0) {
                        $maj = count($existingq)/2;
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
                    if (substr($searchlibs,0,1)=="0") {
                        $lnamesarr[0] = "Unassigned";
                        $libsortorder[0] = 0;
                    }

                    $query = Libraries::getByIdList($llist);
                    foreach ($query as $row) {
                        $lnamesarr[$row['id']] = $row['name'];
                        $libsortorder[$row['id']] = $row['sortorder'];
                    }
                    $lnames = implode(", ",$lnamesarr);

                    $page_libRowHeader = ($searchall==1) ? "<th>Library</th>" : "";

                    if (isset($search)) {
                        $result = QuestionSet::getByUserIdJoin($searchall,$user['id'],$llist,$searchmine,$searchlikes);
                        if ($search=='recommend' && count($existingq)>0) {
                            $existingqlist = implode(',',$existingq);  //pulled from database, so no quotes needed
                            $result = QuestionSet::getByUserId($aid,$user['id'],$existingqlist);
                        }
                        if ($result==0) {
                            $noSearchResults = true;
                        } else {
                            $alt=0;
                            $lastlib = -1;
                            $i=0;
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

                                if (isset($libsortorder[$line['libid']]) && $libsortorder[$line['libid']]==1) { //alpha
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
                                $page_questionTable[$i]['preview'] = "<input type=button value=\"Preview\" onClick=\"previewq('selq','qo$ln',{$line['id']},true,false)\"/>";
                                $page_questionTable[$i]['type'] = $line['qtype'];
                                $avgtimepts = explode(',', $line['avgtime']);
                                if ($avgtimepts[0]>0) {
                                    $page_useavgtimes = true;
                                    $page_questionTable[$i]['avgtime'] = round($avgtimepts[0]/60,1);
                                } else if (isset($avgtimepts[1]) && isset($avgtimepts[3]) && $avgtimepts[3]>10) {
                                    $page_useavgtimes = true;
                                    $page_questionTable[$i]['avgtime'] = round($avgtimepts[1]/60,1);
                                } else {
                                    $page_questionTable[$i]['avgtime'] = '';
                                }
                                if (isset($avgtimepts[3]) && $avgtimepts[3]>10) {
                                    $page_questionTable[$i]['qdata'] = array($avgtimepts[2],$avgtimepts[1],$avgtimepts[3]);
                                }
                                if ($searchall==1) {
                                    $page_questionTable[$i]['lib'] = "<a href=".AppUtility::getURLFromHome('question','question/add-question?cid='.$cid.'&aid='.$aid.'&listlib='.$line['libid']).">List lib</a>";
                                } else {
                                    $page_questionTable[$i]['junkflag'] = $line['junkflag'];
                                    $page_questionTable[$i]['libitemid'] = $line['libitemid'];
                                }
                                $page_questionTable[$i]['extref'] = '';
                                $page_questionTable[$i]['cap'] = 0;
                                if ($line['extref']!='') {
                                    $extref = explode('~~',$line['extref']);
                                    $hasvid = false;  $hasother = false; $hascap = false;
                                    foreach ($extref as $v) {
                                        if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
                                            $hasvid = true;
                                            if (strpos($v,'!!1')!==false) {
                                                $page_questionTable[$i]['cap'] = 1;
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
                                if ($line['solution']!='' && ($line['solutionopts']&2)==2) {
                                    $page_questionTable[$i]['extref'] .= "<img src=".AppUtility::getHomeURL().'/img/assess_tiny.png'."/>";
                                }
                                $page_questionTable[$i]['times'] = 0;

                                if ($line['ownerid']==$user['id']) {
                                    if ($line['userights']==0) {
                                        $page_questionTable[$i]['mine'] = "Private";
                                    } else {
                                        $page_questionTable[$i]['mine'] = "Yes";
                                    }
                                } else {
                                    $page_questionTable[$i]['mine'] = "";
                                }


                                $page_questionTable[$i]['add'] = "<a href=".AppUtility::getURLFromHome('question','question/mod-question?qsetid='.$line['id'].'&aid='.$aid.'&cid='.$cid).">Add</a>";

                                if ($line['userights']>3 || ($line['userights']==3 && $line['groupid']==$groupid) || $line['ownerid']==$user['id']) {
                                    $page_questionTable[$i]['src'] = "<a href=".AppUtility::getURLFromHome('question','question/mod-dataset?id='.$line['id'].'&aid='.$aid.'&cid='.$cid.'&frompot=1').">Edit</a>";
                                } else {
                                    $page_questionTable[$i]['src'] = "<a href=".AppUtility::getURLFromHome('question','question/view-source?id='.$line['id'].'&aid='.$aid.'&cid='.$cid).">View</a>";
                                }

                                $page_questionTable[$i]['templ'] = "<a href=".AppUtility::getURLFromHome('question','question/mod-dataset?id='.$line['id'].'&aid='.$aid.'&cid='.$cid.'&template='.true).">Template</a>";
                                //$i++;
                                $ln++;

                            } //end while

                            //pull question useage data
                            if (count($page_questionTable)>0) {
                                $allusedqids = implode(',', array_keys($page_questionTable));
                                $query = Questions::getByQuestionSetId($allusedqids);
                                foreach ($query as $row) {
                                    $page_questionTable[$row[0]]['times'] = $row[1];
                                }
                            }

                            //sort alpha sorted libraries
                            foreach ($page_libstouse as $libid) {
                                if ($libsortorder[$libid]==1) {
                                    natcasesort($page_libqids[$libid]);
                                    $page_libqids[$libid] = array_keys($page_libqids[$libid]);
                                }
                            }
                            if ($searchall==1) {
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
                    if (count($params['achecked'])!=0) {
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
                    $x=0;
                    $page_assessmentQuestions = array();
                    foreach ($sessiondata['aidstolist'.$aid] as $aidq) {
                        $query = Questions::getByAssessmentIdJoin($aidq);
                        if ($query==0) { //maybe defunct aid; if no questions in it, skip it
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
                        $y=0;
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
                            $page_assessmentQuestions[$x]['mine'][$y] = ($owner[$qid]==$user['id']) ? "Yes" : "" ;
                            $page_assessmentQuestions[$x]['add'][$y] = "<a href=".AppUtility::getURLFromHome('question','question/mod-question?qsetid='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid).">Add</a>";
                            $page_assessmentQuestions[$x]['src'][$y] = ($userights[$qid]>3 || ($userights[$qid]==3 && $qgroupid[$qid]==$groupid) || $owner[$qid]==$user['id']) ? "<a href=".AppUtility::getURLFromHome('question','question/mod-dataset?id='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid.'&frompot=1').">Edit</a>" : "<a href=".AppUtility::getURLFromHome('question','question/view-source?id='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid).">View</a>" ;
                            $page_assessmentQuestions[$x]['templ'][$y] = "<a href=".AppUtility::getURLFromHome('question','question/mod-data-set?id='.$qsetid[$qid].'&aid='.$aid.'&cid='.$cid.'&template=true').">Template</a>";
                            $page_assessmentQuestions[$x]['extref'][$y] = '';
                            $page_assessmentQuestions[$x]['cap'][$y] = 0;
                            if ($extref[$qid]!='') {
                                $extrefarr = explode('~~',$extref[$qid]);
                                $hasvid = false;  $hasother = false;
                                foreach ($extrefarr as $v) {
                                    if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
                                        $hasvid = true;
                                        if (strpos($v,'!!1')!==false) {
                                            $page_assessmentQuestions[$x]['cap'][$y] = 1;
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
                    $i=0;
                    $page_assessmentList = $this->addtoassessmentlist($items,$i,$itemassoc);
                }
            }
        }
        $this->includeCSS(['question/question.css','course/course.css','roster/roster.css']);
        $this->includeJS(['tablesorter.js','question/addquestions.js','general.js','question/addqsort.js','question/junkflag.js']);
        $responseArray = array('course' => $course,'assessmentId' => $aid,'params' => $params, 'overwriteBody'=>$overwriteBody, 'body'=> $body,
            'defpoints' => $defpoints,'searchlibs' => $searchlibs,'beentaken' => $beentaken, 'pageAssessmentName' => $page_assessmentName,
            'itemorder' => $itemorder, 'sessiondata' => $sessiondata, 'jsarr'=>$jsarr, 'displaymethod' => $displaymethod,'lnames'=>$lnames,
            'search'=>$search,'searchall'=>$searchall, 'searchmine'=> $searchmine,'newonly'=>$newonly,'noSearchResults'=>$noSearchResults,
            'pageLibRowHeader'=>$page_libRowHeader,'pageUseavgtimes'=>$page_useavgtimes,'pageLibstouse'=>$page_libstouse,'altr'=>$alt,
            'lnamesarr' => $lnamesarr, '$pageLibqids' => $page_libqids, '$pageQuestionTable' => $page_questionTable,'qid'=>$qid,
            'pageAssessmentQuestions'=> $page_assessmentQuestions, 'pageAssessmentList' => $page_assessmentList);
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
                    $itemassoc[$item]['summary'] = substr($itemassoc[$item]['summary'],0,97).'...';
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
        $groupId = $user['groupid'];
        $params = $this->getRequestParams();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $teacherId = $this->isTeacher($user['id'],$courseId);
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            echo AppConstant::NO_TEACHER_RIGHTS;
            exit;
        }

        $isAdmin = false;
        $isGrpAdmin = false;

        if ($myRights == AppConstant::ADMIN_RIGHT) {
            $teacherId = $user['id'];
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
            require("../includes/filehandler.php");
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
                    $query = QuestionSet::getByUserIdGroupId($params['id'],$user['id'],$groupId);
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
//                            "SELECT id FROM imas_qimages WHERE filename='{$row['filename']}'";
                        $r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
                        if (count($file) == AppConstant::NUMERIC_ONE) { //don't delete if file is used in other questions
                            filehandler::deleteqimage($row['filename']);
                        }
                        QImages::deleteById($row['id']);
                        $imgcnt--;
                        if ($imgcnt == AppConstant::NUMERIC_ZERO) {
                            QuestionSet::setHasImage($params['id']);
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
                $questionSetArray['ownerid'] = $user['id'];
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

            }
        }
        return  $this->redirect(AppUtility::getURLFromHome('site','work-in-progress'));
    }

    public function stripsmartquotes($text) {
        $text = str_replace(
            array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text);
        // Next, replace their Windows-1252 equivalents.
        //removed - was messing with unicode
        /*$text = str_replace(
            array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text);*/
        return $text;
    }

    public function getvideoid($url) {
        $vidid = '';
        if (strpos($url,'youtube.com/watch')!==false) {
            //youtube
            $vidid = substr($url,strrpos($url,'v=')+2);
            if (strpos($vidid,'&')!==false) {
                $vidid = substr($vidid,0,strpos($vidid,'&'));
            }
            if (strpos($vidid,'#')!==false) {
                $vidid = substr($vidid,0,strpos($vidid,'#'));
            }
            $vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
        } else if (strpos($url,'youtu.be/')!==false) {
            //youtube
            $vidid = substr($url,strpos($url,'.be/')+4);
            if (strpos($vidid,'#')!==false) {
                $vidid = substr($vidid,0,strpos($vidid,'#'));
            }
            if (strpos($vidid,'?')!==false) {
                $vidid = substr($vidid,0,strpos($vidid,'?'));
            }
            $vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
        }
        return $vidid;
    }
}