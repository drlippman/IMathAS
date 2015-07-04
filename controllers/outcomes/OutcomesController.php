<?php
namespace app\controllers\outcomes;

use app\components\AppConstant;
use app\models\Assessments;
use app\models\Course;
use app\models\Forums;
use app\models\GbItems;
use app\models\GbScheme;
use app\models\Outcomes;
use app\models\Questions;
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
        $finalData = $this->outcomeTable($courseId);
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


public function getpts($sc){
    if (strpos($sc,'~')===false) {
        if ($sc>0) {
            return $sc;
        } else {
            return 0;
        }
    } else {
        $sc = explode('~',$sc);
        $tot = 0;
        foreach ($sc as $s) {
            if ($s>0) {
                $tot+=$s;
            }
        }
        return round($tot,1);
    }
}



 function outcomeTable($courseId)
{
    /***
    format of output

    row[0] header

    row[0][0] biographical
    row[0][0][0] = "Name"

    Will only included items that are counted in gradebook.  No EC. No PT
    row[0][1] scores
    row[0][1][#][0] = name
    row[0][1][#][1] = category color number
    row[0][1][#][2] = 0 past, 1 current
    row[0][1][#][3] = 1 count, 2 EC
    row[0][1][#][4] = 0 online, 1 offline, 2 discussion
    row[0][1][#][5] = assessmentid, gbitems.id, forumid

    row[0][2] category totals
    row[0][2][#][0] = "Category Name"
    row[0][2][#][1] = category color number

    row[1] first student data row
    row[1][0] biographical
    row[1][0][0] = "Name"
    row[1][0][1] = userid
    row[1][0][2] = locked?

    row[1][1] scores (all types - type is determined from header row)

    row[1][1][#][0][outc#] = score on outcome
    row[1][1][#][1][outc#] = poss score on outcome
    row[1][1][#][2] = other info: 0 none, 1 NC, 2 IP, 3 OT, 4 PT  + 10 if still active
    row[1][1][#][3] = asid or 'new', gradeid, or blank for discussions

    row[1][2] category totals
    row[1][2][#][0][outc#] = cat total past on outcome
    row[1][2][#][1][outc#] = cat poss past on outcome
    row[1][2][#][2][outc#] = cat total attempted on outcome
    row[1][2][#][3][outc#] = cat poss attempted on outcome

    row[1][3] total totals
    row[1][3][0][outc#] = % past on outcome
    row[1][3][1][outc#] = % attempted on outcome


     ***/
    global $cid,$isteacher,$istutor,$tutorid,$userid,$catfilter,$secfilter,$timefilter,$lnfilter,$isdiag,$sel1name,$sel2name,$canviewall,$hidelocked;
    if ($canviewall && func_num_args()>0) {
        $limuser = func_get_arg(0);
    } else if (!$canviewall) {
        $limuser = $userid;
    } else {
        $limuser = 0;
    }

    $category = array();
    $outc = array();
    $gb = array();
    $ln = 0;


    //Pull Gradebook Scheme info
     $query = GbScheme::getByCourseId($courseId);
    foreach($query as $data)
    {
        if($data['useweights'] == 2)
        {
            $data['useweights'] = 0;
        }

    }
//
//    if (isset($GLOBALS['setorderby'])) {
//        $orderby = $GLOBALS['setorderby'];
//    }
     $gb[0][0][0] = "Name";


        $query = Student::findCount($courseId);
        if($query[0]['count(id)']>0)
        {
            $hassection = true;
        }
        else
        {
            $hassection = false;
        }

     //Pull Assessment Info
     $query = Assessments::outcomeData($courseId,$catfilter,$istutor);
     $now = time();
     $kcnt = 0;
     $assessments = array();
     $grades = array();
     $discuss = array();
     $startdate = array();
     $enddate = array();
     $avail = array();
     $category = array();
     $name = array();
     $possible = array();
     $courseorder = array();
     $qposs = array();
     $qoutcome = array();
     $itemoutcome = array();

     foreach($query as $data)
     {
         if (substr($data['deffeedback'],0,8)=='Practice')
         {
           continue;
         }
         if ($data['avail']==2) {
             $data['startdate'] = 0;
             $data['enddate'] = 2000000000;
         }
         if ($now<$data['startdate']) {
             continue; //we don't want future items
         } else if ($now < $data['enddate']) {
             $avail[$kcnt] = 1;
         } else {
             $avail[$kcnt] = 0;
         }
         $enddate[$kcnt] = $data['enddate'];
         $startdate[$kcnt] = $data['startdate'];

         $assessments[$kcnt] = $data['id'];

         $category[$kcnt] = $data['gbcategory'];
         $name[$kcnt] = $data['name'];
         $cntingb[$kcnt] = $data['cntingb']; //1: count, 2: extra credit
         $assessoutcomes[$kcnt] = array();
         $aitems = explode(',',$data['itemorder']);
         foreach ($aitems as $k=>$v) {

             if (strpos($v,'~')!==FALSE) {
                 $sub = explode('~',$v);
                 if (strpos($sub[0],'|')===false) { //backwards compat
                     $aitems[$k] = $sub[0];
                     $aitemcnt[$k] = 1;

                 }
                 else {
                     $grpparts = explode('|',$sub[0]);
                     $aitems[$k] = $sub[1];
                     $aitemcnt[$k] = $grpparts[0];
                 }
             } else {
                 $aitemcnt[$k] = 1;
             }
         }

         $questions = Questions::findQuestionForOuctome($data['id']);
         $totalpossible = 0;
         foreach($questions as $questionData)
         {
             if ($questionData['points']==9999) {
                 $qposs[$questionData['id']] = $data['defpoints'];
             } else {
                 $qposs[$questionData['id']] = $questionData['points'];
             }
             if (is_numeric($questionData['category']) && $questionData['category']>0) {
                 $qoutcome[$questionData['id']] = $questionData['category'];
             } else if ($data['defoutcome']>0) {
                 $qoutcome[$questionData['id']] = $data['defoutcome'];
             }

         }
         $possible[$kcnt] = array();
             foreach ($aitems as $k=>$q)
             {

                 if (!isset($qoutcome[$q]))
                 {
                     continue;
                 }
                 if (!isset($possible[$kcnt][$qoutcome[$q]])) {
                     $possible[$kcnt][$qoutcome[$q]] = 0;
                 }
                 $possible[$kcnt][$qoutcome[$q]] += $aitemcnt[$k]*$qposs[$q];
             }
             $kcnt++;
     }
//Pull Offline Grade item info
     $offlineGradeInfo = GbItems::findOfflineGradeItemForOutcomes($courseId,$istutor,$catfilter, $now);

     foreach($offlineGradeInfo as $offlineInfo)
     {
         $avail[$kcnt] = 0;

         $grades[$kcnt] = $offlineInfo['id'];
         $assessmenttype[$kcnt] = "Offline";
         $category[$kcnt] = $offlineInfo['gbcategory'];
         $enddate[$kcnt] = $offlineInfo['showdate'];
         $startdate[$kcnt] = $offlineInfo['showdate'];
         $possible[$kcnt] = $offlineInfo['points'];
         $name[$kcnt] = $offlineInfo['name'];
         $cntingb[$kcnt] = $offlineInfo['cntingb'];
         $itemoutcome[$kcnt] = explode(',',$offlineInfo['outcomes']);
         $kcnt++;
     }

//Pull Discussion Grade info
     $discussionGradeInfo = Forums::getDiscussion($courseId,$catfilter);
     foreach($discussionGradeInfo as $GradeInfo)
     {

         $discuss[$kcnt] = $GradeInfo['id'];
         $assessmenttype[$kcnt] = "Discussion";
         $category[$kcnt] = $GradeInfo['gbcategory'];
         if ($GradeInfo['avail']==2) {
             $GradeInfo['startdate'] = 0;
             $GradeInfo['enddate'] = 2000000000;
         }
         $enddate[$kcnt] = $GradeInfo['enddate'];
         $startdate[$kcnt] = $GradeInfo['startdate'];
         if ($now < $GradeInfo['enddate']) {
             $avail[$kcnt] = 1;
             if ($GradeInfo['replyby'] > 0 && $GradeInfo['replyby'] < 2000000000) {
                 if ($GradeInfo['postby'] > 0 && $GradeInfo['postby'] < 2000000000) {
                     if ($now>$GradeInfo['replyby'] && $now>$GradeInfo['postby']) {
                         $avail[$kcnt] = 0;
                     }
                 } else {
                     if ($now>$GradeInfo['replyby']) {
                         $avail[$kcnt] = 0;
                     }
                 }
             } else if ($GradeInfo['postby'] > 0 && $GradeInfo['postby'] < 2000000000) {
                 if ($now>$GradeInfo['postby']) {
                     $avail[$kcnt] = 0;
                 }
             }
         } else {
             $avail[$kcnt] = 0;
         }
         $possible[$kcnt] = $GradeInfo['points'];
         $name[$kcnt] = $GradeInfo['name'];
         $cntingb[$kcnt] = $GradeInfo['cntingb'];
         $itemoutcome[$kcnt] = explode(',',$GradeInfo['outcomes']);
         $kcnt++;

     }
     $cats = array();
     $catcolcnt = 0;


    return 0;

}
}