<?php

namespace app\models\forms;
use app\components\AppUtility;
use yii\base\Model;
use Yii;
use yii\db\Query;

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
    public static  function byAllSubject($search,$courseId,$userId){
        $query = new Query();
        $query->select(['*'])
            ->from('imas_forum_posts')
           ->join(	'JOIN',
                'imas_forums',
                'imas_forum_posts.forumid = imas_forums.id')
            ->join('JOIN','imas_users',
                ' imas_users.id=imas_forum_posts.userid')
            ->join('JOIN','imas_forum_threads'
                ,'imas_forum_threads.id=imas_forum_posts.threadid ')
            ->leftJoin('imas_forum_views','imas_forum_threads.id=imas_forum_views.threadid')
        ->andWhere(['imas_forum_views.userid' => $userId ])
        ->andWhere(['imas_forums.courseid'=> $courseId ])
        ->andWhere(['LIKE','imas_forum_posts.subject', $search])
        ->andWhere('imas_forum_posts.id = imas_forum_posts.threadid ');
        $query->orderBy(['imas_forum_posts.postdate'=> SORT_DESC]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;


    }
    public static  function byAllpost($search ){

        $subject = Yii::$app->db->createCommand("SELECT * from  imas_forum_posts where (subject LIKE '%$search%') OR (message LIKE '%$search%') order by postdate desc")->queryAll();
        return $subject;

    }

}
