<link href='../../../web/css/fullcalendar.min.css' rel='stylesheet' />
<link href='../../../web/css/fullcalendar.print.css' rel='stylesheet' media='print' />
<script src='../../../web/js/moment.min.js'></script>
<script src='../../../web/js/jquery.min.js'></script>
<script src='../../../web/js/fullcalendar.min.js'></script>

<?php
use yii\helpers\Html;
use app\components\AppUtility;
?>
    <!--Get current time-->
<?php
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
?>

<?php echo $this->render('_toolbar'); ?>

    <div class="needed">
        <?php echo $this->render('_leftSide');?>
    </div>

    <!--Course name-->

    <div class="course">
        <h3><b><?php echo $course->name ?></b></h3>
    </div>


    <!-- ////////////////// Assessment here //////////////////-->

<div class="margin-top">
<div class="inactivewrapper " onmouseover="this.className='activewrapper' "
     onmouseout="this.className='inactivewrapper'">
    <?php foreach ($assessments as $key => $assessment) { ?>
        <?php if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) { ?>
            <div class=item>
                <div class=icon style="background-color: #1f0;">?</div>
                <div class=title>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $assessment->courseid) ?>"><?php echo $assessment->name ?></a></b>
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
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $assessment->courseid) ?>"><?php echo $assessment->name ?></a></b>
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
    <?php } ?>
</div>


<!-- ////////////////// Forum here //////////////////-->


<?php foreach ($forums as $key => $forum) { ?>
    <?php if ($forum->avail != 0 && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
        <?php if ($forum->avail == 1 && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
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
<?php } ?>


<!-- ////////////////// Wiki here //////////////////-->

<?php foreach ($wiki as $key => $wikis) { ?>
    <!--Hide wiki-->
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
<?php } ?>


<!-- ////////////////// Linked text here //////////////////-->


<?php foreach ($links as $key => $link) { ?>
    <!--Hide linked text-->
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
<?php } ?>


<!-- ////////////////// Inline text here //////////////////-->


<?php foreach ($inlineText as $key => $inline) { ?>
    <!--Hide functionality-->
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
<?php } ?> <!--foreach ends-->


<!-- ////////////////// Block here //////////////////-->

<?php foreach ($blocks as $key => $block) {
$itemList = unserialize($block->itemorder);
//print_r($itemList);
?>

<?php /*AppUtility::dump($itemList    ); */?>
<!-- Hide Block-->
<?php if ($itemList[1]['avail'] != 0 && $itemList[1]['SH'] == 'HO' && $itemList[1]['startdate'] < $currentTime && $itemList[1]['enddate'] > $currentTime) { ?>
    <div class=block>
        <?php if (strlen($itemList[1]['SH']) == 1 || $itemList[1]['SH'][1] == 'O') { ?>
            <span class=left>
                <img alt="expand/collapse" style="cursor:pointer;" id="img3" src="/IMathAS/img/collapse.gif"
                     onClick="toggleblock('3','0-9')"/>
                </span>
        <?php } elseif (strlen($itemList[1]['SH']) > 1) { ?>
            <span class=left>
            <img alt="folder" src="/IMathAS/img/folder2.gif">
        </span>
        <?php } elseif (strlen($itemList[1]['SH']) > 1 && $itemList[1]['SH'][1] == 'T') { ?>
            <span class=left>
            <img alt="folder" src="/IMathAS/img/folder_tree.png">
        </span>
        <?php } else { ?>
            <span class=left>
                <img alt="expand/collapse" style="cursor:pointer;" id="img3" src="/IMathAS/img/collapse.gif"
                     onClick="toggleblock('3','0-9')"/>
                </span>
        <?php } ?>
        <div class=title>
            <span class="right"><a
                    href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $block->id) ?>">Isolate</a></span>
            <span class=pointer onClick="toggleblock('3','0-1')"><b>
                    <a href="#" onclick="return false;"><?php print_r($itemList[1]['name']); ?></a></b></span>
        </div>
    </div>
    <div class=blockitems id="block5">Loading Here......</div>
    <div class="clear"></div>

<?php } elseif ($itemList[1]['avail'] == 2) { ?> <!--Hide block ends-->
    <!--Show Always-->
    <div class=block>
        <?php if (strlen($itemList[1]['SH']) > 1 && $itemList[1]['SH'][1] == 'F') { ?>
            <span class=left>
            <img alt="folder" src="/IMathAS/img/folder2.gif">
                    </span>
        <?php } elseif (strlen($itemList[1]['SH']) > 1 && $itemList[1]['SH'][1] == 'T') { ?>
            <span class=left>
            <img alt="folder" src="/IMathAS/img/folder_tree.png">
                    </span>
        <?php } else { ?>
            <span class=left>
                <img alt="expand/collapse" style="cursor:pointer;" id="img3" src="/IMathAS/img/expand.gif"
                     onClick="toggleblock('3','0-9')"/>
                </span>
        <?php } ?>
        <div class=title>
            <span class="right"><a
                    href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $block->id) ?>">Isolate</a></span>
            <span class=pointer onClick="toggleblock('3','0-1')"><b>
                    <a href="#" onclick="return false;"><?php print_r($itemList[8]['name']); ?></a></b></span>
        </div>
    </div>
    <div class=blockitems id="block5">Loading Here......</div>
    <div class="clear">

    </div>

<?php } ?> <!--Show always ends-->

<?php } ?><!--foreach ends-->
</div>

<!--Calender here -->
<script>

    $(document).ready(function() {
        var startDate = '2015-05-05';
        var endDate = '2015-05-04';
        var reviewDate = '2015-05-09';


        $('#calendar').fullCalendar({
            height: 400,
            header: {

                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            businessHours: false, // display business hours
            editable: false,
            events: [
                {
                    title: 'Assessment',
                    start: endDate
                },
                {
                    title: 'Review Assessment',
                    start: reviewDate,
                    color: '#257e4a'
                }
            ],
            eventClick: function(calEvent, jsEvent, view) {

                alert('Event: ' + calEvent.start);


            }

        });

    });

</script>
<style>

    body {
        margin: 20px 5px;
        font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
        font-size: 14px;
    }

    #calendar {
       margin-left: 20%;
    }

</style>
<body>

<div id='calendar'></div>

</body>
