<?php
use app\components\AppUtility;
$this->title = 'Unhide Courses';
?>
<div class="item-detail-header">
    <?php echo $this->render("../itemHeader/_indexWithLeftContent",['link_title'=>['Home'], 'link_url' => [AppUtility::getHomeURL().'site/index']]); ?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="padding-one-em tab-content shadowBox"">
<div class="padding-one-em text-gray-background">
<h2 class="margin-top-zero">Return Hidden Courses to Course List</h2>
<ul class="course-list">
    <?php if($courseDetails){foreach($courseDetails as $singleCourse){ ?>
        <li><?php echo ucfirst($singleCourse->name) ?> <a href="<?php echo AppUtility::getURLFromHome('site', 'unhide-from-course-list?cid='.$singleCourse->id) ?>">Unhide</a></li>
    <?php }
    }else{
        echo "<li>No courses to unhide.</li>";
    } ?>

</ul>

<a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('site', 'dashboard') ?>">Back to Home Page</a>
</div>
</div>