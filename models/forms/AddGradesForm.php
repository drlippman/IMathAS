<?php
namespace app\models\forms;

use Yii;
use yii\base\Model;
class AddGradesForm extends Model
{
    public $Name;
    public $Points;
    public $ShowGrade;
    public $GradeBookCategory;
    public $Count;
    public $TutorAccess;
    public $ScoringRubric;
    public $UploadGrades;
    public $AssessmentSnapshot;
    public $AddReplaceGrades;


    /**
     * @return array the validation rules.
//     */
//    public function rules()
//    {
//        return [
//            [['FirstName', 'LastName', 'email'], 'required'],
//            ['rePassword', 'compare', 'compareAttribute'=>'password'],
//            [['FirstName', 'LastName','oldPassword'], 'string'],
//            ['email','email'],
//            ['changePassword','boolean'],
//            ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
//            ['remove', 'boolean'],
//            ['file','safe'],
//            [['file'], 'file', 'extensions' => 'gif, jpeg, jpg, png'],
//            [['password', 'oldPassword', 'rePassword'], 'validatePassword'],
//        ];
//
//    }
//
    public function attributeLabels()
    {
        return [
            'ShowGrade' => 'Show grade to students after',
            'UploadGrades' => 'Upload grades?',
            'AssessmentSnapshot'=>'Assessment snapshot?',
            'ScoringRubric'=>' Use scoring rubric',
//            'FirstName' => 'Enter First Name',
//            'LastName' => 'Enter Last Name',
//            'email' => ' Enter Email',
//            'Username' => 'Enter User Name (login name)',
//            'NotifyMeByEmailWhenIReceiveANewMessage'=>'Notify me by email when I receive a new message',
//            'file'=>'Picture',
//            'message'=>'Messages/Posts per page',
//            'homepage'=>'Show on home page',
//            'remove' => 'Remove',
//            'section' => 'Section(optional)',
//            'code' => 'Code(optional)',
//            'timelimitmult' =>'Time Limit Multiplier',
//            'locked' => 'Lock out of course?',
//            'hidefromcourselist' =>'Student has course hidden from course list?',
//            'ispasswordchange' => 'Reset Password',
        ];
    }
//
//    public function validatePassword($attribute, $params)
//    {
//        if($this->changePassword)
//        {
//            if(!$this->oldPassword)
//                return  $this->addError('oldPassword', 'Old password is required.');
//            if(!$this->password)
//                return  $this->addError('password', 'Password is required.');
//            if(!($this->password == $this->rePassword))
//            {
//                $this->addError('rePassword', 'Password did not matched with re-password.');
//                return false;
//            }
//
//            $user_password = Yii::$app->user->identity->password;
//            if(!(AppUtility::verifyPassword($this->oldPassword, $user_password)))
//            {
//                $this->addError('invalid', 'Old password did not matched.');
//                return false;
//            }
//        }
//    }
//
//
//    public function checkPassword()
//    {
//        if (!$this->validate()) {
//            $errors = $this->getErrors();
//
//            if(isset($errors['invalid'][0]))
//            {
//                $error = $errors['invalid'][0];
//                Yii::$app->session->setFlash('danger', $error);
//            }
//            return false;
//        }
//        return true;
//    }

}
