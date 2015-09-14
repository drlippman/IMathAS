<?php
namespace app\models;

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
        $query = \Yii::$app->db->createCommand("SELECT date,tag,title FROM imas_calitems WHERE id IN ($chkList) AND courseid= :ctc");
        $query->bindValue('ctc', $ctc);
        $data = $query->queryAll();
        return $data;
    }

    public static function InsertDataForCopy($calItemData)
    {
        $query = \Yii::$app->db->createCommand("INSERT INTO imas_calitems (courseid,date,tag,title) VALUES $calItemData");
        $data = $query->queryAll();

    }

    public static function deleteByCourseIdOne($courseId)
    {
        $courseData = CalItem::findOne(['courseid', $courseId]);
        if ($courseData) {
            $courseData->delete();
        }
    }

}