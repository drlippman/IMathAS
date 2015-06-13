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

            [['usernameToEnroll'], 'required', 'message'=>'Username cannot be blank'],
            [['section'],'string'],
            [['code'],'string'],
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'usernameToEnroll' => 'Username to enroll',
                'section' => 'Section (optional)',
                'code' => 'Code (optional)'
            ];
    }

}
