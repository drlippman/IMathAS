<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;

$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
?>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print'/>
<!--<div class="mainbody">-->

<div>
    <?php
    $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
    $now = $currentTime;

    echo $this->render('_toolbarTeacher', ['course' => $course]); ?>
</div>
<input type="hidden" class="calender-course-id" id="courseIdentity" value="<?php echo $course->id ?>">
<input type="hidden" class="home-path"
       value="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id) ?>">
<div class="col-lg-2 needed pull-left">
    <?php echo $this->render('_leftSideTeacher', ['course' => $course, 'messageList' => $messageList]); ?>
</div>

<!--Course name-->
<div class="col-lg-10 pull-left">
<div class="course-title">
    <h3><b><?php echo ucfirst($course->name) ?></b></h3>

    <div class="col-lg-offset-3 buttonAlignment">
        <div class="view">
            <p>View:</p>
        </div>
        <a class="btn btn-primary ">Instructor</a>
        <a class="btn btn-primary" href="#">Student</a>
        <a class="btn btn-primary" href="#">Quick Rearrange</a>
    </div>
</div>
<div class="course-content">
    <?php if (!$courseDetail) { ?>
        <p><strong>Welcome to your course!</strong></p>

        <p> To start by copying from another course, use the <a href="#">Course Items: Copy link</a> along the left
            side
            of the screen. </p>

        <p> If you want to build from scratch, use the "Add An Item" pulldown below to get started. </p>

    <?php } ?></div>
<div class="col-lg-3 pull-left padding-zero">
    <?php
    $parent = AppConstant::NUMERIC_ZERO;
    $tb = 't';
    $html = "<select class='form-control' name=addtype id=\"addtype$parent-$tb\" onchange=\"additem('$parent','$tb')\" ";
    if ($tb == 't') {
        $html .= 'style="margin-bottom:5px;"';
    }
    $html .= ">\n";
    $html .= "<option value=\"\">" . _('Add An Item...') . "</option>\n";
    $html .= "<option value=\"assessment\">" . _('Add Assessment') . "</option>\n";
    $html .= "<option value=\"inlinetext\">" . _('Add Inline Text') . "</option>\n";
    $html .= "<option value=\"linkedtext\">" . _('Add Link') . "</option>\n";
    $html .= "<option value=\"forum\">" . _('Add Forum') . "</option>\n";
    $html .= "<option value=\"wiki\">" . _('Add Wiki') . "</option>\n";
    $html .= "<option value=\"block\">" . _('Add Block') . "</option>\n";
    $html .= "<option value=\"calendar\">" . _('Add Calendar') . "</option>\n";
    $html .= "</select><BR>\n";
    echo $html;
    ?>
