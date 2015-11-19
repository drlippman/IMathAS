<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Unenroll Students', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php
    if($gradebook == AppConstant::NUMERIC_ONE) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid='.$course->id]]);
    }else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id]]);
    }
    ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]);?>
</div>
<div class="tab-content shadowBox"">
<?php
if($gradebook != AppConstant::NUMERIC_ONE) {
    echo $this->render("_toolbarRoster", ['course' => $course]);
}
?>
<div class="col-md-12 col-sm-12 padding-left-two-em padding-top-bottom-one-pt-five-em">
    <?php
    if($gradebook == AppConstant::NUMERIC_ONE) {
        echo "<form method=post action=\"unenroll?cid={$course->id}&uid={$studentId}&confirmed=true&gradebook=1\">";
    } else{
        echo "<form method=post action=\"unenroll?cid={$course->id}&uid={$studentId}&confirmed=true\">";
    }
            if($studentId == 'all'){
    ?>
                <p><b style="color:red"><?php AppUtility::t('Warning!')?></b>: <?php AppUtility::t('This will delete ALL course data about these students.  This action')?> <b><?php AppUtility::t('cannot be undone')?></b>.
                    <?php AppUtility::t('If you have a student who isn\'t attending but may return, use the Lock Out of course option instead of unenrolling them.')?></p>
                <p><?php AppUtility::t('Are you SURE you want to unenroll ALL students?')?></p>
    <ul>
        <?php
        foreach ($students as $student) {
            echo " <li>".ucfirst($student['LastName']).", ".ucfirst($student['FirstName'])." (".$student['SID'].")</li>";
            $arr[] = $student['id'];
        }
        ?>
    </ul>
    <p><?php AppUtility::t('This will also clear all regular posts from all class forums')?></p>
    <p><input type=checkbox name="removeoffline" value="1" /> <?php AppUtility::t('Also remove all offline grade items from gradebook?')?>

    </p>
    <p><input type=checkbox name="removewithdrawn" value="1" checked="checked"/> <?php AppUtility::t('Also remove any withdrawn questions?') ?>

    </p>
    <p><input type=checkbox name="usereplaceby" value="1" checked="checked"/> <?php AppUtility::t('Also use any suggested replacements for old questions?')?>

    </p>
    <p><?php AppUtility::t('Also remove wiki revisions: ')?> <input type="radio" name="delwikirev" value="1" /> <?php AppUtility::t('All wikis')?>,
        <input  type="radio" name="delwikirev" value="2" checked="checked" /> <?php AppUtility::t('Group wikis only')?>
    </p>
            <?php }else if($studentId == 'selected'){?>
                <p><b style="color:red"><?php AppUtility::t('Warning')?>!</b>: <?php AppUtility::t('This will delete ALL course data about these students.  This action ')?><b><?php AppUtility::t('cannot be undone')?></b>.
                    <?php AppUtility::t('If you have a student who isn\'t attending but may return, use the Lock Out of course option instead of unenrolling them.')?></p>
                <p><?php AppUtility::t('Are you SURE you want to unenroll the selected students?')?></p>
                <ul>
                    <?php
                    foreach ($students as $student) {
                        echo " <li>".ucfirst($student['LastName']).", ".ucfirst($student['FirstName'])." (".$student['SID'].")</li>";
                        $arr[] = $student['id'];
                    }
                    ?>
                </ul>
                <?php if($delForumMsg == 1){?>
                    <p><?php AppUtility::t('Also delete ')?><b style="color:red;"><?php AppUtility::t('ALL')?></b><?php AppUtility::t(' forum posts by ALL students (not just the selected ones)? ')?><input type=checkbox name="delforumposts"/></p>
                <?php }?>
                <?php if($delWikiMsg == 1){?>
                    <p><?php AppUtility::t('Also delete ')?><b style="color:red;"><?php AppUtility::t('ALL')?></b><?php AppUtility::t(' wiki revisions')?>:
                        <input type="radio" name="delwikirev" value="0" checked="checked" /><?php AppUtility::t('No')?>,
                        <input type="radio" name="delwikirev" value="1" /><?php AppUtility::t('Yes, from all wikis,')?>
                        <input type="radio" name="delwikirev" value="2" /><?php AppUtility::t('Yes, from group wikis only')?></p>
                        <?php }
                ?>
            <?php } ?>
    <p>
        <?php $studentData = implode(',', $arr); ?>
        <input type="hidden" name="studentData" value="<?php echo $studentData ?>"/>
        <span class="padding-right-one-em">
            <input type=submit class="secondarybtn" value="<?php AppUtility::t('Unenroll')?>">
        </span>
        <span class="padding-right-one-em">
            <input type=submit name="lockinstead" value="<?php AppUtility::t('Lock Students Out Instead')?>">
        </span>
        <?php
        if($gradebook == AppConstant::NUMERIC_ONE) {
            echo "<input type=button value='".AppUtility::t('Nevermind', false)."' class=\"secondarybtn\" onclick=\"window.location='".AppUtility::getHomeURL()."gradebook/gradebook/gradebook?cid=$course->id'\">";
    }else{
            echo "<input type=button value='".AppUtility::t('Nevermind', false)."' class=\"secondarybtn\" onclick=\"window.location='".AppUtility::getHomeURL()."roster/roster/student-roster?cid=$course->id'\">";
        }
        ?>
    </p>
    </form>
</div>
</div>
