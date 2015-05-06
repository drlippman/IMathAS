<?php
use yii\helpers\Html;
use app\components\AppUtility;

?>
<link rel="stylesheet" href="../../../web/css/_leftSide.css"/>
<link rel="stylesheet" href="../../../web/css/assessment.css"/>


<?php
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
?>
<?php echo $this->render('_toolbar'); ?>

<div class="needed">
    <?php echo $this->render('_leftSide'); ?>
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


    <?php foreach($inlineText as $key => $inline) {?>
        <!--Hide functionality-->
    <?php if($inline->avail != 0 && $inline->startdate < $currentTime && $inline->enddate > $currentTime) { ?>
            <div class=item>
                <!--Hide title and icon-->
                <?php if($inline->title != '##hidden##') {?>
                <img alt="text item" class="floatleft" src="/IMathAS/img/inline.png"/>
                    <div class=title><b><?php echo $inline->title ?></b>
                    </div>
                <?php } ?>

                <div class=itemsum><p>

                    <p><?php echo $inline->text ?></p></p>
                 </div>
                <?php foreach($inline->instrFiles as $key => $instrFile) {?>
                    <ul class="fileattachlist">
                        <li><a href="/open-math/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a></li>
                    </ul>
                <?php } ?>
                 </div>
            <?php ?>
            <div class="clear"></div>
        <?php }elseif($inline->avail == 2 ) {?> <!--Hide ends and displays show always-->
            <div class=item>
                <!--Hide title and icon-->
                <?php if($inline->title != '##hidden##') {?>
                    <img alt="text item" class="floatleft" src="/IMathAS/img/inline.png"/>
                    <div class=title><b><?php echo $inline->title ?></b>
                    </div>
                <?php } ?>

                <div class=itemsum><p>

                    <p><?php echo $inline->text ?></p></p>
                </div>
                <?php foreach($inline->instrFiles as $key => $instrFile) {?>
                    <ul class="fileattachlist">
                        <li><a href="/open-math/files/<?php echo $instrFile->filename ?>" target="_blank"><?php echo $instrFile->filename ?></a></li>
                    </ul>
                <?php } ?>
            </div>
            <?php ?>
            <div class="clear"></div>
        <?php } ?>
    <?php } ?> <!--foreach ends-->


    <!-- ////////////////// Block here //////////////////-->

    <?php foreach($blocks as $key => $block) {
    $itemList = unserialize($block->itemorder);
    print_r($block->itemorder);
    ?>

    <?php AppUtility::dump($itemList[0]);?>
        <!-- Hide Block-->
    <?php if($itemList[0]['avail'] != 0 && $itemList[0]['SH'] == 'HO' && $itemList[0]['startdate'] < $currentTime && $itemList[0]['enddate'] > $currentTime){ ?>
    <div class=block>
        <?php if (strlen($itemList[0]['SH'])==1 || $itemList[0]['SH'][1]=='O'){?>
            <span class=left>
                <img alt="expand/collapse" style="cursor:pointer;" id="img3" src="/IMathAS/img/collapse.gif" onClick="toggleblock('3','0-9')" />
                </span>
            <?php }elseif(strlen($itemList[0]['SH']) > 1){?>
                <span class=left>
            <img alt="folder" src="/IMathAS/img/folder2.gif">
        </span>
            <?php }elseif(strlen($itemList[0]['SH']) > 1 && $itemList[0]['SH'][1] == 'T') {?>
                <span class=left>
            <img alt="folder" src="/IMathAS/img/folder_tree.png">
        </span>
            <?php } else { ?>
            <span class=left>
                <img alt="expand/collapse" style="cursor:pointer;" id="img3" src="/IMathAS/img/collapse.gif" onClick="toggleblock('3','0-9')" />
                </span>
            <?php }?>
            <div class=title>
                <span class="right"><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $block->id) ?>">Isolate</a></span>
            <span class=pointer onClick="toggleblock('3','0-1')"><b>
                    <a href="#" onclick="return false;"><?php print_r($itemList[0]['name']);?></a></b></span>
            </div>
        </div>
        <div class=blockitem id="block3">Loading content...</div>

    <?php } elseif($itemList[0]['avail'] == 2 ) {?> <!--Hide block ends-->
                 <!--Show Always-->
        <div class=block>
            <?php if(strlen($itemList[0]['SH']) > 1 && $itemList[0]['SH'][1] == 'F'){?>
                <span class=left>
            <img alt="folder" src="/IMathAS/img/folder2.gif">
                    </span>
            <?php }elseif(strlen($itemList[0]['SH']) > 1 && $itemList[0]['SH'][1] == 'T') {?>
                <span class=left>
            <img alt="folder" src="/IMathAS/img/folder_tree.png">
                    </span>
            <?php } else { ?>
                <span class=left>
                <img alt="expand/collapse" style="cursor:pointer;" id="img3" src="/IMathAS/img/expand.gif" onClick="toggleblock('3','0-9')" />
                </span>
            <?php }?>
            <div class=title>
                <span class="right"><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $block->id) ?>">Isolate</a></span>
            <span class=pointer onClick="toggleblock('3','0-1')"><b>
                    <a href="#" onclick="return false;"><?php print_r($itemList[8]['name']);?></a></b></span>
            </div>
        </div>
        <div class=hidden id="block3">Loading content...</div>

    <?php }?> <!--Show always ends-->

    <?php }?><!--foreach ends-->



<!--Calender here -->

