<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 2/5/15
 * Time: 4:17 PM
 */

namespace app\models;


use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasInlinetext;

class InlineText extends BaseImasInlinetext
{
    public static function getByCourseId($courseId)
    {
        return InlineText::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return InlineText::findOne(['id' => $id]);
    }

    public static function getByCourse($courseId)
    {
        return InlineText::findOne(['courseid' => $courseId]);
    }

    public function saveChanges($params)
    {
        $this->title = trim($params['title']);
        $this->text = $params['text'];
        $this->courseid = $params['courseid'];
        $this->startdate = $params['startdate'];
        $this->enddate = $params['enddate'];
        $this->avail = $params['avail'];
        $this->caltag = $params['caltag'];
        $this->isplaylist = $params['isplaylist'];
//        $this->oncal = $params['oncal'];
        $this->save();

        return $this->id;
    }

    public function updateChanges($params, $inlineTextId)
    {
        $endDate = AppUtility::parsedatetime($params['EndDate'], $params['end_end_time']);
        $startDate = AppUtility::parsedatetime($params['StartDate'], $params['start_end_time']);
        $tag = AppUtility::parsedatetime($params['Calendar'], $params['calendar_end_time']);

        $updateIdArray = InlineText::find()->where(['id' => $inlineTextId])->all();
        foreach ($updateIdArray as $key => $updateId) {
            if ($params['hidetitle'] == 1) {
                $params['title'] = '';
            }
            $updateId->title = isset($params['title']) ? $params['title'] : null;
            $updateId->courseid = $params['courseId'];
            $updateId->text = isset($params['inlineText']) ? $params['inlineText'] : null;
            $updateId->avail = isset($params['avail']) ? $params['avail'] : null;

            if ($params['avail'] == AppConstant::NUMERIC_ONE) {
                if ($params['available-after'] == 0) {
                    $startDate = 0;
                }
                if ($params['available-until'] == AppConstant::ALWAYS_TIME) {
                    $endDate = AppConstant::ALWAYS_TIME;
                }
                $this->startdate = $startDate;
                $this->enddate = $endDate;
            } else {
                $this->startdate = AppConstant::NUMERIC_ZERO;
                $this->enddate = AppConstant::ALWAYS_TIME;
            }

            $updateId->oncal = isset($params['oncal']) ? $params['oncal'] : null;
            if ($params['altoncal'] == 1) {
                $updateId->caltag = $params['altcaltag'];
            } else {
                $updateId->caltag = '!';
            }
            $updateId->isplaylist = 0;
            $updateId->save();
        }
    }

    public static function deleteInlineTextId($itemId)
    {
        $inlineTextData = InlineText::findOne(['id' => $itemId]);
        if ($inlineTextData) {
            $inlineTextData->delete();
        }
    }

    public static function setFileOrder($newtypeid, $addedfilelist)
    {
        $inlineData = InlineText::findOne(['id' => $newtypeid]);
        if ($inlineData) {
            $inlineData->fileorder = $addedfilelist;
            $inlineData->save();
        }
    }

    public static function getByIdLimited($id)
    {
        return InlineText::find()->select('title,text,startdate,enddate,avail,oncal,caltag,isplaylist,fileorder')->where(['id' => $id])->one();
    }

    public static function setStartDate($shift, $typeId)
    {
        $date = InlineText::find()->where(['id' => $typeId])->andWhere(['>', 'startdate', '0'])->one();
        if($date) {
            $date->startdate = $date->startdate + $shift;
            $date->save();
        }
    }


    public static function setEndDate($shift, $typeId)
    {

        $date = InlineText::find()->where(['id' => $typeId])->andWhere(['<', 'enddate', '2000000000'])->one();
        if($date) {
            $date->enddate = $date->enddate + $shift;
            $date->save();
        }
    }


}