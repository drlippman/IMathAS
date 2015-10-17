<?php
namespace app\controllers\wiki;
use app\components\AppConstant;
use app\components\AppUtility;
use app\components\WikiUtility;
use app\controllers\AppController;
use app\models\Course;
use app\models\Items;
use app\models\StuGroupMembers;
use app\models\Stugroups;
use app\models\StuGroupSet;
use app\models\Wiki;
use app\models\WikiRevision;
use app\models\WikiView;
use app\components\diff;
use app\components\htmLawed;
class WikiController extends AppController
{
    /**
     * display detail of selected wiki
     */
    public function actionShowWiki()
    {
        $userData = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $userId = $userData->id;
        $courseId = $this->getParamVal('courseId');
        $wikiId = $this->getParamVal('wikiId');
        $course = Course::getById($courseId);
        $subject = $this->getRequestParams('wikicontent');
        $wiki = Wiki::getById($wikiId);
        $stugroupId = AppConstant::NUMERIC_ZERO;
        $courseId = intval($this->getParamVal('courseId'));
        $id = intval($this->getParamVal('wikiId'));
        $groupId = intval($this->getParamVal('grp'));
        $frame = $this->getParamVal('framed');
        $delAll = $this->getParamVal('delall');
        $isTeacher = $this->isTeacher($userId, $courseId);
        $delRev = $this->getParamVal('delrev');
        $revert = $this->getParamVal('revert');
        $snapshot = $this->getParamVal('snapshot');
        $toRev = $this->getParamVal('torev');
        $dispRev = $this->getParamVal('disprev');
        if (isset($frame)) {
            $flexWidth = true;
            $showNav = false;
            $framed = "&framed=true";
        } else {
            $showNav = true;
            $framed = '';
        }
        if ($courseId == AppConstant::NUMERIC_ZERO) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to access this page with a course id";
        } else if ($id == AppConstant::NUMERIC_ZERO) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to access this page with a wiki id";
        }  else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
            $studName = Stugroups::getByName($groupId);
            $groupNote = 'group'.$studName[0]['name']."'s";
            $result = Wiki::getDataById($id);
            $row = $result;
            $wikiName = $row['name'];
            $pageTitle = $wikiName;
            $now = time();
            if (!isset($isTeacher) && ($row['avail'] == AppConstant::NUMERIC_ZERO || ($row['avail'] == AppConstant::NUMERIC_ONE && ($now < $row['startdate'] || $now > $row['enddate']))))
            {
                $overWriteBody = AppConstant::NUMERIC_ONE;
                $body = "This wiki is not currently available for viewing";
            } else if (isset($delAll) && ($isTeacher)) {
                if ($delAll == 'true') {
                    WikiRevision::deleteAllRevision($id, $groupId);
                    return $this->redirect('show-wiki?courseId='.$courseId.'&wikiId='.$id);
                } else {
                    $pageTitle = "Confirm Page Contents Delete";
                }
            } else if (isset($delRev) && ($isTeacher)) {
                if ($delRev == 'true') {
                    $result = WikiRevision::getDataWithLimit($id,$groupId);
                    if (count($result) > 0) {
                        $curId = $result[0]['id'];
                        WikiRevision::deleteRevisionHistory($id, $groupId,$curId);
                    }
                    return $this->redirect('show-wiki?courseId='.$courseId.'&wikiId='.$id);
                } else {
                    $pageTitle = "Confirm History Delete";
                }
            } else if (isset($revert) && ($isTeacher)) {
                if ($revert == 'true') {
                    $revision = intval($this->getParamVal('torev'));
                    $result = WikiRevision::getRevision($id, $groupId, $revision);
                    if (count($result) > 1 && $revision > 0) {
                        $row = count($result);
                        $base = diff::diffstringsplit($row['revision']);
                        foreach($result as $key => $row) { //apply diffs
                            $base = diff::diffapplydiff($base,$row['revision']);
                        }
                        $newBase = ($base);
                        WikiRevision::updateRevision($revision, $newBase);
                        WikiRevision::deleteRevision($id, $groupId,$revision);
                    }
                    return $this->redirect('show-wiki?courseId='.$courseId.'&wikiId='.$id);
                } else {
                    $pageTitle = "Confirm Wiki Version Revert";
                }
            } else { //just viewing
                require_once("../filter/filter.php");
                if (isset($isTeacher) || $now < $row['editbydate']) {
                    $canEdit = true;
                } else {
                    $canEdit = false;
                }
                /**
                 * if is group wiki, get groupid or fail
                 */
                if ($row['groupsetid'] > AppConstant::NUMERIC_ZERO && !isset($isTeacher)) {
                    $isGroup = true;
                    $groupSetId = $row['groupsetid'];
                    $groupResult = Stugroups::getStuGrpId($userId, $groupSetId);
                    if (count($groupResult) == AppConstant::NUMERIC_ZERO) {
                        $overWriteBody = AppConstant::NUMERIC_ONE;
                        $body = "You need to be a member of a group before you can view or edit this wiki";
                        $isGroup = false;
                    } else {
                        $groupId = $groupResult[0]['id'];
                        $curGroupName = $groupResult[0]['name'];
                    }
                } else if ($row['groupsetid'] > AppConstant::NUMERIC_ZERO && isset($isTeacher)) {
                    $isGroup = true;
                    $groupSetId = $row['groupsetid'];
                    $stugroup_ids = array();
                    $stugroup_names = array();
                    $hasNew = array();
                    $wikiLastViews = array();
                    $wikiViewResult = WikiView::getByUserIdAndWikiId($userId, $id);
                    foreach($wikiViewResult as $key => $row){
                        $wikiLastViews[$row['stugroupid']] = $row['lastview'];
                    }

                    $wikiRevisionResult = WikiRevision::getByIdWithMaxTime($id);
                    foreach($wikiRevisionResult as $key => $row){
                        if (!isset($wikiLastViews[$row['stugroupid']]) || $wikiLastViews[$row['stugroupid']] < $row['time']) {
                            $hasNew[$row['stugroupid']] = AppConstant::NUMERIC_ONE;
                        }
                    }
                    $i = AppConstant::NUMERIC_ONE;
                    $studGrpResult = Stugroups::getByGrpSetOrderByName($groupSetId);

                    foreach($studGrpResult as $key => $row)
                    {
                        $stugroup_ids[$i] = $row['id'];
                        $stugroup_names[$i] = $row['name'] . ((isset($hasNew[$row['id']]))?' (New Revisions)':'');
                        if ($row['id'] == $groupId)
                        {
                            $curGroupName = $row['name'];
                        }
                        $i++;
                    }
                    if ($groupId == AppConstant::NUMERIC_ZERO) {
                        if (count($stugroup_ids) == AppConstant::NUMERIC_ZERO) {
                            $overWriteBody = AppConstant::NUMERIC_ONE;
                            $body = "No groups exist yet.  There have to be groups before you can view their wikis";
                            $isGroup = false;
                        } else {
                            $groupId = $stugroup_ids['id'];
                            $curGroupName = $stugroup_names['id'];
                        }
                    }
                } else {
                    $groupId = AppConstant::NUMERIC_ZERO;
                }

                if ($groupId > AppConstant::NUMERIC_ZERO) {
                    $grpmem = '<p>Group Members: <ul class="nomark">';
                    $studGrpMemResult = StuGroupMembers::getStudAndUserData($groupId);
                    foreach($studGrpMemResult as $key => $row){
                        $grpmem .= "<li>{$row['LastName']}, {$row['FirstName']}</li>";
                    }
                    $grpmem .= '</ul></p>';
                }

                $revisionResult = WikiRevision::getRevisionTotalData($id, $groupId);
                $numRevisions = count($revisionResult);
                if ($numRevisions == AppConstant::NUMERIC_ZERO) {
                    $text = '';
                } else {
                    $row = $revisionResult[0];
                    $text = $row['revision'];
                    if (strlen($text) > 6 && substr($text,0,6) == '**wver') {
                        $wikiVer = substr($text,6,strpos($text,'**',6)-6);
                        $text = substr($text,strpos($text,'**',6)+2);
                    } else {
                        $wikiVer = AppConstant::NUMERIC_ONE;
                    }
                    $lastEditTime = AppUtility::tzdate("F j, Y, g:i a",$row['time']);
                    $lastEditedBy = $row['LastName'].', '.$row['FirstName'];
                }
                if (isset($studentid)) {
                    $rec = "data-base=\"wikiintext-$id\" ";
                    $text = str_replace('<a ','<a '.$rec, $text);
                }
                $affectedRow = new WikiView();
                $affectedRow->updateLastView($userId, $id, $groupId,$now);
                if ($affectedRow == AppConstant::NUMERIC_ZERO) {
                    $wikiView = new WikiView();
                    $wikiView->addWikiView($userId, $id, $groupId, $now);
                }
            }
        }
        $revisionTotalData = WikiRevision::getRevisionTotalData($wikiId, $stugroupId, $userId);
        $wikiTotalData = Wiki::getAllData($wikiId);
        $wikiRevisionData = WikiRevision::getByRevisionId($wikiId);
        $count = $wikiRevisionData ;
        $wikiRevisionSortedByTime = '';
        foreach($wikiRevisionData as $singleWikiData){
            $sortBy = $singleWikiData->id;
            $order = AppConstant::DESCENDING;
            $wikiRevisionSortedByTime = WikiRevision::getEditedWiki($sortBy, $order,$singleWikiData->id);

        }
        $this->includeCSS(['course/wiki.css']);
        $responseData = array('body' => $subject,'course' => $course, 'revisionTotalData'=> $revisionTotalData, 'wikiTotalData'=>$wikiTotalData, 'wiki' => $wiki, 'wikiRevisionData' => $wikiRevisionSortedByTime, 'userData' => $userData, 'countOfRevision' => $count, 'wikiId' => $wikiId, 'courseId' => $courseId, 'pageTitle' => $pageTitle, 'groupNote'=> $groupNote, 'isTeacher' => $isTeacher, 'delAll' => $delAll, 'delRev' => $delRev, 'groupId' => $groupId, 'curGroupName' => $curGroupName, 'text' => $text, 'numRevisions' => $numRevisions,
            'canEdit' => $canEdit, 'id' => $id, 'framed' => $framed, 'snapshot' => $snapshot, 'lastEditTime' => $lastEditTime, 'lastEditedBy' => $lastEditedBy, 'revert' => $revert, 'dispRev' => $dispRev, 'toRev' => $toRev);
        return $this->renderWithData('showWiki', $responseData);
    }

    public function actionClearPageContentAjax()
    {
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $wikiId = $params['wikiId'];
        $responseData = array('courseId' => $courseId, 'wikiId' => $wikiId);
        return $this->successResponse($responseData);
    }
    public function actionClearPageHistoryAjax()
    {
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $wikiId = $params['wikiId'];
        $responseData = array('courseId' => $courseId, 'wikiId' => $wikiId);
        return $this->successResponse($responseData);
    }

    public function actionGetRevisions(){
        $param = $this->getRequestParams();
        $courseId = $param['courseId'];
        $wikiId = $param['wikiId'];
        $revisions = WikiUtility::getWikiRevision($courseId, $wikiId);
        return $revisions;
    }
    /**
     * to edit wiki page
     */
    public function actionEditPage()
    {
        $user = $this->getAuthenticatedUser();
        $userId = $user['id'];
        $this->layout = 'master';
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $id = $this->getParamVal('wikiId');
        $params = $this->getRequestParams();
        $frame = $this->getParamVal('framed');
        $overWriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        $useEditor = "wikicontent";
        $isTeacher = $this->isTeacher($userId, $courseId);

        if (isset($frame)) {
            $flexWidth = true;
            $showNav = false;
            $framed = "&framed=true";
        } else {
            $showNav = true;
            $framed = '';
        }
        if ($courseId == AppConstant::NUMERIC_ZERO) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to access this page with a course id";
        } else if ($id == AppConstant::NUMERIC_ZERO) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "You need to access this page with a wiki id";
        }  else {
            $result = Wiki::getDataById($id);
            $row = ($result);
            $wikiName = $row['name'];
            $now = time();

            if (!($isTeacher) && ($row['avail'] == 0 || ($row['avail'] == 1 && ($now < $row['startdate'] || $now > $row['enddate'])) || $now > $row['editbydate']))
            {
                $overWriteBody = AppConstant::NUMERIC_ONE;
                $body = "This wiki is not currently available for editing";
            }  else {
                if ($row['groupsetid'] > AppConstant::NUMERIC_ZERO) {
                    if ($isTeacher) {
                        $groupId = intval($this->getParamVal('grp'));
                        $result = Stugroups::getByName($groupId);
                        $groupName = $result[0]['name'];
                    } else {
                        $groupSetId = $row['groupsetid'];
                        $groupResult = Stugroups::getStuGrpId($userId, $groupSetId);
                        $groupId = $groupResult[0]['id'];
                        $groupName = $groupResult[0]['name'];
                    }
                } else {
                    $groupId = AppConstant::NUMERIC_ZERO;
                }
                if ($params['wikicontent']!= null)
                {
                /**
                 * FORM SUBMITTED, DATA PROCESSING
                 */
                    $inConflict = false;
                    $stuGroupId = AppConstant::NUMERIC_ZERO;

                    /*
                     * clean up wiki content
                     */
//                    $htmLawedConfig = array('elements'=>'*-script');
//                    $wikiContent = htmLawed::htmLawed(stripslashes($params['wikiContent']),$htmLawedConfig);
                    $wikicontent = $params['wikicontent'];
                    $wikicontent = str_replace(array("\r","\n"),' ',$wikicontent);
                    $wikicontent = preg_replace('/\s+/',' ',$wikicontent);
                    $now = time();

                    /*
                     * check for conflicts
                     */
                    $result = WikiRevision::getDataToCheckConflict($id, $groupId);
                    if (($result) > 0)
                    {
                        /*
                         * editing existing wiki
                         */
                        $row = ($result);
                        $revisionId = $row['id'];
                        $revisionText = $row['revision'];

                        if ($revisionId != $params['baserevision'])
                        {
                        /**
                         * someone else has updated this wiki since we retrieved it
                         */
                            $inConflict = true;
                            $lastEditTime = AppUtility::tzdate("F j, Y, g:i a",$row['time']);
                            $lastEditedBy = $row['LastName'].','.$row['FirstName'];
                        } else {
                         /**
                          * we're all good for a diff calculation
                          */

                            $diff = diff::diffsparsejson($wikicontent,$revisionText);

                            if ($diff != '')
                            {
                                $diffStr = $diff;
                                $wikicontent = ($wikicontent);
                                if ($wikiVer > 1) {
                                    $wikicontent = '**wver'.$wikiVer.'**'.$wikicontent;
                                }
                                /*
                                 * insert latest content
                                 */
                                $revisionData= new WikiRevision();
                                $revisionData->saveRevision($id,$groupId,$userId,$wikicontent,$now);
                                /*
                                 * replace previous version with diff off current version
                                 */
                                WikiRevision::updateRevision($revisionId, $diffStr);
                            }
                        }
                    } else {
                        /**
                         *  no wiki page exists yet - just need to insert revision
                         */
                        $wikicontent = ('**wver2**'.$wikicontent);
                        $firstInsertRevision = new WikiRevision();
                        $firstInsertRevision->saveRevision($id,$groupId,$userId,$wikicontent,$now);
                    }
                    if (!$inConflict) {
                        return $this->redirect('show-wiki?courseId='.$courseId.'&wikiId='.$id);
                    }
                } else {
                    $result = WikiRevision::getDataToCheckConflict($id, $groupId);
                    if ($result > 0)
                    {
                    /**
                     * wikipage exists already
                     */
                        $row = ($result);
                        $lastEditTime = AppUtility::tzdate("F j, Y, g:i a",$row['time']);
                        $lastEditedBy = $row['LastName'].', '.$row['FirstName'];
                        $revisionId = $row['id'];
                        $revisionText = str_replace('</span></p>','</span> </p>',$row['revision']);
                        if (strlen($revisionText) > 6 && substr($revisionText,0,6)=='**wver')
                        {
                            $wikiVer = substr($revisionText,6,strpos($revisionText,'**',6)-6);
                            $revisionText = substr($revisionText,strpos($revisionText,'**',6)+2);
                        } else {
                            $wikiVer = 1;
                        }
                    } else {
                        /**
                         *  new wikipage
                         */
                        $revisionId = AppConstant::NUMERIC_ZERO;
                        $revisionText = '';
                    }
                    $inConflict = false;
                }
            }
        }
        $this->includeJS(['course/inlineText.js','editor/tiny_mce.js','editor/tiny_mce_src.js','general.js']);
        $responseData = array('wikiName' => $wikiName, 'groupId' => $groupId, 'groupName' => $groupName, 'inConflict' => $inConflict, 'wikicontent' => $wikicontent, 'lastEditedBy' => $lastEditedBy,'lastEditTime' => $lastEditTime, 'courseId' => $courseId, 'id' => $id,
        'grp' => $groupId, 'revisionId' => $revisionId, 'revisionText' => $revisionText, 'course' => $course);
        return $this->renderWithData('editPage', $responseData);
    }

   public function actionAddWiki()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $courseId = $this->getParamVal('cid');
        $wikiId = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $wiki = Wiki::getById($wikiId);
        $block  = $this->getParamVal('block');
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $params = $this->getRequestParams();
        $wikiid = $params['id'];
        $saveTitle = '';
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $this->noValidRights($teacherId);
        if (isset($params['tb'])) {
            $filter = $params['tb'];
        } else {
            $filter = 'b';
        }
        if ($params['id']) {
            $wiki = Wiki::getById($params['id']);

            if ($wiki['description'] == '') {
                $line['description'] = "<p>Enter description here (displays on course page)</p>";
            }
            $startDate = $wiki['startdate'];
            $endDate = $wiki['enddate'];
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
            $saveTitle = "Modify Wiki";
            $saveButtonTitle = "Save Changes";
            $defaultValues = array(
                'title' => $wiki['name'],
                'description' => $wiki['description'],
                'startDate' => $startDate,
                'endDate' => $endDate,
                'sDate' => $sDate,
                'sTime' =>$sTime,
                'eDate' => $eDate,
                'eTime' => $eTime,
                'pageTitle' => $saveTitle,
                'saveTitle' => AppConstant::SAVE_BUTTON,
                'avail' => $wiki['avail'],
            );
        }
        else {
            $defaultValues = array(
                'pageTitle' => 'Create Wiki',
                'saveTitle' => AppConstant::New_Item,
                'title' => "Enter title here",
                'description' => "Enter wiki description here (displays on course page)",
                'sDate' => date("m/d/Y"),
                'sTime' => time(),
                'eDate' => date("m/d/Y",strtotime("+1 week")),
                'eTime' => time(),
                'calendar' => AppConstant::NUMERIC_ZERO,
                'avail' => AppConstant::NUMERIC_ONE,
                'rdatetype' => date("m/d/Y",strtotime("+1 week")),
            );
        }
        $page_formActionTag = "?block=$block&cid=$courseId&folder=" . $params['folder'];
        $page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
        $page_formActionTag .= "&tb=$filter";
        if ($this->isPost()) {
            if ($wikiid) {
                $link = new Wiki();
                $link->updateChange($params);
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' .$course->id));
            } else{
                if ($params['avail']== AppConstant::NUMERIC_ONE) {
                    if ($params['available-after'] == '0') {
                        $startDate = AppConstant::NUMERIC_ZERO;
                    } else if ($params['available-after'] == 'now') {
                        $startDate = time();
                    } else {
                        $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
                    }
                    if ($params['available-until'] == '2000000000') {
                        $endDate = AppConstant::ALWAYS_TIME;
                    } else {
                        $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
                    }
                } else if ($params['avail'] == AppConstant::NUMERIC_TWO) {
                    if ($params['place-on-calendar-always'] == AppConstant::NUMERIC_ZERO) {
                        $startDate = AppConstant::NUMERIC_ZERO;
                    } else {
                        $startDate = AppUtility::parsedatetime($params['cdate'],"12:00 pm");
                    }
                    $endDate =  AppConstant::ALWAYS_TIME;
                } else {
                    $startDate = AppConstant::NUMERIC_ZERO;
                    $endDate =  AppConstant::ALWAYS_TIME;
                }
                $finalArray['courseid'] = $params['cid'];
                $finalArray['title'] = $params['name'];
                $finalArray['description'] = $params['description'];
                $finalArray['avail'] = $params['avail'];
                $finalArray['startdate'] = $startDate;
                $finalArray['enddate'] = $endDate;
//                $page_formActionTag = AppUtility::getURLFromHome('course', 'course/add-wiki?courseId=' .$course->id.'&block='.$block);
                $saveChanges = new Wiki();
                $lastWikiId = $saveChanges->createItem($finalArray);
                $saveItems = new Items();
                $lastItemsId = $saveItems->saveItems($courseId, $lastWikiId, 'Wiki');
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemorder = $courseItemOrder->itemorder;
                $items = unserialize($itemorder);
                $blocktree = explode('-',$block);
                $sub =& $items;

                for ($i=1;$i<count($blocktree);$i++) {
                    $sub =& $sub[$blocktree[$i]-1]['items'];
                }
                if ($filter=='b') {
                    $sub[] = $lastItemsId;
                } else if ($filter=='t') {
                    array_unshift($sub,$lastItemsId);
                }
                $itemorder = (serialize($items));
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' .$course->id));
            }
        }
        $this->includeJS(["course/inlineText.js","editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor.js']);
        $this->includeCSS(["roster/roster.css", 'course/items.css']);
        $returnData = array('course' => $course, 'saveTitle' => $saveTitle, 'wiki' => $wiki, 'groupNames' => $groupNames, 'defaultValue' => $defaultValues, 'page_formActionTag' => $page_formActionTag);
        return $this->render('addWiki', $returnData);
    }
}