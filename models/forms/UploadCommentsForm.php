<?php
namespace app\models\forms;
use Yii;
use yii\base\Model;

class UploadCommentsForm extends Model{

    public $file;
    public $fileHeaderRow;
    public $commentsColumn;
    public $userIdentity;

    public function rules()
    {
        return [
            [['lastName'],'required','message' => 'Last name field cannot be blank'],
            [['commentsColumn'],'required', 'message' => 'Enter Comment column.'],
            [['commentsColumn'],'number', 'message' => 'comments are in columns must be integer value.'],
            ['file', 'required', 'message' => 'Upload CSV file'],
            ['file', 'safe'],
            [['file'], 'file', 'extensions' => 'csv'],
        ];
    }
    public function attributeLabels()
    {
        return [
            'file' => 'Grade file (CSV)',
            'fileHeaderRow' => 'File has header row?',
            'commentsColumn' => 'Comments are in columns',
            'userIdentity' => 'User is identified by',
        ];
    }
}