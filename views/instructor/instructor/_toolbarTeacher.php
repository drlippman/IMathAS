<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
?>
<div class="item-detail-content">
    <ul class="nav nav-tabs nav-justified">
        <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('instructor/instructor', 'index?cid='.$course->id); ?>"><?php AppUtility::t('Course'); ?></a></li>
        <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$course->id); ?>"><?php AppUtility::t('Gradebook'); ?></a></li>
        <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('course/course', 'calendar?cid='.$course->id); ?>"><?php AppUtility::t('Calendar'); ?></a></li>
        <li class="master-tabs"><a class="grey-color-link" href = "<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>"><?php AppUtility::t('Roster'); ?></a></li>
    </ul>
</div>