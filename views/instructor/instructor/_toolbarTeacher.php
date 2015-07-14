<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
?>
<div class = "instructor-toolbar">
    <div class = "common-toolbar">
        <div id = "tab1" class = "tab">
            <a href = "<?php echo AppUtility::getURLFromHome('instructor/instructor', 'index?cid='.$course->id); ?>"><?php AppUtility::t('Course'); ?></a>
        </div>
        <div id = "tab2" class = "tab">
            <a href = "<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$course->id); ?>"><?php AppUtility::t('Gradebook'); ?></a>
        </div>
        <div id = "tab3" class = "tab">
            <a href = "#"><?php AppUtility::t('Calendar'); ?></a>
        </div>
        <div id = "tab4" class = "tab">
            <a href = "<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>"><?php AppUtility::t('Roster'); ?></a>
        </div>
    </div>
</div>