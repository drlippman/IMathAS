<?php
use app\components\AppUtility;
$isCourseHidden = false;

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
        <h3>Courses you're taking</h3>
    </div>
    <div class="blockitems">
        <ul class="nomark courselist">
            <?php
            foreach ($studCourse as $student) {
                if ($student) {
                    if(!$student->hidefromcourselist){
                        if(($student['available'] & 1) == 0){
                        ?>
                            <li>
                                <?php if($student->locked != 0)
                                { ?>
                                <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $student['id']) ?> " class="locked"><?php echo isset($student['name']) ? ucfirst($student['name']) : ""; ?></a>
                                <?php }
                                else
                                {?>
                                    <span class="delx" onclick="return hidefromcourselist(<?php echo $student['id'] ?>,this);" title="Hide from course list">x</span>
                                    <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $student['id']) ?>"><?php echo isset($student['name']) ? ucfirst($student['name']) : ""; ?></a>
                                    <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='. $student['id'].'&newmsg=1') ?>" class="msg-notification">
                                        <?php
                                        if($msgRecord){
                                            foreach($msgRecord as $record){
                                                if($student->id == $record['courseid']){
                                                    if($record['msgCount'] != 0){
                                                        echo "Messages (".$record['msgCount'].")" ;
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                    </a>

                                <?php }?>
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

<script>
    /**
     * Modal pop up for locked course.
     */
    $('.locked').click(function(e){
        var html = '<div><p>You have been locked out of this course by your instructor.  Please see your instructor for more information.</p></div>';
        var cancelUrl = $(this).attr('href');
        e.preventDefault();
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Ok": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });

    });
</script>