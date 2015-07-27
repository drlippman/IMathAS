<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class AssignSectionAndCodesForm extends Model
{

    public $FirstName;
    public $LastName;
    public $section;
    public $latepass;
    public $code;


    public function attributeLabels()
    {
        return [

        ];
    }

}