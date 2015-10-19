<?php

namespace app\models;

use app\components\AppConstant;
use app\models\_base\BaseImasGbcats;
use Yii;
use app\components\AppUtility;
use yii\db\Query;

class GbCats extends BaseImasGbcats
{
    public static function findCategoryByCourseId($courseId){
        return self::find()->select(['id', 'name', 'scale', 'scaletype', 'chop', 'dropn', 'weight', 'hidden', 'calctype'])->where(['courseid'=>$courseId])->orderBy('name')->all();
    }

    public static function getByCourseId($courseId)
    {
        return GbCats::find()->select('id,name')->where(['courseid' => $courseId])->orderBy(['name' => AppConstant::ASCENDING])->all();
    }

    public  static function updateGbCat($id, $name, $scale, $scaleType, $chop, $drop, $weight, $hide, $calcType){
        $query = GbCats::findOne(['id' => $id]);
        if($query){
            $query->name = $name;
            $query->scale = $scale;
            $query->scaletype = $scaleType;
            $query->chop = $chop;
            $query->dropn = $drop;
            $query->weight = $weight;
            $query->hidden = $hide;
            $query->calctype = $calcType;
            $query->save();
        }
    }

    public static function deleteGbCat($catList){
        foreach($catList as $category){
            $query = GbCats::findOne(['id' => $category]);
            if($query){
                $query->delete();
            }
        }
    }

    public static function createGbCat($courseId, $name, $scale, $scaleType, $chop, $weight, $hide, $calcType){
        $query = new GbCats();
        $query->courseid = $courseId;
        $query->scale = $scale;
        $query->scaletype = $scaleType;
        $query->chop = $chop;
        $query->name = $name;
        $query->weight = $weight;
        $query->hidden = $hide;
        $query->calctype = $calcType;
        $query->save();
    }

    public static function getByCourseIdAndOrderByName($courseId)
    {
        return self::find()->select(['id', 'name'])->where(['courseid'=>$courseId])->orderBy('name')->all();
    }

    public static function getGbCatsForOutcomeMap($catList)
    {
        $query = new Query();
        $query->select(['id', 'name'])
            ->from('imas_gbcats')
            ->where(['IN','id', $catList]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataForCopyCourse($ctc)
    {
        return self::find()->select(['id', 'name', 'scale', 'scaletype', 'chop', 'dropn', 'weight', 'hidden', 'calctype'])->where(['courseid'=>$ctc])->all();
    }

    public static function getData($courseId,$name)
    {
        return self::find()->select(['id'])->where(['courseid'=>$courseId, 'name' => $name])->all();
    }

    public function insertData($courseId,$data)
    {
        $this->courseid = $courseId;
        $this->name = $data['name'];
        $this->scale = $data['scale'];
        $this->scaletype = $data['scaletype'];
        $this->chop = $data['chop'];
        $this->dropn = $data['dropn'];
        $this->weight = $data['weight'];
        $this->hidden = $data['hidden'];
        $this->calctype = $data['calctype'];
        $this->save();
        return $this->id;
    }

    public static function updateData($rpId,$data)
    {
        $query = GbCats::find()->where(['id' => $rpId])->one();
        if($query)
        {
            $query->scale = $data['scale'];
            $query->scaletype = $data['scaletype'];
            $query->chop = $data['chop'];
            $query->dropn = $data['dropn'];
            $query->weight = $data['weight'];
            $query->hidden = $data['hidden'];
            $query->calctype = $data['calctype'];
            $query->save();
        }
    }

    public static function getDataByJoins($ctc,$courseId)
    {
        $query = new Query();
        $query->select('tc.id,toc.id')
            ->from('imas_gbcats AS tc')
            ->join('INNER JOIN',
                'imas_gbcats AS toc',
                'tc.name=toc.name'
            )
            ->where(['tc.courseid= :ctc']);
        $query->andWhere(['toc.courseid=:cid']);
        $command = $query->createCommand()->bindValues(['ctc' => $ctc, 'cid' => $courseId]);
        $items = $command->queryAll();
        return $items;
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = GbCats::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
        }
    }
}