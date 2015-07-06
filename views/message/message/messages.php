<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;
if($isNewMessage == AppConstant::NUMERIC_ONE){
    $this->title = 'New Messages';
}else{
    $this->title = 'Messages';
}
if ($userRights->rights > AppConstant::STUDENT_RIGHT){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = $this->title;
?>
<!-- DataTables CSS -->
<div>
    <?php if ($userRights->rights > AppConstant::STUDENT_RIGHT) { ?>
        <?php echo $this->render('../../instructor/instructor/_toolbarTeacher',['course' => $course]); ?>
        <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
        <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
        <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <?php } else {?>

        <?php echo $this->render('../../course/course/_toolbar', ['course' => $course]);?>
        <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
        <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
        <input type="hidden" class="msg-type" value="<?php echo $isNewMessage ?>">
    <?php } ?>

</div>
<div class="message-container">
    <div><p>
            <a href="<?php echo AppUtility::getURLFromHome('message', 'message/send-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?>"
               class="btn btn-primary">Send New Message</a>
            | <a id="limit-to-tag-link" href="#">Limit to Tagged</a> <a id="show-all-link" href="#">Show All</a> | <a
                href="<?php echo AppUtility::getURLFromHome('message', 'message/sent-message?cid=' . $course->id . '&userid=' . $course->ownerid); ?>">Sent
                Messages</a>
            |         <input type="button"  id='imgtab' class="btn btn-primary" value="Pictures" onclick="rotatepics()" ></p>
    </div>
    <div>
        <p><span class="select-text-margin pull-left"><b>Filter By Course :</b></span>
        <span class="col-md-3">
        <select name="seluid" class="show-course form-control" id="course-id">
            <option value="0">All Courses</option>
        </select>
        </span> <span class="select-text-margin pull-left"><b>By Sender :</b></span>
        <span class="col-md-3">
        <select name="seluid" class="show-users form-control" id="user-id">
            <option value="0">All Users</option>
        </select>
        </span></p>
    </div>
    <br><br>
    <div>
        <p>Check: <a id="check-all-box" class="check-all" href="#">All</a>/<a id="uncheck-all-box" class="uncheck-all"
                                                                              href="#">None</a>
            With Selected:
            <a class="btn btn-primary " id="mark-as-unread">Mark as Unread</a>
            <a class="btn btn-primary" id="mark-read">Mark as Read</a>
            <a class="btn btn-primary  btn-danger" id="mark-delete">Delete</a>
    </div>
    <div class="message-div"></div>
</div>