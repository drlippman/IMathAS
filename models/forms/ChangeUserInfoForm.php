<?php

namespace app\models\forms;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\User;
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
            [['FirstName', 'LastName'], 'string','max'=>30],
            [['SID'], 'string','max'=>30],
            ['rePassword', 'compare', 'compareAttribute'=>'password'],
            [['FirstName', 'LastName','oldPassword'], 'string'],
            ['email','email'],
            ['changePassword','boolean'],
            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
            ['remove', 'boolean'],
            ['file','safe'],
            [['file'], 'file', 'extensions' => 'gif, jpeg, jpg, png'],
            [['password', 'oldPassword', 'rePassword'], 'validatePassword'],
            [['section'],'string', 'max'=>40],
            [['code'], 'integer'],
            ['code', 'compare', 'compareValue' => 0, 'operator'=> '>=','message'=>'Code must be of 10 digits.'],
            ['code', 'compare', 'compareValue' => 9999999999, 'operator' => '<=','message'=>'Code must be of 10 digits.'],
        ];

    }

    public function attributeLabels()
    {
        return [
            'oldPassword' => AppUtility::t('Old Password', false),
            'password' => AppUtility::t('Change Password', false),
            'rePassword'=>AppUtility::t('Confirm Password', false),
            'changePassword'=>AppUtility::t('Click here to change password', false),
            'FirstName' => AppUtility::t('First Name', false),
            'LastName' => AppUtility::t('Last Name', false),
            'email' => AppUtility::t('Email', false),
            'SID' => AppUtility::t('User Name', false),
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


    public function checkPassword($userid,$params)
    {
        $params = $params['ChangeUserInfoForm'];
        $data = User::getById($userid);
        $oldPassword = AppUtility::passwordHash($params['password']);
        if($params['password'] == $params['rePassword'])
        {
            if(strcmp($oldPassword,$data['password']))
            {
                return AppConstant::NUMERIC_TWO;
            }
            else
            {
                return AppConstant::NUMERIC_ZERO;
            }
        }
        else
        {
            return AppConstant::NUMERIC_ONE;
        }
   }

}
