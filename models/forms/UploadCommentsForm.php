<?php
namespace app\models\forms;
use app\components\AppUtility;
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
            [['lastName'],'required','message' => AppUtility::t('Last name field cannot be blank', false)],
            [['commentsColumn'],'required', 'message' => AppUtility::t('Enter Comment column.', false)],
            [['commentsColumn'],'number', 'message' => AppUtility::t('comments are in columns must be integer value.', false)],
            ['file', 'required', 'message' => AppUtility::t('Upload CSV file', false)],
            ['file', 'safe'],
            [['file'], 'file', 'extensions' => 'csv'],
        ];
    }
    public function attributeLabels()
    {
        return [
            'file' => AppUtility::t('Grade file (CSV)', false),
            'fileHeaderRow' => AppUtility::t('File has header row?', false),
            'commentsColumn' => AppUtility::t('Comments are in columns', false),
            'userIdentity' => AppUtility::t('User is identified by', false),
        ];
    }
}