<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 25/6/15
 * Time: 3:37 PM
 */

namespace app\models;
use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasLibraries;
use app\models\_base\BaseImasLibrariesa;
use yii\db\Query;

class Libraries extends BaseImasLibraries
{
    public static function getByIdList($llist){

        $query = \Yii::$app->db->createCommand("SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)")->queryAll();
        return $query;
    }
}