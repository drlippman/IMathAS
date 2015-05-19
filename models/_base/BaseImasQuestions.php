<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_questions".
 *
 * @property string $id
 * @property string $assessmentid
 * @property string $questionsetid
 * @property integer $points
 * @property integer $attempts
 * @property string $penalty
 * @property string $category
 * @property string $rubric
 * @property integer $regen
 * @property integer $showans
 * @property integer $showhints
 * @property integer $extracredit
 * @property string $withdrawn
 *
 * @property ImasQuestionset $questionset
 * @property ImasAssessments $assessment
 */
class BaseImasQuestions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_questions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['assessmentid', 'questionsetid', 'points', 'attempts', 'rubric', 'regen', 'showans', 'showhints', 'extracredit'], 'integer'],
            [['penalty'], 'string', 'max' => 6],
            [['category'], 'string', 'max' => 254],
            [['withdrawn'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'assessmentid' => 'Assessmentid',
            'questionsetid' => 'Questionsetid',
            'points' => 'Points',
            'attempts' => 'Attempts',
            'penalty' => 'Penalty',
            'category' => 'Category',
            'rubric' => 'Rubric',
            'regen' => 'Regen',
            'showans' => 'Showans',
            'showhints' => 'Showhints',
            'extracredit' => 'Extracredit',
            'withdrawn' => 'Withdrawn',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionset()
    {
        return $this->hasOne(BaseImasQuestionset::className(), ['id' => 'questionsetid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssessment()
    {
        return $this->hasOne(BaseImasAssessments::className(), ['id' => 'assessmentid']);
    }
}
