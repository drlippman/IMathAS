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
        $query ->select(['qsetid'])->from('imas_library_items')->where(['id' => $id]);
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
}