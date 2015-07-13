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
        /*if ($istutor) {
            $query->andWhere(['<','tutoredit', 2]);
        }
        if ($catfilter>-1) {
            $query->andWhere(['gbcategory' => $catfilter]);
        }*/
        $query->orderBy('enddate, startdate');
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }
    public function AddLinkedText($params,$outcomes)
    {
//        courseid,title,summary,text,startdate,enddate,avail,oncal,caltag,target,outcomes,points

        $endDate =   AppUtility::parsedatetime($params['edate'],$params['etime']);
        $startDate = AppUtility::parsedatetime($params['sdate'],$params['stime']);
        $this->courseid = $params['cid'];
        $this->title = $params['name'];
        $this->summary = $params['summary'];
        $this->text = $params['text'];
        $this->avail = $params['avail'];
        $this->oncal = $params['place-on-calendar'];
        $this->caltag = $params['tag'];
        $this->target = $params['open-page-in'];
        $this->outcomes = $outcomes;
        $this->points= $params['points'];
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
        $this->save();
        return $this->id;
    }
}