<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\controllers\AppController;
use app\models\_base\BaseImasForums;
use yii\db\Query;
use Yii;

class Forums extends BaseImasForums {

    public static function getByCourseId($courseId)
    {
        return Forums::findAll(['courseid'=>$courseId]);
    }

    public static function getById($id)
    {
        return Forums::findOne(['id' => $id]);
    }

    public static function getByCourse($courseId)
    {
        return Forums::find()->select('id,name')->where(['courseid' => $courseId])->all();
    }

    public static function getByCourseIdOrdered($courseId,$sort,$orderBy)
    {
        return Forums::find()->where(['courseid' => $courseId])->orderBy([$orderBy => $sort])->all();
    }

    public  static  function findDiscussionGradeInfo($courseId, $canviewall, $istutor, $isteacher, $catfilter, $now)
    {
        $query = new Query();
        $query->select(['id', 'name', 'gbcategory', 'startdate', 'enddate', 'replyby', 'postby', 'points', 'cntingb', 'avail'])
            ->from('imas_forums')
            ->where('courseid=:courseId',[':courseId'=>$courseId])
            ->andWhere(['>', 'points', AppConstant::NUMERIC_ZERO])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO]);
        if (!$canviewall) {
            $query->andWhere(['<','startdate',$now]);
        }
        if ($istutor) {
            $query->andWhere(['<','tutoredit', AppConstant::NUMERIC_TWO]);
        }
        if ($catfilter> AppConstant::NUMERIC_NEGATIVE_ONE) {
            $query->andWhere('gbcategory=:gbCat',[':gbCat' => $catfilter]);
        }
        $query->orderBy('enddate, postby, replyby, startdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function getDiscussion($courseId,$catfilter)
    {
        $query = new Query();
        $query->select(['id', 'name', 'gbcategory', 'startdate', 'enddate', 'replyby', 'postby', 'points', 'cntingb', 'avail'])
            ->from('imas_forums')
            ->where('courseid=:courseId',[':courseId'=>$courseId])
            ->andWhere(['>', 'points', AppConstant::NUMERIC_ZERO])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO]);
        if ($catfilter>AppConstant::NUMERIC_NEGATIVE_ONE)
        {
            $query->andWhere('gbcategory=:gbCat',[':gbCat' => $catfilter]);
        }
        $query->orderBy('enddate, postby, replyby, startdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function addNewForum($params)
    {
        $this->name = trim(isset($params['name']))?$params['name']:null;
        $this->description = isset($params['description']) ? $params['description'] : null;
        $this->courseid = isset($params['courseid']) ? $params['courseid'] : null;
        $this->settings = isset($params['settings']) ? $params['settings'] : null;
        $this->startdate = isset($params['startdate']) ? $params['startdate'] : null;
        $this->enddate = isset($params['enddate']) ? $params['enddate'] : null;
        $this->sortby = isset($params['sortby']) ? $params['sortby'] : null;
        $this->defdisplay = isset($params['defdisplay']) ? $params['defdisplay'] : null;
        $this->postby = isset($params['postby']) ? $params['postby'] : null;
        $this->replyby = isset($params['replyby']) ? $params['replyby'] : null;
        $this->groupsetid = isset($params['groupsetid']) ? $params['groupsetid'] : null;
        $this->cntingb = isset($params['cntingb']) ? $params['cntingb'] : null;
        $this->avail = isset($params['avail']) ? $params['avail'] : null;
        $this->forumtype = isset($params['forumtype']) ? $params['forumtype'] : null;
        $this->caltag = isset($params['caltag']) ? $params['caltag'] : null;
        $this->taglist = isset($params['taglist']) ? $params['taglist'] : null;
        $this->gbcategory = isset($params['gbcategory']) ? $params['gbcategory'] : null;
        $this->points = isset($params['points']) ? $params['points'] : null;
        $this->tutoredit = isset($params['tutoredit']) ? $params['tutoredit'] : null;
        $this->rubric = $params['rubric'] ? $params['rubric'] : AppConstant::NUMERIC_ZERO;
        $this->outcomes = isset($params['outcomes']) ? $params['outcomes'] : null;
        $this->save();
        return $this->id;
    }

    public static function deleteForum($itemId)
    {
        $forum = Forums::findOne(['id' => $itemId]);
        if($forum){
            $forum->delete();
        }
    }

    public function updateForum($params,$endDate,$startDate,$postDate,$replyByDate,$settingValue)
    {
        $updateForumData = Forums::findOne(['id' => $params['modifyFid']]);
        $updateForumData->name = trim($params['name']);
        if(empty($params['forum-description']))
        {
            $params['forum-description'] = ' ';
        }
        $updateForumData->description = trim($params['description']);
        $updateForumData->courseid = $params['cid'];
        $updateForumData->settings = $settingValue;

        if($params['avail'] == AppConstant::NUMERIC_ONE)
        {
            if($params['available-after'] == AppConstant::NUMERIC_ZERO){
                $startDate = AppConstant::NUMERIC_ZERO;
            }
            if($params['available-until'] == AppConstant::ALWAYS_TIME){
                $endDate = AppConstant::ALWAYS_TIME;
            }
            $updateForumData->startdate = $startDate;
            $updateForumData->enddate = $endDate;
        }else
        {
            $updateForumData->startdate = AppConstant::NUMERIC_ZERO;
            $updateForumData->enddate = AppConstant::ALWAYS_TIME;
        }

        $updateForumData->sortby = $params['sort-thread'];
        $updateForumData->defdisplay = $params['default-display'];
        if($params['post'] == AppConstant::NUMERIC_ONE){

            $updateForumData->postby = $postDate;
        }else{
            $updateForumData->postby = $params['post'];
        }

        if($params['reply'] == AppConstant::NUMERIC_ONE){

            $updateForumData->replyby = $replyByDate;
        }else{
            $updateForumData->replyby = $params['reply'];
        }

        $updateForumData->groupsetid = $params['groupsetid'];
        $updateForumData->cntingb = $params['count-in-gradebook'];
        $updateForumData->avail = $params['avail'];
        $updateForumData->forumtype = $params['forum-type'];
        $updateForumData->caltag = $params['calendar-icon-text1'].'--'.$params['calendar-icon-text2'];
        $tagList = '';
        if($params['categorize-posts'] == AppConstant::NUMERIC_ONE){
            $tagList = trim($params['taglist']);
        }
        $updateForumData->taglist = $tagList;
        $updateForumData->gbcategory = $params['gradebook-category'];
        $updateForumData->points = $params['points'];
        $updateForumData->tutoredit = $params['tutor-access'];
        $updateForumData->rubric = $params['rubric'];
        $updateForumData->outcomes = $params['outcomes'];
        $updateForumData->save();
    }
    public static function updateGbCat($catList){

        foreach($catList as $category){
            $query = Forums::findOne(['gbcategory' => $category]);
            if($query){
                $query->gbcategory = AppConstant::NUMERIC_ZERO;
                $query->save();
            }
        }
    }

    public static function setRubric($id, $data){
        $rubricData = Forums::findOne(['id' => $id]);
        if($rubricData)
        {
            $rubricData->rubric = $data;
            $rubricData->save();
        }
    }

    public static function setReplyBy($shift,$typeId)
    {
        $date = Forums::find()->where(['id'=>$typeId])->andWhere(['>','replyby',AppConstant::NUMERIC_ZERO])->andWhere(['<','replyby',AppConstant::ALWAYS_TIME])->one();
        if($date)
        {
            $date->replyby = $date->replyby + $shift;
            $date->save();
        }
    }

    public static function setPostBy($shift,$typeId)
    {
        $date = Forums::find()->where(['id' => $typeId])->andWhere(['>', 'postby', AppConstant::NUMERIC_ZERO])->andWhere(['<', 'postby', AppConstant::ALWAYS_TIME])->one();
        if ($date)
        {
            $date->postby = $date->postby + $shift;
            $date->save();
        }
    }

    public static function getForumsForOutcomeMap($courseId)
    {
        return self::find()->select(['id','cntingb','name','gbcategory','outcomes'])->where(['courseId' => $courseId])->andWhere(['<>','outcomes',''])->all();
    }

    public static function getByGroupSetId($deleteGrpSet)
    {
        return Forums::find()->where(['groupsetid' => $deleteGrpSet])->all();
    }

    public static function updateForumForGroups($deleteGrpSet)
    {
        $query = Forums::find()->where(['groupsetid' => $deleteGrpSet])->all();
        if($query)
        {
            foreach($query as $singleData)
            {
                $singleData->groupsetid = AppConstant::NUMERIC_ZERO;
                $singleData->save();
            }
        }
    }

    public static function updateForumData($setslist,$checkedlist)
    {
        $forums = Forums::find()->where(['IN','id',$checkedlist])->all();
        foreach($forums as $forum)
        {
            $forum->attributes = $setslist;
        }
    }

    public static function getForumName($forumId)
    {
        return self::find()->select('name')->where(['id' => $forumId])->one();
    }

    public static function updateForumMassChange($startdate, $enddate, $avail, $id)
    {
        $forum = Forums::findOne(['id' => $id]);
        $forum->startdate = $startdate;
        $forum->enddate = $enddate;
        $forum->avail = $avail;
        $forum->save();
    }

    public static function getForumMassChanges($courseId)
    {
        $query = Forums::find()->where(['courseid' => $courseId])->all();
        return $query;
    }

    public static function getByCid($courseId)
    {
        return Forums::find()->select('id')->where(['courseid' => $courseId])->all();
    }

    public static function deleteByCourseId($courseId)
    {
        $courseData = Forums::findOne(['courseid',$courseId]);
        if($courseData){
            $courseData->delete();
        }
    }
    public static function getDataByCourseId($courseId)
    {
        $query = new Query();
        $query->select(['id','name','startdate','enddate','avail'])
            ->from('imas_forums')
            ->where('courseid=:courseId',[':courseId' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function updateName($val, $typeId)
    {

        $form = Forums::findOne(['id' => $typeId]);
        $form->name = $val;
        $form->save();
    }

    public static function getByName($typeId)
    {
        return Forums::find()->select('name')->where(['id' => $typeId])->one();
    }

    public static function getForumId($thread,$courseId)
    {
        $query = new Query();
        $query->select('imas_forums.id')
            ->from('imas_forums')
            ->join('INNER JOIN',
                'imas_forum_threads',
                'imas_forums.id=imas_forum_threads.forumid'
            )
            ->where(['imas_forum_threads.id=:thread']);
        $query->andWhere('imas_forums.courseid=:courseId');
        $command = $query->createCommand()->bindValues(['thread' => $thread,'courseId'=> $courseId]);
        $items = $command->queryAll();
        return $items;
    }

    public static function getByCourseIdAndTeacher($courseId,$isteacher,$now)
    {
        //TODO: fix below query
        $query = "SELECT * FROM imas_forums WHERE imas_forums.courseid='$courseId'";
        if (!$isteacher) {
            $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now AND imas_forums.enddate>$now)) ";
        }
        $data = Yii::$app->db->createCommand($query)->queryAll();
        return $data;
    }

    public static function getMaxPostDate($cid)
    {
        $query = new Query();
        $query->select('imas_forums.id,COUNT(imas_forum_posts.id) AS postcount,MAX(imas_forum_posts.postdate)')
            ->from('imas_forums')
            ->join('LEFT JOIN',
                'imas_forum_posts',
                'imas_forums.id=imas_forum_posts.forumid'
            )
            ->where('imas_forums.courseid = :courseId')->groupBy('imas_forum_posts.forumid')->orderBy('imas_forums.id');
        $command = $query->createCommand()->bindValues(['courseId'=> $cid]);
        $items = $command->queryAll();
        return $items;
    }
}
