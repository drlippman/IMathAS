<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_groups".
 *
 * @property string $id
 * @property integer $grouptype
 * @property string $name
 */
class BaseImasGroups extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_groups';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['grouptype'], 'integer'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'grouptype' => 'Grouptype',
            'name' => 'Name',
        ];
    }
}