</div>
<br><br><br>
<!-- ////////////////// Assessment here //////////////////-->
<?php $countCourseDetails = count($courseDetail);
if ($countCourseDetails){
$assessment = $blockList = array();

foreach ($courseDetail as $key => $item){
echo AssessmentUtility::createItemOrder($key, $countCourseDetails, $parent, $blockList);
switch (key($item)):
case 'Assessment': ?>
<?php $assessment = $item[key($item)];
if ($assessment->enddate >= $currentTime && $assessment->startdate >= $currentTime) {
?>
<div class="item">
<img alt="assess" class="floatleft" src="<?php echo AppUtility::getAssetURL() ?>img/assess.png"/>
<div class="title">
<b>
    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
       class="confirmation-require assessment-link"
       id="<?php echo $assessment->id ?>"><?php echo $assessment->name ?></a>
</b>
<input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id ?>"
       name="urlTimeLimit" value="<?php echo $assessment->timelimit; ?>">

<?php if ($assessment['avail'] == AppConstant::NUMERIC_ZERO) { ?>
    <BR>Hidden
<?php } else { ?>
    <?php if ($assessment->reviewdate == AppConstant::ALWAYS_TIME) { ?>
        <BR>    Available <?php echo AppUtility::formatDate($assessment->startdate); ?>, until <?php echo AppUtility::formatDate($assessment->enddate); ?>, Review until Always

    <?php } else if ($assessment->reviewdate == AppConstant::NUMERIC_ZERO) { ?>
        <br>Available <?php echo AppUtility::formatDate($assessment->startdate); ?>, until <?php echo AppUtility::formatDate($assessment->enddate); ?>
    <?php } else { ?>
        <br> Available <?php echo AppUtility::formatDate($assessment->startdate); ?>, until <?php echo AppUtility::formatDate($assessment->enddate); ?> Review until <?php echo AppUtility::formatDate($assessment->reviewdate); ?>
    <?php }
} ?>
<?php if ($assessment->allowlate != AppConstant::NUMERIC_ZERO) {
    echo 'LP';
}
?>
<a> Questions </a>| <a
    href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$assessment->id . '&cid=' . $course->id . '&block=0') ?>">
    Settings </a>|<a onclick="deleteItem('<?php echo $assessment->id ;?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> |<a> Copy </a>| <a>Grades</a>
<?php  } else if ($assessment->enddate <= $currentTime && $assessment->startdate <= $currentTime && $assessment->startdate != 0) {
?>
<div class="item">
<img alt="assess" class="floatleft" src="<?php echo AppUtility::getAssetURL() ?>img/assess.png"/>

<div class="title">
<b>
    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
       class="confirmation-require assessment-link"
       id="<?php echo $assessment->id ?>"><?php echo $assessment->name ?></a>
</b>
<input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id ?>"
       name="urlTimeLimit" value="<?php echo $assessment->timelimit; ?>">
<?php if ($assessment['avail'] == AppConstant::NUMERIC_ZERO) { ?>
    <BR>Hidden
<?php } else { ?>
    <?php if ($assessment->reviewdate == AppConstant::ALWAYS_TIME) { ?>
        <BR>    Past Due Date of <?php echo AppUtility::formatDate($assessment->enddate); ?>. Showing as Review.
    <?php } else if ($assessment->reviewdate == AppConstant::NUMERIC_ZERO) { ?>
        <br>Available <?php echo AppUtility::formatDate($assessment->startdate); ?>, until <?php echo AppUtility::formatDate($assessment->enddate); ?>
    <?php } else { ?>
        <br> Past Due Date of <?php echo AppUtility::formatDate($assessment->enddate); ?>,  Showing as Review.untill <?php echo AppUtility::formatDate($assessment->reviewdate); ?>
    <?php }
} ?>
<?php if ($assessment->allowlate != AppConstant::NUMERIC_ZERO) {
    echo 'LP';
} ?>
<a> Questions </a>| <a
    href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id=' . $assessment->id . '&cid=' . $course->id . '&block=0') ?>">
    Settings </a>|<a onclick="deleteItem('<?php echo $assessment->id ;?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> |<a> Copy </a>| <a>Grades</a>
<?php if ($assessment->reviewdate > AppConstant::NUMERIC_ZERO) { ?>
    <br>This assessment is in review mode - no scores will be saved
<?php }
}else if ($assessment->startdate >= 0 || $assessment->enddate == AppConstant::ALWAYS_TIME) {
?>
<div class="item">
    <img alt="assess" class="floatleft"
         src="<?php echo AppUtility::getAssetURL() ?>img/assess.png"/>

    <div class="title">
        <b>
            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
               class="confirmation-require assessment-link"
               id="<?php echo $assessment->id ?>"><?php echo $assessment->name ?></a>
        </b>
        <input type="hidden" class="confirmation-require"
               id="time-limit<?php echo $assessment->id ?>" name="urlTimeLimit"
               value="<?php echo $assessment->timelimit; ?>">
        <?php if ($assessment->startdate >= 0 && $assessment->enddate > $currentTime) { ?>
            <?php if ($assessment['avail'] == AppConstant::NUMERIC_ZERO) { ?>

                <BR>Hidden
            <?php } else { ?>
                <?php if ($assessment->reviewdate >= AppConstant::NUMERIC_ZERO) { ?>
                    <BR> Due <?php echo AppUtility::formatDate($assessment->enddate); ?>.
                    <!--                                                                                --><?php //}else if (){?>
                    <!--                                                                                    <br>Available --><?php //echo AppUtility::formatDate($assessment->startdate); ?><!--, until --><?php //echo AppUtility::formatDate($assessment->enddate); ?>
                <?php } else { ?>
                    <br> Past Due Date of <?php echo AppUtility::formatDate($assessment->enddate); ?>,  Showing as Review.untill <?php echo AppUtility::formatDate($assessment->reviewdate); ?>
                <?php }
            }
        }    else if ($assessment->startdate >= 0 && $assessment->enddate < $currentTime) { ?>
            <?php if ($assessment['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                <BR>Hidden
            <?php } else { ?>
                <?php if ($assessment->reviewdate == AppConstant::ALWAYS_TIME) { ?>
                    <BR>    Past Due Date of <?php echo AppUtility::formatDate($assessment->enddate); ?>. Showing as Review.

                <?php } else if ($assessment->reviewdate == AppConstant::NUMERIC_ZERO) { ?>
                    <?php if($assessment->startdate == AppConstant::NUMERIC_ZERO){ ?>
                        <br>Available Always until <?php echo AppUtility::formatDate($assessment->enddate); ?>
                    <?php }else{ ?>
                        <br>Available <?php echo AppUtility::formatDate($assessment->startdate);  ?>, <?php echo AppUtility::formatDate($assessment->enddate);  ?>
                    <?php } ?>
                <?php } else { ?>
                    <br> Past Due Date of <?php echo AppUtility::formatDate($assessment->enddate); ?>,  Showing as Review.untill <?php echo AppUtility::formatDate($assessment->reviewdate); ?>



                <?php }
            }   } ?>


        <?php if ($assessment->allowlate != AppConstant::NUMERIC_ZERO) {
            echo 'LP';
        } ?>
        <a> Questions </a>| <a
            href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id=' . $assessment->id . '&cid=' . $course->id . '&block=0') ?>">
            Settings </a>|<a onclick="deleteItem('<?php echo $assessment->id ;?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> |<a> Copy </a>| <a>Grades</a>
        <?php if ($assessment->startdate >= 0 && $assessment->enddate < $currentTime && $assessment['avail'] != AppConstant::NUMERIC_ZERO && $assessment->reviewdate != AppConstant::NUMERIC_ZERO) { ?>

            <br> This assessment is in review mode - no scores will be saved
        <?php } ?>

        <?php }   ?>
    </div>
    <div class="itemsum">
        <p><?php echo $assessment->summary ?></p>
    </div>
</div>
<?php break; ?>

<!-- ///////////////////////////// Forum here /////////////////////// -->,
<?php case 'Forum': ?>
    <?php $forum = $item[key($item)];
    if ($forum->avail == 2 || $forum->startdate < $currentTime && $forum->enddate > $currentTime && $forum->avail == 1) {?>

        <div class="item">
            <!--Hide title and icon-->
            <?php if ($forum->name != '##hidden##') {
            $endDate = AppUtility::formatDate($forum->enddate);?>
            <img alt="text item" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>
            <div class="title">
                <b><?php echo $forum->name ?></b> <br>
            </div>
            <div class="itemsum">
                    <?php } ?>

                    <?php if($forum->avail == 2) { ?>
             <?php echo "Showing Always"; ?> <a href="#"> Modify  </a> | <a href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> | <a href="#"> Copy </a><br>
                  <?php  }
                    else {
                        if($forum->startdate == 0 && $forum->enddate == 2000000000 || $forum->startdate != 0 && $forum->enddate == 2000000000)
                        {
                            echo "Showing until: Always"; ?> <a href="#"> Modify  </a> | <a href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> | <a href="#"> Copy </a><br>
                     <?php   }
                        else{
                            echo "Showing until: " .$endDate;?> <a href="#"> Modify  </a> | <a href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> | <a href="#"> Copy </a><br>
                     <?php
                        }
                    }
                    $duedates = "";
                    if ($forum->postby > $currentTime && $forum->postby != 2000000000) {
                        echo('New Threads due '), AppUtility::formatdate($forum->postby).".";
                    }
                    if ($forum->replyby > $currentTime && $forum->replyby != 2000000000) {
                        echo(' Replies due '), AppUtility::formatdate($forum->replyby).".";
                    }
                    ?>
                <p><?php echo $forum->description ?></p>
            </div>
        </div>

    <?php } elseif($forum->avail == 0) { ?>
        <div class="item">
            <!--Hide title and icon-->
            <?php if ($forum->name != '##hidden##') {
            $endDate = AppUtility::formatDate($forum->enddate);?>
            <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>
            <div class="title">
                <b><?php echo $forum->name ?></b> <br>
            </div>
            <div class="itemsum"><p>
                    <?php
                    echo 'Hidden'; ?> <a href="#"> Modify  </a> | <a href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> | <a href="#"> Copy </a><br>
                    <?php
                    } ?>

                <p><?php echo $forum->description ?></p>
            </div>
        </div>
    <?php } else{ ?>
        <div class="item">
            <?php if ($forum->name != '##hidden##') {
            $endDate = AppUtility::formatDate($forum->enddate);?>
            <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>
            <div class="title">
                <b><?php echo $forum->name ?></b> <br>
            </div>
            <div class="itemsum"><p>
                    <?php }
                    $startDate = AppUtility::formatDate($forum->startdate);
                    $endDate = AppUtility::formatDate($forum->enddate);
                    echo "Showing " .$startDate. " until " .$endDate; ?> <a href="#"> Modify  </a> | <a href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> | <a href="#"> Copy </a><br>

            </div>

        </div>
    <?php }?>
    <?php break; ?>

    <!-- ////////////////// Wiki here //////////////////-->
<?php case 'Wiki': ?>
    <?php $wikis = $item[key($item)]; ?>
    <?php $endDateOfWiki = AppUtility::formatDate($wikis['enddate'], 'm/d/y');
    ?>
    <?php if ($wikis->avail == AppConstant::NUMERIC_ZERO) { ?>

        <div class="item">
            <img alt="wiki" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/wiki.png"/>

            <div class="title">
                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                        <?php echo $wikis->name ?></a></b>

                <br><span>Hidden</span>
                <?php echo '<a href="' . AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id) . '"> Modify  </a>'; ?> | <a>Delete</a> | <a>Copy</a>
            </div>
            <div class="itemsum">
                <p>

                <p>&nbsp;<?php echo $wikis->description ?></p></p>
            </div>
            <div class="clear"></div>
        </div>
    <?php } elseif ($wikis->avail == AppConstant::NUMERIC_ONE) { ?>
        <div class="item">
            <img alt="wiki" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/wiki.png"/>

            <div class="title">
                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                        <?php echo $wikis->name ?></a></b>
                <br><span> Showing until:</span>
                <?php if ($wikis['enddate'] < AppConstant::ALWAYS_TIME) {
                    echo $endDateOfWiki;
                } else { ?>
                    Always
                <?php } ?>
                <a href="#">Modify</a> | <a>Delete</a> | <a>Copy</a><br>
                <?php if ($wikis['editbydate'] > AppConstant::NUMERIC_ONE && $wikis['editbydate'] < AppConstant::ALWAYS_TIME) { ?>
                    Edits due by <? echo $endDateOfWiki; ?>
                <?php } ?>
            </div>
            <div class="itemsum">
                <p>

                <p>&nbsp;<?php echo $wikis->description ?></p></p>
            </div>
            <div class="clear"></div>
        </div>
    <?php } else if ($wikis->avail == AppConstant::NUMERIC_TWO) { ?>
        <div class="item">
            <img alt="wiki" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/wiki.png"/>

            <div class="title">
                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                        <?php echo $wikis->name ?></a></b>
                <br><span>Showing Always</span>
                <?php echo '<a href="' . AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id) . '"> Modify  </a>'; ?> | <a>Delete</a> | <a>Copy</a><br>
                <?php if ($wikis['editbydate'] > AppConstant::NUMERIC_ONE && $wikis['editbydate'] < AppConstant::ALWAYS_TIME) { ?>
                    Edits due by <? echo $endDateOfWiki; ?>
                <?php } ?>
            </div>
            <div class="itemsum">
                <p>

                <p>&nbsp;<?php echo $wikis->description ?></p></p>
            </div>
            <div class="clear"></div>
        </div>
    <?php } ?>
    <?php break; ?>

    <!-- ////////////////// Linked text here //////////////////-->
