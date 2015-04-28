<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_stugroups".
 *
 * @property string $id
 * @property string $groupsetid
 * @property string $name
 *
 * @property ImasForumThreads[] $imasForumThreads
 * @property ImasStugroupmembers[] $imasStugroupmembers
 * @property ImasWikiRevisions[] $imasWikiRevisions
 */
class BaseImasStugroups extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_stugroups';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['groupsetid', 'name'], 'required'],
            [['groupsetid'], 'integer'],
            [['name'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'groupsetid' => 'Groupsetid',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasForumThreads()
    {
        return $this->hasMany(BaseImasForumThreads::className(), ['stugroupid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasStugroupmembers()
    {
        return $this->hasMany(BaseImasStugroupmembers::className(), ['stugroupid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasWikiRevisions()
    {
        return $this->hasMany(BaseImasWikiRevisions::className(), ['stugroupid' => 'id']);
    }
}
