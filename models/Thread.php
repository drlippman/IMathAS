<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 30/5/15
 * Time: 1:05 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasForumThreads;
use app\models\_base\BaseImasInstrFiles;

class Thread extends BaseImasForumThreads
{


    public static function getById($id)
    {
        return Thread::findOne(['id' => $id]);
    }

    public static function getNextThreadId($currentId,$next=null,$prev=null)
    {
        if($next == AppConstant::NUMERIC_TWO){
            $thread = Thread::find()->where(['>', 'id', $currentId])->one();
        }elseif($prev == AppConstant::NUMERIC_ONE){
            $thread = Thread::find()->where(['<', 'id', $currentId])->one();
        }
        $minThreadId = Thread::find()->min('id');
        $maxThreadId = Thread::find()->max('id');
        $prevNextValueArray = array();
        $prevNextValueArray = array(
        'threadId' =>$thread->id,
        'maxThread' =>$maxThreadId,
            'minThread' =>$minThreadId,
    );
        return $prevNextValueArray;
    }
} 