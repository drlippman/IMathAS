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
            [['code'], 'integer'],
            ['code', 'compare', 'compareValue' => 0, 'operator'=> '>=','message'=>'Code must be of 10 digits.'],
            ['code', 'compare', 'compareValue' => 9999999999, 'operator' => '<=','message'=>'Code must be of 10 digits.'],
            [['usernameToEnroll','section'],'filter','filter'=>'\yii\helpers\Html::encode'],
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
