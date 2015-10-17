<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasItems;
use Yii;
use yii\db\Query;

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
        $query .= "ON ii.typeid=ia.id AND ii.itemtype='Assessment' WHERE ii.courseid= :cid AND ia.id<> :aid";
        $data = \Yii::$app->db->createCommand($query);
        $data->bindValues(['cid' => $cid, 'aid'=> $aid]);
        return $data->queryAll();
    }

    public static function getByItem($item)
    {
        return Items::find()->select('itemtype,typeid')->where(['id' => $item])->one();
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = Items::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
        }
    }

    public static function getDataByCourseId($courseId)
    {
        $query = new Query();
        $query	->select(['id','itemtype','typeid'])
            ->from(['imas_items'])
            ->where(['courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
}