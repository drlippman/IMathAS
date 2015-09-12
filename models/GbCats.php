<?php

/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 25/6/15
 * Time: 7:13 PM
 */

namespace app\models;
use app\components\AppConstant;
use app\models\_base\BaseImasGbcats;
use Yii;
use app\components\AppUtility;
use yii\db\Query;

class GbCats extends BaseImasGbcats
{
    public static function findCategoryByCourseId($courseId){
        $query = new Query();
        $query->select(['id', 'name', 'scale', 'scaletype', 'chop', 'dropn', 'weight', 'hidden', 'calctype'])
            ->from('imas_gbcats')
            ->where(['courseid'=>$courseId])
            ->orderBy('name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
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
        $query = new Query();
        $query->select(['id', 'name'])
            ->from('imas_gbcats')
            ->where(['courseid'=>$courseId])
            ->orderBy('name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
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
        $query = new Query();
        $query->select(['id', 'name', 'scale', 'scaletype', 'chop', 'dropn', 'weight', 'hidden', 'calctype'])
            ->from('imas_gbcats')
            ->where('courseid= :courseid',[':courseid'=>$ctc]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getData($courseId,$name)
    {
        $query = new Query();
        $query->select(['id'])
            ->from('imas_gbcats')
            ->where('courseid= :courseid',[':courseid'=> $courseId])
            ->andWhere('name= :name',[':name' => $name]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
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
        $query = Yii::$app->db->createCommand("SELECT tc.id,toc.id FROM imas_gbcats AS tc JOIN imas_gbcats AS toc ON tc.name=toc.name WHERE tc.courseid= :ctc AND toc.courseid=:cid ");
        $query->bindValue('ctc',$ctc);
        $query->bindValue('cid',$courseId);
        $data = $query->queryAll();
        return $data;
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = GbCats::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
        }
    }

}
