<?php
use app\components\AppUtility;

$this->title = 'Calendar';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['item_name'=>'Course Setting', 'link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'site/index'], 'page_title' => $this->title]); ?>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'calendar']);?>

    <div class="tab-content col-lg-12">
        <div class="col-lg-12">
            <div class ='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>
                <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
            </div>
            <div class="calendar-day-details pull-left col-lg-3">
                <?php echo 'Day details';?>
            </div>
        </div>
    </div>
</div>