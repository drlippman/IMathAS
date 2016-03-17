<?php
namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasDiags;
use Yii;
use yii\db\Query;


class Diags extends BaseImasDiags
{
    public static function getDiagnostic($myRights, $userId, $groupId)
    {
        $query = new Query();
        $query->select(['imas_diags.id', 'imas_diags.name', 'imas_diags.public'])
            ->from('imas_diags')
            ->join('JOIN',
                'imas_users',
                'imas_users.id=imas_diags.ownerid'
            );
        if ($myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            $query->andWhere('imas_diags.ownerid = :ownerid',[':ownerid' => $userId]);
        } else if ($myRights < AppConstant::NUMERIC_HUNDREAD)
        {
            $query->andWhere('imas_users.groupid = :groupid',[':groupid' => $groupId]);
        }
        $query->orderBy('imas_diags.name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getById($id)
    {
        return self::find()->select('sel1list,sel2name,sel2list,aidlist,forceregen')->where(['id' => $id])->one();
    }

    public function saveDiagnostic($params, $userId)
    {
        $this->ownerid = $userId;
        $this->name = trim($params['diagname']);
        $this->term = $params['term'];
        $this->cid = $params['cid'];
        $this->public = $params['public'];
        $this->ips = $params['iplist'];
        $this->pws = $params['pwlist'];
        $this->idprompt = $params['idprompt'];
        $this->sel1name = $params['sel1name'];
        $this->sel2name = $params['sel2name'];
        $this->entryformat = $params['entryformat'];
        $this->reentrytime = $params['reentrytime'];
        $this->save();
        return $this;
    }

    public static function getByDiagnoId($id)
    {
        return self::find()->select('name,term,cid,public,idprompt,ips,pws,sel1name,sel1list,entryformat,forceregen,reentrytime,ownerid')->where(['id' => $id])->one();
    }

    public static function updateDiagnostics($params)
    {
        $updateDiagno = Diags::findOne(['id' => $params['id']]);
        $updateDiagno->cid = $params['cid'];
        $updateDiagno->name = trim($params['diagname']);
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
        return $updateDiagno;
    }

    public static function getNameById($id)
    {
        return self::find()->select('name')->where(['id' => $id])->one();
    }

    public static function deleteDiagno($params)
    {
        $deleteId = Diags::find()->where(['id' => $params['id']])->one();
        if ($deleteId) {
            $deleteId->delete();
        }
    }

    public static function findByCourseID($courseId)
    {
        return Diags::find()->where(['cid' => $courseId])->one();
    }
    public static function getByIdAndName()
    {
        return self::find()->select(['id','name'])->where(['public' => AppConstant::NUMERIC_THREE])->orWhere(['public' => AppConstant::NUMERIC_SEVEN])->all();
    }

    public static function getAllDataById($diagid)
    {
        return static::find()->where(['id' => $diagid])->one();
    }

    public static function getByDiagId($diagId)
    {
        return self::find()->select(['entryformat','sel1list'])->where(['id' => $diagId])->one();
    }
}