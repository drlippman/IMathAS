<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_users".
 *
 * @property string $id
 * @property string $SID
 * @property string $password
 * @property integer $rights
 * @property string $FirstName
 * @property string $LastName
 * @property string $email
 * @property string $lastaccess
 * @property string $groupid
 * @property integer $msgnotify
 * @property integer $qrightsdef
 * @property string $deflib
 * @property integer $usedeflib
 * @property string $homelayout
 * @property integer $hasuserimg
 * @property string $remoteaccess
 * @property integer $listperpage
 * @property string $hideonpostswidget
 *
 * @property ImasAssessmentSessions[] $imasAssessmentSessions
 * @property ImasBadgerecords[] $imasBadgerecords
 * @property ImasBookmarks[] $imasBookmarks
 * @property ImasContentTrack[] $imasContentTracks
 * @property ImasCourses[] $imasCourses
 * @property ImasDiags[] $imasDiags
 * @property ImasExceptions[] $imasExceptions
 * @property ImasForumLikes[] $imasForumLikes
 * @property ImasForumPosts[] $imasForumPosts
 * @property ImasForumSubscriptions[] $imasForumSubscriptions
 * @property ImasForumViews[] $imasForumViews
 * @property ImasForums[] $imasForums
 * @property ImasGrades[] $imasGrades
 * @property ImasLibraries[] $imasLibraries
 * @property ImasLoginLog[] $imasLoginLogs
 * @property ImasMsgs[] $imasMsgs
 * @property ImasQuestionset[] $imasQuestionsets
 * @property ImasRubrics[] $imasRubrics
 * @property ImasSessions[] $imasSessions
 * @property ImasStudents[] $imasStudents
 * @property ImasStugroupmembers[] $imasStugroupmembers
 * @property ImasTeachers[] $imasTeachers
 * @property ImasTutors[] $imasTutors
 * @property ImasWikiRevisions[] $imasWikiRevisions
 * @property ImasWikiViews[] $imasWikiViews
 * @property McMsgs[] $mcMsgs
 */
class BaseImasUsers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
       //     [['SID', 'password', 'FirstName', 'LastName', 'email', 'hideonpostswidget'], 'required'],
            [['rights', 'lastaccess', 'groupid', 'msgnotify', 'qrightsdef', 'deflib', 'usedeflib', 'hasuserimg', 'listperpage'], 'integer'],
            [['hideonpostswidget'], 'string'],
            [['SID'], 'string', 'max' => 50],
            [['password'], 'string', 'max' => 254],
            [['FirstName', 'LastName'], 'string', 'max' => 254],
            [['email'], 'string', 'max' => 100],
            [['homelayout'], 'string', 'max' => 32],
            [['remoteaccess'], 'string', 'max' => 10],
            [['SID'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'SID' => 'Sid',
            'password' => 'Password',
            'rights' => 'Rights',
            'FirstName' => 'First Name',
            'LastName' => 'Last Name',
            'email' => 'Email',
            'lastaccess' => 'Lastaccess',
            'groupid' => 'Groupid',
            'msgnotify' => 'Msgnotify',
            'qrightsdef' => 'Qrightsdef',
            'deflib' => 'Deflib',
            'usedeflib' => 'Usedeflib',
            'homelayout' => 'Homelayout',
            'hasuserimg' => 'Hasuserimg',
            'remoteaccess' => 'Remoteaccess',
            'listperpage' => 'Listperpage',
            'hideonpostswidget' => 'Hideonpostswidget',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasAssessmentSessions()
    {
        return $this->hasMany(BaseImasAssessmentSessions::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasBadgerecords()
    {
        return $this->hasMany(BaseImasBadgerecords::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasBookmarks()
    {
        return $this->hasMany(BaseImasBookmarks::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasContentTracks()
    {
        return $this->hasMany(BaseImasContentTrack::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasCourses()
    {
        return $this->hasMany(BaseImasCourses::className(), ['ownerid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasDiags()
    {
        return $this->hasMany(BaseImasDiags::className(), ['ownerid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasExceptions()
    {
        return $this->hasMany(BaseImasExceptions::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumLikes()
    {
        return $this->hasMany(BaseImasForumLikes::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumPosts()
    {
        return $this->hasMany(BaseImasForumPosts::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumSubscriptions()
    {
        return $this->hasMany(BaseImasForumSubscriptions::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumViews()
    {
        return $this->hasMany(BaseImasForumViews::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForums()
    {
        return $this->hasMany(BaseImasForums::className(), ['replyby' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasGrades()
    {
        return $this->hasMany(BaseImasGrades::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasLibraries()
    {
        return $this->hasMany(BaseImasLibraries::className(), ['ownerid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasLoginLogs()
    {
        return $this->hasMany(BaseImasLoginLog::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasMsg()
    {
        return $this->hasMany(BaseImasMsgs::className(), ['msgto' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasQuestionsets()
    {
        return $this->hasMany(BaseImasQuestionset::className(), ['ownerid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasRubrics()
    {
        return $this->hasMany(BaseImasRubrics::className(), ['ownerid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasSessions()
    {
        return $this->hasMany(BaseImasSessions::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasStudents()
    {
        return $this->hasMany(BaseImasStudents::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasStugroupmembers()
    {
        return $this->hasMany(BaseImasStugroupmembers::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasTeachers()
    {
        return $this->hasMany(BaseImasTeachers::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasTutors()
    {
        return $this->hasMany(BaseImasTutors::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasWikiRevisions()
    {
        return $this->hasMany(BaseImasWikiRevisions::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasWikiViews()
    {
        return $this->hasMany(BaseImasWikiViews::className(), ['userid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMcMsgs()
    {
        return $this->hasMany(BaseMcMsgs::className(), ['userid' => 'id']);
    }
}
