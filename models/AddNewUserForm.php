<?php

namespace app\models;

use Yii;
use yii\base\Model;
class AddNewUserForm extends Model
{
    public $SID;
    public $FirstName;
    public $LastName;
    public $email;
    public $password;
    public $SetUserRights;
    public $AssignToGroup;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['FirstName', 'LastName','SID','password','email'],'required'],
             [['FirstName', 'LastName'], 'string'],
             ['email','email'],
        ];

    }

    public function attributeLabels()
    {
        return [
            'SID'=>'New User username',
            'FirstName'=>'First Name',
            'LastName'=>'Last Name',
            'email'=>'Email',
            'password'=>'Password',
            'SetUserRights'=>'Set User Rights to',
            'AssignToGroup'=>'Assign To Group',

        ];
    }
}
