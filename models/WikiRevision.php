<?php
namespace app\models;

use app\components\AppUtility;
use app\models\_base\BaseImasWikiRevisions;

class WikiRevision extends BaseImasWikiRevisions
{
    public static function getByRevisionId($wikiId)
    {
        return WikiRevision::findAll(['wikiid' => $wikiId]);
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

    public static function getCountOfId($wikiId, $stugroupid)
    {
//        return WikiRevision::find()->where(['wikiid' => $wikiId])->count(['id' => $id]);
        $query = \Yii::$app->db->createCommand("SELECT i_w_r.id,i_w_r.userid,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName FROM  imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid WHERE i_w_r.wikiid='$wikiId' AND i_w_r.stugroupid='$stugroupid' ORDER BY i_w_r.id DESC")->queryAll();
        return $query;
    }
}