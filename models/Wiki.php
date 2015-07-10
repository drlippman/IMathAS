<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 29/4/15
 * Time: 4:46 PM
 */

namespace app\models;


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
        $query =\Yii::$app->db->createCommand("SELECT name,startdate,enddate,editbydate,avail FROM imas_wikis WHERE id='$wikiId'")->queryAll();
        return $query;
    }

    public function createItem($params)
    {
        AppUtility::dump($params);
        $this->name = isset($params['name']) ? $params['name'] : null;
        $this->courseid =$params['cid'];;
        if(empty($params['description']))
        {
            $params['description'] = ' ';
        }
        $this->description = isset($params['description']) ? $params['description'] : null;
        $this->avail = isset($params['avail']) ? $params['avail'] : null;

        if($params['avail'] == 1)
        {
//            $this->startdate = 1435293000;
            $this->startdate = isset($params['sdatetype']) ? $params['sdatetype'] : null;
            $this->enddate = isset($params['EventDate']) ? $params['EventDate'] : null;
//            $this->enddate = 2000000000;
        }
        $this->settings = 0;
        $this->editbydate = 2000000000;
        $this->save();
        return $this->id;
    }

    public function updateChange($params, $wiki)
    {
        $updateIdArray = Wiki::find()->where(['id' => $wiki])->all();
        foreach($updateIdArray as $key => $updateId)
        {
            $updateId->name = isset($params['name']) ? $params['name'] : null;
            $updateId->courseid = 1;
            $updateId->description = isset($params['description']) ? $params['description'] : null;
            $updateId->avail = isset($params['avail']) ? $params['avail'] : null;

            if($params['avail'] == 1)
            {
//            $this->startdate = 1435293000;
                $this->startdate = isset($params['sdatetype']) ? $params['sdatetype'] : null;
                $this->enddate = isset($params['EventDate']) ? $params['EventDate'] : null;
//            $this->enddate = 2000000000;
            }
            $updateId->settings = 0;
            $updateId->editbydate = 2000000000;
            $updateId->save();
        }
    }
} 