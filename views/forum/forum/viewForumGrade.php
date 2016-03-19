<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('View Forum Grade', false);
$this->params['breadcrumbs'][] = $this->title;

$possiblePoints = $user['points'];
$tutorEdit = $user['tutoredit'];
$canEditScore = (($isTeacher) || (($isTutor) && $tutorEdit == AppConstant::NUMERIC_ONE));
$showLink = ($canEditScore || time() < $user['enddate']);
?>
<div class="item-detail-header">
    <?php
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]);
    ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']);?>
</div>
<div class="tab-content shadowBox">
 <br><div class="col-md-12 col-sm-12"><?php AppUtility::t('Grades on forum')?> <b><?php echo $user['name']?></b>
     <?php AppUtility::t('for')?> <b><?php echo trim(ucfirst($user['FirstName'])).' '. (ucfirst($user['LastName']))?></b></div>
<?php

$scores = array();
$totalPoints = AppConstant::NUMERIC_ZERO;
if(!empty($forumInformation)){
    foreach ($forumInformation as $forum)
    {
        $scores[$forum['refid']] = $forum;
        $totalPoints += $forum['score'];
    }
}

if ($possiblePoints == AppConstant::NUMERIC_ZERO) { ?>
     <div class="col-md-12 col-sm-12"><?php AppUtility::t('This forum is not a graded forum'); ?></div>
<?php } else { ?>
     <div class="col-md-12 col-sm-12"><?php AppUtility::t('Total')?>: <?php echo $totalPoints  ?> <?php AppUtility::t('out of '); echo $possiblePoints; ?></div>
<?php }
if ($canEditScore) {
?>
     <form method="post" action="view-forum-grade?cid=<?php echo $course->id?>&fid=<?php echo $forumId ?>&stu=<?php echo $studentId ?>&uid=<?php echo $userId?>">
<?php }
?>
 <table class="display course-section table table-bordered table-striped table-hover data-table"><thead>
     <tr>
         <th><?php AppUtility::t('Post')?></th>
         <th><?php AppUtility::t('Points')?></th>
         <th><?php AppUtility::t('Private Feedback')?></th>
     </tr></thead><tbody>

<?php
    if(!empty($forumPostData)){

        foreach ($forumPostData as $forumPost) {
            echo "<tr><td>";
            if ($showLink) { ?>
                 <a href="<?php echo AppUtility::getURLFromHome('forum','forum/post?courseid='.$course->id.'&forumid='.$forumId.'&threadid='.$forumPost['threadid']) ?>">
            <?php }
            echo $forumPost['subject'];
            if ($showLink)
            {
                echo '</a>';
            }
            echo "</td>";
            if ($canEditScore) {
                if (($scores[$forumPost['id']])) {
                    echo "<td class='staticScore'><input type=text size=3 class='child' name=\"score[{$forumPost['id']}]\" id=\"score{$forumPost['id']}\" value=\"";
                    echo $scores[$forumPost['id']]['score'];
                } else {
                    echo "<td class='staticScore'><input type=text size=3 class='child' name=\"newscore[{$forumPost['id']}]\" id=\"score{$forumPost['id']}\" value=\"";
                }
                echo "\" /> </td>";
                echo "<td><textarea cols=40 rows=1 id=\"feedback{$forumPost['id']}\" name=\"feedback[{$forumPost['id']}]\">{$scores[$forumPost['id']]['feedback']}</textarea></td>";
            } else {
                if (($scores[$forumPost['id']])) {
                    echo '<td>'.$scores[$forumPost['id']]['score'].'</td>';
                } else {
                    echo "<td>-</td>";
                }
                echo '<td>'.$scores[$forumPost['id']]['feedback'].'</td>';
            }
            echo "</tr>";
        }
    }
if ($canEditScore || ($scores[0]))
    { ?>
     <tr>
         <td>
             <?php AppUtility::t('Additional score')?>
         </td>
    <?php if ($canEditScore) {
        if (($scores[0])) {
            echo "<td class='staticScore'><input type=text class='child' size=3 name=\"score[0]\" id=\"score0\" value=\"";
            echo $scores[0]['score'];
        } else {
            echo "<td class='staticScore'><input type=text size=3 class='child' name=\"newscore[0]\" id=\"score0\" value=\"";
        }
        echo "\" /> </td>";
        echo "<td><textarea cols=40 rows=1 id=\"feedback0\" name=\"feedback[0]\">{$scores[0]['feedback']}</textarea></td>";
    } else {
        echo '<td>'.$scores[0]['score'].'</td>';
        echo '<td>'.$scores[0]['feedback'].'</td>';
    }
    echo "</tr>";
}
echo '</tbody></table>';
if ($canEditScore) { ?>
     <div class="col-md-12 col-sm-12"><input type="submit" value="<?php AppUtility::t('Save Scores')?>" /></div>
     </form>
<?php }
?>
</div>

<script>
    $(document).ready(function(){
        $(function() {
            $('.staticScore').on('keydown', '.child', function(e){-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()});
        })
    })

</script>