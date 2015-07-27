<?php
use app\components\AppUtility;
?>



<p>Communication</p>
<a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id); ?>"> Messages</a>
<a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid=' . $course->id) . '&newmsg=1' ?>" class="msg-notification">
    <?php
    if($messageList)
    echo "New (".count($messageList).")";
?></a>
<br>
<a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>"> Forums</a><br>

<p>Tools</p>
<a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>"> Roster</a><br>
<a href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$course->id); ?>">Gradebook</a><br>
<a href="#">Groups</a><br>
<a href="#">Outcomes</a><br>
<a href="#">Calendar</a><br>

<p>Questions</p>
<a href="#">Manage</a><br>
<a href="#">Libraries</a><br>

<p>Course Items</p>
<a href="#">Copy</a><br>
<a href="#">Export</a><br>
<a href="#">Import</a><br>

<p>Mass Change</p>
<a href="#">Assessments</a><br>
<a href="#">Forums</a><br>
<a href="#">Blocks</a><br>
<a href="#">Dates</a><br>
<a href="#">Time Shift</a><br><br>
<a href="#">Course Settings</a><br>
<a href="#">Help</a><br>
<a href="#">Log Out</a>



