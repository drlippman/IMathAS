<?php

namespace app\models\forms;

use yii\base\Model;
use app\components\AppUtility;
class StudentEnrollmentForm extends Model
{
    public $usernameToEnroll;
    public $section;
    public $code;


    public function rules()
    {
        return [

            [['usernameToEnroll'], 'required', 'message'=>AppUtility::t('Username cannot be blank', false)],
            [['section'],'string'],
            [['code'],'string'],
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'usernameToEnroll' => AppUtility::t('Username to enroll',false),
                'section' => AppUtility::t('Section (optional)', false),
                'code' => AppUtility::t('Code (optional)', false)
            ];
    }

}
