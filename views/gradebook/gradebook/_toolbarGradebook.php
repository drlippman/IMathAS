<?php
use app\components\AppUtility;
?>
<div class="roster-nav-tab">
    <ul class="nav nav-tabs nav-justified sub-menu-bar-nav">
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php AppUtility::t('Offline Grades');?><span class="caret"></span></a>
            <ul class="dropdown-menu full-width">
                <li><a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-grades?cid=' . $course->id); ?>"><?php AppUtility::t('Add Offline Grades');?></a></li>
                <li><a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/manage-offline-grades?cid=' . $course->id); ?>"><?php AppUtility::t('Manage Offline Grades');?></a></li>
            </ul>
        </li>
        <li><a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/grade-book-student-detail?cid=' . $course->id.'&studentId=-1'); ?>"><?php AppUtility::t('Averages');?></a></li>
        <li><a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid=' . $course->id . "&stu=0"); ?>"><?php AppUtility::t('Comments');?></a></li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php AppUtility::t('Filter');?><span class="caret"></span></a>
            <ul class="dropdown-menu full-width dropdown-scroll">
                <li class="dropdown-header"><?php AppUtility::t('Availability');?></li>
                <li><a href="#"><?php AppUtility::t('All');?></a>
                <li><a href="#"><?php AppUtility::t('Past due');?></a></li>
                <li><a href="#"><?php AppUtility::t('Past and Attempted');?></a></li>
                <li><a href="#"><?php AppUtility::t('Available only');?></a></li>
                <li><a href="#"><?php AppUtility::t('Past and Available');?></a></li>
                <li class="divider"></li>
                <li class="dropdown-header"><?php AppUtility::t('Not Counted Items');?></li>
                <li><a href="#"><?php AppUtility::t('Show Student View');?></a>
                <li><a href="#"><?php AppUtility::t('Show All');?></a></li>
                <li><a href="#"><?php AppUtility::t('Hide');?></a></li>
                <li class="divider"></li>
                <li class="dropdown-header"><?php AppUtility::t('Links');?></li>
                <li><a href="#"><?php AppUtility::t('View/Edit');?></a>
                <li><a href="#"><?php AppUtility::t('Scores');?></a></li>
                <li class="divider"></li>
                <li class="dropdown-header"><?php AppUtility::t('Locked Students');?></li>
                <li><a href="#"><?php AppUtility::t('Show');?></a>
                <li><a href="#"><?php AppUtility::t('Hide');?></a></li>

            </ul>
        </li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php AppUtility::t('Options');?><span class="caret"></span></a>
            <ul class="dropdown-menu full-width">
                <li><a href="#"><?php AppUtility::t('Lock Headers');?></a>
                <li class="divider"></li>
                <li><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/new-flag?cid='.$course->id)?>"><?php AppUtility::t('New Flag');?></a>
                <li class="divider"></li>
                <li class="dropdown-header"><?php AppUtility::t('Picture');?></li>
                <li><a href="#"><?php AppUtility::t('Show');?></a>
                <li><a href="#"><?php AppUtility::t('Hide');?></a></li>
            </ul>
        </li>
    </ul>
</div>