<?php
case 'LinkedText': ?>
    <?php $link = $item[key($item)]; ?>
    <!--                                --><?php //if ($link->avail != 0 && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
    <!--Link type : http-->
    <?php $text = $link->text; ?>
    <?php $startDateOfLink = AppUtility::formatDate($link->startdate);
    $endDateOfLink = AppUtility::formatDate($link->enddate); ?>
    <?php if ((substr($text, 0, 4) == 'http') && (strpos(trim($text), " ") == false)) { ?>
        <div class="item">
            <img alt="link to web" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>

            <div class="title">
                <?php if ($link->target == 1) { ?>
                    <b><a href="<?php echo $text ?>" target="_blank"><?php echo $link->title ?>&nbsp;<img
                                src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                <?php } else { ?>
                    <b><a href="<?php echo $text ?>"><?php echo $link->title; ?></a></b>
                <?php } ?>


                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>
                    <?php }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) { ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>
                <a> Modify </a>|<a> Delete </a> |<a> Copy </a>

            </div>
            <div class="itemsum">
                <p>

                <p><?php echo $link->summary ?>&nbsp;</p></p>
            </div>
            <div class="clear"></div>
        </div>


        <!--                        Link type : file-->
    <?php } elseif ((substr($link->text, 0, 5) == 'file:')) { ?>
        <div class="item">
            <img alt="link to doc" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>

            <div class="title">
                <?php if ($link->target != 0) { ?>
                    <?php
                    $filename = substr(strip_tags($link->text), 5);
                    require_once("../components/filehandler.php");
                    $alink = getcoursefileurl($filename);
                    echo '<a href="' . $alink . '">' . $link->title . '</a>';
                } else {
                    $filename = substr(strip_tags($link->text), 5);
                    require_once("../components/filehandler.php");
                    $alink = getcoursefileurl($filename);
                    echo '<a href="' . $alink . '">' . $link->title . '</a>';
                } ?>


                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>

                    <?php }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) { ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>
                <a> Modify </a>|<a> Delete </a> |<a> Copy </a>


            </div>
            <div class="itemsum">
                <p>

                <p><?php echo $link->summary ?>&nbsp;</p></p>
            </div>
            <div class="clear"></div>
        </div>
        <!--Link type : external tool-->
    <?php } elseif (substr($link->text, 0, 8) == 'exttool:') { ?>
        <div class="item">
            <img alt="link to html" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

            <div class="title">
                <!--open on new window or on same window-->
                <?php if ($link->target != 0) { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid . '&id=' . $link->id) ?>"
                          target="_blank">
                            <?php echo $link->title ?>&nbsp;<img
                                src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                <?php } else { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid . '&id=' . $link->id) ?>">
                            <?php echo $link->title ?></a></b>
                <?php } ?>


                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>
                    <?php }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) { ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>
                <a> Modify </a>|<a> Delete </a> |<a> Copy </a>


            </div>
            <div class="itemsum"><p>

                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
            <div class="clear"></div>
        </div>
    <?php } else { ?>
        <div class="item">
            <img alt="link to html" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

            <div class="title">
                <?php if ($link->target != 0) { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid . '&id=' . $link->id) ?>"
                          target="_blank">
                            <?php echo $link->title ?>&nbsp;<img
                                src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                <?php } else { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid . '&id=' . $link->id) ?>">
                            <?php echo $link->title ?></a></b>
                <?php } ?>

                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>
                    <?php }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) { ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>
                <a> Modify </a>|<a> Delete </a> |<a> Copy </a>


            </div>
            <div class="itemsum"><p>

                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
            <div class="clear"></div>
        </div>
    <?php } ?>

    <?php break; ?>

    <!-- ////////////////// Inline text here //////////////////-->
