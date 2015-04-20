<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
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

            [['FirstName', 'LastName', 'email'], 'required'],
            ['rePassword', 'compare', 'compareAttribute'=>'password'],
            [['FirstName', 'LastName','oldPassword'], 'string'],
            ['email','email'],
            ['changePassword','boolean'],
            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
            ['file','safe'],
            [['file'],'file'],
            [['password', 'oldPassword', 'rePassword'], 'validatePassword'],
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

    public function validatePassword($attribute, $params)
    {
            if($this->changePassword)
            {
                if(!$this->oldPassword)
                    return  $this->addError('oldPassword', 'Old password is required.');
                if(!$this->password)
                    return  $this->addError('password', 'Password is required.');
                if(!($this->password == $this->rePassword))
                {
                    $this->addError('rePassword', 'Password did not matched with re-password.');
                    return false;
                }

                $user_password = Yii::$app->user->identity->password;
                if(!(AppUtility::verifyPassword($this->oldPassword, $user_password)))
                {
                  $this->addError('invalid', 'Old password did not matched.');
                    return false;
                }
            }
    }


    public function checkPassword()
    {
        if (!$this->validate()) {
            $errors = $this->getErrors();

            if(isset($errors['invalid'][0]))
            {
                $error = $errors['invalid'][0];
                Yii::$app->session->setFlash('danger', $error);
            }
            return false;
        }
        return true;
    }

}
