<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_forum_views".
 *
 * @property string $id
 * @property string $userid
 * @property string $threadid
 * @property string $lastview
 * @property integer $tagged
 *
 * @property ImasForumThreads $thread
 * @property ImasUsers $user
 */
class BaseImasForumViews extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_forum_views';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['userid', 'threadid', 'lastview'], 'required'],
            [['userid', 'threadid', 'lastview', 'tagged'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'threadid' => 'Threadid',
            'lastview' => 'Lastview',
            'tagged' => 'Tagged',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getThread()
    {
        return $this->hasOne(BaseImasForumThreads::className(), ['id' => 'threadid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'userid']);
    }
}
