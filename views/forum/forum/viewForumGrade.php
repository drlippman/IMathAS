<?php
use app\components\AppUtility;
use app\components\AppConstant;
$pageTitle = AppUtility::t('View Forum Grade');
//echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
//echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Gradebook</a> ";
//echo "&gt; View Forum Grade</div>";
$possiblePoints = $user['points'];
$tutorEdit = $user['tutoredit'];
$canEditScore = (isset($teacherid) || (isset($tutorid) && $tutorEdit == AppConstant::NUMERIC_ONE));
$showLink = ($canEditScore || time() < $user['enddate']);
?>
 <div id="headerviewforumgrade" class="pagetitle">
     <h2><?php AppUtility::t('View Forum Grade')?></h2>
 </div>
 <p><?php AppUtility::t('Grades on forum')?> <b><?php echo $user['name']?></b> <?php AppUtility::t('for')?> <b><?php echo $user['FirstName']; echo $user['LastName']?></b></p>
<?php
$scores = array();
$totalPoints = AppConstant::NUMERIC_ZERO;
foreach ($forumInformation as $forum)
{
    $scores[$forum['refid']] = $forum;
    $totalPoints += $forum['score'];
}
if ($possiblePoints == AppConstant::NUMERIC_ZERO) { ?>
     <p><?php AppUtility::t('This forum is not a graded forum'); ?></p>
<?php } else { ?>
     <p><?php AppUtility::t('Total')?>: <?php echo $totalPoints  ?> <?php AppUtility::t('out of'); echo $possiblePoints; ?></p>
<?php }
if ($canEditScore) { ?>
     <form method="post" action="view-forum-grade?cid=<?php echo $course->id?>&fid=<?php echo $forumId ?>&stu=<?php echo $studentId ?>&uid=<?php echo $userId?>">
<?php } ?>
 <table class="gb"><thead><tr><th><?php AppUtility::t('Post')?></th><th><?php AppUtility::t('Points')?></th><th><?php AppUtility::t('Private Feedback')?></th></tr></thead><tbody>
<?php foreach ($forumPostData as $forumPost) {
    echo "<tr><td>";
    if ($showLink) { ?>
         <a href="<?php echo AppUtility::getURLFromHome('forum','forum/posts?cid='.$course->id.'&forum='.$forumId.'&thread='.$forumPost['threadid']) ?>">
    <?php }
    echo $forumPost['subject'];
    if ($showLink)
    {
        echo '</a>';
    }
    echo "</td>";
    if ($canEditScore) {
        if (isset($scores[$forumPost['id']])) {
            echo "<td><input type=text size=3 name=\"score[{$forumPost['id']}]\" id=\"score{$forumPost['id']}\" value=\"";
            echo $scores[$forumPost['id']][0];
        } else {
            echo "<td><input type=text size=3 name=\"newscore[{$forumPost['id']}]\" id=\"score{$forumPost['id']}\" value=\"";
        }
        echo "\" /> </td>";
        echo "<td><textarea cols=40 rows=1 id=\"feedback{$forumPost['id']}\" name=\"feedback[{$forumPost['id']}]\">{$scores[$forumPost['id']][1]}</textarea></td>";
    } else {
        if (isset($scores[$forumPost['id']])) {
            echo '<td>'.$scores[$forumPost['id']][0].'</td>';
        } else {
            echo "<td>-</td>";
        }
        echo '<td>'.$scores[$row['id']][1].'</td>';
    }
    echo "</tr>";
}
if ($canEditScore || isset($scores[0]))
    { ?>
     <tr>
         <td>
             <?php AppUtility::t('Additional score')?>
         </td>
    <?php if ($canEditScore) {
        if (isset($scores[0])) {
            echo "<td><input type=text size=3 name=\"score[0]\" id=\"score0\" value=\"";
            echo $scores[0][0];
        } else {
            echo "<td><input type=text size=3 name=\"newscore[0]\" id=\"score0\" value=\"";
        }
        echo "\" /> </td>";
        echo "<td><textarea cols=40 rows=1 id=\"feedback0\" name=\"feedback[0]\">{$scores[0][1]}</textarea></td>";
    } else {
        echo '<td>'.$scores[0][0].'</td>';
        echo '<td>'.$scores[0][1].'</td>';
    }
    echo "</tr>";
}
echo '</tbody></table>';
if ($canEditScore) { ?>
     <p><input type="submit" value="<?php AppUtility::t('Save Scores')?>" /></p>
     </form>
<?php }
?>


