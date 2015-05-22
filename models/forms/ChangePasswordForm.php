<?php

namespace app\models\forms;
use yii\base\Model;
class ChangePasswordForm extends Model
{
    public $oldPassword;
    public $confirmPassword;
    public $newPassword;

    public function rules()
    {
        return
            [
                [['oldPassword','confirmPassword','newPassword'],'required'],
                ['confirmPassword', 'compare', 'compareAttribute'=>'newPassword','message' => 'Confirm password does not match Old password'],
            ];

    }

    public function attributeLabels()
    {
        return
            [
                'oldPassword' => 'Old Password',
                'newPassword' => 'New Password',
                'confirmPassword'=>'Confirm Password',

            ];
    }

}
