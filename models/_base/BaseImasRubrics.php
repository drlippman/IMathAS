<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_rubrics".
 *
 * @property string $id
 * @property string $ownerid
 * @property integer $groupid
 * @property string $name
 * @property integer $rubrictype
 * @property string $rubric
 */
class BaseImasRubrics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_rubrics';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ownerid', 'name', 'rubric'], 'required'],
            [['ownerid', 'groupid', 'rubrictype'], 'integer'],
            [['rubric'], 'string'],
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
            'ownerid' => 'Ownerid',
            'groupid' => 'Groupid',
            'name' => 'Name',
            'rubrictype' => 'Rubrictype',
            'rubric' => 'Rubric',
        ];
    }
}
