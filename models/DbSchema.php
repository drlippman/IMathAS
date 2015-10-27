<?php

namespace app\models;
use app\components\AppConstant;
use app\models\_base\BaseImasDbschema;
use app\components\AppUtility;
use yii\db\Query;

class DbSchema extends BaseImasDbschema {
    public static function getById($id)
    {
        return DbSchema::findOne(['id' => $id]);
    }

    public static function setById($id)
    {
        $dbData = DbSchema::getById($id);
        if($dbData){
            $dbData->ver += AppConstant::NUMERIC_ONE;
            $dbData->save();
        }
    }

    public static function getData()
    {
        $query = new Query();
        $query ->select(['id','ver'])
                ->from('imas_dbschema')
                ->where(['id' => AppConstant::NUMERIC_THREE])
                ->orWhere(['id' => AppConstant::NUMERIC_FOUR]);
        $command = $query->createCommand();
        return $command->queryAll();

    }
    public function insertData($id,$var)
    {
        $this->id = $id;
        $this->ver = $var;
        $this->save();
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