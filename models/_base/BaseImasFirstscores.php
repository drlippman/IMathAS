<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_firstscores".
 *
 * @property string $id
 * @property string $courseid
 * @property string $qsetid
 * @property integer $score
 * @property string $scoredet
 * @property integer $timespent
 *
 * @property ImasQuestionset $qset
 * @property ImasCourses $course
 */
class BaseImasFirstscores extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_firstscores';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'qsetid', 'score', 'scoredet', 'timespent'], 'required'],
            [['courseid', 'qsetid', 'score', 'timespent'], 'integer'],
            [['scoredet'], 'string']
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
            'qsetid' => 'Qsetid',
            'score' => 'Score',
            'scoredet' => 'Scoredet',
            'timespent' => 'Timespent',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQset()
    {
        return $this->hasOne(ImasQuestionset::className(), ['id' => 'qsetid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(ImasCourses::className(), ['id' => 'courseid']);
    }
}
