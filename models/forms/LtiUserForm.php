<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 1/9/15
 * Time: 9:16 PM
 */

namespace app\models\forms;


use app\models\_base\BaseImasLtiusers;
use yii\base\Model;
use yii\db\Query;

class LtiUserForm extends BaseImasLtiusers
{

    public static function getUserData($id)
    {
        $query = new Query();
        $query	->select(['org','id'])
            ->from('imas_ltiusers')
            ->where('userid= :id',[':id' => $id]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;

    }

} 