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
if($isPost){
$this->params['breadcrumbs'][] = ['label' => 'ListPostByName', 'url' => ['/forum/forum/list-post-by-name?cid=' . $course->id . '&forumid=' . $forumId]];
}
else{
$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid=' . $course->id . '&forumid=' . $forumId]];
$this->params['breadcrumbs'][] = ['label' => 'post', 'url' => ['/forum/forum/post?courseid=' . $course->id . '&forumid=' . $forumId . '&threadid=' . $threadId]];
}
$this->params['breadcrumbs'][] = $this->title;
?>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" xmlns="http://www.w3.org/1999/html"/>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1">
<div class="headerwrapper">
    <div id="navlistcont">
        <ul id="navlist"></ul>
        <div class="clear"></div>
    </div>
</div>
<div class="replyPost">
    <input type="hidden" class="forum-id" value="<?php echo $forumId ?>">
    <input type="hidden" class="course-id" value="<?php echo $course->id ?>">
    <input type="hidden" class="thread-id" value="<?php echo $threadId ?>">
    <input type="hidden" class="parent-id" value="<?php echo $parentId ?>">
    <?php if($isPost){?>
    <input type="hidden" class="isPost" value="<?php echo $isPost ?>">
    <?php }?>
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
        <textarea id='post-reply' name='post-reply' style='width: 70%;' rows='15' cols='100'></textarea></div></div><br>"; ?>
    </div>
    <div class="col-lg-offset-1 col-md-8">
        <br>
        <input type="submit" class="btn btn-primary" id="reply-btn" value="Post Reply">
    </div>
</div>
<div  class="col-lg-12 replyTo">
    <div class=""><b>Replying To:</b></div>
    <div class="block">
    <span class="leftbtns">
    </span>
    <span class="right">
    </span>
        <b  style="font-family: "Times New Roman", Times, serif"><?php echo $reply[0]['subject']?></b>
        <h5><strong>Posted by:</strong><?php echo $reply[0]['userName']?>,<?php echo $reply[0]['postDate']?></h5>
    </div>
    <div class="blockitems" id="item1">
    <h5><?php echo $reply[0]['message']?></h5></div>
</div>