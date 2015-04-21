<?php

namespace app\models;
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
                ['confirmPassword', 'compare', 'compareAttribute'=>'newPassword'],
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

    public static function Update()
    {

    }

}
