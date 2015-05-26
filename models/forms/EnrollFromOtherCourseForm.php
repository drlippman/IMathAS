<?php
namespace app\models\forms;

use Yii;
use yii\base\Model;
class EnrollFromOtherCourseForm extends model{

    public $username;
    public $FirstName;
    public $LastName;
    public $email;
    public $password;
    public $rights;
    public $AssignToGroup;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [        ];

    }

    public function attributeLabels()
    {
        return ['rights'=>'Select a course to choose students from:',

        ];
    }
}

