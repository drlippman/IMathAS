<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_login_log".
 *
 * @property string $id
 * @property string $userid
 * @property string $courseid
 * @property string $logintime
 * @property string $lastaction
 *
 * @property ImasCourses $course
 * @property ImasUsers $user
 */
class BaseImasLoginLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_login_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'courseid', 'logintime', 'lastaction'], 'required'],
            [['userid', 'courseid', 'logintime', 'lastaction'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'courseid' => 'Courseid',
            'logintime' => 'Logintime',
            'lastaction' => 'Lastaction',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(ImasCourses::className(), ['id' => 'courseid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(ImasUsers::className(), ['id' => 'userid']);
    }
}
