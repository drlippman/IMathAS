<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

?>
<?php AppUtility::includeCSS('_leftSide.css');?>
<div class=mainbody>
    <div class="headerwrapper">
        <div id="navlistcont">
            <ul id="navlist">
                <li><a class="activetab" href="<?php echo AppUtility::getURLFromHome('instructor/instructor', 'index?cid='.$course->id); ?>">Course</a></li>
                <li><a class="ahrefAlign" href="#">Messages</a></li>
                <li><a class="ahrefAlign" href="#">Forums</a></li>
                <li><a class="ahrefAlign" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id); ?>">Roster</a></li>
                <li><a class="ahrefAlign" href="#">Calendar</a></li>
                <li><a class="ahrefAlign" href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid='.$course->id); ?>">Gradebook</a></li>
            </ul>
            <br class="clear"/>
        </div>
    </div>
</div>