<?php

$flexwidth = true;
$nologo = true;

if (!$isTeacher) {
    echo "This page not available to students";
    exit;
}

$stus = array();
if ($type=='notstart') {
    natsort($studentNames);
    echo '<h3>Students who have not started this assessment</h3><ul>';
    foreach ($studentNames as $name) {
        echo '<li>'.$name.'</li>';
    }
    echo '</ul>';
} else if ($type=='help') {
    natsort($studentNames);
    echo '<h3>Students who clicked on help for this question</h3><ul>';
    foreach ($studentNames as $name) {
        echo '<li>'.$name.'</li>';
    }
    echo '</ul>';
} else {
    if ($type=='incomp') {
        natsort($studentNames);
        echo '<h3>Students who have started the assignment, but have not completed this question</h3><ul>';
        foreach ($studentNames as $name) {
            echo '<li>'.$name.'</li>';
        }
        echo '</ul>';
    } else if ($type=='score') {
        asort($studentScores);
        echo '<h3>Students with lowest scores</h3><table class="gb"><thead><tr><th>Name</th><th>Score</th></tr></thead><tbody>';
        foreach ($studentScores as $uid=>$sc) {
            echo '<tr><td>'.$studentNames[$uid].'</td><td>'.$sc.'</td></tr>';
        }
        echo '</tbody></table>';
    } else if ($type=='att') {
        arsort($studentAttribute);
        arsort($studentReGens);
        echo '<h3>Students with most attempts on scored version and Most Regens</h3><table class="gb"><thead><tr><th>Name</th><th>Attempts</th><th style="border-right:1px solid">&nbsp;</th><th>Name</th><th>Regens</th></tr></thead><tbody>';

        $rows = array();
        foreach ($studentAttribute as $uid=>$sc) {
            $rows[] = '<tr><td>'.$studentNames[$uid].'</td><td>'.$sc.'</td><td style="border-right:1px solid">&nbsp;</td>';
        }
        $rrc = 0;
        foreach ($studentReGens as $uid=>$sc) {
            $rows[$rrc] .= '<td>'.$studentNames[$uid].'</td><td>'.$sc.'</td></tr>';
            $rrc++;
        }
        foreach ($rows as $r) {
            echo $r;
        }
        echo '</tbody></table>';
    } else if ($type=='time') {
        arsort($studentTimes);
        echo '<h3>Students with most time spent on this question</h3><table class="gb"><thead><tr><th>Name</th><th>Time</th></tr></thead><tbody>';
        foreach ($studentTimes as $uid=>$sc) {
            echo '<tr><td>'.$studentNames[$uid].'</td><td>';
            if ($sc<60) {
                $sc .= ' sec';
            } else {
                $sc = round($sc/60,2) . ' min';
            }
            echo $sc;
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
}


?>
