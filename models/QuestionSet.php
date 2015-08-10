<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 11/5/15
 * Time: 1:10 PM
 */

namespace app\models;


use app\models\_base\BaseImasQuestionset;
use yii\db\Query;

class QuestionSet extends BaseImasQuestionset
{
    public static function getByQuesSetId($id)
    {
        return static::findAll(['id' => $id]);
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
} 