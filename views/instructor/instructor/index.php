<?php
use app\components\AppUtility;
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
            <button class="btn btn-primary pull-right page-settings"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png">&nbsp;Course Settings</button>
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
    </div>
    <div class="clear-both"></div>
    <div class=" row add-item">
        <div class="col-md-1 plus-icon">
            <i class="fa fa-plus fa-2x"></i>
        </div>
        <div class=" col-md-2 add-item-text">
            <p><?php AppUtility::t('Add An Item...');?></p>
        </div>

    </div>
    <input type="hidden" class="home-path" value="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id) ?>">
    <input type="hidden" class="block-check" value="<?php echo $tb = 't'; ?>">
    <div class="display-item-details">
        <br>
        <br>
        <br>
        <br>
        <div style="padding-left: 20px"><h2>Coming Soon</h2></div>
        <br>
        <br>
        <br>
        <br>
        <br>
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
            if (node.className == 'blockitems')
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
        if (node.className == 'blockitems')
        {
            node.className = 'hidden';
            img.src = '../../img/expand.gif'
        }
        else
        {
            node.className = 'blockitems';
            img.src = '../../img/collapse.gif'
        }
    }
</script>