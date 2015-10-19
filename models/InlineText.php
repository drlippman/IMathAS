<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasInlinetext;
use yii\db\Query;

class InlineText extends BaseImasInlinetext
{
    public static function getByCourseId($courseId)
    {
        return InlineText::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return InlineText::findOne(['id' => $id]);
    }

    public static function getByCourse($courseId)
    {
        return InlineText::findOne(['courseid' => $courseId]);
    }

    public function saveInlineText($params)
    {
        $data = AppUtility::removeEmptyAttributes($params);
        $this->attributes = $data;
        $this->save();
        return $this->id;
    }

    public function updateChanges($params, $inlineTextId)
    {
        $updateIdArray = InlineText::getById($inlineTextId);
        if($updateIdArray)
        {
            $data = AppUtility::removeEmptyAttributes($params);
            $updateIdArray->attributes = $data;
            $updateIdArray->save();
            return $updateIdArray->id;
        }

    }

    public static function deleteInlineTextId($itemId)
    {
        $inlineTextData = InlineText::findOne(['id' => $itemId]);
        if ($inlineTextData) {
            $inlineTextData->delete();
        }
    }

    public static function setFileOrder($newtypeid, $addedfilelist)
    {
        $inlineData = InlineText::findOne(['id' => $newtypeid]);
        if ($inlineData) {
            $inlineData->fileorder = $addedfilelist;
            $inlineData->save();
        }
    }

    public static function getByIdLimited($id)
    {
        return InlineText::find()->select('title,text,startdate,enddate,avail,oncal,caltag,isplaylist,fileorder')->where(['id' => $id])->one();
    }

    public static function setStartDate($shift, $typeId)
    {
        $date = InlineText::find()->where(['id' => $typeId])->andWhere(['>', 'startdate', '0'])->one();
        if($date) {
            $date->startdate = $date->startdate + $shift;
            $date->save();
        }
    }

    public static function setEndDate($shift, $typeId)
    {
        $date = InlineText::find()->where(['id' => $typeId])->andWhere(['<', 'enddate', '2000000000'])->one();
        if($date) {
            $date->enddate = $date->enddate + $shift;
            $date->save();
        }
    }

    public static function getInlineTextForOutcomeMap($courseId)
    {
        $query = new Query();
        $query->select(['id','title','outcomes'])
            ->from('imas_inlinetext')
            ->where(['courseid' => $courseId])
            ->andWhere(['<>','outcomes','']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function updateInlineTextForMassChanges($startdate, $enddate, $avail, $id)
    {
        $inlineText = InlineText::findOne(['id' => $id]);
        $inlineText->startdate = $startdate;
        $inlineText->enddate = $enddate;
        $inlineText->avail = $avail;
        $inlineText->save();
    }

    public static function getInlineTextForMassChanges($courseId)
    {
        $query = InlineText::find()->where(['courseid' => $courseId])->all();
        return $query;
    }

    public static function updateVideoId($from,$to)
    {
        $query = "UPDATE imas_inlinetext SET text=REPLACE(text,'$from','$to') WHERE text LIKE '%$from%'";
        $connection=\Yii::$app->db;
        $command=$connection->createCommand($query);
        $rowCount=$command->execute();
        return $rowCount;
    }

    public static function getFileOrder($id)
    {
        return self::find()->select('fileorder')->where(['id' => $id])->one();
    }

    public static function getByCourseIdAll($courseId)
    {
        return InlineText::find()->select('id')->where(['courseid' => $courseId])->all();
    }

    public static function deleteCourseId($courseId)
    {
        $inlineData = InlineText::find()->where(['courseid' => $courseId])->one();
        if($inlineData){
            $inlineData->delete();
        }
    }

    public static function getDataByCourseId($courseId)
    {
        return self::find()->select(['id','title','text','startdate','enddate','avail'])->where(['courseid' => $courseId])->all();
    }

    public function updateName($val, $inlineTextId)
    {
        $updateIdArray = InlineText::getById($inlineTextId);
        if($updateIdArray)
        {
            $updateIdArray->title = $val;
            $updateIdArray->save();
        }
    }

    public static function getByName($typeId)
    {
        return InlineText::find()->select('name')->where(['id' => $typeId])->one();
    }
}
