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
}