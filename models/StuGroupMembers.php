<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/8/15
 * Time: 8:34 PM
 */

namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\models\_base\BaseImasStugroupmembers;
use yii\db\Query;

class StuGroupMembers extends BaseImasStugroupmembers{

    public static function deleteMemberFromCourse($toUnEnroll, $stuGroups)
    {
        $query = StuGroupMembers::find()->where(['IN', 'stugroupid', $stuGroups])->andWhere(['IN', 'userid', $toUnEnroll])->all();
        if($query){
            foreach($query as $object){
                $object->delete();
            }
        }
    }
}