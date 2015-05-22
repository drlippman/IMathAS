<?php

namespace app\models\forms;

use yii\base\Model;

class StudentEnrollmentForm extends Model
{
    public $usernameToEnroll;
    public $section;
    public $code;


    public function rules()
    {
        return [

            [['usernameToEnroll'], 'required'],
            [['section'],'string'],
            [['code'],'string'],
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'usernameToEnroll' => 'User name to enroll:',
                'section' => 'Section (optional)',
                'code' => 'Code (optional)'
            ];
    }

}
