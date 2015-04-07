<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_calitems".
 *
 * @property string $id
 * @property string $courseid
 * @property string $date
 * @property string $title
 * @property string $tag
 *
 * @property ImasCourses $course
 */
class BaseImasCalitems extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_calitems';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'date', 'title', 'tag'], 'required'],
            [['courseid', 'date'], 'integer'],
            [['title', 'tag'], 'string', 'max' => 254]
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
            'date' => 'Date',
            'title' => 'Title',
            'tag' => 'Tag',
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
