<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = 'New Message';
$this->params['breadcrumbs'][] = $this->title;
?>
<?php echo $this->render('../../instructor/instructor/_toolbarTeacher'); ?>

<div class="">
    <h2><b>Reply</h2>
    <input type="hidden" class="send-msg" value="<?php echo $messages->courseid ?>">
    <input type="hidden" class="msg-sender" value="<?php echo $messages->msgto ?>">
    <input type="hidden" class="msg-sender" value="<?php echo $messages->msgfrom ?>">
    <div class="drop-down">
        <div class="col-md-1"><b>To</b></div>
        <div class="col-md-11"><?php echo ucfirst($fromUser->FirstName).' '.ucfirst($fromUser->LastName); ?>&nbsp;&nbsp;<a href="#">email</a>&nbsp;|&nbsp;<a href="#">gradebook</a></div>
    </div>
    <br><br>
    <div class="drop-down">
        <div class="col-md-1"><b>Sent</b></div>
        <div class="col-md-8"><?php echo date('M d, o g:i a' ,$messages->senddate) ?></div>
    </div>
    <br><br>
    <div>
        <div class="col-md-1"><b>Subject</b></div>
        <div class="col-md-8"><input class="textbox subject" type="text" value="Re: <?php echo $messages->title ?>"></div>
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
<script>
    $(document).ready(function () {
        tinymce.init({
            selector: "textarea",
            toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
        });
        $("#msg-btn").click(function()
        {
            tinyMCE.triggerSave();
            var cid = $(".send-msg").val();
            var sender = $(".msg-sender").val();
            var receiver = $("#seluid").val();
            var subject = $(".subject").val();
            var body = $("#message").val();
            jQuerySubmit('confirm-message',{cid: cid , sender: sender, receiver: receiver, subject: subject, body: body},'sendMessage');
        });

    });

    function sendMessage(response)
    {
        var cid = $(".send-msg").val();
        console.log(response);
        var result = JSON.parse(response);
        if(result.status == 0)
        {
            window.location = "index?cid="+cid;
        }
    }

</script>