<?php

namespace app\models;

use yii\base\Model;

class studentEnrollCourseForm extends Model
{
    public $courseId;
    public $enrollmentKey;

   /* public function rules()
    {
        return [
            [['courseId', 'enrollmentKey'], 'required'],
        ];

    }*/

    public function attributeLabels()
    {
        return
            [
                'courseId' => 'Course ID',
                'enrollmentKey' => 'Enrollment Key',
            ];
    }

}
