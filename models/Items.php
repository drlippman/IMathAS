<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 6/5/15
 * Time: 12:34 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasItems;
use Yii;

class Items extends BaseImasItems
{
    public static function getByCourseId($courseId)
    {
        return Items::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return Items::findOne(['id' => $id]);
    }

    public function create($cid,$item)
    {
        $this->courseid = $cid;
        $this->itemtype = $item;
        $this->typeid = AppConstant::NUMERIC_ZERO;
        $this->save();
        return $this->id;
    }

    public static function deletedItems($id)
    {
        return Items::deleteAll(['id' => $id]);
    }

    public function saveItems($courseId, $typeId, $itemType)
    {
        $this->courseid = $courseId;
        $this->typeid = $typeId;
        $this->itemtype = $itemType;
        $this->save();
        return $this->id;
    }

    public static function getByTypeId($id)
    {
        return Items::findOne(['id' => $id]);
    }

    public static function deleteByTypeIdName($typeId,$itemType){
        $itemData = Items::findOne(['typeid' => $typeId, 'itemtype' => $itemType]);
        $itemId = $itemData['id'];
        if($itemData){
            $itemData->delete();
        }
        return $itemId;
    }

    public static function deletedCalendar($id, $itemType)
    {
        $itemData =  Items::findOne(['id' => $id, 'itemtype' => $itemType]);
        $itemId = $itemData['id'];
        if($itemData){
            $itemData->delete();
        }
        return $itemId;
    }

    public static function getByAssessmentId($cid,$aid){
        $query = "SELECT ii.id AS itemid,ia.id,ia.name,ia.summary FROM imas_items AS ii JOIN imas_assessments AS ia ";
        $query .= "ON ii.typeid=ia.id AND ii.itemtype='Assessment' WHERE ii.courseid='$cid' AND ia.id<>'$aid'";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getByItem($item)
    {
        $query = Yii::$app->db->createCommand("SELECT itemtype,typeid FROM imas_items WHERE id=:item");
        $query->bindValue('item', $item);
        return $query->queryOne();
    }
}