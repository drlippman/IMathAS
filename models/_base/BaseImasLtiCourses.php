<?php

namespace app\models\_base;

use Yii;

/**
 * This is the model class for table "imas_lti_courses".
 *
 * @property string $id
 * @property string $org
 * @property string $contextid
 * @property string $courseid
 */
class BaseImasLtiCourses extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imas_lti_courses';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['org', 'contextid', 'courseid'], 'required'],
            [['courseid'], 'integer'],
            [['org', 'contextid'], 'string', 'max' => 255]
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
            'contextid' => 'Contextid',
            'courseid' => 'Courseid',
        ];
    }
}
