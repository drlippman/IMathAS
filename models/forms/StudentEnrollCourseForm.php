<?php

namespace app\models\forms;

use yii\base\Model;

class StudentEnrollCourseForm extends Model
{
    public $courseId;
    public $enrollmentKey;
    public $selectCourse;

    public function rules()
    {
        return [
            [['courseId', 'enrollmentKey'], 'required'],
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'courseId' => 'Course ID',
                'enrollmentKey' => 'Enrollment Key',
            ];
    }

}
