<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_stugroupmembers".
 *
 * @property string $id
 * @property string $stugroupid
 * @property string $userid
 *
 * @property ImasUsers $user
 * @property ImasStugroups $stugroup
 */
class BaseImasStugroupmembers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_stugroupmembers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['stugroupid', 'userid'], 'required'],
            [['stugroupid', 'userid'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stugroupid' => 'Stugroupid',
            'userid' => 'Userid',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'userid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStugroup()
    {
        return $this->hasOne(BaseImasStugroups::className(), ['id' => 'stugroupid']);
    }
}
