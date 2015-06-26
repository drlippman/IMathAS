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
}

