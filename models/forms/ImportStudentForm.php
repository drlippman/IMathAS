<?php

namespace app\models\forms;

use app\components\AppUtility;
use app\components\AppConstant;
use Yii;
use yii\base\Model;
use app\controllers\roster\RosterController;

class ImportStudentForm extends Model
{
    public $file;
    public $headerRow;
    public $firstName;
    public $nameFirstColumn;
    public $lastName;
    public $nameLastColumn;
    public $emailAddress;
    public $userName;
    public $setPassword;
    public $codeNumber;
    public $sectionValue;
    public $enrollStudent;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['lastName'],'required','message' => AppUtility::t('Last name field cannot be blank', false)],
            [['firstName'],'required','message' => AppUtility::t('First name field cannot be blank', false)],
            [['emailAddress'],'required','message' => AppUtility::t('Email field cannot be blank', false)],
            [['lastName'],'number','message' => AppUtility::t('Last name is in column  must be integer value', false)],
            [['firstName'],'number','message' => AppUtility::t('First name is in column  must be integer value', false)],
            [['emailAddress'],'number','message' => AppUtility::t('Email address is in column  must be integer value', false)],
            ['file', 'required', 'message' => AppUtility::t('Upload the CSV file', false)],
            ['file', 'safe'],
            [['file'], 'file', 'extensions' => 'csv'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => AppUtility::t('Import File', false),
            'headerRow' => AppUtility::t('File contains a header row', false),
            'firstName' => AppUtility::t('First name is in column', false),
            'nameFirstColumn' => AppUtility::t('In that column, first name is', false),
            'lastName' => AppUtility::t('Last name is in column', false),
            'nameLastColumn' => AppUtility::t('In that column, last name is', false),
            'emailAddress' => AppUtility::t('Email address is in column:Enter 0 if no email column', false),
            'userName' => AppUtility::t('Does a column contain a desired username:', false),
            'setPassword' => AppUtility::t('Set password to', false),
            'codeNumber' => AppUtility::t('Assign code number?', false),
            'sectionValue' => AppUtility::t('Assign section value?', false),
            'enrollStudent' => AppUtility::t('Enroll students in', false),
        ];
    }
}