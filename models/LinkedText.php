<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 25/6/15
 * Time: 3:37 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasLinkedtext;
use yii\db\Query;

class LinkedText extends BaseImasLinkedtext
{
    public static function findExternalToolsInfo($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now){
        $query = new Query();
        $query->select(['id', 'title', 'text', 'startdate', 'enddate', 'points', 'avail'])
            ->from('imas_linkedtext')
            ->where(['courseid'=>$courseId])
            ->andWhere(['>', 'points', 0])
            ->andWhere(['>', 'avail', 0]);
        if (!$canviewall) {
            $query->andWhere(['<','startdate', $now]);
        }
        /*if ($istutor) {
            $query->andWhere(['<','tutoredit', 2]);
        }
        if ($catfilter>-1) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }*/
        $query->orderBy('enddate', 'startdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
}