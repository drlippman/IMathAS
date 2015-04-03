<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_forum_subscriptions".
 *
 * @property string $id
 * @property string $forumid
 * @property string $userid
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
}
