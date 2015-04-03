<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_tutors".
 *
 * @property string $id
 * @property string $userid
 * @property string $courseid
 * @property string $section
 */
class BaseImasTutors extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_tutors';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'courseid', 'section'], 'required'],
            [['userid', 'courseid'], 'integer'],
            [['section'], 'string', 'max' => 40]
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
            'section' => 'Section',
        ];
    }
}
