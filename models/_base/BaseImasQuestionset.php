<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_questionset".
 *
 * @property string $id
 * @property string $uniqueid
 * @property string $adddate
 * @property string $lastmoddate
 * @property string $ownerid
 * @property string $author
 * @property integer $userights
 * @property integer $license
 * @property string $description
 * @property string $qtype
 * @property string $control
 * @property string $qcontrol
 * @property string $qtext
 * @property string $answer
 * @property string $solution
 * @property string $extref
 * @property integer $hasimg
 * @property integer $deleted
 * @property string $avgtime
 * @property string $ancestors
 * @property string $ancestorauthors
 * @property string $otherattribution
 * @property string $importuid
 * @property string $replaceby
 * @property integer $broken
 * @property integer $solutionopts
 *
 * @property ImasFirstscores[] $imasFirstscores
 * @property ImasLibraryItems[] $imasLibraryItems
 * @property ImasQimages[] $imasQimages
 * @property ImasQuestions[] $imasQuestions
 * @property ImasUsers $owner
 */
class BaseImasQuestionset extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_questionset';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uniqueid', 'adddate', 'lastmoddate', 'ownerid', 'userights', 'license', 'hasimg', 'deleted', 'replaceby', 'broken', 'solutionopts'], 'integer'],
//            [['author', 'control', 'qcontrol', 'qtext', 'answer', 'solution', 'extref', 'ancestors', 'ancestorauthors', 'otherattribution'], 'required'],
            [['author', 'control', 'qcontrol', 'qtext', 'answer', 'solution', 'extref', 'ancestors', 'ancestorauthors', 'otherattribution'], 'string'],
            [['description', 'avgtime', 'importuid'], 'string', 'max' => 254],
            [['qtype'], 'string', 'max' => 20],
            [['description', 'control','qcontrol','qtext','answer','solution'],'filter','filter'=>'\yii\helpers\HtmlPurifier::process'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uniqueid' => 'Uniqueid',
            'adddate' => 'Adddate',
            'lastmoddate' => 'Lastmoddate',
            'ownerid' => 'Ownerid',
            'author' => 'Author',
            'userights' => 'Userights',
            'license' => 'License',
            'description' => 'Description',
            'qtype' => 'Qtype',
            'control' => 'Control',
            'qcontrol' => 'Qcontrol',
            'qtext' => 'Qtext',
            'answer' => 'Answer',
            'solution' => 'Solution',
            'extref' => 'Extref',
            'hasimg' => 'Hasimg',
            'deleted' => 'Deleted',
            'avgtime' => 'Avgtime',
            'ancestors' => 'Ancestors',
            'ancestorauthors' => 'Ancestorauthors',
            'otherattribution' => 'Otherattribution',
            'importuid' => 'Importuid',
            'replaceby' => 'Replaceby',
            'broken' => 'Broken',
            'solutionopts' => 'Solutionopts',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasFirstscores()
    {
        return $this->hasMany(BaseImasFirstscores::className(), ['qsetid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasLibraryItems()
    {
        return $this->hasMany(BaseImasLibraryItems::className(), ['qsetid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasQimages()
    {
        return $this->hasMany(BaseImasQimages::className(), ['qsetid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasQuestions()
    {
        return $this->hasMany(BaseImasQuestions::className(), ['questionsetid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'ownerid']);
    }
}
