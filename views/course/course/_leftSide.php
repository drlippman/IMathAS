<?php
use app\components\AppUtility;

?>

<p>
    <a href=""> Messages</a><br>
    <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"> Forums</a><br>
    <a href="">Calendar</a><br>
    <a href="">Log Out</a><br>
    <a href="">Help Using IMathAS</a>
</p>
