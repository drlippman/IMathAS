<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 30/5/15
 * Time: 12:59 PM
 */

namespace app\models\forms;
use app\components\AppUtility;
use yii\base\Model;
use Yii;
class ThreadForm extends Model
{
    public static  function thread(){

        $thread = Yii::$app->db->createCommand("SELECT * from  imas_forum_posts ")->queryAll();
        return $thread;

    }


} 