<?php
use app\components\AppUtility;
use app\components\AppConstant;
if(isset($grpSetId))
{
    $this->title = $grpSetName;
}elseif($addGrpSet)
{
    $this->title = AppUtility::t('Add Group Set', false);
}elseif($addGrp)
{
    $this->title = AppUtility::t('Add Group ', false);
}elseif($renameGrp)
{
    $this->title = AppUtility::t('Rename Group', false);
}elseif($renameGrpSet)
{
    $this->title = AppUtility::t('Rename Group Set', false);
}
elseif($deleteGrpSet)
{
    $this->title = AppUtility::t('Delete Group Set', false);
}
elseif($deleteGrp)
{
    $this->title = AppUtility::t('Delete Group', false);
}
else
{
    $this->title = AppUtility::t('Manage Student Groups', false);
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php if(isset($grpSetId) || isset($addGrpSet) || isset($addGrp) || isset($renameGrp) || isset($renameGrpSet) || isset($deleteGrpSet)){?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Manage Student Groups', false)], 'link_url' => [AppUtility::getHomeURL()  . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)]]); ?>
    <?php }else{?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL()  . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
    <?php }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
<div style="padding: 20px">
    <div class="group-background-div" style="min-height: 380px;">
        <br>
        <div class="align-groups">
        <?php if(isset($addGrpSet)){?>
            <h4>Add new set of student groups</h4>
            <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&addgrpset=true')?>">
            <p>New group set name: <input name="grpsetname" type="text" /></p>
            <p><input type="submit" value="Create" />
            <input type=button value="Nevermind" class="#" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" /></p>
            </form>
        <?php }elseif(isset($renameGrpSet)){?>
             <h4>Rename student group set</h4>
             <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&renameGrpSet='.$renameGrpSet)?>">
             <p>New group set name: <input name="grpsetname" type="text" value="<?php echo $grpSetName['name'];?>"/></p>
             <p><input type="submit" value="Rename" />
             <input type=button value="Nevermind" class="#" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" /></p>
             </form>
        <?php }elseif(isset($deleteGrpSet)){?>
                <h4>Delete student group set</h4>
                <p>Are you SURE you want to delete the set of student groups <b><?php echo $deleteGrpName ;?></b> and all the groups contained within it?
                <?php if ($used != '')
                {
                        echo '<p>This set of groups is currently used in the assessments, wikis, and/or forums below.  These items will be set to non-group if this group set is deleted</p><p>';
                        echo "$used</p>";
                 } else
                 {
                        echo '<p>This set of groups is not currently being used</p>';
                 }?>
                 <p>
                    <input type=button value="Yes, Delete" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&deleteGrpSet='.$deleteGrpSet.'&confirm=true')?>'" />
                    <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" />
                </p>
        <?php }elseif(isset($renameGrp)){?>
            <h4>Rename student group </h4>
                <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&renameGrp='.$renameGrp)?>">
                <p>New group name:<input name="grpname" type="text" value="<?php echo $currGrpName;?>"/></p>
                <p><input type="submit" value="Rename" />
                    <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId'.$grpSetId)?>'" /></p>
            </form>
        <?php }elseif(isset($deleteGrp)){?>
            <h4>Delete student group</h4>
                <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&deleteGrp='.$deleteGrp.'&confirm=true')?>" >
                <p>Are you SURE you want to delete the student group <b><?php echo $currGrpNameToDlt;?></b>?</p>
                <p>Any wiki page content for this group will be deleted.</p>
                <p><input type="radio" name="delpost" value="1" checked="checked" /> Delete group forum posts
                <input type="radio" name="delpost" value="0" /> Make group forum posts non-group-specific posts</p>
                <p><input type="submit" value="Yes, Delete">
                <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId)?>'" /></p>
                </form>
        <?php }elseif(isset($addGrp)){?>
            <h4>Add new student group</h4>
            <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&addGrp=true')?>">
             <?php if (isset($stuList))
             {
                echo "<input type='hidden' name='stutoadd' value=$stuList />";
             }?>
                <p>New group name: <input name="grpname" type="text" /></p>
                <p><input type="submit" value="Create" />
                <input type=button value='Nevermind' class='' onClick='window.location="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId)?>"' /></p>
                </form>
        <?php }elseif(isset($remove) && isset($grpId)){?>
            <h4>Remove group member</h4>
            <p>Are you SURE you want to remove <b><?php echo $stuNameToBeRemoved;?></b> from the student group <b><?php echo $Stu_GrpName;?></b>?</p>
             <p><input type=button value="Yes, Remove" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&remove='.$remove.'&grpId='.$grpId.'&confirm=true')?>'" />
             <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId)?>'" /></p>
        <?php }elseif(isset($removeAll)){?>
            <h4>Remove ALL group members</h4>
                <p>Are you SURE you want to remove <b>ALL</b> members of the student group <b><?php echo $Stu_GrpName;?></b>?</p>
            <p><input type=button value="Yes, Remove" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&removeall='.$removeAll.'&confirm=true')?>'" />
                <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId)?>'" /></p>
        <?php }elseif(isset($grpSetId)){?>
        <input type="hidden" id="grpSetId"  value="<?php echo $grpSetId;?>">
            <h3>Managing groups in set <?php echo $grpSetName?></h3>
            <div id="myTable">
                <p><button type="button" onclick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&addGrp=true')?>'">Add New Group</button>
                    <?php if(array_sum($hasUserImg)){
                        echo ' <button type="button" onclick="rotatepics(this)" >'.'View Pictures'.'</button><br/>';
                    }?>
                </p>
                <?php if (count($page_Grp)==0)
                {
                    echo '<p>No student groups have been created yet</p>';
                }foreach($page_Grp as $grpId=>$grpName)
                {
                    echo "<b>Group: $grpName</b> | ";
                    echo "<a href='manage-student-groups?cid=$course->id&grpSetId={$grpSetId}&renameGrp={$grpId}'>Rename</a> | ";
                    echo "<a href='manage-student-groups?cid=$course->id&grpSetId={$grpSetId}&deleteGrp={$grpId}'>Delete</a> | ";
                    echo "<a href='manage-student-groups?cid=$course->id&grpSetId={$grpSetId}&removeall={$grpId}'>Remove all members</a>";
                    echo '<ul>';
                    if (count($page_GrpMembers[$grpId])==0)
                    {
                        echo '<li>No group members</li>';
                    }else
                    {
                        foreach($page_GrpMembers[$grpId] as $uid=>$name)
                        {
                            echo '<li>';
                            $imageUrl = $uid . '' . ".jpg";
                            if($hasUserImg[$uid] == AppConstant::NUMERIC_ONE)
                            {
                                if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true)
                                {
                                    echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
                                }
                                else{?>
                                    <img class="circular-profile-image" id="img<?php echo $imgCount ?>"
                                         src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl ?>">
                               <?php }

                            }
                            echo "$name | <a href='manage-student-groups?cid=$course->id&grpSetId={$grpSetId}&remove={$uid}&grpId={$grpId}'>Remove from group</a></li>";
                        }
                    }
                    echo '</ul>';
                 }
                echo '<h3>Students not in a group yet</h3>';
                if (count($page_unGrpStu) > AppConstant::NUMERIC_ZERO)
                {?>
                    <form method='post' action='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&addstutogrp=true')?>'>
                   <?php echo 'With selected, add to group';
                    echo '<select name="addtogrpid">';
                    echo "<option value='--new--'>New Group</option>";
                    foreach ($page_Grp as $grpId=>$grpName)
                    {
                        echo "<option value=$grpId>$grpName</option>";
                    }
                    echo '</select>';
                    echo '&nbsp;<input type="submit" value="Add" class=" btn btn-primary"/>';
                    echo '<ul class="nomark">';
                    foreach ($page_unGrpStu as $grpId=>$grpName)
                    {
                        echo "<li><input type='checkbox' style='text-align: center' name='stutoadd[]' value=$grpId />";
                        if($hasUserImg[$grpId] == AppConstant::NUMERIC_ONE)
                        {
                            $imageUrl = $grpId . '' . ".jpg";

                           if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true)
                            {
                                echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
                            }
                            else
                            {?>

                                <img class="circular-profile-image" id="img"
                                     src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl ?>">
                      <?php }
                        }

                        echo "&nbsp; $grpName</li>";
                    }
                    echo '</ul>';
                    echo '</form>';
                    echo '<p>&nbsp;</p>';
                }
                else
                {
                    echo '<p>None</p>';
                }
                echo '</div>';
                    ?>
        <?php }else{?>
        <h4>Student Group Sets</h4>
        <?php
        if (count($page_groupSets)==0)
        {
            echo '<p>No existing sets of groups</p>';
        } else
        {
            echo '<p>Select a set of groups to modify the groups in that set</p>';
            echo '<table><tbody><tr>';
                foreach ($page_groupSets as $gs)
                {
                        echo "<td><a href='manage-student-groups?cid=$course->id&grpSetId={$gs['id']}'>{$gs['name']}</a></td><td class=small>";
                        echo "<a href='manage-student-groups?cid=$course->id&renameGrpSet={$gs['id']}'>Rename</a> | ";
                        echo "<a href='manage-student-groups?cid=$course->id&copyGrpSet={$gs['id']}'>Copy</a> | ";
                        echo "<a href='manage-student-groups?cid=$course->id&deleteGrpSet={$gs['id']}'>Delete</a>";
                        echo '</td></tr>';
            }
            echo '</body></table>';
        }
        ?>
        <p class="hide-hover"><input type="button" class="btn btn-primary1 btn-color" value="Add new set of groups" onclick="window.location.href='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&addgrpset=ask');?>'"></p>
        <?php } ?>
        </div>
    </div>
</div>
</div>

    <script type="text/javascript">
        $(document).ready(function()
        {
                picshow(0);
        });
        var picsize = 0;
        function rotatepics(el)
        {
            picsize = (picsize+1)%3;
            if (picsize==0) {
                $(el).html("<?php echo AppUtility::t('View Pictures'); ?>");
            } else if (picsize==1) {
                $(el).html("<?php echo AppUtility::t('View Big Pictures'); ?>");
            } else {
                $(el).html("<?php echo AppUtility::t('Hide Pictures'); ?>");
            }
            picshow(picsize);
        }
        function picshow(size)
        {
            if (size == 0)
            {
                els = document.getElementById("myTable").getElementsByTagName("img");

                for (var i = 0; i < els.length; i++) {
                    els[i].style.display = "none";
                }
            } else
            {
                els = document.getElementById("myTable").getElementsByTagName("img");

                for (var i = 0; i < els.length; i++) {
                    els[i].style.display = "inline";
                    if (size == 2) {
                        els[i].style.width = "100px";
                        els[i].style.height = "100px"
                    }
                    if (size == 1) {
                        els[i].style.width = "50px";
                        els[i].style.height = "50px";
                    }
                }
            }
        }
    </script>