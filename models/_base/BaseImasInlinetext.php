<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_inlinetext".
 *
 * @property string $id
 * @property string $courseid
 * @property string $title
 * @property string $text
 * @property string $startdate
 * @property string $enddate
 * @property string $fileorder
 * @property integer $avail
 * @property integer $oncal
 * @property string $caltag
 * @property integer $isplaylist
 * @property string $outcomes
 *
 * @property ImasCourses $course
 */
class BaseImasInlinetext extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_inlinetext';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'title', 'text', 'startdate', 'enddate', 'fileorder', 'outcomes'], 'required'],
            [['courseid', 'startdate', 'enddate', 'avail', 'oncal', 'isplaylist'], 'integer'],
            [['text', 'fileorder', 'outcomes'], 'string'],
            [['title', 'caltag'], 'string', 'max' => 254]
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
            'title' => 'Title',
            'text' => 'Text',
            'startdate' => 'Startdate',
            'enddate' => 'Enddate',
            'fileorder' => 'Fileorder',
            'avail' => 'Avail',
            'oncal' => 'Oncal',
            'caltag' => 'Caltag',
            'isplaylist' => 'Isplaylist',
            'outcomes' => 'Outcomes',
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
