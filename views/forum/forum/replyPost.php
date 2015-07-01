<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'Reply';
if ($currentUser->rights > AppConstant::STUDENT_RIGHT)
{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
} else
{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid=' . $course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid=' . $course->id . '&forumid=' . $forumId]];
$this->params['breadcrumbs'][] = ['label' => 'post', 'url' => ['/forum/forum/post?courseid=' . $course->id . '&forumid=' . $forumId . '&threadid=' . $threadId]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="replyPost">
    <input type="hidden" class="forum-id" value="<?php echo $forumId ?>">
    <input type="hidden" class="course-id" value="<?php echo $course->id ?>">
    <input type="hidden" class="thread-id" value="<?php echo $threadId ?>">
    <input type="hidden" class="parent-id" value="<?php echo $parentId ?>">
    <h2><b>Post Reply</h2>
    <br><br>
    <div>
        <div class="col-md-1"><b>Subject</b></div>
        <div class="col-md-8"><input class="text-box subject" type="text" value="Re: <?php echo $reply[0]['subject'] ?>">
        </div>
    </div>
    <br><br><br>
    <div>
        <div class="col-md-1"><b>Message</b></div>
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea id='post-reply' name='post-reply' style='width: 100%;' rows='20' cols='200'></textarea></div></div><br>"; ?>
    </div>
    <div class="col-lg-offset-1 col-md-8">
        <br>
        <input type="submit" class="btn btn-primary" id="reply-btn" value="Post Reply">
    </div>
</div>
