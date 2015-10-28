<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\CourseItemsUtility;
use app\components\AssessmentUtility;
?>
<?php
$this->title = ucfirst($courseDetail[0]['Block']['name']);
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<input type="hidden" class="home-path" value="<?php echo AppUtility::getURLFromHome('course', 'course/block-isolate?cid=' . $course->id) ?>">
<input type="hidden" class="calender-course-id" value="<?php echo $course->id?>">
<input type="hidden" class="courseId" value="<?php echo $course->id?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>


<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
        <?php if($user->rights == 100 || $user->rights == 20){ ?>
        <div class="pull-left header-btn">
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=modify&cid='.$course->id); ?>"
               class="btn btn-primary pull-right page-settings"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png">&nbsp;Course Setting
            </a>
        </div>
        <?php } ?>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("_toolbarStudent", ['course' => $course, 'section' => 'course']);?>
</div>

<div class="tab-content shadowBox">
        <?php
        $parent = AppConstant::NUMERIC_ZERO;
        $cnt = AppConstant::NUMERIC_ZERO;
        $countCourseDetails = count($courseDetail);
        if ($countCourseDetails){
        $assessment = $blockList = array();
        for ($i=0;$i<$countCourseDetails;$i++) {
            if ($courseDetail[$i]['Block']) { //if is a block
                $blockList[] = $i+1;
            }
        }
            foreach($courseDetail as $key => $item){
                if($user->rights == 10){
            switch(key($item)):
            case 'Block': ?>
        <div class="padding-left-fifteen padding-top-fifteen">
            <?php $block = $item[key($item)];?>

            <?php if ($block['avail'] != 0 && $block['SH'] == 'HO' && $block['startdate'] < $currentTime && $block['enddate'] > $currentTime) { ?>

            <?php if(count($item['itemList'])) {?>
                    <?php foreach($item['itemList'] as $itemlistKey => $item) { ?>
                        <?php switch(key($item)):
            /*Assessment here*/
            case 'Assessment': ?>
            <div class="inactivewrapper " onmouseout="this.className='inactivewrapper'">
                <?php $assessment = $item[key($item)]; ?>
                                    <?php if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) { ?>
                <div class="item">
                    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                    <div class="title">
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a></b>
                        <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                        <?php if ($assessment->enddate != 2000000000) { ?>
                    <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                            <!-- Use Late Pass here-->
                        <?php if($students->latepass != 0) {?>
                                                        <?php if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){?>
                        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                        <?php } ?>
                                                    <?php } else {?>
                                                        <?php echo "<p>You have no late passes remaining.</p>";?>
                                                    <?php }?>
                                                <?php } ?>
                    </div>
                    <div class="itemsum">
                        <p><?php echo $assessment->summary ?></p>
                    </div>
                </div>
                <?php
                } elseif ($assessment->enddate < $currentTime && ($assessment->reviewdate != 0) && ($assessment->reviewdate > $currentTime)) {
                ?>
                <div class="item">
                    <div class="icon" style="background-color: #1f0;">?</div>
                    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                    <div class="title">
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link"><?php echo $assessment->name ?></a></b>
                        <input type="hidden" class="confirmation-require" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                        <?php if ($assessment->reviewdate == 2000000000) { ?>
                    <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                            <BR>This assessment is in review mode - no scores will be saved.
                        <?php } else { ?>
                    <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                            <BR>This assessment is in review mode - no scores will be saved.
                        <?php } ?>
                    </div>
                    <div class="itemsum">
                        <p><?php echo $assessment->summary ?></p>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php break; ?>

                <!-- Forum here-->
            <?php case 'Forum': ?>
                                <?php $forum = $item[key($item)]; ?>
                                <?php if ($forum->avail != 0 && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
                                    <?php if ($forum->avail == 1 && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
            <div class="item">
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
            <div class="item">
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
                                <?php $wikis = $item[key($item)]; ?>
                                <?php if ($wikis->avail != 0 && $wikis->startdate < $currentTime && $wikis->enddate > $currentTime) { ?>
                                    <?php if ($wikis->avail == 1 && $wikis->enddate > $currentTime && $wikis->startdate < $currentTime) ?>
            <div class="item">
                <img alt="wiki" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/wiki.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'course/index?cid=' . $wikis->courseid) ?>">
                            <?php echo $wikis->name ?></a></b>
                    <span>New Revisions</span>
                </div>
                <div class="itemsum"><p>
                    <p>&nbsp;<?php echo $wikis->description ?></p>
                </div>
                <div class="clear">
                </div>
            </div>
            <?php } elseif ($wikis->avail == 2 && $wikis->enddate == 2000000000) { ?>
            <div class="item">
                <img alt="wiki" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/wiki.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $wikis->courseid) ?>">
                            <?php echo $wikis->name ?></a></b>
                    <span>New Revisions</span>
                </div>
                <div class="itemsum"><p>
                    <p>&nbsp;<?php echo $wikis->description ?></p>
                </div>
                <div class="clear">
                </div>
            </div>
            <?php } ?>
                                <?php break; ?>

                <!-- ////////////////// Linked text here //////////////////-->
            <?php case 'LinkedText': ?>
                                <?php $link = $item[key($item)]; ?>
                                <?php if ($link->avail != 0 && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
                <!--Link type : http-->
            <?php if ((substr($link->text, 0, 4) == 'http')) { ?>
            <div class="item">
                <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
                <!--Link type : file-->
            <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
            <div class="item">
                <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
                <!--Link type : external tool-->
            <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
            <div class="item">
                <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                <div class="title">
                    <!--open on new window or on same window-->
                    <?php if ($link->target != 0) { ?>
                                                <?php echo "<li><a href=\" target=\"_blank\"></a></li>" ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php } ?>
                                                        <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
            <?php } else { ?>
            <div class="item">
                <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
            <?php } ?>
                <!--Hide ends-->
            <?php } elseif ($link->avail == 2 && $link->enddate == 2000000000) { ?>
            <div class="item">
                <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
            <?php } ?> <!--Show always-->
            <?php break; ?>

                <!-- ////////////////// Inline text here //////////////////-->
            <?php case 'InlineText':?>
                                <?php $inline = $item[key($item)]; ?>
                                <?php if ($inline->avail != 0 && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
            <div class="item">
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
            <div class="item">
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
                                <div class="margin-right-fifteen">
                   <div class="col-md-12 padding-alignment calendar-container item">
                       <div class ='calendar padding-alignment calendar-alignment col-md-9 pull-left'>
                           <input type="hidden" class="current-time" value="<?php echo $currentDate?>">
                           <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                           <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
                       </div>
                       <div class="calendar-day-details-right-side pull-left col-md-3">
                           <div class="day-detail-border">
                               <b>Day Details:</b>
                           </div>
                           <div class="calendar-day-details"></div>
                       </div>
                   </div></div>
            <?php break; ?>
                            <?php endswitch; ?>
                    <?php }?>
                <?php }?>

                <div class="clear"></div>
            <?php } elseif ($block['avail'] == 2) { ?>
                <!--Show Always-->
            <?php if(count($item['itemList'])) {?>
                    <?php foreach($item['itemList'] as $itemlistKey => $item) { ?>
                        <?php switch(key($item)):
            /*Assessment here*/
            case 'Assessment': ?>
            <div class="inactivewrapper " onmouseout="this.className='inactivewrapper'">
                <?php $assessment = $item[key($item)]; ?>
                                    <?php if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) { ?>
                <div class="item">
                    <div class="icon" style="background-color: #1f0;">?</div>
                    <div class="title">
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a></b>
                        <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">

                        <?php if ($assessment->enddate != 2000000000) { ?>
                    <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>

                            <!-- Use Late Pass here-->
                        <?php if($students->latepass != 0) {?>
                                                        <?php if($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs*3600) ){?>
                        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/late-pass?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-late-pass" id="<?php echo $assessment->id?>"> Use Late Pass</a>
                        <input type="hidden" class="confirmation-late-pass" id="late-pass<?php echo $assessment->id?>" name="urlLatePass" value="<?php echo $students->latepass;?>">
                        <input type="hidden" class="confirmation-late-pass" id="late-pass-hrs<?php echo $assessment->id?>" name="urlLatePassHrs" value="<?php echo $course->latepasshrs;?>">
                        <?php } ?>
                                                    <?php } else {?>
                                                        <?php echo "<p>You have no late passes remaining.</p>";?>
                                                    <?php }?>

                                                <?php } ?>
                    </div>
                    <div class="itemsum">
                        <p><?php echo $assessment->summary ?></p>
                    </div>
                </div>
                <?php
                } elseif ($assessment->enddate < $currentTime && ($assessment->reviewdate != 0) && ($assessment->reviewdate > $currentTime)) {
                ?>
                <div class="item">
                    <div class="icon" style="background-color: #1f0;">?</div>
                    <div class="title">
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link"><?php echo $assessment->name ?></a></b>
                        <input type="hidden" class="confirmation-require" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">
                        <?php if ($assessment->reviewdate == 2000000000) { ?>
                    <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review.'; ?>
                            <BR>This assessment is in review mode - no scores will be saved.
                        <?php } else { ?>
                    <BR><?php echo 'Past Due Date of ' . AppUtility::formatDate($assessment->enddate) . '. Showing as Review until ' . AppUtility::formatDate($assessment->reviewdate) . '.'; ?>
                            <BR>This assessment is in review mode - no scores will be saved.
                        <?php } ?>
                    </div>
                    <div class="itemsum">
                        <p><?php echo $assessment->summary ?></p>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php break; ?>

                <!-- Forum here-->

            <?php case 'Forum': ?>
                                <?php $forum = $item[key($item)]; ?>
                                <?php if ($forum->avail != 0 && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
                                    <?php if ($forum->avail == 1 && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
            <div class="item">
                <img alt="forum" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $forum->courseid) ?>">
                            <?php echo $forum->name ?></a></b>
                </div>
                <div class="itemsum"><p>

                    <p>&nbsp;<?php echo $forum->description ?></p></p>
                </div>
            </div>
            <?php } elseif ($forum->avail == 2) { ?>
            <div class="item">
                <img alt="forum" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>

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
                                <?php $wikis = $item[key($item)]; ?>
                                <?php if ($wikis->avail != 0 && $wikis->startdate < $currentTime && $wikis->enddate > $currentTime) { ?>
                                    <?php if ($wikis->avail == 1 && $wikis->enddate > $currentTime && $wikis->startdate < $currentTime) ?>
            <div class="item">
                 <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>

                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $wikis->courseid) ?>">
                            <?php echo $wikis->name ?></a></b>
                    <span>New Revisions</span>
                </div>
                <div class="itemsum"><p>

                    <p>&nbsp;<?php echo $wikis->description ?></p>
                </div>
                <div class="clear">

                </div>
            </div>

            <?php } elseif ($wikis->avail == 2 && $wikis->enddate == 2000000000) { ?>
            <div class="item">
                <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $wikis->courseid) ?>">
                            <?php echo $wikis->name ?></a></b>
                    <span>New Revisions</span>
                </div>
                <div class="itemsum"><p>
                    <p>&nbsp;<?php echo $wikis->description ?></p></p>
                </div>
                <div class="clear">
                </div>
            </div>
            <?php } ?>
                                <?php break; ?>

                <!-- ////////////////// Linked text here //////////////////-->
            <?php case 'LinkedText': ?>
                                <?php $link = $item[key($item)]; ?>
                                <?php if ($link->avail != 0 && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
                <!--Link type : http-->
            <?php if ((substr($link->text, 0, 4) == 'http')) { ?>
            <div class="item">
                <img alt="link to web" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
                <!--Link type : file-->
            <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
            <div class="item">
                <img alt="link to doc" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
                <!--Link type : external tool-->
            <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
            <div class="item">
                <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                <div class="title">
                    <!--open on new window or on same window-->
                    <?php if ($link->target != 0) { ?>
                                                <?php echo "<li><a href=\" target=\"_blank\"></a></li>" ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php } ?>
                                                        <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
            <?php } else { ?>
            <div class="item">
                <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p>
                </div>
                <div class="clear"></div>
            </div>
            <?php } ?>
                <!--Hide ends-->
            <?php } elseif ($link->avail == 2 && $link->enddate == 2000000000) { ?>
            <div class="item">
                <img alt="link to html" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>
                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                            <?php echo $link->title ?></a></b>
                </div>
                <div class="itemsum"><p>
                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
            <?php } ?> <!--Show always-->
            <?php break; ?>

                <!-- ////////////////// Inline text here //////////////////-->
            <?php case 'InlineText':?>
                                <?php $inline = $item[key($item)]; ?>
                                <?php if ($inline->avail != 0 && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
            <div class="item">
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
            <div class="item">
                <!--Hide title and icon-->
                <?php if ($inline->title != '##hidden##') { ?>
                    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                <div class="title"><b><?php echo $inline->title ?></b>
                </div>
                <?php } ?>
                <div class="itemsum"><p>
                    <p><?php echo $inline->text ?></p>
                </div>
                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                <ul class="fileattachlist">
                    <li><a href="/open-math/files/<?php echo $instrFile->filename ?>" target="_blank"><?php echo $instrFile->filename ?></a></li>
                </ul>
                <?php } ?>
            </div>
                <div class="clear"></div>
            <?php } ?>
                <?php break; ?>

                                <!--         Calender Here-->
                       <?php case 'Calendar': ?>
                                    <div class="margin-right-fifteen">
                            <div class="col-md-12 padding-alignment calendar-container item">
                                <div class ='calendar padding-alignment calendar-alignment col-md-9 pull-left'>
                                    <input type="hidden" class="current-time" value="<?php echo $currentDate?>">
                                    <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                                    <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
                                </div>
                                <div class="calendar-day-details-right-side pull-left col-md-3">
                                    <div class="day-detail-border">
                                        <b>Day Details:</b>
                                    </div>
                                    <div class="calendar-day-details"></div>
                                </div>
                            </div></div>
                               <?php break; ?>
                       <?php endswitch; ?>
                    <?php }?>
                <?php }?>

                <div class="clear"></div>

    <?php } ?> <!--Show always ends-->
    <?php break; ?>
