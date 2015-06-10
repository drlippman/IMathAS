<?php
$this->title = 'Edit Page';
$this->params['breadcrumbs'][] = $this->title;
use app\components\AppUtility;
AppUtility::dump($userData);
?>
<div class="edit-wiki">
    <h3><b>Edit Wiki: <?php echo $wiki->name;?></h3>
    <br><br>

    <form method="post" action="show-wiki?courseId=<?php echo $course->id ?>&wikiId=<?php echo $wiki->id?>">
            <input type="hidden" name="baserevision" value="<?php echo $wikiRevisionData->id;?>" />
            <div class= 'editor'>
                <textarea id='wikicontent' name='wikicontent' style='width: 100%;' rows='30' cols='60'>
                    <?php foreach($wikiRevisionData as $key => $singleRevision){?>
                        <?php echo htmlentities($singleRevision->revision);?>
                    <?php }?>
                </textarea>
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

