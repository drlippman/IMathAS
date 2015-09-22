<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 25/6/15
 * Time: 3:37 PM
 */

namespace app\models;
use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasLibraryItems;
use yii\db\Query;

class LibraryItems extends BaseImasLibraryItems
{
    public static function getByQuestionSetId($existingqlist){
        $query = \Yii::$app->db->createCommand("SELECT libid,COUNT(qsetid) FROM imas_library_items WHERE qsetid IN ($existingqlist) GROUP BY libid")->queryAll();
        return $query;
    }

    public static function getByGroupId($groupId, $qSetId,$userId,$isGrpAdmin,$isAdmin){
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

    public static function setLibId($toRep,$qSetId,$toChange){
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

    public function createLibraryItems($libArray){
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
        if ($isGrpAdmin) {
            $query = "SELECT DISTINCT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
            $query .= "AND imas_users.groupid='$groupId' AND ili.qsetid='$qSetId'";
        } else {
            $query = "SELECT DISTINCT libid FROM imas_library_items WHERE qsetid='$qSetId'";
            if (!$isAdmin) {
                $query .= " AND ownerid='$userId'";
            }
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getLibIdByQidAndOwner($groupId,$qSetId,$userId,$isGrpAdmin,$isAdmin){
        if ($isGrpAdmin) {
            $query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ili.ownerid=imas_users.id ";
            $query .= "AND imas_users.groupid!='$groupId' AND ili.qsetid='$qSetId'";
        } else if (!$isAdmin) {
            $query = "SELECT libid FROM imas_library_items WHERE qsetid='$qSetId' AND imas_library_items.ownerid!='$userId'";
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
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
        $query = new Query();
        $query ->select(['qsetid'])
            ->from('imas_library_items')
            ->where(['id' => $id]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
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
            ->andWhere(['imas_users.groupid' => $groupId] or ['imas_library_items.libid' => AppConstant::NUMERIC_ZERO])
            ->andWhere(['IN', 'imas_library_items.qsetid',$list])->all();
        return $data;
    }

    public static function getByListAndOwnerId($isAdmin, $chgList, $userId){
        $query = "SELECT ili.qsetid,ili.libid FROM imas_library_items AS ili LEFT JOIN imas_libraries AS il ON ";
        $query .= "ili.libid=il.id WHERE ili.qsetid IN ($chgList)";
        if (!$isAdmin) {
            //unassigned, or owner and lib not closed or mine
            $query .= " AND ((ili.ownerid='$userId' AND (il.ownerid='$userId' OR il.userights%3<>1)) OR ili.libid=0)";
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
        $query = "SELECT imas_library_items.qsetid,imas_library_items.libid FROM imas_library_items ";
        $query .= "JOIN imas_questionset ON imas_library_items.qsetid=imas_questionset.id ";
        $query .= "WHERE imas_library_items.libid IN ($libList) AND imas_library_items.junkflag=0 AND imas_questionset.deleted=0 ";
        if ($nonPrivate)
        {
            $query .= " AND imas_questionset.userights>0";
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getDataForImportQSet($qSetId)
    {
        $query = new Query();
        $query ->select(['libid','qsetid'])
                ->from('imas_library_items')
                ->where(['IN','qsetid',$qSetId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function getDistictqlibData($remlist)
    {
        return LibraryItems::find()->select('qsetid')->distinct()->where(['IN', 'libid', $remlist])->all();
    }

    public static function deleteLibraryAdmin($remlist)
    {
        $query = "DELETE FROM imas_library_items WHERE libid IN ($remlist)";
        \Yii::$app->db->createCommand($query)->execute();
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
    public static function getDataByAdmin($safesearch, $llist, $checked, $clist)
    {
        $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
        $query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE '%$safesearch%' ";
        $query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
        if (count($checked) > 0)
        {
            $query .= "AND imas_questionset.id NOT IN ($clist);";
        }

        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getDataByGrpAdmin($groupid, $llist, $safesearch, $checked, $clist)
    {
        $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
        $query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.description LIKE '%$safesearch%' ";
        $query .= "AND imas_questionset.ownerid=imas_users.id ";
        $query .= "AND (imas_users.groupid='$groupid' OR imas_questionset.userights>0) ";
        $query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
        if (count($checked) > 0)
        {
            $query .= "AND imas_questionset.id NOT IN ($clist);";
        }
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getDataByUserId($userid,$safesearch,$llist, $checked, $clist)
    {
        $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
        $query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE '%$safesearch%' ";
        $query .= "AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) ";
        $query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
        if (count($checked) > 0)
        {
            $query .= "AND imas_questionset.id NOT IN ($clist);";
        }
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getQSetId($lib)
    {
        return LibraryItems::find()->select('qsetid')->where(['libid' => $lib])->all();
    }

    public static function getByLibIdWithLimit($lib, $offset)
    {
        $query = "SELECT qsetid FROM imas_library_items WHERE libid='$lib' LIMIT $offset,1";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getByLibItem($groupid, $qsetid, $lib)
    {
        $query = "SELECT imas_library_items FROM imas_library_items,imas_users WHERE ";
        $query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
        $query .= "imas_library_items.qsetid='$qsetid' AND imas_library_items.libid='$lib'";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }
    public static function deleteLib($qsetid,$lib,$isadmin,$userid)
    {
        $query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid' AND libid='$lib'";
        if (!$isadmin) {
            $query .= " AND ownerid='$userid'";
        }
      return \Yii::$app->db->createCommand($query)->execute();
    }

    public static function getByQSetANDLibAndUId($lib,$qsetid)
    {
        $query = new Query();
        $query ->select(['imas_library_items.ownerid', 'imas_users.groupid'])
            ->from('imas_library_items, imas_users')
            ->where('imas_library_items.ownerid=imas_users.id');
        $query->andWhere(['imas_library_items.libid'=> $lib]);
        $query->andWhere(['imas_library_items.qsetid' => $qsetid]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }
}