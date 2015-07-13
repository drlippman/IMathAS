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
            ->andWhere(['>', 'points', 0])
            ->andWhere(['>', 'avail', 0]);
        if (!$canviewall) {
            $query->andWhere(['<','startdate', $now]);
        }
        if ($istutor) {
            $query->andWhere(['<','tutoredit', 2]);
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
            ->andWhere(['>', 'points', 0])
            ->andWhere(['>', 'avail', 0]);
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
        $endDate =   AppUtility::parsedatetime($params['edate'],$params['etime']);
        $startDate = AppUtility::parsedatetime($params['sdate'],$params['stime']);
        $replayPostDate = AppUtility::parsedatetime($params['replayPostDate'],$params['replayPostTime']);
        $newThreadDate = AppUtility::parsedatetime($params['newThreadDate'],$params['newThreadTime']);
        $settingValue = $params['allow-anonymous-posts']+$params['allow-students-to-modify-posts']+$params['allow-students-to-delete-own-posts']+$params['like-post'] + $params['viewing-before-posting'];


        $this->name = trim($params['title']);
        if(empty($params['forum-description']))
        {
            $params['forum-description'] = ' ';
        }
        $this->description = $params['forum-description'];
        $this->courseid = $params['cid'];
        $this->settings = $settingValue;
        if($params['avail'] == AppConstant::NUMERIC_ONE)
        {
            if($params['available-after'] == 0){
                $startDate = 0;
            }
            if($params['available-until'] == AppConstant::ALWAYS_TIME){
                $endDate = AppConstant::ALWAYS_TIME;
            }
            $this->startdate = $startDate;
            $this->enddate = $endDate;
        }else
        {
            $this->startdate = AppConstant::NUMERIC_ZERO;
            $this->enddate = AppConstant::ALWAYS_TIME;
        }
        $this->sortby = $params['sort-thread'];
        $this->defdisplay = $params['default-display'];
         if($params['reply-to-posts'] == AppConstant::NUMERIC_ONE){

            $this->postby = $replayPostDate;
        }else{
            $this->postby = $params['reply-to-posts'];
        }
        if($params['new-thread'] == AppConstant::NUMERIC_ONE){

            $this->replyby = $newThreadDate;
        }else{
            $this->replyby = $params['new-thread'];
        }

        $this->groupsetid = $params['group-forum'];
        $this->cntingb = $params['count-in-gradebook'];
        $this->avail = $params['avail'];
        $this->forumtype = $params['forum-type'];
        $this->caltag = $params['calendar-icon-text1'].'--'.$params['calendar-icon-text2'];
        $tagList = '';
        if($params['categorize-posts'] == AppConstant::NUMERIC_ONE){
            $tagList = trim($params['taglist']);
        }
            $this->taglist = $tagList;
        $this->gbcategory = $params['gradebook-category'];
        $this->points = $params['points'];
        $this->tutoredit = $params['tutor-access'];
        $this->rubric = $params['rubric'];
        $this->outcomes = $params['associate-outcomes'];
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
    public function updateForum($params,$forumId)
    {

        $endDate =   AppUtility::parsedatetime($params['edate'],$params['etime']);
        $startDate = AppUtility::parsedatetime($params['sdate'],$params['stime']);
        $replayPostDate = AppUtility::parsedatetime($params['replayPostDate'],$params['replayPostTime']);
        $newThreadDate = AppUtility::parsedatetime($params['newThreadDate'],$params['newThreadTime']);
        $settingValue = $params['allow-anonymous-posts']+$params['allow-students-to-modify-posts']+$params['allow-students-to-delete-own-posts']+$params['like-post'] + $params['viewing-before-posting'];

        $updateForumData = Forums::findOne(['id' => $forumId]);
//AppUtility::dump($updateForumData);
        $updateForumData->name = trim($params['title']);

        if(empty($params['forum-description']))
        {
            $params['forum-description'] = ' ';
        }
        $updateForumData->description = $params['forum-description'];
        $updateForumData->courseid = $params['cid'];
        $updateForumData->settings = $settingValue;

        if($params['avail'] == AppConstant::NUMERIC_ONE)
        {
            if($params['available-after'] == 0){
                $startDate = 0;
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
//        AppUtility::dump($updateForumData);
        if($params['reply-to-posts'] == AppConstant::NUMERIC_ONE){

            $updateForumData->postby = $replayPostDate;
        }else{
            $updateForumData->postby = $params['reply-to-posts'];
        }

        if($params['new-thread'] == AppConstant::NUMERIC_ONE){

            $updateForumData->replyby = $newThreadDate;
        }else{
            $updateForumData->replyby = $params['new-thread'];
        }

        $updateForumData->groupsetid = $params['group-forum'];
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
        $updateForumData->outcomes = $params['associate-outcomes'];
        $updateForumData->save();
    }

} 