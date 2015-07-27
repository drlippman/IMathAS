<?php
use app\components\AppUtility;

$this->title = AppUtility::t('Roster', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
<input type="hidden" id="image-id" value="<?php echo $isImageColumnPresent ?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'roster']); ?>
</div>
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="roster-upper-content col-lg-12">
    <div class="page-title col-lg-8 pull-left"><?php AppUtility::t('Student Roster'); ?>
    </div>
    <div class="with-selected col-lg-2 pull-left">
        <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><i class="fa fa-user ic"></i>&nbsp;<?php AppUtility::t('Pictures'); ?>
                    <span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu selected-options">
                    <li>
                        <a href="student-roster?cid=<?php echo $course->id ?>&showpic=1"><?php AppUtility::t('Show'); ?></a>
                    </li>
                    <li>
                        <a href="student-roster?cid=<?php echo $course->id ?>&showpic=0"><?php AppUtility::t('Hide'); ?></a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="with-selected col-lg-2 pull-left">
        <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown"
                   href="#"><?php AppUtility::t('With selected'); ?><span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu selected-options">
                    <li><a class="non-locked" href="#"><i
                                class="fa fa-unlock-alt fa-fw"></i>&nbsp;<?php AppUtility::t('Select non-locked'); ?>
                        </a></li>
                    <li>
                        <form action="roster-email?cid=<?php echo $course->id ?>" method="post" id="roster-email-form">
                            <input type="hidden" id="student-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentEmail()"><i
                                    class="fa fa-at fa-fw"></i>&nbsp;<?php AppUtility::t('Email'); ?></a>
                    </li>
                    </form>
                    <li>
                        <form action="roster-message?cid=<?php echo $course->id ?>" method="post"
                              id="roster-message-form">
                            <input type="hidden" id="message-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentMessage()"><i
                                    class="fa fa-envelope-o fa-fw"></i>&nbsp;<?php AppUtility::t('Message'); ?></a>
                    </li>
                    </form>
                    <li><a id="un-enroll-link" href="#"><i
                                class="fa fa-trash-o fa-fw"></i>&nbsp;<?php AppUtility::t('Unenroll'); ?></a></li>
                    <li><a id="lock-btn" href="#"><i class='fa fa-lock fa-fw'></i>&nbsp;<?php AppUtility::t('Lock'); ?>
                        </a></li>
                    <li>
                        <form action="make-exception?cid=<?php echo $course->id ?>" id="make-exception-form"
                              method="post">
                            <input type="hidden" id="exception-id" name="student-data" value=""/>
                            <input type="hidden" id="section-name" name="section-data" value=""/>
                            <a class="with-selected-list" href="javascript: teacherMakeException()"><i
                                    class='fa fa-plus-square fa-fw'></i>&nbsp;<?php AppUtility::t('Make Exception'); ?>
                            </a>
                        </form>
                    </li>
                    <li>
                        <form action="copy-student-email?cid=<?php echo $course->id ?>" method="post"
                              id="copy-emails-form">
                            <input type="hidden" id="email-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: copyStudentsEmail()"><i
                                    class="fa fa-clipboard fa-fw"></i>&nbsp;<?php AppUtility::t('Copy Emails'); ?></a>
                        </form>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</div>
<div class="roster-table">
    <table class="student-data-table table table-striped table-hover data-table" id="student-information"
           bPaginate="false">
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
                ?>
                <th><?php AppUtility::t('Picture') ?></th>
            <?php } ?>
            <th><?php AppUtility::t('Last') ?></th>
            <th><?php AppUtility::t('First') ?></th>
            <th><?php AppUtility::t('Email') ?></th>
            <th><?php AppUtility::t('UserName') ?></th>
            <th><?php AppUtility::t('Last Access') ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody id="student-information-table">
        </tbody>
    </table>
</div>

