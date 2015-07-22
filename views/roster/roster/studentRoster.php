<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
$this->title = 'Roster';
$this->params['breadcrumbs'][] = $this->title;
?>
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
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
<div class="roster-upper-content col-lg-12">
    <div class="page-title col-lg-10 pull-left"><?php AppUtility::t('Student Roster');?>
    </div>
    <div class="with-selected col-lg-2 pull-left">
        <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><?php AppUtility::t('With selected');?><span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu selected-options">
                    <li><a class="non-locked" href="#"><i class="fa fa-unlock-alt fa-fw"></i>&nbsp;<?php AppUtility::t('Select non-locked');?></a></li>
                    <li>
                        <form action="roster-email?cid=<?php echo $course->id ?>" method="post" id="roster-email-form">
                            <input type="hidden" id="student-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentEmail()"><img class="fa-fw" src="<?php echo AppUtility::getAssetURL()?>img/email.png">&nbsp;<?php AppUtility::t('Email');?></a></li>

                        </form>
                    <li>
                        <form action="roster-message?cid=<?php echo $course->id ?>" method="post" id="roster-message-form">
                            <input type="hidden" id="message-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentMessage()"><i class="fa fa-envelope-o fa-fw"></i>&nbsp;<?php AppUtility::t('Message');?></a></li>
                        </form>
                    <li><a id="un-enroll-link" href="#"><i class="fa fa-trash fa-fw"></i>&nbsp;<?php AppUtility::t('Unenroll');?></a></li>
                    <li><a id="lock-btn" href="#"><i class='fa fa-lock fa-fw'></i>&nbsp;<?php AppUtility::t('Lock');?></a></li>
                    <li>
                        <form action="make-exception?cid=<?php echo $course->id ?>" id="make-exception-form" method="post">
                            <input type="hidden" id="exception-id" name="student-data" value=""/>
                            <input type="hidden" id="section-name" name="section-data" value=""/>
                            <a class="with-selected-list" href="javascript: teacherMakeException()"><i class='fa fa-plus-square fa-fw'></i>&nbsp;<?php AppUtility::t('Make Exception');?></a>
                        </form>
                    </li>
                    <li>
                        <form action="copy-student-email?cid=<?php echo $course->id ?>" method="post" id="copy-emails-form">
                            <input type="hidden" id="email-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: copyStudentsEmail()"><i class="fa fa-clipboard fa-fw"></i>&nbsp;<?php AppUtility::t('Copy Emails');?></a>
                        </form>
                    </li>
                    <li><a href="#"><i class="fa fa-user fa-fw"></i>&nbsp;<?php AppUtility::t('Pictures');?></a></li>
                </ul>
            </li>

        </ul>
    </div>
</div>
<div class="roster-table">
    <table class="student-data-table table table-striped table-hover data-table" id="student-information" bPaginate="false" >
        <thead>
        <tr>
            <th class="studentId">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="header-checked" value="">
                        <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                    </label>
                </div>
            </th>
            <?php if ($isImageColumnPresent == 1) {
                ?><th></th>
            <?php } ?>
            <th>Last</th>
            <th>First</th>
            <th>Email</th>
            <th>UserName</th>
            <th>Last Access</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="student-information-table">
        </tbody>
    </table>
</div>
</div>
