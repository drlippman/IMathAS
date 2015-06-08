<?php
use app\components\AppUtility;
?>
<div id="homefullwidth">
    <div class="block">
        <h3>Courses you're taking</h3>
    </div>
    <div class="blockitems">
        <ul class="nomark courselist">
            <?php
            foreach ($students as $student) {
                if ($student) {
                    ?>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $student->courseid) ?>"><?php echo isset($student->course['name']) ? ucfirst($student->course['name']) : ""; ?></a>
                    </li>
                <?php
                }
            }
            ?>
        </ul>
        <div class="center">
            <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('student', 'student/student-enroll-course') ?>">Enroll in a New Class</a><br>
            <a  id="unhidelink" class="course-taking small" href="work-in-progress">Unhide hidden courses</a>
        </div>
    </div>
</div>