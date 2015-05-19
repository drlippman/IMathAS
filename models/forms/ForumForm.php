<?php

namespace app\models\forms;
use yii\base\Model;
class ForumForm extends Model
{
    public $search;
    public $thread;
    public $post;

    public function rules()
    {
        return
            [
                [['search'],'required']
            ];
    }

    public function attributeLabels()
    {
        return
            [
                'search' => 'Search',
                'thread' => 'Thread',
            ];
    }
}
