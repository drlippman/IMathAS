<?php
namespace app\components;
use \yii\base\Component;
use app\components\AppUtility;
use app\components\AppConstant;

class CourseItemsUtility extends Component
{
public $cnt = 0;
    public static  function AddAssessment($assessment,$item,$course,$currentTime,$parent)
    {
         $assessment = $item[key($item)];

if ($assessment->enddate >= $currentTime && $assessment->startdate >= $currentTime) {
?>
<div class="item">
    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
<div class="title">
<b>
    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>" class="confirmation-require assessment-link"
       id="<?php echo $assessment->id ?>"><?php echo ucfirst($assessment->name) ?></a>
</b>
<div class="floatright">
    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
    <ul class=" select1 dropdown-menu selected-options">
        <li><a class="question" href="#"><?php AppUtility::t('Questions');?></a></li>
        <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$assessment->id . '&cid=' . $course->id . '&block=0') ?>"><?php AppUtility::t('Setting');?></a></li>
        <li><a id="delete" href="#" onclick="deleteItem('<?php echo $assessment->id ;?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
        <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
    </ul>
</div>

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
    <?php if ($assessment->allowlate != AppConstant::NUMERIC_ZERO) { ?>
        <span title="Late Passes Allowed">LP</span>
    <?php
    } ?>
<?php  } else if ($assessment->enddate <= $currentTime && $assessment->startdate <= $currentTime && $assessment->startdate != 0) {
?>
<div class="item">
<img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>

<div class="title">
<b>
    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
       class="confirmation-require assessment-link"
       id="<?php echo $assessment->id ?>"><?php echo ucfirst($assessment->name) ?></a>
</b>
<div class="floatright">
    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
    <ul class=" select1 dropdown-menu selected-options">
        <li><a class="question" href="#"><?php AppUtility::t('Questions');?></a></li>
        <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$assessment->id . '&cid=' . $course->id . '&block=0') ?>"><?php AppUtility::t('Setting');?></a></li>
        <li><a id="delete" href="#" onclick="deleteItem('<?php echo $assessment->id ;?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
        <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
    </ul>
</div>

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
    <?php if ($assessment->allowlate != AppConstant::NUMERIC_ZERO) { ?>
        <span title="Late Passes Allowed">LP</span>
    <?php
    } ?>

<?php if ($assessment->reviewdate > AppConstant::NUMERIC_ZERO) { ?>
    <br>This assessment is in review mode - no scores will be saved
<?php }
}else if ($assessment->startdate >= 0 || $assessment->enddate == AppConstant::ALWAYS_TIME) {
?>
<div class="item">
    <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>

    <div class="title">
        <b>
            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-assessment?id=' . $assessment->id . '&cid=' . $course->id) ?>"
               class="confirmation-require assessment-link"
               id="<?php echo $assessment->id ?>"><?php echo ucfirst($assessment->name) ?></a>
        </b>
        <div class="floatright">
            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
            <ul class=" select1 dropdown-menu selected-options">
                <li><a class="question" href="#"><?php AppUtility::t('Questions');?></a></li>
                <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$assessment->id . '&cid=' . $course->id . '&block=0') ?>"><?php AppUtility::t('Setting');?></a></li>
                <li><a id="delete" href="#" onclick="deleteItem('<?php echo $assessment->id ;?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
            </ul>
        </div>

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


        <?php if ($assessment->allowlate != AppConstant::NUMERIC_ZERO) { ?>
             <span title="Late Passes Allowed">LP</span>
            <?php
        } ?>

        <?php if ($assessment->startdate >= 0 && $assessment->enddate < $currentTime && $assessment['avail'] != AppConstant::NUMERIC_ZERO && $assessment->reviewdate != AppConstant::NUMERIC_ZERO) { ?>

            <br> This assessment is in review mode - no scores will be saved
        <?php } ?>

        <?php }   ?>
    </div>
    <div class="itemsum">
        <p><?php echo $assessment->summary ?></p>
    </div>
