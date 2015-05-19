<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_drillassess_sessions".
 *
 * @property string $id
 * @property string $drillassessid
 * @property string $userid
 * @property integer $curitem
 * @property integer $seed
 * @property string $curscores
 * @property string $starttime
 * @property string $scorerec
 *
 * @property ImasDrillassess $drillassess
 */
class BaseImasDrillassessSessions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_drillassess_sessions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['drillassessid', 'userid', 'curitem', 'seed', 'curscores', 'starttime', 'scorerec'], 'required'],
            [['drillassessid', 'userid', 'curitem', 'seed', 'starttime'], 'integer'],
            [['curscores', 'scorerec'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'drillassessid' => 'Drillassessid',
            'userid' => 'Userid',
            'curitem' => 'Curitem',
            'seed' => 'Seed',
            'curscores' => 'Curscores',
            'starttime' => 'Starttime',
            'scorerec' => 'Scorerec',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDrillassess()
    {
        return $this->hasOne(BaseImasDrillassess::className(), ['id' => 'drillassessid']);
    }
}
