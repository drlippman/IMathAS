<?php

namespace app\models\forms;

use app\components\AppUtility;
use app\components\AppConstant;
use Yii;
use yii\base\Model;
use app\controllers\instructor\InstructorController;

class ManageEventForm extends Model
{
    public $tag;
    public $eventDetails;
    public $newTag;
    public $newEventDetails;
    public $delete;

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
        return [
            'tag' => '',
            'eventDetails' => '',
            'newTag' => '',
            'newEventDetails' => '',
            'delete'=> ''
        ];
    }
}