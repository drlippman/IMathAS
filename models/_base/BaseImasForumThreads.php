<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_forum_threads".
 *
 * @property string $id
 * @property string $forumid
 * @property string $stugroupid
 * @property string $lastposttime
 * @property string $lastpostuser
 * @property string $views
 *
 * @property ImasForumLikes[] $imasForumLikes
 * @property ImasForumPosts[] $imasForumPosts
 * @property ImasStugroups $stugroup
 * @property ImasForums $forum
 * @property ImasForumViews[] $imasForumViews
 */
class BaseImasForumThreads extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_forum_threads';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['id', 'forumid', 'lastposttime', 'lastpostuser', 'views'], 'required'],
            [['forumid', 'stugroupid', 'lastposttime', 'lastpostuser', 'views'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'forumid' => 'Forumid',
            'stugroupid' => 'Stugroupid',
            'lastposttime' => 'Lastposttime',
            'lastpostuser' => 'Lastpostuser',
            'views' => 'Views',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumLikes()
    {
        return $this->hasMany(BaseImasForumLikes::className(), ['threadid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumPosts()
    {
        return $this->hasMany(BaseImasForumPosts::className(), ['threadid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStugroup()
    {
        return $this->hasOne(BaseImasStugroups::className(), ['id' => 'stugroupid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForum()
    {
        return $this->hasOne(BaseImasForums::className(), ['id' => 'forumid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumViews()
    {
        return $this->hasMany(BaseImasForumViews::className(), ['threadid' => 'id']);
    }
}
