<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Message';
if ($userRights->rights > 10){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Messages', 'url' => ['/message/message/index?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if ($userRights->rights > 10) { ?>
    <?php echo $this->render('../../instructor/instructor/_toolbarTeacher',['course' => $course]); ?>
    <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
<?php } else {?>

    <?php echo $this->render('../../course/course/_toolbar', ['course' => $course]);?>
    <input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
    <input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
<?php } ?>
<div id="headerviewmsg">
    <h2>Message</h2>
</div>
<div>
    <table class = "msg-view">
        <tbody>
        <tr>
            <td><b>From:</b></td>
            <td><?php echo ucfirst($fromUser->FirstName) . ' ' . ucfirst($fromUser->LastName) ?></td>
        </tr>
        <tr>
            <td><b>Sent:</b></td>
            <td><?php echo date('M d, o g:i a', $messages->senddate) ?></td>
        </tr>
        <tr>
            <td><b>Subject:</b></td>
            <td><?php echo $messages->title ?></td>
        </tr>
        </tbody>
    </table>
</div>
<div>
        <pre>
            <?php echo $messages->message ?>
        </pre>
</div>
<div>
    <?php $sent = $_GET['message'];
    if ($sent != 1) {
        ?>
        <a href="<?php echo AppUtility::getURLFromHome('message', 'message/reply-message?id=' . $messages->id.'&cid='.$course->id); ?>"
           class="btn btn-primary "> Reply</a>&nbsp;
        <a class="btn btn-primary" id="mark-as-unread"> Mark Unread </a>&nbsp;
        <a class="btn btn-primary  btn-danger" id="mark-delete"> Delete</a>&nbsp;
        <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-conversation?id=' . $messages->id . '&message=' . $sent . '&baseid=' . $messages->baseid.'&cid='.$course->id); ?>">
            View Conversation </a>&nbsp;
        <a href="<?php echo AppUtility::getURLFromHome('site','work-in-progress') ?>" id="marked"> Gradebook</a>
    <?php } ?>

    <?php $sent = $_GET['message'];
    if ($sent == 1) { ?>
        <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-conversation?id=' . $messages->id . '&message=' . $sent . '&baseid=' . $messages->baseid.'&cid='.$course->id); ?>">
            View Conversation </a>&nbsp;
    <?php } ?>

</div>