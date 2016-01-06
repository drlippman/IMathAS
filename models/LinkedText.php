<?php

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
            ->where('courseid=:courseId',[':courseId' => $courseId])
            ->andWhere(['>', 'points', 0])
            ->andWhere(['>', 'avail', 0]);
        if (!$canviewall) {
            $query->andWhere('startdate < :now', [':now' => $now]);
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
        $updaateLink->outcomes = $params['outcomes'];
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
        return LinkedText::find()->select(['id','title','outcomes'])->where(['courseid' => $courseId])->andWhere(['<>','outcomes',''])->all();
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

    public static function updateDataForCopyCourse($toupdate)
    {
        $linkText = LinkedText::find()->where(['IN', 'id',$toupdate])->all();
        if($linkText)
        {
            foreach($linkText as $text)
            {
                $text->text = '<p>Unable to copy tool</p>';
                $text->save();
            }
        }
    }

    public static function getByIdForCopy($toupdate)
    {
        return LinkedText::find()->select('id,text')->where(['IN', 'id', $toupdate])->all();
    }

    public static function updateData($text,$id)
    {
        $linkText = LinkedText::find()->where('id', $id)->all();
        if($linkText)
        {
            foreach($linkText as $lText)
            {
                $lText->text = $text;
                $lText->save();
            }
        }
    }

    public static function updateVideoId($from,$to)
    {
        //TODO: fix below query
        $query = "UPDATE imas_linkedtext SET text=REPLACE(text,'$from','$to') WHERE text LIKE '%$from%'";
        $connection=\Yii::$app->db;
        $command=$connection->createCommand($query);
        $rowCount=$command->execute();
        return $rowCount;
    }

    public static function updateSummary($from,$to)
    {
        //TODO: fix below query
        $query = "UPDATE imas_linkedtext SET summary=REPLACE(summary,'$from','$to') WHERE summary LIKE '%$from%'";
        $connection=\Yii::$app->db;
        $command=$connection->createCommand($query);
        $rowCount=$command->execute();
        return $rowCount;
    }

    public static function getByTextAndId($courseId)
    {
        //TODO: fix below query
        return \Yii::$app->db->createCommand("SELECT text,points,id FROM imas_linkedtext WHERE courseid='{$courseId}' AND text LIKE 'file:%'")->queryAll();
    }

    public static function getByIdForFile($safetext)
    {
        return LinkedText::find()->select('id')->where(['text' => $safetext])->all();
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = LinkedText::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
        }
    }

    public static function getText($id)
    {
        return LinkedText::find()->select('text')->where(['id' => $id])->one();
    }

    public static function getDataByCourseId($courseId)
    {
        $query = new Query();
        $query->select(['id','title','startdate','enddate','avail'])
            ->from('imas_linkedtext')
            ->where('courseid=:courseId',[':courseId' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function updateName($val, $typeId)
    {
        $linkText = LinkedText::findOne(['id' => $typeId]);
        $linkText->title = $val;
        $linkText->save();
    }

    public static function getByName($typeId)
    {
        $query = new Query();
        $query->select('title as name')
            ->from('imas_linkedtext')
            ->where('id=:typeId',[':typeId' => $typeId]);
        return $query->createCommand()->queryOne();
    }

    public static function getLinkDataByIdAndCourseID($id,$courseId)
    {
        return LinkedText::find()->where(['id' => $id])->andWhere(['courseid' => $courseId])->one();
    }

    public static function getLinkedDataPublicly($id)
    {
        return self::find()->select('text,title')->where(['id' => $id])->one();
    }
}