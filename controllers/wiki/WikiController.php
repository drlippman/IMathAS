<?php
namespace app\controllers\wiki;
use app\components\AppConstant;
use app\components\AppUtility;
use app\components\WikiUtility;
use app\controllers\AppController;
use app\models\Course;
use app\models\Items;
use app\models\StuGroupSet;
use app\models\Wiki;
use app\models\WikiRevision;

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
        $responseData = array('body' => $subject,'course' => $course, 'revisionTotalData'=> $revisionTotalData, 'wikiTotalData'=>$wikiTotalData, 'wiki' => $wiki, 'wikiRevisionData' => $wikiRevisionSortedByTime, 'userData' => $userData, 'countOfRevision' => $count, 'wikiId' => $wikiId, 'courseId' => $courseId);
        return $this->renderWithData('showWiki', $responseData);
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
        $userData = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $wikiId = $this->getParamVal('wikiId');
        $wiki = Wiki::getById($wikiId);
        $wikiRevisionData = WikiRevision::getByRevisionId($wikiId);
        $wikiRevisionSortedByTime = '';
        $wikiRevision = WikiRevision::getByRevisionId($wikiId);
        foreach($wikiRevisionData as $singleWikiData){
            $sortBy = $singleWikiData->id;
            $order = AppConstant::DESCENDING;
            $wikiRevisionSortedByTime = WikiRevision::getEditedWiki($sortBy, $order,$singleWikiData->id);
        }
        if ($this->isPostMethod())
        {
            $params = $this->getRequestParams();
            $saveRevision = new WikiRevision();
            $saveRevision->saveRevision($params);
        }
        $this->includeCSS(['course/wiki.css']);
        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
        $responseData = array('wiki' => $wiki, 'course' => $course, 'wikiRevision' => $wikiRevision, 'wikiRevisionData' => $wikiRevisionSortedByTime, 'userData' => $userData);
        return $this->renderWithData('editPage', $responseData);
    }

    public function wikiEditedRevisionData($wikiRevisionData, $wikiData)
    {
        $revisiontext = $wikiRevisionData->revision;
        if ($wikiRevisionData->revision!= null) { //FORM SUBMITTED, DATA PROCESSING
            require_once("../components/htmLawed.php");
            $htmlawedconfig = array('elements'=>'*-script');
            $wikicontent = htmLawed(stripslashes($wikiData['body']),$htmlawedconfig);
            $wikicontent = str_replace(array("\r","\n"),' ',$wikicontent);
            $wikicontent = preg_replace('/\s+/',' ',$wikicontent);
            if (strlen($revisiontext)>6 && substr($revisiontext,0,6)=='**wver') {
                $wikiver = substr($revisiontext,6,strpos($revisiontext,'**',6)-6);
            } else {
                $wikiver = 1;
            }
        }
    }

    public function actionAddWiki()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = "master";
        $courseId = $this->getParamVal('courseId');
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
        if ($this->isPost()) {
            if ($wikiid) {
                $link = new Wiki();
                $link->updateChange($params);
                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
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
            $finalArray['courseid'] = $params['courseId'];
            $finalArray['title'] = $params['name'];
            $finalArray['description'] = $params['description'];
            $finalArray['avail'] = $params['avail'];
            $finalArray['startdate'] = $startDate;
            $finalArray['enddate'] = $endDate;
            $page_formActionTag = AppUtility::getURLFromHome('course', 'course/add-wiki?courseId=' .$course->id.'&block='.$block);
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
            array_unshift($sub,intval($lastItemsId));
            $itemorder = (serialize($items));
            $saveItemOrderIntoCourse = new Course();
            $saveItemOrderIntoCourse->setItemOrder($itemorder, $courseId);
            return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
            }
        }
        $this->includeJS(["course/inlineText.js","editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor.js']);
        $this->includeCSS(["roster/roster.css", 'course/items.css']);
        $returnData = array('course' => $course, 'saveTitle' => $saveTitle, 'wiki' => $wiki, 'groupNames' => $groupNames, 'defaultValue' => $defaultValues);
        return $this->render('addWiki', $returnData);
    }
}