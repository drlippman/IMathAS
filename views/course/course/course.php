<?php
use app\components\AppUtility;
use \app\components\ShowItemCourse;
use app\components\AppConstant;
$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
$imasRoot = AppUtility::getURLFromHome('course', 'course/save-quick-reorder?cid='.$course->id);?>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print'/>
<input type="hidden" class="calender-course-id" id="courseIdentity" value="<?php echo $course->id ?>">
<input type="hidden" class="home-path-course" value="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $course->id) ?>">
<input type="hidden" class="web-path" value="<?php echo AppUtility::getHomeURL() ?>">
<input type="hidden" class="calender-course-id" value="<?php echo $course->id?>">
<input type="hidden" class="user-rights" value="<?php echo $myRights?>">

<?php
$studview= $sessionData['stuview'];
if($studview>-1){
    $studview=1;
}
$groupAdmin = $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT;
if($isStudent || ($isTutor && ($user['rights'] != $groupAdmin))&&!$teacherId) {
    ?>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$curBreadcrumb], 'link_url' => [AppUtility::getHomeURL().'site/index']]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $curName ?></div>
            </div>
        </div>
    </div>
    <div class="item-detail-content">
        <?php echo $this->render("_toolbarStudent", ['course' => $course, 'section' => 'course', 'students' => $students, 'userId' => $user, 'isTutor'=> $isTutor]);?>
    </div>
<?php }else if ((($teacherId) || ($isTutor && ($user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT)))) {
    ?>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),$curBreadcrumb], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
    </div>
    <div class = "title-container padding-bottom-two-em">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page word-wrap-break-word"><?php echo $curName; ?></div>
            </div>
            <?php if($user['rights'] >= AppConstant::LIMITED_COURSE_CREATOR_RIGHT){?>
            <div class="pull-left header-btn">
                <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=modify&cid='.$course->id); ?>"
                 class="btn btn-primary pull-right page-settings"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png">&nbsp;Course Settings
                </a>
            </div>
            <?php }?>
        </div>
    </div>
    <div class="item-detail-content">
        <?php echo $this->render("_toolbarTeacher", ['course' => $course, 'section' => 'course']);?>
    </div>
<?php } elseif($teacherId && $backLink) { ?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $curName; ?></div>
        </div>
    </div>
</div>

<?php }
?>
<?php if($user['rights'] == AppConstant::STUDENT_RIGHT){
?>
<div class="tab-content shadowBox student-course-setting">
    <?php } elseif($user['rights'] >= AppConstant::STUDENT_RIGHT) {?>
    <div class="tab-content shadowBox">
    <?php }?>
<?php if($teacherId && !$isStudent || ($isTutor && ($user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT))){
  ?>
    <div class="row course-copy-export col-md-12 col-sm-12 padding-left-right-zero">
        <div class="col-md-2 col-sm-2 course-top-menu">
            <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id);?>"><?php AppUtility::t('Copy Items');?></a>
        </div>
        <div class="col-md-1 col-sm-1 course-top-menu padding-left-zero">
            <a href="#"><?php AppUtility::t('Export');?></a>
        </div>

        <ul class="nav sub-menu col-md-2 col-sm-3">
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
        <div class='btn-group settings col-md-2 col-sm-3 padding-left-zero padding-top-three'>
            <a class='btn btn-primary setting-btn last'
               href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$course->id. '&quickview=off&teachview=1');?>"><i class="fa fa-eye"></i>
                <?php AppUtility::t('Instructor'); ?>
            </a>
            <a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'>
                <span class='fa fa-caret-down'></span>
            </a>
            <ul class='dropdown-menu'>
                <li>
                    <a href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$course->id. '&stuview=1');?>">
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

    <div class="clear-both"></div>
<?php }
?>
    <input type="hidden" class="courseId" value="<?php echo $course->id?>">

<?php if ($isTutor && isset($sessionData['ltiitemtype']) && $sessionData['ltiitemtype'] == 3) {
    $placeinhead .= '<script type="text/javascript">jQuery(function(){jQuery(".instrdates").hide();});</script>';
}

