<?php

namespace app\models;
use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasLibraryItems;
use yii\db\Query;

class LibraryItems extends BaseImasLibraryItems
{
    public static function getByQuestionSetId($existingqlist){
        return LibraryItems::find()->select('libid,COUNT(qsetid')->where(['IN','qsetid',$existingqlist])->groupBy('libid')->all();
    }

    public static function getByGroupId($groupId, $qSetId,$userId,$isGrpAdmin,$isAdmin)
    {
        if ($isGrpAdmin) {
            $query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
            $query .= "AND (imas_users.groupid='$groupId' OR ili.libid=0) AND ili.qsetid='$qSetId'";
        } else {
            $query = "SELECT ili.libid FROM imas_library_items AS ili JOIN imas_libraries AS il ON ";
            $query .= "ili.libid=il.id OR ili.libid=0 WHERE ili.qsetid='$qSetId'";
            if (!$isAdmin) {
                //unassigned, or owner and lib not closed or mine
                $query .= " AND ((ili.ownerid='$userId' AND (il.ownerid='$userId' OR il.userights%3<>1)) OR ili.libid=0)";
            }
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function setLibId($toRep,$qSetId,$toChange)
    {
        $data = LibraryItems::getByQueSetId($qSetId, $toChange);
        if($data){
            foreach($data as $singleItem){
                $singleItem->libid = $toRep;
                $singleItem->save();
            }
        }
    }

    public static function getByQueSetId($qSetId, $toChange){
        return LibraryItems::findAll(['qsetid' => $qSetId,'libid' => $toChange]);
    }

    public function createLibraryItems($libArray)
    {
        $this->libid = $libArray['libid'];
        $this->qsetid = $libArray['qsetid'];
        $this->ownerid = $libArray['ownerid'];
        $this->save();
    }

    public static function deleteLibraryItems($libId,$qSetId){
        $data = LibraryItems::getByQueSetId($qSetId, $libId);
        if($data){
            foreach($data as $singleItem){
                $singleItem->delete();
            }
        }
        return $data;
    }

    public static function getByQid($qSetId){
        return LibraryItems::findAll(['qsetid' => $qSetId]);
    }

    public static function getIdByQid($qSetId){
        return LibraryItems::find()->select('id')->where(['qsetid' => $qSetId])->all();
    }

    public static function getDestinctLibIdByIdAndOwner($groupId,$qSetId,$userId,$isGrpAdmin,$isAdmin){
        $query = new Query();
        if ($isGrpAdmin) {
            $query->select('DISTINCT(ili.libid)')->from('imas_library_items AS ili')
                ->join('INNER JOIN', 'imas_users',
                    'ili.ownerid=imas_users.id')
                ->where('imas_users.groupid = :groupId', [':groupId' => $groupId])
                ->andWhere('ili.qsetid=:qSetId', [':qSetId' => $qSetId]);

        } else {
            $query->select('DISTINCT(libid)')->from('imas_library_items')->where('qsetid =:qSetId', [':qSetId' => $qSetId]);
            if (!$isAdmin) {
                $query->andWhere('ownerid= :userId', [':userId' => $userId]);
            }
        }
        $data = $query->createCommand()->queryAll();
        return $data;
    }

    public static function getLibIdByQidAndOwner($groupId,$qSetId,$userId,$isGrpAdmin,$isAdmin){
        $query = new Query();
        if ($isGrpAdmin) {
            $query->select('ili.libid')->from('imas_library_items AS ili')
                ->join('INNER JOIN', 'imas_users',
                       'ili.ownerid=imas_users.id')
                ->andWhere(['imas_users.groupid <> :groupId', [':groupId' => $groupId]])
                ->andWhere('ili.qsetid=:qSetId', [':qSetId' => $qSetId]);
        } else if (!$isAdmin) {
            $query->select('libid')->from('imas_library_items')
            ->where('qsetid= :qSetId', [':qSetId' => $qSetId])
                ->andWhere(['imas_library_items.ownerid <> :userId', [':userId' => $userId]]);
        }
        return $query->createCommand()->queryAll();
    }

    public static function UpdateJunkFlag($id, $flag){
        $data = LibraryItems::findOne(['id' => $id]);
        if($data){
            $data->junkflag = $flag;
            $data->save();
        }
        return count($data);
    }

    public static function updateWrongLibFlag($val)
    {
        $query = "UPDATE imas_library_items AS ili
     	JOIN imas_questionset AS iqs ON iqs.id=ili.qsetid
	    JOIN imas_libraries AS il ON ili.libid=il.id
	    SET ili.junkflag = 1 WHERE (iqs.uniqueid, il.uniqueid) IN (".implode(',',$val).")";
        $data = \Yii::$app->db->createCommand($query)->execute();
        return $data;
    }

    public static function getQueSetId($id)
    {
        return LibraryItems::find()->select('qsetid')->where(['id' => $id])->all();
    }

    public function insertData($libId,$qId,$user)
    {
        $this->libid = $libId;
        $this->qsetid = $qId;
        $this->ownerid = $user->id;
        $this->save();
    }

    public static function DeleteByIds($list){
        $data = LibraryItems::getByList($list);
        if($data){
            foreach($data as $singleData){
                $singleData->delete();
            }
        }
    }

    public static function deleteByQsetId($id){
        LibraryItems::deleteAll(['qsetid' => $id]);
    }

    public static function getByList($list){
        return LibraryItems::find()->where(['IN', 'qsetid', $list])->all();
    }

    public static function getByLibAndUserTable($groupId,$list){
        $data = LibraryItems::find()->select('imas_library_items.qsetid, imas_library_items.libid')
            ->from('imas_library_items,imas_users')->where('imas_library_items.ownerid=imas_users.id')
            ->andWhere('imas_users.groupid=:groupId'or ['imas_library_items.libid' => AppConstant::NUMERIC_ZERO], [':groupId' => $groupId])
            ->andWhere(['IN', 'imas_library_items.qsetid',$list])->all();
        return $data;
    }

    public static function getByListAndOwnerId($isAdmin, $chgList, $userId)
    {
        $query = new Query();
        $query->select('ili.qsetid,ili.libid')->from('imas_library_items AS ili')
            ->join('LEFT JOIN', 'imas_libraries AS il',
                'ili.libid=il.id')
            ->andWhere(['IN', 'ili.qsetid', $chgList]);
        if (!$isAdmin) {
            //unassigned, or owner and lib not closed or mine
            $query->andWhere(['ili.ownerid' => $userId] and (['il.ownerid' => $userId] or ['<>','il.userights%3',1]) or['ili.libid' => 0]);
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getDistinctLibId($list)
    {
        return LibraryItems::find()->select('libid')->distinct()->where(['IN', 'qsetid', $list])->all();
    }

    public static function getDataToExportLib($libList,$nonPrivate)
    {
        $query = new Query();
        $query->select('imas_library_items.qsetid,imas_library_items.libid')->from('imas_library_items')
            ->join('INNER JOIN', 'imas_questionset',
                'imas_library_items.qsetid=imas_questionset.id')
            ->andWhere(['IN', 'imas_library_items.libid', $libList])
            ->andWhere(['imas_library_items.junkflag' => 0])
            ->andWhere(['imas_questionset.deleted' => 0]);

        if ($nonPrivate)
        {
            $query->andWhere(['>', 'imas_questionset.userights', 0]);
        }
        $data = $query->createCommand()->queryAll();
        return $data;
    }

    public static function getDataForImportQSet($qSetId)
    {
        return LibraryItems::find()->select(['libid','qsetid'])->where(['IN','qsetid',$qSetId])->all();
    }

    public static function getDistictqlibData($remlist)
    {
        return LibraryItems::find()->select('qsetid')->distinct()->where(['IN', 'libid', $remlist])->all();
    }

    public static function deleteLibraryAdmin($remlist)
    {
        LibraryItems::deleteAll(['IN','libid',$remlist]);
    }

    public static function deleteLibraryGrpAdmin($libid)
    {
        $id = LibraryItems::find()->where(['libid' => $libid])->one();
        if($id)
        {
            $id->delete();
        }
    }

    public static function getByDistinctQid($qids)
    {
        return LibraryItems::find()->select('qsetid')->distinct()->where(['IN', 'qsetid', $qids])->all();
    }

    public static function getDistinctQSet($remlist)
    {
        return LibraryItems::find()->select('qsetid')->distinct()->where(['libid' => $remlist])->all();
    }

    public function insertDataLib($libId,$qId)
    {
        $this->libid = $libId;
        $this->qsetid = $qId;
        $this->save();
    }

    public static function getDataByAdmin($safesearch, $llist, $checked)
    {
        $query = new Query();
        $query->select('DISTINCT (imas_questionset.id),imas_questionset.description,imas_questionset.qtype')
            ->from('imas_questionset')
            ->join('INNER JOIN',
                'imas_library_items',
                'imas_library_items.qsetid=imas_questionset.id')

            ->where(['IN', 'imas_library_items.libid', $llist]);
        if (count($safesearch) > 0)
        {
            $query->andWhere(['LIKE','imas_questionset.description',$safesearch]);
        }
        if (count($checked) > 0)
        {
            $query->andWhere(['NOT IN', 'imas_questionset.id', $checked]);
        }
        return $query->createCommand()->queryAll();
    }

    public static function getDataByGrpAdmin($groupid, $llist, $safesearch, $checked)
    {
        $query = new Query();
        $query->select('DISTINCT (imas_questionset.id),imas_questionset.description,imas_questionset.qtype')
            ->from('imas_questionset,imas_library_items,imas_users')
            ->where('imas_questionset.ownerid=imas_users.id')
            ->andWhere('imas_library_items.qsetid=imas_questionset.id')
        ->andWhere(['imas_users.groupid' => $groupid] or ['>', 'imas_questionset.userights', 0])

        ->andWhere(['IN', 'imas_library_items.libid', $llist]);
        if (count($safesearch) > 0)
        {
            $query->andWhere(['LIKE','imas_questionset.description',$safesearch]);
        }
        if (count($checked) > 0)
        {
            $query->andWhere(['NOT IN', 'imas_questionset.id', $checked]);
        }
        return $query->createCommand()->queryAll();
    }

    public static function getDataByUserId($userid,$safesearch,$llist,$checked)
    {
        $query = new Query();
        $query->select('DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype')
            ->from('imas_questionset')
            ->join('INNER JOIN',
                'imas_library_items',
                'imas_library_items.qsetid=imas_questionset.id')
            ->where(['LIKE', 'imas_questionset.description', $safesearch])
            ->andWhere(['imas_questionset.ownerid' => $userid] or ['>', 'imas_questionset.userights', 0])
            ->andWhere(['IN', 'imas_library_items.libid', $llist]);

        if (count($checked) > 0)
        {
            $query->andWhere(['NOT IN', 'imas_questionset.id', $checked]);
        }
        return $query->createCommand()->queryAll();
    }

    public static function getQSetId($lib)
    {
        return LibraryItems::find()->select('qsetid')->where(['libid' => $lib])->all();
    }

    public static function getByLibIdWithLimit($lib, $offset)
    {
        return self::find()->select('qsetid')->where(['libid' => $lib])->limit(1)->offset($offset)->all();
    }

    public static function getByLibItem($groupid, $qsetid, $lib)
    {
        $query = new Query();
        $query->select('imas_library_items')
            ->from('imas_library_items')
            ->join('INNER JOIN',
                'imas_users',
                'imas_library_items.ownerid=imas_users.id')
            ->where(['imas_users.groupid=: groupid', [':groupid' => $groupid]])
            ->andWhere(['imas_library_items.qsetid=:qsetid', [':qsetid' => $qsetid]])
            ->andWhere(['imas_library_items.libid:=lib', [':lib' => $lib]]);
        return $query->createCommand()->queryAll();
    }

    public static function deleteLib($qsetid,$lib,$isadmin,$userid)
    {
        if (!$isadmin) {
            LibraryItems::deleteAll(['qsetid'=> $qsetid, 'libid' => $lib,'ownerid' => $userid]);
        }else{
            LibraryItems::deleteAll(['qsetid'=> $qsetid, 'libid' => $lib]);
        }
    }

    public static function getByQSetANDLibAndUId($lib,$qsetid)
    {
        $query = new Query();
        $query ->select(['imas_library_items.ownerid', 'imas_users.groupid'])
            ->from('imas_library_items, imas_users')
            ->where('imas_library_items.ownerid=imas_users.id');
        $query->andWhere(['imas_library_items.libid'=> ':lib']);
        $query->andWhere(['imas_library_items.qsetid' => ':qsetid']);
        $command = $query->createCommand()->bindValues([':lib' => $lib, ':qsetid' => $qsetid]);
        $data = $command->queryOne();
        return $data;
    }

    public static function deleteByQsetIdAndLibId($libId,$qSetId)
    {
        $data = LibraryItems::find()->where(['libid' => $libId])->andWhere(['qsetid' => $qSetId])->all();
        if($data)
        {
            foreach($data as $row)
            {
                $row->delete();
            }
        }
    }

    public static function  getDataForModTutorial($groupId,$id)
    {
        $query = new Query();
        $query ->select('DISTINCT ili.libid')
            ->from('imas_library_items AS ili')
            ->join('INNER JOIN',
            'imas_users',
            'ili.ownerid=imas_users.id')
            ->where(['imas_users.groupid=:groupId', [':groupId' => $groupId]]);
        $query->andWhere(['ili.qsetid=:id', [':id' => $id]]);
        $data = $query->createCommand()->queryAll();
        return $data;
    }

    public static function getByQidIfNotAdmin($id,$isAdmin,$userId)
    {
        if (!$isAdmin){
            return LibraryItems::find()->select('libid')->distinct()->where(['qsetid' => $id, 'ownerid' => $userId])->all();
        }else{
            return LibraryItems::find()->select('libid')->distinct()->where(['qsetid' => $id])->all();
        }
    }

    public static function getDataForModTutorialIfNoAdmin($userId,$id)
    {
        return LibraryItems::find()->select('libid')->where(['qsetid' => $id])->andWhere(['<>', 'ownerid', $userId])->all();
    }
}