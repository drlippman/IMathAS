<?php

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasDbschema;
use yii\db\Query;

class DbSchema extends BaseImasDbschema {
    public static function getById($id){
        return DbSchema::findOne(['id' => $id]);

    }

    public static function setById($id){
        $dbData = DbSchema::getById($id);
        if($dbData){
            $dbData->ver += 1;
            $dbData->save();
        }
    }

    public static function getData()
    {
        $query = new Query();
        $query ->select(['id','ver'])
                ->from('imas_dbschema')
                ->where(['id' => 3])
                ->orWhere(['id' => 4]);
        $command = $query->createCommand();
        return $command->queryAll();

    }
    public static function insertData($lastFirstUpdate,$lastUpdate)
    {
        $query = "INSERT INTO imas_dbschema (id,ver) VALUES (3,$lastUpdate),(4,$lastFirstUpdate)";
    }

    public static function updateData($update,$id)
    {

        $data = DbSchema::find()->where(['id' => $id])->one();
        if($data)
        {
            $data->ver = $update;
            $data->save();
        }
    }
}