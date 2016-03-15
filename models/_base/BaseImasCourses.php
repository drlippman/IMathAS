<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_courses".
 *
 * @property string $id
 * @property string $ownerid
 * @property string $name
 * @property string $enrollkey
 * @property string $itemorder
 * @property integer $hideicons
 * @property integer $allowunenroll
 * @property integer $copyrights
 * @property string $blockcnt
 * @property integer $msgset
 * @property integer $toolset
 * @property integer $chatset
 * @property integer $showlatepass
 * @property string $topbar
 * @property integer $cploc
 * @property integer $available
 * @property string $lockaid
 * @property string $theme
 * @property integer $latepasshrs
 * @property integer $picicons
 * @property integer $newflag
 * @property integer $istemplate
 * @property integer $deflatepass
 * @property string $deftime
 * @property string $outcomes
 * @property string $ancestors
 * @property string $ltisecret
 *
 * @property ImasAssessments[] $imasAssessments
 * @property ImasBadgesettings[] $imasBadgesettings
 * @property ImasBookmarks[] $imasBookmarks
 * @property ImasCalitems[] $imasCalitems
 * @property ImasContentTrack[] $imasContentTracks
 * @property ImasUsers $owner
 * @property ImasDrillassess[] $imasDrillassesses
 * @property ImasExternalTools[] $imasExternalTools
 * @property ImasFirstscores[] $imasFirstscores
 * @property ImasForums[] $imasForums
 * @property ImasGbcats[] $imasGbcats
 * @property ImasGbitems[] $imasGbitems
 * @property ImasGbscheme[] $imasGbschemes
 * @property ImasInlinetext[] $imasInlinetexts
 * @property ImasItems[] $imasItems
 * @property ImasLinkedtext[] $imasLinkedtexts
 * @property ImasLoginLog[] $imasLoginLogs
 * @property ImasOutcomes[] $imasOutcomes
 * @property ImasStudents[] $imasStudents
 * @property ImasStugroupset[] $imasStugroupsets
 * @property ImasTeachers[] $imasTeachers
 * @property ImasTutors[] $imasTutors
 * @property ImasWikis[] $imasWikis
 */
class BaseImasCourses extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_courses';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ownerid', 'hideicons', 'allowunenroll', 'copyrights', 'blockcnt', 'msgset', 'toolset', 'chatset', 'showlatepass', 'cploc', 'available', 'lockaid', 'latepasshrs', 'picicons', 'newflag', 'istemplate', 'deflatepass', 'deftime'], 'integer'],
            [['name'], 'required'],
            [['itemorder', 'outcomes', 'ancestors'], 'string'],
            [['name'], 'string', 'max' => 150],
            [['enrollkey'], 'string', 'max' => 50],
            [['topbar'], 'string', 'max' => 32],
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
            'ownerid' => 'Ownerid',
            'name' => 'Name',
            'enrollkey' => 'Enrollkey',
            'itemorder' => 'Itemorder',
            'hideicons' => 'Hideicons',
            'allowunenroll' => 'Allowunenroll',
            'copyrights' => 'Copyrights',
            'blockcnt' => 'Blockcnt',
            'msgset' => 'Msgset',
            'toolset' => 'Toolset',
            'chatset' => 'Chatset',
            'showlatepass' => 'Showlatepass',
            'topbar' => 'Topbar',
            'cploc' => 'Cploc',
            'available' => 'Available',
            'lockaid' => 'Lockaid',
            'theme' => 'Theme',
            'latepasshrs' => 'Latepasshrs',
            'picicons' => 'Picicons',
            'newflag' => 'Newflag',
            'istemplate' => 'Istemplate',
            'deflatepass' => 'Deflatepass',
            'deftime' => 'Deftime',
            'outcomes' => 'Outcomes',
            'ancestors' => 'Ancestors',
            'ltisecret' => 'Ltisecret',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasAssessments()
    {
        return $this->hasMany(BaseImasAssessments::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasBadgesettings()
    {
        return $this->hasMany(BaseImasBadgesettings::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasBookmarks()
    {
        return $this->hasMany(BaseImasBookmarks::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasCalitems()
    {
        return $this->hasMany(BaseImasCalitems::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasContentTracks()
    {
        return $this->hasMany(BaseImasContentTrack::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'ownerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasDrillassesses()
    {
        return $this->hasMany(BaseImasDrillassess::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasExternalTools()
    {
        return $this->hasMany(BaseImasExternalTools::className(), ['courseid' => 'id']);
    }

    public function getImasMsgs()
    {
        return $this->hasMany(BaseImasMsgs::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasFirstscores()
    {
        return $this->hasMany(BaseImasFirstscores::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForums()
    {
        return $this->hasMany(BaseImasForums::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasGbcats()
    {
        return $this->hasMany(BaseImasGbcats::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasGbitems()
    {
        return $this->hasMany(BaseImasGbitems::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasGbschemes()
    {
        return $this->hasMany(BaseImasGbscheme::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasInlinetexts()
    {
        return $this->hasMany(BaseImasInlinetext::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasItems()
    {
        return $this->hasMany(BaseImasItems::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasLinkedtexts()
    {
        return $this->hasMany(BaseImasLinkedtext::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasLoginLogs()
    {
        return $this->hasMany(BaseImasLoginLog::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasOutcomes()
    {
        return $this->hasMany(BaseImasOutcomes::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasStudents()
    {
        return $this->hasMany(BaseImasStudents::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasStugroupsets()
    {
        return $this->hasMany(BaseImasStugroupset::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasTeachers()
    {
        return $this->hasMany(BaseImasTeachers::className(), ['courseid' => 'id']);
    }


    public function getTeachersAsArray()
    {
        return $this->hasMany(BaseImasTeachers::className(), ['courseid' => 'id'])->asArray();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasTutors()
    {
        return $this->hasMany(BaseImasTutors::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasWikis()
    {
        return $this->hasMany(BaseImasWikis::className(), ['courseid' => 'id']);
    }
}
