<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;

class ManageLatePassesForm extends Model
{
    public $latepasshrs;
    public $toAllStudents;
    public $FirstName;
    public $LastName;
    public $section;
    public $latepass;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

//            [['username', 'password', 'FirstName', 'LastName'], 'required'],
//            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => 'Username can contain only alphanumeric characters and hyphens(-).'],
//            ['rePassword', 'compare', 'compareAttribute' => 'password','message'=>'Confirm password doesn\'t match with password.'],
//            [['FirstName', 'LastName'], 'string'],
//            ['email', 'email','message' => 'Enter a valid email address.'],
//            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
//            /*[['NotifyMeByEmailWhenIReceiveANewMessage'],'requiredValue' => 1, 'message' => 'ghg'],*/
//            [['courseID', 'EnrollmentKey'], 'string'],
        ];

    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }
}