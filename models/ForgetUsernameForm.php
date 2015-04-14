<?php

namespace app\models;
use yii\base\Model;
class ForgetUsernameForm extends Model
{
    public $email;

    public function rules()
    {
        return
            [
                [['email'],'required'],
                [['email'],'email'],
            ];
    }

    public function attributeLabels()
    {
        return
            [
                'email' => 'Email',
            ];
    }
}
