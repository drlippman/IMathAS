<?php
use app\components\displayq2;
use app\components\interpretUtility;
use app\components\AppUtility;

$this->title = 'Item Result'; ?>

<div class="item-detail-header">
    <?php
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?stu=0&cid=' . $course->id], 'page_title' => $this->title]);
    ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item"><br>
    <?php
    if (!$isTeacher && !$isTutor) {
        echo '<br><div class="text">';
        echo "You need to log in as a teacher or tutor to access this page";
        echo '</div>';
    } else {

//look for this question in the itemorder (may be multiple times)
//get the answer they gave on the (first or last) attempt
//if multiple choice, multiple answer, or matching, use the question code and seed
//   to backtrack to original option
//tally results, grouping by result
//output results.  For numeric/function, sort by frequency

        $scorebarwidth = 60;

        echo '<div class="item-analysis">';
        echo '<div id="headergb-itemanalysis" class="pagetitle"><h2>Item Results: ';
        $defpoints = $assessment['defpoints'];
        echo $assessment['name'] . '</h2></div>';
        $itemorder = $assessment['itemorder'];
        $itemarr = array();
        $itemnum = array();
        foreach (explode(',', $itemorder) as $k => $itel) {
            if (strpos($itel, '~') !== false) {
                $sub = explode('~', $itel);
                if (strpos($sub[0], '|') !== false) {
                    array_shift($sub);
                }
                foreach ($sub as $j => $itsub) {
                    $itemarr[] = $itsub;
                    $itemnum[$itsub] = ($k + 1) . '-' . ($j + 1);
                }
            } else {
                $itemarr[] = $itel;
                $itemnum[$itel] = ($k + 1);
            }
        }
        echo '<p class="red-text"  >Warning: Results are not accurate or meaningful for randomized questions</p>';

        $questions = array_keys($qdata);

        foreach ($itemarr as $k => $q) {
            echo '<div  class="item-result-border">';
            echo '<p><span  class="right-side">(Question ID ' . $qsids[$q] . ')</span><b>' . $qsdata[$qsids[$q]][2] . '</b></p>';
            echo '<br class="clear"/>';
            echo '<div class="item-result-answer">';
            showresults($q, $qsdata[$qsids[$q]][0], $qdata, $qsids, $qsdata, $scorebarwidth);
            echo '</div>';
            echo '<div class="item-result-question">';
            echo '</div>';
            echo '<br class="clear"/>';
            echo '</div>';
        }
    }
    echo '</div><br>';
    echo '</div>';
    function showresults($q, $qtype, $qdata, $qsids, $qsdata, $scorebarwidth)
    {
        eval(interpretUtility::interpret('control', $qtype, $qsdata[$qsids[$q]][1]));
        if ($qtype == 'choices' || $qtype == 'multans' || $qtype == 'multipart') {
            if (isset($choices) && !isset($questions)) {
                $questions =& $choices;
            }
            if ($qtype == 'multipart') {
                if (!is_array($anstypes)) {
                    $anstypes = explode(',', $anstypes);
                }
                foreach ($anstypes as $i => $type) {
                    if ($type == 'choices' || $type == 'multans') {
                        if (isset($questions[$i])) {
                            $ql = $questions[$i];
                        } else {
                            $ql = $questions;
                        }
                        if ($type == 'multans') {
                            if (is_array($answers)) {
                                $al = $answers[$i];
                            } else {
                                $al = $answers;
                            }
                        } else if ($type == 'choices') {
                            if (is_array($answer)) {
                                $al = $answer[$i];
                            } else {
                                $al = $answer;
                            }
                        }
                        disp($qdata, $qsdata, $qsids, $scorebarwidth, $q, $type, $i, $al, $ql);
                    } else {
                        if (is_array($answer)) {
                            $al = $answer[$i];
                        } else {
                            $al = $answer;
                        }
                        disp($qdata, $qsdata, $qsids, $scorebarwidth, $q, $type, $i, $al);
                    }

                }
            } else {
                if ($qtype == 'multans') {
                    $al = $answers;
                } else if ($qtype == 'choices') {
                    $al = $answer;
                }
                disp($qdata, $qsdata, $qsids, $scorebarwidth, $q, $qtype, -1, $al, $questions);
            }
        } else {
            disp($qdata, $qsdata, $qsids, $scorebarwidth, $q, $qtype, -1, $answer);
        }
    }

    function disp($qdata, $qsdata, $qsids, $scorebarwidth, $q, $qtype, $part = -1, $answer, $questions = array())
    {

        $res = array();
        $correct = array();
        $answer = explode(',', $answer);
        if($qdata) {
            foreach ($qdata[$q] as $varr) {
                if ($part > -1) {
                    $v = $varr[0][$part];
                } else {
                    $v = $varr[0];
                }
                $v = explode('|', $v); //sufficient for choices and multans
                foreach ($v as $vp) {
                    if ($part > -1) {
                        if ($varr[1][$part] > 0) {
                            $correct[] = $vp;
                        }
                    } else {
                        if ($varr[1] > 0) {
                            $correct[] = $vp;
                        }
                    }
                    if ($vp !== '') {
                        $res[] = $vp;
                    }
                }
            }
        }
        $res = array_count_values($res);
        if (!empty($res)) {
            $restot = max($res);
        }

        if ($part > -1) {
            echo "Part " . ($part + 1);
        }
        echo '<table class="gridded">';
        echo '<thead>';
        echo '<tr><td class="black-text">Answer</td><td class="black-text">Count of students</td></tr>';
        echo '</thead><tbody>';
        if ($qtype == 'choices' || $qtype == 'multans') {
            for ($k = 0; $k < count($questions); $k++) {
                if (!isset($res[$k])) {
                    continue;
                }
                echo '<tr><td>' . $questions[$k] . '</td><td>' . $res[$k];
                echo ' <span class="scorebarinner" ';
                if (in_array($k, $answer)) {
                    echo 'background:#9f9;';
                } else {
                    echo 'background:#f99;';
                }
                echo 'width:' . round($scorebarwidth * $res[$k] / $restot) . 'px;"';
                echo '>&nbsp;</span>';
                echo '</td></tr>';
            }
        } else {
            arsort($res);
            foreach ($res as $ans => $cnt) {
                echo '<tr><td>' . $ans . '</td><td>' . $cnt;
                echo ' <span class="scorebarinner" ';

                if (in_array($ans, $correct)) {
                    echo 'background:#9f9;';
                } else {
                    echo 'background:#f99;';
                }
                echo 'width:' . round($scorebarwidth * $cnt / $restot) . 'px;"';
                echo '>&nbsp;</span>';
                echo '</td></tr>';
            }
        }
        echo '</tbody></table>';
    }
    ?>
