<?php
use app\components\AppUtility;

echo $this->render('_toolbar',['course'=> $course]);
?>
<!-- Name of selected linked text-->
<div class="linked-text">
        <h3><b><?php echo $links->title ?></b></h3>
        <div class="col-lg-3">
            <h5><?php echo $links->text ?></h5>
        </div>
</div>
<div class="col-lg-12 align-linked-text-right">
    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $links->courseid) ?>"> Return to course page </a>
</div>
