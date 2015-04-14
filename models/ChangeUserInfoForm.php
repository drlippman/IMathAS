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
    public $uploadPicture;
    public $message;
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
            [['FirstName', 'LastName'], 'string'],
            ['email','email'],
            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
            ['uploadPicture','file'],
        ];

    }

    public function attributeLabels()
    {
        return [
            'password' => 'Change Password',
            'rePassword'=>'confirm Password',
            'FirstName' => 'Enter FirstName',
            'LastName' => 'Enter LastName',
            'email' => 'Email',
            'NotifyMeByEmailWhenIReceiveANewMessage'=>'Notify me by email when I receive a new message',
            'uploadPicture'=>'Picture',
            'message'=>'Messages/Posts per page',
            'homepage'=>'Show on home page',
        ];
    }

}
