<?php
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AppUtility;
use app\components\AppConstant;
date_default_timezone_set("Asia/Calcutta");
$this->title = 'Add New Thread';
if ($rights > AppConstant::STUDENT_RIGHT){
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}

//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => [Yii::$app->session->get('referrer')]];
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid='.$course->id.'&forumid='.$forumName->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="" xmlns="http://www.w3.org/1999/html">
    <h3><b>Add Thread - <?php echo $forumName->name;?></h3>
    <br><br>
    <div>
        <div class="col-md-2"><b>Subject</b></div>
        <div class="col-md-8"><input class="subject form-control" type="text" ></div>
    </div>
    <br><br><br>
    <div>
        <div class="col-md-2"><b>Message</b></div>
        <?php echo "<div class='left col-md-10'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='20'></textarea></div></div><br>"; ?>
    </div>
    <div>
        <br>
        <input type="hidden" id="userId" value="<?php echo $userId; ?>">
        <input type="hidden" id="forumId" value="<?php echo $forumName->id; ?>">
        <input type="hidden" id="courseId" value="<?php echo $course->id; ?>">

    </div>
    <?php if($rights > 10)
    {?>
         <div >
            <span class="col-md-2"><b>Post Type:</b></span>
        <span class="col-md-10" id="post-type-radio-list">
            <input type="radio" name="post-type" id="regular" value="0" checked >Regular<br>
            <input type="radio" name="post-type" id="displayed_at_top_of_list" value="1" >Displayed at top of list<br>
            <input type="radio" name="post-type" id="displayed_at_top_and_locked" value="2">Displayed at top and locked (no replies)<br>
            <input type="radio" name="post-type" id="only_students_can_see" value="3">Displayed at top and students can only see their own replies <br>
            </span>
        </div>
        <div>
            <span class="col-md-2"><b>Always Replies:</b></span>
        <span class="col-md-10" id="always-replies-radio-list" >
            <input type="radio" name="always-replies" value="0" checked >Use default<br>
            <input type="radio" name="always-replies"  value="1" >Always<br>
            <input type="radio" name="always-replies"  value="2">Never<br>
            <input type="radio" name="always-replies" class="end pull-left "  id="always" value="3"><label class="end pull-left ">Before</label>


                <div class="col-md-3" id="datepicker-id">
                    <?php
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
                    echo '<label class="end pull-left  select-text-margin"> At</label>';
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
            </div>

            </span>
        </div>
 <?php }?>
    <div class="col-md-4  col-lg-offset-2">
    <input type="button" class="btn btn-primary" id="addNewThread" value="Post Thread">
        </div>
    
</div>

