<?php
namespace app\controllers\outcomes;

use app\components\AppConstant;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
use app\models\Forums;
use app\models\GbCats;
use app\models\GbItems;
use app\models\GbScheme;
use app\models\Grades;
use app\models\InlineText;
use app\models\LinkedText;
use app\models\Outcomes;
use app\models\Questions;
use app\models\Student;
use app\models\User;
use app\components\AppUtility;
use app\controllers\AppController;
use Yii;

class OutcomesController extends AppController
{
    public $currOutcomesArray = array();
    public $seenOutcomesArr = array();
    public $courseId;
    public $params;

    public function beforeAction($action)
    {
        $courseId = $this->getParamVal('cid');
        $user = $this->getAuthenticatedUser();
        $teacherId = $this->isTeacher($user['id'], $courseId);
        if (($user['rights'] < AppConstant::TEACHER_RIGHT) || ($user['rights'] > AppConstant::STUDENT_RIGHT && !$teacherId)) {
            return $this->noValidRights($teacherId);
        }
        return true;
    }

    public function actionAddOutcomes()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $this->courseId = $this->getParamVal('cid');
        $this->params = $this->getRequestParams();
        if(isset($this->params['order']))
        {
            $query = Outcomes::getExistingOutcomes($this->courseId);
            foreach($query as $singleOutcome)
            {
               $this->currOutcomesArray[$singleOutcome['id']] = $singleOutcome['name'];

            }
            $itemArray = $this->addItems($this->params['order']);
            $outcomeOrder = serialize($itemArray);
            $saveOutcome = new Course();
            $saveOutcome->SaveOutcomes($this->courseId,$outcomeOrder);
            $unused = array_diff(array_keys($this->currOutcomesArray), $this->seenOutcomesArr);
            if(count($unused) > AppConstant::NUMERIC_ZERO)
            {
                $unusedList = implode(',',$unused);
                Outcomes::deleteUnusedOutcomes($unusedList);
                Assessments::updateOutcomes($this->courseId,$unusedList);
            }
        }
        //load existing outcomes
        $courseOutcomeData = Course::getByCourseIdOutcomes($this->courseId);
        if(($courseOutcomeData[0]['outcomes']) == '')
        {
            $outcomes = array();
        }
        else
        {
            $outcomes = unserialize(($courseOutcomeData[0]['outcomes']));
        }
        $outcomeData = Outcomes::getExistingOutcomes($this->courseId);
        foreach($outcomeData as $data)
        {
            $outcomeInfo[$data['id']] = $data['name'];
        }
        $this->includeCSS(['outcomes.css']);
        $this->includeJS(['mootools.js','nested1.js']);
        return $this->render('addOutcomes',['courseId' => $this->courseId,'outcomes' => $outcomes,'outcomeInfo' => $outcomeInfo,'order' => $this->params['order']]);
    }

    function addItems($list)
    {
        $outArr = array();
        $list = substr($list,1,-1);
        $i = AppConstant::NUMERIC_ZERO; $nd = AppConstant::NUMERIC_ZERO; $last = AppConstant::NUMERIC_ZERO;
        $listArr = array();
        while ($i<strlen($list))
        {
            if ($list[$i]=='[') {
                $nd++;
            } else if($list[$i]==']') {
                $nd--;
            } else if ($list[$i]==',' && $nd==AppConstant::NUMERIC_ZERO) {
                $listArr[] = substr($list,$last,$i-$last);
                $last = $i+1;
            }
            $i++;
        }
        $listArr[] = substr($list,$last);
        foreach ($listArr as $it)
        {
            if (strpos($it,'grp')!==false)
            { //is outcome group
                $pos = strpos($it,':');
                if ($pos===false)
                {
                    $block = array("outcomes"=>array());
                    $pts[0] = $it;
                } else
                {
                    $pts[0] = substr($it,AppConstant::NUMERIC_ZERO,$pos);
                    $pts[1] = substr($it,$pos+1);
                    $subarr = $this->addItems($pts[1]);
                    $block = array("outcomes"=>$subarr);
                }
                if (substr($pts[0],AppConstant::NUMERIC_ZERO,3)=='new')
                {
                    $name = stripslashes($this->params['newg'.substr($pts[0],6)]);
                } else
                {
                    $name = stripslashes($this->params['g'.substr($pts[0],3)]);
                }
                $block['name'] = $name;
                $outArr[] = $block;

            }else
            { //is an outcome
                if (substr($it,AppConstant::NUMERIC_ZERO,3)=='new')
                {
                    $oCnt = substr($it,3);
                    $query  = new Outcomes();
                    $insertId = $query->insertOutcomes($this->courseId,$this->params['newo'.$oCnt]);
                    $this->seenOutcomesArr[] = $insertId;
                    $outArr[] = $insertId;

                }
                elseif(isset($this->currOutcomesArray[$it]))
                {
                    if (stripslashes($this->params['o'.$it])!= $this->currOutcomesArray[$it])
                    {
                        Outcomes::UpdateOutcomes($this->params['o'.$it],$it,$this->courseId);
                    }
                    $outArr[]= $it;
                    $this->seenOutcomesArr[] = $it;
                }
            }

        }
        return $outArr;
    }
    public function actionOutcomeReport()
    {
        $this->guestUserHandler();
        $courseId = $this->getParamVal('cid');
        $report = $this->getParamVal('report');
        $studentsOutcome = $this->getParamVal('stud');
        $typeSelected = $this->getParamVal('type');
        $outcomeData = Outcomes::getByCourseId($courseId);
        if($outcomeData)
        {
            if($studentsOutcome){
                $finalData = $this->outcomeTable($courseId,$studentsOutcome);
            }else
            {
                $finalData = $this->outcomeTable($courseId);
            }
        }
        if (isset($typeSelected))
        {
            $type = $typeSelected;
        } else {
            $type = AppConstant::NUMERIC_ONE;
        }
        $selectedOutcome = $this->getParamVal('selectedOutcome');
        $courseOutcomeData = Course::getByCourseIdOutcomes($courseId);
        if(($courseOutcomeData[0]['outcomes']) == '')
        {
            $outcomes = array();
        }
        else
        {
            $outcomes = unserialize(($courseOutcomeData[0]['outcomes']));
        }
        $outcomeInfo = array();
        foreach($outcomeData as $data)
        {
            $outcomeInfo[$data['id']] = $data['name'];
        }
        $outc = array();
        $outc =$this->flattenout($outcomes);
        $this->includeJS('tablesorter.js');
        $this->includeCSS(['dataTables.bootstrap.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js','general.js' ]);
        return $this->render('outcomeReport',['courseId' => $courseId,'finalData' => $finalData,'outc' => $outc,'headerData' => $outcomeInfo,'report' => $report,'report' => $report,'selectedOutcome' => $selectedOutcome,'outcomesData' => $outcomes,'type' => $type]);
    }

    public function actionOutcomeMap()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $courseOutcomeData = Course::getByCourseIdOutcomes($courseId);
        if(($courseOutcomeData[0]['outcomes']) == '')
        {
            $outcomes = array();
        }
        else
        {
            $outcomes = unserialize(($courseOutcomeData[0]['outcomes']));
        }
        $outcomeData = Outcomes::getByCourseId($courseId);
        $outcomeInfo = array();
        foreach($outcomeData as $data)
        {
            $outcomeInfo[$data['id']] = $data['name'];
        }
        $outcomeLinks = AppConstant::NUMERIC_ZERO;
        $outcomeAssoc = array();
        $assessGbCat = array();
        $assessNames = array();
        $assessQCnt = array();
        $assessmentData = Assessments::assessmentDataForOutcomes($courseId);
        foreach($assessmentData as $singleData)
        {
            if (!is_numeric($singleData['category'])) {continue;}
            if ($singleData['category']==AppConstant::NUMERIC_ZERO) {
                $outC = $singleData['defoutcome'];
            } else {
                $outC = $singleData['category'];
            }
            if (!isset($assessQCnt[$singleData['id']])) {
                $assessQCnt[$singleData['id']] = array();
            }
            if (!isset($assessQCnt[$singleData['id']][$outC])) {
                $assessQCnt[$singleData['id']][$outC] = AppConstant::NUMERIC_ONE;
            } else {
                $assessQCnt[$singleData['id']][$outC]++;
            }
            $assessGbCat[$singleData['id']] = $singleData['gbcategory'];
            $assessNames[$singleData['id']] = $singleData['name'];
            $outcomeLinks++;
        }
        foreach ($assessQCnt as $id=>$os) {
            foreach ($os as $o=>$cnt) {
                if (!isset($outcomeAssoc[$o])) {
                    $outcomeAssoc[$o] = array();
                }
                if (!isset($outcomeAssoc[$o][$assessGbCat[$id]])) {
                    $outcomeAssoc [$o][$assessGbCat[$id]] = array();
                }
                $outcomeAssoc[$o][$assessGbCat[$id]][] = array('assess',$id);
            }
        }
        $offGbCat = array();
        $offNames = array();
        $getGbItems = GbItems::getGbItemsForOutcomeMap($courseId);
        foreach($getGbItems as $gbItems)
        {
            $oc = explode(',',$gbItems['outcomes']);
            foreach ($oc as $o) {
                if (!isset($outcomeAssoc[$o])) {
                    $outcomeAssoc[$o] = array();
                }
                if (!isset($outcomeAssoc[$o][$gbItems['gbcategory']])) {
                    $outcomeAssoc[$o][$gbItems['gbcategory']] = array();
                }
                $outcomeAssoc[$o][$gbItems['gbcategory']][] = array('offline',$gbItems['id']);
                $outcomeLinks++;
            }
            $offGbCat[$gbItems['id']] = $gbItems['gbcategory'];
            $offNames[$gbItems['id']] = $gbItems['name'];
        }
        $forumGbCat = array();
        $forumNames = array();
        $getForums = Forums::getForumsForOutcomeMap($courseId);
        foreach($getForums as $singleForumData)
        {
            $oc = explode(',',$singleForumData['outcomes']);
            if ($singleForumData['cntingb']!=AppConstant::NUMERIC_ZERO) {
                $forumGbCat[$singleForumData['id']] = $singleForumData['gbcategory'];
            } else {
                $singleForumData['gbcategory'] = 'UG';
            }
            foreach ($oc as $o) {
                if (!isset($outcomeAssoc[$o])) {
                    $outcomeAssoc[$o] = array();
                }
                if (!isset($outcomeAssoc[$o][$singleForumData['gbcategory']])) {
                    $outcomeAssoc[$o][$singleForumData['gbcategory']] = array();
                }
                $outcomeAssoc[$o][$singleForumData['gbcategory']][] = array('forum',$singleForumData['id']);
                $outcomeLinks++;
            }
            $forumNames[$singleForumData['id']] = $singleForumData['name'];
        }
        $linkNames = array();
        $getLinkText = LinkedText::getLinkedTextForOutcomeMap($courseId);
        foreach($getLinkText as $singleLinkTextData)
        {
            $oc = explode(',',$singleLinkTextData['outcomes']);
            foreach ($oc as $o) {
                if (!isset($outcomeAssoc[$o])) {
                    $outcomeAssoc[$o] = array();
                }
                if (!isset($outcomeAssoc[$o]['UG'])) {
                    $outcomeAssoc[$o]['UG'] = array();
                }
                $outcomeAssoc[$o]['UG'][] = array('link',$singleLinkTextData['id']);
                $outcomeLinks++;
            }
            $linkNames[$singleLinkTextData['id']] = $singleLinkTextData['title'];
        }
        $inlineNames = array();
        $getInlineText = InlineText::getInlineTextForOutcomeMap($courseId);
        foreach($getInlineText as $singleInlineTextData)
        {
            $oc = explode(',',$singleInlineTextData['outcomes']);
            foreach ($oc as $o) {
                if (!isset($outcomeAssoc[$o])) {
                    $outcomeAssoc[$o] = array();
                }
                if (!isset($outcomeAssoc[$o]['UG'])) {
                    $outcomeAssoc[$o]['UG'] = array();
                }
                $outcomeAssoc[$o]['UG'][] = array('inline',$singleInlineTextData['id']);
                $outcomeLinks++;
            }
            $inlineNames[$singleInlineTextData['id']] = $singleInlineTextData['title'];
        }
        $cats = array_unique(array_merge($offGbCat,$forumGbCat,$assessGbCat));
        $catNames = array();

        if (in_array(AppConstant::NUMERIC_ZERO, $cats))
        {
            $catNames[0] = 'Default';
        }
        if (count($cats)>AppConstant::NUMERIC_ZERO)
        {
            $catList = implode(',',$cats);
            $query = GbCats::getGbCatsForOutcomeMap($cats);
            foreach($query as $data){
                $catNames[$data['id']] = $data['name'];
            }
        }
        natsort($catNames);
        return $this->render('outcomeMap',['course'=> $course ,'outcomeLinks' => $outcomeLinks,'catNames' => $catNames,'assessNames' => $assessNames,'forumNames' => $forumNames,'offNames' => $offNames,'linkNames' => $linkNames,'inlineNames' => $inlineNames,'outcomeInfo' => $outcomeInfo,'outcomeAssoc' => $outcomeAssoc,'outcomes' => $outcomes]);
    }
    function flattenout($arr)
    {
        global $outc;
        foreach ($arr as $oi)
        {
            if (is_array($oi))
            {
                $this->flattenout($oi['outcomes']);
            }else
            {
                $outc[] = $oi;
            }
        }
        return $outc;
    }

   public function getpts($sc)
    {
        if (strpos($sc,'~')===false) {
            if ($sc>AppConstant::NUMERIC_ZERO) {
                return $sc;
            } else {
                return AppConstant::NUMERIC_ZERO;
            }
        } else {
            $sc = explode('~',$sc);
            $tot = AppConstant::NUMERIC_ZERO;
            foreach ($sc as $s) {
                if ($s>AppConstant::NUMERIC_ZERO) {
                    $tot+=$s;
                }
            }
            return round($tot,AppConstant::NUMERIC_ZERO);
        }
    }

     function outcomeTable($courseId)
     {
        global $cid,$isteacher,$istutor,$tutorid,$userid,$catfilter,$secfilter,$timefilter,$lnfilter,$isdiag,$sel1name,$sel2name,$canviewall,$hidelocked;
        $canviewall = true;
        $catfilter = AppConstant::NUMERIC_NEGATIVE_ONE;
        $secfilter = AppConstant::NUMERIC_NEGATIVE_ONE;
        if($canviewall && func_num_args()>AppConstant::NUMERIC_ONE)
        {
            $limuser = func_get_arg(AppConstant::NUMERIC_ONE);
        }else if (!$canviewall)
        {
            $limuser = $userid;
        }else
        {
            $limuser = AppConstant::NUMERIC_ZERO;
        }
        $category = array();
        $outc = array();
        $gb = array();
        $ln = AppConstant::NUMERIC_ZERO;
        //Pull Gradebook Scheme info
         $query = GbScheme::getByCourseId($courseId);
         $useweights =$query['useweights'];
         $orderby =$query['orderby'];
         $defaultcat =$query['defaultcat'];
         $usersort =$query['usersort'];
         if($useweights == AppConstant::NUMERIC_TWO)
         {
             $useweights = AppConstant::NUMERIC_ZERO;
         }
    //
    //    if (isset($GLOBALS['setorderby'])) {
    //        $orderby = $GLOBALS['setorderby'];
    //    }
         $gb[0][0][0] = "Name";
         $query = Student::findCount($courseId);
         if($query[0]['count(id)']>AppConstant::NUMERIC_ZERO)
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
         $kcnt = AppConstant::NUMERIC_ZERO;
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
             if (substr($data['deffeedback'],AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_EIGHT)=='Practice')
             {
               continue;
             }
             if ($data['avail']==AppConstant::NUMERIC_TWO) {
                 $data['startdate'] = AppConstant::NUMERIC_ZERO;
                 $data['enddate'] = AppConstant::ALWAYS_TIME;
             }
             if ($now<$data['startdate']) {
                 continue; //we don't want future items
             } else if ($now < $data['enddate']) {
                 $avail[$kcnt] = AppConstant::NUMERIC_ONE;
             } else {
                 $avail[$kcnt] = AppConstant::NUMERIC_ONE;
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
                         $aitemcnt[$k] = AppConstant::NUMERIC_ONE;
                     }
                     else {
                         $grpparts = explode('|',$sub[0]);
                         $aitems[$k] = $sub[1];
                         $aitemcnt[$k] = $grpparts[0];
                     }
                 } else {
                     $aitemcnt[$k] = AppConstant::NUMERIC_ONE;
                 }
             }
             $questions = Questions::findQuestionForOuctome($data['id']);
             $totalpossible = AppConstant::NUMERIC_ZERO;
             foreach($questions as $questionData)
             {
                 if ($questionData['points']==AppConstant::QUARTER_NINE) {
                     $qposs[$questionData['id']] = $data['defpoints'];
                 } else {
                     $qposs[$questionData['id']] = $questionData['points'];
                 }
                 if (is_numeric($questionData['category']) && $questionData['category']>AppConstant::NUMERIC_ZERO) {
                     $qoutcome[$questionData['id']] = $questionData['category'];
                 } else if ($data['defoutcome']>AppConstant::NUMERIC_ZERO) {
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
                     $possible[$kcnt][$qoutcome[$q]] = AppConstant::NUMERIC_ZERO;
                 }
                 $possible[$kcnt][$qoutcome[$q]] += $aitemcnt[$k]*$qposs[$q];
             }
             $kcnt++;
         }
    //Pull Offline Grade item info
         $offlineGradeInfo = GbItems::findOfflineGradeItemForOutcomes($courseId,$istutor,$catfilter, $now);
         foreach($offlineGradeInfo as $offlineInfo)
         {
             $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
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
             if ($GradeInfo['avail']==AppConstant::NUMERIC_TWO) {
                 $GradeInfo['startdate'] = AppConstant::NUMERIC_ZERO;
                 $GradeInfo['enddate'] = AppConstant::ALWAYS_TIME;
             }
             $enddate[$kcnt] = $GradeInfo['enddate'];
             $startdate[$kcnt] = $GradeInfo['startdate'];
             if ($now < $GradeInfo['enddate']) {
                 $avail[$kcnt] = AppConstant::NUMERIC_ONE;
                 if ($GradeInfo['replyby'] > AppConstant::NUMERIC_ZERO && $GradeInfo['replyby'] < AppConstant::ALWAYS_TIME) {
                     if ($GradeInfo['postby'] > AppConstant::NUMERIC_ZERO && $GradeInfo['postby'] < AppConstant::ALWAYS_TIME) {
                         if ($now>$GradeInfo['replyby'] && $now>$GradeInfo['postby']) {
                             $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                         }
                     } else {
                         if ($now>$GradeInfo['replyby']) {
                             $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                         }
                     }
                 } else if ($GradeInfo['postby'] > AppConstant::NUMERIC_ZERO && $GradeInfo['postby'] < AppConstant::ALWAYS_TIME) {
                     if ($now>$GradeInfo['postby']) {
                         $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
                     }
                 }
             } else {
                 $avail[$kcnt] = AppConstant::NUMERIC_ZERO;
             }
             $possible[$kcnt] = $GradeInfo['points'];
             $name[$kcnt] = $GradeInfo['name'];
             $cntingb[$kcnt] = $GradeInfo['cntingb'];
             $itemoutcome[$kcnt] = explode(',',$GradeInfo['outcomes']);
             $kcnt++;
         }
         $cats = array();
         $catcolcnt = AppConstant::NUMERIC_ZERO;
         if (in_array(AppConstant::NUMERIC_ZERO,$category)) {  //define default category, if used
             $cats[0] = explode(',',$defaultcat);
             array_unshift($cats[0],"Default");
             array_push($cats[0],$catcolcnt);
             $catcolcnt++;
         }
         $gbCatData = GbCats::findCategoryByCourseId($courseId);
         foreach($gbCatData as $gbData)
         {
             if (in_array($gbData['id'],$category))
             { //define category if used
                 if ($gbData['name']{AppConstant::NUMERIC_ZERO}>=AppConstant::ONE_VALUE && $gbData['name']{AppConstant::NUMERIC_ZERO}<=AppConstant::NINE_VALUE) {
                     $gbData['name'] = substr($gbData['name'],AppConstant::NUMERIC_ONE);
                 }
                 $cats[$gbData['id']] = array_slice($gbData,AppConstant::NUMERIC_ONE);
                 array_push($cats[$gbData['id']],$catcolcnt);
                 $catcolcnt++;
             }
         }
         $pos = AppConstant::NUMERIC_ZERO;
         $itemorder = array();
         $assesscol = array();
         $gradecol = array();
         $discusscol = array();
         if ($orderby==AppConstant::NUMERIC_ONE) { //order $category by enddate
             asort($enddate,SORT_NUMERIC);
             $newcategory = array();
             foreach ($enddate as $k=>$v) {
                 $newcategory[$k] = $category[$k];
             }
             $category = $newcategory;
         } else if ($orderby==AppConstant::NUMERIC_FIVE) { //order $category by enddate reverse
             arsort($enddate,SORT_NUMERIC);
             $newcategory = array();
             foreach ($enddate as $k=>$v) {
                 $newcategory[$k] = $category[$k];
             }
             $category = $newcategory;
         }else if ($orderby==AppConstant::NUMERIC_SEVEN) { //order $category by startdate
                 asort($startdate,SORT_NUMERIC);
                 $newcategory = array();
                 foreach ($startdate as $k=>$v) {
                     $newcategory[$k] = $category[$k];
                 }
                 $category = $newcategory;
         } else if ($orderby==AppConstant::NUMERIC_NINE) { //order $category by startdate reverse
             arsort($startdate,SORT_NUMERIC);
             $newcategory = array();
             foreach ($startdate as $k=>$v) {
                 $newcategory[$k] = $category[$k];
             }
             $category = $newcategory;
         } else if ($orderby==AppConstant::NUMERIC_THREE) { //order $category alpha
             natcasesort($name);//asort($name);
             $newcategory = array();
             foreach ($name as $k=>$v) {
                 $newcategory[$k] = $category[$k];
             }
             $category = $newcategory;
         }
         foreach(array_keys($cats) as $cat)
         {//foreach category
             $catkeys = array_keys($category,$cat); //pull items in that category
             if (($orderby&AppConstant::NUMERIC_ONE)==AppConstant::NUMERIC_ONE) { //order by category
                 array_splice($itemorder,count($itemorder),AppConstant::NUMERIC_ZERO,$catkeys);
             }
             foreach ($catkeys as $k) {
                 if (isset($cats[$cat][6]) && $cats[$cat][6]==AppConstant::NUMERIC_ONE) {//hidden
                     $cntingb[$k] = AppConstant::NUMERIC_ZERO;
                 }
                 if (($orderby&AppConstant::NUMERIC_ONE)==AppConstant::NUMERIC_ONE) {  //display item header if displaying by category
                     //$cathdr[$pos] = $cats[$cat][6];
                     $gb[0][1][$pos][0] = $name[$k]; //item name
                     $gb[0][1][$pos][1] = $cats[$cat][7]; //item category number
                     $gb[0][1][$pos][2] = $avail[$k]; //0 past, 1 current, 2 future
                     $gb[0][1][$pos][3] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
                     if (isset($assessments[$k])) {
                         $gb[0][1][$pos][4] = AppConstant::NUMERIC_ZERO; //0 online, 1 offline
                         $gb[0][1][$pos][5] = $assessments[$k];
                         $assesscol[$assessments[$k]] = $pos;
                     } else if (isset($grades[$k])) {
                         $gb[0][1][$pos][4] = AppConstant::NUMERIC_ONE; //0 online, 1 offline
                         $gb[0][1][$pos][5] = $grades[$k];
                         $gradecol[$grades[$k]] = $pos;
                     } else if (isset($discuss[$k])) {
                         $gb[0][1][$pos][4] = AppConstant::NUMERIC_TWO; //0 online, 1 offline, 2 discuss
                         $gb[0][1][$pos][5] = $discuss[$k];
                         $discusscol[$discuss[$k]] = $pos;
                     }
                     $gb[0][1][$pos][6] = array();
                     $pos++;
                 }
             }
         }
         if (($orderby&AppConstant::NUMERIC_ONE)==AppConstant::NUMERIC_ZERO) {//if not grouped by category
             if ($orderby==AppConstant::NUMERIC_ZERO) {   //enddate
                 asort($enddate,SORT_NUMERIC);
                 $itemorder = array_keys($enddate);
             } else if ($orderby==AppConstant::NUMERIC_TWO) {  //alpha
                 natcasesort($name);//asort($name);
                 $itemorder = array_keys($name);
             } else if ($orderby==AppConstant::NUMERIC_FOUR) { //enddate reverse
                 arsort($enddate,SORT_NUMERIC);
                 $itemorder = array_keys($enddate);
             } else if ($orderby==AppConstant::NUMERIC_SIX) { //startdate
                 asort($startdate,SORT_NUMERIC);
                 $itemorder = array_keys($startdate);
             } else if ($orderby==AppConstant::NUMERIC_EIGHT) { //startdate reverse
                 arsort($startdate,SORT_NUMERIC);
                 $itemorder = array_keys($startdate);
             }
             foreach ($itemorder as $k) {
                 $gb[0][1][$pos][0] = $name[$k]; //item name
                 $gb[0][1][$pos][1] = $cats[$category[$k]][7]; //item category name
                 $gb[0][1][$pos][2] = $avail[$k]; //0 past, 1 current, 2 future
                 $gb[0][1][$pos][3] = $cntingb[$k]; //0 no count and hide, 1 count, 2 EC, 3 no count
                 if (isset($assessments[$k])) {
                     $gb[0][1][$pos][4] = AppConstant::NUMERIC_ZERO; //0 online, 1 offline
                     $gb[0][1][$pos][5] = $assessments[$k];
                     $assesscol[$assessments[$k]] = $pos;
                 } else if (isset($grades[$k])) {
                     $gb[0][1][$pos][4] = AppConstant::NUMERIC_ONE; //0 online, 1 offline
                     $gb[0][1][$pos][5] = $grades[$k];
                     $gradecol[$grades[$k]] = $pos;
                 } else if (isset($discuss[$k])) {
                     $gb[0][1][$pos][4] = AppConstant::NUMERIC_TWO; //0 online, 1 offline, 2 discuss
                     $gb[0][1][$pos][5] = $discuss[$k];
                     $discusscol[$discuss[$k]] = $pos;
                 }
                 $pos++;
             }
         }
         //create category headers
         $pos = AppConstant::NUMERIC_ZERO;
         $catorder = array_keys($cats);
         foreach($catorder as $cat) {//foreach category
             $gb[0][2][$pos][0] = $cats[$cat][0];
             $gb[0][2][$pos][1] = $cats[$cat][7];
             $pos++;
         }
    //Pull student data
         $ln = AppConstant::NUMERIC_ONE;
         $findStudentDataForOutcomes = Student::findStudentByCourseIdForOutcomes($courseId,  $limuser, $secfilter, $hidelocked, $timefilter, $lnfilter, $isdiag, $hassection, $usersort);
         $alt = AppConstant::NUMERIC_ZERO;
         $sturow = array();
         foreach($findStudentDataForOutcomes as $StudentDataForOutcomes)
         {
             unset($asid); unset($pts); unset($IP); unset($timeused);
             $cattotpast[$ln] = array();
             $cattotpastec[$ln] = array();
             $catposspast[$ln] = array();
             $cattotcur[$ln] = array();
             $cattotcurec[$ln] = array();
             $catposscur[$ln] = array();
             //Student ID info
             $gb[$ln][0][0] = "{$StudentDataForOutcomes['LastName']},&nbsp;{$StudentDataForOutcomes['FirstName']}";
             $gb[$ln][0][1] = $StudentDataForOutcomes['id'];
             $gb[$ln][0][2] = $StudentDataForOutcomes['locked'];
             $sturow[$StudentDataForOutcomes['id']] = $ln;
             $ln++;
         }
    //pull exceptions
         $exceptions = array();
         $findExceptionForOutcomes = Exceptions::findExceptions($courseId);

         foreach($findExceptionForOutcomes as $exceptions)
         {
             if (!isset($sturow[$exceptions[1]])) { continue;}
             $exceptions[$exceptions[0]][$exceptions[1]] = array($exceptions[2],$exceptions[3]);
             $gb[$sturow[$exceptions[1]]][1][$assesscol[$exceptions[0]]][2] = AppConstant::NUMERIC_TEN;
         }
         //Get assessment scores
         $assessidx = array_flip($assessments);
         $assessmentScoresForOutcomes = AssessmentSession::findAssessmentForOutcomes($courseId, $limuser);
         foreach($assessmentScoresForOutcomes as $ssessmentForOutcomes)
         {
             if (!isset($assessidx[$ssessmentForOutcomes['assessmentid']]) || !isset($sturow[$ssessmentForOutcomes['userid']]) || !isset($assesscol[$ssessmentForOutcomes['assessmentid']])) {
                 continue;
             }
             $i = $assessidx[$ssessmentForOutcomes['assessmentid']];
             $row = $sturow[$ssessmentForOutcomes['userid']];
             $col = $assesscol[$ssessmentForOutcomes['assessmentid']];
             $gb[$row][1][$col][3] = $ssessmentForOutcomes['id'];; //assessment session id
             if (strpos($ssessmentForOutcomes['questions'],';')===false) {
                 $questions = explode(",",$ssessmentForOutcomes['questions']);
             }else
             {
                 list($questions,$bestquestions) = explode(";",$ssessmentForOutcomes['questions']);
                 $questions = explode(",",$bestquestions);
             }
             $sp = explode(';',$ssessmentForOutcomes['bestscores']);
             $scores = explode(",",$sp[0]);
             $pts = array();
             $ptsposs = array();
             for ($j=AppConstant::NUMERIC_ZERO;$j<count($scores);$j++)
             {
                 if (!isset($qoutcome[$questions[$j]])) { continue; } //no outcome set - skip it
                 if (!isset($pts[$qoutcome[$questions[$j]]])) {
                     $pts[$qoutcome[$questions[$j]]] = AppConstant::NUMERIC_ZERO;
                 }
                 if (!isset($ptsposs[$qoutcome[$questions[$j]]])) {
                     $ptsposs[$qoutcome[$questions[$j]]] = AppConstant::NUMERIC_ZERO;
                 }
                 $pts[$qoutcome[$questions[$j]]] += $this->getpts($scores[$j]);
                 $ptsposs[$qoutcome[$questions[$j]]] += $qposs[$questions[$j]];
             }
             if (in_array(AppConstant::NUMERIC_NEGATIVE_ONE,$scores))
             {
                 $IP=AppConstant::NUMERIC_ONE;
             } else
             {
                 $IP=AppConstant::NUMERIC_ZERO;
             }
             if (isset($exceptions[$ssessmentForOutcomes['assessmentid']][$ssessmentForOutcomes['userid']]))
             {// && $now>$enddate[$i] && $now<$exceptions[$l['assessmentid']][$l['userid']]) {
                 if ($enddate[$i]>$exceptions[$ssessmentForOutcomes['assessmentid']][$ssessmentForOutcomes['userid']][0] && $assessmenttype[$i]=="NoScores") {
                     //if exception set for earlier, and NoScores is set, use later date to hide score until later
                     $thised = $enddate[$i];
                 } else {
                     $thised = $exceptions[$ssessmentForOutcomes['assessmentid']][$ssessmentForOutcomes['userid']][0];
                     if ($limuser>AppConstant::NUMERIC_ZERO && $gb[0][1][$col][2]==AppConstant::NUMERIC_TWO) {  //change $avail past/cur/future
                         if ($now<$thised) {
                             $gb[0][1][$col][2] = AppConstant::NUMERIC_ONE;
                         } else {
                             $gb[0][1][$col][2] = AppConstant::NUMERIC_ZERO;
                         }
                     }
                 }
                 $inexception = true;
             }else
             {
                 $thised = $enddate[$i];
                 $inexception = false;
             }
             $countthisone = false;
             $gb[$row][1][$col][1] = $ptsposs;
             if ($assessmenttype[$i]=="NoScores" && $sa[$i]!="I" && $now<$thised && !$canviewall)
             {
                 $gb[$row][1][$col][0] = 'N/A'; //score is not available
                 $gb[$row][1][$col][2] = AppConstant::NUMERIC_ZERO;  //no other info
             } else if (($minscores[$i]<AppConstant::NUMERIC_TEN_THOUSAND && $pts<$minscores[$i]) || ($minscores[$i]>AppConstant::NUMERIC_TEN_THOUSAND && $pts<($minscores[$i]-AppConstant::NUMERIC_TEN_THOUSAND)/AppConstant::NUMERIC_HUNDREAD*$possible[$i]))
             {
                 if ($canviewall) {
                     $gb[$row][1][$col][0] = $pts; //the score
                     $gb[$row][1][$col][2] = AppConstant::NUMERIC_ONE;  //no credit
                 } else {
                     $gb[$row][1][$col][0] = 'NC'; //score is No credit
                     $gb[$row][1][$col][2] = AppConstant::NUMERIC_ONE;  //no credit
                 }
             } else if ($IP==AppConstant::NUMERIC_ONE && $thised>$now && (($timelimits[$i]==AppConstant::NUMERIC_ZERO) || ($timeused < $timelimits[$i]*$timelimitmult[$l['userid']])))
             {
                 $gb[$row][1][$col][0] = $pts; //the score
                 $gb[$row][1][$col][2] = AppConstant::NUMERIC_TWO;  //in progress
                 $countthisone =true;
             }else	if (($timelimits[$i]>AppConstant::NUMERIC_ZERO) && ($timeused > $timelimits[$i]*$timelimitmult[$l['userid']])) {
                 $gb[$row][1][$col][0] = $pts; //the score
                 $gb[$row][1][$col][2] = AppConstant::NUMERIC_THREE;  //over time
             }else if ($assessmenttype[$i]=="Practice") {
                 $gb[$row][1][$col][0] = $pts; //the score
                 $gb[$row][1][$col][2] = AppConstant::NUMERIC_FOUR;  //practice test
             }else { //regular score available to students
                 $gb[$row][1][$col][0] = $pts; //the score
                 $gb[$row][1][$col][2] = AppConstant::NUMERIC_ZERO;  //no other info
                 $countthisone =true;
             }
             if ($now < $thised) { //still active
                 $gb[$row][1][$col][2] += AppConstant::NUMERIC_TEN;
             }
             if ($countthisone) {
                 foreach ($pts as $oc=>$pv) {
                     if ($cntingb[$i] == AppConstant::NUMERIC_ONE) {
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_ONE) { //past
                             $cattotpast[$row][$category[$i]][$oc][$col] = $pv;
                             $catposspast[$row][$category[$i]][$oc][$col] = $ptsposs[$oc];
                         }
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_TWO) { //past or cur
                             $cattotcur[$row][$category[$i]][$oc][$col] = $pv;
                             $catposscur[$row][$category[$i]][$oc][$col] = $ptsposs[$oc];
                         }
                     } else if ($cntingb[$i] == AppConstant::NUMERIC_TWO) {
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_ONE) { //past
                             $cattotpastec[$row][$category[$i]][$oc][$col] = $pv;
                         }
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_TWO) { //past or cur
                             $cattotcurec[$row][$category[$i]][$oc][$col] = $pv;
                         }
                     }
                 }
             }
         }
         //Get other grades
         $gradeidx = array_flip($grades);
         unset($gradeid); unset($opts);
         unset($discusspts);
         $discussidx = array_flip($discuss);
         $gradetypeselects = array();
         if (count($grades)>AppConstant::NUMERIC_ZERO) {
             $gradeidlist = implode(',',$grades);
             $gradetypeselects[] = "(gradetype='offline' AND gradetypeid IN ($gradeidlist))";
         }
         if (count($discuss)>AppConstant::NUMERIC_ZERO) {
             $forumidlist = implode(',',$discuss);
             $gradetypeselects[] = "(gradetype='forum' AND gradetypeid IN ($forumidlist))";
         }
         if (count($gradetypeselects)>AppConstant::NUMERIC_ZERO)
         {
             $sel = implode(' OR ',$gradetypeselects);
             $gradeForOutcomes = Grades::outcomeGrades($sel,$limuser);
             foreach( $gradeForOutcomes as $grades)
             {
                if ($grades['gradetype']=='offline') {
                if (!isset($gradeidx[$grades['gradetypeid']]) || !isset($sturow[$grades['userid']]) || !isset($gradecol[$grades['gradetypeid']]))
                {
                     continue;
                }
                 $i = $assessidx[$grades['assessmentid']];
                 $row = $sturow[$grades['userid']];
                 $col = $assesscol[$grades['assessmentid']];
                 foreach ($itemoutcome[$i] as $oc)
                 {
                    $gb[$row][1][$col][3] = $l['id'];
                     if ($l['score']!=null) {
                         $gb[$row][1][$col][0][$oc] = AppConstant::NUMERIC_ONE*$l['score'];
                         $gb[$row][1][$col][1][$oc] = $possible[$i];
                     }
                     if ($cntingb[$i] == AppConstant::NUMERIC_ONE) {
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_ONE) { //past
                             $cattotpast[$row][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ONE*$l['score'];
                             $catposspast[$row][$category[$i]][$oc][$col] = $possible[$i];
                         }
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_TWO) { //past or cur
                             $cattotcur[$row][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ONE*$l['score'];
                             $catposscur[$row][$category[$i]][$oc][$col] = $possible[$i];
                         }
                     }
                     else if ($cntingb[$i]==AppConstant::NUMERIC_TWO)
                     {
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_ONE) { //past
                             $cattotpastec[$row][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ONE*$l['score'];
                         }
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_TWO) { //past or cur
                             $cattotcurec[$row][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ONE*$l['score'];
                         }
                     }
                 }
             } else if ($l['gradetype']=='forum')
                 {
                     if (!isset($discussidx[$l['gradetypeid']]) || !isset($sturow[$l['userid']]) || !isset($discusscol[$l['gradetypeid']])) {
                         continue;
                     }
                     $i = $discussidx[$l['gradetypeid']];
                     $row = $sturow[$l['userid']];
                     $col = $discusscol[$l['gradetypeid']];
                     foreach ($itemoutcome[$i] as $oc) {
                         if ($l['score']!=null) {
                             if (isset($gb[$row][1][$col][0])) {
                                 $gb[$row][1][$col][0][$oc] += AppConstant::NUMERIC_ONE*$l['score']; //adding up all forum scores
                             } else {
                                 $gb[$row][1][$col][0][$oc] = AppConstant::NUMERIC_ONE*$l['score'];
                             }
                         }
                         if ($gb[0][1][$col][2]<AppConstant::NUMERIC_ONE) { //past
                             $cattotpast[$row][$category[$i]][$oc][$col] = $gb[$row][1][$col][0];
                             $catposspast[$row][$category[$i]][$oc][$col] = $possible[$i];
                         }
                         if ($gb[0][1][$col][3]<AppConstant::NUMERIC_TWO) { //past or cur
                             $cattotcur[$row][$category[$i]][$oc][$col] = $gb[$row][1][$col][0];
                             $catposscur[$row][$category[$i]][$oc][$col] = $possible[$i];
                         }
                     }
                 }
             }
         }
         for ($ln = AppConstant::NUMERIC_ONE; $ln<count($sturow)+AppConstant::NUMERIC_ONE;$ln++)
         {
             if($gb[0][1])
             {
                 foreach($gb[0][1] as $col=>$inf) {
                     if ($gb[0][1][$col][2]>AppConstant::NUMERIC_ZERO || count($gb[$ln][1][$col][1])>AppConstant::NUMERIC_ZERO) {continue;} //skip if current, or if already set
                     if ($inf[4]==AppConstant::NUMERIC_ZERO && count($possible[$assessidx[$inf[5]]])==AppConstant::NUMERIC_ZERO) {continue;} //assess has no outcomes
                     $gb[$ln][1][$col] = array();
                     $gb[$ln][1][$col][0] = array();
                     $gb[$ln][1][$col][1] = array();
                     if ($inf[4]==AppConstant::NUMERIC_ZERO)
                     { //online item
                         $i = $assessidx[$inf[5]];
                         foreach ($possible[$i] as $oc=>$p)
                         {
                             $gb[$ln][1][$col][0][$oc] = AppConstant::NUMERIC_ZERO;
                             $gb[$ln][1][$col][1][$oc] = $p;
                             $cattotpast[$ln][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ZERO;
                             $catposspast[$ln][$category[$i]][$oc][$col] = $p;
                             $cattotcur[$ln][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ZERO;
                             $catposscur[$ln][$category[$i]][$oc][$col] = $p;
                         }
                         $gb[$ln][1][$col][3] = AppConstant::NUMERIC_ZERO;
                         $gb[$ln][1][$col][4] = 'new';
                     }else { //offline or discussion
                         if ($inf[4]==AppConstant::NUMERIC_ONE) {
                             $i = $gradeidx[$inf[5]];
                         } else if ($inf[4]==AppConstant::NUMERIC_TWO) {
                             $i = $discussidx[$inf[5]];
                         }
                         foreach ($itemoutcome[$i] as $oc) {
                             $gb[$ln][1][$col][0][$oc] = AppConstant::NUMERIC_ZERO;
                             $gb[$ln][1][$col][1][$oc] = $possible[$i];
                             $cattotpast[$ln][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ZERO;
                             $catposspast[$ln][$category[$i]][$oc][$col] = $possible[$i];
                             $cattotcur[$ln][$category[$i]][$oc][$col] = AppConstant::NUMERIC_ZERO;
                             $catposscur[$ln][$category[$i]][$oc][$col] = $possible[$i];
                         }
                     }
                 }
             }
             $totpast = array();
             $totposspast = array();
             $totcur = array();
             $totposscur = array();
             $pos = AppConstant::NUMERIC_ZERO; //reset position for category totals
             foreach($catorder as $cat) {//foreach category
                 //add up scores for each outcome
                 if (isset($cattotpast[$ln][$cat])) {
                     foreach ($cattotpast[$ln][$cat] as $oc=>$scs) {
                         $cattotpast[$ln][$cat][$oc] = array_sum($scs);
                         if (isset($cattotpastec[$ln][$cat][$oc])) {
                             $cattotpast[$ln][$cat][$oc] += array_sum($cattotpastec[$ln][$cat][$oc]);
                         }
                         $catposspast[$ln][$cat][$oc] = array_sum($catposspast[$ln][$cat][$oc]);
                         $gb[$ln][2][$pos][0][$oc] = $cattotpast[$ln][$cat][$oc];
                         $gb[$ln][2][$pos][1][$oc] = $catposspast[$ln][$cat][$oc];
                         if (!isset($totpast[$oc])) {
                             $totpast[$oc] = AppConstant::NUMERIC_ZERO;
                             $totposspast[$oc] = AppConstant::NUMERIC_ZERO;
                         }
                         if ($useweights==AppConstant::NUMERIC_ONE && $catposspast[$ln][$cat][$oc]>AppConstant::NUMERIC_ZERO) {
                             $totposspast[$oc] += $cats[$cat][5]/AppConstant::NUMERIC_HUNDREAD;
                             $totpast[$oc] += $cattotpast[$ln][$cat][$oc]*$cats[$cat][5]/(AppConstant::NUMERIC_HUNDREAD*$catposspast[$ln][$cat][$oc]);
                         } else if ($useweights==AppConstant::NUMERIC_ZERO) {
                             $totposspast[$oc] += $catposspast[$ln][$cat][$oc];
                             $totpast[$oc] += $cattotpast[$ln][$cat][$oc];
                         }
                     }
                 }
                 if (isset($cattotcur[$ln][$cat])) {
                     foreach ($cattotcur[$ln][$cat] as $oc=>$scs) {
                         $cattotcur[$ln][$cat][$oc] = array_sum($scs);
                         if (isset($cattotcurec[$ln][$cat][$oc])) {
                             $cattotcur[$ln][$cat][$oc] += array_sum($cattotcurec[$ln][$cat][$oc]);
                         }
                         $catposscur[$ln][$cat][$oc] = array_sum($catposscur[$ln][$cat][$oc]);
                         $gb[$ln][2][$pos][2][$oc] = $cattotcur[$ln][$cat][$oc];
                         $gb[$ln][2][$pos][3][$oc] = $catposscur[$ln][$cat][$oc];
                         if (!isset($totcur[$oc])) {
                             $totcur[$oc] = AppConstant::NUMERIC_ZERO;
                             $totposscur[$oc] = AppConstant::NUMERIC_ZERO;
                         }
                         if ($useweights==AppConstant::NUMERIC_ONE && $catposscur[$ln][$cat][$oc]>0) {
                             $totposscur[$oc] += $cats[$cat][5]/AppConstant::NUMERIC_HUNDREAD;
                             $totcur[$oc] += $cattotcur[$ln][$cat][$oc]*$cats[$cat][5]/(AppConstant::NUMERIC_HUNDREAD*$catposscur[$ln][$cat][$oc]);
                         } else if ($useweights==AppConstant::NUMERIC_ZERO) {
                             $totposscur[$oc] += $catposscur[$ln][$cat][$oc];
                             $totcur[$oc] += $cattotcur[$ln][$cat][$oc];
                         }
                     }
                 }
                 $pos++;
             }
             foreach ($totpast as $oc=>$v) {
                 if ($totposspast[$oc]>AppConstant::NUMERIC_ZERO) {
                     $gb[$ln][3][0][$oc] = $totpast[$oc]/$totposspast[$oc];
                 }
             }
             foreach ($totcur as $oc=>$v) {
                 if ($totposscur[$oc]>AppConstant::NUMERIC_ZERO) {
                     $gb[$ln][3][1][$oc] = $totcur[$oc]/$totposscur[$oc];
                 }
             }
         }
         if ($limuser<AppConstant::NUMERIC_ONE) {
             $gb[$ln][0][0] = "Averages";
             $gb[$ln][0][1] = AppConstant::NUMERIC_NEGATIVE_ONE;
             if($gb[0][1])
             {
                 foreach ($gb[0][1] as $i=>$inf)
                 {
                     $avg = array();  $avgposs = array();
                     for ($j=1;$j<$ln;$j++) {
                         if (isset($gb[$j][1][$i]) && isset($gb[$j][1][$i][0])) {
                             foreach ($gb[$j][1][$i][0] as $oc=>$sc) {
                                 if (!isset($avg[$oc])) { $avg[$oc] = array(); $avgposs[$oc] = array();}
                                 $avg[$oc][] = $sc;
                                 $avgposs[$oc][] = $gb[$j][1][$i][1][$oc];
                             }
                         }
                     }
                     foreach ($avg as $oc=>$scs) {
                         $gb[$ln][1][$i][0][$oc] = array_sum($avg[$oc])/count($avg[$oc]);
                         $gb[$ln][1][$i][1][$oc] = array_sum($avgposs[$oc])/count($avg[$oc]);
                     }
                 }
             }
             foreach ($gb[0][2] as $i=>$inf) {
                 $avg = array();  $avgposs = array();
                 $avgatt = array();  $avgattposs = array();
                 for ($j=AppConstant::NUMERIC_ONE;$j<$ln;$j++) {
                     if (isset($gb[$j][2][$i]) && isset($gb[$j][2][$i][0])) {
                         foreach ($gb[$j][2][$i][0] as $oc=>$sc) {
                             if (!isset($avg[$oc])) { $avg[$oc] = array(); $avgposs[$oc] = array();}
                             $avg[$oc][] = $sc;
                             $avgposs[$oc][] = $gb[$j][2][$i][1][$oc];
                         }
                     }
                     if (isset($gb[$j][2][$i]) && isset($gb[$j][2][$i][2])) {
                         foreach ($gb[$j][2][$i][2] as $oc=>$sc) {
                             if (!isset($avgatt[$oc])) { $avgatt[$oc] = array(); $avgpossatt[$oc] = array();}
                             $avgatt[$oc][] = $sc;
                             $avgattposs[$oc][] = $gb[$j][2][$i][3][$oc];
                         }
                     }
                 }
                 foreach ($avg as $oc=>$scs) {
                     $gb[$ln][2][$i][0][$oc] = array_sum($avg[$oc])/count($avg[$oc]);
                     $gb[$ln][2][$i][1][$oc] = array_sum($avgposs[$oc])/count($avg[$oc]);
                 }
                 foreach ($avgatt as $oc=>$scs) {
                     $gb[$ln][2][$i][2][$oc] = array_sum($avgatt[$oc])/count($avgatt[$oc]);
                     $gb[$ln][2][$i][3][$oc] = array_sum($avgattposs[$oc])/count($avgatt[$oc]);
                 }
             }
             $avg = array();  $avgatt = array();
             for ($j=AppConstant::NUMERIC_ONE;$j<$ln;$j++) {
                 if (isset($gb[$j][3][0])) {
                     foreach ($gb[$j][3][0] as $oc=>$sc) {
                         if (!isset($avg[$oc])) { $avg[$oc] = array();}
                         $avg[$oc][] = $sc;
                     }
                 }
                 if (isset($gb[$j][3][1])) {
                     foreach ($gb[$j][3][1] as $oc=>$sc) {
                         if (!isset($avgatt[$oc])) { $avgatt[$oc] = array();}
                         $avgatt[$oc][] = $sc;
                     }
                 }
             }
             foreach ($avg as $oc=>$scs) {
                 $gb[$ln][3][0][$oc] = array_sum($avg[$oc])/count($avg[$oc]);
             }
             foreach ($avgatt as $oc=>$scs) {
                 $gb[$ln][3][1][$oc] = array_sum($avgatt[$oc])/count($avgatt[$oc]);
             }
         }
         if ($limuser==AppConstant::NUMERIC_NEGATIVE_ONE) {
             $gb[1] = $gb[$ln];
         }
         return $gb;
    }
}