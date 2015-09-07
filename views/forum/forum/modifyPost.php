<?php
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
use app\components\AssessmentUtility;
$this->title = AppUtility::t('Modify Post',false);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<form method="post" action="modify-post?forumId=<?php echo $forumId ?>&courseId=<?php echo $course->id ?>&threadId=<?php echo $threadId ?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn">
            <input class="btn btn-primary pull-right add-new-thread" type="submit" id="save-changes" value="Save changes">
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($currentUser->rights == 100 || $currentUser->rights == 20) {
        echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($currentUser->rights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>

    <input type="hidden" id="thread-id" value="<?php echo $threadId ?>">
    <input type="hidden" id="forum-id" value="<?php echo $forumId ?>">
    <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
  <div class="tab-content shadowBox">
    <div style="padding-top: 20px">
        <div class="col-sm-2 subject-label"><?php echo AppUtility::t('Subject');?></div>
        <div class="col-sm-10">
            <input type=text  value="&nbsp;&nbsp;<?php echo $thread[0]['subject'] ?>" size=0 style="width: 100%;height: 40px; border: #6d6d6d 1px solid;"  name=subject class="subject textbox 3">
        </div>
    </div>
    <BR class=form>

    <div class="editor-div">
        <div class="col-sm-2 message-label"><?php echo AppUtility::t('Message');?></div>
        <div class="col-sm-10 message-div">
            <div class=editor>
                <textarea cols=5 rows=12 id=message name=message style="width: 100%">
                    <?php echo $thread[0]['message'];?>
                </textarea>
            </div>
        </div>
    </div>
    <?php if($forumData['forumtype'] == 1){?>

    <?php }?>
    <?php if($currentUser['rights'] > 10)
    {?>
        <div >
            <span class="col-sm-2 align-title"><?php echo AppUtility::t('Post Type');?></span>
            <span class="col-sm-10" id="post-type-radio-list">
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                 <input type='radio' checked name='post-type' id="regular" value='0'value="0"<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_ZERO);?> >
                                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Regular');?></td></div></tr>
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                 <input type='radio'  name='post-type' id="displayed_at_top_of_list" value='1'<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_ONE);?> >
                                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Displayed at top of list');?></td></div></tr>
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                 <input type='radio'  name='post-type' id="displayed_at_top_and_locked" value='2'<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_TWO);?>>
                                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Displayed at top and locked (no replies)');?></td></div></tr>
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                 <input type='radio'  name='post-type' id="only_students_can_see" value='3'<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_THREE);?> >
                                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Displayed at top and students can only see their own replies ');?></td></div></tr>
            </span>
        </div><br><br>
        <?php if ($thread[0]['replyBy'] === null) {
        $thread[0]['replyBy'] = 1;
    }
        if ($thread[0]['replyBy'] != 3) {
            $date = date('m/d/y');
            $time = date("G:i");
        }
        if ($thread[0]['replyBy'] > 3 && $thread[0]['replyBy'] < AppConstant::ALWAYS_TIME) {
            $date = date('m/d/y', $thread[0]['replyBy']);
            $time = date("G:i", $thread[0]['replyBy']);
            $thread[0]['replyBy'] = 3;
        }
        ?>
        <div>
            <span class="col-sm-2 align-title"><?php echo AppUtility::t('Always Replies');?></span>
            <span class="col-sm-10" id="always-replies-radio-list">
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                <input type='radio' checked  name='always-replies' value='1'<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::NUMERIC_ZERO);?>>
                                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Use default');?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                <input type='radio' name='always-replies' value='1'<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], 1);?> >
                                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Always');?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                <input type='radio' name='always-replies' value='2'<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::ALWAYS_TIME);?> >
                                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Never');?></td></div></tr>
                <div class='radio student-enroll override-hidden visibility pull-left'><label class='checkbox-size label-visibility pull-left'><td>
                            <input type=radio name="always-replies" value="3"<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::NUMERIC_THREE);?> >
                            <span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Before')?></td></div>
                <?php
                echo '<div class = "col-lg-4 time-input" id="datepicker-id">';
                echo DatePicker::widget([
                    'name' => 'startDate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => $date,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'm/d/yy']
                ]);
                echo '</div>';?>
                <?php
                echo '<label class="end col-lg-1 select-text-margin margin-right-minus-thirtythree">At</label>';
                echo '<div class="pull-left col-lg-4">';
                echo TimePicker::widget([
                    'name' => 'startTime',
                    'options' => ['placeholder' => 'Select operating time ...'],
                    'convertFormat' => true,
                    'value' => $time,
                    'pluginOptions' => [
                        'format' => "m/d/Y g:i A",
                        'todayHighlight' => true,
                    ]
                ]);
                echo '</div>';?>
            </span>
        </div>
    <?php }if ($currentUser['rights'] == 10 && ($forumData['settings'] & 1 == 1)){
        ?>
        <div>
            <div class="col-md-2"><b><?php echo AppUtility::t('Post Anonymously');?></b></div>
            <div class="col-md-8"><input id="post-anonymously" value="post-anonymously" name="post-anonymously" type="checkbox" value="1"<?php if ($thread[0]['isANon'] == 1){
                    echo "checked=1";
                } ?> ></div>
        </div>
    <?php } ?>
</div>