<div class="item">
    <script type="text/javascript">
        var calcallback = "http://localhost/open-math/course/course.php?cid=1";
    </script>
    <div class="floatright">Show <select id="callength" onchange="changecallength(this)">
            <option value="2" >2</option>
            <option value="3" >3</option>
            <option value="4" selected="selected">4</option>
            <option value="5" >5</option>
            <option value="6" >6</option>
            <option value="7" >7</option>
            <option value="8" >8</option>
            <option value="9" >9</option>
            <option value="10" >10</option>
            <option value="11" >11</option>
            <option value="12" >12</option>
            <option value="13" >13</option>
            <option value="14" >14</option>
            <option value="15" >15</option>
            <option value="16" >16</option>
            <option value="17" >17</option>
            <option value="18" >18</option>
            <option value="19" >19</option>
            <option value="20" >20</option>
            <option value="21" >21</option>
            <option value="22" >22</option>
            <option value="23" >23</option>
            <option value="24" >24</option>
            <option value="25" >25</option>
        </select> weeks </div>
    <div class=center>
        <a href="course.php?calpageshift=-1&cid=1">&lt; &lt;</a> Now
        <a href="course.php?calpageshift=1&cid=1">&gt; &gt;</a>
    </div>
    <table class="cal" >
        <script type="text/javascript">
            cid = 1;
            caleventsarr = {"5-3":{date:"Sunday May 3, 2015"},
                "5-4":{date:"Monday May 4, 2015"},
                "5-5":{date:"Tuesday May 5, 2015"},
                "5-6":{date:"Wednesday May 6, 2015"},
                "5-7":{date:"Thursday May 7, 2015"},
                "5-8":{date:"Friday May 8, 2015"},
                "5-9":{date:"Saturday May 9, 2015"},
                "5-10":{date:"Sunday May 10, 2015"},
                "5-11":{date:"Monday May 11, 2015"},
                "5-12":{date:"Tuesday May 12, 2015"},
                "5-13":{date:"Wednesday May 13, 2015"},
                "5-14":{date:"Thursday May 14, 2015"},
                "5-15":{date:"Friday May 15, 2015"},
                "5-16":{date:"Saturday May 16, 2015"},
                "5-17":{date:"Sunday May 17, 2015"},
                "5-18":{date:"Monday May 18, 2015"},
                "5-19":{date:"Tuesday May 19, 2015"},
                "5-20":{date:"Wednesday May 20, 2015"},
                "5-21":{date:"Thursday May 21, 2015"},
                "5-22":{date:"Friday May 22, 2015"},
                "5-23":{date:"Saturday May 23, 2015"},
                "5-24":{date:"Sunday May 24, 2015"},
                "5-25":{date:"Monday May 25, 2015"},
                "5-26":{date:"Tuesday May 26, 2015"},
                "5-27":{date:"Wednesday May 27, 2015"},
                "5-28":{date:"Thursday May 28, 2015"},
                "5-29":{date:"Friday May 29, 2015"},
                "5-30":{date:"Saturday May 30, 2015"}};
        </script>
        <thead>
        <tr>
            <th>Sunday</th>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
            <th>Saturday</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td id="5-3" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1430634600" class="caldl">May 3</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-4" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1430721000" class="caldl">4</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-5" onclick="showcalcontents(this)" class="today">
                <div class="td">
                    <span class=day>5</span>
                    <div class=center></div></div>
            </td>
            <td id="5-6" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1430893800" class="caldl">6</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-7" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1430980200" class="caldl">7</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-8" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431066600" class="caldl">8</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-9" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431153000" class="caldl">9</a>
                    </span>
                    <div class=center></div></div>
            </td>
        </tr>
        <tr>
            <td id="5-10" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431239400" class="caldl">10</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-11" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431325800" class="caldl">11</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-12" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431412200" class="caldl">12</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-13" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431498600" class="caldl">13</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-14" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431585000" class="caldl">14</a>
                    </span>
                    <div class=center>

                    </div></div>
            </td>
            <td id="5-15" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431671400" class="caldl">15</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-16" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431757800" class="caldl">16</a>
                    </span>
                    <div class=center></div></div>
            </td>
        </tr>
        <tr>
            <td id="5-17" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431844200" class="caldl">17</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-18" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1431930600" class="caldl">18</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-19" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432017000" class="caldl">19</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-20" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432103400" class="caldl">20</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-21" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432189800" class="caldl">21</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-22" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432276200" class="caldl">22</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-23" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432362600" class="caldl">23</a>
                    </span>
                    <div class=center></div></div>
            </td>
        </tr>
        <tr>
            <td id="5-24" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432449000" class="caldl">24</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-25" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432535400" class="caldl">25</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-26" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432621800" class="caldl">26</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-27" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432708200" class="caldl">27</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-28" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432794600" class="caldl">28</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-29" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432881000" class="caldl">29</a>
                    </span>
                    <div class=center></div></div>
            </td>
            <td id="5-30" onclick="showcalcontents(this)" >
                <div class="td">
                    <span class=day>
                        <a href="course.php?cid=1&calstart=1432967400" class="caldl">30</a>
                    </span>
                    <div class=center></div></div>
            </td>
        </tr>
        </tbody>
    </table>
    <div style="margin-top: 10px; padding:10px; border:1px solid #000;">
        <span class=right>
            <a href="#" onclick="showcalcontents(1430634600000); return false;"/>Show all</a>
        </span>
        <div id="caleventslist">

        </div><div class="clear"></div></div>
    <script>
        showcalcontents(document.getElementById('5-5'));
    </script>
    <div class="clear"></div></div>