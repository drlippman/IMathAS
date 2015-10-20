<?php


namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasOutcomes;
use yii\db\Query;
use Yii;

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
        return Outcomes::find()->select(['id','name'])->where(['courseid' => $courseId])->all();
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
        $data = Outcomes::find()->where(['IN', 'id', $unusedList])->all();
        if($data){
            foreach($data as $singleData){
                $singleData->delete();
            }
        }
    }

    public static function getDataByJoins($ctc,$courseId)
    {
        $query = Yii::$app->db->createCommand("SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid= :ctc AND toc.courseid=:cid ");
        $query->bindValue('ctc',$ctc);
        $query->bindValue('cid',$courseId);
        $data = $query->queryAll();
        return $data;
    }

    public static function getDataForCopyCourse($ctc)
    {
        $query = new Query();
        $query -> select(['id','name','ancestors'])
            ->from('imas_outcomes')
            ->where('courseid= :courseid',[':courseid' => $ctc]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function insertDataForCopyCourse($data,$courseId)
    {
        $this->courseid = $courseId;
        $this->name = $data['name'];
        $this->ancestors = $data['ancestors'];
        $this->save();
        return $this->id;
    }
}