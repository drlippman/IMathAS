<?php
$this->title = 'Reply';
$this->params['breadcrumbs'][] = $this->title;
use app\components\AppUtility;
?>
<div class="replypost">
    <input type="hidden" class="forumid" value="<?php echo $forumid ?>">
    <input type="hidden" class="courseid" value="<?php echo $courseid ?>">
    <input type="hidden" class="threadid" value="<?php echo $threadid ?>">
    <h2><b>Post Reply</h2>
    <br><br>
    <div>
        <div class="col-md-1"><b>Subject</b></div>
        <div class="col-md-8"><input class="textbox subject" type="text" value="Re:<?php echo $reply[0]['subject'] ?>">
        </div>
    </div>
    <br><br><br>
    <div>
        <div class="col-md-1"><b>Message</b></div>
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea id='postreply' name='post-reply' style='width: 100%;' rows='20' cols='200'></textarea></div></div><br>"; ?>
    </div>
    <div class="col-lg-offset-1 col-md-8">
        <br>
        <a class="btn btn-primary" id="reply-btn">Post Reply</a>
    </div>
</div>
