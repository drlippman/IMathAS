<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
class AdminIndexForm extends Model
{
    public $username;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [

            [['username'], 'string'],

        ];

    }

    /*public function attributeLabels()
    {
        return [



        ];
    }*/
}
