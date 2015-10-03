<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 11/5/15
 * Time: 1:10 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasQuestionset;
use yii\db\Query;

class QuestionSet extends BaseImasQuestionset
{
    public static function getByQuesSetId($id)
    {
        return QuestionSet::findOne(['id' => $id]);
    }
    public static function getByIdUsingInClause($ids)
    {
        $data = QuestionSet::find()->where(['IN','id',$ids])->all();
        return $data;
    }

    public static function getByUserIdJoin($searchall,$userid,$llist,$searchmine,$searchlikes){
        $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.extref,imas_library_items.libid,imas_questionset.ownerid,imas_questionset.avgtime,imas_questionset.solution,imas_questionset.solutionopts,imas_library_items.junkflag, imas_library_items.id AS libitemid,imas_users.groupid ";
        $query .= "FROM imas_questionset JOIN imas_library_items ON imas_library_items.qsetid=imas_questionset.id ";
        $query .= "JOIN imas_users ON imas_questionset.ownerid=imas_users.id WHERE imas_questionset.deleted=0 AND imas_questionset.replaceby=0 AND $searchlikes "; //imas_questionset.description LIKE '%$safesearch%' ";
        $query .= " (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0)";

        if ($searchall==0) {
            $query .= "AND imas_library_items.libid IN ($llist)";
        }
        if ($searchmine==1) {
            $query .= " AND imas_questionset.ownerid='$userid'";
        } else {
            $query .= " AND (imas_library_items.libid > 0 OR imas_questionset.ownerid='$userid') ";
        }
        $query .= " ORDER BY imas_library_items.libid,imas_library_items.junkflag,imas_questionset.id";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getByUserId($aid,$userid,$existingqlist){
        $query = \Yii::$app->db->createCommand("SELECT a.questionsetid, count( DISTINCT a.assessmentid ) as qcnt,
						imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.ownerid
						FROM imas_questions AS a
						JOIN imas_questions AS b ON a.assessmentid = b.assessmentid
						JOIN imas_questions AS c ON b.questionsetid = c.questionsetid
						AND c.assessmentid ='$aid'
						JOIN imas_questionset  ON a.questionsetid=imas_questionset.id
						AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0)
						AND imas_questionset.deleted=0
						AND imas_questionset.replaceby=0
						WHERE a.questionsetid NOT IN ($existingqlist)
						GROUP BY a.questionsetid ORDER BY qcnt DESC LIMIT 100")->queryAll();
        return $query;
    }

    public static function getByGroupId($id,$groupId){
        $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
        $query .= "WHERE iq.id='$id' AND iq.ownerid=imas_users.id AND (imas_users.groupid='$groupId' OR iq.userights>2)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getByUserIdGroupId($id,$userId,$groupId){
        $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
        $query .= "WHERE iq.id='$id' AND iq.ownerid=imas_users.id AND (iq.ownerid='$userId' OR (iq.userights=3 AND imas_users.groupid='$groupId') OR iq.userights>3)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function updateQuestionSet($params,$now,$extref,$replaceby,$solutionopts){
        $questionSet = QuestionSet::findOne(['id' => $params['id']]);
        if($questionSet)
        {
            $questionSet->description = isset($params['description']) ? $params['description'] : null;
            $questionSet->author = isset($params['author']) ? $params['author'] : null;
            $questionSet->userights = isset($params['userights']) ? $params['userights'] : null;
            $questionSet->license = isset($params['license']) ? $params['license'] : null;
            $questionSet->otherattribution = isset($params['addattr']) ? $params['addattr'] : null;
            $questionSet->qtype = isset($params['qtype']) ? $params['qtype'] : null;
            $questionSet->control = isset($params['control']) ? $params['control'] : null;
            $questionSet->qcontrol = isset($params['qcontrol']) ? $params['qcontrol'] : null;
            $questionSet->solution = isset($params['solution']) ? $params['solution'] : null;
            $questionSet->qtext = isset($params['qtext']) ? $params['qtext'] : null;
            $questionSet->answer = isset($params['answer']) ? $params['answer'] : null;
            $questionSet->lastmoddate = $now;
            $questionSet->extref = $extref;
            $questionSet->replaceby = $replaceby;
            $questionSet->solutionopts = $solutionopts;
            if (isset($params['undelete'])) {
            $questionSet->deleted = AppConstant::NUMERIC_ZERO;
            }
            $questionSet->save();
        }
        return $questionSet;
    }

    public static function getQuestionDataById($id){
         return QuestionSet::findOne(['id' => $id]);
    }

    public static function setHasImage($id,$no){
        $data = QuestionSet::getQuestionDataById($id);
        if($data){
            $data->hasimg = $no;
            $data->save();
        }
    }

    public function createQuestionSet($params){
        $data = AppUtility::removeEmptyAttributes($params);
        $this->attributes = $data;
        $this->save();
        return $this->id;
    }

    public static function getByQSetIdJoin($id){
        $query = "SELECT imas_questionset.*,imas_users.groupid FROM imas_questionset,imas_users WHERE ";
        $query .= "imas_questionset.ownerid=imas_users.id AND imas_questionset.id='$id'";
        $data = \Yii::$app->db->createCommand($query)->queryOne();
        return $data;
    }

    public static function getUserAndQuestionSetJoin($id){
        $query = "SELECT imas_users.email,imas_questionset.* ";
        $query .= "FROM imas_users,imas_questionset WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.id='$id'";
        $data = \Yii::$app->db->createCommand($query)->queryOne();
        return $data;
    }

    public static function getDataForCopyCourse($ctc)
    {
        $query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
        $query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
        $query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
        $query .= "imas_assessments.courseid='{$ctc}' AND imas_questionset.replaceby>0";
        $data = \Yii::$app->db->createCommand($query)->queryOne();
        return $data;
    }
    public static function getByQuestionId($questionList)
    {
        $query = new Query();
        $query  = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questionset.id AS qid,imas_questions.withdrawn,imas_questionset.qtype,imas_questionset.control,imas_questions.showhints,imas_questionset.extref ";
		$query .= "FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN ($questionList)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public  static function getQuestionSetData($ids){
        $query = "SELECT id,description,extref,qtype,control FROM imas_questionset WHERE id IN ('".implode("','",$ids)."')";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function updateVideoId($from,$to)
    {
        $query = "UPDATE imas_questionset SET extref=REPLACE(extref,'$from','$to') WHERE extref LIKE '%$from%'";
        $connection=\Yii::$app->db;
        $command=$connection->createCommand($query);
        $rowCount=$command->execute();
        return $rowCount;
    }

    public static function getExternalRef()
    {
        $data = new Query();
        $data ->select(['uniqueid','lastmoddate','extref'])
               ->from(['imas_questionset'])
            ->where(['<>','extref','']);
        $command = $data->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function getWrongLibFlag()
    {
        $query = "SELECT iqs.uniqueid,il.uniqueid AS uniqId FROM imas_questionset AS iqs
                  JOIN imas_library_items AS ili ON iqs.id=ili.qsetid
                  JOIN imas_libraries AS il ON ili.libid=il.id
                  WHERE ili.junkflag>0";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;

    }
    public static function getDataToUpdateExtRef()
    {
        $query = "SELECT id,uniqueid,lastmoddate,extref FROM imas_questionset WHERE 1";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;

    }
    public static function updateExternalRef($uniqueId,$rowId)
    {
        $data = QuestionSet::find()->where(['id' => $rowId])->one();
        if($data)
        {
            $data->extref = $uniqueId;
            $data->save();
        }
    }

    public static function getDataToUpdateQuestionUsageData()
    {
        $query = "SELECT id,questionsetid FROM imas_questions WHERE 1";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;

    }

    public static function updateAvgTime($avg,$qsid)
    {
        $query = "UPDATE imas_questionset SET avgtime='$avg' WHERE id=$qsid";
        return  \Yii::$app->db->createCommand($query)->query();

    }

    public static function getIdAndAvgTime()
    {
        $data = new Query();
        $data->select(['avgtime', 'id'])
            ->from(['imas_questionset']);
        $command = $data->createCommand();
        $data = $command->queryAll();
        return $data;

    }
    public static function getQuestionData($aid)
    {
        $query = "SELECT iq.id,iq.category,iqs.description FROM imas_questions AS iq,imas_questionset as iqs";
        $query .= " WHERE iq.questionsetid=iqs.id AND iq.assessmentid='$aid'";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getQtext($id){
        return QuestionSet::find()->select('qtext')->where(['id' => $id])->one();
    }
    public static function getControlAndQType($id){
        return QuestionSet::find()->select('control,qtype')->where(['id' => $id])->one();
    }

    public static function findDataToImportLib($qIdsToCheck)
    {
        $query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($qIdsToCheck) AND (control LIKE '%includecodefrom(UID%' OR qtext LIKE '%includeqtextfrom(UID%')";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function getUniqueId($includedList)
    {
        $query = "SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($includedList)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function getDataToImportLib($qIdsToCheck)
    {
        $query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($qIdsToCheck)";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function updateQuestionSetToImportLib($control,$qText,$rowId)
    {
        $QuestionSet = QuestionSet::find()->where(['id' => $rowId])->one();
        if($QuestionSet)
        {
            $QuestionSet->control = $control;
            $QuestionSet->qtext = $qText;
            $QuestionSet->save();
        }
    }

    public static function getByQSetIdAndGroupId($list, $groupId){
        $data = QuestionSet::find()->select('imas_questionset.id')->from('imas_questionset,imas_users')
                ->where(['IN','imas_questionset.id', $list])->andWhere('imas_questionset.ownerid=imas_users.id')
                ->andWhere(['imas_users.groupid' => $groupId])->all();
        return $data;
    }

    public static function setDeletedById($id){
        $data = QuestionSet::findOne(['id' => $id]);
        if($data){
            $data->deleted = AppConstant::NUMERIC_ONE;
            $data->save();
        }
    }

    public static function getIdByIDAndOwnerId($removeList, $userId){
        return QuestionSet::find()->select('id')->where(['IN', 'id', $removeList])->andWhere(['ownerid' => $userId])->all();
    }

    public static function setDeletedByIds($removeList){
        $data = QuestionSet::getByIdUsingInClause($removeList);
        if($data){
            foreach($data as $singleData){
                $singleData->deleted = AppConstant::NUMERIC_ONE;
                $singleData->save();
            }
        }
    }

    public static function getLastModDateAndId($UId)
    {
        $data = new Query();
        $data->select(['id', 'adddate','lastmoddate'])
            ->from(['imas_questionset'])
            ->where(['uniqueid' => $UId]);
        $command = $data->createCommand();
        $data = $command->queryone();
        return $data;

    }

    public static function getQSetAndUserData($qSetId,$groupId)
    {
        $data = new Query();
        $data->select(['imas_questionset.id'])
            ->from(['imas_questionset','imas_users'])
            ->where(['imas_questionset.id' => $qSetId])
            ->andWhere(['imas_questionset.ownerid=imas_users.id'])
            ->andWhere(['imas_users.groupid' => $groupId]);
        $command = $data->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function UpdateQuestionsetData($qd,$hasImg,$now,$qSetId)
    {
        $query = "UPDATE imas_questionset SET description='{$qd['description']}',author='{$qd['author']}',";
        $query.= "qtype='{$qd['qtype']}',control='{$qd['control']}',qcontrol='{$qd['qcontrol']}',qtext='{$qd['qtext']}',";
        $query.= "answer='{$qd['answer']}',extref='{$qd['extref']}',license='{$qd['license']}',ancestorauthors='{$qd['ancestorauthors']}',otherattribution='{$qd['otherattribution']}',";
        $query.= "solution='{$qd['solution']}',solutionopts='{$qd['solutionopts']}',";
        $query.= "adddate=$now,lastmoddate=$now,hasimg=$hasImg WHERE id='$qSetId'";
        $data = \Yii::$app->db->createCommand($query)->execute();
        return $data;
    }

    public static function UpdateQuestionsetDataIfNotAdmin($qd,$hasImg,$now,$qSetId,$user,$isAdmin,$num)
    {
        $query = "UPDATE imas_questionset SET description='{$qd['description']}',author='{$qd['author']}',";
        $query.= "qtype='{$qd['qtype']}',control='{$qd['control']}',qcontrol='{$qd['qcontrol']}',qtext='{$qd['qtext']}',";
        $query.= "answer='{$qd['answer']}',extref='{$qd['extref']}',license='{$qd['license']}',ancestorauthors='{$qd['ancestorauthors']}',otherattribution='{$qd['otherattribution']}',";
        $query.= "solution='{$qd['solution']}',solutionopts='{$qd['solutionopts']}',adddate=$now,lastmoddate=$now,hasimg=$hasImg WHERE id='$qSetId'";
        if($num == 0)
        {
            if (!$isAdmin)
            {
                $query.= " AND ownerid=$user->id";
            }
        }
        else if($num == 1)
        {
            if (!$isAdmin)
            {
                $query .= " AND (ownerid='$user->id' OR userights>3)";
            }
        }
        $data = \Yii::$app->db->createCommand($query)->execute();
        return $data;
    }
    public function InsertData($now,$user,$qd,$importUIdVal,$hasImg,$rights)
    {
        $this->uniqueid = $qd['uqid'];
        $this->adddate  =$now;
        $this->lastmoddate = $now;
        $this->ownerid = $user->id;
        $this->userights = $rights;
        $this->description = $qd['description'];
        $this->author = $qd['author'];
        $this->qtype = $qd['qtype'];
        $this->control = $qd['control'];
        $this->qcontrol = $qd['qcontrol'];
        $this->qtext = $qd['qtext'];
        $this->answer = $qd['answer'];
        $this->solution = $qd['solution'];
        $this->extref = $qd['extref'];
        $this->solutionopts = $qd['solutionopts'];
        $this->license = $qd['license'];
        $this->ancestorauthors = $qd['ancestorauthors'];
        $this->otherattribution = $qd['otherattribution'];
        $this->importuid = $importUIdVal;
        $this->hasimg = $hasImg;
        $this->save();
        return $this->id;
    }

    public static function setDeletedByIdsAndOwnerId($removeList, $userId){
        $data = QuestionSet::getIdByIDAndOwnerId($removeList, $userId);
        if($data){
            foreach($data as $singleData){
                $singleData->deleted = AppConstant::NUMERIC_ONE;
                $singleData->save();
            }
        }
    }
    public static function setOwnerIdById($id, $ownerId){
        $data = QuestionSet::findOne(['id' => $id]);
        if($data){
            $data->ownerid = $ownerId;
            $data->save();
        }
    }

    public static function setOwnerIdByIds($removeList, $ownerId){
        $data = QuestionSet::getByIdUsingInClause($removeList);
        if($data){
            foreach($data as $singleData){
                $singleData->ownerid = $ownerId;
                $singleData->save();
            }
        }
    }

    public static function setOwnerIdByIdsAndOwnerId($removeList, $userId, $ownerId){
        $data = QuestionSet::getIdByIDAndOwnerId($removeList, $userId);
        if($data){
            foreach($data as $singleData){
                $singleData->ownerid = $ownerId;
                $singleData->save();
            }
        }
    }

    public static function getSelectedDataByQuesSetId($id)
    {
        return QuestionSet::find()->select('description,userights,qtype,control,qcontrol,qtext,answer,hasimg,ancestors,ancestorauthors,license,author')->where(['id' => $id])->one();
    }

    public static function setLicense($selLicense, $qtochg){
        $data = QuestionSet::getByIdUsingInClause($qtochg);
        if($data){
            foreach($data as $singleData){
                $singleData->license = $selLicense;
                $singleData->save();
            }
        }
    }

    public static function setLicenseByUserId($selLicense, $qtochg, $userId){
        $data = QuestionSet::getIdByQidAndOwnerId($qtochg, $userId);
        if($data){
            foreach($data as $singleData){
                $singleData->license = $selLicense;
                $singleData->save();
            }
        }
    }

    public static function getIdByQidAndOwnerId($removeList, $userId){
        return QuestionSet::find()->where(['IN', 'id', $removeList])->andWhere(['ownerid' => $userId])->all();
    }

    public static function setOtherAttribution($attribute, $qtochg){
        $data = QuestionSet::getByIdUsingInClause($qtochg);
        if($data){
            foreach($data as $singleData){
                $singleData->otherattribution = $attribute;
                $singleData->save();
            }
        }
    }

    public static function setOtherAttributionByUserId($attribute, $qtochg, $userId){
        $data = QuestionSet::getIdByQidAndOwnerId($qtochg, $userId);
        if($data){
            foreach($data as $singleData){
                $singleData->otherattribution = $attribute;
                $singleData->save();
            }
        }
    }

    public static function setOtherAttributionById($attr, $id){
        $data = QuestionSet::getByQuesSetId($id);
        if($data){
            $data->otherattribution = $attr;
            $data->save();
        }
    }

    public static function setUserRightsByList($ids, $rights){
        $data = QuestionSet::getByIdUsingInClause($ids);
        if($data){
            foreach($data as $singleData){
                $singleData->userights = $rights;
                $singleData->save();
            }
        }
    }

    public static function setUserRightsByListAndUserId($qtochg, $rights, $userId){
        $data = QuestionSet::getIdByQidAndOwnerId($qtochg, $userId);
        if($data){
            foreach($data as $singleData){
                $singleData->userights = $rights;
                $singleData->save();
            }
        }
    }

    public static function getQidByQSetIdAndGroupId($id, $groupId){
        $data = QuestionSet::find()->select('imas_questionset.id')->from('imas_questionset,imas_users')
            ->where(['imas_questionset.id' => $id])->andWhere('imas_questionset.ownerid=imas_users.id')
            ->andWhere(['imas_users.groupid' => $groupId])->all();
        return $data;
    }

    public static function setDeletedByIdAndOwnerId($id, $userId){
        $data = QuestionSet::getIdByQidAndOwner($id, $userId);
        if($data){
            foreach($data as $singleData){
                $singleData->deleted = AppConstant::NUMERIC_ONE;
                $singleData->save();
            }
        }
    }
    public static function getIdByQidAndOwner($id, $userId){
        return QuestionSet::find()->where(['id' => $id])->andWhere(['ownerid' => $userId])->all();
    }

    public static function setOwnerIdByIdAndOwnerId($id, $userId, $ownerId){
        $data = QuestionSet::getIdByQidAndOwner($id, $userId);
        if($data){
            foreach($data as $singleData){
                $singleData->ownerid = $ownerId;
                $singleData->save();
            }
        }
    }

    public static function getQuestionSetDataByJoin($searchlikes, $isAdmin, $searchall, $hidepriv, $llist, $searchmine, $isGrpAdmin, $userId, $groupId)
        {
        $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid,imas_questionset.description,imas_questionset.userights,imas_questionset.lastmoddate,imas_questionset.extref,imas_questionset.replaceby,";
        $query .= "imas_questionset.qtype,imas_users.firstName,imas_users.lastName,imas_users.groupid,imas_library_items.libid,imas_library_items.junkflag, imas_library_items.id AS libitemid ";
        $query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.deleted=0 AND $searchlikes ";
        $query .= "imas_library_items.qsetid=imas_questionset.id AND imas_questionset.ownerid=imas_users.id ";

        if ($isAdmin) {
            if ($searchall==0) {
                $query .= "AND imas_library_items.libid IN ($llist)";
            }
            if ($hidepriv==1) {
                $query .= " AND imas_questionset.userights>0";
            }
            if ($searchmine==1) {
                $query .= " AND imas_questionset.ownerid='$userId'";
            }
        } else if ($isGrpAdmin) {
            $query .= "AND (imas_users.groupid='$groupId' OR imas_questionset.userights>0) ";
            $query .= "AND (imas_library_items.libid > 0 OR imas_users.groupid='$groupId')";
            if ($searchall==0) {
                $query .= " AND imas_library_items.libid IN ($llist)";
            }
            if ($searchmine==1) {
                $query .= " AND imas_questionset.ownerid='$userId'";
            }
        } else {
            $query .= "AND (imas_questionset.ownerid='$userId' OR imas_questionset.userights>0) ";
            $query .= "AND (imas_library_items.libid > 0 OR imas_questionset.ownerid='$userId')";
            if ($searchall==0) {
                $query .= " AND imas_library_items.libid IN ($llist)";
            }
            if ($searchmine==1) {
                $query .= " AND imas_questionset.ownerid='$userId'";
            }
        }
        $query.= " ORDER BY imas_library_items.libid,imas_library_items.junkflag,imas_questionset.replaceby,imas_questionset.id LIMIT 500";
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getDataToExportLib($qList,$nonPrivate,$call)
    {
        $query = "SELECT * FROM imas_questionset WHERE id IN ($qList)";
        if ($nonPrivate)
        {
            $query.= " AND userights>0";
        }
        if($call == 0)
        {
            $query.= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
        }
        else if($call == 1)
        {
            $query.= " ORDER BY uniqueid";
        }
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function getUniqueIdToExportLib($includedGs)
    {
        $query = new Query();
        $query->select(['id', 'uniqueid'])
            ->from('imas_questionset')
            ->where(['IN', 'id', $includedGs]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function getLicenseData($ids)
    {
        return QuestionSet::find()->select('id,uniqueid,author,ancestorauthors,license,otherattribution')->where(['IN','id',$ids])->all();
    }
    public static function getDataForImportQSet($uniqueId)
    {
        $query = new Query();
        $query->select(['id','uniqueid','adddate','lastmoddate'])
            ->from('imas_questionset')
            ->where(['IN', 'uniqueid', $uniqueId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function updateDataForImportQSet($qdata,$now,$qSetId,$hasImg)
    {
        $query = "UPDATE imas_questionset SET description='{$qdata['description']}',author='{$qdata['author']}',";
        $query .= "qtype='{$qdata['qtype']}',control='{$qdata['control']}',qcontrol='{$qdata['qcontrol']}',qtext='{$qdata['qtext']}',";
        $query .= "answer='{$qdata['answer']}',extref='{$qdata['extref']}',license='{$qdata['license']}',ancestorauthors='{$qdata['ancestorauthors']}',otherattribution='{$qdata['otherattribution']}',";
        $query .= "solution='{$qdata['solution']}',solutionopts='{$qdata['solutionopts']}',";
        $query .= "adddate=$now,lastmoddate=$now,hasimg=$hasImg WHERE id='$qSetId'";
        $data = \Yii::$app->db->createCommand($query)->execute();
        return $data;
    }

    public static function updateIdIn($qlist)
    {
        $query = "UPDATE imas_questionset SET deleted=1 WHERE id IN ($qlist)";
        \Yii::$app->db->createCommand($query)->execute();
    }

    public static function getById($clist)
    {
        $result = QuestionSet::find()->select(['id','description','qtype'])->where(['IN','id',$clist])->all();
        return $result;
    }

    public static function getByIdLike($clist)
    {
        $query = "SELECT * FROM imas_questionset WHERE id IN ($clist)";
        $query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }
    public static function updateId($qlist)
    {
       $query = "UPDATE imas_questionset SET deleted=1 WHERE id='$qlist'";
       return \Yii::$app->db->createCommand($query)->execute();
    }

    public static function updateInAdmin($qsetid,$isadmin, $userid)
    {
        $query = "UPDATE imas_questionset SET deleted=1 WHERE id='$qsetid'";
        if (!$isadmin) {
            $query .= " AND ownerid='$userid'";
        }
        return \Yii::$app->db->createCommand($query)->execute();
    }

    public static function getByOrUserId($qsetid,$groupid)
    {
        $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
        $query .= "WHERE iq.id='$qsetid' AND iq.ownerid=imas_users.id AND (imas_users.groupid='$groupid' OR iq.userights>3)";
        return \Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function updateQSetId($params,$now,$qSetId){
        $query = "UPDATE imas_questionset SET description='{$params['description']}',";
        $query .= "qtype='{$params['qtype']}',control='{$params['control']}',qcontrol='{$params['qcontrol']}',";
        $query .= "qtext='{$params['qtext']}',answer='{$params['answer']}',lastmoddate=$now ";
        $query .= "WHERE id='$qSetId'";
        return \Yii::$app->db->createCommand($query)->execute();
    }

    public static function updateQSetAdmin($params,$now,$qSetId,$isadmin,$userid)
    {
        $query = "UPDATE imas_questionset SET description='{$params['description']}',";
        $query .= "qtype='{$params['qtype']}',control='{$params['control']}',qcontrol='{$params['qcontrol']}',";
        $query .= "qtext='{$params['qtext']}',answer='{$params['answer']}',lastmoddate=$now ";
        $query .= "WHERE id='$qSetId'";
        if (!$isadmin)
        {
            $query .= " AND (ownerid='$userid' OR userights>3);";
        }
        return \Yii::$app->db->createCommand($query)->execute();
    }

    public static function getByUIdQSetId($qsetid)
    {
        $query = new Query();
        $query ->select(['imas_questionset.*', 'imas_users.groupid'])
            ->from('imas_questionset, imas_users')
            ->where('imas_questionset.ownerid=imas_users.id');
        $query->andWhere(['imas_questionset.id' => $qsetid]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public static function getAncestor($templateId)
    {
        $query = new Query();
        $query ->select(['ancestors'])
            ->from('imas_questionset')
            ->where(['id' => $templateId]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public function insertDataForModTutorial($uQid,$now,$params,$user,$qType,$code,$qText,$ancestors)
    {
        $this->uniqueid = $uQid;
        $this->adddate = $now;
        $this->lastmoddate = $now;
        $this->description = $params['description'];
        $this->ownerid = $user['id'];
        $this->author = $params['author'];
        $this->userights = $params['userights'];
        $this->qtype = $qType;
        $this->control = $code;
        $this->qtext = $qText;
        $this->ancestors = $ancestors;
        $this->save();
        return $this->id;
    }

    public static function updateQSetForTutorial($qSetId,$makeLocal)
    {
        $data = QuestionSet::find()->where(['id' => $makeLocal])->one();
        if($data)
        {
            $data->questionsetid = $qSetId;
            $data->save();
        }
    }
    public static function updateQueSet($params,$now,$qType,$code,$id,$qText)
    {
        $data = QuestionSet::find()->where(['id' => $id])->one();
        if($data)
        {
            $data->lastmoddate = $now;
            $data->description = $params['description'];
            $data->author = $params['author'];
            $data->userights = $params['userights'];
            $data->qtype = $qType;
            $data->control = $code;
            $data->qtext = $qText;
            $data->save();
        }

    }
}