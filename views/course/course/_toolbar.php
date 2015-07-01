<?php
use app\components\AppUtility;
?>
<div class=mainbody>
    <div class="headerwrapper">
        <div id="navlistcont">
            <ul id="navlist">
                <li><a class="activetab" href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid='.$course->id); ?>">Course</a></li>
                <li><a class="ahref-align" href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id); ?>">Messages</a></li>
                <li><a class="ahref-align" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/search-forum?cid='.$course->id); ?>">Forums</a></li>
                <li><a class="ahref-align" href="<?php echo AppUtility::getURLFromHome('course', 'course/calendar?cid=' .$course->id) ?>">Calendar</a></li>
                <li><a class="ahref-align" href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Gradebook</a></li>
            </ul>
            <br class="clear"/>
        </div>
    </div>
</div>