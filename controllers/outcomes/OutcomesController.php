<?php
namespace app\controllers\outcomes;

use app\components\AppConstant;
use app\models\Course;
use app\models\Outcomes;
use app\models\Student;
use app\models\User;
use app\components\AppUtility;
use app\controllers\AppController;
use Yii;

class OutcomesController extends AppController
{

    public function actionAddOutcomes()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $this->includeCSS(['outcomes.css']);
        return $this->render('addOutcomes',['courseId' => $courseId]);

    }

    public function actionGetOutcomeAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $outcomeArray = $params['outcomeArray'];
        foreach($outcomeArray as $outcome)
        {
            $saveOutcome = new Outcomes();
            $saveOutcome->SaveOutcomes($courseId,$outcome);
        }
        return $this->successResponse();
    }
    public function actionGetOutcomeGrpAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $outcomeGrpArray = $params['outcomeGrpArray'];
        foreach($outcomeGrpArray as $outcomeGrp)
        {
            $serializedOutcomeGrp = serialize($outcomeGrp);
            $saveOutcome = new Course();
            $saveOutcome->SaveOutcomes($courseId,$serializedOutcomeGrp);
        }
        return $this->successResponse();
    }

    public function actionGetOutcomeDataAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['courseId'];
        $courseOutcomeArray = array();
        $courseOutcomeData = Course::getById($courseId);
        $courseOutcome = unserialize($courseOutcomeData['outcomes']);
        foreach ($courseOutcome as $outcome)
        {
            if(is_array($outcome))
            {

                $tempArray = array(
                    'outcomes' => $outcome['name'],
                );
                array_push($courseOutcomeArray,$tempArray['outcomes']);
             }
        }
        $outcomeData = Outcomes::getByCourseId($courseId);
        $outcomeDataArray = array();
        foreach($outcomeData as $data){
            $tempArray = array(

                'name' =>$data['name'],
            );
            array_push($outcomeDataArray,$tempArray);
        }
        $responseData = array('courseOutcome' => $courseOutcomeArray,'outcomeData' => $outcomeDataArray);
        return $this->successResponse($responseData);
    }

    public function actionOutcomeReport()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $this->includeCSS(['dataTables.bootstrap.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js','general.js' ]);
        return $this->render('outcomeReport',['courseId' => $courseId]);
    }

    public function actionGetOutcomeReportAjax()
    {
        $courseId = $this->getRequestParams('courseId');
        $studentOutcomeReport = Student::getByCourse($courseId);
        $outcomeData = Outcomes::getByCourseId($courseId);
        $studentOutcomeReportArray = array();
        $headerArray =array();
        $headerArray = ['Name'];

       for($i=1;$i<=count($outcomeData);$i++)
       {
           $headerArray[$i]=$outcomeData[$i-1]['name'];

       }
        $studentOutcomeReportArray = array('header'=>$headerArray);
        $tempArray = array();
        foreach($studentOutcomeReport as $studentData)
        {
            $temp = array(

                'userName' => $studentData->user->FirstName.' '.$studentData->user->LastName,
            );
            array_push($tempArray,$temp);
        }
        $studentOutcomeReportArray['data'] = ($tempArray);
        $studentOutcomeReportArray['Average'] = ('hi');
        $responseData = array('studentOutcomeReportArray' => $studentOutcomeReportArray);
        return $this->successResponse($responseData);
    }

}