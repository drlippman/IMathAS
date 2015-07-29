<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 29/4/15
 * Time: 4:46 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasWikis;

class Wiki extends BaseImasWikis
{
    public static function getByCourseId($courseId)
    {
        return static::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return static::findOne(['id' => $id]);
    }

    public static function getAllData($wikiId)
    {
        $query = Wiki::find(['name','startdate','enddate','editbydate','avail'])->where(['id' => $wikiId])->all();
        return $query;
    }

    public function createItem($params)
    {
        $endDate = AppUtility::parsedatetime($params['EndDate'],$params['end_end_time']);
        $startDate = AppUtility::parsedatetime($params['StartDate'],$params['start_end_time']);
        $tag = AppUtility::parsedatetime($params['Calendar'],$params['calendar_end_time']);

        $this->name = isset($params['name']) ? $params['name'] : null;
        $this->courseid = $params['courseId'];
        if(empty($params['description']))
        {
            $params['description'] = ' ';
        }
        $this->description = isset($params['description']) ? $params['description'] : null;
        $this->avail = isset($params['avail']) ? $params['avail'] : null;

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
        $this->settings = 0;

        if($params['rdatetype'] == AppConstant::NUMERIC_ZERO || $params['rdatetype'] == AppConstant::ALWAYS_TIME){
            $tag = $params['rdatetype'];
        }
//        $this->editbydate = 200000;
        if(!empty($params['group-wiki']))
        {
            $this->groupsetid = $params['group-wiki'];
        }
        $this->save();
//        AppUtility::dump($this);
        return $this->id;
    }

    public function updateChange($params, $wiki)
    {
        $endDate =   AppUtility::parsedatetime($params['EndDate'],$params['end_end_time']);
        $startDate = AppUtility::parsedatetime($params['StartDate'],$params['start_end_time']);
        $tag = AppUtility::parsedatetime($params['Calendar'],$params['calendar_end_time']);

        $updateIdArray = Wiki::find()->where(['id' => $wiki])->all();
        foreach($updateIdArray as $key => $updateId)
        {
            $updateId->name = isset($params['name']) ? $params['name'] : null;
            $updateId->courseid = $params['courseId'];
            $updateId->description = isset($params['description']) ? $params['description'] : null;
            $updateId->avail = isset($params['avail']) ? $params['avail'] : null;

            if($params['avail'] == AppConstant::NUMERIC_ONE)
            {
                if($params['available-after'] == 0){
                    $startDate = 0;
                }
                if($params['available-until'] == AppConstant::ALWAYS_TIME){
                    $endDate = AppConstant::ALWAYS_TIME;
                }
                $updateId->startdate = $startDate;
                $updateId->enddate = $endDate;
            }else
            {
                $updateId->startdate = AppConstant::NUMERIC_ZERO;
                $updateId->enddate = AppConstant::ALWAYS_TIME;
            }
            $updateId->settings = 0;

            if($params['rdatetype'] == AppConstant::NUMERIC_ZERO || $params['rdatetype'] == AppConstant::ALWAYS_TIME){
                $tag = $params['rdatetype'];
            }
            $updateId->editbydate = $tag;
            $updateId->groupsetid = $params['group-wiki'];
            $updateId->save();
        }
    }

    public static function deleteById($itemId){
        $wikiData = Wiki::findOne($itemId);
        if($wikiData){
            $wikiData->delete();
        }
    }
    public static function getAllDataWiki($wikiId)
    {
        $query =\Yii::$app->db->createCommand("SELECT name,startdate,enddate,editbydate,avail FROM imas_wikis WHERE id='$wikiId'")->queryOne();
        return $query;
    }
} 