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
                foreach($teachCourse as $key => $course){
                    if ($course) {
                        ?>
                        <li>
                            <?php
                            if($course->lockaid > 0)
                            {
                                if(isset($course->name))
                                {?>
                                    <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course['id']) ?>"><?php echo ucfirst($course['name'])?></a><?php echo '<span style="color:green;">', _(' Lockdown'), '</span> '?>
                                <?php }else{?>
                                    <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course['id']) ?>"><?php echo " "?></a><?php echo '<span style="color:green;, Lockdown">'?>
                                <?php }
                            }else{?>
                            <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course['id']) ?>"><?php echo ucfirst($course['name']) ?>
                                <?php }
                                ?>
                                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='. $course['id']) ?>" class="msg-notification">
                                    <?php
                                    if($msgRecord){
                                        foreach($msgRecord as $record){
                                            if($course->id == $record['courseid']){
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
