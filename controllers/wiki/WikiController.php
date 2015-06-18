<?php
namespace app\controllers\wiki;
use app\components\AppConstant;
use app\components\AppUtility;
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
        $courseId = $this->getParamVal('courseId');
        $wikiId = $this->getParamVal('wikiId');
        $course = Course::getById($courseId);
        $subject = $this->getBodyParams('wikicontent');
        $wiki = Wiki::getById($wikiId);
        $stugroupid = 0;
        $revisionTotalData = WikiRevision::getCountOfId($wikiId, $stugroupid);
        $wikiTotalData = Wiki::getAllData($wikiId);
        $wikiRevisionData = WikiRevision::getByRevisionId($wikiId);
        $count = $wikiRevisionData ;
        $wikiRevisionSortedByTime = '';
        foreach($wikiRevisionData as $singleWikiData){
           // $count = $singleWikiData->id;

            $sortBy = $singleWikiData->id;
            $order = AppConstant::DESCENDING;
            $wikiRevisionSortedByTime = WikiRevision::getEditedWiki($sortBy, $order,$singleWikiData->id);

        }
        $this->includeJS(['viewwiki.js']);
        $responseData = array('body' => $subject,'course' => $course, 'wiki' => $wiki, 'wikiRevisionData' => $wikiRevisionSortedByTime, 'userData' => $userData, 'countOfRevision' => $count);
        return $this->renderWithData('showWiki', $responseData);
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
        $wikiRevisionSortedByTime = '';
        foreach($wikiRevisionData as $singleWikiData){
            $sortBy = $singleWikiData->id;
            $order = AppConstant::DESCENDING;
            $wikiRevisionSortedByTime = WikiRevision::getEditedWiki($sortBy, $order,$singleWikiData->id);
        }

        $wikiRevision = WikiRevision::getByRevisionId($wikiId);
        if ($this->isPost()) {
            $data = $this->getRequestParams();
        }

        $this->includeJS(["editor/tiny_mce.js" , 'editor/tiny_mce_src.js', 'general.js', 'editor/plugins/asciimath/editor_plugin.js', 'editor/themes/advanced/editor_template.js']);
        $responseData = array('wiki' => $wiki, 'course' => $course, 'wikiRevision' => $wikiRevision, 'wikiRevisionData' => $wikiRevisionSortedByTime, 'userData' => $userData);
        return $this->renderWithData('editPage', $responseData);
    }
    public function actionWikiRev()
    {
        $userData = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $wikiId = $this->getParamVal('wikiId');
        $wiki = Wiki::getById($wikiId);
        $wikiRevisionData = WikiRevision::getByRevisionId($wikiId);
        $stugroupid = 0;
        $revisionTotalData = WikiRevision::getCountOfId($wikiId, $stugroupid);
        $wikiTotalData = Wiki::getAllData($wikiId);
        $responseData = array('courseId'=>$courseId, 'wikiId'=>$wikiId, 'revisionTotalData' => $revisionTotalData, 'wikiTotalData' => $wikiTotalData,'countOfRevision' => $wikiRevisionData);
        return $this->renderWithData('wikiRev', $responseData);
    }
    public function wikiEditedRevisionData($wikiRevisionData, $wikiData)
    {
        $revisiontext = $wikiRevisionData->revision;
        $revisionid = $wikiRevisionData->id;
        if ($wikiRevisionData->revision!= null) { //FORM SUBMITTED, DATA PROCESSING
            $inconflict = false;
            $stugroupid = 0;

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

    public function actionGetLastDataAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $cid = $params['courseId'];
        $wikiId = $params['wikiId'];
        $wikiData = WikiRevision::getByWikiId($wikiId);
        $wikiRevisionSortedById = '';
        $order = AppConstant::DESCENDING;
        foreach($wikiData as $singleWikiData){
            $sortBy = $singleWikiData->id;
            $wikiRevisionSortedById = WikiRevision::getEditedWiki($sortBy, $order,$singleWikiData->id);
        }
        $responseData = array('wikiRevisionSortedById'=> $wikiRevisionSortedById[0]['attributes']);
        return $this->successResponse($responseData);
    }

    public function actionGetFirstDataAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $cid = $params['courseId'];
        $wikiId = $params['wikiId'];
        $wikiData = WikiRevision::getByWikiId($wikiId);
        $wikiRevisionSortedById = '';
        foreach($wikiData as $singleWikiData){
        $order = AppConstant::ASCENDING;
        $sortBy = $singleWikiData->id;
        $wikiRevisionSortedById = WikiRevision::getFirstWikiData($sortBy, $order);
        }
        $responseData = array('wikiRevisionSortedById'=> $wikiRevisionSortedById[0]['attributes']);
         return $this->successResponse($responseData);
    }
    public function actionGetNewerDataAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $cid = $params['courseId'];
        $wikiId = $params['wikiId'];
        $wikiData = WikiRevision::getByWikiId($wikiId);
        $wikiRevisionSortedById = '';
        $sortBy = '';
        foreach($wikiData as $singleWikiData){
//          $order = AppConstant::DESCENDING;
            $sortBy = $singleWikiData->id;
            $sortBy ++;
        }
        AppUtility::dump($sortBy);
        $responseData = array('wikiRevisionSortedById'=> $wikiRevisionSortedById[0]['attributes']);
//           $responseData = array('wikiRevisionSortedById'=> $sortBy);
         return $this->successResponse($responseData);
    }
}