<?php

/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 5/6/15
 * Time: 7:13 PM
 */

namespace app\models;
use app\components\AppConstant;
use Yii;

use app\components\AppUtility;
use app\models\_base\BaseImasGbitems;
use yii\db\Query;

class GbItems extends BaseImasGbitems
{
    public function createGbItemsByCourseId($courseId,$params)
    {
//        AppUtility::dump($params['AddGradesForm']['TutorAccess']);
        $this->courseid = $courseId;
        $name = $params['AddGradesForm']['Name'];
        $this->name = isset($name) ? $name : null;
        $this->points = $params['AddGradesForm']['Points'];
        $showdate = AppConstant::NUMERIC_ZERO;
        if($params['AddGradesForm']['ShowGrade'] == AppConstant::NUMERIC_TWO)
        {
            $showdate = strtotime(date('F d, o g:i a'));
        }
        $this->showdate = $showdate;
        $this->gbcategory = $params['AddGradesForm']['GradeBookCategory'];
        $this ->rubric = $params['rubric'];
        $this->cntingb = $params['AddGradesForm']['Count'];
        $this->tutoredit = $params['AddGradesForm']['TutorAccess'];
        $this->save();
        return $this->id;
    }
    public static function findAllOfflineGradeItem($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now){

        $query = new Query();
        $query->select(['*'])
            ->from('imas_gbitems')
            ->where(['courseid'=>$courseId]);
        if (!$canviewall) {
            $query->andWhere(['<','showdate', $now]);
        }
        if (!$canviewall) {
            $query->andWhere(['>','cntingb', 0]);
        }
        if ($istutor) {
            $query->andWhere(['<','tutoredit', 2]);
        }
        if ($catfilter>-1) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('showdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

}

