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
                        if(($student->course['available'] & 1) == 0){
                        ?>
                            <li>
                                <span class="delx" onclick="return hidefromcourselist(<?php echo $student->courseid ?>,this);" title="Hide from course list">x</span>
                                <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $student->courseid .'&isCalendar=false') ?>"><?php echo isset($student->course['name']) ? ucfirst($student->course['name']) : ""; ?></a>
                                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='. $student->courseid) ?>" class="msg-notification">
                                    <?php
                                    if($msgRecord){
                                        foreach($msgRecord as $record){
                                            if($student->courseid == $record['courseid']){
                                                if($record['msgCount'] != 0){
                                                    echo "Messages (".$record['msgCount'].")" ;
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </a>
                            </li>
                    <?php
                        }
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
            <a  id="unhidelink" class="course-taking small" href="<?php echo AppUtility::getURLFromHome('site', 'unhide-from-course-list') ?>">Unhide hidden courses</a>
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