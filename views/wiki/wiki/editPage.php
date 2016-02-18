<?php
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = $wikiName;
?>

<div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'View Wiki',''], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id,AppUtility::getHomeURL().'wiki/wiki/show-wiki?courseId='.$course->id."&wikiId=".$id], 'page_title' => $this->title]); ?>
</div>
<div class = "title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo "Edit Wiki: ".$this->title ?></div>
        </div>
    </div>
</div>

<?php if ($groupId > AppConstant::NUMERIC_ZERO) {
    echo "<p style='color: #ffffff'>Group: $groupName</p>";
}
if ($inConflict) {
    ?>
    <div class="col-md-12 col-sm-12"><span style="color:#f00;"><?php AppUtility::t('Conflict')?></span>.<?php AppUtility::t('Someone else has already submitted a revision to this page since you opened it.
        Your submission is displayed here, and the recently submitted revision has been loaded into the editor so you can reapply your
        changes to the current version of the page')?></div>
    <div class="editor wikicontent"><?php echo $wikicontent; ?></div>
<?php
}


if (isset($lastEditedBy)) {
    echo "<p class='subheadings'>Last Edited by $lastEditedBy on $lastEditTime</p>";
}
?>
<div class="item-detail-content">
    <?php if($user['rights'] > 10) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'course']);
    } elseif($user['rights'] == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'course', 'userId' => $currentUser]);
    }
    ?>
</div>

<form method="post" action="edit-page?courseId=<?php echo $courseId ?>&wikiId=<?php echo $id?>&grp=<?php echo $groupId?>">
    <input type="hidden" name="baserevision" value="<?php echo $revisionId;?>" />
    <div class="editor">
        <textarea cols=60 rows=30 id="wikicontent" name="wikicontent" style="width: 100%">
            <?php echo filter($revisionText);?></textarea>
    </div><br/>

  <div class="header-btn floatleft">
        <button class="btn btn-primary page-settings submit" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Save Revision' ?></button>
    </div>
</form>

