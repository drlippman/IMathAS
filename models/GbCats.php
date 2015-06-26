<?php

/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 25/6/15
 * Time: 7:13 PM
 */

namespace app\models;
use app\components\AppConstant;
use app\models\_base\BaseImasGbcats;
use Yii;
use app\components\AppUtility;
use yii\db\Query;

class GbCats extends BaseImasGbcats
{
    public static function findCategoryByCourseId($courseId){
        $query = new Query();
        $query->select(['id', 'name', 'scale', 'scaletype', 'chop', 'dropn', 'weight', 'hidden', 'calctype'])
            ->from('imas_gbcats')
            ->where(['courseid'=>$courseId])
            ->orderBy('name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
}
