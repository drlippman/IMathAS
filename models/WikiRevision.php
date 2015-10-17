<?php
namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasWikiRevisions;

class WikiRevision extends BaseImasWikiRevisions
{
    public static function getByRevisionId($wikiId)
    {
        return WikiRevision::findAll(['wikiid' => $wikiId]);
    }

    public function saveRevision($id,$groupId,$userId,$wikiContent,$now)
    {
        $this->wikiid = $id;
        $this->stugroupid = $groupId;
        $this->userid = $userId;
        $this->revision = $wikiContent;
        $this->time = $now;
        $this->save();
    }

    public static function getEditedWiki($sortBy, $order, $wikiId)
    {
        return WikiRevision::find()->where(['id' => $wikiId])->all();
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
        return WikiRevision::findAll(['wikiid' => $wikiId]);
    }

    public static function getRevisionTotalData($wikiId, $stugroupid)
    {
        $query = \Yii::$app->db->createCommand("SELECT i_w_r.id as revision_id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id as user_id FROM imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid WHERE i_w_r.wikiid= '$wikiId' AND i_w_r.stugroupid= '$stugroupid' ORDER BY i_w_r.id DESC")->queryAll();
        return $query;
    }

    public static function deleteByWikiId($wikiId)
    {
        $wikiRevisionData = WikiRevision::findAll(['wikiid' => $wikiId]);
        if ($wikiRevisionData) {
            foreach ($wikiRevisionData as $singleData) {
                $singleData->delete();
            }
        }
    }

    public static function deleteWikiRivision($wikilist)
    {
        $query = WikiRevision::find()->where(['IN', 'wikiid', $wikilist])->all();
        if ($query) {
            foreach ($query as $object) {
                $object->delete();
            }
        }
    }

    public static function deleteGrp($grpId)
    {
        $query = WikiRevision::find()->where(['stugroupid' => $grpId])->all();
        if ($query) {
            foreach ($query as $object) {
                $object->delete();
            }
        }
    }

    public static function deleteByWikiRevisionId($itemId)
    {
        $instrFileData = WikiRevision::findOne(['wikiid' => $itemId]);
        if ($instrFileData) {
            $instrFileData->delete();
        }
    }

    public static function getByIdWithMaxTime($id)
    {
        $query = "SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid='$id' GROUP BY stugroupid";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function deleteAllRevision($id, $groupId)
    {
        $queryAll = WikiRevision::find()->where(['wikiid' => $id])->andWhere(['stugroupid' => $groupId])->all();
        if($queryAll) {
            foreach($queryAll as $key => $query)
            {
                $query->delete();
            }
        }
    }

    public static function getDataWithLimit($id,$groupId)
    {
        $query = "SELECT id FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupId' ORDER BY id DESC LIMIT 1";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function deleteRevisionHistory($id, $groupId,$curid)
    {
        $queryAll = WikiRevision::find()->where(['wikiid' => $id])->andWhere(['stugroupid' => $groupId])->andWhere(['<','id', $curid])->all();
        if($queryAll) {
            foreach($queryAll as $key => $query)
            {
                $query->delete();
            }
        }
    }

    public static function getRevision($id, $groupId, $revision)
    {
        $query = "SELECT revision FROM imas_wiki_revisions WHERE wikiid='$id' AND stugroupid='$groupId' ";
        $query .= "AND id>=$revision ORDER BY id DESC";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function updateRevision($revision, $newBase)
    {
        $updateRevisions = WikiRevision::findAll(['id' => $revision]);
        if($updateRevisions)
        {
            foreach($updateRevisions as $key => $updateRevision)
            {
                $updateRevision->revision = $newBase;
            }
        }

    }

    public static function deleteRevision($id, $groupId,$revision)
    {
        $queryAll = WikiRevision::find()->where(['wikiid' => $id])->andWhere(['stugroupid' => $groupId])->andWhere(['>','id', $revision])->all();
        if($queryAll) {
            foreach($queryAll as $key => $query)
            {
                $query->delete();
            }
        }
    }

    public static function getDataToCheckConflict($id, $groupId)
    {
        $query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName FROM ";
        $query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
        $query .= "WHERE i_w_r.wikiid='$id' AND i_w_r.stugroupid='$groupId' ORDER BY id DESC LIMIT 1";
        return \Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getMaxTime($typeid, $groupSetId, $canEdit, $wikiGrpId)
    {
        $query = "SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid='$typeid' ";
        if ($groupSetId >0 && !$canEdit) {
            /*
             * if group and not instructor limit to group
             */
            $query .= "AND stugroupid='$wikiGrpId' ";
        }
        $query .= "GROUP BY stugroupid";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }
}