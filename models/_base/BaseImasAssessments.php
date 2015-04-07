<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_assessments".
 *
 * @property string $id
 * @property string $courseid
 * @property string $name
 * @property string $summary
 * @property string $intro
 * @property string $startdate
 * @property string $enddate
 * @property string $reviewdate
 * @property integer $timelimit
 * @property string $displaymethod
 * @property integer $defpoints
 * @property integer $defattempts
 * @property string $deffeedback
 * @property string $defpenalty
 * @property string $deffeedbacktext
 * @property string $itemorder
 * @property integer $shuffle
 * @property string $gbcategory
 * @property string $password
 * @property integer $cntingb
 * @property integer $minscore
 * @property integer $showcat
 * @property integer $showhints
 * @property integer $showtips
 * @property integer $isgroup
 * @property string $groupsetid
 * @property string $reqscoreaid
 * @property integer $reqscore
 * @property integer $noprint
 * @property integer $avail
 * @property integer $groupmax
 * @property integer $allowlate
 * @property integer $eqnhelper
 * @property integer $exceptionpenalty
 * @property string $posttoforum
 * @property integer $msgtoinstr
 * @property integer $istutorial
 * @property string $defoutcome
 * @property string $ltisecret
 * @property string $endmsg
 * @property string $viddata
 * @property string $caltag
 * @property string $calrtag
 * @property integer $tutoredit
 * @property string $ancestors
 *
 * @property ImasAssessmentSessions[] $imasAssessmentSessions
 * @property ImasCourses $course
 * @property ImasExceptions[] $imasExceptions
 * @property ImasQuestions[] $imasQuestions
 */
class BaseImasAssessments extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_assessments';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'startdate', 'enddate', 'reviewdate', 'timelimit', 'defpoints', 'defattempts', 'shuffle', 'gbcategory', 'cntingb', 'minscore', 'showcat', 'showhints', 'showtips', 'isgroup', 'groupsetid', 'reqscoreaid', 'reqscore', 'noprint', 'avail', 'groupmax', 'allowlate', 'eqnhelper', 'exceptionpenalty', 'posttoforum', 'msgtoinstr', 'istutorial', 'defoutcome', 'tutoredit'], 'integer'],
            [['name', 'summary', 'intro', 'displaymethod', 'deffeedback', 'itemorder', 'password', 'ltisecret', 'endmsg', 'viddata', 'ancestors'], 'required'],
            [['summary', 'intro', 'itemorder', 'endmsg', 'viddata', 'ancestors'], 'string'],
            [['name', 'caltag', 'calrtag'], 'string', 'max' => 254],
            [['displaymethod', 'deffeedback'], 'string', 'max' => 20],
            [['defpenalty'], 'string', 'max' => 6],
            [['deffeedbacktext'], 'string', 'max' => 512],
            [['password'], 'string', 'max' => 15],
            [['ltisecret'], 'string', 'max' => 10]
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
            'summary' => 'Summary',
            'intro' => 'Intro',
            'startdate' => 'Startdate',
            'enddate' => 'Enddate',
            'reviewdate' => 'Reviewdate',
            'timelimit' => 'Timelimit',
            'displaymethod' => 'Displaymethod',
            'defpoints' => 'Defpoints',
            'defattempts' => 'Defattempts',
            'deffeedback' => 'Deffeedback',
            'defpenalty' => 'Defpenalty',
            'deffeedbacktext' => 'Deffeedbacktext',
            'itemorder' => 'Itemorder',
            'shuffle' => 'Shuffle',
            'gbcategory' => 'Gbcategory',
            'password' => 'Password',
            'cntingb' => 'Cntingb',
            'minscore' => 'Minscore',
            'showcat' => 'Showcat',
            'showhints' => 'Showhints',
            'showtips' => 'Showtips',
            'isgroup' => 'Isgroup',
            'groupsetid' => 'Groupsetid',
            'reqscoreaid' => 'Reqscoreaid',
            'reqscore' => 'Reqscore',
            'noprint' => 'Noprint',
            'avail' => 'Avail',
            'groupmax' => 'Groupmax',
            'allowlate' => 'Allowlate',
            'eqnhelper' => 'Eqnhelper',
            'exceptionpenalty' => 'Exceptionpenalty',
            'posttoforum' => 'Posttoforum',
            'msgtoinstr' => 'Msgtoinstr',
            'istutorial' => 'Istutorial',
            'defoutcome' => 'Defoutcome',
            'ltisecret' => 'Ltisecret',
            'endmsg' => 'Endmsg',
            'viddata' => 'Viddata',
            'caltag' => 'Caltag',
            'calrtag' => 'Calrtag',
            'tutoredit' => 'Tutoredit',
            'ancestors' => 'Ancestors',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasAssessmentSessions()
    {
        return $this->hasMany(ImasAssessmentSessions::className(), ['assessmentid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(ImasCourses::className(), ['id' => 'courseid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasExceptions()
    {
        return $this->hasMany(ImasExceptions::className(), ['assessmentid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasQuestions()
    {
        return $this->hasMany(ImasQuestions::className(), ['assessmentid' => 'id']);
    }
}
