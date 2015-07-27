<?php
namespace app\models;
use app\components\AppUtility;
use app\models\_base\BaseImasExternalTools;
use yii\db\Query;

class ExternalTools extends BaseImasExternalTools
{
    public static function externalToolsData($courseId)
    {
        $toolsData = ExternalTools::findAll(['courseid' => $courseId]);
        return $toolsData;
    }

    public function updateExternalToolsData($params)
    {
        if($params['tool']) {
            $toolsData = ExternalTools::findOne(['id' => $params['tool']]);
            $toolsData->custom = $params['toolcustom'];
            $toolsData->url = $params['toolcustomurl'];
            $toolsData->save();
        }
    }
}