</div>
<?php endswitch;} elseif($user->rights == 100 || $user->rights == 20){ ?>
        <div class="row course-copy-export">
            <div class="col-md-2 course-top-menu">
                <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id);?>"><?php AppUtility::t('Copy Items');?></a>
            </div>
            <div class="col-md-2 course-top-menu">
                <a href="#"><?php AppUtility::t('Export');?></a>
            </div>

            <ul class="nav roster-menu-bar-nav sub-menu col-md-2">
                <li class="dropdown">
                    <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><?php AppUtility::t('Mass Change'); ?>
                        <span class="caret right-aligned"></span></a>
                    <ul class="dropdown-menu selected-options mass-changes">
                        <li>
                            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/change-assessment?cid=' . $course->id)?>"><?php AppUtility::t('Assessments'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/change-forum?cid=' . $course->id)?>"><?php AppUtility::t('Forums'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo AppUtility::getURLFromHome('block', 'block/change-block?cid=' . $course->id)?>"><?php AppUtility::t('Blocks'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/mass-change-dates?cid=' . $course->id)?>"><?php AppUtility::t('Dates'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/time-shift?cid=' . $course->id)?>"><?php AppUtility::t('Time Shifts'); ?></a>
                        </li>

                    </ul>
                </li>
            </ul>
            <div class='btn-group settings'>
                <a class='btn btn-primary setting-btn last'
                   href="#"><i class="fa fa-eye"></i>

                    <?php AppUtility::t('Instructor'); ?>
                </a>
                <a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'>
                    <span class='fa fa-caret-down'></span>
                </a>
                <ul class='dropdown-menu'>
                    <li>
                        <a href="#">
                            <?php AppUtility::t('Student'); ?>
                    </li>
                    <li>
                        <a href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$course->id. '&quickview=on');?>">
                            <?php AppUtility::t('Quick Rearrange'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
                    <div class="row add-item">
                        <div class="col-md-1 plus-icon">
                            <img class="add-item-icon" src="<?php echo AppUtility::getAssetURL()?>img/addItem.png">
                        </div>
                        <div class="col-md-2 add-item-text">
                            <p><?php AppUtility::t('Add An Item...');?></p>
                        </div>
                    </div>
                   <div class="padding-left-fifteen padding-top-fifteen"><?php switch (key($item)):
                        case  'Block':
                        $block = $item[key($item)];
                            if ($block['avail'] != 0 && $block['startdate'] < $currentTime && $block['enddate'] > $currentTime || $block['avail'] == 2) {
                            if (count($item['itemList'])) { ?>
                            <?php $blockList = array();
                            $countCourseDetails = count($item['itemList']);
                            for ($i=0;$i<$countCourseDetails;$i++) {
                                if ($item['itemList'][$i]['Block']) { //if is a block
                                    $blockList[] = $i+1;
                                }
                            }
                            ?>
                            <?php foreach ($item['itemList'] as $itemlistKey => $item) {
                                    switch (key($item)):
                                    case 'Assessment': ?>
                                    <?php  $cnt++;
                                    ?>
                                    <?php CourseItemsUtility::AddAssessment($assessment,$item,$course,$currentTime,$parent,$canEdit,$viewAll,$hasStats);?>
                                    <input type="hidden" class="assessment-link" value="<?php echo $assessment->id?>">
                                    <?php break; ?>
                                    <!-- ///////////////////////////// Forum here /////////////////////// -->,
                                    <?php case 'Forum': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddForum($item,$course,$currentTime,$parent, $hasStats); ?>
                    <?php break; ?>
                    <!-- ////////////////// Wiki here //////////////////-->
                <?php case 'Wiki': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddWiki($item,$course,$parent, $currentTime,$hasStats); ?>
                    <?php break; ?>
                    <!-- ////////////////// Linked text here //////////////////-->
                <?php
                    case 'LinkedText': ?>
                        <?php  $cnt++; ?>
                        <?php CourseItemsUtility::AddLink($item,$currentTime,$parent,$course,$hasStats);?>
                        <?php break; ?>
                        <!-- ////////////////// Inline text here //////////////////-->
                    <?php case 'InlineText': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddInlineText($item,$currentTime,$course,$parent);?>
                    <?php break; ?>
                    <!-- Calender Here-->
                <?php case 'Calendar': ?>
                    <?php  $cnt++; ?>
                    <?php CourseItemsUtility::AddCalendar($item,$parent,$course);?>
                    <?php break; ?>
                    <!--  Block here-->
                <?php case  'Block': ?>
                    <?php  $cnt++; ?>
                    <?php $displayBlock = new CourseItemsUtility();
                    $displayBlock->DisplayWholeBlock($item,$currentTime,$assessment,$course,$parent,$cnt,$canEdit,$viewAll,$hasStats);
                    ?>
                    <?php break; ?>
                <?php endswitch;
                ?>
                            <?php } ?>
                            <?php } ?>

            <?php  }
                             break; ?>
                        <?php endswitch;
                }?></div>

<div class="col-md-12 align-linked-text-right padding-bottom-fifteen padding-right-twenty">
<?php if($user->rights == 100 || $user->rights == 20) {?>
        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $course->id) ?>">Back</a>
   <?php } elseif($user->rights == 10){ ?>
        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $course->id) ?>">Back</a>
<?php }?>
</div>
</div>

<?php }
}?>
