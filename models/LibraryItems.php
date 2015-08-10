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
use app\models\_base\BaseImasLibraryItems;
use yii\db\Query;

class LibraryItems extends BaseImasLibraryItems
{
    public static function getByQuestionSetId($existingqlist){
        $query = \Yii::$app->db->createCommand("SELECT libid,COUNT(qsetid) FROM imas_library_items WHERE qsetid IN ($existingqlist) GROUP BY libid")->queryAll();
        return $query;
    }
}