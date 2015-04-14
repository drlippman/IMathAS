<?php

namespace app\models;
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
                ['confirmPassword', 'compare', 'compareAttribute'=>'newPassword'],
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
