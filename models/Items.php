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
        $query = new Query();
        $query->select('ii.id AS itemid,ia.id,ia.name,ia.summary')
            ->from('imas_items AS ii')
            ->join('INNER JOIN',
                'imas_assessments AS ia',
                'ii.typeid=ia.id')
            ->where('ii.itemtype=Assessment')
            ->andWhere('ii.courseid= :cid', [':cid' => $cid])
            ->andWhere('ia.id <> :aid', [':aid' => $aid]);
        return $query->createCommand()->queryAll();
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
        return self::find()->select(['id','itemtype','typeid'])->where(['courseid' => $courseId])->all();
    }

    public static function getByCourseIdAndItenType($id, $itemType)
    {
        return Items::findAll(['courseid' => $id, 'itemtype' => $itemType]);
    }
}