<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/8/15
 * Time: 5:28 PM
 */
namespace app\models;

use app\models\_base\BaseImasDrillassessSessions;

class DrillassessSessions extends BaseImasDrillassessSessions
{
    public static function deleteDrillSession($drills, $toUnEnroll)
    {
        $query = DrillassessSessions::find()->where(['IN', 'drillassessid', $drills])->andWhere(['IN', 'userid', $toUnEnroll])->all();
        if ($query) {
            foreach ($query as $drillSession) {
                $drillSession->delete();
            }
        }
    }
}