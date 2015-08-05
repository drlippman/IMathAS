<?php


namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\models\_base\BaseImasStugroups;
use yii\db\Query;

class Stugroups extends BaseImasStugroups {
    public static function findByCourseId($courseId){
        $query = new Query();
        $query	->select(['imas_stugroups.id'])
            ->from('imas_stugroups')
            ->join(	'INNER JOIN',
                'imas_stugroupset',
                'imas_stugroups.groupsetid=imas_stugroupset.id'
            )
            ->where(['imas_stugroupset.courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }



}