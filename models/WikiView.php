<?php

namespace app\models;

use app\models\_base\BaseImasWikiViews;
use yii\db\Query;

class WikiView extends BaseImasWikiViews
{
    public static function getWikiViewTotalData($wikiId, $userId)
    {
        return WikiView::findAll(['wikiid' => $wikiId, 'userid' => $userId]);
    }

    public static function deleteByWikiId($wikiId)
    {
        $wikiViewData = WikiRevision::findAll(['wikiid' => $wikiId]);
        if ($wikiViewData) {
            foreach ($wikiViewData as $singleData) {
                $singleData->delete();
            }
        }
    }

    public static function deleteWikiRelatedToCourse($wikis, $toUnEnroll)
    {
        $query = WikiView::find()->where(['IN', 'wikiid', $wikis])->andWhere(['IN', 'userid', $toUnEnroll])->all();
        if ($query) {
            foreach ($query as $object) {
                $object->delete();
            }
        }
    }

    public static function deleteWikiId($wid)
    {
        $query = WikiView::find()->where(['wikiid' => $wid])->one();
        if ($query) {
            $query->delete();
        }
    }

    public static function getByUserIdAndWikiId($userId, $id)
    {
        $query = new Query();
        $query->select(['stugroupid','lastview'])
            ->from('imas_wiki_views')
            ->where(['userid' => $userId]);
        $query->andWhere(['wikiid' => $id]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function updateLastView($userId, $id, $groupId,$now)
    {
        $lastView = WikiView::find()->where(['userid' => $userId])->andWhere(['wikiid' => $id])->andWhere(['stugroupid' => $groupId])->one();
        if($lastView){
            $lastView->lastview = $now;
        }
    }

    public function addWikiView($userId, $wikiId, $stuGroupId, $lastView)
    {
        $this->userid = $userId;
        $this->wikiid = $wikiId;
        $this->stugroupid = $stuGroupId;
        $this->lastview = $lastView;
        $this->save();
    }
} 