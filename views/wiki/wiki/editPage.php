<?php
$this->title = 'Edit Page';
$this->params['breadcrumbs'][] = $this->title;
use app\components\AppUtility;
?>
<?php
if(!empty($wikiRevisionData))
{
$lasteditedby = $userData->FirstName.',' .$userData->LastName;
foreach($wikiRevisionData as $key => $singleData) {
    $time = $singleData->time;
    $lastedittime = AppUtility::tzdate("F j, Y, g:i a", $time);
    $numrevisions = $singleData->id;
}
}?>

<div class="edit-wiki">
    <h3><b>Edit Wiki: <?php echo $wiki->name;?></b></h3>
    <?php if ($numrevisions>0) {
        echo "Last edited by $lasteditedby on $lastedittime.";
    } ?>
    <form method="post" action="edit-page?courseId=<?php echo $course->id ?>&wikiId=<?php echo $wiki->id?>">
        <div class= 'editor'>
            <textarea id='wikicontent' name='wikicontent' style='width: 100%;' rows='30' cols='60'>

                <?php foreach($wikiRevisionData as $key => $singleRevision){
                    $wikicontent = $singleRevision->revision;
                    $wikiContent = str_replace(array("\r","\n"),' ', $wikicontent);
                    $wikiContent = preg_replace('/\s+/',' ', $wikicontent);
                    ?>
                    <?php echo $wikiContent;?>
                <?php }?>

            </textarea>
            <input type="hidden" name="time" value="<?php echo $singleRevision->time;?>" />
            <input type="hidden" name="stugroupid" value="<?php echo $singleRevision->stugroupid;?>" />
            <input type="hidden" name="userid" value="<?php echo $singleRevision->userid;?>" />
        </div>
        <div class="col-lg-offset-1 col-md-8">
            <input type="submit" class="btn btn-primary" value="Save Revision">
        </div>
    </form>
</div>

<script>
    $(document).ready(function () {
        initEditor();
    });
</script>

