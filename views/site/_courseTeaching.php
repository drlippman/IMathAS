<?php
use app\components\AppUtility;
?>

<div id="homefullwidth">
    <div class="block">
        <h3>Courses you're teaching</h3>
    </div>
    <ul class="nomark courselist">
    <div class="blockitems">
        <?php
        foreach ($teachers as $teacher) {
            if ($teacher) {
                ?>
                <li>
                    <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid='. $teacher->course->id) ?>"><?php echo isset($teacher->course->name) ? ucfirst($teacher->course->name) : ""; ?></a>
                </li>
            <?php
            }
        }
        ?>
    </ul>
    </div>
</div>
