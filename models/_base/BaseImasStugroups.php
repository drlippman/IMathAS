<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_stugroups".
 *
 * @property string $id
 * @property string $groupsetid
 * @property string $name
 */
class BaseImasStugroups extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_stugroups';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['groupsetid', 'name'], 'required'],
            [['groupsetid'], 'integer'],
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
            'groupsetid' => 'Groupsetid',
            'name' => 'Name',
        ];
    }
}
