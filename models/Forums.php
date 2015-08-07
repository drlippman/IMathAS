<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 29/4/15
 * Time: 12:30 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\models\_base\BaseImasForums;
use yii\db\Query;

class Forums extends BaseImasForums {

    public static function getByCourseId($courseId)
    {
        return Forums::findAll(['courseid' => $courseId]);
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
            ->where(['courseid'=>$courseId])
            ->andWhere(['>', 'points', AppConstant::NUMERIC_ZERO])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO]);
        if (!$canviewall) {
            $query->andWhere(['<','startdate', $now]);
        }
        if ($istutor) {
            $query->andWhere(['<','tutoredit', AppConstant::NUMERIC_TWO]);
        }
        if ($catfilter> AppConstant::NUMERIC_NEGATIVE_ONE) {
            $query->andWhere(['gbcategory' => $catfilter]);
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
            ->where(['courseid'=>$courseId])
            ->andWhere(['>', 'points', AppConstant::NUMERIC_ZERO])
            ->andWhere(['>', 'avail', AppConstant::NUMERIC_ZERO]);
        if ($catfilter>AppConstant::NUMERIC_NEGATIVE_ONE) {
            $query->andWhere(['gbcategory' => $catfilter]);
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
        $this->rubric = isset($params['rubric']) ? $params['rubric'] : null;
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
    public function updateForum($params)
    {

        $endDate =   AssessmentUtility::parsedatetime($params['edate'],$params['etime']);
        $startDate = AssessmentUtility::parsedatetime($params['sdate'],$params['stime']);
        $postDate = AppUtility::parsedatetime($params['postDate'],$params['postTime']);
        $replyByDate = AppUtility::parsedatetime($params['replyByDate'],$params['replyByTime']);
        $settingValue = $params['allow-anonymous-posts']+$params['allow-students-to-modify-posts']+$params['allow-students-to-delete-own-posts']+$params['like-post'] + $params['viewing-before-posting'];
        $updateForumData = Forums::findOne(['id' => $params['modifyFid']]);
        $updateForumData->name = trim($params['name']);
        if(empty($params['forum-description']))
        {
            $params['forum-description'] = ' ';
        }
        $updateForumData->description = $params['forum-description'];
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
        if ($rubricData){
            $rubricData->rubric = $data;
            $rubricData->save();
        }
    }
}
