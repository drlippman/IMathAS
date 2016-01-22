<?php
namespace app\models;

use app\components\AppUtility;
use app\models\_base\BaseImasCalitems;

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

    public static function setEvent($date, $tag, $title, $id)
    {
        $eventData = CalItem::findOne(['id' => $id]);
        if ($eventData) {
            $eventData->date = $date;
            $eventData->tag = $tag;
            $eventData->title = $title;
            $eventData->save();
        }
    }

    public function createEvent($newdate, $tag, $title, $courseId)
    {
        $this->date = $newdate;
        $this->tag = $tag;
        $this->title = $title;
        $this->courseid = $courseId;
        $this->save();
        return $this;
    }

    public static function deleteByCourseId($id, $courseId)
    {
        $eventData = CalItem::getById($id, $courseId);
        if ($eventData) {
            $eventData->delete();
        }
    }

    public static function getById($id, $courseId)
    {
        return CalItem::findOne(['id' => $id, 'courseid' => $courseId]);
    }

    public static function setDateByCourseId($shift, $courseId)
    {

        $date = CalItem::find()->where(['id' => $courseId])->one();
        if ($date) {
            $date->date = $date->date + $shift;
            $date->save();
        }

    }

    public static function deleteForCopyCourse($courseId)
    {
        $data = CalItem::find()->where(['courseid' => $courseId])->all();
        if ($data) {
            foreach ($data as $singleCalItem) {
                $singleCalItem->delete();
            }
        }
    }

    public static function getDataForCopyCourse($chkList, $ctc)
    {
        return self::find()->select('date,tag,title')->where(['IN','id',$chkList])->andWhere(['courseid' => $ctc])->all();
    }

    public function InsertDataForCopy($courseId,$data)
    {
        $this->courseid = $courseId;
        $this->date = $data['date'];
        $this->tag = $data['tag'];
        $this->title = $data['title'];
        $this->save();
    }

    public static function deleteByCourseIdOne($courseId)
    {
        $courseData = CalItem::findOne(['courseid', $courseId]);
        if ($courseData) {
            $courseData->delete();
        }
    }

}