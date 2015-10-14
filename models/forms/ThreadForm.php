<?php

namespace app\models\forms;
use app\components\AppConstant;
use app\components\AppUtility;
use app\models\ForumPosts;
use yii\base\Model;
use Yii;
class ThreadForm extends Model
{
    public $file;

    public function rules()
    {
        return [
            ['file','safe'],
            [['file'], 'file'],
        ];
    }
    public function attributeLabels()
    {
        return
            [
                'file' => ' '
            ];
    }

    public static  function thread($forumid){
        $sortBy = AppConstant::DESCENDING;
        $thread = ForumPosts::find()->where(['forumid' => $forumid])->orderBy(['posttype'=> $sortBy ,'id' => $sortBy])->all();
        return $thread;
    }

    public static  function postByName($forumid,$sort,$orderby){

        $thread = ForumPosts::find()->where(['forumid' => $forumid])->orderBy([$orderby=>$sort])->all();
        return $thread;


    }


} 