<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_sessions".
 *
 * @property string $sessionid
 * @property string $userid
 * @property string $time
 * @property integer $tzoffset
 * @property string $tzname
 * @property string $sessiondata
 *
 * @property ImasUsers $user
 */
class BaseImasSessions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_sessions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sessionid', 'userid', 'time', 'sessiondata'], 'required'],
            [['userid', 'time', 'tzoffset'], 'integer'],
            [['sessiondata'], 'string'],
            [['sessionid'], 'string', 'max' => 32],
            [['tzname'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sessionid' => 'Sessionid',
            'userid' => 'Userid',
            'time' => 'Time',
            'tzoffset' => 'Tzoffset',
            'tzname' => 'Tzname',
            'sessiondata' => 'Sessiondata',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'userid']);
    }
}
