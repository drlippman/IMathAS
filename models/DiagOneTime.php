<?php

namespace app\models;

use app\models\_base\BaseImasDiagOnetime;

class DiagOneTime extends BaseImasDiagOnetime
{
    public static function deleteByTime($old, $now)
    {
        $query = DiagOneTime::find()->where(['<', 'time' => $old])->orWhere(['>', 'goodfor', 1000000000])->andWhere(['<', 'goodfor', $now])->all();
        return $query;
    }

    public static function getByDiag($diag)
    {
        $query = \Yii::$app->db->createCommand("SELECT time,code,goodfor FROM imas_diag_onetime WHERE diag='$diag' ORDER BY time")->queryAll();
        return $query;
    }

    public static function getByTime($now)
    {
        $query = \Yii::$app->db->createCommand("SELECT code,goodfor FROM imas_diag_onetime WHERE time='$now'")->queryAll();
        return $query;
    }

    public function generateDiagOneTime($diag, $now, $code, $goodfor)
    {
        $this->diag = $diag;
        $this->time = $now;
        $this->code = $code;
        $this->goodfor = $goodfor;
        $this->save();
        return $this->id;
    }

    public static function deleteDiagOneTime($diag)
    {
        $diagOneTime = DiagOneTime::find()->where(['diag' => $diag])->all();
        foreach ($diagOneTime as $key => $single) {
            $single->delete();
        }
    }

    public static function getByCode($password, $diagid)
    {
        $query = new Query();
        $query	->select(['id','goodfor'])
            ->from('imas_diag_onetime')
            ->where(['code' => $password]);
        $query->andWhere(['diag' => $diagid]);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function deleteById($diagId)
    {
        $diagOneTime = DiagOneTime::find()->where(['id' => $diagId])->one();
        if($diagOneTime)
        {
           $diagOneTime->delete();
        }
    }

    public static function setGoodFor($id, $expiry)
    {
        $setGood = static::findOne(['id' =>$id]);
        if($setGood)
        {
            $setGood->goodfor = $expiry;
            $setGood->save();
        }
    }

} 