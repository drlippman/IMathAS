
<div class="item-detail-header">
    <?php echo $this->render("header/_index",['link_title'=>'Home', 'link_url' => 'http://localhost/openmath/web/site/dashboard', 'page_title' => $this->title]); ?>
</div>
<div class ='calendar'>
    <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
    <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
</div>