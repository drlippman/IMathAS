<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_diag_onetime".
 *
 * @property string $id
 * @property string $diag
 * @property string $time
 * @property string $code
 * @property string $goodfor
 */
class BaseImasDiagOnetime extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_diag_onetime';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['diag', 'time', 'code'], 'required'],
            [['diag', 'time', 'goodfor'], 'integer'],
            [['code'], 'string', 'max' => 9]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'diag' => 'Diag',
            'time' => 'Time',
            'code' => 'Code',
            'goodfor' => 'Goodfor',
        ];
    }
}
