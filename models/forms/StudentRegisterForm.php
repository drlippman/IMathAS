<?php

namespace app\models\forms;

use app\components\AppUtility;
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
            [['courseID', 'EnrollmentKey'], 'string'],
        ];

    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'SID' => AppUtility::t('Enter username', false),
            'password' => AppUtility::t('Password', false),
            'rePassword' => AppUtility::t('Confirm password', false),
            'FirstName' => AppUtility::t('First name', false),
            'LastName' => AppUtility::t('Last name', false),
            'email' => AppUtility::t('Email', false),
            'CourseId' => AppUtility::t('Course Id', false),
            'EnrollmentKey' => AppUtility::t('Enrollment Key', false),
            'NotifyMeByEmailWhenIReceiveANewMessage' => AppUtility::t('Notify me by email when I receive a new message', false)
        ];
    }
}