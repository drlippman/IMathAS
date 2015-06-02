<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class StudentRegisterForm extends Model
{
    public $username;
    public $FirstName;
    public $LastName;
    public $password;
    public $rePassword;
    public $email;
    public $NotifyMeByEmailWhenIReceiveANewMessage = true;
    public $courseID;
    public $EnrollmentKey;
    public $isUserNameExist;
    public $contactNo;
    public $uploadFile;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

            [['username', 'password', 'FirstName', 'LastName'], 'required'],
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => 'Username can contain only alphanumeric characters and hyphens (-).'],
            ['rePassword', 'compare', 'compareAttribute' => 'password','message'=>'Confirm password must be repeated exactly.'],
            [['FirstName', 'LastName'], 'string'],
            ['email', 'email','message' => 'Enter a valid email address.'],
            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
            /*[['NotifyMeByEmailWhenIReceiveANewMessage'],'requiredValue' => 1, 'message' => 'ghg'],*/
            [['courseID', 'EnrollmentKey'], 'string'],
        ];

    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'SID' => 'Enter username',
            'password' => 'Password',
            'rePassword' => 'Confirm password',
            'FirstName' => 'First name',
            'LastName' => 'Last name',
            'email' => 'Email',
            'CourseId' => 'Course Id',
            'EnrollmentKey' => 'Enrollment Key',
            'NotifyMeByEmailWhenIReceiveANewMessage' => 'Notify me by email when I receive a new message'
        ];
    }
}