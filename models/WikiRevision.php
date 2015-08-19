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
    public function saveRevision($params)
    {
        $this->wikiid = isset($params['wikiId']) ? $params['wikiId'] : null;
        $this->userid = 3;
        $this->revision = isset($params['wikicontent']) ? $params['wikicontent'] : null;
        $this->stugroupid = 0;
        $this->time = isset($params['time']) ? $params['time'] : null;
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

    public static function getRevisionTotalData($wikiId, $stugroupid)
    {
        $query = \Yii::$app->db->createCommand("SELECT i_w_r.id as revision_id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id as user_id FROM imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid WHERE i_w_r.wikiid= '$wikiId' AND i_w_r.stugroupid= '$stugroupid' ORDER BY i_w_r.id DESC")->queryAll();
        return $query;
    }

    public static function deleteByWikiId($wikiId){
        $wikiRevisionData = WikiRevision::findAll(['wikiid' => $wikiId]);
        if($wikiRevisionData){
            foreach($wikiRevisionData as $singleData){
                $singleData->delete();
            }
        }
    }
    public static function deleteWikiRivision($wikilist)
    {
        $query = WikiRevision::find()->where(['IN', 'wikiid', $wikilist])->all();
        if($query){
            foreach($query as $object){
                $object->delete();
            }
        }
    }

    public static function deleteGrp($grpId)
    {
        $query = WikiRevision::find()->where(['stugroupid'=> $grpId])->all();
        if($query)
        {
            foreach($query as $object)
            {
                $object->delete();
            }
        }
    }
}