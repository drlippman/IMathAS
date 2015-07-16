<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
?>

    <div class="common-navbar">
        <ul class="nav nav-tabs nav-justified" id="nav">
            <?php if($section == 'course'){ ?>
            <li class="active master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('instructor/instructor', 'index?cid='.$course->id); ?>"><?php AppUtility::t('Course'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('instructor/instructor', 'index?cid='.$course->id); ?>"><?php AppUtility::t('Course'); ?></a></li>
            <?php } ?>
            <?php if($section == 'gradebook'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "#"><?php AppUtility::t('Gradebook'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "#"><?php AppUtility::t('Gradebook'); ?></a></li>
            <?php } ?>
            <?php if($section == 'calendar'){ ?>
            <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><i class="fa fa-calendar fa-2x"></i><?php AppUtility::t('Calendar'); ?></a></li>
            <?php } else { ?>
            <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><i class="fa fa-calendar fa-2x"></i><?php AppUtility::t('Calendar'); ?></a></li>
            <?php } ?>
            <?php if($section == 'roster'){ ?>
                <li class="active master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>"><?php AppUtility::t('Roster'); ?></a></li>
            <?php } else { ?>
                <li class="master-tabs"><a class="grey-color-link"  href = "<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>"><?php AppUtility::t('Roster'); ?></a></li>
            <?php } ?>
        </ul>
    </div>

