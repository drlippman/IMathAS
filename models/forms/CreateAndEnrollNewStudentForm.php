<?php

namespace app\models\forms;

use app\components\AppUtility;
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

            [['username'],'required','message'=>AppUtility::t('Username cannot be blank', false)],
            [['password'],'required','message'=>AppUtility::t('Password cannot be blank', false)],
            [['FirstName'],'required','message'=>AppUtility::t('First Name cannot be blank', false)],
            [['LastName'],'required','message'=>AppUtility::t('Last Name cannot be blank', false)],
            [['username'],'string','max'=>30],
            [['FirstName', 'LastName'],'string','max'=>30],
            [['email'],'required','message'=>AppUtility::t('Email Address cannot be blank', false)],
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => AppUtility::t('Username can contain only alphanumeric characters and Underscore(_).', false)],
            [['FirstName', 'LastName'], 'string'],
            ['email','email'],
            [['section'],'string', 'max'=>40],
            [['code'], 'integer'],
            ['code', 'compare', 'compareValue' => 0, 'operator'=> '>=','message'=>'Code must be of 10 digits.'],
            ['code', 'compare', 'compareValue' => 9999999999, 'operator' => '<=','message'=>'Code must be of 10 digits.'],
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'username' => AppUtility::t('Username', false),
                'password' => AppUtility::t('Choose a password', false),
                'FirstName'=> AppUtility::t('First Name', false),
                'LastName' => AppUtility::t('Last Name', false),
                'email'    => AppUtility::t('E-mail address', false),
                'section' => AppUtility::t('Section (optional)', false),
                'code' => AppUtility::t('Code (optional)', false)
            ];
    }

}
