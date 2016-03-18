<?php
namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasExternalTools;
use yii\db\Query;
use Yii;

class ExternalTools extends BaseImasExternalTools
{
    public static function externalToolsData($courseId)
    {
        $toolsData = ExternalTools::findAll(['courseid' => $courseId]);
        return $toolsData;
    }

    public function updateExternalToolsData($params)
    {
        if ($params['tool']) {
            $toolsData = ExternalTools::findOne(['id' => $params['tool']]);
            $toolsData->custom = $params['toolcustom'];
            $toolsData->url = $params['toolcustomurl'];
            $toolsData->save();
        }
    }

    public static function dataForCopy($toolidlist)
    {
        return ExternalTools::find()->select(['id,courseid,groupid,name,url,ltikey,secret,custom,privacy'])->where(['IN', 'id', $toolidlist])->all();
    }

    public static function getId($courseId, $url)
    {
        return self::find()->select('id')->from('imas_external_tools')->where(['url' => $url])->andWhere(['courseid' => $courseId])->all();
    }

    public function insertData($courseId, $groupid, $rowsub)
    {
        $this->courseid = $courseId;
        $this->groupid = $groupid;
        $this->name = $rowsub['name'];
        $this->url = $rowsub['url'];
        $this->ltikey = $rowsub['ltikey'];
        $this->secret = $rowsub['secret'];
        $this->custom = $rowsub['custom'];
        $this->privacy = $rowsub['privacy'];
        $this->save();
        return $this->id;
    }

    public function saveExternalTool($params)
    {
        $data = AppUtility::removeEmptyAttributes($params);
        $this->attributes = $data;
        $this->save();
        return $this;
    }

    public static function updateExternalToolByAdmin($params, $isAdmin, $attrValue, $attr, $privacy)
    {
        $updateExtTool = ExternalTools::find()->where(['id' => $params['id']])->andWhere([$attr => $attrValue])->one();
        if ($updateExtTool) {
            $updateExtTool->name = trim($params['tname']);
            $updateExtTool->url = $params['url'];
            $updateExtTool->ltikey = $params['key'];
            $updateExtTool->secret = $params['secret'];
            $updateExtTool->custom = $params['custom'];
            $updateExtTool->privacy = $privacy;
            if ($isAdmin) {
                if ($params['scope'] == AppConstant::NUMERIC_ZERO) {
                    $updateExtTool->groupid = AppConstant::NUMERIC_ZERO;
                } else {
                    $updateExtTool->groupid = $params['groupId'];
                }
            }
        }
        $updateExtTool->save();
       return $updateExtTool;
    }

    public static function updateExternalTool($params, $attr, $privacy)
    {
        $updateExtTool = ExternalTools::find()->where(['id' => $params['id']])->one();
        if ($updateExtTool) {
            $updateExtTool->name = $params['tname'];
            $updateExtTool->url = $params['url'];
            $updateExtTool->ltikey = $params['key'];
            $updateExtTool->secret = $params['secret'];
            $updateExtTool->custom = $params['custom'];
            $updateExtTool->privacy = $privacy;
            $updateExtTool->groupid = $attr;
            $updateExtTool->save();
            return $updateExtTool;
        }

    }

    public static function deleteById($id, $isTeacher, $isGrpAdmin, $courseId, $groupId)
    {
        $externalTool = ExternalTools::findOne($id);
        if ($externalTool) {
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
        $query->select(['name', 'url', 'ltikey', 'secret', 'custom', 'privacy', 'groupid'])
            ->from('imas_external_tools')
            ->where(['id' => $id]);
        if ($isTeacher) {
            $query->andWhere('courseid=:courseId', [':courseId' => $courseId]);
        } else if ($isGrpAdmin) {
            $query->andWhere(['groupid:groupId', ['groupId' => $groupId]]);
        }
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public static function getByCourseId($courseId)
    {
        $query = new Query();
        $query->select(['imas_external_tools.id', 'imas_external_tools.name AS name', 'imas_groups.name AS group_name'])
            ->from('imas_external_tools')
            ->Join('LEFT JOIN',
                'imas_groups',
                'imas_external_tools.groupid=imas_groups.id'
            )
            ->where('imas_external_tools.courseid = :courseId');
        $query->orderBy('imas_external_tools.groupid,imas_external_tools.name');
        $command = $query->createCommand()->bindValue('courseId', $courseId);
        $data = $command->queryAll();
        return $data;
    }

    public static function getByGroupId($courseId, $groupId)
    {
        return self::find()->select(['id', 'name'])->where(['courseid' => $courseId, 'groupid' => $groupId])->orderBy('name')->all();
    }
    public static function getByCourseAndOrderByName($courseId)
    {
        return self::find()->select(['id', 'name'])->where(['courseid' => $courseId])->orderBy('name')->all();
    }

    public static function getById($id)
    {
        $extenalTool = ExternalTools::find()->where(['id' => $id])->one();
        return $extenalTool;
    }

    public static function externalToolsDataForLink($courseId, $groupId)
    {
        $query = new Query();
        $query->select('id,name')->from('imas_external_tools')->where('groupid= :groupId')->orWhere('groupid = 0')
            ->andWhere('courseid = 0')->orWhere('courseid = :courseId')->orderBy('name');
        $command = $query->createCommand()->bindValues(['courseId' =>  $courseId,'groupId' => $groupId]);
        $data = $command->queryAll();
        return $data;
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = ExternalTools::findOne(['courseid', $courseId]);
        if ($courseData) {
            $courseData->delete();
        }
    }
    public static function getExternalToolName($id)
    {
        return self::find()->select('name')->where(['id' => $id])->one();
    }

    public static function getToolData($tool, $courseId, $groupId)
    {
        return Yii::$app->db->createCommand("SELECT * from imas_external_tools WHERE id=$tool AND (courseid=':courseId' OR (courseid=0 AND (groupid=':groupId' OR groupid=0)))")->bindValues(['courseId' => $courseId, ':groupId' => $groupId])->queryOne();
    }
}