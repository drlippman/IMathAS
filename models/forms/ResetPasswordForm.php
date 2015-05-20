<?php

namespace app\models\forms;
use yii\base\Model;
class ResetPasswordForm extends Model
{
    public $confirmPassword;
    public $newPassword;

    public function rules()
    {
        return
            [
                [['confirmPassword','newPassword'],'required'],
                ['confirmPassword', 'compare', 'compareAttribute'=>'newPassword', 'message' => 'Confirm password does not match with password.' ],
            ];

    }

    public function attributeLabels()
    {
        return
            [
                'newPassword' => 'New Password',
                'confirmPassword'=>'Confirm Password',

            ];
    }

}
