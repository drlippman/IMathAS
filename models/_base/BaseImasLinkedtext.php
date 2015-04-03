<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_linkedtext".
 *
 * @property string $id
 * @property string $courseid
 * @property string $title
 * @property string $summary
 * @property string $text
 * @property string $startdate
 * @property string $enddate
 * @property integer $avail
 * @property integer $oncal
 * @property integer $target
 * @property string $caltag
 * @property integer $points
 * @property string $outcomes
 */
class BaseImasLinkedtext extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_linkedtext';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'title', 'summary', 'text', 'startdate', 'enddate', 'outcomes'], 'required'],
            [['courseid', 'startdate', 'enddate', 'avail', 'oncal', 'target', 'points'], 'integer'],
            [['summary', 'text', 'outcomes'], 'string'],
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
            'summary' => 'Summary',
            'text' => 'Text',
            'startdate' => 'Startdate',
            'enddate' => 'Enddate',
            'avail' => 'Avail',
            'oncal' => 'Oncal',
            'target' => 'Target',
            'caltag' => 'Caltag',
            'points' => 'Points',
            'outcomes' => 'Outcomes',
        ];
    }
}
