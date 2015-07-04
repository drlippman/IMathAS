<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 6:30 PM
 */

namespace app\models;




use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasGbscheme;

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
       $result = GbScheme::find()->select('useweights,orderby,defaultcat,usersort')->where(['courseid'=> $courseId])->all();
        return $result;




    }
}