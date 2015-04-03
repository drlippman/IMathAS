<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_forum_likes".
 *
 * @property string $id
 * @property string $userid
 * @property string $threadid
 * @property string $postid
 * @property integer $type
 */
class BaseImasForumLikes extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_forum_likes';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'threadid', 'postid', 'type'], 'required'],
            [['userid', 'threadid', 'postid', 'type'], 'integer']
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
            'postid' => 'Postid',
            'type' => 'Type',
        ];
    }
}
