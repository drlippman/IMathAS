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
            if(count($teachers ) == 0 && $myRights >39)
            {
             AppUtility::t("To add a course, head to the Admin Page");
            }else{
            foreach ($teachers as $teacher) {
                if ($teacher) {
                    ?>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $teacher->courseid) ?>"><?php echo isset($teacher->course->name) ? ucfirst($teacher->course->name) : ''; ?></a>
                        <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='. $teacher->course->id) ?>" class="msg-notification">
                            <?php
                                if($msgRecord){
                                    foreach($msgRecord as $record){
                                        if($teacher->courseid == $record['courseid']){
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
            }
            ?>
        </ul>
        <div class="center">
            <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('admin', 'admin/index') ?>">Admin
                Page</a>
        </div>
    </div>
</div>
