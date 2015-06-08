<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/6/15
 * Time: 1:21 PM
 */

namespace app\controllers\wiki;


use app\components\AppConstant;
use app\components\AppUtility;
use app\controllers\AppController;
use app\models\Course;
use app\models\Wiki;
use app\models\WikiRevision;

class WikiController extends AppController
{
    //display detail of selected wiki
    public function actionShowWiki()
    {
        $courseId = $this->getParamVal('courseId');
        $wikiId = $this->getParamVal('wikiId');
        $course = Course::getById($courseId);
        $wiki = Wiki::getById($wikiId);
        return $this->renderWithData('showWiki', ['course' => $course, 'wiki' => $wiki]);
    }

    //to edit wiki page
    public function actionEditPage()
    {
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $wikiId = $this->getParamVal('wikiId');
        $wiki = Wiki::getById($wikiId);
        $wikiRevision = WikiRevision::getByRevisionId($wikiId);
        if ($this->isPost()) {
            $data = $this->getBodyParams();
            AppUtility::dump($data);
        }
        $this->includeJS(["../js/editor/tiny_mce.js" , '../js/editor/tiny_mce_src.js', '../js/general.js', '../js/editor/plugins/asciimath/editor_plugin.js', '../js/editor/themes/advanced/editor_template.js']);
        return $this->renderWithData('editPage', ['wiki' => $wiki, 'course' => $course, 'wikiRevision' => $wikiRevision]);
    }
}