<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_stugroupset".
 *
 * @property string $id
 * @property string $courseid
 * @property string $name
 * @property integer $delempty
 *
 * @property ImasCourses $course
 */
class BaseImasStugroupset extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_stugroupset';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'name'], 'required'],
            [['courseid', 'delempty'], 'integer'],
            [['name'], 'string', 'max' => 254]
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
            'delempty' => 'Delempty',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(ImasCourses::className(), ['id' => 'courseid']);
    }
}
