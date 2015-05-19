<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
class DeleteCourseForm extends Model
{
    public $name;
    public $cid;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

        ];

    }

    public function attributeLabels()
    {
        return
            [
                

            ];
    }


}
