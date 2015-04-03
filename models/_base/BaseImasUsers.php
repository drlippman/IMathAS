<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_users".
 *
 * @property string $id
 * @property string $SID
 * @property string $password
 * @property integer $rights
 * @property string $FirstName
 * @property string $LastName
 * @property string $email
 * @property string $lastaccess
 * @property string $groupid
 * @property integer $msgnotify
 * @property integer $qrightsdef
 * @property string $deflib
 * @property integer $usedeflib
 * @property string $homelayout
 * @property integer $hasuserimg
 * @property string $remoteaccess
 * @property integer $listperpage
 * @property string $hideonpostswidget
 */
class BaseImasUsers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['SID', 'password', 'FirstName', 'LastName', 'email', 'hideonpostswidget'], 'required'],
            [['rights', 'lastaccess', 'groupid', 'msgnotify', 'qrightsdef', 'deflib', 'usedeflib', 'hasuserimg', 'listperpage'], 'integer'],
            [['hideonpostswidget'], 'string'],
            [['SID'], 'string', 'max' => 50],
            [['password'], 'string', 'max' => 254],
            [['FirstName', 'LastName'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['homelayout'], 'string', 'max' => 32],
            [['remoteaccess'], 'string', 'max' => 10],
            [['SID'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'SID' => 'Sid',
            'password' => 'Password',
            'rights' => 'Rights',
            'FirstName' => 'First Name',
            'LastName' => 'Last Name',
            'email' => 'Email',
            'lastaccess' => 'Lastaccess',
            'groupid' => 'Groupid',
            'msgnotify' => 'Msgnotify',
            'qrightsdef' => 'Qrightsdef',
            'deflib' => 'Deflib',
            'usedeflib' => 'Usedeflib',
            'homelayout' => 'Homelayout',
            'hasuserimg' => 'Hasuserimg',
            'remoteaccess' => 'Remoteaccess',
            'listperpage' => 'Listperpage',
            'hideonpostswidget' => 'Hideonpostswidget',
        ];
    }
}
