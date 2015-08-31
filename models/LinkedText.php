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
use app\models\_base\BaseImasLinkedtext;
use yii\db\Query;

class LinkedText extends BaseImasLinkedtext
{
    public static function findExternalToolsInfo($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now){
        $query = new Query();
        $query->select(['id', 'title', 'text', 'startdate', 'enddate', 'points', 'avail'])
            ->from('imas_linkedtext')
            ->where(['courseid'=>$courseId])
            ->andWhere(['>', 'points', 0])
            ->andWhere(['>', 'avail', 0]);
        if (!$canviewall) {
            $query->andWhere(['<','startdate', $now]);
        }

        $query->orderBy('enddate, startdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public function addLinkedText($params)
    {
        $this->courseid = $params['courseid'];
        $this->title = $params['title'];
        $this->summary = $params['summary'];
        $this->text = $params['text'];
        $this->avail = $params['avail'];
        $this->oncal = $params['oncal'];
        $this->caltag = $params['caltag'];
        $this->target = $params['target'];
        $this->outcomes = $params['outcomes'];
        $this->points = $params['points'];
        $this->startdate = $params['startdate'];
        $this->enddate = $params['enddate'];
        $this->save();

        return $this->id;
    }

    public static function getById($id)
    {
        $linkData = LinkedText::find()->where(['id' => $id])->one();
        return $linkData;
    }

    public function updateLinkData($params)
    {
        $updaateLink = LinkedText::findOne(['id' => $params['id']]);
        $updaateLink->courseid = $params['courseid'];
        $updaateLink->title = $params['title'];
        $updaateLink->summary = $params['summary'];
        $updaateLink->text = $params['text'];
        $updaateLink->avail = $params['avail'];
        $updaateLink->oncal = $params['oncal'];
        $updaateLink->caltag = $params['caltag'];
        $updaateLink->target = $params['target'];
        $updaateLink->points= $params['points'];
        $updaateLink->startdate = $params['startdate'];
        $updaateLink->enddate = $params['enddate'];
        $updaateLink->save();
    }
    public static function findByCourseId($cid)
    {
        $linkData = LinkedText::find()->where(['courseid' => $cid])->andWhere(['>','points',AppConstant::NUMERIC_ZERO])->all()  ;
        return $linkData;
    }

    public static function setStartDate($shift,$typeId)
    {
        $date = LinkedText::find()->where(['id'=>$typeId])->andWhere(['>','startdate','0'])->one();
        if($date) {
            $date->startdate = $date->startdate + $shift;
            $date->save();
        }
    }

    public static function setEndDate($shift,$typeId)
    {
        $date = LinkedText::find()->Where(['id'=>$typeId])->andWhere(['<','enddate','2000000000'])->one();
        if($date) {
            $date->enddate = $date->enddate + $shift;
            $date->save();
        }
    }

    public static function getLinkedTextForOutcomeMap($courseId)
    {
        $query = new Query();
        $query->select(['id','title','outcomes'])
            ->from('imas_linkedtext')
            ->where(['courseid' => $courseId])
            ->andWhere(['<>','outcomes','']);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }

    public static function updateLinkForMassChanges($startdate, $enddate, $avail, $id)
    {
        $linkText = LinkedText::findOne(['id' => $id]);
        $linkText->startdate = $startdate;
        $linkText->enddate = $enddate;
        $linkText->avail = $avail;
        $linkText->save();
    }

    public static function getLinkTextForMassChanges($courseId)
    {
        $query = LinkedText::find()->where(['courseid' => $courseId])->all();
        return $query;
    }

}