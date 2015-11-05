<?php
namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasAssessments;
use yii\db\Query;
use Yii;

class Assessments extends BaseImasAssessments
{
    public static function getByCourseId($courseId)
    {
        return Assessments::find()->where(['courseid' => $courseId])->orderBy('name')->all();
    }

    public static function getByAssessmentId($id)
    {
        return Assessments::findOne(['id' => $id]);
    }

    public function create($values)
    {
        $this->attributes = $values;
        $this->save();
    }

    public static function findAllAssessmentForGradebook($courseId, $canviewall, $istutor, $isteacher, $catfilter, $time)
    {
        $query = new Query();
        $query->select(['id', 'name', 'defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'allowlate'])
            ->from('imas_assessments')
            ->where('courseid = :courseId')
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO]);
        if (!$canviewall) {
            $query->andWhere(['>', 'cntingb', AppConstant::NUMERIC_ZERO]);
        }
        if ($istutor) {
            $query->andWhere(['<', 'tutoredit', AppConstant::NUMERIC_TWO]);
        }
        $catfilter > AppConstant::NUMERIC_NEGATIVE_ONE ? $query->andWhere(':gbcategory = :catfilter') : $query->andWhere(':catfilter = :catfilter');
        $query->orderBy('enddate, name');
        $command = $query->createCommand()->bindValues([':courseId' => $courseId,':catfilter' => $catfilter]);
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourse($courseId)
    {
        return Assessments::find()->select('id,name')->where(['courseid' => $courseId])->orderBy('name')->all();
    }

    public static function outcomeData($courseId, $istutor, $catfilter)
    {
        $query = new Query();
        $query->select(['id', 'name', 'defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'defoutcome'])
            ->from('imas_assessments')
            ->where(['courseid', $courseId])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO])
            ->andWhere(['>', 'cntingb', AppConstant::NUMERIC_ZERO])
            ->andWhere(['<', 'cntingb', AppConstant::NUMERIC_THREE]);
        if ($istutor) {
            $query->andWhere(['<', 'tutoredit', AppConstant::NUMERIC_TWO]);
        }
        $catfilter > AppConstant::NUMERIC_NEGATIVE_ONE ? $query->andWhere('gbcategory = :catfilter') : $query->andWhere(':catfilter = :catfilter');
        $query->orderBy('enddate, name');
        $command = $query->createCommand()->bindValue(':catfilter',$catfilter);
        $data = $command->queryAll();
        return $data;
    }

    public function createAssessment($params)
    {
        $data = AppUtility::removeEmptyAttributes($params);
        $this->attributes = $data;
        $this->save();
        return $this->id;
    }

    public static function updateAssessment($params)
    {
        $assessment = Assessments::findOne(['id' => $params['id']]);
        if($assessment){
            $data = AppUtility::removeEmptyAttributes($params);
            $assessment->attributes = $data;
            $assessment->save();
        }
    }

    public static function deleteAssessmentById($assessmentId)
    {
        $assessmentData = Assessments::findOne(['id', $assessmentId]);
        if ($assessmentData) {
            $assessmentData->delete();
        }
    }

    public static function updateGbCat($catList)
    {

        foreach ($catList as $category) {
            $query = Assessments::findOne(['gbcategory' => $category]);
            if ($query) {
                $query->gbcategory = AppConstant::NUMERIC_ZERO;
                $query->save();
            }
        }
    }

    public function copyAssessment($params)
    {
        $this->courseid = isset($params['courseid']) ? $params['courseid'] : null;
        $this->name = isset($params['name']) ? $params['name'] : null;
        $this->summary = isset($params['summary']) ? $params['summary'] : null;
        $this->intro = isset($params['intro']) ? $params['intro'] : null;
        $this->startdate = isset($params['startdate']) ? $params['startdate'] : null;
        $this->enddate = isset($params['enddate']) ? $params['enddate'] : null;
        $this->reviewdate = isset($params['reviewdate']) ? $params['reviewdate'] : null;
        $this->timelimit = isset($params['timelimit']) ? $params['timelimit'] : null;
        $this->minscore = isset($params['minscore']) ? $params['minscore'] : null;
        $this->displaymethod = isset($params['displaymethod']) ? $params['displaymethod'] : null;
        $this->defpoints = isset($params['defpoints']) ? $params['defpoints'] : null;
        $this->defattempts = isset($params['defattempts']) ? $params['defattempts'] : null;
        $this->defpenalty = isset($params['defpenalty']) ? $params['defpenalty'] : null;
        $this->deffeedback = isset($params['deffeedback']) ? $params['deffeedback'] : null;
        $this->shuffle = isset($params['shuffle']) ? $params['shuffle'] : null;
        $this->gbcategory = isset($params['gbcategory']) ? $params['gbcategory'] : null;
        $this->password = isset($params['password']) ? $params['password'] : null;
        $this->cntingb = isset($params['cntingb']) ? $params['cntingb'] : null;
        $this->tutoredit = isset($params['tutoredit']) ? $params['tutoredit'] : null;
        $this->showcat = isset($params['showcat']) ? $params['showcat'] : null;
        $this->eqnhelper = isset($params['eqnhelper']) ? $params['eqnhelper'] : null;
        $this->showtips = isset($params['showtips']) ? $params['showtips'] : null;
        $this->caltag = isset($params['caltag']) ? $params['caltag'] : null;
        $this->calrtag = isset($params['calrtag']) ? $params['calrtag'] : null;
        $this->isgroup = isset($params['isgroup']) ? $params['isgroup'] : null;
        $this->groupmax = isset($params['groupmax']) ? $params['groupmax'] : null;
        $this->groupsetid = isset($params['groupsetid']) ? $params['groupsetid'] : null;
        $this->showhints = isset($params['showhints']) ? $params['showhints'] : null;
        $this->reqscore = isset($params['reqscore']) ? $params['reqscore'] : null;
        $this->noprint = isset($params['noprint']) ? $params['noprint'] : null;
        $this->avail = isset($params['avail']) ? $params['avail'] : null;
        $this->allowlate = isset($params['allowlate']) ? $params['allowlate'] : null;
        $this->exceptionpenalty = isset($params['exceptionpenalty']) ? $params['exceptionpenalty'] : null;
        $this->ltisecret = isset($params['ltisecret']) ? $params['ltisecret'] : null;
        $this->endmsg = isset($params['endmsg']) ? $params['endmsg'] : null;
        $this->deffeedbacktext = isset($params['deffeedbacktext']) ? $params['deffeedbacktext'] : null;
        $this->msgtoinstr = isset($params['msgtoinstr']) ? $params['msgtoinstr'] : null;
        $this->posttoforum = isset($params['posttoforum']) ? $params['posttoforum'] : null;
        $this->istutorial = isset($params['istutorial']) ? $params['istutorial'] : null;
        $this->defoutcome = isset($params['defoutcome']) ? $params['defoutcome'] : null;
        $this->itemorder = isset($params['itemorder']) ? $params['itemorder'] : null;
        $this->ancestors = isset($params['ancestors']) ? $params['ancestors'] : null;
        $this->viddata = isset($params['viddata']) ? $params['viddata'] : null;
        $this->save();
        return $this->id;
    }

    public static function setItemOrder($itemOrder, $id)
    {
        $assessmentData = Assessments::findOne(['id' => $id]);
        if ($assessmentData) {
            $assessmentData->itemorder = $itemOrder;
            $assessmentData->save();
        }
    }

    public static function setVidData($itemOrder, $viddata, $aid)
    {
        $assessmentData = Assessments::findOne(['id' => $aid]);
        if ($assessmentData) {
            $assessmentData->itemorder = $itemOrder;
            $assessmentData->viddata = $viddata;
            $assessmentData->save();
            return $assessmentData;
        }
    }

    public static function getByAssessmentIds($assessmentIdList)
    {

        return Assessments::find()->where(['IN', 'id', $assessmentIdList])->all();
    }

    public static function setStartDate($shift, $typeId)
    {
        $date = Assessments::find()->where(['id' => $typeId])->andWhere(['>', 'startdate', AppConstant::ZERO_VALUE])->one();
        if ($date) {
            $date->startdate = $date->startdate + $shift;
            $date->save();
        }

    }

    public static function setEndDate($shift, $typeId)
    {
        $date = Assessments::find()->where(['id' => $typeId])->andWhere(['<', 'enddate', '2000000000'])->one();
        if ($date) {
            $date->enddate = $date->enddate + $shift;
            $date->save();
        }
    }

    public static function selectItemOrder($todoaid)
    {
        return Assessments::find()->select(['id,itemorder'])->where(['IN','id',$todoaid])->all();
    }

    public static function UpdateItemOrder($newItemList, $id)
    {
        $data = Assessments::findOne(['id' => $id]);
        if ($data) {
            $data->itemorder = $newItemList;
            $data->save();
        }
    }

    public static function assessmentDataForOutcomes($courseId)
    {
        $query = new Query();
        $query->select(['ia.name', 'ia.gbcategory', 'ia.defoutcome', 'ia.id', 'iq.category'])
            ->from('imas_assessments AS ia')
            ->join('JOIN', 'imas_questions AS iq',
                'ia.id=iq.assessmentid')
            ->where('ia.courseid = :courseId')
            ->andWhere(['>', 'ia.defoutcome', AppConstant::ZERO_VALUE])
            ->orWhere(['<>', 'iq.category', AppConstant::ZERO_VALUE]);
        $command = $query->createCommand()->bindValue('courseId' , $courseId);
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourseIdJoinWithSessionData($assessmentId, $userId, $isteacher, $istutor)
    {
        $query = new Query();
        $query->select(['imas_assessments.name', 'imas_assessments.timelimit', 'imas_assessments.defpoints', 'imas_assessments.tutoredit', 'imas_assessments.defoutcome',
            'imas_assessments.showhints', 'imas_assessments.deffeedback', 'imas_assessments.enddate', 'imas_assessment_sessions.*'])
            ->from('imas_assessments')
            ->join('INNER JOIN',
                'imas_assessment_sessions',
                'imas_assessments.id=imas_assessment_sessions.assessmentid'
            )
            ->where('imas_assessment_sessions.id = :assessmentId');
        (!$isteacher && !$istutor) ? $query->andWhere('imas_assessment_sessions.userid = :userId') : $query->andWhere(':userId = :userId');
        $command = $query->createCommand()->bindValues([':assessmentId' => $assessmentId,':userId' => $userId]);
        $data = $command->queryOne();
        return $data;
    }

    public static function getByGroupSetId($deleteGrpSet)
    {
        return Assessments::find()->where(['>', 'isgroup', AppConstant::ZERO_VALUE])->andWhere(['groupsetid' => $deleteGrpSet])->all();
    }

    public static function updateAssessmentForGroups($deleteGrpSet)
    {
        $query = Assessments::find()->where(['groupsetid' => $deleteGrpSet])->all();
        if ($query) {
            foreach ($query as $singleData) {
                $singleData->isgroup = AppConstant::NUMERIC_ZERO;
                $singleData->groupsetid = AppConstant::NUMERIC_ZERO;
                $singleData->save();
            }
        }
    }

    public static function getIdForGroups($grpSetId)
    {
        return self::find()->select(['id'])->where(['groupsetid' => $grpSetId])->one();
    }

    public static function CommonMethodToGetAssessmentData($toCopy, $id)
    {
        return self::find()->select($toCopy)->where(['id' => $id])->one();
    }

    public static function updateAssessmentData($setslist, $id)
    {
        $assessment = Assessments::findOne(['id' => $id]);
        $assessment->attributes = $setslist;
        $assessment->save();
    }

    public static function updateAssessmentForMassChange($startdate, $enddate, $reviewdate, $avail, $id)
    {
        $assessment = Assessments::findOne(['id' => $id]);
        $assessment->startdate = $startdate;
        $assessment->enddate = $enddate;
        $assessment->reviewdate = $reviewdate;
        $assessment->avail = $avail;
        $assessment->save();
    }

    public static function getAssessmentForMassChange($courseId)
    {
        $query = Assessments::find()->where(['courseid' => $courseId])->all();
        return $query;
    }

    public static function  updateOutcomes($courseId, $unusedList)
    {
        $assessmentData = Assessments::find()->where(['courseid' => $courseId])->andWhere(['IN', 'defoutcome', $unusedList])->all();
        if($assessmentData){
            foreach($assessmentData as $data){
                $data->defoutcome = AppConstant::NUMERIC_ZERO;
                $data->save();
            }
        }
    }

    public static function updateAssessmentForCopyCourse($assessNewId, $newId, $num)
    {
        $query = Assessments::find()->where(['id' => $newId])->one();
        if ($query) {
            if ($num == AppConstant::NUMERIC_ZERO) {
                $query->reqscoreaid = $assessNewId;
                $query->save();
            } else {
                $query->reqscoreaid = AppConstant::NUMERIC_ZERO;
                $query->save();
            }
        }
    }

    public static function updatePostToForum($assessNewId, $newId, $num)
    {
        $query = Assessments::find()->where(['id' => $newId])->one();
        if ($query) {
            if ($num == AppConstant::NUMERIC_ZERO) {
                $query->posttoforum = $assessNewId;
                $query->save();
            } else {
                $query->posttoforum = AppConstant::NUMERIC_ZERO;
                $query->save();
            }
        }
    }

    public static function getCourseAndUserId($courseId, $userId)
    {
        $query = new Query();
        $query->select('ia.id,ias.bestscores')->from('imas_assessments AS ia');
        $query->join('INNER JOIN','imas_assessment_sessions AS ias',
                  'ia.id=ias.assessmentid');
        $query->where('ia.courseid= :courseId', 'ias.userid= :userId');
        $command = $query->createCommand()->bindValues(['courseId' => $courseId, 'userId' => $userId]);
        $data = $command->queryAll();
        return $data;
    }

    public static function setEndMessage($id, $msgstr)
    {
        $data = Assessments::getByAssessmentId($id);
        if ($data) {
            $data->endmsg = $msgstr;
            $data->save();
        }
    }

    public static function getByCId($cid)
    {
        return Assessments::find()->select(['id','name'])->where(['courseid' => $cid])->all();
    }

    public static function updateVideoId($from, $to)
    {
        $Assessments = Assessments::find()->where(['LIKE','intro',$from])->all();
        $rowCount = 0;
        if($Assessments)
        {
            foreach($Assessments as $Assessment)
            {
                $Assessment->summary = $to;
                $Assessment->save();
                $rowCount++;
            }
        }
        return $rowCount;
    }

    public static function updateSummary($from, $to)
    {
        $Assessments = Assessments::find()->where(['LIKE','summary',$from])->one();
        $rowCount = 0;
        if($Assessments)
        {
            foreach($Assessments as $Assessment)
            {
                $Assessment->summary = $to;
                $Assessment->save();
                $rowCount++;
            }
        }
        return $rowCount;

    }

    public static function getItemOrderById($assessmentId)
    {
        return Assessments::find()->select('itemorder')->where(['id' => $assessmentId])->one();
    }

    public static function getByName($courseId)
    {
        return self::find()->select(['id', 'name'])->where(['courseid' => $courseId])->orderBy('name')->all();
    }

    public static function getByAssId($courseId)
    {
        return Assessments::find()->select('id')->where(['courseid' => $courseId])->all();
    }

    public static function deleteByCourseId($courseId)
    {
        $deleteId = Assessments::find()->where(['courseid' => $courseId])->one();
        if ($deleteId) {
            $deleteId->delete();
        }
    }

    public static function getSelectedData($id){
        return Assessments::find()->select('itemorder,shuffle,defpoints,name,intro')->where(['id' => $id])->all();
    }

    public static  function updateVideoCued($data,$aid)
    {
        $assessmentData = Assessments::find()->where(['id' => $aid])->one();
        if($assessmentData)
        {
            $assessmentData->viddata = $data;
            $assessmentData->save();
        }
    }
    public static function getEndDateById($aid)
    {
        return Assessments::find()->select('enddate')->where(['id' => $aid])->all();
    }

    public static function getDateAndAllowById($aid)
    {
        return self::find()->select(['allowlate','enddate','startdate'])->where(['id' => $aid])->one();
    }

    public static function getAssessmentData($id){
        return Assessments::find()->select('deffeedback,startdate,enddate,reviewdate,shuffle,itemorder,password,avail,isgroup,groupsetid,deffeedbacktext,timelimit,courseid,istutorial,name,allowlate')->where(['id' => $id])->one();
    }

    public static function getAssessmentIntro($id){
        return Assessments::find()->select('intro')->where(['id' => $id])-> all();
    }
    public static function getDataByCourseId($cid)
    {
        return self::find()->select(['id','name','startdate','enddate','reviewdate','avail'])->where(['courseid' => $cid])->all();
    }

    public function updateName($val, $typeId)
    {
        $linkText = Assessments::findOne(['id' => $typeId]);
        $linkText->name = $val;
        $linkText->save();
    }

    public static function getAssessmentName($typeId)
    {
        return Assessments::find()->select('name')->where(['id' => $typeId])->one();
    }

    public static function getCourseIdName($typeId)
    {
        return Assessments::find()->select('courseid')->where(['id' => $typeId])->one();
    }

    public static function getAssessmentDataById($typeId)
    {
        return self::find()->select(['name','summary','startdate','enddate','reviewdate','deffeedback','reqscore','reqscoreaid','avail','allowlate','timelimit'])->where(['id' => $typeId])->one();
    }

    public static function getAssessmentDataId($line)
    {
        return Assessments::findOne(['id' => $line])->toArray();
    }
    public static function getByAssessmentIdAsArray($id)
    {
        $assessment = Assessments::findOne(['id' => $id]);
        if(is_object($assessment)) {
            return $assessment->toArray();
        } else {
            return $assessment;
        }
    }
}