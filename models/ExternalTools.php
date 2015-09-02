<?php
namespace app\models;
use app\components\AppUtility;
use app\models\_base\BaseImasExternalTools;
use yii\db\Query;

class ExternalTools extends BaseImasExternalTools
{
    public static function externalToolsData($courseId)
    {
        $toolsData = ExternalTools::findAll(['courseid' => $courseId]);
        return $toolsData;
    }

    public function updateExternalToolsData($params)
    {
        if($params['tool']) {
            $toolsData = ExternalTools::findOne(['id' => $params['tool']]);
            $toolsData->custom = $params['toolcustom'];
            $toolsData->url = $params['toolcustomurl'];
            $toolsData->save();
        }
    }

    public static function dataForCopy($toolidlist)
    {
        $query = \Yii::$app->db->createCommand("SELECT id,courseid,groupid,name,url,ltikey,secret,custom,privacy FROM imas_external_tools WHERE id IN ($toolidlist)")->queryAll();
        return $query;
    }
    public static function getId($courseId,$url)
    {
        $query  = \Yii::$app->db->createCommand("SELECT id FROM imas_external_tools WHERE url='" . addslashes($url) . "' AND courseid='$courseId'")->queryAll();
        return $query;
    }

    public function insertData($courseId,$groupid,$rowsub)
    {
           $this->courseid = $courseId;
           $this->groupid =  $groupid;
           $this->name =  $rowsub['name'];
           $this->url =  $rowsub['url'];
           $this->ltikey = $rowsub['ltikey'];
           $this->secret = $rowsub['secret'];
           $this->custom = $rowsub['custom'];
           $this->privacy = $rowsub['privacy'];
           $this->save();
        return $this->id;

    }

    public function saveExternalTool($courseId,$groupId,$params, $isTeacher, $isGroupAdmin, $isAdmin, $privacy)
    {
        $this->name =  $params['tname'];
        $this->url =  $params['url'];
        $this->ltikey = $params['key'];
        $this->secret = $params['secret'];
        $this->custom = $params['custom'];
        $this->privacy = $privacy;
        if ($isTeacher) {
            $this->groupid = $groupId;
            $this->courseid = $courseId;
        } else if ($isGroupAdmin || ($isAdmin && $params['scope'] == 1)) {
            $this->groupid = $groupId;
            $this->courseid = 0;
        } else {
            $this->groupid = 0;
            $this->courseid = 0;
        }
        $this->save();
    }
    public static function updateExternalToolByAdmin($params, $isAdmin,$attrValue,$attr, $privacy)
    {
        $updateExtTool = ExternalTools::find()->where(['id' => $params['id']])->andWhere([$attr => $attrValue])->one();
        if($updateExtTool)
        {
            $updateExtTool->name =  $params['tname'];
            $updateExtTool->url =  $params['url'];
            $updateExtTool->ltikey = $params['key'];
            $updateExtTool->secret = $params['secret'];
            $updateExtTool->custom = $params['custom'];
            $updateExtTool->privacy = $privacy;
            if($isAdmin)
            {
                if($params['scope'] == 0)
                {
                    $updateExtTool->groupid = 0;
                } else{
                    $updateExtTool->groupid = $params['groupId'];
                }
            }

        }
        $updateExtTool->save();
    }
    public static function updateExternalTool($params,$attr, $privacy)
    {
        $updateExtTool = ExternalTools::find()->where(['id' => $params['id']])->one();
        if($updateExtTool)
        {
            $updateExtTool->name =  $params['tname'];
            $updateExtTool->url =  $params['url'];
            $updateExtTool->ltikey = $params['key'];
            $updateExtTool->secret = $params['secret'];
            $updateExtTool->custom = $params['custom'];
            $updateExtTool->privacy = $privacy;
            $updateExtTool->groupid = $attr;
        }
        $updateExtTool->save();
    }

    public static function deleteById($id, $isTeacher, $isGrpAdmin, $courseId, $groupId)
    {
        $externalTool = ExternalTools::findOne($id);
        if($externalTool)
        {
            $externalTool->delete();
            if ($isTeacher) {
                $externalTool->courseid = $courseId;
            } else if ($isGrpAdmin) {
               $externalTool->groupid = $groupId;
            }
        }
    }

    public static function getByRights($id, $isTeacher, $courseId, $isGrpAdmin, $groupId)
    {
        $query = new Query();
        $query	->select(['name','url','ltikey','secret','custom','privacy','groupid'])
            ->from('imas_external_tools')
            ->where(['id' => $id]);
        if ($isTeacher) {
            $query->andWhere(['courseid' => $courseId]);
        } else if ($isGrpAdmin) {
            $query->andWhere(['groupid' => $groupId]);
        }
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public static function getByCourseId($courseId)
    {
        $query = new Query();
        $query ->select(['imas_external_tools.id','imas_external_tools.name AS nm' ,'imas_groups.name'])
            ->from('imas_external_tools')
            ->Join('LEFT JOIN',
                'imas_groups',
                'imas_external_tools.groupid=imas_groups.id'
            )
        ->where(['imas_external_tools.courseid' => $courseId]);
        $query->orderBy('imas_external_tools.groupid,imas_external_tools.name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByGroupId($courseId, $groupId)
    {
        $query = new Query();
        $query ->select(['id','name'])
        ->from('imas_external_tools')
        ->where(['courseid' => $courseId])
        ->andWhere(['groupid' => $groupId]);
        $query->orderBy('name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function getByCourseAndOrderByName($courseId){
        $query = new Query();
        $query ->select(['id','name'])
            ->from('imas_external_tools')
            ->where(['courseid' => $courseId]);
        $query->orderBy('name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
}