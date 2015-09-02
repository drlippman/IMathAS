<?php
use app\components\AppUtility;
$this->title = 'Item Analysis';?>

<div class="item-detail-header">

    <?php
    if($student == 0) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?stu=0&cid=' . $course->id], 'page_title' => $this->title]);
    }else if ($student==-1) {
      echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Gradebook','Averages'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id,AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?stu=0&cid=' . $course->id,AppUtility::getHomeURL().'gradebook/gradebook/gradebook?stu='.$student.'&cid='.$course->id], 'page_title' => $this->title]);
    } else if ($from=='isolate') {
      echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'View Scores'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id,'#'], 'page_title' => $this->title]);
/* pass same parameters when assign hyper link to isolateassessgrade page
 *  echo "&gt; <a href=\"isolateassessgrade.php?cid=$courseId&aid=$assessmentId\"></a> ";
 */
    } else if ($from=='gisolate') {
      echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'View Group Scores'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id,'#'], 'page_title' => $this->title]);
/*pass same parameters when assign hyper link to isolateassessbygroup page
 *     echo "&gt; <a href=\"isolateassessbygroup.php?cid=$courseId&aid=$assessmentId\"></a> ";
 */
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
if ($isTeacher) {
   echo '<br><div class="text">';
    echo "This page not available to students";
        echo '</div>';
}else {
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

    <div class="cpmid item-analysis"><a
            href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/isolate-assessment-grade?cid=' . $courseId . '&amp;aid=' . $assessmentId) ?>">View
            Score List</a></div>
    <?php
    echo '<div id="headergb-itemanalysis" class="pagetitle item-analysis"><h2>Item Analysis: ';
    $defpoints = $assessmentData['defpoints'];
    $aname = $assessmentData['name'];
    $itemorder = $assessmentData['itemorder'];
    $defoutcome = $assessmentData['defoutcome'];
    $showhints = $assessmentData['showhints'];
    echo $aname . '</h2></div>';
    echo '<div class="item-analysis">';
    $root = AppUtility::getHomeURL() . 'instructor/instructor/item-analysis-detail?cid=' . $courseId . '&aid=' . $assessmentId . '&qid=' . $qid . '&type=notstart';

    if ($notstarted == 0) {
        echo '<p>All students have started this assessment. ';
    } else {
        echo "<p><a href=\"#\" onclick=\"GB_show('Not Started',$root,500,300);return false;\">$notstarted student" . ($notstarted > 1 ? 's' : '') . "</a> ($nonstartedper%) " . ($notstarted > 1 ? 'have' : 'has') . " not started this assessment.  They are not included in the numbers below. ";
    }
    echo '</p>';
    //echo '<a href="isolateassessgrade.php?cid='.$cid.'&aid='.$aid.'">View Score List</a>.</p>';

    echo "<table class=gb id=myTable><thead>"; //<tr><td>Name</td>\n";
    echo "<tr><th>#</th><th scope=\"col\">Question</th><th>Grade</th>";
    //echo "<th scope=\"col\">Average Score<br/>All</th>";
    echo "<th scope=\"col\" title=\"Average score for all students who attempted this question\">Average Score<br/>Attempted</th><th title=\"Average number of attempts and regens (new versions)\" scope=\"col\">Average Attempts<br/>(Regens)</th><th scope=\"col\" title=\"Percentage of students who have not started this question yet\">% Incomplete</th>";
    echo "<th scope=\"col\" title=\"Average time a student worked on this question, and average time per attempt on this question\">Time per student<br/> (per attempt)</th>";
    if ($showhints == 1) {
        echo '<th scope="col" title="Percentage of students who clicked on help resources in the question, if available">Clicked on Help</th>';
    }
    echo "<th scope=\"col\">Preview</th></tr></thead>\n";
    echo "<tbody>";

    if (count($qtotal) > 0) {
        $i = 1;
        $descrips = array();
        $points = array();
        $withdrawn = array();
        $qsetids = array();
        $needmanualgrade = array();
        $showextref = array();

        foreach ($questionData as $row) {
            $descrips[$row[1]] = $row[0];
            $points[$row[1]] = $row[2];
            $qsetids[$row[1]] = $row[3];
            $withdrawn[$row[1]] = $row[4];
            if ($row[5] == 'essay' || $row[5] == 'file') {
                $needmanualgrade[$row[1]] = true;
            } else if ($row[5] == 'multipart') {
                if (preg_match('/anstypes.*?(".*?"|array\(.*?\))/', $row[6], $matches)) {
                    if (strpos($matches[1], 'essay') !== false || strpos($matches[1], 'file') !== false) {
                        $needmanualgrade[$row[1]] = true;
                    }
                }
            }
            if ($row[8] != '' && ($row[7] == 2 || ($row[7] == 0 && $showhints == 1))) {
                $showextref[$row[1]] = true;
            } else {
                $showextref[$row[1]] = false;
            }
        }

        $avgscore = array();
        $qs = array();

        foreach ($itemarr as $qid) {
            if ($i % 2 != 0) {
                echo "<tr class=even>";
            } else {
                echo "<tr class=odd>";
            }
            $pts = $points[$qid];
            if ($pts == 9999) {
                $pts = $defpoints;
            }
            if ($qcnt[$qid] > 0) {
                $avg = round($qtotal[$qid] / $qcnt[$qid], 2);
                if ($qcnt[$qid] - $qincomplete[$qid] > 0) {
                    $avg2 = round($qtotal[$qid] / ($qcnt[$qid] - $qincomplete[$qid]), 2); //avg adjusted for not attempted
                } else {
                    $avg2 = 0;
                }
                $avgscore[$i - 1] = $avg;
                $qs[$i - 1] = $qid;

                if ($pts > 0) {
                    $pc = round(100 * $avg / $pts);
                    $pc2 = round(100 * $avg2 / $pts);
                } else {
                    $pc = 'N/A';
                    $pc2 = 'N/A';
                }
                $pi = round(100 * $qincomplete[$qid] / $qcnt[$qid], 1);

                if ($qcnt[$qid] - $qincomplete[$qid] > 0) {
                    $avgatt = round($attempts[$qid] / ($qcnt[$qid] - $qincomplete[$qid]), 2);
                    $avgreg = round($regens[$qid] / ($qcnt[$qid] - $qincomplete[$qid]), 2);
                    $avgtot = round($timeontask[$qid] / ($qcnt[$qid] - $qincomplete[$qid]), 2);
                    $avgtota = round($timeontask[$qid] / ($tcnt[$qid]), 2);
                    if ($avgtot == 0) {
                        $avgtot = 'N/A';
                    } else if ($avgtot < 60) {
                        $avgtot .= ' sec';
                    } else {
                        $avgtot = round($avgtot / 60, 2) . ' min';
                    }
                    if ($avgtota == 0) {
                        $avgtot = 'N/A';
                    } else if ($avgtota < 60) {
                        $avgtota .= ' sec';
                    } else {
                        $avgtota = round($avgtota / 60, 2) . ' min';
                    }
                } else {
                    $avgatt = 0;
                    $avgreg = 0;
                    $avgtot = 0;
                }
            } else {
                $avg = "NA";
                $avg2 = "NA";
                $avgatt = "NA";
                $avgreg = "NA";
                $pc = 0;
                $pc2 = 0;
                $pi = "NA";
            }

            echo "<td>{$itemnum[$qid]}</td><td>";
            if ($withdrawn[$qid] == 1) {
                echo '<span class="red">Withdrawn</span> ';
            }
            echo "{$descrips[$qid]}</td>";
            echo "<td><a href=\"gradeallq.php?stu=$student&cid=$cid&asid=average&aid=$aid&qid=$qid\" ";
            if (isset($needmanualgrade[$qid])) {
                echo 'class="manualgrade" ';
            }
            echo ">Grade</a></td>";
            //echo "<td>$avg/$pts ($pc%)</td>";
            echo "<td class=\"pointer c\" onclick=\"GB_show('Low Scores','gb-itemanalysisdetail.php?cid=$cid&aid=$aid&qid=$qid&type=score',500,500);return false;\"><b>$pc2%</b></td>";
            echo "<td class=\"pointer\" onclick=\"GB_show('Most Attempts and Regens','gb-itemanalysisdetail.php?cid=$cid&aid=$aid&qid=$qid&type=att',500,500);return false;\">$avgatt ($avgreg)</td>";
            echo "<td class=\"pointer c\" onclick=\"GB_show('Incomplete','gb-itemanalysisdetail.php?cid=$cid&aid=$aid&qid=$qid&type=incomp',500,500);return false;\">$pi%</td>";
            echo "<td class=\"pointer\" onclick=\"GB_show('Most Time','gb-itemanalysisdetail.php?cid=$cid&aid=$aid&qid=$qid&type=time',500,500);return false;\">$avgtot ($avgtota)</td>";
            if ($showhints == 1) {
                if ($showextref[$qid]) {
                    echo "<td class=\"pointer c\" onclick=\"GB_show('Got Help','gb-itemanalysisdetail.php?cid=$cid&aid=$aid&qid=$qid&type=help',500,500);return false;\">" . round(100 * $vidcnt[$qid] / ($qcnt[$qid] - $qincomplete[$qid])) . '%</td>';
                } else {
                    echo '<td class="c">N/A</td>';
                }
            }
            echo "<td><input type=button value=\"Preview\" onClick=\"previewq({$qsetids[$qid]})\"/></td>\n";

            echo "</tr>\n";
            $i++;
        }

        echo "</tbody></table>\n";
        echo "<script type=\"text/javascript\">\n";
        echo "initSortTable('myTable',Array('N','S',false,'N','N','N','N','N',false),true);\n";
        echo "</script>\n";
        echo "<p>Average time taken on this assessment: ";
        if (count($timetaken) > 0) {
            echo round(array_sum($timetaken) / count($timetaken) / 60, 1);
        } else {
            echo 0;
        }
        echo " minutes</p>\n";
    } else {
        echo '</tbody></table>';
    }

    echo '<p>Items with grade link <span class="manualgrade">highlighted</span> require manual grading.<br/>';
    echo "Note: Average Attempts, Regens, and Time only counts those who attempted the problem<br/>";
    echo 'All averages only include those who have started the assessment</p>';

    if ($numberOfQuestions > 0) {
        include("../assessment/catscores.php");
        catscores($qs, $avgscore, $defpoints, $defoutcome, $cid);
    }
    echo '<div class="cpmid">Experimental:<br/>'; ?>
    <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/item-results?cid=' . $course->id . '&aid=' . $assessmentId); ?>">Summary
        of assessment results</a> (only meaningful for non-randomized questions)<br/>

    <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/assessment-export?cid=' . $course->id . '&aid=' . $assessmentId); ?>">Export
        student answer details</a></div>
<? echo '</div><br>';
}
echo '</div>';

function getpts($sc) {
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

?>



