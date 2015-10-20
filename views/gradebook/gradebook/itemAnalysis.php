<?php
use app\components\AppUtility;
use app\components\CategoryScoresUtility;
use app\components\AppConstant;
$this->title = 'Item Analysis';?>
<div class="item-detail-header">

    <?php
    if($student == AppConstant::NUMERIC_ZERO) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?stu=0&cid=' . $course->id], 'page_title' => $this->title]);
    }else if ($student== AppConstant::NUMERIC_NEGATIVE_ONE) {
      echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Gradebook','Averages'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id,AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?stu=0&cid=' . $course->id,AppUtility::getHomeURL().'gradebook/gradebook/gradebook?stu='.$student.'&cid='.$course->id], 'page_title' => $this->title]);
    } else if ($from=='isolate') {
      echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'View Scores'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id,AppUtility::getHomeURL().'gradebook/gradebook/isolate-assessment-grade?cid='.$course->id.'&aid='.$assessmentId], 'page_title' => $this->title]);
    } else if ($from=='gisolate') {
      echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'View Group Scores'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id,AppUtility::getHomeURL().'gradebook/gradebook/isolate-assessment-group?cid='.$course->id.'&aid='.$assessmentId], 'page_title' => $this->title]);
     } ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <?php
if (!$isTeacher) {
   echo '<br><div class="text">';
    echo "This page not available to students";
        echo '</div>';
}else {
    $cid = $course->id;
    $aid = $assessmentId;
    $imasroot = AppUtility::getHomeURL();
    $placeinhead = '<script type="text/javascript">';
    $placeinhead .= '$(function() {$("a[href*=\'gradeallq\']").attr("title","' . _('Grade this question for all students') . '");});';
    $placeinhead .= 'function previewq(qn) {';
    $placeinhead .= "var addr = '$imasroot/course/testquestion.php?cid=$courseId&qsetid='+qn;";
    $placeinhead .= "window.open(addr,'Testing','width=400,height=300,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
    $placeinhead .= "}\n</script>";
    $placeinhead .= '<style type="text/css"> .manualgrade { background: #ff6;} td.pointer:hover {text-decoration: underline;}</style>';
    /*
     * Handle breadcrumb
     */

    //echo "&gt; Item Analysis";
    ?>
    <br>
    <div class="cpmid item-analysis">
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/isolate-assessment-grade?cid=' . $courseId . '&amp;aid=' . $assessmentId) ?>"><?php AppUtility::t('View Score List');?></a>
    </div>
     <div id="headergb-itemanalysis" class="pagetitle item-analysis"><h2><?php AppUtility::t('Item Analysis')?>:
    <?php $defaultPoints = $assessmentData['defpoints'];
    $defaultOutcome = $assessmentData['defoutcome'];
    $showHints = $assessmentData['showhints'];
    echo $assessmentData['name'] . '</h2></div>';
    echo '<div class="item-analysis">';
    if ($notStarted == AppConstant::NUMERIC_ZERO) { ?>
         <p> <?php AppUtility::t('All students have started this assessment.')?>
    <?php } else { ?>
         <p><a href="#" onclick="GB_show('Not Started','<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis-detail?cid=' . $courseId . '&aid=' . $assessmentId . '&qid=' . $qid . '&type=notstart');?>',500,300);return false;"><?php echo $notStarted;?> <?php AppUtility::t('student')?> <?php ($notStarted > 1 ? 's' : '') ?> </a>  (<?php echo $nonStartAssessment?> %) <?php ($notStarted > 1 ? AppUtility::t('have not started this assessment.  They are not included in the numbers below.') : AppUtility::t('has not started this assessment.  They are not included in the numbers below.')) ?>
    <?php } ?>
     </p>
     <div class="overflow-x-auto width-hundread-per">
             <table class='table table-bordered table-striped table-hover data-table' id=myTable><thead>
             <tr><th>#</th><th scope="col"><?php AppUtility::t('Question')?></th><th><?php AppUtility::t('Grade')?></th>
             <th scope="col" title="<?php AppUtility::t('Average score for all students who attempted this question')?>"><?php AppUtility::t('Average Score')?><br/><?php AppUtility::t('Attempted')?></th>
             <th title="<?php AppUtility::t('Average number of attempts and regens (new versions)')?>" scope="col"><?php AppUtility::t('Average Attempts')?><br/>(<?php AppUtility::t('Regens')?>)</th>
             <th scope="col" title="<?php AppUtility::t('Percentage of students who have not started this question yet')?>">% <?php AppUtility::t('Incomplete')?></th>
             <th scope="col" title="<?php AppUtility::t('Average time a student worked on this question, and average time per attempt on this question')?>"><?php AppUtility::t('Time per student')?><br/> (<?php AppUtility::t('per attempt')?>)</th>
            <?php if ($showHints == 1)
            { ?>
                 <th scope="col" title="<?php AppUtility::t('Percentage of students who clicked on help resources in the question, if available')?>"><?php AppUtility::t('Clicked on Help')?></th>
            <?php } ?>
             <th scope="col"><?php AppUtility::t('Preview')?></th></tr></thead>
             <tbody>
        <?php
            if (count($questionTotal) > AppConstant::NUMERIC_ZERO) {
                $i = AppConstant::NUMERIC_ONE;
                $description = array();
                $points = array();
                $withdrawn = array();
                $questionSetIds = array();
                $needManualGrade = array();
                $showExtraRef = array();
                foreach ($questionData as $row)
                {
                    $description[$row[1]] = $row[0];
                    $points[$row[1]] = $row[2];
                    $questionSetIds[$row[1]] = $row[3];
                    $withdrawn[$row[1]] = $row[4];
                    if ($row[5] == 'essay' || $row[5] == 'file') {
                        $needManualGrade[$row[1]] = true;
                    } else if ($row[5] == 'multipart') {
                        if (preg_match('/anstypes.*?(".*?"|array\(.*?\))/', $row[6], $matches)) {
                            if (strpos($matches[1], 'essay') !== false || strpos($matches[1], 'file') !== false) {
                                $needManualGrade[$row[1]] = true;
                            }
                        }
                    }
                    if ($row[8] != '' && ($row[7] == AppConstant::NUMERIC_TWO || ($row[7] == AppConstant::NUMERIC_ZERO && $showHints == AppConstant::NUMERIC_ONE)))
                    {
                        $showExtraRef[$row[1]] = true;
                    } else {
                        $showExtraRef[$row[1]] = false;
                    }
                }
                $averageScore = array();
                $qs = array();
                foreach ($itemArray as $qid)
                {
                    if ($i % 2 != AppConstant::NUMERIC_ZERO) {
                        echo "<tr class=even>";
                    } else {
                        echo "<tr class=odd>";
                    }
                    $pts = $points[$qid];
                    if ($pts == AppConstant::QUARTER_NINE)
                    {
                        $pts = $defaultPoints;
                    }
                    if ($questionCount[$qid] > AppConstant::NUMERIC_ZERO) {
                        $avg = round($questionTotal[$qid] / $questionCount[$qid], AppConstant::NUMERIC_TWO);
                        if ($questionCount[$qid] - $questionInComplete[$qid] > AppConstant::NUMERIC_ZERO)
                        {
                            $avg2 = round($questionTotal[$qid] / ($questionCount[$qid] - $questionInComplete[$qid]), AppConstant::NUMERIC_TWO); //avg adjusted for not attempted
                        } else {
                            $avg2 = AppConstant::NUMERIC_ZERO;
                        }
                        $averageScore[$i - 1] = $avg;
                        $qs[$i - 1] = $qid;
                        if ($pts > AppConstant::NUMERIC_ZERO)
                        {
                            $pc = round(AppConstant::NUMERIC_HUNDREAD * $avg / $pts);
                            $pc2 = round(AppConstant::NUMERIC_HUNDREAD * $avg2 / $pts);
                        } else {
                            $pc = AppUtility::t('N/A',false);
                            $pc2 = AppUtility::t('N/A',false);
                        }
                        $pi = round(AppConstant::NUMERIC_HUNDREAD * $questionInComplete[$qid] / $questionCount[$qid], AppConstant::NUMERIC_ONE);

                        if ($questionCount[$qid] - $questionInComplete[$qid] > AppConstant::NUMERIC_ZERO) {
                            $averageAttribute = round($attempts[$qid] / ($questionCount[$qid] - $questionInComplete[$qid]), AppConstant::NUMERIC_TWO);
                            $avgreg = round($regens[$qid] / ($questionCount[$qid] - $questionInComplete[$qid]), AppConstant::NUMERIC_TWO);
                            $averageTotal = round($timeOnTask[$qid] / ($questionCount[$qid] - $questionInComplete[$qid]), AppConstant::NUMERIC_TWO);
                            $averageTotalAssessment = round($timeOnTask[$qid] / ($totalCount[$qid]), AppConstant::NUMERIC_TWO);
                            if ($averageTotal == AppConstant::NUMERIC_ZERO) {
                                $averageTotal = AppUtility::t('N/A',false);
                            } else if ($averageTotal < AppConstant::SIXTY) {
                                $averageTotal .= AppUtility::t(' sec',false);
                            } else {
                                $averageTotal = round($averageTotal / AppConstant::SIXTY, AppConstant::NUMERIC_TWO) . AppUtility::t(' min');
                            }
                            if ($averageTotalAssessment == AppConstant::NUMERIC_ZERO)
                            {
                                $averageTotal = AppUtility::t('N/A',false);
                            } else if ($averageTotalAssessment < AppConstant::SIXTY) {
                                $averageTotalAssessment .= AppUtility::t(' sec',false);
                            } else {
                                $averageTotalAssessment = round($averageTotalAssessment / AppConstant::SIXTY, AppConstant::NUMERIC_TWO) . ' min';
                            }
                        } else {
                            $averageAttribute = AppConstant::NUMERIC_ZERO;
                            $avgreg = AppConstant::NUMERIC_ZERO;
                            $averageTotal = AppConstant::NUMERIC_ZERO;
                        }
                    } else {
                        $avg = AppUtility::t('NA');
                        $avg2 = AppUtility::t('NA');
                        $averageAttribute = AppUtility::t('NA');
                        $avgreg = AppUtility::t('NA');
                        $pc = AppConstant::NUMERIC_ZERO;
                        $pc2 = AppConstant::NUMERIC_ZERO;
                        $pi = AppUtility::t('NA');
                    }
                    echo "<td>{$itemNumber[$qid]}</td><td>";
                    if ($withdrawn[$qid] == AppConstant::NUMERIC_ONE)
                    { ?>
                         <span class="red"><?php AppUtility::t('Withdrawn')?></span>
                    <?php }
                    echo "{$description[$qid]}</td>"; ?>
                     <td><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-all-question?stu='.$student.'&cid='.$cid.'&asid=average&aid='.$aid.'&qid='.$qid);?>"
                    <?php if (isset($needManualGrade[$qid])) {
                        echo 'class="manualgrade" ';
                    } ?> ><?php AppUtility::t('Grade')?></a></td>
                     <td class="pointer c" onclick="GB_show('Low Scores','<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis-detail?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&type=score')?>','<?php AppConstant::FIVE_HUNDRED ?>','<?php AppConstant::FIVE_HUNDRED ?>');return false;"><b><?php echo $pc2.'%'; ?></b></td>
                     <td class="pointer" onclick="GB_show('Most Attempts and Regens','<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis-detail?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&type=att')?>','<?php AppConstant::FIVE_HUNDRED ?>','<?php AppConstant::FIVE_HUNDRED ?>');return false;"><?php echo $averageAttribute .'('.$avgreg.')' ?></td>
                     <td class="pointer c" onclick="GB_show('Incomplete','<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis-detail?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&type=incomp'); ?>','<?php AppConstant::FIVE_HUNDRED ?>','<?php AppConstant::FIVE_HUNDRED ?>');return false;"><?php echo $pi.'%';?></td>
                     <td class="pointer" onclick="GB_show('Most Time','<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis-detail?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&type=time'); ?>','<?php AppConstant::FIVE_HUNDRED ?>','<?php AppConstant::FIVE_HUNDRED ?>');return false;"><?php echo $averageTotal .'('.$averageTotalAssessment.')';?></td>
                    <?php if ($showHints == AppConstant::NUMERIC_ONE)
                {
                        if ($showExtraRef[$qid]) { ?>
                             <td class="pointer c" onclick="GB_show('Got Help','<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis-detail?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&type=help') ?>','<?php AppConstant::FIVE_HUNDRED ?>','<?php AppConstant::FIVE_HUNDRED ?>');return false;"><?php echo round(AppConstant::NUMERIC_HUNDREAD * $vidcnt[$qid]   ($questionCount[$qid] - $questionInComplete[$qid])).'%'; ?></td>
                        <?php } else { ?>
                            <td class="c"><?php AppUtility::t('N/A')?></td>
                        <?php }
                    }
                    echo "<td><input type=button value=\"Preview\" onClick=\"previewq({$qsetids[$qid]})\"/></td>\n";
                    echo "</tr>\n";
                    $i++;
                } ?>
             </tbody></table>
     </div>

         <p><?php AppUtility::t('Average time taken on this assessment')?>:
        <?php if (count($timeTaken) > AppConstant::NUMERIC_ZERO)
        {
            echo round(array_sum($timeTaken) / count($timeTaken) / AppConstant::SIXTY, AppConstant::NUMERIC_ONE);
        } else {
            echo AppConstant::NUMERIC_ZERO;
        } ?>
         <?php AppUtility::t('minutes')?></p>
    <?php } else
    {
        echo '</tbody></table>';
    } ?>
     <p><?php AppUtility::t('Items with grade link')?> <span class="manualgrade"><?php AppUtility::t('highlighted')?></span> <?php AppUtility::t('require manual grading')?>.<br/>
     <?php AppUtility::t('Note: Average Attempts, Regens, and Time only counts those who attempted the problem')?><br/>
     <?php AppUtility::t('All averages only include those who have started the assessment')?></p>
<?php
    if ($numberOfQuestions > AppConstant::NUMERIC_ZERO)
    {
        CategoryScoresUtility::catscores($qs, $averageScore, $defaultPoints, $defaultOutcome, $cid);
    } ?>
     <div class="cpmid"><?php AppUtility::t('Experimental')?>:<br/>
    <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/item-results?cid=' . $course->id . '&aid=' . $assessmentId); ?>">
        <?php AppUtility::t('Summary of assessment results')?></a> (<?php AppUtility::t('only meaningful for non-randomized questions')?>)<br/>
    <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/assessment-export?cid=' . $course->id . '&aid=' . $assessmentId); ?>">
        <?php AppUtility::t('Export student answer details')?></a></div>
<? echo '</div><br>';
}
echo '</div>';
function getpts($sc) {
    if (strpos($sc,'~')===false) {
        if ($sc > AppConstant::NUMERIC_ZERO) {
            return $sc;
        } else {
            return AppConstant::NUMERIC_ZERO;
        }
    } else {
        $sc = explode('~',$sc);
        $tot = AppConstant::NUMERIC_ZERO;
        foreach ($sc as $s) {
            if ($s > AppConstant::NUMERIC_ZERO) {
                $tot+=$s;
            }
        }
        return round($tot,AppConstant::NUMERIC_ONE);
    }
}

?>



