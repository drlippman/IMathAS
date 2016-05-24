<?php
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AppUtility;
use app\components\AppConstant;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;


$this->title =  AppUtility::t(' Add New Thread',false);
///this->title ^^^
$this->params['breadcrumbs'][] =  $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<div class="item-detail-header">
    <?php
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), Html::encode($course->name),'Thread'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/thread?cid=' . $course->id.'&forum='.$forumData->id]]);
    ?>
</div>
<?php
 $form = ActiveForm::begin([
     'id' => 'add-thread',
     'options' => ['enctype' => 'multipart/form-data'],
    ]);
  ?>
<div class ="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo Html::encode($this->title) ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($rights == AppConstant::ADMIN_RIGHT || $rights == AppConstant::TEACHER_RIGHT) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($rights == AppConstant::STUDENT_RIGHT)
    {
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>
<input type="hidden" id="userId" value="<?php echo Html::encode($userId); ?>">
<input type="hidden" id="forumId" value="<?php echo Html::encode($forumData->id); ?>">
<input type="hidden" id="courseId" value="<?php echo Html::encode($course->id); ?>">
<div class="tab-content shadowBox">
    <div class="col-sm-12 col-md-12" style="padding-top: 30px;">
        <div class="col-sm-2 col-md-2 subject-label"><?php echo AppUtility::t('Subject')?></div>
        <div class="col-sm-10 col-md-10">
            <input type=text size=0 style="width: 100%;height: 40px; border: #6d6d6d 1px solid;" name=subject placeholder="" class="subject form-control" maxlength="60">
        </div>
    </div>
    <BR class=form>
    <?php

    if ($tagList != '') {
        $p = strpos($tagList,':');
        echo '<br><div class="col-md-12 col-sm-12"><span class="col-md-2 col-sm-2"><label for="tag">'.substr($tagList,0,$p).'</label></span>';
        echo '<span class="col-md-6 col-sm-6"><select name="tag" class="form-control" style="border: #6d6d6d 1px solid">';
        echo '<option value="">Select...</option>';
        $tags = explode(',',substr($tagList,$p));

        foreach ($tags as $tag) {
            $tag =  str_replace('"','&quot;',$tag);
            echo '<option value="'.$tag.'" ';
            if ($tag==$lineTag) {echo 'selected="selected"';}
            echo '>'.$tag.'</option>';
        }
        echo '</select></span></div><br class="form" />';
    }
    ?>
    <div class="col-sm-12 col-md-12" style="padding-top: 20px;">
        <div class="col-sm-2 col-md-2 message-label"><?php echo AppUtility::t('Message')?></div>
        <div class="col-sm-10 col-md-10 message-div">
            <div class=editor>
                <textarea cols=5 rows=12 id=message name=message style="width: 100%">
                </textarea>
            </div>
        </div>
    </div>
    <?php if($forumData['forumtype'] == AppConstant::NUMERIC_ONE)
    {
        ?>
    <div style="margin-left: 18%">
            <input name="file-0" type="file" id="uplaod-file" style="border: white 1px solid;" class="file-upload"/><br>
            <input type="text" size="20" name="description-0" placeholder="Description"><br>
            <br><button class="add-more">Add More Files</button><br>
    </div>
<?php }?>
    <?php if($rights > AppConstant::STUDENT_RIGHT)
    { ?>
        <div class="col-sm-12 col-md-12">
            <span class="col-sm-2 col-md-2 align-title"><?php echo AppUtility::t('Post Type')?></span>
            <span class="col-sm-10 col-md-10" id="post-type-radio-list">
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
        <div class="col-sm-12 col-md-12" style="padding-top: 20px;margin-right: 69px">
            <span class="col-md-2 col-sm-2 align-title"><?php echo AppUtility::t('Always Replies')?></span>
            <span class="col-md-10 col-sm-10" id="always-replies-radio-list">
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                <input type='radio' checked  name='always-replies' value='null'>
                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Use default')?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                <input type='radio' name='always-replies' value='always'>
                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Always')?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                <input type='radio' name='always-replies' value='never'>
                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Never')?></td></div></tr>
                <div class='radio padding-right-five student-enroll visibility pull-left override-hidden'><label class='checkbox-size label-visibility pull-left'><td>
                <input type=radio name="always-replies" value="date" /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Before')?></td>
                </div>
                <?php
                echo '<div class = "col-sm-4 padding-left-zero col-md-3 time-input" id="datepicker-id">';
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
                echo '<span class="pull-left padding-right-fifteen select-text-margin ">At</span>';
                echo '<div class="pull-left col-md-3 col-sm-5 padding-left-zero">';
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
    <?php }elseif($allowaNon == 1 && $rights == AppConstant::STUDENT_RIGHT)
    {
        ?>
        <div class="col-sm-12 col-md-12">
            <div class="col-sm-2 col-md-2"><b><?php echo AppUtility::t('Post Anonymously')?></b></div>
            <div class="col-sm-8 col-md-8"><input name="settings" id="post-anonymously" value="1" type="checkbox" ></div>
            <?php if ($forumData['settings']==1) {echo "checked=1";}
           echo "></span><br class=form/>"; ?>
        </div>
    <?php }
    if($isTeacher && $groupSetId > AppConstant::NUMERIC_ZERO){
        echo '<div class="col-sm-12 col-md-12"><div class="col-md-2 col-sm-2">Set thread to group:</div><div class="col-sm-4 col-md-4">';
        echo '<select name="stugroup" class="form-control">';
        echo '<option value="0" ';
        if ($curstugroupid==0) { echo 'selected="selected"';}
        echo '>Non group-specific</option>';
        foreach($groupSet as $row){
            echo '<option value="'.$row['id'].'" ';
            if ($curstugroupid==$row['id']) { echo 'selected="selected"';}
            echo '>'.$row['name'].'</option>';
        }
        echo '</select></div></div><br class="form" />';
    }?>

    <div class="col-md-6 col-md-offset-2 col-sm-6 col-sm-offset-2 padding-left-twenty-five padding-bottom-thirty padding-top-five">
        <a href="#" class="btn btn-primary add-new-thread" id="addNewThread"><i class="fa fa-share"></i>&nbsp;Create Thread</a>
    </div>
</div>
</form>
