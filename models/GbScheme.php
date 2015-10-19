<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasGbscheme;
use yii\db\Query;

class GbScheme extends BaseImasGbscheme
{
    public function create($courseId)
    {
        $this->useweights = AppConstant::GB_USE_WEIGHT;
        $this->usersort = AppConstant::GB_USER_SORT;
        $this->defgbmode = AppConstant::GB_DEF_GB_MODE;
        $this->orderby = AppConstant::GB_ORDERED_BY;
        $this->courseid = $courseId;
        $this->save();
        return $this->id;
    }

    public static  function getByCourseId($courseId)
    {
       $result = GbScheme::findOne(['courseid'=> $courseId]);
        return $result;
    }

    public static  function findByCourseId($courseId)
    {
        return self::find()->select(['useweights', 'orderby', 'defaultcat', 'defgbmode', 'usersort', 'stugbmode', 'colorize'])->where(['imas_gbscheme.courseid' => $courseId])->all();
    }

    public static function updateGbScheme($useWeights, $orderBy, $userSort, $defaultCat, $defGbMode, $stuGbMode, $colorize, $courseId)
    {
        $query = GbScheme::findOne(['courseid' => $courseId]);
        if($query){
            $query->useweights = $useWeights;
            $query->orderby = $orderBy;
            $query->usersort = $userSort;
            $query->defaultcat = $defaultCat;
            $query->defgbmode = $defGbMode;
            $query->stugbmode = $stuGbMode;
            $query->colorize = $colorize;
            $query->save();
        }
    }

    public static function getDataForCopyCourse($ctc)
    {
        return self::find()->select(['useweights','orderby','defaultcat','defgbmode','stugbmode','colorize'])->where(['courseid' => $ctc])->one();
    }

    public static function updateDataForCopyCourse($query,$courseId)
    {
        $query = GbScheme::find()->where(['courseid' => $courseId])->one();
        if($query)
        {
            $query->useweights = $query['useweights'];
            $query->orderby = $query['orderby'];
            $query->defaultcat = $query['defaultcat'];
            $query->defgbmode = $query['defgbmode'];
            $query->stugbmode = $query['stugbmode'];
            $query->colorize = $query['colorize'];
            $query->save();
        }
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = GbScheme::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
        }
    }
}