</div>
        <?php }


    public static function AddForum($item,$course,$currentTime,$parent)
    {

        $forum = $item[key($item)];
        if ($forum->avail == 2 || $forum->startdate < $currentTime && $forum->enddate > $currentTime && $forum->avail == 1) {?>

            <div class="item">
                <!--Hide title and icon-->
                <?php if ($forum->name != '##hidden##') {
                $endDate = AppUtility::formatDate($forum->enddate);?>
                <img alt="text item" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                <div class="title">
                    <b><a><?php echo ucfirst($forum->name) ?></a></b>
                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-forum?id=' . $forum->id . '&cid=' . $course->id) ?>"><?php AppUtility::t('Modify');?></a></li>
                            <li><a id="delete" href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                            <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                        </ul>
                    </div>
                    <br>
                </div>
                <div class="itemsum">
                    <?php } ?>

                    <?php if($forum->avail == 2) { ?>
                        <?php echo "Showing Always"; ?>
                    <?php  }
                    else {
                        if($forum->startdate == 0 && $forum->enddate == 2000000000 || $forum->startdate != 0 && $forum->enddate == 2000000000)
                        {
                            echo "Showing until: Always"; ?>
                        <?php   }
                        else{
                            echo "Showing until: " .$endDate;?> <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-forum?id=' . $forum->id . '&cid=' . $course->id) ?>"> Modify  </a> | <a href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"> Delete </a> | <a href="#"> Copy </a><br>
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
                <img alt="assess" class="floatleft faded item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                <div class="title">
                    <b><a><?php echo ucfirst($forum->name) ?></a></b>
                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-forum?id=' . $forum->id . '&cid=' . $course->id) ?>"><?php AppUtility::t('Modify');?></a></li>
                            <li><a id="delete" href="#" onclick="deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                            <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                        </ul>
                    </div>
                    <br>
                </div>
                <div class="itemsum"><p>
                        <?php
                        echo 'Hidden'; ?>
                        <?php
                        } ?>

                    <p><?php echo $forum->description ?></p>
                </div>
            </div>
        <?php } else{ ?>
            <div class="item">
                <?php if ($forum->name != '##hidden##') {
                $endDate = AppUtility::formatDate($forum->enddate);?>
                <img alt="assess" class="floatleft faded item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>                <div class="title">
                    <b><a><?php echo ucfirst($forum->name) ?></a></b> <br>
                </div>
                <div class="itemsum"><p>
                        <?php }
                        $startDate = AppUtility::formatDate($forum->startdate);
                        $endDate = AppUtility::formatDate($forum->enddate);
                        echo "Showing " .$startDate. " until " .$endDate; ?>
                </div>

            </div>
        <?php }?>

    <?php }

     public static function AddWiki($item,$course,$parent)
     {
         $wikis = $item[key($item)]; ?>
         <?php $endDateOfWiki = AppUtility::formatDate($wikis['enddate'], 'm/d/y');
         ?>
         <?php if ($wikis->avail == AppConstant::NUMERIC_ZERO) { ?>

         <div class="item">
             <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>

             <div class="title">
                 <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                         <?php echo ucfirst($wikis->name) ?></a></b>
                 <div class="floatright">
                     <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                     <ul class=" select1 dropdown-menu selected-options">
                         <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id)?>"><?php AppUtility::t('Modify');?></a></li>
                         <li><a id="delete" href="#" onclick="deleteItem('<?php echo $wikis->id; ?>','<?php echo AppConstant::WIKI?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                         <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                     </ul>
                 </div>

                 <br><span>Hidden</span>

             </div>
             <div class="itemsum">
                 <p>

                 <p>&nbsp;<?php echo $wikis->description ?></p></p>
             </div>
             <div class="clear"></div>
         </div>
     <?php } elseif ($wikis->avail == AppConstant::NUMERIC_ONE) { ?>
         <div class="item">
             <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>

             <div class="title">
                 <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                         <?php echo ucfirst($wikis->name) ?></a></b>
                 <div class="floatright">
                     <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                     <ul class=" select1 dropdown-menu selected-options">
                         <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id)?>"><?php AppUtility::t('Modify');?></a></li>
                         <li><a id="delete" href="#" onclick="deleteItem('<?php echo $wikis->id; ?>','<?php echo AppConstant::WIKI?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                         <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                     </ul>
                 </div>
                 <br><span> Showing until:</span>
                 <?php if ($wikis['enddate'] < AppConstant::ALWAYS_TIME) {
                     echo $endDateOfWiki;
                 } else { ?>
                     Always
                 <?php } ?>

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
             <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
             <div class="title">
                 <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                         <?php echo ucfirst($wikis->name) ?></a></b>
                 <div class="floatright">
                     <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                     <ul class=" select1 dropdown-menu selected-options">
                         <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id)?>"><?php AppUtility::t('Modify');?></a></li>
                         <li><a id="delete" href="#" onclick="deleteItem('<?php echo $wikis->id; ?>','<?php echo AppConstant::WIKI?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                         <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                     </ul>
                 </div>
                 <br><span>Showing Always</span>

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

  <?php }

        public static function AddLink($item,$currentTime,$parent,$course)
        {
            $link = $item[key($item)]; ?>
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
                        <b><a href="<?php echo $text ?>"><?php echo ucfirst($link->title); ?></a></b>
                    <?php } ?>
                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-link?id=' . $link->id . '&cid=' . $course->id) ?>"><?php AppUtility::t('Modify');?></a></li>
                            <li><a id="delete" href="#" onclick="deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                            <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                        </ul>
                    </div>

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
                        echo '<a href="' . $alink . '">' . ucfirst($link->title) . '</a>';
                    } ?>
                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-link?id=' . $link->id . '&cid=' . $course->id) ?>"><?php AppUtility::t('Modify');?></a></li>
                            <li><a id="delete" href="#" onclick="deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                            <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                        </ul>
                    </div>

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
                                <?php echo ucfirst($link->title) ?></a></b>
                    <?php } ?>
                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-link?id=' . $link->id . '&cid=' . $course->id) ?>"><?php AppUtility::t('Modify');?></a></li>
                            <li><a id="delete" href="#" onclick="deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                            <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                        </ul>
                    </div>

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
                                <?php echo ucfirst($link->title) ?>&nbsp;<img
                                    src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                    <?php } else { ?>
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid . '&id=' . $link->id) ?>">
                                <?php echo $link->title ?></a></b>
                    <?php } ?>
                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-link?id=' . $link->id . '&cid=' . $course->id) ?>"><?php AppUtility::t('Modify');?></a></li>
                            <li><a id="delete" href="#" onclick="deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                            <li><a id="copy" href="#" ><?php AppUtility::t('Copy');?></a></li>
                        </ul>
                    </div>
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

                </div>
                <div class="itemsum"><p>

                    <p><?php echo $link->summary ?>&nbsp;</p></p></div>
                <div class="clear"></div>
            </div>
        <?php } ?>
        <?php }

        public static function AddInlineText($item,$currentTime,$course,$parent)
        {

            $inline = $item[key($item)];
            ?>

            <input type="hidden" id="inlineText-selected-id" value="<?php echo $inline->id?>">
            <?php if ($inline->avail != 0 && $inline->avail == 2 || $inline->startdate < $currentTime && $inline->enddate > $currentTime && $inline->avail == 1) { ?> <!--Hide ends and displays show always-->
            <div class="item">
                <?php $InlineId = $inline->id;?>
                <!--Hide title and icon-->
                <?php if ($inline->title != '##hidden##') {
                $endDate = AppUtility::formatDate($inline->enddate);?>
            <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                <div class="title">
                    <b><?php echo ucfirst($inline->title)?></b>

                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown" href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('course', 'course/modify-inline-text?id=' . $inline->id.'&courseId=' .$course->id)?>"><?php AppUtility::t('Modify');?></a></li>
                            <li><a id="delete" href="#" onclick="deleteItem('<?php echo $inline->id; ?>','<?php echo AppConstant::INLINE_TEXT?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Delete');?></a></li>
                            <li><a id="copy" href="#" onclick="copyItem('<?php echo $item['inline']['id']; ?>','<?php echo AppConstant::INLINE_TEXT?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')"><?php AppUtility::t('Copy');?></a></li>
                        </ul>
                    </div>

                </div>
                <div class="itemsum">
                    <?php } ?>
                    <?php if($inline->avail == 2) { ?>
                        <?php echo "Showing Always";
                    }
                    else {
                        if($inline->startdate == 0 && $inline->enddate == 2000000000 || $inline->startdate != 0 && $inline->enddate == 2000000000)
                        {
                            echo "Showing until: Always";
                         }
                        else{
                            echo "Showing until: " .$endDate;
                         }
                    }
                    ?>
                    <p><?php echo $inline->text ?></p>
                </div>
                <?php if($inline->instrFiles!= 0){
                foreach ($inline->instrFiles as $key => $instrFile) { ?>
                    <ul class="fileattachlist">
                        <li>
                            <a href="/openmath/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                        </li>
                    </ul>
                <?php  }
                } ?>


            </div>
        <?php } elseif($inline->avail == 0) { ?>
            <div class="item">
                <!--Hide title and icon-->
                <?php if ($inline->title != '##hidden##') {
                $endDate = AppUtility::formatDate($inline->enddate);?>
            <img alt="assess" class="floatleft faded item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>

                <div class="title">
                    <b><?php echo ucfirst($inline->title) ?></b>
                    <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/><br>
                </div>
                <div class="itemsum"><p>
                        <?php  }
                        echo 'Hidden';
                        ?>
                    <p><?php echo $inline->text ?></p>
                </div>
                <?php if($inline->instrFiles!= 0){
                foreach ($inline->instrFiles as $key => $instrFile) { ?>
                    <ul class="fileattachlist">
                        <li>
                            <a href="/openmath/files/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                        </li>
                    </ul>
                <?php }
                } ?>
            </div>
            <div class="clear"></div>
        <?php } else{ ?>
            <div class="item">
                <?php if ($inline->title != '##hidden##') {
                $endDate = AppUtility::formatDate($inline->enddate);?>
                <img alt="assess" class="floatleft faded item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>

                <div class="title">
                    <b><?php echo ucfirst($inline->title) ?></b>
                    <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL()?>img/courseSettingItem.png"/><br>
                </div>
                <div class="itemsum"><p>
                        <?php }
                        $startDate = AppUtility::formatDate($inline->startdate);
                        $endDate = AppUtility::formatDate($inline->enddate);
                        echo "Showing " .$startDate. " until " .$endDate; ?>
                </div>
            </div>
        <?php }?>

       <?php }

        public static function  AddCalendar($item,$parent,$course)
        {
            ?>
            <div class="item" style="padding-bottom: 15px; padding-right: 15px">
            <pre><a href="#" onclick="deleteItem('<?php echo $item['Calendar'] ;?>','<?php echo AppConstant::CALENDAR ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a
                    href="
            <?php echo AppUtility::getURLFromHome('instructor', 'instructor/manage-events?cid=' . $course->id); ?>">Manage Events</a></pre>
            <div class='calendar'>
            </div>
            </div>
  <?php }

        public function DisplayWholeBlock($item,$currentTime,$assessment,$course,$parent,$cnt)
        {
                             $block = $item[key($item)];?>
                             <input type="hidden" id="SH" value="<?php echo $block['SH']?>" >
                             <input type="hidden" id="id" value="<?php echo $block['id']?>" >
                             <?php $StartDate = AppUtility::formatDate($block['startdate']);?>
                             <?php $endDate = AppUtility::formatDate($block['enddate']);?>

                            <?php if ($block['avail'] == 1){  ?>
                            <div class=block>
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
                                <div class="title">
                                <span class="pointer" onclick="#">
                                <b>
                                <a href="#" onclick="return false;"><?php echo $block['name']?></a>
                                        <?php if($block['newflag'] == 1){?>
                                <span class="red">New</span>

                                <?php }?>
                                </b>
                                </span>

                                <span class="instrdates" style="font-family: "Times New Roman", Times, serif">
                                    <?php if($block['SH'] == 'HC'){$title = 'Showing Collapsed';}
                                    else if($block['SH'] == 'HO'){$title = 'Showing Expanded';}
                                    elseif($block['SH'] == 'HF'){$title = 'Showing as Folder';}elseif($block['SH'] == 'HT'){$title = 'Showing as TreeReader';}
                                    elseif($block['SH'] == 'SO'){$title = 'Showing Expanded';}elseif($block['SH'] == 'SC'){$title = 'Showing Collapsed';}
                                    elseif($block['SH'] == 'SF'){$title = 'Showing as Folder';}elseif($block['SH'] == 'ST'){$title = 'Showing as TreeReader';}?>
                                    <?php if($block['startdate'] == AppConstant::NUMERIC_ZERO && $block['enddate'] == AppConstant::ALWAYS_TIME){$StartDate = 'ALways'; $endDate = 'ALways';}?>
                                    <br><?php echo $title?>   <?php echo $StartDate?> until <?php echo $endDate?></span>
                                 <span class="instronly">
                                     <?php if($block['SH'] == 'HT' ||$block['SH'] == 'ST'){?>
                                 <a href="#">Edit Content</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>">Modify</a> | <a href="#" onclick="deleteItem('<?php echo $parent.'-'.$cnt ?>','<?php echo AppConstant::BLOCK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a href="#">Copy</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/new-flag?cid='.$course->id.'&newflag='.$parent.'-'.$cnt)?>">NewFlag</a>
                                    <?php }else{?>
                                    <a href="#">Isolate</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>">Modify</a> | <a href="#" onclick="deleteItem('<?php echo $parent.'-'.$cnt ?>','<?php echo AppConstant::BLOCK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a href="#">Copy</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/new-flag?cid='.$course->id.'&newflag='.$parent.'-'.$cnt)?>">NewFlag</a>
                                <?php }?>
                                </span>
                                </div>
                            </div>
                            <div class=blockitems id="block5<?php echo $block['id']?>">
                                <?php if (count($item['itemList'])) { ?>
                                    <?php foreach ($item['itemList'] as $itemlistKey => $item) {?>
                                    <?php switch (key($item)):

                                            /*Assessment here*/
                                            case 'Assessment': ?>
                                                <div class="inactivewrapper "
                                                     onmouseout="this.className='inactivewrapper'">
                                                    <?php $this->AddAssessment($assessment,$item,$course,$currentTime,$parent); ?>
                                                </div>
                                                <?php break; ?>

                                                <!-- Forum here-->
                                            <?php case 'Forum': ?>
                                                <?php $this->AddForum($item,$course,$currentTime,$parent); ?>
                                                <?php break; ?>

                                                <!-- ////////////////// Wiki here //////////////////-->
                                            <?php case 'Wiki': ?>
                                                <?php $this->AddWiki($item,$course,$parent); ?>
                                                <?php break; ?>

                                                <!-- ////////////////// Linked text here //////////////////-->
                                            <?php case 'LinkedText': ?>
                                                <?php $this->AddLink($item,$currentTime,$parent,$course);?>
                                                <?php break; ?>

                                                <!-- ////////////////// Inline text here //////////////////-->
                                            <?php case 'InlineText': ?>
                                                <?php $this->AddInlineText($item,$currentTime,$course,$parent);?>
                                                <?php break; ?>

                                                <!-- Calender Here-->
                                            <?php case 'Calendar': ?>
                                                <?php $this->AddCalendar($item,$parent,$course);?>
                                                <?php break; ?>
                                            <?php case '':?>
                                                  <?php

                                                       //this->DisplayWholeBlock($block['items'],$currentTime,$assessment,$course,$parent,$cnt);
                                                ?>
                                                 <?php break; ?>
                                            <?php endswitch; ?>
                                    <?php } ?>
                                    <?php } ?>
                             <?php $this->AddItemsDropDown();?>
                            </div>
                            <div class="clear"></div>
                        <?php }elseif(($block['avail']) == AppConstant::NUMERIC_TWO){?>
                            <!--Show Always-->
                        <div class=block>
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
                <div class="title">
                                <span class="pointer" onclick="#">
                                <b>
                                    <a href="#" onclick="return false;"><?php echo $block['name']?></a>
                                    <?php if($block['newflag'] == 1){?>
                                        <span class="red">New</span>
                                    <?php }?>
                                </b>
                                </span>

                    <span class="instrdates" style="font-family: "Times New Roman", Times, serif">
                    <?php if($block['SH'] == 'HC'){$title = 'Showing Collapsed';}
                    else if($block['SH'] == 'HO'){$title = 'Showing Expanded';}
                    elseif($block['SH'] == 'HF'){$title = 'Showing as Folder';}elseif($block['SH'] == 'HT'){$title = 'Showing as TreeReader';}
                    elseif($block['SH'] == 'SO'){$title = 'Showing Expanded';}elseif($block['SH'] == 'SC'){$title = 'Showing Collapsed';}
                    elseif($block['SH'] == 'SF'){$title = 'Showing as Folder';}elseif($block['SH'] == 'ST'){$title = 'Showing as TreeReader';}?>
                    <br><?php echo $title?> Always</span>
                                 <span class="instronly">
                                <a href="#">Isolate</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>">Modify</a> | <a href="#" onclick="deleteItem('<?php echo $parent.'-'.$cnt ?>','<?php echo AppConstant::BLOCK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a href="#">Copy</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/new-flag?cid='.$course->id.'&newflag='.$parent.'-'.$cnt)?>">NewFlag</a>
                                </span>
                </div>
            </div>
            <div class=blockitems id="block5<?php echo $block['id']?>">
                <?php if (count($item['itemList'])) { ?>
                    <?php foreach ($item['itemList'] as $itemlistKey => $item) {?>
                        <?php switch (key($item)):

                            /*Assessment here*/
                            case 'Assessment': ?>
                                <div class="inactivewrapper "
                                     onmouseout="this.className='inactivewrapper'">
                                    <?php $this->AddAssessment($assessment,$item,$course,$currentTime,$parent); ?>
                                </div>
                                <?php break; ?>

                                <!-- Forum here-->
                            <?php case 'Forum': ?>
                                <?php $this->AddForum($item,$course,$currentTime,$parent); ?>
                                <?php break; ?>

                                <!-- ////////////////// Wiki here //////////////////-->
                            <?php case 'Wiki': ?>
                                <?php $this->AddWiki($item,$course,$parent); ?>
                                <?php break; ?>

                                <!-- ////////////////// Linked text here //////////////////-->
                            <?php case 'LinkedText': ?>
                                <?php $this->AddLink($item,$currentTime,$parent,$course);?>
                                <?php break; ?>

                                <!-- ////////////////// Inline text here //////////////////-->
                            <?php case 'InlineText': ?>
                                <?php $this->AddInlineText($item,$currentTime,$course,$parent);?>
                                <?php break; ?>

                                <!-- Calender Here-->
                            <?php case 'Calendar': ?>
                                <?php $this->AddCalendar($item,$parent,$course);?>
                                <?php break; ?>
                            <?php case '':?>
                                <?php

                                //$this->DisplayWholeBlock($block['items'],$currentTime,$assessment,$course,$parent,$cnt);
                                ?>
                                <?php break; ?>
                            <?php endswitch; ?>
                    <?php } ?>
                <?php } ?>
                <?php $this->AddItemsDropDown();?>
            </div>
            <div class="clear"></div>
            <?php }else {  ?>
                             <input type="hidden" id="isHidden" value="1">
                            <div class=block>
                                <?php if (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'F') { ?>
                                <span class=left>
                                <img alt="folder"  src="<?php echo AppUtility::getHomeURL() ?>img/folder2.gif">
                                </span>
                                <?php } elseif (strlen($block['SH']) > 1 && $block['SH'][1] == 'T') { ?>
                                <span class=left>
                                 <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder_tree.png">
                                 </span><?php } else { ?>
                                 <span class=left>
                                 <img alt="expand/collapse" style="cursor:pointer;" id="img<?php echo $block['id']?>" onclick="xyz(this,<?php echo $block['id']?>)" src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"/>
                                 </span><?php } ?>
                                <div class="title">
                                    <span class="pointer" onclick="#">
                                    <b>
                                    <a href="#" onclick="return false;"><?php echo $block['name']?></a>
                                        <?php if($block['newflag'] == 1){?>
                                            <span class="red">New</span>
                                        <?php }?>
                                    </b>
                                    </span>
                                    <span class="instrdates">
                                    <br>Hidden</span><span class="instronly">
                                    <a href="#">Isolate</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>">Modify</a> | <a href="#" onclick="deleteItem('<?php echo $parent.'-'.$cnt ?>','<?php echo AppConstant::BLOCK?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a href="#">Copy</a> | <a href="<?php echo AppUtility::getURLFromHome('block','block/new-flag?cid='.$course->id.'&newflag='.$parent.'-'.$cnt)?>">NewFlag</a>
                                    </span>
                                </div>
                            </div>
                        <div class=blockitems id="block5<?php echo $block['id']?>">
                            <?php if (count($item['itemList'])) { ?>
                                <?php foreach ($item['itemList'] as $itemlistKey => $item) { ?>
                                    <?php switch (key($item)):
                                        case 'Assessment': ?>
                                            <div class="inactivewrapper "
                                                 onmouseout="this.className='inactivewrapper'">
                                                <?php $this->AddAssessment($assessment,$item,$course,$currentTime,$parent); ?>
                                            </div>
                                            <?php break; ?>
                                            <!-- Forum here-->
                                        <?php case 'Forum': ?>
                                            <?php $this->AddForum($item,$course,$currentTime,$parent); ?>
                                            <?php break; ?>

                                            <!-- ////////////////// Wiki here //////////////////-->
                                        <?php case 'Wiki': ?>
                                            <?php $this->AddWiki($item,$course,$parent); ?>
                                            <?php break; ?>

                                            <!-- ////////////////// Linked text here //////////////////-->
                                        <?php case 'LinkedText': ?>
                                            <?php $this->AddLink($item,$currentTime,$parent,$course);?>
                                            <?php break; ?>

                                            <!-- ////////////////// Inline text here //////////////////-->
                                        <?php case 'InlineText': ?>
                                            <?php $this->AddInlineText($item,$currentTime,$course,$parent);?>
                                            <?php break; ?>

                                            <!-- Calender Here-->
                                        <?php case 'Calendar': ?>
                                            <?php $this->AddCalendar($item,$parent,$course);?>
                                            <?php break; ?>
                                        <?php case '':?>
                                            <?php

                                  //          $this->DisplayWholeBlock($block['items'],$currentTime,$assessment,$course,$parent,$cnt);
                                            ?>
                                            <?php break; ?>
                                        <?php endswitch; ?>
                                <?php } ?>
                            <?php } ?>
                            <?php $this->AddItemsDropDown();?>
                        </div>
                        <div class="clear">
                        </div>
      <?php } ?> <!--Show always ends-->
     <?php }

        public static function AddItemsDropDown()
        {
            ?>


            <div class="padding-zero" style="width: 25%">
                <?php  $parent = AppConstant::NUMERIC_ZERO;
                $tb = 't';
                $html = "<select class='form-control padding-zero' name=addtype id=\"addtype$parent-$tb\" onchange=\"additem('$parent','$tb')\" ";
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
        <?php }

  }?>






