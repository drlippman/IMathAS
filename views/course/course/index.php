<link href='../../../web/css/fullcalendar.print.css' rel='stylesheet' media='print' />
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">

<?php
use yii\helpers\Html;
use app\components\AppUtility;
?>
<?php
//AppUtility::dump($assessments);
/*Conversion into hour, minute and seconds*/
//$hour = (floor(abs($assessments->timelimit)/3600) < 10) ? '0'+floor(abs($assessments->timelimit)/3600) : floor(abs($assessments->timelimit)/3600);
//$min = floor((abs($assessments->timelimit)%3600)/60);
?>
<!--<input type="hidden" id="show-timer" name="showtimer" value="--><?php //echo $hour .' hour, ' .$min .' minutes.'?><!--">-->
    <!--Get current time-->
<?php
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
?>

<?php echo $this->render('_toolbar'); ?>

    <div class="needed">
        <?php echo $this->render('_leftSide');?>
    </div>

<!--    <!--Course name-->

    <div class="course">
        <h3><b><?php echo $course->name ?></b></h3>
    </div>


    <!-- ////////////////// Assessment here //////////////////-->
<div class="margin-top">
<?php if($courseDetail){
    foreach($courseDetail as $key => $item){
        switch(key($item)):
            case 'Assessment': ?>
                <div class="inactivewrapper " onmouseout="this.className='inactivewrapper'">
                    <?php $assessment = $item[key($item)]; ?>
                    <?php if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) { ?>
                        <div class=item>
                            <div class=icon style="background-color: #1f0;">?</div>
                            <div class=title>
                                <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id.'&cid=' .$course->id) ?>" class="confirmation-require assessment-link" id="<?php echo $assessment->id?>"><?php echo $assessment->name ?></a></b>
                                <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id?>" name="urlTimeLimit" value="<?php echo $assessment->timelimit;?>">

                                <?php if ($assessment->enddate != 2000000000) { ?>
                                    <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>

                                <?php } ?>
                            </div>
                            <div class=itemsum>
                                <p><?php echo $assessment->summary ?></p>
                            </div>
                        </div>
                    <?php
                    } elseif ($assessment->enddate < $currentTime && ($assessment->reviewdate != 0) && ($assessment->reviewdate > $currentTime)) {
                        ?>
                        <div class=item>
                            <div class=icon style="background-color: #1f0;">?</div>
                            <div class=title>
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
                            <div class=itemsum>
                                <p><?php echo $assessment->summary ?></p>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php break; ?>

            <!-- ////////////////// Forum here //////////////////-->
            <?php case 'Forum': ?>

                <?php $forum = $item[key($item)]; ?>
                <?php if ($forum->avail != 0 && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
                    <?php if ($forum->avail == 1 && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
                        <div class=item>
                        <img alt="forum" class="floatleft" src="/IMathAS/img/forum.png"/>
                        <div class=title>
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $forum->courseid) ?>">
                    <?php echo $forum->name ?></a></b>
                    </div>
                    <div class=itemsum><p>

                        <p>&nbsp;<?php echo $forum->description ?></p></p>
                    </div>
                    </div>
                <?php } elseif ($forum->avail == 2) { ?>
                    <div class=item>
                        <img alt="forum" class="floatleft" src="/IMathAS/img/forum.png"/>

                        <div class=title>
                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $forum->courseid) ?>">
                                    <?php echo $forum->name ?></a></b>
                        </div>
                        <div class=itemsum><p>

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
                        <div class=item>
                        <img alt="wiki" class="floatleft" src="/IMathAS/img/wiki.png"/>

                        <div class=title>
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $wikis->courseid) ?>">
                    <?php echo $wikis->name ?></a></b>
                    <span>New Revisions</span>
                    </div>
                    <div class=itemsum><p>

                        <p>&nbsp;<?php echo $wikis->description ?></p></p>
                    </div>
                    <div class="clear">

                    </div>
                    </div>

                <?php } elseif ($wikis->avail == 2 && $wikis->enddate == 2000000000) { ?>
                    <div class=item>
                        <img alt="wiki" class="floatleft" src="/IMathAS/img/wiki.png"/>

                        <div class=title>
                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $wikis->courseid) ?>">
                                    <?php echo $wikis->name ?></a></b>
                            <span>New Revisions</span>
                        </div>
                        <div class=itemsum><p>

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
                        <div class=item>
                            <img alt="link to web" class="floatleft" src="/IMathAS/img/web.png"/>

                            <div class=title>
                                <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                                        <?php echo $link->title ?></a></b>
                            </div>
                            <div class=itemsum><p>

                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>

                        <!--Link type : file-->

                    <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
                        <div class=item>
                            <img alt="link to doc" class="floatleft" src="/IMathAS/img/doc.png"/>

                            <div class=title>
                                <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                                        <?php echo $link->title ?></a></b>
                            </div>
                            <div class=itemsum><p>

                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>

                        <!--Link type : external tool-->

                    <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
                        <div class=item>
                            <img alt="link to html" class="floatleft" src="/IMathAS/img/html.png"/>

                            <div class=title>

                                <!--open on new window or on same window-->

                                <?php if ($link->target != 0) { ?>
                                <?php echo "<li><a href=\" target=\"_blank\"></a></li>" ?>
                                <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                                        <?php } ?>
                                        <?php echo $link->title ?></a></b>
                            </div>
                            <div class=itemsum><p>

                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>
                    <?php } else { ?>
                        <div class=item>
                            <img alt="link to html" class="floatleft" src="/IMathAS/img/html.png"/>

                            <div class=title>
                                <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                                        <?php echo $link->title ?></a></b>
                            </div>
                            <div class=itemsum><p>

                                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                            <div class="clear"></div>
                        </div>
                    <?php } ?>
                    <!--Hide ends-->
                <?php } elseif ($link->avail == 2 && $link->enddate == 2000000000) { ?>
                    <div class=item>
                        <img alt="link to html" class="floatleft" src="/IMathAS/img/html.png"/>

                        <div class=title>
                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid) ?>">
                                    <?php echo $link->title ?></a></b>
                        </div>
                        <div class=itemsum><p>

                            <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                        <div class="clear"></div>
                    </div>
                <?php } ?> <!--Show always-->

                <?php break; ?>

            <!-- ////////////////// Inline text here //////////////////-->
            <?php case 'InlineText': ?>
                <?php $inline = $item[key($item)]; ?>
                <?php if ($inline->avail != 0 && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
                    <div class=item>
                        <!--Hide title and icon-->
                        <?php if ($inline->title != '##hidden##') { ?>
                            <img alt="text item" class="floatleft" src="/IMathAS/img/inline.png"/>
                            <div class=title><b><?php echo $inline->title ?></b>
                            </div>
                        <?php } ?>

                        <div class=itemsum><p>

                            <p><?php echo $inline->text ?></p></p>
                        </div>
                        <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                            <ul class="fileattachlist">
                                <li>
                                    <a href="/open-math/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                                </li>
                            </ul>
                        <?php } ?>
                    </div>
                    <?php ?>
                    <div class="clear"></div>
                <?php } elseif ($inline->avail == 2) { ?> <!--Hide ends and displays show always-->
                    <div class=item>
                        <!--Hide title and icon-->
                        <?php if ($inline->title != '##hidden##') { ?>
                            <img alt="text item" class="floatleft" src="/IMathAS/img/inline.png"/>
                            <div class=title><b><?php echo $inline->title ?></b>
                            </div>
                        <?php } ?>

                        <div class=itemsum><p>

                            <p><?php echo $inline->text ?></p></p>
                        </div>
                        <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                            <ul class="fileattachlist">
                                <li><a href="/open-math/files/<?php echo $instrFile->filename ?>"
                                       target="_blank"><?php echo $instrFile->filename ?></a></li>
                            </ul>
                        <?php } ?>
                    </div>
                    <?php ?>
                    <div class="clear"></div>
                <?php } ?>
                <?php break; ?>

            <?php case 'Calendar': ?>

                <div id='calendar'></div>

                <div id="eventContent" title="Event Details">
                    <div id="eventInfo">

                    </div>
                    <p><strong><a id="eventLink"></a></strong></p>
                </div>
            <?php break; ?>

            <?php case 'Block': ?>

            <?php break; ?>

        <?php endswitch;?>

    <?php }
}?>




<!-- Calender Here-->
