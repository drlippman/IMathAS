<?php
$this->title = 'Add New Thread';
$this->params['breadcrumbs'][] = $this->title;
use app\components\AppUtility;
?>
<div class="">
    <h3><b>Add Thread - <?php echo $forumName->name;?></h3>
    <br><br>
    <div>
        <div class="col-md-1"><b>Subject</b></div>
        <div class="col-md-8"><input class="subject" type="text"></div>
    </div>
    <br><br><br>
    <div>
        <div class="col-md-1"><b>Message</b></div>
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='200'></textarea></div></div><br>"; ?>
    </div>
    <div class="col-lg-offset-1 col-md-8">
        <br>
        <input type="hidden" id="userId" value="<?php echo $userId; ?>">
        <input type="hidden" id="forumId" value="<?php echo $forumName->id; ?>">
        <input type="hidden" id="courseId" value="<?php echo $courseid; ?>">
        <a class="btn btn-primary" id="addthread">Post Thread</a>
    </div>
</div>

