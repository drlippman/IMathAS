<?php

use yii\base\Model;
class SetPassword extends Model
{
    public $password;

    public function rules()
    {
        return
            [
                [['password'],'required'],
            ];
    }

    public function attributeLabels()
    {
        return
            [
                'password' => 'Password',
            ];
    }

}