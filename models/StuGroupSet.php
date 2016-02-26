<?php


namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use Yii;
use app\models\_base\BaseImasStugroupset;
use yii\db\Query;

class StuGroupSet extends BaseImasStugroupset
{

    public static function getByCourseId($courseId)
    {
        return StuGroupSet::find()->select('id,name')->where(['courseid' => $courseId])->orderBy(['name' => AppConstant::ASCENDING])->all();
    }

    public static function getByJoin($courseId)
    {
        $query = Yii::$app->db->createCommand("SELECT imas_stugroupset.id,imas_stugroupset.name FROM imas_stugroupset
         LEFT JOIN imas_stugroups ON imas_stugroups.groupsetid=imas_stugroupset.id LEFT JOIN imas_stugroupmembers
         ON imas_stugroups.id=imas_stugroupmembers.stugroupid WHERE imas_stugroupset.courseid= :courseId GROUP BY
         imas_stugroupset.id HAVING count(imas_stugroupmembers.id)=0");
        $query->bindValue('courseId', $courseId);
        $data = $query->queryAll();
        return $data;
    }

    public function createGroupSet($courseId, $name)
    {
        $this->courseid = $courseId;
        $this->name = $name;
        $this->save();
        return $this->id;
    }

    public static function findGroupData($courseId)
    {
        return StuGroupSet::find()->select(['id', 'name'])->where(['courseid'  => $courseId])->orderBy('name')->all();
    }

    public function InsertGroupData($groupName, $courseId)
    {
        $this->name = ($groupName)?trim($groupName):null;
        $this->courseid = $courseId;
        $this->save();
        return $this;
    }

    public static function getByGrpSetId($renameGrpSetId)
    {
        return StuGroupSet::find('name')->where(['id' => $renameGrpSetId])->one();
    }

    public function UpdateGrpSet($modifiedGrpName, $renameGrpSetId)
    {
        $query = StuGroupSet::find('name')->where(['id' => $renameGrpSetId])->one();
        if ($query) {
            $query->name = $modifiedGrpName;
            $query->save();
        }
    }

    public function  copyGroupSet($copyGrpSet, $courseId)
    {
        $query = StuGroupSet::find('name')->where(['id' => $copyGrpSet])->one();
        $copiedName = $query['name']. ' (copy)';
        $this->InsertGroupData($copiedName, $courseId);
        return $this->id;
    }

    public static function deleteGrpSet($deleteGrpSet)
    {
        $query = StuGroupSet::find()->where(['id' => $deleteGrpSet])->all();
        if ($query) {
            foreach ($query as $dlt) {

                $dlt->delete();
            }
        }
    }

    public static function getByCid($courseId)
    {
        return StuGroupSet::find()->select('id')->where(['courseid' => $courseId])->all();
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = StuGroupSet::findOne(['courseid', $courseId]);
        if ($courseData) {
            $courseData->delete();
        }
    }
}