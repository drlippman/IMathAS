<?php
use app\components\AppUtility;

?>

<p>

    <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id); ?>"> Messages</a>
    <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid=' . $course->id) . '&newmsg=1' ?>" class="msg-notification">
        <?php
        if($messageList)
            echo "New (".count($messageList).")";
        ?></a>
    <br>
    <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"> Forums</a><br>
    <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Calendar</a><br>
    <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Help Using IMathAS</a>
</p>
