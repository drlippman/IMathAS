<?php
namespace app\models;

use app\components\AppUtility;
use app\models\_base\BaseImasWikiRevisions;

class WikiRevision extends BaseImasWikiRevisions
{
    public static function getByRevisionId($id)
    {
        return WikiRevision::findAll(['wikiid' => $id]);
    }
    public function saveRevision($params, $user, $wikicontent)
    {
        //AppUtility::dump($wikicontent);
        $this->wikiid = isset($params['wikiId']) ? $params['wikiId'] : null;
        $this->userid = isset($user) ? $user : null;
        $this->revision = isset($wikicontent) ? $wikicontent : null;
        $this->stugroupid = 0;
        $postdate = strtotime(date('F d, o g:i a'));
        $this->time = $postdate;
        $this->save();
       // AppUtility::dump($this);
    }

    public static function getEditedWiki($sortBy, $order,$wikiId)
    {
        return WikiRevision::find()->where(['id'=> $wikiId])->all();
    }
    public static function getFirstWikiData($sortBy, $order)
        {
            return WikiRevision::find()->all();
        }

    public static function getRevisionId($id)
    {
        return WikiRevision::findAll(['id' => $id]);
    }

    public static function getByWikiId($wikiId)
    {
        return WikiRevision::findAll(['wikiid' =>$wikiId]);
    }
}