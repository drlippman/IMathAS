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

    public static function getAllLibrariesByJoin(){
        $query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,COUNT(imas_library_items.id) AS count ";
        $query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id GROUP BY imas_libraries.id";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
}