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
        foreach ($teachCourse as $teacher) {
            if ($teacher) {
                if(($teacher->available & 2) == 0){
                ?>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid='. $teacher['id'].'&folder=0') ?>"><?php echo isset($teacher['name']) ? ucfirst($teacher['name']) : ""; ?></a>
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
