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
use app\models\_base\BaseImasGrades;
use yii\db\Query;

class Grades extends BaseImasGrades
{
    public function createGradesByUserId($singal,$gbItemsId)
    {
        if($singal['gradeText'] || $singal['feedbackText']){
            $this->gradetypeid = $gbItemsId;
            $this->userid = $singal['studentId'];
            $this->score = $singal['gradeText'];
            $this->feedback = $singal['feedbackText'];
            $this->save();
        }
    }

    public static function GetOtherGrades($gradetypeselects, $limuser){
        $query = new Query();
        $query->select(['*'])
            ->from('imas_grades');
        foreach($gradetypeselects as $gradeSelect){
            $query->orWhere(["gradetype" => $gradeSelect['gradetype']])
                    ->andWhere(['IN', 'gradetypeid', $gradeSelect['gradetypeid']]);
        }
        if ($limuser > 0) {
            $query->andWhere(["userid" => $limuser]);
        }
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }
}

