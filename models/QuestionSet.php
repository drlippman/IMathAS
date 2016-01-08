<?php

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

    public static function getByUserIdJoin($searchall,$userid,$llist,$searchmine,$searchlikes)
    {
        $placeholders= "";
        if($llist)
        {
            foreach($llist as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.extref,imas_library_items.libid,imas_questionset.ownerid,imas_questionset.avgtime,imas_questionset.solution,imas_questionset.solutionopts,imas_library_items.junkflag, imas_library_items.id AS libitemid,imas_users.groupid ";
        $query .= "FROM imas_questionset JOIN imas_library_items ON imas_library_items.qsetid=imas_questionset.id ";
        $query .= "JOIN imas_users ON imas_questionset.ownerid=imas_users.id WHERE imas_questionset.deleted=0 AND imas_questionset.replaceby=0 AND $searchlikes "; //imas_questionset.description LIKE '%$safesearch%' ";
        $query .= " (imas_questionset.ownerid=':userid' OR imas_questionset.userights>0)";

        if ($searchall==0) {
            $query .= "AND imas_library_items.libid IN ($placeholders)";
        }
        if ($searchmine==1) {
            $query .= " AND imas_questionset.ownerid=':userid'";
        } else {
            $query .= " AND (imas_library_items.libid > 0 OR imas_questionset.ownerid=':userid') ";
        }
        $query .= " ORDER BY imas_library_items.libid,imas_library_items.junkflag,imas_questionset.id";
        $command = \Yii::$app->db->createCommand($query);
        $command->bindValue('userid', $userid);
        if ($searchall==0) {
            foreach($llist as $i => $parent){
                $command->bindValue(":".$i, $parent);
            }
        }
        $data = $command->queryAll();
        return $data;
    }

    public static function getByUserId($aid,$userid,$existingqlist){
        $query = \Yii::$app->db->createCommand("SELECT a.questionsetid, count( DISTINCT a.assessmentid ) as qcnt,
						imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.ownerid
						FROM imas_questions AS a
						JOIN imas_questions AS b ON a.assessmentid = b.assessmentid
						JOIN imas_questions AS c ON b.questionsetid = c.questionsetid
						AND c.assessmentid =':aid'
						JOIN imas_questionset  ON a.questionsetid=imas_questionset.id
						AND (imas_questionset.ownerid=':userid' OR imas_questionset.userights>0)
						AND imas_questionset.deleted=0
						AND imas_questionset.replaceby=0
						WHERE a.questionsetid NOT IN ($existingqlist)
						GROUP BY a.questionsetid ORDER BY qcnt DESC LIMIT 100")->bindValues([':aid' => $aid,':userid' => $userid])->queryAll();
        return $query;
    }

    public static function getByGroupId($id,$groupId){
        $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
        $query .= "WHERE iq.id='$id' AND iq.ownerid=imas_users.id AND (imas_users.groupid=':groupId' OR iq.userights>2)";
        $data = \Yii::$app->db->createCommand($query)->bindValue(':groupId',$groupId)->queryAll();
        return $data;
    }

    public static function getByUserIdGroupId($id,$userId,$groupId){
        $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
        $query .= "WHERE iq.id='$id' AND iq.ownerid=imas_users.id AND (iq.ownerid=':userId' OR (iq.userights=3 AND imas_users.groupid='$groupId') OR iq.userights>3)";
        $data = \Yii::$app->db->createCommand($query)->bindValue(':userId',$userId)->queryAll();
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

    public static function getByQSetIdJoin($id)
    {
        $query = new Query();
        $query->select('imas_questionset.*,imas_users.groupid')->from('imas_questionset')->join('INNER JOIN','imas_users','imas_questionset.ownerid=imas_users.id')
            ->where('imas_questionset.id= :id');
        $command = $query->createCommand();
        $data = $command->bindValue(':id',$id)->queryOne();
        return $data;
    }

    public static function getUserAndQuestionSetJoin($id){
        $query = new Query();
        $query->select('imas_users.email,imas_questionset.* ')->from('imas_users')->join('INNER JOIN','imas_questionset','imas_users.id=imas_questionset.ownerid')
            ->where('imas_questionset.id= :id');
        $command = $query->createCommand();
        $data = $command->bindValue(':id',$id)->queryOne();
        return $data;
    }

    public static function getDataForCopyCourse($ctc)
    {
        $query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
        $query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
        $query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
        $query .= "imas_assessments.courseid= :ctc AND imas_questionset.replaceby>0";
        $data = \Yii::$app->db->createCommand($query)->bindValue(':ctc',$ctc)->queryOne();
        return $data;
    }

    public static function getByQuestionId($questionList)
    {
        $placeholders= "";
        if($questionList)
        {
            foreach($questionList as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
//        $questionList = implode(',',$questionList);
        $query  = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questionset.id AS qid,imas_questions.withdrawn,imas_questionset.qtype,imas_questionset.control,imas_questions.showhints,imas_questionset.extref ";
		$query .= "FROM imas_questionset,imas_questions
		          WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN ($placeholders)";
        $command = \Yii::$app->db->createCommand($query);
        foreach($questionList as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data = $command->queryAll();
        return $data;
    }

    public  static function getQuestionSetData($ids)
    {
        return self::find()->select('id,description,extref,qtype,control')->where(['IN','id',$ids])->all();
    }

    public static function updateVideoId($from,$to)
    {
        $query = "UPDATE imas_questionset SET extref=REPLACE(extref,':from',':to') WHERE extref LIKE ':fromLike'";
        $connection=\Yii::$app->db;
        $command=$connection->createCommand($query);
        $command->bindValues([':from' => $from, ':to' => $to, ':fromLike' => "%".$from."%"]);
        $rowCount=$command->execute();
        return $rowCount;
    }

    public static function getExternalRef()
    {
        $query = "SELECT uniqueid,lastmoddate,extref FROM imas_questionset WHERE extref<>''";
        return $data = \Yii::$app->db->createCommand($query)->queryAll();
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
        return self::find()->select('id,uniqueid,lastmoddate,extref')->all();
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
        return self::find()->select('id,questionsetid')->all();
    }

    public static function updateAvgTime($avg,$qsid)
    {
        $questionSetId = QuestionSet::getByQuesSetId($qsid);
        $questionSetId->avgtime = $avg;
        $questionSetId->save();
    }

    public static function getIdAndAvgTime()
    {
        return self::find()->select(['avgtime', 'id'])->all();
    }
    public static function getQuestionData($aid)
    {
        $query = new Query();
        $query->select('iq.id,iq.category,iqs.description')->from('imas_questions AS iq')
            ->join('INNER JOIN','imas_questionset as iqs','iq.questionsetid=iqs.id')->where('iq.assessmentid = :aid');
        return $query->createCommand()->bindValue(':aid',$aid)->queryAll();
    }

    public static function getQtext($id){
        return QuestionSet::find()->select('qtext')->where(['id' => $id])->one();
    }
    public static function getControlAndQType($id){
        return QuestionSet::find()->select('control,qtype')->where(['id' => $id])->one();
    }

    public static function findDataToImportLib($qIdsToCheck)
    {
        //TODO: fix below query
        $placeholders= "";
        if($qIdsToCheck)
        {
            foreach($qIdsToCheck as $i => $singleQId){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($placeholders) AND (control LIKE '%includecodefrom(UID%' OR qtext LIKE '%includeqtextfrom(UID%')";
        $command = \Yii::$app->db->createCommand($query);
        foreach($qIdsToCheck as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data = $command->queryAll();
        return $data;
    }
    public static function getUniqueId($includedList)
    {
        return self::find()->select('id,uniqueid')->where('IN','uniqueid',$includedList)->all();
    }
    public static function getDataToImportLib($qIdsToCheck)
    {
        return self::find()->select('id,control,qtext')->where('IN','id',$qIdsToCheck)->all();
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
                ->andWhere('imas_users.groupid=:groupId', [':groupId' => $groupId])->all();
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
        return self::find()->select(['id', 'adddate','lastmoddate'])
            ->where(['uniqueid' => $UId])->one();
    }

    public static function getQSetAndUserData($qSetId,$groupId)
    {
        $data = new Query();
        $data->select(['imas_questionset.id'])
            ->from(['imas_questionset','imas_users'])
            ->where('imas_questionset.id = :qSetId')
            ->andWhere(['imas_questionset.ownerid=imas_users.id'])
            ->andWhere('imas_users.groupid = :groupId');
        $command = $data->createCommand();
        $data = $command->bindValues([':qSetId' => $qSetId,':groupId' => $groupId])->queryAll();
        return $data;
    }

    public static function UpdateQuestionsetData($qd,$hasImg,$now,$qSetId)
    {
        $questionSetId = QuestionSet::getByQuesSetId($qSetId);
        if($questionSetId)
        {
         return $questionSetId->updateCounters(['description' => strval($qd['description']),'hasimg' => intval($hasImg),'author' => strval($qd['author']), 'qtype' => strval($qd['qtype']),
                'control' => strval($qd['control']), 'qcontrol' => strval($qd['qcontrol']), 'qtext' => strval($qd['qtext']),
                'answer' => strval($qd['answer']), 'extref' => strval($qd['extref']), 'license' => intval($qd['license']), 'ancestorauthors' => strval($qd['ancestorauthors']),
                'otherattribution' => strval($qd['otherattribution']), 'solution' => strval($qd['solution']), 'solutionopts' => intval($qd['solutionopts']), 'adddate' => intval($now),
                'lastmoddate' => intval($now)]);
        }
    }

    public static function UpdateQuestionsetDataIfNotAdmin($qd,$hasImg,$now,$qSetId,$user,$isAdmin,$num)
    {
        $query = QuestionSet::getByQuesSetId($qSetId);
        if($num == 0)
        {
            if (!$isAdmin)
            {
                $query = QuestionSet::getIdByQidAndOwner($qSetId,$user->id);
            }
        }
        else if($num == 1)
        {
            if (!$isAdmin)
            {
                $query = QuestionSet::find()->where(['id' => $qSetId])->andWhere(['ownerid' => $user->id])->orWhere(['>','userights', AppConstant::NUMERIC_THREE])->one();
            }
        }
        $query->description = strval($qd['description']);
        $query->author = strval($qd['author']);
        $query->qtype = strval($qd['qtype']);
        $query->control = strval($qd['control']);
        $query->qcontrol = $qd['qcontrol'];
        $query->qtext = strval($qd['qtext']);
        $query->answer = strval($qd['answer']);
        $query->extref = strval($qd['extref']);
        $query->license = intval($qd['license']);
        $query->ancestorauthors = strval($qd['ancestorauthors']);
        $query->otherattribution = strval($qd['otherattribution']);
        $query->solution = strval($qd['solution']);
        $query->solutionopts = intval($qd['solutionopts']);
        $query->adddate = intval($now);
        $query->lastmoddate = intval($now);
        $query->hasimg = intval($hasImg);
        $query->save();
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
            ->where('imas_questionset.id=:id',[':id' => $id])->andWhere('imas_questionset.ownerid=imas_users.id')
            ->andWhere('imas_users.groupid=:groupId',[':groupId' => $groupId])->all();
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
            //TODO: fix below query
            $placeholders= "";
            if($llist)
            {
                foreach($llist as $i => $singleList){
                    $placeholders .= ":".$i.", ";
                }
                $placeholders = trim(trim(trim($placeholders),","));
            }

            $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid,imas_questionset.description,imas_questionset.userights,imas_questionset.lastmoddate,imas_questionset.extref,imas_questionset.replaceby,";
        $query .= "imas_questionset.qtype,imas_users.firstName,imas_users.lastName,imas_users.groupid,imas_library_items.libid,imas_library_items.junkflag, imas_library_items.id AS libitemid ";
        $query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.deleted=0 AND $searchlikes ";
        $query .= "imas_library_items.qsetid=imas_questionset.id AND imas_questionset.ownerid=imas_users.id ";

        if ($isAdmin) {
            if ($searchall==0) {
                $query .= "AND imas_library_items.libid IN ($placeholders)";
            }
            if ($hidepriv==1) {
                $query .= " AND imas_questionset.userights>0";
            }
            if ($searchmine==1) {
                $query .= " AND imas_questionset.ownerid=:userId";
            }
        } else if ($isGrpAdmin) {
            $query .= "AND (imas_users.groupid=:groupId OR imas_questionset.userights>0) ";
            $query .= "AND (imas_library_items.libid > 0 OR imas_users.groupid=:groupId)";
            if ($searchall==0) {
                $query .= " AND imas_library_items.libid IN ($placeholders)";
            }
            if ($searchmine==1) {
                $query .= " AND imas_questionset.ownerid=:userId";
            }
        } else {
            $query .= "AND (imas_questionset.ownerid=:userId OR imas_questionset.userights>0) ";
            $query .= "AND (imas_library_items.libid > 0 OR imas_questionset.ownerid=:userId)";
            if ($searchall==0) {
                $query .= " AND imas_library_items.libid IN ($placeholders)";
            }
            if ($searchmine==1) {
                $query .= " AND imas_questionset.ownerid=:userId";
            }
        }
        $query.= " ORDER BY imas_library_items.libid,imas_library_items.junkflag,imas_questionset.replaceby,imas_questionset.id LIMIT 500";
        $command = \Yii::$app->db->createCommand($query);
            if ($isAdmin) {
                if ($searchall==0) {
                    foreach($llist as $i => $parent){
                        $command->bindValue(":".$i, $parent);
                    }
                }

                if ($searchmine==1) {
                    $command->bindValue(':userId',$userId);
                }
            } else if ($isGrpAdmin) {
                $command->bindValue(':groupId', $groupId);
                if ($searchall==0) {
                    foreach($llist as $i => $parent){
                        $command->bindValue(":".$i, $parent);
                    }
                }
                if ($searchmine==1) {
                    $command->bindValue(':userId', $userId);
                }
            } else {
                $command->bindValue(':userId',$userId);
                if ($searchall==0) {
                    foreach($llist as $i => $parent){
                        $command->bindValue(":".$i, $parent);
                    }
                }
                if ($searchmine==1) {
                    $command->bindValue(':userId', $userId);
                }
            }
        $data = $command->queryAll();
        return $data;
    }

    public static function getDataToExportLib($qList,$nonPrivate,$call)
    {
        $placeholders= "";
        if($qList)
        {
            foreach($qList as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT * FROM imas_questionset WHERE id IN ($placeholders)";
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
        $command = \Yii::$app->db->createCommand($query);
        foreach($qList as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $data = $command->queryAll();
        return $data;
    }
    public static function getUniqueIdToExportLib($includedGs)
    {
        return self::find()->select(['id', 'uniqueid'])->where(['IN', 'id', $includedGs])->all();
    }
    public static function getLicenseData($ids)
    {
        return QuestionSet::find()->select('id,uniqueid,author,ancestorauthors,license,otherattribution')->where(['IN','id',$ids])->all();
    }
    public static function getDataForImportQSet($uniqueId)
    {
        return self::find()->select(['id','uniqueid','adddate','lastmoddate'])->where(['IN', 'uniqueid', $uniqueId])->all();
    }

    public static function updateIdIn($qlist)
    {
        $query = QuestionSet::find()->where('IN', 'id', $qlist)->all();
        if($query)
        {
            foreach($query as $qSet)
            {
                $qSet->deleted = AppConstant::NUMERIC_ONE;
                $qSet->save();
            }
        }
    }

    public static function getById($clist)
    {
        $result = QuestionSet::find()->select(['id','description','qtype'])->where(['IN','id',$clist])->all();
        return $result;
    }

    public static function getByIdLike($clist)
    {
        $placeholders= "";
        if($clist)
        {
            foreach($clist as $i => $singleThread){
                $placeholders .= ":".$i.", ";
            }
            $placeholders = trim(trim(trim($placeholders),","));
        }
        $query = "SELECT * FROM imas_questionset WHERE id IN ($placeholders)";
        $query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
        $command = \Yii::$app->db->createCommand($query);
        foreach($clist as $i => $parent){
            $command->bindValue(":".$i, $parent);
        }
        $command->queryAll();
    }
    public static function updateId($qlist)
    {
        $questionSet = QuestionSet::find()->where('id', $qlist)->all();
        if($questionSet)
        {
            foreach($questionSet as $qSet)
            {
                $qSet->deleted =AppConstant::NUMERIC_ONE;
                $qSet->save();
            }

        }
    }

    public static function updateInAdmin($qsetid,$isadmin, $userid)
    {
        $questionSet = QuestionSet::find()->where('id',$qsetid)->all();

        if (!$isadmin) {
            $questionSet = QuestionSet::getIdByQidAndOwner($qsetid,$userid);
        }
        foreach($questionSet as $qSet)
        {
            $qSet->deleted = AppConstant::NUMERIC_ONE;
            $qSet->save();
        }
    }

    public static function getByOrUserId($qsetid,$groupid)
    {
        $query = "SELECT iq.id FROM imas_questionset AS iq,imas_users ";
        $query .= "WHERE iq.id=':qsetid' AND iq.ownerid=imas_users.id AND (imas_users.groupid=':groupid' OR iq.userights > 3)";
        return \Yii::$app->db->createCommand($query)->bindValues([':qsetid' => $qsetid,':groupid' => $groupid])->queryAll();
    }

    public static function updateQSetId($params,$now,$qSetId){
        $questionSet = QuestionSet::find()->where(['id', $qSetId])->all();
        if($questionSet)
        {
            foreach($questionSet as $qSet)
            {
                $qSet->description = strval($params['description']);
                $qSet->qtype = strval($params['qtype']);
                $qSet->control = strval($params['control']);
                $qSet->qcontrol = strval($params['qcontrol']);
                $qSet->qtext = strval($params['qtext']);
                $qSet->answer = strval($params['answer']);
                $qSet->lastmodedate = intval($now);
                $qSet->save();
            }
        }
    }

    public static function updateQSetAdmin($params,$now,$qSetId,$isadmin,$userid)
    {
        $questionSet = QuestionSet::find()->where('id',$qSetId)->all();
        if (!$isadmin)
        {
            $questionSet = QuestionSet::getIdByQidAndOwner($qSetId, $userid);
        }

        foreach($questionSet as $qSet)
        {
            $qSet->description = strval($params['description']);
            $qSet->qtype = strval($params['qtype']);
            $qSet->control = strval($params['control']);
            $qSet->qcontrol = strval($params['qcontrol']);
            $qSet->qtext = strval($params['qtext']);
            $qSet->answer = strval($params['answer']);
            $qSet->lastmoddate = intval($now);
            $qSet->save();
        }
    }

    public static function getByUIdQSetId($qsetid)
    {
        $query = new Query();
        $query ->select(['imas_questionset.*', 'imas_users.groupid'])
            ->from('imas_questionset, imas_users')
            ->where('imas_questionset.ownerid=imas_users.id');
        $query->andWhere('imas_questionset.id = :qsetid');
        $command = $query->createCommand();
        $data = $command->bindValue(':qsetid',$qsetid)->queryOne();
        return $data;
    }

    public static function getAncestor($templateId)
    {
        return self::find()->select(['ancestors'])->where(['id' => $templateId])->one();

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

    public static function setBrokenFlag($flag, $qsetid){
        $data = QuestionSet::findOne(['id' => $qsetid]);
        if($data){
            $data->broken = $flag;
            $data->save();
        }
        return count($data);
    }

    public static function getOwnerId($id){
        return QuestionSet::find()->select('ownerid')->where(['id' => $id])->all();
    }

    public static function getExtRef($id){
        return QuestionSet::find()->select('extref')->where(['id' => $id])->one();
    }
    public static function getBrokenData()
    {
        $query = new Query();
        $query->select(['userights', 'COUNT(id)'])
            ->from('imas_questionset')->where(['broken'=> 1])->andWhere(['deleted' => AppConstant::NUMERIC_ZERO])->groupBy('userights');
        return $query->createCommand()->queryAll();
    }

    public static function getDescription($queId)
    {
        return self::find()->select('description')->where(['id' => $queId])->one();
    }

    public static function getDataById($qsetid)
    {
        return self::find()->select('qtype,control,qcontrol,qtext,answer,hasimg')->where(['id' => $qsetid])->one();
    }
}