<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "mc_msgs".
 *
 * @property string $id
 * @property string $userid
 * @property string $msg
 * @property string $time
 */
class BaseMcMsgs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mc_msgs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'msg', 'time'], 'required'],
            [['userid', 'time'], 'integer'],
            [['msg'], 'string']
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
            'msg' => 'Msg',
            'time' => 'Time',
        ];
    }
}
