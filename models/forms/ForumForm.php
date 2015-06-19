<?php

namespace app\models\forms;
use app\components\AppUtility;
use yii\base\Model;
use Yii;
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
    public static  function byAllSubject($search ){

        $subject = Yii::$app->db->createCommand("SELECT * from  imas_forum_posts where subject LIKE '$search%' order by postdate desc")->queryAll();
        return $subject;

    }


    public static  function byAllpost($search ){

        $subject = Yii::$app->db->createCommand("SELECT * from  imas_forum_posts where (subject LIKE '%$search%') OR (message LIKE '%$search%') order by postdate desc")->queryAll();
        return $subject;

    }

}
