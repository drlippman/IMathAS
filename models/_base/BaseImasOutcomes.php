<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_outcomes".
 *
 * @property string $id
 * @property string $courseid
 * @property string $name
 * @property string $ancestors
 */
class BaseImasOutcomes extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_outcomes';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'name', 'ancestors'], 'required'],
            [['courseid'], 'integer'],
            [['ancestors'], 'string'],
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
            'courseid' => 'Courseid',
            'name' => 'Name',
            'ancestors' => 'Ancestors',
        ];
    }
}
