<?php
namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasCalitems;
use yii\db\Query;

class CalItem extends BaseImasCalitems
{
    public static function getByCourseId($courseId)
    {
        return CalItem::findAll(['courseid' => $courseId]);
    }

    public static function getByCourse($courseId)
    {
        return CalItem::find()->where(['courseid' => $courseId])->orderBy('date')->all();
    }

    public static function setEvent($date,$tag,$title,$id)
    {
        $eventData = CalItem::findOne(['id' => $id]);
        if($eventData){
            $eventData->date = $date;
            $eventData->tag = $tag;
            $eventData->title = $title;
            $eventData->save();
        }
    }

    public function createEvent($newdate,$tag,$title,$courseId){
        $this->date = $newdate;
        $this->tag = $tag;
        $this->title = $title;
        $this->courseid = $courseId;
        $this->save();
    }

    public static function deleteByCourseId($id,$courseId)
    {
        $eventData = CalItem::getById($id,$courseId);
        if($eventData){
            $eventData->delete();
        }
    }

    public static function getById($id, $courseId)
    {
        return CalItem::findOne(['id' => $id, 'courseid' => $courseId]);
    }

    public static function setDateByCourseId($shift,$courseId)
    {

        $date = CalItem::find()->where(['id' => $courseId])->one();
       if($date) {
           $date->date = $date->date + $shift;
           $date->save();
       }

    }

}