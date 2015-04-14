<?php

namespace app\models;
use yii\base\Model;
class ForgetPasswordForm extends Model
{
    public $username;

    public function rules()
    {
        return
            [
                [['username'],'required'],
            ];
    }

    public function attributeLabels()
    {
        return
            [
                'username' => 'Username',
            ];
    }
}
