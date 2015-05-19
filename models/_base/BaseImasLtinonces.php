<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_ltinonces".
 *
 * @property string $id
 * @property string $nonce
 * @property string $time
 */
class BaseImasLtinonces extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_ltinonces';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nonce', 'time'], 'required'],
            [['nonce'], 'string'],
            [['time'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nonce' => 'Nonce',
            'time' => 'Time',
        ];
    }
}
