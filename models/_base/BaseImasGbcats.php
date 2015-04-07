<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_gbcats".
 *
 * @property string $id
 * @property string $name
 * @property string $courseid
 * @property integer $calctype
 * @property integer $scale
 * @property integer $scaletype
 * @property string $chop
 * @property integer $dropn
 * @property integer $weight
 * @property integer $hidden
 *
 * @property ImasCourses $course
 */
class BaseImasGbcats extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_gbcats';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'courseid'], 'required'],
            [['courseid', 'calctype', 'scale', 'scaletype', 'dropn', 'weight', 'hidden'], 'integer'],
            [['chop'], 'number'],
            [['name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'courseid' => 'Courseid',
            'calctype' => 'Calctype',
            'scale' => 'Scale',
            'scaletype' => 'Scaletype',
            'chop' => 'Chop',
            'dropn' => 'Dropn',
            'weight' => 'Weight',
            'hidden' => 'Hidden',
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
