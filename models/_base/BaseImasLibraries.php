<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_libraries".
 *
 * @property string $id
 * @property string $uniqueid
 * @property string $adddate
 * @property string $lastmoddate
 * @property string $name
 * @property string $ownerid
 * @property integer $userights
 * @property integer $sortorder
 * @property string $parent
 * @property string $groupid
 *
 * @property ImasUsers $owner
 */
class BaseImasLibraries extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_libraries';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uniqueid', 'adddate', 'lastmoddate', 'ownerid', 'userights', 'sortorder', 'parent', 'groupid'], 'integer'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uniqueid' => 'Uniqueid',
            'adddate' => 'Adddate',
            'lastmoddate' => 'Lastmoddate',
            'name' => 'Name',
            'ownerid' => 'Ownerid',
            'userights' => 'Userights',
            'sortorder' => 'Sortorder',
            'parent' => 'Parent',
            'groupid' => 'Groupid',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(BaseImasUsers::className(), ['id' => 'ownerid']);
    }
}
