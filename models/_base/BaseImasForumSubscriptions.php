<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_forum_subscriptions".
 *
 * @property string $id
 * @property string $forumid
 * @property string $userid
 *
 * @property ImasUsers $user
 * @property ImasForums $forum
 */
class BaseImasForumSubscriptions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_forum_subscriptions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['forumid', 'userid'], 'required'],
            [['forumid', 'userid'], 'integer']
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
            'userid' => 'Userid',
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
    public function getForum()
    {
        return $this->hasOne(BaseImasForums::className(), ['id' => 'forumid']);
    }
}
