<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_exceptions".
 *
 * @property string $id
 * @property string $userid
 * @property string $assessmentid
 * @property string $startdate
 * @property string $enddate
 * @property integer $islatepass
 * @property integer $waivereqscore
 */
class BaseImasExceptions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_exceptions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'assessmentid', 'startdate', 'enddate'], 'required'],
            [['userid', 'assessmentid', 'startdate', 'enddate', 'islatepass', 'waivereqscore'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'assessmentid' => 'Assessmentid',
            'startdate' => 'Startdate',
            'enddate' => 'Enddate',
            'islatepass' => 'Islatepass',
            'waivereqscore' => 'Waivereqscore',
        ];
    }
}
