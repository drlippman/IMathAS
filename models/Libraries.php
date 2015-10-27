<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasLibraries;
use app\models\_base\BaseImasLibrariesa;
use yii\db\Query;

class Libraries extends BaseImasLibraries
{
    public static function getAllLibrariesByJoin(){
        $query = new Query();
        $query->select('imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,COUNT(imas_library_items.id) AS count')
            ->from('imas_libraries')
            ->join('LEFT JOIN',
            'imas_library_items',
            'imas_library_items.libid=imas_libraries.id')
            ->groupBy('imas_libraries.id');
        return $query->createCommand()->queryAll();
    }

    public static function getByQSetId($qSetId){
        $query = new Query();
        $query->select('imas_libraries.id,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.groupid')
            ->from('imas_libraries')
            ->join('INNER JOIN',
                'imas_library_items',
                'imas_library_items.libid=imas_libraries.id')
            ->where(['imas_library_items.qsetid= :qSetId', [':qSetId' => $qSetId]]);
        return $query->createCommand()->queryAll();
    }

    public static function getByIdList($ids)
    {
        return Libraries::find()->where(['IN', 'id', $ids])->all();
    }

    public static function getUserAndLibrary($questionId)
    {
        $query = new Query();
        $query->select('imas_libraries.name,imas_users.LastName,imas_users.FirstName')
            ->from('imas_libraries,imas_library_items,imas_users')
            ->where('imas_libraries.id=imas_library_items.libid')
            ->andWhere('imas_library_items.ownerid=imas_users.id')
            ->andWhere(['imas_library_items.qsetid= :questionId', [':questionId' => $questionId]]);
        return $query->createCommand()->queryAll();
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

    public static function getQidAndLibID($aid)
    {
        $query = new Query();
        $query->select('imas_questions.id,imas_libraries.id AS libid,imas_libraries.name')
            ->from('imas_questions,imas_library_items,imas_libraries')
            ->where(['imas_questions.assessmentid=:aid', [':aid' => $aid]])
            ->andWhere('imas_questions.questionsetid=imas_library_items.qsetid')
            ->andWhere('imas_library_items.libid=imas_libraries.id')
            ->orderBy('imas_questions.id');
        return $query->createCommand()->queryAll();
    }

    public static function getById()
    {
        $query = new Query();
        $query->select('imas_libraries.id,imas_libraries.name,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.parent,imas_libraries.groupid,count(imas_library_items.id) AS count')
            ->from('imas_libraries')
            ->join('LEFT JOIN',
                'imas_library_items',
                'imas_library_items.libid=imas_libraries.id')
            ->groupBy('imas_libraries.id');
        return $query->createCommand()->queryAll();
    }

    public static function getByQuestionId($questionid){
        $query = new Query();
        $query->select('imas_questions.questionsetid,imas_questions.category,imas_libraries.name')->from('imas_questions')
            ->join('INNER JOIN','imas_libraries','imas_questions.category=imas_libraries.id')->where('imas_questions.id = :questionid')
            ->createCommand()->bindValue(':questionid',$questionid)->queryAll();
    }

    public static function getAllQSetId($qids)
    {
        $query = new Query();
        $query->select('imas_questions.questionsetid,imas_questions.category,imas_libraries.name,imas_questions.id')
            ->from('imas_questions')
            ->join('LEFT JOIN',
                'imas_libraries',
                'imas_questions.category=imas_libraries.id')
            ->where(['IN', 'imas_questions.id', $qids]);
        return $query->createCommand()->queryAll();
    }

    public static function dataForImportLib($lookup)
    {
        return Libraries::find()->select('id,uniqueid,adddate,lastmoddate')->where(['IN','uniqueid', $lookup])->all();
    }

    public static function updateLibData($isGrpAdmin, $isAdmin,$name,$now,$id,$user)
    {
        $questionSet = Libraries::find()->where('id' ,$id)->one();
        if ($isGrpAdmin)
        {
            $questionSet = Libraries::find()->where(['id' => $id, 'groupid' => $user->groupid])->one();
        }
        else if (!$isAdmin)
        {
            $questionSet = Libraries::find()->where(['id' => $id])->andWhere(['ownerid' => $user->id] or ['>', 'userights', 2])->one();
        }
        if($questionSet)
        {
            $questionSet->name = $name;
            $questionSet->adddate = $now;
            $questionSet->lastmoddate = $now;
            $questionSet->save();
        }
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

    public static function getByNameParents($name, $parents)
    {
        $query = new Query();
        $query	->select(['*'])
            ->from('imas_libraries')
            ->where('name = :name');
        $query->andWhere('parent = :parents' );
        $command = $query->createCommand();
        $data = $command->bindValue(':parents',$parents)->queryAll();
        return $data;
    }

    public function insertDataWithSort($uqid,$now, $now,$params,$userId,$groupId)
    {
        $this->uniqueid = $uqid;
        $this->adddate = $now;
        $this->lastmoddate = $now;
        $this->name = $params['name'];
        $this->ownerid = $userId;
        $this->userights = $params['rights'];
        $this->sortorder = $params['sortorder'];
        $this->parent = $params['libs'];
        $this->groupid = $groupId;
        $this->save();
        return $this->id;
    }

    public static function getByLibraryId($id)
    {
        return Libraries::find()->select('name','userights','parent','sortorder')->where(['id' => $id])->all();
    }

    public static function getByModifyId($id, $isAdmin, $userId)
    {
        $query = new Query();
        $query	->select(['name','userights','parent','sortorder'])
            ->from('imas_libraries')
            ->where('id = :id');
        if(!$isAdmin)
        {
            $query->andWhere(['ownerid' => $userId]);
        }
        $command = $query->createCommand();
        $data = $command->bindValue(':id',$id)->queryOne();
        return $data;
    }

    public static function updateById($params,$isadmin,$groupid,$isgrpadmin,$userid,$now)
    {
        $query = Libraries::find()->where(['id' => $_GET['modify']])->one();
        if (!$isadmin) {
            $query = Libraries::find()->where(['id' => $_GET['modify'],'groupid' => $groupid])->one();
        }
        if (!$isadmin && !$isgrpadmin) {
            $query = Libraries::find()->where(['id' => $_GET['modify'],'ownerid' => $userid])->one();
        }
        if($query)
        {
            $query->name = $params['name'];
            $query->userights = $params['rights'];
            $query->sortorder = $params['sortorder'];
            $query->lastmoddate = $now;
            if ($params['modify'] != $params['libs'])
            {
                $query->parent = $params['libs'];
            }
            $query->save();
        }
    }

    public static function updateByGrpIdUserId($params, $newgpid,$isadmin,$groupid, $isgrpadmin, $userid, $translist)
    {
        $query = "UPDATE imas_libraries SET ownerid='{$params['newowner']}',groupid='$newgpid' WHERE imas_libraries.id IN ($translist)";
        if (!$isadmin) {
            $query .= " AND groupid='$groupid'";
        }
        if (!$isadmin && !$isgrpadmin) {
            $query .= " AND ownerid='$userid'";
        }
        \Yii::$app->db->createCommand($query)->execute();
    }

    public static function getLibraryData($rootLibs,$nonPrivate)
    {
        $query = new Query();
        $query ->select('id,name,parent,uniqueid,lastmoddate')
            ->from('imas_libraries')
            ->where(['IN','rootLibs',$rootLibs]);
        if($nonPrivate)
        {
            $query->andWhere(['>','userights','0']);
        }
        $query ->orderBy('uniqueid');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataByParent($lib,$nonPrivate)
    {
        $query = new Query();
        $query ->select(['id','name','uniqueid','lastmoddate'])
            ->from('imas_libraries')
            ->where(['parent' => $lib]);
        if($nonPrivate)
        {
            $query->andWhere(['>','userights','0']);
        }
        $query ->orderBy('uniqueid');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataForLibTree()
    {
        $query = new Query();
        $query->select('imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,COUNT(imas_library_items.id) AS count')
            ->from('imas_libraries')
            ->join('LEFT JOIN',
                'imas_library_items',
                'imas_library_items.libid=imas_libraries.id')
            ->groupBy('imas_libraries.id');
        return $query->createCommand()->queryAll();
    }

    public static function updateByGrpUserIdSingle($params, $newgpid, $idTransfer, $isadmin, $groupid, $isgrpadmin, $userid)
    {
        $query = Libraries::find()->where(['id' => $idTransfer])->one();
        if (!$isadmin) {
            $query = Libraries::find()->where(['id' => $idTransfer, 'groupid' => $groupid])->one();
        }
        if (!$isadmin && !$isgrpadmin) {
            $query = Libraries::find()->where(['id' => $idTransfer, 'ownerid' => $userid])->one();
        }
        if($query)
        {
            $query->ownerid = $params['newowner'];
            $query->groupid = $newgpid;
            $query->save();
        }
     }

    public static function getCountOfId($parent)
    {
        return Libraries::find()->where(['parent' => $parent])->all();
    }

    public static function getByIdGroupAdmin($remlist, $groupid)
    {
        return Libraries::find()->select('id')->where(['IN','id',$remlist])->andWhere(['groupid' => $groupid])->all();
    }

    public static function getByIdAdmin($remlist, $userid)
    {
        return Libraries::find()->select('id')->where(['IN','id',$remlist])->andWhere(['ownerid' => $userid])->all();
    }

    public static function deleteLibraryAdmin($remlist,$isadmin,$groupid,$isgrpadmin,$userid)
    {
        $query = "DELETE FROM imas_libraries WHERE id IN ($remlist)";
        if (!$isadmin) {
            $query .= " AND groupid='$groupid'";
        }
        if (!$isadmin && !$isgrpadmin) {
            $query .= " AND ownerid='$userid'";
        }
        \Yii::$app->db->createCommand($query)->execute();
    }

    public static function deleteLibrarySingle($remove,$isadmin,$groupid,$isgrpadmin,$userid)
    {
        $query = "DELETE FROM imas_libraries WHERE id='$remove'";
        if (!$isadmin) {
            $query .= " AND groupid='$groupid'";
        }
        if (!$isadmin && !$isgrpadmin) {
            $query .= " AND ownerid='$userid'";
        }
      return  \Yii::$app->db->createCommand($query)->execute();
    }

    public static function updateUserRightLastModeDate($rights,$now, $llist,$isadmin,$groupid,$isgrpadmin,$userid)
    {
        $query = "UPDATE imas_libraries SET userights='$rights',lastmoddate=$now WHERE id IN ($llist)";
        if (!$isadmin) {
            $query .= " AND groupid='$groupid'";
        }
        if (!$isadmin && !$isgrpadmin) {
            $query .= " AND ownerid='$userid'";
        }
        \Yii::$app->db->createCommand($query)->execute();
    }

    public static function updateParent($lib,$now,$parlist,$isadmin,$groupid,$isgrpadmin,$userid)
    {
        $query = "UPDATE imas_libraries SET parent='$lib',lastmoddate=$now WHERE id IN ($parlist)";
        if (!$isadmin) {
            $query .= " AND groupid='$groupid'";
        }
        if (!$isadmin && !$isgrpadmin) {
            $query .= " AND ownerid='$userid'";
        }
        \Yii::$app->db->createCommand($query)->execute();
    }

    public static function getByName($id)
    {
        return Libraries::find()->select('name')->where(['id'=> $id])->all();
    }

    public static function getByNameList($ids)
    {
        return Libraries::find()->select('name')->where(['IN', 'id', $ids])->all();
    }

    public static function updateGroupIdAdmin($id, $groupId)
    {
        $libraries = Libraries::find()->where(['ownerid' => $id])->all();
        if($libraries)
        {
            foreach($libraries as $library)
            {
                $library -> groupid = $groupId;
                $library -> save();
            }
        }
    }

    public static function getLibrariesByIdList($ids)
    {
        return Libraries::find()->select('name,id,sortorder')->where(['IN', 'id', $ids])->all();
    }

    public function getLibData($inLibsSafeArray){
        return self::find()->select('id,ownerid,userights,groupid')->where(['IN', 'id', $inLibsSafeArray])->all();
    }
}