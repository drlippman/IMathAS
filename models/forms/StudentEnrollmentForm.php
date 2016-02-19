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

            [['usernameToEnroll'], 'required', 'message'=>AppUtility::t('Username cannot be blank.', false)],
            [['section'],'string', 'max' => 40],
            [['code'],'integer', 'max' => 10, 'message'=>AppUtility::t('Code (optional) must be an integer and must be not greater than 10.', false)],
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
