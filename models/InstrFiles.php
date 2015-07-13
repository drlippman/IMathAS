<?php

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasInstrFiles;

class InstrFiles extends BaseImasInstrFiles
{
    public static function getAllData($itemId)
    {
        return InstrFiles::findAll(['itemid' => $itemId]);
    }
    public function saveFile($params)
    {
        $this->description = isset($params['newfiledescr']) ? $params['newfiledescr'] : null;
        $this->filename = isset($params['filename']) ? $params['filename'] : null;
        $this->itemid = isset($params['itemid']) ? $params['itemid'] : null;
        AppUtility::dump($this);
        $this->save();
        return $this->id;
    }

    public static function deleteById($itemId){
        $instrFileData = InstrFiles::findAll(['itemid' => $itemId]);
        if($instrFileData){
            foreach($instrFileData as $singleFile){
                $singleFile->delete();
            }
        }
    }
} 