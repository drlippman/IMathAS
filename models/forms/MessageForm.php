<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
class MessageForm extends Model
{
    public $message;
    public $sendBySender;

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
            'message'=>'Filter By Course:',
            'sendBySender'=>'Send By Sender',

        ];
    }
}
