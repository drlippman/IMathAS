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
            [['name', 'enrollkey'], 'required'],
            [['itemorder', 'outcomes', 'ancestors'], 'string'],
            [['name'], 'string', 'max' => 254],
            [['enrollkey'], 'string', 'max' => 100],
            [['topbar', 'theme'], 'string', 'max' => 32],
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
        return $this->hasMany(ImasAssessments::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasBadgesettings()
    {
        return $this->hasMany(ImasBadgesettings::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasBookmarks()
    {
        return $this->hasMany(ImasBookmarks::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasCalitems()
    {
        return $this->hasMany(ImasCalitems::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasContentTracks()
    {
        return $this->hasMany(ImasContentTrack::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(ImasUsers::className(), ['id' => 'ownerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasDrillassesses()
    {
        return $this->hasMany(ImasDrillassess::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasExternalTools()
    {
        return $this->hasMany(ImasExternalTools::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasFirstscores()
    {
        return $this->hasMany(ImasFirstscores::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForums()
    {
        return $this->hasMany(ImasForums::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasGbcats()
    {
        return $this->hasMany(ImasGbcats::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasGbitems()
    {
        return $this->hasMany(ImasGbitems::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasGbschemes()
    {
        return $this->hasMany(ImasGbscheme::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasInlinetexts()
    {
        return $this->hasMany(ImasInlinetext::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasItems()
    {
        return $this->hasMany(ImasItems::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasLinkedtexts()
    {
        return $this->hasMany(ImasLinkedtext::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasLoginLogs()
    {
        return $this->hasMany(ImasLoginLog::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasOutcomes()
    {
        return $this->hasMany(ImasOutcomes::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasStudents()
    {
        return $this->hasMany(ImasStudents::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasStugroupsets()
    {
        return $this->hasMany(ImasStugroupset::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasTeachers()
    {
        return $this->hasMany(ImasTeachers::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasTutors()
    {
        return $this->hasMany(ImasTutors::className(), ['courseid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasWikis()
    {
        return $this->hasMany(ImasWikis::className(), ['courseid' => 'id']);
    }
}
