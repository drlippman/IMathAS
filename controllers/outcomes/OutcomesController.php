<?php
namespace app\controllers\outcomes;

use app\components\AppConstant;
use app\models\Course;
use app\models\User;
use app\components\AppUtility;
use app\controllers\AppController;
use Yii;

class OutcomesController extends AppController
{

    public function actionAddoutcomes()
    {
        $this->includeCSS(['outcomes.css']);
        return $this->render('addOutcomes');

    }

}