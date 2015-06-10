<?php
use app\components\AppUtility;
echo $this->render('_toolbar',['course'=> $course]);
//AppUtility::dump($userData->FirstName);?>
<div id="wikiName">
    <h2><?php echo $wiki->name; ?></h2>
</div>
<?php
$lasteditedby = $userData->FirstName.',' .$userData->LastName;
foreach($wikiRevisionData as $key => $singleData) {
$time = $singleData->time;

$lastedittime = AppUtility::tzdate("F j, Y, g:i a", $time);
$numrevisions = $singleData->id;
}?>

<p><span id="revisioninfo">Revision <?php echo $numrevisions; ?>
       <?php if ($numrevisions>0) {
	echo ".  Last edited by $lasteditedby on $lastedittime.";
} ?>
</span>
<div class="editor">
    <span>
        <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/edit-page?courseId=' .$course->id .'&wikiId=' .$wiki->id ); ?>"
           class="btn btn-primary btn-sm">Edit this page</a></span>
        <?php foreach($wikiRevisionData as $key => $singleWikiRevision) { ?>
            <div id="wikicontent" class="wikicontent">
                <?php $text = $singleWikiRevision->revision; ?>
                    <p><?php echo $text; ?></p>
            </div>
    <?php }?>
</div>