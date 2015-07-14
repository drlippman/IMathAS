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
            ->where(['courseid'=>$courseId]);
//            ->orderBy('name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourseId($courseId)
    {
        return GbCats::find()->select('id,name')->where(['courseid' => $courseId])->all();
    }

    public  static function updateGbCat($id, $name, $scale, $scaleType, $chop, $drop, $weight, $hide, $calcType){
        $query = GbCats::findOne(['id' => $id]);
        if($query){
            $query->name = $name;
            $query->scale = $scale;
            $query->scaletype = $scaleType;
            $query->chop = $chop;
            $query->dropn = $drop;
            $query->weight = $weight;
            $query->hidden = $hide;
            $query->calctype = $calcType;
            $query->save();
        }
    }
    public static function deleteGbCat($catList){

        foreach($catList as $category){
            $query = GbCats::findOne(['id' => $category]);
            if($query){
                $query->delete();
            }
        }
    }
    public static function createGbCat($courseId, $name, $scale, $scaleType, $chop, $weight, $hide, $calcType){
        $query = new GbCats();
        $query->courseid = $courseId;
        $query->scale = $scale;
        $query->scaletype = $scaleType;
        $query->chop = $chop;
        $query->name = $name;
        $query->weight = $weight;
        $query->hidden = $hide;
        $query->calctype = $calcType;
        $query->save();
    }
}
