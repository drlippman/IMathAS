<?php
namespace app\models\forms;

use app\models\_base\BaseImasCourses;
use Yii;
use yii\base\Model;
class EnrollFromOtherCourseForm extends model{


    public $rights;


    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [    ];

    }

    public function attributeLabels()
    {
        return ['rights'=>'Select a course to choose students from:',
        ];
    }
    public static function findById(){
        BaseImasCourses::find()->all();
    }

}

