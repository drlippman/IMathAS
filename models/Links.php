<?php

namespace app\models;


use app\models\_base\BaseImasLinkedtext;

class Links extends BaseImasLinkedtext
{
    public static function getByCourseId($courseId)
    {
        return Links::findAll(['courseid' => $courseId]);
    }

    public static function getById($id)
    {
        return Links::findOne(['id' => $id]);
    }

    public static function deleteById($linkId){
        $linkData = Links::findOne(['id' => $linkId]);
        if($linkData){
            $linkData->delete();
        }
    }

    public static function getPoints($id)
    {
        return Links::find()->select('text,title,points')->where(['id' => $id])->one();
    }
} 