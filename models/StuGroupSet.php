<?php


namespace app\models;

use Yii;
use yii\db\Exception;
use app\components\AppUtility;
use app\models\_base\BaseImasStugroupset;
use yii\db\Query;

class StuGroupSet extends BaseImasStugroupset {

    public static function getByCourseId($courseId){
        return StuGroupSet::find()->select('id,name')->where(['courseid' => $courseId])->all();
    }

    public static function getByJoin($courseId){
        $query = Yii::$app->db->createCommand("SELECT imas_stugroupset.id,imas_stugroupset.name FROM imas_stugroupset
         LEFT JOIN imas_stugroups ON imas_stugroups.groupsetid=imas_stugroupset.id LEFT JOIN imas_stugroupmembers
         ON imas_stugroups.id=imas_stugroupmembers.stugroupid WHERE imas_stugroupset.courseid='$courseId' GROUP BY
         imas_stugroupset.id HAVING count(imas_stugroupmembers.id)=0")->queryAll();
        return $query;
    }

    public function createGroupSet($courseId,$name){
        $this->courseid = $courseId;
        $this->name = $name;
        $this->save();
        return $this->id;
    }

}