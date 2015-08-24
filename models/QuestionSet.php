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
        return static::findOne(['id' => $id]);
    }
    public static function getById($id)
    {
        $query = "SELECT qtext FROM imas_questionset WHERE id= 1";
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($query);
        $qdata = $command->queryAll();
        return $qdata;
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
        if($questionSet){
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
        $this->uniqueid = isset($params['uniqueid']) ? $params['uniqueid'] : null;
        $this->adddate = isset($params['adddate']) ? $params['adddate'] : null;
        $this->lastmoddate = isset($params['lastmoddate']) ? $params['lastmoddate'] : null;
        $this->description = isset($params['description']) ? $params['description'] : null;
        $this->ownerid = isset($params['ownerid']) ? $params['ownerid'] : null;
        $this->author = isset($params['author']) ? $params['author'] : null;
        $this->userights = isset($params['userights']) ? $params['userights'] : null;
        $this->license = isset($params['license']) ? $params['license'] : null;
        $this->otherattribution = isset($params['otherattribution']) ? $params['otherattribution'] : null;
        $this->qtype = isset($params['qtype']) ? $params['qtype'] : null;
        $this->control = isset($params['control']) ? $params['control'] : null;
        $this->qcontrol = isset($params['qcontrol']) ? $params['qcontrol'] : null;
        $this->qtext = isset($params['qtext']) ? $params['qtext'] : null;
        $this->answer = isset($params['answer']) ? $params['answer'] : null;
        $this->hasimg = isset($params['hasimg']) ? $params['hasimg'] : null;
        $this->ancestors = isset($params['ancestors']) ? $params['ancestors'] : null;
        $this->ancestorauthors = isset($params['ancestorauthors']) ? $params['ancestorauthors'] : null;
        $this->extref = isset($params['extref']) ? $params['extref'] : null;
        $this->replaceby = isset($params['replaceby']) ? $params['replaceby'] : null;
        $this->solution = isset($params['solution']) ? $params['solution'] : null;
        $this->solutionopts = isset($params['solutionopts']) ? $params['solutionopts'] : null;
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
} 