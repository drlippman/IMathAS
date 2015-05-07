<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
class ChangeRightsForm extends Model
{
    public $rights;
    public $groupid;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [


            ['groupid', 'string'],
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'rights' => 'Set User rights  to',
                'groupid' => 'Assign To Group'
            ];
    }

}
