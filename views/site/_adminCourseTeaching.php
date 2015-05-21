<?php
use app\components\AppUtility;

?>
<div id="homefullwidth">
    <div class="block">
        <h3>Courses you're teaching</h3>
    </div>
    <div class="blockitems">
        <ul class="nomark courselist">
            <?php
            foreach ($teachers as $teacher) {
                if ($teacher) {
                    ?>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $teacher->courseid) ?>"><?php echo isset($teacher->course->name) ? ucfirst($teacher->course->name) : ''; ?></a>
                    </li>
                <?php
                }
            }
            ?>
        </ul>
        <div class="center">
            <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/index') ?>">Admin
                Page</a>
        </div>
    </div>
</div>
