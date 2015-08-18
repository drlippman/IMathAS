<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = ucfirst($course->name);

?>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print' />
<!--Get current time-->
<?php
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<input type="hidden" class="calender-course-id" value="<?php echo $course->id?>">


<!--Course name-->
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home'], 'link_url' => [AppUtility::getHomeURL().'site/index']]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("_toolbarStudent", ['course' => $course, 'section' => 'course', 'students' => $students]);?>
</div>

<!-- ////////////////// Assessment here //////////////////-->
<div class="tab-content shadowBox student-no-second-level-nav">
    <?php if(count($courseDetail)){
        foreach($courseDetail as $key => $item){

            switch(key($item)):
                case 'Assessment': ?>

                    <?php $assessment = $item[key($item)];

                    if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) {
                        ?>
                        <div class="item student-course-alignment">
                            <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                            <div class="title">
                                <?php if($assessment->timelimit > 0) { //timelimit
                                    if($assessment->password == '') {?> <!--Set password-->
                                        <b>
                                            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a>
                                        </b>
                                        <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                        <?php if ($assessment->enddate != 2000000000) { ?>
                                            <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                            <!-- Use Late Pass here-->
                                            <?php if($students->latepass != 0) {
                                                if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                    ?>
                                                    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                <?php } ?>
                                            <?php } else {?>
                                                <!--                                --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                            <?php }?>
                                        <?php }
                                        //                        }?>
                                    <?php } else {?>
                                        <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a></b>
                                        <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                        <?php if ($assessment->enddate != 2000000000) { ?>
                                            <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                            <!-- Use Late Pass here-->
                                            <?php if($students->latepass != 0) {
                                                if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                    ?>
                                                    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                <?php } ?>
                                            <?php } else {?>
                                                <!--                                    --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                            <?php }?>
                                        <?php }?>
                                    <?php } ?>
                                <?php } else { ?>
                                    <?php if($assessment->password == '') {?>
                                        <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                        <?php if ($assessment->enddate != 2000000000) { ?>
                                            <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                            <!-- Use Late Pass here-->
                                            <?php if($students->latepass != 0) {?>
                                                <?php if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){?>
                                                    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                <?php } ?>
                                            <?php } else {?>
                                                <!--                            --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                            <?php }?>
                                        <?php }?>
                                    <?php } else {?>
                                        <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                        <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                        <?php if ($assessment->enddate != 2000000000) { ?>
                                            <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                            <!-- Use Late Pass here-->
                                            <?php if($students->latepass != 0) {
                                                if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                    ?>
                                                    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                    <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                <?php } ?>
                                            <?php } else {?>
                                                <!--                                --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                            <?php }?>
                                        <?php }?>
                                    <?php } ?>
                                <?php }?>
                            </div>
                            <div class="itemsum">
                                <p><?php echo $assessment->summary ?></p>
                            </div>
                        </div>
                    <?php } elseif ($assessment->enddate < $currentTime && ($assessment->reviewdate != 0) && ($assessment->reviewdate > $currentTime)) {?>
                        <div class="item student-course-alignment">
                            <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                            <div class="title">
                                <?php if($assessment->password == '') {?>
                                    <b>
                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="assessment-link"><?php echo $assessment->name ?></a>
                                    </b>
                                    <input type="hidden" class="confirmation-require" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                    <?php if ($assessment->reviewdate == 2000000000) { ?>
                                        <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                                        <BR>This assessment is in review mode - no scores will be saved.
                                    <?php } else { ?>
                                        <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                                        <BR>This assessment is in review mode - no scores will be saved.
                                    <?php } ?>
                                <?php } else {?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                    <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                    <?php if ($assessment->reviewdate == 2000000000) { ?>
                                        <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                                        <BR>This assessment is in review mode - no scores will be saved.
                                    <?php } else { ?>
                                        <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                                        <BR>This assessment is in review mode - no scores will be saved.
                                    <?php } ?>
                                <?php } ?>
                            </div>
                            <div class="itemsum">
                                <p><?php echo $assessment->summary ?></p>
                            </div>
                        </div>
                    <?php } ?>
                    <?php break; ?>

                    <!-- ///////////////////////////// Forum here /////////////////////// -->
                <?php case 'Forum':?>
                <?php $forum = $item[key($item)] ?>
                <?php if ($forum->avail != 0 && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
                    <?php if ($forum->avail == 1 && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
                        <div class="item student-course-alignment">
                        <img alt="text item" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                    <div class="title">
                        <b><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid=' . $forum->courseid.'&forumid='.$forum->id) ?>">
                                <?php echo $forum->name ?></a></b>

                    </div>
                    <div class="itemsum">
                        <p><p>&nbsp;<?php echo $forum->description ?></p></p>
                    </div>
                    </div>
                <?php } elseif ($forum->avail == 2) { ?>
                    <div class="item student-course-alignment">
                        <img alt="text item" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                        <div class="title">
                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $forum->courseid) ?>">
                                    <?php echo $forum->name ?></a></b>
                        </div>
                        <div class="itemsum">
                            <p><p>&nbsp;<?php echo $forum->description ?></p></p>
                        </div>
                    </div>
                <?php } ?>
                <?php break; ?>

                <!-- ////////////////// Wiki here //////////////////-->
            <?php case 'Wiki': ?>
                <?php $wikis = $item[key($item)];
                $hasNew = false;?>
                <?php if ($wikis->avail != 0 && $wikis->startdate < $currentTime && $wikis->enddate > $currentTime) { ?>
                    <?php if ($wikis->avail == 1 && $wikis->enddate > $currentTime && $wikis->startdate < $currentTime) ?>
                        <div class="item student-course-alignment">
                        <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                    <div class="title">
                        <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid .'&wikiId=' .$wikis->id) ?>">
                                <?php echo $wikis->name ?></a></b>
                        <span>New Revisions</span>
                    </div>
                    <div class="itemsum">
                        <p><p>&nbsp;<?php echo $wikis->description ?></p></p>
                    </div>
                    <div class="clear"></div>
                    </div>
                <?php } elseif ($wikis->avail == 2 && $wikis->enddate == 2000000000) { ?>
                    <div class="item student-course-alignment">
                        <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                        <div class="title">
                            <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid .'&wikiId=' .$wikis->id) ?>">
                                    <?php echo $wikis->name ?></a></b>
                            <span>New Revisions</span>
                        </div>
                        <div class="itemsum">
                            <p><p>&nbsp;<?php echo $wikis->description ?></p></p>
                        </div>
                        <div class="clear"></div>
                    </div>
                <?php } ?>
                <?php break; ?>

                <!-- ////////////////// Linked text here //////////////////-->
            <?php case 'LinkedText': ?>
                <?php $link = $item[key($item)]; ?>
                <?php if ($link->avail != 0 && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
                    <!--Link type : http-->
                    <?php $text = $link->text;?>
                    <?php if ((substr($text, 0, 4) == 'http') && (strpos(trim($text), " ") == false)) { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                            <div class="title">
                                <?php if($link->target == 1){?>
                            <b><a href="<?php echo $text ?>"target="_blank"><?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                        <?php }else {?>
                                    <b><a href="<?php echo $text ?>"><?php echo $link->title; ?></a></b>
                                <?php }?>
                            </div>
                            <div class="itemsum">
                                <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <!--Link type : file-->
                    <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                            <div class="title">
                                <?php if($link->target != 0) {?>
                                    <?php
                                   /* $filename = substr(strip_tags($link->text),5);
                                    require_once("../components/filehandler.php");
                                    $alink = getcoursefileurl($filename);
                                    echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                } else
                                {
                                   /* $filename = substr(strip_tags($link->text),5);
                                    require_once("../components/filehandler.php");
                                    $alink = getcoursefileurl($filename);
                                    echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                }?>

                            </div>
                            <div class="itemsum">
                                <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <!--Link type : external tool-->
                    <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                            <div class="title">
                                <!--open on new window or on same window-->
                                <?php if ($link->target != 0) { ?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>"target="_blank">
                                            <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                <?php }else{ ?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                            <?php echo $link->title ?></a></b>
                                <?php }?>
                            </div>
                            <div class="itemsum"><p>
                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>
                    <?php } else { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                            <div class="title">
                                <?php if($link->target != 0) {?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>" target="_blank">
                                            <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                <?php } else {?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                            <?php echo $link->title ?></a></b>
                                <?php }?>
                            </div>
                            <div class="itemsum"><p>
                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>
                    <?php } ?>
                    <!--Hide ends-->
                <?php } elseif ($link->avail == 2 && $link->enddate == 2000000000) { ?>
                    <!--Link type : http-->
                    <?php $text = $link->text;?>
                    <?php if ((substr($text, 0, 4) == 'http') && (strpos(trim($text), " ") == false)) { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                            <div class="title">
                                <?php if($link->target == 1){?>
                            <b><a href="<?php echo $text ?>"target="_blank"><?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                        <?php }else {?>
                                    <b><a href="<?php echo $text ?>"><?php echo $link->title; ?></a></b>
                                <?php }?>
                            </div>
                            <div class="itemsum">
                                <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <!--Link type : file-->
                    <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                            <div class="title">
                                <?php if($link->target != 0) {?>
                                    <?php
                                  /*  $filename = substr(strip_tags($link->text),5);
                                    require_once("../components/filehandler.php");
                                    $alink = getcoursefileurl($filename);
                                    echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                } else
                                {
                                   /* $filename = substr(strip_tags($link->text),5);
                                    require_once("../components/filehandler.php");
                                    $alink = getcoursefileurl($filename);
                                    echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                }?>
                            </div>
                            <div class="itemsum">
                                <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <!--Link type : external tool-->
                    <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                            <div class="title">
                                <!--open on new window or on same window-->
                                <?php if ($link->target != 0) { ?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>"target="_blank">
                                            <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                <?php }else{ ?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                            <?php echo $link->title ?></a></b>
                                <?php }?>
                            </div>
                            <div class="itemsum"><p>
                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>
                    <?php } else { ?>
                        <div class="item student-course-alignment">
                            <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                            <div class="title">
                                <?php if($link->target != 0) {?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>" target="_blank">
                                            <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                <?php } else {?>
                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                            <?php echo $link->title ?></a></b>
                                <?php }?>
                            </div>
                            <div class="itemsum"><p>
                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>
                    <?php } ?>
                <?php } ?> <!--Show always-->
                <?php break; ?>

                <!-- ////////////////// Inline text here //////////////////-->
            <?php case 'InlineText': ?>
                <?php $inline = $item[key($item)]; ?>
                <?php if ($inline->avail != 0 && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
                    <div class="item student-course-alignment">
                        <!--Hide title and icon-->
                        <?php if ($inline->title != '##hidden##') { ?>
                            <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                            <div class="title">
                                <b><?php echo $inline->title ?></b>
                            </div>
                        <?php } ?>
                        <div class="itemsum"><p>
                            <p><?php echo $inline->text ?></p>
                        </div>
                        <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                            <ul class="fileattachlist">
                                <li>
                                    <a href="/math/web/Uploads/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                                </li>
                            </ul>
                        <?php } ?>
                    </div>
                    <div class="clear"></div>
                <?php } elseif ($inline->avail == 2) { ?> <!--Hide ends and displays show always-->
                    <div class="item student-course-alignment">
                        <!--Hide title and icon-->
                        <?php if ($inline->title != '##hidden##') { ?>
                            <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                            <div class="title">
                                <b><?php echo $inline->title ?></b>
                            </div>
                        <?php } ?>
                        <div class="itemsum"><p>
                            <p><?php echo $inline->text ?></p>
                        </div>
                        <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                            <ul class="fileattachlist">
                                <li><a href="/math/web/Uploads/<?php echo $instrFile->filename ?>"
                                       target="_blank"><?php echo $instrFile->filename ?></a></li>
                            </ul>
                        <?php } ?>
                    </div>
                    <div class="clear"></div>
                <?php } ?>
                <?php break; ?>

                <!-- Calender Here-->
            <?php case 'Calendar':?>
                <div class="student-course-alignment student-course-alignment-calendar">
                <div class="col-lg-12 padding-alignment calendar-container item">
                    <div class ='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>
                        <input type="hidden" class="current-time" value="<?php echo $currentDate?>">
                        <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                        <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
                    </div>
                    <div class="calendar-day-details-right-side pull-left col-lg-3">
                        <div class="day-detail-border">
                            <b style="font-size: 18px">Day Details:</b>
                        </div>
                        <div class="calendar-day-details"></div>
                    </div>
                </div>
                </div>
                <?php break; ?>

                <!--  Block here-->
            <?php case 'Block': ?>
                <?php $block = $item[key($item)];
                $blockId = $block['id'];
                ?>
                <input type="hidden" id="SH" value="<?php echo $block['SH']?>" >
                <input type="hidden" id="id" value="<?php echo $block['id']?>" >
                <?php if ($block['avail'] == 1){  ?>
                    <div class= "block item student-course-alignment">
                        <?php if (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'F') { ?>
                            <span class=left>
                                <img alt="folder"  src="<?php echo AppUtility::getHomeURL() ?>img/folder2.gif">
                            </span>
                        <?php } elseif (strlen($block['SH']) > 1 && $block['SH'][1] == 'T') { ?>
                            <span class=left>
                                 <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder_tree.png">
                            </span>
                        <?php } else { ?>
                            <span class=left>
                                      <img alt="expand/collapse" style="cursor:pointer;" id="img<?php echo $block['id']?>" onclick="xyz(this,<?php echo $block['id']?>)" src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"/>
                                    </span>
                        <?php } ?>
                        <div class=title>
                            <span class="right">
                                <a href="<?php echo AppUtility::getURLFromHome('course', 'course/block-isolate?cid=' .$course->id ."&blockId=" .$blockId) ?>">Isolate</a>
                            </span>
                            <b><a href="#" onclick="return false;"><?php echo ucfirst($block['name']); ?></a></b>
                        </div>
                    </div>
                    <div class="blockitems student-block" id="block5<?php echo $block['id']?>">
                        <?php if(count($item['itemList'])) {?>
                            <?php foreach($item['itemList'] as $itemlistKey => $item) { ?>
                                <?php switch(key($item)):
                                    /*Assessment here*/
                                    case 'Assessment': ?>

                                        <?php $assessment = $item[key($item)];

                                        if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) {
                                            ?>
                                            <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                                                <div class="title">
                                                    <?php if($assessment->timelimit > 0) { //timelimit
                                                        if($assessment->password == '') {?> <!--Set password-->
                                                            <b>
                                                                <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a>
                                                            </b>
                                                            <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {
                                                                    if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                                        ?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                                --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }
                                                            //                        }?>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a></b>
                                                            <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {
                                                                    if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                                        ?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                                    --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }?>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <?php if($assessment->password == '') {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {?>
                                                                    <?php if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                            --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }?>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                                            <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {
                                                                    if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                                        ?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                                --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }?>
                                                        <?php } ?>
                                                    <?php }?>
                                                </div>
                                                <div class="itemsum">
                                                    <p><?php echo $assessment->summary ?></p>
                                                </div>
                                            </div>
                                        <?php } elseif ($assessment->enddate < $currentTime && ($assessment->reviewdate != 0) && ($assessment->reviewdate > $currentTime)) {?>
                                            <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                                                <div class="title">
                                                    <?php if($assessment->password == '') {?>
                                                        <b>
                                                            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="assessment-link"><?php echo $assessment->name ?></a>
                                                        </b>
                                                        <input type="hidden" class="confirmation-require" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                        <?php if ($assessment->reviewdate == 2000000000) { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } else { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } ?>
                                                    <?php } else {?>
                                                        <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                                        <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                        <?php if ($assessment->reviewdate == 2000000000) { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } else { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } ?>
                                                    <?php } ?>
                                                </div>
                                                <div class="itemsum">
                                                    <p><?php echo $assessment->summary ?></p>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <?php break; ?>

                                        <!-- Forum here-->
                                    <?php case 'Forum': ?>
                                        <?php $forum = $item[key($item)]; ?>
                                        <?php if ($forum->avail != 0 && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
                                            <?php if ($forum->avail == 1 && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
                                                <div class="item student-course-alignment">
                                                <img alt="text item" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                                            <div class="title">
                                                <b><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid=' . $forum->courseid.'&forumid='.$forum->id) ?>">
                                                        <?php echo $forum->name ?></a></b>
                                            </div>
                                            <div class="itemsum"><p>
                                                <p>&nbsp;<?php echo $forum->description ?></p></p>
                                            </div>
                                            </div>
                                        <?php } elseif ($forum->avail == 2) { ?>
                                            <div class="item student-course-alignment">
                                                <img alt="text item" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                                                <div class="title">
                                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $forum->courseid) ?>">
                                                            <?php echo $forum->name ?></a></b>
                                                </div>
                                                <div class="itemsum"><p>
                                                    <p>&nbsp;<?php echo $forum->description ?></p></p>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php break; ?>

                                        <!-- ////////////////// Wiki here //////////////////-->
                                    <?php case 'Wiki': ?>

                                        <?php $wikis = $item[key($item)];
                                        $hasNew = false;?>
                                        <?php if ($wikis->avail != 0 && $wikis->startdate < $currentTime && $wikis->enddate > $currentTime) { ?>
                                            <?php if ($wikis->avail == 1 && $wikis->enddate > $currentTime && $wikis->startdate < $currentTime) ?>
                                                <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                                            <div class="title">
                                                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid .'&wikiId=' .$wikis->id) ?>">
                                                        <?php echo $wikis->name ?></a></b>
                                                <span>New Revisions</span>
                                            </div>
                                            <div class="itemsum">
                                                <p><p>&nbsp;<?php echo $wikis->description ?></p></p>
                                            </div>
                                            <div class="clear"></div>
                                            </div>
                                        <?php } elseif ($wikis->avail == 2 && $wikis->enddate == 2000000000) { ?>
                                            <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                                                <div class="title">
                                                    <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid .'&wikiId=' .$wikis->id) ?>">
                                                            <?php echo $wikis->name ?></a></b>
                                                    <span>New Revisions</span>
                                                </div>
                                                <div class="itemsum">
                                                    <p><p>&nbsp;<?php echo $wikis->description ?></p></p>
                                                </div>
                                                <div class="clear"></div>
                                            </div>
                                        <?php } ?>

                                        <?php break; ?>

                                        <!-- ////////////////// Linked text here //////////////////-->
                                    <?php case 'LinkedText': ?>
                                        <?php $link = $item[key($item)]; ?>
                                        <?php if ($link->avail != 0 && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
                                            <!--Link type : http-->
                                            <?php $text = $link->text;?>
                                            <?php if ((substr($text, 0, 4) == 'http') && (strpos(trim($text), " ") == false)) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                                                    <div class="title">
                                                        <?php if($link->target == 1){?>
                                                <b><a href="<?php echo $text ?>"target="_blank"><?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                                            <?php }else {?>
                                                            <b><a href="<?php echo $text ?>"><?php echo $link->title; ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : file-->
                                            <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <?php
                                                          /*  $filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                                        } else
                                                        {
                                                           /* $filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                                        }?>

                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : external tool-->
                                            <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <!--open on new window or on same window-->
                                                        <?php if ($link->target != 0) { ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>"target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php }else{ ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>" target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } ?>
                                            <!--Hide ends-->
                                        <?php } elseif ($link->avail == 2 && $link->enddate == 2000000000) { ?>
                                            <!--Link type : http-->
                                            <?php $text = $link->text;?>
                                            <?php if ((substr($text, 0, 4) == 'http') && (strpos(trim($text), " ") == false)) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                                                    <div class="title">
                                                        <?php if($link->target == 1){?>
                                                <b><a href="<?php echo $text ?>"target="_blank"><?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                                            <?php }else {?>
                                                            <b><a href="<?php echo $text ?>"><?php echo $link->title; ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : file-->
                                            <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <?php
                                                           /* $filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>';
                                                        } else
                                                        {
                                                            $filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                                        }?>
                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : external tool-->
                                            <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <!--open on new window or on same window-->
                                                        <?php if ($link->target != 0) { ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>"target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php }else{ ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>" target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } ?>
                                        <?php } ?> <!--Show always-->
                                        <?php break; ?>

                                        <!-- ////////////////// Inline text here //////////////////-->
                                    <?php case 'InlineText':?>
                                        <?php $inline = $item[key($item)]; ?>
                                        <?php if ($inline->avail != 0 && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
                                            <div class="item student-course-alignment">
                                                <!--Hide title and icon-->
                                                <?php if ($inline->title != '##hidden##') { ?>
                                                    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                                                    <div class="title">
                                                        <b><?php echo $inline->title ?></b>
                                                    </div>
                                                <?php } ?>
                                                <div class="itemsum"><p>
                                                    <p><?php echo $inline->text ?></p>
                                                </div>
                                                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                                                    <ul class="fileattachlist">
                                                        <li>
                                                            <a href="/open-math/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                                                        </li>
                                                    </ul>
                                                <?php } ?>
                                            </div>
                                            <div class="clear"></div>
                                        <?php } elseif ($inline->avail == 2) { ?> <!--Hide ends and displays show always-->
                                            <div class="item student-course-alignment">
                                                <!--Hide title and icon-->
                                                <?php if ($inline->title != '##hidden##') { ?>
                                                    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                                                    <div class="title">
                                                        <b><?php echo $inline->title ?></b>
                                                    </div>
                                                <?php } ?>
                                                <div class="itemsum"><p>
                                                    <p><?php echo $inline->text ?></p>
                                                </div>
                                                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                                                    <ul class="fileattachlist">
                                                        <li><a href="/open-math/files/<?php echo $instrFile->filename ?>"
                                                               target="_blank"><?php echo $instrFile->filename ?></a></li>
                                                    </ul>
                                                <?php } ?>
                                            </div>
                                            <div class="clear"></div>
                                        <?php } ?>
                                        <?php break; ?>

                                        <!-- Calender Here-->
                                    <?php case 'Calendar': ?>
                                        <div class="student-course-alignment student-course-alignment-calendar">
                                        <div class="col-lg-12 padding-alignment calendar-container item" style="margin-left: 20px">
                                            <div class ='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>
                                                <input type="hidden" class="current-time" value="<?php echo $currentDate?>">
                                                <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                                                <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
                                            </div>
                                            <div class="calendar-day-details-right-side pull-left col-lg-3">
                                                <div class="day-detail-border">
                                                    <b style="font-size: 18px">Day Details:</b>
                                                </div>
                                                <div class="calendar-day-details"></div>
                                            </div>
                                        </div>
                                            </div>
                                        <?php break; ?>
                                    <?php endswitch; ?>
                            <?php }?>
                        <?php }?>
                    </div>

                    <div class="clear"></div>
                <?php } elseif(($block['avail']) == AppConstant::NUMERIC_TWO){?>
                    <!--Show Always-->
                    <div class="block item student-course-alignment">
                        <?php if (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'F') { ?>
                            <span class=left>
                                <img alt="folder"  src="<?php echo AppUtility::getHomeURL() ?>img/folder2.gif">
                            </span>
                        <?php } elseif (strlen($block['SH']) > 1 && $block['SH'][1] == 'T') { ?>
                            <span class=left>
                                 <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder_tree.png">
                            </span>
                        <?php } else { ?>
                            <span class=left>
                                      <img alt="expand/collapse" style="cursor:pointer;" id="img<?php echo $block['id']?>" onclick="xyz(this,<?php echo $block['id']?>)" src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"/>
                                    </span>
                        <?php } ?>
                        <div class=title>
                            <span class="right">
                                <a href="<?php echo AppUtility::getURLFromHome('course', 'course/block-isolate?cid=' .$course->id ."&blockId=" .$blockId) ?>">Isolate</a>
                            </span>
                                                <b><a href="#" onclick="return false;"><?php echo ucfirst($block['name']); ?></a></b>
                        </div>
                    </div>
                    <div class="blockitems student-block" id="block5<?php echo $block['id']?>">
                        <?php if(count($item['itemList'])) {?>
                            <?php foreach($item['itemList'] as $itemlistKey => $item) { ?>
                                <?php switch(key($item)):
                                    /*Assessment here*/
                                    case 'Assessment': ?>

                                        <?php $assessment = $item[key($item)];

                                        if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) {
                                            ?>
                                            <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                                                <div class="title">
                                                    <?php if($assessment->timelimit > 0) { //timelimit
                                                        if($assessment->password == '') {?> <!--Set password-->
                                                            <b>
                                                                <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a>
                                                            </b>
                                                            <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {
                                                                    if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                                        ?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                                --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }
                                                            //                        }?>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a></b>
                                                            <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {
                                                                    if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                                        ?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                                    --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }?>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <?php if($assessment->password == '') {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {?>
                                                                    <?php if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                            --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }?>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                                            <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                            <?php if ($assessment->enddate != 2000000000) { ?>
                                                                <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                                                <!-- Use Late Pass here-->
                                                                <?php if($students->latepass != 0) {
                                                                    if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){
                                                                        ?>
                                                                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                                                                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                                                                    <?php } ?>
                                                                <?php } else {?>
                                                                    <!--                                --><?php //echo "<p>You have no late passes remaining.</p>";?>
                                                                <?php }?>
                                                            <?php }?>
                                                        <?php } ?>
                                                    <?php }?>
                                                </div>
                                                <div class="itemsum">
                                                    <p><?php echo $assessment->summary ?></p>
                                                </div>
                                            </div>
                                        <?php } elseif ($assessment->enddate < $currentTime && ($assessment->reviewdate != 0) && ($assessment->reviewdate > $currentTime)) {?>
                                            <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                                                <div class="title">
                                                    <?php if($assessment->password == '') {?>
                                                        <b>
                                                            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="assessment-link"><?php echo $assessment->name ?></a>
                                                        </b>
                                                        <input type="hidden" class="confirmation-require" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                        <?php if ($assessment->reviewdate == 2000000000) { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } else { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } ?>
                                                    <?php } else {?>
                                                        <b><a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/password?id=' . $assessment->id.'&cid=' .$course->id) ?>"><?php echo $assessment->name ?></a></b>
                                                        <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                                                        <?php if ($assessment->reviewdate == 2000000000) { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } else { ?>
                                                            <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                                                            <BR>This assessment is in review mode - no scores will be saved.
                                                        <?php } ?>
                                                    <?php } ?>
                                                </div>
                                                <div class="itemsum">
                                                    <p><?php echo $assessment->summary ?></p>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php break; ?>

                                        <!-- Forum here-->

                                    <?php case 'Forum': ?>
                                        <?php $forum = $item[key($item)]; ?>
                                        <?php if ($forum->avail != 0 && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
                                            <?php if ($forum->avail == 1 && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
                                                <div class="item student-course-alignment">
                                                <img alt="text item" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                                            <div class="title">
                                                <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $forum->courseid) ?>">
                                                        <?php echo $forum->name ?></a></b>
                                            </div>
                                            <div class="itemsum"><p>

                                                <p>&nbsp;<?php echo $forum->description ?></p></p>
                                            </div>
                                            </div>
                                        <?php } elseif ($forum->avail == 2) { ?>
                                            <div class="item student-course-alignment">
                                                <img alt="text item" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>

                                                <div class="title">
                                                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $forum->courseid) ?>">
                                                            <?php echo $forum->name ?></a></b>
                                                </div>
                                                <div class="itemsum"><p>

                                                    <p>&nbsp;<?php echo $forum->description ?></p></p>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php break; ?>

                                        <!-- ////////////////// Wiki here //////////////////-->

                                    <?php case 'Wiki': ?>
                                        <?php $wikis = $item[key($item)];
                                        $hasNew = false;?>
                                        <?php if ($wikis->avail != 0 && $wikis->startdate < $currentTime && $wikis->enddate > $currentTime) { ?>
                                            <?php if ($wikis->avail == 1 && $wikis->enddate > $currentTime && $wikis->startdate < $currentTime) ?>
                                                <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                                            <div class="title">
                                                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid .'&wikiId=' .$wikis->id) ?>">
                                                        <?php echo $wikis->name ?></a></b>
                                                <span>New Revisions</span>
                                            </div>
                                            <div class="itemsum">
                                                <p><p>&nbsp;<?php echo $wikis->description ?></p></p>
                                            </div>
                                            <div class="clear"></div>
                                            </div>
                                        <?php } elseif ($wikis->avail == 2 && $wikis->enddate == 2000000000) { ?>
                                            <div class="item student-course-alignment">
                                                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                                                <div class="title">
                                                    <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid .'&wikiId=' .$wikis->id) ?>">
                                                            <?php echo $wikis->name ?></a></b>
                                                    <span>New Revisions</span>
                                                </div>
                                                <div class="itemsum">
                                                    <p><p>&nbsp;<?php echo $wikis->description ?></p></p>
                                                </div>
                                                <div class="clear"></div>
                                            </div>
                                        <?php } ?>
                                        <?php break; ?>

                                        <!-- ////////////////// Linked text here //////////////////-->
                                    <?php case 'LinkedText': ?>

                                        <?php $link = $item[key($item)]; ?>
                                        <?php if ($link->avail != 0 && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
                                            <!--Link type : http-->
                                            <?php $text = $link->text;?>
                                            <?php if ((substr($text, 0, 4) == 'http') && (strpos(trim($text), " ") == false)) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                                                    <div class="title">
                                                        <?php if($link->target == 1){?>
                                                <b><a href="<?php echo $text ?>"target="_blank"><?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                                            <?php }else {?>
                                                            <b><a href="<?php echo $text ?>"><?php echo $link->title; ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : file-->
                                            <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <?php
                                                            /*$filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                                        } else
                                                        {
                                                          /*  $filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                                        }?>

                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : external tool-->
                                            <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <!--open on new window or on same window-->
                                                        <?php if ($link->target != 0) { ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>"target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php }else{ ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>" target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } ?>
                                            <!--Hide ends-->
                                        <?php } elseif ($link->avail == 2 && $link->enddate == 2000000000) { ?>
                                            <!--Link type : http-->
                                            <?php $text = $link->text;?>
                                            <?php if ((substr($text, 0, 4) == 'http') && (strpos(trim($text), " ") == false)) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                                                    <div class="title">
                                                        <?php if($link->target == 1){?>
                                                <b><a href="<?php echo $text ?>"target="_blank"><?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                                            <?php }else {?>
                                                            <b><a href="<?php echo $text ?>"><?php echo $link->title; ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : file-->
                                            <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <?php
                                                           /* $filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                                        } else
                                                        {
                                                          /*  $filename = substr(strip_tags($link->text),5);
                                                            require_once("../components/filehandler.php");
                                                            $alink = getcoursefileurl($filename);
                                                            echo '<a href="'.$alink.'">'.$link->title.'</a>'; */
                                                        }?>
                                                    </div>
                                                    <div class="itemsum">
                                                        <p><p><?php echo $link->summary ?>&nbsp;</p></p>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <!--Link type : external tool-->
                                            <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <!--open on new window or on same window-->
                                                        <?php if ($link->target != 0) { ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>"target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php }else{ ?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="item student-course-alignment">
                                                    <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                                                    <div class="title">
                                                        <?php if($link->target != 0) {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>" target="_blank">
                                                                    <?php echo $link->title ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                                                        <?php } else {?>
                                                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid .'&id=' .$link->id) ?>">
                                                                    <?php echo $link->title ?></a></b>
                                                        <?php }?>
                                                    </div>
                                                    <div class="itemsum"><p>
                                                        <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                                                    <div class="clear"></div>
                                                </div>
                                            <?php } ?>
                                        <?php } ?> <!--Show always-->
                                        <?php break; ?>

                                        <!-- ////////////////// Inline text here //////////////////-->
                                    <?php case 'InlineText':?>

                                        <?php $inline = $item[key($item)]; ?>
                                        <?php if ($inline->avail != 0 && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
                                            <div class="item student-course-alignment">
                                                <!--Hide title and icon-->
                                                <?php if ($inline->title != '##hidden##') { ?>
                                                    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                                                    <div class="title">
                                                        <b><?php echo $inline->title ?></b>
                                                    </div>
                                                <?php } ?>
                                                <div class="itemsum"><p>
                                                    <p><?php echo $inline->text ?></p>
                                                </div>
                                                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                                                    <ul class="fileattachlist">
                                                        <li>
                                                            <a href="/open-math/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                                                        </li>
                                                    </ul>
                                                <?php } ?>
                                            </div>
                                            <div class="clear"></div>
                                        <?php } elseif ($inline->avail == 2) { ?> <!--Hide ends and displays show always-->
                                            <div class="item student-course-alignment">
                                                <!--Hide title and icon-->
                                                <?php if ($inline->title != '##hidden##') { ?>
                                                    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                                                    <div class="title">
                                                        <b><?php echo $inline->title ?></b>
                                                    </div>
                                                <?php } ?>
                                                <div class="itemsum"><p>
                                                    <p><?php echo $inline->text ?></p>
                                                </div>
                                                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                                                    <ul class="fileattachlist">
                                                        <li><a href="/open-math/files/<?php echo $instrFile->filename ?>"
                                                               target="_blank"><?php echo $instrFile->filename ?></a></li>
                                                    </ul>
                                                <?php } ?>
                                            </div>
                                            <div class="clear"></div>
                                        <?php } ?>
                                        <?php break; ?>

                                        <!-- Calender Here-->
                                    <?php case 'Calendar': ?>
                                        <div class="student-course-alignment student-course-alignment-calendar">
                                        <div class="col-lg-12 padding-alignment calendar-container item">
                                            <div class ='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>
                                                <input type="hidden" class="current-time" value="<?php echo $currentDate?>">
                                                <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                                                <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
                                            </div>
                                            <div class="calendar-day-details-right-side pull-left col-lg-3">
                                                <div class="day-detail-border">
                                                    <b style="font-size: 18px">Day Details:</b>
                                                </div>
                                                <div class="calendar-day-details"></div>
                                            </div>
                                        </div>
                                            </div>
                                        <?php break; ?>
                                    <?php endswitch; ?>
                            <?php }?>
                        <?php }?>
                    </div>
                    <div class="clear">
                    </div>
                <?php } ?> <!--Show always ends-->
                <?php break; ?>

            <?php endswitch;?>

        <?php }?>

    <?php }?>
</div>
<script>
    $(document).ready(function ()
    {
        var SH = $('#SH').val();
        var id = $('#id').val();
        if(SH == 'HC')
        {
            var node = document.getElementById('block5' + id);
            var img = document.getElementById('img' + id);
            if (node.className == 'blockitems student-block')
            {
                node.className = 'hidden';
                img.src = '../../img/expand.gif'
            }
        }
    });
    function xyz(e,id)
    {
        var node = document.getElementById('block5' + id);
        var img = document.getElementById('img' + id);
        if (node.className == 'blockitems student-block')
        {
            node.className = 'hidden';
            img.src = '../../img/expand.gif'
        }
        else
        {
            node.className = 'blockitems student-block';
            img.src = '../../img/collapse.gif'
        }
    }
</script>


