<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 25/6/15
 * Time: 3:37 PM
 */

namespace app\models;
use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasLibraries;
use app\models\_base\BaseImasLibrariesa;
use yii\db\Query;

class Libraries extends BaseImasLibraries
{
    public static function getAllLibrariesByJoin(){
        $query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,COUNT(imas_library_items.id) AS count ";
        $query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id GROUP BY imas_libraries.id";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getByQSetId($qSetId){
        $query = "SELECT imas_libraries.id,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.groupid ";
        $query .= "FROM imas_libraries,imas_library_items WHERE imas_library_items.libid=imas_libraries.id ";
        $query .= "AND imas_library_items.qsetid='$qSetId'";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getByIdList($ids)
    {
        return Libraries::find()->where(['IN', 'id', $ids])->all();
    }

    public static function getUserAndLibrary($questionId){
        $query = "SELECT imas_libraries.name,imas_users.LastName,imas_users.FirstName FROM imas_libraries,imas_library_items,imas_users  WHERE imas_libraries.id=imas_library_items.libid AND imas_library_items.ownerid=imas_users.id AND imas_library_items.qsetid='$questionId'";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function updateGroupId($id)
    {
        $libraries = Libraries::find()->where(['groupid' => $id])->all();
        if($libraries)
        {
            foreach($libraries as $library)
            {
                $library -> groupid = AppConstant::NUMERIC_ZERO;
                $library -> save();
            }
        }
    }

    public static function getQidAndLibID($aid){
        $query = "SELECT imas_questions.id,imas_libraries.id AS libid,imas_libraries.name FROM imas_questions,imas_library_items,imas_libraries ";
        $query .= "WHERE imas_questions.assessmentid='$aid' AND imas_questions.questionsetid=imas_library_items.qsetid AND ";
        $query .= "imas_library_items.libid=imas_libraries.id ORDER BY imas_questions.id";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function getById()
    {
        $query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.parent,imas_libraries.groupid,count(imas_library_items.id) AS count ";
        $query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id ";
        $query .= "GROUP BY imas_libraries.id";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getByQuestionId($questionid){
//        $query = Libraries::find()->select('imas_questions.questionsetid,imas_questions.category,imas_libraries.name')->from('imas_questions,imas_libraries')
//            ->where(['imas_questions.category=imas_libraries.id'])->andWhere(['imas_questions.id' => $questionid]);
        $query = "SELECT imas_questions.questionsetid,imas_questions.category,imas_libraries.name FROM imas_questions LEFT JOIN imas_libraries ";
        $query .= "ON imas_questions.category=imas_libraries.id WHERE imas_questions.id='$questionid'";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getAllQSetId($qids){
        $query = "SELECT imas_questions.questionsetid,imas_questions.category,imas_libraries.name,imas_questions.id FROM imas_questions LEFT JOIN imas_libraries ";
        $query .= "ON imas_questions.category=imas_libraries.id WHERE imas_questions.id IN ($qids)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function dataForImportLib($lookup)
    {
        $query = "SELECT id,uniqueid,adddate,lastmoddate FROM imas_libraries WHERE uniqueid IN ('$lookup')";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function updateLibData($isGrpAdmin, $isAdmin,$name,$now,$id,$user)
    {
        $query = "UPDATE imas_libraries SET name='{$name}',adddate=$now,lastmoddate=$now WHERE id={$id}";
        if ($isGrpAdmin)
        {
            $query .= " AND groupid='$user->groupid'";
        }
        else if (!$isAdmin)
        {
            $query .= " AND (ownerid='$user->id' or userights >1)";
        }
        $data = \Yii::$app->db->createCommand($query)->execute();
        return $data;
    }

    public function insertData($uniqueId,$now,$names,$user,$libRights,$parent)
    {
        $this->uniqueid = $uniqueId;
        $this->adddate = $now;
        $this->lastmoddate = $now;
        $this->name = $names;
        $this->ownerid = $user->id;
        $this->userights =$libRights;
        $this->parent = $parent;
        $this->groupid = $user->groupid;
        $this->save();
        return $this->id;

    }


}