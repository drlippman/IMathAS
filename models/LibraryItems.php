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
}