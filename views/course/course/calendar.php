<?php
use app\components\AppUtility;

$this->title = 'Calendar';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="item-detail-header">
    <?php echo $this->render("header/_index",['item_name'=>'Course Setting', 'link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'site/index'], 'page_title' => $this->title]); ?>
</div>
<div class ='calendar'>
    <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
    <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
</div>