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
        $userId = $userData->id;
        $courseId = $this->getParamVal('courseId');
        $wikiId = $this->getParamVal('wikiId');
        $course = Course::getById($courseId);
        $subject = $this->getBodyParams('wikicontent');
        $wiki = Wiki::getById($wikiId);
        $stugroupId = 0;
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
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $wikiId = $this->getParamVal('wikiId');
        $wiki = Wiki::getById($wikiId);
        $wikiRevisionData = WikiRevision::getByRevisionId($wikiId);
        $stugroupId = 0;
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

        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
        $responseData = array('wiki' => $wiki, 'course' => $course, 'wikiRevision' => $wikiRevision, 'wikiRevisionData' => $wikiRevisionSortedByTime, 'userData' => $userData);
        return $this->renderWithData('editPage', $responseData);
    }
    public function wikiEditedRevisionData($wikiRevisionData, $wikiData)
    {
        $revisiontext = $wikiRevisionData->revision;
        $revisionid = $wikiRevisionData->id;
        if ($wikiRevisionData->revision!= null) { //FORM SUBMITTED, DATA PROCESSING
            $inconflict = false;
            $stugroupId = 0;

            //clean up wiki content
            require_once("../components/htmLawed.php");
            $htmlawedconfig = array('elements'=>'*-script');
            $wikicontent = htmLawed(stripslashes($wikiData['body']),$htmlawedconfig);
            $wikicontent = str_replace(array("\r","\n"),' ',$wikicontent);
            $wikicontent = preg_replace('/\s+/',' ',$wikicontent);
            $wikicontent = ('**wver2**'.$wikicontent);

            if (strlen($revisiontext)>6 && substr($revisiontext,0,6)=='**wver') {
                $wikiver = substr($revisiontext,6,strpos($revisiontext,'**',6)-6);
                $revisiontext = substr($revisiontext,strpos($revisiontext,'**',6)+2);
            } else {
                $wikiver = 1;
            }
            if ($wikiver>1) {
                $wikicontent = '**wver'.$wikiver.'**'.$wikicontent;
           }
        }
    }

    public function actionAddWiki()
    {
        $this->guestUserHandler();
        $this->getAuthenticatedUser();
        $this->layout = "master";
        $courseId = $this->getParamVal('courseId');
        $wikiId = $this->getParamVal('id');
        $course = Course::getById($courseId);
        $wiki = Wiki::getById($wikiId);
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $params = $this->getRequestParams();
        $wikiid = $params['id'];


        $saveTitle = '';
        if(isset($params['id']))
        {
            $pageTitle = 'Modify Wiki';
            if($this->isPostMethod()){
                $page_formActionTag = AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wiki->id.'&courseId=' .$course->id);
                $saveChanges = new Wiki();
                $saveChanges->updateChange($params, $wikiid);
                return $this->redirect(AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' .$course->id));
            }
            $saveTitle = AppConstant::SAVE_BUTTON;
        }
        else {
            $pageTitle = 'Add Wiki';
            if($this->isPostMethod()){
                $params = $this->getRequestParams();
                $page_formActionTag = AppUtility::getURLFromHome('course', 'course/add-wiki?courseId=' .$course->id);
                $saveChanges = new Wiki();
                $lastWikiId = $saveChanges->createItem($params);
                $saveItems = new Items();
                $lastItemsId = $saveItems->saveItems($courseId, $lastWikiId, 'Wiki');
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemorder = $courseItemOrder->itemorder;
                $items = unserialize($itemorder);
                $blocktree = array(0);
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
            $saveTitle = AppConstant::New_Item;
        }
        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor.js']);
        $returnData = array('course' => $course, 'saveTitle' => $saveTitle, 'wiki' => $wiki, 'groupNames' => $groupNames, 'pageTitle' => $pageTitle);
        return $this->render('addWiki', $returnData);
    }
}