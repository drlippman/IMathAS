<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
$this->title = 'Roster';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'roster']);?>
</div>
<div class="tab-content shadowBox"">
<div class="roster-nav-tab">
    <ul class="nav nav-tabs nav-justified roster-menu-bar-nav">
        <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'login-grid-view?cid='.$course->id); ?>"><?php AppUtility::t('View Login Grid');?></a></li>
        <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'assign-sections-and-codes?cid='.$course->id); ?>"><?php AppUtility::t('Assign Sections/Codes');?></a></li>
        <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-late-passes?cid='.$course->id); ?>"><?php AppUtility::t('Manage LatePass');?></a></li>
        <li><a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-tutors?cid='.$course->id); ?>"><?php AppUtility::t('Manage Tutors');?></a></li>
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
<div class="roster-upper-content">
    <div class="page-title"><?php AppUtility::t('Student Roster');?>
    </div>
</div>
</div>
