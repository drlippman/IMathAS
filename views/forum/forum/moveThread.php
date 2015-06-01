


<div>
<h3>OpenMath - Move Thread</h3>
          <p>What do you want to do?<br/>
              <input type="radio" name="movetype" value="0" checked="checked" onclick="toggleforumselect(0)"/> Move thread to different forum<br/>
              <input type="radio" name="movetype" value="1" onclick="toggleforumselect(1)"/> Move post to be a reply to a thread</p>
<div id="fsel" >Move to forum:<br/>

    <?php

    foreach ($forums as $forum) { ?>

        <input type="radio" id='"<?php echo $forum['forumId']?>"' name="<?php echo $forum['forumName']?>"><?php echo $forum['forumName']?><br>

    <?php }?>

<div id="tsel" style="display:none;">Move to thread:<br/>
     </div>



    <input type=button value="Nevermind" class="secondarybtn" onClick="window.location='thread.php?page=1&cid=2&forum=2'"></p>
</div>
    <div class="footerwrapper"></div>
