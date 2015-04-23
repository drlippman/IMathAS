<?php
?>
<div id="homefullwidth">
    <div class="block">
        <h3>Courses you're taking</h3>
    </div>
    <div class="blockitems">
        <ul class="nomark courselist">
            <?php
                foreach($students as $student)
                {?>
                    <li><a><?php echo $student->course->name ?></a></li>
                <?php }
            ?>
        </ul>
        <div class="center">
            <a class="btn btn-primary" href="<?php echo Yii::$app->homeUrl?>student/student/student-enroll-course">Enroll in a New
                Class</a><br>
            <a id="unhidelink" style="display:none" class="small" href="work-in-progress">Unhide hidden
                courses</a>
        </div>
    </div>
</div>