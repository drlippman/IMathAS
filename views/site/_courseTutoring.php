<?php
use app\components\AppUtility;
?>
<div id="homefullwidth">
    <div class="block">
        <h3>Courses you're tutoring</h3>
    </div>
    <div class="blockitems">
        <ul class="nomark courselist">
            <?php
            foreach ($tutors as $tutor) {
                if ($tutor) {
                    if(($tutor->course->available & 2) == 0){
                        ?>
                        <li>
                            <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid='. $tutor->course->id) ?>"><?php echo isset($tutor->course->name) ? ucfirst($tutor->course->name) : ""; ?></a>
                        </li>
                    <?php
                    }
                }
            }
            ?>
        </ul>
    </div>
</div>