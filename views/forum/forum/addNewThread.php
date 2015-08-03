<?php
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;

$this->title = AppUtility::t('Add New Thread',false);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn">
            <a href="#"class="btn btn-primary pull-right add-new-thread" id="addNewThread"><i class="fa fa-share"></i>&nbsp;Create Thread</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<input type="hidden" id="userId" value="<?php echo $userId; ?>">
<input type="hidden" id="forumId" value="<?php echo $forumData->id; ?>">
<input type="hidden" id="courseId" value="<?php echo $course->id; ?>">
<div class="tab-content shadowBox">
    <div style="padding-top: 20px">
        <div class="col-lg-2 subject-label"><?php echo AppUtility::t('Subject')?></div>
        <div class="col-lg-10">
            <input type=text size=0 style="width: 100%;height: 40px; border: #6d6d6d 1px solid;" name=name value="" class="subject">
        </div>
    </div>
    <BR class=form>

    <div class="editor-div">
        <div class="col-lg-2 message-label"><?php echo AppUtility::t('Message')?></div>
        <div class="col-lg-10 message-div">
            <div class=editor>
                <textarea cols=5 rows=12 id=message name=message style="width: 100%">
                </textarea>
            </div>
        </div>
    </div>
    <?php if($forumData['forumtype'] == 1){?>

    <?php }?>
    <?php if($rights > 10)
    {?>
        <div >
            <span class="col-md-2 align-title"><?php echo AppUtility::t('Post Type')?></span>
            <span class="col-md-10" id="post-type-radio-list">
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                 <input type='radio' checked name='post-type' id="regular" value='0'>
                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Regular')?></td></div></tr>
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                 <input type='radio'  name='post-type' id="displayed_at_top_of_list" value='1'>
                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Displayed at top of list')?></td></div></tr>
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                 <input type='radio'  name='post-type' id="displayed_at_top_and_locked" value='2'>
                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Displayed at top and locked (no replies)')?></td></div></tr>
                 <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                 <input type='radio'  name='post-type' id="only_students_can_see" value='3'>
                 <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Displayed at top and students can only see their own replies ')?></td></div></tr>
            </span>
        </div>
        <div>
            <span class="col-md-2 align-title"><?php echo AppUtility::t('Always Replies')?></span>
            <span class="col-md-10" id="always-replies-radio-list">
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                <input type='radio' checked  name='always-replies' value='0'>
                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Use default')?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                <input type='radio' name='always-replies' value='1'>
                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Always')?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                <input type='radio' name='always-replies' value='2'>
                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Never')?></td></div></tr>
                <div class='radio student-enroll visibility pull-left override-hidden'><label class='checkbox-size label-visibility pull-left'><td>
                <input type=radio name="always-replies" value="3" /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Before')?></td></div>
                <?php
                echo '<div class = "col-lg-4 time-input" id="datepicker-id">';
                echo DatePicker::widget([
                        'name' => 'endDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y",strtotime("+1 week")),
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                echo '</div>';?>
                <?php
                echo '<label class="end col-lg-1">At</label>';
                echo '<div class="pull-left col-lg-4">';
                echo TimePicker::widget([
                        'name' => 'startTime',
                        'options' => ['placeholder' => 'Select operating time ...'],
                        'convertFormat' => true,
                        'value' => date('g:i A'),
                        'pluginOptions' => [
                            'format' => "m/d/Y g:i A",
                            'todayHighlight' => true,
                        ]
                    ]);
                echo '</div>';?>
            </span>
        </div>
    <?php }elseif($rights == 10 && ($forumData['settings'] & 1 == 1)){?>
        <div>
            <div class="col-md-2"><b><?php echo AppUtility::t('Post Anonymously')?></b></div>
            <div class="col-md-8"><input id="post-anonymously" value="post-anonymously" type="checkbox" ></div>
        </div>
    <?php } ?>
</div>


