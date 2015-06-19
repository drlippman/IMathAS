<?php
use app\components\AppUtility;
$this->title = 'Unhide Courses';
$this->params['breadcrumbs'][] = $this->title;
?>
<h2>Return Hidden Courses to Course List</h2>
<div>
<ul class="course-list">
    <?php if($courseDetails){foreach($courseDetails as $singleCourse){ ?>
        <li><?php echo ucfirst($singleCourse->name) ?> <a href="<?php echo AppUtility::getURLFromHome('site', 'unhide-from-course-list?cid='.$singleCourse->id) ?>">Unhide</a></li>
    <?php }
    }else{
        echo "<li>No courses to unhide.</li>";
    } ?>

</ul>
</div>
<a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('site', 'dashboard') ?>">Back to Home Page</a>