<?php
use app\components\AppConstant;
use app\components\AppUtility;

if ($undo) {
    echo "<div>Un-use LatePass</div>";
    $row = $resultExp;
    if (count($resultExp) == AppConstant::NUMERIC_ZERO)
    {
        echo '<p>Invalid</p>';
    } else{
        if ($now > $endDate && $row['enddate'] < $now + $hours * 60 * 60) {
            echo '<p>Too late to un-use this LatePass</p>';
        }
        echo $latePass;
    }
    if (!$sessionData['ltiitemtype'] || $sessionData['ltiitemtype']!=0) { ?>
        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid='.$courseId)?>"><?php echo 'Continue'?></a>
    <?php  } else { ?>
        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-test?cid='.$courseId.'&id'.$sessionData['ltiitemid'])?>"><?php echo 'Continue'?></a>
    <?php  }
} else{
    echo "Redeem LatePass</div>\n";
    if ($numLatepass == AppConstant::NUMERIC_ZERO)
    {
        echo "<p>You have no late passes remaining.</p>";
    }
     else {
        echo "<p>You are not allowed to use additional latepasses on this assessment.</p>";
    }
}