<?php
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = $wikiName;
?>

<div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<?php if ($groupId > AppConstant::NUMERIC_ZERO) {
    echo "<p>Group: $groupName</p>";
}
if ($inConflict) {
    ?>
    <p><span style="color:#f00;">Conflict</span>.  Someone else has already submitted a revision to this page since you opened it.
        Your submission is displayed here, and the recently submitted revision has been loaded into the editor so you can reapply your
        changes to the current version of the page</p>

    <div class="editor wikicontent"><?php echo $wikicontent; ?></div>
<?php
}

if (isset($lastEditedBy)) {
    echo "<p>Last Edited by $lastEditedBy on $lastEditTime</p>";
}
?>
<form method="post" action="edit-page?courseId=<?php echo $courseId ?>&wikiId=<?php echo $id?>">
    <input type="hidden" name="baserevision" value="<?php echo $revisionId;?>" />
    <div class="editor">
        <textarea cols=60 rows=30 id="wikicontent" name="wikicontent" style="width: 100%">
            <?php echo htmlentities($revisionText);?></textarea>
    </div>

    <div class=submit><input type=submit value="<?php echo _("Save Revision");?>"></div>
</form>

