<?php


use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Post';
$currentLevel = AppConstant::NUMERIC_ZERO;

?>
<div class="item-detail-header">
    <?php if($currentUser->rights > AppConstant::STUDENT_RIGHT) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Forum', false),AppUtility::t('Thread', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/search-forum?cid=' . $course->id,AppUtility::getHomeURL() .'forum/forum/thread?cid=' . $courseId . '&forum=' . $forumid]]);
    } else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Forum', false),AppUtility::t('Thread', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/search-forum?cid=' . $course->id,AppUtility::getHomeURL() .'forum/forum/thread?cid=' . $courseId . '&forum=' . $forumid]]);
    }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<input type="hidden" id="course-id" value="<?php echo $course->id?>">

<div class="item-detail-content">
    <?php
    if ($currentUser->rights > AppConstant::STUDENT_RIGHT) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } else {
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }
    ?>
</div>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" xmlns="http://www.w3.org/1999/html"/>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<meta name="viewport">
<input type="hidden" id="course-id" value="<?php echo $courseId ?>">
<input type="hidden" id="forum-id" value="<?php echo $forumid ?>">
<input type="hidden" id="tag-id" value="<?php echo $tagValue ?>">
<input type="hidden" id="thread-id" value="<?php echo $threadid ?>">
<input type="hidden" id="user-id" value="<?php echo $currentUser['id'] ?>">
<input type="hidden" class="home-path" value="<?php echo AppUtility::getHomeURL() ?>">

<div class="tab-content shadowBox padding-top-one">

    <?php
    if (!$oktoshow) {

    } else{
        echo "<br/><b style=\"font-size: 120%\">Post: {$subject[$threadid]}</b><br/>\n";
        echo "<b style=\"font-size: 100%\">Forum: $forumname</b></p>";

        $nextth = '';
        $prevth = '';

        if (($resultPrev) > 0) {
            $prevth = $resultPrev['id'];
            echo "<a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$prevth&grp=$groupid\">Prev</a> ";
        } else {
            echo "Prev ";
        }

        if (($resultNext) > 0) {
            $nextth = $resultNext['id'];
            echo "<a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$nextth&grp=$groupid\">Next</a> ";
        } else {
            echo "Next";
        }
        echo " | <a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$threadid&markunread=true\">Mark Unread</a>";
        if ($tagged) {
            echo " | <a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$threadid&markuntagged=true\">Unflag</a>";
        } else {
            echo " | <a href=\"post?courseid=$courseId&forumid=$forumid&threadid=$threadid&marktagged=true\">Flag</a>";
        } ?>


        <button onclick="expandall()">Expand All</button>
        <button onclick="collapseall()">Collapse All</button>
        <button onclick="showall()">Show All</button>
        <button onclick="hideall()">Hide All</button>

      <?php   if ($view==2) {
            echo "<a href=\"post?view=$view&courseid=$courseId&forumid=$forumid&page=$page&threadid=$threadid&view=0\">View Expanded</a>";
        } else {
            echo "<a href=\"post?view=$view&courseid=$courseId&forumid=$forumid&page=$page&threadid=$threadid&view=2\">View Condensed</a>";
        }
        echo "<br/>";echo "<br/>";
        $printChildren = new AppUtility();
        $printChildren->printchildren(0);

        if ($caneditscore && $haspoints) {
            echo '<div><input type=submit name="save" value="Save Grades" /></div>';
            if ($prevth!='' && $page!=-3) {
                echo '<input type="hidden" name="prevth" value="'.$prevth.'"/>';
                echo '<input type="submit" name="save" value="Save Grades and View Previous"/>';
            }
            if ($nextth!='' && $page!=-3) {
                echo '<input type="hidden" name="nextth" value="'.$nextth.'"/>';
                echo '<input type="submit" name="save" value="Save Grades and View Next"/>';
            }
            echo "</form>";
        }
?>
        <img src="<?php echo AppUtility::getHomeURL()?>img/expand.gif" style="visibility: hidden">
        <img src="<?php echo AppUtility::getHomeURL()?>img/collapse.gif" style="visibility: hidden">

    <?php
        }
    ?>
</div>

