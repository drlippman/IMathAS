<?php

namespace app\models;

use Yii;
use yii\base\Model;
class ChangeUserInfoForm extends Model
{
    public $FirstName;
    public $LastName;
    public $password;
    public $rePassword;
    public $email;
    public $NotifyMeByEmailWhenIReceiveANewMessage= true;
    public $file;
    public $message;
    public $oldPassword;
    public $changePassword;
    public $homepage;
    public $NewMessagesWidget;
    public $NewForumPostsWidget;
    public $NewMessagesNotesOnCourseList;
    public $NewPostsNotesOnCourseList;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

//            ['rePassword', 'compare', 'compareAttribute'=>'password'],
            [['FirstName', 'LastName','oldPassword'], 'string'],
            ['email','email'],
            ['changePassword','boolean'],
            ['rePassword', 'compare', 'compareAttribute'=>'password'],
            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
            ['file','safe'],
            [['file'],'file'],
        ];

    }

    public function attributeLabels()
    {
        return [
            'oldPassword' => 'Old Password',
            'password' => 'Change Password',
            'rePassword'=>'Confirm Password',
            'changePassword'=>'Click here to change password',
            'FirstName' => 'Enter First Name',
            'LastName' => 'Enter Last Name',
            'email' => 'Email',
            'NotifyMeByEmailWhenIReceiveANewMessage'=>'Notify me by email when I receive a new message',
            'file'=>'Picture',
            'message'=>'Messages/Posts per page',
            'homepage'=>'Show on home page',
        ];
    }

}
