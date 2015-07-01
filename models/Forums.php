<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 29/4/15
 * Time: 12:30 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasForums;
use yii\db\Query;

class Forums extends BaseImasForums {

    public static function getByCourseId($courseId)
    {
        return Forums::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return Forums::findOne(['id' => $id]);
    }

    public static function getByCourse($courseId)
    {
        return Forums::find()->select('id,name')->where(['courseid' => $courseId])->all();
    }

    public static function getByCourseIdOrdered($courseId,$sort,$orderBy)
    {
        return Forums::find()->where(['courseid' => $courseId])->orderBy([$orderBy => $sort])->all();
    }
    public  static  function findDiscussionGradeInfo($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now){

        $query = new Query();
        $query->select(['id', 'name', 'gbcategory', 'startdate', 'enddate', 'replyby', 'postby', 'points', 'cntingb', 'avail'])
            ->from('imas_forums')
            ->where(['courseid'=>$courseId])
            ->andWhere(['>', 'points', 0])
            ->andWhere(['>', 'avail', 0]);
        if (!$canviewall) {
            $query->andWhere(['<','startdate', $now]);
        }
        if ($istutor) {
            $query->andWhere(['<','tutoredit', 2]);
        }
        if ($catfilter>-1) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('enddate', 'postby', 'replyby', 'startdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
} 