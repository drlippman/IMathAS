<?php
namespace app\models\forms;

use Yii;
use yii\base\Model;
class AddGradesForm extends Model
{
    public $Name;
    public $Points;
    public $ShowGrade;
    public $GradeBookCategory;
    public $Count;
    public $TutorAccess;
    public $ScoringRubric;
    public $UploadGrades;
    public $AssessmentSnapshot;
    public $AddReplaceGrades;
    public $AssessmentToSnapshot;
    public $Gradetype;
    public $header;
    public $file;
    public $gradesColumn;
    public $feedbackColumn;
    public $fileHeaderRow;
    public $commentsColumn;
    public $userIdentity;

    public function attributeLabels()
    {
        return [
            'ShowGrade' => 'Show grade to students after',
            'UploadGrades' => 'Upload grades?',
            'AssessmentSnapshot'=>'Assessment snapshot?',
            'ScoringRubric'=>' Use scoring rubric',
            'gradesColumn' => 'Grade is in column:',
            'feedbackColumn' => 'Feedback is in column (0 if none):',
            'header' => 'File has header row?',
            'file' => 'Grade file (CSV):',
            'fileHeaderRow' => 'File has header row?',
            'commentsColumn' => 'Comments are in columns',
            'userIdentity' => 'User is identified by',

        ];
    }
    public function rules()
    {
        return [
            [['lastName'],'required','message' => 'Last name field cannot be blank'],
            ['file', 'required', 'message' => 'Upload the CSV file'],
            ['file', 'safe'],
            [['file'], 'file', 'extensions' => 'csv'],
        ];
    }
}
