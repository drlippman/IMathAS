<?php


namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasOutcomes;
use yii\db\Query;

class Outcomes extends BaseImasOutcomes {

    public  function SaveOutcomes($courseId,$outcome)
    {

            $this->name =$outcome;
            $this->courseid = $courseId;
            $this->save();
            return $this->id;

    }
    public static function getByCourseId($courseId)
    {

        $query = new Query();
        $query -> select(['id','name'])
               ->from('imas_outcomes')
                ->where(['courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourse($courseId){
        return Outcomes::find()->select('id,name')->where(['courseid' => $courseId])->all();
    }

    public static function getExistingOutcomes($courseId)
    {
        $query = new Query();
        $query -> select(['id','name'])
            ->from('imas_outcomes')
            ->where('courseid= :courseid',[':courseid' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
     }

    public function insertOutcomes($courseId,$name)
    {
        $this->courseid = $courseId;
        $this->name = $name;
        $this->save();
        return $this->id;

    }

    public static function UpdateOutcomes($name,$Id,$courseId)
    {
        $query = Outcomes::find()->where(['id' => $Id])->andWhere(['courseid' => $courseId])->one();
        if($query)
        {
            $query->name = $name;
            $query->save();
        }
    }

    public static function deleteUnusedOutcomes($unusedList)
    {
        $query = "DELETE FROM imas_outcomes WHERE id IN ($unusedList)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
    }
}