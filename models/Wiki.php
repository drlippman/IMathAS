<?php

namespace app\models;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\_base\BaseImasWikis;
use yii\db\Query;

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
        $query = Wiki::find(['name', 'startdate', 'enddate', 'editbydate', 'avail'])->where(['id' => $wikiId])->all();
        return $query;
    }

    public function createItem($params)
    {
        $this->courseid = $params['courseid'];
        $this->name = $params['title'];
        $this->description = $params['description'];
        $this->avail = $params['avail'];
        $this->startdate = $params['startdate'];
        $this->enddate = $params['enddate'];
        $this->editbydate=$params['editbydate'];
        $this->save();
        return $this->id;
    }

    public function updateChange($params)
    {
        $updateWiki = Wiki::findOne(['id' => $params['id']]);
        $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
        $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
        $updateWiki->courseid = $params['cid'];
        $updateWiki->name = $params['name'];
        $updateWiki->description = $params['description'];
        $updateWiki->avail = $params['avail'];
        $updateWiki->editbydate=$params['rdatetype'];
        if ($params['avail'] == AppConstant::NUMERIC_ONE) {
            if ($params['available-after'] == AppConstant::NUMERIC_ZERO) {
                $startDate = AppConstant::NUMERIC_ZERO;
            }
            if ($params['available-until'] == AppConstant::ALWAYS_TIME) {
                $endDate = AppConstant::ALWAYS_TIME;
            }
            $updateWiki->startdate = $startDate;
            $updateWiki->enddate = $endDate;
        } else {
            $updateWiki->startdate = AppConstant::NUMERIC_ZERO;
            $updateWiki->enddate = AppConstant::ALWAYS_TIME;
        }
        $updateWiki->save();
    }

    public static function deleteById($itemId)
    {
        $wikiData = Wiki::findOne($itemId);
        if ($wikiData) {
            $wikiData->delete();
        }
    }

    public static function getAllDataWiki($wikiId)
    {
        return Wiki::find()->select('name,startdate,enddate,editbydate,avail')->where(['id' => $wikiId])->one();
    }

    public function addWiki($wiki)
    {
        $this->courseid = isset($wiki['courseid']) ? $wiki['courseid'] : null;
        $this->name = isset($wiki['name']) ? $wiki['name'] : null;
        $this->description = isset($wiki['description']) ? $wiki['description'] : null;
        $this->startdate = isset($wiki['startdate']) ? $wiki['startdate'] : null;
        $this->enddate = isset($wiki['enddate']) ? $wiki['enddate'] : null;
        $this->editbydate = isset($wiki['editbydate']) ? $wiki['editbydate'] : null;
        $this->avail = isset($wiki['avail']) ? $wiki['avail'] : null;
        $this->settings = isset($wiki['settings']) ? $wiki['settings'] : null;
        $this->groupsetid = isset($wiki['groupsetid']) ? $wiki['groupsetid'] : null;
        $this->save();
        return $this->id;
    }

    public static function setEditByDate($shift, $typeId)
    {
        $date = Wiki::find()->where(['id' => $typeId])->andWhere(['>', 'editbydate', '0'])->andWhere(['<', 'editbydate', '2000000000'])->one();
        if ($date) {
            $date->editbydate = $date['editbydate'] + $shift;
            $date->save();
        }
    }

    public static function getByGroupSetId($deleteGrpSet)
    {
        return Wiki::find()->where(['groupsetid' => $deleteGrpSet])->all();
    }

    public static function updateWikiForGroups($deleteGrpSet)
    {
        $query = Wiki::find()->where(['groupsetid' => $deleteGrpSet])->all();
        if ($query) {
            foreach ($query as $singleData) {
                $singleData->groupsetid = AppConstant::NUMERIC_ZERO;
                $singleData->save();
            }
        }
    }

    public static function updateWikiById($startdate, $enddate, $avail, $id)
    {
        $wiki = Wiki::findOne(['id' => $id]);
        if ($wiki) {
            $wiki->startdate = $startdate;
            $wiki->enddate = $enddate;
            $wiki->avail = $avail;
            $wiki->save();
        }
    }

    public static function getWikiMassChanges($courseId)
    {
        $query = Wiki::find()->where(['courseid' => $courseId])->all();
        return $query;
    }

    public static function getByCourseIdAll($courseId)
    {
        return Wiki::find()->select('id')->where(['courseid' => $courseId])->all();
    }

    public static function deleteCourseId($courseId)
    {
        $wikiData = Wiki::find()->where(['courseid' => $courseId])->one();
        if ($wikiData) {
            $wikiData->delete();
        }
    }

    public static function getDataByCourseId($courseId)
    {
        $query = new Query();
        $query->select(['id','name','startdate','enddate','avail'])
            ->from('imas_wikis')
            ->where('courseid=:courseId',[':courseId' => $courseId]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public function updateName($val, $typeId)
    {
        $form = Wiki::findOne(['id' => $typeId]);
        $form->name = $val;
        $form->save();
    }

    public static function getByName($typeId)
    {
        return Wiki::find()->select('name')->where(['id' => $typeId])->one();
    }

    public static function getDataById($id)
    {
        return self::find()->select(['name','startdate','enddate','editbydate','avail','groupsetid'])->where(['id' => $id])->one();
    }
} 