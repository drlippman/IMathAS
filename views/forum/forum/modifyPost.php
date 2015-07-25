<?php
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AssessmentUtility;
use app\components\AppUtility;

date_default_timezone_set("Asia/Calcutta");
$this->title = 'Modify Thread';
if ($currentUser->rights > AppConstant::STUDENT_RIGHT) {

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
} else {
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid=' . $course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid=' . $course->id . '&forumid=' . $forumId]];
$this->params['breadcrumbs'][] = $this->title; ?>
<div>
    <h2><b>Modify Post</b></h2>
    <br><br><br>

    <form method="post"
          action="modify-post?forumId=<?php echo $forumId ?>&courseId=<?php echo $course->id ?>&threadId=<?php echo $threadId ?>">
        <input type="hidden" id="thread-id" value="<?php echo $threadId ?>">
        <input type="hidden" id="forum-id" value="<?php echo $forumId ?>">
        <input type="hidden" id="course-id" value="<?php echo $course->id ?>">

        <div>
            <span class="col-md-2"><b>Subject:</b></span>
            <span class="col-md-8"><input class="subject textbox 3" name="subject" type="text"
                                          value="<?php echo $thread[0]['subject'] ?>"></span>
        </div>
        <br><br><br>

        <div>
            <span class="col-md-2"><b>Message:</b></span>
            <?php echo "<span class='left col-md-9'><div class= 'editor'>
        <textarea id='message' name='message'  style='width: 100%;' rows='20' cols='100'>";
            echo $thread[0]['message'];
            echo "</textarea></div></span><br>"; ?>
        </div>
        <br class=form><br class=form>


            <?php if ($threadCreatedUserData['rights'] > 10)
            {
            ?>
        <div>
            <span class="col-md-2"><b>Post Type:</b></span>
        <span class="col-md-10" id="post-type">
            <input type="radio" name="post-type" class="post-type"
                   value="0"<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_ZERO);?>>Regular<br>
            <input type="radio" name="post-type" class="post-type"
                   value="1"<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_ONE);?> >Displayed at top of list<br>
            <input type="radio" name="post-type" class="post-type"
                   value="2"<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_TWO);?>>Displayed at top and locked (no replies)<br>
            <input type="radio" name="post-type" class="post-type"
                   value="3"<?php AssessmentUtility::writeHtmlChecked($thread[0]['postType'], AppConstant::NUMERIC_THREE);?>>Displayed at top and students can only see their own replies <br>
            </span>
        </div>
        <br class=form> <br class=form>

        <div>
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
            <span class="col-md-2"><b>Always Replies:</b></span>
        <span class="col-md-10" id="reply-by">
            <input type="radio" name="always-replies"
                   value="1"<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], 1);?> >Use default<br>
            <input type="radio" name="always-replies"
                   value="0"<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::NUMERIC_ZERO);?> >Always<br>
            <input type="radio" name="always-replies"
                   value="2000000000"<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::ALWAYS_TIME);?>>Never<br>
            <input type="radio" name="always-replies" class="end pull-left " id="always"
                   value="3"<?php AssessmentUtility::writeHtmlChecked($thread[0]['replyBy'], AppConstant::NUMERIC_THREE);?>><label
                class="end pull-left ">Before</label>

                <div class="col-md-3" id="datepicker-id">
                    <?php
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
                    echo '<label class="end pull-left  select-text-margin"> At</label>';
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
                </div>
            </span>
        </div>
        <br class=form> <br class=form>
        <?php }
        if ($currentUser['rights'] == 10 && ($forumData['settings'] & 1 == 1)){
        ?>
        <span class="col-md-2"><b>Post Anonymously:</b></span>
        <span class="col-md-8"><input id="post-anonymously" name="post-anonymously"
                                      value="1"<?php if ($thread[0]['isANon'] == 1) {
                echo "checked=1";
            } ?> type="checkbox"></span>
<br class=form>
<?php } ?>
<div>
    <div class="col-lg-offset-2 col-md-8">
        <input class="btn btn-primary" type="submit" id="save-changes" value="Save changes">
    </div>
</div>
</form>
</div>