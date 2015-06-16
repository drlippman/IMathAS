<?php
use app\components\AppUtility;
echo $this->render('_toolbar',['course'=> $course]);
//AppUtility::dump($course->id);?>
<div id="wikiName">
    <h2><?php echo $wiki->name; ?></h2>
   <input type="hidden" class="wiki-id" value="<?php echo $wiki->id;?>">
   <input type="hidden" class="course-id" value="<?php echo $course->id;?>">

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
    echo '<span id="revcontrol" style="display:none;"><br/>
    Revision history:
    <a href="#" name="first-link" id="first" data-var="1">First</a>
    <a id="older" href="#" onclick="seehistory(1); return false;">Older</a> ';
    echo '<a id="newer" class="grayout" href="#" onclick="seehistory(-1); return false;">Newer</a>
    <a href="#" name="last-link" class="grayout" id="last" data-var="2">Last</a>
    <input type="button" id="showrev" value="Show Changes" onclick="showrevisions()" />';
}
?>
<div class="editor">
    <span>
        <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/edit-page?courseId=' .$course->id .'&wikiId=' .$wiki->id ); ?>"
           class="btn btn-primary btn-sm">Edit this page</a></span>
        <?php foreach($wikiRevisionData as $key => $singleWikiRevision) { ?>
            <div id="wikicontent" class="wikicontent" ><input type="text" value="" />
                <?php $text = $singleWikiRevision->revision; ?>
                    <p><?php echo $text; ?></p>
            </div>
    <?php }?>
</div>
<script>
    $(document).ready(function(){
        $(function() {
            $("#prevrev").click(function() {
                $("#revcontrol").toggle();
            });
        });
        getFirstLastData();
    });

    /**
     * to get selected wiki's first data
     */
    function getFirstLastData(){
        $("a[name=first-link]").on("click", function ()
        {
            var firstVar = $(this).attr("data-var");
            var wikiId = $(".wiki-id").val();
            var courseId = $(".course-id").val();
            var firstData = { firstVar : firstVar, wikiId : wikiId, courseId : courseId };
            jQuerySubmit('get-first-last-data-ajax', firstData, 'getFirstLastSuccess');
        });
    }

    function getFirstLastSuccess(response)
    {
       var result = JSON.parse(response);
        console.log(result);
        if(result.status == 0){
        var wikiData = result.data;
            alert('hi');
            $('#wikicontent').val(wikiData);
//alert(wikiData.wikiRevisionSortedById.revision));
        }
    }
</script>