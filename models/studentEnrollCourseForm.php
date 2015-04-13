<?php

namespace app\models;
use yii\base\Model;
class studentEnrollCourseForm extends Model
{
    public $courseId;
    public $enrollmentKey;

    public function rules()
    {

    }

    public function attributeLabels()
    {
        return
            [
                'courseId'=>'Course ID',
                'enrollmentKey'=>'Enrollment Key',

            ];
    }

}
