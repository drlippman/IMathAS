<?php
namespace app\models;

use app\components\AppConstant;
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
            ->where(['courseid' => $courseId])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO]);
        if (!$canviewall) {
            $query->andWhere(['>', 'cntingb', AppConstant::NUMERIC_ZERO]);
        }
        if ($istutor) {
            $query->andWhere(['<', 'tutoredit', AppConstant::NUMERIC_TWO]);
        }
        if ($catfilter > AppConstant::NUMERIC_NEGATIVE_ONE) {
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

    public static function outcomeData($courseId, $istutor, $catfilter)
    {
        $query = new Query();
        $query->select(['id', 'name', 'defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'defoutcome'])
            ->from('imas_assessments')
            ->where(['courseid' => $courseId])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO])
            ->andWhere(['>', 'cntingb', AppConstant::NUMERIC_ZERO])
            ->andWhere(['<', 'cntingb', AppConstant::NUMERIC_THREE]);
        if ($istutor) {
            $query->andWhere(['<', 'tutoredit', AppConstant::NUMERIC_TWO]);
        }
        if ($catfilter > AppConstant::NUMERIC_NEGATIVE_ONE) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('enddate, name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function createAssessment($params)
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

    public static function updateAssessment($params, $timeLimit, $isGroup, $showHints,
                                            $tutorEdit, $defFeedback, $shuffle, $calTag,
                                            $calrtag, $defFeedbackText, $isTutorial, $endMsg,
                                            $startDate, $endDate, $reviewDate)
    {
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
        if ($params['avail'] == AppConstant::NUMERIC_ONE) {
            $assessment->startdate = $startDate;
            $assessment->enddate = $endDate;
            $assessment->reviewdate = $reviewDate;
        }
        $assessment->save();
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

    public static function findOneAssessmentDataForGradebook($courseId, $istutor, $isteacher, $catfilter)
    {
        $query = new Query();
        $query->select(['id', 'name', 'defpoints', 'deffeedback', 'timelimit', 'minscore', 'startdate', 'enddate', 'itemorder', 'gbcategory', 'cntingb', 'avail', 'groupsetid', 'allowlate'])
            ->from('imas_assessments')
            ->where(['courseid' => $courseId])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO]);
        if ($istutor) {
            $query->andWhere(['<', 'tutoredit', AppConstant::NUMERIC_TWO]);
        }
        if ($catfilter > AppConstant::NUMERIC_NEGATIVE_ONE) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }
        $query->orderBy('enddate, name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
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
        $query = "SELECT id,itemorder FROM imas_assessments WHERE id IN (" . implode(',', $todoaid) . ')';
        $data = \Yii::$app->db->createCommand($query)->queryAll();
        return $data;
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
            ->where(['ia.courseid' => $courseId])
            ->andWhere(['>', 'ia.defoutcome', AppConstant::ZERO_VALUE])
            ->orWhere(['<>', 'iq.category', AppConstant::ZERO_VALUE]);

        $command = $query->createCommand();
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
            ->where(['imas_assessment_sessions.id' => $assessmentId]);
        if (!$isteacher && !$istutor) {
            $query->andWhere(['imas_assessment_sessions.userid' => $userId]);
        }
        $command = $query->createCommand();
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
        $query = new Query();
        $query->select(['id'])
            ->from('imas_assessments')
            ->where(['groupsetid' => $grpSetId]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;

    }

    public static function CommonMethodToGetAssessmentData($toCopy, $id)
    {
        $query = new Query();
        $query->select($toCopy)
            ->from('imas_assessments')
            ->where(['id' => $id]);
        $command = $query->createCommand();
        $data = $command->queryOne();
        return $data;
    }

    public static function updateAssessmentData($setslist, $checkedlist)
    {
        $query = "UPDATE imas_assessments SET $setslist WHERE id IN ($checkedlist)";
        Yii::$app->db->createCommand($query)->query();
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
        $query = "UPDATE imas_assessments SET defoutcome=0 WHERE courseid='$courseId' AND defoutcome IN ($unusedList)";
        \Yii::$app->db->createCommand()->queryAll($query);
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
        return Yii::$app->db->createCommand("SELECT ia.id,ias.bestscores FROM imas_assessments AS ia JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid WHERE ia.courseid='$courseId' AND ias.userid='$userId'")->queryAll();
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
        $query = Yii::$app->db->createCommand("SELECT id,name FROM imas_assessments WHERE courseid='$cid'")->queryAll();
        return $query;
    }

    public static function updateVideoId($from, $to)
    {
        $query = "UPDATE imas_assessments SET intro=REPLACE(intro,'$from','$to') WHERE intro LIKE '%$from%'";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($query);
        $rowCount = $command->execute();
        return $rowCount;
    }

    public static function updateSummary($from, $to)
    {
        $query = "UPDATE imas_assessments SET summary=REPLACE(summary,'$from','$to') WHERE summary LIKE '%$from%'";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($query);
        $rowCount = $command->execute();
        return $rowCount;

    }

    public static function getItemOrderById($assessmentId)
    {
        return Assessments::find()->select('itemorder')->where(['id' => $assessmentId])->one();
    }

    public static function getByName($courseId)
    {
        $query = new Query();
        $query->select(['id', 'name'])
            ->from('imas_assessments')
            ->where(['courseid' => $courseId]);
        $query->orderBy('name');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
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
}