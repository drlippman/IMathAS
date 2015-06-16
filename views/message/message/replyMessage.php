<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'Reply Message';
if ($userRights->rights > AppConstant::STUDENT_RIGHT){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Messages', 'url' => ['/message/message/index?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if ($userRights->rights > AppConstant::STUDENT_RIGHT) { ?>
    <?php echo $this->render('../../instructor/instructor/_toolbarTeacher',['course' => $course]); ?>
    <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
<?php } else {?>

    <?php echo $this->render('../../course/course/_toolbar', ['course' => $course]);?>
    <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
<?php } ?>
<div class="">                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          
    <h2><b>Reply</h2>
    <input type="hidden" class="send-msg" value="<?php echo $messages->courseid ?>">
    <input type="hidden" class="msg-receiver" value="<?php echo $messages->msgto ?>">
    <input type="hidden" class="msg-sender" value="<?php echo $messages->msgfrom ?>">
    <input type="hidden" class="base-id" value="<?php echo $messages->baseid ?>">
    <input type="hidden" class="parent-id" value="<?php echo $messages->id ?>">
    <input type="hidden" class="is-replied" value="<?php echo $messages->replied ?>">

    <div class="drop-down">
        <div class="col-md-1"><b>To</b></div>
        <div class="col-md-11"><?php echo ucfirst($fromUser->FirstName) . ' ' . ucfirst($fromUser->LastName); ?>&nbsp;&nbsp;<a
                href="#">email</a>&nbsp;|&nbsp;<a href="#">gradebook</a></div>
    </div>
    <br><br>

    <div class="sent-date">
        <div class="col-md-1"><b>Sent</b></div>
        <div class="col-md-8"><?php echo date('M d, o g:i a', $messages->senddate) ?></div>
    </div>
    <br><br>

    <div>
        <div class="col-md-1"><b>Subject</b></div>
        <div class="col-md-8"><input class="textbox subject" type="text" value="Re: <?php echo $messages->title ?>">
        </div>
    </div>
    <br><br><br>

    <div>
        <div class="col-md-1"><b>Message</b></div>
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='200'></textarea></div></div><br>"; ?>
    </div>
    <div class="col-lg-offset-1 col-md-8">
        <br>
        <a class="btn btn-primary" id="msg-btn">Send Message</a>
    </div>
</div>