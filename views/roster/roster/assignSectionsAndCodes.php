<?php
use app\components\AppUtility;
$this->title = AppUtility::t('Assign Section And Codes', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id]]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content padding-top-two-em">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox">
    <?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
    <div class="inner-content col-md-12 col-sm-12 padding-left-two-em padding-right-two-em">
        <form method="post" action="assign-sections-and-codes?cid=<?php echo $cid ?>">
            <input type="hidden" id="course-id" value="<?php echo $cid ?>">
            <table class="student-data table table-bordered table-striped table-hover data-table" bPaginate="false"
                   id="student-data-table">
                <thead>
                <tr>
                    <th><?php AppUtility::t('Name') ?></th>
                    <th><?php AppUtility::t('Section') ?></th>
                    <th><?php AppUtility::t('Code') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($studentInformation as $singleStudentInformation) { ?>
                    <tr>
                        <td><?php echo ucfirst($singleStudentInformation['Name']) ?></td>
                        <td><input class="form-control" type="text" value="<?php echo $singleStudentInformation['section'] ?>"
                                   name='section[<?php echo $singleStudentInformation['userid'] ?>]'></td>
                        <td><input class="form-control" type="text" value="<?php echo $singleStudentInformation['code'] ?>"
                                   name='code[<?php echo $singleStudentInformation['userid'] ?>]'></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <div class="col-md-6 col-sm-6 padding-left-zero">
                <input type="submit" class="btn btn-primary" id="change-button" value="<?php AppUtility::t('Submit') ?>">
            <span class="padding-left-one-em">
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid=' . $course->id) ?>"><?php AppUtility::t('Back') ?></a>
            </span>
            </div>
        </form>
    </div>
</div>