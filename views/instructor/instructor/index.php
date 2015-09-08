<?php
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;

$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<link href='<?php echo AppUtility::getHomeURL(); ?>css/course/course.css?<?php echo time(); ?>' rel='stylesheet' type='text/css'>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print'/>

<input type="hidden" class="calender-course-id" id="courseIdentity" value="<?php echo $course->id ?>">
<input type="hidden" class="courseId" value="<?php echo $course->id?>">

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn">
            <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course-setting?cid='.$course->id); ?>"
               class="btn btn-primary pull-right page-settings"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png">&nbsp;Course Setting</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("_toolbarTeacher", ['course' => $course, 'section' => 'course']);?>
</div>

<div class="tab-content shadowBox">
    <div class="row course-copy-export">
        <div class="col-md-1 course-top-menu">
            <a href="#"><?php AppUtility::t('Copy All');?></a>
        </div>
        <div class="col-md-2 course-top-menu">
            <a href="#"><?php AppUtility::t('Export Course');?></a>
        </div>
        <div class="col-md-2 course-top-menu">
            <a href="<?php echo AppUtility::getURLFromHome('outcomes','outcomes/add-outcomes?cid='.$course->id);?>"><?php AppUtility::t('outcomes');?></a>
        </div>
        <div class="col-md-2 course-top-menu">
            <a href="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id);?>"><?php AppUtility::t('Groups');?></a>
        </div>
        <div class="col-md-2 course-top-menu">
            <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id);?>"><?php AppUtility::t('CopyCourse');?></a>
        </div>
        <div class="col-md-2 course-top-menu">
            <a href="<?php echo AppUtility::getURLFromHome('course','course/index?cid='.$course->id. '&stuview=0');?>"><?php AppUtility::t('Student view');?></a>
        </div>
        <ul class="nav nav-tabs  roster-menu-bar-nav sub-menu col-md-2 pull-right">
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><?php AppUtility::t('Mass Change'); ?>
                    <span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu selected-options">
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/change-assessment?cid=' . $course->id)?>"><?php AppUtility::t('Assessments'); ?></a>
                    </li>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/change-forum?cid=' . $course->id)?>"><?php AppUtility::t('Forums'); ?></a>
                    </li>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('block', 'block/change-block?cid=' . $course->id)?>"><?php AppUtility::t('Blocks'); ?></a>
                    </li>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/mass-change-dates?cid=' . $course->id)?>"><?php AppUtility::t('Dates'); ?></a>
                    </li>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/time-shift?cid=' . $course->id)?>"><?php AppUtility::t('Time Shifts'); ?></a>
                    </li>

                </ul>
            </li>
        </ul>
</div>
    <div class="clear-both"></div>
    <div class="row add-item">
        <div class="col-md-1 plus-icon">
            <img class="add-item-icon" src="<?php echo AppUtility::getAssetURL()?>img/addItem.png">
        </div>
        <div class="col-md-2 add-item-text">
            <p><?php AppUtility::t('Add An Item...');?></p>
        </div>
    </div>
    <input type="hidden" class="home-path" value="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id) ?>">
    <input type="hidden" class="block-check" value="<?php echo $tb = 't'; ?>">
    <div class="display-item-details" style="padding-top: 20px">
        <?php
        $parent = AppConstant::NUMERIC_ZERO;
        $cnt = AppConstant::NUMERIC_ZERO;
        $countCourseDetails = count($courseDetail);
        if ($countCourseDetails){
            $assessment = $blockList = array();
            for ($i=0;$i<$countCourseDetails;$i++) {
                if ($courseDetail[$i]['Block']) { //if is a block
                    $blockList[] = $i+1;
                }
            }
            foreach ($courseDetail as $key => $item)
            {
                echo AssessmentUtility::createItemOrder($key, $countCourseDetails, $parent, $blockList);
                switch (key($item)):
                    case 'Assessment': ?>
                        <?php  $cnt++; ?>
                        <?php CourseItemsUtility::AddAssessment($assessment,$item,$course,$currentTime,$parent,$canEdit,$viewAll);?>
                        <input type="hidden" class="assessment-link" value="<?php echo $assessment->id?>">
                        <?php break; ?>
                        <!-- ///////////////////////////// Forum here /////////////////////// -->,
                    <?php case 'Forum': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddForum($item,$course,$currentTime,$parent); ?>
                    <?php break; ?>
                    <!-- ////////////////// Wiki here //////////////////-->
                <?php case 'Wiki': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddWiki($item,$course,$parent, $currentTime); ?>
                    <?php break; ?>
                    <!-- ////////////////// Linked text here //////////////////-->
                <?php
                    case 'LinkedText': ?>
                        <?php  $cnt++; ?>
                        <?php CourseItemsUtility::AddLink($item,$currentTime,$parent,$course);?>
                        <?php break; ?>
                        <!-- ////////////////// Inline text here //////////////////-->
                    <?php case 'InlineText': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddInlineText($item,$currentTime,$course,$parent);?>
                    <?php break; ?>
                    <!-- Calender Here-->
                <?php case 'Calendar': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddCalendar($item,$parent,$course);?>
                    <?php break; ?>
                    <!--  Block here-->
                <?php case  'Block': ?>
                    <?php  $cnt++; ?>
                    <?php $displayBlock = new CourseItemsUtility();
                    $displayBlock->DisplayWholeBlock($item,$currentTime,$assessment,$course,$parent,$cnt,$canEdit,$viewAll);
                    ?>
                    <?php break; ?>
                <?php endswitch;
                ?>

            <?php }?>

        <?php } ?>
    </div>
</div>

<script>
    $(document).ready(function ()
    {
        var SH = $('#SH').val();
        var id = $('#id').val();
        var isHidden = $('#isHidden').val();
        if(SH == 'HC')
        {
            var node = document.getElementById('block5' + id);
            var img = document.getElementById('img' + id);
            if (node.className == 'blockitems block-alignment')
            {
                node.className = 'hidden';
                img.src = '../../img/expand.gif'
            }
        }
    });
    function xyz(e,id)
    {
        var node = document.getElementById('block5' + id);
        var img = document.getElementById('img' + id);
        if (node.className == 'blockitems block-alignment')
        {
            node.className = 'hidden';
            img.src = '../../img/expand.gif'
        }
        else
        {
            node.className = 'blockitems block-alignment';
            img.src = '../../img/collapse.gif'
        }
    }
</script>