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
    public function saveFile($params, $inlineText)
    {
        $this->description = isset($params['newfiledescr']) ? $params['newfiledescr'] : null;
        $this->filename = isset($params['filename']) ? $params['filename'] : null;
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
        $query ->select(['description','filename','id'])
               ->from('imas_instr_files')
                ->where(['itemid' => $itemId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }
} 