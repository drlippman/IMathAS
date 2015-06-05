<?php
$this->title = 'Reply';
$this->params['breadcrumbs'][] = $this->title;
use app\components\AppUtility;
?>
<div class="replypost">
    <input type="hidden" class="forumid" value="<?php echo $forumid ?>">
    <input type="hidden" class="courseid" value="<?php echo $courseid ?>">
    <input type="hidden" class="threadid" value="<?php echo $threadid ?>">
    <h2><b>Post Reply</h2>
    <br><br>
    <div>
        <div class="col-md-1"><b>Subject</b></div>
        <div class="col-md-8"><input class="textbox subject" type="text" value="Re:<?php echo $reply[0]['subject'] ?>">
        </div>
    </div>
    <br><br><br>
    <div>
        <div class="col-md-1"><b>Message</b></div>
        <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea id='postreply' name='post-reply' style='width: 100%;' rows='20' cols='200'></textarea></div></div><br>"; ?>
    </div>
    <div class="col-lg-offset-1 col-md-8">
        <br>
        <a class="btn btn-primary" id="reply-btn">Post Reply</a>
    </div>
</div>

<script>
    $(document).ready(function () {
        tinymce.init({
            selector: "textarea",
            toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
        });

        $("#reply-btn").click(function()
        {

            tinyMCE.triggerSave();
            var courseid = $(".courseid").val();
            var forumid = $(".forumid").val();
            var threadid = $(".threadid").val();
            var subject = $(".subject").val();
            var body = $("#postreply").val();
            var replyDetails = {couserid:courseid,forumid:forumid,threadid:threadid,subject:subject,body:body};
            jQuerySubmit('reply-post-ajax',replyDetails,'replyPost');
        });

    });

    function replyPost(response)
    {
        console.log(response)
        var courseId = $(".courseid").val();
        var forumId = $(".forumid").val();
        var threadId = $(".threadid").val();

        var result = JSON.parse(response);
        if(result.status == 0)
        {
            window.location = "post?courseid="+courseId+"&forumid="+forumId+"&threadid="+threadId;
        }
    }


</script>