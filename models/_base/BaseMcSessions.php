<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "mc_sessions".
 *
 * @property integer $userid
 * @property string $sessionid
 * @property string $name
 * @property integer $room
 * @property string $lastping
 * @property integer $mathdisp
 * @property integer $graphdisp
 */
class BaseMcSessions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mc_sessions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sessionid', 'name', 'room', 'lastping', 'mathdisp', 'graphdisp'], 'required'],
            [['room', 'lastping', 'mathdisp', 'graphdisp'], 'integer'],
            [['sessionid'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'userid' => 'Userid',
            'sessionid' => 'Sessionid',
            'name' => 'Name',
            'room' => 'Room',
            'lastping' => 'Lastping',
            'mathdisp' => 'Mathdisp',
            'graphdisp' => 'Graphdisp',
        ];
    }
}