<?php case 'InlineText': ?>
    <?php $inline = $item[key($item)];
  ?>

    <input type="hidden" id="inlineText-selected-id" value="<?php echo $inline->id?>">
    <?php if ($inline->avail != 0 && $inline->avail == 2 || $inline->startdate < $currentTime && $inline->enddate > $currentTime && $inline->avail == 1) { ?> <!--Hide ends and displays show always-->
        <div class="item">
            <?php $InlineId = $inline->id;?>
            <!--Hide title and icon-->
            <?php if ($inline->title != '##hidden##') {
            $endDate = AppUtility::formatDate($inline->enddate);?>
        <img alt="text item" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
            <div class="title">
                <b><?php echo $inline->title ?></b> <br>
            </div>
            <div class="itemsum">
                    <?php } ?>
                    <?php if($inline->avail == 2) { ?>
                       <?php echo "Showing Always"; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a>'; ?> | <a id = "mark-as-deleted" href="<?php echo AppUtility::getURLFromHome('course', 'course/delete-inline-text?id=' . $inline->id.'&courseId=' .$course->id) ?>">  Delete </a> | <a href="<?php echo AppUtility::getURLFromHome('course', 'course/copy-item?id=' . $inline->id.'&courseId=' .$course->id) ?>"> Copy </a>
                    <?php }
                    else {
                        if($inline->startdate == 0 && $inline->enddate == 2000000000 || $inline->startdate != 0 && $inline->enddate == 2000000000)
                        {
                            echo "Showing until: Always"; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a>'; ?> | <a id = "mark-as-deleted" href="<?php echo AppUtility::getURLFromHome('course', 'course/delete-inline-text?id=' . $inline->id.'&courseId=' .$course->id) ?>">  Delete </a> | <a href="<?php echo AppUtility::getURLFromHome('course', 'course/copy-item?id=' . $inline->id.'&courseId=' .$course->id) ?>"> Copy </a>
                       <?php }
                        else{
                            echo "Showing until: " .$endDate; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a>'; ?> | <a id = "mark-as-deleted" href="<?php echo AppUtility::getURLFromHome('course', 'course/delete-inline-text?id=' . $inline->id.'&courseId=' .$course->id) ?>">  Delete </a> | <a href="<?php echo AppUtility::getURLFromHome('course', 'course/copy-item?id=' . $inline->id.'&courseId=' .$course->id) ?>"> Copy </a>
                        <?php }
                    }
                    ?>
                <p><?php echo $inline->text ?></p>
            </div>
            <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                <ul class="fileattachlist">
                    <li>
                        <a href="/openmath/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                    </li>
                </ul>
            <?php } ?>
        </div>
    <?php } elseif($inline->avail == 0) { ?>
        <div class="item">
            <!--Hide title and icon-->
            <?php if ($inline->title != '##hidden##') {
            $endDate = AppUtility::formatDate($inline->enddate);?>
        <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
            <div class="title">
                <b><?php echo $inline->title ?></b> <br>
            </div>
            <div class="itemsum"><p>
                    <?php  }
                    echo 'Hidden'; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a>'; ?> | <a id = "mark-as-deleted" href="<?php echo AppUtility::getURLFromHome('course', 'course/delete-inline-text?id=' . $inline->id.'&courseId=' .$course->id) ?>">  Delete </a> | <a href="<?php echo AppUtility::getURLFromHome('course', 'course/copy-item?id=' . $inline->id.'&courseId=' .$course->id) ?>"> Copy </a>

                <p><?php echo $inline->text ?></p>
            </div>
            <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                <ul class="fileattachlist">
                    <li>
                        <a href="/openmath/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                    </li>
                </ul>
            <?php } ?>
        </div>
        <div class="clear"></div>
    <?php } else{ ?>
        <div class="item">
            <?php if ($inline->title != '##hidden##') {
            $endDate = AppUtility::formatDate($inline->enddate);?>
            <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
            <div class="title">
                <b><?php echo $inline->title ?></b> <br>
            </div>
            <div class="itemsum"><p>
                    <?php }
                    $startDate = AppUtility::formatDate($inline->startdate);
                    $endDate = AppUtility::formatDate($inline->enddate);
                    echo "Showing " .$startDate. " until " .$endDate; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a> | <a id="mark-as-read" href="#"> Delete </a> | <a href=""> Copy </a>';?>
            </div>
        </div>
    <?php }?>
    <?php break; ?>

    <!-- Calender Here-->
