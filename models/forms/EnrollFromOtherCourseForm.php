<?php
namespace app\models\forms;

use app\components\AppUtility;
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
        return ['rights'=>AppUtility::t('Select a course to choose students from:',false),
        ];
    }
    public static function findById(){
        BaseImasCourses::find()->all();
    }

}

