<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\validators\validator;
/**
 * This is the model class for table "imas_users".
 *
 * @property integer $id
 * @property string $SID
 * @property string $password
 * @property integer $rights
 * @property string $FirstName
 * @property string $LastName
 * @property string $email
 * @property integer $lastaccess
 * @property integer $groupid
 * @property integer $msgnotify
 * @property integer $qrightsdef
 * @property integer $deflib
 * @property integer $usedeflib
 * @property string $homelayout
 * @property integer $hasuserimg
 * @property string $remoteaccess
 * @property integer $listperpage
 * @property string $hideonpostswidget
 */
class RegistrationForm extends Model
{
    /**
     * @inheritdoc
     */
  /*  public static function tableName()
    {
        return 'imas_users';
    }*/

    /**
     * @inheritdoc
     */
    public $username;
    public $password;
    public $FirstName;
    public $LastName;
    public $email;
    public $confirmPassword;
    public $school;
    public $phoneno;
    public $NotifyMeByEmailWhenIReceiveANewMessage;
    public $terms;

    public function rules()
    {

        return [
            [['FirstName', 'LastName', 'username', 'password','email'], 'required'],
            ['email','email','message' => 'Enter a valid email address.'],
            [['password'], 'string', 'max' => 254],
            [['FirstName', 'LastName'], 'string', 'max' => 20,'tooLong'=>'{attribute} contains maximum 20 characters.'],
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => 'Username can contain only alphanumeric characters and hyphens (-).'],
            [['email'], 'string', 'max' => 100],
            ['phoneno','number'],
            [['phoneno'], 'string','max'=> 10,'min'=> 10],
            [['confirmPassword'],'compare','compareAttribute'=>'password','message' => 'Confirm password does not match with password.'],
            ['terms', 'compare', 'compareValue' => 1, 'message' => 'You should accept term to use our service.']
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'SID' => 'User Name',
            'password' => 'Password',
            'FirstName' => 'First Name',
            'LastName' => 'Last Name',
            'email' => 'Email Address',
            'phoneno'=>'Phone Number',
            'terms'=>'I have read and agree to the Terms of Use (below).',
            'school' => 'School/College'
        ];
    }
}
