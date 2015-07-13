<?php
//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => [Yii::$app->session->get('referrer')]];
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AppUtility;
date_default_timezone_set("Asia/Calcutta");
$this->title = 'Modify Thread';
if ($currentUser->rights > AppConstant::STUDENT_RIGHT){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid='.$course->id.'&forumid='.$forumId]];
$this->params['breadcrumbs'][] = $this->title;?>
<div>
    <h2><b>Modify Post</b></h2>

    <br><br><br>
    <input type="hidden" id="thread-id" value="<?php echo $threadId ?>">
    <input type="hidden" id="forum-id" value="<?php echo $forumId ?>">
    <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
    <div>
        <span class="col-md-1"><b>Subject:</b></span>
        <span class="col-md-8"><input class="textbox subject" type="text" value="<?php echo $thread[0]['subject'] ?>"></span>
    </div>
    <br><br><br>

    <div>
        <span class="col-md-1"><b>Message:</b></span>
        <?php echo "<span class='left col-md-11'><div class= 'editor'>
        <textarea id='message' name='message'  style='width: 100%;' rows='20' cols='200'>";echo $thread[0]['message'];
        echo "</textarea></div></span><br>"; ?>
    </div>

    <?php if($currentUser['rights'] > 10)
    {?>
        <div >
            <span class="col-md-2"><b>Post Type:</b></span>
        <span class="col-md-10" id="post-type">
            <input type="radio" name="post-type" id="regular" value="0" checked >Regular<br>
            <input type="radio" name="post-type" id="displayed_at_top_of_list" value="1" >Displayed at top of list<br>
            <input type="radio" name="post-type" id="displayed_at_top_and_locked" value="2">Displayed at top and locked (no replies)<br>
            <input type="radio" name="post-type" id="only_students_can_see" value="3">Displayed at top and students can only see their own replies <br>
            </span>
        </div>
        <div>
            <span class="col-md-2"><b>Always Replies:</b></span>
        <span class="col-md-10" id="reply-by" >
            <input type="radio" name="always-replies" value="NULL" checked >Use default<br>
            <input type="radio" name="always-replies"  value="0" >Always<br>
            <input type="radio" name="always-replies"  value="2000000000">Never<br>
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

    <div class="col-lg-offset-2 col-md-8">
        <br>
        <a class="btn btn-primary" id="save-changes">Save changes</a>
    </div>
</div>
