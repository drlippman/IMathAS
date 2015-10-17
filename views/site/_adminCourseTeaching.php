<?php
use app\components\AppUtility;


$twoColumn = (count($pagelayout[1])>0 && count($pagelayout[2])>0);
for ($i=0; $i<3; $i++) {
    if ($i==0) {
        echo '<div id="homefullwidth">';
    }
    if ($twocolumn) {
        if ($i==1) {
            echo '<div id="leftcolumn">';
        } else if ($i==2) {
            echo '<div id="rightcolumn">';
        }
    }
    for ($j=0; $j<count($pagelayout[$i]); $j++) {
        switch ($pagelayout[$i][$j]) {
            case 10:
                AppUtility::printMessagesGadget($page_newmessagelist, $page_coursenames);
                break;
            case 11:
                AppUtility::printPostsGadget($page_newpostlist, $page_coursenames, $postthreads);
                break;
        }
    }
    if ($i==2 || $twocolumn) {
        echo '</div>';
    }
}
?>
<div id="homefullwidth">
    <div class="block">
        <h3>Courses you're teaching</h3>
    </div>
    <div class="blockitems">
        <ul class="nomark courselist">
            <?php
            if(count($teachers) == 0 && $myRights >39)
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
                                    <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $course['id']) ?>"><?php echo ucfirst($course['name'])?></a><?php echo '<span style="color:green;">', _(' Lockdown'), '</span> '?>
                                <?php }else{?>
                                    <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $course['id']) ?>"><?php echo " "?></a><?php echo '<span style="color:green;, Lockdown">'?>
                                <?php }
                            }else{?>
                            <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $course['id']) ?>"><?php echo ucfirst($course['name']) ?>
                                <?php }

                                ?>
                                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='. $course['id']) ?>" class="msg-notification">
<!--                                    --><?php
//                                    if($msgRecord){
//                                        foreach($msgRecord as $record){
//                                            if($course->id == $record['courseid']){
//                                                if($record['msgCount'] != 0){
//                                                    echo "Messages (".$record['msgCount'].")" ;
//                                                }
//                                            }
//                                        }
//                                    }
//                                    ?>
<!--                                </a>-->
                              <?php  if ($showNewMsgNote && isset($newMsgCnt[$course['id']]) && $newMsgCnt[$course['id']] > 0) {
                                echo ' <a class="newnote" href="msgs/msglist.php?cid='.$course['id'].'">', sprintf(_('Messages (%d)'), $newMsgCnt[$course['id']]), '</a>';
                                } ?>
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
