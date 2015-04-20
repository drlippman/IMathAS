<?php

namespace app\models;
use yii\base\Model;
class AdminDiagnosticForm extends Model
{
   public $DiagnosticName;
   public $TermDesignator;
   public $LinkedWithCourse;
   public $Available;
   public $IncludeInPublicListing;
   public $AllowReentry;
   public $UniqueIDPrompt;
   public $AttachFirstLevelSelectorToID;
   public $IDEntryFormat;
   public $IDEntryNumberOfCharacters;
   public $EnterIPAddress;
   public $EnterPassword;
   public $SuperPasswords;
   public $SelectorName;
   public $AlphabetizeSelectorsOnSubmit;
   public $EnterNewSelectorOption;

    public function rules()
    {
        return
            [
                [['DiagnosticName'],'required'],
            ];

    }

    public function attributeLabels()
    {
        return
            [
                'DiagnosticName'=>'Diagnostic Name',
                'TermDesignator' => 'Term Designator',
                'LinkedWithCourse'=>'Linked With Course',
                'Available'=> 'Available',
                'IncludeInPublicListing' => 'Include In Public Listing',
                'AllowReentry'=> 'Allow Reentry',
                'UniqueIDPrompt'=>'Unique ID Prompt',
                'AttachFirstLevelSelectorToID'=> 'Attach First Level Selector To ID',
                'IDEntryFormat'=> 'ID Entry Format',
                'IDEntryNumberOfCharacters'=>'ID Entry Number Of Characters',
                'EnterIPAddress'=>'Enter IP Address',
                'EnterPassword'=>'Enter Password',
                'SuperPasswords'=>'Enter Passwords',
                'SelectorName'=>'Selector Name',
                'AlphabetizeSelectorsOnSubmit'=>'Alphabetize Selectors On Submit',
                'EnterNewSelectorOption'=>'Enter New Selector Option',
            ];
    }

}

