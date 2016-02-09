<?php
namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasWikiRevisions;
use yii\db\Query;

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
        if($this->save()){
            echo("yes");
        }else{
            print_r($this->getErrors());die;
        }
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
        $query = new Query();
        $query	->select(['i_w_r.id as revision_id','i_w_r.revision','i_w_r.time','i_u.LastName','i_u.FirstName','i_u.id as user_id'])
            ->from('imas_wiki_revisions as i_w_r')
            ->join(	'INNER JOIN', 'imas_users as i_u', 'i_u.id=i_w_r.userid')
            ->where('i_w_r.wikiid= :wikiId')
        ->andWhere('i_w_r.stugroupid = :stugroupid')
        ->orderBy(['i_w_r.id' => AppConstant::DESCENDING]);
        $command = $query->createCommand();
        $data = $command->bindValue(':stugroupid',$stugroupid)->bindValue(':wikiId',$wikiId)->queryAll();
        return $data;
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
        $query = "SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid=':id' GROUP BY stugroupid";
        return \Yii::$app->db->createCommand($query)->bindValue(':id',$id)->queryAll();
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
      return WikiRevision::find()->select('id')->where(['wikiid' => $id])->andWhere(['stugroupid' => $groupId])->orderBy(['id' => AppConstant::DESCENDING])->limit('1')->all();
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
        return WikiRevision::find()->select('revision')->where(['wikiid' => $id])->andWhere(['stugroupid' => $groupId])->andWhere(['>=','id',$revision])->orderBy(['id' => AppConstant::DESCENDING])->all();
    }

    public static function updateRevision($revision, $newBase)
    {
        $updateRevisions = WikiRevision::findAll(['id' => $revision]);
        if($updateRevisions)
        {
            foreach($updateRevisions as $key => $updateRevision)
            {
               $updateRevision->revision = $newBase;
                $updateRevision->save();
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
        $query = new Query();
        $query	->select(['i_w_r.id','i_w_r.revision','i_w_r.time','i_u.LastName','i_u.FirstName'])
            ->from('imas_wiki_revisions as i_w_r')
            ->join(	'INNER JOIN', 'imas_users as i_u', 'i_u.id=i_w_r.userid')
            ->where('i_w_r.wikiid = :id')
            ->andWhere('i_w_r.stugroupid = :stugroupid')
            ->orderBy(['id' => AppConstant::DESCENDING])
        ->limit('1');
        $command = $query->createCommand();
        $data = $command->bindValue(':stugroupid',$groupId)->bindValue(':id',$id)->queryOne();
        return $data;
    }

    public static function getMaxTime($typeid, $groupSetId, $canEdit, $wikiGrpId)
    {
        $query = "SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid=':typeid' ";
        if ($groupSetId >0 && !$canEdit) {
            /*
             * if group and not instructor limit to group
             */
            $query .= "AND stugroupid=':wikiGrpId' ";
        }
        $query .= "GROUP BY stugroupid";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValues(['typeid' => $typeid, 'wikiGrpId' => $wikiGrpId]);
        return $data->queryAll();
    }

    public static function getRevisionDataPublicly($wikiId)
    {
        $query = new Query();
        $query	->select(['i_w_r.id as revision_id','i_w_r.revision','i_w_r.time','i_u.LastName','i_u.FirstName','i_u.id as user_id'])
            ->from('imas_wiki_revisions as i_w_r')
            ->join(	'INNER JOIN',
                'imas_users as i_u',
                'i_u.id=i_w_r.userid')
            ->where('i_w_r.wikiid= :wikiId')
            ->orderBy(['i_w_r.id' => AppConstant::DESCENDING]);
        $command = $query->createCommand();
        $data = $command->bindValue(':wikiId',$wikiId)->queryOne();
        return $data;
    }
}