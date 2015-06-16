<?php
use app\components\AppUtility;
$isCourseHidden = false;
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
                    if(!$student->hidefromcourselist){
                        ?>
                        <li>
                            <span class="delx" onclick="return hidefromcourselist(this, $student->courseid);" title="Hide from course list">x</span>
                            <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $student->courseid) ?>"><?php echo isset($student->course['name']) ? ucfirst($student->course['name']) : ""; ?></a>
                        </li>
                    <?php
                    }
                    elseif($student->hidefromcourselist)
                    {
                        $isCourseHidden = true;
                    }
                }
            }
            ?>
        </ul>
        <div class="center">
            <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('student', 'student/student-enroll-course') ?>">Enroll in a New Class</a><br>
            <a  id="unhidelink" class="course-taking small" href="work-in-progress">Unhide hidden courses</a>
            <?php
            if($isCourseHidden){
            ?>
                <input type="hidden" class="hidden-course" value="<?php echo $isCourseHidden ?>">
            <?php
            }
            ?>
        </div>
    </div>
</div>