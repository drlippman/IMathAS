<?php
namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasAssessments;
use yii\db\Query;
use yii\debug\components\search\matchers\GreaterThan;

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

    public static function findAllAssessmentForGradebook($courseId, $canviewall, $istutor, $isteacher, $catfilter, $time){
        $query = new Query();
        $query->select(['id', 'name','defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'allowlate'])
            ->from('imas_assessments')
            ->where(['courseid' => $courseId])
            ->andWhere(['>', 'avail', 0]);
        if(!$canviewall){
           $query->andWhere(['>', 'cntingb', 0]);
        }
        if($istutor){
            $query->andWhere(['<', 'tutoredit', 2]);
        }
        if(!$isteacher){
//            $query->andWhere(['<', 'startdate', $time]);
        }
        if($catfilter > -1){
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('enddate, name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getByCourse($courseId)
    {
        return Assessments::find()->select('id,name')->where(['courseid' => $courseId])->orderBy('name')->all();
    }

    public static function outcomeData($courseId,$istutor,$catfilter)
    {
        $query = new Query();
        $query->select(['id', 'name','defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'defoutcome'])
            ->from('imas_assessments')
            ->where(['courseid' => $courseId])
            ->andWhere(['>', 'avail', 0])
            ->andWhere(['>', 'cntingb', 0])
            ->andWhere(['<', 'cntingb', 3]);
        if($istutor){
            $query->andWhere(['<', 'tutoredit', 2]);
        }
        if($catfilter > -1){
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('enddate, name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public function createAssessment($params){
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
        $this->reqscoreaid = isset($params['reqscoreaid']) ? $params['reqscoreaid'] : null;
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
        $this->save();
        return $this->id;
    }

    public static function updateAssessment($params,$timeLimit,$isGroup,$showHints,
                                            $tutorEdit,$defFeedback,$shuffle,$calTag,
                                            $calrtag,$defFeedbackText,$isTutorial,$endMsg,
                                            $startDate,$endDate,$reviewDate){
        $assessment = Assessments::findOne(['id' => $params['id']]);
        $assessment->name = trim($params['name']);
        $assessment->summary = $params['summary'];
        $assessment->intro = $params['intro'];
        $assessment->timelimit = $timeLimit;
        $assessment->minscore = $params['minscore'];
        $assessment->isgroup = $isGroup;
        $assessment->showhints = $showHints;
        $assessment->tutoredit = $tutorEdit;
        $assessment->eqnhelper = $params['eqnhelper'];
        $assessment->showtips = $params['showtips'];
        $assessment->displaymethod = $params['displaymethod'];
        $assessment->defattempts = $params['defattempts'];
        $assessment->deffeedback = $defFeedback;
        $assessment->shuffle = $shuffle;
        $assessment->gbcategory = $params['gbcat'];
        $assessment->password = $params['assmpassword'];
        $assessment->cntingb = $params['cntingb'];
        $assessment->showcat = $params['showqcat'];
        $assessment->caltag = $calTag;
        $assessment->calrtag = $calrtag;
        $assessment->reqscore = $params['reqscore'];
        $assessment->reqscoreaid = $params['reqscoreaid'];
        $assessment->noprint = $params['noprint'];
        $assessment->avail = $params['avail'];
        $assessment->groupmax = $params['groupmax'];
        $assessment->allowlate = $params['allowlate'];
        $assessment->exceptionpenalty = $params['exceptionpenalty'];
        $assessment->ltisecret = $params['ltisecret'];
        $assessment->deffeedback = $defFeedbackText;
        $assessment->msgtoinstr = $params['msgtoinstr'];
        $assessment->posttoforum = $params['posttoforum'];
        $assessment->istutorial = $isTutorial;
        $assessment->defoutcome = $params['defoutcome'];
        if (isset($params['defpoints'])) {
            $assessment->defpoints = $params['defpoints'];
            $assessment->defpenalty = $params['defpenalty'];
           }
        if (isset($params['copyendmsg'])) {
            $assessment->endmsg = $endMsg;
        }
        if ($params['avail']==1) {
            $assessment->startdate = $startDate;
            $assessment->enddate = $endDate;
            $assessment->reviewdate = $reviewDate;
        }
        $assessment->save();
    }

    public static function deleteAssessmentById($assessmentId){
        $assessmentData = Assessments::findOne(['id',$assessmentId]);
        if($assessmentData){
            $assessmentData->delete();
        }
    }
    public static function updateGbCat($catList){

        foreach($catList as $category){
            $query = Assessments::findOne(['gbcategory' => $category]);
            if($query){
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
    public static function setItemOrder($itemOrder,$id){
        $assessmentData = Assessments::findOne(['id' => $id]);
        if($assessmentData){
            $assessmentData->itemorder = $itemOrder;
        }
    }
    public static function findOneAssessmentDataForGradebook($courseId,$istutor, $isteacher, $catfilter){
        $query = new Query();
        $query->select(['id', 'name','defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'allowlate'])
            ->from('imas_assessments')
            ->where(['courseid' => $courseId])
            ->andWhere(['>', 'avail', 0]);
        if($istutor){
            $query->andWhere(['<', 'tutoredit', 2]);
        }
        if(!$isteacher){
//           $query->andWhere(['<', 'startdate', $time]);
        }
        if($catfilter > -1){
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('enddate, name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public static function setVidData($itemOrder,$viddata,$aid){
        $assessmentData = Assessments::findOne(['id' => $aid]);
        if($assessmentData){
            $assessmentData->itemorder = $itemOrder;
            $assessmentData->viddata = $viddata;
            $assessmentData->save();
        }
    }

    public static function getByAssessmentIds($assessmentIdList)
    {

        return Assessments::find()->where(['IN', 'id', $assessmentIdList])->all();
    }

    public static function setStartDate($shift,$typeId)
    {
        $date = Assessments::find()->where(['id'=>$typeId])->andWhere(['>','startdate','0'])->one();
        if($date) {
            $date->startdate = $date->startdate + $shift;
            $date->save();
        }

    }

    public static function setEndDate($shift,$typeId)
    {
        $date = Assessments::find()->where(['id'=>$typeId])->andWhere(['<','enddate','2000000000'])->one();
        if($date) {
            $date->enddate = $date->enddate + $shift;
            $date->save();
        }
    }
    public static function selectItemOrder($todoaid){
        $query = "SELECT id,itemorder FROM imas_assessments WHERE id IN (".implode(',',$todoaid).')';
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }
    public static function UpdateItemOrder($newitemlist, $id)
    {
        $query = "UPDATE imas_assessments SET itemorder='$newitemlist' WHERE id={$id}";
        \Yii::$app->db->createCommand($query)->query();
    }
    public static  function assessmentDataForOutcomes($courseId)
    {
        $query = new Query();
        $query->select(['ia.name', 'ia.gbcategory','ia.defoutcome', 'ia.id', 'iq.category'])
            ->from('imas_assessments AS ia')
            ->join('JOIN','imas_questions AS iq',
                'ia.id=iq.assessmentid')
            ->where(['ia.courseid' => $courseId])
            ->andWhere(['>','ia.defoutcome','0'])
            ->orWhere(['NOT LIKE','iq.category','0']);

        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;

    }

}