<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_students".
 *
 * @property string $id
 * @property string $userid
 * @property string $courseid
 * @property string $section
 * @property string $code
 * @property string $gbcomment
 * @property string $gbinstrcomment
 * @property integer $latepass
 * @property string $lastaccess
 * @property string $locked
 * @property integer $hidefromcourselist
 * @property string $timelimitmult
 * @property integer $stutype
 * @property string $custominfo
 *
 * @property ImasCourses $course
 * @property ImasUsers $user
 */
class BaseImasStudents extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_students';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'courseid', 'latepass', 'lastaccess', 'locked', 'hidefromcourselist', 'stutype'], 'integer'],
            [['gbcomment', 'gbinstrcomment', 'custominfo'], 'required'],
            [['gbcomment', 'gbinstrcomment', 'custominfo'], 'string'],
            [['timelimitmult'], 'number'],
            [['section'], 'string', 'max' => 40],
            [['code'], 'string', 'max' => 32]
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
            'courseid' => 'Courseid',
            'section' => 'Section',
            'code' => 'Code',
            'gbcomment' => 'Gbcomment',
            'gbinstrcomment' => 'Gbinstrcomment',
            'latepass' => 'Latepass',
            'lastaccess' => 'Lastaccess',
            'locked' => 'Locked',
            'hidefromcourselist' => 'Hidefromcourselist',
            'timelimitmult' => 'Timelimitmult',
            'stutype' => 'Stutype',
            'custominfo' => 'Custominfo',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(ImasCourses::className(), ['id' => 'courseid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(ImasUsers::className(), ['id' => 'userid']);
    }
}
