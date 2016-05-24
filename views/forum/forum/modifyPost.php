<?php
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AppUtility;
use app\components\AppConstant;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\ActiveForm;
use app\components\AssessmentUtility;
$this->title = AppUtility::t('Modify Post',false);
$this->params['breadcrumbs'][] = Html::encode($this->title);
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;

$form = ActiveForm::begin([
    'id' => '',
    'options' => ['enctype' => 'multipart/form-data'],
    ]);
    ?>
<!--<form enctype="multipart/form-data" method="post" action="modify-post?forumId=--><?php //echo $forumId ?><!--&courseId=--><?php //echo $course->id ?><!--&threadId=--><?php //echo $threadId ?><!--">-->
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), Html::encode($course->name)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL().'course/course/course?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo Html::encode($this->title); ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($currentUser->rights == 100 || $currentUser->rights == 20) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($currentUser->rights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>

    <input type="hidden" id="thread-id" value="<?php echo $threadId ?>">
    <input type="hidden" id="forum-id" value="<?php echo $forumId ?>">
    <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
  <div class="tab-content shadowBox">
    <div style="padding-top: 20px">
        <div class="col-sm-2 subject-label"><?php echo AppUtility::t('Subject');
            ?></div>
        <div class="col-sm-10">
            <input type=text maxlength="60" value="<?php echo Html::encode($thread[0]['subject']) ?>" size=0 style="width: 100%; height: 40px; border:#6d6d6d 1px solid;"  name=subject class="subject textbox padding-left-ten">
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
      <div style="margin-left: 18%">
    <?php if($forumData['forumtype'] == 1){?>
        <?php if($thread[0]['files'] != '')
        {
            $files = explode('@@',$thread[0]['files']);


                for ($i=0;$i<count($files)/2;$i++){
                    ?>
                    <br><input type="text" name="file[<?php echo $i;?>]" value="<?php echo $files[2*$i]?>"/>
                    <?php if ($GLOBALS['filehandertype'] == 's3')
                    {
                        /*PATH FOR AMAZON IMAGE */
                    }
                    else{
                        $uploadDir = AppConstant::UPLOAD_DIRECTORY;?>
                        <a href="<?php echo AppUtility::getAssetURL()?>Uploads/<?php echo $files[2*$i+1]?>" target="_blank">View</a>
                    <?php }?>
                    Delete? <input type="checkbox" name="fileDel[<?php echo $i;?>]" value="1"/><br/>
                <?php }
             ?>

        <?php } ?>
        <br><input name="file-0" type="file" id="uplaod-file" style="border: white 1px solid;" class="file-upload"/><br><input type="text" size="20" name="description-0" placeholder="Description"><br>
        <br><button class="add-more">Add More Files</button><br>
    <?php }?>
     </div>
    <?php if($currentUser['rights'] > 10 && $forumPostData['parent'] == 0 && $forumPostData['userid'] == $userId)
    {?>
        <div>
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
        <?php
        $date=date("m/d/Y",strtotime("+1 week"));
        $time=date('g:i A');
        if ($thread[0]['replyBy'] == null) {
        $thread[0]['replyBy'] = 0;
        }
        elseif ($thread[0]['replyBy'] == 0) {
            $thread[0]['replyBy'] = 1;
        }
        elseif ($thread[0]['replyBy']<AppConstant::ALWAYS_TIME && $thread[0]['replyBy']>AppConstant::NUMERIC_ZERO)
        {
            $date = AppUtility::tzdate("m/d/Y",($thread[0]['replyBy']));
            $time = AppUtility::tzdate("g:i a",($thread[0]['replyBy']));
            $thread[0]['replyBy']=3;
        }

         ?>
        <div>
            <span class="col-sm-2 align-title"><?php echo AppUtility::t('Allow Replies');?></span>
            <span class="col-sm-10" id="always-replies-radio-list">
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                <input type='radio' checked  name='always-replies' value='null'<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'],0);?>>
                                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Use default');?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                <input type='radio' name='always-replies' value='always'<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::ALWAYS_TIME);?> >
                                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Always');?></td></div></tr>
                <tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td>
                                <input type='radio' name='always-replies' value='never'<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'],1);?> >
                                <span class='cr'><i class='cr-icon fa fa-check align-check'></i></span></label></td><td ><?php echo AppUtility::t('Never');?></td></div></tr>
                <div class='radio student-enroll override-hidden visibility pull-left'><label class='checkbox-size label-visibility pull-left'><td>
                            <input type=radio name="always-replies" value="date"<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::NUMERIC_THREE);?> >
                            <span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Before')?></td></div>
                <?php
                echo '<div class = "col-md-4 time-input" id="datepicker-id">';
                echo DatePicker::widget([
                    'name' => 'startDate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => $date,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy']
                ]);
                echo '</div>';?>
                <?php
                echo '<label class="end col-md-1 select-text-margin margin-right-minus-thirtythree">At</label>';
                echo '<div class="pull-left col-md-4">';
                echo TimePicker::widget([
                    'name' => 'startTime',
                    'options' => ['placeholder' => 'Select operating time ...'],
                    'convertFormat' => true,
                    'value' => $time,
                    'pluginOptions' => [
                        'format' => "mm/dd/yyyy g:i A",
                        'todayHighlight' => true,
                    ]
                ]);
                echo '</div>';?>
            </span>
        </div>
    <?php }

    if ($currentUser['rights'] == 10 && ($forumData['settings'] & 1 == 1)){
        ?>
        <div>
            <div class="col-md-2"><b><?php echo AppUtility::t('Post Anonymously');?></b></div>
            <div class="col-md-8"><input id="post-anonymously" value="post-anonymously" name="post-anonymously" type="checkbox" value="1"<?php if ($thread[0]['isANon'] == 1){
                    echo "checked=1";
                } ?> ></div>
        </div>
    <?php } ?>
      <div class="col-sm-6 header-btn col-sm-offset-2 padding-left-fifteen padding-top-five padding-bottom-thirty">
          <input class="btn btn-primary add-new-thread" type="submit" id="save-changes" value="Save changes">
      </div>
</div>
<?php ActiveForm::end(); ?>