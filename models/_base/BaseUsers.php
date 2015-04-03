<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property integer $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $username
 * @property integer $contact
 * @property integer $is_active
 * @property string $password
 */
class BaseUsers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'email', 'username', 'contact', 'is_active', 'password'], 'required'],
            [['contact', 'is_active'], 'integer'],
            [['first_name', 'last_name'], 'string', 'max' => 32],
            [['email', 'password'], 'string', 'max' => 256],
            [['username'], 'string', 'max' => 64],
            [['username'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'username' => 'Username',
            'contact' => 'Contact',
            'is_active' => 'Is Active',
            'password' => 'Password',
        ];
    }
}
