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
    public $userIdentifiedBy;
    public $header;
    public $file;
    public $gradesColumn;
    public $feedbackColumn;

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
            'userIdentifiedBy' => 'User is identified by:'

        ];
    }
    public function rules()
    {
        return [
            ['file', 'required', 'message' => 'Upload the CSV file'],
            ['file', 'safe'],
            [['file'], 'file', 'extensions' => 'csv'],
        ];
    }
}
