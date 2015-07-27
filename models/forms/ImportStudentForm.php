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
            [['lastName'],'required','message' => 'Last name field cannot be blank'],
            [['firstName'],'required','message' => 'First name field cannot be blank'],
            [['emailAddress'],'required','message' => 'Email field cannot be blank'],
            [['lastName'],'number','message' => 'Last name is in column  must be integer value'],
            [['firstName'],'number','message' => 'First name is in column  must be integer value'],
            [['emailAddress'],'number','message' => 'Email address is in column  must be integer value'],
            ['file', 'required', 'message' => 'Upload the CSV file'],
            ['file', 'safe'],
            [['file'], 'file', 'extensions' => 'csv'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => 'Import File',
            'headerRow' => 'File contains a header row',
            'firstName' => 'First name is in column',
            'nameFirstColumn' => 'In that column, first name is',
            'lastName' => 'Last name is in column',
            'nameLastColumn' => 'In that column, last name is',
            'emailAddress' => 'Email address is in column:Enter 0 if no email column',
            'userName' => 'Does a column contain a desired username:',
            'setPassword' => 'Set password to',
            'codeNumber' => 'Assign code number?',
            'sectionValue' => 'Assign section value?',
            'enrollStudent' => 'Enroll students in',
        ];
    }
}