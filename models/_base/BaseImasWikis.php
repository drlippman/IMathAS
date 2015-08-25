<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_wikis".
 *
 * @property string $id
 * @property string $name
 * @property string $description
 * @property string $courseid
 * @property string $startdate
 * @property string $editbydate
 * @property string $enddate
 * @property integer $settings
 * @property string $groupsetid
 * @property integer $avail
 *
 * @property ImasWikiViews[] $imasWikiViews
 * @property ImasCourses $course
 */
class BaseImasWikis extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_wikis';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['name', 'description', 'courseid', 'startdate', 'editbydate', 'enddate'], 'required'],
//            [['description'], 'string'],
//            [['courseid', 'startdate', 'editbydate', 'enddate', 'settings', 'groupsetid', 'avail'], 'integer'],
//            [['name'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'courseid' => 'Courseid',
            'startdate' => 'Startdate',
            'editbydate' => 'Editbydate',
            'enddate' => 'Enddate',
            'settings' => 'Settings',
            'groupsetid' => 'Groupsetid',
            'avail' => 'Avail',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImasWikiViews()
    {
        return $this->hasMany(BaseImasWikiViews::className(), ['wikiid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(BaseImasCourses::className(), ['id' => 'courseid']);
    }
}
