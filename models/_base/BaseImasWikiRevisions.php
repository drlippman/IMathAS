<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_wiki_revisions".
 *
 * @property string $id
 * @property string $wikiid
 * @property string $stugroupid
 * @property string $userid
 * @property string $time
 * @property string $revision
 *
 * @property ImasUsers $user
 * @property ImasStugroups $stugroup
 */
class BaseImasWikiRevisions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_wiki_revisions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wikiid', 'userid', 'time', 'revision'], 'required'],
            [['wikiid', 'stugroupid', 'userid', 'time'], 'integer'],
            [['revision'], 'string'],
            [['userid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'wikiid' => 'Wikiid',
            'stugroupid' => 'Stugroupid',
            'userid' => 'Userid',
            'time' => 'Time',
            'revision' => 'Revision',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'userid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStugroup()
    {
        return $this->hasOne(BaseImasStugroups::className(), ['id' => 'stugroupid']);
    }
}
