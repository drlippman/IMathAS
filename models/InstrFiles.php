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
        $this->description = isset($params['newfiledescr']) ? $params['newfiledescr'] : null;
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
        $query = new Query();
        $query ->select(['id','description','filename'])
               ->from('imas_instr_files')
                ->where(['itemid' => $itemId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }

    public static function deleteByItemId($itemId)
    {
        $instrFileData = InstrFiles::findOne(['itemid' => $itemId]);
        if($instrFileData){
            $instrFileData->delete();
        }
    }

    public static function getByIdForFile($fileName)
    {
        $query = new Query();
        $query ->select(['id'])
            ->from('imas_instr_files')
            ->where(['filename' => $fileName]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
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
} 