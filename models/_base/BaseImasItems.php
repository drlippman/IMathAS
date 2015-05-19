<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_items".
 *
 * @property string $id
 * @property string $courseid
 * @property string $itemtype
 * @property string $typeid
 *
 * @property ImasInstrFiles[] $imasInstrFiles
 * @property ImasCourses $course
 */
class BaseImasItems extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_items';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseid', 'itemtype', 'typeid'], 'required'],
            [['courseid', 'typeid'], 'integer'],
            [['itemtype'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'courseid' => 'Courseid',
            'itemtype' => 'Itemtype',
            'typeid' => 'Typeid',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasInstrFiles()
    {
        return $this->hasMany(BaseImasInstrFiles::className(), ['itemid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }
}
