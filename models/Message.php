<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 22/4/15
 * Time: 6:30 PM
 */

namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\models\_base\BaseImasMsgs;

class Teacher extends BaseImasMsgs
{
    public function create($courseid)
    {
        $this->courseid = $courseid;
        $this->save();
        return $this->id;
    }

    public static function getByUserId($id)
    {
        return static::findAll(['id' => $id]);
    }
}
