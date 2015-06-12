<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 30/5/15
 * Time: 12:59 PM
 */

namespace app\models\forms;
use app\components\AppUtility;
use app\models\ForumPosts;
use yii\base\Model;
use Yii;
class ThreadForm extends Model
{
    public static  function thread($forumid){

        $thread = ForumPosts::findAll(['forumid' => $forumid]);
        return $thread;

    }


} 