<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
class ChangeRightsForm extends Model
{
    public $rights;
    public $AssignToGroup;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
                'rights',
            'AssignToGroup',
        ];

    }

    public function attributeLabels()
    {
        return
            [
                'rights' => 'Set User rights  to',
                'AssignToGroup' => 'Assign To Group'
            ];
    }

}
