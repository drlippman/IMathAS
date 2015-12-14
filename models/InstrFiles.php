<?php

namespace app\models;

use app\components\AppUtility;
use app\models\_base\BaseImasInstrFiles;
use yii\db\Query;

class InstrFiles extends BaseImasInstrFiles
{
    public static function getAllData($itemId)
    {
        return InstrFiles::findAll(['itemid' => $itemId]);
    }

    public function saveFile($params,$filename, $inlineText)
    {
        $this->description = $params['newfiledescr'];
        $this->filename = $filename;
        $this->itemid =  $inlineText;
        $this->save();
        return $this->id;
    }

    public static function deleteById($itemId)
    {
        $instrFileData = InstrFiles::findAll(['itemid' => $itemId]);
        if($instrFileData){
            foreach($instrFileData as $singleFile){
                $singleFile->delete();
            }
        }
    }

    public static function getFileName($itemId)
    {
        return InstrFiles::find()->select('id,description,filename')->where(['itemid' => $itemId])->all();
    }

    public static function deleteByItemId($itemId)
    {
        $instrFileData = InstrFiles::findOne(['id' => $itemId]);
        if($instrFileData){
            $instrFileData->delete();
        }
    }

    public static function getByIdForFile($fileName)
    {
        return InstrFiles::find()->select('id')->where(['filename' => $fileName])->all();
    }

    public static function setFileDescription($id, $description)
    {
        $inlineData = InstrFiles::findOne(['id' => $id]);
        if ($inlineData) {
            $inlineData->description = $description;
            $inlineData->save();
            return $inlineData->id;
        }
    }

    public static function getByName($itemid)
    {
        return InstrFiles::find()->select('filename')->where(['itemid' => $itemid])->all();
    }

    public static function getIdName($safefn)
    {
        return InstrFiles::find()->select('id')->where(['filename' => $safefn])->all();
    }

    public function insertFile($params, $itemId)
    {
        $this->description = $params['description'];
        $this->filename = $params['filename'];
        $this->itemid =  $itemId;
        $this->save();
        return $this->id;
    }
}