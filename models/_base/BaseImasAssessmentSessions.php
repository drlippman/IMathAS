<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_assessment_sessions".
 *
 * @property string $id
 * @property string $userid
 * @property string $assessmentid
 * @property string $agroupid
 * @property string $lti_sourcedid
 * @property string $questions
 * @property string $seeds
 * @property string $scores
 * @property string $attempts
 * @property string $lastanswers
 * @property string $reattempting
 * @property integer $starttime
 * @property integer $endtime
 * @property string $timeontask
 * @property string $bestseeds
 * @property string $bestscores
 * @property string $bestattempts
 * @property string $bestlastanswers
 * @property string $reviewseeds
 * @property string $reviewscores
 * @property string $reviewattempts
 * @property string $reviewlastanswers
 * @property string $reviewreattempting
 * @property string $feedback
 */
class BaseImasAssessmentSessions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_assessment_sessions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'assessmentid', 'lti_sourcedid', 'questions', 'seeds', 'scores', 'attempts', 'lastanswers', 'reattempting', 'starttime', 'endtime', 'timeontask', 'bestseeds', 'bestscores', 'bestattempts', 'bestlastanswers', 'reviewseeds', 'reviewscores', 'reviewattempts', 'reviewlastanswers', 'reviewreattempting', 'feedback'], 'required'],
            [['userid', 'assessmentid', 'agroupid', 'starttime', 'endtime'], 'integer'],
            [['lti_sourcedid', 'questions', 'seeds', 'scores', 'attempts', 'lastanswers', 'timeontask', 'bestseeds', 'bestscores', 'bestattempts', 'bestlastanswers', 'reviewseeds', 'reviewscores', 'reviewattempts', 'reviewlastanswers', 'feedback'], 'string'],
            [['reattempting', 'reviewreattempting'], 'string', 'max' => 255],
            [['userid', 'assessmentid'], 'unique', 'targetAttribute' => ['userid', 'assessmentid'], 'message' => 'The combination of Userid and Assessmentid has already been taken.']
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
            'assessmentid' => 'Assessmentid',
            'agroupid' => 'Agroupid',
            'lti_sourcedid' => 'Lti Sourcedid',
            'questions' => 'Questions',
            'seeds' => 'Seeds',
            'scores' => 'Scores',
            'attempts' => 'Attempts',
            'lastanswers' => 'Lastanswers',
            'reattempting' => 'Reattempting',
            'starttime' => 'Starttime',
            'endtime' => 'Endtime',
            'timeontask' => 'Timeontask',
            'bestseeds' => 'Bestseeds',
            'bestscores' => 'Bestscores',
            'bestattempts' => 'Bestattempts',
            'bestlastanswers' => 'Bestlastanswers',
            'reviewseeds' => 'Reviewseeds',
            'reviewscores' => 'Reviewscores',
            'reviewattempts' => 'Reviewattempts',
            'reviewlastanswers' => 'Reviewlastanswers',
            'reviewreattempting' => 'Reviewreattempting',
            'feedback' => 'Feedback',
        ];
    }
}
