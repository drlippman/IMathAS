<?php
//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => [Yii::$app->session->get('referrer')]];
//$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$courseId]];
//$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid='.$courseId.'&forumid='.$forumId]];
//$this->params['breadcrumbs'][] = $this->title;?>
<div>
    <h2><b>Modify Post</b></h2>

    <br><br><br>
    <input type="hidden" id="thread-id" value="<?php echo $threadId ?>">
    <input type="hidden" id="forum-id" value="<?php echo $forumId ?>">
    <input type="hidden" id="course-id" value="<?php echo $courseId ?>">
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

    <div class="col-lg-offset-2 col-md-8">
        <br>
        <a class="btn btn-primary" id="save-changes">Save changes</a>
    </div>
</div>
