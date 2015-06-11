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
}
?>
</span>
<?php
if ($numrevisions>1) {
    $last = $numrevisions-1;
    echo '<span id="prevrev"><input type="button" value="Show Revision History"/></span>';
    echo '<span id="revcontrol" style="display:none;"><br/>Revision history: <a href="#" id="first" onclick="jumpto(1)">First</a> <a id="older" href="#" onclick="seehistory(1); return false;">Older</a> ';
    echo '<a id="newer" class="grayout" href="#" onclick="seehistory(-1); return false;">Newer</a> <a href="#" class="grayout" id="last" onclick="first()">Last</a> <input type="button" id="showrev" value="Show Changes" onclick="showrevisions()" />';
}
?>
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
<script>
    $(document).ready(function(){
        $(function() {
            $("#prevrev").click(function() {
//                $("#prevrev").toggle();
                $("#revcontrol").toggle();
            });
        });

    });
    function first()
    {
//        var x = $(".wikicontent").text;
//        alert(x);
        $("#last").click(function(){
            $(".wikicontent").hide();
        });
    }

</script>