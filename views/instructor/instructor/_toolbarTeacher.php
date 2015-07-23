<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
?>

    <div class="common-navbar">
        <ul class="nav nav-tabs teacher-tab nav-justified nav-tabs-non-shadow" id="nav">
            <?php if($section == 'course'){ ?>
            <li class="active master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('instructor/instructor', 'index?cid='.$course->id); ?>"><i class="fa fa-book icon-nav"></i><?php AppUtility::t('Course'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('instructor/instructor', 'index?cid='.$course->id); ?>"><i class="fa fa-book icon-nav"></i><?php AppUtility::t('Course'); ?></a></li>
            <?php } ?>
            <?php if($section == 'gradebook'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "#"><i class="fa fa-file icon-nav"></i><?php AppUtility::t('Gradebook'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "#"><i class="fa fa-file icon-nav"></i><?php AppUtility::t('Gradebook'); ?></a></li>
            <?php } ?>
            <?php if($section == 'calendar'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><i class="fa fa-calendar icon-nav"></i><?php AppUtility::t('Calendar'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><i class="fa fa-calendar icon-nav"></i><?php AppUtility::t('Calendar'); ?></a></li>
            <?php } ?>
            <?php if($section == 'roster'){ ?>
                <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>"><i class="fa fa-users icon-nav"></i><?php AppUtility::t('Roster'); ?></a></li>
            <?php } else { ?>
                <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>"><i class="fa fa-users icon-nav"></i><?php AppUtility::t('Roster'); ?></a></li>
            <?php } ?>
            <?php if($section == 'Forums'){ ?>
                <li class="active master-tabs border-right-zero"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"><i class="fa fa-weixin icon-nav"></i><?php AppUtility::t('Forums'); ?></a></li>
            <?php } else { ?>
                <li class="master-tabs border-right-zero"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"><i class="fa fa-weixin icon-nav"></i><?php AppUtility::t('Forums'); ?></a></li>
            <?php } ?>
        </ul>
    </div>

