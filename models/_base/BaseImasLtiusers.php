<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_ltiusers".
 *
 * @property string $id
 * @property string $org
 * @property string $ltiuserid
 * @property integer $userid
 */
class BaseImasLtiusers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_ltiusers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['org', 'ltiuserid', 'userid'], 'required'],
            [['userid'], 'integer'],
            [['org', 'ltiuserid'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'org' => 'Org',
            'ltiuserid' => 'Ltiuserid',
            'userid' => 'Userid',
        ];
    }
}
