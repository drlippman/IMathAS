<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_dbschema".
 *
 * @property string $id
 * @property string $ver
 */
class BaseImasDbschema extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_dbschema';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'ver'], 'required'],
            [['id', 'ver'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ver' => 'Ver',
        ];
    }
}
