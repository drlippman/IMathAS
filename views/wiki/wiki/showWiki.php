<?php
use app\components\AppUtility;
echo $this->render('_toolbar',['course'=> $course]);
//AppUtility::dump($course->id);?>
<script type="text/css">
    textarea {
        color: red! important;
        outline: none! important;
        border: transparent! important;
    }
</script>
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
    <textarea id='wikicontent' name='wikicontent' style='width: 100% '>
                <?php $text = $singleWikiRevision->revision; ?>
                    <?php echo $text; ?>
    </textarea>
    <?php }?>
</div>
<script>
    $(document).ready(function(){
        $(function() {
            $("#prevrev").click(function() {
                $("#revcontrol").toggle();
            });
        });
        getLastData();
        getFirstData();
    });

    /**
     * to get selected wiki's last data
     */
    function getLastData(){
        $("a[name=last-link]").on("click", function ()
        {
            var lastVar = $(this).attr("data-var");
            var wikiId = $(".wiki-id").val();
            var courseId = $(".course-id").val();
            var lastData = { lastVar : lastVar, wikiId : wikiId, courseId : courseId };
            jQuerySubmit('get-last-data-ajax', lastData, 'getLastSuccess');
        });
    }
    /**
     * to get selected wiki's first data
     */
    function getFirstData(){
        $("a[name=first-link]").on("click", function ()
        {
            var firstVar = $(this).attr("data-var");
            var wikiId = $(".wiki-id").val();
            var courseId = $(".course-id").val();
            var firstData = { firstVar : firstVar, wikiId : wikiId, courseId : courseId };
            jQuerySubmit('get-first-data-ajax', firstData, 'getFirstSuccess');
        });
    }

    /**
     *  last wiki's response
     */
    function getLastSuccess(response)
    {
       var result = JSON.parse(response);
        if(result.status == 0){
        var wikiData = result.data;
            $.each(wikiData, function(index, wikiDataDetails)
            {
               var revision = wikiDataDetails.revision;
                $('#wikicontent').val(revision);
            });

        }
    }

    /**
     *  first wiki's response
     */
    function getFirstSuccess(response)
    {
        var result = JSON.parse(response);
        console.log(result);
//        if(result.status == 0){
//            var wikiData = result.data;
//            $.each(wikiData, function(index, wikiDataDetails)
//            {
//                var revision = wikiDataDetails.revision;
//                $('#wikicontent').val(revision);
//            });
//
//        }
    }
</script>