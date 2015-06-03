<?php
$this->title = 'Reply';
$this->params['breadcrumbs'][] = $this->title;
use app\components\AppUtility;
?>
<div class="">
    <h2><b>Post Reply</h2>
   <br><br>
    <div>
        <div class="col-md-1"><b>Subject</b></div>
        <div class="col-md-8"><input class="textbox subject" type="text" value="<?php echo $reply[0]['subject'] ?>">
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
        <a class="btn btn-primary" id="msg-btn">Post Reply</a>
    </div>
</div>

<script>
    $(document).ready(function () {
        tinymce.init({
            selector: "textarea",
            toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
        });

    });
 </script>