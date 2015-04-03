<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_badgerecords".
 *
 * @property string $id
 * @property string $userid
 * @property string $badgeid
 * @property string $data
 */
class BaseImasBadgerecords extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_badgerecords';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'badgeid', 'data'], 'required'],
            [['userid', 'badgeid'], 'integer'],
            [['data'], 'string']
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
            'badgeid' => 'Badgeid',
            'data' => 'Data',
        ];
    }
}
