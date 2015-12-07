<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
?>
    <div class="common-navbar">
        <ul class="nav nav-tabs teacher-tab nav-justified nav-tabs-non-shadow" id="nav">
            <?php if($section == 'course'){ ?>
            <li class="active master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('course/course', 'course?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/course.png"/><span class="margin-left-fifteen"><?php AppUtility::t('Course'); ?></span></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('course/course', 'course?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/course.png"/><span class="margin-left-fifteen"><?php AppUtility::t(' Course'); ?></span></a></li>
            <?php } ?>
            <?php if($section == 'gradebook'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/grade-book-student-detail?cid=' .$course->id);?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/gradbook.png"/><span class="margin-left-fifteen"><?php AppUtility::t(' Gradebook'); ?></span></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/grade-book-student-detail?cid=' .$course->id);?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/gradbook.png"/><span class="margin-left-fifteen"><?php AppUtility::t(' Gradebook'); ?></span></a></li>
            <?php } ?>
            <?php if($section == 'calendar'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconCalendar.png"/><span class="margin-left-fifteen"><?php AppUtility::t(' Calendar'); ?></span></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconCalendar.png"/><span class="margin-left-fifteen"><?php AppUtility::t(' Calendar'); ?></span></a></li>
            <?php } ?>
            <?php if($section == 'Forums'){ ?>
                <li class="active master-tabs border-right-zero"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconForum.png"/><span class="margin-left-fifteen"><?php AppUtility::t(' Forums'); ?></span></a></li>
            <?php } else { ?>
                <li class="master-tabs border-right-zero"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"><img class="nav-course-icon" src="<?php echo AppUtility::getAssetURL()?>img/iconForum.png"/><span class="margin-left-fifteen"><?php AppUtility::t(' Forums'); ?></span></a></li>
            <?php } ?>
        </ul>
    </div>

