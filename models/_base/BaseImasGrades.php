<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_grades".
 *
 * @property string $id
 * @property string $gradetype
 * @property string $gradetypeid
 * @property string $refid
 * @property string $userid
 * @property string $score
 * @property string $feedback
 *
 * @property ImasUsers $user
 */
class BaseImasGrades extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_grades';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['gradetypeid', 'userid', 'feedback'], 'required'],
            [['gradetypeid', 'refid', 'userid'], 'integer'],
            [['score'], 'number'],
            [['feedback'], 'string'],
            [['gradetype'], 'string', 'max' => 15]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'gradetype' => 'Gradetype',
            'gradetypeid' => 'Gradetypeid',
            'refid' => 'Refid',
            'userid' => 'Userid',
            'score' => 'Score',
            'feedback' => 'Feedback',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(ImasUsers::className(), ['id' => 'userid']);
    }
}
