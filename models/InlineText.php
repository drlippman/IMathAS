<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 2/5/15
 * Time: 4:17 PM
 */

namespace app\models;


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
        $this->title = isset($params['title']) ? $params['title'] : null;
        $this->courseid = 1;
        $this->text = isset($params['inlineText']) ? $params['inlineText'] : null;
        $this->avail = isset($params['avail']) ? $params['avail'] : null;

        if($params['avail'] == 1)
        {
            AppUtility::dump('hii');
//            $this->startdate = 1435293000;
            $this->startdate = isset($params['sdatetype']) ? $params['sdatetype'] : null;
            $this->enddate = isset($params['EventDate']) ? $params['EventDate'] : null;
//            $this->enddate = 2000000000;
        }
        $this->oncal = isset($params['oncal']) ? $params['oncal'] : null;
        $this->caltag ='!';
        $this->isplaylist = 0;
        $this->save();
        return $this->id;
    }

    public function updateChanges($params, $inlineTextId)
    {
        $updateIdArray = InlineText::find()->where(['id' => $inlineTextId])->all();
        foreach($updateIdArray as $key => $updateId)
        {
            if($params['hidetitle'] == '##hidden##')
            {
                $updateId->title = '';
            }
        $updateId->title = isset($params['title']) ? $params['title'] : null;
        $updateId->courseid = 1;
        $updateId->text = isset($params['inlineText']) ? $params['inlineText'] : null;
        $updateId->avail = isset($params['avail']) ? $params['avail'] : null;
            if($params['avail'] == 1)
            {
//                AppUtility::dump('hii');
//                $this->startdate = 1435293000;
////        $this->enddate = isset($params['EventDate']) ? $params['EventDate'] : null;
//                $this->enddate = 2000000000;
                $this->startdate = isset($params['sdatetype']) ? $params['sdatetype'] : null;
                $this->enddate = isset($params['edatetype']) ? $params['edatetype'] : null;
            }
        $updateId->enddate = 2000000000;
        $updateId->oncal = isset($params['oncal']) ? $params['oncal'] : null;
        $updateId->caltag ='!';
        $updateId->isplaylist = 0;
        $updateId->save();
        }
    }

    public static function deleteInlineTextId($itemId)
    {
        $inlineTextData = InlineText::findOne(['id' => $itemId]);
        if($inlineTextData){
            $inlineTextData->delete();
        }
    }
} 