<?php case 'Calendar': ?>
    <pre><a onclick="deleteItem('<?php echo $item['Calendar'] ;?>','<?php echo AppConstant::CALENDAR ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a
            href="
            <?php echo AppUtility::getURLFromHome('instructor', 'instructor/manage-events?cid=' . $course->id); ?>">Manage Events</a></pre>
    <div class='calendar'>
        <div id="demo">
        </div>
    </div>
    <?php break; ?>

    <!--  Block here-->
<?php case 'Block': ?>
    <?php $block = $item[key($item)]; ?>
    <?php if ($block['avail'] != 0 && $block['SH'] == 'HO' && $block['startdate'] < $currentTime && $block['enddate'] > $currentTime) { ?>
        <div class=block>
            <?php if (strlen($block['SH']) == 1 || $block['SH'][1] == 'O') { ?>
                <span class=left>
                        <img alt="expand/collapse" style="cursor:pointer;" id="img3"
                             src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"/>
                    </span>
            <?php } elseif (strlen($block['SH']) > 1) { ?>
                <span class=left>
                        <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder2.gif">
                    </span>
            <?php } elseif (strlen($block['SH']) > 1 && $block['SH'][1] == 'T') { ?>
                <span class=left>
                        <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder_tree.png">
                    </span>
            <?php } else { ?>
                <span class=left>
                        <img alt="expand/collapse" style="cursor:pointer;" id="img3"
                             src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"/>
                    </span>
            <?php } ?>
            <div class=title>
                        <span class="right">
                            <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=') ?>">Isolate</a>
                        </span>
                <b><a href="#" onclick="return false;"><?php print_r($block['name']); ?></a></b>
            </div>
        </div>
        <div class=blockitems id="block5">
        <?php if (count($item['itemList'])) { ?>
            <?php foreach ($item['itemList'] as $itemlistKey => $item) { ?>
                <?php switch (key($item)):
                    /*Assessment here*/
                    case 'Assessment': ?>
                        <div class="inactivewrapper "
                             onmouseout="this.className='inactivewrapper'">
                            <?php $assessment = $item[key($item)]; ?>
                            <?php if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) { ?>
                                <div class="item">
                                    <img alt="forum" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/assess.png"/>

                                    <div class="title">
                                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
                                              class="confirmation-require assessment-link"
                                              id="<?php echo $assessment->id ?>"><?php echo $assessment->name ?></a></b>
                                        <input type="hidden" class="confirmation-require"
                                               id="time-limit<?php echo $assessment->id ?>"
                                               name="urlTimeLimit"
                                               value="<?php echo $assessment->timelimit; ?>">
                                        <?php if ($assessment->enddate != 2000000000) { ?>
                                            <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>
                                            <!-- Use Late Pass here-->
                                            <?php if ($students->latepass != 0) { ?>
                                                <?php if ($students->latepass != 0 && (($currentTime - $assessment->enddate) < $course->latepasshrs * 3600)) { ?>
                                                    <a href="<?php echo AppUtility::getURLFromHome('course', 'course/late-pass?id=' . $assessment->id . '&cid=' . $course->id) ?>"
                                                       class="confirmation-late-pass"
                                                       id="<?php echo $assessment->id ?>"> Use
                                                        Late Pass</a>
                                                    <input type="hidden"
                                                           class="confirmation-late-pass"
                                                           id="late-pass<?php echo $assessment->id ?>"
                                                           name="urlLatePass"
                                                           value="<?php echo $students->latepass; ?>">
                                                    <input type="hidden"
                                                           class="confirmation-late-pass"
                                                           id="late-pass-hrs<?php echo $assessment->id ?>"
                                                           name="urlLatePassHrs"
                                                           value="<?php echo $course->latepasshrs; ?>">
                                                <?php } ?>
                                            <?php } else { ?>
                                                <?php echo "<p>You have no late passes remaining.</p>"; ?>
                                            <?php } ?>
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
                                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
                                              class="confirmation-require assessment-link"><?php echo $assessment->name ?></a></b>
                                        <input type="hidden" class="confirmation-require"
                                               name="urlTimeLimit"
                                               value="<?php echo $assessment->timelimit; ?>">
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
                        <?php if ($forum->avail != AppConstant::ZERO_VALUE && $forum->startdate < $currentTime && $forum->enddate > $currentTime) { ?>
                            <?php if ($forum->avail == AppConstant::NUMERIC_ONE && $forum->enddate > $currentTime && $forum->startdate < $currentTime) ?>
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
                        <?php } elseif ($forum->avail == AppConstant::NUMERIC_TWO) { ?>
                            <div class="item">
                                <img alt="forum" class="floatleft"
                                     src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>

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
                        <?php if ($wikis->avail != AppConstant::NUMERIC_ZERO && $wikis->startdate < $currentTime && $wikis->enddate > $currentTime) { ?>
                            <?php if ($wikis->avail == AppConstant::NUMERIC_ONE && $wikis->enddate > $currentTime && $wikis->startdate < $currentTime) ?>
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
                        <?php } elseif ($wikis->avail == AppConstant::NUMERIC_TWO && $wikis->enddate == AppConstant::ALWAYS_TIME) { ?>
                            <div class="item">
                                <img alt="wiki" class="floatleft"
                                     src="<?php echo AppUtility::getHomeURL() ?>img/wiki.png"/>

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
                        <?php if ($link->avail != AppConstant::NUMERIC_ZERO && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
                            <!--Link type : http-->
                            <?php if ((substr($link->text, 0, 4) == 'http')) { ?>
                                <div class="item">
                                    <img alt="link to web" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>

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
                                    <img alt="link to doc" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>

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
                                    <img alt="link to html" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

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
                                    <img alt="link to html" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

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
                        <?php } elseif ($link->avail == AppConstant::NUMERIC_TWO && $link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            <div class="item">
                                <img alt="link to html" class="floatleft"
                                     src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

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
                    <?php case 'InlineText': ?>
                        <?php $inline = $item[key($item)];?>
                        <input type="hidden" id="inlineText-selected-id" value="<?php echo $inline->id?>">
                        <?php if ($inline->avail != 0 && $inline->avail == 2 || $inline->startdate < $currentTime && $inline->enddate > $currentTime && $inline->avail == 1) { ?> <!--Hide ends and displays show always-->
                            <div class="item">
                                <!--Hide title and icon-->
                                <?php if ($inline->title != '##hidden##') {
                                $endDate = AppUtility::formatDate($inline->enddate);?>
                            <img alt="text item" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
                                <div class="title">
                                    <b><?php echo $inline->title ?></b> <br>
                                </div>
                                <div class="itemsum"><p>
                                        <?php } ?>
                                        <?php if($inline->avail == 2) {
                                            echo "Showing Always"; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a> | <a id = "mark-as-delete" href="#"> Delete </a> | <a href="#"> Copy </a>';
                                        }
                                        else {
                                            if($inline->startdate == 0 && $inline->enddate == 2000000000 || $inline->startdate != 0 && $inline->enddate == 2000000000)
                                            {
                                                echo "Showing until: Always"; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a> | <a id = "mark-as-delete" href="#"> Delete </a> | <a href="#"> Copy </a>';
                                            }
                                            else{
                                                echo "Showing until: " .$endDate; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a> | <a id = "mark-as-delete" href="#"> Delete </a> | <a href="#"> Copy </a>';
                                            }
                                        }
                                        ?>
                                    <p><?php echo $inline->text ?></p>
                                </div>
                                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                                    <ul class="fileattachlist">
                                        <li>
                                            <a href="/openmath/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                                        </li>
                                    </ul>
                                <?php } ?>
                            </div>
                        <?php } elseif($inline->avail == 0) { ?>
                            <div class="item">
                                <!--Hide title and icon-->
                                <?php if ($inline->title != '##hidden##') {
                                $endDate = AppUtility::formatDate($inline->enddate);?>
                            <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
                                <div class="title">
                                    <b><?php echo $inline->title ?></b> <br>
                                </div>
                                <div class="itemsum"><p>
                                        <?php  }
                                        echo 'Hidden'; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a> | <a id = "mark-as-delete" href="#"> Delete </a> | <a href="#"> Copy </a>';
                                        ?>

                                    <p><?php echo $inline->text ?></p>
                                </div>
                                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                                    <ul class="fileattachlist">
                                        <li>
                                            <a href="/openmath/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                                        </li>
                                    </ul>
                                <?php } ?>
                            </div>
                            <div class="clear"></div>
                        <?php } else{ ?>
                            <div class="item">
                                <?php if ($inline->title != '##hidden##') {
                                $endDate = AppUtility::formatDate($inline->enddate);?>
                                <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
                                <div class="title">
                                    <b><?php echo $inline->title ?></b> <br>
                                </div>
                                <div class="itemsum"><p>
                                        <?php }
                                        $startDate = AppUtility::formatDate($inline->startdate);
                                        $endDate = AppUtility::formatDate($inline->enddate);
                                        echo "Showing " .$startDate. " until " .$endDate; echo '<a href="' .AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id).'"> Modify  </a> | <a id="mark-as-delete" href="#"> Delete </a> | <a href="#"> Copy </a>';?>
                                </div>
                            </div>
                        <?php }?>
                        <?php break; ?>

                        <!-- Calender Here-->
                    <?php case 'Calendar': ?>
                        <pre><a onclick="deleteItem('<?php echo $item['Calendar'] ;?>','<?php echo AppConstant::CALENDAR ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a
                                href="
            <?php echo AppUtility::getURLFromHome('instructor', 'instructor/manage-events?cid=' . $course->id); ?>">Manage
                                Events</a></pre>
                        <div class='calendar'>
                            <div id="demo">
                            </div>
                        </div>
                        <?php break; ?>
                    <?php endswitch; ?>
            <?php } ?>
        <?php } ?>
        </div>
        <div class="clear"></div>
    <?php } elseif ($block['avail'] == AppConstant::NUMERIC_TWO) { ?>
        <!--Show Always-->
        <div class=block>
            <?php if (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'F') { ?>
                <span class=left>
                <img alt="folder" src=".<?php echo AppUtility::getHomeURL() ?>img/folder2.gif">
            </span>
            <?php } elseif (strlen($block['SH']) > 1 && $block['SH'][1] == 'T') { ?>
                <span class=left>
                <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder_tree.png">
            </span>
            <?php } else { ?>
                <span class=left>
                <img alt="expand/collapse" style="cursor:pointer;" id="img3"
                     src="<?php echo AppUtility::getHomeURL() ?>img/expand.gif"/>
            </span>
            <?php } ?>
            <div class=title>
            <span class="right">
                <a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=') ?>">Isolate</a>
            </span>
                <b><a href="#" onclick="return false;"><?php print_r($block['name']); ?></a></b>
            </div>
        </div>
        <div class=blockitems id="block5">
        <?php if (count($item['itemList'])) { ?>
            <?php foreach ($item['itemList'] as $itemlistKey => $item) { ?>
                <?php switch (key($item)):
                    /*Assessment here*/
                    case 'Assessment': ?>
                        <div class="inactivewrapper "
                             onmouseout="this.className='inactivewrapper'">
                            <?php $assessment = $item[key($item)]; ?>
                            <?php if ($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) { ?>
                                <div class="item">
                                    <div class="icon" style="background-color: #1f0;">?</div>
                                    <div class="title">
                                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
                                              class="confirmation-require assessment-link"
                                              id="<?php echo $assessment->id ?>"><?php echo $assessment->name ?></a></b>
                                        <input type="hidden" class="confirmation-require"
                                               id="time-limit<?php echo $assessment->id ?>"
                                               name="urlTimeLimit"
                                               value="<?php echo $assessment->timelimit; ?>">

                                        <?php if ($assessment->enddate != 2000000000) { ?>
                                            <BR><?php echo 'Due ' . AppUtility::formatDate($assessment->enddate); ?>

                                            <!-- Use Late Pass here-->
                                            <?php if ($students->latepass != AppConstant::NUMERIC_ZERO) { ?>
                                                <?php if ($students->latepass != AppConstant::NUMERIC_ZERO && (($currentTime - $assessment->enddate) < $course->latepasshrs * 3600)) { ?>
                                                    <a href="<?php echo AppUtility::getURLFromHome('course', 'course/late-pass?id=' . $assessment->id . '&cid=' . $course->id) ?>"
                                                       class="confirmation-late-pass"
                                                       id="<?php echo $assessment->id ?>"> Use
                                                        Late Pass</a>
                                                    <input type="hidden"
                                                           class="confirmation-late-pass"
                                                           id="late-pass<?php echo $assessment->id ?>"
                                                           name="urlLatePass"
                                                           value="<?php echo $students->latepass; ?>">
                                                    <input type="hidden"
                                                           class="confirmation-late-pass"
                                                           id="late-pass-hrs<?php echo $assessment->id ?>"
                                                           name="urlLatePassHrs"
                                                           value="<?php echo $course->latepasshrs; ?>">
                                                <?php } ?>
                                            <?php } else { ?>
                                                <?php echo "<p>You have no late passes remaining.</p>"; ?>
                                            <?php } ?>

                                        <?php } ?>
                                    </div>
                                    <div class="itemsum">
                                        <p><?php echo $assessment->summary ?></p>
                                    </div>
                                </div>
                            <?php
                            } elseif ($assessment->enddate < $currentTime && ($assessment->reviewdate != AppConstant::NUMERIC_ZERO) && ($assessment->reviewdate > $currentTime)) {
                                ?>
                                <div class="item">
                                    <div class="icon" style="background-color: #1f0;">?</div>
                                    <div class="title">
                                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
                                              class="confirmation-require assessment-link"><?php echo $assessment->name ?></a></b>
                                        <input type="hidden" class="confirmation-require"
                                               name="urlTimeLimit"
                                               value="<?php echo $assessment->timelimit; ?>">
                                        <?php if ($assessment->reviewdate == AppConstant::ALWAYS_TIME) { ?>
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
                        <?php $forum = $item[key($item)];
                        if ($forum->avail == 2 || $forum->startdate < $currentTime && $forum->enddate > $currentTime && $forum->avail == 1) {?>

                            <div class="item">
                                <!--Hide title and icon-->
                                <?php if ($forum->name != '##hidden##') {
                                $endDate = AppUtility::formatDate($forum->enddate);?>
                                <img alt="text item" class="floatleft" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>
                                <div class="title">
                                    <b><?php echo $forum->name ?></b> <br>
                                </div>
                                <div class="itemsum"><p>
                                        <?php } ?>

                                        <?php if($forum->avail == 2) {
                                            echo "Showing Always"; echo '<a href="#"> Modify  </a> | <a href="#"> Delete </a> | <a href="#"> Copy </a><br>';
                                        }
                                        else {
                                            if($forum->startdate == 0 && $forum->enddate == 2000000000 || $forum->startdate != 0 && $forum->enddate == 2000000000)
                                            {
                                                echo "Showing until: Always"; echo '<a href="#"> Modify  </a> | <a href="#"> Delete </a> | <a href="#"> Copy </a><br>';
                                            }
                                            else{
                                                echo "Showing until: " .$endDate; echo '<a href="#"> Modify  </a> | <a href="#"> Delete </a> | <a href="#"> Copy </a><br>';
                                            }
                                        }
                                        $duedates = "";
                                        if ($forum->postby > $currentTime && $forum->postby != 2000000000) {
                                            echo('New Threads due '), AppUtility::formatdate($forum->postby).".";
                                        }
                                        if ($forum->replyby > $currentTime && $forum->replyby != 2000000000) {
                                            echo(' Replies due '), AppUtility::formatdate($forum->replyby).".";
                                        }
                                        ?>
                                    <p><?php echo $forum->description ?></p>
                                </div>
                            </div>

                        <?php } elseif($forum->avail == 0) { ?>
                            <div class="item">
                                <!--Hide title and icon-->
                                <?php if ($forum->name != '##hidden##') {
                                $endDate = AppUtility::formatDate($forum->enddate);?>
                                <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>
                                <div class="title">
                                    <b><?php echo $forum->name ?></b> <br>
                                </div>
                                <div class="itemsum"><p>
                                        <?php
                                        echo 'Hidden'; echo '<a href="#"> Modify  </a> | <a href="#"> Delete </a> | <a href="#"> Copy </a>';
                                        } ?>

                                    <p><?php echo $forum->description ?></p>
                                </div>
                            </div>
                        <?php } else{ ?>
                            <div class="item">
                                <?php if ($forum->name != '##hidden##') {
                                $endDate = AppUtility::formatDate($forum->enddate);?>
                                <img alt="text item" class="floatleft faded" src="<?php echo AppUtility::getHomeURL() ?>img/forum.png"/>
                                <div class="title">
                                    <b><?php echo $forum->name ?></b> <br>
                                </div>
                                <div class="itemsum"><p>
                                        <?php }
                                        $startDate = AppUtility::formatDate($forum->startdate);
                                        $endDate = AppUtility::formatDate($forum->enddate);
                                        echo "Showing " .$startDate. " until " .$endDate; echo '<a href="#"> Modify  </a> | <a href="#"> Delete </a> | <a href="#"> Copy </a>';?>
                                </div>

                            </div>
                        <?php }?>
                        <?php break; ?>

                        <!-- ////////////////// Wiki here //////////////////-->

                    <?php case 'Wiki': ?>
                        <?php $wikis = $item[key($item)]; ?>
                        <?php if ($wikis->avail != AppConstant::NUMERIC_ZERO && $wikis->startdate < $currentTime && $wikis->enddate > $currentTime) { ?>
                            <?php if ($wikis->avail == AppConstant::NUMERIC_ONE && $wikis->enddate > $currentTime && $wikis->startdate < $currentTime) ?>
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

                        <?php } elseif ($wikis->avail == AppConstant::NUMERIC_TWO && $wikis->enddate == AppConstant::ALWAYS_TIME) { ?>
                            <div class="item">
                                <img alt="wiki" class="floatleft"
                                     src="<?php echo AppUtility::getHomeURL() ?>img/wiki.png"/>

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
                        <?php if ($link->avail != AppConstant::NUMERIC_ONE && $link->startdate < $currentTime && $link->enddate > $currentTime) { ?>
                            <!--Link type : http-->
                            <?php if ((substr($link->text, 0, 4) == 'http')) { ?>
                                <div class="item">
                                    <img alt="link to web" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>

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
                                    <img alt="link to doc" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>

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
                                    <img alt="link to html" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

                                    <div class="title">
                                        <!--open on new window or on same window-->
                                        <?php if ($link->target != AppConstant::NUMERIC_ZERO) { ?>
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
                                    <img alt="link to html" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

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
                        <?php } elseif ($link->avail == AppConstant::NUMERIC_TWO && $link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            <div class="item">
                                <img alt="link to html" class="floatleft"
                                     src="<?php echo AppUtility::getHomeURL() ?>img/html.png"/>

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
                    <?php case 'InlineText': ?>
                        <?php $inline = $item[key($item)]; ?>
                        <?php if ($inline->avail != AppConstant::NUMERIC_ZERO && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
                            <div class="item">
                                <!--Hide title and icon-->
                                <?php if ($inline->title != '##hidden##') { ?>
                                    <img alt="text item" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
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
                        <?php } elseif ($inline->avail == AppConstant::NUMERIC_TWO) { ?> <!--Hide ends and displays show always-->
                            <div class="item">
                                <!--Hide title and icon-->
                                <?php if ($inline->title != '##hidden##') { ?>
                                    <img alt="text item" class="floatleft"
                                         src="<?php echo AppUtility::getHomeURL() ?>img/inline.png"/>
                                    <div class="title"><b><?php echo $inline->title ?></b>
                                    </div>
                                <?php } ?>
                                <div class="itemsum"><p>

                                    <p><?php echo $inline->text ?></p>
                                </div>
                                <?php foreach ($inline->instrFiles as $key => $instrFile) { ?>
                                    <ul class="fileattachlist">
                                        <li>
                                            <a href="/open-math/files/<?php echo $instrFile->filename ?>"
                                               target="_blank"><?php echo $instrFile->filename ?></a>
                                        </li>
                                    </ul>
                                <?php } ?>
                            </div>
                            <div class="clear"></div>
                        <?php } ?>
                        <?php break; ?>

                        <!-- Calender Here-->

                        <!--                    --><?php //case 'Calendar': ?>
                        <!--                        <div class ='calendar'></div>-->
                        <!--                    --><?php //break; ?>
                    <?php endswitch; ?>
            <?php } ?>
        <?php } ?>
        </div>
        <div class="clear">
        </div>
    <?php } ?> <!--Show always ends-->
    <?php break; ?>

<?php endswitch;
?>

<?php }?>

<?php } ?>
</div>
