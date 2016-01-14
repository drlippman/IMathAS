<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_external_tools".
 *
 * @property string $id
 * @property string $name
 * @property string $url
 * @property string $ltikey
 * @property string $secret
 * @property string $custom
 * @property integer $privacy
 * @property string $courseid
 * @property string $groupid
 *
 * @property ImasGroups $group
 * @property ImasCourses $course
 */
class BaseImasExternalTools extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_external_tools';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['name', 'url', 'ltikey', 'secret', 'custom', 'privacy', 'courseid', 'groupid'], 'required'],
            [['privacy', 'courseid', 'groupid'], 'integer'],
            [['name', 'url', 'ltikey', 'secret', 'custom'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'url' => 'Url',
            'ltikey' => 'Ltikey',
            'secret' => 'Secret',
            'custom' => 'Custom',
            'privacy' => 'Privacy',
            'courseid' => 'Courseid',
            'groupid' => 'Groupid',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(BaseImasGroups::className(), ['id' => 'groupid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }
}
