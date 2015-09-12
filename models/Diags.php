<?php
namespace app\models;


use app\components\AppUtility;
use app\components\AppConstant;
use app\models\_base\BaseImasCourses;
use app\models\_base\BaseImasDiags;
use Yii;
use yii\db\Exception;
use yii\db\Query;


class Diags extends BaseImasDiags
{
    public static function getDiagnostic($myRights, $userId, $groupId)
    {
        $query = new Query();
        $query	->select(['imas_diags.id','imas_diags.name','imas_diags.public'])
        ->from('imas_diags')
            ->join('JOIN',
                'imas_users',
                'imas_users.id=imas_diags.ownerid'
            );
        if ($myRights<75) {
            $query->andWhere(['imas_diags.ownerid' => $userId]);
        } else if ($myRights<100) {
            $query->andWhere(['imas_users.groupid=' => $groupId]);
        }
        $query->orderBy('imas_diags.name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getById($id)
    {
        $query = \Yii::$app->db->createCommand("SELECT sel1list,sel2name,sel2list,aidlist,forceregen FROM imas_diags WHERE id='$id'")->queryOne();
        return $query;
    }
    public function saveDiagnostic($params, $userId)
    {
        $this->ownerid = $userId;
        $this->name = $params['diagname'];
        $this->term = $params['term'];
        $this->cid = $params['cid'];
        $this->public = $params['public'];
        $this->ips = $params['iplist'];
        $this->pws = $params['pwlist'];
        $this->idprompt = $params['idprompt'];
        $this->sel1name = $params['sel1name'];
//        $this->sel1list = $params['sel1list'];
//        $this->aidlist = $params['aidlist'];
        $this->sel2name = $params['sel2name'];
//        $this->sel2list = $params['sel2list'];
        $this->entryformat = $params['entryformat'];
//        $this->forceregen = $params['forceregen'];
        $this->reentrytime = $params['reentrytime'];
        $this->save();
        return $this->id;
    }
    public static function getByDiagnoId($id)
    {
        $query = \Yii::$app->db->createCommand("SELECT name,term,cid,public,idprompt,ips,pws,sel1name,sel1list,entryformat,forceregen,reentrytime,ownerid FROM imas_diags WHERE id='$id'")->queryOne();
        return $query;
    }

    public static function updateDiagnostics($params)
    {
        $updateDiagno = Diags::findOne(['id' => $params['id']]);
        $updateDiagno->cid = $params['cid'];
        $updateDiagno->name = $params['diagname'];
        $updateDiagno->term = $params['term'];
        $updateDiagno->public = $params['public'];
        $updateDiagno->idprompt = $params['idprompt'];
        $updateDiagno->ips = $params['iplist'];
        $updateDiagno->pws = $params['pwlist'];
        $updateDiagno->sel1name = $params['sel1name'];
        $updateDiagno->sel1list = $params['sel1list'];
        $updateDiagno->aidlist = $params['aidlist'];
        $updateDiagno->sel2name = $params['sel2name'];
        $updateDiagno->sel2list = $params['sel2list'];
        $updateDiagno->entryformat = $params['entryformat'];
        $updateDiagno->forceregen = $params['forceregen'];
        $updateDiagno->reentrytime = $params['reentrytime'];
        $updateDiagno->save();
        return $updateDiagno->id;
    }

    public static function getNameById($id)
    {
        $query = new Query();
        $query	->select(['name'])
            ->from('imas_diags')
            ->where(['id' => $id]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public static function deleteDiagno($params)
    {
        $deleteId = Diags::find()->where(['id' => $params['id']])->one();
        if ($deleteId)
        {
            $deleteId->delete();
        }
    }

    public  static  function findByCourseID($courseId)
    {
        return Diags::find()->where(['cid' => $courseId])->one();
    }
}