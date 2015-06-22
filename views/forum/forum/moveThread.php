<?php
//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => [Yii::$app->session->get('referrer')]];
//$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$courseId]];
//$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid='.$courseId.'&forumid='.$forumId]];
//$this->params['breadcrumbs'][] = $this->title;
 use app\components\AppUtility;?>
<form id="myForm" method="post" action="move-thread?forumId=<?php echo $forumId ?>&courseId=<?php echo $courseId ?>&threadId=<?php echo $threadId ?>">

<input type="hidden" id="thread-id" value="<?php echo $threadId ?>" >

<div>
    <h3>OpenMath - Move Thread</h3>

    <p>What do you want to do?<br/>

        <input type="radio" checked name="movetype" value="0" onclick="select(0)"/> Move thread to different forum<br/>
        <input type="radio" name="movetype" value="1" onclick="select(1)"/> Move post to be a reply to a thread

</div>



<div id="move-forum">Move to forum:
    <div>
        <?php $currentTime = time();

        foreach ($forums as $forum) {
             ?>
            <input type="radio" id="<?php echo $forum['forumId'] ?>" name="forum-name"
                   value="<?php echo $forum['forumId'] ?>"><?php echo $forum['forumName'] ?><br>

        <?php  } ?>
    </div>
</div>


<div id="move-thread">Move to thread:
    <div>

        <?php
        foreach ($threads as $thread) { ?>
            <?php

            if ( $thread['forumiddata'] == $forumId && $thread['threadId'] != $threadId && $thread['parent'] == 0 ) { ?>
             <input type="radio" id="<?php echo $thread['threadId'] ?>" name="thread-name" value="<?php echo $thread['threadId'] ?>"><?php echo $thread['subject']?><br>
            <?php }
        } ?>
    </div>
</div>


    <input type=submit class="btn btn-primary" id="move-button" value="Move">
    <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('forum/forum', 'thread?cid='.$courseId.'&forumid='.$forumId)  ?>">Cancel</a>
</form>

