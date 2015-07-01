<?php
namespace app\controllers\wiki;
use app\components\AppConstant;
use app\components\AppUtility;
use app\components\WikiUtility;
use app\controllers\AppController;
use app\models\Course;
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
        $responseData = array('body' => $subject,'course' => $course, 'revisionTotalData'=> $revisionTotalData, 'wikiTotalData'=>$wikiTotalData, 'wiki' => $wiki, 'wikiRevisionData' => $wikiRevisionSortedByTime, 'userData' => $userData, 'countOfRevision' => $count);
        return $this->renderWithData('showWiki', $responseData);
    }

    public function actionGetRevisions(){
        $revisions = WikiUtility::getWikiRevision();
        return $revisions;
    }
    /**
     * to edit wiki page
     */
    public function actionEditPage()
    {
        $userData = $this->getAuthenticatedUser();
//        $userId = $userData->id;
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
        if ($this->isPost())
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
            $wikicontent = addslashes('**wver2**'.$wikicontent);

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
}