if ($overwriteBody == 1) {
    echo $body;
} else {
    if (isset($teacherId)) {
        ?>
        <script type="text/javascript">
            function moveitem(from,blk) {
                var to = document.getElementById(blk+'-'+from).value;

                if (to != from) {
                    var toopen = '<?php echo $jsAddress1 ?>&block=' + blk + '&from=' + from + '&to=' + to;
                    window.location = toopen;
                }
            }

            function additem(blk,tb) {
                var type = document.getElementById('addtype'+blk+'-'+tb).value;
                if (tb=='BB' || tb=='LB') { tb = 'b';}
                if (type!='') {
                    var toopen = '<?php echo $jsAddress2 ?>/assessment/assessment/add-assessment?block='+blk+'&tb='+tb+'&cid=<?php echo $courseId ?>';
                    window.location = toopen;
                }
            }
        </script>

    <?php
    }
    $blockAddress = AppUtility::getURLFromHome('course', 'course/get-block-items?cid='.$course->id. '&folder=');
    ?>
    <script type="text/javascript">
        var getbiaddr = '<?php echo $blockAddress;?>';
        var oblist = '<?php echo $oblist ?>';
        var plblist = '<?php echo $plblist ?>';
        var cid = '<?php echo $course->id ?>';
    </script>

    <?php
    //check for course layout
    if (isset($CFG['GEN']['courseinclude'])) {
        if ($firstLoad) {
            $courseId = $course->id;
            echo "<script>document.cookie = 'openblocks-$courseId=' + oblist;\n";
            echo "document.cookie = 'loadedblocks-$courseId=0';</script>\n";
        }
        exit;
    }
    ?>

    <?php
    ShowItemCourse::makeTopMenu($studview);
    if (count($items) > 0) {
        if ($quickView =='on' && isset($teacherId)) {
            echo '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;}</style>';
            echo "<script>var AHAHsaveurl = '$imasRoot';";
            echo 'var unsavedmsg = "'._("You have unrecorded changes.  Are you sure you want to abandon your changes?").'";';
            echo "</script>";
            echo "<script src=\"$imasroot/javascript/mootools.js\"></script>";
            echo "<script src=\"$imasroot/javascript/nested1.js?v=070214\"></script>";
            echo '<p class="margin-ten"><button type="button" onclick="quickviewexpandAll()">'._("Expand All").'</button> ';
            echo '<button type="button" onclick="quickviewcollapseAll()">'._("Collapse All").'</button></p>';

            echo '<ul id=qviewtree class=qview>';
            $quickViewFun = new AppUtility();
            $quickViewFun->quickview($items,0);
            echo '</ul>';
            echo '<p>&nbsp;</p>';
        } else {
            $showItem = new ShowItemCourse();
            $showItem->showItems($items,$folder);
        }

    } else {
        if (($teacherId) && $quickView!='on' && !$isStudent || ($isTutor && ($user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT))) {
            if ($folder == '0') {
                echo '<p class="padding-left-fifteen"><b>Welcome to your course!</b></p>';
                echo '<p class="padding-left-fifteen">To start by copying from another course, use the <a href="#">Course Items: Copy</a> ';
                echo 'link along the left side of the screen.</p><p class="padding-left-fifteen">If you want to build from scratch, use the "Add An Item" pull down below to get started.</p><p>&nbsp;</p>';
            }
            echo ShowItemCourse::generateAddItem($folder,'t',$studview);
        }
    }

    if (($backLink)) {
        echo $backLink;
    }
    if (($useLeftBar && ($teacherId)) || ($useLeftStuBar && !($teacherId))) {
        echo "</div>";
    } else if (!($nocoursenav)) {
        ?>
        <?php
        if ($teacherId) {
            ?>
            <div class=cp>
            <span class=column>
			<?php echo ShowItemCourse::generateAddItem($folder, 'BB',$studview); ?>
            <?php
        }
    }
    if ($firstLoad) {
        echo "<script>document.cookie = 'openblocks-$courseId=' + oblist;\n";
        echo "document.cookie = 'loadedblocks-$courseId=0';</script>\n";
    }
}
?></div></div>

