<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_forum_posts".
 *
 * @property string $id
 * @property string $forumid
 * @property string $threadid
 * @property string $userid
 * @property string $postdate
 * @property string $views
 * @property string $parent
 * @property integer $posttype
 * @property string $subject
 * @property string $message
 * @property string $files
 * @property string $tag
 * @property integer $isanon
 * @property string $replyby
 */
class BaseImasForumPosts extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_forum_posts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['forumid', 'threadid', 'userid', 'postdate', 'views', 'parent', 'posttype', 'subject', 'message', 'files', 'tag'], 'required'],
            [['forumid', 'threadid', 'userid', 'postdate', 'views', 'parent', 'posttype', 'isanon', 'replyby'], 'integer'],
            [['message', 'files'], 'string'],
            [['subject', 'tag'], 'string', 'max' => 254]
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
            'threadid' => 'Threadid',
            'userid' => 'Userid',
            'postdate' => 'Postdate',
            'views' => 'Views',
            'parent' => 'Parent',
            'posttype' => 'Posttype',
            'subject' => 'Subject',
            'message' => 'Message',
            'files' => 'Files',
            'tag' => 'Tag',
            'isanon' => 'Isanon',
            'replyby' => 'Replyby',
        ];
    }
}
