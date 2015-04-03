<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_teachers".
 *
 * @property string $id
 * @property string $userid
 * @property string $courseid
 */
class BaseImasTeachers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_teachers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'courseid'], 'integer']
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
            'courseid' => 'Courseid',
        ];
    }
}
