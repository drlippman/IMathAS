<?php

namespace app\models\forms;

use app\components\AppConstant;
use app\components\AppUtility;
use Yii;
use yii\base\Model;
class ChangeUserInfoForm extends Model
{
    public $SID;
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
    public $remove;
    public $section;
    public $code;
    public $timelimitmult;
    public $locked;
    public $ispasswordchange;
    public $hidefromcourselist;
    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['SID'], 'required', 'message' => AppUtility::t('User name cannot be blank', false)],
            [['email'], 'required', 'message' => AppUtility::t('Email cannot be blank', false)],
            [['LastName'], 'required', 'message' => AppUtility::t('Last name cannot be blank', false)],
            [['FirstName'], 'required', 'message' => AppUtility::t('First name cannot be blank', false)],
            ['rePassword', 'compare', 'compareAttribute'=>'password'],
            [['FirstName', 'LastName','oldPassword'], 'string'],
            ['email','email'],
            ['changePassword','boolean'],
            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
            ['remove', 'boolean'],
            ['file','safe'],
            [['file'], 'file', 'extensions' => 'gif, jpeg, jpg, png'],
            [['password', 'oldPassword', 'rePassword'], 'validatePassword'],
        ];

    }

    public function attributeLabels()
    {
        return [
            'oldPassword' => AppUtility::t('Old Password', false),
            'password' => AppUtility::t('Change Password', false),
            'rePassword'=>AppUtility::t('Confirm Password', false),
            'changePassword'=>AppUtility::t('Click here to change password', false),
            'FirstName' => AppUtility::t('Enter First Name', false),
            'LastName' => AppUtility::t('Enter Last Name', false),
            'email' => AppUtility::t('Enter Email', false),
            'SID' => AppUtility::t('Enter User Name', false),
            'NotifyMeByEmailWhenIReceiveANewMessage'=>AppUtility::t('Notify me by email when I receive a new message', false),
            'file'=>AppUtility::t('Picture', false),
            'message'=> AppUtility::t('Messages/Posts per page', false),
            'homepage'=>AppUtility::t('Show on home page', false),
            'remove' => AppUtility::t('Remove', false),
            'section' => AppUtility::t('Section(optional)', false),
            'code' => AppUtility::t('Code(optional)', false),
            'timelimitmult' => AppUtility::t('Time Limit Multiplier', false),
            'locked' => AppUtility::t('Lock out of course?', false),
            'hidefromcourselist' => AppUtility::t('Student has course hidden from course list?', false),
            'ispasswordchange' => AppUtility::t('Reset Password', false),
        ];
    }

    public function validatePassword($attribute, $params)
    {
            if($this->changePassword)
            {
                if(!$this->oldPassword)
                    return  $this->addError('oldPassword', AppUtility::t('Old password is required.', false));
                if(!$this->password)
                    return  $this->addError('password', AppUtility::t('Password is required.', false));
                if(!($this->password == $this->rePassword))
                {
                    $this->addError('rePassword', AppUtility::t('Password did not matched with re-password.', false));
                    return false;
                }

                $user_password = Yii::$app->user->identity->password;
                if(!(AppUtility::verifyPassword($this->oldPassword, $user_password)))
                {
                  $this->addError('invalid', AppUtility::t('Old password did not matched.', false));
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
