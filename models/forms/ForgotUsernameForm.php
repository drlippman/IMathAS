<?php

namespace app\models\forms;
use yii\base\Model;
class ForgotUsernameForm extends Model
{
    public $email;

    public function rules()
    {
        return
            [
                [['email'],'required', 'message' => 'Email address cannot be blank'],
                [['email'],'email','message' => 'Enter a valid email address.'],
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
