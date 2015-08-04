<?php
use app\components\AppUtility;

$this->title = 'Edit Page';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i>Save Revision</button>
            </div>
        </div>
    </div>
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

<div class="tab-content shadowBox non-nav-tab-item">
    <div class="edit-wiki padding">
        <h3><b>Edit Wiki: <?php echo $wiki->name;?></b></h3>
        <?php if ($numrevisions>0) {
            echo "Last edited by $lasteditedby on $lastedittime.";
        } ?>
        <form method="post" action="edit-page?courseId=<?php echo $course->id ?>&wikiId=<?php echo $wiki->id?>">
            <div class= 'editor'>
                <textarea id='wikicontent' name='wikicontent' style='width: 100%;' rows='30' cols='60'>

                    <?php
                        if(!empty($wikiRevisionData)){
                    foreach($wikiRevisionData as $key => $singleRevision){
                        $wikicontent = $singleRevision->revision;
                        $wikiContent = str_replace(array("\r","\n"),' ', $wikicontent);
                        $wikiContent = preg_replace('/\s+/',' ', $wikicontent);
                        ?>
                        <?php echo $wikiContent;?>
                    <?php
                        }
                    }?>

                </textarea>
                <input type="hidden" name="time" value="<?php echo $singleRevision->time;?>" />
                <input type="hidden" name="stugroupid" value="<?php echo $singleRevision->stugroupid;?>" />
                <input type="hidden" name="userid" value="<?php echo $singleRevision->userid;?>" />
            </div>
        </form>
    </div>
</div>
<script>
    $(document).ready(function () {
        initEditor();
    });
</script>

