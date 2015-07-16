<?php
use app\components\AppUtility;

$this->title = 'Calendar';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="item-detail-header">
    <?php echo $this->render("header/_index",['item_name'=>'Course Setting', 'link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'site/index'], 'page_title' => $this->title]); ?>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'calendar']);?>
</div>

<div class="tab-content">
    <br><br><br><br><br><br><br><br><br><br>
    <div class ='calendar col-lg-9'>
        <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
        <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
    </div>
    <div class="col-lg-3 right-float calendar-details">
        <?php echo 'Tudip';?>
    </div>
</div>