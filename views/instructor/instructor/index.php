<?php
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;

$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
$imasRoot = AppUtility::getURLFromHome('instructor', 'instructor/save-quick-reorder?cid='.$course->id);

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
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=modify&cid='.$course->id); ?>"
               class="btn btn-primary pull-right page-settings"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png">&nbsp;Course Setting
            </a>
            <div class="tile_div">
                <a href="#">Instructor</a>
                <a href="#">Student</a>
                <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/index?cid='.$course->id. '&quickview=on');?>" class="last">Quick Rearrange</a>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("_toolbarTeacher", ['course' => $course, 'section' => 'course']);?>
</div>

<div class="tab-content shadowBox">
    <?php if (isset($isTeacher) && $quickview == 'on') {
        if ($useviewbuttons) {
            echo '<br class="clear"/>';
        }
        echo '<div class="cpmid">';
        if (!$useviewbuttons) {
            echo _('Quick View.'), " <a href=\"#\">", _('Back to regular view'), "</a>. ";
        }
        if (isset($CFG['CPS']['miniicons'])) {
            echo _('Use icons to drag-and-drop order.'),' ',_('Click the icon next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';
        } else {
            echo _('Use colored boxes to drag-and-drop order.'),' ',_('Click the B next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';
        }
        echo '<span id="submitnotice" style="color:red;"></span>';
        echo '<div class="clear"></div>';
        echo '</div>';

    } else {?>
    <div class="row course-copy-export">
        <div class="col-md-2 course-top-menu">
            <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id);?>"><?php AppUtility::t('Copy Items');?></a>
        </div>
        <div class="col-md-2 course-top-menu">
            <a href="#"><?php AppUtility::t('Export');?></a>
        </div>

        <ul class="nav roster-menu-bar-nav sub-menu col-md-2">
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><?php AppUtility::t('Mass Change'); ?>
                    <span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu selected-options mass-changes">
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
    <?php } ?>
    <input type="hidden" class="home-path" value="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id) ?>">
    <input type="hidden" class="block-check" value="<?php echo $tb = 't'; ?>">
    <div class="display-item-details" style="padding-top: 20px">
        <?php
        $parent = AppConstant::NUMERIC_ZERO;
        $cnt = AppConstant::NUMERIC_ZERO;
        $countCourseDetails = count($courseDetail);
        if ($quickview == 'on' && isset($isTeacher)) {
            echo '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;}</style>';
            echo "<script>var AHAHsaveurl = '$imasRoot';";
            echo 'var unsavedmsg = "'._("You have unrecorded changes.  Are you sure you want to abandon your changes?").'";';
            echo "</script>";
            echo '<p><button type="button" onclick="quickviewexpandAll()">'._("Expand All").'</button> ';
            echo '<button type="button" onclick="quickviewcollapseAll()">'._("Collapse All").'</button></p>';

            echo '<ul id=qviewtree class=qview>';
            $quickViewItem = unserialize($course['itemorder']);
                $quickViewFun = new AppUtility();
                $quickViewFun->quickview($quickViewItem,$courseDetail,0);
                echo '</ul>';
                echo '<p>&nbsp;</p>';
        }else{
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
                        <?php  $cnt++;
                       ?>
                        <?php CourseItemsUtility::AddAssessment($assessment,$item,$course,$currentTime,$parent,$canEdit,$viewAll,$hasStats);?>
                        <input type="hidden" class="assessment-link" value="<?php echo $assessment->id?>">
                        <?php break; ?>
                        <!-- ///////////////////////////// Forum here /////////////////////// -->,
                    <?php case 'Forum': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddForum($item,$course,$currentTime,$parent, $hasStats); ?>
                    <?php break; ?>
                    <!-- ////////////////// Wiki here //////////////////-->
                <?php case 'Wiki': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddWiki($item,$course,$parent, $currentTime,$hasStats); ?>
                    <?php break; ?>
                    <!-- ////////////////// Linked text here //////////////////-->
                <?php
                    case 'LinkedText': ?>
                        <?php  $cnt++; ?>
                        <?php CourseItemsUtility::AddLink($item,$currentTime,$parent,$course,$hasStats);?>
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
                    $displayBlock->DisplayWholeBlock($item,$currentTime,$assessment,$course,$parent,$cnt,$canEdit,$viewAll,$hasStats);
                    ?>
                    <?php break; ?>
                <?php endswitch;
                ?>

            <?php }?>

        <?php }
        }?>
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