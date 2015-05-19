<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_gbitems".
 *
 * @property string $id
 * @property string $courseid
 * @property string $name
 * @property integer $points
 * @property string $showdate
 * @property string $gbcategory
 * @property string $rubric
 * @property integer $cntingb
 * @property integer $tutoredit
 * @property string $outcomes
 *
 * @property ImasCourses $course
 */
class BaseImasGbitems extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_gbitems';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'name', 'showdate', 'gbcategory', 'outcomes'], 'required'],
            [['courseid', 'points', 'showdate', 'gbcategory', 'rubric', 'cntingb', 'tutoredit'], 'integer'],
            [['outcomes'], 'string'],
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
            'points' => 'Points',
            'showdate' => 'Showdate',
            'gbcategory' => 'Gbcategory',
            'rubric' => 'Rubric',
            'cntingb' => 'Cntingb',
            'tutoredit' => 'Tutoredit',
            'outcomes' => 'Outcomes',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }
}
