<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_log".
 *
 * @property string $id
 * @property string $time
 * @property string $log
 */
class BaseImasLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['time', 'log'], 'required'],
            [['time'], 'integer'],
            [['log'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'time' => 'Time',
            'log' => 'Log',
        ];
    }
}
