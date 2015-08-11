<?php
use app\components\AppUtility;
?>
<div class="roster-nav-tab">
    <ul class="nav nav-tabs aligned roster-menu-bar-nav">
        <li><a href="#"><?php AppUtility::t('Groups');?></a></li>
        <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'login-grid-view?cid='.$course->id); ?>"><?php AppUtility::t('View Login Grid');?></a></li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php AppUtility::t('Manage');?><span class="caret"></span></a>
            <ul class="dropdown-menu settings-menu">
                <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'assign-sections-and-codes?cid='.$course->id); ?>"><?php AppUtility::t('Assign Sections and/or Codes');?></a></li>
                <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-late-passes?cid='.$course->id); ?>"><?php AppUtility::t('Manage LatePass');?></a></li>
                <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-tutors?cid='.$course->id); ?>"><?php AppUtility::t('Manage Tutors');?></a></li>
            </ul>
        </li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php AppUtility::t('Enroll Students');?><span class="caret"></span></a>
            <ul class="dropdown-menu enroll-options">
                <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-enrollment?cid='.$course->id.'&enroll=student'); ?>"><?php AppUtility::t('Enroll Student with known username');?></a></li>
                <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'enroll-from-other-course?cid='.$course->id); ?>"><?php AppUtility::t('Enroll Student from another Course');?></a></li>
                <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'create-and-enroll-new-student?cid='.$course->id); ?>"><?php AppUtility::t('Create and Enroll new student');?></a></li>
            </ul>
        </li>
        <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'import-student?cid='.$course->id); ?>"><?php AppUtility::t('Import Students');?></a></li>
    </ul>
</div>