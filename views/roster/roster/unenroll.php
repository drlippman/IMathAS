<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Unenroll Students', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php
    if($gradebook == AppConstant::NUMERIC_ONE) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid='.$course->id]]);
    }else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id]]);
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
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]);?>
</div>
<div class="tab-content shadowBox"">
<?php
if($gradebook != AppConstant::NUMERIC_ONE) {
    echo $this->render("_toolbarRoster", ['course' => $course]);
}
?>
<div class="padding15">
    <?php
            if($studentId == 'all'){
    ?>
                <p><b style="color:red">Warning!</b>: This will delete ALL course data about these students.  This action <b>cannot be undone</b>.
                    If you have a student who isn't attending but may return, use the Lock Out of course option instead of unenrolling them.</p>
                <p>Are you SURE you want to unenroll ALL students?</p>
    <?php }?>
    <ul>
        <?php
        foreach ($students as $student) {
            echo " <li>".ucfirst($student['LastName']).", ".ucfirst($student['FirstName'])." (".$student['SID'].")</li>";
        }
        ?>
    </ul>
    <p>This will also clear all regular posts from all class forums</p>
    <p><input type=checkbox name="removeoffline" value="1" /> Also remove all offline grade items from gradebook?

    </p>
    <p><input type=checkbox name="removewithdrawn" value="1" checked="checked"/> Also remove any withdrawn questions?

    </p>
    <p><input type=checkbox name="usereplaceby" value="1" checked="checked"/> Also use any suggested replacements for old questions?

    </p>
    <p>Also remove wiki revisions: <input type="radio" name="delwikirev" value="1" />All wikis,
        <input  type="radio" name="delwikirev" value="2" checked="checked" />Group wikis only
    </p>
    <p>
        <input type=submit class="secondarybtn" value="Unenroll">
        <input type=submit name="lockinstead" value="Lock Students Out Instead">
        <?php
//        if ($calledfrom=='lu') {
            echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='listusers.php?cid=$cid'\">";
//        } else if ($calledfrom=='gb') {
//            echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}'\">";
//        }
        ?>
    </p>
</div>
</div>
