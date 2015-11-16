<?php
use app\components\AppUtility;

if ($commentType == "instr") {
    $this->title = AppUtility::t('Modify Instructor Notes', false);
} else {
    $this->title = AppUtility::t('Modify Gradebook Comments', false);
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id]]); ?>
</div>
<form id="mainform" method="post" action="#">
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading width-eighty-per">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
    <div class="item-detail-content">
        <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]); ?>
    </div>
    <div class="tab-content shadowBox">

    <div class="col-md-12 col-sm-12 padding-left-zero padding-right-zero">
        <?php
        if ($commentType == "instr") {
            ?>
            <div class="col-md-12 col-sm-12 modify-gradebook-comments-background">
                <a class="padding-left-fifteen padding-top-twenty-five" href="gb-comments?cid=<?php echo $course->id ?>"><?php AppUtility::t('View/Edit Student comments') ?></a>
            </div>
            <div class="padding-left-thirty col-md-12 col-sm-12">
                <div class="padding-top-twenty-five">
                    <?php AppUtility::t('These notes will only display on this page and gradebook exports. They will not be visible to students.') ?>
                </div>
                <div class="padding-top-twenty padding-bottom-twenty">
                    <a href="upload-comments?cid=<?php echo $course->id ?>&comtype=instr"><?php AppUtility::t('Upload comments') ?></a>
                </div>
            </div>
        <?php } else { ?>
            <div class="col-md-12 col-sm-12 modify-gradebook-comments-background">
                <a class="padding-left-fifteen" href="gb-comments?cid=<?php echo $course->id ?>&comtype=instr"><?php AppUtility::t('View/Edit Instructor notes') ?></a>
            </div>
            <div class="padding-left-thirty col-md-12 col-sm-12">
                <div class="padding-top-twenty-five">
                    <?php AppUtility::t('These comments will display at the top of the student\'s gradebook score list.') ?>
                </div>
                <div class="padding-top-twenty padding-bottom-twenty">
                    <a href="upload-comments?cid=<?php echo $course->id ?>"><?php AppUtility::t('Upload comments') ?></a>
                </div>
            </div>
        <?php } ?>
    </div>
    <div class="col-md-12 col-sm-12 padding-left-thirty padding-bottom-thirty inner-content-gradebook padding-top-zero">
        <span class="col-md-2 col-sm-3 padding-zero"><?php AppUtility::t('Add/Replace to all') ?></span>
    <span class="col-md-10 col-sm-9 padding-zero">
        <div class="col-sm-8 padding-zero">
            <textarea cols="50" rows="3" id="comment_txt" class="form-control"></textarea><br>
            <input type="hidden" id="isComment" name="isComment" value="1"/>
            <input type="hidden" id="courseId" value="<?php echo $course->id ?>"/>
            <input type="button" value="<?php AppUtility::t('Append') ?>" class="btn btn-primary"
                   onclick="appendPrependReplaceText(1)"/>
            <input type="button" value="<?php AppUtility::t('Prepend') ?>" class="btn btn-primary margin-left-ten"
                   onclick="appendPrependReplaceText(3)"/>
            <input type="button" value="<?php AppUtility::t('Replace') ?>" class="btn btn-primary margin-left-ten"
                   onclick="appendPrependReplaceText(2)"/>
        </div>
    </span>
        <br class="form">
        <?php foreach ($studentsInfo as $student) { ?>
            <br class="form">
            <span
                class="col-md-2 col-sm-3 padding-zero"><?php echo ucfirst($student['LastName'] . ", " . ucfirst($student['FirstName'])); ?></span>
            <span class="col-md-10 col-sm-9 padding-zero">
            <div class="col-sm-8 padding-zero">
                <textarea class="form-control comment-text-id" cols="50" rows="3"
                          name="<?php echo $student['id']; ?>"><?php if ($commentType == "instr") {
                        echo trim($student['gbinstrcomment']);
                    } else {
                        echo trim($student['gbcomment']);
                    } ?></textarea>
            </div>
        </span>
            <br class="form">
        <?php
        }
        ?>
        <div class="col-md-4 col-md-offset-2 col-sm-4 col-sm-offset-3 padding-left-zero padding-top-thirty">
            <button type="submit" class="btn btn-primary" id="gbComments">
                <i class="fa fa-share header-right-btn"></i><?php AppUtility::t('Save Comments') ?>
            </button>
        </div>
    </div>
    </div>
</form>
