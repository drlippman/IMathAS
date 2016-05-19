<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_forums".
 *
 * @property string $id
 * @property string $name
 * @property string $description
 * @property string $courseid
 * @property string $startdate
 * @property string $enddate
 * @property integer $settings
 * @property integer $sortby
 * @property integer $defdisplay
 * @property string $replyby
 * @property string $postby
 * @property string $grpaid
 * @property string $groupsetid
 * @property integer $points
 * @property integer $cntingb
 * @property string $gbcategory
 * @property integer $tutoredit
 * @property string $rubric
 * @property integer $avail
 * @property string $caltag
 * @property integer $forumtype
 * @property string $taglist
 * @property string $outcomes
 *
 * @property ImasForumPosts[] $imasForumPosts
 * @property ImasForumSubscriptions[] $imasForumSubscriptions
 * @property ImasForumThreads[] $imasForumThreads
 * @property ImasCourses $course
 * @property ImasUsers $replyby0
 * @property ImasUsers $postby0
 */
class BaseImasForums extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_forums';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description', 'taglist', 'outcomes'], 'string'],
            [['courseid', 'startdate', 'enddate', 'settings', 'sortby', 'defdisplay', 'replyby', 'postby', 'grpaid', 'groupsetid', 'points', 'cntingb', 'gbcategory', 'tutoredit', 'rubric', 'avail', 'forumtype'], 'integer'],
            [['name', 'caltag'], 'string', 'max' => 254],
            [['description','name'],'filter','filter'=>'\yii\helpers\HtmlPurifier::process'],
            // [['name'],'filter','filter'=>'\yii\helpers\Html::encode'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'courseid' => 'Courseid',
            'startdate' => 'Startdate',
            'enddate' => 'Enddate',
            'settings' => 'Settings',
            'sortby' => 'Sortby',
            'defdisplay' => 'Defdisplay',
            'replyby' => 'Replyby',
            'postby' => 'Postby',
            'grpaid' => 'Grpaid',
            'groupsetid' => 'Groupsetid',
            'points' => 'Points',
            'cntingb' => 'Cntingb',
            'gbcategory' => 'Gbcategory',
            'tutoredit' => 'Tutoredit',
            'rubric' => 'Rubric',
            'avail' => 'Avail',
            'caltag' => 'Caltag',
            'forumtype' => 'Forumtype',
            'taglist' => 'Taglist',
            'outcomes' => 'Outcomes',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumPosts()
    {
        return $this->hasMany(BaseImasForumPosts::className(), ['forumid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumSubscriptions()
    {
        return $this->hasMany(BaseImasForumSubscriptions::className(), ['forumid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumThreads()
    {
        return $this->hasMany(BaseImasForumThreads::className(), ['forumid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplyby0()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'replyby']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPostby0()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'postby']);
    }
}
