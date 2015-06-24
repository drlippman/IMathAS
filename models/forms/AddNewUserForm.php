<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
class AddNewUserForm extends Model
{
    public $username;
    public $FirstName;
    public $LastName;
    public $email;
    public $password;
    public $rights;
    public $AssignToGroup;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['FirstName', 'LastName','username','password','email'],'required'],
            [['rights'],'required'],
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => 'Username can contain only alphanumeric characters and hyphens(-).'],
            [['FirstName', 'LastName'], 'string'],
            ['email','email'],
        ];

    }

    public function attributeLabels()
    {
        return [
            'SID' => Yii::t('yii', 'New User username'),
            'FirstName'=> Yii::t('yii', 'First Name'),
            'LastName'=> Yii::t('yii','Last Name'),
            'email'=>'Email',
            'password'=>'Password',
            'rights'=>'Set User Rights to',
            'AssignToGroup'=>'Assign To Group',

        ];
    }
}
