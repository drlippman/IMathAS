<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
?>
    <div class="common-navbar">
        <ul class="nav nav-tabs teacher-tab nav-justified nav-tabs-non-shadow" id="nav">
            <?php if($section == 'course'){ ?>
            <li class="active master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('course/course', 'index?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/course.png"/><?php AppUtility::t(' Course'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('course/course', 'index?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/course.png"/><?php AppUtility::t(' Course'); ?></a></li>
            <?php } ?>
            <?php if($section == 'gradebook'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/grade-book-student-detail?cid=' .$course->id);?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/gradbook.png"/><?php AppUtility::t(' Gradebook'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/grade-book-student-detail?cid=' .$course->id);?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/gradbook.png"/><?php AppUtility::t(' Gradebook'); ?></a></li>
            <?php } ?>
            <?php if($secti == 'calendar'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconCalendar.png"/><?php AppUtility::t(' Calendar'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconCalendar.png"/><?php AppUtility::t(' Calendar'); ?></a></li>
            <?php } ?>
            <?php if($section == 'Forums'){ ?>
                <li class="active master-tabs border-right-zero"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconForum.png"/><?php AppUtility::t(' Forums'); ?></a></li>
            <?php } else { ?>
                <li class="master-tabs border-right-zero"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconForum.png"/><?php AppUtility::t(' Forums'); ?></a></li>
            <?php } ?>
        </ul>
    </div>

