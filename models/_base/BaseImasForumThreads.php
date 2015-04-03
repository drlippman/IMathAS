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
            [['id', 'forumid', 'lastposttime', 'lastpostuser', 'views'], 'required'],
            [['id', 'forumid', 'stugroupid', 'lastposttime', 'lastpostuser', 'views'], 'integer']
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
}
