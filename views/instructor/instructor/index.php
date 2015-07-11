<?php
use app\components\AppUtility;

$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
?>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print'/>
<!--<div class="mainbody">-->

<div class="item-detail-header">
    <?php echo $this->render("header/_index",['item_name'=>'Course Setting', 'link_title'=>'Home', 'link_url' => '/', 'page_title' => $this->title]); ?>
</div>

<div class="item-detail-content">
    <?php echo $this->render("_toolbarTeacher");?>
</div>