<?php

namespace app\models\forms;


use app\components\AppUtility;
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
        $data = $command->queryAll();
        return $data;

    }
    public static function deleteLtiUsr($id)
    {
        $data = LtiUserForm::find()->where(['id' => $id])->one();
        if($data)
        {
            $data->delete();
        }
    }

} 