<?php
$this->title = 'Edit Page';
$this->params['breadcrumbs'][] = $this->title;
use app\components\AppUtility;
?>
<div class="edit-wiki">
    <h2><b>Edit Wiki: <?php echo $wiki->name;?></h2>
    <br><br>

    <form method="post" action="show-wiki?courseId=<?php echo $course->id ?>&wikiId=<?php echo $wiki->id?>">
        <div>
            <?php echo "<div class='left col-md-11'><div class= 'editor'>
            <textarea id='wiki-edit-body' name='message' style='width: 100%;' rows='20' cols='200'></textarea></div></div><br>"; ?>
            <input type="hidden" value="<?php echo $wikiRevision->wikiid ?>">
            <input type="hidden" value="<?php echo $wikiRevision->userid ?>">
            <input type="hidden" value="<?php echo $wikiRevision->time ?>">
            <input type="hidden" value="<?php echo $wikiRevision->revision ?>">
        </div>
        <div class="col-lg-offset-1 col-md-8">
            <input type="submit" class="btn btn-primary" value="Save Revision">
        </div>
    </form>
</div>

<script>
    $(document).ready(function () {
        tinymce.init({
            selector: "textarea",
            toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
        });
    });
</script>