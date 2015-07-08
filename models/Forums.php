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

        }else
        {
            $this->startdate = AppConstant::NUMERIC_ZERO;
            $this->enddate = AppConstant::ALWAYS_TIME;
        }
//        $this->sortby = $params['sort-thread'];
        $this->defdisplay = $params['default-display'];
        if($params['reply-to-posts'] > AppConstant::NUMERIC_ZERO && $params['reply-to-posts'] < AppConstant::ALWAYS_TIME){

        }
        $this->replyby = $params['reply-to-posts'];
        $this->postby = $params['new-thread'];
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
//        AppUtility::dump($this);
        $this->save();
        return $this->id;
    }
    public function deleteForum($params)
    {
        $forum = Forums::findOne(['id' => $params['id']]);
        if($forum){
            $forum->delete();
        }

    }

} 