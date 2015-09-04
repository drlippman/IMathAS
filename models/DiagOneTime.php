<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 3/9/15
 * Time: 7:12 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasDiagOnetime;
use yii\db\Query;

class DiagOneTime extends BaseImasDiagOnetime
{
    public static function deleteByTime($old, $now)
    {
        $query = DiagOneTime::find()->where(['<','time' => $old])->orWhere(['>','goodfor', 1000000000])->andWhere(['<','goodfor',$now])->all();
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
    public  static function deleteDiagOneTime($diag)
    {
        $diagOneTime = DiagOneTime::find()->where(['diag' => $diag])->all();
       foreach($diagOneTime as $key => $single){
           $single->delete();
        }
    }
} 