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
        foreach ($teachCourse as $teacher) {
            if ($teacher) {
                if(($teacher->available & 2) == 0){
                ?>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid='. $teacher['id'].'&folder=0') ?>"><?php echo isset($teacher['name']) ? ucfirst($teacher['name']) : ""; ?></a>
                        <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='. $teacher['id']) ?>" class="msg-notification">
                            <?php
                            if($msgRecord){
                                foreach($msgRecord as $record){
                                    if($teacher->id == $record['courseid']){
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
    </div>
</div>
