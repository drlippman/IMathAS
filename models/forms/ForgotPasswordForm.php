<?php

namespace app\models\forms;
use yii\base\Model;
class ForgotPasswordForm extends Model
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
