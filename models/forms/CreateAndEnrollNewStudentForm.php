<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 27/5/15
 * Time: 12:32 PM
 */

namespace app\models\forms;

use yii\base\Model;

class CreateAndEnrollNewStudentForm extends Model
{
    public $username;
    public $password;
    public $FirstName;
    public $LastName;
    public $email;
    public $section;
    public $code;


    public function rules()
    {
        return [

            [['username'],'required','message'=>'Username cannot be blank'],
            [['password'],'required','message'=>'Password cannot be blank'],
            [['FirstName'],'required','message'=>'First Name cannot be blank'],
            [['LastName'],'required','message'=>'Last Name cannot be blank'],
            [['email'],'required','message'=>'Email Address cannot be blank'],
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => 'Username can contain only alphanumeric characters and Underscore(_).'],
            [['FirstName', 'LastName'], 'string'],
            ['email','email'],
            [['section'],'string'],
            [['code'],'string'],
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'username' => 'Username :',
                'password' => 'Choose a password :',
                'FirstName'=> 'Enter First Name :',
                'LastName' => 'Enter Last Name :',
                'email'    => 'Enter E-mail address :',
                'section' => 'Section (optional) :',
                'code' => 'Code (optional) :'
            ];
    }

}
