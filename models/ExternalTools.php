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

    public static function dataForCopy($toolidlist)
    {
        $query = \Yii::$app->db->createCommand("SELECT id,courseid,groupid,name,url,ltikey,secret,custom,privacy FROM imas_external_tools WHERE id IN ($toolidlist)")->queryAll();
        return $query;
    }
    public static function getId($courseId,$url)
    {
        $query  = \Yii::$app->db->createCommand("SELECT id FROM imas_external_tools WHERE url='" . addslashes($url) . "' AND courseid='$courseId'")->queryAll();
        return $query;
    }

    public function insertData($courseId,$groupid,$rowsub)
    {
           $this->courseid = $courseId;
           $this->groupid =  $groupid;
           $this->name =  $rowsub['name'];
           $this->url =  $rowsub['url'];
           $this->ltikey =   $rowsub['ltikey'];
           $this->secret =   $rowsub['secret'];
           $this->custom =   $rowsub['custom'];
           $this->privacy =   $rowsub['privacy'];
           $this->save();
        return $this->id;

    }
}