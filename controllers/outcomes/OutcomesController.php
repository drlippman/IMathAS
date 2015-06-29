<?php
namespace app\controllers\outcomes;

use app\components\AppConstant;
use app\models\Course;
use app\models\Outcomes;
use app\models\User;
use app\components\AppUtility;
use app\controllers\AppController;
use Yii;

class OutcomesController extends AppController
{

    public function actionAddoutcomes()
    {
        $this->guestUserHandler();
        $courseid = $this->getParamVal('cid');
        $this->includeCSS(['outcomes.css']);
        return $this->render('addOutcomes',['courseid' => $courseid]);

    }

    public function actionGetOutcomeAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseid = $params['courseid'];
        $outcomearray = array();
        $outcomearray = $params['outcomearray'];
        foreach($outcomearray as $outcome)
        {
            $saveOutcome = new Outcomes();
            $saveOutcome->SaveOutcomes($courseid,$outcome);
        }
        return $this->successResponse();
    }

}