<?php
namespace app\models;


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
    public function createAssessment($params,$startDate,$endDate,$reviewDate,$timeLimit,$shuffle,$defFeedback,$tutorEdit,$showHints,$endMsg,$defFeedbackText,$isTutorial){
        $this->courseid = isset($params['cid']) ? $params['cid'] : null;
        $this->name = isset($params['name']) ? $params['name'] : null;
        $this->summary = isset($params['summary']) ? $params['summary'] : null;
        $this->intro = isset($params['intro']) ? $params['intro'] : null;
        $this->startdate = $startDate;
        $this->enddate = $endDate;
        $this->reviewdate = $reviewDate;
        $this->timelimit = $timeLimit;
        $this->minscore = isset($params['minscore']) ? $params['minscore'] : null;
        $this->displaymethod = isset($params['displaymethod']) ? $params['displaymethod'] : null;
        $this->defpoints = isset($params['defpoints']) ? $params['defpoints'] : null;
        $this->defattempts = isset($params['defattempts']) ? $params['defattempts'] : null;
        $this->defpenalty = isset($params['defpenalty']) ? $params['defpenalty'] : null;
        $this->deffeedback = $defFeedback;
        $this->shuffle = $shuffle;
        $this->gbcategory = isset($params['gbcat']) ? $params['gbcat'] : null;
        $this->password = isset($params['assmpassword']) ? $params['assmpassword'] : null;
        $this->cntingb = isset($params['cntingb']) ? $params['cntingb'] : null;
        $this->tutoredit = $tutorEdit;
        $this->showcat = isset($params['showqcat']) ? $params['showqcat'] : null;
        $this->eqnhelper = isset($params['eqnhelper']) ? $params['eqnhelper'] : null;
        $this->showtips = isset($params['showtips']) ? $params['showtips'] : null;
        $this->caltag = isset($params['caltagact']) ? $params['caltagact'] : null;
        $this->calrtag = isset($params['caltagrev']) ? $params['caltagrev'] : null;
        $this->isgroup = isset($params['isgroup']) ? $params['isgroup'] : null;
        $this->groupmax = isset($params['groupmax']) ? $params['groupmax'] : null;
        $this->groupsetid = isset($params['groupsetid']) ? $params['groupsetid'] : null;
        $this->showhints = $showHints;
        $this->reqscore = isset($params['reqscore']) ? $params['reqscore'] : null;
        $this->reqscoreaid = isset($params['reqscoreaid']) ? $params['reqscoreaid'] : null;
        $this->noprint = isset($params['noprint']) ? $params['noprint'] : null;
        $this->avail = isset($params['avail']) ? $params['avail'] : null;
        $this->allowlate = isset($params['allowlate']) ? $params['allowlate'] : null;
        $this->exceptionpenalty = isset($params['exceptionpenalty']) ? $params['exceptionpenalty'] : null;
        $this->ltisecret = isset($params['ltisecret']) ? $params['ltisecret'] : null;
        $this->endmsg = $endMsg;
        $this->deffeedbacktext = $defFeedbackText;
        $this->msgtoinstr = isset($params['msgtoinstr']) ? $params['msgtoinstr'] : null;
        $this->posttoforum = isset($params['posttoforum']) ? $params['posttoforum'] : null;
        $this->istutorial = $isTutorial;
        $this->defoutcome = isset($params['defoutcome']) ? $params['defoutcome'] : null;
        $this->save();
        return $this->id;
    }

    public static function updateAssessment($params,$timeLimit,$isGroup,$showHints,
                                            $tutorEdit,$defFeedback,$shuffle,$calTag,
                                            $calrtag,$defFeedbackText,$isTutorial,$endMsg,
                                            $startDate,$endDate,$reviewDate){
        $assessment = Assessments::findOne(['id' => $params['id']]);
        $assessment->name = $params['name'];
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
} 