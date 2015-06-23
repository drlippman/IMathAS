<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 6/5/15
 * Time: 12:34 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\models\_base\BaseImasItems;

class Items extends BaseImasItems
{
    public static function getByCourseId($courseId)
    {
        return Items::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return Items::findOne(['id' => $id]);
    }

    public function create($cid,$item)
    {
        $this->courseid = $cid;
        $this->itemtype = $item;
        $this->typeid = AppConstant::NUMERIC_ZERO;
        $this->save();
        return $this->id;
    }

    public static function deletedItems($id)
    {
        return Items::deleteAll(['id' => $id]);
    }
}