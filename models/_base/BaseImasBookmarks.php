<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_bookmarks".
 *
 * @property string $id
 * @property string $courseid
 * @property string $userid
 * @property string $name
 * @property string $value
 */
class BaseImasBookmarks extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_bookmarks';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'userid', 'name', 'value'], 'required'],
            [['courseid', 'userid'], 'integer'],
            [['value'], 'string'],
            [['name'], 'string', 'max' => 128]
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
            'userid' => 'Userid',
            'name' => 'Name',
            'value' => 'Value',
        ];
    }
}
