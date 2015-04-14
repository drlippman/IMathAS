<?php

namespace app\models;

use Yii;
use yii\base\Model;
class DiagnosticForm extends Model
{
    public $diagnosticName;
    public $termDesignator;
    public $linkedWithCourse;
    public $available;
    public $includeInPublicListing;
    public $reEntry;
    public $uniqueIdPrompt;
    public $firstLevelSelector;
    public $idEntryFormat;
    public $idEntryNumber;
    public $enterIp;
    public $enterPasswordOther;
    public $enterPasswordSuper;
    public $selectorName;
    public $selectorOnSubmit;
    public $selectorOption;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

            /* ['rePassword', 'compare', 'compareAttribute'=>'password'],
             [['FirstName', 'LastName'], 'string'],
             ['email','email'],
             ['NotifyMeByEmailWhenIReceiveANewMessage', 'boolean'],
             ['uploadPicture','file'],*/
        ];

    }

    public function attributeLabels()
    {
        return [
    'diagnosticName'=>'Diagnostic Name',
    'termDesignator'=>'Term designator',
     'linkedWithCourse'=>'Linked with course',
    'available'=>'Available? (Can be taken)?',
    'includeInPublicListing'=>'Include in public listing?',
    'reEntry'=>'Include in public listing?',
    'uniqueIdPrompt'=>'Unique ID prompt',
    'firstLevelSelector'=>'Attach first level selector to ID',
    'idEntryFormat'=>'ID entry format',
    'idEntryNumber'=>'ID entry number of characters?',
    'enterIp'=>'Enter IP address',
    'enterPasswordOther'=>'Enter Password',
    'enterPasswordSuper'=>'Enter Password',
    'selectorName'=>'Selector name',
    'selectorOnSubmit'=>'Alphabetize selectors on submit? ',
    'selectorOption'=>'Enter new selector option',

        ];
    }
}
