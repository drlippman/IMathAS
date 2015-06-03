<?php use app\components\AppUtility;?>
<form id="myForm" method="post" action="move-thread?forumId=<?php echo $forumId ?>&courseId=<?php echo $courseId ?>&threadId=<?php echo $threadId ?>">

<input type="hidden" id="thread-id" value="<?php echo $threadId ?>" >

<div>
    <h3>OpenMath - Move Thread</h3>

    <p>What do you want to do?<br/>

        <input type="radio" checked name="movetype" value="0" onclick="select(0)"/> Move thread to different forum<br/>
        <input type="radio" name="movetype" value="1" onclick="select(1)"/> Move post to be a reply to a thread

</div>



<div id="move-forum">Move to forum:
    <div>
        <?php
        foreach ($forums as $forum) { ?>
            <input type="radio" id="<?php echo $forum['forumId'] ?>" name="forum-name"
                   value="<?php echo $forum['forumId'] ?>"><?php echo $forum['forumName'] ?><br>

        <?php } ?>
    </div>
</div>


<div id="move-thread">Move to thread:
    <div>

        <?php
        foreach ($threads as $thread) { ?>
            <?php

            if ($thread['forumiddata'] == $forumId && $thread['threadId'] != $threadId) { ?>
             <input type="radio" name="thread-name"><?php echo $thread['subject']?><br>
            <?php }
        } ?>
    </div>
</div>


    <input type=submit class="btn btn-primary" id="move-button" value="Move">
    <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('forum/forum', 'thread?cid='.$courseId.'&forumid'.$forumId)  ?>">Cancel</a>
</form>

<script type="text/javascript">
$(document).ready(function() {
    $('#move-forum').show();
    $('#move-thread').hide();

    $('#myForm input').on('change', function() {
      var v=$('input[name="movetype"]:checked', '#myForm').val();

            if (v==0) {
                $('#move-forum').show();
                $('#move-thread').hide();
            }
            if (v==1) {
                $('#move-forum').hide();
                $('#move-thread').show();
            }


    $("#move-button").click(function () {
        //var thread_id =  $( "#thread-id" ).val();
        var forum_id =  $('input[name="forum-name"]:checked', '#myForm').val();

    });
    });
});
</script>